<?php
namespace eBizIndia;

class Transaction {
    private $transaction_id;

    public function __construct(?int $transaction_id = null) {
        $this->transaction_id = $transaction_id;
    }

    /**
     * Get transaction list with filters, sorting, pagination
     * @param array $options
     * @return array|false
     */
    public static function getList($options = []) {
        $data = [];
        $fields_mapper = [];

        $fields_mapper['*'] = 't.transaction_id, t.portfolio_id, t.transaction_date,
                    t.stock_code, t.stock_name, t.instrument_type, t.transaction_type,
                    t.quantity, t.price, t.transaction_value, t.expiry_date, t.strike_price,
                    t.upload_date, t.source_file, t.created_at,
                    p.portfolio_name';
        $fields_mapper['recordcount'] = 'COUNT(DISTINCT t.transaction_id)';
        $fields_mapper['transaction_id'] = 't.transaction_id';
        $fields_mapper['portfolio_id'] = 't.portfolio_id';
        $fields_mapper['transaction_date'] = 't.transaction_date';
        $fields_mapper['stock_code'] = 't.stock_code';
        $fields_mapper['stock_name'] = 't.stock_name';
        $fields_mapper['transaction_type'] = 't.transaction_type';
        $fields_mapper['portfolio_name'] = 'p.portfolio_name';

        $where_clause = [];
        $str_params_to_bind = [];
        $int_params_to_bind = [];

        // Build filters
        if (array_key_exists('filters', $options) && is_array($options['filters'])) {
            $field_counter = 0;
            foreach ($options['filters'] as $filter) {
                ++$field_counter;
                switch ($filter['field']) {
                    case 'transaction_id':
                    case 'portfolio_id':
                        $fld = $fields_mapper[$filter['field']];
                        $type = $filter['type'] ?? '=';
                        switch ($type) {
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
                                $where_clause[] = $fld . ' ' . $type . ' ' . $ph;
                                $int_params_to_bind[$ph] = $val;
                        }
                        break;

                    case 'stock_code':
                    case 'stock_name':
                    case 'portfolio_name':
                        $fld = $fields_mapper[$filter['field']];
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

                    case 'transaction_type':
                        $fld = $fields_mapper['transaction_type'];
                        $v = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                        $where_clause[] = $fld . ' = :whr' . $field_counter . '_';
                        $str_params_to_bind[':whr' . $field_counter . '_'] = $v;
                        break;

                    case 'transaction_date':
                        $fld = $fields_mapper['transaction_date'];
                        $type = $filter['type'] ?? '=';
                        $v = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                        $where_clause[] = $fld . ' ' . $type . ' :whr' . $field_counter . '_';
                        $str_params_to_bind[':whr' . $field_counter . '_'] = $v;
                        break;

                    case 'date_range':
                        if (!empty($filter['start_date'])) {
                            $where_clause[] = $fields_mapper['transaction_date'] . ' >= :start_date';
                            $str_params_to_bind[':start_date'] = $filter['start_date'];
                        }
                        if (!empty($filter['end_date'])) {
                            $where_clause[] = $fields_mapper['transaction_date'] . ' <= :end_date';
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
            $order_by = ' ORDER BY t.transaction_date DESC, t.transaction_id DESC';
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
            $join_clause = " LEFT JOIN `portfolios` p ON t.portfolio_id = p.portfolio_id";
        }

        $sql = "SELECT {$select_string}
                FROM `transactions` t
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
     * Add new transaction
     * @param array $data
     * @return int|false Last insert ID or false
     */
    public function add(array $data) {
        if (empty($data)) {
            return false;
        }

        // Set default values
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['upload_date'])) {
            $data['upload_date'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['instrument_type'])) {
            $data['instrument_type'] = 'Spot';
        }

        $sql = "INSERT INTO `transactions` SET ";
        $values = [];
        $str_data = [];
        $int_data = [];

        foreach ($data as $field => $value) {
            $key = ":{$field}";
            if ($value === '' || $value === null) {
                $values[] = "`{$field}` = NULL";
            } else {
                $values[] = "`{$field}` = {$key}";
                if (in_array($field, ['portfolio_id', 'transaction_id'])) {
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
     * Update transaction
     * @param array $data
     * @return bool|null
     */
    public function update($data) {
        if (empty($data) || empty($this->transaction_id)) {
            return false;
        }

        $sql = "UPDATE `transactions` SET ";
        $values = [];
        $str_data = [];
        $int_data = [':transaction_id' => $this->transaction_id];

        foreach ($data as $field => $value) {
            $key = ":{$field}";
            if ($value === '' || $value === null) {
                $values[] = "`{$field}` = NULL";
            } else {
                $values[] = "`{$field}` = {$key}";
                if (in_array($field, ['portfolio_id'])) {
                    $int_data[$key] = $value;
                } else {
                    $str_data[$key] = $value;
                }
            }
        }

        $sql .= implode(', ', $values);
        $sql .= " WHERE `transaction_id` = :transaction_id";

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
     * Get transaction details
     * @param array $fields_to_fetch
     * @return array|false
     */
    public function getDetails($fields_to_fetch = []) {
        if (empty($this->transaction_id)) {
            return false;
        }

        $options = [
            'filters' => [
                ['field' => 'transaction_id', 'value' => $this->transaction_id]
            ]
        ];

        if (!empty($fields_to_fetch)) {
            $options['fieldstofetch'] = $fields_to_fetch;
        }

        return self::getList($options);
    }

    /**
     * Delete transaction
     * @return bool
     */
    public function delete() {
        if (empty($this->transaction_id)) {
            return false;
        }

        $sql = "DELETE FROM `transactions`
                WHERE transaction_id = :id";

        try {
            PDOConn::query($sql, [], [':id' => $this->transaction_id]);
            return true;
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Get transactions for a portfolio within date range
     * @param int $portfolio_id
     * @param string|null $start_date
     * @param string|null $end_date
     * @return array|false
     */
    public static function getPortfolioTransactions($portfolio_id, $start_date = null, $end_date = null) {
        $options = [
            'filters' => [
                ['field' => 'portfolio_id', 'value' => $portfolio_id]
            ]
        ];

        if ($start_date || $end_date) {
            $options['filters'][] = [
                'field' => 'date_range',
                'start_date' => $start_date,
                'end_date' => $end_date
            ];
        }

        return self::getList($options);
    }

    /**
     * Get transactions for a stock within a portfolio
     * @param int $portfolio_id
     * @param string $stock_code
     * @return array|false
     */
    public static function getStockTransactions($portfolio_id, $stock_code) {
        $options = [
            'filters' => [
                ['field' => 'portfolio_id', 'value' => $portfolio_id],
                ['field' => 'stock_code', 'value' => $stock_code, 'type' => '=']
            ],
            'order_by' => [
                ['field' => 'transaction_date', 'type' => 'ASC'],
                ['field' => 'transaction_id', 'type' => 'ASC']
            ]
        ];

        return self::getList($options);
    }
}
