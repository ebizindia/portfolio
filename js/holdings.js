/**
 * Holdings Page JavaScript
 */

let currentHoldingsData = [];

$(document).ready(function() {
    // Initialize holdings page
    initHoldingsPage();

    // Load initial data
    loadHoldings();

    // Event handlers
    $('#btn_load').on('click', loadHoldings);
    $('#btn_export').on('click', exportHoldings);

    $('#view_type').on('change', function() {
        const viewType = $(this).val();

        $('#portfolio_filter_div').hide();
        $('#combination_filter_div').hide();

        if (viewType === 'portfolio') {
            $('#portfolio_filter_div').show();
        } else if (viewType === 'combination') {
            $('#combination_filter_div').show();
        }
    });

    // Search functionality
    $('#search_box').on('keyup', function() {
        filterHoldingsTable();
    });
});

/**
 * Initialize Holdings Page
 */
function initHoldingsPage() {
    // Any initialization code
}

/**
 * Load Holdings Data
 */
function loadHoldings() {
    const viewType = $('#view_type').val();
    let filterId = 0;

    if (viewType === 'portfolio') {
        filterId = parseInt($('#filter_portfolio').val()) || 0;
        if (filterId === 0) {
            showAlert('warning', 'Please select a portfolio');
            return;
        }
    } else if (viewType === 'combination') {
        filterId = parseInt($('#filter_combination').val()) || 0;
        if (filterId === 0) {
            showAlert('warning', 'Please select a combination');
            return;
        }
    }

    $.ajax({
        url: 'holdings.php',
        type: 'POST',
        data: {
            mode: 'getAllHoldings',
            view_type: viewType,
            filter_id: filterId
        },
        dataType: 'json',
        beforeSend: function() {
            $('#holdings_tbody').html('<tr><td colspan="11" class="text-center">Loading...</td></tr>');
        },
        success: function(response) {
            if (response.error_code === 0 && response.data) {
                currentHoldingsData = response.data;
                displayHoldings(response.data);
                updateSummaryCards(response.data);
            } else {
                $('#holdings_tbody').html('<tr><td colspan="11" class="text-center text-danger">' + (response.message || 'Error loading holdings') + '</td></tr>');
            }
        },
        error: function() {
            $('#holdings_tbody').html('<tr><td colspan="11" class="text-center text-danger">Failed to load holdings</td></tr>');
        }
    });
}

/**
 * Display Holdings in Table
 */
function displayHoldings(holdings) {
    const tbody = $('#holdings_tbody');
    tbody.empty();

    if (holdings.length === 0) {
        tbody.append('<tr><td colspan="11" class="text-center">No holdings found</td></tr>');
        return;
    }

    let totalInvested = 0;
    let totalCurrent = 0;
    let totalQuantity = 0;
    let totalPL = 0;

    holdings.forEach(function(holding) {
        const invested = parseFloat(holding.total_invested) || 0;
        const current = parseFloat(holding.current_value) || 0;
        const pl = parseFloat(holding.unrealized_pl) || 0;
        const quantity = parseFloat(holding.current_quantity) || 0;

        totalInvested += invested;
        totalCurrent += current;
        totalPL += pl;
        totalQuantity += quantity;

        const plPct = invested > 0 ? (pl / invested) * 100 : 0;
        const portfolioPct = totalCurrent > 0 ? (current / totalCurrent) * 100 : 0;
        const plClass = pl >= 0 ? 'text-success' : 'text-danger';

        const row = `
            <tr>
                <td>${escapeHtml(holding.stock_code)}</td>
                <td>${escapeHtml(holding.stock_name)}</td>
                <td>${escapeHtml(holding.portfolio_name || '')}</td>
                <td class="text-end">${formatNumber(quantity, 2)}</td>
                <td class="text-end">₹${formatNumber(holding.avg_cost_price)}</td>
                <td class="text-end">₹${formatNumber(invested)}</td>
                <td class="text-end">₹${formatNumber(holding.current_market_price || 0)}</td>
                <td class="text-end">₹${formatNumber(current)}</td>
                <td class="text-end ${plClass}">₹${formatNumber(pl)}</td>
                <td class="text-end ${plClass}">${formatNumber(plPct, 2)}%</td>
                <td class="text-end">${formatNumber(portfolioPct, 2)}%</td>
            </tr>
        `;
        tbody.append(row);
    });

    // Update footer
    const totalPLPct = totalInvested > 0 ? (totalPL / totalInvested) * 100 : 0;
    const plClass = totalPL >= 0 ? 'text-success' : 'text-danger';

    $('#footer_quantity').text(formatNumber(totalQuantity, 2));
    $('#footer_invested').text('₹' + formatNumber(totalInvested));
    $('#footer_current').text('₹' + formatNumber(totalCurrent));
    $('#footer_pl').text('₹' + formatNumber(totalPL)).attr('class', 'text-end ' + plClass);
    $('#footer_pl_pct').text(formatNumber(totalPLPct, 2) + '%').attr('class', 'text-end ' + plClass);
}

/**
 * Update Summary Cards
 */
function updateSummaryCards(holdings) {
    let totalInvested = 0;
    let totalCurrent = 0;
    let totalPL = 0;

    holdings.forEach(function(holding) {
        totalInvested += parseFloat(holding.total_invested) || 0;
        totalCurrent += parseFloat(holding.current_value) || 0;
        totalPL += parseFloat(holding.unrealized_pl) || 0;
    });

    const totalPLPct = totalInvested > 0 ? (totalPL / totalInvested) * 100 : 0;

    $('#total_holdings').text(holdings.length);
    $('#total_invested').text('₹' + formatNumber(totalInvested));
    $('#current_value').text('₹' + formatNumber(totalCurrent));
    $('#unrealized_pl').text('₹' + formatNumber(totalPL));
    $('#unrealized_pl_pct').text('(' + formatNumber(totalPLPct, 2) + '%)');

    // Color code
    if (totalPL >= 0) {
        $('#unrealized_pl').addClass('text-success').removeClass('text-danger');
    } else {
        $('#unrealized_pl').addClass('text-danger').removeClass('text-success');
    }
}

/**
 * Filter Holdings Table based on search
 */
function filterHoldingsTable() {
    const searchText = $('#search_box').val().toLowerCase();

    if (searchText === '') {
        displayHoldings(currentHoldingsData);
        return;
    }

    const filtered = currentHoldingsData.filter(function(holding) {
        return (
            holding.stock_code.toLowerCase().includes(searchText) ||
            holding.stock_name.toLowerCase().includes(searchText) ||
            (holding.portfolio_name && holding.portfolio_name.toLowerCase().includes(searchText))
        );
    });

    displayHoldings(filtered);
}

/**
 * Export Holdings to Excel
 */
function exportHoldings() {
    const viewType = $('#view_type').val();
    let filterId = 0;

    if (viewType === 'portfolio') {
        filterId = parseInt($('#filter_portfolio').val()) || 0;
    } else if (viewType === 'combination') {
        filterId = parseInt($('#filter_combination').val()) || 0;
    }

    // Create a form and submit
    const form = $('<form>', {
        'method': 'POST',
        'action': 'holdings.php'
    });

    form.append($('<input>', {
        'type': 'hidden',
        'name': 'mode',
        'value': 'exportHoldings'
    }));

    form.append($('<input>', {
        'type': 'hidden',
        'name': 'view_type',
        'value': viewType
    }));

    form.append($('<input>', {
        'type': 'hidden',
        'name': 'filter_id',
        'value': filterId
    }));

    $('body').append(form);
    form.submit();
    form.remove();
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
    if (typeof bootbox !== 'undefined') {
        bootbox.alert(message);
    } else {
        alert(message);
    }
}
