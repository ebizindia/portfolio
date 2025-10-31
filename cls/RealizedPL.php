<?php
namespace eBizIndia;

/**
 * RealizedPL class - Manages realized profit/loss records
 */
class RealizedPL {
    private $pl_id;

    public function __construct(?int $pl_id = null) {
        $this->pl_id = $pl_id;
    }

    /**
     * Get realized P&L list with filters, sorting, pagination
     * @param array $options
     * @return array|false
     */
    public static function getList($options = []) {
        $data = [];
        $fields_mapper = [];

        $fields_mapper['*'] = 'rpl.pl_id, rpl.portfolio_id, rpl.stock_code,
                    rpl.sell_date, rpl.quantity_sold, rpl.sell_price,
                    rpl.avg_buy_price, rpl.realized_pl, rpl.created_at,
                    p.portfolio_name';
        $fields_mapper['recordcount'] = 'COUNT(DISTINCT rpl.pl_id)';
        $fields_mapper['sum_pl'] = 'SUM(rpl.realized_pl)';
        $fields_mapper['pl_id'] = 'rpl.pl_id';
        $fields_mapper['portfolio_id'] = 'rpl.portfolio_id';
        $fields_mapper['stock_code'] = 'rpl.stock_code';
        $fields_mapper['sell_date'] = 'rpl.sell_date';
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
                    case 'pl_id':
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

                    case 'sell_date':
                        $fld = $fields_mapper['sell_date'];
                        $type = $filter['type'] ?? '=';
                        $v = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                        $where_clause[] = $fld . ' ' . $type . ' :whr' . $field_counter . '_';
                        $str_params_to_bind[':whr' . $field_counter . '_'] = $v;
                        break;

                    case 'date_range':
                        if (!empty($filter['start_date'])) {
                            $where_clause[] = $fields_mapper['sell_date'] . ' >= :start_date';
                            $str_params_to_bind[':start_date'] = $filter['start_date'];
                        }
                        if (!empty($filter['end_date'])) {
                            $where_clause[] = $fields_mapper['sell_date'] . ' <= :end_date';
                            $str_params_to_bind[':end_date'] = $filter['end_date'];
                        }
                        break;

                    case 'year':
                        $where_clause[] = 'YEAR(' . $fields_mapper['sell_date'] . ') = :year';
                        $int_params_to_bind[':year'] = $filter['value'];
                        break;

                    case 'financial_year':
                        // FY starts from April 1
                        $fy_year = $filter['value'];
                        $fy_start = $fy_year . '-04-01';
                        $fy_end = ($fy_year + 1) . '-03-31';
                        $where_clause[] = $fields_mapper['sell_date'] . ' >= :fy_start';
                        $where_clause[] = $fields_mapper['sell_date'] . ' <= :fy_end';
                        $str_params_to_bind[':fy_start'] = $fy_start;
                        $str_params_to_bind[':fy_end'] = $fy_end;
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
            } elseif (in_array('sum_pl', $options['fieldstofetch'])) {
                $select_string = $fields_mapper['sum_pl'] . ' as sum_pl';
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
            $order_by = ' ORDER BY rpl.sell_date DESC';
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
            $join_clause = " LEFT JOIN `portfolios` p ON rpl.portfolio_id = p.portfolio_id";
        }

        $sql = "SELECT {$select_string}
                FROM `realized_pl` rpl
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
     * Get total realized P&L for a portfolio
     * @param int $portfolio_id
     * @param string|null $start_date
     * @param string|null $end_date
     * @return float|false
     */
    public static function getTotalPL(int $portfolio_id, $start_date = null, $end_date = null) {
        $options = [
            'filters' => [
                ['field' => 'portfolio_id', 'value' => $portfolio_id]
            ],
            'fieldstofetch' => ['sum_pl']
        ];

        if ($start_date || $end_date) {
            $options['filters'][] = [
                'field' => 'date_range',
                'start_date' => $start_date,
                'end_date' => $end_date
            ];
        }

        $result = self::getList($options);
        return !empty($result) ? ($result[0]['sum_pl'] ?? 0) : 0;
    }

    /**
     * Get realized P&L by stock
     * @param int $portfolio_id
     * @param string|null $start_date
     * @param string|null $end_date
     * @return array|false
     */
    public static function getPLByStock(int $portfolio_id, $start_date = null, $end_date = null) {
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

        $records = self::getList($options);

        if ($records === false) {
            return false;
        }

        // Aggregate by stock
        $by_stock = [];
        foreach ($records as $record) {
            $stock_code = $record['stock_code'];

            if (!isset($by_stock[$stock_code])) {
                $by_stock[$stock_code] = [
                    'stock_code' => $stock_code,
                    'total_pl' => 0,
                    'total_quantity_sold' => 0,
                    'transaction_count' => 0
                ];
            }

            $by_stock[$stock_code]['total_pl'] += $record['realized_pl'];
            $by_stock[$stock_code]['total_quantity_sold'] += $record['quantity_sold'];
            $by_stock[$stock_code]['transaction_count']++;
        }

        return array_values($by_stock);
    }

    /**
     * Get realized P&L by year
     * @param int $portfolio_id
     * @return array|false
     */
    public static function getPLByYear(int $portfolio_id) {
        $sql = "SELECT
                    YEAR(sell_date) as year,
                    SUM(realized_pl) as total_pl,
                    COUNT(*) as transaction_count,
                    SUM(quantity_sold) as total_quantity_sold
                FROM `realized_pl`
                WHERE portfolio_id = :portfolio_id
                GROUP BY YEAR(sell_date)
                ORDER BY year DESC";

        try {
            $stmt = PDOConn::query($sql, [], [':portfolio_id' => $portfolio_id]);
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
     * Get realized P&L by financial year
     * @param int $portfolio_id
     * @return array|false
     */
    public static function getPLByFinancialYear(int $portfolio_id) {
        $sql = "SELECT
                    CASE
                        WHEN MONTH(sell_date) >= 4 THEN YEAR(sell_date)
                        ELSE YEAR(sell_date) - 1
                    END as fy_year,
                    SUM(realized_pl) as total_pl,
                    COUNT(*) as transaction_count,
                    SUM(quantity_sold) as total_quantity_sold
                FROM `realized_pl`
                WHERE portfolio_id = :portfolio_id
                GROUP BY fy_year
                ORDER BY fy_year DESC";

        try {
            $stmt = PDOConn::query($sql, [], [':portfolio_id' => $portfolio_id]);
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
     * Get combined realized P&L for multiple portfolios
     * @param array $portfolio_ids
     * @param string|null $start_date
     * @param string|null $end_date
     * @return float|false
     */
    public static function getCombinedPL(array $portfolio_ids, $start_date = null, $end_date = null) {
        if (empty($portfolio_ids)) {
            return 0;
        }

        $options = [
            'filters' => [
                ['field' => 'portfolio_id', 'value' => $portfolio_ids, 'type' => 'IN']
            ],
            'fieldstofetch' => ['sum_pl']
        ];

        if ($start_date || $end_date) {
            $options['filters'][] = [
                'field' => 'date_range',
                'start_date' => $start_date,
                'end_date' => $end_date
            ];
        }

        $result = self::getList($options);
        return !empty($result) ? ($result[0]['sum_pl'] ?? 0) : 0;
    }

    /**
     * Get P&L summary for portfolio
     * @param int $portfolio_id
     * @return array|false
     */
    public static function getSummary(int $portfolio_id) {
        $total_pl = self::getTotalPL($portfolio_id);

        if ($total_pl === false) {
            return false;
        }

        // Get positive and negative separately
        $sql = "SELECT
                    SUM(CASE WHEN realized_pl > 0 THEN realized_pl ELSE 0 END) as total_profit,
                    SUM(CASE WHEN realized_pl < 0 THEN realized_pl ELSE 0 END) as total_loss,
                    COUNT(CASE WHEN realized_pl > 0 THEN 1 END) as profitable_trades,
                    COUNT(CASE WHEN realized_pl < 0 THEN 1 END) as losing_trades,
                    COUNT(*) as total_trades
                FROM `realized_pl`
                WHERE portfolio_id = :portfolio_id";

        try {
            $stmt = PDOConn::query($sql, [], [':portfolio_id' => $portfolio_id]);
            $stats = $stmt->fetch(\PDO::FETCH_ASSOC);

            return [
                'total_realized_pl' => $total_pl,
                'total_profit' => $stats['total_profit'] ?? 0,
                'total_loss' => abs($stats['total_loss'] ?? 0),
                'profitable_trades' => $stats['profitable_trades'] ?? 0,
                'losing_trades' => $stats['losing_trades'] ?? 0,
                'total_trades' => $stats['total_trades'] ?? 0,
                'win_rate' => $stats['total_trades'] > 0 ?
                    ($stats['profitable_trades'] / $stats['total_trades']) * 100 : 0
            ];
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }
}
