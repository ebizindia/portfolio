<div id="search_records" class="row">
	<div class="col-lg-12 col-sm-12">
		<form class="form-inline search-form" name="search_form" onsubmit="return portfoliofuncs.doSearch(this);">

			<div class="basic-search-box">
				<div class="row">

						<div class="col-lg-12 col-sm-12">
							<div class="row">
								<div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 form-group">
									<label class="" style="font-weight: normal; white-space: nowrap;" for="search-field_portfolio_name">Portfolio Name</label>
									<input type="text" id="search-field_portfolio_name" placeholder="Portfolio name has" class="form-control srchfld" style="height: 32px;width: 100%;" maxlength="100" data-type="CONTAINS" data-fld="portfolio_name" />
								</div>

								<div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 form-group">
									<label class="" style="font-weight: normal; white-space: nowrap;" for="search-field_portfolio_type">Portfolio Type</label>
									<select id="search-field_portfolio_type" class="form-control srchfld" style="height: 32px;width: 100%;" data-type="EXACT" data-fld="portfolio_type">
										<option value="">-- All Types --</option>
										<option value="Own">Own</option>
										<option value="Portfolio Manager">Portfolio Manager</option>
										<option value="Unlisted & AIF">Unlisted & AIF</option>
									</select>
								</div>

								<div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 form-group">
									<label class="" style="font-weight: normal; white-space: nowrap;" for="search-field_status">Status</label>
									<select id="search-field_status" class="form-control srchfld" style="height: 32px;width: 100%;" data-type="EXACT" data-fld="status">
										<option value="">-- All Statuses --</option>
										<option value="Active">Active</option>
										<option value="Inactive">Inactive</option>
									</select>
								</div>

								<div class="col-xs-12 col-sm-6 col-md-6 col-lg-2 form-group">
									<label class="mobile_display_none">&nbsp;</label>
									<button class="btn btn-primary user-btn-search rounded search_button">
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
