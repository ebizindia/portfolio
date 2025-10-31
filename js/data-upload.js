/**
 * Data Upload Module - Handles file upload, validation, and import
 */
var dataUploadFuncs = (function() {
    var currentUploadId = null;
    var validationData = null;

    /**
     * Initialize the module
     */
    function init() {
        setupEventListeners();
        loadUploadHistory();
    }

    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        var uploadArea = document.getElementById('upload-area');
        var fileInput = document.getElementById('file-input');

        if (!uploadArea || !fileInput) return;

        // Click to browse
        uploadArea.addEventListener('click', function() {
            fileInput.click();
        });

        // File selection
        fileInput.addEventListener('change', function(e) {
            if (this.files.length > 0) {
                handleFileSelect(this.files[0]);
            }
        });

        // Drag and drop
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('drag-over');
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('drag-over');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('drag-over');

            var files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFileSelect(files[0]);
            }
        });

        // Import button
        var importBtn = document.getElementById('import-btn');
        if (importBtn) {
            importBtn.addEventListener('click', importFile);
        }

        // Cancel button
        var cancelBtn = document.getElementById('cancel-btn');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', resetUpload);
        }
    }

    /**
     * Handle file selection
     */
    function handleFileSelect(file) {
        // Validate file type
        var validExtensions = ['xlsx', 'xls', 'csv'];
        var fileName = file.name;
        var fileExt = fileName.split('.').pop().toLowerCase();

        if (validExtensions.indexOf(fileExt) === -1) {
            bootbox.alert('Invalid file format. Please upload Excel (.xlsx, .xls) or CSV files only.');
            return;
        }

        // Validate file size (10MB)
        if (file.size > 10 * 1024 * 1024) {
            bootbox.alert('File size exceeds 10MB limit. Please upload a smaller file.');
            return;
        }

        // Upload file
        uploadFile(file);
    }

    /**
     * Upload file to server
     */
    function uploadFile(file) {
        var formData = new FormData();
        formData.append('upload_file', file);
        formData.append('mode', 'uploadfile');

        showProgress('Uploading file...', 30);

        $.ajax({
            url: 'data-upload.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                try {
                    var result = JSON.parse(response);
                    if (result.error_code === 0) {
                        currentUploadId = result.upload_id;
                        showProgress('Upload successful. Validating...', 50);
                        validateFile(result.upload_id);
                    } else {
                        hideProgress();
                        bootbox.alert('Upload failed: ' + result.message);
                    }
                } catch (e) {
                    hideProgress();
                    bootbox.alert('Error processing server response.');
                }
            },
            error: function() {
                hideProgress();
                bootbox.alert('Upload failed due to network error. Please try again.');
            }
        });
    }

    /**
     * Validate uploaded file
     */
    function validateFile(uploadId) {
        $.ajax({
            url: 'data-upload.php',
            type: 'POST',
            data: {
                mode: 'validatefile',
                upload_id: uploadId
            },
            success: function(response) {
                try {
                    var result = JSON.parse(response);
                    if (result.error_code === 0) {
                        showProgress('Validation successful!', 100);
                        validationData = result.validation;
                        displayValidationResults(result.validation);
                        setTimeout(hideProgress, 1000);
                    } else {
                        hideProgress();
                        validationData = result.validation;
                        displayValidationResults(result.validation);
                    }
                    loadUploadHistory();
                } catch (e) {
                    hideProgress();
                    bootbox.alert('Error processing validation response.');
                }
            },
            error: function() {
                hideProgress();
                bootbox.alert('Validation failed due to network error.');
            }
        });
    }

    /**
     * Display validation results
     */
    function displayValidationResults(validation) {
        var resultsDiv = document.getElementById('validation-results');
        var importActionsDiv = document.getElementById('import-actions');

        if (!resultsDiv) return;

        var html = '';

        if (validation.valid) {
            // Success
            html += '<div class="validation-box validation-success">';
            html += '<h5><i class="fa fa-check-circle"></i> Validation Successful</h5>';
            html += '<p>Found ' + validation.data.length + ' records ready to import.</p>';

            // Warnings
            if (validation.warnings && validation.warnings.length > 0) {
                html += '<div class="mt-2"><strong>Warnings:</strong></div>';
                html += '<ul class="warning-list">';
                validation.warnings.forEach(function(warning) {
                    html += '<li><i class="fa fa-exclamation-triangle text-warning"></i> ' + warning + '</li>';
                });
                html += '</ul>';
            }

            html += '</div>';
            importActionsDiv.style.display = 'block';

        } else {
            // Errors
            html += '<div class="validation-box validation-error">';
            html += '<h5><i class="fa fa-times-circle"></i> Validation Failed</h5>';
            html += '<p>Please fix the following errors and re-upload the file:</p>';
            html += '<ul class="error-list">';
            validation.errors.forEach(function(error) {
                html += '<li><i class="fa fa-times text-danger"></i> ' + error + '</li>';
            });
            html += '</ul>';
            html += '</div>';
            importActionsDiv.style.display = 'none';
        }

        resultsDiv.innerHTML = html;
    }

    /**
     * Import validated file
     */
    function importFile() {
        if (!currentUploadId) {
            bootbox.alert('No file selected for import.');
            return;
        }

        var skipDuplicates = document.getElementById('skip-duplicates').checked;

        bootbox.confirm({
            message: 'Are you sure you want to import this file? This will add ' +
                    (validationData.data.length) + ' records to the database.',
            buttons: {
                confirm: {
                    label: 'Yes, Import',
                    className: 'btn-success'
                },
                cancel: {
                    label: 'Cancel',
                    className: 'btn-secondary'
                }
            },
            callback: function(result) {
                if (result) {
                    performImport(currentUploadId, skipDuplicates);
                }
            }
        });
    }

    /**
     * Perform the import
     */
    function performImport(uploadId, skipDuplicates) {
        showProgress('Importing data...', 50);

        $.ajax({
            url: 'data-upload.php',
            type: 'POST',
            data: {
                mode: 'importfile',
                upload_id: uploadId,
                skip_duplicates: skipDuplicates ? 1 : 0
            },
            success: function(response) {
                try {
                    var result = JSON.parse(response);
                    hideProgress();

                    if (result.error_code === 0) {
                        var message = result.message;
                        if (result.import && result.import.errors && result.import.errors.length > 0) {
                            message += '<br><br><strong>Errors encountered:</strong><ul>';
                            result.import.errors.forEach(function(error) {
                                message += '<li>' + error + '</li>';
                            });
                            message += '</ul>';
                        }

                        bootbox.alert(message, function() {
                            resetUpload();
                            loadUploadHistory();
                        });
                    } else {
                        var errorMsg = 'Import failed: ' + result.message;
                        if (result.import && result.import.errors) {
                            errorMsg += '<br><br><strong>Errors:</strong><ul>';
                            result.import.errors.forEach(function(error) {
                                errorMsg += '<li>' + error + '</li>';
                            });
                            errorMsg += '</ul>';
                        }
                        bootbox.alert(errorMsg);
                    }
                } catch (e) {
                    hideProgress();
                    bootbox.alert('Error processing import response.');
                }
            },
            error: function() {
                hideProgress();
                bootbox.alert('Import failed due to network error.');
            }
        });
    }

    /**
     * Load upload history
     */
    function loadUploadHistory(page) {
        page = page || 1;

        var filters = {
            mode: 'getUploadHistory',
            pno: page
        };

        // Apply filters
        var statusFilter = document.getElementById('filter-status');
        var startDateFilter = document.getElementById('filter-start-date');
        var endDateFilter = document.getElementById('filter-end-date');

        if (statusFilter && statusFilter.value) {
            filters.status = statusFilter.value;
        }
        if (startDateFilter && startDateFilter.value) {
            filters.start_date = startDateFilter.value;
        }
        if (endDateFilter && endDateFilter.value) {
            filters.end_date = endDateFilter.value;
        }

        $.ajax({
            url: 'data-upload.php',
            type: 'POST',
            data: filters,
            success: function(response) {
                try {
                    var result = JSON.parse(response);
                    var tbody = document.getElementById('upload-history-tbody');
                    if (!tbody) return;

                    if (result[0] === 0 && result[1].list) {
                        var records = result[1].list;
                        var html = '';

                        if (records.length === 0) {
                            html = '<tr><td colspan="8" class="text-center">No upload history found.</td></tr>';
                        } else {
                            records.forEach(function(rec) {
                                var statusClass = '';
                                switch(rec.status) {
                                    case 'Imported': statusClass = 'badge-success'; break;
                                    case 'Validated': statusClass = 'badge-info'; break;
                                    case 'Failed': statusClass = 'badge-danger'; break;
                                    default: statusClass = 'badge-warning';
                                }

                                html += '<tr>';
                                html += '<td>' + rec.file_name + '</td>';
                                html += '<td>' + formatDate(rec.upload_date) + '</td>';
                                html += '<td>' + formatDateTime(rec.uploaded_at) + '</td>';
                                html += '<td><span class="badge ' + statusClass + '">' + rec.status + '</span></td>';
                                html += '<td>' + (rec.records_count || 0) + '</td>';
                                html += '<td>' + (rec.file_size / 1024).toFixed(2) + ' KB</td>';
                                html += '<td>' + (rec.first_name + ' ' + rec.last_name) + '</td>';
                                html += '<td>';
                                if (rec.status === 'Failed' && rec.validation_errors) {
                                    html += '<button class="btn btn-sm btn-warning" onclick="dataUploadFuncs.showErrors(\'' + rec.validation_errors + '\')">View Errors</button> ';
                                }
                                html += '<button class="btn btn-sm btn-danger" onclick="dataUploadFuncs.deleteUpload(' + rec.upload_id + ')">Delete</button>';
                                html += '</td>';
                                html += '</tr>';
                            });
                        }

                        tbody.innerHTML = html;
                    }
                } catch (e) {
                    console.error('Error loading upload history:', e);
                }
            },
            error: function() {
                console.error('Failed to load upload history');
            }
        });
    }

    /**
     * Delete upload record
     */
    function deleteUpload(uploadId) {
        bootbox.confirm({
            message: 'Are you sure you want to delete this upload record?',
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
                    $.ajax({
                        url: 'data-upload.php',
                        type: 'POST',
                        data: {
                            mode: 'deleteupload',
                            recordid: uploadId
                        },
                        success: function(response) {
                            try {
                                var result = JSON.parse(response);
                                if (result.error_code === 0) {
                                    bootbox.alert('Upload record deleted successfully.');
                                    loadUploadHistory();
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
            }
        });
    }

    /**
     * Show validation errors
     */
    function showErrors(errorsJson) {
        try {
            var errors = JSON.parse(errorsJson);
            var html = '<ul class="error-list">';
            errors.forEach(function(error) {
                html += '<li>' + error + '</li>';
            });
            html += '</ul>';

            document.getElementById('error-details-content').innerHTML = html;
            $('#error-details-modal').modal('show');
        } catch (e) {
            bootbox.alert('Error displaying errors.');
        }
    }

    /**
     * Apply filters and reload history
     */
    function applyFilters() {
        loadUploadHistory(1);
    }

    /**
     * Refresh history
     */
    function refreshHistory() {
        loadUploadHistory(1);
    }

    /**
     * Reset upload form
     */
    function resetUpload() {
        currentUploadId = null;
        validationData = null;
        document.getElementById('file-input').value = '';
        document.getElementById('validation-results').innerHTML = '';
        document.getElementById('import-actions').style.display = 'none';
        hideProgress();
    }

    /**
     * Show progress bar
     */
    function showProgress(message, percentage) {
        var progressSection = document.getElementById('progress-section');
        var progressBar = document.getElementById('progress-bar');
        var progressMessage = document.getElementById('progress-message');

        if (progressSection) {
            progressSection.style.display = 'block';
            progressBar.style.width = percentage + '%';
            progressBar.setAttribute('aria-valuenow', percentage);
            progressBar.textContent = percentage + '%';
            progressMessage.textContent = message;
        }
    }

    /**
     * Hide progress bar
     */
    function hideProgress() {
        var progressSection = document.getElementById('progress-section');
        if (progressSection) {
            progressSection.style.display = 'none';
        }
    }

    /**
     * Format date
     */
    function formatDate(dateStr) {
        if (!dateStr) return '';
        var date = new Date(dateStr);
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return ('0' + date.getDate()).slice(-2) + '-' + months[date.getMonth()] + '-' + date.getFullYear();
    }

    /**
     * Format date time
     */
    function formatDateTime(dateStr) {
        if (!dateStr) return '';
        var date = new Date(dateStr);
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return ('0' + date.getDate()).slice(-2) + '-' + months[date.getMonth()] + '-' + date.getFullYear() +
               ' ' + ('0' + date.getHours()).slice(-2) + ':' + ('0' + date.getMinutes()).slice(-2);
    }

    // Public API
    return {
        init: init,
        deleteUpload: deleteUpload,
        showErrors: showErrors,
        applyFilters: applyFilters,
        refreshHistory: refreshHistory
    };
})();

// Initialize on document ready
$(document).ready(function() {
    dataUploadFuncs.init();
});
