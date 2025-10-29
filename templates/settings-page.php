<?php
/**
 * Settings page template for WooPerformance Tracker
 *
 * @package WooPerformanceTracker
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php esc_html_e('WooPerformance Tracker Settings', 'woo-performance-tracker'); ?></h1>

    <form method="post" action="options.php">
        <?php
        settings_fields(\WooPerformanceTracker\Settings::OPTION_GROUP);
        do_settings_sections(\WooPerformanceTracker\Settings::PAGE_SLUG);
        submit_button();
        ?>
    </form>

    <div class="wpt-settings-info">
        <h3><?php esc_html_e('Information', 'woo-performance-tracker'); ?></h3>
        <ul>
            <li><?php esc_html_e('Tracking data is stored in a custom database table.', 'woo-performance-tracker'); ?></li>
            <li><?php esc_html_e('Old data is automatically cleaned up based on retention settings.', 'woo-performance-tracker'); ?></li>
            <li><?php esc_html_e('Dashboard data is cached for performance.', 'woo-performance-tracker'); ?></li>
            <li><?php esc_html_e('IP addresses can be anonymized for privacy compliance.', 'woo-performance-tracker'); ?></li>
        </ul>
    </div>
</div>