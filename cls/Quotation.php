<?php
namespace eBizIndia;

class Quotation {
    private $quotation_id;

    public function __construct(?int $quotation_id = null) {
        $this->quotation_id = $quotation_id;
    }

    public function getDetails($fields_to_fetch = []) {
        if (empty($this->quotation_id)) {
            return false;
        }

        $options = [];
        $options['filters'] = [
            ['field' => 'id', 'type' => '=', 'value' => $this->quotation_id]
        ];

        if (!empty($fields_to_fetch)) {
            $options['fieldstofetch'] = $fields_to_fetch;
        }

        return self::getList($options);
    }

    public static function getList($options = []) {
        $data = [];
        $fields_mapper = [];

        // Define field mappings
        $fields_mapper['*'] = "q.id as id, q.quotation_no, q.quotation_date, q.enquiry_no, q.enquiry_date, 
                               q.customer_id, c.name as customer_name, q.address, q.gstin, 
                               q.contact_name, q.contact_designation, 
                               q.price_basis, q.validity, q.payment, q.our_gst_no, q.guarantee, 
                               q.sender_name, q.active, 
                               q.created_on, q.created_by, q.created_from_ip, q.updated_on, q.updated_by, q.updated_from_ip";
        
        $fields_mapper['recordcount'] = 'count(distinct q.id)';
        $fields_mapper['id'] = 'q.id';
        $fields_mapper['quotation_no'] = 'q.quotation_no';
        $fields_mapper['quotation_date'] = 'q.quotation_date';
        $fields_mapper['enquiry_no'] = 'q.enquiry_no';
        $fields_mapper['enquiry_date'] = 'q.enquiry_date';
        $fields_mapper['customer_id'] = 'q.customer_id';
        $fields_mapper['customer_name'] = 'c.name';
        $fields_mapper['address'] = 'q.address';
        $fields_mapper['gstin'] = 'q.gstin';
        $fields_mapper['contact_name'] = 'q.contact_name';
        $fields_mapper['contact_designation'] = 'q.contact_designation';
        $fields_mapper['price_basis'] = 'q.price_basis';
        $fields_mapper['validity'] = 'q.validity';
        $fields_mapper['payment'] = 'q.payment';
        $fields_mapper['our_gst_no'] = 'q.our_gst_no';
        $fields_mapper['guarantee'] = 'q.guarantee';
        $fields_mapper['sender_name'] = 'q.sender_name';
        $fields_mapper['active'] = 'q.active';
        $fields_mapper['created_on'] = 'q.created_on';
        $fields_mapper['created_by'] = 'q.created_by';
        $fields_mapper['created_from_ip'] = 'q.created_from_ip';
        $fields_mapper['updated_on'] = 'q.updated_on';
        $fields_mapper['updated_by'] = 'q.updated_by';
        $fields_mapper['updated_from_ip'] = 'q.updated_from_ip';

        $where_clause = [];
        $str_params_to_bind = [];
        $int_params_to_bind = [];
        $record_count = false;

        // Apply filters
        if (array_key_exists('filters', $options) && is_array($options['filters'])) {
            $field_counter = 0;
            foreach ($options['filters'] as $filter) {
                ++$field_counter;
                switch ($filter['field']) {
                    case 'id':
                    case 'customer_id':
                    case 'created_by':
                    case 'updated_by':
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
                                    $where_clause[] = $fields_mapper[$filter['field']] . ' in(' . implode(',', $place_holders) . ') ';
                                }
                                break;
                            default:
                                $val = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $ph = ":whr{$field_counter}_";
                                $where_clause[] = $fields_mapper[$filter['field']] . ' ' . $filter['type'] . ' ' . $ph;
                                $int_params_to_bind[$ph] = $val;
                        }
                        break;

                    case 'quotation_no':
                    case 'enquiry_no':
                    case 'customer_name':
                    case 'contact_name':
                        switch ($filter['type']) {
                            case 'CONTAINS':
                                $v = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $where_clause[] = $fields_mapper[$filter['field']] . ' like :whr' . $field_counter . '_';
                                $str_params_to_bind[':whr' . $field_counter . '_'] = '%' . $v . '%';
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

                    case 'quotation_date':
                    case 'enquiry_date':
                    case 'created_on':
                    case 'updated_on':
                        $dt = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                        $fld = $fields_mapper[$filter['field']];
                        switch ($filter['type']) {
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

                    case 'active':
                        $type = strtolower($filter['value']);
                        $val = ($type == 'yes' || $type == 'y') ? 'Y' : 'N';
                        $where_clause[] = $fields_mapper['active'] . " = :whr{$field_counter}_active";
                        $str_params_to_bind[":whr{$field_counter}_active"] = $val;
                        break;
                }
            }
        }

        // Fields to fetch
        $select_string = $fields_mapper['*'];
        if (array_key_exists('fieldstofetch', $options) && is_array($options['fieldstofetch'])) {
            $fields_to_fetch_count = count($options['fieldstofetch']);
            if ($fields_to_fetch_count > 0) {
                $selected_fields = [];
                if (in_array('recordcount', $options['fieldstofetch'])) {
                    $record_count = true;
                    $selected_fields[] = $fields_mapper['recordcount'] . ' as recordcount';
                } else {
                    if (!in_array('*', $options['fieldstofetch'])) {
                        if (!in_array('id', $options['fieldstofetch'])) {
                            $options['fieldstofetch'][] = 'id';
                            $fields_to_fetch_count += 1;
                        }
                    }
                    for ($i = 0; $i < $fields_to_fetch_count; $i++) {
                        if (array_key_exists($options['fieldstofetch'][$i], $fields_mapper)) {
                            $selected_fields[] = $fields_mapper[$options['fieldstofetch'][$i]] . 
                                (($options['fieldstofetch'][$i] != '*') ? ' as ' . $options['fieldstofetch'][$i] : '');
                        }
                    }
                }
                if (count($selected_fields) > 0) {
                    $select_string = implode(', ', $selected_fields);
                }
            }
        }

        $select_string = ($record_count) ? $select_string : 'distinct ' . $select_string;

        // Group by
        $group_by_clause = '';
        if (array_key_exists('group_by', $options) && is_array($options['group_by'])) {
            foreach ($options['group_by'] as $field) {
                if (preg_match("/^(q|c)\./",$fields_mapper[$field])) {
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

        // Order by
        $order_by_clause = '';
        if (array_key_exists('order_by', $options) && is_array($options['order_by'])) {
            foreach ($options['order_by'] as $order) {
                if (preg_match("/^(q|c)\./",$fields_mapper[$order['field']])) {
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

            if ($order_by_clause != '' && !stristr($order_by_clause, 'q.id')) {
                $order_by_clause .= ', '.$fields_mapper['id'].' DESC ';
            }
        }

        if (!$record_count && $order_by_clause == '') {
            $order_by_clause = " ORDER BY q.quotation_date DESC, q.id DESC";
        }

        // Pagination
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
                FROM `" . CONST_TBL_PREFIX . "quotations` q
                LEFT JOIN `" . CONST_TBL_PREFIX . "customers` c ON q.customer_id = c.id
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

        try {
            // $conn = PDOConn::getInstance();
            // $conn->beginTransaction();

            // Add quotation header
            $sql = "INSERT INTO `" . CONST_TBL_PREFIX . "quotations` SET ";
            $values = [];
            $str_data = [];

            // Add tracking information
            $data['created_on'] = date('Y-m-d H:i:s');
            $data['created_by'] = $GLOBALS['loggedindata'][0]['id'] ?? null;
            $data['created_from_ip'] = \eBizIndia\getRemoteIP();

            foreach ($data as $field => $value) {
                if ($field == 'items') continue; // Skip items, will be processed separately
                
                $key = ":$field";
                if ($value === '') {
                    $values[] = "`$field` = NULL";
                } else {
                    $values[] = "`$field` = $key";
                    $str_data[$key] = $value;
                }
            }

            $sql .= implode(',', $values);

            $stmt_obj = PDOConn::query($sql, $str_data);
            $quotation_id = PDOConn::lastInsertId();
            
            if (!$quotation_id) {
                throw new \Exception('Error adding the quotation.');
            }
            
            // Process items if present
            if (isset($data['items']) && is_array($data['items']) && !empty($data['items'])) {
                if (!$this->addItems($quotation_id, $data['items'])) {
                    throw new \Exception('Error adding quotation items.');
                }
            }
            
            // $conn->commit();
            return $quotation_id;
            
        } catch (\Exception $e) {
            ErrorHandler::logError(
                [
                    'function' => __METHOD__,
                    'data' => $data
                ],
                $e
            );
            
            // if (isset($conn) && $conn->inTransaction()) {
            //     $conn->rollBack();
            // }
            
            return false;
        }
    }

    public function update($data) {
        if (empty($data) || empty($this->quotation_id)) {
            return false;
        }

        try {
            // $conn = PDOConn::getInstance();
            // $conn->beginTransaction();

            // Update quotation header
            $sql = "UPDATE `" . CONST_TBL_PREFIX . "quotations` SET ";
            $values = [];
            $str_data = [];
            $int_data = [':id' => $this->quotation_id];

            foreach ($data as $field => $value) {
                if ($field == 'items') continue; // Skip items, will be processed separately
                
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

            $stmt_obj = PDOConn::query($sql, $str_data, $int_data);
            
            if ($stmt_obj === false) {
                throw new \Exception('Error updating the quotation.');
            }
            
            // Process items if present
            if (isset($data['items']) && is_array($data['items'])) {
                // First delete all existing items
                $delete_sql = "DELETE FROM `" . CONST_TBL_PREFIX . "quotation_items` WHERE `quotation_id` = :quotation_id";
                PDOConn::query($delete_sql, [':quotation_id' => $this->quotation_id]);
                
                // Then add all new items
                if (!empty($data['items'])) {
                    if (!$this->addItems($this->quotation_id, $data['items'])) {
                        throw new \Exception('Error updating quotation items.');
                    }
                }
            }
            
            // $conn->commit();
            return true;
            
        } catch (\Exception $e) {
            ErrorHandler::logError(
                [
                    'function' => __METHOD__,
                    'quotation_id' => $this->quotation_id,
                    'data' => $data
                ],
                $e
            );
            
            // if (isset($conn) && $conn->inTransaction()) {
            //     $conn->rollBack();
            // }
            
            return false;
        }
    }

    private function addItems($quotation_id, $items) {
        if (empty($quotation_id) || empty($items)) {
            return false;
        }

        try {
            $insert_sql = "INSERT INTO `" . CONST_TBL_PREFIX . "quotation_items` 
                (quotation_id, sl_no, pr, material, description, quantity, item_id, sku, make, 
                unit_price, discount_percent, gst_percent, hsn_code, delivery, 
                created_on, created_by, created_from_ip) 
                VALUES ";
            
            $created_by = $GLOBALS['loggedindata'][0]['id'] ?? null;
            $created_from_ip = \eBizIndia\getRemoteIP();
            $dttm = date('Y-m-d H:i:s');

            $values = [];
            $params = [];
            
            foreach ($items as $index => $item) {
                $prefix = "item_" . $index;
                
                $values[] = "(:quotation_id, :sl_no_$prefix, :pr_$prefix, :material_$prefix, :description_$prefix, 
                            :quantity_$prefix, :item_id_$prefix, :sku_$prefix, :make_$prefix, 
                            :unit_price_$prefix, :discount_percent_$prefix, :gst_percent_$prefix, 
                            :hsn_code_$prefix, :delivery_$prefix, 
                            :created_on, :created_by, :created_from_ip)";
                
                $params[":sl_no_$prefix"] = $item['sl_no'];
                $params[":pr_$prefix"] = $item['pr'] ?? null;
                $params[":material_$prefix"] = $item['material'];
                $params[":description_$prefix"] = $item['description'];
                $params[":quantity_$prefix"] = $item['quantity'];
                $params[":item_id_$prefix"] = $item['item_id'] ?? null;
                $params[":sku_$prefix"] = $item['sku'] ?? null;
                $params[":make_$prefix"] = $item['make'] ?? null;
                $params[":unit_price_$prefix"] = $item['unit_price'];
                $params[":discount_percent_$prefix"] = $item['discount_percent'] ?? 0.00;
                $params[":gst_percent_$prefix"] = $item['gst_percent'] ?? 0;
                $params[":hsn_code_$prefix"] = $item['hsn_code'] ?? null;
                $params[":delivery_$prefix"] = $item['delivery'] ?? null;
            }
            
            $params[":quotation_id"] = $quotation_id;
            $params[":created_on"] = $dttm;
            $params[":created_by"] = $created_by;
            $params[":created_from_ip"] = $created_from_ip;
            
            $insert_sql .= implode(', ', $values);
            PDOConn::query($insert_sql, $params);
            
            return true;
        } catch (\Exception $e) {
            ErrorHandler::logError(
                [
                    'function' => __METHOD__,
                    'quotation_id' => $quotation_id,
                    'items' => $items
                ],
                $e
            );
            return false;
        }
    }

    public function getItems() {
        if (empty($this->quotation_id)) {
            return false;
        }

        $sql = "SELECT qi.*, i.sku, i.short_desc FROM `" . CONST_TBL_PREFIX . "quotation_items` qi JOIN `" . CONST_TBL_PREFIX . "items`  i ON qi.item_id=i.id 
                WHERE quotation_id = :quotation_id 
                ORDER BY sl_no ASC";

        try {
            $stmt_obj = PDOConn::query($sql, [':quotation_id' => $this->quotation_id]);
            return $stmt_obj->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            ErrorHandler::logError(
                [
                    'function' => __METHOD__,
                    'quotation_id' => $this->quotation_id
                ],
                $e
            );
            return false;
        }
    }
    
    /*public static function generateQuotationNumber() {
        // Generate a unique quotation number format: QT-YYYYMMDD-XXXX
        $date_part = date('Ymd');
        $sql = "SELECT MAX(SUBSTRING_INDEX(quotation_no, '-', -1)) as last_num 
                FROM `" . CONST_TBL_PREFIX . "quotations` 
                WHERE quotation_no LIKE :pattern";
                
        try {
            $pattern = "QT-$date_part-%";
            $stmt_obj = PDOConn::query($sql, [':pattern' => $pattern]);
            $result = $stmt_obj->fetch(\PDO::FETCH_ASSOC);
            
            $last_num = (int)($result['last_num'] ?? 0);
            $new_num = $last_num + 1;
            
            return "QT-$date_part-" . str_pad($new_num, 4, '0', STR_PAD_LEFT);
        } catch (\Exception $e) {
            ErrorHandler::logError(
                [
                    'function' => __METHOD__
                ],
                $e
            );
            // Fallback if query fails
            return "QT-$date_part-" . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
    }*/
    
    public static function delete($quotation_id) {
        if (empty($quotation_id)) {
            return false;
        }

        try {
            $conn = PDOConn::getInstance();
            $conn->beginTransaction();
            
            // Delete items first
            $delete_items_sql = "DELETE FROM `" . CONST_TBL_PREFIX . "quotation_items` WHERE quotation_id = :quotation_id";
            PDOConn::query($delete_items_sql, [':quotation_id' => $quotation_id]);
            
            // Then delete the quotation header
            $delete_quotation_sql = "DELETE FROM `" . CONST_TBL_PREFIX . "quotations` WHERE id = :id";
            PDOConn::query($delete_quotation_sql, [':id' => $quotation_id]);
            
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            ErrorHandler::logError(
                [
                    'function' => __METHOD__,
                    'quotation_id' => $quotation_id
                ],
                $e
            );
            
            if (isset($conn) && $conn->inTransaction()) {
                $conn->rollBack();
            }
            
            return false;
        }
    }
}