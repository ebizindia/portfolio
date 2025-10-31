<div class="card">
    <div class="card-body">
        <div class="card-header-heading">
            <div class="row">
                <div class="col-6">
                    <h4 id="panel-heading-text" class="pull-left row">
                        Add Customer
                        <img src="images/info.png" class="info-button" alt="Info">
                    </h4>
                </div>
                <div class="col-6 text-right">
                    <a href="customers.php#" class="btn btn-danger rounded record-list-show-button back-to-list-button row" id="back-to-list-button">
                        <img src="images/left-arrow.png" class="custom-button" alt="Left"> Back To List 
                    </a>
                    <a href="customers.php#" class="btn btn-danger record-list-show-button back-to-list-button row mobile-bck-to-list">
                        <img src="images/left-arrow.png" class="custom-button" alt="Left"> 
                    </a>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12 col-lg-12">
                <form class="form-horizontal frmWidth1080" role="form" name="addrecform" id="addrecform" action="customers.php" method="post" 
				  onsubmit="return customers.saveRecDetails(this);"  target="form_post_submit_target_window"  data-mode="add-rec" 
				  enctype="multipart/form-data"   novalidate>
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
						 <div class="section-header">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2a7 7 0 017 7c0 4-4 7-7 13-3-6-7-9-7-13a7 7 0 017-7z"/></svg>
							Basic Information
						</div>
						 <div class="form-group row">

							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_comp_name">
								Customer Name <span class="mandatory">*</span>
							</label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<input type="text" id="add_comp_name" placeholder="Customer Name" 
									   class="form-control" name="name" value="" maxlength="255">
							</div>
						</div>

						<div class="form-group row">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_mem_cat_id">
								Customer Group <span class="mandatory">*</span>
							</label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<select class="form-control" id="add_mem_cat_id" name="customer_group_id">
									<option value="">-- Select Customer Group --</option>
									<?php foreach($this->body_template_data['customer_groups'] as $group){ ?>
										<option value="<?php echo $group['id'];?>"><?php echo $group['name'];?></option>
									<?php } ?>
								</select>
							</div>
						</div>

						<div class="form-group row">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_sector_id">
								Industry
							</label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<select class="form-control" id="add_sector_id" name="industry_id">
									<option value="">-- Select Industry --</option>
									<?php foreach($this->body_template_data['industries'] as $industry){ ?>
										<option value="<?php echo $industry['id'];?>"><?php echo $industry['industry'];?></option>
									<?php } ?>
								</select>
							</div>
						</div>
					</div>

					<div class="bd-callout bd-callout-info">
						<div class="section-header"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
						<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 110-5 2.5 2.5 0 010 5z"/>
						</svg>Address
						</div>

						<div class="form-group row">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_comp_address_1">
								Address Line 1
							</label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<input type="text" id="add_comp_address_1" placeholder="Address line 1" 
									   class="form-control" name="address_1" value="" maxlength="255">
							</div>
						</div>

						<div class="form-group row">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_comp_address_2">
								Address Line 2
							</label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<input type="text" id="add_comp_address_2" placeholder="Address line 2" 
									   class="form-control" name="address_2" value="" maxlength="255">
							</div>
						</div>

						<div class="form-group row">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_comp_address_3">
								Address Line 3
							</label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<input type="text" id="add_comp_address_3" placeholder="Address line 3" 
									   class="form-control" name="address_3" value="" maxlength="255">
							</div>
						</div>

						<div class="form-group row">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_comp_city">
								City
							</label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<input type="text" id="add_comp_city" placeholder="City" 
									   class="form-control" name="city" value="" maxlength="100">
							</div>
						</div>

						<div class="form-group row">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_comp_state">
								State
							</label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<select class="form-control" id="add_comp_state" name="state">
									<option value="">-- Select State --</option>
									<option value="Andhra Pradesh">Andhra Pradesh</option>
									<option value="Andaman and Nicobar Islands">Andaman and Nicobar Islands</option>
									<option value="Arunachal Pradesh">Arunachal Pradesh</option>
									<option value="Assam">Assam</option>
									<option value="Bihar">Bihar</option>
									<option value="Chandigarh">Chandigarh</option>
									<option value="Chhattisgarh">Chhattisgarh</option>
									<option value="Dadar and Nagar Haveli">Dadar and Nagar Haveli</option>
									<option value="Daman and Diu">Daman and Diu</option>
									<option value="Delhi">Delhi</option>
									<option value="Lakshadweep">Lakshadweep</option>
									<option value="Puducherry">Puducherry</option>
									<option value="Goa">Goa</option>
									<option value="Gujarat">Gujarat</option>
									<option value="Haryana">Haryana</option>
									<option value="Himachal Pradesh">Himachal Pradesh</option>
									<option value="Jammu and Kashmir">Jammu and Kashmir</option>
									<option value="Jharkhand">Jharkhand</option>
									<option value="Karnataka">Karnataka</option>
									<option value="Kerala">Kerala</option>
									<option value="Madhya Pradesh">Madhya Pradesh</option>
									<option value="Maharashtra">Maharashtra</option>
									<option value="Manipur">Manipur</option>
									<option value="Meghalaya">Meghalaya</option>
									<option value="Mizoram">Mizoram</option>
									<option value="Nagaland">Nagaland</option>
									<option value="Odisha">Odisha</option>
									<option value="Punjab">Punjab</option>
									<option value="Rajasthan">Rajasthan</option>
									<option value="Sikkim">Sikkim</option>
									<option value="Tamil Nadu">Tamil Nadu</option>
									<option value="Telangana">Telangana</option>
									<option value="Tripura">Tripura</option>
									<option value="Uttar Pradesh">Uttar Pradesh</option>
									<option value="Uttarakhand">Uttarakhand</option>
									<option value="West Bengal">West Bengal</option>
								</select>
							</div>
						</div>

						<div class="form-group row">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_comp_pin">
								PIN
							</label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<input type="text" id="add_comp_pin" placeholder="PIN" 
									   class="form-control" name="pin" value="" maxlength="6">
							</div>
						</div>
					</div>			

					<div class="bd-callout bd-callout-info">                    
						<div class="section-header"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
						<path d="M12 4a8 8 0 100 16 8 8 0 000-16zm1 12h-2v-2h2v2zm0-4h-2V7h2v5z"/>
						</svg>Other Information
						</div>
						<div class="form-group row">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_website">
								Website
							</label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<input type="text" id="add_website" placeholder="Website" 
									   class="form-control" name="website" value="" maxlength="200">
							</div>
						</div>

						<div class="form-group row">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_business_details">
								Business Details
							</label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<textarea id="add_business_details" placeholder="Business Details" 
										  class="form-control" name="business_details"></textarea>
							</div>
						</div>

						<div class="form-group row">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_active_y">
								Active <span class="mandatory">*</span>
							</label>
							<div class="form-check form-check-inline pl-3">
								<label class="form-check-label">
									<input id="add_active_y" class="form-check-input" type="radio" 
										   name="active" value="y" checked="checked">
									Yes
								</label>
							</div>
							<div class="form-check form-check-inline">
								<label class="form-check-label">
									<input id="add_active_n" name="active" class="form-check-input" 
										   type="radio" value="n">
									No
								</label>
							</div>
						</div>
					</div>
					<!-- Contact Person Information Section -->
					<div class="bd-callout bd-callout-info">
						<div class="section-header d-flex align-items-center mb-3">
							<svg class="mr-2" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
								<path d="M12 12c2.67 0 8 1.34 8 4v2H4v-2c0-2.66 5.33-4 8-4zm0-2a4 4 0 110-8 4 4 0 010 8z"></path>
							</svg>
							<span>Contact Person Information</span>
						</div>

						<div class="form-group row">
							<label class="control-label col-12 col-lg-2">
								Contacts
							</label>
							<div class="col-12 col-lg-10">
								<div class="table-responsive">
									<table id="contacts-table" class="table table-bordered table-sm">
										<thead class="thead-lightblue">
											<tr>
												<th>Name <span class="mandatory">*</span></th>
												<th>Department</th>
												<th>Designation</th>
												<th>Email</th>
												<th>Phone</th>
												<th class="actionColumn">Actions</th>
											</tr>
										</thead>
										<tbody>
										<!-- Contact rows will be dynamically added here -->
										</tbody>
									</table>
									<!-- button type="button" class="btn btn-success" id="add-contact-btn">
										Add Contact
									</button -->
									<button type="button" class="btn btn-primary rounded d-inline-flex align-items-center rounded" id="add-contact-btn">
										<svg class="icon" viewBox="0 0 16 16" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M8 1a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2H9v5a1 1 0 1 1-2 0V9H2a1 1 0 1 1 0-2h5V2a1 1 0 0 1 1-1z"/>
										</svg>
										<span>Add Person</span>
									</button>
								</div>
							</div>
						</div>
					</div>	
					<div class="clearfix"></div>
					<div class="form-actions form-group text-center">
							<button class="btn btn-success btn-pill" type="submit" id="record-save-button" style="margin-right: 10px;">
							<img src="images/check.png" class="check-button" alt="Check"> 
							<span>Add Customer</span>
						</button>
					</div>		
				</form>			
			</div>
		</div>
	</div>
</div>