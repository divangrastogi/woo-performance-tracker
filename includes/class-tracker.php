<?php
/**
 * Event tracking for WooPerformance Tracker
 *
 * Hooks into WooCommerce actions to track performance metrics
 *
 * @package WooPerformanceTracker
 * @since 1.0.0
 */

namespace WooPerformanceTracker;

/**
 * Tracker class
 */
class Tracker {

    /**
     * Database instance
     *
     * @var Database
     */
    private $db;

    /**
     * Cache manager instance
     *
     * @var Cache_Manager
     */
    private $cache;

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Get database instance
     *
     * @return Database
     */
    private function get_db() {
        if (!$this->db && function_exists('woo_performance_tracker')) {
            $this->db = woo_performance_tracker()->db;
        }
        return $this->db;
    }

    /**
     * Get cache instance
     *
     * @return Cache_Manager
     */
    private function get_cache() {
        if (!$this->cache && function_exists('woo_performance_tracker')) {
            $this->cache = woo_performance_tracker()->cache;
        }
        return $this->cache;
    }

    /**
     * Initialize tracking hooks
     */
    private function init_hooks() {
        // Product view tracking
        add_action('template_redirect', array($this, 'track_product_view'));

        // Add to cart tracking
        add_action('woocommerce_add_to_cart', array($this, 'track_add_to_cart'), 10, 6);

        // Checkout initiation tracking
        add_action('woocommerce_before_checkout_form', array($this, 'track_checkout_initiated'));

        // Order completion tracking
        add_action('woocommerce_thankyou', array($this, 'track_order_completed'));

        // Cart abandonment detection
        add_action('woocommerce_cart_updated', array($this, 'detect_cart_abandonment'));

        // Scheduled cleanup
        add_action('wpt_cleanup_old_data', array($this, 'cleanup_old_data'));
    }

    /**
     * Track product view
     */
    public function track_product_view() {
        if (!$this->should_track()) {
            return;
        }

        if (is_product()) {
            global $product;
            if ($product && $product->get_id()) {
                $this->log_event('product_view', array(
                    'product_id' => $product->get_id(),
                    'session_id' => $this->get_session_id(),
                ));
            }
        }
    }

    /**
     * Track add to cart
     *
     * @param string $cart_item_key Cart item key
     * @param int $product_id Product ID
     * @param int $quantity Quantity added
     * @param int $variation_id Variation ID
     * @param array $variation Variation data
     * @param array $cart_item_data Cart item data
     */
    public function track_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        if (!$this->should_track()) {
            return;
        }

        $this->log_event('add_to_cart', array(
            'product_id' => $product_id,
            'session_id' => $this->get_session_id(),
            'metadata' => array(
                'quantity' => $quantity,
                'variation_id' => $variation_id,
                'variation' => $variation,
            ),
        ));
    }

    /**
     * Track checkout initiation
     */
    public function track_checkout_initiated() {
        if (!$this->should_track()) {
            return;
        }

        $this->log_event('checkout_initiated', array(
            'session_id' => $this->get_session_id(),
        ));
    }

    /**
     * Track order completion
     *
     * @param int $order_id Order ID
     */
    public function track_order_completed($order_id) {
        if (!$this->should_track()) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $revenue = $order->get_total();
        $user_id = $order->get_customer_id();

        // Log order completion
        $this->log_event('order_completed', array(
            'order_id' => $order_id,
            'user_id' => $user_id,
            'session_id' => $this->get_session_id(),
            'revenue' => $revenue,
            'metadata' => array(
                'order_total' => $revenue,
                'currency' => $order->get_currency(),
                'payment_method' => $order->get_payment_method(),
            ),
        ));

        // Clear cache after order completion
        $this->cache->flush();
    }

    /**
     * Detect cart abandonment
     */
    public function detect_cart_abandonment() {
        if (!$this->should_track()) {
            return;
        }

        // This is a simplified implementation
        // In a real scenario, you'd want to check cart contents and set timers
        $cart = WC()->cart;
        if ($cart && !$cart->is_empty()) {
            // Store cart session for potential abandonment tracking
            $session_id = $this->get_session_id();
            $cart_hash = $cart->get_cart_hash();

            // You could store this in a transient or custom table for abandonment detection
            set_transient('wpt_cart_' . $session_id, array(
                'cart_hash' => $cart_hash,
                'last_updated' => time(),
            ), HOUR_IN_SECONDS);
        }
    }

    /**
     * Log tracking event
     *
     * @param string $event_type Event type
     * @param array $data Event data
     */
    private function log_event($event_type, $data = array()) {
        $data['event_type'] = $event_type;
        $this->get_db()->insert_event($data);
    }

    /**
     * Get or generate session ID
     *
     * @return string
     */
    public function get_session_id() {
        if (is_user_logged_in()) {
            return 'user_' . get_current_user_id();
        }

        // For guests, use a cookie-based session
        if (isset($_COOKIE['wpt_session_id'])) {
            return sanitize_text_field(wp_unslash($_COOKIE['wpt_session_id']));
        }

        // Generate new session ID
        $session_id = 'guest_' . wp_generate_password(32, false);
        setcookie('wpt_session_id', $session_id, time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN, is_ssl());

        return $session_id;
    }

    /**
     * Check if tracking should be performed
     *
     * @return bool
     */
    public function should_track() {
        // Check if tracking is enabled
        if (!get_option('wpt_tracking_enabled', '1')) {
            return false;
        }

        // Don't track admin users unless explicitly allowed
        if (current_user_can('manage_options') && !get_option('wpt_track_admin_users', '0')) {
            return false;
        }

        // Check excluded user roles
        $excluded_roles = get_option('wpt_exclude_user_roles', array());
        if (!empty($excluded_roles) && is_user_logged_in()) {
            $user = wp_get_current_user();
            foreach ($excluded_roles as $role) {
                if (in_array($role, $user->roles)) {
                    return false;
                }
            }
        }

        // Check if anonymous tracking is enabled
        if (!is_user_logged_in() && !get_option('wpt_track_anonymous', '1')) {
            return false;
        }

        // Don't track in admin area
        if (is_admin()) {
            return false;
        }

        return true;
    }

    /**
     * Clean up old data (cron job)
     */
    public function cleanup_old_data() {
        $retention_days = get_option('wpt_data_retention_days', 90);
        $this->get_db()->cleanup_old_data($retention_days);
    }
}