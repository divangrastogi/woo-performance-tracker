/**
 * Admin dashboard script for WooPerformance Tracker
 *
 * Handles dashboard interactions and chart rendering
 *
 * @package WooPerformanceTracker
 * @since 1.0.0
 */

(function ($) {
  "use strict";

  /**
   * Dashboard class
   */
  var WPTDashboard = {
    charts: {},

    /**
     * Initialize dashboard
     */
    init: function () {
      console.log("WPT: Initializing dashboard");
      this.bindEvents();
      this.initHeaderSparkline();
      this.removeAdminNotices();
      this.initTableStates();
      // Load initial data quietly (without showing loading state)
      setTimeout(
        function () {
          this.loadDashboardData(false);
        }.bind(this),
        100,
      );
      console.log("WPT: Dashboard initialization complete");
    },

    /**
     * Handle date filter form submission
     */
    handleDateFilter: function (e) {
      if (e) {
        e.preventDefault();
      }
      this.loadDashboardData(true);
    },

    /**
     * Bind event handlers
     */
    bindEvents: function () {
      // Date range filter
      $("#wpt-date-filter").on("submit", this.handleDateFilter.bind(this));

      // Export button
      $("#wpt-export-csv").on("click", this.handleExport.bind(this));

      // Refresh button
      $("#wpt-refresh-data").on("click", this.handleDateFilter.bind(this));
    },

    /**
     * Initialize table states
     */
    initTableStates: function () {
      console.log("WPT: Initializing table states");
      console.log(
        "WPT: Looking for table:",
        $(".wpt-top-products tbody").length,
      );

      // Show initial "no data" state for tables
      this.showTableNoData(".wpt-top-products tbody");

      console.log("WPT: Table state initialized");
    },

    /**
     * Show loading state for table
     */
    showTableLoading: function (tableSelector) {
      var $tbody = $(tableSelector);
      $tbody.html(
        '<tr><td colspan="6" class="wpt-text-center">' +
          '<span class="wpt-loading"></span>' +
          "Loading data..." +
          "</td></tr>",
      );
    },

    /**
     * Show no data state for table
     */
    showTableNoData: function (tableSelector) {
      console.log("WPT: Showing no data for selector:", tableSelector);
      var $tbody = $(tableSelector);
      console.log("WPT: Table body found:", $tbody.length, "elements");

      if ($tbody.length === 0) {
        console.error("WPT: Table body not found for selector:", tableSelector);
        return;
      }

      var html =
        '<tr><td colspan="6" class="wpt-no-data">' +
        '<div class="wpt-no-data-content">' +
        '<span class="wpt-no-data-icon">📊</span>' +
        '<span class="wpt-no-data-text">' +
        (wptDashboard && wptDashboard.strings
          ? wptDashboard.strings.noData
          : "No data available") +
        "</span>" +
        '<span class="wpt-no-data-subtext">Try adjusting your date range or check if tracking is enabled.</span>' +
        "</div></td></tr>";

      $tbody.html(html);
      console.log("WPT: No data HTML set");
    },

    /**
     * Clear error states
     */
    clearErrors: function () {
      $(".wpt-error-message").remove();
      $("#wpt-dashboard-container").removeClass("has-error");
    },

    /**
     * Remove admin notices from dashboard page
     */
    removeAdminNotices: function () {
      // Hide all admin notices on the dashboard page
      $(".notice, .error, .updated, .update-nag").hide();

      // Also hide notices that might be added dynamically
      $(document).on("DOMNodeInserted", function (e) {
        if (
          $(e.target).hasClass("notice") ||
          $(e.target).hasClass("error") ||
          $(e.target).hasClass("updated") ||
          $(e.target).hasClass("update-nag")
        ) {
          $(e.target).hide();
        }
      });

      // Periodic check for any notices that might slip through
      setInterval(function () {
        $(".notice, .error, .updated, .update-nag").hide();
      }, 1000);
    },

    /**
     * Initialize header sparkline chart
     */
    initHeaderSparkline: function () {
      var ctx = document.getElementById("wpt-header-sparkline");
      if (!ctx) return;

      // Sample data - in real implementation, this would come from API
      var data = [12, 19, 15, 25, 22, 30, 28, 35, 32, 38, 42, 45, 48, 52, 55];

      this.charts.headerSparkline = new Chart(ctx, {
        type: "line",
        data: {
          labels: Array(data.length).fill(""),
          datasets: [
            {
              data: data,
              borderColor: "rgba(255, 255, 255, 0.8)",
              backgroundColor: "rgba(255, 255, 255, 0.1)",
              borderWidth: 2,
              fill: true,
              pointRadius: 0,
              pointHoverRadius: 0,
              tension: 0.4,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false,
            },
            tooltip: {
              enabled: false,
            },
          },
          scales: {
            x: {
              display: false,
            },
            y: {
              display: false,
            },
          },
          elements: {
            point: {
              hoverRadius: 0,
            },
          },
          animation: {
            duration: 2000,
            easing: "easeInOutQuart",
          },
        },
      });
    },

    /**
     * Load dashboard data
     *
     * @param {boolean} showLoading Whether to show loading states
     */
    loadDashboardData: function (showLoading) {
      if (showLoading === undefined) {
        showLoading = true;
      }

      var self = this;
      var $container = $("#wpt-dashboard-container");

      if (showLoading) {
        $container.addClass("loading");
      }

      var dateFrom = $("#wpt-date-from").val();
      var dateTo = $("#wpt-date-to").val();

      // Clear any existing error states
      this.clearErrors();

      // Show loading state for products table only if showLoading is true
      if (showLoading) {
        this.showTableLoading(".wpt-top-products tbody");
      } else {
        // For quiet loading, keep the current "no data" state
        // The table will be updated when data arrives
      }

      // Track AJAX requests
      var ajaxRequests = 3; // stats, timeline, products

      function onAjaxComplete() {
        ajaxRequests--;
        if (ajaxRequests === 0 && showLoading) {
          $container.removeClass("loading");
        }
      }

      // Load stats
      $.ajax({
        url: wptDashboard.ajaxUrl,
        method: "POST",
        data: {
          action: "wpt_get_dashboard_stats",
          nonce: wptDashboard.nonce,
          date_from: dateFrom,
          date_to: dateTo,
        },
        success: function (response) {
          if (response.success) {
            self.updateSummaryCards(response.data);
          } else {
            self.showError("API Error: " + (response.data || "Unknown error"));
          }
          onAjaxComplete();
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", xhr, status, error);
          self.showError("Failed to load statistics: " + error);
          onAjaxComplete();
        },
      });

      // Load timeline data
      $.ajax({
        url: wptDashboard.ajaxUrl,
        method: "POST",
        data: {
          action: "wpt_get_dashboard_timeline",
          nonce: wptDashboard.nonce,
          date_from: dateFrom,
          date_to: dateTo,
          interval: "day",
        },
        success: function (response) {
          if (response.success) {
            self.renderTimelineChart(response.data);
          } else {
            self.showError("API Error: " + (response.data || "Unknown error"));
          }
          onAjaxComplete();
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", xhr, status, error);
          self.showError("Failed to load timeline data: " + error);
          onAjaxComplete();
        },
      });

      // Load top products
      $.ajax({
        url: wptDashboard.ajaxUrl,
        method: "POST",
        timeout: 30000, // 30 seconds timeout
        data: {
          action: "wpt_get_dashboard_products",
          nonce: wptDashboard.nonce,
          date_from: dateFrom,
          date_to: dateTo,
          limit: 10,
        },
        success: function (response) {
          console.log("Products response:", response);
          if (response.success) {
            self.renderTopProductsTable(response.data || []);
            self.renderFunnelChart(response.data || []);
          } else {
            console.error("Products API Error:", response.data);
            self.renderTopProductsTable([]); // Show no data message
            self.renderFunnelChart([]);
            self.showError("API Error: " + (response.data || "Unknown error"));
          }
          onAjaxComplete();
        },
        error: function (xhr, status, error) {
          console.error("Products AJAX Error:", xhr, status, error);
          console.error("Response text:", xhr.responseText);
          self.renderTopProductsTable([]); // Always show no data message on error
          self.renderFunnelChart([]);
          self.showError("Failed to load products data: " + error);
          onAjaxComplete();
        },
      });
    },

    /**
     * Update summary cards
     *
     * @param {Object} data Statistics data
     */
    updateSummaryCards: function (data) {
      $("#wpt-total-views").text(this.formatNumber(data.total_views));
      $("#wpt-total-add-to-cart").text(
        this.formatNumber(data.total_add_to_cart),
      );
      $("#wpt-total-orders").text(this.formatNumber(data.total_orders));
      $("#wpt-conversion-rate").text(data.conversion_rate + "%");
      $("#wpt-total-revenue").text("$" + this.formatNumber(data.revenue));
      $("#wpt-abandonment-rate").text(data.cart_abandonment_rate + "%");
    },

    /**
     * Render timeline chart
     *
     * @param {Object} data Timeline data
     */
    renderTimelineChart: function (data) {
      var ctx = document.getElementById("wpt-timeline-chart");
      if (!ctx) return;

      // Destroy existing chart
      if (this.charts.timeline) {
        this.charts.timeline.destroy();
      }

      this.charts.timeline = new Chart(ctx, {
        type: "line",
        data: {
          labels: data.labels,
          datasets: [
            {
              label: "Views",
              data: data.views,
              borderColor: "rgb(54, 162, 235)",
              backgroundColor: "rgba(54, 162, 235, 0.1)",
              tension: 0.4,
            },
            {
              label: "Add to Cart",
              data: data.add_to_cart,
              borderColor: "rgb(255, 205, 86)",
              backgroundColor: "rgba(255, 205, 86, 0.1)",
              tension: 0.4,
            },
            {
              label: "Orders",
              data: data.orders,
              borderColor: "rgb(75, 192, 192)",
              backgroundColor: "rgba(75, 192, 192, 0.1)",
              tension: 0.4,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: "top",
            },
            title: {
              display: true,
              text: "Performance Timeline",
            },
          },
          scales: {
            y: {
              beginAtZero: true,
            },
          },
        },
      });
    },

    /**
     * Render top products table
     *
     * @param {Array} products Products data
     */
    renderTopProductsTable: function (products) {
      var $tbody = $(".wpt-top-products tbody");
      $tbody.empty();

      // Ensure products is an array
      if (!Array.isArray(products)) {
        products = [];
      }

      if (products.length === 0) {
        // Show no data message after loading attempt
        this.showTableNoData(".wpt-top-products tbody");
        return;
      }

      products.forEach(function (product) {
        var row =
          "<tr>" +
          '<td><a href="' +
          product.product_url +
          '" target="_blank">' +
          product.product_name +
          "</a></td>" +
          "<td>" +
          this.formatNumber(product.views) +
          "</td>" +
          "<td>" +
          this.formatNumber(product.add_to_cart) +
          "</td>" +
          "<td>" +
          this.formatNumber(product.orders) +
          "</td>" +
          "<td>$" +
          this.formatNumber(product.revenue) +
          "</td>" +
          "<td>" +
          product.conversion_rate +
          "%</td>" +
          "</tr>";
        $tbody.append(row);
      }, this);
    },

    /**
     * Render funnel chart
     *
     * @param {Array} products Products data for funnel calculation
     */
    renderFunnelChart: function (products) {
      // Calculate funnel data from products
      var funnelData = {
        views: products.reduce(function (sum, product) {
          return sum + product.views;
        }, 0),
        add_to_cart: products.reduce(function (sum, product) {
          return sum + product.add_to_cart;
        }, 0),
        orders: products.reduce(function (sum, product) {
          return sum + product.orders;
        }, 0),
      };

      var ctx = document.getElementById("wpt-funnel-chart");
      if (!ctx) return;

      // Destroy existing chart
      if (this.charts.funnel) {
        this.charts.funnel.destroy();
      }

      this.charts.funnel = new Chart(ctx, {
        type: "doughnut",
        data: {
          labels: ["Views", "Add to Cart", "Orders"],
          datasets: [
            {
              data: [
                funnelData.views,
                funnelData.add_to_cart,
                funnelData.orders,
              ],
              backgroundColor: [
                "rgb(54, 162, 235)",
                "rgb(255, 205, 86)",
                "rgb(75, 192, 192)",
              ],
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: "bottom",
            },
            title: {
              display: true,
              text: "Conversion Funnel",
            },
          },
        },
      });
    },

    /**
     * Handle date filter form submission
     *
     * @param {Event} e Form submit event
     */
    handleDateFilter: function (e) {
      e.preventDefault();
      this.loadDashboardData();
    },

    /**
     * Handle CSV export
     */
    handleExport: function () {
      var dateFrom = $("#wpt-date-from").val();
      var dateTo = $("#wpt-date-to").val();

      var url =
        wptDashboard.restUrl +
        "/products?date_from=" +
        dateFrom +
        "&date_to=" +
        dateTo +
        "&format=csv";
      window.open(url, "_blank");
    },

    /**
     * Show error message
     *
     * @param {string} message Error message
     */
    showError: function (message) {
      // Simple error display - could be enhanced with proper notices
      alert(message);
    },

    /**
     * Format number with commas
     *
     * @param {number} num Number to format
     * @return {string} Formatted number
     */
    formatNumber: function (num) {
      return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },
  };

  // Expose to global scope for debugging and external access
  window.WPTDashboard = WPTDashboard;

  // Initialize on document ready
  $(document).ready(function () {
    WPTDashboard.init();
  });
})(jQuery);
