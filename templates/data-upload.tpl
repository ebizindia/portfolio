<?php
if($this->body_template_data['mode'] == 'getUploadHistory'){

	$mode_index=$this->body_template_data['mode'];
	$this->body_template_data[$mode_index]['records'];

	if($this->body_template_data[$mode_index]['records_count']==0){
		echo "<tr><td colspan='8' class='text-danger' align='center'>No upload history found.</td></tr>";
	}else{
		for($i_ul=0; $i_ul<$this->body_template_data[$mode_index]['records_count']; $i_ul++){
			$rec = $this->body_template_data[$mode_index]['records'][$i_ul];

			$status_class = '';
			switch($rec['status']) {
				case 'Imported': $status_class = 'badge-success'; break;
				case 'Validated': $status_class = 'badge-info'; break;
				case 'Failed': $status_class = 'badge-danger'; break;
				default: $status_class = 'badge-warning';
			}

			echo "<tr>";
			echo "<td>" . htmlspecialchars($rec['file_name']) . "</td>";
			echo "<td>" . date('d-M-Y', strtotime($rec['upload_date'])) . "</td>";
			echo "<td>" . date('d-M-Y H:i', strtotime($rec['uploaded_at'])) . "</td>";
			echo "<td><span class='badge {$status_class}'>" . htmlspecialchars($rec['status']) . "</span></td>";
			echo "<td>" . ($rec['records_count'] ?? 0) . "</td>";
			echo "<td>" . round($rec['file_size'] / 1024, 2) . " KB</td>";
			echo "<td>" . htmlspecialchars($rec['first_name'] . ' ' . $rec['last_name']) . "</td>";
			echo "<td>";
			if($rec['status'] == 'Failed' && !empty($rec['validation_errors'])) {
				echo "<button class='btn btn-sm btn-warning' onclick='dataUploadFuncs.showErrors(" . $rec['upload_id'] . ")'>View Errors</button> ";
			}
			echo "<button class='btn btn-sm btn-danger' onclick='dataUploadFuncs.deleteUpload(" . $rec['upload_id'] . ")'>Delete</button>";
			echo "</td>";
			echo "</tr>";
		}

		if($this->body_template_data[$mode_index]['pagination_html']!=''){
			echo "<tr><td colspan='8' class='pagination-row'>\n";
				echo $this->body_template_data[$mode_index]['pagination_html'];
			echo "</td></tr>\n";
		}
	}
}else{
?>
<style>
.upload-area {
    border: 3px dashed #ccc;
    border-radius: 10px;
    padding: 50px;
    text-align: center;
    background: #f9f9f9;
    cursor: pointer;
    transition: all 0.3s;
}
.upload-area:hover, .upload-area.drag-over {
    border-color: #007bff;
    background: #e9f5ff;
}
.upload-area i {
    font-size: 48px;
    color: #007bff;
    margin-bottom: 20px;
}
.validation-box {
    margin-top: 20px;
    padding: 15px;
    border-radius: 5px;
}
.validation-success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}
.validation-error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}
.validation-warning {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
}
.error-list, .warning-list {
    list-style: none;
    padding-left: 0;
    margin: 10px 0 0 0;
}
.error-list li, .warning-list li {
    padding: 5px 0;
}
.progress-section {
    display: none;
    margin-top: 20px;
}
</style>

<div class="row">
    <div class="col-12 mt-3 mb-2">
		<div class="card">
            <div class="card-body">
                <div class="card-header-heading">
                    <h4>Upload Transaction Data</h4>
                    <p class="text-muted">Upload Excel (.xlsx, .xls) or CSV files containing transaction data</p>
                </div>

                <?php if($this->body_template_data['can_upload']===true){ ?>

                <!-- Upload Section -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="upload-area" id="upload-area">
                            <i class="fa fa-cloud-upload"></i>
                            <h5>Drag & Drop your file here</h5>
                            <p>or click to browse</p>
                            <p class="text-muted small">Supported formats: Excel (.xlsx, .xls), CSV | Max size: 10MB</p>
                            <input type="file" id="file-input" accept=".xlsx,.xls,.csv" style="display: none;">
                        </div>
                    </div>
                </div>

                <!-- Progress Section -->
                <div class="progress-section" id="progress-section">
                    <div class="row">
                        <div class="col-12">
                            <h5 id="progress-title">Uploading...</h5>
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated"
                                     id="progress-bar" role="progressbar"
                                     style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                            </div>
                            <p id="progress-message" class="mt-2"></p>
                        </div>
                    </div>
                </div>

                <!-- Validation Results -->
                <div id="validation-results"></div>

                <!-- Import Actions -->
                <div id="import-actions" style="display: none;" class="mt-3">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="skip-duplicates" checked>
                                <label class="form-check-label" for="skip-duplicates">
                                    Skip duplicate transactions
                                </label>
                            </div>
                            <button class="btn btn-success" id="import-btn">
                                <i class="fa fa-download"></i> Import Data
                            </button>
                            <button class="btn btn-secondary" id="cancel-btn">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>

                <?php } else { ?>
                <div class="alert alert-warning">
                    You do not have permission to upload files. Please contact administrator.
                </div>
                <?php } ?>

            </div>
        </div>
    </div>
</div>

<!-- Upload History -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="card-header-heading">
                    <div class="row">
                        <div class="col-8">
                            <h4>Upload History</h4>
                        </div>
                        <div class="col-4 text-right">
                            <button class="btn btn-sm btn-primary" onclick="dataUploadFuncs.refreshHistory()">
                                <i class="fa fa-refresh"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>Status</label>
                        <select class="form-control" id="filter-status">
                            <option value="">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Validated">Validated</option>
                            <option value="Imported">Imported</option>
                            <option value="Failed">Failed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Start Date</label>
                        <input type="date" class="form-control" id="filter-start-date">
                    </div>
                    <div class="col-md-3">
                        <label>End Date</label>
                        <input type="date" class="form-control" id="filter-end-date">
                    </div>
                    <div class="col-md-3">
                        <label>&nbsp;</label><br>
                        <button class="btn btn-primary" onclick="dataUploadFuncs.applyFilters()">Apply Filters</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>Upload Date</th>
                                <th>Uploaded At</th>
                                <th>Status</th>
                                <th>Records</th>
                                <th>File Size</th>
                                <th>Uploaded By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="upload-history-tbody">
                            <tr>
                                <td colspan="8" class="text-center">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sample Data Format Modal -->
<div class="modal fade" id="sample-format-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sample Data Format</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h6>Required Columns:</h6>
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Column Name</th>
                            <th>Description</th>
                            <th>Format</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>transaction_date</td>
                            <td>Date of transaction</td>
                            <td>YYYY-MM-DD or DD-MM-YYYY</td>
                        </tr>
                        <tr>
                            <td>portfolio_name</td>
                            <td>Name of the portfolio</td>
                            <td>Text (must exist in system)</td>
                        </tr>
                        <tr>
                            <td>stock_code</td>
                            <td>Stock symbol/code</td>
                            <td>Text (e.g., RELIANCE, TCS)</td>
                        </tr>
                        <tr>
                            <td>stock_name</td>
                            <td>Full name of stock</td>
                            <td>Text</td>
                        </tr>
                        <tr>
                            <td>transaction_type</td>
                            <td>Type of transaction</td>
                            <td>BUY or SELL</td>
                        </tr>
                        <tr>
                            <td>quantity</td>
                            <td>Number of shares</td>
                            <td>Numeric (positive)</td>
                        </tr>
                        <tr>
                            <td>price</td>
                            <td>Price per share</td>
                            <td>Numeric (positive)</td>
                        </tr>
                    </tbody>
                </table>
                <h6 class="mt-3">Optional Columns:</h6>
                <ul>
                    <li><strong>instrument_type:</strong> Type of instrument (default: Spot)</li>
                    <li><strong>expiry_date:</strong> For derivatives</li>
                    <li><strong>strike_price:</strong> For options</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Error Details Modal -->
<div class="modal fade" id="error-details-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Validation Errors</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="error-details-content"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="js/data-upload.js"></script>

<?php } ?>
