<div id="search_records" class="row">
    <div class="col-lg-12 col-sm-12">
        <form class="form-inline search-form" name="search_form" onsubmit="return itemfuncs.doSearch(this);">
            <div class="basic-search-box">
                <div class="row">
                    <div class="col-lg-12 col-sm-12">
                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 form-group">
                                <label class="" style="font-weight: normal; white-space: nowrap;" for="search-field_name">Name</label>
                                <input type="text" id="search-field_name" placeholder="Name contains" class="form-control srchfld" style="height: 32px;width: 100%;" maxlength="100" data-type="CONTAINS" data-fld="name" />
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 form-group">
                                <label class="" style="font-weight: normal; white-space: nowrap;" for="search-field_unit">Unit</label>
                                <input type="text" id="search-field_unit" placeholder="Unit contains" class="form-control srchfld" style="height: 32px;width: 100%;" maxlength="100" data-type="CONTAINS" data-fld="unit" />
                            </div>

                            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 form-group">
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