<?php
define('ALLOW_HOSTINGER_CRON', true);

/**
 * Hostinger Cron Job for WhatsApp Schedule Notifications
 * Standalone script for direct execution
 * With additional check for existing student reports
 * Uses a single consolidated log file for all notification tracking
 * FIXED: Prevents duplicate sending and adds better error handling
 */

// Force timezone at the beginning
date_default_timezone_set('Africa/Cairo'); // Replace with your timezone
ini_set('date.timezone', 'Africa/Cairo'); // Double enforcement

// Absolute paths - USE ACTUAL HOSTINGER PATHS
define('WP_LOAD_PATH', 'domains/zuwad-academy.com/public_html/system/wp-load.php');
define('DEBUG_LOG_PATH', 'domains/zuwad-academy.com/public_html/system/wp-content/debug.log');
define('WP_CONTENT_DIR', 'domains/zuwad-academy.com/public_html/system/wp-content');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Logging function with fallback mechanism
function zuwad_cron_log($message, $type = 'INFO')
{
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] [{$type}] HOSTINGER CRON: {$message}\n";

    // Attempt to create log directory if it doesn't exist
    $log_dir = dirname(DEBUG_LOG_PATH);
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }

    // Write to log file only
    @file_put_contents(DEBUG_LOG_PATH, $log_entry, FILE_APPEND);

    // Output to console/browser only if not running in CLI
    if (php_sapi_name() !== 'cli') {
    echo $log_entry;
    }
}

// // Log current time and timezone for debugging
// zuwad_cron_log("Current time: " . date('Y-m-d H:i:s'));
// zuwad_cron_log("Current timezone: " . date_default_timezone_get());

// Function to safely load WordPress
function load_wordpress_environment()
{
    // Ensure WordPress load file exists
    if (!file_exists(WP_LOAD_PATH)) {
        zuwad_cron_log("WordPress load file not found at " . WP_LOAD_PATH, 'ERROR');
        return false;
    }

    // Define WordPress constants if not already defined
    if (!defined('ABSPATH')) {
        define('ABSPATH', dirname(WP_LOAD_PATH) . '/');
    }

    try {
        // Load WordPress
        require_once(WP_LOAD_PATH);

        // Reset timezone after WordPress loads
        date_default_timezone_set('Africa/Cairo');
        ini_set('date.timezone', 'Africa/Cairo');

        // Additional checks
        if (!function_exists('wp_load_alloptions')) {
            zuwad_cron_log("WordPress core functions not loaded", 'ERROR');
            return false;
        }

        // zuwad_cron_log("WordPress environment loaded successfully");
        return true;
    } catch (Exception $e) {
        zuwad_cron_log("Error loading WordPress: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

// Helper function to format time in Arabic
function format_time_arabic($time)
{
    if (strpos($time, 'AM') !== false) {
        return str_replace('AM', 'صباحاً', $time);
    } else if (strpos($time, 'PM') !== false) {
        return str_replace('PM', 'مساءً', $time);
    }
    return $time;
}

// Function to calculate time until class with timezone awareness
function calculate_time_until_class($hour)
{
    date_default_timezone_set('Africa/Cairo'); // Force timezone again

    $cairo_timezone = new DateTimeZone('Africa/Cairo');
    $now = new DateTime('now', $cairo_timezone);

    // Make sure we parse the hour format correctly
    if (strpos($hour, 'AM') !== false || strpos($hour, 'PM') !== false) {
        // Handle 12-hour format
        $class_time = DateTime::createFromFormat('h:i A', $hour, $cairo_timezone);
    } else {
        // Handle 24-hour format
        $class_time = DateTime::createFromFormat('H:i', $hour, $cairo_timezone);
    }

    // Check if parsing was successful
    if ($class_time === false) {
        zuwad_cron_log("Failed to parse time format: $hour", 'ERROR');
        return false;
    }

    // Set the date components to today
    $class_time->setDate(
        (int)$now->format('Y'),
        (int)$now->format('m'),
        (int)$now->format('d')
    );

    $time_until_class = $class_time->getTimestamp() - $now->getTimestamp();

    // Debug log the calculation
    $hours = floor($time_until_class / 3600);
    $minutes = floor(($time_until_class % 3600) / 60);
    // zuwad_cron_log("Time calculation: Class at $hour, Current time: " . $now->format('H:i:s') .
    //     ", Time until class: {$hours}h {$minutes}m");

    return $time_until_class;
}

// Function to check if notification should be sent
function should_send_notification($time_until_class, $type, $student_id)
{
    $student_name = get_user_meta($student_id, 'first_name', true) . ' ' .
        get_user_meta($student_id, 'last_name', true);
    $student_name = trim($student_name) ?: 'الطالب';

    // Check if student is due for notification based on time
    $is_due = false;
    $time_until = '';
    
    switch ($type) {
        case '10min':
            // Between 8-12 minutes (480-720 seconds)
            if ($time_until_class > 0 && $time_until_class <= 720 && $time_until_class >= 480) {
                $is_due = true;
                $time_until = floor($time_until_class/60) . " minutes";
            }
            break;

        case '6hour':
            // Narrow window to just 10 minutes at the 6-hour mark (21600-22200 seconds)
            // This will reduce logs from 60 per hour to just 10
            if ($time_until_class >= 21600 && $time_until_class <= 22200) {
                $is_due = true;
                $time_until = floor($time_until_class/3600) . " hours";
            }
            break;
    }

    // If student is due for notification, check for report
    if ($is_due) {
        if (student_has_report_for_today($student_id, null)) {
            zuwad_cron_log("skipping Student {$student_name} has a report for today - Class in {$time_until}", 'INFO');
            return false;
        }
        zuwad_cron_log("Student {$student_name} is due for {$type} notification - Class in {$time_until}", 'INFO');
        return true;
    }

    return false;
}

// Main cron execution function
function log_todays_schedule($schedules)
{
    $log_message = "\n=== Today's Classes (" . date('Y-m-d') . ") ===\n";
    if (empty($schedules)) {
        $log_message .= "No classes scheduled today\n";
    } else {
        foreach ($schedules as $schedule) {
            $log_message .= "{$schedule['student_name']} - {$schedule['hour']}\n";
        }
    }
    $log_message .= "================================\n";
    // zuwad_cron_log($log_message, 'SCHEDULE');
}

// Debug function for schedule entries
function debug_schedule_entry($schedule, $time_until_class)
{
    $hours = floor($time_until_class / 3600);
    $minutes = floor(($time_until_class % 3600) / 60);

    // zuwad_cron_log(
    //     "Schedule entry: {$schedule['student_name']} - " .
    //         "Class at {$schedule['hour']} - " .
    //         "Time until class: {$hours}h {$minutes}m - " .
    //         "Current time: " . date('H:i:s') . " Cairo time"
    // );
}

// ==========================================
// FIXED CONSOLIDATED NOTIFICATION TRACKING SYSTEM
// ==========================================

// Define the path to our consolidated log file
function get_consolidated_log_path()
{
    return WP_CONTENT_DIR . '/notifications_consolidated_' . date('Y-m-d') . '.txt';
}

/**
 * FIXED: Improved notification tracking that checks for ANY attempt, not just successful ones
 * This prevents resending after errors
 */
function has_notification_been_sent_by_phone($phone, $type)
{
    // SPECIAL CASE: Always return false for teacher notifications to ensure they're always sent
    if (strpos($type, '10min-teacher') !== false) {
        return false;
    }

    $log_file = get_consolidated_log_path();

    if (!file_exists($log_file)) {
        return false;
    }

    $log_content = @file_get_contents($log_file);
    if ($log_content === false) {
        zuwad_cron_log("Could not read consolidated log file", 'ERROR');
        return false; // Assume not sent if we can't read the file
    }

    // Each log entry has the format: "student_name|phone|time|type|status|error_info"
    // We need to find any line where phone and type match - regardless of status
    $entries = explode("\n", $log_content);

    foreach ($entries as $entry) {
        if (empty($entry)) continue;

        $parts = explode('|', $entry);
        if (count($parts) < 4) continue;

        $entry_phone = $parts[1];
        $entry_type = $parts[3];

        // Check for ANY attempt (success or error) to avoid repeated sends for students
        if ($entry_phone === $phone && $entry_type === $type) {
            // zuwad_cron_log("Found existing notification attempt for phone: $phone, type: $type", 'INFO');
            return true;
        }
    }

    return false;
}

// Record a notification in our consolidated log
function record_notification($student_name, $phone, $type, $status = 'success', $error_info = '')
{
    $log_file = get_consolidated_log_path();
    $time = date('H:i:s');

    // Format: student_name|phone|time|type|status|error_info
    $log_entry = "$student_name|$phone|$time|$type|$status|$error_info\n";

    $result = @file_put_contents($log_file, $log_entry, FILE_APPEND);

    if ($result === false) {
        zuwad_cron_log("Failed to write to consolidated log: $log_entry", 'ERROR');
        return false;
    }

    return true;
}

// Function: Check if student has report for today
function student_has_report_for_today($student_id, $class_hour)
{
    global $wpdb;

    if (!$wpdb) {
        zuwad_cron_log("Database connection not available for student report check", 'ERROR');
        return false;
    }

    try {
        // Format the date for database query
        $today_date = date('Y-m-d');

        // Log the check attempt
        // zuwad_cron_log("Checking for reports - Student ID: $student_id, Date: $today_date", 'DEBUG');

        // Check if there's a report for this student on this date in wp_student_reports
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM wp_student_reports 
            WHERE student_id = %d
            AND DATE(date) = %s",
            $student_id,
            $today_date
        );
        
        // Log the query for debugging
        // zuwad_cron_log("Executing query: " . $query, 'DEBUG');
        
        $report_exists = $wpdb->get_var($query);

        if ($wpdb->last_error) {
            zuwad_cron_log("Database error checking student reports: " . $wpdb->last_error, 'ERROR');
            return false;
        }

        // Log the result
        // zuwad_cron_log("Report check result - Student ID: $student_id, Reports found: $report_exists", 'DEBUG');

        return ($report_exists > 0);
    } catch (Exception $e) {
        zuwad_cron_log("Error checking student reports: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * FIXED: Added additional error handling and retry prevention
 */
function zuwad_hostinger_schedule_check()
{
    date_default_timezone_set('Africa/Cairo'); // Force timezone again
    global $wpdb;
    // zuwad_cron_log('Schedule check initiated');

    if (!$wpdb) {
        zuwad_cron_log("Database connection failed", 'CRITICAL');
        return;
    }

    try {
        // Get current time
        $current_hour = (int)date('H');
        $current_minute = (int)date('i');
        $is_midnight = ($current_hour === 0 && $current_minute < 5);

        $today = date('l');
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

        // Fetch schedules
        $all_schedules = $wpdb->get_results("SELECT * FROM wp_student_schedules");
        if ($wpdb->last_error) {
            throw new Exception("Database query error: " . $wpdb->last_error);
        }

        // Process schedules
        $todays_schedules = [];
        foreach ($all_schedules as $schedule) {
            $schedule_data = json_decode($schedule->schedule, true);
            if ($schedule_data === null) continue;

            foreach ($schedule_data as $entry) {
                // Only use the top-level day value, ignore the original field
                $day = $entry['day'];
                if ($day == $today_arabic) {
                    $student_name = get_user_meta($schedule->student_id, 'first_name', true) . ' ' .
                        get_user_meta($schedule->student_id, 'last_name', true);
                    $student_name = trim($student_name) ?: 'الطالب';

                    $hour = $entry['hour'];

                    $todays_schedules[] = [
                        'student_id' => $schedule->student_id, // Store student_id for report check
                        'student_name' => $student_name,
                        'hour' => $hour,
                        'original_entry' => $entry
                    ];
                }
            }
        }

        // Log today's schedule at midnight
        if ($is_midnight) {
            log_todays_schedule($todays_schedules);
        }

        // FIXED: Process notifications with improved tracking
        foreach ($todays_schedules as $schedule) {
            // Get the time until class
            $time_until_class = calculate_time_until_class($schedule['hour']);

            if ($time_until_class === false) {
                // zuwad_cron_log("Skipping schedule for {$schedule['student_name']} - Invalid time format: {$schedule['hour']}", 'WARNING');
                continue; // Skip this entry if time calculation failed
            }

            // Get student phone number first to avoid unnecessary processing
            $student_phone = get_user_meta($schedule['student_id'], 'phone', true);
            if (empty($student_phone)) {
                // zuwad_cron_log("No phone number found for student: {$schedule['student_name']}", 'WARNING');
                continue;
            }

            // Check for 10min notification
            $should_send_10min = should_send_notification($time_until_class, '10min', $schedule['student_id']);
            if ($should_send_10min) {
                if (!has_notification_been_sent_by_phone($student_phone, '10min')) {
                    $message = "🌸✨ السلام عليكم \n";
                    $message .= "سيبدأ درسنا خلال دقائق ⌛\n\n";
                    $message .= "استعدوا فالمعلم/ة بانتظاركم 🥰✨ \n\n";
                    $message .= "تنويه: (يتم ارسال هذه الرسالة تلقائياً من خلال الذكاء الاصطناعي) 🖥🤖.";

                    // zuwad_cron_log("Attempting to send 10min notification to {$schedule['student_name']} - Class at {$schedule['hour']}", 'INFO');
                    send_whatsapp_notification($message, $schedule['student_id'], '10min');
                    sleep(2); // Reduced delay between messages to 2 seconds
                }
            }

            // Check for 6hour notification
            $should_send_6hour = should_send_notification($time_until_class, '6hour', $schedule['student_id']);
            if ($should_send_6hour) {
                if (!has_notification_been_sent_by_phone($student_phone, '6hour')) {
                    $message = "🌸✨ السلام عليكم\n\n";
                    $message .= "أود تذكيركم بموعد درسنا اليوم⏰.\n\n";
                    $message .= "المعلمة ستكون بانتظاركم في الموعد، فلا تتأخروا حتى نستغل كل لحظة في الفهم والتعلم 📚💡\n\n";
                    $message .= "تنويه: (يتم ارسال هذه الرسالة تلقائياً من خلال الذكاء الاصطناعي) 🖥🤖.";

                    // zuwad_cron_log("Attempting to send 6hour notification to {$schedule['student_name']} - Class at {$schedule['hour']}", 'INFO');
                    send_whatsapp_notification($message, $schedule['student_id'], '6hour');
                    sleep(2); // Reduced delay between messages to 2 seconds
                }
            }
        }

        // zuwad_cron_log('Schedule check completed');
    } catch (Exception $e) {
        zuwad_cron_log("Critical error in schedule processing: " . $e->getMessage(), 'CRITICAL');
    }
}

/**
 * FIXED: Improved error handling and tracking in notification sending
 */
function send_whatsapp_notification($message, $student_id, $notification_type)
{
    $authorization = 'FXRU7qBLWMKCrw2CLRpNAQvTZyhVw8a3x9undIdbf73489b5';
    $m_id = get_user_meta($student_id, 'm_id', true);
    $chatId = get_user_meta($student_id, 'phone', true);
    $student_name = get_user_meta($student_id, 'first_name', true) . ' ' .
        get_user_meta($student_id, 'last_name', true);
    $student_name = trim($student_name) ?: 'الطالب';

    // Flag to track if we need to send student notification
    $should_send_to_student = true;

    // FIXED: Track uniquely by date+phone+type to prevent multiple attempts on same day
    if (has_notification_been_sent_by_phone($chatId, $notification_type)) {
        // Log as skipped due to duplicate - better to be safe
        record_notification($student_name, $chatId, $notification_type, 'skipped', 'previous attempt exists');
        // zuwad_cron_log("Skipping duplicate notification for $student_name ($notification_type)", 'INFO');

        // Don't send to student, but continue execution to handle teacher notification
        $should_send_to_student = false;
    }

    // Only send to student if needed
    if ($should_send_to_student) {
        // Send notification to student
        try {
            $response = send_whatsapp_message($chatId, $message, $authorization, $m_id);
            $response_data = json_decode($response, true);

            if (isset($response_data['success']) && $response_data['success'] === true) {
                // Only record successful notifications
                record_notification($student_name, $chatId, $notification_type, 'success');
                // zuwad_cron_log("Successfully sent $notification_type notification to $student_name", 'INFO');
            } else {
                $error_msg = isset($response_data['message']) ? $response_data['message'] : 'Unknown error';
                zuwad_cron_log("Failed to send notification: $error_msg", 'ERROR');
                // Don't record failed notifications so they can be retried
            }
        } catch (Exception $e) {
            zuwad_cron_log("Exception sending notification: " . $e->getMessage(), 'ERROR');
            // Don't record failed notifications so they can be retried
        }
    }

    // ALWAYS send teacher notification for 10-minute reminders regardless of student notification status
    if ($notification_type === '10min') {
        send_teacher_notification($student_id, $student_name, date('g:i A'));
    }
}

/**
 * FIXED: Improved teacher notification with better error handling
 */
function send_teacher_notification($student_id, $student_name, $class_time)
{
    $authorization = 'FXRU7qBLWMKCrw2CLRpNAQvTZyhVw8a3x9undIdbf73489b5';

    // Get teacher ID from student meta
    $teacher_id = get_user_meta($student_id, 'teacher', true);

    if (!$teacher_id) {
        // zuwad_cron_log("Cannot send teacher notification: No teacher assigned for student $student_name", 'ERROR');
        return;
    }

    // Get teacher details
    $teacher_phone = get_user_meta($teacher_id, 'phone', true);
    $teacher_name = get_user_meta($teacher_id, 'first_name', true) . ' ' .
        get_user_meta($teacher_id, 'last_name', true);
    $teacher_name = trim($teacher_name) ?: 'المعلم';
    $m_id = get_user_meta($teacher_id, 'm_id', true);

    if (!$teacher_phone) {
        // zuwad_cron_log("Cannot send teacher notification: No phone number found for teacher $teacher_name", 'ERROR');
        return;
    }

    // Create a unique notification type for this specific teacher-student combination
    $teacher_notification_type = '10min-teacher-' . $student_id;

    // Prepare teacher message
    $message = "السلام عليكم {$teacher_name}! 🚀📖\n";
    $message .= "استعد، انطلق! 🚦✨ حصتك القادمة مع {$student_name} تبدأ بعد 10 دقائق فقط! ⏳🔥\n\n";
    $message .= "حان وقت تجهيز قهوتك ☕، ضبط مقعدك 💺، والاستعداد لحصة مليئة بالفائدة والإبداع! 😃🎯\n";
    $message .= "إذا احتجت لأي شيء، نحن هنا دائمًا لدعمك. 💙\n";
    $message .= "تنويه: (يتم إرسال هذه الرسالة تلقائيًا من خلال الذكاء الاصطناعي) 🖥🤖.\n";
    $message .= "حصة موفقة! 🚀";

    // Send message to teacher
    try {
        $response = send_whatsapp_message($teacher_phone, $message, $authorization, $m_id);
        $response_data = json_decode($response, true);

        if (isset($response_data['success']) && $response_data['success'] === true) {
            // Only record successful teacher notifications
            record_notification("Teacher: $teacher_name", $teacher_phone, $teacher_notification_type, 'success');
            // zuwad_cron_log("Successfully sent teacher notification to $teacher_name for student $student_name");
        } else {
            $error_msg = isset($response_data['message']) ? $response_data['message'] : 'Unknown error';
            zuwad_cron_log("Failed to send teacher notification: $error_msg", 'ERROR');
            // Don't record failed teacher notifications so they can be retried
        }
    } catch (Exception $e) {
        zuwad_cron_log("Exception sending teacher notification: " . $e->getMessage(), 'ERROR');
        // Don't record failed teacher notifications so they can be retried
    }
}

// WhatsApp message sending function with improved error handling
function send_whatsapp_message($chatId, $message, $authorization, $m_id)
{
    // Get configuration from central function, passing m_id for device selection
    $config = zuwad_get_whatsapp_config(null, $m_id);
    
    // Format phone number - ensure it starts with +
    $phone_number = trim($chatId);
    if (substr($phone_number, 0, 1) !== '+') {
        $phone_number = '+' . $phone_number;
    }
    
    // Trim any @c.us suffix
    $phone_number = str_replace('@c.us', '', $phone_number);
    
    // For text messages, use the message endpoint
    $url = $config['api_url_base'] . 'send/message';
    
    $payload = array(
        'device_id' => $config['device_id'],
        'to' => $phone_number,
        'message' => $message
    );

    // Prepare the headers for the API request
    $headers = array(
            'Content-Type' => 'application/json',
        'Accept' => 'application/json'
    );
    
    // Prepare the args for wp_remote_post
    $args = array(
        'headers' => $headers,
        'body' => json_encode($payload),
        'timeout' => 60,
        'sslverify' => false
    );

    // Try with timeout and retry logic
    $max_retries = 1; // Maximum number of retries
    $retry_count = 0;

    while ($retry_count <= $max_retries) {
        try {
            // Use file_get_contents as a fallback if wp_remote_post is not available
            if (function_exists('wp_remote_post')) {
                $response = wp_remote_post($url, $args);

                if (is_wp_error($response)) {
                    throw new Exception('WhatsApp API request failed: ' . $response->get_error_message());
                }

                $response_code = wp_remote_retrieve_response_code($response);
                $response_body = wp_remote_retrieve_body($response);
                $response_data = json_decode($response_body, true);
                
                // Check response status - noti-fire API returns success in a different format
                if ($response_code === 200 && isset($response_data['status']) && $response_data['status'] === true) {
                    return json_encode([
                        'success' => true,
                        'device_id' => $config['device_id']
                    ]);
                } else if (!empty($response_data) && isset($response_data['message'])) {
                    return json_encode([
                        'success' => false,
                        'message' => $response_data['message'],
                        'code' => $response_code,
                        'device_id' => $config['device_id']
                    ]);
                } else {
                    return json_encode([
                        'success' => false,
                        'message' => 'Invalid or malformed API response',
                        'device_id' => $config['device_id']
                    ]);
                }
            } else {
                // Fallback to file_get_contents if wp_remote_post is not available
                $context = stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => implode("\r\n", [
                            'Content-Type: application/json',
                            'Accept: application/json'
                        ]),
                        'content' => json_encode($payload),
                        'timeout' => $retry_count > 0 ? 15 : 10 // Increase timeout on retry
                    ]
                ]);

                $result = @file_get_contents($url, false, $context);

                if ($result === false) {
                    throw new Exception('WhatsApp API request failed with file_get_contents');
                }

                $json_data = json_decode($result, true);
                
                if (isset($json_data['status']) && $json_data['status'] === true) {
                    return json_encode([
                        'success' => true,
                        'device_id' => $config['device_id']
                    ]);
                } else if (!empty($json_data) && isset($json_data['message'])) {
                    return json_encode([
                        'success' => false,
                        'message' => $json_data['message'],
                        'device_id' => $config['device_id']
                    ]);
                } else {
                    return json_encode([
                        'success' => false,
                        'message' => 'Invalid or malformed API response: ' . substr($result, 0, 100) . (strlen($result) > 100 ? '...' : ''),
                        'device_id' => $config['device_id']
                    ]);
                }
            }
        } catch (Exception $e) {
            $retry_count++;

            if ($retry_count <= $max_retries) {
                zuwad_cron_log("Retrying WhatsApp API request (attempt $retry_count) after error: " . $e->getMessage(), 'WARNING');
                sleep(2); // Wait 2 seconds before retrying
            } else {
                // Return structured error response on failure
                return json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'device_id' => $config['device_id']
                ]);
            }
        }
    }
    
    // Should never reach here, but just in case
    return json_encode([
        'success' => false,
        'message' => 'Maximum retries exceeded with no result',
        'device_id' => $config['device_id']
    ]);
}

// Function to display the notification logs - for admin viewing
function display_notification_logs()
{
    $log_file = get_consolidated_log_path();
    $logs = [];

    if (file_exists($log_file)) {
        $logs = file($log_file, FILE_IGNORE_NEW_LINES);
    }

    echo "<h2>Notification Logs for " . date('Y-m-d') . "</h2>";
    echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>Name</th><th>Phone</th><th>Time</th><th>Type</th><th>Status</th><th>Error Info</th>";
    echo "</tr>";

    if (empty($logs)) {
        echo "<tr><td colspan='6' style='text-align: center;'>No notifications sent today</td></tr>";
    } else {
        foreach ($logs as $log) {
            if (empty($log)) continue;

            $parts = explode('|', $log);
            if (count($parts) < 5) continue;

            $name = $parts[0] ?? 'Unknown';
            $phone = $parts[1] ?? 'Unknown';
            $time = $parts[2] ?? 'Unknown';
            $type = $parts[3] ?? 'Unknown';
            $status = $parts[4] ?? 'Unknown';
            $error_info = $parts[5] ?? '';

            // Set row color based on status
            $row_color = '';
            if ($status === 'error') {
                $row_color = 'background-color: #ffcccc;'; // Light red for errors
            } elseif ($status === 'skipped') {
                $row_color = 'background-color: #ffffcc;'; // Light yellow for skipped
            } elseif ($status === 'pending') {
                $row_color = 'background-color: #e6e6ff;'; // Light blue for pending
            } elseif ($status === 'success') {
                $row_color = 'background-color: #ccffcc;'; // Light green for success
            }

            // Add icon to indicate if it's student or teacher notification
            $is_teacher = (strpos($name, 'Teacher') !== false);
            $icon = $is_teacher ? '👨‍🏫' : '👨‍🎓';

            echo "<tr style='$row_color'>";
            echo "<td>$icon $name</td>";
            echo "<td>$phone</td>";
            echo "<td>$time</td>";
            echo "<td>$type</td>";
            echo "<td>$status</td>";
            echo "<td>$error_info</td>";
            echo "</tr>";
        }
    }

    echo "</table>";
}

// Create a WordPress admin page to view logs (uncomment to enable)
/*
function register_notification_logs_page()
{
    add_menu_page(
        'Notification Logs',
        'Notification Logs',
        'manage_options',
        'notification-logs',
        'display_notification_logs',
        'dashicons-list-view',
        30
    );
}
add_action('admin_menu', 'register_notification_logs_page');
*/

// Execution flow with comprehensive error handling
function execute_cron_job()
{
    date_default_timezone_set('Africa/Cairo'); // Force timezone again

    if (!load_wordpress_environment()) {
        zuwad_cron_log("Failed to load WordPress environment", 'CRITICAL');
        exit(1);
    }

    zuwad_hostinger_schedule_check();
}

// خارج الدالة تمامًا
if (defined('ALLOW_HOSTINGER_CRON') && ALLOW_HOSTINGER_CRON === true) {
    execute_cron_job();
} else {
    zuwad_cron_log("Script accessed incorrectly", 'WARNING');
    http_response_code(403);
    echo "Access Denied";
}

zuwad_cron_log("✅ Cron test at " . date('Y-m-d H:i:s'));
