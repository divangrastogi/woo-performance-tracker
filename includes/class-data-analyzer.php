<?php
/**
 * Data analyzer for WooPerformance Tracker
 *
 * Calculates performance metrics and insights
 *
 * @package WooPerformanceTracker
 * @since 1.0.0
 */

namespace WooPerformanceTracker;

/**
 * Data_Analyzer class
 */
class Data_Analyzer {

    /**
     * Database instance
     *
     * @var Database
     */
    private $db;

    /**
     * Constructor
     */
    public function __construct() {
        // Database will be set later or lazy-loaded
    }

    /**
     * Set database instance
     *
     * @param Database $db Database instance
     */
    public function set_database($db) {
        $this->db = $db;
    }

    /**
     * Calculate conversion rate
     *
     * @param int $views Number of views
     * @param int $orders Number of orders
     * @return float
     */
    public function calculate_conversion_rate($views, $orders) {
        if ($views == 0) {
            return 0.0;
        }
        return round(($orders / $views) * 100, 2);
    }

    /**
     * Get top performing products
     *
     * @param int $limit Number of products to return
     * @param array $date_range Date range filters
     * @param string $orderby Sort field
     * @return array
     */
    public function get_top_products($limit = 10, $date_range = array(), $orderby = 'views') {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wc_performance_logs';

        $where = '';
        $where_values = array();

        if (!empty($date_range['from']) && !empty($date_range['to'])) {
            $where = 'AND created_at BETWEEN %s AND %s';
            $where_values = array($date_range['from'], $date_range['to']);
        }

        $order_map = array(
            'views' => 'total_views',
            'add_to_cart' => 'total_add_to_cart',
            'orders' => 'total_orders',
            'revenue' => 'total_revenue',
        );

        $order_field = isset($order_map[$orderby]) ? $order_map[$orderby] : 'total_views';

        $sql = $wpdb->prepare(
            "SELECT
                product_id,
                COUNT(CASE WHEN event_type = 'product_view' THEN 1 END) as total_views,
                COUNT(CASE WHEN event_type = 'add_to_cart' THEN 1 END) as total_add_to_cart,
                COUNT(CASE WHEN event_type = 'order_completed' THEN 1 END) as total_orders,
                SUM(revenue) as total_revenue
            FROM {$table_name}
            WHERE product_id IS NOT NULL {$where}
            GROUP BY product_id
            ORDER BY {$order_field} DESC
            LIMIT %d",
            array_merge($where_values, array($limit))
        );

        $results = $wpdb->get_results($sql, ARRAY_A);

        // Debug: log the query and results
        if (WP_DEBUG) {
            error_log('WPT get_top_products SQL: ' . $sql);
            error_log('WPT get_top_products results count: ' . count($results));
        }

        $products = array();
        foreach ($results as $result) {
            $product_id = intval($result['product_id']);

            // Get product info safely
            $product_name = 'Product #' . $product_id;
            $product_url = '#';

            if (function_exists('wc_get_product')) {
                try {
                    $product = wc_get_product($product_id);
                    if ($product) {
                        $product_name = $product->get_name();
                        $product_url = get_permalink($product_id) ?: '#';
                    }
                } catch (Exception $e) {
                    // Product might not exist or WooCommerce not available
                    $product_name = 'Product #' . $product_id . ' (Not Found)';
                }
            }

            $products[] = array(
                'product_id' => $product_id,
                'product_name' => $product_name,
                'product_url' => $product_url,
                'views' => intval($result['total_views']),
                'add_to_cart' => intval($result['total_add_to_cart']),
                'orders' => intval($result['total_orders']),
                'revenue' => floatval($result['total_revenue']),
                'conversion_rate' => $this->calculate_conversion_rate($result['total_views'], $result['total_orders']),
            );
        }

        return $products;
    }

    /**
     * Get cart abandonment rate
     *
     * @param array $date_range Date range filters
     * @return float
     */
    public function get_abandonment_rate($date_range = array()) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wc_performance_logs';

        $where = '';
        $where_values = array();

        if (!empty($date_range['from']) && !empty($date_range['to'])) {
            $where = 'WHERE created_at BETWEEN %s AND %s';
            $where_values = array($date_range['from'], $date_range['to']);
        }

        $sql = $wpdb->prepare(
            "SELECT
                COUNT(CASE WHEN event_type = 'add_to_cart' THEN 1 END) as carts,
                COUNT(CASE WHEN event_type = 'order_completed' THEN 1 END) as orders
            FROM {$table_name} {$where}",
            $where_values
        );

        $result = $wpdb->get_row($sql, ARRAY_A);

        if (!$result || $result['carts'] == 0) {
            return 0.0;
        }

        $abandoned = $result['carts'] - $result['orders'];
        return round(($abandoned / $result['carts']) * 100, 2);
    }

    /**
     * Get timeline data for charts
     *
     * @param string $interval Time interval (hour/day/week/month)
     * @param array $date_range Date range filters
     * @return array
     */
    public function get_timeline_data($interval = 'day', $date_range = array()) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wc_performance_logs';

        // Set default date range to last 30 days if not provided
        if (empty($date_range['from'])) {
            $date_range['from'] = date('Y-m-d', strtotime('-30 days'));
        }
        if (empty($date_range['to'])) {
            $date_range['to'] = date('Y-m-d');
        }

        $date_format = $this->get_date_format($interval);
        $group_by = $this->get_group_by_clause($interval);

        $sql = $wpdb->prepare(
            "SELECT
                DATE_FORMAT(created_at, '{$date_format}') as period,
                COUNT(CASE WHEN event_type = 'product_view' THEN 1 END) as views,
                COUNT(CASE WHEN event_type = 'add_to_cart' THEN 1 END) as add_to_cart,
                COUNT(CASE WHEN event_type = 'order_completed' THEN 1 END) as orders,
                SUM(revenue) as revenue
            FROM {$table_name}
            WHERE created_at BETWEEN %s AND %s
            GROUP BY {$group_by}
            ORDER BY period ASC",
            $date_range['from'] . ' 00:00:00',
            $date_range['to'] . ' 23:59:59'
        );

        $results = $wpdb->get_results($sql, ARRAY_A);

        $data = array(
            'labels' => array(),
            'views' => array(),
            'add_to_cart' => array(),
            'orders' => array(),
            'revenue' => array(),
        );

        foreach ($results as $result) {
            $data['labels'][] = $result['period'];
            $data['views'][] = intval($result['views']);
            $data['add_to_cart'][] = intval($result['add_to_cart']);
            $data['orders'][] = intval($result['orders']);
            $data['revenue'][] = floatval($result['revenue']);
        }

        return $data;
    }

    /**
     * Get funnel data
     *
     * @param array $date_range Date range filters
     * @return array
     */
    public function get_funnel_data($date_range = array()) {
        $stats = $this->db->get_stats($date_range);

        return array(
            'views' => intval($stats['total_views']),
            'add_to_cart' => intval($stats['total_add_to_cart']),
            'orders' => intval($stats['total_orders']),
        );
    }

    /**
     * Get date format for grouping
     *
     * @param string $interval Time interval
     * @return string
     */
    private function get_date_format($interval) {
        $formats = array(
            'hour' => '%Y-%m-%d %H:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%U',
            'month' => '%Y-%m',
        );

        return isset($formats[$interval]) ? $formats[$interval] : $formats['day'];
    }

    /**
     * Get GROUP BY clause for interval
     *
     * @param string $interval Time interval
     * @return string
     */
    private function get_group_by_clause($interval) {
        $clauses = array(
            'hour' => 'DATE(created_at), HOUR(created_at)',
            'day' => 'DATE(created_at)',
            'week' => 'YEAR(created_at), WEEK(created_at)',
            'month' => 'YEAR(created_at), MONTH(created_at)',
        );

        return isset($clauses[$interval]) ? $clauses[$interval] : $clauses['day'];
    }
}