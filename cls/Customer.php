<?php
namespace eBizIndia;

class Customer {
    private $customer_id;

    public function __construct(?int $customer_id = null) {
        $this->customer_id = $customer_id;
    }

    public function getDetails($fields_to_fetch = []) {
        if (empty($this->customer_id)) {
            return false;
        }

        $options = [];
        $options['filters'] = [
            ['field' => 'id', 'type' => '=', 'value' => $this->customer_id]
        ];

        if (!empty($fields_to_fetch)) {
            $options['fieldstofetch'] = $fields_to_fetch;
        }

        return self::getList($options);
    }

    public static function getList($options = []) {
        $data = [];
        $fields_mapper = $fields_mapper1 = [];

        // // Detailed field mapping
        // $fields_mapper1['*'] = 'T1.*';
        // $fields_mapper1['id'] = 'T1.id';
        // $fields_mapper1['name'] = 'T1.name';
        // $fields_mapper1['industry_id'] = 'T1.industry_id';
        // $fields_mapper1['customer_group_id'] = 'T1.customer_group_id';
        // $fields_mapper1['industry_name'] = 'ind.industry';
        // $fields_mapper1['customer_group_name'] = 'cg.name';
        
        $fields_mapper['*'] = "c.id as id, c.name as name, c.industry_id, c.customer_group_id, 
                                ind.industry as industry_name, 
                                cg.name as customer_group_name,
                                c.address_1, c.address_2, c.address_3, 
                                c.state, c.city, c.pin, 
                                c.website, c.business_details, c.active";
        
        $fields_mapper['recordcount'] = 'count(distinct c.id)';
        $fields_mapper['id'] = 'c.id';
        $fields_mapper['name'] = 'c.name';
        $fields_mapper['industry_id'] = 'c.industry_id';
        $fields_mapper['customer_group_id'] = 'c.customer_group_id';
        $fields_mapper['active'] = 'c.active';
        $fields_mapper['industry_name'] = 'ind.industry';
        $fields_mapper['customer_group_name'] = 'cg.name';

        $where_clause = [];
        $str_params_to_bind = [];
        $int_params_to_bind = [];

        // Implement filters similar to Company class
        if (array_key_exists('filters', $options) && is_array($options['filters'])) {
            $field_counter = 0;
            foreach ($options['filters'] as $filter) {
                ++$field_counter;
                switch ($filter['field']) {
                    case 'id':
                    case 'industry_id':
                    case 'customer_group_id':
                        $fld = $fields_mapper[$filter['field']];
                        switch ($filter['type']) {
                            case 'IN':
                                if (is_array($filter['value'])) {
                                    $place_holders = [];
                                    $k = 0;
                                    foreach ($filter['value'] as $val) {
                                        $k++;
                                        $ph = ":whr{$field_counter}_{$k}_";
                                        $place_holders[] = $ph;
                                        $int_params_to_bind[$ph] = $val;
                                    }
                                    $where_clause[] = $fld . ' in(' . implode(',', $place_holders) . ') ';
                                }
                                break;
                            default:
                                $val = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $ph = ":whr{$field_counter}_";
                                $where_clause[] = $fld . ' ' . $filter['type'] . ' ' . $ph;
                                $int_params_to_bind[$ph] = $val;
                        }
                        break;

                    case 'name':
                    case 'industry_name':
                    case 'customer_group_name':
                        $fld = $fields_mapper[$filter['field']];
                        switch ($filter['type']) {
                            case 'CONTAINS':
                                $v = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $where_clause[] = $fld . ' like :whr' . $field_counter . '_';
                                $str_params_to_bind[':whr' . $field_counter . '_'] = '%' . $v . '%';
                                break;
                            default:
                                $v = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $where_clause[] = $fld . '=:whr' . $field_counter . '_';
                                $str_params_to_bind[':whr' . $field_counter . '_'] = $v;
                        }
                        break;

                    case 'active':
                        $type = strtolower($filter['type']);
                        $val = ($type == 'yes') ? 'y' : 'n';
                        $where_clause[] = $fields_mapper['active'] . " = :whr{$field_counter}_active";
                        $str_params_to_bind[":whr{$field_counter}_active"] = $val;
                        break;
                }
            }
        }

        // Similar query building logic as in Company class
        $select_string = $fields_mapper['*'];
        $record_count = false;

        // Field selection logic
        if (array_key_exists('fieldstofetch', $options) && is_array($options['fieldstofetch'])) {
            $fields_to_fetch_count = count($options['fieldstofetch']);
            if ($fields_to_fetch_count > 0) {
                $selected_fields = [];
                if (in_array('recordcount', $options['fieldstofetch'])) {
                    $record_count = true;
                } else {
                    if (!in_array('*', $options['fieldstofetch'])) {
                        if (!in_array('id', $options['fieldstofetch'])) {
                            $options['fieldstofetch'][] = 'id';
                            $fields_to_fetch_count += 1;
                        }
                    }
                }

                for ($i = 0; $i < $fields_to_fetch_count; $i++) {
                    if (array_key_exists($options['fieldstofetch'][$i], $fields_mapper)) {
                        $selected_fields[] = $fields_mapper[$options['fieldstofetch'][$i]] . 
                            (($options['fieldstofetch'][$i] != '*') ? ' as ' . $options['fieldstofetch'][$i] : '');
                    }
                }

                if (count($selected_fields) > 0) {
                    $select_string = implode(', ', $selected_fields);
                }
            }
        }

        $select_string = ($record_count) ? $select_string : 'distinct ' . $select_string;

        // Ordering and pagination logic similar to Company class
        // $order_by_clause = ' ORDER BY c.name ASC';

        $group_by_clause = '';
        if (array_key_exists('group_by', $options) && is_array($options['group_by'])) {
            foreach ($options['group_by'] as $field) {
                if (preg_match("/^(cg|ind|c)\./",$fields_mapper[$field])) {
                    $group_by_clause .= ", ".$fields_mapper[$field];
                } else {
                    $group_by_clause .= ", $field";
                }
            }

            $group_by_clause = trim($group_by_clause, ",");
            if ($group_by_clause != '') {
                $group_by_clause = ' GROUP BY '.$group_by_clause;
            }
        }

        $order_by_clause = '';

        if (array_key_exists('order_by', $options) && is_array($options['order_by'])) {
            foreach ($options['order_by'] as $order) {
                if (preg_match("/^(cg|ind|c)\./",$fields_mapper[$order['field']])) {
                    $order_by_clause .= ", ".$fields_mapper[$order['field']];

                    if (!$record_count) {
                        if (!preg_match("/,?\s*".str_replace('.', "\.", $fields_mapper[$order['field']])."/",$select_string)) {
                            $select_string .= ", ".$fields_mapper[$order['field']]. ' as '.$order['field'];
                        }
                    }
                } else if (array_key_exists($order['field'], $fields_mapper)) {
                    if (!preg_match("/\s*as\s*".$order['field']."/",$select_string)) {
                        $select_string .= ", ".$fields_mapper[$order['field']].' as '.$order['field'];
                    }

                    $order_by_clause .= ", ".$order['field'];
                }

                if (array_key_exists('type', $order) && $order['type'] == 'DESC') {
                    $order_by_clause .= ' DESC';
                }
            }

            $order_by_clause = trim($order_by_clause, ",");
            if ($order_by_clause != '') {
                $order_by_clause = ' ORDER BY '.$order_by_clause;
            }

            if ($order_by_clause != '' && !stristr($order_by_clause, 'c.id')) {
                $order_by_clause .= ', '.$fields_mapper['id'].' DESC ';
            }
        }

        if (!$record_count && $order_by_clause == '') {
            $order_by_clause = " ORDER BY c.name ASC";

            if (!preg_match("/\s+as\s+name/",$select_string)) {
                $select_string .= ', '.$fields_mapper['name'].' as name';
            }
        }



        $limit_clause = '';
        if (array_key_exists('page', $options) && 
            filter_var($options['page'], FILTER_VALIDATE_INT) && $options['page'] > 0 && 
            array_key_exists('recs_per_page', $options) && 
            filter_var($options['recs_per_page'], FILTER_VALIDATE_INT) && $options['recs_per_page'] > 0) {
            $limit_clause = "LIMIT " . (($options['page'] - 1) * $options['recs_per_page']) . ", " . $options['recs_per_page'];
        }

        $where_clause_string = '';
        if (!empty($where_clause)) {
            $where_clause_string = ' WHERE ' . implode(' AND ', $where_clause);
        }

        $sql = "SELECT $select_string 
                FROM `" . CONST_TBL_PREFIX . "customers` c
                LEFT JOIN `" . CONST_TBL_PREFIX . "industries` ind ON c.industry_id = ind.id
                LEFT JOIN `" . CONST_TBL_PREFIX . "customer_groups` cg ON c.customer_group_id = cg.id
                $where_clause_string 
                $group_by_clause 
                $order_by_clause 
                $limit_clause";

        try {
            $pdo_stmt_obj = PDOConn::query($sql, $str_params_to_bind, $int_params_to_bind);
            
            $data = [];
            while ($row = $pdo_stmt_obj->fetch(\PDO::FETCH_ASSOC)) {
                $data[] = $row;
            }
            
            return $data;
        } catch (\Exception $e) {
            ErrorHandler::logError(
                [
                    'function' => __METHOD__,
                    'sql' => $sql,
                    'str_params' => $str_params_to_bind,
                    'int_params' => $int_params_to_bind
                ],
                $e
            );
            return false;
        }
    }

    public function add(array $data) {
        if (empty($data)) {
            return false;
        }

        $sql = "INSERT INTO `" . CONST_TBL_PREFIX . "customers` SET ";
        $values = [];
        $str_data = [];

        // Add tracking information
        $data['created_on'] = date('Y-m-d H:i:s');
        $data['created_by'] = $GLOBALS['loggedindata'][0]['id'] ?? null;
        $data['created_from'] = \eBizIndia\getRemoteIP();

        foreach ($data as $field => $value) {
            $key = ":$field";
            if ($value === '') {
                $values[] = "`$field` = NULL";
            } else {
                $values[] = "`$field` = $key";
                $str_data[$key] = $value;
            }
        }

        $sql .= implode(',', $values);

        try {
            $stmt_obj = PDOConn::query($sql, $str_data);
            return PDOConn::lastInsertId();
        } catch (\Exception $e) {
            ErrorHandler::logError(
                [
                    'function' => __METHOD__,
                    'sql' => $sql,
                    'data' => $data
                ],
                $e
            );
            return false;
        }
    }

    public function update($data) {
        if (empty($data) || empty($this->customer_id)) {
            return false;
        }

        $sql = "UPDATE `" . CONST_TBL_PREFIX . "customers` SET ";
        $values = [];
        $str_data = [];
        $int_data = [':id' => $this->customer_id];

        foreach ($data as $field => $value) {
            $key = ":$field";
            if ($value === '') {
                $values[] = "`$field` = NULL";
            } else {
                $values[] = "`$field` = $key";
                $str_data[$key] = $value;
            }
        }

        $sql .= implode(',', $values);
        $sql .= " WHERE `id` = :id";

        try {
            $stmt_obj = PDOConn::query($sql, $str_data, $int_data);
            
            if ($stmt_obj === false) {
                return false;
            }
            
            $affectedRows = $stmt_obj->rowCount();
            return $affectedRows > 0 ? true : null;
        } catch (\Exception $e) {
            ErrorHandler::logError(
                [
                    'function' => __METHOD__,
                    'sql' => $sql,
                    'data' => $data
                ],
                $e
            );
            return false;
        }
    }

    public function addContacts(array $contacts) {
        if (empty($this->customer_id)) {
            return false;
        }

        try {
            
            $insert_sql = "INSERT INTO `" . CONST_TBL_PREFIX . "customer_contacts` 
                (customer_id, name, department, designation, email, phone, created_on, created_by, created_from) 
                VALUES ";
            
            $created_by = $GLOBALS['loggedindata'][0]['id'] ?? null;
            $created_from = \eBizIndia\getRemoteIP();

            $values = $params = [];
            $key_suffix = 0;
            $dttm = date('Y-m-d H:i:s');
            $params[":customer_id"] = $this->customer_id;
            $params[":created_by"] = $created_by;
            $params[":created_from"] = $created_from;
            foreach ($contacts as $contact) {
                $key_suffix++; 
                $values[] = "(:customer_id, :name_{$key_suffix}, :department_{$key_suffix}, :designation_{$key_suffix}, :email_{$key_suffix}, :phone_{$key_suffix}, '$dttm', :created_by, :created_from)";
                $params[":name_{$key_suffix}"] = $contact['name'];
                $params[":department_{$key_suffix}"] = $contact['department'] ?? null;
                $params[":designation_{$key_suffix}"] = $contact['designation'] ?? null;
                $params[":email_{$key_suffix}"] = $contact['email'] ?? null;
                $params[":phone_{$key_suffix}"] = $contact['phone'] ?? null;
                
            }
            $insert_sql .= implode(', ', $values);
            PDOConn::query($insert_sql, $params);
            
            return true;
        } catch (\Exception $e) {
            ErrorHandler::logError(
                [
                    'function' => __METHOD__,
                    'contacts' => $contacts
                ],
                $e
            );
            return false;
        }
    }

    public function getContacts() {
        if (empty($this->customer_id)) {
            return false;
        }

        $sql = "SELECT * FROM `" . CONST_TBL_PREFIX . "customer_contacts` 
                WHERE customer_id = :customer_id";

        try {
            $stmt_obj = PDOConn::query($sql, [':customer_id' => $this->customer_id]);
            return $stmt_obj->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            ErrorHandler::logError(
                [
                    'function' => __METHOD__,
                    'customer_id' => $this->customer_id
                ],
                $e
            );
            return false;
        }
    }

    public function updateContact($contact_id, $data) {
        // Update a specific contact without using non-existent update fields
        $sql = "UPDATE `" . CONST_TBL_PREFIX . "customer_contacts` SET 
                name = :name,
                department = :department,
                designation = :designation,
                email = :email,
                phone = :phone
                WHERE id = :id AND customer_id = :customer_id";
        
        $params = [
            ':id' => $contact_id,
            ':customer_id' => $this->customer_id,
            ':name' => $data['name'],
            ':department' => $data['department'] ?? null,
            ':designation' => $data['designation'] ?? null,
            ':email' => $data['email'] ?? null,
            ':phone' => $data['phone'] ?? null
        ];
        
        try {
            $stmt_obj = PDOConn::query($sql, $params);
            if ($stmt_obj === false) {
                return false;
            }
            return $stmt_obj->rowCount() > 0;
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__, 'contact_id' => $contact_id], $e);
            return false;
        }
    }

    public function deleteContacts($contact_ids) {
        if (empty($contact_ids) || !is_array($contact_ids)) {
            return false;
        }
        
        try {
            $placeholders = [];
            $params = [':customer_id' => $this->customer_id];
            
            foreach ($contact_ids as $idx => $id) {
                $key = ":id_$idx";
                $placeholders[] = $key;
                $params[$key] = $id;
            }
            
            $sql = "DELETE FROM `" . CONST_TBL_PREFIX . "customer_contacts` 
                    WHERE customer_id = :customer_id AND id IN (" . implode(',', $placeholders) . ")";
            
            PDOConn::query($sql, $params);
            return true;
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__, 'contact_ids' => $contact_ids], $e);
            return false;
        }
    }

    
    public function contactDataChanged($existing_data, $new_data) {
        try {
            if(empty($existing_data))
                return true;
            // Compare relevant fields
            if ($existing_data['name'] != $new_data['name'] ||
                $existing_data['department'] != ($new_data['department'] ?? null) ||
                $existing_data['designation'] != ($new_data['designation'] ?? null) ||
                $existing_data['email'] != ($new_data['email'] ?? null) ||
                $existing_data['phone'] != ($new_data['phone'] ?? null)) {
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__, 'contact_id' => $contact_id], $e);
            return true; // On error, assume changed to be safe
        }
    }
}