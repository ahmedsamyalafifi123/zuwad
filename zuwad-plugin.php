<?php

/**
 * Zuwad Plugin
 *
 * @package           ZuwadPlugin
 * @author            Afifi :)
 * @copyright         Zuwad Academy
 *
 * @wordpress-plugin
 * Plugin Name:       Zuwad Plugin
 * Plugin URI:        https://zuwad-academy.com
 * Description:       Custom plugin for Zuwad Academy
 * Version:           1.3.984
 * Author:            Zuwad Academy
 * Author URI:        https://zuwad-academy.com
 * Text Domain:       zuwad-plugin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('ZUWAD_PLUGIN_VERSION', '1.3.984');
define('ZUWAD_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ZUWAD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NOTIFICATION_LOG_PATH', WP_CONTENT_DIR . '/notification_logs.log');

/**
 * Include necessary files
 */
function zuwad_plugin_includes()
{
    // Core classes and functionality
    require_once ZUWAD_PLUGIN_PATH . 'includes/class-zuwad-plugin-settings.php';

    // Templates and pages
    require_once ZUWAD_PLUGIN_PATH . 'templates/supervisor.php';

    // Feature modules
    require_once ZUWAD_PLUGIN_PATH . 'includes/student_schedules.php';
    require_once ZUWAD_PLUGIN_PATH . 'includes/student_reports.php';
    require_once ZUWAD_PLUGIN_PATH . 'includes/teacher_calendar.php';
    require_once ZUWAD_PLUGIN_PATH . 'includes/whatsapp.php';
    require_once ZUWAD_PLUGIN_PATH . 'includes/sales.php';
    require_once ZUWAD_PLUGIN_PATH . 'includes/api.php';
    require_once ZUWAD_PLUGIN_PATH . 'includes/wrong_session_numbers.php';
    require_once ZUWAD_PLUGIN_PATH . 'includes/homepage.php';

    // Page controllers
    require_once ZUWAD_PLUGIN_PATH . 'pages/payment/payment.php';
    require_once ZUWAD_PLUGIN_PATH . 'pages/payment/payment_receipts/payment_receipts_shortcode.php';
    require_once ZUWAD_PLUGIN_PATH . 'pages/send/wa-send-form.php';
    require_once ZUWAD_PLUGIN_PATH . 'pages/analytics/student_dashboard.php';

    // These files are commented out - may be added back in future versions
    // require_once ZUWAD_PLUGIN_PATH . 'includes/class-zuwad-plugin-roles.php';
    // require_once ZUWAD_PLUGIN_PATH . 'includes/whatsapp-cron.php';
    // require_once ZUWAD_PLUGIN_PATH . 'includes/hostinger-cron.php';
    // require_once ZUWAD_PLUGIN_PATH . 'includes/whatsapp-cron-test.php';
}

/**
 * Plugin activation hook
 */
function zuwad_plugin_activate()
{
    // Add activation tasks here (e.g., create custom tables, set default options)
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'zuwad_plugin_activate');

/**
 * Plugin deactivation hook
 */
function zuwad_plugin_deactivate()
{
    // Clear scheduled events
    wp_clear_scheduled_hook('check_schedules_cron');

    // Flush rewrite rules to remove any custom permalinks
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'zuwad_plugin_deactivate');

/**
 * Register the shortcode to display the settings page
 */
function zuwad_plugin_shortcode()
{
    ob_start();
    include ZUWAD_PLUGIN_PATH . 'templates/settings-page.php';
    return ob_get_clean();
}
add_shortcode('zuwad_settings', 'zuwad_plugin_shortcode');

/**
 * Load plugin text domain for translations
 */
function zuwad_plugin_load_textdomain()
{
    load_plugin_textdomain('zuwad-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'zuwad_plugin_load_textdomain');

/**
 * Check if a shortcode is present in the current page content
 *
 * @param string $shortcode The shortcode to check for
 * @return boolean True if the shortcode is present, false otherwise
 */
function zuwad_has_shortcode($shortcode) {
    global $post;
    
    // Check if we're on a singular page and post content exists
    if (is_singular() && is_a($post, 'WP_Post')) {
        return has_shortcode($post->post_content, $shortcode);
    }
    
    return false;
}

/**
 * Enqueue plugin assets (CSS/JS)
 */
function zuwad_plugin_enqueue_assets()
{
    // Common third-party libraries
    wp_enqueue_script('jspdf', 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js', array(), null, true);
    wp_enqueue_script('html2canvas', 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js', array(), null, true);
    wp_enqueue_style('sweetalert2-css', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css');
    wp_enqueue_script('sweetalert2-js', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), null, true);

    // Common plugin scripts and styles
    wp_enqueue_script('zuwad-report-utils', ZUWAD_PLUGIN_URL . 'assets/js/zuwad-report-utils.js', array('jquery', 'jspdf', 'html2canvas', 'sweetalert2-js'), ZUWAD_PLUGIN_VERSION, true);
    wp_enqueue_script('zuwad-pdf-script', ZUWAD_PLUGIN_URL . 'assets/js/pdf.js', array('jquery', 'jspdf', 'html2canvas', 'zuwad-report-utils'), ZUWAD_PLUGIN_VERSION, true);
    wp_enqueue_script('zuwad-helper-functions', ZUWAD_PLUGIN_URL . 'assets/js/helper-functions.js', array('jquery'), ZUWAD_PLUGIN_VERSION, true);

    // Check if we're on a page with the zuwad_homepage shortcode or the payments page
    if (zuwad_has_shortcode('zuwad_homepage')) {
        // Load all necessary assets for the homepage/dashboard
        wp_enqueue_style('zuwad-payment-style', ZUWAD_PLUGIN_URL . 'pages/payment/payment.css', array(), ZUWAD_PLUGIN_VERSION);
        wp_enqueue_script('zuwad-payment-script', ZUWAD_PLUGIN_URL . 'pages/payment/payment.js', array('jquery', 'zuwad-helper-functions'), ZUWAD_PLUGIN_VERSION, true);
        wp_enqueue_style('zuwad-supervisor-style', ZUWAD_PLUGIN_URL . 'assets/css/supervisor.css', array(), ZUWAD_PLUGIN_VERSION);
        
        wp_localize_script('zuwad-payment-script', 'zuwadPlugin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('zuwad_plugin_nonce'),
            'userRole' => zuwad_get_current_user_role()
        ));
    }

    if (is_page("payments")) {
        wp_enqueue_style('zuwad-supervisor-style', ZUWAD_PLUGIN_URL . 'assets/css/supervisor.css', array(), ZUWAD_PLUGIN_VERSION);
        wp_enqueue_style('zuwad-payment-receipts-style', ZUWAD_PLUGIN_URL . 'pages/payment/payment_receipts/payment_receipts.css', array(), ZUWAD_PLUGIN_VERSION);
        wp_enqueue_script('zuwad-payment-receipts-script', ZUWAD_PLUGIN_URL . 'pages/payment/payment_receipts/payment_receipts.js', array('jquery'), ZUWAD_PLUGIN_VERSION, true);
        
        // Localize the payment script with AJAX URL and nonce
        wp_localize_script('zuwad-payment-receipts-script', 'zuwadPlugin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('zuwad_plugin_nonce'),
            'userRole' => zuwad_get_current_user_role()
        ));
    }

    if (is_page("send")) {
        wp_enqueue_style('waapi-styles', ZUWAD_PLUGIN_URL . 'pages/send/wa-send-form.css', array(), ZUWAD_PLUGIN_VERSION);
        wp_enqueue_script('zuwad-send-script', ZUWAD_PLUGIN_URL . 'pages/send/wa-send-form.js', array('jquery', 'zuwad-helper-functions'), ZUWAD_PLUGIN_VERSION, true);

        // Localize the script with AJAX URL and nonce
        wp_localize_script('zuwad-send-script', 'waapiData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('waapi-nonce')
        ));
    }

    if (is_page("analytics")) {
        wp_enqueue_style('zuwad-dashboard-style', ZUWAD_PLUGIN_URL . 'pages/analytics/student_dashboard.css', array(), ZUWAD_PLUGIN_VERSION);
        wp_enqueue_script('zuwad-dashboard-script', ZUWAD_PLUGIN_URL . 'pages/analytics/student_dashboard.js', array('jquery', 'zuwad-helper-functions'), ZUWAD_PLUGIN_VERSION, true);
    }
    


    if (is_page("my-students")) {
        wp_enqueue_style('zuwad-supervisor-style', ZUWAD_PLUGIN_URL . 'assets/css/supervisor.css', array(), ZUWAD_PLUGIN_VERSION);
    }



    if (is_front_page() || is_home()) {
        // Load libraries for front page
        wp_enqueue_style('lightbox2-css', 'https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css');
        wp_enqueue_script('lightbox2-js', 'https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js', array('jquery'), null, true);

        wp_enqueue_style('flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
        wp_enqueue_script('flatpickr-js', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js', array('jquery'), null, true);

        wp_enqueue_style('fullcalendar-css', 'https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css');
        wp_enqueue_script('fullcalendar-js', 'https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js', array('jquery'), null, true);

        // Load all plugin styles and scripts for front page
        wp_enqueue_style('zuwad-calendar-style', ZUWAD_PLUGIN_URL . 'assets/css/teacher_calendar.css', array(), ZUWAD_PLUGIN_VERSION);
        wp_enqueue_script('zuwad-calendar-script', ZUWAD_PLUGIN_URL . 'assets/js/teacher_calendar.js', array('jquery', 'fullcalendar-js', 'zuwad-helper-functions', 'zuwad-report-utils'), ZUWAD_PLUGIN_VERSION, true);

        wp_enqueue_style('zuwad-report-style', ZUWAD_PLUGIN_URL . 'assets/css/student_reports.css', array(), ZUWAD_PLUGIN_VERSION);
        wp_enqueue_script('zuwad-report-script', ZUWAD_PLUGIN_URL . 'assets/js/student_reports.js', array('jquery', 'zuwad-helper-functions'), ZUWAD_PLUGIN_VERSION, true);

        wp_enqueue_style('zuwad-schedules-style', ZUWAD_PLUGIN_URL . 'assets/css/student_schedules.css', array(), ZUWAD_PLUGIN_VERSION);
        wp_enqueue_script('zuwad-schedules-script', ZUWAD_PLUGIN_URL . 'assets/js/student_schedules.js', array('jquery', 'zuwad-helper-functions'), ZUWAD_PLUGIN_VERSION, true);

        wp_enqueue_style('zuwad-plugin-style', ZUWAD_PLUGIN_URL . 'assets/css/style.css', array(), ZUWAD_PLUGIN_VERSION);
        wp_enqueue_script('zuwad-plugin-script', ZUWAD_PLUGIN_URL . 'assets/js/script.js', array('jquery', 'zuwad-helper-functions'), ZUWAD_PLUGIN_VERSION, true);

        wp_enqueue_style('zuwad-supervisor-style', ZUWAD_PLUGIN_URL . 'assets/css/supervisor.css', array(), ZUWAD_PLUGIN_VERSION);
        wp_enqueue_script('zuwad-supervisor-script', ZUWAD_PLUGIN_URL . 'assets/js/supervisor.js', array('jquery', 'zuwad-helper-functions'), ZUWAD_PLUGIN_VERSION, true);
    }

    // Localize scripts for AJAX
    wp_localize_script('zuwad-dashboard-script', 'zuwadDashboard', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('zuwad_dashboard_nonce')
    ));

    wp_localize_script('zuwad-plugin-script', 'zuwadPlugin', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('zuwad_plugin_nonce'),
        'userRole' => zuwad_get_current_user_role()
    ));
}
add_action('wp_enqueue_scripts', 'zuwad_plugin_enqueue_assets');

/**
 * Helper function to get current user role
 * 
 * @return string The current user's role or 'guest' if not logged in
 */
function zuwad_get_current_user_role()
{
    $current_user = wp_get_current_user();
    if (!$current_user) {
        return 'guest';
    }

    // If user has multiple roles, return the first one
    $roles = $current_user->roles;
    return !empty($roles) ? $roles[0] : 'guest';
}

/**
 * AJAX handler to get all teachers
 */
function zuwad_plugin_get_teachers()
{
    $teachers = get_users(array(
        'role' => 'teacher',  // Fetch users with the 'teacher' role
    ));

    $teacher_options = array();

    foreach ($teachers as $teacher) {
        $teacher_options[] = array(
            'id' => $teacher->ID,
            'name' => $teacher->display_name . ' ' . $teacher->last_name,
        );
    }

    wp_send_json_success($teacher_options);
}
add_action('wp_ajax_get_teachers', 'zuwad_plugin_get_teachers');
add_action('wp_ajax_nopriv_get_teachers', 'zuwad_plugin_get_teachers');

/**
 * AJAX handler to get all supervisors
 */
function zuwad_plugin_get_supervisors()
{
    // Fetch users with the 'supervisor' role
    $supervisors = get_users(array(
        'role' => 'supervisor',
        'fields' => array('ID', 'display_name'),
    ));

    if (empty($supervisors)) {
        wp_send_json_error(array('message' => 'لا يوجد مشرفين متاحين.'));
    }

    // Prepare the response
    $response = array();
    foreach ($supervisors as $supervisor) {
        $response[] = array(
            'id' => $supervisor->ID,
            'name' => $supervisor->display_name,
        );
    }

    wp_send_json_success($response);
}
add_action('wp_ajax_get_supervisors', 'zuwad_plugin_get_supervisors');
add_action('wp_ajax_nopriv_get_supervisors', 'zuwad_plugin_get_supervisors');

/**
 * AJAX handler for report image uploads
 */
function handle_report_image_upload()
{
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $uploaded_file = $_FILES['report_image'];

    $upload_overrides = array(
        'test_form' => false
    );

    $movefile = wp_handle_upload($uploaded_file, $upload_overrides);

    if ($movefile && !isset($movefile['error'])) {
        // Prepare file attachment data
        $attachment = array(
            'post_mime_type' => $movefile['type'],
            'post_title' => sanitize_file_name($uploaded_file['name']),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        // Insert attachment to the media library
        $attach_id = wp_insert_attachment($attachment, $movefile['file']);

        if (!is_wp_error($attach_id)) {
            wp_update_attachment_metadata(
                $attach_id,
                wp_generate_attachment_metadata($attach_id, $movefile['file'])
            );

            wp_send_json_success(array(
                'url' => $movefile['url'],
                'attachment_id' => $attach_id
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to insert attachment'));
        }
    } else {
        wp_send_json_error(array('message' => $movefile['error']));
    }

    wp_die();
}
add_action('wp_ajax_upload_report_image', 'handle_report_image_upload');
add_action('wp_ajax_nopriv_upload_report_image', 'handle_report_image_upload');

/**
 * AJAX handler to get student phone number
 */
function zuwad_get_student_phone()
{
    // Ensure nonce is checked first
    check_ajax_referer('zuwad_plugin_nonce', '_ajax_nonce');

    // Explicitly check for student_id in POST data
    if (!isset($_POST['student_id'])) {
        wp_send_json_error('Student ID is missing from the request');
        return;
    }

    $student_id = intval($_POST['student_id']);

    if (!$student_id) {
        error_log('zuwad_get_student_phone: Invalid student ID');
        wp_send_json_error('Invalid student ID');
        return;
    }

    // Verify the user exists
    $user = get_userdata($student_id);
    if (!$user) {
        wp_send_json_error('User not found');
        return;
    }

    // Get phone number from user meta
    $phone = get_user_meta($student_id, 'phone', true);
    // error_log('zuwad_get_student_phone: Raw phone from user meta: ' . print_r($phone, true));

    // Sanitize phone number (remove any non-digit characters)
    $phone = preg_replace('/[^0-9]/', '', $phone);
    // error_log('zuwad_get_student_phone: Sanitized phone: ' . $phone);

    if (empty($phone)) {
        wp_send_json_error('Phone number not found');
        return;
    }

    // Log successful phone retrieval
    // error_log('zuwad_get_student_phone: Successfully retrieved phone: ' . $phone);
    wp_send_json_success(['phone' => $phone]);
}
add_action('wp_ajax_get_student_phone', 'zuwad_get_student_phone');
add_action('wp_ajax_nopriv_get_student_phone', 'zuwad_get_student_phone');

// Initialize the plugin
zuwad_plugin_includes();
