<?php
/**
 * Admin dashboard template for WooPerformance Tracker
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
    <div class="wpt-dashboard-header">
        <div class="wpt-header-content">
            <h1 class="wpt-gradient-text"><?php esc_html_e('WooCommerce Performance Tracker', 'woo-performance-tracker'); ?> 🚀</h1>
            <p class="wpt-header-description"><?php esc_html_e('Monitor your store\'s performance with real-time analytics and insights', 'woo-performance-tracker'); ?></p>
            <div class="wpt-header-stats">
                <div class="wpt-header-stat">
                    <span class="wpt-header-stat-icon">📊</span>
                    <span class="wpt-header-stat-label"><?php esc_html_e('Last Updated', 'woo-performance-tracker'); ?>:</span>
                    <span class="wpt-header-stat-value" id="wpt-last-updated"><?php esc_html_e('Just now', 'woo-performance-tracker'); ?></span>
                </div>
                <div class="wpt-header-stat">
                    <span class="wpt-header-stat-icon">⚡</span>
                    <span class="wpt-header-stat-label"><?php esc_html_e('Status', 'woo-performance-tracker'); ?>:</span>
                    <span class="wpt-header-stat-value wpt-status-active"><?php esc_html_e('Active', 'woo-performance-tracker'); ?></span>
                </div>
            </div>
        </div>
        <div class="wpt-header-visual">
            <div class="wpt-header-chart-preview">
                <canvas id="wpt-header-sparkline" width="200" height="60"></canvas>
            </div>
        </div>
    </div>

    <div id="wpt-dashboard-container">
        <!-- Filters -->
        <div class="wpt-filters">
            <form id="wpt-date-filter" method="get">
                <input type="hidden" name="page" value="wpt-dashboard">

                <div class="wpt-filters-fields">
                    <div class="form-field">
                        <label for="wpt-date-from"><?php esc_html_e('From Date', 'woo-performance-tracker'); ?></label>
                        <input type="date" id="wpt-date-from" name="date_from" value="<?php echo esc_attr($date_from); ?>">
                    </div>

                    <div class="form-field">
                        <label for="wpt-date-to"><?php esc_html_e('To Date', 'woo-performance-tracker'); ?></label>
                        <input type="date" id="wpt-date-to" name="date_to" value="<?php echo esc_attr($date_to); ?>">
                    </div>
                </div>

                <div class="wpt-filters-actions">
                    <button type="submit" class="button button-primary">
                        <span>🔍</span>
                        <?php esc_html_e('Apply Filter', 'woo-performance-tracker'); ?>
                    </button>

                    <button type="button" id="wpt-refresh-data" class="button secondary">
                        <span>🔄</span>
                        <?php esc_html_e('Refresh', 'woo-performance-tracker'); ?>
                    </button>

                    <button type="button" id="wpt-export-csv" class="button secondary">
                        <span>📊</span>
                        <?php esc_html_e('Export CSV', 'woo-performance-tracker'); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="wpt-summary-cards">
            <div class="wpt-summary-card views wpt-card-entrance" data-delay="0">
                <div class="wpt-card-icon">👁️</div>
                <h3><?php esc_html_e('Total Views', 'woo-performance-tracker'); ?></h3>
                <p class="wpt-value" id="wpt-total-views">0</p>
                <div class="wpt-change positive" id="wpt-views-change" style="display: none;">
                    <span class="wpt-trend-icon">↗️</span>
                    <span id="wpt-views-change-value"></span>
                    <span class="wpt-trend-label"><?php esc_html_e('vs last period', 'woo-performance-tracker'); ?></span>
                </div>
            </div>

            <div class="wpt-summary-card cart wpt-card-entrance" data-delay="100">
                <div class="wpt-card-icon">🛒</div>
                <h3><?php esc_html_e('Add to Cart', 'woo-performance-tracker'); ?></h3>
                <p class="wpt-value" id="wpt-total-add-to-cart">0</p>
                <div class="wpt-change positive" id="wpt-cart-change" style="display: none;">
                    <span class="wpt-trend-icon">↗️</span>
                    <span id="wpt-cart-change-value"></span>
                    <span class="wpt-trend-label"><?php esc_html_e('vs last period', 'woo-performance-tracker'); ?></span>
                </div>
            </div>

            <div class="wpt-summary-card orders positive wpt-card-entrance" data-delay="200">
                <div class="wpt-card-icon">📦</div>
                <h3><?php esc_html_e('Total Orders', 'woo-performance-tracker'); ?></h3>
                <p class="wpt-value" id="wpt-total-orders">0</p>
                <div class="wpt-change positive" id="wpt-orders-change" style="display: none;">
                    <span class="wpt-trend-icon">↗️</span>
                    <span id="wpt-orders-change-value"></span>
                    <span class="wpt-trend-label"><?php esc_html_e('vs last period', 'woo-performance-tracker'); ?></span>
                </div>
            </div>

            <div class="wpt-summary-card conversion positive wpt-card-entrance" data-delay="300">
                <div class="wpt-card-icon">📈</div>
                <h3><?php esc_html_e('Conversion Rate', 'woo-performance-tracker'); ?></h3>
                <p class="wpt-value" id="wpt-conversion-rate">0%</p>
                <div class="wpt-change positive" id="wpt-conversion-change" style="display: none;">
                    <span class="wpt-trend-icon">↗️</span>
                    <span id="wpt-conversion-change-value"></span>
                    <span class="wpt-trend-label"><?php esc_html_e('vs last period', 'woo-performance-tracker'); ?></span>
                </div>
            </div>

            <div class="wpt-summary-card revenue positive wpt-card-entrance" data-delay="400">
                <div class="wpt-card-icon">💰</div>
                <h3><?php esc_html_e('Revenue', 'woo-performance-tracker'); ?></h3>
                <p class="wpt-value" id="wpt-total-revenue">$0</p>
                <div class="wpt-change positive" id="wpt-revenue-change" style="display: none;">
                    <span class="wpt-trend-icon">↗️</span>
                    <span id="wpt-revenue-change-value"></span>
                    <span class="wpt-trend-label"><?php esc_html_e('vs last period', 'woo-performance-tracker'); ?></span>
                </div>
            </div>

            <div class="wpt-summary-card abandonment warning wpt-card-entrance" data-delay="500">
                <div class="wpt-card-icon">⚠️</div>
                <h3><?php esc_html_e('Cart Abandonment', 'woo-performance-tracker'); ?></h3>
                <p class="wpt-value" id="wpt-abandonment-rate">0%</p>
                <div class="wpt-change negative" id="wpt-abandonment-change" style="display: none;">
                    <span class="wpt-trend-icon">↘️</span>
                    <span id="wpt-abandonment-change-value"></span>
                    <span class="wpt-trend-label"><?php esc_html_e('vs last period', 'woo-performance-tracker'); ?></span>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="wpt-charts">
            <div class="wpt-chart-container wpt-float">
                <h3><?php esc_html_e('Performance Timeline', 'woo-performance-tracker'); ?> 📈</h3>
                <div class="wpt-chart-wrapper">
                    <canvas id="wpt-timeline-chart"></canvas>
                </div>
                <div class="wpt-chart-legend">
                    <div class="wpt-legend-item">
                        <span class="wpt-legend-color" style="background: var(--wpt-primary);"></span>
                        <span><?php esc_html_e('Views', 'woo-performance-tracker'); ?></span>
                    </div>
                    <div class="wpt-legend-item">
                        <span class="wpt-legend-color" style="background: var(--wpt-success);"></span>
                        <span><?php esc_html_e('Orders', 'woo-performance-tracker'); ?></span>
                    </div>
                    <div class="wpt-legend-item">
                        <span class="wpt-legend-color" style="background: var(--wpt-warning);"></span>
                        <span><?php esc_html_e('Revenue', 'woo-performance-tracker'); ?></span>
                    </div>
                </div>
            </div>

            <div class="wpt-chart-container wpt-float">
                <h3><?php esc_html_e('Conversion Funnel', 'woo-performance-tracker'); ?> 🎯</h3>
                <div class="wpt-chart-wrapper">
                    <canvas id="wpt-funnel-chart"></canvas>
                </div>
                <div class="wpt-funnel-stats">
                    <div class="wpt-funnel-step">
                        <span class="wpt-funnel-label"><?php esc_html_e('Views to Cart', 'woo-performance-tracker'); ?></span>
                        <span class="wpt-funnel-value" id="wpt-funnel-views-cart">0%</span>
                    </div>
                    <div class="wpt-funnel-step">
                        <span class="wpt-funnel-label"><?php esc_html_e('Cart to Order', 'woo-performance-tracker'); ?></span>
                        <span class="wpt-funnel-value" id="wpt-funnel-cart-order">0%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Products Table -->
        <div class="wpt-top-products">
            <h3><?php esc_html_e('Top Performing Products', 'woo-performance-tracker'); ?></h3>
            <table>
                <thead>
                    <tr>
                        <th><?php esc_html_e('Product', 'woo-performance-tracker'); ?></th>
                        <th><?php esc_html_e('Views', 'woo-performance-tracker'); ?></th>
                        <th><?php esc_html_e('Add to Cart', 'woo-performance-tracker'); ?></th>
                        <th><?php esc_html_e('Orders', 'woo-performance-tracker'); ?></th>
                        <th><?php esc_html_e('Revenue', 'woo-performance-tracker'); ?></th>
                        <th><?php esc_html_e('Conversion Rate', 'woo-performance-tracker'); ?></th>
                    </tr>
                </thead>
                 <tbody>
                 </tbody>
            </table>
        </div>
    </div>
</div>