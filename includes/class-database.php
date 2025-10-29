<?php
/**
 * Database operations for WooPerformance Tracker
 *
 * Handles all database interactions for performance logging
 *
 * @package WooPerformanceTracker
 * @since 1.0.0
 */

namespace WooPerformanceTracker;

/**
 * Database class
 */
class Database {

    /**
     * Get the table name
     *
     * @return string
     */
    private function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'wc_performance_logs';
    }

    /**
     * Check if table exists
     *
     * @return bool
     */
    public function table_exists() {
        global $wpdb;
        $table_name = $this->get_table_name();
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;

        // If table doesn't exist, try to create it
        if (!$exists) {
            $this->create_table();
            return $this->table_exists();
        }

        return true;
    }

    /**
     * Create the database table if it doesn't exist
     *
     * @return bool
     */
    public function create_table() {
        global $wpdb;

        $table_name = $this->get_table_name();
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

        return true;
    }

    /**
     * Insert a tracking event
     *
     * @param array $data Event data to insert
     * @return int|false Insert ID on success, false on failure
     */
    public function insert_event($data) {
        global $wpdb;

        $defaults = array(
            'event_type' => '',
            'product_id' => null,
            'order_id' => null,
            'user_id' => get_current_user_id() ?: null,
            'session_id' => null,
            'revenue' => null,
            'metadata' => null,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : null,
            'created_at' => current_time('mysql'),
        );

        $data = wp_parse_args($data, $defaults);

        // Sanitize data
        $data = $this->sanitize_event_data($data);

        $result = $wpdb->insert(
            $this->get_table_name(),
            $data,
            array('%s', '%d', '%d', '%d', '%s', '%f', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            error_log('WPT Database Error: ' . $wpdb->last_error);
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Get events with filters
     *
     * @param array $filters Query filters
     * @return array
     */
    public function get_events($filters = array()) {
        global $wpdb;

        $defaults = array(
            'event_type' => null,
            'product_id' => null,
            'user_id' => null,
            'date_from' => null,
            'date_to' => null,
            'limit' => 1000,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
        );

        $filters = wp_parse_args($filters, $defaults);

        $where = array();
        $where_values = array();

        if (!empty($filters['event_type'])) {
            $where[] = 'event_type = %s';
            $where_values[] = $filters['event_type'];
        }

        if (!empty($filters['product_id'])) {
            $where[] = 'product_id = %d';
            $where_values[] = intval($filters['product_id']);
        }

        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = %d';
            $where_values[] = intval($filters['user_id']);
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= %s';
            $where_values[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= %s';
            $where_values[] = $filters['date_to'];
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $limit_clause = $wpdb->prepare('LIMIT %d OFFSET %d', $filters['limit'], $filters['offset']);
        $order_clause = $wpdb->prepare('ORDER BY %s %s', $filters['orderby'], $filters['order']);

        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->get_table_name()} {$where_clause} {$order_clause} {$limit_clause}",
            $where_values
        );

        return $wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Get aggregated statistics
     *
     * @param array $date_range Date range filters
     * @return array
     */
    public function get_stats($date_range = array()) {
        if (!$this->table_exists()) {
            return array(
                'total_views' => 0,
                'total_add_to_cart' => 0,
                'total_orders' => 0,
                'total_revenue' => 0.00,
                'unique_sessions' => 0,
            );
        }

        global $wpdb;

        $table_name = $this->get_table_name();

        $where = '';
        $where_values = array();

        if (!empty($date_range['from']) && !empty($date_range['to'])) {
            $where = 'WHERE created_at BETWEEN %s AND %s';
            $where_values = array($date_range['from'], $date_range['to']);
        }

        $sql = $wpdb->prepare(
            "SELECT
                COUNT(CASE WHEN event_type = 'product_view' THEN 1 END) as total_views,
                COUNT(CASE WHEN event_type = 'add_to_cart' THEN 1 END) as total_add_to_cart,
                COUNT(CASE WHEN event_type = 'order_completed' THEN 1 END) as total_orders,
                SUM(revenue) as total_revenue,
                COUNT(DISTINCT session_id) as unique_sessions
            FROM {$table_name} {$where}",
            $where_values
        );

        $result = $wpdb->get_row($sql, ARRAY_A);

        if (!$result) {
            return array(
                'total_views' => 0,
                'total_add_to_cart' => 0,
                'total_orders' => 0,
                'total_revenue' => 0.00,
                'unique_sessions' => 0,
            );
        }

        global $wpdb;

        $table_name = $this->get_table_name();

        $where = '';
        $where_values = array();

        if (!empty($date_range['from']) && !empty($date_range['to'])) {
            $where = 'WHERE created_at BETWEEN %s AND %s';
            $where_values = array($date_range['from'], $date_range['to']);
        }

        $sql = $wpdb->prepare(
            "SELECT
                COUNT(CASE WHEN event_type = 'product_view' THEN 1 END) as total_views,
                COUNT(CASE WHEN event_type = 'add_to_cart' THEN 1 END) as total_add_to_cart,
                COUNT(CASE WHEN event_type = 'order_completed' THEN 1 END) as total_orders,
                SUM(revenue) as total_revenue,
                COUNT(DISTINCT session_id) as unique_sessions
            FROM {$table_name} {$where}",
            $where_values
        );

        $result = $wpdb->get_row($sql, ARRAY_A);

        if (!$result) {
            return array(
                'total_views' => 0,
                'total_add_to_cart' => 0,
                'total_orders' => 0,
                'total_revenue' => 0.00,
                'unique_sessions' => 0,
            );
        }

        return $result;
    }

    /**
     * Clean up old data
     *
     * @param int $days Number of days to keep
     * @return int Number of deleted rows
     */
    public function cleanup_old_data($days = 90) {
        global $wpdb;

        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->get_table_name()} WHERE created_at < %s",
                $cutoff_date
            )
        );
    }

    /**
     * Get client IP address
     *
     * @return string|null
     */
    private function get_client_ip() {
        $ip_headers = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        );

        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = sanitize_text_field(wp_unslash($_SERVER[$header]));
                // Handle comma-separated IPs (like X-Forwarded-For)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return null;
    }

    /**
     * Sanitize event data
     *
     * @param array $data Event data
     * @return array Sanitized data
     */
    private function sanitize_event_data($data) {
        $sanitized = array();

        foreach ($data as $key => $value) {
            switch ($key) {
                case 'event_type':
                    $sanitized[$key] = sanitize_key($value);
                    break;
                case 'product_id':
                case 'order_id':
                case 'user_id':
                    $sanitized[$key] = $value ? intval($value) : null;
                    break;
                case 'session_id':
                    $sanitized[$key] = $value ? sanitize_text_field($value) : null;
                    break;
                case 'revenue':
                    $sanitized[$key] = $value ? floatval($value) : null;
                    break;
                case 'metadata':
                    $sanitized[$key] = $value ? wp_json_encode($value) : null;
                    break;
                case 'ip_address':
                    $sanitized[$key] = $value ? sanitize_text_field($value) : null;
                    break;
                case 'user_agent':
                    $sanitized[$key] = $value ? sanitize_text_field($value) : null;
                    break;
                case 'created_at':
                    $sanitized[$key] = $value ? sanitize_text_field($value) : current_time('mysql');
                    break;
                default:
                    $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}