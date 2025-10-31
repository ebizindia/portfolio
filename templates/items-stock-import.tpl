<style>
.csv_btn{
  background:#d4edff; 
  color:#212529;
  border: 1px solid #53a4df;
}
.csv_btn:hover{
  background: #bfe4ff !important;
  transition:.3s all ease-in-out;
}
.exl_btn{
  transition:.3s all ease-in-out;
}
.exl_btn:hover{
  background: #baeaf2 !important;
  transition:.3s all ease-in-out;
}
</style>
<div class="card">
    <div class="card-body">
        <div class="card-header-heading">
            <div class="row">
                <div class="col-6">
                    <h4 id="panel-heading-text" class="pull-left row">
                        Import Items Stock from CSV
                    </h4>
                </div>
                <div class="col-6 text-right">
                    <a href="items-stock.php#" class="btn btn-danger rounded record-list-show-button back-to-list-button row" id="back-to-list-button">
                        <img src="images/left-arrow.png" class="custom-button" alt="Left"> Back To List
                    </a>
                    <a href="items-stock.php#" class="btn btn-danger record-list-show-button back-to-list-button row mobile-bck-to-list">
                        <img src="images/left-arrow.png" class="custom-button" alt="Left">
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12 col-lg-12">
                <div class="import-section">
                    <form class="form-horizontal" role="form" name="importform" id="importform"
                          onsubmit="return itemsStock.importCSV(this);"
                          enctype="multipart/form-data"
                          novalidate>

                        <div class="alert alert-warning mt-2" role="alert" id="msgFrm">
                            <p style="margin-bottom: 0">All fields marked with an asterisk (<span class="required">*</span>) are required.</p>
                        </div>
                        <div class="alert alert-danger d-none">
                            <strong><i class="icon-remove"></i></strong>
                            <span class="alert-message"></span>
                        </div>
                        <div class="alert alert-success d-none">
                            <strong><i class="icon-ok"></i></strong>
                            <span class="alert-message"></span>
                        </div>

                        <div class="bd-callout bd-callout-info">
                            <div class="form-group row">
                                <label class="control-label col-xs-12 col-sm-6 col-lg-2" for="import_warehouse_id">
                                    Warehouse <span class="mandatory">*</span>
                                </label>
                                <div class="col-xs-12 col-sm-6 col-lg-4">
                                    <select id="import_warehouse_id" name="warehouse_id" class="form-control" required>
                                        <option value="">-- Select Warehouse --</option>
                                        <?php foreach($this->body_template_data['warehouses'] as $warehouse) { ?>
                                        <option value="<?php echo $warehouse['id']; ?>"><?php echo \eBizIndia\_esc($warehouse['name']); ?></option>
                                        <?php } ?>
                                    </select>
                                    <small class="text-muted">All existing stock data for the selected warehouse will be replaced.</small>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="control-label col-xs-12 col-sm-6 col-lg-2" for="csv_file">
                                    Import File <span class="mandatory">*</span>
                                </label>
                                <div class="col-xs-12 col-sm-6 col-lg-4">
                                    <input type="file" id="csv_file" name="csv_file"
                                           class="form-control" accept=".csv,.xlsx,.xls" required>
                                    <small class="text-muted">CSV (max <?php echo \eBizIndia\_esc($this->body_template_data['max_allowed_sizes']['CSV']['disp']); ?>), Excel (max <?php echo \eBizIndia\_esc($this->body_template_data['max_allowed_sizes']['EXCEL']['disp']); ?>) files allowed</small>
                                </div>
                            </div>
                        </div>

                        <div class="csv-format-info">
                            <h6><strong>Required File Format (CSV/Excel):</strong></h6>
                            <p>Your file must contain the following columns (multiple header names accepted):</p>
                            <ul class="mb-2">
                                <li><strong>Item Name</strong> - Name of the item<br>
                                    <small class="text-muted">Accepted headers: <?php echo \eBizIndia\_esc('"'.implode('", "',$this->body_template_data['csv_headers']['item_name']).'"' ); ?> </small>
                                </li>

                                <li><strong>Quantity</strong> - Numeric quantity (e.g., 150.500)<br>
                                    <small class="text-muted">Accepted headers: <?php echo \eBizIndia\_esc('"'.implode('", "',$this->body_template_data['csv_headers']['quantity']).'"' ); ?> </small>
                                </li>
                                <li><strong>Unit</strong> - Unit of measurement (e.g., KG, PCS, LTR)<br>
                                    <small class="text-muted">Accepted headers: <?php echo \eBizIndia\_esc('"'.implode('", "',$this->body_template_data['csv_headers']['unit']).'"' ); ?> </small>
                                </li>
                                <li><strong>Expiry</strong> - Month-Year format (e.g., Sep-25, Jan-26, 03-26)<br>
                                    <small class="text-muted">Accepted headers: <?php echo \eBizIndia\_esc('"'.implode('", "',$this->body_template_data['csv_headers']['expiry']).'"' ); ?> </small>
                                </li>
                                <li><strong>As On Date</strong> - Date format (YYYY-MM-DD or DD-MM-YYYY)<br>
                                    <small class="text-muted">Accepted headers: <?php echo \eBizIndia\_esc('"'.implode('", "',$this->body_template_data['csv_headers']['as_on_date']).'"' ); ?> </small>
                                </li>
                            </ul>
						</div>
						<div class="csv-format-info mt-3">
                            <div>
                                <strong>Excel Files:</strong>
                                <ul class="mb-0 mt-1">
                                    <li>Use the first worksheet for your data</li>
                                    <li>Excel date cells will be automatically converted</li>
                                    <li>Empty rows will be skipped automatically</li>
                                </ul>
                            </div>
                        </div>
						<div class="csv-format-info mt-3">
                            <div>
                                <strong>File Size Recommendations:</strong>
                                <ul class="mb-0 mt-1">
                                    <li><strong>CSV files:</strong> Up to <?php echo \eBizIndia\_esc($this->body_template_data['max_allowed_sizes']['CSV']['disp']); ?>, faster processing</li>
                                    <li><strong>Excel files:</strong> Up to <?php echo \eBizIndia\_esc($this->body_template_data['max_allowed_sizes']['EXCEL']['disp']); ?>, slower processing due to formatting</li>
                                    <li><strong>Large datasets:</strong> Use CSV format for files over 5MB</li>
                                </ul>
                            </div>
                        </div>
						<div class="csv-format-info mt-3">
                            <div>
                                <div class="mb-3"><strong>Download Templates:</strong></div>
                                <a href="items-stock.php?mode=downloadTemplate&format=csv" class="btn btn-sm rounded mr-2 csv_btn" target="_blank">
                                    <i class="fa fa-download"></i> <strong>CSV Template</strong>
                                </a>
                                <a href="items-stock.php?mode=downloadTemplate&format=excel" class="btn btn-sm rounded csv_btn" target="_blank">
                                    <i class="fa fa-download"></i> <strong>Excel Template</strong>
                                </a>
                            </div>
                        </div>

                        <div class="clearfix"></div>
                        <div class="form-actions form-group text-center">
                            <button class="btn btn-success btn-pill" type="submit" id="import-button" style="margin-right: 10px;">
                                <img src="images/check.png" class="custom-button-small" alt="Upload">
                                <span>Import Items Stock</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>