<?php
$page='access-privilege';
require_once 'inc.php';
$template_type='';
$page_title = "User's access privileges".CONST_TITLE_AFX;
$page_description = 'Access Privileges.';

$body_template_file = CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'access-privilege.tpl';
$body_template_data = array();
$page_renderer->registerBodyTemplate($body_template_file,$body_template_data);


$admin_menu_obj= new \eBizIndia\AdminMenu($conn);


if(filter_has_var(INPUT_GET, 'mode') && $_GET['mode']=='savePrivilege'){

	$result=array(0,array());
	$entries_made_available=trim($_POST['hdn_entries_made_available']);
	$entries_removed=trim($_POST['hdn_entries_removed']);
	$criterion_selected=trim($_POST['hdn_criterion_selected']);
	$privilege_for=trim($_POST['hdn_privilege_for']);

	if($entries_made_available=='' && $entries_removed==''){
		$result[0]=2;

	}else if($criterion_selected!='for_user' && $criterion_selected!='for_role'){
		$result[0]=3;

	}else if($privilege_for==''){
		$result[0]=4;

	}else{
        $conn = \eBizIndia\PDOConn::getInstance();
		if($criterion_selected=='for_role'){

		
			$conn->beginTransaction();

			if($entries_made_available!=''){
				$menu_perm_ids=explode(',',$entries_made_available);
				$action=1;

				$res=$admin_menu_obj->grantRevokePrivilegesOfARole($privilege_for,$action,$menu_perm_ids);
				if($res!==0){
					$result[0]=1;
					$conn->rollBack();	
				}
			}

			if($result[0]==0 && $entries_removed!=''){
				$menu_perm_ids=explode(',',$entries_removed);
				$action=0;

				$res=$admin_menu_obj->grantRevokePrivilegesOfARole($privilege_for,$action,$menu_perm_ids);
				if($res!==0){
					$result[0]=5;
					$conn->rollBack();
				}
			}

			if($result[0]==0)
				$conn->commit();	


		}elseif($criterion_selected=='for_user'){
			
			$conn->beginTransaction();

			if($entries_made_available!=''){
				$menu_perm_ids=explode(',',$entries_made_available);
				$action=1;

				$res=$admin_menu_obj->grantRevokePrivilegesOfAUser($privilege_for,$action,$menu_perm_ids);
				if($res!==0){
					$result[0]=1;
					$conn->rollBack();	
				}
			}

			if($result[0]==0 && $entries_removed!=''){
				$menu_perm_ids=explode(',',$entries_removed);
				$action=0;

				$res=$admin_menu_obj->grantRevokePrivilegesOfAUser($privilege_for,$action,$menu_perm_ids);
				if($res!==0){
					$result[0]=5;
					$conn->rollBack();
				}
			}

			if($result[0]==0)
				$conn->commit();



		}

	}	
	
	echo "<script type='text/javascript' >\n";
	echo "parent.access_privilege.handlePrivilegeSaveResponse(".json_encode($result).");\n";
	echo "</script>\n";
	exit;


}elseif(filter_has_var(INPUT_POST, 'mode') && $_POST['mode']=='getMenuListForUser'){
	$result=array(0,array());
	
	$options=[];
    $options['fieldstofetch'] = ['recordcount'];
	$options['filters']=[];
	$options['filters'][]=['field'=>'user_acnt_id','type'=>'EQUAL','value'=>$_POST['userid']];
	$userdetails=\eBizIndia\Member::getList($options);

	if($userdetails===false){
		$result[0]=1; // DB error
	}else if(empty($userdetails)){
		$result[0]=2; // user not found
	}else{
		$options = ['resourceonly'=>true];
		$menu_src = $admin_menu_obj->getUsersMenuList($_POST['userid'],$options);
		
		if($menu_src===false){
			$result[0]=1; // DB error

		}else{
			$result[1]['menuids']=array();
			$result[1]['menu_perms']=array();
			while($row=$menu_src->fetch(PDO::FETCH_ASSOC)){
				// print_r($row);
				if($row['showMenuInDisplay']=='1'){
					if(!in_array($row['menuid'], $result[1]['menuids'])){
						$result[1]['menuids'][]=(int)$row['menuid'];
						$result[1]['menu_perms'][$row['menuid']] = [];
					}
					
					if($row['menu_perm_id']!=null)
						$result[1]['menu_perms'][$row['menuid']][] = (int)$row['menu_perm_id'];
				}

			}
			$result[1]['menuids'] = array_unique($result[1]['menuids']);

		}

	}

	echo json_encode($result);
	exit;


}elseif(filter_has_var(INPUT_POST, 'mode') && $_POST['mode']=='getMenuListForRole'){
	$result=array(0,array());
	
	
	$options = ['recource_only'=>true];
//    \eBizIndia\_p($_POST);
	$menu_src = $admin_menu_obj->getRoleWiseMenuAssignments($_POST['role_id'],$options);
//	\eBizIndia\_p($menu_src);
	if($menu_src===false){
		$result[0]=1; // DB error

	}else{
		$result[1]['menuids']=array();
		$result[1]['menu_perms']=array();
		while($row=$menu_src->fetch(PDO::FETCH_ASSOC)){
			// print_r($row);
			if($row['showMenuInDisplay']=='1'){
				if(!in_array($row['menuid'], $result[1]['menuids'])){
					$result[1]['menuids'][]=(int)$row['menuid'];
					$result[1]['menu_perms'][$row['menuid']] = [];
				}
				
				if($row['menu_perm_id']!=null)
					$result[1]['menu_perms'][$row['menuid']][] = (int)$row['menu_perm_id'];
			}

			
		}

		$result[1]['menuids'] = array_unique($result[1]['menuids']);

	}

	

	echo json_encode($result);
	exit;


}elseif(filter_has_var(INPUT_GET, 'mode') && $_GET['mode']=='getUsersListForMenu'){
	
	$result=array(0,array());
	$userids=$admin_menu_obj->getUsersHavingAccessTo($_POST['menuid']);
	
	if($userids===false){
		$result[0]=1; // DB error
	}else{
		$result[1]['userids']=$userids;
	}

	echo json_encode($result);
	exit;


}elseif(filter_has_var(INPUT_GET, 'mode') && $_GET['mode']=='getMenuNUsersNRolesLists'){
	$result=array('users'=>[],'menu'=>[], 'roles'=>[]);
	$result['menu']=$admin_menu_obj->getMenuList('1','1',['visible_menus_only'=>true]);

	
	$options=[];
	$options['filters']=[];
	$options['fieldstofetch']=['id', 'user_acnt_id', 'name', 'mobile', 'user_acnt_status', 'role', 'role_id'];
	$options['resourceonly'] = true;
	$options['order_by'] = [['field'=>'name']];
	$userlist_src=\eBizIndia\Member::getList($options);
	
	$userids = [];
	$idx = -1;
	while($row=$userlist_src->fetch(PDO::FETCH_ASSOC)){
		
		if(!in_array($row['user_acnt_id'], $userids)){
			$idx++;
			$result['users'][$idx]=array_diff_key($row, ['role'=>'', 'role_id'=>'']); //array_intersect_key($row,$fields_to_list);
			$result['users'][$idx]['roles'] = [];
			$userids[] = $row['user_acnt_id'];
		}

		$result['users'][$idx]['roles'][] = $row['role_id'];

	}

	$result['roles'] = $admin_menu_obj->getUserRoles('',false); // get all roles irrespective of the status



	echo json_encode($result,JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS);	
	exit;
	
}
$additional_body_template_data=array(
										'menuslist'=>$result

									);



$additional_base_template_data = array(
										'page_title' => $page_title,
										'page_description' => $page_description,
										'template_type'=>$template_type,
										'dom_ready_code'=>\scriptProviderFuncs\getDomReadyJsCode($page),
										'other_js_code'=>'',
										'module_name' => $page
									);
									
									
$page_renderer->updateBodyTemplateData($additional_body_template_data);									 
$page_renderer->updateBaseTemplateData($additional_base_template_data);
$page_renderer->addCss(\scriptProviderFuncs\getCss($page));
$js_files=\scriptProviderFuncs\getJavascripts($page);
$page_renderer->addJavascript($js_files['BSH'],'BEFORE_SLASH_HEAD');
$page_renderer->addJavascript($js_files['BSB'],'BEFORE_SLASH_BODY');
$page_renderer->renderPage();

?>