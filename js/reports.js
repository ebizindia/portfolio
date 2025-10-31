/**
 * Reports Page JavaScript
 */

$(document).ready(function() {
    // Initialize reports page
    initReportsPage();

    // Report type change handler
    $('input[name="report_type"]').on('change', function() {
        updateParameterVisibility();
    });

    // Form submit handler
    $('#report_form').on('submit', function(e) {
        e.preventDefault();
        generateReport();
    });

    // Initial parameter visibility
    updateParameterVisibility();
});

/**
 * Initialize Reports Page
 */
function initReportsPage() {
    // Any initialization code
}

/**
 * Update parameter visibility based on report type
 */
function updateParameterVisibility() {
    const reportType = $('input[name="report_type"]:checked').val();

    // Hide all optional sections
    $('#combination_div').hide();
    $('#portfolio_div').hide();
    $('#date_range_div').hide();

    // Show relevant sections based on report type
    switch (reportType) {
        case 'combination':
            $('#combination_div').show();
            break;

        case 'individual':
            $('#portfolio_div').show();
            break;

        case 'transactions':
        case 'realized_pl':
            $('#date_range_div').show();
            $('#portfolio_div').show();
            break;

        case 'holdings':
            $('#portfolio_div').show();
            break;

        case 'consolidated':
        default:
            // No additional parameters needed
            break;
    }
}

/**
 * Generate Report
 */
function generateReport() {
    // Hide previous results
    $('#report_result').hide();
    $('#report_error').hide();

    // Get form data
    const formData = {
        mode: 'generateReport',
        report_type: $('input[name="report_type"]:checked').val(),
        format: $('input[name="format"]:checked').val(),
        combination_id: $('#combination_id').val() || null,
        portfolio_ids: $('#portfolio_ids').val() ? [$('#portfolio_ids').val()] : [],
        start_date: $('#start_date').val() || null,
        end_date: $('#end_date').val() || null
    };

    // Validate based on report type
    if (formData.report_type === 'combination' && !formData.combination_id) {
        showError('Please select a combination');
        return;
    }

    if (formData.report_type === 'individual' && formData.portfolio_ids.length === 0) {
        showError('Please select a portfolio');
        return;
    }

    // Disable generate button
    $('#btn_generate').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generating...');

    $.ajax({
        url: 'reports.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.error_code === 0) {
                // Show download link
                $('#download_link').attr('href', response.file_url);
                $('#report_result').show();

                // Show success message
                if (typeof bootbox !== 'undefined') {
                    bootbox.alert({
                        message: 'Report generated successfully! Click the download button to get your report.',
                        className: 'bootbox-success'
                    });
                }
            } else {
                showError(response.message || 'Failed to generate report');
            }
        },
        error: function() {
            showError('Failed to generate report due to server error');
        },
        complete: function() {
            // Re-enable generate button
            $('#btn_generate').prop('disabled', false).html('<i class="fas fa-cog"></i> Generate Report');
        }
    });
}

/**
 * Show error message
 */
function showError(message) {
    $('#error_message').text(message);
    $('#report_error').show();

    // Scroll to error
    $('html, body').animate({
        scrollTop: $('#report_error').offset().top - 100
    }, 500);
}

/**
 * Helper function to format numbers
 */
function formatNumber(num, decimals = 2) {
    if (num === null || num === undefined) return '0.00';
    return parseFloat(num).toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}
