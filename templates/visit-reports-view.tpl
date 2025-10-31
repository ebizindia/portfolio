<div class="card">
    <div class="card-body">
        <div class="card-header-heading">
            <div class="row">
                <div class="col-6">
                    <h4 id="panel-heading-text" class="pull-left row">
                        Visit Report Details

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
						<label class="control-label col-xs-12 col-sm-6 col-lg-2">
							Customer:
						</label>
						<div class="col-xs-12 col-sm-6 col-lg-4">
							<span id="view_customer_name" class="form-control-plaintext"></span>
						</div>
					</div>

                    <!-- NEW: Department Display -->
                    <div class="form-group row">
                        <label class="control-label col-xs-12 col-sm-6 col-lg-2">
                            Department:
                        </label>
                        <div class="col-xs-12 col-sm-6 col-lg-4">
                            <span id="view_department" class="form-control-plaintext"></span>
                        </div>
                    </div>

                    <!-- NEW: Type Display -->
                    <div class="form-group row">
                        <label class="control-label col-xs-12 col-sm-6 col-lg-2">
                            Type:
                        </label>
                        <div class="col-xs-12 col-sm-6 col-lg-4">
                            <span id="view_type" class="form-control-plaintext"></span>
                        </div>
                    </div>

					<div class="form-group row">
						<label class="control-label col-xs-12 col-sm-6 col-lg-2">
							Visit Date:
						</label>
						<div class="col-xs-12 col-sm-6 col-lg-4">
							<span id="view_visit_date" class="form-control-plaintext"></span>
						</div>
					</div>

					<div class="form-group row">
						<label class="control-label col-xs-12 col-sm-6 col-lg-2">
							Meeting Title:
						</label>
						<div class="col-xs-12 col-sm-6 col-lg-4">
							<span id="view_meeting_title" class="form-control-plaintext"></span>
						</div>
					</div>

					<div class="form-group row">
						<label class="control-label col-xs-12 col-sm-6 col-lg-2">
							Detailed Notes:
						</label>
						<div class="col-xs-12 col-sm-6 col-lg-6">
							<div id="view_detailed_notes" class="form-control-plaintext border p-2 bg-light ck-content" style="min-height: 100px;">
								<!-- HTML content will be displayed here -->
							</div>
						</div>
					</div>

					<div class="form-group row" id="attachment-row" style="">
						<label class="control-label col-xs-12 col-sm-6 col-lg-2">
							Attachment:
						</label>
						<div class="col-xs-12 col-sm-6 col-lg-4">
							<a href="#" id="attachment-download-link" class="btn btn-info btn-sm rounded" target="doc"  >
								<i class="fa fa-download"></i> <span id="attachment-filename"></span>
							</a>
						</div>
					</div>

					<div class="form-group row">
						<label class="control-label col-xs-12 col-sm-6 col-lg-2">
							People Met:
						</label>
						<div class="col-xs-12 col-sm-6 col-lg-6">
							<div id="view_contacts_list" class="border p-2 bg-light view_contact_block">
								<!-- Contacts will be populated here -->
							</div>
						</div>
					</div>

					<div class="form-group row">
						<label class="control-label col-xs-12 col-sm-6 col-lg-2">
							Created By:
						</label>
						<div class="col-xs-12 col-sm-6 col-lg-4">
							<span id="view_created_by" class="form-control-plaintext"></span>
						</div>
					</div>

					<div class="form-group row">
						<label class="control-label col-xs-12 col-sm-6 col-lg-2">
							Created On:
						</label>
						<div class="col-xs-12 col-sm-6 col-lg-4">
							<span id="view_created_on" class="form-control-plaintext"></span>
						</div>
					</div>

					<!-- Admin Notes Section -->
				</div>	
				<div class="bd-callout bd-callout-info">
					<div class="form-group row" id="admin-notes-section" style="margin-bottom:0;">
						<label class="control-label col-xs-12 col-sm-6 col-lg-2">
							Admin Notes:
						</label>
						<div class="col-xs-12 col-sm-6 col-lg-6">
							<div id="admin-notes-view" class="border p-2 bg-light ck-content" style="min-height: 80px;">
								<!-- Admin notes HTML content will be displayed here -->
							</div>
							<div id="admin-notes-edit" style="display: none;">
								<form id="admin-notes-form" method="post" onsubmit="return visitReports.updateAdminNotes(this);" target="form_post_submit_target_window" class="frmWidth1080">
									<input type="hidden" name="mode" value="updateAdminNotes">
									<input type="hidden" name="recordid" id="admin_notes_recordid" value="">
									<textarea class="form-control mb-2" name="admin_notes" id="admin_notes_textarea" rows="4" placeholder="Enter admin notes..."></textarea>
									<button type="submit" class="btn btn-success rounded btn-sm" id="save-admin-notes-btn">
										<i class="fa fa-save"></i> Save Notes
									</button>
									<button type="button" class="btn btn-secondary rounded btn-sm ml-2" onclick="visitReports.cancelEditAdminNotes();">
										Cancel
									</button>									
								</form>
							</div>
							<div id="admin-notes-updated-info" style="display: none;">
								<span id="view_admin_notes_updated" class="form-control-plaintext small text-muted"></span>
							</div>
							<div id="admin-notes-buttons" style="margin-top: 15px;">
							<!-- Edit button will be shown for ADMINs -->
							</div>
						</div>                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>