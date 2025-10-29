<?php
/**
 * Settings page for WooPerformance Tracker
 *
 * Provides plugin settings and configuration
 *
 * @package WooPerformanceTracker
 * @since 1.0.0
 */

namespace WooPerformanceTracker;

/**
 * Settings class
 */
class Settings {

    /**
     * Settings page slug
     */
    const PAGE_SLUG = 'wpt-settings';

    /**
     * Option group
     */
    const OPTION_GROUP = 'wpt_settings';

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize settings hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add settings page to WooCommerce menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'woocommerce',
            __('Performance Tracker Settings', 'woo-performance-tracker'),
            __('Tracker Settings', 'woo-performance-tracker'),
            'manage_woocommerce',
            self::PAGE_SLUG,
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            self::OPTION_GROUP,
            'wpt_tracking_enabled',
            array(
                'type' => 'boolean',
                'default' => true,
                'sanitize_callback' => 'wp_validate_boolean',
            )
        );

        register_setting(
            self::OPTION_GROUP,
            'wpt_data_retention_days',
            array(
                'type' => 'integer',
                'default' => 90,
                'sanitize_callback' => array($this, 'sanitize_retention_days'),
            )
        );

        register_setting(
            self::OPTION_GROUP,
            'wpt_cache_duration',
            array(
                'type' => 'integer',
                'default' => 300,
                'sanitize_callback' => array($this, 'sanitize_cache_duration'),
            )
        );

        register_setting(
            self::OPTION_GROUP,
            'wpt_exclude_user_roles',
            array(
                'type' => 'array',
                'default' => array(),
                'sanitize_callback' => array($this, 'sanitize_user_roles'),
            )
        );

        register_setting(
            self::OPTION_GROUP,
            'wpt_track_anonymous',
            array(
                'type' => 'boolean',
                'default' => true,
                'sanitize_callback' => 'wp_validate_boolean',
            )
        );

        register_setting(
            self::OPTION_GROUP,
            'wpt_anonymize_ip',
            array(
                'type' => 'boolean',
                'default' => false,
                'sanitize_callback' => 'wp_validate_boolean',
            )
        );

        register_setting(
            self::OPTION_GROUP,
            'wpt_auto_cleanup',
            array(
                'type' => 'boolean',
                'default' => true,
                'sanitize_callback' => 'wp_validate_boolean',
            )
        );

        // Register settings sections
        add_settings_section(
            'wpt_general_settings',
            __('General Settings', 'woo-performance-tracker'),
            array($this, 'render_general_section'),
            self::PAGE_SLUG
        );

        add_settings_section(
            'wpt_privacy_settings',
            __('Privacy & Data Settings', 'woo-performance-tracker'),
            array($this, 'render_privacy_section'),
            self::PAGE_SLUG
        );

        // Register settings fields
        add_settings_field(
            'wpt_tracking_enabled',
            __('Enable Tracking', 'woo-performance-tracker'),
            array($this, 'render_tracking_enabled_field'),
            self::PAGE_SLUG,
            'wpt_general_settings'
        );

        add_settings_field(
            'wpt_data_retention_days',
            __('Data Retention (Days)', 'woo-performance-tracker'),
            array($this, 'render_retention_days_field'),
            self::PAGE_SLUG,
            'wpt_general_settings'
        );

        add_settings_field(
            'wpt_cache_duration',
            __('Cache Duration (Seconds)', 'woo-performance-tracker'),
            array($this, 'render_cache_duration_field'),
            self::PAGE_SLUG,
            'wpt_general_settings'
        );

        add_settings_field(
            'wpt_exclude_user_roles',
            __('Exclude User Roles', 'woo-performance-tracker'),
            array($this, 'render_exclude_roles_field'),
            self::PAGE_SLUG,
            'wpt_privacy_settings'
        );

        add_settings_field(
            'wpt_track_anonymous',
            __('Track Anonymous Users', 'woo-performance-tracker'),
            array($this, 'render_track_anonymous_field'),
            self::PAGE_SLUG,
            'wpt_privacy_settings'
        );

        add_settings_field(
            'wpt_anonymize_ip',
            __('Anonymize IP Addresses', 'woo-performance-tracker'),
            array($this, 'render_anonymize_ip_field'),
            self::PAGE_SLUG,
            'wpt_privacy_settings'
        );

        add_settings_field(
            'wpt_auto_cleanup',
            __('Auto Cleanup Old Data', 'woo-performance-tracker'),
            array($this, 'render_auto_cleanup_field'),
            self::PAGE_SLUG,
            'wpt_privacy_settings'
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'woo-performance-tracker'));
        }

        include WPT_PLUGIN_DIR . 'templates/settings-page.php';
    }

    /**
     * Render general settings section
     */
    public function render_general_section() {
        echo '<p>' . esc_html__('Configure general tracking settings.', 'woo-performance-tracker') . '</p>';
    }

    /**
     * Render privacy settings section
     */
    public function render_privacy_section() {
        echo '<p>' . esc_html__('Configure privacy and data handling settings.', 'woo-performance-tracker') . '</p>';
    }

    /**
     * Render tracking enabled field
     */
    public function render_tracking_enabled_field() {
        $value = get_option('wpt_tracking_enabled', '1');
        ?>
        <label for="wpt_tracking_enabled">
            <input type="checkbox" id="wpt_tracking_enabled" name="wpt_tracking_enabled" value="1" <?php checked($value, '1'); ?> />
            <?php esc_html_e('Enable performance tracking', 'woo-performance-tracker'); ?>
        </label>
        <p class="description"><?php esc_html_e('Uncheck to disable all tracking functionality.', 'woo-performance-tracker'); ?></p>
        <?php
    }

    /**
     * Render retention days field
     */
    public function render_retention_days_field() {
        $value = get_option('wpt_data_retention_days', 90);
        ?>
        <input type="number" id="wpt_data_retention_days" name="wpt_data_retention_days" value="<?php echo esc_attr($value); ?>" min="1" max="365" />
        <p class="description"><?php esc_html_e('Number of days to keep tracking data. Older data will be automatically deleted.', 'woo-performance-tracker'); ?></p>
        <?php
    }

    /**
     * Render cache duration field
     */
    public function render_cache_duration_field() {
        $value = get_option('wpt_cache_duration', 300);
        ?>
        <input type="number" id="wpt_cache_duration" name="wpt_cache_duration" value="<?php echo esc_attr($value); ?>" min="60" max="3600" />
        <p class="description"><?php esc_html_e('How long to cache dashboard data in seconds.', 'woo-performance-tracker'); ?></p>
        <?php
    }

    /**
     * Render exclude roles field
     */
    public function render_exclude_roles_field() {
        $value = get_option('wpt_exclude_user_roles', array());
        $roles = wp_roles()->roles;
        ?>
        <div class="wpt-role-checkboxes">
            <?php foreach ($roles as $role_key => $role) : ?>
                <label>
                    <input type="checkbox" name="wpt_exclude_user_roles[]" value="<?php echo esc_attr($role_key); ?>" <?php checked(in_array($role_key, $value)); ?> />
                    <?php echo esc_html($role['name']); ?>
                </label><br>
            <?php endforeach; ?>
        </div>
        <p class="description"><?php esc_html_e('Select user roles to exclude from tracking.', 'woo-performance-tracker'); ?></p>
        <?php
    }

    /**
     * Render track anonymous field
     */
    public function render_track_anonymous_field() {
        $value = get_option('wpt_track_anonymous', '1');
        ?>
        <label for="wpt_track_anonymous">
            <input type="checkbox" id="wpt_track_anonymous" name="wpt_track_anonymous" value="1" <?php checked($value, '1'); ?> />
            <?php esc_html_e('Track anonymous (non-logged-in) users', 'woo-performance-tracker'); ?>
        </label>
        <?php
    }

    /**
     * Render anonymize IP field
     */
    public function render_anonymize_ip_field() {
        $value = get_option('wpt_anonymize_ip', '0');
        ?>
        <label for="wpt_anonymize_ip">
            <input type="checkbox" id="wpt_anonymize_ip" name="wpt_anonymize_ip" value="1" <?php checked($value, '1'); ?> />
            <?php esc_html_e('Anonymize IP addresses before storing', 'woo-performance-tracker'); ?>
        </label>
        <p class="description"><?php esc_html_e('Removes the last octet of IPv4 addresses for privacy.', 'woo-performance-tracker'); ?></p>
        <?php
    }

    /**
     * Render auto cleanup field
     */
    public function render_auto_cleanup_field() {
        $value = get_option('wpt_auto_cleanup', '1');
        ?>
        <label for="wpt_auto_cleanup">
            <input type="checkbox" id="wpt_auto_cleanup" name="wpt_auto_cleanup" value="1" <?php checked($value, '1'); ?> />
            <?php esc_html_e('Automatically clean up old data', 'woo-performance-tracker'); ?>
        </label>
        <?php
    }

    /**
     * Sanitize retention days
     *
     * @param mixed $value Input value
     * @return int Sanitized value
     */
    public function sanitize_retention_days($value) {
        $value = intval($value);
        return max(1, min(365, $value));
    }

    /**
     * Sanitize cache duration
     *
     * @param mixed $value Input value
     * @return int Sanitized value
     */
    public function sanitize_cache_duration($value) {
        $value = intval($value);
        return max(60, min(3600, $value));
    }

    /**
     * Sanitize user roles
     *
     * @param mixed $value Input value
     * @return array Sanitized value
     */
    public function sanitize_user_roles($value) {
        if (!is_array($value)) {
            return array();
        }

        $roles = wp_roles()->roles;
        $valid_roles = array_keys($roles);

        return array_intersect($value, $valid_roles);
    }
}