<?php
namespace eBizIndia;

/**
 * FIFOCalculator - Implements First In First Out logic for stock transactions
 *
 * This class handles:
 * - Matching sell transactions with buy transactions using FIFO method
 * - Calculating realized profit/loss when stocks are sold
 * - Maintaining accurate cost basis for remaining holdings
 * - Supporting partial sells and multiple buy lots
 */
class FIFOCalculator {
    private $portfolio_id;
    private $stock_code;
    private $buy_queue = [];
    private $realized_pl_records = [];
    private $debug_mode = false;

    /**
     * Constructor
     * @param int $portfolio_id Portfolio ID
     * @param string $stock_code Stock code
     */
    public function __construct(?int $portfolio_id = null, ?string $stock_code = null) {
        $this->portfolio_id = $portfolio_id;
        $this->stock_code = $stock_code;
    }

    /**
     * Calculate FIFO for a specific stock in a portfolio
     * @param int $portfolio_id Portfolio ID
     * @param string $stock_code Stock code
     * @return array ['current_quantity' => float, 'avg_cost' => float, 'total_invested' => float, 'realized_pl' => array]
     */
    public function calculateFIFO(int $portfolio_id, string $stock_code) {
        $this->portfolio_id = $portfolio_id;
        $this->stock_code = strtoupper(trim($stock_code));

        // Reset state
        $this->buy_queue = [];
        $this->realized_pl_records = [];

        // Get all transactions for this stock in chronological order
        $transactions = Transaction::getStockTransactions($portfolio_id, $this->stock_code);

        if (empty($transactions)) {
            return [
                'current_quantity' => 0,
                'avg_cost' => 0,
                'total_invested' => 0,
                'realized_pl' => []
            ];
        }

        // Process each transaction
        foreach ($transactions as $txn) {
            if ($txn['transaction_type'] === 'BUY') {
                $this->processBuy($txn);
            } elseif ($txn['transaction_type'] === 'SELL') {
                $this->processSell($txn);
            }
        }

        // Calculate current position
        $current_quantity = 0;
        $total_invested = 0;

        foreach ($this->buy_queue as $lot) {
            $current_quantity += $lot['remaining_quantity'];
            $total_invested += $lot['remaining_quantity'] * $lot['price'];
        }

        $avg_cost = $current_quantity > 0 ? $total_invested / $current_quantity : 0;

        return [
            'current_quantity' => $current_quantity,
            'avg_cost' => $avg_cost,
            'total_invested' => $total_invested,
            'realized_pl' => $this->realized_pl_records
        ];
    }

    /**
     * Process a BUY transaction
     * @param array $transaction Transaction record
     */
    private function processBuy($transaction) {
        // Add to buy queue
        $this->buy_queue[] = [
            'transaction_id' => $transaction['transaction_id'],
            'transaction_date' => $transaction['transaction_date'],
            'quantity' => (float)$transaction['quantity'],
            'remaining_quantity' => (float)$transaction['quantity'],
            'price' => (float)$transaction['price'],
            'stock_code' => $transaction['stock_code'],
            'stock_name' => $transaction['stock_name']
        ];

        if ($this->debug_mode) {
            error_log("BUY: Added {$transaction['quantity']} @ {$transaction['price']}");
        }
    }

    /**
     * Process a SELL transaction using FIFO
     * @param array $transaction Transaction record
     */
    private function processSell($transaction) {
        $sell_quantity = (float)$transaction['quantity'];
        $sell_price = (float)$transaction['price'];
        $sell_date = $transaction['transaction_date'];
        $remaining_to_sell = $sell_quantity;

        if ($this->debug_mode) {
            error_log("SELL: Processing {$sell_quantity} @ {$sell_price}");
        }

        // Match with buy lots using FIFO
        foreach ($this->buy_queue as $index => &$lot) {
            if ($remaining_to_sell <= 0) {
                break;
            }

            if ($lot['remaining_quantity'] <= 0) {
                continue;
            }

            // Determine quantity to match from this lot
            $match_quantity = min($lot['remaining_quantity'], $remaining_to_sell);
            $buy_price = $lot['price'];

            // Calculate realized P&L for this match
            $realized_pl = ($sell_price - $buy_price) * $match_quantity;

            // Record realized P&L
            $this->realized_pl_records[] = [
                'portfolio_id' => $this->portfolio_id,
                'stock_code' => $this->stock_code,
                'sell_date' => $sell_date,
                'quantity_sold' => $match_quantity,
                'sell_price' => $sell_price,
                'avg_buy_price' => $buy_price,
                'realized_pl' => $realized_pl,
                'buy_date' => $lot['transaction_date']
            ];

            // Update remaining quantity in lot
            $lot['remaining_quantity'] -= $match_quantity;
            $remaining_to_sell -= $match_quantity;

            if ($this->debug_mode) {
                error_log("Matched {$match_quantity} from lot @ {$buy_price}, P&L: {$realized_pl}");
            }
        }

        // Check if we sold more than we had
        if ($remaining_to_sell > 0.01) { // Small tolerance for floating point
            error_log("WARNING: Short sell detected for {$this->stock_code}. Oversold by {$remaining_to_sell}");
            // Could throw exception or handle short sell differently
        }

        // Clean up fully consumed lots
        $this->buy_queue = array_values(array_filter($this->buy_queue, function($lot) {
            return $lot['remaining_quantity'] > 0.001;
        }));
    }

    /**
     * Calculate FIFO for all stocks in a portfolio
     * @param int $portfolio_id Portfolio ID
     * @return array Array of holdings with FIFO calculations
     */
    public static function calculatePortfolioFIFO(int $portfolio_id) {
        // Get all unique stocks in portfolio
        $sql = "SELECT DISTINCT stock_code, stock_name
                FROM `transactions`
                WHERE portfolio_id = :portfolio_id
                ORDER BY stock_code";

        try {
            $stmt = PDOConn::query($sql, [], [':portfolio_id' => $portfolio_id]);
            $stocks = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $holdings = [];
            $calculator = new self();

            foreach ($stocks as $stock) {
                $result = $calculator->calculateFIFO($portfolio_id, $stock['stock_code']);

                if ($result['current_quantity'] > 0.001) {
                    $holdings[] = [
                        'stock_code' => $stock['stock_code'],
                        'stock_name' => $stock['stock_name'],
                        'current_quantity' => $result['current_quantity'],
                        'avg_cost_price' => $result['avg_cost'],
                        'total_invested' => $result['total_invested'],
                        'realized_pl' => $result['realized_pl']
                    ];
                }
            }

            return $holdings;

        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Update holdings table for a portfolio
     * @param int $portfolio_id Portfolio ID
     * @return bool Success status
     */
    public static function updateHoldingsTable(int $portfolio_id) {
        try {
            $conn = PDOConn::getInstance();
            $conn->beginTransaction();

            // Calculate FIFO for all stocks
            $holdings = self::calculatePortfolioFIFO($portfolio_id);

            if ($holdings === false) {
                $conn->rollBack();
                return false;
            }

            // Delete existing holdings for this portfolio
            $delete_sql = "DELETE FROM `holdings` WHERE portfolio_id = :portfolio_id";
            PDOConn::query($delete_sql, [], [':portfolio_id' => $portfolio_id]);

            // Insert new holdings
            foreach ($holdings as $holding) {
                $insert_sql = "INSERT INTO `holdings`
                    (portfolio_id, stock_code, stock_name, current_quantity, avg_cost_price, total_invested, last_updated)
                    VALUES
                    (:portfolio_id, :stock_code, :stock_name, :quantity, :avg_cost, :total_invested, NOW())";

                PDOConn::query($insert_sql, [
                    ':stock_code' => $holding['stock_code'],
                    ':stock_name' => $holding['stock_name']
                ], [
                    ':portfolio_id' => $portfolio_id,
                    ':quantity' => $holding['current_quantity'],
                    ':avg_cost' => $holding['avg_cost_price'],
                    ':total_invested' => $holding['total_invested']
                ]);
            }

            $conn->commit();
            return true;

        } catch (\Exception $e) {
            if (isset($conn) && $conn->inTransaction()) {
                $conn->rollBack();
            }
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Update realized P&L table for a portfolio
     * @param int $portfolio_id Portfolio ID
     * @return bool Success status
     */
    public static function updateRealizedPLTable(int $portfolio_id) {
        try {
            $conn = PDOConn::getInstance();
            $conn->beginTransaction();

            // Get all unique stocks
            $sql = "SELECT DISTINCT stock_code FROM `transactions` WHERE portfolio_id = :portfolio_id";
            $stmt = PDOConn::query($sql, [], [':portfolio_id' => $portfolio_id]);
            $stocks = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Delete existing realized P&L for this portfolio
            $delete_sql = "DELETE FROM `realized_pl` WHERE portfolio_id = :portfolio_id";
            PDOConn::query($delete_sql, [], [':portfolio_id' => $portfolio_id]);

            $calculator = new self();

            // Calculate for each stock
            foreach ($stocks as $stock) {
                $result = $calculator->calculateFIFO($portfolio_id, $stock['stock_code']);

                // Insert realized P&L records
                foreach ($result['realized_pl'] as $pl) {
                    $insert_sql = "INSERT INTO `realized_pl`
                        (portfolio_id, stock_code, sell_date, quantity_sold, sell_price, avg_buy_price, realized_pl, created_at)
                        VALUES
                        (:portfolio_id, :stock_code, :sell_date, :quantity, :sell_price, :buy_price, :pl, NOW())";

                    PDOConn::query($insert_sql, [
                        ':stock_code' => $pl['stock_code'],
                        ':sell_date' => $pl['sell_date']
                    ], [
                        ':portfolio_id' => $portfolio_id,
                        ':quantity' => $pl['quantity_sold'],
                        ':sell_price' => $pl['sell_price'],
                        ':buy_price' => $pl['avg_buy_price'],
                        ':pl' => $pl['realized_pl']
                    ]);
                }
            }

            $conn->commit();
            return true;

        } catch (\Exception $e) {
            if (isset($conn) && $conn->inTransaction()) {
                $conn->rollBack();
            }
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Recalculate all portfolios
     * @return array ['success' => int, 'failed' => int, 'errors' => array]
     */
    public static function recalculateAll() {
        $result = ['success' => 0, 'failed' => 0, 'errors' => []];

        try {
            // Get all active portfolios
            $portfolios = Portfolio::getList([
                'filters' => [
                    ['field' => 'status', 'value' => 'Active']
                ]
            ]);

            foreach ($portfolios as $portfolio) {
                $portfolio_id = $portfolio['portfolio_id'];

                // Update holdings
                if (self::updateHoldingsTable($portfolio_id)) {
                    // Update realized P&L
                    if (self::updateRealizedPLTable($portfolio_id)) {
                        $result['success']++;
                    } else {
                        $result['failed']++;
                        $result['errors'][] = "Failed to update P&L for portfolio {$portfolio_id}";
                    }
                } else {
                    $result['failed']++;
                    $result['errors'][] = "Failed to update holdings for portfolio {$portfolio_id}";
                }
            }

        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            $result['errors'][] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Enable debug mode
     */
    public function enableDebug() {
        $this->debug_mode = true;
    }

    /**
     * Get current buy queue (for debugging)
     * @return array
     */
    public function getBuyQueue() {
        return $this->buy_queue;
    }

    /**
     * Get realized P&L records (for debugging)
     * @return array
     */
    public function getRealizedPLRecords() {
        return $this->realized_pl_records;
    }
}
