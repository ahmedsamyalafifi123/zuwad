<?php
/**
 * Zuwad Plugin Uninstaller
 *
 * This file runs when the plugin is uninstalled.
 * It cleans up all plugin data, including options, user meta, and custom tables.
 *
 * @package           ZuwadPlugin
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Define plugin constants if they don't exist
if (!defined('ZUWAD_PLUGIN_VERSION')) {
    define('ZUWAD_PLUGIN_VERSION', '1.3.568');
}

/**
 * Clean up all plugin options
 */
function zuwad_plugin_cleanup_options() {
    // List of all plugin options to remove
    $options = array(
        'zuwad_settings',
        'zuwad_theme_settings',
        'zuwad_dashboard_settings',
        'waapi_sending_status',
        'waapi_sent_numbers',
        // Add any other options your plugin uses
    );
    
    // Delete all listed options
    foreach ($options as $option) {
        delete_option($option);
    }
}

/**
 * Clean up user meta data added by the plugin
 */
function zuwad_plugin_cleanup_user_meta() {
    global $wpdb;
    
    // List of meta keys used by the plugin
    $meta_keys = array(
        'teacher',
        'supervisor',
        'lessons_number',
        'lesson_duration',
        'currency',
        'amount',
        'payment_status',
        'teacher_classification',
        'teacher_status',
        'parent_name',
        'parent_phone',
        'parent_email',
        // Add any other meta keys your plugin uses
    );
    
    // Create a SQL-safe string of meta keys
    $meta_keys_string = "'" . implode("','", $meta_keys) . "'";
    
    // Delete user meta in bulk
    $wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key IN ($meta_keys_string)");
}

/**
 * Remove any custom tables created by the plugin
 */
function zuwad_plugin_cleanup_tables() {
    global $wpdb;
    
    // List of custom tables (without the prefix)
    $tables = array(
        'zuwad_student_reports',
        'zuwad_student_schedules',
        'zuwad_teacher_calendar',
        // Add any other tables your plugin creates
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
    }
}

/**
 * Remove any uploaded media files
 */
function zuwad_plugin_cleanup_uploads() {
    // Get the uploads directory path
    $upload_dir = wp_upload_dir();
    
    // Define the plugin's upload directory
    $plugin_dir = $upload_dir['basedir'] . '/zuwad-plugin/';
    
    // Check if the directory exists
    if (is_dir($plugin_dir)) {
        // Recursively delete the directory and its contents
        zuwad_plugin_remove_directory($plugin_dir);
    }
}

/**
 * Helper function to recursively remove a directory
 */
function zuwad_plugin_remove_directory($dir) {
    if (!is_dir($dir)) {
        return;
    }
    
    $files = array_diff(scandir($dir), array('.', '..'));
    
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            zuwad_plugin_remove_directory($path);
        } else {
            unlink($path);
        }
    }
    
    rmdir($dir);
    
    return;
}

// Execute all cleanup functions
zuwad_plugin_cleanup_options();
zuwad_plugin_cleanup_user_meta();
zuwad_plugin_cleanup_tables();
zuwad_plugin_cleanup_uploads();

// Clear any scheduled cron jobs
wp_clear_scheduled_hook('check_schedules_cron');

// Flush rewrite rules to remove any custom rewrite rules
flush_rewrite_rules();
