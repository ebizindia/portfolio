<?php
namespace eBizIndia;

class Portfolio {
    private $portfolio_id;

    public function __construct(?int $portfolio_id = null) {
        $this->portfolio_id = $portfolio_id;
    }

    /**
     * Get portfolio list with filters, sorting, pagination
     * @param array $options
     * @return array|false
     */
    public static function getList($options = []) {
        $data = [];
        $fields_mapper = [];

        $fields_mapper['*'] = 'p.portfolio_id, p.portfolio_name, p.portfolio_type,
                    p.description, p.status, p.created_at, p.created_by,
                    p.updated_at, p.updated_by';
        $fields_mapper['recordcount'] = 'COUNT(DISTINCT p.portfolio_id)';
        $fields_mapper['portfolio_id'] = 'p.portfolio_id';
        $fields_mapper['portfolio_name'] = 'p.portfolio_name';
        $fields_mapper['portfolio_type'] = 'p.portfolio_type';
        $fields_mapper['status'] = 'p.status';
        $fields_mapper['created_at'] = 'p.created_at';

        $where_clause = [];
        $str_params_to_bind = [];
        $int_params_to_bind = [];

        // Build filters
        if (array_key_exists('filters', $options) && is_array($options['filters'])) {
            $field_counter = 0;
            foreach ($options['filters'] as $filter) {
                ++$field_counter;
                switch ($filter['field']) {
                    case 'portfolio_id':
                        $fld = $fields_mapper['portfolio_id'];
                        $val = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                        $ph = ":whr{$field_counter}_";
                        $where_clause[] = $fld . ' = ' . $ph;
                        $int_params_to_bind[$ph] = $val;
                        break;

                    case 'portfolio_name':
                        $fld = $fields_mapper['portfolio_name'];
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

                    case 'portfolio_type':
                        $fld = $fields_mapper['portfolio_type'];
                        $v = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                        $where_clause[] = $fld . ' = :whr' . $field_counter . '_';
                        $str_params_to_bind[':whr' . $field_counter . '_'] = $v;
                        break;

                    case 'status':
                        $fld = $fields_mapper['status'];
                        $v = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                        $where_clause[] = $fld . ' = :whr' . $field_counter . '_';
                        $str_params_to_bind[':whr' . $field_counter . '_'] = $v;
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
            $order_by = ' ORDER BY p.portfolio_name ASC';
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

        $sql = "SELECT {$select_string}
                FROM `portfolios` p
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
     * Add new portfolio
     * @param array $data
     * @return int|false Last insert ID or false
     */
    public function add(array $data) {
        if (empty($data)) {
            return false;
        }

        // Add audit fields
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = $GLOBALS['loggedindata'][0]['id'] ?? null;
        $data['created_from'] = getRemoteIP();

        $sql = "INSERT INTO `portfolios` SET ";
        $values = [];
        $str_data = [];
        $int_data = [];

        foreach ($data as $field => $value) {
            $key = ":{$field}";
            if ($value === '' || $value === null) {
                $values[] = "`{$field}` = NULL";
            } else {
                $values[] = "`{$field}` = {$key}";
                if (in_array($field, ['created_by', 'updated_by'])) {
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
     * Update portfolio
     * @param array $data
     * @return bool|null
     */
    public function update($data) {
        if (empty($data) || empty($this->portfolio_id)) {
            return false;
        }

        // Add audit fields
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['updated_by'] = $GLOBALS['loggedindata'][0]['id'] ?? null;
        $data['updated_from'] = getRemoteIP();

        $sql = "UPDATE `portfolios` SET ";
        $values = [];
        $str_data = [];
        $int_data = [':portfolio_id' => $this->portfolio_id];

        foreach ($data as $field => $value) {
            $key = ":{$field}";
            if ($value === '' || $value === null) {
                $values[] = "`{$field}` = NULL";
            } else {
                $values[] = "`{$field}` = {$key}";
                if (in_array($field, ['created_by', 'updated_by'])) {
                    $int_data[$key] = $value;
                } else {
                    $str_data[$key] = $value;
                }
            }
        }

        $sql .= implode(', ', $values);
        $sql .= " WHERE `portfolio_id` = :portfolio_id";

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
     * Get portfolio details
     * @param array $fields_to_fetch
     * @return array|false
     */
    public function getDetails($fields_to_fetch = []) {
        if (empty($this->portfolio_id)) {
            return false;
        }

        $options = [
            'filters' => [
                ['field' => 'portfolio_id', 'value' => $this->portfolio_id]
            ]
        ];

        if (!empty($fields_to_fetch)) {
            $options['fieldstofetch'] = $fields_to_fetch;
        }

        return self::getList($options);
    }

    /**
     * Delete portfolio (soft delete by setting status)
     * @return bool|null
     */
    public function delete() {
        return $this->update(['status' => 'Inactive']);
    }

    /**
     * Get total invested amount for portfolio
     * @return float|false
     */
    public function getTotalInvested() {
        if (empty($this->portfolio_id)) {
            return false;
        }

        $sql = "SELECT SUM(transaction_value) as total
                FROM `transactions`
                WHERE portfolio_id = :portfolio_id
                AND transaction_type = 'BUY'";

        try {
            $stmt = PDOConn::query($sql, [], [':portfolio_id' => $this->portfolio_id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row['total'] ?? 0;
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Get current value of portfolio
     * @return float|false
     */
    public function getCurrentValue() {
        if (empty($this->portfolio_id)) {
            return false;
        }

        $sql = "SELECT SUM(current_value) as total
                FROM `holdings`
                WHERE portfolio_id = :portfolio_id";

        try {
            $stmt = PDOConn::query($sql, [], [':portfolio_id' => $this->portfolio_id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row['total'] ?? 0;
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Get unrealized P&L for portfolio
     * @return float|false
     */
    public function getUnrealizedPL() {
        if (empty($this->portfolio_id)) {
            return false;
        }

        $sql = "SELECT SUM(unrealized_pl) as total
                FROM `holdings`
                WHERE portfolio_id = :portfolio_id";

        try {
            $stmt = PDOConn::query($sql, [], [':portfolio_id' => $this->portfolio_id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row['total'] ?? 0;
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Get realized P&L for portfolio
     * @param string|null $start_date
     * @param string|null $end_date
     * @return float|false
     */
    public function getRealizedPL($start_date = null, $end_date = null) {
        if (empty($this->portfolio_id)) {
            return false;
        }

        $sql = "SELECT SUM(realized_pl) as total
                FROM `realized_pl`
                WHERE portfolio_id = :portfolio_id";

        $str_params = [];
        $int_params = [':portfolio_id' => $this->portfolio_id];

        if ($start_date) {
            $sql .= " AND sell_date >= :start_date";
            $str_params[':start_date'] = $start_date;
        }

        if ($end_date) {
            $sql .= " AND sell_date <= :end_date";
            $str_params[':end_date'] = $end_date;
        }

        try {
            $stmt = PDOConn::query($sql, $str_params, $int_params);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row['total'] ?? 0;
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }
}
