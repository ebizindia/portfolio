/**
 * Transaction Management Module
 */
var transactionfuncs = (function() {
    var currentPage = 1;
    var currentSort = [];
    var currentFilters = {};

    /**
     * Initialize the module
     */
    function init() {
        setupEventListeners();
        loadPortfolioList();
        loadTransactions();
    }

    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Toggle filters
        $('.toggle-filters').on('click', function() {
            $('#filter-section').toggleClass('d-none');
        });

        // Sortable columns
        $('.sortable').on('click', function() {
            var field = this.id.replace('colheader_', '');
            handleSort(field);
        });
    }

    /**
     * Load portfolio list for filter
     */
    function loadPortfolioList() {
        $.ajax({
            url: 'transactions.php',
            type: 'POST',
            data: { mode: 'getPortfolioList' },
            success: function(response) {
                try {
                    var portfolios = JSON.parse(response);
                    var select = $('#filter-portfolio');
                    select.html('<option value="">All Portfolios</option>');

                    if (portfolios && portfolios.length > 0) {
                        portfolios.forEach(function(p) {
                            select.append('<option value="' + p.portfolio_id + '">' + p.portfolio_name + '</option>');
                        });
                    }
                } catch (e) {
                    console.error('Error loading portfolio list:', e);
                }
            }
        });
    }

    /**
     * Load transactions
     */
    function loadTransactions(page) {
        page = page || currentPage;
        currentPage = page;

        var data = {
            mode: 'getList',
            pno: page
        };

        // Add filters
        if (currentFilters.portfolio_id) {
            data.portfolio_id = currentFilters.portfolio_id;
        }
        if (currentFilters.transaction_type) {
            data.transaction_type = currentFilters.transaction_type;
        }
        if (currentFilters.start_date) {
            data.start_date = currentFilters.start_date;
        }
        if (currentFilters.end_date) {
            data.end_date = currentFilters.end_date;
        }

        // Add sorting
        if (currentSort.length > 0) {
            data.sortdata = JSON.stringify(currentSort);
        }

        $.ajax({
            url: 'transactions.php',
            type: 'POST',
            data: data,
            success: function(response) {
                try {
                    var result = JSON.parse(response);
                    if (result[0] === 0) {
                        displayTransactions(result[1]);
                    } else {
                        $('#table_body').html('<tr><td colspan="9" class="text-danger text-center">Error loading transactions</td></tr>');
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    $('#table_body').html('<tr><td colspan="9" class="text-danger text-center">Error loading transactions</td></tr>');
                }
            },
            error: function() {
                $('#table_body').html('<tr><td colspan="9" class="text-danger text-center">Network error</td></tr>');
            }
        });
    }

    /**
     * Display transactions
     */
    function displayTransactions(data) {
        var tbody = $('#table_body');
        var html = '';

        if (!data.list || data.list.length === 0) {
            html = '<tr><td colspan="9" class="text-center">No transactions found</td></tr>';
        } else {
            data.list.forEach(function(rec) {
                var typeClass = rec.transaction_type === 'BUY' ? 'text-success' : 'text-danger';
                var date = new Date(rec.transaction_date);
                var dateStr = ('0' + date.getDate()).slice(-2) + '-' +
                              ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][date.getMonth()] + '-' +
                              date.getFullYear();

                html += '<tr>';
                html += '<td>';
                html += '<button class="btn btn-sm btn-primary" onclick="transactionfuncs.editRecord(' + rec.transaction_id + ')" title="Edit"><i class="fa fa-edit"></i></button> ';
                html += '<button class="btn btn-sm btn-danger" onclick="transactionfuncs.deleteRecord(' + rec.transaction_id + ')" title="Delete"><i class="fa fa-trash"></i></button>';
                html += '</td>';
                html += '<td>' + dateStr + '</td>';
                html += '<td>' + rec.portfolio_name + '</td>';
                html += '<td>' + rec.stock_code + '</td>';
                html += '<td>' + rec.stock_name + '</td>';
                html += '<td class="' + typeClass + '"><strong>' + rec.transaction_type + '</strong></td>';
                html += '<td class="text-right">' + parseFloat(rec.quantity).toFixed(2) + '</td>';
                html += '<td class="text-right">₹' + parseFloat(rec.price).toFixed(2) + '</td>';
                html += '<td class="text-right">₹' + parseFloat(rec.transaction_value).toFixed(2) + '</td>';
                html += '</tr>';
            });
        }

        tbody.html(html);

        // Update count
        $('#heading_rec_cnt').text('(' + (data.reccount || 0) + ')');
    }

    /**
     * Handle sorting
     */
    function handleSort(field) {
        var existingSort = currentSort.find(function(s) { return s.sorton === field; });

        if (existingSort) {
            existingSort.sortorder = existingSort.sortorder === 'ASC' ? 'DESC' : 'ASC';
        } else {
            currentSort = [{ sorton: field, sortorder: 'ASC' }];
        }

        // Update sort icons
        $('.sortable i').removeClass('fa-sort-up fa-sort-down').addClass('fa-sort');
        var icon = $('#colheader_' + field + ' i');
        icon.removeClass('fa-sort').addClass(
            existingSort && existingSort.sortorder === 'ASC' ? 'fa-sort-up' : 'fa-sort-down'
        );

        loadTransactions(1);
    }

    /**
     * Apply filters
     */
    function applyFilters() {
        currentFilters = {
            portfolio_id: $('#filter-portfolio').val(),
            transaction_type: $('#filter-type').val(),
            start_date: $('#filter-start-date').val(),
            end_date: $('#filter-end-date').val()
        };

        loadTransactions(1);
    }

    /**
     * Clear filters
     */
    function clearFilters() {
        $('#filter-portfolio').val('');
        $('#filter-type').val('');
        $('#filter-start-date').val('');
        $('#filter-end-date').val('');
        currentFilters = {};
        loadTransactions(1);
    }

    /**
     * Edit record
     */
    function editRecord(transactionId) {
        bootbox.alert('Edit functionality will be implemented in the next phase.');
    }

    /**
     * Delete record
     */
    function deleteRecord(transactionId) {
        bootbox.confirm({
            message: 'Are you sure you want to delete this transaction?<br><br><strong>Warning:</strong> This action cannot be undone.',
            buttons: {
                confirm: {
                    label: 'Yes, Delete',
                    className: 'btn-danger'
                },
                cancel: {
                    label: 'Cancel',
                    className: 'btn-secondary'
                }
            },
            callback: function(result) {
                if (result) {
                    performDelete(transactionId);
                }
            }
        });
    }

    /**
     * Perform delete
     */
    function performDelete(transactionId) {
        $.ajax({
            url: 'transactions.php',
            type: 'POST',
            data: {
                mode: 'deleterec',
                recordid: transactionId
            },
            success: function(response) {
                try {
                    var result = JSON.parse(response);
                    if (result.error_code === 0) {
                        bootbox.alert('Transaction deleted successfully.', function() {
                            loadTransactions();
                        });
                    } else {
                        bootbox.alert('Failed to delete: ' + result.message);
                    }
                } catch (e) {
                    bootbox.alert('Error processing response.');
                }
            },
            error: function() {
                bootbox.alert('Delete failed due to network error.');
            }
        });
    }

    /**
     * Export to CSV
     */
    function exportToCSV() {
        var form = $('<form>', {
            'method': 'POST',
            'action': 'transactions.php'
        });

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'mode',
            'value': 'exportcsv'
        }));

        // Add filters
        if (currentFilters.portfolio_id) {
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'portfolio_id',
                'value': currentFilters.portfolio_id
            }));
        }
        if (currentFilters.transaction_type) {
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'transaction_type',
                'value': currentFilters.transaction_type
            }));
        }
        if (currentFilters.start_date) {
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'start_date',
                'value': currentFilters.start_date
            }));
        }
        if (currentFilters.end_date) {
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'end_date',
                'value': currentFilters.end_date
            }));
        }

        form.appendTo('body').submit().remove();
    }

    /**
     * Clear search
     */
    function clearSearch() {
        clearFilters();
    }

    // Public API
    return {
        init: init,
        editRecord: editRecord,
        deleteRecord: deleteRecord,
        applyFilters: applyFilters,
        clearFilters: clearFilters,
        exportToCSV: exportToCSV,
        clearSearch: clearSearch
    };
})();

// Initialize on document ready
$(document).ready(function() {
    transactionfuncs.init();
});
