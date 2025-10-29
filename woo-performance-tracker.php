<?php
/**
 * Plugin Name: WooPerformance Tracker
 * Description: Track WooCommerce performance metrics with custom analytics
 * Version: 1.0.0
 * Author: WBCom Designs
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woo-performance-tracker
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('WPT_VERSION', '1.0.0');
define('WPT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPT_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('WPT_TABLE_NAME', 'wc_performance_logs');

// Check WooCommerce dependency
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', 'wpt_woocommerce_missing_notice');
    return;
}

/**
 * Display admin notice if WooCommerce is not active
 */
function wpt_woocommerce_missing_notice() {
    ?>
    <div class="error">
        <p><?php esc_html_e('WooPerformance Tracker requires WooCommerce to be installed and active.', 'woo-performance-tracker'); ?></p>
    </div>
    <?php
}

// Autoloader for plugin classes
spl_autoload_register(function ($class_name) {
    if (strpos($class_name, 'WooPerformanceTracker\\') === 0) {
        $class_name = str_replace('WooPerformanceTracker\\', '', $class_name);
        $class_name = str_replace('\\', '/', $class_name);
        $class_name = str_replace('_', '-', strtolower($class_name));
        $file_path = WPT_PLUGIN_DIR . 'includes/class-' . $class_name . '.php';

        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
});

// Include required files
require_once WPT_PLUGIN_DIR . 'includes/class-activator.php';
require_once WPT_PLUGIN_DIR . 'includes/class-deactivator.php';

// Activation and deactivation hooks
register_activation_hook(__FILE__, array('WooPerformanceTracker\\Activator', 'activate'));
register_deactivation_hook(__FILE__, array('WooPerformanceTracker\\Deactivator', 'deactivate'));

/**
 * Main plugin class
 */
class WooPerformanceTracker {

    /**
     * Single instance of the plugin
     *
     * @var WooPerformanceTracker
     */
    private static $instance = null;

    /**
     * Database handler
     *
     * @var Database
     */
    public $db;

    /**
     * Tracker handler
     *
     * @var Tracker
     */
    public $tracker;

    /**
     * AJAX handler
     *
     * @var Ajax_Handler
     */
    public $ajax;

    /**
     * REST API handler
     *
     * @var Rest_Api
     */
    public $api;

    /**
     * Admin dashboard
     *
     * @var Admin_Dashboard
     */
    public $dashboard;

    /**
     * Data analyzer
     *
     * @var Data_Analyzer
     */
    public $analyzer;

    /**
     * Cache manager
     *
     * @var Cache_Manager
     */
    public $cache;

    /**
     * Settings handler
     *
     * @var Settings
     */
    public $settings;

    /**
     * Get single instance of the plugin
     *
     * @return WooPerformanceTracker
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->init_components();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'));
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'woo-performance-tracker',
            false,
            dirname(WPT_PLUGIN_BASENAME) . '/languages/'
        );
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize components
        $this->db = new WooPerformanceTracker\Database();
        $this->cache = new WooPerformanceTracker\Cache_Manager();
        $this->analyzer = new WooPerformanceTracker\Data_Analyzer();
        $this->analyzer->set_database($this->db);

        $this->tracker = new WooPerformanceTracker\Tracker();
        $this->ajax = new WooPerformanceTracker\Ajax_Handler();
        $this->api = new WooPerformanceTracker\Rest_Api();
        $this->dashboard = new WooPerformanceTracker\Admin_Dashboard();
        $this->settings = new WooPerformanceTracker\Settings();
    }

    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Components are initialized in init() method
    }
}

/**
 * Initialize the plugin
 *
 * @return WooPerformanceTracker
 */
function woo_performance_tracker() {
    return WooPerformanceTracker::get_instance();
}

// Start the plugin
woo_performance_tracker();