<?php
$page='users';
require_once 'inc.php';
$template_type='';
$page_title = 'Users'.CONST_TITLE_AFX;
$page_description = 'One can users of the system.';
$body_template_file = CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'users.tpl';
$body_template_data = array();
$page_renderer->registerBodyTemplate($body_template_file,$body_template_data);
$email_pattern="/^\w+([.']?-*\w+)*@\w+([.-]?\w+)*(\.\w{2,4})+$/i";
$user_date_display_format_for_storage = 'd-m-Y';
$default_pswd = 'xyz123';
$profile_type = 'member';
$self_edit = $others_edit = $can_add = false; // $can_edit = false;
$_cu_role = $loggedindata[0]['profile_details']['assigned_roles'][0]['role'];
// if($loggedindata[0]['profile_details']['assigned_roles'][0]['role']=='ADMIN')
// 	$can_edit = true;
$default_list_filter  = '[]'; //($_cu_role=='ADMIN')?'[{"field":"is_active", "value":"y"}]':'[]';

if(CONST_MEM_PROF_EDIT_RESTC[$_cu_role]['self'][0]===true)
	$self_edit = true;
if(CONST_MEM_PROF_EDIT_RESTC[$_cu_role]['others'][0]===true)
	$can_add = $others_edit = true;

$member_fields = [
	'title'=>'', 
	'name'=>'',
	'email'=>'',
	'mobile'=>'', 
	'mobile2'=>'', 
	'gender'=>'', 
	'blood_grp'=>'', 
	'dob'=>'', 
	'annv'=>'', 
	'designation'=>'', 
	'password'=>'',
	'role'=>'',
	'status'=>'',
	'remarks'=>'',
	'user_group_id'=>'',
	 
];

if(filter_has_var(INPUT_POST,'mode') && $_POST['mode']=='createUser'){
	$result=array('error_code'=>0,'message'=>[], 'elemid'=>array(), 'other_data'=>['new_roles'=>[]]);
	$result['other_data']['post'] = $_POST;
	
	if($can_add===false){
		$result['error_code']=403;
		$result['message']="Sorry, you are not authorised to perfom this action.";
	}else{

		$data=array();
		$data = \eBizIndia\trim_deep(\eBizIndia\striptags_deep(array_intersect_key($_POST, $member_fields)));
		$data['dateDisplayFormat'] = $user_date_display_format_for_storage; 
		
		$other_data['field_meta'] = CONST_FIELD_META;
		$other_data['roles'] = array_column(\eBizIndia\enums\Role::cases(), 'value');
		$other_data['gender'] = array_column(\eBizIndia\enums\Gender::cases(), 'value');
		$other_data['blood_grps'] = array_keys(CONST_BLOOD_GRPS);
		$other_data['profile_pic'] = $_FILES['profile_pic'];

		// Get user groups for validation
		$user_groups_options = [];
		$user_groups_options['filters'] = [];
		$user_groups_options['filters'][] = ['field'=>'active', 'type'=>'EQUAL', 'value'=>'y'];
		$user_groups_list = \eBizIndia\UserGroup::getList($user_groups_options);
		$other_data['user_groups'] = $user_groups_list ?: [];

		$member_obj = new \eBizIndia\Member();	
		$validation_res = $member_obj->validate($data, 'add', $other_data);
		
		if($validation_res['error_code']>0){
			$result = $validation_res;
		} else {
			$username = $data['mobile'];
			$options=[];
			$options['filters']=[];
			$options['filters'][]=['field'=>'username','type'=>'EQUAL','value'=>$username];
			$res_user=$usercls->getList($options);
			
			$error_details_to_log = [];
			if($res_user===false){
				$result['error_code']=1; // DB error
				$result['message']="User could not be added due to server error.";

				$error_details_to_log['mode'] = 'createUser';
				$error_details_to_log['part'] = 'fetch user details for given mobile number';
				$error_details_to_log['func resp'] = 'boolean false';
				$error_details_to_log['result'] = $result;

			}elseif($res_user===null){
				$result['error_code']=1; // DB error
				$result['message']="User could not be added due to server error.";

				$error_details_to_log['mode'] = 'createUser';
				$error_details_to_log['part'] = 'fetch user details for given mobile number';
				$error_details_to_log['func resp'] = 'null';
				$error_details_to_log['result'] = $result;

			}
			elseif(!empty($res_user)){
				$result['error_code']=1; // Duplicate mobile number
				$result['message']="The mobile number ".\eBizIndia\_esc($data['mobile'], true)."  is already being used in some other user's profile.";

				$error_details_to_log['mode'] = 'createUser';
				$error_details_to_log['part'] = 'fetch user details for given mobile number';
				$error_details_to_log['func resp'] = 'non empty array';
				$error_details_to_log['result'] = $result;

			}
			else{
				
				$options=[];
				$options['filters']=[];
				$options['filters'][]=['field'=>'mobile','type'=>'EQUAL','value'=>$data['mobile']];
				$res_user=\eBizIndia\Member::getList($options);
				$error_details_to_log = [];
				if($res_user===false){
					$result['error_code']=1; // DB error
					$result['message']="User could not be added due to server error.";

					$error_details_to_log['mode'] = 'createUser';
					$error_details_to_log['part'] = 'fetch user details for given mobile number';
					$error_details_to_log['func resp'] = 'boolean false';
					$error_details_to_log['result'] = $result;

				}elseif($res_user===null){
					$result['error_code']=1; // DB error
					$result['message']="User could not be added due to server error.";

					$error_details_to_log['mode'] = 'createUser';
					$error_details_to_log['part'] = 'fetch user details for given mobile number';
					$error_details_to_log['func resp'] = 'null';
					$error_details_to_log['result'] = $result;

				}
				elseif(!empty($res_user)){
					$result['error_code']=1; // DB error
					$result['message']="The mobile number ".\eBizIndia\_esc($data['mobile'], true)."  is already being used in some other user's profile.";

					$error_details_to_log['mode'] = 'createUser';
					$error_details_to_log['part'] = 'fetch user details for given mobile number';
					$error_details_to_log['func resp'] = 'non empty array';
					$error_details_to_log['result'] = $result;

				}
				else{
                    
					$created_at = date('Y-m-d H:i:s');
					$ip = \eBizIndia\getRemoteIP();
//					$default_pswd = \eBizIndia\generatePassword();
					$login_account_data = [
						'username' => $data['mobile'],
//						'password' => password_hash(empty($data['password'])?$default_pswd:$data['password'], PASSWORD_BCRYPT),
						'password' => password_hash($data['password'], PASSWORD_BCRYPT),
						'profile_type' => $profile_type,
						'profile_id' => null,
						'status' => $data['status']==='y'?1:0,
						'createdOn' => $created_at,
						'createdBy' => $loggedindata[0]['id'],
						'createdFrom' => $ip,
					];	
                    
					$member_data = array_diff_key($data, ['password'=>'', 'role'=>'', 'status'=>'', 'dateDisplayFormat'=>'', 'groups'=>'', 'sector'=>'' ]);
					$member_data['active'] = $data['status'];
					$member_data['created_at'] = $created_at;
					$member_data['created_by'] = $loggedindata[0]['id'];
					$member_data['created_from'] = $ip;
					

					try{
						$conn = \eBizIndia\PDOConn::getInstance();
						$conn->beginTransaction();
						$error_details_to_log['mode'] = 'createUser';
						$error_details_to_log['part'] = 'Create a user record.';
											 
						
						$rec_id=$member_obj->saveDetails($member_data);
						if($rec_id===false)
							throw new Exception('Error creating a user record.');

						$error_details_to_log['mode'] = 'createUser';
						$error_details_to_log['part'] = 'Create a login account for the user record.';
						$login_account_data['profile_id'] = $rec_id;
						$login_rec_id=$usercls->saveUserDetails($login_account_data);	
						
						if($login_rec_id===false)
							throw new Exception('Error setting up a login account for a the new user.');

						$error_details_to_log['mode'] = 'createUser';
						$error_details_to_log['part'] = 'Fetch the details for the default role for the user type users.';
						$roles = $usercls->getRoles($data['role'],'',1); // only for company users
						if(empty($roles))
							throw new Exception('Error fetching the role ID for the new user record.');

						$error_details_to_log['mode'] = 'createUser';
						$error_details_to_log['part'] = 'Assign the selected role to the user\'s user account.';
						$roleids_to_assign = [$roles[0]['role_id']];
						if(!$usercls->assignRolesToUsers($login_rec_id,$roleids_to_assign)){
							throw new Exception('User could not be created due to error in saving the user role.');
						}


						$result['error_code']=0;
						$result['message']='The member <b>'.\eBizIndia\_esc($data['name']).'</b> has been created.';
						$conn->commit();

						if($_FILES['profile_pic']['error']===0){
							$profile_pic_res = $member_obj->uploadProflieImage($rec_id, $_FILES['profile_pic']['name'], $_FILES['profile_pic']['tmp_name']);
							if(empty($profile_pic_res)){
								$result['message'] .= "<span style='color:#ff3333;'  > The profile pic could not be processed.</span>";
							}else{
								if(!$member_obj->saveDetails(['profile_pic'=>$profile_pic_res['dp_file_name']], $rec_id)){
									$result['message'] .= "<span style='color:#ff3333;'  > The profile pic could not be registered.</span>";
									unlink(CONST_PROFILE_IMG_DIR_PATH.$profile_pic_res['dp_file_name']);
								}
							}
						}

						
					}catch(\Exception $e){
						$last_error = \eBizIndia\PDOConn::getLastError();
						$result['error_code']=1; // DB error
						if($last_error[1] == 1062){
							$result['message'] = "Process failed. Please make sure the mobile no is not in use by some other user.";
						}else{
							$result['message']="The user could not be added due to server error.";
						}
						$error_details_to_log['member_data'] = $member_data;
						$error_details_to_log['login_account_data'] = $login_account_data;
						$error_details_to_log['result'] = $result;
						\eBizIndia\ErrorHandler::logError($error_details_to_log, $e);
						if($conn && $conn->inTransaction())
							$conn->rollBack();
					}

					
				}	

			}
		}
	}


	$_SESSION['create_user_result'] = $result;
	header("Location:?");
	exit;

}elseif(filter_has_var(INPUT_POST,'mode') && $_POST['mode']=='updateUser'){
	$result=array('error_code'=>0,'message'=>[],'other_data'=>['new_roles'=>[], 'roleids_for_showing_selected'=>[]]);
	if($others_edit===false && $self_edit==false){
		$result['error_code']=403;
		$result['message']="Sorry, you are not authorised to perfom this action.";
	}else {
		$data=array();
		$recordid=(int)$_POST['recordid']; // member table's id
		// data validation
		if($recordid == ''){
			$result['error_code']=2;
			$result['message'][]="Invalid record ID.";

		}else{
			$options=[];
			$options['filters']=[];
			$options['filters'][]=['field'=>'id','type'=>'EQUAL','value'=>$recordid];
			$options['fieldstofetch'] = ['*'];
			$recorddetails = \eBizIndia\Member::getList($options);
			if($recorddetails===false){
				$result['error_code']=1;
				$result['message'][]="Failed to verify the user details due to server error.";
				$result['error_fields'][]="#add_form_field_name";
			}elseif(empty($recorddetails)){
				// member record with this ID does not exist
				$result['error_code']=3;
				$result['message'][]="The user account was not found.";
				$result['error_fields'][]="#add_form_field_name";
			}elseif( ($loggedindata[0]['id'] === $recorddetails[0]['user_acnt_id'] && $self_edit===false) || ($loggedindata[0]['id'] !== $recorddetails[0]['user_acnt_id'] && $others_edit===false) ){
				// self edit and others edit not allowed
				$result['error_code']=403;
				$result['message']="Sorry, you are not authorised to perfom this action.";
			}else{
				$edit_restricted_fields = [];
				if($self_edit===true && $loggedindata[0]['id'] === $recorddetails[0]['user_acnt_id']){
					// editing of one's own profile is allowed and the user is trying to do so remove the edit restricted fields from the allowed fields list
					$edit_restricted_fields = CONST_MEM_PROF_EDIT_RESTC[$_cu_role]['self'][1];
				}else if($others_edit===true && $loggedindata[0]['id'] !== $recorddetails[0]['user_acnt_id']){
					$edit_restricted_fields = CONST_MEM_PROF_EDIT_RESTC[$_cu_role]['others'][1];
				}

				$member_fields = array_diff_key($member_fields, array_fill_keys($edit_restricted_fields, '')); // removing the edit restricted fields from the list of fields
				
				$data = \eBizIndia\trim_deep(\eBizIndia\striptags_deep(array_intersect_key($_POST, $member_fields)));
				$other_data['field_meta'] = CONST_FIELD_META;
				$other_data['roles'] = array_column(\eBizIndia\enums\Role::cases(), 'value');
				$other_data['gender'] = array_column(\eBizIndia\enums\Gender::cases(), 'value');	
				$other_data['blood_grps'] = array_keys(CONST_BLOOD_GRPS);
				$other_data['loggedindata'] = $loggedindata[0];
				$other_data['recorddetails'] = $recorddetails[0];
				$other_data['profile_pic'] = !in_array('profile_pic', $edit_restricted_fields)?$_FILES['profile_pic']:[];
				$other_data['edit_restricted_fields'] = $edit_restricted_fields;

				// Get user groups for validation
				$user_groups_options = [];
				$user_groups_options['filters'] = [];
				$user_groups_options['filters'][] = ['field'=>'active', 'type'=>'EQUAL', 'value'=>'y'];
				$user_groups_list = \eBizIndia\UserGroup::getList($user_groups_options);
				$other_data['user_groups'] = $user_groups_list ?: [];

				$member_obj = new \eBizIndia\Member();	
				$validation_res = $member_obj->validate($data, 'update', $other_data); 
				if($validation_res['error_code']>0){
					$result = $validation_res;
				} else {
					$curr_dttm = date('Y-m-d H:i:s');
					$login_account_data = $member_data = [];
					if(array_key_exists('password', $member_fields) && !empty($data['password'])){	
						$login_account_data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
					}
					if($loggedindata[0]['id'] !== $recorddetails[0]['user_acnt_id'] && array_key_exists('status', $member_fields) &&  $data['status']!==$recorddetails[0]['active']){	
						$login_account_data['status'] = $data['status']==='y'?1:0;
					}
					
					$mobile_changed = $role_changed = $gender_changed = false;
					foreach($member_fields as $fld=>$val){
						if($fld=='password')
							continue;
						if($fld=='email' && empty($recorddetails[0][$fld]))
							$recorddetails[0][$fld] = '';

						if($fld=='mobile' && $data['mobile']!==$recorddetails[0]['mobile']){
							$mobile_changed = true;
							$member_data['mobile'] = $login_account_data['username'] = $data['mobile'];
						}else if($fld=='role'){
							if($data['role']!=$recorddetails[0]['assigned_roles'][0]['role'])
								$role_changed = $data['role'];
						}else if($fld=='status'){
							if($data['status']!==$recorddetails[0]['active'] && $loggedindata[0]['id'] !== $recorddetails[0]['user_acnt_id'])
								$member_data['active'] = $data[$fld]!='y'?'n':'y';
						}else if($fld=='user_group_id'){
							if($data['user_group_id']!==($recorddetails[0]['user_group_id'] ?? ''))
								$member_data['user_group_id'] = $data['user_group_id'];
							$user_group_changed = true;
						}else if($data[$fld]!==($recorddetails[0][$fld]??'') ){
							$member_data[$fld] = $data[$fld];
							if($fld==='gender')
								$gender_changed = true;
						}
					}
					
					if(!empty($login_account_data) || $role_changed){
						$login_account_data['lastUpdatedOn'] = $curr_dttm;
						$login_account_data['lastUpdatedBy'] = $loggedindata[0]['id'];
						$login_account_data['lastUpdatedFrom'] = \eBizIndia\getRemoteIP();
					}


					$delete_profile_pic = false;
					if(!in_array('profile_pic', $edit_restricted_fields) && $_POST['delete_profile_pic']==1 && $recorddetails[0]['profile_pic']!=''){
						$member_data['profile_pic'] = '';
						$delete_profile_pic = true;
					}

					try{
						$conn = \eBizIndia\PDOConn::getInstance();
						if(!empty($member_data) || !empty($login_account_data) || !empty($role_changed) || (!in_array('profile_pic', $edit_restricted_fields) && $_FILES['profile_pic']['error']===0) ){
							$conn->beginTransaction();
							if(!empty($member_data)){

								if($mobile_changed){
									// check the email address for uniqueness
									$username_check = $usercls->usernameExists($member_data['mobile'], [$recorddetails[0]['user_acnt_id']]);
									if($username_check===false)
										throw new Exception("Error updating the user record");
									if(!empty($username_check))
										throw new Exception("Process failed. A user with the given mobile number already exists.");
								}

								$member_data['updated_at'] = $curr_dttm;
								$member_data['updated_by'] = $loggedindata[0]['id'];
								$member_data['updated_from'] = \eBizIndia\getRemoteIP();
								$error_details_to_log['mode'] = 'updateUser';
								$error_details_to_log['part'] = 'Update the user record.';
								$member_obj = new \eBizIndia\Member();
								$res = $member_obj->saveDetails($member_data, $recordid);
								if($res===false)
									throw new Exception('Error updating the user record.');
							}

							if(!empty($login_account_data)){
								$error_details_to_log['mode'] = 'updateUser';
								$error_details_to_log['part'] = 'Update the login account for the user record.';
								$login_res = $usercls->saveUserDetails($login_account_data, $recorddetails[0]['user_acnt_id']);	
								if($login_res===false)
									throw new Exception('Error updating the login account for the user record.');
							}

							if(!empty($role_changed)){
								$roles = $usercls->getRoles($role_changed,'',1); // only for user users
								if(empty($roles))
									throw new Exception('Error fetching the role ID for the new user record.');

								$error_details_to_log['mode'] = 'updateUser';
								$error_details_to_log['part'] = 'Assign new role to the user\'s user account.';
								if(!$usercls->revokeUserRoles($recorddetails[0]['user_acnt_id'])){
									throw new Exception('Member could not be created due to error in updating the user role.');
								}
								$roleids_to_assign = [$roles[0]['role_id']];
								if(!$usercls->assignRolesToUsers($recorddetails[0]['user_acnt_id'],$roleids_to_assign)){
									throw new Exception('Member could not be created due to error in saving the user role.');
								}

							}

							if($loggedindata[0]['id']==$recorddetails[0]['user_acnt_id']){
								// If the user is editing his own account
								$userdata = $usercls->refreshLoggedInUserData();
								if(!$userdata){
									throw new Exception('Error updating the member record.');
								}
								if(isset($_COOKIE['loggedin_user']) && !empty($login_account_data['password']) ){
									setcookie('loggedin_user',base64_encode($userdata[0]['username'].$login_account_data['password']),time()+(30*24*60*60),CONST_APP_PATH_FROM_ROOT.'/',$_SERVER['HTTP_HOST'],false,true);
								}
							}

							$result['error_code']=0;
							$result['message']='The changes have been saved.';
							$result['other_data']['recordid']=$recorddetails[0]['user_acnt_id']; // id of the user table for the record which was edited
							$result['other_data']['loggedin_user_id']=$loggedindata[0]['id']; // logged in user's id as there in the users table
							$result['other_data']['profile_details']=$userdata[0]['profile_details'];
							$result['other_data']['title']=$data['title'];
							$result['other_data']['mobile'] = $data['mobile'];
							$result['other_data']['mobile2'] = $data['mobile2'];
							$result['other_data']['name']=$recorddetails[0]['name'];
							$conn->commit();

							if(!in_array('profile_pic', $edit_restricted_fields) && $_FILES['profile_pic']['error']===0){
								if(file_exists(CONST_PROFILE_IMG_DIR_PATH.$recorddetails[0]['profile_pic'])){
									$new_name = CONST_PROFILE_IMG_DIR_PATH.uniqid().'.'.pathinfo($recorddetails[0]['profile_pic'], PATHINFO_EXTENSION);
									rename(CONST_PROFILE_IMG_DIR_PATH.$recorddetails[0]['profile_pic'], $new_name);
								}
								$profile_pic_res = $member_obj->uploadProflieImage($recordid, $_FILES['profile_pic']['name'], $_FILES['profile_pic']['tmp_name']);
								if(empty($profile_pic_res)){
									$result['message'] .= "<span style='color:#ff3333;'  > The profile pic could not be processed.</span>";
									if(!empty($new_name)){
										rename($new_name, CONST_PROFILE_IMG_DIR_PATH.$recorddetails[0]['profile_pic']);
									}
								}else{
									$profile_pic_data = [
										'profile_pic'=>$profile_pic_res['dp_file_name']
									];
									if(empty($member_data)){
										$profile_pic_data['updated_at'] = $curr_dttm;	
										$profile_pic_data['updated_by'] = $loggedindata[0]['id'];
										$profile_pic_data['updated_from'] = \eBizIndia\getRemoteIP();
									}
									
									if(!$member_obj->saveDetails($profile_pic_data, $recordid)){
										$result['message'] .= "<span style='color:#ff3333;'  > The profile pic could not be registered.</span>";
										unlink(CONST_PROFILE_IMG_DIR_PATH.$profile_pic_res['dp_file_name']);
										if(!empty($new_name)){
											rename($new_name, CONST_PROFILE_IMG_DIR_PATH.$recorddetails[0]['profile_pic']);
										}
									}else{
										if(!empty($new_name)){
											// Delete the previously uploaded image file
											unlink($new_name);
										}
										$profile_pic_size = getimagesize(CONST_PROFILE_IMG_DIR_PATH.$profile_pic_res['dp_file_name']);
										$result['other_data']['profile_pic_max_width'] = CONST_PROFILE_IMG_DIM['dw'];
										$result['other_data']['profile_pic_org_width'] = $profile_pic_size[0];
										$result['other_data']['profile_pic_url'] = CONST_PROFILE_IMG_URL_PATH.$profile_pic_res['dp_file_name'];
									}
								}
							}else if($delete_profile_pic && file_exists(CONST_PROFILE_IMG_DIR_PATH.$recorddetails[0]['profile_pic'])){
								unlink(CONST_PROFILE_IMG_DIR_PATH.$recorddetails[0]['profile_pic']);
								$result['other_data']['profile_pic_deleted'] = 1;
								$result['other_data']['placeholder_image'] = ($member_data['gender']??'')==='F'?CONST_NOIMAGE_F_FILE:CONST_NOIMAGE_M_FILE;
							}else if($gender_changed && $recorddetails[0]['profile_pic']==''){
								$result['other_data']['placeholder_image'] = ($member_data['gender']??'')==='F'?CONST_NOIMAGE_F_FILE:CONST_NOIMAGE_M_FILE;
							}

							
						}else{
							$result['error_code']=4;
							$result['message']='There were no changes to save.';
						}
					}catch(\Exception $e){
						$result['error_code']=5; // DB error
						$last_error = \eBizIndia\PDOConn::getLastError();
						if($last_error[1] == 1062){
							$result['message'] = "Process failed. Please make sure the mobile number is not in use by some other member.";
						}else{
							$result['message']= $e->getMessage();
						}
						$error_details_to_log['member_data'] = $member_data;
						$error_details_to_log['login_account_data'] = $login_account_data;
						$error_details_to_log['result'] = $result;
						\eBizIndia\ErrorHandler::logError($error_details_to_log, $e);
						if($conn && $conn->inTransaction())
							$conn->rollBack();
					}
				
				}
			}

		}

	}

	$_SESSION['update_user_result']=$result;

	header("Location:?");
	exit;

}elseif(isset($_SESSION['update_user_result']) && is_array($_SESSION['update_user_result'])){
	header("Content-Type: text/html; charset=UTF-8");
	echo "<script type='text/javascript' >\n";
	echo "parent.usersfuncs.handleUpdateUserResponse(".json_encode($_SESSION['update_user_result']).");\n";
	echo "</script>";
	unset($_SESSION['update_user_result']);
	exit;

}elseif(isset($_SESSION['create_user_result']) && is_array($_SESSION['create_user_result'])){
	header("Content-Type: text/html; charset=UTF-8");
	echo "<script type='text/javascript' >\n";
	echo "parent.usersfuncs.handleAddUserResponse(".json_encode($_SESSION['create_user_result']).");\n";
	echo "</script>";
	unset($_SESSION['create_user_result']);
	exit;

}elseif(isset($_SESSION['comp_update_rec_result']) && is_array($_SESSION['comp_update_rec_result'])){
	header("Content-Type: text/html; charset=UTF-8");
	echo "<script type='text/javascript' >\n";
	echo "parent.usersfuncs.handleCompUpdateResponse(".json_encode($_SESSION['comp_update_rec_result']).");\n";
	echo "</script>";
	unset($_SESSION['comp_update_rec_result']);
	exit;

}elseif(filter_has_var(INPUT_POST,'mode') && $_POST['mode']=='getRecordDetails'){
	$result=array();
	$error=0; // no error
	$can_edit = false;
	$show_others_dnd_status = true;

	if($_POST['recordid']==''){
		$error=1; // Record ID missing

	}else{
		$options=[];
		$options['filters']=[];
		$options['filters'][]=['field'=>'id','type'=>'EQUAL','value'=>(int)$_POST['recordid']];
		if($_cu_role === 'REGULAR'){
			// Allow only active records, to be retrieved by REGULAR type members
			if($loggedindata[0]['profile_details']['id']!==((int)$_POST['recordid'])){
				$options['filters'][] = [
					'field' => 'active',
					'type' => 'EQUAL',
					'value' => 'y'
				];

				
			}
			
			$show_others_dnd_status = false;
		}
		$options['fieldstofetch'] = ['*'];
		$recorddetails = \eBizIndia\Member::getList($options);
		if($recorddetails===false){
			$error=2; // db error
		}elseif(count($recorddetails)==0){
			$error=3; // User ID does not exist
		}else{
			$recorddetails=$recorddetails[0];

			$edit_restricted_fields = [];
			if($self_edit===true && $loggedindata[0]['id'] === $recorddetails['user_acnt_id']){
				// editing of one's own profile is allowed and the user is opening his own profile
				$edit_restricted_fields = CONST_MEM_PROF_EDIT_RESTC[$_cu_role]['self'][1];
				$can_edit = true;
			}else if($others_edit===true && $loggedindata[0]['id'] !== $recorddetails['user_acnt_id']){
				// editing other recortds is allowed and the user is opening someone else's profile
				$edit_restricted_fields = CONST_MEM_PROF_EDIT_RESTC[$_cu_role]['others'][1];
				$can_edit = true;
			}

			$enum_obj = \eBizIndia\enums\Gender::tryFrom($recorddetails['gender']);
			$recorddetails['gender_view'] = !empty($enum_obj)?$enum_obj->label():'';

		 	// non admins will not see the role and status
			$enum_obj = \eBizIndia\enums\Role::tryFrom($recorddetails['assigned_roles'][0]['role']);
			$recorddetails['role_view'] = !empty($enum_obj)?$enum_obj->label():'';

			if($recorddetails['active']==='y')
				$recorddetails['status_view'] = 'Active';
			else
				$recorddetails['status_view'] = 'Inactive';
		
							
			if($recorddetails['dob']!='')
				$recorddetails['dob_view'] = date('d-M-Y', strtotime($recorddetails['dob']));
			if($recorddetails['annv']!='')
				$recorddetails['annv_view'] = date('d-M-Y', strtotime($recorddetails['annv']));

			if($recorddetails['blood_grp']!='')
				$recorddetails['blood_grp_view'] = CONST_BLOOD_GRPS[$recorddetails['blood_grp']]??'';
				
			$recorddetails['selftype'] = ''; //$memtypedet;
			if($recorddetails['profile_pic']!=''){
				$recorddetails['profile_pic_url'] = CONST_PROFILE_IMG_URL_PATH.$recorddetails['profile_pic'];
				$profile_pic_size = getimagesize(CONST_PROFILE_IMG_DIR_PATH.$recorddetails['profile_pic']);
				$recorddetails['profile_pic_max_width'] = CONST_PROFILE_IMG_DIM['dw'];
				$recorddetails['profile_pic_org_width'] = $profile_pic_size[0];
			}
			else
				$recorddetails['profile_pic_url'] = ($recorddetails['gender']==='F')?CONST_NOIMAGE_F_FILE:CONST_NOIMAGE_M_FILE;
			$recorddetails['remarks_view'] = nl2br(\eBizIndia\_esc($recorddetails['remarks'], true));
			
		}

	}

	$result[0]=$error;
	$result[1]['allow_detail_view'] = true; 
	$result[1]['is_admin'] = $_cu_role==='ADMIN'?true:false;
	$result[1]['can_edit'] = $can_edit;
	$result[1]['show_dnd'] = $show_others_dnd_status;
	$result[1]['cuid'] = $loggedindata[0]['id'];  // This is the auto id of the table users and not member
	$result[1]['record_details']=filterData($recorddetails,'mem_view',['loggedindata'=>$loggedindata[0],'cu_role'=>$_cu_role, 'memtypedet' => $memtypedet]);
	$result[1]['edit_restricted_fields']=$edit_restricted_fields;
	
	echo json_encode($result);

	exit;

}elseif(filter_has_var(INPUT_GET,'mode') && $_GET['mode']==='export'){
	if(strcasecmp($_cu_role, 'ADMIN')!==0){
		header('HTTP/1.0 403 Forbidden', true, 403);
		die;
	}

	$mem_export_fields = [
		'title'=>'Title', 
		'name'=>'Name',
		'email'=>'Email',
		'role'=>'Role',
		'mobile'=>'WhatsApp Number', 
		'mobile2'=>'2nd Mobile', 
	    'gender'=>'Gender', 
		'blood_grp'=>'Blood Group', 
		'dob'=>'Date Of Birth', 
		'annv'=>'Anniversary Date', 
		'designation'=>'Designation',
		'user_group_name'=>'User Group',
		'active'=>'Status',
		'remarks' => 'Admin Remarks',
	];


	$options=[];
	$options['filters']=[];
	if(filter_has_var(INPUT_GET, 'searchdata') && $_GET['searchdata']!=''){
		$searchdata=json_decode($_GET['searchdata'],true);
		if(is_array($searchdata) && !empty($searchdata)){
			$options['filters']=[];
			foreach($searchdata as $filter){
				$field=$filter['searchon'];

				if(array_key_exists('searchtype',$filter)){
					$type=$filter['searchtype'];

				}else{
					$type='';

				}

				if(array_key_exists('searchtext', $filter))
					$value=trim($filter['searchtext']);
				else
					$value='';

				$options['filters'][] = array('field'=>$field,'type'=>$type,'value'=>$value);
			}
		}
	}

	if(filter_has_var(INPUT_GET, 'sortdata') && $_GET['sortdata']!=''){
		$options['order_by']=[];
		$sortdata=json_decode($_GET['sortdata'],true);
		foreach($sortdata as $sort_param){
			$options['order_by'][]=array('field'=>$sort_param['sorton'],'type'=>$sort_param['sortorder']);
		}
	}

	$records=\eBizIndia\Member::getList($options);
	
	if($records===false){
		header('HTTP/1.0 500 Internal Server Error', true, 500);
		die;
	}else if(empty($records)){
		header('HTTP/1.0 204 No Content', true, 204);
		die;
	}else{
		
		ob_clean();
		header('Content-Description: File Transfer');
	    header('Content-Type: application/csv');
	    header("Content-Disposition: attachment; filename=users.csv");
	    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	    $fh = fopen('php://output', 'w');
	    if(!$fh){
	    	header('HTTP/1.0 500 Internal Server Error', true, 500);
	    	die;
	    }
	    $col_headers = array_values($mem_export_fields);
	    $data_row_flds = array_fill_keys(array_keys($mem_export_fields), '');
	    fputcsv($fh, $col_headers);
	    foreach ($records as $rec) {
			$data_row = array_intersect_key(array_replace($data_row_flds, $rec), $data_row_flds);
			$data_row['role'] = $rec['assigned_roles'][0]['role'];	
			$tmp = \eBizIndia\enums\Gender::tryFrom($data_row['gender']);
			$data_row['gender'] = !empty($tmp)?$tmp->label():'';		
			$data_row['active'] = $data_row['active']=='y'?'Active':'Inactive';	
			
			fputcsv($fh, array_values($data_row));
		}
		ob_flush();
		fclose($fh);
		die;
	}


}elseif(filter_has_var(INPUT_POST,'mode') && $_POST['mode']=='getList'){
	$result=array(0,array()); // error code and list html
	$show_dnd_status = true;
	$options=[];
	$options['filters']=[];

	$filterparams=array();
	$sortparams=array();

	$pno=(isset($_POST['pno']) && $_POST['pno']!='' && is_numeric($_POST['pno']))?$_POST['pno']:((isset($_GET['pno']) && $_GET['pno']!='' && is_numeric($_GET['pno']))?$_GET['pno']:1);
	$recsperpage=(isset($_POST['recsperpage']) && $_POST['recsperpage']!='' && is_numeric($_POST['recsperpage']))?$_POST['recsperpage']:((isset($_GET['recsperpage']) && $_GET['recsperpage']!='' && is_numeric($_GET['recsperpage']))?$_GET['recsperpage']:CONST_RECORDS_PER_PAGE);

	$filtertext = [];
	if(filter_has_var(INPUT_POST, 'searchdata') && $_POST['searchdata']!=''){
		$searchdata=json_decode($_POST['searchdata'],true);
		if(!is_array($searchdata)){
			$error=2; // invalid search parameters
		}else if(!empty($searchdata)){
			$options['filters']=[];
			foreach($searchdata as $filter){
				$field=$filter['searchon'];
				if($field=='is_active' && $_cu_role === 'REGULAR')
					continue; // active/inactive filtering is not allowed for REGULAR type members

				if(array_key_exists('searchtype',$filter)){
					$type=$filter['searchtype'];

				}else{
					$type='';

				}

				if(array_key_exists('searchtext', $filter))
					$value=trim($filter['searchtext']);
				else
					$value='';

				$options['filters'][] = array('field'=>$field,'type'=>$type,'value'=>$value);
    
				if($field=='mob')
					$fltr_text = 'Mobile number ';
				else if($field=='is_active')
					$fltr_text = 'Status ';
				else if($field=='user_group_id')
					$fltr_text = 'User group ';
				else 
					$fltr_text = ucfirst($field).' ';
				
				switch($type){
					case 'CONTAINS':
						$fltr_text .= 'has ';	break;
					case 'EQUAL':
						$fltr_text .= 'is ';	break;
					case 'STARTS_WITH':
						$fltr_text .= 'starts with ';	break;
					case 'AFTER':
						$fltr_text .= 'after ';	break;
				}

				$disp_value = !empty($filter['disp_text'])?$filter['disp_text']:$value;

				$filtertext[]='<span class="searched_elem"  >'.$fltr_text.'  <b>'.\eBizIndia\_esc($disp_value, true).'</b><span class="remove_filter" data-fld="'.$field.'"  >X</span> </span>';
			}
			$result[1]['filtertext'] = implode(' ',$filtertext);
		}
	}

	$tot_rec_options = [
		'fieldstofetch'=>['recordcount'],
		'filters' => [],
	];

	if($_cu_role === 'REGULAR'){
		// Allow only active records to be listed for REGULAR type members
		
		$options['filters'][] = $tot_rec_options['filters'][] = [
			'field' => 'active',
			'type' => 'EQUAL',
			'value' => 'y'
		]; 

		$show_dnd_status = false;
	}

	$options['fieldstofetch'] = ['recordcount'];

	// get total emp count
	$tot_rec_cnt = \eBizIndia\Member::getList($tot_rec_options); 
	$result[1]['tot_rec_cnt'] = $tot_rec_cnt[0]['recordcount'];
    
	// $recordcount=$usercls->getList($options);
	$recordcount = \eBizIndia\Member::getList($options);
	$recordcount = $recordcount[0]['recordcount'];
	$paginationdata=\eBizIndia\getPaginationData($recordcount,$recsperpage,$pno,CONST_PAGE_LINKS_COUNT);
	$result[1]['paginationdata']=$paginationdata;


	if($recordcount>0){
		$noofrecords=$paginationdata['recs_per_page'];
		$options['fieldstofetch'] = ['id', 'name', 'email', 'mobile', 'role', 'active', 'profile_pic', 'gender', 'user_acnt_id', 'user_group_name'];
		$options['page'] = $pno;
		$options['recs_per_page'] = $noofrecords;

		if(isset($_POST['sortdata']) && $_POST['sortdata']!=''){
			$options['order_by']=[];
			$sortdata=json_decode($_POST['sortdata'],true);
			foreach($sortdata as $sort_param){

				$options['order_by'][]=array('field'=>$sort_param['sorton'],'type'=>$sort_param['sortorder']);

				if($sort_param['sorton']=='batch_no')
					$options['order_by'][]=array('field'=>'name','type'=>'ASC');

			}
		}

		$records=\eBizIndia\Member::getList($options);
		
		if($records===false){
			$error=1; // db error
		}else{
			$result[1]['list']=$records;
		}
	}

	$result[0]=$error;
	$result[1]['reccount']=$recordcount;

	if($_POST['listformat']=='html'){
		$get_list_template_data=array();
		$get_list_template_data['mode']=$_POST['mode'];
		$get_list_template_data[$_POST['mode']]=array();
		$get_list_template_data[$_POST['mode']]['error']=$error;
		$get_list_template_data[$_POST['mode']]['records']=$records;
		$get_list_template_data[$_POST['mode']]['records_count']=count($records??[]);
		$get_list_template_data[$_POST['mode']]['self_edit']=$self_edit;
		$get_list_template_data[$_POST['mode']]['others_edit']=$others_edit;
		$get_list_template_data[$_POST['mode']]['cu_id']=$loggedindata[0]['id'];
		$get_list_template_data[$_POST['mode']]['show_dnd_status']=$show_dnd_status;
		$get_list_template_data[$_POST['mode']]['filtertext']=$result[1]['filtertext'];
		$get_list_template_data[$_POST['mode']]['filtercount']=count($filtertext);
		$get_list_template_data[$_POST['mode']]['tot_col_count']=count($records[0]??[])+1; // +1 for the action column

		$paginationdata['link_data']="";
		$paginationdata['page_link']='#';//"users.php#pno=<<page>>&sorton=".urlencode($options['order_by'][0]['field'])."&sortorder=".urlencode($options['order_by'][0]['type']);
		$get_list_template_data[$_POST['mode']]['pagination_html']=$page_renderer->fetchContent(CONST_THEMES_TEMPLATE_INCLUDE_PATH.'pagination-bar.tpl',$paginationdata);

		$get_list_template_data['logged_in_user']=$loggedindata[0];
		$get_list_template_data['country_code'] = CONST_COUNTRY_CODE;
		$get_list_template_data['cu_role'] = $_cu_role;
		$get_list_template_data['selftype'] = ''; //$memtypedet;


		$page_renderer->updateBodyTemplateData($get_list_template_data);
		$result[1]['list']=$page_renderer->fetchContent();

	}

	echo json_encode($result,JSON_HEX_TAG);
	exit;

}

$salutation_list=[];
foreach($_salutations as $val){

	$salutation_list[] = $val['text'];

}

$admin_menu_obj = new \eBizIndia\AdminMenu();
$user_roles = $admin_menu_obj->getUserRoles('',false);

$user_roles_for_select_list = [];
foreach($user_roles as $role){

	$user_roles_for_select_list[] = ['id'=>$role['role_id'], 'text'=>$role['role_name'], 'for'=>$role['role_for']];
}

$designations = [];
$cities = [];
$states = [];
$countries = [];
$work_type = [];

//$options = [];
//$options['filters'] = [];
//$options['filters'][] = ['field'=>'active', 'type'=>'EQUAL', 'value'=>'y'];

// Get user groups for dropdown
$user_groups_options = [];
$user_groups_options['filters'] = [];
$user_groups_options['filters'][] = ['field'=>'active', 'type'=>'EQUAL', 'value'=>'y'];
$user_groups_options['order_by'] = [];
$user_groups_options['order_by'][] = ['field'=>'name', 'type'=>'ASC'];
$user_groups_list = \eBizIndia\UserGroup::getList($user_groups_options);
$user_groups_for_select = [];
foreach($user_groups_list as $group){
	$user_groups_for_select[] = ['id'=>$group['id'], 'text'=>$group['name']];
}

$def_group_names = []; //array_values(array_unique(array_column($mapped_def_groups, 'grp_name')));

$user_levels = []; //$usercls->getUserLevelsList();

$dom_ready_data['users']=array(
								'salutation'=>json_encode($salutation_list),
								'user_roles_list'=>json_encode($user_roles_for_select_list),
								'user_levels'=>json_encode($user_levels),
								'user_groups_list'=>json_encode($user_groups_for_select),
								'designations' => json_encode($designations),
								'cities' => json_encode($cities),
								'states' => json_encode($states),
								'countries' => json_encode($countries),
								'work_type' => json_encode($work_type),
								'field_meta' => CONST_FIELD_META,
								
							);

$jscode .= "const country_code = \"".CONST_COUNTRY_CODE.'";'.PHP_EOL;
$jscode .= 'const default_list_filter = '.$default_list_filter.';'.PHP_EOL;

$additional_base_template_data = array(
										'page_title' => $page_title,
										'page_description' => $page_description,
										'template_type'=>$template_type,
										'dom_ready_code'=>\scriptProviderFuncs\getDomReadyJsCode($page,$dom_ready_data),
										'other_js_code'=>$jscode,
										'module_name' => $page
									);


$additional_body_template_data = ['user_roles'=>$user_roles,   'user_levels'=>$user_levels,  'pg_list'=>$pg_list,'salutation'=>$salutation_list,'default_pswd'=>$default_pswd, 'can_add'=>$can_add, 'country_code'=>CONST_COUNTRY_CODE, 'profile_pic_file_types'=>CONST_FIELD_META['profile_pic']['file_types'], 'allow_export'=>($_cu_role==='ADMIN'?true:false), 'blood_grps' => CONST_BLOOD_GRPS , 'field_meta' => CONST_FIELD_META, 'groups' => $groups,'selftype'=>$memtypedet, 'is_admin'=>$_cu_role=='ADMIN'?true:false];

$page_renderer->updateBodyTemplateData($additional_body_template_data);

$page_renderer->updateBaseTemplateData($additional_base_template_data);
$page_renderer->addCss(\scriptProviderFuncs\getCss($page));
$js_files=\scriptProviderFuncs\getJavascripts($page);
$page_renderer->addJavascript($js_files['BSH'],'BEFORE_SLASH_HEAD');
$page_renderer->addJavascript($js_files['BSB'],'BEFORE_SLASH_BODY');
$page_renderer->renderPage();

function filterData($data, $for='', $other_info = []){
	switch ($for) {
		case 'mem_view':
			$flds_to_remove = [];
			if($other_info['loggedindata']['profile_details']['id']!=$data['id'] && $other_info['cu_role']!='ADMIN'){
				$flds_to_remove['mobile'] = $flds_to_remove['mobile2'] = '';
			}
			if($other_info['cu_role']!='ADMIN'){
				$flds_to_remove['username'] = '';
				if($other_info['loggedindata']['profile_details']['id']!=$data['id']){ // not viewing self profile
					$flds_to_remove['joining_dt'] = $flds_to_remove['joining_dt_view'] = $flds_to_remove['paid_on_dt'] = $flds_to_remove['paid_on_dt_view'] = $flds_to_remove['remarks'] = $flds_to_remove['remarks_view']  = $flds_to_remove['role_view']  = $flds_to_remove['exp_date'] = '';

				}

			}
			$data = array_diff_key($data, $flds_to_remove);
			break;
	}
	return $data;
}
?>
