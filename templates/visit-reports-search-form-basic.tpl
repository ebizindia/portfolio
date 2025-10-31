<style>
.search-date-field .ui-datepicker-trigger img{
    position: relative;
top: -5px;
}
@media (max-width:575px){
    .search-date-field .ui-datepicker-trigger img{
        top: -7px;
    }
}
</style>
<div id="search_records" class="row">
    <div class="col-lg-12 col-sm-12">
        <form class="form-inline search-form" name="search_form" onsubmit="return visitReports.doSearch(this);">
            <div class="basic-search-box">
                <div class="row">
                    <div class="col-lg-12 col-sm-12">
                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-2 form-group">
                                <label class="" style="font-weight: normal; white-space: nowrap;" for="search-field_customer_group_id">Customer Group</label>
                                <select id="search-field_customer_group_id"
                                        class="form-control srchfld"
                                        style="height: 32px;width: 100%;"
                                        data-type="EQUAL"
                                        data-fld="customer_group_id">
                                    <option value="">-- Select Group --</option>
                                    <?php foreach($this->body_template_data['customer_groups'] as $group){ ?>
                                    <option value="<?php echo $group['id'];?>"><?php echo \eBizIndia\_esc($group['name']);?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-2 form-group">
                                <label class="" style="font-weight: normal; white-space: nowrap;" for="search-field_customer_id">Customer</label>
                                <select id="search-field_customer_id"
                                        class="form-control srchfld"
                                        style="height: 32px;width: 100%;"
                                        data-type="EQUAL"
                                        data-fld="customer_id">
                                    <option value="">-- Select Customer --</option>
                                    <?php foreach($this->body_template_data['customers'] as $customer){ ?>
                                    <option value="<?php echo $customer['id'];?>"><?php echo \eBizIndia\_esc($customer['name']);?></option>
                                    <?php } ?>
                                </select>
                            </div>

                            <!-- NEW: Department Search -->
                            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-2 form-group">
                                <label class="" style="font-weight: normal; white-space: nowrap;" for="search-field_department">Department</label>
                                <select id="search-field_department"
                                        class="form-control srchfld"
                                        style="height: 32px;width: 100%;"
                                        data-type="EQUAL"
                                        data-fld="department">
                                    <option value="">-- Select Department --</option>
                                    <?php foreach($this->body_template_data['departments'] as $value => $label){ ?>
                                    <option value="<?php echo $value;?>"><?php echo \eBizIndia\_esc($label);?></option>
                                    <?php } ?>
                                </select>
                            </div>

                            <!-- NEW: Type Search -->
                            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-2 form-group">
                                <label class="" style="font-weight: normal; white-space: nowrap;" for="search-field_type">Type</label>
                                <select id="search-field_type"
                                        class="form-control srchfld"
                                        style="height: 32px;width: 100%;"
                                        data-type="EQUAL"
                                        data-fld="type">
                                    <option value="">-- Select Type --</option>
                                    <?php foreach($this->body_template_data['visit_types'] as $value => $label){ ?>
                                    <option value="<?php echo $value;?>"><?php echo \eBizIndia\_esc($label);?></option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-2 form-group">
                                <label class="" style="font-weight: normal; white-space: nowrap;" for="search-field_meeting_title">Meeting Title</label>
                                <input type="text" id="search-field_meeting_title"
                                       placeholder="Meeting title has"
                                       class="form-control srchfld"
                                       style="height: 32px;width: 100%;"
                                       maxlength="100"
                                       data-type="CONTAINS"
                                       data-fld="meeting_title" />
                            </div>
                            <?php if ($this->body_template_data['user_role'] === 'ADMIN') { ?>
                            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-2 form-group">
                                <label class="" style="font-weight: normal; white-space: nowrap;" for="search-field_created_by">Salesperson</label>
                                <select id="search-field_created_by"
                                        class="form-control srchfld"
                                        style="height: 32px;width: 100%;"
                                        data-type="EQUAL"
                                        data-fld="created_by">
                                    <option value="">-- Select Salesperson --</option>
                                    <?php foreach($this->body_template_data['salespersons'] as $salesperson){ ?>
                                    <option value="<?php echo $salesperson['user_acnt_id'];?>"><?php echo \eBizIndia\_esc($salesperson['name']);?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <?php } ?>
                        </div>
                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-2 form-group search-date-field">
                                <label class="" style="font-weight: normal; white-space: nowrap;" for="search-field_visit_date_start">Visit Date From</label>
                                <input type="text" id="search-field_visit_date_start"
                                       class="form-control srchfld datepicker"
                                       style="height: 32px;width: 100%;"
                                       data-type="DATE_RANGE"
                                       data-fld="visit_date_range"
                                       readonly />
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-2 form-group search-date-field">
                                <label class="" style="font-weight: normal; white-space: nowrap;" for="search-field_visit_date_end">Visit Date To</label>
                                <input type="text" id="search-field_visit_date_end"
                                       class="form-control srchfld datepicker"
                                       style="height: 32px;width: 100%;"
                                       data-type="DATE_RANGE"
                                       data-fld="visit_date_range"
                                       readonly />
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