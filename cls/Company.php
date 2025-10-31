<?php
namespace eBizIndia;
class Company{
	private $comp_id;
	public function __construct(?int $comp_id=null){
		$this->comp_id = $comp_id;
	}
	public function getDetails($fields_to_fetch=[]){
		if(empty($this->comp_id))
			return false;
		$options = [];
		$options['filters'] = [
			[ 'field' => 'id', 'type' => '=', 'value' => $this->comp_id ]
		];
		if(!empty($fields_to_fetch))
			$options['fieldstofetch'] = $fields_to_fetch;
		return  self::getList($options);
	}
	public static function getList($options=[]){
		$data = [];
		$fields_mapper = $fields_mapper1 = [];
		$fields_mapper1['*'] = 'T1.*';
		$fields_mapper1['id']='T1.id';
		$fields_mapper1['membership_numb']='T1.membership_numb';
		$fields_mapper1['comp_name']="T1.comp_name";
		$fields_mapper1['comp_address_1']="T1.comp_address_1";
		$fields_mapper1['comp_address_2']="T1.comp_address_2";
		$fields_mapper1['comp_address_3']="T1.comp_address_3";
		$fields_mapper1['comp_state']="T1.comp_state";
		$fields_mapper1['comp_city']="T1.comp_city";
		$fields_mapper1['comp_pin']="T1.comp_pin";
		//$fields_mapper1['member_category']="T1.member_category";
		$fields_mapper1['mem_cat_id']="T1.mem_cat_id";
		$fields_mapper1['mem_cat_name']="T1.mem_cat_name";
		$fields_mapper1['mem_cat_annual_price']="T1.mem_cat_annual_price";
		$fields_mapper1['mem_cat_requires_renewal']="T1.mem_cat_requires_renewal";
		$fields_mapper1['mem_cat_renew_others']="T1.mem_cat_renew_others";
		$fields_mapper1['membership_type']="T1.membership_type";
		$fields_mapper1['comp_sector']="T1.comp_sector";
		$fields_mapper1['sector_id']="T1.sector_id";
		$fields_mapper1['business_details']="T1.business_details";
		$fields_mapper1['join_date']="T1.join_date";
		$fields_mapper1['expiry_date']="T1.expiry_date";
		$fields_mapper1['expiry_date_dmY']="DATE_FORMAT(T1.expiry_date, '%d-%m-%\Y')";
		$fields_mapper1['joining_fee']="T1.joining_fee";
		$fields_mapper1['membership_fee']="T1.membership_fee";
		$fields_mapper1['amount_paid']="T1.amount_paid";
		$fields_mapper1['payment_mode']="T1.payment_mode";
		$fields_mapper1['payment_status']="T1.payment_status";
		$fields_mapper1['payment_txn_ref']="T1.payment_txn_ref";
		$fields_mapper1['payment_instrument_type']="T1.payment_instrument_type";
		$fields_mapper1['payment_instrument']="T1.payment_instrument";
		$fields_mapper1['paid_on']="T1.paid_on";
		$fields_mapper1['pmt_id']="T1.pmt_id";
		$fields_mapper1['active']="T1.active";
		$fields_mapper1['pan']="T1.pan";
		$fields_mapper1['gstin']="T1.gstin";
		$fields_mapper1['trade_license_file']="T1.trade_license_file";
		$fields_mapper1['trade_license_file_name']="T1.trade_license_file_name";
		$fields_mapper1['fssai_file']="T1.fssai_file";
		$fields_mapper1['fssai_file_name']="T1.fssai_file_name";
		//$fields_mapper1['brand_name']="T1.brand_name";
		$fields_mapper1['brands']="T1.brands";
		$fields_mapper1['website']="T1.website";
		$fields_mapper1['annual_renewal_fee_custom']="T1.annual_renewal_fee_custom";
		$fields_mapper1['renewed_on']="T1.renewed_on";
		//$fields_mapper1['brand_id']="cb.id";
	    $fields_mapper['*']="cm.id as id, cm.membership_numb as membership_numb, cm.comp_name as comp_name, cm.comp_address_1, cm.comp_address_2, cm.comp_address_3, cm.comp_state, cm.comp_city, cm.comp_pin, cm.mem_cat_id, cat.cat_name as mem_cat_name, cat.requires_renewal as mem_cat_requires_renewal, cat.renew_others as mem_cat_renew_others, cm.sector_id, sec.sector as comp_sector, cm.business_details, cm.membership_type, cm.join_date, cm.expiry_date,DATE_FORMAT(cm.expiry_date, '%d-%m-%\Y') AS expiry_date_dmY, cm.joining_fee, cm.membership_fee, cm.membership_fee_gst_rate, cm.amount_paid, cm.payment_mode, cm.payment_txn_ref, cm.payment_instrument_type, cm.payment_instrument, cm.paid_on, cm.pmt_id, cm.active,cm.brands,cm.gstin,cm.pan,fssai_file,fssai_file_name,trade_license_file,trade_license_file_name,website, COALESCE(cm.annual_renewal_fee,'') as annual_renewal_fee_custom";
	    $fields_mapper['recordcount']='count(distinct cm.id)';
		$fields_mapper['id']='cm.id';
		$fields_mapper['membership_numb']='cm.membership_numb';
		$fields_mapper['comp_name']="cm.comp_name";
		$fields_mapper['comp_address_1']="COALESCE(cm.comp_address_1, '')";
		$fields_mapper['comp_address_2']="COALESCE(cm.comp_address_2,'')";
		$fields_mapper['comp_address_3']="COALESCE(cm.comp_address_3,'')";
		$fields_mapper['comp_state']="cm.comp_state";
		$fields_mapper['comp_city']="cm.comp_city";
		$fields_mapper['comp_pin']="cm.comp_pin";
		$fields_mapper['mem_cat_id']="cm.mem_cat_id";
		$fields_mapper['mem_cat_name']="cat.cat_name";
		$fields_mapper['mem_cat_joining_fee']="cat.joining_fee";
		$fields_mapper['mem_cat_annual_price']="cat.annual_price";
		$fields_mapper['mem_cat_lifetime_price']="cat.lifetime_price";
		$fields_mapper['mem_cat_requires_renewal']="cat.requires_renewal";
		$fields_mapper['mem_cat_renew_others']="cat.renew_others";
		$fields_mapper['membership_type']="cm.membership_type";
		$fields_mapper['sector_id']="cm.sector_id";
		$fields_mapper['comp_sector']="sec.sector";
		$fields_mapper['business_details']="cm.business_details";
		$fields_mapper['join_date']="cm.join_date";
		$fields_mapper['expiry_date']="cm.expiry_date";
		$fields_mapper['expiry_date_dmY']="DATE_FORMAT(cm.expiry_date, '%d-%m-%\Y')";
		$fields_mapper['joining_fee']='COALESCE(cm.joining_fee,"")';
		$fields_mapper['membership_fee']='COALESCE(cm.membership_fee,"")';
		$fields_mapper['membership_fee_gst_rate']='COALESCE(cm.membership_fee_gst_rate,"")';
		$fields_mapper['amount_paid']='cm.amount_paid';
		$fields_mapper['payment_mode']='COALESCE(cm.payment_mode,"")';
		$fields_mapper['payment_txn_ref']='COALESCE(cm.payment_txn_ref,"")';
		$fields_mapper['payment_instrument_type']='COALESCE(cm.payment_instrument_type,"")';
		$fields_mapper['payment_instrument']='COALESCE(cm.payment_instrument,"")';
		$fields_mapper['paid_on']='COALESCE(cm.paid_on,"")';
		$fields_mapper['pmt_id']='COALESCE(cm.pmt_id,"")';
		$fields_mapper['active']="cm.active";
		$fields_mapper['pan']="cm.pan";
		$fields_mapper['gstin']="cm.gstin";
		$fields_mapper1['trade_license_file']="cm.trade_license_file";
		$fields_mapper1['trade_license_file_name']="cm.trade_license_file_name";
		$fields_mapper1['fssai_file']="cm.fssai_file";
		$fields_mapper1['fssai_file_name']="cm.fssai_file_name";
		//$fields_mapper['brand_id']='cm.id';
		$fields_mapper['brands']='cm.brands';
		$fields_mapper['website']='cm.website';
		$fields_mapper['annual_renewal_fee_custom']='COALESCE(cm.annual_renewal_fee,"")';
		$fields_mapper['renewed_on']='COALESCE(cm.renewed_on,"")';
		$where_clause=[];
		$str_params_to_bind=[];
		$int_params_to_bind=[];
		if( array_key_exists('filters',$options) && is_array($options['filters']) ){
			$field_counter=0;
			foreach($options['filters'] as $filter){
				++$field_counter;
				$fld = $fields_mapper[$filter['field']];
				switch ($filter['field']) {
					case 'id':
					case 'sector_id':
					case 'brand_id':
						switch($filter['type']){
							case 'IN':
								if(is_array($filter['value'])){
									$place_holders=[];
									$k=0;
									foreach($filter['value'] as $val){
										$k++;
										$ph = ":whr{$field_counter}_{$k}_";
										$place_holders[]=$ph;
										$int_params_to_bind[$ph]=$val;
									}
									$where_clause[] = $fld.' in('.implode(',',$place_holders).') ';
								}
								break;
							case 'NOT_IN':
								if(is_array($filter['value'])){
									$place_holders=[];
									$k=0;
									foreach($filter['value'] as $val){
										$k++;
										$ph = ":whr{$field_counter}_{$k}_";
										$place_holders[]=$ph;
										$int_params_to_bind[$ph]=$val;
									}
									$where_clause[] = $fld.' not in('.implode(',',$place_holders).') ';
								}
								break;
							default:
								$val=(is_array($filter['value']))?$filter['value'][0]:$filter['value'];
								$ph = ":whr{$field_counter}_";
								$where_clause[] = $fld.' '.$filter['type'].' '.$ph;
								$int_params_to_bind[$ph]=$val;
						}
						break;
					case 'brands':
					case 'mem_cat_name':
					case 'comp_name':
					case 'comp_sector':
					case 'membership_numb':
					case 'mem_cat_requires_renewal':
						switch ($filter['type']) {
                            case 'IN':
                                if (is_array($filter['value'])) {
                                    $place_holders = [];
                                    $k = 0;
                                    foreach ($filter['value'] as $v) {
                                        $k++;
                                        $place_holders[] = ":whr" . $field_counter . "_{$k}_";
                                        $str_params_to_bind[":whr" . $field_counter . "_{$k}_"] = $v;
                                    }
                                    $where_clause[] = $fields_mapper[$filter['field']] . ' in(' . implode(',', $place_holders) . ') ';
                                }
                                break;
                            case 'CONTAINS':
                                $v = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $where_clause[] = $fields_mapper[$filter['field']] . ' like :whr' . $field_counter . '_';
                                $str_params_to_bind[':whr' . $field_counter . '_'] = '%' . $v . '%';
                                break;
                            case 'NOT_EQUAL':
                                $v = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $where_clause[] = ' ( '.$fields_mapper[$filter['field']] . ' IS NULL OR '.$fields_mapper[$filter['field']] . '!=:whr' . $field_counter . '_ ) ';
                                $str_params_to_bind[':whr' . $field_counter . '_'] = $v;
                                break;
                            default:
                                $v = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $where_clause[] = $fields_mapper[$filter['field']] . '=:whr' . $field_counter . '_';
                                $str_params_to_bind[':whr' . $field_counter . '_'] = $v;
                        }
                        break;
                    case 'expiry_date':
						$dt = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
						$fld = $fields_mapper[$filter['field']];
						switch ($filter['type']) {
							case 'BETWEEN':
								$dt1 = $filter['value'][0];
								$dt2 = $filter['value'][1];
								$where_clause[] = '( '.$fld . ' >= :whr' . $field_counter . '_jdt_1_ AND '.$fld . ' <= :whr' . $field_counter . '_jdt_2_ ) ';
								$str_params_to_bind[':whr' . $field_counter . '_jdt_1_'] = $dt1;
								$str_params_to_bind[':whr' . $field_counter . '_jdt_2_'] = $dt2;
								break;
							case 'AFTER':
								$where_clause[] = $fld . " > :whr" . $field_counter . "_jdt";
								$str_params_to_bind[':whr' . $field_counter . '_jdt'] = $dt;
								break;
							case 'AFTER_OR_EQUAL':
								$where_clause[] = $fld . " >= :whr" . $field_counter . "_jdt";
								$str_params_to_bind[':whr' . $field_counter . '_jdt'] = $dt;
								break;
							case 'BEFORE':
								$where_clause[] = $fld . " < :whr" . $field_counter . "_jdt";
								$str_params_to_bind[':whr' . $field_counter . '_jdt'] = $dt;
								break;
							case 'EQUAL':
							default:
								$where_clause[] = $fld . " = :whr" . $field_counter . "_jdt";
								$str_params_to_bind[':whr' . $field_counter . '_jdt'] = $dt;
								break;
						}
						break;    
					case 'active':
						$type = strtolower($filter['type']);
						if($type=='yes'){
							$val = 'y';
						}else if($type=='no'){
							$val = 'n';
						}else
							break;	
						$type = ' like ';
						$ph = ":whr{$field_counter}_";
						$where_clause[] = "$fld $type $ph";
						$str_params_to_bind[$ph]=$val;
						break;	
				}
			}
		}
		$select_string=$fields_mapper1['*'];
		$select_string_subquery=$fields_mapper['*'];
		if(array_key_exists('fieldstofetch', $options) && is_array($options['fieldstofetch'])){
			$fields_to_fetch_count=count($options['fieldstofetch']);
			if($fields_to_fetch_count>0){
				$selected_fields=array();
				if(in_array('recordcount', $options['fieldstofetch'])){
					$record_count=true;
				}else{
					if(!in_array('*',$options['fieldstofetch'])){
						if(!in_array('id',$options['fieldstofetch'])){ // This is required as the id is being used for table joining
							$options['fieldstofetch'][]='id';
							$fields_to_fetch_count+=1; // increment the count by 1 to include this column
						}
					}
				}
				for($i=0; $i<$fields_to_fetch_count; $i++){
					if(array_key_exists($options['fieldstofetch'][$i],$fields_mapper1)){
						$selected_fields[]=$fields_mapper1[$options['fieldstofetch'][$i]].(($options['fieldstofetch'][$i]!='*')?' as '.$options['fieldstofetch'][$i]:'');
					}
					if(array_key_exists($options['fieldstofetch'][$i],$fields_mapper)){
						$selected_fields_subquery[]=$fields_mapper[$options['fieldstofetch'][$i]].(($options['fieldstofetch'][$i]!='*')?' as '.$options['fieldstofetch'][$i]:'');
					}
				}
				if(count($selected_fields)>0){
					$select_string=implode(', ',$selected_fields);
				}
				if(count($selected_fields_subquery)>0){
					$select_string_subquery=implode(', ',$selected_fields_subquery);
				}
			}
		}
		$select_string_subquery=($record_count)?$select_string_subquery:'distinct '.$select_string_subquery;
		$group_by_clause='';
		if(array_key_exists('group_by', $options) && is_array($options['group_by'])){
			foreach ($options['group_by'] as $field) {
				if(preg_match("/^(cm|cb|cb1|sec|cat)\./",$fields_mapper[$field]))
					$group_by_clause.=", ".$fields_mapper[$field];
				else
					$group_by_clause.=", $field";
			}
			$group_by_clause=trim($group_by_clause,",");
			if($group_by_clause!=''){
				$group_by_clause=' GROUP BY '.$group_by_clause;
			}
		}
		$order_by_clause = $order_by_clause_outer = ''; // $order_by_clause_outer is required to preserver the subquery's order
		if(array_key_exists('order_by', $options) && is_array($options['order_by'])){
			foreach ($options['order_by'] as $order) {
				if(preg_match("/^(cm|cb|cb1|sec|cat)\./",$fields_mapper[$order['field']])){
					$order_by_clause.=", ".$fields_mapper[$order['field']];
					if(!$record_count){
						if(!preg_match("/,?\s*".str_replace('.', "\.", $fields_mapper[$order['field']])."/",$select_string_subquery))
							$select_string_subquery .= ", ".$fields_mapper[$order['field']]. ' as '.$order['field'];
						$order_by_clause_outer.=", ".$fields_mapper1[$order['field']];
					}
				}else if(array_key_exists($order['field'], $fields_mapper)){
					if(!preg_match("/\s*as\s*".$order['field']."/",$select_string_subquery))
						$select_string_subquery .= ", ".$fields_mapper[$order['field']].' as '.$order['field'];
					$order_by_clause.=", ".$order['field'];
					$order_by_clause_outer.=", ".$fields_mapper1[$order['field']];
				}else if(array_key_exists($order['field'], $fields_mapper1)){
					$order_by_clause_outer.=", ".$fields_mapper1[$order['field']];
				}
				if(array_key_exists('type', $order) && $order['type']=='DESC'){
					$order_by_clause.=' DESC';
					$order_by_clause_outer.=' DESC';
				}
			}
			$order_by_clause=trim($order_by_clause,",");
			$order_by_clause_outer=trim($order_by_clause_outer,",");
			if($order_by_clause!=''){
				$order_by_clause=' ORDER BY '.$order_by_clause;
			}
			if($order_by_clause_outer!=''){
				$order_by_clause_outer=' ORDER BY '.$order_by_clause_outer;
			}
			// user ID is a unique value across all the users so to maintain a unique order across queries with the same set of order by clauses we can include this field as the last field in the order by clause.
			if($order_by_clause!='' && !stristr($order_by_clause, 'cm.id')){
				$order_by_clause .= ', '.$fields_mapper['id'].' DESC ';
				$order_by_clause_outer .= ', '.$fields_mapper1['id']. ' DESC ';
			}
		}
		if(!$record_count && $order_by_clause==''){
			$order_by_clause=" ORDER BY cm.comp_name DESC, cm.id DESC ";
			if(!preg_match("/\s+as\s+comp_name/",$select_string_subquery)){
				$select_string_subquery .= ', '.$fields_mapper['comp_name'].' as comp_name';
				$select_string .= ', '.$fields_mapper1['comp_name'].' as comp_name';
			}
			if(!preg_match("/,?\s+cm\.id/",$select_string_subquery)){
				$select_string_subquery .= ', '.$fields_mapper['id'].' as id';
				$select_string .= ', '.$fields_mapper1['id'].' as id';
			}
			if($order_by_clause_outer == '')
				$order_by_clause_outer=" ORDER BY T1.comp_name DESC, T1.id DESC ";
		}
		$limit_clause='';
		if(array_key_exists('page', $options) && filter_var($options['page'],FILTER_VALIDATE_INT) && $options['page']>0 && array_key_exists('recs_per_page', $options) && filter_var($options['recs_per_page'],FILTER_VALIDATE_INT) && $options['recs_per_page']>0){
			$limit_clause="LIMIT ".( ($options['page']-1) * $options['recs_per_page'] ).", $options[recs_per_page] ";
		}
		$where_clause_string = '';
		if(!empty($where_clause))
			$where_clause_string = ' WHERE '.implode(' AND ', $where_clause);
		/*$brand_join = '';
		if(preg_match("/(cb1)\./","$select_string_subquery $where_clause_string $group_by_clause $order_by_clause"))
			$brand_join .= " LEFT JOIN ".CONST_TBL_PREFIX."brands cb1 ON cm.id = cb1.comp_id ";*/
		$sector_join = '';
		if(preg_match("/(sec)\./","$select_string_subquery $where_clause_string $group_by_clause $order_by_clause"))
			$sector_join .= " LEFT JOIN `".CONST_TBL_PREFIX."sectors` sec ON cm.sector_id = sec.id ";
		$sql="SELECT $select_string_subquery from `".CONST_TBL_PREFIX."company` as cm JOIN `".CONST_TBL_PREFIX."category_mast` as cat on cm.mem_cat_id=cat.id $sector_join $brand_join   $where_clause_string $group_by_clause $order_by_clause $limit_clause";
		if(empty($record_count)){
			$sql="SELECT $select_string from ($sql) as T1 ";
			//if(preg_match("/(cb)\./",$select_string))
				//$sql .= " LEFT JOIN `".CONST_TBL_PREFIX."brands` cb ON T1.id = cb.comp_id "; 
			$sql .= $order_by_clause_outer;
		}
		//echo $sql;die();
		$error_details_to_log = [];
		$error_details_to_log['function'] = __METHOD__;
		$error_details_to_log['sql'] = $sql;
		$error_details_to_log['str_params_to_bind'] = $str_params_to_bind;
		$error_details_to_log['int_params_to_bind'] = $int_params_to_bind;
		try{
			$pdo_stmt_obj = PDOConn::query($sql, $str_params_to_bind, $int_params_to_bind);
			if(array_key_exists('resourceonly', $options) && $options['resourceonly'])
				return $pdo_stmt_obj;
			$idx = -1;
			$user_id = '';
			$data = [];
			while($row=$pdo_stmt_obj->fetch(\PDO::FETCH_ASSOC)){
				/*if(!$record_count){
					if($user_id!==$row['id']){
						++$idx;
						$data[$idx]=array_diff_key($row,['brand_name'=>'', 'brand_id'=>'']);

						if(array_key_exists('brand', $row) || array_key_exists('brand_id', $row)){
							$data[$idx]['brands'] = [];
						}
						$user_id=$row['id'];
					}

					if(array_key_exists('brands', $data[$idx])){
						$data[$idx]['brands'][] = ['brand_name'=>$row['brand_name'],'brand_id'=>$row['brand_id']];
					}
				}else{*/
					$data[] = $row;
				//}
			}
			return $data;
		}catch(\Exception $e){var_dump($e);
			if(!is_a($e, '\PDOStatement'))
				ErrorHandler::logError($error_details_to_log,$e);
			else
				ErrorHandler::logError($error_details_to_log);
			return false;
		}
	}
	public function add(array $data){ 
		$str_data = [];
		if(empty($data))
			return false;
		$sql="INSERT INTO `".CONST_TBL_PREFIX."company` SET ";
		$values=array();
		foreach($data as $field=>$value){
			$key = ":$field";
			if($value==='')
				$values[]="`$field`=NULL";
			else{
				$values[]="`$field`=$key";
				$str_data[$key] = $value;
			}
		}
        $sql.=implode(',',$values);
        $sql.=$whereclause;
        $error_details_to_log = [];
		$error_details_to_log['at'] = date('Y-m-d H:i:s');
		$error_details_to_log['function'] = __METHOD__;
		$error_details_to_log['type'] = $type;
		$error_details_to_log['data'] = $data;
		$error_details_to_log['id'] = $id;
		$error_details_to_log['sql'] = $sql;
		try{	
			$stmt_obj = PDOConn::query($sql, $str_data);
			return PDOConn::lastInsertId();
		}catch(\Exception $e){
			if(!is_a($e, '\PDOStatement'))
				ErrorHandler::logError($error_details_to_log,$e);
			else
				ErrorHandler::logError($error_details_to_log);
			return false;
		}
	}
	public function update($data){ 
		$str_data = $int_data = [];
		if(empty($data) || empty($this->comp_id))
			return false;
        $int_data[':id'] = $this->comp_id;
		$sql="Update `".CONST_TBL_PREFIX."company` SET ";
		$values=array();
		foreach($data as $field=>$value){
			$key = ":$field";
			if($value==='')
				$values[]="`$field`=NULL";
			else{
				$values[]="`$field`=$key";
				$str_data[$key] = $value;
			}
		}
        $sql.=implode(',',$values);
		$sql.=" WHERE `id`=:id";
		//echo $sql;die();
        $error_details_to_log = [];
		$error_details_to_log['at'] = date('Y-m-d H:i:s');
		$error_details_to_log['function'] = __METHOD__;
		$error_details_to_log['type'] = $type;
		$error_details_to_log['data'] = $data;
		$error_details_to_log['id'] = $id;
		$error_details_to_log['sql'] = $sql;
		try{
			$stmt_obj = PDOConn::query($sql, $str_data, $int_data);
			if($stmt_obj===false)
				return false;
			$affetcedrows= $stmt_obj->rowCount();
			if($affetcedrows<=0)
				return null; // query did not fail but nothing was updated
			return true;
		}catch(\Exception $e){var_dump($e);
			if(!is_a($e, '\PDOStatement'))
				ErrorHandler::logError($error_details_to_log,$e);
			else
				ErrorHandler::logError($error_details_to_log);
			return false;
		}
	}
	public function validateRenewalDetails($data, $other_data=[]){
		$result['error_code'] = 0;
		return $result;
	}
	function sendRenewalAlertEmailToAuthorities($email_data, $recp){
		$payment_details = $email_data['payment_details'];
		$payment_details['renewed_on_dt'] = date('d-m-Y', $email_data['renewed_on']);
		$payment_details['renewed_at'] = date('g:i a', $email_data['renewed_on']);
		$payment_details['paid_on_dmy'] = date('d-m-Y', strtotime($payment_details['paid_on']));
		$join_renew_remarks = nl2br(\eBizIndia\_esc($payment_details['join_renew_remarks']??'', true));
		$html_msg = <<<EOF
<!DOCTYPE html> 
	<html>
		<head>
		</head> 
		<body>
			<p>Hi,</p>
			<p>The NRAI membership for the company <b>{$email_data['company']}</b> has been renewed on {$payment_details['renewed_on_dt']} at {$payment_details['renewed_at']} by {$email_data['logged_in_mem_name']} of {$email_data['logged_in_mem_comp']}. Here are the basic details:</p>
			<p>
			<b>Membership category:</b> {$email_data['mem_cat_name']}<br>
			<b>Membership valid till:</b> {$email_data['expiry_date']}<br><br>
			<b>Renewal fee:</b> {$payment_details['membership_fee']}<br>
			<b>Paid on:</b> {$payment_details['paid_on_dmy']}<br>
			<b>Mode of payment:</b> {$payment_details['payment_mode']}<br>
			<b>Instrument type:</b> {$payment_details['payment_instrument_type']}<br>
			<b>Instrument:</b> {$payment_details['payment_instrument']}<br>
			<b>Bank reference:</b> {$payment_details['payment_txn_ref']}<br><br>
			{$join_renew_remarks}
			</p>
			<p>Regards,<br>{$email_data['from_name']}</p>
		</body>
	</html>	
EOF;
		$subject = CONST_MAIL_SUBJECT_PREFIX." Membership renewed for ".$email_data['company'];
		$extra_data = [];
		if(!empty(CONST_EMAIL_OVERRIDE))
			$extra_data['recp'] = explode(',',CONST_EMAIL_OVERRIDE);
		else{
			$extra_data['cc'] = $recp['cc']??[];
			$extra_data['bcc'] = $recp['bcc']??[];
			$extra_data['recp'] = $recp['to']??'';
		}
		$extra_data['from'] = CONST_MAIL_SENDERS_EMAIL;
		$extra_data['from_name'] = CONST_MAIL_SENDERS_NAME;
		$data = [
			'subject' => $subject,
			'html_message' => $html_msg,
		];
		if(!empty($extra_data['recp'])){
			$mail = new Mailer(true, ['use_default'=>CONST_USE_SERVERS_DEFAULT_SMTP]); // Will use server's default email settings
			$mail->resetOverrideEmails(); // becuase the overide email is being set in the recp var above
			return $mail->sendEmail($data, $extra_data);
		}
		return false;
	}
	function sendRenewalEmailToMember($email_data, $recp){
		$html_msg = <<<EOF
<!DOCTYPE html> 
	<html>
		<head>
		</head> 
		<body>
			<p>Hi,</p>
			<p>Congratulations! <br>The NRAI membership for your company <b>{$email_data['company']}</b> has been renewed against the fee amount of Rs.{$email_data['payment_details']['membership_fee']}.
			</p>
			<p>The new membership expiry date is {$email_data['expiry_date']}.</p>
			
			<p>Regards,<br>{$email_data['from_name']}</p>
			
		</body>
	</html>	
EOF;
		$subject = CONST_MAIL_SUBJECT_PREFIX." Congratulations! Membership renewed for ".$email_data['company'];
		$extra_data = [];
		if(!empty(CONST_EMAIL_OVERRIDE))
			$extra_data['recp'] = explode(',',CONST_EMAIL_OVERRIDE);
		else{
			$extra_data['cc'] = $recp['cc']??[];
			$extra_data['bcc'] = $recp['bcc']??[];
			$extra_data['recp'] = $recp['to']??'';
		}
		$extra_data['from'] = CONST_MAIL_SENDERS_EMAIL;
		$extra_data['from_name'] = CONST_MAIL_SENDERS_NAME;
		$data = [
			'subject' => $subject,
			'html_message' => $html_msg,
		];
		if(!empty($extra_data['recp'])){
			$mail = new Mailer(true, ['use_default'=>CONST_USE_SERVERS_DEFAULT_SMTP]); // Will use server's default email settings
			$mail->resetOverrideEmails(); // becuase the overide email is being set in the recp var above
			return $mail->sendEmail($data, $extra_data);
		}
		return false;
	}


	function companyJoinExpUpdatedNotification($email_data, $recp){
		$r = print_r($recp, true);
		$msg = '';
		$value_msg = '<p>';
		if($email_data['joining_modified'] && $email_data['expiry_modified']){
			$msg = 'The joining date as well as the expiry date ';
			$value_msg .='Old Joining Date: '.$email_data['old_joining_dt'].'<br>New Joining Date: '.$email_data['new_joining_dt'].'<br>Old Expiry Date: '.$email_data['old_expiry_dt'].'<br>New Expiry Date: '.$email_data['new_expiry_dt'];
			$subject = CONST_MAIL_SUBJECT_PREFIX." The membership expiry and joining dates of ".$email_data['comp_name']. ' have been updated';
		}else if($email_data['joining_modified']){
			$msg = 'The joining date ';
			$value_msg .='Old Joining Date: '.$email_data['old_joining_dt'].'<br>New Joining Date: '.$email_data['new_joining_dt'];
			$subject = CONST_MAIL_SUBJECT_PREFIX." The joining date of ".$email_data['comp_name']. ' has been updated';
		}else if($email_data['expiry_modified']){
			$msg = 'The expiry date ';	
			$value_msg .='Old Expiry Date: '.$email_data['old_expiry_dt'].'<br>New Expiry Date: '.$email_data['new_expiry_dt'];
			$subject = CONST_MAIL_SUBJECT_PREFIX." The membership expiry date of ".$email_data['comp_name'].' has been updated';
		}
		$value_msg .= '</p>';
		if($msg=='')
			return null;

		$html_msg = <<<EOF
<!DOCTYPE html> 
	<html>
		<head>
		</head> 
		<body>
			<p>Hello,</p>
			<p>{$msg} of the company <strong>{$email_data['comp_name']}</strong> has been updated by {$email_data['name']} on {$email_data['updated_on']}, from the IP <a href="https://whois.domaintools.com/{$email_data['ip']}" >{$email_data['ip']}</a>.</p>
			{$value_msg}
			<p>
				<a href="{$email_data['comp_edit_url']}" >Click HERE</a> to view/edit the company's profile.
			</p>
			<p>Regards,<br>{$email_data['from_name']}</p>
			
		</body>
	</html>	
EOF;
		$extra_data = [];
		if(!empty(CONST_EMAIL_OVERRIDE)){
			$extra_data['recp'] = explode(',',CONST_EMAIL_OVERRIDE);
		}else{
			$extra_data['cc'] = $recp['cc']??[];
			$extra_data['bcc'] = $recp['bcc']??[];
			$extra_data['recp'] = $recp['to']??'';
		}
		$extra_data['from'] = CONST_MAIL_SENDERS_EMAIL;
		$extra_data['from_name'] = CONST_MAIL_SENDERS_NAME;

		$data = [
			'subject' => $subject,
			'html_message' => $html_msg,
		];

		if(!empty($extra_data['recp'])){
			$mail = new Mailer(true, ['use_default'=>CONST_USE_SERVERS_DEFAULT_SMTP]); // Will use server's default email settings
			$mail->resetOverrideEmails(); // becuase the overide email is being set in the recp var above
			return $mail->sendEmail($data, $extra_data);
		}

		return false;


	}

}