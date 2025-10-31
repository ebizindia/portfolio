<?php
namespace eBizIndia;

/**
 * Holding class - Manages current stock holdings in portfolios
 */
class Holding {
    private $holding_id;

    public function __construct(?int $holding_id = null) {
        $this->holding_id = $holding_id;
    }

    /**
     * Get holdings list with filters, sorting, pagination
     * @param array $options
     * @return array|false
     */
    public static function getList($options = []) {
        $data = [];
        $fields_mapper = [];

        $fields_mapper['*'] = 'h.holding_id, h.portfolio_id, h.stock_code, h.stock_name,
                    h.current_quantity, h.avg_cost_price, h.total_invested,
                    h.current_market_price, h.current_value, h.unrealized_pl,
                    h.last_updated, p.portfolio_name';
        $fields_mapper['recordcount'] = 'COUNT(DISTINCT h.holding_id)';
        $fields_mapper['holding_id'] = 'h.holding_id';
        $fields_mapper['portfolio_id'] = 'h.portfolio_id';
        $fields_mapper['stock_code'] = 'h.stock_code';
        $fields_mapper['stock_name'] = 'h.stock_name';
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
                    case 'holding_id':
                    case 'portfolio_id':
                        $fld = $fields_mapper[$filter['field']];
                        $type = $filter['type'] ?? '=';
                        if ($type === 'IN' && is_array($filter['value'])) {
                            $place_holders = [];
                            $k = 0;
                            foreach ($filter['value'] as $val) {
                                $k++;
                                $ph = ":whr{$field_counter}_{$k}_";
                                $place_holders[] = $ph;
                                $int_params_to_bind[$ph] = $val;
                            }
                            $where_clause[] = $fld . ' IN(' . implode(',', $place_holders) . ')';
                        } else {
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
            $order_by = ' ORDER BY h.stock_code ASC';
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
            $join_clause = " LEFT JOIN `portfolios` p ON h.portfolio_id = p.portfolio_id";
        }

        $sql = "SELECT {$select_string}
                FROM `holdings` h
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
     * Get holding details
     * @param array $fields_to_fetch
     * @return array|false
     */
    public function getDetails($fields_to_fetch = []) {
        if (empty($this->holding_id)) {
            return false;
        }

        $options = [
            'filters' => [
                ['field' => 'holding_id', 'value' => $this->holding_id]
            ]
        ];

        if (!empty($fields_to_fetch)) {
            $options['fieldstofetch'] = $fields_to_fetch;
        }

        $result = self::getList($options);
        return !empty($result) ? $result[0] : false;
    }

    /**
     * Update market price for a holding
     * @param float $market_price
     * @return bool
     */
    public function updateMarketPrice(float $market_price) {
        if (empty($this->holding_id)) {
            return false;
        }

        // Get current holding details
        $holding = $this->getDetails();
        if (!$holding) {
            return false;
        }

        $current_value = $holding['current_quantity'] * $market_price;
        $unrealized_pl = $current_value - $holding['total_invested'];

        $sql = "UPDATE `holdings`
                SET current_market_price = :market_price,
                    current_value = :current_value,
                    unrealized_pl = :unrealized_pl,
                    last_updated = NOW()
                WHERE holding_id = :holding_id";

        try {
            PDOConn::query($sql, [], [
                ':market_price' => $market_price,
                ':current_value' => $current_value,
                ':unrealized_pl' => $unrealized_pl,
                ':holding_id' => $this->holding_id
            ]);
            return true;
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Update market prices for all holdings in a portfolio
     * @param int $portfolio_id
     * @param array $prices Array of ['stock_code' => price]
     * @return array ['updated' => int, 'failed' => int]
     */
    public static function updatePortfolioPrices(int $portfolio_id, array $prices) {
        $result = ['updated' => 0, 'failed' => 0];

        $holdings = self::getList([
            'filters' => [
                ['field' => 'portfolio_id', 'value' => $portfolio_id]
            ]
        ]);

        if (!$holdings) {
            return $result;
        }

        foreach ($holdings as $holding) {
            $stock_code = $holding['stock_code'];

            if (isset($prices[$stock_code])) {
                $holding_obj = new self($holding['holding_id']);
                if ($holding_obj->updateMarketPrice($prices[$stock_code])) {
                    $result['updated']++;
                } else {
                    $result['failed']++;
                }
            }
        }

        return $result;
    }

    /**
     * Get total portfolio value
     * @param int $portfolio_id
     * @return float|false
     */
    public static function getPortfolioValue(int $portfolio_id) {
        $sql = "SELECT SUM(current_value) as total_value
                FROM `holdings`
                WHERE portfolio_id = :portfolio_id";

        try {
            $stmt = PDOConn::query($sql, [], [':portfolio_id' => $portfolio_id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row['total_value'] ?? 0;
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Get total invested amount
     * @param int $portfolio_id
     * @return float|false
     */
    public static function getTotalInvested(int $portfolio_id) {
        $sql = "SELECT SUM(total_invested) as total_invested
                FROM `holdings`
                WHERE portfolio_id = :portfolio_id";

        try {
            $stmt = PDOConn::query($sql, [], [':portfolio_id' => $portfolio_id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row['total_invested'] ?? 0;
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Get total unrealized P&L
     * @param int $portfolio_id
     * @return float|false
     */
    public static function getUnrealizedPL(int $portfolio_id) {
        $sql = "SELECT SUM(unrealized_pl) as unrealized_pl
                FROM `holdings`
                WHERE portfolio_id = :portfolio_id";

        try {
            $stmt = PDOConn::query($sql, [], [':portfolio_id' => $portfolio_id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row['unrealized_pl'] ?? 0;
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Get holdings summary for portfolio
     * @param int $portfolio_id
     * @return array|false
     */
    public static function getPortfolioSummary(int $portfolio_id) {
        $total_invested = self::getTotalInvested($portfolio_id);
        $current_value = self::getPortfolioValue($portfolio_id);
        $unrealized_pl = self::getUnrealizedPL($portfolio_id);

        if ($total_invested === false || $current_value === false || $unrealized_pl === false) {
            return false;
        }

        $unrealized_pl_pct = $total_invested > 0 ? ($unrealized_pl / $total_invested) * 100 : 0;

        return [
            'total_invested' => $total_invested,
            'current_value' => $current_value,
            'unrealized_pl' => $unrealized_pl,
            'unrealized_pl_pct' => $unrealized_pl_pct,
            'holdings_count' => count(self::getList([
                'filters' => [['field' => 'portfolio_id', 'value' => $portfolio_id]]
            ]))
        ];
    }

    /**
     * Get combined holdings for multiple portfolios
     * @param array $portfolio_ids
     * @return array|false
     */
    public static function getCombinedHoldings(array $portfolio_ids) {
        if (empty($portfolio_ids)) {
            return [];
        }

        $holdings = self::getList([
            'filters' => [
                ['field' => 'portfolio_id', 'value' => $portfolio_ids, 'type' => 'IN']
            ]
        ]);

        if ($holdings === false) {
            return false;
        }

        // Aggregate by stock code
        $combined = [];
        foreach ($holdings as $holding) {
            $stock_code = $holding['stock_code'];

            if (!isset($combined[$stock_code])) {
                $combined[$stock_code] = [
                    'stock_code' => $stock_code,
                    'stock_name' => $holding['stock_name'],
                    'total_quantity' => 0,
                    'total_invested' => 0,
                    'current_value' => 0,
                    'unrealized_pl' => 0
                ];
            }

            $combined[$stock_code]['total_quantity'] += $holding['current_quantity'];
            $combined[$stock_code]['total_invested'] += $holding['total_invested'];
            $combined[$stock_code]['current_value'] += $holding['current_value'] ?? 0;
            $combined[$stock_code]['unrealized_pl'] += $holding['unrealized_pl'] ?? 0;
        }

        // Calculate weighted average cost
        foreach ($combined as &$stock) {
            $stock['avg_cost_price'] = $stock['total_quantity'] > 0 ?
                $stock['total_invested'] / $stock['total_quantity'] : 0;
        }

        return array_values($combined);
    }
}
