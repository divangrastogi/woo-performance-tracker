<?php
/**
 * Activator class for WooPerformance Tracker
 *
 * Handles plugin activation tasks including database table creation
 *
 * @package WooPerformanceTracker
 * @since 1.0.0
 */

namespace WooPerformanceTracker;

/**
 * Activator class
 */
class Activator {

    /**
     * Plugin activation hook
     */
    public static function activate() {
        self::create_database_table();
        self::set_default_options();
        self::schedule_cleanup_cron();
        flush_rewrite_rules();
    }

    /**
     * Create the performance logs database table
     */
    private static function create_database_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wc_performance_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            product_id bigint(20) unsigned DEFAULT NULL,
            order_id bigint(20) unsigned DEFAULT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            session_id varchar(255) DEFAULT NULL,
            revenue decimal(10,2) DEFAULT NULL,
            metadata longtext DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent varchar(255) DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY product_id (product_id),
            KEY created_at (created_at),
            KEY session_id (session_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Store table version for future upgrades
        add_option('wpt_db_version', '1.0.0');
    }

    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        $defaults = array(
            'wpt_tracking_enabled' => '1',
            'wpt_data_retention_days' => '90',
            'wpt_cache_duration' => '300', // 5 minutes
            'wpt_exclude_user_roles' => array(),
            'wpt_track_anonymous' => '1',
            'wpt_anonymize_ip' => '0',
            'wpt_auto_cleanup' => '1',
        );

        foreach ($defaults as $option => $value) {
            if (!get_option($option)) {
                add_option($option, $value);
            }
        }
    }

    /**
     * Schedule the data cleanup cron job
     */
    private static function schedule_cleanup_cron() {
        if (!wp_next_scheduled('wpt_cleanup_old_data')) {
            wp_schedule_event(time(), 'daily', 'wpt_cleanup_old_data');
        }
    }
}