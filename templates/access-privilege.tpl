<style>
.flex_container {
	display: flex;
	flex-wrap: nowrap; 
}

.flex_container_block {
	width: auto;
	margin: 10px 15px 10px 0;
	white-space: nowrap;
	align-content: center;
}
.flex_container_select select{
	width: 200px !important;
}
.flex_container_select{
	width: 200px;
}
#manage_privilege_for_label {
  max-width: 100% !important;
  margin-right: 5px;
  white-space: nowrap;
}
.cross{
float: left;
  margin-right: 10px;
  width: 22px;
  height: auto;
  margin-top: 2px;
}
.tick{
float: left;
margin-right: 10px;
}
@media (max-width:991px){
	.flex_container {
		display: block;
	}
	.flex_container_mobile {
	  display: inline-block;
	  width: 140px;
	}
}
@media (max-width:480px){
	.flex_container_select {
		white-space: normal;
	}
}
</style>

<div class="row">
	<div class="col-12 mt-3 mb-2">
		<div class="card">
			<div class="card-body">
				<div class="card-header-heading">
					<h4 class="row"> Access Privileges&nbsp;</h4>
				</div>
				
				<div class="flex_container">	
						
							<div class="flex_container_block flex_container_mobile">
								<div class="form-check form-check-inline">
									<label class="form-check-label">
										<input class="form-check-input" type="radio" name='privilege_managing_criterion' id='privilege_managing_criterion_U' value="U"> Per User Basis
									</label>
								</div>
							</div>
							
							<div class="flex_container_block flex_container_mobile">
								<div class="form-check form-check-inline">
								  <label class="form-check-label">
									<input class="form-check-input" type="radio" name='privilege_managing_criterion' id='privilege_managing_criterion_R' value="R" > Per Role Basis
								  </label>
								</div>
							</div>
						
					
							<div class="flex_container_block flex_container_select">
								<label for="manage_privilege_for" class="col-form-label " id="manage_privilege_for_label">Manage Access Privileges Of
								</label>
							
								<select id="manage_privilege_for" class="form-control col">
									<option selected>Choose...</option>
									<option>...</option>
								</select>
							</div>
							
							
							
					
				
				</div>
				<div class="row">
					<div class="col-12 mt-3">
						<div id="available_nonavailable_lists" class="d-none">
								<p id='user_help_text_1' >Users under the 'Admin' role have access to all the menus and their respective privileges. For others, you can change the access privileges by clicking the add/remove links of the respective item in order to send it to the other list. Finally save the changes by clicking the "Save Changes" button.</p>
								<p id='user_help_text_2' >The role 'Admin' has access to all the menus and their respective privileges. For other roles you can change the access privileges by clicking the add/remove links of the respective item in order to send it to the other list then setting/removing the privileges on the menus and finally saving the changes by clicking the "Save Changes" button.</p>
								<hr class="my-2 mt-2">
								<div class="guide alert-primary show-invited-info" id='user_help_text_3' ></div>
								<div class="col-lg-12 col-sm-12" style="padding: 0;">
									<div class="alert d-none" id="page_alert">
										<strong>
											<i class="icon"></i>
										</strong>
										<span class="alert-message"></span>
									</div>
								</div>

								<form name='access_privilege_form' action='<?php echo CONST_APP_PATH_FROM_ROOT;?>/access-privilege.php?mode=savePrivilege' method='post' onsubmit="return access_privilege.savePrivileges();" target='form_post_submit_target_window' >
									<input type='hidden' name='hdn_entries_made_available' id='hdn_entries_made_available' value='' />
									<input type='hidden' name='hdn_entries_removed'  id='hdn_entries_removed' value='' />
									<input type='hidden' name='hdn_criterion_selected'  id='hdn_criterion_selected' value='' />
									<input type='hidden' name='hdn_privilege_for'  id='hdn_privilege_for' value='' />

									<div class="row">
										<div class="col-xs-12 col-sm-6">

											<div id='available_list'  >
												<img class="tick" src="images/green-tick.png" alt="tick"><h5 class="text-success font-weight-bold"> Can Access</h5>
												<hr class="my-2 mt-2">
											</div>

											<div class=" clearfix "   >
												<div class="items_list" id='available_list_items' style="padding-bottom:10px;"></div>
											</div>
										</div>

										<div class="col-xs-12 col-sm-6">

											<div id='nonavailable_list'   >
												<img class="cross" src="images/delete-1.png" alt="cross"><h5 class="text-danger font-weight-bold"> Cannot Access</h5>
												<hr class="my-2 mt-2">
											</div>

											<div class="clearfix "   >
												<div class="items_list" id='nonavailable_list_items' style="padding-bottom:10px;"></div>
											</div>
										</div>
									</div>

									<div class="form-actions form-group">
										<div class="col-sm-12 text-center">
											<button class="btn btn-success rounded disabled accesspriv-data-save-button" disabled='disabled'  type="submit" id="save_access_privilege" >
											  <i class="fa fa-check"></i>
											  Save Changes
											</button>

											<a class="btn btn-danger btn-danger-custom rounded" type="cancel" id="access_cancel" tabindex="99"  onclick="access_privilege.cancelProcess();" >
											  <i class="fa fa-remove"></i>
											  Cancel
											</a>
										</div>
									</div>
								</form>
						</div>
					</div>
				</div>
		  </div>
		</div>
	</div>
</div>

<div id='elements_to_clone' class='d-none'  >
	<div class="privilege-menu item panel panel-info card mb-2">
		<div  class='item_move_handle card-header' >
			<span class="itemtext card-title"></span>
			<div  class='btn btn-success btn-sm rounded item_add_icon' >
				<i class='fa fa-angle-double-left'  > </i> Add
			</div>
			<div  class='btn btn-danger btn-sm rounded item_remove_icon'  >
				Remove <i class='fa fa-angle-double-right'  > </i>
			</div>
		</div>
		<div  class='item_privilege_list panel-body card-body'  >
		</div>
	</div>
	<span  class='privilege_elem'  ><input type='checkbox' class='privilege_chkbox' /><label class='privilege_label'  ></label></span>
</div>