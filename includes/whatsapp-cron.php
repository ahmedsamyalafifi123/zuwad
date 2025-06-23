<?php
/**
 * WhatsApp Schedule Notification Functions
 * Prepared for direct cron job execution
 */

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Function to be called directly by Hostinger cron
function zuwad_run_schedule_check() {
    // Ensure WordPress environment is loaded
    require_once('/domains/zuwad-academy.com/public_html/system/wp-load.php');

    // Log the start of the cron job
  //  error_log('==================== HOSTINGER CRON SCHEDULE CHECK START ====================');
  //  error_log('Timestamp: ' . current_time('mysql'));
  //  error_log('WordPress Site: ' . get_bloginfo('url'));

    // Call the main schedule checking function
    check_schedules_and_send_reminders();

  //  error_log('==================== HOSTINGER CRON SCHEDULE CHECK END ====================');
}

// Function to send WhatsApp message
function send_whatsapp_message($chatId, $message, $authorization) {
    // Prepare the request body
    $body = json_encode([
        'chatId' => $chatId,
        'message' => $message
    ]);

    // Prepare the request arguments
    $args = [
        'body'    => $body,
        'headers' => [
            'Accept'         => 'application/json',
            'Authorization' => 'Bearer ' . $authorization,
            'Content-Type'   => 'application/json',
        ],
        'timeout' => 30 // Set a timeout to prevent hanging
    ];

    // Send the request using WordPress HTTP API
    $response = wp_remote_post('https://waapi.app/api/v1/instances/42576/client/action/send-message', $args);

    // Check for errors
    if (is_wp_error($response)) {
        // Log the error
      //  error_log('WhatsApp Message Send Error: ' . $response->get_error_message());
        throw new Exception('Failed to send WhatsApp message: ' . $response->get_error_message());
    }

    // Get the response body
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    // Log the response for debugging
  //  error_log('WhatsApp Message Response Code: ' . $response_code);
  //  error_log('WhatsApp Message Response Body: ' . $response_body);

    // Check if the request was successful
    if ($response_code !== 200) {
        throw new Exception('Failed to send WhatsApp message. Response code: ' . $response_code);
    }

    return $response_body;
}

// Function to fetch student name by student_id
function get_student_name($student_id) {
    global $wpdb;

    // Log the attempt to fetch student name
  //  error_log("Attempting to fetch name for student ID: $student_id");

    // Check if the table exists
    $table_name = $wpdb->prefix . 'students';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
      //  error_log("Table $table_name does not exist. Creating it...");
        
        // Create the students table if it doesn't exist
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    // Fetch the student's name from the database
    $student_name = $wpdb->get_var($wpdb->prepare(
        "SELECT name FROM $table_name WHERE id = %d",
        $student_id
    ));

    return $student_name ?: 'الطالب'; // Default to 'الطالب' if name is not found
}

// Function to safely parse nested schedule data
function parse_schedule_data($schedule_json) {
    // Ensure we're working with the most nested array
    while (is_array($schedule_json) && count($schedule_json) === 1 && isset($schedule_json[0])) {
        $schedule_json = $schedule_json[0];
    }

    // Validate the schedule data
    if (!is_array($schedule_json)) {
      //  error_log("Invalid schedule data: " . print_r($schedule_json, true));
        return null;
    }

    // Ensure we have day and hour keys
    $day = $schedule_json['day'] ?? null;
    $hour = $schedule_json['hour'] ?? null;

    if (!$day || !$hour) {
      //  error_log("Missing day or hour in schedule: " . print_r($schedule_json, true));
        return null;
    }

    return [
        'day' => $day,
        'hour' => $hour
    ];
}

// Function to convert Arabic day names to English for strtotime
function convert_arabic_day($day) {
    $day_map = [
        'السبت' => 'Saturday',
        'الأحد' => 'Sunday',
        'الاثنين' => 'Monday',
        'الثلاثاء' => 'Tuesday',
        'الأربعاء' => 'Wednesday',
        'الخميس' => 'Thursday',
        'الجمعة' => 'Friday'
    ];
    return $day_map[$day] ?? $day;
}

// Function to check if a message has been sent
function has_message_been_sent($schedule_id, $message_type) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'whatsapp_message_log';

    // Create the log table if it doesn't exist
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id INT AUTO_INCREMENT PRIMARY KEY,
        schedule_id INT,
        message_type VARCHAR(50),
        sent_at DATETIME,
        UNIQUE KEY unique_message (schedule_id, message_type)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Check if message has been sent
    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_name WHERE schedule_id = %d AND message_type = %s",
        $schedule_id,
        $message_type
    ));

    return !empty($result);
}

// Function to log message as sent
function log_message_sent($schedule_id, $message_type) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'whatsapp_message_log';

    $wpdb->insert(
        $table_name,
        [
            'schedule_id' => $schedule_id,
            'message_type' => $message_type,
            'sent_at' => current_time('mysql')
        ],
        ['%d', '%s', '%s']
    );
}

// Function to check schedules and send reminders
function check_schedules_and_send_reminders() {
    global $wpdb;

    // Clear previous log entries for this run
  //  error_log('==================== SCHEDULE CHECK START ====================');
  //  error_log('Timestamp: ' . current_time('mysql'));
  //  error_log('WordPress Site: ' . get_bloginfo('url'));

    // Get today and tomorrow's dates in Arabic day names
    $today = date('l');
    $tomorrow = date('l', strtotime('+1 day'));
    
    $arabic_days = [
        'Saturday' => 'السبت',
        'Sunday' => 'الأحد', 
        'Monday' => 'الاثنين', 
        'Tuesday' => 'الثلاثاء', 
        'Wednesday' => 'الأربعاء', 
        'Thursday' => 'الخميس', 
        'Friday' => 'الجمعة'
    ];

    $today_arabic = $arabic_days[$today];
    $tomorrow_arabic = $arabic_days[$tomorrow];

    // Fetch all schedules
    try {
        $all_schedules = $wpdb->get_results("SELECT * FROM wp_student_schedules");
        
      //  error_log('Total schedules in database: ' . count($all_schedules));

        if (empty($all_schedules)) {
          //  error_log('No schedules found in the database.');
            return;
        }
    } catch (Exception $e) {
      //  error_log('ERROR fetching schedules: ' . $e->getMessage());
        return;
    }

    // Filter and process schedules for today and tomorrow
    $filtered_schedules = [];
    foreach ($all_schedules as $schedule) {
        // Decode the schedule JSON
        $schedule_data = json_decode($schedule->schedule, true);
        
        if ($schedule_data === null) {
          //  error_log('Invalid schedule JSON for ID: ' . $schedule->id);
            continue;
        }

        // Find schedules for today or tomorrow
        foreach ($schedule_data as $entry) {
            // Check both current entry and original day
            $day = $entry['original']['day'] ?? $entry['day'];
            $hour = $entry['hour'];

            if ($day == $today_arabic || $day == $tomorrow_arabic) {
                // Retrieve student name from user meta
                $student_name = get_user_meta($schedule->student_id, 'first_name', true) . ' ' . 
                                get_user_meta($schedule->student_id, 'last_name', true);
                $student_name = trim($student_name) ?: 'الطالب';

                $filtered_schedule = [
                    'schedule_id' => $schedule->id,
                    'student_id' => $schedule->student_id,
                    'student_name' => $student_name,
                    'day' => $day,
                    'hour' => $hour
                ];
                $filtered_schedules[] = $filtered_schedule;
                
                break; // Only add first matching schedule for the day
            }
        }
    }

    // Log schedules for today and tomorrow
    $today_schedules = array_filter($filtered_schedules, function($schedule) use ($today_arabic) {
        return $schedule['day'] == $today_arabic;
    });
    $tomorrow_schedules = array_filter($filtered_schedules, function($schedule) use ($tomorrow_arabic) {
        return $schedule['day'] == $tomorrow_arabic;
    });

  //  error_log('Schedules for today (' . $today_arabic . '): ' . count($today_schedules));
    foreach ($today_schedules as $schedule) {
       error_log('Today\'s Schedule Details - Student: ' . $schedule['student_name'] . 
                  ', Time: ' . $schedule['hour'] . 
                  ', Schedule ID: ' . $schedule['schedule_id']);
    }

  //  error_log('Schedules for tomorrow (' . $tomorrow_arabic . '): ' . count($tomorrow_schedules));
    foreach ($tomorrow_schedules as $schedule) {
       error_log('Tomorrow\'s Schedule Details - Student: ' . $schedule['student_name'] . 
                  ', Time: ' . $schedule['hour'] . 
                  ', Schedule ID: ' . $schedule['schedule_id']);
    }

    // Prepare the message
    $message_parts = [];
    $message_parts[] = "📅 جدول المواعيد اليومي - " . current_time('mysql');

    // Add today's schedules
    if (!empty($today_schedules)) {
        $message_parts[] = "\n🔹 مواعيد اليوم ($today_arabic):";
        foreach ($today_schedules as $schedule) {
            $message_parts[] = "• {$schedule['student_name']} - الساعة {$schedule['hour']}";
        }
    } else {
        $message_parts[] = "\n🔹 لا توجد مواعيد اليوم ($today_arabic)";
    }

    // Add tomorrow's schedules
    if (!empty($tomorrow_schedules)) {
        $message_parts[] = "\n🔹 مواعيد غدًا ($tomorrow_arabic):";
        foreach ($tomorrow_schedules as $schedule) {
            $message_parts[] = "• {$schedule['student_name']} - الساعة {$schedule['hour']}";
        }
    } else {
        $message_parts[] = "\n🔹 لا توجد مواعيد غدًا ($tomorrow_arabic)";
    }

    // Combine the message parts
    $message = implode("\n", $message_parts);

    // Authorization token for WhatsApp API
    $authorization = 'FXRU7qBLWMKCrw2CLRpNAQvTZyhVw8a3x9undIdbf73489b5';

    // Chat ID (replace with the actual chat ID)
    $chatId = '96878096004';

    try {
        // Send the message
        $response = send_whatsapp_message($chatId, $message, $authorization);
      //  error_log('WhatsApp Message Response Code: 200');
      //  error_log('WhatsApp Message Response Body: ' . $response);
    } catch (Exception $e) {
      //  error_log('Error sending daily schedule WhatsApp message: ' . $e->getMessage());
    }

  //  error_log('==================== SCHEDULE CHECK END ====================');
}

// If this file is called directly by PHP CLI (Hostinger cron), execute the schedule check
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    zuwad_run_schedule_check();
}