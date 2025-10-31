<?php
namespace eBizIndia;
class ItemCategory {
    private $category_id;
    
    public function __construct(?int $category_id=null) {
        $this->category_id = $category_id;
    }
    
    public function getDetails($fields_to_fetch=[]) {
        if(empty($this->category_id))
            return false;
        $options = [];
        $options['filters'] = [
            ['field'=>'id', 'type'=>'=', 'value'=> $this->category_id]
        ];
        if(!empty($fields_to_fetch))
            $options['fieldstofetch'] = $fields_to_fetch;
        return self::getList($options);
    }
    
    public static function getList($options=[]) {
        $data = [];
        $fields_mapper = [];
        $fields_mapper['*'] = 'ic.id as id, ic.name, ic.group, ic.active, ic.created_on, ic.created_by, ic.created_from_ip, ic.updated_on, ic.updated_by, ic.updated_from_ip';
        $fields_mapper['recordcount'] = 'count(ic.id)';
        $fields_mapper['id'] = 'ic.id';
        $fields_mapper['name'] = 'ic.name';
        $fields_mapper['group'] = 'ic.group';
        $fields_mapper['active'] = 'ic.active';
        $fields_mapper['created_on'] = 'ic.created_on';
        $fields_mapper['created_by'] = 'ic.created_by';
        $fields_mapper['created_from_ip'] = 'ic.created_from_ip';
        $fields_mapper['updated_on'] = 'ic.updated_on';
        $fields_mapper['updated_by'] = 'ic.updated_by';
        $fields_mapper['updated_from_ip'] = 'ic.updated_from_ip';
        
        $where_clause = [];
        $str_params_to_bind = [];
        $int_params_to_bind = [];
        
        if(array_key_exists('filters', $options) && is_array($options['filters'])) {
            $field_counter = 0;
            foreach($options['filters'] as $filter) {
                ++$field_counter;
                switch($filter['field']) {
                    case 'id':
                    case 'created_by':
                    case 'updated_by':
                        switch($filter['type']) {
                            case 'IN':
                                if(is_array($filter['value'])) {
                                    $place_holders = [];
                                    $k = 0;
                                    foreach($filter['value'] as $val) {
                                        $k++;
                                        $ph = ":whr{$field_counter}_{$k}_";
                                        $place_holders[] = $ph;
                                        $int_params_to_bind[$ph] = $val;
                                    }
                                    $where_clause[] = $fields_mapper[$filter['field']] . ' in(' . implode(',', $place_holders) . ') ';
                                }
                                break;
                            case 'NOT_IN':
                                if(is_array($filter['value'])) {
                                    $place_holders = [];
                                    $k = 0;
                                    foreach($filter['value'] as $val) {
                                        $k++;
                                        $ph = ":whr{$field_counter}_{$k}_";
                                        $place_holders[] = $ph;
                                        $int_params_to_bind[$ph] = $val;
                                    }
                                    $where_clause[] = $fields_mapper[$filter['field']] . ' not in(' . implode(',', $place_holders) . ') ';
                                }
                                break;
                            default:
                                $val = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $ph = ":whr{$field_counter}_";
                                $where_clause[] = $fields_mapper[$filter['field']] . ' ' . $filter['type'] . ' ' . $ph;
                                $int_params_to_bind[$ph] = $val;
                        }
                        break;
                    case 'name':
                    case 'group':
                    case 'active':
                        switch($filter['type']) {
                            case 'IN':
                                if(is_array($filter['value'])) {
                                    $place_holders = [];
                                    $k = 0;
                                    foreach($filter['value'] as $v) {
                                        $k++;
                                        $place_holders[] = ":whr{$field_counter}_{$k}_";
                                        $str_params_to_bind[":whr{$field_counter}_{$k}_"] = $v;
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
                                $where_clause[] = ' ( ' . $fields_mapper[$filter['field']] . ' IS NULL OR ' . $fields_mapper[$filter['field']] . '!=:whr' . $field_counter . '_ ) ';
                                $str_params_to_bind[':whr' . $field_counter . '_'] = $v;
                                break;
                            case 'STARTS_WITH':
                                $v = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $where_clause[] = $fields_mapper[$filter['field']] . ' like :whr' . $field_counter . '_';
                                $str_params_to_bind[':whr' . $field_counter . '_'] = $v . '%';
                                break;
                            default:
                                $v = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $where_clause[] = $fields_mapper[$filter['field']] . '=:whr' . $field_counter . '_';
                                $str_params_to_bind[':whr' . $field_counter . '_'] = $v;
                        }
                        break;
                    case 'created_on':
                    case 'updated_on':
                        $dt = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                        $fld = $fields_mapper[$filter['field']];
                        switch($filter['type']) {
                            case 'BETWEEN':
                                $dt1 = $filter['value'][0];
                                $dt2 = $filter['value'][1];
                                $where_clause[] = '( ' . $fld . ' >= :whr' . $field_counter . '_dt_1_ AND ' . $fld . ' <= :whr' . $field_counter . '_dt_2_ ) ';
                                $str_params_to_bind[':whr' . $field_counter . '_dt_1_'] = $dt1;
                                $str_params_to_bind[':whr' . $field_counter . '_dt_2_'] = $dt2;
                                break;
                            case 'AFTER':
                                $where_clause[] = $fld . " > :whr" . $field_counter . "_dt";
                                $str_params_to_bind[':whr' . $field_counter . '_dt'] = $dt;
                                break;
                            case 'BEFORE':
                                $where_clause[] = $fld . " < :whr" . $field_counter . "_dt";
                                $str_params_to_bind[':whr' . $field_counter . '_dt'] = $dt;
                                break;
                            case 'EQUAL':
                            default:
                                $where_clause[] = $fld . " = :whr" . $field_counter . "_dt";
                                $str_params_to_bind[':whr' . $field_counter . '_dt'] = $dt;
                                break;
                        }
                        break;
                }
            }
        }
        
        $select_string = $fields_mapper['*'];
        if(array_key_exists('fieldstofetch', $options) && is_array($options['fieldstofetch'])) {
            $fields_to_fetch_count = count($options['fieldstofetch']);
            if($fields_to_fetch_count > 0) {
                $selected_fields = [];
                if(in_array('recordcount', $options['fieldstofetch'])) {
                    $record_count = true;
                    $selected_fields[] = $fields_mapper['recordcount'] . ' as recordcount';
                } else {
                    if(!in_array('*', $options['fieldstofetch'])) {
                        if(!in_array('id', $options['fieldstofetch'])) {
                            $options['fieldstofetch'][] = 'id';
                            $fields_to_fetch_count += 1;
                        }
                    }
                    for($i = 0; $i < $fields_to_fetch_count; $i++) {
                        if(array_key_exists($options['fieldstofetch'][$i], $fields_mapper)) {
                            $selected_fields[] = $fields_mapper[$options['fieldstofetch'][$i]] . (($options['fieldstofetch'][$i] != '*') ? ' as ' . $options['fieldstofetch'][$i] : '');
                        }
                    }
                }
                if(count($selected_fields) > 0) {
                    $select_string = implode(', ', $selected_fields);
                }
            }
        }
        
        $group_by_clause = '';
        if(array_key_exists('group_by', $options) && is_array($options['group_by'])) {
            foreach($options['group_by'] as $field) {
                $group_by_clause .= ', ' . $fields_mapper[$field];
            }
            $group_by_clause = trim($group_by_clause, ',');
            if($group_by_clause != '') {
                $group_by_clause = ' GROUP BY ' . $group_by_clause;
            }
        }
        
        $order_by_clause = '';
        if(array_key_exists('order_by', $options) && is_array($options['order_by'])) {
            foreach($options['order_by'] as $order) {
                $order_by_clause .= ', ' . $fields_mapper[$order['field']];
                if(array_key_exists('type', $order) && $order['type'] == 'DESC') {
                    $order_by_clause .= ' DESC';
                }
            }
            $order_by_clause = trim($order_by_clause, ',');
            if($order_by_clause != '') {
                $order_by_clause = ' ORDER BY ' . $order_by_clause;
            }
            if($order_by_clause != '' && !stristr($order_by_clause, 'ic.id')) {
                $order_by_clause .= ', ic.id DESC';
            }
        }
        
        if(!$record_count && $order_by_clause == '') {
            $order_by_clause = " ORDER BY ic.name ASC, ic.id DESC";
        }
        
        $limit_clause = '';
        if(array_key_exists('page', $options) && filter_var($options['page'], FILTER_VALIDATE_INT) && $options['page'] > 0 && array_key_exists('recs_per_page', $options) && filter_var($options['recs_per_page'], FILTER_VALIDATE_INT) && $options['recs_per_page'] > 0) {
            $limit_clause = "LIMIT " . (($options['page'] - 1) * $options['recs_per_page']) . ", $options[recs_per_page] ";
        }
        
        $where_clause_string = '';
        if(!empty($where_clause)) {
            $where_clause_string = ' WHERE ' . implode(' AND ', $where_clause);
        }
        
        $sql = "SELECT $select_string FROM `" . CONST_TBL_PREFIX . "item_categories` as ic $where_clause_string $group_by_clause $order_by_clause $limit_clause";
        
        $error_details_to_log = [];
        $error_details_to_log['function'] = __METHOD__;
        $error_details_to_log['sql'] = $sql;
        $error_details_to_log['str_params_to_bind'] = $str_params_to_bind;
        $error_details_to_log['int_params_to_bind'] = $int_params_to_bind;
        
        try {
            $pdo_stmt_obj = PDOConn::query($sql, $str_params_to_bind, $int_params_to_bind);
            $data = [];
            while($row = $pdo_stmt_obj->fetch(\PDO::FETCH_ASSOC)) {
                $data[] = $row;
            }
            return $data;
        } catch(\Exception $e) {
            if(!is_a($e, '\PDOStatement'))
                ErrorHandler::logError($error_details_to_log, $e);
            else
                ErrorHandler::logError($error_details_to_log);
            return false;
        }
    }
    
    public function add(array $data) {
        $str_data = [];
        if(empty($data))
            return false;
            
        $sql = "INSERT INTO `" . CONST_TBL_PREFIX . "item_categories` SET ";
        $values = [];
        
        foreach($data as $field => $value) {
            $key = ":$field";
            if($value === '') {
                $values[] = "`$field`=NULL";
            } else {
                $values[] = "`$field`=$key";
                $str_data[$key] = $value;
            }
        }
        
        $sql .= implode(',', $values);
        
        $error_details_to_log = [];
        $error_details_to_log['function'] = __METHOD__;
        $error_details_to_log['sql'] = $sql;
        $error_details_to_log['data'] = $data;
        
        try {
            $stmt_obj = PDOConn::query($sql, $str_data);
            return PDOConn::lastInsertId();
        } catch(\Exception $e) {
            if(!is_a($e, '\PDOStatement'))
                ErrorHandler::logError($error_details_to_log, $e);
            else
                ErrorHandler::logError($error_details_to_log);
            return false;
        }
    }
    
    public function update($data) {
        $str_data = $int_data = [];
        if(empty($data) || empty($this->category_id))
            return false;
            
        $int_data[':id'] = $this->category_id;
        $sql = "UPDATE `" . CONST_TBL_PREFIX . "item_categories` SET ";
        $values = [];
        
        foreach($data as $field => $value) {
            $key = ":$field";
            if($value === '') {
                $values[] = "`$field`=NULL";
            } else {
                $values[] = "`$field`=$key";
                $str_data[$key] = $value;
            }
        }
        
        $sql .= implode(',', $values);
        $sql .= " WHERE `id`=:id";
        
        $error_details_to_log = [];
        $error_details_to_log['function'] = __METHOD__;
        $error_details_to_log['sql'] = $sql;
        $error_details_to_log['data'] = $data;
        $error_details_to_log['id'] = $this->category_id;
        
        try {
            $stmt_obj = PDOConn::query($sql, $str_data, $int_data);
            if($stmt_obj === false)
                return false;
                
            $affectedRows = $stmt_obj->rowCount();
            if($affectedRows <= 0)
                return null; // Query did not fail but nothing was updated
                
            return true;
        } catch(\Exception $e) {
            if(!is_a($e, '\PDOStatement'))
                ErrorHandler::logError($error_details_to_log, $e);
            else
                ErrorHandler::logError($error_details_to_log);
            return false;
        }
    }
    
    public static function delete($ids) {
        if(empty($ids))
            return false;
            
        $str_params_to_bind = [];
        $place_holders = [];
        
        if(!is_array($ids))
            $ids = [$ids];
        
        for($i = 0; $i < count($ids); $i++) {
            $place_holders[] = ":id$i";
            $str_params_to_bind[":id$i"] = $ids[$i];
        }
        
        $sql = "DELETE FROM `" . CONST_TBL_PREFIX . "item_categories` WHERE `id` IN (" . implode(',', $place_holders) . ")";
        
        $error_details_to_log = [];
        $error_details_to_log['function'] = __METHOD__;
        $error_details_to_log['sql'] = $sql;
        $error_details_to_log['params'] = $str_params_to_bind;
        
        try {
            $stmt_obj = PDOConn::query($sql, $str_params_to_bind);
            return ($stmt_obj !== false);
        } catch(\Exception $e) {
            if(!is_a($e, '\PDOStatement'))
                ErrorHandler::logError($error_details_to_log, $e);
            else
                ErrorHandler::logError($error_details_to_log);
            return false;
        }
    }
}