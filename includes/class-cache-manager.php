<?php
/**
 * Cache manager for WooPerformance Tracker
 *
 * Handles caching of expensive queries and data
 *
 * @package WooPerformanceTracker
 * @since 1.0.0
 */

namespace WooPerformanceTracker;

/**
 * Cache_Manager class
 */
class Cache_Manager {

    /**
     * Cache prefix
     */
    const CACHE_PREFIX = 'wpt_';

    /**
     * Get cached data
     *
     * @param string $key Cache key
     * @return mixed Cached data or false if not found
     */
    public function get($key) {
        $cache_key = $this->get_cache_key($key);
        return get_transient($cache_key);
    }

    /**
     * Set cache data
     *
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int $expiration Expiration time in seconds
     * @return bool
     */
    public function set($key, $data, $expiration = null) {
        if ($expiration === null) {
            $expiration = get_option('wpt_cache_duration', 300);
        }

        $cache_key = $this->get_cache_key($key);
        return set_transient($cache_key, $data, $expiration);
    }

    /**
     * Delete cached data
     *
     * @param string $key Cache key
     * @return bool
     */
    public function delete($key) {
        $cache_key = $this->get_cache_key($key);
        return delete_transient($cache_key);
    }

    /**
     * Flush all plugin caches
     *
     * @return bool
     */
    public function flush() {
        global $wpdb;

        $prefix = '_transient_' . self::CACHE_PREFIX;

        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $prefix . '%'
            )
        );

        // Also clear timeout entries
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_' . $prefix . '%'
            )
        );

        return $result !== false;
    }

    /**
     * Get cache key with prefix
     *
     * @param string $key Base key
     * @return string Full cache key
     */
    public function get_cache_key($key) {
        return self::CACHE_PREFIX . md5($key);
    }

    /**
     * Check if caching is available
     *
     * @return bool
     */
    public function is_available() {
        // Check if transients are working
        $test_key = $this->get_cache_key('test');
        $test_value = 'test_value_' . time();

        if (!$this->set($test_key, $test_value, 60)) {
            return false;
        }

        $retrieved = $this->get($test_key);
        $this->delete($test_key);

        return $retrieved === $test_value;
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public function get_stats() {
        global $wpdb;

        $prefix = '_transient_' . self::CACHE_PREFIX;

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
                $prefix . '%'
            )
        );

        return array(
            'cached_items' => intval($count),
            'cache_available' => $this->is_available(),
        );
    }

    /**
     * Warm up common caches
     */
    public function warmup() {
        // This could be called on plugin activation or via cron
        // to pre-populate common queries

        $analyzer = woo_performance_tracker()->analyzer;

        // Warm up today's stats
        $today = array(
            'from' => date('Y-m-d'),
            'to' => date('Y-m-d'),
        );

        $analyzer->get_timeline_data('day', $today);
        $analyzer->get_top_products(10, $today);

        // Warm up last 7 days
        $week = array(
            'from' => date('Y-m-d', strtotime('-7 days')),
            'to' => date('Y-m-d'),
        );

        $analyzer->get_timeline_data('day', $week);
        $analyzer->get_top_products(10, $week);
    }
}