<?php
namespace eBizIndia;

class CustomerVisitReport {
    private $visit_report_id;

    public function __construct(?int $visit_report_id = null) {
        $this->visit_report_id = $visit_report_id;
    }

    public function getDetails($fields_to_fetch = []) {
        if (empty($this->visit_report_id)) {
            return false;
        }

        $options = [];
        $options['filters'] = [
            ['field' => 'id', 'type' => '=', 'value' => $this->visit_report_id]
        ];

        if (!empty($fields_to_fetch)) {
            $options['fieldstofetch'] = $fields_to_fetch;
        }

        $result = self::getList($options);
        return $result ? $result[0] : false;
    }

    public static function getList(array $options = []): array|bool {
        $data = [];
        $fields_mapper = [];

        // Field mapping
        $fields_mapper['*'] = "vr.id, vr.customer_id, vr.department, vr.type, vr.visit_date, vr.meeting_title, 
                               vr.detailed_notes, vr.attachment_file_name, vr.attachment_file_path, 
                               vr.attachment_file_type, vr.attachment_file_size, vr.admin_notes,
                               vr.admin_notes_updated_by, vr.admin_notes_updated_on, vr.admin_notes_updated_from_ip,
                               vr.created_by, vr.created_on, vr.created_from_ip,
                               c.name as customer_name, 
                               creator.name as created_by_name,
                               cg.name as customer_group_name,
                               admin_updater.name as admin_notes_updated_by_name";

        $fields_mapper['recordcount'] = 'count(distinct vr.id)';
        $fields_mapper['id'] = 'vr.id';
        $fields_mapper['customer_id'] = 'vr.customer_id';
        $fields_mapper['customer_group_id'] = 'c.customer_group_id';
        $fields_mapper['user_group_id'] = 'creator.user_group_id';
        $fields_mapper['department'] = 'vr.department';
        $fields_mapper['type'] = 'vr.type';
        $fields_mapper['visit_date'] = 'vr.visit_date';
        $fields_mapper['meeting_title'] = 'vr.meeting_title';
        $fields_mapper['detailed_notes'] = 'vr.detailed_notes';
        $fields_mapper['customer_name'] = 'c.name';
        $fields_mapper['created_by'] = 'vr.created_by';
        $fields_mapper['created_by_name'] = 'creator.name';
        $fields_mapper['created_on'] = 'vr.created_on';
        $fields_mapper['customer_group_name'] = 'cg.name';
        $fields_mapper['attachment_file_path'] = 'vr.attachment_file_path';
        $fields_mapper['attachment_file_name'] = 'vr.attachment_file_name';
        $fields_mapper['admin_notes_updated_by_name'] = 'admin_updater.name';

        $where_clause = [];
        $str_params_to_bind = [];
        $int_params_to_bind = [];
        $filter_symbols = [
            'EQUAL' => ' = ',
            '=' => ' = ',
            'NOT_EQUAL' => ' != ',
            '!=' => ' != ',
            'GREATER_THAN' => ' > ',
            '>' => ' > ',
            '>=' => ' >= ',
            'GREATER_THAN_EQUAL' => ' >= ',
            'LESS_THAN' => ' < ',
            '<' => ' < ',
            'LESS_THAN_EQUAL' => ' <= ',
            '<=' => ' <= ',
            'CONTAINS' => ' like ',
            'like' => ' like ',

        ];

        // Handle filters
        if (array_key_exists('filters', $options) && is_array($options['filters'])) {
            $field_counter = 0;
            foreach ($options['filters'] as $filter) {
                ++$field_counter;
                switch ($filter['field']) {
                    case 'id':
                    case 'customer_id':
                    case 'customer_group_id':
                    case 'user_group_id':
                    case 'department':
                    case 'type':
                    case 'created_by':
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
                                    $where_clause[] = $fld . ' IN(' . implode(',', $place_holders) . ')';
                                }
                                break;
                            default:
                                $val = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $ph = ":whr{$field_counter}_";
                                $where_clause[] = $fld . $filter_symbols[$filter['type']] . $ph;
                                $int_params_to_bind[$ph] = $val;
                        }
                        break;

                    case 'customer_name':
                    case 'meeting_title':
                    case 'created_by_name':
                    case 'customer_group_name':
                        $fld = $fields_mapper[$filter['field']];
                        switch ($filter['type']) {
                            case 'CONTAINS':
                                $v = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $where_clause[] = $fld . ' LIKE :whr' . $field_counter . '_';
                                $str_params_to_bind[':whr' . $field_counter . '_'] = '%' . $v . '%';
                                break;
                            default:
                                $v = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $where_clause[] = $fld . ' = :whr' . $field_counter . '_';
                                $str_params_to_bind[':whr' . $field_counter . '_'] = $v;
                        }
                        break;

                    case 'visit_date':
                        switch ($filter['type']) {
                            case 'BETWEEN':
                                if (is_array($filter['value']) && count($filter['value']) == 2) {
                                    $where_clause[] = "vr.visit_date BETWEEN :whr{$field_counter}_start AND :whr{$field_counter}_end";
                                    $str_params_to_bind[":whr{$field_counter}_start"] = $filter['value'][0];
                                    $str_params_to_bind[":whr{$field_counter}_end"] = $filter['value'][1];
                                }
                                break;
                            case '>=':
                            case '>':
                            case '<=':
                            case '<':
                            case '=':
                                $v = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $where_clause[] = "vr.visit_date " . $filter['type'] . " :whr{$field_counter}_";
                                $str_params_to_bind[":whr{$field_counter}_"] = $v;
                                break;
                        }
                        break;
                }
            }
        }

        // Field selection logic
        $select_string = $fields_mapper['*'];
        $record_count = false;

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

        $select_string = ($record_count) ? $select_string : 'DISTINCT ' . $select_string;

        // Ordering logic
        $order_by_clause = '';
        if (array_key_exists('order_by', $options) && is_array($options['order_by'])) {
            foreach ($options['order_by'] as $order) {
                if (array_key_exists($order['field'], $fields_mapper)) {
                    if (!preg_match("/\s*as\s*" . $order['field'] . "/", $select_string)) {
                        $select_string .= ", " . $fields_mapper[$order['field']] . ' as ' . $order['field'];
                    }
                    $order_by_clause .= ", " . $order['field'];
                }

                if (array_key_exists('type', $order) && $order['type'] == 'DESC') {
                    $order_by_clause .= ' DESC';
                }
            }

            $order_by_clause = trim($order_by_clause, ",");
            if ($order_by_clause != '') {
                $order_by_clause = ' ORDER BY ' . $order_by_clause;
            }

            if ($order_by_clause != '' && !stristr($order_by_clause, 'vr.id')) {
                $order_by_clause .= ', vr.id DESC';
            }
        }

        if (!$record_count && $order_by_clause == '') {
            $order_by_clause = " ORDER BY vr.visit_date DESC, vr.id DESC";
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
        /*$cg_join = '';
        if(preg_match("/cg\./", "$select_string $where_clause_string $order_by_clause"))
            $cg_join = " LEFT JOIN `" . CONST_TBL_PREFIX . "customers` c ON c.id = vr.customer_id
                LEFT JOIN `" . CONST_TBL_PREFIX . "customer_groups` cg ON cg.id = c.customer_group_id";*/

        $admin_notes_user_join = '';
        if(preg_match("/admin_updater\./", "$select_string $where_clause_string $order_by_clause"))
            $admin_notes_user_join = " LEFT JOIN `" . CONST_TBL_PREFIX . "users` admin_user ON vr.admin_notes_updated_by = admin_user.id
                LEFT JOIN `" . CONST_TBL_PREFIX . "members` admin_updater ON admin_user.profile_id = admin_updater.id AND admin_user.profile_type = 'member' ";

        $creator_join = " LEFT JOIN `" . CONST_TBL_PREFIX . "users` creator_user ON vr.created_by = creator_user.id
                LEFT JOIN `" . CONST_TBL_PREFIX . "members` creator ON creator_user.profile_id = creator.id AND creator_user.profile_type = 'member' ";
        if(!$record_count){

            $sql = "SELECT $select_string 
                FROM `" . CONST_TBL_PREFIX . "customer_visit_reports` vr
                JOIN `" . CONST_TBL_PREFIX . "customers` c ON vr.customer_id = c.id 
                LEFT JOIN `" . CONST_TBL_PREFIX . "customer_groups` cg ON cg.id = c.customer_group_id
                $creator_join 
                $admin_notes_user_join 
                $where_clause_string 
                $order_by_clause 
                $limit_clause";
        }else{
            $sql = "SELECT $select_string 
                FROM `" . CONST_TBL_PREFIX . "customer_visit_reports` vr
                JOIN `" . CONST_TBL_PREFIX . "customers` c ON vr.customer_id = c.id 
                $creator_join 
                $where_clause_string ";
        }

        try {
            $pdo_stmt_obj = PDOConn::query($sql, $str_params_to_bind, $int_params_to_bind);
//            ob_clean();
//            $pdo_stmt_obj->debugDumpParams();
//            $dd = ob_get_contents();
//            ErrorHandler::logError([__METHOD__.': Sql: '.print_r($dd, true)]);
//            ob_clean();
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

        $sql = "INSERT INTO `" . CONST_TBL_PREFIX . "customer_visit_reports` SET ";
        $values = [];
        $str_data = [];

        // Add tracking information
        $data['created_on'] = date('Y-m-d H:i:s');
        $data['created_by'] = $GLOBALS['loggedindata'][0]['id'] ?? null;
        $data['created_from_ip'] = \eBizIndia\getRemoteIP();

        foreach ($data as $field => $value) {
            $key = ":$field";
            if ($value === '' || $value === null) {
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

    public function updateAdminNotes($admin_notes) {
        if (empty($this->visit_report_id)) {
            return false;
        }

        $data = [
            'admin_notes' => $admin_notes,
            'admin_notes_updated_by' => $GLOBALS['loggedindata'][0]['id'] ?? null,
            'admin_notes_updated_on' => date('Y-m-d H:i:s'),
            'admin_notes_updated_from_ip' => \eBizIndia\getRemoteIP()
        ];

        $sql = "UPDATE `" . CONST_TBL_PREFIX . "customer_visit_reports` SET ";
        $values = [];
        $str_data = [];
        $int_data = [':id' => $this->visit_report_id];

        foreach ($data as $field => $value) {
            $key = ":$field";
            if ($value === '' || $value === null) {
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
            return $stmt_obj->rowCount() > 0;
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
        if (empty($this->visit_report_id) || empty($contacts)) {
            return false;
        }

        try {
            $insert_sql = "INSERT INTO `" . CONST_TBL_PREFIX . "visit_report_contacts` 
                (visit_report_id, contact_id, name, department, designation, email, phone, is_new_contact) 
                VALUES ";

            $values = [];
            $params = [":visit_report_id" => $this->visit_report_id];
            $key_suffix = 0;

            foreach ($contacts as $contact) {
                $key_suffix++;
                $values[] = "(:visit_report_id, :contact_id_{$key_suffix}, :name_{$key_suffix}, :department_{$key_suffix}, :designation_{$key_suffix}, :email_{$key_suffix}, :phone_{$key_suffix}, :is_new_contact_{$key_suffix})";

                $params[":contact_id_{$key_suffix}"] = $contact['contact_id'] ?? null;
                $params[":name_{$key_suffix}"] = $contact['name'];
                $params[":department_{$key_suffix}"] = $contact['department'] ?? null;
                $params[":designation_{$key_suffix}"] = $contact['designation'] ?? null;
                $params[":email_{$key_suffix}"] = $contact['email'] ?? null;
                $params[":phone_{$key_suffix}"] = $contact['phone'] ?? null;
                $params[":is_new_contact_{$key_suffix}"] = $contact['is_new_contact'] ?? 0;
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
        if (empty($this->visit_report_id)) {
            return false;
        }

        $sql = "SELECT * FROM `" . CONST_TBL_PREFIX . "visit_report_contacts` 
                WHERE visit_report_id = :visit_report_id 
                ORDER BY name";

        try {
            $stmt_obj = PDOConn::query($sql, [':visit_report_id' => $this->visit_report_id]);
            return $stmt_obj->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            ErrorHandler::logError(
                [
                    'function' => __METHOD__,
                    'visit_report_id' => $this->visit_report_id
                ],
                $e
            );
            return false;
        }
    }

    public function validateAttachment($file_data) {
        if (empty($file_data) || $file_data['error'] == UPLOAD_ERR_NO_FILE) {
            return ['valid' => true]; // No file is okay
        }

        if ($file_data['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'message' => 'File upload error occurred.'];
        }

        // Check file size
        if ($file_data['size'] > CONST_VISIT_REPORT_MAX_FILE_SIZE) {
            return ['valid' => false, 'message' => 'File size exceeds maximum allowed size.'];
        }

        // Check file extension
        $file_ext = strtolower(pathinfo($file_data['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, CONST_VISIT_REPORT_ALLOWED_EXTENSIONS)) {
            return ['valid' => false, 'message' => 'File type not allowed.'];
        }

        // Check MIME type
        if (!in_array($file_data['type'], CONST_MIME_TYPES[$file_ext] ?? [])) {
            return ['valid' => false, 'message' => 'Invalid file type.'];
        }

        return ['valid' => true];
    }

    public function uploadAttachment($file_data) {
        if (empty($this->visit_report_id)) {
            return false;
        }

        $validation = $this->validateAttachment($file_data);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }

        if ($file_data['error'] == UPLOAD_ERR_NO_FILE) {
            return ['success' => true]; // No file to upload
        }

        $upload_dir = CONST_UPLOAD_DIR_PATH . CONST_VISIT_REPORT_DIR . '/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                return ['success' => false, 'message' => 'Could not create upload directory.'];
            }
        }

        $file_ext = strtolower(pathinfo($file_data['name'], PATHINFO_EXTENSION));
        $unique_filename = 'visit_report_' . $this->visit_report_id . '_' . time() . '_' . uniqid() . '.' . $file_ext;
        $file_path = $upload_dir . $unique_filename;

        if (move_uploaded_file($file_data['tmp_name'], $file_path)) {
            return [
                'success' => true,
                'file_name' => $file_data['name'],
                'file_path' => $unique_filename,
                'file_type' => $file_data['type'],
                'file_size' => $file_data['size']
            ];
        }

        return ['success' => false, 'message' => 'Failed to upload file.'];
    }

    public function canUserAccess($user_id, $user_role) {
        if (empty($this->visit_report_id)) {
            return false;
        }

        // ADMIN can access all records
        if (strcasecmp($user_role, 'ADMIN') === 0) {
            return true;
        }

        // Others can only access their own records
        $options = [
            'filters' => [
                ['field' => 'id', 'type' => '=', 'value' => $this->visit_report_id],
                ['field' => 'created_by', 'type' => '=', 'value' => $user_id]
            ],
            'fieldstofetch' => ['id']
        ];

        $result = self::getList($options);
        return !empty($result);
    }

    public function delete() {
        if (empty($this->visit_report_id)) {
            return false;
        }

        try {
            $conn = PDOConn::getInstance();
            $conn->beginTransaction();

            // First, get the record details to check for attachment
            $record_details = $this->getDetails(['attachment_file_path']);
            if (!$record_details) {
                throw new Exception('Visit report not found.');
            }

            // Delete the main visit report record
            // (visit_report_contacts will be deleted automatically due to foreign key CASCADE)
            $delete_report_sql = "DELETE FROM `" . CONST_TBL_PREFIX . "customer_visit_reports` 
                             WHERE id = :id";
            $stmt = PDOConn::query($delete_report_sql, [], [':id' => $this->visit_report_id]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Visit report could not be deleted.');
            }

            // Delete attachment file if it exists
            if (!empty($record_details['attachment_file_path'])) {
                $file_path = CONST_UPLOAD_DIR_PATH . CONST_VISIT_REPORT_DIR . '/' . $record_details['attachment_file_path'];
                if (file_exists($file_path)) {
                    if (!unlink($file_path)) {
                        // Log the error but don't fail the transaction
                        ErrorHandler::logError([
                            'function' => __METHOD__,
                            'message' => 'Could not delete attachment file',
                            'file_path' => $file_path,
                            'visit_report_id' => $this->visit_report_id
                        ]);
                    }
                }
            }

            $conn->commit();
            return true;

        } catch (\Exception $e) {
            if ($conn && $conn->inTransaction()) {
                $conn->rollBack();
            }

            ErrorHandler::logError([
                'function' => __METHOD__,
                'visit_report_id' => $this->visit_report_id
            ], $e);

            return false;
        }
    }
}