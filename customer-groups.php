<?php
$page='customer-groups';
require_once 'inc.php';
$template_type='';
$page_title = 'Manage Customer Groups'.CONST_TITLE_AFX;
$page_description = 'One can manage the customer groups.';
$body_template_file = CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'customer-groups.tpl';
$body_template_data = array();
$page_renderer->registerBodyTemplate($body_template_file,$body_template_data);
$can_add = $can_edit = $can_delete = $can_view = false;
$_cu_role = $loggedindata[0]['profile_details']['assigned_roles'][0]['role'];
if (in_array('ALL', $allowed_menu_perms)) {
    $can_add = $can_edit = $can_delete = $can_view = true;
} else {
    if (in_array('ADD', $allowed_menu_perms)) {
        $can_add = true;
    }

    if (in_array('EDIT', $allowed_menu_perms)) {
        $can_edit = true;
    }

    if (in_array('DELETE', $allowed_menu_perms)) {
        $can_delete = true;
    }

    if (in_array('VIEW', $allowed_menu_perms)) {
        $can_view = true;
    }
}
$rec_fields = [
	'name'=>'', 
	'active' => '',
];

if(filter_has_var(INPUT_POST,'mode') && $_POST['mode']=='createrec'){
	$result=array('error_code'=>0,'message'=>[], 'elemid'=>array(), 'other_data'=>[]);
	
	if($can_add===false){
		$result['error_code']=403;
		$result['message']="Sorry, you are not authorised to perfom this action.";
	}else{

		$data=array();
		$customer_groups = trim($_POST['name']);
		
		// $other_data['field_meta'] = CONST_FIELD_META;

		
		if(empty($customer_groups)){
			$result['error_code'] = 2;
			$result['message'] = 'Customer Group is required.';
			$result['error_fields'][]="#add_form_field_name";
		} else {
			$result['other_data']['customer_groups_prev'] = $customer_groups;
			$customer_groups = \eBizIndia\trim_deep(\eBizIndia\striptags_deep(preg_split("/(\r?\n)+/", $customer_groups)));
			$invalid_customer_group = array_filter($customer_groups, function($grp){
				return mb_strlen($grp)>100;
			});
			$valid_customer_groups = array_filter($customer_groups, function($grp){
				return $grp!='';
			});
			$result['other_data']['customer_groups'] = $customer_groups;
			if(!empty($invalid_customer_group)){
				$result['error_code'] = 2;
				$result['message'] = 'One or more of the customer group values exceed the allowed number of characters.';
				$result['error_fields'][]="#add_form_field_name";
			}else if(empty($valid_customer_groups)){
				$result['error_code'] = 2;
				$result['message'] = 'Please enter one or more valid customer group values.';
				$result['error_fields'][]="#add_form_field_name";
			}else{
				$created_on = date('Y-m-d H:i:s');
				$ip = \eBizIndia\getRemoteIP();
				$data['created_on'] = $created_on;
				$data['created_by'] = $loggedindata[0]['id'];
				$data['created_from'] = $ip;
				try{
					$res = \eBizIndia\CustomerGroup::add($valid_customer_groups);
					if(empty($res)){
						throw new Exception('Error adding customer groups.');
					}
					$result['error_code'] = 0;
					$result['message'] = count($valid_customer_groups)>1?'The customer groups were added successfully.':'The customer group was added successfully.';
				}catch(\Exception $e){
					$last_error = \eBizIndia\PDOConn::getLastError();
					if($result['error_code']==0){
						$result['error_code']=1; // DB error
						$result['message']="The customer groups could not be added due to server error.";
					}
					$error_details_to_log['result'] = $result;
					\eBizIndia\ErrorHandler::logError($error_details_to_log, $e);
				}

			}

		}
	}


	$_SESSION['create_rec_result'] = $result;
	header("Location:?");
	exit;

}elseif(filter_has_var(INPUT_POST,'mode') && $_POST['mode']=='updaterec'){
	$result=array('error_code'=>0,'message'=>[],'other_data'=>[]);
	if($can_edit===false){
		$result['error_code']=403;
		$result['message']="Sorry, you are not authorised to update the customer groups.";
	}else {
		$data=array();
		$recordid=(int)$_POST['recordid']; 
		// data validation
		if($recordid == ''){
			$result['error_code']=2;
			$result['message'][]="Invalid customer group reference.";
		}else{
			$options= [];
			$options['filters'] = [
				['field'=>'id', 'type'=>'EQUAL', 'value'=> $recordid],
			];
			$recorddetails  = \eBizIndia\CustomerGroup::getList($options);
			if($recorddetails===false){
				$result['error_code']=1;
				$result['message'][]="Failed to verify the customer group details due to server error.";
				$result['error_fields'][]="#edit_form_field_name";
			}elseif(empty($recorddetails)){
				// Customer Group with this ID does not exist
				$result['error_code']=3;
				$result['message'][]="The customer group you are trying to modify was not found.";
				$result['error_fields'][]="#edit_form_field_name";
			}else{
				$edit_restricted_fields = [];
				$rec_fields = array_diff_key($rec_fields, array_fill_keys($edit_restricted_fields, '')); // removing the edit restricted fields from the list of fields
				$data = \eBizIndia\trim_deep(\eBizIndia\striptags_deep(array_intersect_key($_POST, $rec_fields)));
				if($data['name']==''){
					$result['error_code']=2;
					$result['message'][]="Customer group name is required.";
					$result['error_fields'][]="#edit_form_field_name";
				} elseif(mb_strlen($data['name'])>100){
					$result['error_code']=2;
					$result['message'][]="Customer group name exceeds the allowed number of characters.";
					$result['error_fields'][]="#edit_form_field_name";
				} elseif($data['active']!='y' && $data['active']!='n'){
					$result['error_code']=2;
					$result['message'][]="Please select a status for the customer group.";
					$result['error_fields'][]="input[name=status]:eq(0)";
				} else {
					$result['other_data']['post'] = $data;
					$data_to_update = [];
					foreach($rec_fields as $fld=>$val){
						if($data[$fld]!==$recorddetails[0][$fld]){
							$data_changed = true;
							$data_to_update[$fld] = $data[$fld];
							
						}
					}

					try{
						if(!empty($data_to_update)){
							// Initialize with a common success message and code
							
							if(!\eBizIndia\CustomerGroup::update($data_to_update, $recordid))
								throw new Exception('Error updating the customer group.');
							
							$result['error_code']=0;
							$result['message']='The changes have been saved.';
							
						}else{
							$result['error_code']=4;
							$result['message']='There were no changes to save.';
						}
					}catch(\Exception $e){
						$last_error = \eBizIndia\PDOConn::getLastError();
						$result['error_code']=1;
						if($last_error[1] == 1062){
							$result['message'] = "Process failed. A customer group with this name already exists.";
						}else{
							$result['message']="The customer group could not be updated due to server error.";
						}			
						$error_details_to_log['last_error'] = $last_error;
						$error_details_to_log['result'] = $result;
						\eBizIndia\ErrorHandler::logError($error_details_to_log, $e);
					}
				
				}
			}

		}

	}

	$_SESSION['update_rec_result']=$result;

	header("Location:?");
	exit;

}elseif(isset($_SESSION['update_rec_result']) && is_array($_SESSION['update_rec_result'])){
	header("Content-Type: text/html; charset=UTF-8");
	echo "<script type='text/javascript' >\n";
	echo "parent.customergroupfuncs.handleUpdateRecResponse(".json_encode($_SESSION['update_rec_result']).");\n";
	echo "</script>";
	unset($_SESSION['update_rec_result']);
	exit;

}elseif(isset($_SESSION['create_rec_result']) && is_array($_SESSION['create_rec_result'])){
	header("Content-Type: text/html; charset=UTF-8");
	echo "<script type='text/javascript' >\n";
	echo "parent.customergroupfuncs.handleAddRecResponse(".json_encode($_SESSION['create_rec_result']).");\n";
	echo "</script>";
	unset($_SESSION['create_rec_result']);
	exit;

}elseif(filter_has_var(INPUT_POST,'mode') && $_POST['mode']=='deleteCustomerGroup'){
	$result=array();
	$error=0; // no error
	if($can_delete===false){
		$result['error_code']=403;
		$result['message']="Sorry, you are not authorised to perfom this action.";
	}else if($_POST['rec_id']==''){
		$result['error_code']=2;
		$result['message']="The customer group ID reference was not found.";
	}else{
		if(\eBizIndia\CustomerGroup::delete([$_POST['rec_id']])){
			$result['error_code']=0;
			$result['message']="The customer group was deleted successfully.";
		}else{
			$last_error = \eBizIndia\PDOConn::getLastError();
			if($last_error[1]==1451 || $last_error[1]==1452 ){
				$result['error_code']=1;
				$result['message']="The customer group could not be deleted as it is in use in one or more customer profiles.";
			}else{
				$result['error_code']=1;
				$result['message']="The customer group could not be deleted due to server error.";
			}
		}
	}

	echo json_encode($result);

	exit;

}elseif(filter_has_var(INPUT_POST,'mode') && $_POST['mode']=='getRecordDetails'){
	$result=array();
	$error=0; // no error
	$can_edit = true;
	
	if($_POST['recordid']==''){
		$error=1; // Record ID missing

	}else{
		$options= [];
		$options['filters'] = [
			['field'=>'id', 'type'=>'EQUAL', 'value'=> $_POST['recordid']],
		];
		$recorddetails  = \eBizIndia\CustomerGroup::getList($options);

		if($recorddetails===false){
			$error=2; // db error
		}elseif(count($recorddetails)==0){
			$error=3; // Rec ID does not exist
		}else{
			$recorddetails=$recorddetails[0];
			$recorddetails['name_disp'] = \eBizIndia\_esc($recorddetails['name'], true);
			$edit_restricted_fields = [];
		}
	}

	$result[0]=$error;
	$result[1]['can_edit'] = $can_edit;
	$result[1]['cuid'] = $loggedindata[0]['id'];  // This is the auto id of the table users and not member
	$result[1]['record_details']=$recorddetails;
	$result[1]['edit_restricted_fields']=$edit_restricted_fields;
	
	echo json_encode($result);

	exit;

}elseif(filter_has_var(INPUT_POST,'mode') && $_POST['mode']=='getList'){
	$result=array(0,array()); // error code and list html
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

				if(array_key_exists('searchtype',$filter)){
					$type=$filter['searchtype'];

				}else{
					$type='';

				}

				if(array_key_exists('searchtext', $filter))
					$value= \eBizIndia\trim_deep($filter['searchtext']);
				else
					$value='';

				$options['filters'][] = array('field'=>$field,'type'=>$type,'value'=>$value);

				if($field=='name')
					$fltr_text = 'Group name ';
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
				

				$filtertext[]='<span class="searched_elem"  >'.$fltr_text.'  <b>'.\eBizIndia\_esc($value, true).'</b><span class="remove_filter" data-fld="'.$field.'"  >X</span> </span>';
			}
			$result[1]['filtertext'] = implode($filtertext);
		}
	}

	$tot_rec_options = [
		'fieldstofetch'=>['recordcount'],
		'filters' => [],
	];

	$options['fieldstofetch'] = ['recordcount'];

	// get total emp count
	$tot_rec_cnt = \eBizIndia\CustomerGroup::getList($tot_rec_options); 
	$result[1]['tot_rec_cnt'] = $tot_rec_cnt[0]['recordcount'];

	// $recordcount=$usercls->getList($options);
	$recordcount = \eBizIndia\CustomerGroup::getList($options);
	$recordcount = $recordcount[0]['recordcount'];
	$paginationdata=\eBizIndia\getPaginationData($recordcount,$recsperpage,$pno,CONST_PAGE_LINKS_COUNT);
	$result[1]['paginationdata']=$paginationdata;


	if($recordcount>0){
		$noofrecords=$paginationdata['recs_per_page'];
		unset($options['fieldstofetch']);
		$options['page'] = $pno;
		$options['recs_per_page'] = $noofrecords;

		if(isset($_POST['sortdata']) && $_POST['sortdata']!=''){
			$options['order_by']=[];
			$sortdata=json_decode($_POST['sortdata'],true);
			foreach($sortdata as $sort_param){

				$options['order_by'][]=array('field'=>$sort_param['sorton'],'type'=>$sort_param['sortorder']);

			}
		}

		$records=\eBizIndia\CustomerGroup::getList($options);
		
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
		$get_list_template_data[$_POST['mode']]['cu_id']=$loggedindata[0]['id'];
		$get_list_template_data[$_POST['mode']]['filtertext']=$result[1]['filtertext'];
		$get_list_template_data[$_POST['mode']]['filtercount']=count($filtertext);
		$get_list_template_data[$_POST['mode']]['tot_col_count']=count($records[0]??[])+1; // +1 for the action column

		$paginationdata['link_data']="";
		$paginationdata['page_link']='#';//"users.php#pno=<<page>>&sorton=".urlencode($options['order_by'][0]['field'])."&sortorder=".urlencode($options['order_by'][0]['type']);
		$get_list_template_data[$_POST['mode']]['pagination_html']=$page_renderer->fetchContent(CONST_THEMES_TEMPLATE_INCLUDE_PATH.'pagination-bar.tpl',$paginationdata);

		$get_list_template_data['logged_in_user']=$loggedindata[0];
        $get_list_template_data['can_edit'] = $can_edit;
        $get_list_template_data['can_delete'] = $can_delete;
		$page_renderer->updateBodyTemplateData($get_list_template_data);
		$result[1]['list']=$page_renderer->fetchContent();

	}

	echo json_encode($result,JSON_HEX_TAG);
	exit;

}

$dom_ready_data['customer-groups']=array(
								'field_meta' => CONST_FIELD_META,
							);
$jscode .= "const CAN_ADD = ".var_export($can_add,true).";\n";
$jscode .= "const CAN_EDIT = ".var_export($can_edit, true).";\n";
$jscode .= "const CAN_DELETE = ".var_export($can_delete, true).";\n";

$additional_base_template_data = array(
										'page_title' => $page_title,
										'page_description' => $page_description,
										'template_type'=>$template_type,
										'dom_ready_code'=>\scriptProviderFuncs\getDomReadyJsCode($page,$dom_ready_data),
										'other_js_code'=>$jscode,
										'module_name' => $page
									);


$additional_body_template_data = ['can_add'=>$can_add];

$page_renderer->updateBodyTemplateData($additional_body_template_data);

$page_renderer->updateBaseTemplateData($additional_base_template_data);
$page_renderer->addCss(\scriptProviderFuncs\getCss($page));
$js_files=\scriptProviderFuncs\getJavascripts($page);
$page_renderer->addJavascript($js_files['BSH'],'BEFORE_SLASH_HEAD');
$page_renderer->addJavascript($js_files['BSB'],'BEFORE_SLASH_BODY');
$page_renderer->renderPage();

?>