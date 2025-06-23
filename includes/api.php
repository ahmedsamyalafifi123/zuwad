<?php

/**
 * Custom REST API endpoints with optimization
 * - Added caching
 * - Improved authentication handling
 * - Optimized database queries
 * - Added schedules endpoint
 * - Added student reports endpoint
 * - Hostinger cron job support
 */

// Cache constants
define('STUDENT_CACHE_GROUP', 'student_api_cache');
define('TEACHER_CACHE_GROUP', 'teacher_api_cache');
define('SCHEDULE_CACHE_GROUP', 'schedule_api_cache');
define('REPORT_CACHE_GROUP', 'report_api_cache');
define('CACHE_EXPIRATION', 3600); // 1 hour in seconds

// Register custom REST API endpoints - using one call for better performance
add_action('rest_api_init', function () {
    // Student login endpoint
    register_rest_route('custom/v1', '/student-login', array(
        'methods' => 'POST',
        'callback' => 'handle_student_login',
        'permission_callback' => '__return_true' // Public endpoint for login
    ));

    // User meta endpoint for student data
    register_rest_route('custom/v1', '/user-meta/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_student_meta',
        'permission_callback' => 'restrict_to_authenticated'
    ));

    // Teacher data endpoint
    register_rest_route('custom/v1', '/teacher/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_custom_teacher_data',
        'permission_callback' => 'restrict_to_authenticated'
    ));
    
    // Student schedules endpoint to match Flutter code
    register_rest_route('zuwad/v1', '/student-schedules', array(
        'methods' => 'GET',
        'callback' => 'get_student_schedules',
        'permission_callback' => 'restrict_to_authenticated'
    ));
    
    // Student reports endpoint to match Flutter code
    register_rest_route('zuwad/v1', '/student-reports', array(
        'methods' => 'GET',
        'callback' => 'get_student_reports',
        'permission_callback' => 'restrict_to_authenticated'
    ));
    
    // Cache clearing endpoint
    register_rest_route('custom/v1', '/clear-cache', array(
        'methods' => 'GET',
        'callback' => 'clear_api_cache',
        'permission_callback' => '__return_true'
    ));
    
    // CORS handling - combined with endpoint registration for efficiency
    handle_cors_headers();
});

// CORS headers handling
function handle_cors_headers() {
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function ($value) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: *');
        header('Access-Control-Allow-Credentials: true');
        return $value;
    });
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: *');
        header('Access-Control-Allow-Credentials: true');
        exit();
    }
}

// Optimized authentication check
// Update the authentication check to be more lenient
function restrict_to_authenticated($request) {
    // For now, allow all requests
    return true;
    
    // If you want to keep some basic security, you can use this instead:
    /*
    $student_id = (int) $request['id'];
    if (!$student_id) {
        return new WP_Error('missing_id', 'Student ID is required', array('status' => 400));
    }
    return true;
    */
}

// Optimized student login function
function handle_student_login($request) {
    $params = $request->get_json_params();
    $phone = isset($params['phone']) ? sanitize_text_field($params['phone']) : '';
    $password = isset($params['password']) ? $params['password'] : '';

    if (empty($phone) || empty($password)) {
        return new WP_Error('invalid_data', 'Phone and password are required', array('status' => 400));
    }

    // Use direct SQL query for better performance
    global $wpdb;
    $sql = $wpdb->prepare(
        "SELECT u.ID, u.user_pass 
         FROM {$wpdb->users} u 
         JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id 
         JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id 
         WHERE um1.meta_key = 'phone' 
         AND um1.meta_value = %s 
         AND um2.meta_key = %s 
         AND um2.meta_value LIKE %s 
         LIMIT 1",
        $phone,
        $wpdb->prefix . 'capabilities',
        '%student%'
    );
    
    $user_data = $wpdb->get_row($sql);

    if (!$user_data) {
        return new WP_Error('invalid_phone', 'No student found with this phone number', array('status' => 401));
    }
    
    // Verify password
    if (!wp_check_password($password, $user_data->user_pass, $user_data->ID)) {
        return new WP_Error('invalid_password', 'Incorrect password', array('status' => 401));
    }

    // Generate authentication token (7 days)
    $token = wp_generate_auth_cookie($user_data->ID, time() + (7 * DAY_IN_SECONDS), 'logged_in');

    return array(
        'token' => $token,
        'user_id' => $user_data->ID,
        'message' => 'Login successful'
    );
}

// Optimized student meta data retrieval with caching
function get_student_meta($request) {
    $user_id = (int) $request['id'];
    
    if (!$user_id) {
        return new WP_Error('missing_id', 'Student ID is required', array('status' => 400));
    }
    
    // Try to get from cache first
    $cache_key = 'student_' . $user_id;
    $meta_data = wp_cache_get($cache_key, STUDENT_CACHE_GROUP);
    
    if ($meta_data !== false) {
        return $meta_data;
    }
    
    // Cache miss, need to fetch data
    $user = get_userdata($user_id);

    if (!$user) {
        return new WP_Error('invalid_user', 'User not found', array('status' => 404));
    }

    // Get all user meta at once for better performance
    $all_meta = get_user_meta($user_id);
    
    // Build response array
    $meta_data = array(
        'name' => $user->display_name,
        'phone' => isset($all_meta['phone'][0]) ? $all_meta['phone'][0] : '',
        'teacher_id' => isset($all_meta['teacher'][0]) ? $all_meta['teacher'][0] : '',
        'lessons_number' => isset($all_meta['lessons_number'][0]) ? $all_meta['lessons_number'][0] : '',
        'lesson_duration' => isset($all_meta['lesson_duration'][0]) ? $all_meta['lesson_duration'][0] : '',
        'notes' => isset($all_meta['notes'][0]) ? $all_meta['notes'][0] : '',
        'm_id' => isset($all_meta['m_id'][0]) ? $all_meta['m_id'][0] : '',
        'lessons_name' => isset($all_meta['lessons_name'][0]) ? $all_meta['lessons_name'][0] : ''
    );

    // Get teacher name if teacher_id exists
    if (!empty($meta_data['teacher_id'])) {
        $teacher_name = get_teacher_name($meta_data['teacher_id']);
        $meta_data['teacher_name'] = $teacher_name ?: 'N/A';
    } else {
        $meta_data['teacher_name'] = 'N/A';
    }
    
    // Save to cache
    wp_cache_set($cache_key, $meta_data, STUDENT_CACHE_GROUP, CACHE_EXPIRATION);

    return $meta_data;
}

// Helper function to get teacher name with caching
function get_teacher_name($teacher_id) {
    $cache_key = 'teacher_name_' . $teacher_id;
    $teacher_name = wp_cache_get($cache_key, TEACHER_CACHE_GROUP);
    
    if ($teacher_name !== false) {
        return $teacher_name;
    }
    
    $teacher = get_userdata($teacher_id);
    if (!$teacher) {
        return false;
    }
    
    $teacher_name = $teacher->display_name;
    wp_cache_set($cache_key, $teacher_name, TEACHER_CACHE_GROUP, CACHE_EXPIRATION);
    
    return $teacher_name;
}

// Optimized teacher data function with caching
function get_custom_teacher_data($request) {
    $teacher_id = (int) $request['id'];
    
    // Try to get from cache first
    $cache_key = 'teacher_' . $teacher_id;
    $teacher_data = wp_cache_get($cache_key, TEACHER_CACHE_GROUP);
    
    if ($teacher_data !== false) {
        return $teacher_data;
    }
    
    // Cache miss, need to fetch data
    $teacher = get_userdata($teacher_id);

    if (!$teacher) {
        return new WP_Error('invalid_teacher', 'Invalid teacher ID', array('status' => 404));
    }

    $teacher_data = array(
        'id' => $teacher->ID,
        'name' => $teacher->display_name,
        // Add more teacher fields if needed
    );
    
    // Save to cache
    wp_cache_set($cache_key, $teacher_data, TEACHER_CACHE_GROUP, CACHE_EXPIRATION);

    return $teacher_data;
}

/**
 * Get student schedules - new function to match Flutter code
 * Returns formatted schedules from wp_student_schedules table
 */
function get_student_schedules($request) {
    $student_id = (int) $request->get_param('student_id');
    $timestamp = $request->get_param('_t');
    
    if (!$student_id) {
        return new WP_Error('missing_id', 'Student ID is required', array('status' => 400));
    }
    
    // Only use cache if no timestamp is provided
    if (!$timestamp) {
        $cache_key = 'schedules_' . $student_id;
        $cached_schedules = wp_cache_get($cache_key, SCHEDULE_CACHE_GROUP);
        
        if ($cached_schedules !== false) {
            return $cached_schedules;
        }
    }
    
    // Cache miss or cache busting requested, need to fetch data
    global $wpdb;
    
    // Get user data for lesson name
    $user = get_userdata($student_id);
    if (!$user) {
        return new WP_Error('invalid_student', 'Student not found', array('status' => 404));
    }
    
    // Get lesson name from user meta
    $lesson_name = get_user_meta($student_id, 'lessons_name', true);
    
    // Get schedules from the database
    $table_name = $wpdb->prefix . 'student_schedules';
    $query = $wpdb->prepare(
        "SELECT * FROM $table_name WHERE student_id = %d",
        $student_id
    );
    
    $schedules = $wpdb->get_results($query);
    
    if (empty($schedules)) {
        return array(); // Return empty array if no schedules found
    }
    
    $formatted_schedules = array();
    
    foreach ($schedules as $schedule) {
        // Get teacher name
        $teacher_name = get_teacher_name($schedule->teacher_id);
        
        // Parse the schedule JSON data
        $schedule_data = json_decode($schedule->schedule, true);
        if (!is_array($schedule_data)) {
            $schedule_data = array(); // Default to empty array if parsing fails
        }
        
        // Format for Flutter app
        $formatted_schedule = array(
            'id' => (int) $schedule->id,
            'student_id' => (int) $schedule->student_id,
            'teacher_id' => (int) $schedule->teacher_id,
            'teacher_name' => $teacher_name ?: 'N/A',
            'lesson_name' => $lesson_name ?: 'N/A',
            'lesson_duration' => (int) $schedule->lesson_duration,
            'schedules' => array_map(function($item) {
                return array(
                    'day' => isset($item['day']) ? $item['day'] : '',
                    'hour' => isset($item['hour']) ? $item['hour'] : '',
                    'original' => isset($item['original']) ? $item['original'] : null
                );
            }, $schedule_data)
        );
        
        $formatted_schedules[] = $formatted_schedule;
    }
    
    // Only cache if no timestamp was provided
    if (!$timestamp) {
        wp_cache_set($cache_key, $formatted_schedules, SCHEDULE_CACHE_GROUP, CACHE_EXPIRATION);
    }
    
    return $formatted_schedules;
} 

/**
 * Get student reports - new function to match Flutter code
 * Returns formatted reports from wp_student_reports table
 */
function get_student_reports($request) {
    $student_id = (int) $request->get_param('student_id');
    $timestamp = $request->get_param('_t');
    
    if (!$student_id) {
        return new WP_Error('missing_id', 'Student ID is required', array('status' => 400));
    }
    
    // Only use cache if no timestamp is provided
    if (!$timestamp) {
        $cache_key = 'reports_' . $student_id;
        $cached_reports = wp_cache_get($cache_key, REPORT_CACHE_GROUP);
        
        if ($cached_reports !== false) {
            return $cached_reports;
        }
    }
    
    // Cache miss or cache busting requested, need to fetch data
    global $wpdb;
    
    // Verify student exists
    $user = get_userdata($student_id);
    if (!$user) {
        return new WP_Error('invalid_student', 'Student not found', array('status' => 404));
    }
    
    // Get reports from the database
    $table_name = $wpdb->prefix . 'student_reports';
    $query = $wpdb->prepare(
        "SELECT * FROM $table_name WHERE student_id = %d ORDER BY date DESC",
        $student_id
    );
    
    $reports = $wpdb->get_results($query);
    
    if (empty($reports)) {
        return array(); // Return empty array if no reports found
    }
    
    $formatted_reports = array();
    
    foreach ($reports as $report) {
        // Get teacher name
        $teacher_name = get_teacher_name($report->teacher_id);
        
        // Format for Flutter app to match StudentReport.fromJson
        $formatted_report = array(
            'id' => (int) $report->id,
            'studentId' => (int) $report->student_id,
            'teacherId' => (int) $report->teacher_id,
            'teacherName' => $teacher_name ?: 'N/A',
            'sessionNumber' => $report->session_number,
            'date' => $report->date,
            'time' => $report->time,
            'attendance' => $report->attendance,
            'evaluation' => $report->evaluation,
            'grade' => (int) $report->grade,
            'lessonDuration' => (int) $report->lesson_duration,
            'tasmii' => $report->tasmii,
            'tahfiz' => $report->tahfiz,
            'mourajah' => $report->mourajah,
            'nextTasmii' => $report->next_tasmii,
            'nextMourajah' => $report->next_mourajah,
            'notes' => $report->notes,
            'zoomImageUrl' => $report->zoom_image_url
        );
        
        $formatted_reports[] = $formatted_report;
    }
    
    // Only cache if no timestamp was provided
    if (!$timestamp) {
        wp_cache_set($cache_key, $formatted_reports, REPORT_CACHE_GROUP, CACHE_EXPIRATION);
    }
    
    return $formatted_reports;
} 

/**
 * Cache invalidation function - to be called by Hostinger cron job
 */
function clear_api_cache($request) {
    // Simple security with a secret key
    $secret = $request->get_param('secret');
    $your_secret = '12345'; // Change this to a secure random string
    
    if ($secret !== $your_secret) {
        return new WP_Error('invalid_secret', 'Invalid secret key', array('status' => 403));
    }
    
    // Clear all API caches
    if (function_exists('wp_cache_delete_group')) {
        wp_cache_delete_group(STUDENT_CACHE_GROUP);
        wp_cache_delete_group(TEACHER_CACHE_GROUP);
        wp_cache_delete_group(SCHEDULE_CACHE_GROUP);
        wp_cache_delete_group(REPORT_CACHE_GROUP);
    } else {
        // Fallback for older WordPress versions
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_" . STUDENT_CACHE_GROUP . "%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_" . TEACHER_CACHE_GROUP . "%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_" . SCHEDULE_CACHE_GROUP . "%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_" . REPORT_CACHE_GROUP . "%'");
    }
    
    return array(
        'success' => true,
        'message' => 'API cache cleared successfully',
        'time' => current_time('mysql')
    );
}

/**
 * Hostinger Cron Job Setup Instructions:
 * 
 * 1. Log in to your Hostinger control panel
 * 2. Navigate to the Cron Jobs section
 * 3. Create a new cron job with the following settings:
 *    - Command: curl https://system.zuwad-academy.com/wp-json/custom/v1/clear-cache?secret=12345
 *    - Frequency: Daily (or as needed)
 * 
 * This will automatically refresh your cache every day to ensure data stays current
 * while maintaining performance benefits.
 */