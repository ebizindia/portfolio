<?php
namespace eBizIndia;

/**
 * XIRRCalculator - Calculate Extended Internal Rate of Return
 *
 * XIRR calculates the annualized rate of return for a series of cash flows
 * that may not occur at regular intervals (unlike IRR).
 *
 * Uses Newton-Raphson method for iterative calculation
 */
class XIRRCalculator {
    private $cash_flows = [];
    private $dates = [];
    private $tolerance = 0.0001;
    private $max_iterations = 100;

    /**
     * Calculate XIRR for given cash flows
     * @param array $cash_flows Array of ['date' => 'YYYY-MM-DD', 'amount' => float]
     * @param float|null $guess Initial guess (default: 0.1 = 10%)
     * @return float|false XIRR as decimal (e.g., 0.15 = 15%) or false on error
     */
    public function calculate(array $cash_flows, $guess = null) {
        if (empty($cash_flows)) {
            return false;
        }

        // Prepare data
        $this->cash_flows = [];
        $this->dates = [];

        foreach ($cash_flows as $cf) {
            $this->cash_flows[] = (float)$cf['amount'];
            $this->dates[] = strtotime($cf['date']);
        }

        if (count($this->cash_flows) < 2) {
            return false;
        }

        // Check for valid cash flows (must have both positive and negative)
        $has_positive = false;
        $has_negative = false;

        foreach ($this->cash_flows as $cf) {
            if ($cf > 0) $has_positive = true;
            if ($cf < 0) $has_negative = true;
        }

        if (!$has_positive || !$has_negative) {
            return false; // XIRR requires both inflows and outflows
        }

        // Initial guess
        $rate = $guess ?? 0.1;

        // Newton-Raphson method
        for ($i = 0; $i < $this->max_iterations; $i++) {
            $npv = $this->calculateNPV($rate);
            $derivative = $this->calculateDerivative($rate);

            if (abs($derivative) < 1e-10) {
                // Derivative too small, try different starting point
                $rate = rand(1, 20) / 100;
                continue;
            }

            $new_rate = $rate - ($npv / $derivative);

            // Check convergence
            if (abs($new_rate - $rate) < $this->tolerance) {
                return $new_rate;
            }

            $rate = $new_rate;

            // Prevent extreme values
            if ($rate < -0.99) $rate = -0.99;
            if ($rate > 10) $rate = 10;
        }

        // Did not converge
        return false;
    }

    /**
     * Calculate Net Present Value for given rate
     * @param float $rate Rate as decimal
     * @return float NPV
     */
    private function calculateNPV($rate) {
        $npv = 0;
        $base_date = $this->dates[0];

        foreach ($this->cash_flows as $index => $amount) {
            $days_diff = ($this->dates[$index] - $base_date) / 86400; // Convert to days
            $years_diff = $days_diff / 365;
            $npv += $amount / pow(1 + $rate, $years_diff);
        }

        return $npv;
    }

    /**
     * Calculate derivative of NPV with respect to rate
     * @param float $rate Rate as decimal
     * @return float Derivative
     */
    private function calculateDerivative($rate) {
        $derivative = 0;
        $base_date = $this->dates[0];

        foreach ($this->cash_flows as $index => $amount) {
            $days_diff = ($this->dates[$index] - $base_date) / 86400;
            $years_diff = $days_diff / 365;

            if ($years_diff != 0) {
                $derivative -= $years_diff * $amount / pow(1 + $rate, $years_diff + 1);
            }
        }

        return $derivative;
    }

    /**
     * Calculate XIRR for a portfolio
     * @param int $portfolio_id Portfolio ID
     * @param string|null $end_date End date (default: today)
     * @return float|false XIRR as decimal or false
     */
    public static function calculatePortfolioXIRR(int $portfolio_id, $end_date = null) {
        $end_date = $end_date ?? date('Y-m-d');

        // Get all BUY transactions (negative cash flows)
        $buy_transactions = Transaction::getList([
            'filters' => [
                ['field' => 'portfolio_id', 'value' => $portfolio_id],
                ['field' => 'transaction_type', 'value' => 'BUY']
            ],
            'order_by' => [
                ['field' => 'transaction_date', 'type' => 'ASC']
            ]
        ]);

        // Get all SELL transactions (positive cash flows)
        $sell_transactions = Transaction::getList([
            'filters' => [
                ['field' => 'portfolio_id', 'value' => $portfolio_id],
                ['field' => 'transaction_type', 'value' => 'SELL']
            ],
            'order_by' => [
                ['field' => 'transaction_date', 'type' => 'ASC']
            ]
        ]);

        if (empty($buy_transactions)) {
            return false;
        }

        $cash_flows = [];

        // Add BUY transactions as negative cash flows
        foreach ($buy_transactions as $txn) {
            $cash_flows[] = [
                'date' => $txn['transaction_date'],
                'amount' => -1 * $txn['transaction_value'] // Negative for outflow
            ];
        }

        // Add SELL transactions as positive cash flows
        if (!empty($sell_transactions)) {
            foreach ($sell_transactions as $txn) {
                $cash_flows[] = [
                    'date' => $txn['transaction_date'],
                    'amount' => $txn['transaction_value'] // Positive for inflow
                ];
            }
        }

        // Add current portfolio value as final positive cash flow
        $current_value = Holding::getPortfolioValue($portfolio_id);

        if ($current_value > 0) {
            $cash_flows[] = [
                'date' => $end_date,
                'amount' => $current_value
            ];
        }

        // Calculate XIRR
        $calculator = new self();
        return $calculator->calculate($cash_flows);
    }

    /**
     * Calculate XIRR for multiple portfolios (combination)
     * @param array $portfolio_ids Array of portfolio IDs
     * @param string|null $end_date End date (default: today)
     * @return float|false XIRR as decimal or false
     */
    public static function calculateCombinedXIRR(array $portfolio_ids, $end_date = null) {
        if (empty($portfolio_ids)) {
            return false;
        }

        $end_date = $end_date ?? date('Y-m-d');
        $cash_flows = [];

        // Get all transactions for all portfolios
        foreach ($portfolio_ids as $portfolio_id) {
            // BUY transactions
            $buy_txns = Transaction::getList([
                'filters' => [
                    ['field' => 'portfolio_id', 'value' => $portfolio_id],
                    ['field' => 'transaction_type', 'value' => 'BUY']
                ]
            ]);

            if (!empty($buy_txns)) {
                foreach ($buy_txns as $txn) {
                    $cash_flows[] = [
                        'date' => $txn['transaction_date'],
                        'amount' => -1 * $txn['transaction_value']
                    ];
                }
            }

            // SELL transactions
            $sell_txns = Transaction::getList([
                'filters' => [
                    ['field' => 'portfolio_id', 'value' => $portfolio_id],
                    ['field' => 'transaction_type', 'value' => 'SELL']
                ]
            ]);

            if (!empty($sell_txns)) {
                foreach ($sell_txns as $txn) {
                    $cash_flows[] = [
                        'date' => $txn['transaction_date'],
                        'amount' => $txn['transaction_value']
                    ];
                }
            }

            // Add current portfolio value
            $current_value = Holding::getPortfolioValue($portfolio_id);
            if ($current_value > 0) {
                $cash_flows[] = [
                    'date' => $end_date,
                    'amount' => $current_value
                ];
            }
        }

        if (empty($cash_flows)) {
            return false;
        }

        // Calculate XIRR
        $calculator = new self();
        return $calculator->calculate($cash_flows);
    }

    /**
     * Calculate Simple Return (no time weighting)
     * @param int $portfolio_id Portfolio ID
     * @return float|false Return as percentage or false
     */
    public static function calculateSimpleReturn(int $portfolio_id) {
        $total_invested = Holding::getTotalInvested($portfolio_id);
        $current_value = Holding::getPortfolioValue($portfolio_id);
        $realized_pl = RealizedPL::getTotalPL($portfolio_id);

        if ($total_invested == 0) {
            return false;
        }

        $total_gain = ($current_value - $total_invested) + $realized_pl;
        return ($total_gain / $total_invested) * 100;
    }

    /**
     * Set tolerance for convergence
     * @param float $tolerance
     */
    public function setTolerance(float $tolerance) {
        $this->tolerance = $tolerance;
    }

    /**
     * Set maximum iterations
     * @param int $max_iterations
     */
    public function setMaxIterations(int $max_iterations) {
        $this->max_iterations = $max_iterations;
    }
}
