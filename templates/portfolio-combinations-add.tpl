<div class="card">
	                <div class="card-body">
	                    <div class="card-header-heading">
	                    <div class="row">
	                        <div class="col-6"><h4 id="panel-heading-text" class="pull-left row">Add Portfolio Combination&nbsp;<img src="images/info.png" class="info-button" alt="Info"></h4></div>

	                        <div class="col-6 text-right">
	        <a href="portfolio-combinations.php#" class="btn btn-danger rounded record-list-show-button back-to-list-button row" id="back-to-list-button">
	            <img src="images/left-arrow.png" class="custom-button" alt="Left"> Back To List </a>

	   <a href="portfolio-combinations.php#" class="btn btn-danger record-list-show-button back-to-list-button row mobile-bck-to-list"  id="back-to-list-button"><img src="images/left-arrow.png" class="custom-button" alt="Left"> </a>

	                        </div>
	                    </div>
	                </div>
	                    <div class="row">
	                        <div class="col-md-12 col-sm-12 col-xs-12 col-lg-12">
	                        <form class="form-horizontal frmWidth1080" role="form" name='addrecform' id="addrecform" action='portfolio-combinations.php' method='post' onsubmit="return combinationfuncs.saveRecDetails(this);" target="form_post_submit_target_window"  data-mode="add-rec" enctype="multipart/form-data" novalidate  >
	                        <input type='hidden' name='mode' id='add_edit_mode' value='createrec' />
	                        <input type='hidden' name='recordid' id='add_edit_recordid' value='' />
							<input type='hidden' name='portfolio_ids' id='portfolio_ids' value='' />

	                        <div class="alert alert-danger d-none">
	                            <strong><i class="icon-remove"></i></strong>
	                            <span class="alert-message"></span>
	                        </div>
	                        <div class="alert alert-success d-none">
	                            <strong><i class="icon-ok"></i></strong>
	                            <span class="alert-message"></span>
	                        </div>

	                        <!-- Combination details -->
	                        <div class="bd-callout bd-callout-info">
								<div class="form-group row">
									<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_form_field_combination_name"> Combination Name <span class="mandatory">*</span></label>
									<div class="col-xs-12 col-sm-6 col-lg-4">
										<input type="text" id="add_form_field_combination_name" placeholder="Enter combination name" class="form-control" name='combination_name' value="" maxlength='100' autocomplete="off" />
										<div class="form-elem-guide-text default-box">
										  <span class="">Cannot be more than 100 chars long.</span>
										</div>
									</div>
								</div>

								<div class="form-group row">
									<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_form_field_description"> Description </label>
									<div class="col-xs-12 col-sm-6 col-lg-6">
										<textarea id="add_form_field_description" placeholder="Enter combination description (optional)" class="form-control" name='description' rows="3" maxlength="500"></textarea>
										<div class="form-elem-guide-text default-box">
										  <span class="">Optional. Maximum 500 characters.</span>
										</div>
									</div>
								</div>

								<div class="form-group row">
									<label class="control-label col-xs-12 col-sm-6 col-lg-2"> Portfolios <span class="mandatory">*</span></label>
									<div class="col-xs-12 col-sm-6 col-lg-6">
										<div class="portfolio-selector" id="portfolio-selector">
											<div class="text-muted">Loading portfolios...</div>
										</div>
										<div class="form-elem-guide-text default-box">
										  <span class="">Select one or more portfolios to include in this combination.</span>
										</div>
									</div>
								</div>

							</div>
							<div class="clearfix"></div>
							<div class="form-actions form-group">
								<div class="col-md-12 col-sm-12 col-xs-12 text-center">
									<center>
										<button class="btn btn-success btn-pill" type="submit" id="record-save-button" style="margin-right: 10px;">
											<img src="images/check.png" class="check-button" alt="Check"> <span>Add Combination</span>
										</button>
										<a href="portfolio-combinations.php#" class="btn btn-danger d-none" type="button" id="record-add-cancel-button" data-back-to="" onclick="combinationfuncs.closeAddForm();">
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
