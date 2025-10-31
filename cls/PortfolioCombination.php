<?php
namespace eBizIndia;

class PortfolioCombination {
    private $combination_id;

    public function __construct(?int $combination_id = null) {
        $this->combination_id = $combination_id;
    }

    /**
     * Get combination list with portfolios
     * @param array $options
     * @return array|false
     */
    public static function getList($options = []) {
        $data = [];
        $fields_mapper = [];

        $fields_mapper['*'] = 'pc.combination_id, pc.combination_name, pc.description,
                    pc.created_at, pc.created_by, pc.updated_at';
        $fields_mapper['recordcount'] = 'COUNT(DISTINCT pc.combination_id)';
        $fields_mapper['combination_id'] = 'pc.combination_id';
        $fields_mapper['combination_name'] = 'pc.combination_name';
        $fields_mapper['created_at'] = 'pc.created_at';

        $where_clause = [];
        $str_params_to_bind = [];
        $int_params_to_bind = [];

        // Build filters
        if (array_key_exists('filters', $options) && is_array($options['filters'])) {
            $field_counter = 0;
            foreach ($options['filters'] as $filter) {
                ++$field_counter;
                switch ($filter['field']) {
                    case 'combination_id':
                        $fld = $fields_mapper['combination_id'];
                        $val = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                        $ph = ":whr{$field_counter}_";
                        $where_clause[] = $fld . ' = ' . $ph;
                        $int_params_to_bind[$ph] = $val;
                        break;

                    case 'combination_name':
                        $fld = $fields_mapper['combination_name'];
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

        // Add portfolio information if not counting
        if (!$record_count) {
            $select_string .= ", GROUP_CONCAT(p.portfolio_name ORDER BY p.portfolio_name SEPARATOR ', ') as portfolio_names,
                               COUNT(DISTINCT pcm.portfolio_id) as portfolio_count";
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
            $order_by = ' ORDER BY pc.combination_name ASC';
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
        $group_by = '';
        if (!$record_count) {
            $join_clause = " LEFT JOIN `portfolio_combination_mapping` pcm
                                ON pc.combination_id = pcm.combination_id
                            LEFT JOIN `portfolios` p
                                ON pcm.portfolio_id = p.portfolio_id";
            $group_by = ' GROUP BY pc.combination_id';
        }

        $sql = "SELECT {$select_string}
                FROM `portfolio_combinations` pc
                {$join_clause}
                {$where_string}
                {$group_by}
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
     * Add new combination
     * @param array $data
     * @param array $portfolio_ids
     * @return int|false
     */
    public function add(array $data, array $portfolio_ids = []) {
        if (empty($data['combination_name'])) {
            return false;
        }

        $data['created_by'] = $GLOBALS['loggedindata'][0]['id'] ?? null;
        $data['created_at'] = date('Y-m-d H:i:s');

        try {
            $conn = PDOConn::getInstance();
            $conn->beginTransaction();

            // Insert combination
            $sql = "INSERT INTO `portfolio_combinations`
                    SET combination_name = :name, description = :desc,
                        created_by = :created_by, created_at = :created_at";

            $str_params = [
                ':name' => $data['combination_name'],
                ':desc' => $data['description'] ?? ''
            ];
            $int_params = [
                ':created_by' => $data['created_by']
            ];
            $str_params[':created_at'] = $data['created_at'];

            $stmt = PDOConn::query($sql, $str_params, $int_params);

            $combination_id = PDOConn::lastInsertId();

            // Insert portfolio mappings
            if (!empty($portfolio_ids)) {
                $this->combination_id = $combination_id;
                $this->addPortfolios($portfolio_ids);
            }

            $conn->commit();
            return $combination_id;

        } catch (\Exception $e) {
            if ($conn && $conn->inTransaction()) {
                $conn->rollBack();
            }
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Update combination
     * @param array $data
     * @param array $portfolio_ids
     * @return bool|null
     */
    public function update(array $data, array $portfolio_ids = null) {
        if (empty($this->combination_id)) {
            return false;
        }

        try {
            $conn = PDOConn::getInstance();
            $conn->beginTransaction();

            // Update combination
            $data['updated_at'] = date('Y-m-d H:i:s');

            $sql = "UPDATE `portfolio_combinations`
                    SET combination_name = :name, description = :desc,
                        updated_at = :updated_at
                    WHERE combination_id = :id";

            $str_params = [
                ':name' => $data['combination_name'],
                ':desc' => $data['description'] ?? '',
                ':updated_at' => $data['updated_at']
            ];
            $int_params = [
                ':id' => $this->combination_id
            ];

            $stmt = PDOConn::query($sql, $str_params, $int_params);
            $affected = $stmt->rowCount();

            // Update portfolio mappings if provided
            if ($portfolio_ids !== null) {
                // Delete existing mappings
                $this->removeAllPortfolios();
                // Add new mappings
                if (!empty($portfolio_ids)) {
                    $this->addPortfolios($portfolio_ids);
                }
            }

            $conn->commit();
            return $affected > 0 ? true : null;

        } catch (\Exception $e) {
            if ($conn && $conn->inTransaction()) {
                $conn->rollBack();
            }
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Get combination details
     * @param array $fields_to_fetch
     * @return array|false
     */
    public function getDetails($fields_to_fetch = []) {
        if (empty($this->combination_id)) {
            return false;
        }

        $options = [
            'filters' => [
                ['field' => 'combination_id', 'value' => $this->combination_id]
            ]
        ];

        if (!empty($fields_to_fetch)) {
            $options['fieldstofetch'] = $fields_to_fetch;
        }

        return self::getList($options);
    }

    /**
     * Delete combination
     * @return bool
     */
    public function delete() {
        if (empty($this->combination_id)) {
            return false;
        }

        $sql = "DELETE FROM `portfolio_combinations`
                WHERE combination_id = :id";

        try {
            PDOConn::query($sql, [], [':id' => $this->combination_id]);
            return true;
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Add portfolios to combination
     * @param array $portfolio_ids
     * @return bool
     */
    public function addPortfolios(array $portfolio_ids) {
        if (empty($this->combination_id) || empty($portfolio_ids)) {
            return false;
        }

        $sql = "INSERT IGNORE INTO `portfolio_combination_mapping`
                (combination_id, portfolio_id) VALUES ";

        $values = [];
        $int_params = [':cid' => $this->combination_id];

        foreach ($portfolio_ids as $idx => $pid) {
            $values[] = "(:cid, :pid{$idx})";
            $int_params[":pid{$idx}"] = $pid;
        }

        $sql .= implode(', ', $values);

        try {
            PDOConn::query($sql, [], $int_params);
            return true;
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Remove all portfolios from combination
     * @return bool
     */
    public function removeAllPortfolios() {
        if (empty($this->combination_id)) {
            return false;
        }

        $sql = "DELETE FROM `portfolio_combination_mapping`
                WHERE combination_id = :cid";

        try {
            PDOConn::query($sql, [], [':cid' => $this->combination_id]);
            return true;
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Get portfolios in combination
     * @return array|false
     */
    public function getPortfolios() {
        if (empty($this->combination_id)) {
            return false;
        }

        $sql = "SELECT p.*
                FROM `portfolios` p
                JOIN `portfolio_combination_mapping` pcm
                    ON p.portfolio_id = pcm.portfolio_id
                WHERE pcm.combination_id = :cid
                AND p.status = 'Active'
                ORDER BY p.portfolio_name";

        try {
            $stmt = PDOConn::query($sql, [], [':cid' => $this->combination_id]);
            $data = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $data[] = $row;
            }
            return $data;
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Get portfolio IDs in combination
     * @return array|false
     */
    public function getPortfolioIds() {
        $portfolios = $this->getPortfolios();
        if ($portfolios === false) {
            return false;
        }
        return array_column($portfolios, 'portfolio_id');
    }

    /**
     * Get total invested amount for combination
     * @return float|false
     */
    public function getTotalInvested() {
        if (empty($this->combination_id)) {
            return false;
        }

        $sql = "SELECT SUM(t.transaction_value) as total
                FROM `transactions` t
                JOIN `portfolio_combination_mapping` pcm
                    ON t.portfolio_id = pcm.portfolio_id
                WHERE pcm.combination_id = :cid
                AND t.transaction_type = 'BUY'";

        try {
            $stmt = PDOConn::query($sql, [], [':cid' => $this->combination_id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row['total'] ?? 0;
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Get current value of combination
     * @return float|false
     */
    public function getCurrentValue() {
        if (empty($this->combination_id)) {
            return false;
        }

        $sql = "SELECT SUM(h.current_value) as total
                FROM `holdings` h
                JOIN `portfolio_combination_mapping` pcm
                    ON h.portfolio_id = pcm.portfolio_id
                WHERE pcm.combination_id = :cid";

        try {
            $stmt = PDOConn::query($sql, [], [':cid' => $this->combination_id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row['total'] ?? 0;
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Get performance metrics for combination
     * @param string|null $end_date
     * @return array|false
     */
    public function getPerformanceMetrics($end_date = null) {
        if (empty($this->combination_id)) {
            return false;
        }

        $portfolio_ids = $this->getPortfolioIds();
        if (empty($portfolio_ids)) {
            return false;
        }

        return PerformanceCalculator::calculateCombined($portfolio_ids, $end_date);
    }

    /**
     * Get combined holdings for combination
     * @return array|false
     */
    public function getCombinedHoldings() {
        if (empty($this->combination_id)) {
            return false;
        }

        $portfolio_ids = $this->getPortfolioIds();
        if (empty($portfolio_ids)) {
            return [];
        }

        return Holding::getCombinedHoldings($portfolio_ids);
    }

    /**
     * Get combined realized P&L for combination
     * @param string|null $start_date
     * @param string|null $end_date
     * @return float|false
     */
    public function getCombinedRealizedPL($start_date = null, $end_date = null) {
        if (empty($this->combination_id)) {
            return false;
        }

        $portfolio_ids = $this->getPortfolioIds();
        if (empty($portfolio_ids)) {
            return 0;
        }

        return RealizedPL::getCombinedPL($portfolio_ids, $start_date, $end_date);
    }

    /**
     * Get combination summary with performance
     * @return array|false
     */
    public function getSummary() {
        if (empty($this->combination_id)) {
            return false;
        }

        $portfolio_ids = $this->getPortfolioIds();
        if (empty($portfolio_ids)) {
            return false;
        }

        // Basic metrics
        $total_invested = 0;
        $current_value = 0;
        $unrealized_pl = 0;

        foreach ($portfolio_ids as $pid) {
            $total_invested += Holding::getTotalInvested($pid);
            $current_value += Holding::getPortfolioValue($pid);
            $unrealized_pl += Holding::getUnrealizedPL($pid);
        }

        $realized_pl = RealizedPL::getCombinedPL($portfolio_ids);
        $total_pl = $unrealized_pl + $realized_pl;

        // Performance metrics
        $xirr = XIRRCalculator::calculateCombinedXIRR($portfolio_ids);
        $roce = $total_invested > 0 ? ($total_pl / $total_invested) * 100 : 0;

        return [
            'combination_id' => $this->combination_id,
            'portfolio_count' => count($portfolio_ids),
            'total_invested' => $total_invested,
            'current_value' => $current_value,
            'unrealized_pl' => $unrealized_pl,
            'realized_pl' => $realized_pl,
            'total_pl' => $total_pl,
            'total_pl_pct' => $total_invested > 0 ? ($total_pl / $total_invested) * 100 : 0,
            'xirr' => $xirr !== false ? $xirr * 100 : null,
            'roce' => $roce
        ];
    }

    /**
     * Get portfolio breakdown for combination
     * @return array|false
     */
    public function getPortfolioBreakdown() {
        if (empty($this->combination_id)) {
            return false;
        }

        $portfolios = $this->getPortfolios();
        if (empty($portfolios)) {
            return [];
        }

        $breakdown = [];

        foreach ($portfolios as $portfolio) {
            $pid = $portfolio['portfolio_id'];

            $breakdown[] = [
                'portfolio_id' => $pid,
                'portfolio_name' => $portfolio['portfolio_name'],
                'portfolio_type' => $portfolio['portfolio_type'],
                'total_invested' => Holding::getTotalInvested($pid),
                'current_value' => Holding::getPortfolioValue($pid),
                'unrealized_pl' => Holding::getUnrealizedPL($pid),
                'realized_pl' => RealizedPL::getTotalPL($pid)
            ];
        }

        return $breakdown;
    }

    /**
     * Get top holdings across combination
     * @param int $limit
     * @return array|false
     */
    public function getTopHoldings($limit = 10) {
        if (empty($this->combination_id)) {
            return false;
        }

        $combined_holdings = $this->getCombinedHoldings();
        if (empty($combined_holdings)) {
            return [];
        }

        // Sort by current value descending
        usort($combined_holdings, function($a, $b) {
            return ($b['current_value'] ?? 0) <=> ($a['current_value'] ?? 0);
        });

        return array_slice($combined_holdings, 0, $limit);
    }
}
