<?php
namespace eBizIndia;

/**
 * BenchmarkData class - Manages benchmark index data for performance comparison
 */
class BenchmarkData {
    private $benchmark_id;

    public function __construct(?int $benchmark_id = null) {
        $this->benchmark_id = $benchmark_id;
    }

    /**
     * Get benchmark data list with filters
     * @param array $options
     * @return array|false
     */
    public static function getList($options = []) {
        $data = [];
        $fields_mapper = [];

        $fields_mapper['*'] = 'b.benchmark_id, b.index_name, b.date, b.close_value, b.return_pct, b.created_at';
        $fields_mapper['recordcount'] = 'COUNT(DISTINCT b.benchmark_id)';
        $fields_mapper['benchmark_id'] = 'b.benchmark_id';
        $fields_mapper['index_name'] = 'b.index_name';
        $fields_mapper['date'] = 'b.date';

        $where_clause = [];
        $str_params_to_bind = [];
        $int_params_to_bind = [];

        // Build filters
        if (array_key_exists('filters', $options) && is_array($options['filters'])) {
            $field_counter = 0;
            foreach ($options['filters'] as $filter) {
                ++$field_counter;
                switch ($filter['field']) {
                    case 'benchmark_id':
                        $val = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                        $where_clause[] = $fields_mapper['benchmark_id'] . ' = :whr' . $field_counter . '_';
                        $int_params_to_bind[':whr' . $field_counter . '_'] = $val;
                        break;

                    case 'index_name':
                        $v = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                        $where_clause[] = $fields_mapper['index_name'] . ' = :whr' . $field_counter . '_';
                        $str_params_to_bind[':whr' . $field_counter . '_'] = $v;
                        break;

                    case 'date':
                        $type = $filter['type'] ?? '=';
                        $v = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                        $where_clause[] = $fields_mapper['date'] . ' ' . $type . ' :whr' . $field_counter . '_';
                        $str_params_to_bind[':whr' . $field_counter . '_'] = $v;
                        break;

                    case 'date_range':
                        if (!empty($filter['start_date'])) {
                            $where_clause[] = $fields_mapper['date'] . ' >= :start_date';
                            $str_params_to_bind[':start_date'] = $filter['start_date'];
                        }
                        if (!empty($filter['end_date'])) {
                            $where_clause[] = $fields_mapper['date'] . ' <= :end_date';
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
            }
        }

        // Order by
        $order_by = '';
        if (!empty($options['order_by'])) {
            $order_parts = [];
            foreach ($options['order_by'] as $order) {
                if (isset($fields_mapper[$order['field']])) {
                    $order_parts[] = $fields_mapper[$order['field']] . ' ' . ($order['type'] ?? 'ASC');
                }
            }
            if (!empty($order_parts)) {
                $order_by = ' ORDER BY ' . implode(', ', $order_parts);
            }
        } else if (!$record_count) {
            $order_by = ' ORDER BY b.date DESC';
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
                FROM `benchmark_data` b
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
            ErrorHandler::logError(['function' => __METHOD__, 'sql' => $sql], $e);
            return false;
        }
    }

    /**
     * Add benchmark data
     * @param array $data
     * @return int|false
     */
    public function add(array $data) {
        if (empty($data)) {
            return false;
        }

        $data['created_at'] = date('Y-m-d H:i:s');

        $sql = "INSERT INTO `benchmark_data` SET ";
        $values = [];
        $str_data = [];

        foreach ($data as $field => $value) {
            $key = ":{$field}";
            if ($value === '' || $value === null) {
                $values[] = "`{$field}` = NULL";
            } else {
                $values[] = "`{$field}` = {$key}";
                $str_data[$key] = $value;
            }
        }

        $sql .= implode(', ', $values);

        try {
            $stmt = PDOConn::query($sql, $str_data);
            return PDOConn::lastInsertId();
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Get benchmark return between two dates
     * @param string $index_name Index name (e.g., 'NIFTY50', 'SENSEX')
     * @param string|null $start_date
     * @param string|null $end_date
     * @return float|false Return as percentage or false
     */
    public static function getReturn(string $index_name, $start_date = null, $end_date = null) {
        $end_date = $end_date ?? date('Y-m-d');

        // Get end value
        $end_data = self::getList([
            'filters' => [
                ['field' => 'index_name', 'value' => $index_name],
                ['field' => 'date', 'value' => $end_date, 'type' => '<=']
            ],
            'order_by' => [
                ['field' => 'date', 'type' => 'DESC']
            ],
            'recs_per_page' => 1,
            'page' => 1
        ]);

        if (empty($end_data)) {
            return false;
        }

        $end_value = $end_data[0]['close_value'];

        // If no start date, get first available date
        if (!$start_date) {
            $start_data = self::getList([
                'filters' => [
                    ['field' => 'index_name', 'value' => $index_name]
                ],
                'order_by' => [
                    ['field' => 'date', 'type' => 'ASC']
                ],
                'recs_per_page' => 1,
                'page' => 1
            ]);
        } else {
            $start_data = self::getList([
                'filters' => [
                    ['field' => 'index_name', 'value' => $index_name],
                    ['field' => 'date', 'value' => $start_date, 'type' => '>=']
                ],
                'order_by' => [
                    ['field' => 'date', 'type' => 'ASC']
                ],
                'recs_per_page' => 1,
                'page' => 1
            ]);
        }

        if (empty($start_data)) {
            return false;
        }

        $start_value = $start_data[0]['close_value'];

        if ($start_value == 0) {
            return false;
        }

        // Calculate return percentage
        $return = (($end_value - $start_value) / $start_value) * 100;

        return $return;
    }

    /**
     * Import benchmark data from CSV
     * @param string $file_path Path to CSV file
     * @param string $index_name Index name
     * @return array ['imported' => int, 'skipped' => int, 'errors' => array]
     */
    public static function importFromCSV(string $file_path, string $index_name) {
        $result = ['imported' => 0, 'skipped' => 0, 'errors' => []];

        if (!file_exists($file_path)) {
            $result['errors'][] = 'File not found';
            return $result;
        }

        if (($handle = fopen($file_path, 'r')) === false) {
            $result['errors'][] = 'Cannot open file';
            return $result;
        }

        // Expect CSV format: date,close_value
        $headers = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) {
                continue;
            }

            $date = trim($row[0]);
            $close_value = (float)trim($row[1]);

            if (empty($date) || $close_value <= 0) {
                $result['skipped']++;
                continue;
            }

            // Check if already exists
            $existing = self::getList([
                'filters' => [
                    ['field' => 'index_name', 'value' => $index_name],
                    ['field' => 'date', 'value' => $date]
                ]
            ]);

            if (!empty($existing)) {
                $result['skipped']++;
                continue;
            }

            // Insert
            $benchmark = new self();
            if ($benchmark->add([
                'index_name' => $index_name,
                'date' => $date,
                'close_value' => $close_value
            ])) {
                $result['imported']++;
            } else {
                $result['errors'][] = "Failed to import row: {$date}";
            }
        }

        fclose($handle);

        // Calculate return percentages
        self::calculateReturns($index_name);

        return $result;
    }

    /**
     * Calculate return percentages for all benchmark data
     * @param string $index_name
     * @return bool
     */
    public static function calculateReturns(string $index_name) {
        $data = self::getList([
            'filters' => [
                ['field' => 'index_name', 'value' => $index_name]
            ],
            'order_by' => [
                ['field' => 'date', 'type' => 'ASC']
            ]
        ]);

        if (empty($data)) {
            return false;
        }

        $prev_value = null;

        foreach ($data as $record) {
            if ($prev_value !== null) {
                $return_pct = (($record['close_value'] - $prev_value) / $prev_value) * 100;

                // Update return percentage
                $sql = "UPDATE `benchmark_data`
                        SET return_pct = :return_pct
                        WHERE benchmark_id = :id";

                try {
                    PDOConn::query($sql, [], [
                        ':return_pct' => $return_pct,
                        ':id' => $record['benchmark_id']
                    ]);
                } catch (\Exception $e) {
                    error_log("Failed to update return for benchmark_id {$record['benchmark_id']}");
                }
            }

            $prev_value = $record['close_value'];
        }

        return true;
    }

    /**
     * Get available benchmark indices
     * @return array|false
     */
    public static function getAvailableIndices() {
        $sql = "SELECT DISTINCT index_name
                FROM `benchmark_data`
                ORDER BY index_name";

        try {
            $stmt = PDOConn::query($sql, [], []);
            return $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }
}
