<?php
/**
 * Admin dashboard for WooPerformance Tracker
 *
 * Provides the admin interface for viewing performance metrics
 *
 * @package WooPerformanceTracker
 * @since 1.0.0
 */

namespace WooPerformanceTracker;

/**
 * Admin_Dashboard class
 */
class Admin_Dashboard {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize admin hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_init', array($this, 'maybe_restrict_admin_notices'), 1);

        // Register admin-ajax handlers
        add_action('wp_ajax_wpt_get_dashboard_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_wpt_get_dashboard_products', array($this, 'ajax_get_products'));
        add_action('wp_ajax_wpt_get_dashboard_timeline', array($this, 'ajax_get_timeline'));
    }

    /**
     * Add admin menu page
     */
    public function add_menu_page() {
        add_submenu_page(
            'woocommerce',
            __('Performance Tracker', 'woo-performance-tracker'),
            __('Performance Tracker', 'woo-performance-tracker'),
            'manage_woocommerce',
            'wpt-dashboard',
            array($this, 'render_dashboard')
        );
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'woocommerce_page_wpt-dashboard') {
            return;
        }

        // Enqueue Chart.js from CDN (could be local)
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '4.4.0',
            true
        );

        // Enqueue dashboard script
        wp_enqueue_script(
            'wpt-admin-dashboard',
            WPT_PLUGIN_URL . 'assets/js/admin-dashboard.js',
            array('jquery', 'chart-js'),
            WPT_VERSION,
            true
        );

        // Enqueue dashboard styles
        wp_enqueue_style(
            'wpt-admin-dashboard',
            WPT_PLUGIN_URL . 'assets/css/admin-dashboard.css',
            array(),
            WPT_VERSION
        );

        // Localize script
        wp_localize_script('wpt-admin-dashboard', 'wptDashboard', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpt_dashboard_nonce'),
            'restUrl' => rest_url(self::API_NAMESPACE),
            'strings' => array(
                'loading' => __('Loading...', 'woo-performance-tracker'),
                'error' => __('Error loading data', 'woo-performance-tracker'),
                'noData' => __('No data available', 'woo-performance-tracker'),
            ),
        ));

        // Register admin-ajax handlers for dashboard
        add_action('wp_ajax_wpt_get_dashboard_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_wpt_get_dashboard_products', array($this, 'ajax_get_products'));
        add_action('wp_ajax_wpt_get_dashboard_timeline', array($this, 'ajax_get_timeline'));
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'woo-performance-tracker'));
        }

        // Get date range from URL parameters
        $date_from = isset($_GET['date_from']) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : '';

        // Set defaults
        if (empty($date_from)) {
            $date_from = date('Y-m-d', strtotime('-30 days'));
        }
        if (empty($date_to)) {
            $date_to = date('Y-m-d');
        }

        include WPT_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }

    /**
     * Maybe restrict admin notices on dashboard page
     *
     * Checks if we're on the dashboard page and sets up notice restriction
     */
    public function maybe_restrict_admin_notices() {
        // Check if we're on the dashboard page using query parameters
        if (isset($_GET['page']) && $_GET['page'] === 'wpt-dashboard') {
            // Remove all admin notices for this page
            add_action('admin_notices', array($this, 'remove_all_admin_notices'), -9999);
            add_action('user_admin_notices', array($this, 'remove_all_admin_notices'), -9999);
            add_action('network_admin_notices', array($this, 'remove_all_admin_notices'), -9999);
            add_action('all_admin_notices', array($this, 'remove_all_admin_notices'), -9999);
        }
    }

    /**
     * Remove all admin notices
     *
     * Completely removes all admin notices to keep dashboard clean
     */
    public function remove_all_admin_notices() {
        global $wp_filter;

        // Remove all admin notice hooks
        if (isset($wp_filter['admin_notices'])) {
            unset($wp_filter['admin_notices']);
        }

        if (isset($wp_filter['user_admin_notices'])) {
            unset($wp_filter['user_admin_notices']);
        }

        if (isset($wp_filter['network_admin_notices'])) {
            unset($wp_filter['network_admin_notices']);
        }

        if (isset($wp_filter['all_admin_notices'])) {
            unset($wp_filter['all_admin_notices']);
        }

        // Start output buffering to catch any notices that might be output
        ob_start();

        // Add a shutdown function to discard the buffer
        add_action('shutdown', function() {
            if (isset($_GET['page']) && $_GET['page'] === 'wpt-dashboard') {
                ob_end_clean();
            }
        });
    }

    /**
     * Restore admin notices when leaving the dashboard page
     */
    public function restore_admin_notices() {
        // This ensures notices are restored if user navigates away
        // The notices will be restored on the next page load
        remove_action('admin_notices', array($this, 'restrict_admin_notices'), 1);
    }

    /**
     * Get dashboard data
     *
     * @param array $params Query parameters
     * @return array
     */
    public function get_dashboard_data($params = array()) {
        $cache = woo_performance_tracker()->cache;
        $analyzer = woo_performance_tracker()->analyzer;

        $cache_key = 'dashboard_data_' . md5(wp_json_encode($params));
        $data = $cache->get($cache_key);

        if ($data === false) {
            $date_range = array(
                'from' => $params['date_from'] ?? date('Y-m-d', strtotime('-30 days')),
                'to' => $params['date_to'] ?? date('Y-m-d'),
            );

            $stats = woo_performance_tracker()->db->get_stats($date_range);

            $data = array(
                'summary' => array(
                    'total_views' => intval($stats['total_views']),
                    'total_add_to_cart' => intval($stats['total_add_to_cart']),
                    'total_orders' => intval($stats['total_orders']),
                    'conversion_rate' => $analyzer->calculate_conversion_rate($stats['total_views'], $stats['total_orders']),
                    'revenue' => floatval($stats['total_revenue']),
                    'cart_abandonment_rate' => $analyzer->get_abandonment_rate($date_range),
                ),
                'timeline' => $analyzer->get_timeline_data('day', $date_range),
                'top_products' => $analyzer->get_top_products(10, $date_range),
                'funnel' => $analyzer->get_funnel_data($date_range),
            );

            $cache->set($cache_key, $data, get_option('wpt_cache_duration', 300));
        }

        return $data;
    }

    /**
     * AJAX handler for getting dashboard stats
     */
    public function ajax_get_stats() {
        check_ajax_referer('wpt_dashboard_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Insufficient permissions', 'woo-performance-tracker'));
        }

        $date_from = isset($_POST['date_from']) ? sanitize_text_field(wp_unslash($_POST['date_from'])) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field(wp_unslash($_POST['date_to'])) : '';

        $date_range = array();
        if (!empty($date_from)) {
            $date_range['from'] = $date_from;
        }
        if (!empty($date_to)) {
            $date_range['to'] = $date_to;
        }

        $stats = woo_performance_tracker()->db->get_stats($date_range);

        $data = array(
            'success' => true,
            'data' => array(
                'total_views' => intval($stats['total_views']),
                'total_add_to_cart' => intval($stats['total_add_to_cart']),
                'total_orders' => intval($stats['total_orders']),
                'conversion_rate' => woo_performance_tracker()->analyzer->calculate_conversion_rate($stats['total_views'], $stats['total_orders']),
                'revenue' => floatval($stats['total_revenue']),
                'cart_abandonment_rate' => woo_performance_tracker()->analyzer->get_abandonment_rate($date_range),
                'unique_sessions' => intval($stats['unique_sessions']),
            ),
        );

        wp_send_json($data);
    }

    /**
     * AJAX handler for getting dashboard products
     */
    public function ajax_get_products() {
        try {
            check_ajax_referer('wpt_dashboard_nonce', 'nonce');

            if (!current_user_can('manage_woocommerce')) {
                wp_send_json_error(__('Insufficient permissions', 'woo-performance-tracker'));
            }

            $date_from = isset($_POST['date_from']) ? sanitize_text_field(wp_unslash($_POST['date_from'])) : '';
            $date_to = isset($_POST['date_to']) ? sanitize_text_field(wp_unslash($_POST['date_to'])) : '';
            $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;

            $date_range = array();
            if (!empty($date_from)) {
                $date_range['from'] = $date_from;
            }
            if (!empty($date_to)) {
                $date_range['to'] = $date_to;
            }

            // Try to get products, but provide fallback
            $products = array();
            try {
                $products = woo_performance_tracker()->analyzer->get_top_products($limit, $date_range);
            } catch (Exception $e) {
                // If query fails, return empty array
                $products = array();
                if (WP_DEBUG) {
                    error_log('WPT: get_top_products failed: ' . $e->get_message());
                }
            }

            $data = array(
                'success' => true,
                'data' => $products,
                'debug' => array(
                    'date_range' => $date_range,
                    'limit' => $limit,
                    'product_count' => count($products),
                ),
            );

            wp_send_json($data);
        } catch (Exception $e) {
            wp_send_json_error('Error loading products: ' . $e->get_message());
        }
    }

    /**
     * AJAX handler for getting dashboard timeline
     */
    public function ajax_get_timeline() {
        check_ajax_referer('wpt_dashboard_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Insufficient permissions', 'woo-performance-tracker'));
        }

        $date_from = isset($_POST['date_from']) ? sanitize_text_field(wp_unslash($_POST['date_from'])) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field(wp_unslash($_POST['date_to'])) : '';
        $interval = isset($_POST['interval']) ? sanitize_text_field(wp_unslash($_POST['interval'])) : 'day';

        $date_range = array();
        if (!empty($date_from)) {
            $date_range['from'] = $date_from;
        }
        if (!empty($date_to)) {
            $date_range['to'] = $date_to;
        }

        $timeline = woo_performance_tracker()->analyzer->get_timeline_data($interval, $date_range);

        $data = array(
            'success' => true,
            'data' => $timeline,
        );

        wp_send_json($data);
    }

    /**
     * API namespace constant
     */
    const API_NAMESPACE = 'wc-performance/v1';
}