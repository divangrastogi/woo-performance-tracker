<?php
/**
 * Deactivator class for WooPerformance Tracker
 *
 * Handles plugin deactivation tasks
 *
 * @package WooPerformanceTracker
 * @since 1.0.0
 */

namespace WooPerformanceTracker;

/**
 * Deactivator class
 */
class Deactivator {

    /**
     * Plugin deactivation hook
     */
    public static function deactivate() {
        self::unschedule_cleanup_cron();
        self::clear_transients();
        flush_rewrite_rules();
    }

    /**
     * Unschedule the data cleanup cron job
     */
    private static function unschedule_cleanup_cron() {
        $timestamp = wp_next_scheduled('wpt_cleanup_old_data');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'wpt_cleanup_old_data');
        }
    }

    /**
     * Clear plugin-related transients
     */
    private static function clear_transients() {
        global $wpdb;

        // Delete all plugin transients
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
    }
}