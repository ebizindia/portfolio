<style>
.ui-datepicker-trigger img{
position: relative;
top: -5px;
}
#add_detailed_notes +.ck-editor .ck-editor__editable:not(.ck-editor__nested-editable) { 
    min-height: 150px;
}
</style>
<div class="card">
    <div class="card-body">
        <div class="card-header-heading">
            <div class="row">
                <div class="col-6">
                    <h4 id="panel-heading-text" class="pull-left row">
                        Add Visit Report

                    </h4>
                </div>
                <div class="col-6 text-right">
                    <a href="visit-reports.php#" class="btn btn-danger rounded record-list-show-button back-to-list-button row" id="back-to-list-button">
                        <img src="images/left-arrow.png" class="custom-button" alt="Left"> Back To List
                    </a>
                    <a href="visit-reports.php#" class="btn btn-danger record-list-show-button back-to-list-button row mobile-bck-to-list">
                        <img src="images/left-arrow.png" class="custom-button" alt="Left">
                    </a>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12 col-lg-12">
                <form class="form-horizontal frmWidth1080" role="form" name="addrecform" id="addrecform"
                      action="visit-reports.php" method="post"
                      onsubmit="return visitReports.saveRecDetails(this);"
                      target="form_post_submit_target_window"
                      data-mode="add-rec"
                      enctype="multipart/form-data"
                      novalidate>
                    <input type="hidden" name="mode" id="add_edit_mode" value="createrec" />
                    <input type="hidden" name="recordid" id="add_edit_recordid" value="" />

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
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_customer_id">
								Customer <span class="mandatory">*</span>
							</label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<select class="form-control" id="add_customer_id" name="customer_id" onchange="visitReports.loadCustomerContacts(this.value);">
									<option value="">-- Select Customer --</option>
									<?php foreach($this->body_template_data['customers'] as $customer){ ?>
									<option value="<?php echo $customer['id'];?>"><?php echo \eBizIndia\_esc($customer['name']);?></option>
									<?php } ?>
								</select>
							</div>
						</div>

                        <!-- NEW: Department Field -->
                        <div class="form-group row">
                            <label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_department">
                                Department <span class="mandatory">*</span>
                            </label>
                            <div class="col-xs-12 col-sm-6 col-lg-4">
                                <select class="form-control" id="add_department" name="department" required>
                                    <option value="">-- Select Department --</option>
                                    <?php foreach($this->body_template_data['departments'] as $value => $label){ ?>
                                    <option value="<?php echo $value;?>"><?php echo \eBizIndia\_esc($label);?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <!-- NEW: Type Field -->
                        <div class="form-group row">
                            <label class="control-label col-xs-12 col-sm-6 col-lg-2">
                                Type <span class="mandatory">*</span>
                            </label>
                            <div class="col-xs-12 col-sm-6 col-lg-4">
                                <div class="form-check-inline mr-3">
                                    <?php foreach($this->body_template_data['visit_types'] as $value => $label){ ?>
                                    <label class="form-check-label mr-3">
                                        <input type="radio" class="form-check-input" name="type" value="<?php echo $value;?>" required>
                                        <?php echo \eBizIndia\_esc($label);?>
                                    </label>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>

						<div class="form-group row ">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_visit_date">
								Visit Date <span class="mandatory">*</span>
							</label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<input type="text" id="add_visit_date" class="form-control datepicker" name="visit_date" value="" required readonly>
							</div>
						</div>

						<div class="form-group row">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_meeting_title">
								Meeting Title <span class="mandatory">*</span>
							</label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<input type="text" id="add_meeting_title" placeholder="Meeting Title"
									   class="form-control" name="meeting_title" value="" maxlength="150" required>
							</div>
						</div>

						<div class="form-group row">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_detailed_notes">
								Notes <span class="mandatory">*</span>
							</label>
							<div class="col-xs-12 col-sm-6 col-lg-6">
						<textarea id="add_detailed_notes" placeholder="Enter detailed notes about the visit, discussions, outcomes, and next steps..." class="form-control" name="detailed_notes" rows="4" required></textarea>
								<small class="form-text text-muted">Provide comprehensive details about the visit including key discussion points and outcomes.</small>
							</div>
						</div>

						<div class="form-group row">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_attachment">
								Attachment
							</label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<input type="file" id="add_attachment" class="form-control" name="attachment"
									   accept=".doc,.docx,.xls,.xlsx,.pdf">
								<small class="form-text text-muted">Allowed: DOC, DOCX, XLS, XLSX, PDF (Max: 10MB)</small>
							</div>
						</div>

						<div class="form-group row">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2">
								People Met <span class="mandatory">*</span>
							</label>
							<div class="col-xs-12 col-sm-6 col-lg-10">
								<div class="alert alert-info mb-3" role="alert">
									<small><i class="fa fa-info-circle"></i> At least one contact is required. You can select from existing customer contacts or add new ones.</small>
								</div>

								<div id="existing-contacts-section" class="mb-3" style="display: none;">
									<label class="font-weight-bold">Select from existing contacts:</label>
									<select id="existing-contacts-select" class="form-control mb-2" multiple size="5"  style="max-width: 479px;"  >
										<!-- Options will be populated dynamically -->
									</select>
									<button type="button" class="btn btn-info btn-sm rounded" id="add-selected-contacts-btn">
										<i class="fa fa-plus"></i> Add Selected Contacts To List
									</button>
								</div>

								<div class="table-responsive">
									<table id="contacts-table" class="table table-bordered table-sm">
										<thead class="thead-lightblue">
										<tr>
											<th>Name <span class="text-danger">*</span></th>
											<th>Department</th>
											<th>Designation</th>
											<th>Email</th>
											<th>Phone</th>
											<th style="width:2%;">Actions</th>
										</tr>
										</thead>
										<tbody>
										<!-- Contact rows will be dynamically added here -->
										</tbody>
									</table>
								</div>
								<button type="button" class="btn btn-primary d-inline-flex align-items-center rounded" id="add-new-contact-btn">
								<svg class="icon" viewBox="0 0 16 16" width="16" height="16" fill="currentColor" aria-hidden="true">
									<path d="M8 1a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2H9v5a1 1 0 1 1-2 0V9H2a1 1 0 1 1 0-2h5V2a1 1 0 0 1 1-1z"></path>
									</svg>
									<span>Add New Contact</span>
								</button>
							</div>
						</div>
					</div>

                    <div class="clearfix"></div>
                    <div class="form-actions form-group text-center">
                        <button class="btn btn-success btn-pill" type="submit" id="record-save-button rounded" style="margin-right: 10px;">
                            <img src="images/check.png" class="check-button" alt="Check">
                            <span>Add Visit Report</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>