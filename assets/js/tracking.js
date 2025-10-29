/**
 * Frontend tracking script for WooPerformance Tracker
 *
 * Tracks product views and other events
 *
 * @package WooPerformanceTracker
 * @since 1.0.0
 */

(function ($) {
  "use strict";

  /**
   * Tracking class
   */
  var WPTTracking = {
    /**
     * Initialize tracking
     */
    init: function () {
      this.bindEvents();
      this.trackProductView();
    },

    /**
     * Bind event handlers
     */
    bindEvents: function () {
      // Track add to cart events
      $(document).on(
        "click",
        ".add_to_cart_button",
        this.trackAddToCart.bind(this),
      );

      // Track product view on page load (for AJAX-loaded content)
      $(document).on("wc-product-view", this.trackProductView.bind(this));
    },

    /**
     * Track product view
     */
    trackProductView: function () {
      if (typeof wptTracker === "undefined" || !wptTracker.productId) {
        return;
      }

      this.sendTrackingEvent("product_view", {
        product_id: wptTracker.productId,
      });
    },

    /**
     * Track add to cart
     *
     * @param {Event} e Click event
     */
    trackAddToCart: function (e) {
      var $button = $(e.currentTarget);
      var productId = $button.data("product_id") || $button.val();

      if (!productId) {
        // Try to find product ID from form
        var $form = $button.closest("form");
        productId = $form.find('input[name="product_id"]').val();
      }

      if (productId) {
        var quantity = $button.data("quantity") || 1;

        this.sendTrackingEvent("add_to_cart", {
          product_id: parseInt(productId),
          quantity: parseInt(quantity),
        });
      }
    },

    /**
     * Send tracking event via AJAX
     *
     * @param {string} eventType Event type
     * @param {Object} eventData Event data
     */
    sendTrackingEvent: function (eventType, eventData) {
      if (typeof wptTracker === "undefined") {
        return;
      }

      $.ajax({
        url: wptTracker.ajaxUrl,
        type: "POST",
        data: {
          action: "wpt_track_event",
          event_type: eventType,
          event_data: JSON.stringify(eventData),
          nonce: wptTracker.nonce,
        },
        success: function (response) {
          if (window.console && window.console.log) {
            console.log("WPT: Event tracked", eventType, eventData);
          }
        },
        error: function (xhr, status, error) {
          if (window.console && window.console.error) {
            console.error("WPT: Tracking error", error);
          }
        },
      });
    },

    /**
     * Check if tracking should be performed
     *
     * @return {boolean}
     */
    shouldTrack: function () {
      // Check if Do Not Track is enabled
      if (navigator.doNotTrack === "1" || navigator.doNotTrack === "yes") {
        return false;
      }

      // Check for privacy/consent cookies if applicable
      // This could be extended to check for GDPR consent

      return true;
    },
  };

  // Initialize on document ready
  $(document).ready(function () {
    WPTTracking.init();
  });

  // Also initialize on AJAX content loaded (for themes that load content via AJAX)
  $(document).on("ajaxComplete", function () {
    // Re-initialize if needed
  });
})(jQuery);
