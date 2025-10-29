# WooPerformance Tracker

A comprehensive WordPress plugin for tracking WooCommerce store performance metrics including product views, cart additions, orders, and conversion analytics.

## Features

- **Real-time Tracking**: Track product views, add-to-cart events, and order completions
- **Performance Analytics**: Detailed dashboard with conversion rates and revenue tracking
- **Interactive Charts**: Visual timeline and funnel charts using Chart.js with enhanced legends
- **Modern UI/UX**: Stunning dashboard with animations, gradients, icons, and responsive design
- **Trend Indicators**: Visual change indicators with bounce animations and comparison labels
- **Advanced Animations**: Card entrance animations, floating elements, and smooth transitions
- **Dark Mode Support**: Automatic dark mode detection with optimized color schemes
- **REST API**: Programmatic access to performance data
- **Privacy Compliant**: GDPR-friendly with IP anonymization and user opt-out options
- **Caching**: Built-in caching for optimal performance
- **Export**: CSV export functionality for data analysis
- **Accessibility**: WCAG compliant with keyboard navigation and screen reader support

## 🚀 Advanced Analytics vs Standard WooCommerce Reports

While WooCommerce provides excellent built-in sales reports, **WooPerformance Tracker** offers a specialized, performance-focused analytics solution designed specifically for conversion optimization and user behavior analysis.

### Key Differences & Advantages

| Feature | WooCommerce Reports | 🚀 WooPerformance Tracker |
|---------|-------------------|---------------------------|
| **Data Focus** | General sales & orders | User behavior & conversions |
| **Database** | Standard WC tables | Custom optimized `wp_wc_performance_logs` table |
| **Real-time Tracking** | Order-based only | Product views, cart actions, session tracking |
| **Conversion Analysis** | Basic conversion rates | Detailed funnel analysis & abandonment tracking |
| **User Experience** | Standard admin tables | Modern animated dashboard with trend indicators |
| **Performance** | General queries | Optimized with caching & efficient indexing |
| **API Access** | Limited WC REST API | Dedicated `/wp-json/wc-performance/v1/` endpoints |
| **Customization** | Standard reports | Fully customizable analytics dashboard |

### 🎯 Specialized Tracking Capabilities

**WooPerformance Tracker** excels in areas where standard WooCommerce reports fall short:

- **🔍 Product-Level Insights**: Track individual product performance, views-to-purchase ratios, and conversion paths
- **📊 Session-Based Analytics**: Monitor user sessions, cart abandonment patterns, and behavior flows
- **⚡ Real-Time Performance**: AJAX-powered tracking for immediate data updates and live analytics
- **🎨 Modern Visualization**: Interactive charts with trend indicators, animations, and responsive design
- **🔧 Developer-Friendly**: REST API for custom integrations and automated reporting
- **📈 Conversion Optimization**: Focus on improving user experience and conversion rates

### 💡 When to Use WooPerformance Tracker

Choose **WooPerformance Tracker** when you need:
- Detailed conversion funnel analysis
- Real-time user behavior tracking
- Custom performance dashboards
- Advanced e-commerce analytics
- Session-based optimization insights

Choose **WooCommerce Reports** for:
- Standard sales and order reporting
- Basic revenue and product analytics
- Out-of-the-box order management

**WooPerformance Tracker** is the perfect companion to WooCommerce's built-in reports, providing the deep analytics needed for serious e-commerce optimization! 🛍️📊

## Requirements

- WordPress 5.8+
- WooCommerce 6.0+
- PHP 7.4+

## Installation

1. Download the plugin zip file
2. Go to WordPress Admin > Plugins > Add New
3. Click "Upload Plugin" and select the zip file
4. Click "Install Now" and then "Activate"

## Configuration

After activation:

1. Navigate to **WooCommerce > Performance Tracker**
2. Configure settings via **WooCommerce > Tracker Settings**
3. Set data retention period and privacy options

### Settings Options

- **Enable Tracking**: Turn tracking on/off
- **Data Retention**: Days to keep tracking data (default: 90)
- **Cache Duration**: How long to cache dashboard data (default: 5 minutes)
- **Exclude User Roles**: Don't track specific user roles
- **Track Anonymous Users**: Enable/disable anonymous user tracking
- **Anonymize IP**: Remove last octet of IP addresses for privacy
- **Auto Cleanup**: Automatically remove old data

## Usage

### Dashboard

The main dashboard features a modern, visually stunning interface with:

- **Header Section**: Gradient header with status indicators and mini sparkline chart
- **Summary Cards**: Animated cards with icons, trend indicators, and hover effects showing:
  - Total views with eye icon 👁️
  - Add-to-cart events with cart icon 🛒
  - Total orders with package icon 📦
  - Conversion rate with growth icon 📈
  - Revenue with money icon 💰
  - Cart abandonment rate with warning icon ⚠️
- **Timeline Chart**: Enhanced performance trends with color-coded legends and floating animation
- **Conversion Funnel**: Interactive funnel with real-time conversion percentages and target icon 🎯
- **Top Products Table**: Enhanced table with hover effects, color-coded metrics, and smooth animations
- **Responsive Design**: Fully responsive layout that works on all devices
- **Dark Mode**: Automatic dark mode support for better user experience

### Filters

- **Date Range**: Filter data by custom date ranges
- **Product Filter**: Focus on specific products (coming soon)
- **Export**: Download data as CSV for external analysis

## REST API

Access performance data programmatically:

### Get Statistics
```
GET /wp-json/wc-performance/v1/stats?date_from=2024-01-01&date_to=2024-01-31
```

### Get Products Data
```
GET /wp-json/wc-performance/v1/products?date_from=2024-01-01&limit=10
```

### Get Timeline Data
```
GET /wp-json/wc-performance/v1/timeline?date_from=2024-01-01&interval=day
```

## Developer Information

### Hooks and Filters

#### Actions
- `wpt_before_track_event`: Before logging an event
- `wpt_after_track_event`: After logging an event
- `wpt_cleanup_old_data`: When cleaning up old data

#### Filters
- `wpt_should_track`: Control whether to track current user/request
- `wpt_event_data`: Modify event data before saving
- `wpt_dashboard_data`: Modify dashboard data before display

### Database Schema

The plugin creates a custom table `wp_wc_performance_logs`:

```sql
CREATE TABLE wp_wc_performance_logs (
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
);
```

### Event Types

- `product_view`: Product page visits
- `add_to_cart`: Add to cart actions
- `checkout_initiated`: Checkout page access
- `order_completed`: Successful purchases

## Privacy & GDPR

- IP addresses are anonymized by default (last octet removed)
- Users can opt-out via browser Do Not Track
- Data retention periods are configurable
- No personal data is collected without consent
- Data export functionality available

## Performance

- Database queries are optimized with proper indexing
- Dashboard data is cached for 5 minutes by default
- AJAX requests are debounced to prevent spam
- Old data is automatically cleaned up

## Troubleshooting

### Common Issues

1. **Dashboard not loading**: Check WooCommerce is active and plugin is properly installed
2. **No data showing**: Verify tracking is enabled and users are visiting product pages
3. **Charts not rendering**: Ensure JavaScript is enabled and Chart.js is loading
4. **Slow performance**: Check cache settings and database indexes

### Debug Mode

Add to wp-config.php for debugging:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Changelog

### 1.0.0
- Initial release
- Basic tracking functionality
- Dashboard with charts
- REST API endpoints
- Settings page
- Privacy compliance features

### 1.0.1
- **UI Enhancement to 10/10**: Complete dashboard redesign with modern aesthetics
- Added stunning gradient header with status indicators
- Implemented card entrance animations with staggered delays
- Enhanced summary cards with icons, trend indicators, and bounce animations
- Added floating animations and advanced hover effects
- Implemented comprehensive dark mode support
- Added chart legends and funnel statistics
- Enhanced responsive design for all screen sizes
- Improved accessibility with WCAG compliance
- Added advanced CSS animations and transitions
- Optimized performance with reduced motion support

### 1.0.2
- **Critical Bug Fixes**: Resolved JavaScript selector errors preventing table display
- Fixed incorrect jQuery selector from `#wpt-top-products` to `.wpt-top-products` in dashboard script
- Removed unreachable duplicate code in `renderTopProductsTable` function
- Enhanced error handling and debugging logs for table state management
- Improved initial table state loading and "no data" message display
- Added comprehensive console logging for debugging dashboard initialization

## Support

For support and feature requests:
- Create an issue on GitHub
- Check the WordPress.org support forums
- Contact the plugin author

## License

This plugin is licensed under the GPLv2 or later.

## Credits

Developed by WBCom Designs
Built for WooCommerce compatibility
