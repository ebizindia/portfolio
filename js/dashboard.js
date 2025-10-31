/**
 * Portfolio Dashboard JavaScript
 */

$(document).ready(function() {
    // Dashboard Consolidated Page
    if ($('#portfolio_summary_table').length) {
        initConsolidatedDashboard();
    }

    // Dashboard Combination Page
    if ($('#combination_id').length) {
        initCombinationDashboard();
    }

    // Dashboard Individual Page
    if ($('#portfolio_content').length && $('#portfolio_id').length) {
        initIndividualDashboard();
    }
});

/**
 * Initialize Consolidated Dashboard
 */
function initConsolidatedDashboard() {
    loadConsolidatedMetrics();
    loadPortfolioSummary();
    loadYearwiseComparison();

    $('#btn_refresh').on('click', function() {
        loadConsolidatedMetrics();
        loadPortfolioSummary();
        loadYearwiseComparison();
    });
}

/**
 * Load Consolidated Metrics
 */
function loadConsolidatedMetrics() {
    const endDate = $('#end_date').val();
    const benchmark = $('#benchmark').val();

    $.ajax({
        url: 'dashboard-consolidated.php',
        type: 'POST',
        data: {
            mode: 'getConsolidatedMetrics',
            end_date: endDate,
            benchmark: benchmark
        },
        dataType: 'json',
        success: function(response) {
            if (response.error_code === 0 && response.data) {
                const data = response.data;

                $('#total_invested').text('₹' + formatNumber(data.total_invested));
                $('#current_value').text('₹' + formatNumber(data.current_value));
                $('#total_pl').text('₹' + formatNumber(data.total_pl));
                $('#total_pl_pct').text('(' + formatNumber(data.total_pl_pct, 2) + '%)');
                $('#xirr').text(data.xirr !== null ? formatNumber(data.xirr, 2) + '%' : 'N/A');

                // Color code P&L
                if (data.total_pl >= 0) {
                    $('#total_pl').addClass('text-success').removeClass('text-danger');
                } else {
                    $('#total_pl').addClass('text-danger').removeClass('text-success');
                }
            } else {
                showAlert('error', response.message || 'Error loading metrics');
            }
        },
        error: function() {
            showAlert('error', 'Failed to load consolidated metrics');
        }
    });
}

/**
 * Load Portfolio Summary
 */
function loadPortfolioSummary() {
    $.ajax({
        url: 'dashboard-consolidated.php',
        type: 'POST',
        data: { mode: 'getPortfolioSummary' },
        dataType: 'json',
        success: function(response) {
            if (response.error_code === 0 && response.data) {
                const tbody = $('#portfolio_summary_tbody');
                tbody.empty();

                if (response.data.length === 0) {
                    tbody.append('<tr><td colspan="8" class="text-center">No portfolios found</td></tr>');
                    return;
                }

                response.data.forEach(function(portfolio) {
                    const plClass = portfolio.total_pl >= 0 ? 'text-success' : 'text-danger';

                    const row = `
                        <tr>
                            <td><a href="dashboard-individual.php?portfolio_id=${portfolio.portfolio_id}">${escapeHtml(portfolio.portfolio_name)}</a></td>
                            <td>${escapeHtml(portfolio.portfolio_type)}</td>
                            <td class="text-end">₹${formatNumber(portfolio.total_invested)}</td>
                            <td class="text-end">₹${formatNumber(portfolio.current_value)}</td>
                            <td class="text-end ${plClass}">₹${formatNumber(portfolio.unrealized_pl)}</td>
                            <td class="text-end ${plClass}">₹${formatNumber(portfolio.realized_pl)}</td>
                            <td class="text-end ${plClass}">₹${formatNumber(portfolio.total_pl)}</td>
                            <td class="text-end ${plClass}">${formatNumber(portfolio.total_pl_pct, 2)}%</td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            }
        }
    });
}

/**
 * Load Year-wise Comparison
 */
function loadYearwiseComparison() {
    $.ajax({
        url: 'dashboard-consolidated.php',
        type: 'POST',
        data: { mode: 'getYearwiseComparison' },
        dataType: 'json',
        success: function(response) {
            if (response.error_code === 0 && response.data) {
                const tbody = $('#yearwise_tbody');
                tbody.empty();

                response.data.forEach(function(year) {
                    const plClass = year.pl >= 0 ? 'text-success' : 'text-danger';

                    const row = `
                        <tr>
                            <td>${year.year}</td>
                            <td class="text-end">₹${formatNumber(year.total_invested)}</td>
                            <td class="text-end">₹${formatNumber(year.current_value)}</td>
                            <td class="text-end ${plClass}">₹${formatNumber(year.pl)}</td>
                            <td class="text-end ${plClass}">${formatNumber(year.pl_pct, 2)}%</td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            }
        }
    });
}

/**
 * Initialize Combination Dashboard
 */
function initCombinationDashboard() {
    $('#btn_load').on('click', function() {
        const combinationId = $('#combination_id').val();
        if (!combinationId) {
            showAlert('warning', 'Please select a combination');
            return;
        }
        loadCombinationData(combinationId);
    });

    // Auto-load if combination_id in URL
    const urlParams = new URLSearchParams(window.location.search);
    const combinationId = urlParams.get('combination_id');
    if (combinationId) {
        $('#combination_id').val(combinationId);
        loadCombinationData(combinationId);
    }
}

/**
 * Load Combination Data
 */
function loadCombinationData(combinationId) {
    // Load metrics
    $.ajax({
        url: 'dashboard-combination.php',
        type: 'POST',
        data: {
            mode: 'getCombinationMetrics',
            combination_id: combinationId
        },
        dataType: 'json',
        success: function(response) {
            if (response.error_code === 0 && response.data) {
                const data = response.data;

                $('#portfolio_count').text(data.portfolio_count);
                $('#total_invested').text('₹' + formatNumber(data.total_invested));
                $('#current_value').text('₹' + formatNumber(data.current_value));
                $('#total_pl').text('₹' + formatNumber(data.total_pl));
                $('#total_pl_pct').text('(' + formatNumber(data.total_pl_pct, 2) + '%)');
                $('#unrealized_pl').text('₹' + formatNumber(data.unrealized_pl));
                $('#realized_pl').text('₹' + formatNumber(data.realized_pl));
                $('#xirr').text(data.xirr !== null ? formatNumber(data.xirr, 2) + '%' : 'N/A');

                $('#combination_content').show();
                $('#no_selection_msg').hide();
            }
        }
    });

    // Load portfolio breakdown
    loadPortfolioBreakdown(combinationId);

    // Load top holdings
    loadTopHoldings(combinationId);
}

/**
 * Load Portfolio Breakdown
 */
function loadPortfolioBreakdown(combinationId) {
    $.ajax({
        url: 'dashboard-combination.php',
        type: 'POST',
        data: {
            mode: 'getPortfolioBreakdown',
            combination_id: combinationId
        },
        dataType: 'json',
        success: function(response) {
            if (response.error_code === 0 && response.data) {
                const tbody = $('#breakdown_tbody');
                tbody.empty();

                response.data.forEach(function(portfolio) {
                    const row = `
                        <tr>
                            <td>${escapeHtml(portfolio.portfolio_name)}</td>
                            <td>${escapeHtml(portfolio.portfolio_type)}</td>
                            <td class="text-end">₹${formatNumber(portfolio.total_invested)}</td>
                            <td class="text-end">₹${formatNumber(portfolio.current_value)}</td>
                            <td class="text-end">₹${formatNumber(portfolio.unrealized_pl)}</td>
                            <td class="text-end">₹${formatNumber(portfolio.realized_pl)}</td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            }
        }
    });
}

/**
 * Load Top Holdings
 */
function loadTopHoldings(combinationId) {
    $.ajax({
        url: 'dashboard-combination.php',
        type: 'POST',
        data: {
            mode: 'getTopHoldings',
            combination_id: combinationId,
            limit: 10
        },
        dataType: 'json',
        success: function(response) {
            if (response.error_code === 0 && response.data) {
                const tbody = $('#holdings_tbody');
                tbody.empty();

                response.data.forEach(function(holding) {
                    const pl = (holding.current_value || 0) - (holding.total_invested || 0);
                    const plClass = pl >= 0 ? 'text-success' : 'text-danger';

                    const row = `
                        <tr>
                            <td>${escapeHtml(holding.stock_code)}</td>
                            <td>${escapeHtml(holding.stock_name)}</td>
                            <td class="text-end">${formatNumber(holding.current_quantity, 2)}</td>
                            <td class="text-end">₹${formatNumber(holding.avg_cost_price)}</td>
                            <td class="text-end">₹${formatNumber(holding.current_market_price || 0)}</td>
                            <td class="text-end">₹${formatNumber(holding.current_value || 0)}</td>
                            <td class="text-end ${plClass}">₹${formatNumber(pl)}</td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            }
        }
    });
}

/**
 * Initialize Individual Dashboard
 */
function initIndividualDashboard() {
    $('#btn_load').on('click', function() {
        const portfolioId = $('#portfolio_id').val();
        if (!portfolioId) {
            showAlert('warning', 'Please select a portfolio');
            return;
        }
        loadIndividualPortfolioData(portfolioId);
    });

    // Auto-load if portfolio_id in URL
    const urlParams = new URLSearchParams(window.location.search);
    const portfolioId = urlParams.get('portfolio_id');
    if (portfolioId) {
        $('#portfolio_id').val(portfolioId);
        loadIndividualPortfolioData(portfolioId);
    }
}

/**
 * Load Individual Portfolio Data
 */
function loadIndividualPortfolioData(portfolioId) {
    const endDate = $('#end_date').val();

    // Load metrics
    $.ajax({
        url: 'dashboard-individual.php',
        type: 'POST',
        data: {
            mode: 'getPortfolioMetrics',
            portfolio_id: portfolioId,
            end_date: endDate
        },
        dataType: 'json',
        success: function(response) {
            if (response.error_code === 0 && response.data) {
                const data = response.data;

                $('#total_invested').text('₹' + formatNumber(data.total_invested));
                $('#current_value').text('₹' + formatNumber(data.current_value));
                $('#total_pl').text('₹' + formatNumber(data.total_pl));
                $('#total_pl_pct').text('(' + formatNumber(data.total_pl_pct, 2) + '%)');
                $('#unrealized_pl').text('₹' + formatNumber(data.unrealized_pl));
                $('#realized_pl').text('₹' + formatNumber(data.realized_pl));
                $('#xirr').text(data.xirr !== null ? formatNumber(data.xirr, 2) + '%' : 'N/A');

                $('#portfolio_content').show();
                $('#no_selection_msg').hide();
            }
        }
    });

    // Load holdings
    loadPortfolioHoldings(portfolioId);

    // Load transactions
    loadTransactionHistory(portfolioId);
}

/**
 * Load Portfolio Holdings
 */
function loadPortfolioHoldings(portfolioId) {
    $.ajax({
        url: 'dashboard-individual.php',
        type: 'POST',
        data: {
            mode: 'getPortfolioHoldings',
            portfolio_id: portfolioId
        },
        dataType: 'json',
        success: function(response) {
            if (response.error_code === 0 && response.data) {
                const tbody = $('#holdings_tbody');
                tbody.empty();

                if (response.data.length === 0) {
                    tbody.append('<tr><td colspan="9" class="text-center">No holdings found</td></tr>');
                    return;
                }

                response.data.forEach(function(holding) {
                    const plPct = holding.total_invested > 0
                        ? ((holding.unrealized_pl || 0) / holding.total_invested) * 100
                        : 0;
                    const plClass = (holding.unrealized_pl || 0) >= 0 ? 'text-success' : 'text-danger';

                    const row = `
                        <tr>
                            <td>${escapeHtml(holding.stock_code)}</td>
                            <td>${escapeHtml(holding.stock_name)}</td>
                            <td class="text-end">${formatNumber(holding.current_quantity, 2)}</td>
                            <td class="text-end">₹${formatNumber(holding.avg_cost_price)}</td>
                            <td class="text-end">₹${formatNumber(holding.total_invested)}</td>
                            <td class="text-end">₹${formatNumber(holding.current_market_price || 0)}</td>
                            <td class="text-end">₹${formatNumber(holding.current_value || 0)}</td>
                            <td class="text-end ${plClass}">₹${formatNumber(holding.unrealized_pl || 0)}</td>
                            <td class="text-end ${plClass}">${formatNumber(plPct, 2)}%</td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            }
        }
    });
}

/**
 * Load Transaction History
 */
function loadTransactionHistory(portfolioId) {
    $.ajax({
        url: 'dashboard-individual.php',
        type: 'POST',
        data: {
            mode: 'getTransactionHistory',
            portfolio_id: portfolioId
        },
        dataType: 'json',
        success: function(response) {
            if (response.error_code === 0 && response.data) {
                const tbody = $('#transactions_tbody');
                tbody.empty();

                // Show only last 20 transactions
                const transactions = response.data.slice(0, 20);

                transactions.forEach(function(txn) {
                    const typeClass = txn.transaction_type === 'BUY' ? 'text-success' : 'text-danger';

                    const row = `
                        <tr>
                            <td>${txn.transaction_date}</td>
                            <td>${escapeHtml(txn.stock_code)}</td>
                            <td class="${typeClass}">${txn.transaction_type}</td>
                            <td class="text-end">${formatNumber(txn.quantity, 2)}</td>
                            <td class="text-end">₹${formatNumber(txn.price)}</td>
                            <td class="text-end">₹${formatNumber(txn.transaction_value)}</td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            }
        }
    });
}

/**
 * Helper Functions
 */
function formatNumber(num, decimals = 2) {
    if (num === null || num === undefined) return '0.00';
    return parseFloat(num).toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, m => map[m]);
}

function showAlert(type, message) {
    // Using bootbox if available
    if (typeof bootbox !== 'undefined') {
        bootbox.alert(message);
    } else {
        alert(message);
    }
}
