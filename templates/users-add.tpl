
    <style>
        /*.company_details_block{
            border:1px solid rgba(136, 136, 136, 0.1);
            border-radius:2px;
            background:#fafeff;
            padding:10px;
            margin-bottom: 10px;
        }
        .company_details_block .form-group{
            margin-bottom: 0rem !important;
        }
        .company_detail_txt{
            font-family: "Raleway", sans-serif;
            font-size: 14px;
            color: #5d6a75;
        }*/
        .purecounter{
            font-weight:bold;
        }
        .break_margin > span{
            margin-bottom:5px;
        }
        .break_margin br{
            display:none;
        }
        /*.company_details_table{
            display:table;
        }
        .company_details_left{
            width:300px;
            display:table-cell;
            vertical-align:top;
        }
        .company_details_right{
            width:300px;
            display:table-cell;
            vertical-align:top;
        }
        @media screen and (max-width:575px){
            .company_details_block .control-label {
                display:none;
            }
            .company_details_left, .company_details_right{
                display:block;
            }
        }*/
    </style>
</head>
<body>
<div class="card">
    <div class="card-body">
        <div class="card-header-heading">
            <div class="row">
                <div class="col-6">
                    <h4 id="panel-heading-text" class="pull-left row">
                        Add User&nbsp;
                        <img src="images/info.png" class="info-button" alt="Info">
                    </h4>
                </div>
                <div class="col-6 text-right">
                    <a href="users.php#" class="btn btn-danger rounded record-list-show-button back-to-list-button row" id="back-to-list-button">
                        <img src="images/left-arrow.png" class="custom-button" alt="Left"> Back To List
                    </a>
                    <a href="users.php#" class="btn btn-danger record-list-show-button back-to-list-button row mobile-bck-to-list" id="back-to-list-button">
                        <img src="images/left-arrow.png" class="custom-button" alt="Left">
                    </a>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12 col-lg-12">
                <form class="form-horizontal" role="form" name='adduserform' id="adduserform" action='users.php' method='post' onsubmit="return usersfuncs.saveUserDetails(this);" target="form_post_submit_target_window" data-mode="add-user" enctype="multipart/form-data" novalidate>
                    <input type='hidden' name='mode' id='add_edit_mode' value='createUser' />
                    <input type='hidden' name='recordid' id='add_edit_recordid' value='' />
                    <!--<div class="alert alert-warning mt-2" role="alert" id="msgFrm">
                        <p style="margin-bottom: 0">All fields marked with an asterisk (<span class="required">*</span>) are required.</p>
                    </div>-->
                    <div class="alert alert-danger d-none">
                        <strong><i class="icon-remove"></i></strong>
                        <span class="alert-message"></span>
                    </div>
                    <div class="alert alert-success d-none">
                        <strong><i class="icon-ok"></i></strong>
                        <span class="alert-message"></span>
                    </div>
                    <!--<h6>Basic Information</h6>
                    <hr class="my-2 mt-2 mb-4">-->
                    <div class="bd-callout bd-callout-info">
						<div class="section-header">
                         <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2a7 7 0 017 7c0 4-4 7-7 13-3-6-7-9-7-13a7 7 0 017-7z"/></svg>
                         Basic Information
						 </div>
						<div class="form-group row addonly">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_form_field_profilepic"> Profile Pic. </label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<div class="profile_image">
									<img src="" alt='profile_pic' id="profile_pic_img" class="profile_pic_img">
									<div class="remove_image d-none"><a href="#" id="remove_profile_pic" title="Mark the profile pic for deletion."><img src="images/clear1.png"></a><a href="#" id="undo_remove_profile_pic" class="d-none" title="Remove the delete marker from the profile pic to keep it after saving the modifications."><img src="images/undo.png"></a></div>
									<input type='hidden' name='delete_profile_pic' value='0' id="delete_profile_pic" />
								</div>
								<div id="img_del_marked_msg" class="d-none" style="font-size: 11px; color: #ff3333;">The profile pic has been marked for deletion and will be deleted after you click the "Save" button below.</div>
								<input type="file" id="add_form_field_profilepic" placeholder="Profile Pic" class="form-control" name='profile_pic' value="" accept="<?php echo '.'.implode(', .',$this->body_template_data['profile_pic_file_types']); ?>" style="margin-top: 5px;" />
								<a href="#" id="remove_profile_pic_selection">Clear Selection</a>
								<div class="form-elem-guide-text default-box">
									<span class="">Allowed file types: <?php echo implode(', ',$this->body_template_data['profile_pic_file_types']); ?></span>
								</div>
							</div>
						</div>
						<div class="form-group row addonly">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_form_field_title"> Salutation <span class="mandatory">*</span></label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<select class="form-control" id="add_form_field_title" name='title' onchange="usersfuncs.titleChanged(this);">
									<option value="">-- Select salutation --</option>
									<?php foreach($this->body_template_data['salutation'] as $title){ ?>
									<option value="<?php echo htmlentities($title) ?>"><?php echo htmlentities($title); ?></option>
									<?php } ?>
								</select>
							</div>
						</div>
						<div class="form-group row addonly">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_form_field_name"> Name <span class="mandatory">*</span></label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<input type="text" id="add_form_field_name" placeholder="Full name" class="form-control" name='name' value="" maxlength='100' autocomplete="off" />
								<div class="form-elem-guide-text default-box">
									<span class="">Only alphabets, hyphen, period and spaces are allowed.</span>
								</div>
							</div>
						</div>
						<div class="form-group row addonly">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_form_field_user_group_id"> User Group <span class="mandatory">*</span></label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<select class="form-control" id="add_form_field_user_group_id" name='user_group_id'>
									<option value="">-- Select user group --</option>
								</select>
								<div class="form-elem-guide-text default-box">
									<span class="">Select the user group this user belongs to.</span>
								</div>
							</div>
						</div>
						<div class="form-group row addonly">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_form_field_email"> Email</label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<input type="text" id="add_form_field_email" placeholder="User's email id" class="form-control" name='email' value="" maxlength='255' autocomplete="off" style="padding-right: 25px;" />
								<img src="images/email-16x14.png" alt="email" class="email-icon-form-input" data-url="">
								<div class="form-elem-guide-text default-box">
								   
								</div>
							</div>
						</div>
						<div class="form-group row">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_form_field_mobile"> WhatsApp Number <span class="mandatory">*</span></label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<input type="text" id="add_form_field_mobile" placeholder="WhatsApp number" class="form-control" name='mobile' value="" maxlength='15' autocomplete="off" style="padding-right: 52px;" />
								<img src="images/whatsapp-16x14.png" alt="whatsapp" class="wa-icon-form-input" data-url="" data-target="_blank">
								<img src="images/phone-16x14.png" alt="whatsapp" class="tel-icon-form-input" data-url="" data-target="_blank">
								<div class="form-elem-guide-text default-box">
									<span class="">Only digits are allowed.</span>
									 <span class="">User's Mobile Number. Will be used as username for login.</span>
								</div>
							</div>
						</div>
						<div class="form-group row addonly">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_form_field_mobile2"> Alternate Mobile </label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<input type="text" id="add_form_field_mobile2" placeholder="Alternate mobile number" class="form-control" name='mobile2' value="" maxlength='15' autocomplete="off" />
								<div class="form-elem-guide-text default-box">
									<span class="">Only digits are allowed.</span>
								</div>
							</div>
						</div>
						<div class="form-group row addonly">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_form_field_gender_M"> Gender <span class="mandatory">*</span></label>
							<?php foreach (\eBizIndia\enums\Gender::cases() as $case){ ?>
							<div class="form-check form-check-inline pl-3">
								<label class="form-check-label">
									<input id="add_form_field_gender_<?php echo $case->value; ?>" class="form-check-input" type="radio" name="gender" value='<?php echo $case->value; ?>' />
									<?php echo \eBizIndia\_esc($case->label()); ?>
								</label>
							</div>
							<?php } ?>
						</div>
						<!-- <div class="form-group row addonly">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_form_field_bloodgrp"> Blood Group </label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<select class="form-control" id="add_form_field_bloodgrp" name='blood_grp'>
									<option value="">-- Select blood group --</option>
									<?php foreach($this->body_template_data['blood_grps'] as $bg){ ?>
									<option value="<?php echo htmlentities($bg) ?>"><?php echo htmlentities($bg); ?></option>
									<?php } ?>
								</select>
							</div>
						</div>
						<div class="form-group row addonly">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_form_field_dob_picker"> Date Of Birth</label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<input type="hidden" name="dob" id="add_form_field_dob" value="">
								<input type="text" id="add_form_field_dob_picker" placeholder="Date of birth" class="form-control" value="" maxlength='10' autocomplete="off" readonly style="background-color: transparent !important; cursor: default !important;" />
							</div>
						</div>
						<div class="form-group row addonly">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_form_field_annv_picker"> Anniversary Date </label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<input type="hidden" name="annv" id="add_form_field_annv" value="">
								<input type="text" id="add_form_field_annv_picker" placeholder="Wedding anniversary date" class="form-control" value="" maxlength='10' autocomplete="off" readonly style="background-color: transparent !important; cursor: default !important;" />
							</div>
						</div>	 -->
						<div class="form-group row addonly">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_form_field_designation"> Designation </label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<input type="text" id="add_form_field_designation" placeholder="Designation in company" class="form-control"  name='designation' value="" maxlength='100'  />
							</div>
						</div> 
                    </div> 
					
                    <div class="bd-callout bd-callout-info">
						<div class="section-header">
                         <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 4a8 8 0 100 16 8 8 0 000-16zm1 12h-2v-2h2v2zm0-4h-2V7h2v5z"></path></svg>
                         Settings
						 </div>
						<div class="form-group row">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_form_field_role_REGULAR"> Role <span class="mandatory">*</span> </label>
							<?php foreach (\eBizIndia\enums\Role::cases() as $case){ ?>
							<div class="form-check form-check-inline pl-3">
								<label class="form-check-label">
									<input id="add_form_field_role_<?php echo $case->value; ?>" class="form-check-input" type="radio" name="role" value='<?php echo $case->value; ?>' />
									<?php echo \eBizIndia\_esc($case->label()); ?>
								</label>
							</div>
							<?php } ?>
						</div>
						<div class="form-group row">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_form_field_password"> Password <span class="mandatory" id="pswd_field_mandatory_marker" >*</span> </label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<input type="text" id="add_form_field_password" placeholder="Login password" class="form-control" name='password' value="" maxlength='20' />
								<div class="form-elem-guide-text default-box">
									<span class="" id="add_password_msg">Required for login purpose.</span>
									<span class="d-none" id="edit_password_msg">Required for login purpose.<br>Leave the box empty to keep the current password.</span>
								</div>
							</div>
						</div>
						<div class="form-group row">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_form_field_status_y"> Status </label>
							<div class="form-check form-check-inline pl-3">
								<label class="form-check-label">
									<input id="add_form_field_status_y" class="form-check-input" type="radio" name="status" value='y' />
									Active
								</label>
							</div>
							<div class="form-check form-check-inline">
								<label class="form-check-label">
									<input id='add_form_field_status_n' name="status" class="form-check-input" type="radio" value='n' />
									Inactive
								</label>
							</div>
						</div>
						<?php if($this->body_template_data['is_admin']){ ?>
						<div class="form-group row" id="remarks_box">
							<label class="control-label col-xs-12 col-sm-6 col-lg-2" for="add_form_field_remarks">Remarks</label>
							<div class="col-xs-12 col-sm-6 col-lg-4">
								<textarea id="add_form_field_remarks" placeholder="Enter any important, extra information, to be recorded with this user's profile." class="form-control" name='remarks'></textarea>
							</div>
						</div>
					</div>
						
                    <?php } ?>
                    <div class="clearfix"></div>
                    <div class="form-actions form-group">
                        <div class="col-md-12 col-sm-12 col-xs-12 text-center">
                            <center>
                                <button class="btn btn-success btn-pill" type="submit" id="record-save-button" style="margin-right: 10px;">
                                    <img src="images/check.png" class="check-button" alt="Check"> <span>Add User</span>
                                </button>
                                <a href="users.php#" class="btn btn-danger d-none" type="button" id="record-add-cancel-button" data-back-to="" onclick="usersfuncs.closeAddUserForm();">
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