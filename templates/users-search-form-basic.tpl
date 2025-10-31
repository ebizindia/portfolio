<style>
	.sector-search{
		margin-left: 26px;
		padding: 0px;
	}

	@media (min-width:992px){
		.bldgrp-search{
			padding-right: 0px;
		}
		.batch-search{
			padding-left: 0px;
			padding-right: 0px;
		}	
	}

	@media (max-width:991px){
		.sector-search{
			margin-left: 0px;
			padding-right: 15px;
			padding-left: 15px;
		}	
	}

	

</style>
<div id="search_records" class="row ">
	<div class=" col-lg-12 col-sm-12">
		<form class="form-inline search-form" name="search_form" onsubmit="return usersfuncs.doSearch(this);">

			<div class="basic-search-box ">
				<div class="row">
					
						<div class="col-lg-12 col-sm-12">
							<div class="row">
								<div class="col-xs-12 col-sm-6 col-md-6 col-lg-2 form-group ">
									<label class="" style="font-weight: normal; white-space: nowrap;" for="search-field_name" >Name</label>
									<input type="text" id="search-field_name"  placeholder="Name has" class="form-control srchfld" style="height: 32px;width: 100%;" maxlength="50" data-type="CONTAINS" data-fld="name" />
								</div>

								<div class="col-xs-12 col-sm-6 col-md-6 col-lg-2 form-group ">
									<label class="" style="font-weight: normal; white-space: nowrap;" for="search-field_email" >Email</label>
									<input type="text" id="search-field_email"  placeholder="Email Id has" class="form-control srchfld" style="height: 32px;width: 100%;" maxlength="50" data-type="CONTAINS" data-fld="email"  />
								</div>

								<div class="col-xs-12 col-sm-6 col-md-6 col-lg-2 form-group ">
									<label class="" style="font-weight: normal; white-space: nowrap;" for="search-field_mob" >Mobile</label>
									<input type="text" id="search-field_mob"  placeholder="Mobile no. has" class="form-control srchfld" style="height: 32px;width: 100%;" maxlength="50" data-type="CONTAINS" data-fld="mob" />
								</div>

								<div class="col-xs-12 col-sm-6 col-md-6 col-lg-2 form-group ">
									<label class="" style="font-weight: normal; white-space: nowrap;" for="search-field_mob" >Mobile</label>
									<input type="text" id="search-field_mob"  placeholder="Mobile no. has" class="form-control srchfld" style="height: 32px;width: 100%;" maxlength="50" data-type="CONTAINS" data-fld="mob" />
								</div>

								<div class="col-xs-12 col-sm-6 col-md-6 col-lg-2 form-group">
									<label class="" style="font-weight: normal; white-space: nowrap; width: 100%;" for="search-field_user_group" >User Group</label>
									<select class="form-control srchfld" id="search-field_user_group" data-type="EQUAL" data-fld="user_group_id" style="width: 100%;" >
										<option value="">-- Any --</option>
									</select>
								</div>
								
								<?php
									if($this->body_template_data['is_admin']){
								?>
									<div class="col-xs-12 col-sm-6 col-md-6 col-lg-2 form-group">
										<label class="" style="font-weight: normal; white-space: nowrap; width: 100%;" for="search-field_status" >Status</label>
										<select class="form-control srchfld" id="search-field_status" data-type="EQUAL" data-fld="is_active" style="width: 100%;" >
		                                    <option value="">-- Any --</option>
		                                    <option value="y">Active</option>
		                                    <option value="n">Inactive</option>
		                                </select>
									</div>
								<?php
									}
								?>
								
								<div class="col-xs-12 col-sm-6 col-md-6 col-lg-2 form-group">
									<label class="mobile_display_none" >&nbsp;</label>
									<button class="btn btn-primary user-btn-search rounded search_button" style="margin-right: 10px;">
										<img src="images/search.png" class="custom-button" alt="Search"> Search
									</button>
								</div>
						</div>

						</div>
				</div>

			</div>

		</form>
	</div>
</div>
