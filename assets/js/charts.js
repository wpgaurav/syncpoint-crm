/**
 * Starter CRM Dashboard Charts
 *
 * @package StarterCRM
 * @since 1.0.0
 */

(function () {
    'use strict';

    /**
     * Initialize charts when DOM is ready.
     */
    document.addEventListener('DOMContentLoaded', function () {
        initRevenueChart();
        initContactsChart();
    });

    /**
     * Initialize revenue chart.
     */
    function initRevenueChart() {
        var canvas = document.getElementById('scrm-revenue-chart');
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        var ctx = canvas.getContext('2d');

        // Sample data - will be replaced with real data via AJAX.
        var labels = getLast30Days();
        var data = generateSampleData(30, 500, 3000);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Revenue',
                    data: data,
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function (context) {
                                return '$' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }

    /**
     * Initialize contacts chart.
     */
    function initContactsChart() {
        var canvas = document.getElementById('scrm-contacts-chart');
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        var ctx = canvas.getContext('2d');

        // Sample data.
        var labels = getLast30Days();
        var data = generateCumulativeData(30, 5, 15);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Contacts',
                    data: data,
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    /**
     * Get last 30 days labels.
     *
     * @return {Array} Array of date labels.
     */
    function getLast30Days() {
        var labels = [];
        var today = new Date();

        for (var i = 29; i >= 0; i--) {
            var date = new Date(today);
            date.setDate(date.getDate() - i);
            labels.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
        }

        return labels;
    }

    /**
     * Generate sample data.
     *
     * @param {number} count Number of data points.
     * @param {number} min Minimum value.
     * @param {number} max Maximum value.
     * @return {Array} Array of data points.
     */
    function generateSampleData(count, min, max) {
        var data = [];
        for (var i = 0; i < count; i++) {
            data.push(Math.floor(Math.random() * (max - min + 1)) + min);
        }
        return data;
    }

    /**
     * Generate cumulative data.
     *
     * @param {number} count Number of data points.
     * @param {number} minIncrement Min daily increment.
     * @param {number} maxIncrement Max daily increment.
     * @return {Array} Array of cumulative data points.
     */
    function generateCumulativeData(count, minIncrement, maxIncrement) {
        var data = [];
        var total = 100; // Starting value

        for (var i = 0; i < count; i++) {
            total += Math.floor(Math.random() * (maxIncrement - minIncrement + 1)) + minIncrement;
            data.push(total);
        }

        return data;
    }

})();
