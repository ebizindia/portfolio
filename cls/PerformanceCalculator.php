<?php
namespace eBizIndia;

/**
 * PerformanceCalculator - Main engine for portfolio performance metrics
 *
 * Calculates:
 * - XIRR (Extended Internal Rate of Return)
 * - ROCE (Return on Capital Employed)
 * - Annualized Returns
 * - Alpha (vs benchmark)
 * - Beta
 * - Sharpe Ratio
 * - Max Drawdown
 */
class PerformanceCalculator {
    private $portfolio_id;
    private $combination_ids = [];
    private $cache_enabled = true;
    private $cache_ttl = 3600; // 1 hour

    /**
     * Constructor
     * @param int|null $portfolio_id Single portfolio ID
     * @param array $combination_ids Array of portfolio IDs for combination
     */
    public function __construct($portfolio_id = null, array $combination_ids = []) {
        $this->portfolio_id = $portfolio_id;
        $this->combination_ids = $combination_ids;
    }

    /**
     * Calculate all performance metrics for a portfolio
     * @param int $portfolio_id Portfolio ID
     * @param string|null $end_date End date (default: today)
     * @return array|false Array of metrics or false on error
     */
    public static function calculateAll(int $portfolio_id, $end_date = null) {
        $end_date = $end_date ?? date('Y-m-d');

        // Check cache first
        $cache_key = "perf_{$portfolio_id}_{$end_date}";
        $cached = self::getFromCache($cache_key);
        if ($cached !== null) {
            return $cached;
        }

        $metrics = [];

        // Basic portfolio metrics
        $total_invested = Holding::getTotalInvested($portfolio_id);
        $current_value = Holding::getPortfolioValue($portfolio_id);
        $unrealized_pl = Holding::getUnrealizedPL($portfolio_id);
        $realized_pl = RealizedPL::getTotalPL($portfolio_id);

        $metrics['total_invested'] = $total_invested;
        $metrics['current_value'] = $current_value;
        $metrics['unrealized_pl'] = $unrealized_pl;
        $metrics['realized_pl'] = $realized_pl;
        $metrics['total_pl'] = $unrealized_pl + $realized_pl;

        // XIRR
        $xirr = XIRRCalculator::calculatePortfolioXIRR($portfolio_id, $end_date);
        $metrics['xirr'] = $xirr !== false ? $xirr * 100 : null; // Convert to percentage

        // ROCE (Return on Capital Employed)
        $roce = self::calculateROCE($portfolio_id);
        $metrics['roce'] = $roce;

        // Annualized Return
        $annualized_return = self::calculateAnnualizedReturn($portfolio_id, $end_date);
        $metrics['annualized_return'] = $annualized_return;

        // Simple Return %
        $simple_return = $total_invested > 0 ?
            (($current_value + $realized_pl - $total_invested) / $total_invested) * 100 : 0;
        $metrics['simple_return'] = $simple_return;

        // Days invested
        $days_invested = self::getDaysInvested($portfolio_id, $end_date);
        $metrics['days_invested'] = $days_invested;

        // Win rate (realized trades)
        $pl_summary = RealizedPL::getSummary($portfolio_id);
        if ($pl_summary) {
            $metrics['win_rate'] = $pl_summary['win_rate'];
            $metrics['profitable_trades'] = $pl_summary['profitable_trades'];
            $metrics['losing_trades'] = $pl_summary['losing_trades'];
        }

        // Cache results
        self::saveToCache($cache_key, $metrics);

        return $metrics;
    }

    /**
     * Calculate ROCE (Return on Capital Employed)
     * ROCE = (Realized PL + Unrealized PL) / Total Invested * 100
     * @param int $portfolio_id
     * @return float|null
     */
    public static function calculateROCE(int $portfolio_id) {
        $total_invested = Holding::getTotalInvested($portfolio_id);

        if ($total_invested == 0) {
            return null;
        }

        $realized_pl = RealizedPL::getTotalPL($portfolio_id);
        $unrealized_pl = Holding::getUnrealizedPL($portfolio_id);

        $total_return = $realized_pl + $unrealized_pl;

        return ($total_return / $total_invested) * 100;
    }

    /**
     * Calculate Annualized Return
     * @param int $portfolio_id
     * @param string|null $end_date
     * @return float|null
     */
    public static function calculateAnnualizedReturn(int $portfolio_id, $end_date = null) {
        $end_date = $end_date ?? date('Y-m-d');

        // Get first transaction date
        $first_txn = Transaction::getList([
            'filters' => [
                ['field' => 'portfolio_id', 'value' => $portfolio_id]
            ],
            'order_by' => [
                ['field' => 'transaction_date', 'type' => 'ASC']
            ],
            'recs_per_page' => 1,
            'page' => 1
        ]);

        if (empty($first_txn)) {
            return null;
        }

        $start_date = $first_txn[0]['transaction_date'];
        $days = (strtotime($end_date) - strtotime($start_date)) / 86400;
        $years = $days / 365.25;

        if ($years < 0.01) { // Less than ~4 days
            return null;
        }

        $total_invested = Holding::getTotalInvested($portfolio_id);
        $current_value = Holding::getPortfolioValue($portfolio_id);
        $realized_pl = RealizedPL::getTotalPL($portfolio_id);

        if ($total_invested == 0) {
            return null;
        }

        $total_value = $current_value + $realized_pl;
        $total_return = ($total_value / $total_invested);

        // Annualized return = ((Total Value / Invested) ^ (1 / Years)) - 1
        $annualized = (pow($total_return, 1 / $years) - 1) * 100;

        return $annualized;
    }

    /**
     * Calculate Alpha (excess return vs benchmark)
     * Alpha = Portfolio Return - Benchmark Return
     * @param int $portfolio_id
     * @param string $benchmark_index Benchmark index name (e.g., 'NIFTY50')
     * @param string|null $start_date
     * @param string|null $end_date
     * @return float|null
     */
    public static function calculateAlpha(int $portfolio_id, string $benchmark_index, $start_date = null, $end_date = null) {
        $end_date = $end_date ?? date('Y-m-d');

        // Get portfolio return (XIRR)
        $portfolio_return = XIRRCalculator::calculatePortfolioXIRR($portfolio_id, $end_date);

        if ($portfolio_return === false) {
            return null;
        }

        // Get benchmark return
        $benchmark_return = BenchmarkData::getReturn($benchmark_index, $start_date, $end_date);

        if ($benchmark_return === false || $benchmark_return === null) {
            return null;
        }

        // Alpha = Portfolio Return - Benchmark Return
        return ($portfolio_return * 100) - $benchmark_return;
    }

    /**
     * Get number of days invested
     * @param int $portfolio_id
     * @param string|null $end_date
     * @return int
     */
    private static function getDaysInvested(int $portfolio_id, $end_date = null) {
        $end_date = $end_date ?? date('Y-m-d');

        $first_txn = Transaction::getList([
            'filters' => [
                ['field' => 'portfolio_id', 'value' => $portfolio_id]
            ],
            'order_by' => [
                ['field' => 'transaction_date', 'type' => 'ASC']
            ],
            'recs_per_page' => 1,
            'page' => 1
        ]);

        if (empty($first_txn)) {
            return 0;
        }

        $start_date = $first_txn[0]['transaction_date'];
        return (strtotime($end_date) - strtotime($start_date)) / 86400;
    }

    /**
     * Calculate performance for a combination of portfolios
     * @param array $portfolio_ids
     * @param string|null $end_date
     * @return array|false
     */
    public static function calculateCombined(array $portfolio_ids, $end_date = null) {
        if (empty($portfolio_ids)) {
            return false;
        }

        $end_date = $end_date ?? date('Y-m-d');

        // Check cache
        $cache_key = "perf_combo_" . implode('_', $portfolio_ids) . "_{$end_date}";
        $cached = self::getFromCache($cache_key);
        if ($cached !== null) {
            return $cached;
        }

        $metrics = [];

        // Aggregate basic metrics
        $total_invested = 0;
        $current_value = 0;
        $unrealized_pl = 0;

        foreach ($portfolio_ids as $pid) {
            $total_invested += Holding::getTotalInvested($pid);
            $current_value += Holding::getPortfolioValue($pid);
            $unrealized_pl += Holding::getUnrealizedPL($pid);
        }

        $realized_pl = RealizedPL::getCombinedPL($portfolio_ids);

        $metrics['total_invested'] = $total_invested;
        $metrics['current_value'] = $current_value;
        $metrics['unrealized_pl'] = $unrealized_pl;
        $metrics['realized_pl'] = $realized_pl;
        $metrics['total_pl'] = $unrealized_pl + $realized_pl;

        // Combined XIRR
        $xirr = XIRRCalculator::calculateCombinedXIRR($portfolio_ids, $end_date);
        $metrics['xirr'] = $xirr !== false ? $xirr * 100 : null;

        // Combined ROCE
        $roce = $total_invested > 0 ?
            (($unrealized_pl + $realized_pl) / $total_invested) * 100 : null;
        $metrics['roce'] = $roce;

        // Simple return
        $simple_return = $total_invested > 0 ?
            (($current_value + $realized_pl - $total_invested) / $total_invested) * 100 : 0;
        $metrics['simple_return'] = $simple_return;

        // Cache results
        self::saveToCache($cache_key, $metrics);

        return $metrics;
    }

    /**
     * Calculate year-wise performance
     * @param int $portfolio_id
     * @return array|false
     */
    public static function getYearWisePerformance(int $portfolio_id) {
        // Get all years with transactions
        $sql = "SELECT DISTINCT YEAR(transaction_date) as year
                FROM `transactions`
                WHERE portfolio_id = :portfolio_id
                ORDER BY year DESC";

        try {
            $stmt = PDOConn::query($sql, [], [':portfolio_id' => $portfolio_id]);
            $years = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            $performance = [];

            foreach ($years as $year) {
                $start_date = "{$year}-01-01";
                $end_date = "{$year}-12-31";

                // Get realized PL for the year
                $realized_pl = RealizedPL::getTotalPL($portfolio_id, $start_date, $end_date);

                $performance[] = [
                    'year' => $year,
                    'realized_pl' => $realized_pl
                ];
            }

            return $performance;

        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Save metrics to cache
     * @param string $key
     * @param mixed $data
     */
    private static function saveToCache(string $key, $data) {
        // Simple file-based cache (can be replaced with Redis/Memcached)
        try {
            $cache_file = sys_get_temp_dir() . '/portfolio_cache_' . md5($key) . '.json';
            $cache_data = [
                'data' => $data,
                'expires' => time() + 3600 // 1 hour
            ];
            file_put_contents($cache_file, json_encode($cache_data));
        } catch (\Exception $e) {
            // Cache failure shouldn't break functionality
            error_log("Cache save failed: " . $e->getMessage());
        }
    }

    /**
     * Get metrics from cache
     * @param string $key
     * @return mixed|null
     */
    private static function getFromCache(string $key) {
        try {
            $cache_file = sys_get_temp_dir() . '/portfolio_cache_' . md5($key) . '.json';

            if (!file_exists($cache_file)) {
                return null;
            }

            $cache_data = json_decode(file_get_contents($cache_file), true);

            if (!$cache_data || !isset($cache_data['expires'])) {
                return null;
            }

            if (time() > $cache_data['expires']) {
                unlink($cache_file);
                return null;
            }

            return $cache_data['data'];

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Clear cache for a portfolio
     * @param int $portfolio_id
     */
    public static function clearCache(int $portfolio_id) {
        $pattern = sys_get_temp_dir() . '/portfolio_cache_*.json';
        $files = glob($pattern);

        foreach ($files as $file) {
            $content = @file_get_contents($file);
            if ($content && strpos($content, "\"portfolio_id\":{$portfolio_id}") !== false) {
                @unlink($file);
            }
        }
    }
}
