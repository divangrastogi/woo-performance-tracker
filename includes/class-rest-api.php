<?php
/**
 * REST API endpoints for WooPerformance Tracker
 *
 * Provides REST API endpoints for dashboard data
 *
 * @package WooPerformanceTracker
 * @since 1.0.0
 */

namespace WooPerformanceTracker;

/**
 * Rest_Api class
 */
class Rest_Api {

    /**
     * Namespace for API endpoints
     */
    const API_NAMESPACE = 'wc-performance/v1';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route(self::API_NAMESPACE, '/stats', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_stats'),
                'permission_callback' => array($this, 'check_permissions'),
                'args' => array(
                    'date_from' => array(
                        'required' => false,
                        'validate_callback' => array($this, 'validate_date'),
                    ),
                    'date_to' => array(
                        'required' => false,
                        'validate_callback' => array($this, 'validate_date'),
                    ),
                    'product_id' => array(
                        'required' => false,
                        'validate_callback' => 'is_numeric',
                    ),
                ),
            ),
        ));

        register_rest_route(self::API_NAMESPACE, '/products', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_products'),
                'permission_callback' => array($this, 'check_permissions'),
                'args' => array(
                    'date_from' => array(
                        'required' => false,
                        'validate_callback' => array($this, 'validate_date'),
                    ),
                    'date_to' => array(
                        'required' => false,
                        'validate_callback' => array($this, 'validate_date'),
                    ),
                    'limit' => array(
                        'required' => false,
                        'default' => 10,
                        'validate_callback' => 'is_numeric',
                    ),
                    'orderby' => array(
                        'required' => false,
                        'default' => 'views',
                        'enum' => array('views', 'add_to_cart', 'orders', 'revenue'),
                    ),
                ),
            ),
        ));

        register_rest_route(self::API_NAMESPACE, '/timeline', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_timeline'),
                'permission_callback' => array($this, 'check_permissions'),
                'args' => array(
                    'date_from' => array(
                        'required' => false,
                        'validate_callback' => array($this, 'validate_date'),
                    ),
                    'date_to' => array(
                        'required' => false,
                        'validate_callback' => array($this, 'validate_date'),
                    ),
                    'interval' => array(
                        'required' => false,
                        'default' => 'day',
                        'enum' => array('hour', 'day', 'week', 'month'),
                    ),
                ),
            ),
        ));
    }

    /**
     * Get performance statistics
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_stats($request) {
        try {
            $cache = woo_performance_tracker()->cache;
            $analyzer = woo_performance_tracker()->analyzer;

            $params = $request->get_params();
            $cache_key = 'wpt_stats_' . md5(wp_json_encode($params));

            $data = $cache->get($cache_key);
            if ($data === false) {
                $date_range = array();
                if (!empty($params['date_from'])) {
                    $date_range['from'] = $params['date_from'];
                }
                if (!empty($params['date_to'])) {
                    $date_range['to'] = $params['date_to'];
                }

                $stats = woo_performance_tracker()->db->get_stats($date_range);

                $data = array(
                    'success' => true,
                    'data' => array(
                        'total_views' => intval($stats['total_views']),
                        'total_add_to_cart' => intval($stats['total_add_to_cart']),
                        'total_orders' => intval($stats['total_orders']),
                        'conversion_rate' => $analyzer->calculate_conversion_rate($stats['total_views'], $stats['total_orders']),
                        'revenue' => floatval($stats['total_revenue']),
                        'cart_abandonment_rate' => $analyzer->get_abandonment_rate($date_range),
                        'unique_sessions' => intval($stats['unique_sessions']),
                    ),
                );

                $cache->set($cache_key, $data, get_option('wpt_cache_duration', 300));
            }

            return new \WP_REST_Response($data, 200);
        } catch (Exception $e) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => 'Error loading statistics: ' . $e->get_message(),
            ), 500);
        }
    }

    /**
     * Get product performance data
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_products($request) {
        $cache = woo_performance_tracker()->cache;
        $analyzer = woo_performance_tracker()->analyzer;

        $params = $request->get_params();
        $cache_key = 'wpt_products_' . md5(wp_json_encode($params));

        $data = $cache->get($cache_key);
        if ($data === false) {
            $date_range = array();
            if (!empty($params['date_from'])) {
                $date_range['from'] = $params['date_from'];
            }
            if (!empty($params['date_to'])) {
                $date_range['to'] = $params['date_to'];
            }

            $products = $analyzer->get_top_products($params['limit'], $date_range, $params['orderby']);

            $data = array(
                'success' => true,
                'data' => $products,
            );

            $cache->set($cache_key, $data, get_option('wpt_cache_duration', 300));
        }

        return new \WP_REST_Response($data, 200);
    }

    /**
     * Get timeline data for charts
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_timeline($request) {
        $cache = woo_performance_tracker()->cache;
        $analyzer = woo_performance_tracker()->analyzer;

        $params = $request->get_params();
        $cache_key = 'wpt_timeline_' . md5(wp_json_encode($params));

        $data = $cache->get($cache_key);
        if ($data === false) {
            $date_range = array();
            if (!empty($params['date_from'])) {
                $date_range['from'] = $params['date_from'];
            }
            if (!empty($params['date_to'])) {
                $date_range['to'] = $params['date_to'];
            }

            $timeline_data = $analyzer->get_timeline_data($params['interval'], $date_range);

            $data = array(
                'success' => true,
                'data' => $timeline_data,
            );

            $cache->set($cache_key, $data, get_option('wpt_cache_duration', 300));
        }

        return new \WP_REST_Response($data, 200);
    }

    /**
     * Check API permissions
     *
     * @param WP_REST_Request $request Request object
     * @return bool
     */
    public function check_permissions($request) {
        // Allow access if user is logged in and has WooCommerce management capabilities
        return is_user_logged_in() && (current_user_can('manage_woocommerce') || current_user_can('manage_options'));
    }

    /**
     * Validate date parameter
     *
     * @param string $date Date string
     * @return bool
     */
    public function validate_date($date) {
        return strtotime($date) !== false;
    }
}
