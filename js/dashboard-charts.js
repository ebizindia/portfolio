/**
 * Dashboard Charts using Chart.js
 * Note: Requires Chart.js library to be loaded
 */

// Chart instances
let performanceChart = null;
let distributionChart = null;
let portfolioChart = null;
let holdingsChart = null;
let compositionChart = null;

/**
 * Create Performance Chart for Consolidated Dashboard
 */
function createPerformanceChart(labels, data) {
    const ctx = document.getElementById('performance_chart');
    if (!ctx) return;

    if (performanceChart) {
        performanceChart.destroy();
    }

    performanceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Portfolio Value',
                data: data.values,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }, {
                label: 'Invested Amount',
                data: data.invested,
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Portfolio Performance Over Time'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₹' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

/**
 * Create Distribution Chart (Pie Chart)
 */
function createDistributionChart(labels, data) {
    const ctx = document.getElementById('distribution_chart');
    if (!ctx) return;

    if (distributionChart) {
        distributionChart.destroy();
    }

    distributionChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(255, 159, 64, 0.8)',
                    'rgba(199, 199, 199, 0.8)',
                    'rgba(83, 102, 255, 0.8)',
                    'rgba(255, 102, 204, 0.8)',
                    'rgba(102, 255, 178, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                },
                title: {
                    display: true,
                    text: 'Portfolio Distribution by Value'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '₹' + context.parsed.toLocaleString();
                            return label;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Create Portfolio Chart for Combination Dashboard
 */
function createPortfolioChart(labels, data) {
    const ctx = document.getElementById('portfolio_chart');
    if (!ctx) return;

    if (portfolioChart) {
        portfolioChart.destroy();
    }

    portfolioChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                title: {
                    display: false
                }
            }
        }
    });
}

/**
 * Create Holdings Chart (Bar Chart)
 */
function createHoldingsChart(labels, data) {
    const ctx = document.getElementById('holdings_chart');
    if (!ctx) return;

    if (holdingsChart) {
        holdingsChart.destroy();
    }

    holdingsChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Current Value',
                data: data,
                backgroundColor: 'rgba(75, 192, 192, 0.8)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₹' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

/**
 * Create Composition Chart for Individual Portfolio
 */
function createCompositionChart(labels, data) {
    const ctx = document.getElementById('composition_chart');
    if (!ctx) return;

    if (compositionChart) {
        compositionChart.destroy();
    }

    compositionChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(255, 159, 64, 0.8)',
                    'rgba(199, 199, 199, 0.8)',
                    'rgba(83, 102, 255, 0.8)',
                    'rgba(255, 102, 204, 0.8)',
                    'rgba(102, 255, 178, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        font: {
                            size: 10
                        }
                    }
                },
                title: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(2);
                            label += '₹' + value.toLocaleString() + ' (' + percentage + '%)';
                            return label;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Update charts when data changes
 */
function updateChartsOnDataLoad() {
    // This function can be called after AJAX data is loaded
    // to update charts with new data
}

/**
 * Destroy all charts (useful for cleanup)
 */
function destroyAllCharts() {
    if (performanceChart) performanceChart.destroy();
    if (distributionChart) distributionChart.destroy();
    if (portfolioChart) portfolioChart.destroy();
    if (holdingsChart) holdingsChart.destroy();
    if (compositionChart) compositionChart.destroy();
}
