<?php
/**
 * AJAX handler for WooPerformance Tracker
 *
 * Handles AJAX requests for frontend tracking
 *
 * @package WooPerformanceTracker
 * @since 1.0.0
 */

namespace WooPerformanceTracker;

/**
 * Ajax_Handler class
 */
class Ajax_Handler {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize AJAX hooks
     */
    private function init_hooks() {
        // Frontend tracking
        add_action('wp_ajax_wpt_track_view', array($this, 'handle_track_view'));
        add_action('wp_ajax_nopriv_wpt_track_view', array($this, 'handle_track_view'));

        add_action('wp_ajax_wpt_track_event', array($this, 'handle_track_event'));
        add_action('wp_ajax_nopriv_wpt_track_event', array($this, 'handle_track_event'));

        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Handle product view tracking
     */
    public function handle_track_view() {
        // Verify nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'wpt_track_nonce')) {
            wp_die(esc_html__('Security check failed', 'woo-performance-tracker'));
        }

        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

        if (!$product_id) {
            wp_send_json_error(array('message' => 'Invalid product ID'));
        }

        $tracker = woo_performance_tracker()->tracker;

        if ($tracker->should_track()) {
            $tracker->log_event('product_view', array(
                'product_id' => $product_id,
                'session_id' => $tracker->get_session_id(),
            ));
        }

        wp_send_json_success();
    }

    /**
     * Handle generic event tracking
     */
    public function handle_track_event() {
        // Verify nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'wpt_track_nonce')) {
            wp_die(esc_html__('Security check failed', 'woo-performance-tracker'));
        }

        $event_type = sanitize_key($_POST['event_type'] ?? '');
        $event_data = isset($_POST['event_data']) ? json_decode(wp_unslash($_POST['event_data']), true) : array();

        if (empty($event_type)) {
            wp_send_json_error(array('message' => 'Invalid event type'));
        }

        $tracker = woo_performance_tracker()->tracker;

        if ($tracker->should_track()) {
            $data = array_merge($event_data, array(
                'session_id' => $tracker->get_session_id(),
            ));

            $tracker->log_event($event_type, $data);
        }

        wp_send_json_success();
    }

    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        if (!$this->should_enqueue_scripts()) {
            return;
        }

        wp_enqueue_script(
            'wpt-tracking',
            WPT_PLUGIN_URL . 'assets/js/tracking.js',
            array('jquery'),
            WPT_VERSION,
            true
        );

        wp_localize_script('wpt-tracking', 'wptTracker', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpt_track_nonce'),
            'productId' => is_product() ? get_the_ID() : 0,
            'isProductPage' => is_product(),
        ));
    }

    /**
     * Check if scripts should be enqueued
     *
     * @return bool
     */
    private function should_enqueue_scripts() {
        // Only enqueue on frontend
        if (is_admin()) {
            return false;
        }

        // Check if tracking is enabled
        if (!get_option('wpt_tracking_enabled', '1')) {
            return false;
        }

        // Enqueue on product pages and shop pages
        return is_product() || is_shop() || is_product_category() || is_product_tag();
    }
}