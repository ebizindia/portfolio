<div id="search_records" class="row">
    <div class="col-lg-12 col-sm-12">
        <form class="form-inline search-form" name="search_form" onsubmit="return itemsStock.doSearch(this);">
            <div class="basic-search-box">
                <div class="row">
                    <div class="col-lg-12 col-sm-12">
                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 form-group">
                                <label class="" style="font-weight: normal; white-space: nowrap;" for="search-field_warehouse_id">Warehouse</label>
                                <select id="search-field_warehouse_id"
                                        class="form-control srchfld"
                                        style="height: 32px;width: 100%;"
                                        data-type="EQUAL"
                                        data-fld="warehouse_id">
                                    <option value="">-- Select Warehouse --</option>
                                    <?php foreach($this->body_template_data['warehouses'] as $warehouse) { ?>
                                    <option value="<?php echo $warehouse['id']; ?>"><?php echo \eBizIndia\_esc($warehouse['name']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 form-group">
                                <label class="" style="font-weight: normal; white-space: nowrap;" for="search-field_item_name">Item Name</label>
                                <input type="text" id="search-field_item_name"
                                       placeholder="Item name has"
                                       class="form-control srchfld"
                                       style="height: 32px;width: 100%;"
                                       maxlength="100"
                                       data-type="CONTAINS"
                                       data-fld="item_name" />
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