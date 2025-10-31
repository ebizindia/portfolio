<div class="card">
	                <div class="card-body">
	                    <div class="card-header-heading">
	                    <div class="row">
	                        <div class="col-6"><h4 id="panel-heading-text" class="pull-left row">Add Portfolio&nbsp;<img src="images/info.png" class="info-button" alt="Info"></h4></div>

	                        <div class="col-6 text-right">
	        <a href="portfolios.php#" class="btn btn-danger rounded record-list-show-button back-to-list-button row" id="back-to-list-button">
	            <img src="images/left-arrow.png" class="custom-button" alt="Left"> Back To List </a>

	   <a href="portfolios.php#" class="btn btn-danger record-list-show-button back-to-list-button row mobile-bck-to-list"  id="back-to-list-button"><img src="images/left-arrow.png" class="custom-button" alt="Left"> </a>

	                        </div>
	                    </div>
	                </div>
	                    <div class="row">
	                        <div class="col-md-12 col-sm-12 col-xs-12 col-lg-12">
	                        <form class="form-horizontal frmWidth1080" role="form" name='addrecform' id="addrecform" action='portfolios.php' method='post' onsubmit="return portfoliofuncs.saveRecDetails(this);" target="form_post_submit_target_window"  data-mode="add-rec" enctype="multipart/form-data" novalidate  >
	                        <input type='hidden' name='mode' id='add_edit_mode' value='createrec' />
	                        <input type='hidden' name='recordid' id='add_edit_recordid' value='' />

	                        <div class="alert alert-danger d-none">
	                            <strong><i class="icon-remove"></i></strong>
	                            <span class="alert-message"></span>
	                        </div>
	                        <div class="alert alert-success d-none">
	                            <strong><i class="icon-ok"></i></strong>
	                            <span class="alert-message"></span>
	                        </div>

	                        <!-- Portfolio details -->
	                        <div class="bd-callout bd-callout-info">
								<div class="form-group row">
									<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_form_field_portfolio_name"> Portfolio Name <span class="mandatory">*</span></label>
									<div class="col-xs-12 col-sm-6 col-lg-4">
										<input type="text" id="add_form_field_portfolio_name" placeholder="Enter portfolio name" class="form-control" name='portfolio_name' value="" maxlength='100' autocomplete="off" />
										<div class="form-elem-guide-text default-box">
										  <span class="">Cannot be more than 100 chars long.</span>
										</div>
									</div>
								</div>

								<div class="form-group row">
									<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_form_field_portfolio_type"> Portfolio Type <span class="mandatory">*</span></label>
									<div class="col-xs-12 col-sm-6 col-lg-4">
										<select id="add_form_field_portfolio_type" class="form-control" name='portfolio_type'>
											<option value="">-- Select Type --</option>
											<option value="Own">Own</option>
											<option value="Portfolio Manager">Portfolio Manager</option>
											<option value="Unlisted & AIF">Unlisted & AIF</option>
										</select>
									</div>
								</div>

								<div class="form-group row">
									<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_form_field_description"> Description </label>
									<div class="col-xs-12 col-sm-6 col-lg-6">
										<textarea id="add_form_field_description" placeholder="Enter portfolio description (optional)" class="form-control" name='description' rows="3" maxlength="500"></textarea>
										<div class="form-elem-guide-text default-box">
										  <span class="">Optional. Maximum 500 characters.</span>
										</div>
									</div>
								</div>

								<!-- Status field only for the edit screen -->
								<div class="form-group row editonly d-none">
									<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="edit_form_field_status_active"> Status </label>
									<div class="form-check form-check-inline pl-3">
										<label class="form-check-label">
											<input id="edit_form_field_status_active" class="form-check-input" type="radio" name="status" value='Active' />
											Active
										</label>
									</div>
									<div class="form-check form-check-inline">
										<label class="form-check-label">
											<input id='edit_form_field_status_inactive' name="status" class="form-check-input" type="radio" value='Inactive' />
											Inactive
										</label>
									</div>
								</div>
							</div>
							<div class="clearfix"></div>
							<div class="form-actions form-group">
								<div class="col-md-12 col-sm-12 col-xs-12 text-center">
									<center>
										<button class="btn btn-success btn-pill" type="submit" id="record-save-button" style="margin-right: 10px;">
											<img src="images/check.png" class="check-button" alt="Check"> <span>Add Portfolio</span>
										</button>
										<a href="portfolios.php#" class="btn btn-danger d-none" type="button" id="record-add-cancel-button" data-back-to="" onclick="portfoliofuncs.closeAddForm();">
											<img src="images/cancel-black.png" class="custom-button-extra-small" alt="cancel">
											Cancel
										</a>
									</center>
								</div>
								<div class="col-md-4 col-sm-2 hidden-xs"></div>
							</div>
	                        </form>
	                        </div>
	                    </div>
	                </div>
	            </div>
