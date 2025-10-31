<?php
namespace eBizIndia;
class Member{
	
	protected $user_acnt_obj;

	public function __construct($user_acnt_obj=null){
		$this->user_acnt_obj = $user_acnt_obj;
	}

	public function getProfile($user_acnt_id){
		if(empty($user_acnt_id))
			return false;
		$options = [];
		$options['filters'] = [
			[ 'field' => 'user_acnt_id', 'type' => 'EQUAL', 'value' => $user_acnt_id ]
		];
		$profile = self::getList($options);
		return $profile;
	}

    public function validate($data, $mode='add', $other_data=[])
    {
        $result['error_code'] = 0;
        $restricted_fields = $other_data['edit_restricted_fields'] ?? [];
        $file_upload_errors = [
            0 => 'There is no error, the file uploaded with success',
            1 => 'The uploaded file exceeds the allowed max size of '.ini_get('upload_max_filesize'),
            2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
            3 => 'The uploaded file was only partially uploaded',
            4 => 'No file was uploaded',
            6 => 'Missing a temporary folder',
            7 => 'Failed to write file to disk.',
            8 => 'A PHP extension stopped the file upload.',
        ];

        if(!in_array('profile_pic', $restricted_fields) && $other_data['profile_pic']['error']>0 && $other_data['profile_pic']['error']!=4){
            $result['error_code']=2;
            $result['message'] = 'Process failed as the profile pic could not be uploaded.'.$file_upload_errors[$other_data['profile_pic']['error']];
            $result['error_fields'][] = '#add_form_field_profilepic';

        }else if(!in_array('profile_pic', $restricted_fields) && $other_data['profile_pic']['error']==0){
            $file_ext = strtolower(pathinfo($other_data['profile_pic']['name'], PATHINFO_EXTENSION));
            if(empty($file_ext) || !in_array($file_ext, CONST_FIELD_META['profile_pic']['file_types'])){
                $result['error_code']=2;
                $result['message']="The selected file is not among one of the allowed file types.";
                $result['error_fields'][] = '#add_form_field_profilepic';

            }else if(!in_array($other_data['profile_pic']['type'], CONST_MIME_TYPES[$file_ext]??[] )){
                $result['error_code']=2;
                $result['message']="The selected profile image is not a valid file type.";
                $result['error_fields'][] = '#add_form_field_profilepic';
            }
        }

        if($result['error_code'] == 0){
            if(!in_array('title', $restricted_fields) && $data['title'] == ''){
                $result['error_code']=2;
                $result['message'][]="Salutation is required.";
                $result['error_fields'][]="#add_form_field_title";
            }else if(!in_array('name', $restricted_fields) && $data['name'] == ''){
                $result['error_code']=2;
                $result['message'][]="Name is required.";
                $result['error_fields'][]="#add_form_field_name";
            }else if(!in_array('name', $restricted_fields) && !empty($other_data['field_meta']['name']['regex']) && !preg_match($other_data['field_meta']['name']['regex'], $data['name'])) {
                $result['error_code']=2;
                $result['message'][]="Name contains invalid characters.";
                $result['error_fields'][]="#add_form_field_name";
            }else if($mode=='reg' &&  !in_array('mobile', $restricted_fields) && $data['mobile']=='') {
                $result['error_code']=2;
                $result['message'][]="WhatsApp number is required.";
                $result['error_fields'][]="#add_form_field_mobile";
            }else if(!in_array('mobile', $restricted_fields) && !empty($other_data['field_meta']['mobile']['regex']) && $data['mobile']!='' && !preg_match($other_data['field_meta']['mobile']['regex'], $data['mobile'])) {
                $result['error_code']=2;
                $result['message'][]="WhatsApp number is invalid.";
                $result['error_fields'][]="#add_form_field_mobile";
            }else if(!in_array('mobile2', $restricted_fields) && !empty($other_data['field_meta']['mobile']['regex']) && $data['mobile2']!='' && !preg_match($other_data['field_meta']['mobile']['regex'], $data['mobile2'])) {
                $result['error_code']=2;
                $result['message'][]="Alternate mobile number is invalid.";
                $result['error_fields'][]="#add_form_field_mobile2";
            }else if($mode=='reg' &&  $data['gender']=='') {
                $result['error_code']=2;
                $result['message'][]="Gender is required.";
                $result['error_fields'][]="#add_form_field_gender_M";
            }else if($data['gender']!='' && !in_array($data['gender'], $other_data['gender']) ) {
                $result['error_code']=2;
                $result['message'][]="Gender is invalid.";
                $result['error_fields'][]="#add_form_field_gender_M";
            }else if(!in_array('user_group_id', $restricted_fields) && ($data['user_group_id'] ?? '') == '') {
                $result['error_code']=2;
                $result['message'][]="User group is required.";
                $result['error_fields'][]="#add_form_field_user_group_id";
            }else if(!in_array('user_group_id', $restricted_fields) && !empty($data['user_group_id']) && !in_array($data['user_group_id'], array_column($other_data['user_groups'] ?? [], 'id'))) {
                $result['error_code']=2;
                $result['message'][]="Selected user group is invalid.";
                $result['error_fields'][]="#add_form_field_user_group_id";
            }else if($mode==='add' && $data['password']==='') {
                $result['error_code']=2;
                $result['message'][]="Password is required.";
                $result['error_fields'][]="#add_form_field_password";
            }else if($data['password']!=='' && strlen($data['password'])<6) {
                $result['error_code']=2;
                $result['message'][]="Password must be at least 6 characters long.";
                $result['error_fields'][]="#add_form_field_password";
            }/*else if($data['blood_grp']!='' && !in_array($data['blood_grp'], $other_data['blood_grps']) ) {
                $result['error_code']=2;
                $result['message'][]="Blood group is invalid.";
                $result['error_fields'][]="#add_form_field_bloodgrp";
            }else if($data['dob']!='' && !isDateValid($data['dob'])){
                $result['error_code']=2;
                $result['message'][]="Date of birth is invalid.";
                $result['error_fields'][]="#add_form_field_dob_picker";
            }*/
        }
        return $result;
    }

    public static function getList(array $options = []): array|bool|\PDOStatement
    {
        $data = [];
        $fields_mapper = $fields_mapper1 = [];

        // Mapping fields to be selected from the database
        $fields_mapper1['*'] = 'T1.*, r.role_name as role, ur.role_id as role_id';
        $fields_mapper1['id'] = 'T1.id';
        $fields_mapper1['title'] = 'T1.title';
        $fields_mapper1['fname'] = 'T1.fname';
        $fields_mapper1['name'] = 'T1.name';
        $fields_mapper1['email'] = 'T1.email';
        $fields_mapper1['mobile'] = 'T1.mobile';
        $fields_mapper1['mobile2'] = 'T1.mobile2';
        $fields_mapper1['designation'] = 'T1.designation';
        $fields_mapper1['gender'] = 'T1.gender';
        $fields_mapper1['blood_grp'] = 'T1.blood_grp';
        $fields_mapper1['dob'] = 'T1.dob';
        $fields_mapper1['annv'] = 'T1.annv';
        $fields_mapper1['profile_pic'] = 'T1.profile_pic';
        $fields_mapper1['active'] = 'T1.active';
        $fields_mapper1['dnd'] = 'T1.dnd';
        $fields_mapper1['remarks'] = 'T1.remarks';
        $fields_mapper1['user_acnt_id'] = 'T1.user_acnt_id';
        $fields_mapper1['username'] = 'T1.username';
        $fields_mapper1['user_acnt_status'] = 'T1.user_acnt_status';
        $fields_mapper1['user_group_id'] = 'T1.user_group_id';
        $fields_mapper1['user_group_name'] = 'T1.user_group_name';
        $fields_mapper1['role'] = 'r.role_name';
        $fields_mapper1['role_id'] = 'ur.role_id';

        $fields_mapper['*'] = "mem.id as id, COALESCE(mem.title,'') as title, COALESCE(mem.fname,'') as fname, mem.name as name, mem.email as email, COALESCE(mem.mobile, '') as mobile, COALESCE(mem.mobile2, '') as mobile2, COALESCE(mem.gender, '') as gender, COALESCE(mem.blood_grp, '') as blood_grp, COALESCE(mem.dob, '') as dob, COALESCE(mem.annv, '') as annv, COALESCE(mem.designation,'') as designation, COALESCE(mem.profile_pic,'') as profile_pic, mem.active as active, mem.dnd as dnd, u.id as user_acnt_id, u.username as username, u.status as user_acnt_status, COALESCE(mem.remarks,'') as remarks, COALESCE(mem.user_group_id, 0) as user_group_id, COALESCE(ug.name, '') as user_group_name";

        $fields_mapper['recordcount'] = 'count(distinct(mem.id))';
        $fields_mapper['id'] = "mem.id";
        $fields_mapper['fname'] = 'COALESCE(mem.fname,"")';
        $fields_mapper['name'] = 'mem.name';
        $fields_mapper['email'] = 'mem.email';
        $fields_mapper['mobile'] = 'COALESCE(mem.mobile, "")';
        $fields_mapper['mobile2'] = 'COALESCE(mem.mobile2, "")';
        $fields_mapper['gender'] = 'COALESCE(mem.gender, "")';
        $fields_mapper['blood_grp'] = 'COALESCE(mem.blood_grp, "")';
        $fields_mapper['dob'] = 'COALESCE(mem.dob, "")';
        $fields_mapper['annv'] = 'COALESCE(mem.annv, "")';
        $fields_mapper['designation'] = 'COALESCE(mem.designation,"")';
        $fields_mapper['profile_pic'] = 'COALESCE(mem.profile_pic,"")';
        $fields_mapper['active'] = 'mem.active';
        $fields_mapper['dnd'] = 'mem.dnd';
        $fields_mapper['remarks'] = 'COALESCE(mem.remarks, "")';
        $fields_mapper['user_acnt_id'] = 'u.id';
        $fields_mapper['username'] = 'u.username';
        $fields_mapper['user_acnt_status'] = 'u.status';
        $fields_mapper['user_group_id'] = 'COALESCE(mem.user_group_id, 0)';
        $fields_mapper['user_group_name'] = 'COALESCE(ug.name, "")';

        $where_clause = [];
        $str_params_to_bind = [];
        $int_params_to_bind = [];

        // Handle filters in options
        if (array_key_exists('filters', $options) && is_array($options['filters'])) {
            $field_counter = 0;
            foreach ($options['filters'] as $filter) {
                ++$field_counter;
                switch ($filter['field']) {
                    case 'user_acnt_id':
                    case 'id':
                        switch ($filter['type']) {
                            case 'IN':
                                if (is_array($filter['value'])) {
                                    $place_holders = [];
                                    $k = 0;
                                    foreach ($filter['value'] as $userid) {
                                        $k++;
                                        $place_holders[] = ":whr" . $field_counter . "_userid_{$k}_";
                                        $int_params_to_bind[":whr" . $field_counter . "_userid_{$k}_"] = $userid;
                                    }
                                    $where_clause[] = $fields_mapper[$filter['field']] . ' in(' . implode(',', $place_holders) . ') ';
                                }
                                break;
                            case 'NOT_IN':
                                if (is_array($filter['value'])) {
                                    $place_holders = [];
                                    $k = 0;
                                    foreach ($filter['value'] as $userid) {
                                        $k++;
                                        $place_holders[] = ":whr" . $field_counter . "_userid_{$k}_";
                                        $int_params_to_bind[":whr" . $field_counter . "_userid_{$k}_"] = $userid;
                                    }
                                    $where_clause[] = $fields_mapper[$filter['field']] . ' not in(' . implode(',', $place_holders) . ') ';
                                }
                                break;
                            case 'NOT_EQUAL':
                                $userid = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $where_clause[] = $fields_mapper[$filter['field']] . '!=:whr' . $field_counter . '_userid';
                                $int_params_to_bind[':whr' . $field_counter . '_userid'] = $userid;
                                break;
                            default:
                                $userid = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $where_clause[] = $fields_mapper[$filter['field']] . '=:whr' . $field_counter . '_userid';
                                $int_params_to_bind[':whr' . $field_counter . '_userid'] = $userid;
                        }
                        break;
                    case 'role_id':
                        switch ($filter['type']) {
                            case 'IN':
                                if (is_array($filter['value'])) {
                                    $place_holders = [];
                                    $k = 0;
                                    foreach ($filter['value'] as $roleid) {
                                        $k++;
                                        $place_holders[] = ":whr" . $field_counter . "_roleid_{$k}_";
                                        $int_params_to_bind[":whr" . $field_counter . "_roleid_{$k}_"] = $roleid;
                                    }
                                    $where_clause[] = ' ur1.role_id in(' . implode(',', $place_holders) . ') ';
                                }
                                break;
                            case 'NOT_IN':
                                if (is_array($filter['value'])) {
                                    $place_holders = [];
                                    $k = 0;
                                    foreach ($filter['value'] as $roleid) {
                                        $k++;
                                        $place_holders[] = ":whr" . $field_counter . "_roleid_{$k}_";
                                        $int_params_to_bind[":whr" . $field_counter . "_roleid_{$k}_"] = $roleid;
                                    }
                                    $where_clause[] = ' ur1.role_id not in(' . implode(',', $place_holders) . ') ';
                                }
                                break;
                            case 'NOT_EQUAL':
                                $roleid = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $where_clause[] = ' ur1.role_id!=:whr' . $field_counter . '_roleid';
                                $int_params_to_bind[':whr' . $field_counter . '_roleid'] = $roleid;
                                break;
                            default:
                                $roleid = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $where_clause[] = ' ur1.role_id=:whr' . $field_counter . '_roleid';
                                $int_params_to_bind[':whr' . $field_counter . '_roleid'] = $roleid;
                        }
                        break;
                    case 'user_group_id':
                        switch ($filter['type']) {
                            case 'IN':
                                if (is_array($filter['value'])) {
                                    $place_holders = [];
                                    $k = 0;
                                    foreach ($filter['value'] as $groupid) {
                                        $k++;
                                        $place_holders[] = ":whr" . $field_counter . "_groupid_{$k}_";
                                        $int_params_to_bind[":whr" . $field_counter . "_groupid_{$k}_"] = $groupid;
                                    }
                                    $where_clause[] = $fields_mapper[$filter['field']] . ' in(' . implode(',', $place_holders) . ') ';
                                }
                                break;
                            case 'NOT_IN':
                                if (is_array($filter['value'])) {
                                    $place_holders = [];
                                    $k = 0;
                                    foreach ($filter['value'] as $groupid) {
                                        $k++;
                                        $place_holders[] = ":whr" . $field_counter . "_groupid_{$k}_";
                                        $int_params_to_bind[":whr" . $field_counter . "_groupid_{$k}_"] = $groupid;
                                    }
                                    $where_clause[] = $fields_mapper[$filter['field']] . ' not in(' . implode(',', $place_holders) . ') ';
                                }
                                break;
                            case 'NOT_EQUAL':
                                $groupid = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $where_clause[] = $fields_mapper[$filter['field']] . '!=:whr' . $field_counter . '_groupid';
                                $int_params_to_bind[':whr' . $field_counter . '_groupid'] = $groupid;
                                break;
                            default:
                                $groupid = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $where_clause[] = $fields_mapper[$filter['field']] . '=:whr' . $field_counter . '_groupid';
                                $int_params_to_bind[':whr' . $field_counter . '_groupid'] = $groupid;
                        }
                        break;

                    case 'blood_grp':
                    case 'email':
                    case 'name':
                    case 'mobile':
                    case 'mobile2':
                        $nm = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                        $fld = in_array($filter['field'], ['blood_grp', 'mobile', 'mobile2', 'residence_city', 'residence_country']) ? $filter['field'] : $fields_mapper[$filter['field']];
                        if($filter['type']=='IS_EMPTY'){
                            $where_clause[] = " ( $fld is NULL or $fld='' ) ";
                        }else if($filter['type']=='NOT_EMPTY'){
                            $where_clause[] = " ( $fld is not NULL and $fld!='' ) ";
                        }else{
                            $where_clause[] = $fld . " like :whr" . $field_counter . "_nm";
                            switch ($filter['type']) {
                                case 'CONTAINS':
                                    $str_params_to_bind[':whr' . $field_counter . '_nm'] = "%$nm%";
                                    break;
                                case 'STARTS_WITH':
                                    $str_params_to_bind[':whr' . $field_counter . '_nm'] = "$nm%";
                                    break;
                                case 'ENDS_WITH':
                                    $str_params_to_bind[':whr' . $field_counter . '_nm'] = "%$nm";
                                    break;
                                case 'EQUAL':
                                default:
                                    $str_params_to_bind[':whr' . $field_counter . '_nm'] = "$nm";
                                    break;
                            }

                        }

                        break;
                    case 'mob': // both mobile fields
                        $mob = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                        $where_clause[] = " ( mem.mobile like :whr" . $field_counter . "_mob OR mem.mobile2 like :whr" . $field_counter . "_mob )";
                        switch ($filter['type']) {
                            case 'CONTAINS':
                                $str_params_to_bind[':whr' . $field_counter . '_mob'] = "%$mob%";
                                break;
                            case 'STARTS_WITH':
                                $str_params_to_bind[':whr' . $field_counter . '_mob'] = "$mob%";
                                break;
                            case 'ENDS_WITH':
                                $str_params_to_bind[':whr' . $field_counter . '_mob'] = "%$mob";
                                break;
                            case 'EQUAL':
                            default:
                                $str_params_to_bind[':whr' . $field_counter . '_mob'] = "$mob";
                                break;
                        }
                        break;
                    case 'role':
                        $role = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                        $where_clause[] = " r1.role_name like :whr" . $field_counter . "_role";
                        switch ($filter['type']) {
                            case 'CONTAINS':
                                $str_params_to_bind[':whr' . $field_counter . '_role'] = "%$role%";
                                break;
                            case 'STARTS_WITH':
                                $str_params_to_bind[':whr' . $field_counter . '_role'] = "$role%";
                                break;
                            case 'ENDS_WITH':
                                $str_params_to_bind[':whr' . $field_counter . '_role'] = "%$role";
                                break;
                            case 'EQUAL':
                            default:
                                $str_params_to_bind[':whr' . $field_counter . '_role'] = "$role";
                                break;
                        }
                        break;
                    case 'dnd':
                    case 'active':
                        $status = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                        switch ($filter['type']) {
                            case 'NOT_EQUAL':
                                $where_clause[] = $fields_mapper[$filter['field']] . ' !=:whr' . $field_counter . '_active';
                                $str_params_to_bind[':whr' . $field_counter . '_active'] = $status;
                                break;
                            default:
                                $where_clause[] = $fields_mapper[$filter['field']] . '=:whr' . $field_counter . '_active';
                                $str_params_to_bind[':whr' . $field_counter . '_active'] = $status;
                        }
                        break;
                    case 'is_active':
                        $status = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                        if($status=='n'){
                            $where_clause[] = ' ( '.$fields_mapper['active'] . ' ="n"  ) ';
                        }else{
                            $where_clause[] = ' ( '.$fields_mapper['active'] . ' ="y"  ) ';
                        }
                        break;
                    case 'active_dndno_or_id':
                        $id_clause = '';
                        if (is_array($filter['value'])) {
                            $place_holders = [];
                            $k = 0;
                            foreach ($filter['value'] as $userid) {
                                $k++;
                                $place_holders[] = ":whr" . $field_counter . "_userid_{$k}_";
                                $int_params_to_bind[":whr" . $field_counter . "_userid_{$k}_"] = $userid;
                                $id_clause = ' OR ' . $fields_mapper['id'] . ' in(' . implode(',', $place_holders) . ')';
                            }
                        }
                        $where_clause[] = ' ( (' . $fields_mapper['active'] . '="y" AND ' . $fields_mapper['dnd'] . '="n" ) ' . $id_clause . ' ) ';
                        break;
                }
            }
        }

        $select_string = $fields_mapper1['*'];
        $select_string_subquery = $fields_mapper['*'];

        if (array_key_exists('fieldstofetch', $options) && is_array($options['fieldstofetch'])) {
            $fields_to_fetch_count = count($options['fieldstofetch']);

            if ($fields_to_fetch_count > 0) {
                $selected_fields = [];

                if (in_array('recordcount', $options['fieldstofetch'])) {
                    $record_count = true;
                } else {
                    if (!in_array('*', $options['fieldstofetch'])) {
                        if (!in_array('id', $options['fieldstofetch'])) { // This is required as the id is being used for table joining
                            $options['fieldstofetch'][] = 'id';
                            $fields_to_fetch_count += 1; // increment the count by 1 to include this column
                        }
                    }
                }

                for ($i = 0; $i < $fields_to_fetch_count; $i++) {
                    if (array_key_exists($options['fieldstofetch'][$i], $fields_mapper1)) {
                        $selected_fields[] = $fields_mapper1[$options['fieldstofetch'][$i]] . (($options['fieldstofetch'][$i] != '*') ? ' as ' . $options['fieldstofetch'][$i] : '');
                    }

                    if (array_key_exists($options['fieldstofetch'][$i], $fields_mapper)) {
                        $selected_fields_subquery[] = $fields_mapper[$options['fieldstofetch'][$i]] . (($options['fieldstofetch'][$i] != '*') ? ' as ' . $options['fieldstofetch'][$i] : '');
                    }
                }

                if (count($selected_fields) > 0) {
                    $select_string = implode(', ', $selected_fields);
                }

                if (count($selected_fields_subquery) > 0) {
                    $select_string_subquery = implode(', ', $selected_fields_subquery);
                }
            }
        }

        $select_string_subquery = ($record_count) ? $select_string_subquery : 'distinct ' . $select_string_subquery;
        $group_by_clause = '';
        if (array_key_exists('group_by', $options) && is_array($options['group_by'])) {
            foreach ($options['group_by'] as $field) {
                if (preg_match("/^(mem|u|r|ur|r1|ur1|g|mg|g1|mg1|ug)\./", $fields_mapper[$field])) {
                    $group_by_clause .= ", " . $fields_mapper[$field];
                } else {
                    $group_by_clause .= ", $field";
                }
            }

            $group_by_clause = trim($group_by_clause, ",");
            if ($group_by_clause != '') {
                $group_by_clause = ' GROUP BY ' . $group_by_clause;
            }
        }

        $order_by_clause = $order_by_clause_outer = ''; // $order_by_clause_outer is required to preserver the subquery's order

        if (array_key_exists('order_by', $options) && is_array($options['order_by'])) {
            foreach ($options['order_by'] as $order) {
                if (preg_match("/^(mem|u|r|ur|r1|ur1|g|mg|g1|mg1|ug)\./", $fields_mapper[$order['field']])) {
                    $order_by_clause .= ", " . $fields_mapper[$order['field']];

                    if (!$record_count) {
                        if (!preg_match("/,?\s*" . str_replace('.', "\.", $fields_mapper[$order['field']]) . "/", $select_string_subquery)) {
                            $select_string_subquery .= ", " . $fields_mapper[$order['field']] . ' as ' . $order['field'];
                        }

                        $order_by_clause_outer .= ", " . $fields_mapper1[$order['field']];
                    }
                } else if (array_key_exists($order['field'], $fields_mapper)) {
                    if (!preg_match("/\s*as\s*" . $order['field'] . "/", $select_string_subquery)) {
                        $select_string_subquery .= ", " . $fields_mapper[$order['field']] . ' as ' . $order['field'];
                    }

                    $order_by_clause .= ", " . $order['field'];
                    $order_by_clause_outer .= ", " . $fields_mapper1[$order['field']];
                } else if (array_key_exists($order['field'], $fields_mapper1)) {
                    $order_by_clause_outer .= ", " . $fields_mapper1[$order['field']];
                }

                if (array_key_exists('type', $order) && $order['type'] == 'DESC') {
                    $order_by_clause .= ' DESC';
                    $order_by_clause_outer .= ' DESC';
                }
            }

            $order_by_clause = trim($order_by_clause, ",");
            $order_by_clause_outer = trim($order_by_clause_outer, ",");
            if ($order_by_clause != '') {
                $order_by_clause = ' ORDER BY ' . $order_by_clause;
            }

            if ($order_by_clause_outer != '') {
                $order_by_clause_outer = ' ORDER BY ' . $order_by_clause_outer;
            }

            // user ID is a unique value across all the users so to maintain a unique order across queries with the same set of order by clauses we can include this field as the last field in the order by clause.
            if ($order_by_clause != '' && !stristr($order_by_clause, 'mem.id')) {
                $order_by_clause .= ', ' . $fields_mapper['id'] . ' DESC ';
                $order_by_clause_outer .= ', ' . $fields_mapper1['id'] . ' DESC ';
            }
        }

        if (!$record_count && $order_by_clause == '') {
            $order_by_clause = " ORDER BY mem.name DESC, mem.id DESC ";

            if (!preg_match("/\s+as\s+name/", $select_string_subquery)) {
                $select_string_subquery .= ', ' . $fields_mapper['name'] . ' as name';
                $select_string .= ', ' . $fields_mapper1['name'] . ' as name';
            }
            if (!preg_match("/,?\s+mem\.id/", $select_string_subquery)) {
                $select_string_subquery .= ', ' . $fields_mapper['id'] . ' as id';
                $select_string .= ', ' . $fields_mapper1['id'] . ' as id';
            }

            if ($order_by_clause_outer == '') {
                $order_by_clause_outer = " ORDER BY T1.name DESC, T1.id DESC ";
            }
        }

        $limit_clause = '';

        if (array_key_exists('page', $options) && filter_var($options['page'], FILTER_VALIDATE_INT) && $options['page'] > 0 && array_key_exists('recs_per_page', $options) && filter_var($options['recs_per_page'], FILTER_VALIDATE_INT) && $options['recs_per_page'] > 0) {
            $limit_clause = "LIMIT " . (($options['page'] - 1) * $options['recs_per_page']) . ", $options[recs_per_page] ";
        }

        $where_clause_string = '';
        if (!empty($where_clause)) {
            $where_clause_string = ' WHERE ' . implode(' AND ', $where_clause);
        }

        // Adding joins for role, group, and user_groups tables
        $role_join = '';
        if (preg_match("/(r1|ur1)\./", "$select_string_subquery $where_clause_string $group_by_clause $order_by_clause")) {
            $role_join .= " JOIN `".CONST_TBL_PREFIX."user_roles` ur1 ON u.id = ur1.user_id JOIN  `".CONST_TBL_PREFIX."roles` r1 ON r1.role_id=ur1.role_id ";
        }

        $user_group_join = '';
        if (preg_match("/(ug)\./", "$select_string_subquery $where_clause_string $group_by_clause $order_by_clause") ||
            str_contains($select_string_subquery, 'user_group_name')) {
            $user_group_join = " LEFT JOIN `".CONST_TBL_PREFIX."user_groups` ug ON mem.user_group_id = ug.id ";
        }

        // Constructing the SQL query
        $sql = "SELECT $select_string_subquery from `".CONST_TBL_PREFIX."members` as mem 
				LEFT JOIN `".CONST_TBL_PREFIX."users` as u ON mem.id=u.profile_id and u.profile_type='member' 
				$user_group_join $role_join $where_clause_string $group_by_clause $order_by_clause $limit_clause";

        if (empty($record_count)) {
            $sql = "SELECT $select_string from ($sql) as T1 ";
            if (preg_match("/(r|ur)\./", $select_string)) {
                $sql .= " LEFT JOIN `".CONST_TBL_PREFIX."user_roles` ur ON T1.user_acnt_id = ur.user_id JOIN  `".CONST_TBL_PREFIX."roles` r ON r.role_id=ur.role_id";
            }

            $sql .= $order_by_clause_outer;
        }

        $error_details_to_log = [];
        $error_details_to_log['function'] = __METHOD__;
        $error_details_to_log['sql'] = $sql;
        $error_details_to_log['str_params_to_bind'] = $str_params_to_bind;
        $error_details_to_log['int_params_to_bind'] = $int_params_to_bind;

        try {
            $pdo_stmt_obj = PDOConn::query($sql, $str_params_to_bind, $int_params_to_bind);

            if (array_key_exists('resourceonly', $options) && $options['resourceonly']) {
                return $pdo_stmt_obj;
            }

            $idx = -1;
            $user_id = '';
            $data = [];

            while ($row = $pdo_stmt_obj->fetch(\PDO::FETCH_ASSOC)) {
                if (!$record_count) {
                    if ($user_id !== $row['id']) {
                        ++$idx;
                        $data[$idx] = array_diff_key($row, ['role' => '', 'role_id' => '']);

                        if (array_key_exists('role', $row) || array_key_exists('role_id', $row)) {
                            $data[$idx]['assigned_roles'] = [];
                            $data[$idx]['role_names'] = [];
                        }

                        $user_id = $row['id'];
                    }

                    if (array_key_exists('assigned_roles', $data[$idx])) {
                        $data[$idx]['assigned_roles'][] = ['role' => $row['role'], 'role_id' => $row['role_id']];
                        $data[$idx]['role_names'][] = $row['role'];
                    }

                } else {
                    $data[] = $row;
                }
            }
            return $data;
        } catch (\Exception $e) {
            if (!is_a($e, '\PDOStatement')) {
                ErrorHandler::logError($error_details_to_log, $e);
            } else {
                ErrorHandler::logError($error_details_to_log);
            }
            return false;
        }
    }
	


	function saveDetails($data, $id =''){
		// If comp_id is in the data but is empty, remove it
	    if(isset($data['comp_id']) && empty($data['comp_id'])) {
	        unset($data['comp_id']);
	    }
		$str_data = $int_data = [];
		$table = '`'.CONST_TBL_PREFIX . 'members`';
		if(is_array($id) && !empty($id)){
			
			$type='update';
			$sql="UPDATE $table SET ";
			$place_holders = [];
			$id_count = count($id);
			for ($i=0; $i < $id_count; $i++) { 
				$key = ":id_{$i}_";
				$place_holders[] = $key;
				$int_data[$key] = $id[$i];
			}
			$whereclause=" WHERE `id` IN (".implode(",", $place_holders).")";
		}else if($id!=''){ 
			// updating user details
			$type='update';
			$sql="UPDATE $table SET ";
			$int_data[':id'] = $id;
			$whereclause=" WHERE `id`=:id";

		}else{ 
			// Inserting new user
			$type='insert';
			$sql="INSERT INTO $table SET ";

			$whereclause='';

		}
		
		$values=array();

		foreach ($data as $field => $value) {
			$key = ":$field";
			if ($value === '') {
				$values[] = "`$field` = NULL";
			} else {
				$values[] = "`$field` = $key";
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
			$stmt_obj = PDOConn::query($sql, $str_data, $int_data);
			$affetcedrows= $stmt_obj->rowCount();
			
			if($type=='insert')
				return PDOConn::lastInsertId();
			return true;
		}catch(Exception $e){
			if(!is_a($e, '\PDOStatement'))
				ErrorHandler::logError($error_details_to_log,$e);
			else
				ErrorHandler::logError($error_details_to_log);
			return false;

		}

	}


	function sendNewRegistrationEmail($email_data, $recp){
		$html_msg = <<<EOF
<!DOCTYPE html> 
	<html>
		<head>
		</head> 
		<body>
			<p>Hi {$email_data['name']},</p>
			<p>Welcome to {$email_data['org_name']}! Your name has been added to the members' list. Please login to the directory here:</p>
			<p>
			Visit: {$email_data['login_url']}<br>
			Email: {$email_data['email']}<br>
			Password: {$email_data['password']}
			</p>
			<p>
				Happy Connecting!
			</p>
			<p>Regards,<br>{$email_data['from_name']}</p>
		</body>
	</html>	
EOF;
		$subject = CONST_MAIL_SUBJECT_PREFIX." Welcome to ".$email_data['org_name'];
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


	public function uploadProflieImage($profile_id, $name, $tmp_name, $mode=''){ // for fronedn registrations the $mode should be "reg".
		if($name=='' || $tmp_name=='')
			return false;
			
		$now = time();	
		$ext = pathinfo($name, PATHINFO_EXTENSION);
		$tmp_file_name = CONST_PROFILE_IMG_PREFIX .($mode==='reg'?'reg-':'') . $profile_id.'-'.$now.'.'.strtolower($ext); 
		$dp_file_name = CONST_PROFILE_IMG_PREFIX . ($mode==='reg'?'reg-':'').$profile_id.'_'.uniqid().'.'.strtolower($ext); 
		if(!@move_uploaded_file($tmp_name, CONST_PROFILE_IMG_DIR_PATH . $tmp_file_name) && !@copy($tmp_name, CONST_PROFILE_IMG_DIR_PATH . $tmp_file_name)){
			return false;
		}
		$img_obj = new \eBizIndia\Img();
		// $img_obj->createThumbnail(CONST_DESIGN_IMAGE . $tmp_file_name, CONST_DESIGN_IMAGE . $disk_thumb_file_name, CONST_DESIGN_IMG_THUMB_WIDTH,'');
		if(!$img_obj->resizeImageWithADimensionFixed(CONST_PROFILE_IMG_DIR_PATH . $tmp_file_name, CONST_PROFILE_IMG_DIM['w'], null, CONST_PROFILE_IMG_DIR_PATH.$dp_file_name, 'WD')){
			unlink(CONST_PROFILE_IMG_DIR_PATH.$tmp_file_name);
			return false;
		}

		$result = [];
		$result['dp_file_name'] = $dp_file_name;
		$result['org_file_name'] = $name;
		unlink(CONST_PROFILE_IMG_DIR_PATH.$tmp_file_name);
		return $result;

	}

	public static function getBdayAnnvOndate($dt, $active = '', $dnd='', $ids_to_include=[], $list_type = 'both'){ // bday, annv, both, $active - y or n or blank string
		$where_clause = $str_data = $int_data = [];
		$dt_clause = ' = :dt';
		$dt_tm = strtotime($dt);
		$str_data[':dt'] = $dt;
		$y = date('Y', $dt_tm);
		
		$sql = "SELECT mem.id, mem.fname, mem.name, mem.mobile, mem.mobile2, mem.email, mem.dob, mem.annv, date_format(mem.dob, '$y-%m-%d') as dob_mnth_yr, date_format(mem.annv, '$y-%m-%d') as annv_mnth_yr, mem.active, mem.dnd, COALESCE(cat.cat_name, '') as cat_name from `".CONST_TBL_PREFIX . "members` mem  WHERE "; 
		$active_dnd_clause = [];
		if(!empty($active)){
			$active_dnd_clause[] =  " mem.active=:active  ";
			// $active_dnd_clause[] =  " c.active=:active  ";
			$str_data[':active'] = $active;
		}
		if(!empty($dnd)){
			$active_dnd_clause[] =  " mem.dnd=:dnd  ";
			$str_data[':dnd'] = $dnd;
		}
		
		$id_clause = '';
		if(!empty($ids_to_include)){
			$place_holders=[];
			$k=0;
			foreach($ids_to_include as $mem_id){
				$k++;
				$place_holders[]=":memid_{$k}_";
				$int_data[":memid_{$k}_"]=$mem_id;
				$id_clause = ' mem.id in('.implode(',',$place_holders).')';
			}
		}
		if(!empty($active_dnd_clause)){
			$active_dnd_clause = [
				' ('.implode(' AND ', $active_dnd_clause).') '
			];
			if(!empty($id_clause))
				$active_dnd_clause[] = $id_clause;

			$where_clause[] = ' ( '.implode(' OR ', $active_dnd_clause).'  ) ';

		}

		if($list_type=='bday')
			$where_clause[] = ' mem.dob is not null and ( date_format(mem.dob, "'.$y.'-%m-%d") '.$dt_clause.' ) ';
		else if($list_type=='annv')
			$where_clause[] = ' mem.annv is not null and ( date_format(mem.annv, "'.$y.'-%m-%d") '.$dt_clause.' ) ';
		else if($list_type=='both')
			$where_clause[] = ' ( ( mem.dob is not null and ( date_format(mem.dob, "'.$y.'-%m-%d") '.$dt_clause.' ) ) OR ( mem.annv is not null and ( date_format(mem.annv, "'.$y.'-%m-%d") '.$dt_clause.' ) )  ) ';
		if(count($where_clause))
			$sql .= implode(' AND ', $where_clause);

		$sql .= ' ORDER BY name ';
		try{
			$data = [];
			$stmt_obj = PDOConn::query($sql, $str_data, $int_data);
			// $stmt_obj->debugDumpParams();
			while ($row = $stmt_obj->fetch(\PDO::FETCH_ASSOC)) {
				if($row['dob_mnth_yr']===$row['annv_mnth_yr'])
					$row['type'] = 'both';
				else if($row['dob_mnth_yr'] === $dt) 
					$row['type'] = 'bday';
				else if($row['annv_mnth_yr'] === $dt) 
					$row['type'] = 'annv';
				$data[] = $row;
			}
			return $data;
		}catch(\Exception $e){
			ErrorHandler::logError([], $e);
			return false;
		}
		
		
	}


	function profileUpdatedNotification($email_data, $recp){
		$his = $email_data['gender']=='F'?'her':'his';
		$r = print_r($recp, true);
		$html_msg = <<<EOF
<!DOCTYPE html> 
	<html>
		<head>
		</head> 
		<body>
			<p>Hello,</p>
			<p>A member having the following details has updated {$his} profile.</p>
			<p>
			Name: {$email_data['name']}<br>
			Mobile Number: {$email_data['mobile']}<br>
			</p>
			<p>
				<a href="{$email_data['profile_url']}" >Click HERE</a> to view the member's updated profile.
			</p>
			<p>Regards,<br>{$email_data['from_name']}</p>
		</body>
	</html>	
EOF;
		$subject = CONST_MAIL_SUBJECT_PREFIX." Profile updated by ".$email_data['name'];
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