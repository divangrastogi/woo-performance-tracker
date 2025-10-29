<?php
/**
 * Uninstall script for WooPerformance Tracker
 *
 * Removes all plugin data when plugin is uninstalled
 *
 * @package WooPerformanceTracker
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if user wants to remove data
$remove_data = get_option('wpt_remove_data_on_uninstall', false);

if ($remove_data) {
    global $wpdb;

    // Drop custom table
    $table_name = $wpdb->prefix . 'wc_performance_logs';
    $wpdb->query("DROP TABLE IF EXISTS {$table_name}");

    // Remove all plugin options
    $option_names = array(
        'wpt_tracking_enabled',
        'wpt_data_retention_days',
        'wpt_cache_duration',
        'wpt_exclude_user_roles',
        'wpt_track_anonymous',
        'wpt_anonymize_ip',
        'wpt_auto_cleanup',
        'wpt_db_version',
        'wpt_remove_data_on_uninstall',
    );

    foreach ($option_names as $option_name) {
        delete_option($option_name);
    }

    // Clear any transients
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            '_transient_wpt_%'
        )
    );

    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            '_transient_timeout_wpt_%'
        )
    );

    // Clear scheduled events
    wp_clear_scheduled_hook('wpt_cleanup_old_data');
}