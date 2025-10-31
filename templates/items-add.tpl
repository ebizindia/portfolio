<div class="card">
    <div class="card-body">
        <div class="card-header-heading">
            <div class="row">
                <div class="col-6"><h4 id="panel-heading-text" class="pull-left row">Add Item&nbsp;<img src="images/info.png" class="info-button" alt="Info"></h4></div>
                <div class="col-6 text-right">
                    <a href="items.php#" class="btn btn-danger rounded record-list-show-button back-to-list-button row" id="back-to-list-button">
                        <img src="images/left-arrow.png" class="custom-button" alt="Left"> Back To List
                    </a>
                    <a href="items.php#" class="btn btn-danger record-list-show-button back-to-list-button row mobile-bck-to-list" id="back-to-list-button">
                        <img src="images/left-arrow.png" class="custom-button" alt="Left">
                    </a>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12 col-lg-12">
                <form class="form-horizontal" role="form" name='addrecform' id="addrecform" action='items.php' method='post' onsubmit="return itemfuncs.saveRecDetails(this);" target="form_post_submit_target_window" data-mode="add-rec" enctype="multipart/form-data" novalidate>
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

                    <!-- Item entry fields -->
                    <div class="form-group row">
                        <label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_name">Name <span class="mandatory">*</span></label>
                        <div class="col-xs-12 col-sm-6 col-lg-4">
                            <input type="text" id="add_name" placeholder="Item Name" class="form-control" name="name" value="" maxlength="100">
                            <small class="form-text text-muted">Maximum 100 characters</small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_make">Make</label>
                        <div class="col-xs-12 col-sm-6 col-lg-4">
                            <input type="text" id="add_make" placeholder="Manufacturer/Brand" class="form-control" name="make" value="Timken" maxlength="30">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_unit">Unit <span class="mandatory">*</span></label>
                        <div class="col-xs-12 col-sm-6 col-lg-4">
                            <input type="text" id="add_unit" placeholder="Unit" class="form-control" name="unit" value="pc" maxlength="20">
                        </div>
                    </div>

                    <div class="clearfix"></div>
                    <div class="form-actions form-group text-center">
                        <button class="btn btn-success btn-pill" type="submit" id="record-save-button" style="margin-right: 10px;">
                            <img src="images/check.png" class="check-button" alt="Check"> <span>Add Item</span>
                        </button>
                        <div class="col-md-4 col-sm-2 hidden-xs"></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>