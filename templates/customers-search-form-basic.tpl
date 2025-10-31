<div id="search_records" class="row">
    <div class="col-lg-12 col-sm-12">
        <form class="form-inline search-form" name="search_form" onsubmit="return customers.doSearch(this);">
            <div class="basic-search-box">
                <div class="row">
                    <div class="col-lg-12 col-sm-12">
                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-2 form-group">
                                <label class="" style="font-weight: normal; white-space: nowrap;" for="search-field_name">Customer</label>
                                <input type="text" id="search-field_name" 
                                       placeholder="Customer name has" 
                                       class="form-control srchfld" 
                                       style="height: 32px;width: 100%;" 
                                       maxlength="100" 
                                       data-type="CONTAINS" 
                                       data-fld="name" />
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-2 form-group">
                                <label class="" style="font-weight: normal; white-space: nowrap;" for="search-field_customer_group_name">Customer Group</label>
                                <input type="text" id="search-field_customer_group_name" 
                                       placeholder="Customer group has" 
                                       class="form-control srchfld" 
                                       style="height: 32px;width: 100%;" 
                                       maxlength="100" 
                                       data-type="CONTAINS" 
                                       data-fld="customer_group_name" />
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-2 form-group">
                                <label class="" style="font-weight: normal; white-space: nowrap;" for="search-field_industry_name">Industry</label>
                                <input type="text" id="search-field_industry_name" 
                                       placeholder="Industry has" 
                                       class="form-control srchfld" 
                                       style="height: 32px;width: 100%;" 
                                       maxlength="100" 
                                       data-type="CONTAINS" 
                                       data-fld="industry_name" />
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