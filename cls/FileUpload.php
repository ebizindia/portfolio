<?php
namespace eBizIndia;

class FileUpload {
    private $upload_id;

    public function __construct(?int $upload_id = null) {
        $this->upload_id = $upload_id;
    }

    /**
     * Get file upload list with filters, sorting, pagination
     * @param array $options
     * @return array|false
     */
    public static function getList($options = []) {
        $data = [];
        $fields_mapper = [];

        $fields_mapper['*'] = 'f.upload_id, f.file_name, f.file_path, f.file_size,
                    f.upload_date, f.status, f.validation_errors, f.records_count,
                    f.uploaded_by, f.uploaded_at, f.uploaded_from,
                    u.first_name, u.last_name';
        $fields_mapper['recordcount'] = 'COUNT(DISTINCT f.upload_id)';
        $fields_mapper['upload_id'] = 'f.upload_id';
        $fields_mapper['file_name'] = 'f.file_name';
        $fields_mapper['status'] = 'f.status';
        $fields_mapper['upload_date'] = 'f.upload_date';
        $fields_mapper['uploaded_by'] = 'f.uploaded_by';

        $where_clause = [];
        $str_params_to_bind = [];
        $int_params_to_bind = [];

        // Build filters
        if (array_key_exists('filters', $options) && is_array($options['filters'])) {
            $field_counter = 0;
            foreach ($options['filters'] as $filter) {
                ++$field_counter;
                switch ($filter['field']) {
                    case 'upload_id':
                    case 'uploaded_by':
                        $fld = $fields_mapper[$filter['field']];
                        $val = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                        $ph = ":whr{$field_counter}_";
                        $where_clause[] = $fld . ' = ' . $ph;
                        $int_params_to_bind[$ph] = $val;
                        break;

                    case 'file_name':
                        $fld = $fields_mapper['file_name'];
                        $type = $filter['type'] ?? 'CONTAINS';
                        switch ($type) {
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

                    case 'status':
                        $fld = $fields_mapper['status'];
                        $v = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                        $where_clause[] = $fld . ' = :whr' . $field_counter . '_';
                        $str_params_to_bind[':whr' . $field_counter . '_'] = $v;
                        break;

                    case 'upload_date':
                        $fld = $fields_mapper['upload_date'];
                        $type = $filter['type'] ?? '=';
                        $v = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                        $where_clause[] = $fld . ' ' . $type . ' :whr' . $field_counter . '_';
                        $str_params_to_bind[':whr' . $field_counter . '_'] = $v;
                        break;

                    case 'date_range':
                        if (!empty($filter['start_date'])) {
                            $where_clause[] = $fields_mapper['upload_date'] . ' >= :start_date';
                            $str_params_to_bind[':start_date'] = $filter['start_date'];
                        }
                        if (!empty($filter['end_date'])) {
                            $where_clause[] = $fields_mapper['upload_date'] . ' <= :end_date';
                            $str_params_to_bind[':end_date'] = $filter['end_date'];
                        }
                        break;
                }
            }
        }

        // Select fields
        $select_string = $fields_mapper['*'];
        $record_count = false;

        if (!empty($options['fieldstofetch'])) {
            if (in_array('recordcount', $options['fieldstofetch'])) {
                $select_string = $fields_mapper['recordcount'];
                $record_count = true;
            } else {
                // Build select string from requested fields
                $selected_fields = [];
                foreach ($options['fieldstofetch'] as $field) {
                    if (isset($fields_mapper[$field])) {
                        $selected_fields[] = $fields_mapper[$field] . ' AS ' . $field;
                    }
                }
                if (!empty($selected_fields)) {
                    $select_string = implode(', ', $selected_fields);
                }
            }
        }

        // Order by
        $order_by = '';
        if (!empty($options['order_by'])) {
            $order_parts = [];
            foreach ($options['order_by'] as $order) {
                if (isset($fields_mapper[$order['field']])) {
                    $order_parts[] = $fields_mapper[$order['field']] .
                        ' ' . ($order['type'] ?? 'ASC');
                }
            }
            if (!empty($order_parts)) {
                $order_by = ' ORDER BY ' . implode(', ', $order_parts);
            }
        } else if (!$record_count) {
            $order_by = ' ORDER BY f.uploaded_at DESC';
        }

        // Pagination
        $limit = '';
        if (!empty($options['page']) && !empty($options['recs_per_page'])) {
            $offset = ($options['page'] - 1) * $options['recs_per_page'];
            $limit = " LIMIT {$offset}, {$options['recs_per_page']}";
        }

        // Build query
        $where_string = '';
        if (!empty($where_clause)) {
            $where_string = ' WHERE ' . implode(' AND ', $where_clause);
        }

        $join_clause = '';
        if (!$record_count) {
            $join_clause = " LEFT JOIN `users` u ON f.uploaded_by = u.id";
        }

        $sql = "SELECT {$select_string}
                FROM `file_uploads` f
                {$join_clause}
                {$where_string}
                {$order_by}
                {$limit}";

        try {
            $stmt = PDOConn::query($sql, $str_params_to_bind, $int_params_to_bind);
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $data[] = $row;
            }
            return $data;
        } catch (\Exception $e) {
            ErrorHandler::logError([
                'function' => __METHOD__,
                'sql' => $sql
            ], $e);
            return false;
        }
    }

    /**
     * Add new file upload record
     * @param array $data
     * @return int|false Last insert ID or false
     */
    public function add(array $data) {
        if (empty($data)) {
            return false;
        }

        // Add audit fields
        $data['uploaded_at'] = date('Y-m-d H:i:s');
        $data['uploaded_by'] = $GLOBALS['loggedindata'][0]['id'] ?? null;
        $data['uploaded_from'] = \eBizIndia\getRemoteIP();

        $sql = "INSERT INTO `file_uploads` SET ";
        $values = [];
        $str_data = [];
        $int_data = [];

        foreach ($data as $field => $value) {
            $key = ":{$field}";
            if ($value === '' || $value === null) {
                $values[] = "`{$field}` = NULL";
            } else {
                $values[] = "`{$field}` = {$key}";
                if (in_array($field, ['uploaded_by', 'file_size', 'records_count'])) {
                    $int_data[$key] = $value;
                } else {
                    $str_data[$key] = $value;
                }
            }
        }

        $sql .= implode(', ', $values);

        try {
            $stmt = PDOConn::query($sql, $str_data, $int_data);
            return PDOConn::lastInsertId();
        } catch (\Exception $e) {
            ErrorHandler::logError([
                'function' => __METHOD__,
                'sql' => $sql,
                'data' => $data
            ], $e);
            return false;
        }
    }

    /**
     * Update file upload record
     * @param array $data
     * @return bool|null
     */
    public function update($data) {
        if (empty($data) || empty($this->upload_id)) {
            return false;
        }

        $sql = "UPDATE `file_uploads` SET ";
        $values = [];
        $str_data = [];
        $int_data = [':upload_id' => $this->upload_id];

        foreach ($data as $field => $value) {
            $key = ":{$field}";
            if ($value === '' || $value === null) {
                $values[] = "`{$field}` = NULL";
            } else {
                $values[] = "`{$field}` = {$key}";
                if (in_array($field, ['records_count', 'file_size'])) {
                    $int_data[$key] = $value;
                } else {
                    $str_data[$key] = $value;
                }
            }
        }

        $sql .= implode(', ', $values);
        $sql .= " WHERE `upload_id` = :upload_id";

        try {
            $stmt = PDOConn::query($sql, $str_data, $int_data);
            $affected = $stmt->rowCount();
            return $affected > 0 ? true : null;
        } catch (\Exception $e) {
            ErrorHandler::logError([
                'function' => __METHOD__,
                'sql' => $sql
            ], $e);
            return false;
        }
    }

    /**
     * Get file upload details
     * @param array $fields_to_fetch
     * @return array|false
     */
    public function getDetails($fields_to_fetch = []) {
        if (empty($this->upload_id)) {
            return false;
        }

        $options = [
            'filters' => [
                ['field' => 'upload_id', 'value' => $this->upload_id]
            ]
        ];

        if (!empty($fields_to_fetch)) {
            $options['fieldstofetch'] = $fields_to_fetch;
        }

        $result = self::getList($options);
        return !empty($result) ? $result[0] : false;
    }

    /**
     * Delete file upload record
     * @return bool
     */
    public function delete() {
        if (empty($this->upload_id)) {
            return false;
        }

        $sql = "DELETE FROM `file_uploads`
                WHERE upload_id = :id";

        try {
            PDOConn::query($sql, [], [':id' => $this->upload_id]);
            return true;
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Check if duplicate file exists
     * @param string $file_name
     * @param string $upload_date
     * @return array|false
     */
    public static function checkDuplicate($file_name, $upload_date) {
        $options = [
            'filters' => [
                ['field' => 'file_name', 'value' => $file_name, 'type' => '='],
                ['field' => 'upload_date', 'value' => $upload_date]
            ]
        ];

        return self::getList($options);
    }
}
