# WooPerformance Tracker - Complete Plugin Development Guide

## Project Overview
Build a professional WordPress plugin that tracks WooCommerce store performance metrics including product views, sales conversions, and cart abandonments. The plugin should follow WordPress coding standards, use proper boilerplate structure, and maintain files under 1000 lines of code each.

---

## Technical Requirements

### Core Specifications
- **Plugin Name**: WooPerformance Tracker
- **Minimum WordPress Version**: 5.8
- **Minimum WooCommerce Version**: 6.0
- **PHP Version**: 7.4+
- **Architecture**: Object-oriented, modular design
- **File Size Limit**: Maximum 1000 lines per file
- **Namespace**: `WooPerformanceTracker`

### Database Architecture
Create a custom table: `wp_wc_performance_logs`

**Table Schema**:
```sql
CREATE TABLE `wp_wc_performance_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `revenue` decimal(10,2) DEFAULT NULL,
  `metadata` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `event_type` (`event_type`),
  KEY `product_id` (`product_id`),
  KEY `created_at` (`created_at`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Event Types to Track**:
- `product_view` - Product page views
- `add_to_cart` - Add to cart actions
- `cart_abandonment` - Detected abandoned carts
- `checkout_initiated` - Checkout page accessed
- `order_completed` - Successful purchases

---

## Plugin Structure (Boilerplate)

```
woo-performance-tracker/
├── woo-performance-tracker.php          # Main plugin file
├── uninstall.php                        # Cleanup on uninstall
├── README.md                            # Documentation
├── assets/
│   ├── css/
│   │   └── admin-dashboard.css         # Dashboard styles
│   ├── js/
│   │   ├── tracking.js                 # Frontend tracking
│   │   └── admin-dashboard.js          # Dashboard interactions
│   └── images/
│       └── icon.png                    # Plugin icon
├── includes/
│   ├── class-activator.php             # Activation hooks
│   ├── class-deactivator.php           # Deactivation hooks
│   ├── class-database.php              # Database operations
│   ├── class-tracker.php               # Event tracking logic
│   ├── class-ajax-handler.php          # AJAX request handler
│   ├── class-rest-api.php              # REST API endpoints
│   ├── class-admin-dashboard.php       # Admin dashboard UI
│   ├── class-data-analyzer.php         # Data analysis & calculations
│   ├── class-cache-manager.php         # Caching layer
│   └── class-settings.php              # Settings page
├── templates/
│   ├── admin-dashboard.php             # Dashboard template
│   └── settings-page.php               # Settings template
└── vendor/                              # Composer dependencies (if any)
```

---

## Detailed Implementation Requirements

### 1. Main Plugin File (`woo-performance-tracker.php`)
**Requirements**:
- Standard WordPress plugin header with metadata
- Check for WooCommerce dependency
- Define constants: `WPT_VERSION`, `WPT_PLUGIN_DIR`, `WPT_PLUGIN_URL`
- Register activation/deactivation hooks
- Initialize autoloader for classes
- Bootstrap the plugin

**Example Structure**:
```php
<?php
/**
 * Plugin Name: WooPerformance Tracker
 * Description: Track WooCommerce performance metrics with custom analytics
 * Version: 1.0.0
 * Author: Your Name
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// Define constants
define('WPT_VERSION', '1.0.0');
define('WPT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Require autoloader or main classes
require_once WPT_PLUGIN_DIR . 'includes/class-activator.php';
// ... initialization code
```

---

### 2. Database Class (`includes/class-database.php`)
**Responsibilities**:
- Create/update custom table on activation
- Provide methods for CRUD operations
- Handle database queries efficiently
- Implement prepared statements for security

**Required Methods**:
- `create_table()` - Create performance logs table
- `insert_event($data)` - Insert tracking event
- `get_events($filters)` - Retrieve events with filters
- `get_stats($date_range)` - Get aggregated statistics
- `cleanup_old_data($days)` - Remove old logs
- `table_exists()` - Check if table exists

---

### 3. Tracker Class (`includes/class-tracker.php`)
**Responsibilities**:
- Hook into WooCommerce actions
- Log events to database
- Generate session IDs for anonymous users
- Handle data sanitization

**WooCommerce Hooks to Implement**:
- `template_redirect` - Track product views
- `woocommerce_add_to_cart` - Track add to cart
- `woocommerce_before_checkout_form` - Track checkout initiation
- `woocommerce_thankyou` - Track order completion
- `woocommerce_cart_updated` - Detect potential abandonments

**Required Methods**:
- `track_product_view($product_id)`
- `track_add_to_cart($product_id, $quantity)`
- `track_checkout_initiated()`
- `track_order_completed($order_id)`
- `get_session_id()` - Generate/retrieve session identifier
- `should_track()` - Check if tracking is enabled/allowed

---

### 4. AJAX Handler (`includes/class-ajax-handler.php`)
**Responsibilities**:
- Handle AJAX requests from frontend
- Validate nonces for security
- Return JSON responses

**Required AJAX Actions**:
- `wpt_track_view` - Log product view via AJAX
- `wpt_track_event` - Generic event tracking
- Both for logged-in and guest users (`wp_ajax_*` and `wp_ajax_nopriv_*`)

**Implementation Pattern**:
```php
add_action('wp_ajax_wpt_track_view', [$this, 'handle_track_view']);
add_action('wp_ajax_nopriv_wpt_track_view', [$this, 'handle_track_view']);
```

---

### 5. REST API Class (`includes/class-rest-api.php`)
**Responsibilities**:
- Register REST API endpoints
- Implement proper authentication
- Return formatted JSON data
- Handle error responses

**Required Endpoints**:

**GET** `/wp-json/wc-performance/v1/stats`
- Query Parameters: `date_from`, `date_to`, `product_id` (optional)
- Response: Overall statistics
```json
{
  "success": true,
  "data": {
    "total_views": 1250,
    "total_add_to_cart": 320,
    "total_orders": 85,
    "conversion_rate": 26.56,
    "revenue": 12450.50,
    "cart_abandonment_rate": 73.44
  }
}
```

**GET** `/wp-json/wc-performance/v1/products`
- Query Parameters: `date_from`, `date_to`, `limit`, `orderby`
- Response: Product performance list

**GET** `/wp-json/wc-performance/v1/timeline`
- Query Parameters: `date_from`, `date_to`, `interval` (hour/day/week/month)
- Response: Time-series data for charts

**Implementation Requirements**:
- Use `register_rest_route()`
- Implement permission callbacks
- Validate and sanitize parameters
- Use WP_REST_Response for responses

---

### 6. Admin Dashboard (`includes/class-admin-dashboard.php`)
**Responsibilities**:
- Register admin menu page
- Enqueue Chart.js or Recharts
- Render dashboard template
- Fetch and prepare data for charts

**Dashboard Components**:
1. **Summary Cards** (Top Row):
   - Total Product Views
   - Total Add to Carts
   - Total Orders
   - Conversion Rate
   - Revenue
   - Cart Abandonment Rate

2. **Charts** (Visual Analytics):
   - Line Chart: Views vs Add-to-Cart vs Orders (over time)
   - Bar Chart: Top 10 Products by Views
   - Pie Chart: Conversion Funnel (Views → Cart → Orders)
   - Line Chart: Daily Revenue Trend

3. **Filters**:
   - Date Range Picker (Last 7 days, 30 days, Custom)
   - Product Filter (All Products or specific)
   - Export Button (CSV download)

**Required Methods**:
- `add_menu_page()` - Register admin menu
- `enqueue_assets()` - Load CSS/JS
- `render_dashboard()` - Output dashboard HTML
- `get_dashboard_data()` - Fetch statistics

---

### 7. Data Analyzer (`includes/class-data-analyzer.php`)
**Responsibilities**:
- Calculate conversion rates
- Identify cart abandonments
- Generate insights
- Aggregate data for reports

**Required Methods**:
- `calculate_conversion_rate($views, $orders)`
- `get_top_products($limit, $date_range)`
- `get_abandonment_rate($date_range)`
- `get_timeline_data($interval, $date_range)`
- `get_funnel_data($date_range)` - Views → Cart → Checkout → Orders

---

### 8. Cache Manager (`includes/class-cache-manager.php`)
**Responsibilities**:
- Implement transient-based caching
- Cache expensive queries
- Provide cache invalidation
- Set appropriate expiration times

**Required Methods**:
- `get($key)` - Get cached data
- `set($key, $data, $expiration)` - Store cache
- `delete($key)` - Remove cache
- `flush()` - Clear all plugin caches
- `get_cache_key($identifier)` - Generate unique keys

**Caching Strategy**:
- Cache dashboard stats for 5 minutes
- Cache product lists for 15 minutes
- Invalidate cache on new events (configurable)
- Use WordPress Transients API

---

### 9. Settings Page (`includes/class-settings.php`)
**Responsibilities**:
- Register settings using Settings API
- Create settings page UI
- Handle form submissions
- Validate settings

**Settings Options**:
- Enable/Disable Tracking
- Data Retention Period (days)
- Cache Duration (minutes)
- Exclude User Roles (don't track admins, etc.)
- Track Anonymous Users (Yes/No)
- GDPR Compliance Options
- Auto-cleanup Old Data (Yes/No)

**Implementation**:
- Use `register_setting()`, `add_settings_section()`, `add_settings_field()`
- Sanitize all inputs
- Provide default values

---

### 10. Frontend Tracking Script (`assets/js/tracking.js`)
**Responsibilities**:
- Track product views via AJAX
- Send tracking data asynchronously
- Handle errors gracefully
- Respect user privacy settings

**Implementation**:
```javascript
(function($) {
  'use strict';
  
  // Track product view on page load
  if (wptTracker.productId) {
    $.ajax({
      url: wptTracker.ajaxUrl,
      type: 'POST',
      data: {
        action: 'wpt_track_view',
        product_id: wptTracker.productId,
        nonce: wptTracker.nonce
      }
    });
  }
  
  // Track add to cart clicks
  $(document).on('click', '.add_to_cart_button', function() {
    // Tracking logic
  });
})(jQuery);
```

**Localize Script** (in PHP):
```php
wp_localize_script('wpt-tracking', 'wptTracker', [
  'ajaxUrl' => admin_url('admin-ajax.php'),
  'nonce' => wp_create_nonce('wpt_track_nonce'),
  'productId' => get_the_ID(),
]);
```

---

### 11. Dashboard JavaScript (`assets/js/admin-dashboard.js`)
**Responsibilities**:
- Initialize charts using Chart.js
- Fetch data via REST API
- Handle date range filters
- Update charts dynamically
- Handle CSV export

**Chart.js Implementation Example**:
```javascript
// Fetch data and render chart
fetch('/wp-json/wc-performance/v1/timeline?date_from=' + dateFrom + '&date_to=' + dateTo)
  .then(response => response.json())
  .then(data => {
    const ctx = document.getElementById('timelineChart').getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: data.labels,
        datasets: [
          {
            label: 'Views',
            data: data.views,
            borderColor: 'rgb(75, 192, 192)'
          },
          {
            label: 'Add to Cart',
            data: data.add_to_cart,
            borderColor: 'rgb(255, 159, 64)'
          },
          {
            label: 'Orders',
            data: data.orders,
            borderColor: 'rgb(54, 162, 235)'
          }
        ]
      }
    });
  });
```

---

## Performance Optimization Requirements

### 1. Database Optimization
- Add indexes on frequently queried columns (`event_type`, `product_id`, `created_at`)
- Use `$wpdb->prepare()` for all queries
- Limit result sets with pagination
- Implement bulk insert for multiple events
- Archive old data instead of deleting

### 2. Caching Strategy
- Cache dashboard statistics
- Use object caching if available
- Implement cache warming for popular queries
- Set appropriate cache expiration times
- Invalidate cache intelligently

### 3. Query Optimization
- Avoid N+1 query problems
- Use JOIN operations efficiently
- Limit SELECT columns to necessary fields
- Implement date range indexes
- Use aggregate functions in SQL rather than PHP

### 4. Asset Loading
- Enqueue scripts only on relevant pages
- Minify CSS and JS in production
- Use `wp_enqueue_script()` with proper dependencies
- Defer non-critical JavaScript
- Conditionally load Chart.js only on dashboard

### 5. AJAX Optimization
- Debounce AJAX requests
- Batch multiple events when possible
- Use WordPress nonces for security
- Implement request throttling
- Handle errors and retries gracefully

---

## Security Requirements

### 1. Data Validation & Sanitization
- Sanitize all user inputs using WordPress functions
- Validate data types before database insertion
- Escape output using `esc_html()`, `esc_attr()`, `esc_url()`
- Use `absint()` for IDs, `sanitize_text_field()` for strings

### 2. Nonce Verification
- Implement nonces for all AJAX requests
- Verify nonces before processing any action
- Use `wp_create_nonce()` and `wp_verify_nonce()`

### 3. Capability Checks
- Check user permissions before displaying admin pages
- Use `current_user_can('manage_woocommerce')` for admin features
- Implement proper REST API authentication
- Restrict sensitive endpoints

### 4. SQL Injection Prevention
- Use `$wpdb->prepare()` for all queries
- Never concatenate user input into SQL
- Use parameterized queries exclusively

### 5. Privacy & GDPR
- Anonymize IP addresses (optional setting)
- Provide data export functionality
- Implement data deletion on user request
- Add privacy policy information
- Allow users to opt-out of tracking

---

## Code Quality Standards

### 1. WordPress Coding Standards
- Follow WordPress PHP Coding Standards
- Use proper naming conventions (snake_case for functions, PascalCase for classes)
- Add proper DocBlocks for all functions and classes
- Use proper indentation (tabs for indentation, spaces for alignment)

### 2. Documentation Requirements
Each file should include:
- File-level DocBlock with description and package info
- Class-level DocBlock
- Method-level DocBlocks with @param and @return
- Inline comments for complex logic

**Example**:
```php
/**
 * Database operations for performance tracking
 *
 * @package WooPerformanceTracker
 * @since 1.0.0
 */
class Database {
  /**
   * Insert a tracking event
   *
   * @param array $data Event data to insert
   * @return int|false Insert ID on success, false on failure
   */
  public function insert_event($data) {
    // Implementation
  }
}
```

### 3. Error Handling
- Use try-catch blocks for database operations
- Log errors using `error_log()`
- Return WP_Error objects for failures
- Display user-friendly error messages
- Implement fallback mechanisms

### 4. File Organization
- Keep files under 1000 lines (STRICT REQUIREMENT)
- One class per file
- Separate concerns (data, logic, presentation)
- Group related functionality
- Use namespaces for better organization

---

## Testing Requirements

### 1. Manual Testing Checklist
- [ ] Plugin activates without errors
- [ ] Custom table created successfully
- [ ] Product views tracked correctly
- [ ] Add-to-cart events logged
- [ ] Order completion recorded with revenue
- [ ] Dashboard displays accurate data
- [ ] Charts render properly
- [ ] Date filters work correctly
- [ ] REST API returns valid JSON
- [ ] Settings save and load properly
- [ ] Cache invalidation works
- [ ] Plugin deactivates cleanly
- [ ] Uninstall removes all data

### 2. Edge Cases to Handle
- WooCommerce not active (show admin notice)
- Database table creation failure
- Large data sets (pagination)
- Concurrent requests (race conditions)
- Invalid product IDs
- Missing order data
- Cache availability
- JavaScript disabled in browser

---

## Installation & Setup Instructions

### For AI to Generate
Include a detailed README.md with:

1. **Installation Steps**:
   - Upload plugin to WordPress
   - Activate plugin
   - Verify WooCommerce is active
   - Check database table creation

2. **Configuration**:
   - Navigate to WooCommerce → Performance Tracker
   - Configure settings
   - Set data retention period
   - Enable/disable tracking features

3. **Usage Guide**:
   - How to view dashboard
   - Understanding metrics
   - Using filters
   - Exporting data

4. **Developer Documentation**:
   - Available hooks and filters
   - REST API endpoints
   - Database schema
   - Extending the plugin

---

## Deliverables Checklist

When building this plugin, ensure ALL of the following are completed:

- [ ] All files follow boilerplate structure
- [ ] No file exceeds 1000 lines of code
- [ ] Custom database table created with proper indexes
- [ ] All WooCommerce hooks implemented
- [ ] AJAX tracking for product views working
- [ ] REST API endpoints functional and documented
- [ ] Admin dashboard with 4+ charts
- [ ] Summary statistics calculated correctly
- [ ] Caching layer implemented
- [ ] Settings page with all options
- [ ] Frontend tracking script loaded conditionally
- [ ] All security measures in place (nonces, sanitization, escaping)
- [ ] Error handling implemented throughout
- [ ] Code follows WordPress coding standards
- [ ] Proper documentation and comments
- [ ] README.md with installation instructions
- [ ] Activation/deactivation hooks working
- [ ] Uninstall script removes all data
- [ ] Performance optimizations applied
- [ ] GDPR compliance options available

---

## Advanced Features (Optional Enhancements)

Consider implementing these for a more robust solution:

1. **Email Reports**: Send weekly/monthly performance reports to admin
2. **Real-time Dashboard**: Use WebSockets for live metrics
3. **Product Recommendations**: Suggest products based on view patterns
4. **A/B Testing**: Track performance of product variations
5. **Heatmaps**: Show where users click on product pages
6. **Export Options**: CSV, PDF, Excel formats
7. **Scheduled Reports**: Auto-generate and email reports
8. **Multi-store Support**: For WooCommerce networks
9. **Comparison Tools**: Compare time periods
10. **Alert System**: Notify on unusual patterns

---

## Final Notes for AI Implementation

- **Modularity**: Keep classes focused on single responsibilities
- **Scalability**: Design for stores with thousands of products
- **Maintainability**: Write clean, documented code
- **Extensibility**: Provide hooks for developers to extend
- **User Experience**: Make dashboard intuitive and fast
- **Performance**: Always prioritize performance optimization
- **Security**: Never compromise on security practices
- **Standards**: Strictly follow WordPress and WooCommerce conventions

**Remember**: Each file should be self-contained, well-documented, and under 1000 lines. Break large files into smaller, logical components when necessary.

---

## Example Implementation Order

1. Create main plugin file and define constants
2. Build Database class and create table
3. Implement Tracker class with WooCommerce hooks
4. Set up AJAX handler for frontend tracking
5. Create REST API endpoints
6. Build Data Analyzer for calculations
7. Implement Cache Manager
8. Design Admin Dashboard with templates
9. Add Settings page
10. Write frontend tracking JavaScript
11. Create dashboard JavaScript with charts
12. Add CSS styling
13. Implement activation/deactivation/uninstall hooks
14. Test thoroughly
15. Document everything

---

**Version**: 1.0.0  
**Last Updated**: October 2025  
**License**: GPLv2 or later