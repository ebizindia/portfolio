<?php
namespace eBizIndia;

/**
 * Report Generator Class
 * Handles generation of portfolio reports in various formats (PDF, Excel, CSV)
 */
class ReportGenerator {
    private $report_type;
    private $portfolio_ids = [];
    private $combination_id = null;
    private $start_date = null;
    private $end_date = null;

    public function __construct() {
        // Constructor
    }

    /**
     * Set report parameters
     * @param array $params
     */
    public function setParameters(array $params) {
        $this->report_type = $params['report_type'] ?? 'consolidated';
        $this->portfolio_ids = $params['portfolio_ids'] ?? [];
        $this->combination_id = $params['combination_id'] ?? null;
        $this->start_date = $params['start_date'] ?? null;
        $this->end_date = $params['end_date'] ?? date('Y-m-d');
    }

    /**
     * Generate PDF Report
     * @return string|false File path or false on error
     */
    public function generatePDF() {
        try {
            // Note: This requires TCPDF or mPDF library
            // For now, returning a placeholder implementation

            $data = $this->getReportData();
            $html = $this->buildHTMLReport($data);

            // Save to file
            $filename = 'portfolio_report_' . date('Y-m-d_His') . '.pdf';
            $filepath = CONST_UPLOAD_PATH . '/reports/' . $filename;

            // TODO: Implement actual PDF generation using TCPDF or mPDF
            // $pdf = new \TCPDF();
            // $pdf->AddPage();
            // $pdf->writeHTML($html);
            // $pdf->Output($filepath, 'F');

            return $filepath;

        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Generate Excel Report
     * @return string|false File path or false on error
     */
    public function generateExcel() {
        try {
            $data = $this->getReportData();

            $filename = 'portfolio_report_' . date('Y-m-d_His') . '.xlsx';
            $filepath = CONST_UPLOAD_PATH . '/reports/' . $filename;

            // Note: This requires PhpSpreadsheet library
            // For now, generating CSV format

            return $this->generateCSV();

        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Generate CSV Report
     * @return string|false File path or false on error
     */
    public function generateCSV() {
        try {
            $data = $this->getReportData();

            $filename = 'portfolio_report_' . date('Y-m-d_His') . '.csv';
            $filepath = CONST_UPLOAD_PATH . '/reports/' . $filename;

            $fp = fopen($filepath, 'w');

            // Write headers
            if ($this->report_type === 'consolidated') {
                fputcsv($fp, [
                    'Portfolio Name',
                    'Portfolio Type',
                    'Total Invested',
                    'Current Value',
                    'Unrealized P&L',
                    'Realized P&L',
                    'Total P&L',
                    'P&L %',
                    'XIRR %'
                ]);

                // Write data
                foreach ($data['portfolios'] as $portfolio) {
                    fputcsv($fp, [
                        $portfolio['portfolio_name'],
                        $portfolio['portfolio_type'],
                        $portfolio['total_invested'],
                        $portfolio['current_value'],
                        $portfolio['unrealized_pl'],
                        $portfolio['realized_pl'],
                        $portfolio['total_pl'],
                        $portfolio['total_pl_pct'],
                        $portfolio['xirr'] ?? 'N/A'
                    ]);
                }
            } elseif ($this->report_type === 'holdings') {
                fputcsv($fp, [
                    'Portfolio',
                    'Stock Code',
                    'Stock Name',
                    'Quantity',
                    'Avg Cost Price',
                    'Total Invested',
                    'Current Price',
                    'Current Value',
                    'Unrealized P&L',
                    'P&L %'
                ]);

                // Write data
                foreach ($data['holdings'] as $holding) {
                    $pl_pct = $holding['total_invested'] > 0
                        ? (($holding['unrealized_pl'] ?? 0) / $holding['total_invested']) * 100
                        : 0;

                    fputcsv($fp, [
                        $holding['portfolio_name'] ?? '',
                        $holding['stock_code'],
                        $holding['stock_name'],
                        $holding['current_quantity'],
                        $holding['avg_cost_price'],
                        $holding['total_invested'],
                        $holding['current_market_price'] ?? 0,
                        $holding['current_value'] ?? 0,
                        $holding['unrealized_pl'] ?? 0,
                        number_format($pl_pct, 2)
                    ]);
                }
            } elseif ($this->report_type === 'transactions') {
                fputcsv($fp, [
                    'Portfolio',
                    'Date',
                    'Stock Code',
                    'Stock Name',
                    'Type',
                    'Quantity',
                    'Price',
                    'Transaction Value'
                ]);

                // Write data
                foreach ($data['transactions'] as $txn) {
                    fputcsv($fp, [
                        $txn['portfolio_name'] ?? '',
                        $txn['transaction_date'],
                        $txn['stock_code'],
                        $txn['stock_name'],
                        $txn['transaction_type'],
                        $txn['quantity'],
                        $txn['price'],
                        $txn['transaction_value']
                    ]);
                }
            }

            fclose($fp);
            return $filepath;

        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Get report data based on parameters
     * @return array
     */
    private function getReportData() {
        $data = [];

        if ($this->report_type === 'consolidated') {
            $data['portfolios'] = $this->getConsolidatedPortfolioData();
            $data['summary'] = $this->getConsolidatedSummary();
        } elseif ($this->report_type === 'combination') {
            if ($this->combination_id) {
                $combination = new PortfolioCombination($this->combination_id);
                $data['summary'] = $combination->getSummary();
                $data['breakdown'] = $combination->getPortfolioBreakdown();
                $data['holdings'] = $combination->getCombinedHoldings();
            }
        } elseif ($this->report_type === 'individual' && !empty($this->portfolio_ids)) {
            $portfolio_id = $this->portfolio_ids[0];
            $data['metrics'] = $this->getPortfolioMetrics($portfolio_id);
            $data['holdings'] = Holding::getPortfolioHoldings($portfolio_id);
            $data['transactions'] = $this->getPortfolioTransactions($portfolio_id);
        } elseif ($this->report_type === 'holdings') {
            $data['holdings'] = $this->getAllHoldings();
        } elseif ($this->report_type === 'transactions') {
            $data['transactions'] = $this->getAllTransactions();
        } elseif ($this->report_type === 'realized_pl') {
            $data['realized_pl'] = $this->getRealizedPLData();
        }

        return $data;
    }

    /**
     * Get consolidated portfolio data
     * @return array
     */
    private function getConsolidatedPortfolioData() {
        $portfolios = Portfolio::getList([
            'filters' => [
                ['field' => 'status', 'value' => 'Active']
            ],
            'order_by' => [
                ['field' => 'portfolio_name', 'type' => 'ASC']
            ]
        ]);

        $result = [];

        if (!empty($portfolios)) {
            foreach ($portfolios as $portfolio) {
                $pid = $portfolio['portfolio_id'];

                $invested = Holding::getTotalInvested($pid);
                $current = Holding::getPortfolioValue($pid);
                $unrealized = Holding::getUnrealizedPL($pid);
                $realized = RealizedPL::getTotalPL($pid);
                $total_pl = $unrealized + $realized;
                $xirr = XIRRCalculator::calculatePortfolioXIRR($pid);

                $result[] = [
                    'portfolio_id' => $pid,
                    'portfolio_name' => $portfolio['portfolio_name'],
                    'portfolio_type' => $portfolio['portfolio_type'],
                    'total_invested' => $invested,
                    'current_value' => $current,
                    'unrealized_pl' => $unrealized,
                    'realized_pl' => $realized,
                    'total_pl' => $total_pl,
                    'total_pl_pct' => $invested > 0 ? ($total_pl / $invested) * 100 : 0,
                    'xirr' => $xirr !== false ? $xirr * 100 : null
                ];
            }
        }

        return $result;
    }

    /**
     * Get consolidated summary
     * @return array
     */
    private function getConsolidatedSummary() {
        $portfolios = Portfolio::getList([
            'filters' => [
                ['field' => 'status', 'value' => 'Active']
            ]
        ]);

        $total_invested = 0;
        $current_value = 0;
        $unrealized_pl = 0;
        $realized_pl = 0;

        if (!empty($portfolios)) {
            $portfolio_ids = array_column($portfolios, 'portfolio_id');

            foreach ($portfolio_ids as $pid) {
                $total_invested += Holding::getTotalInvested($pid);
                $current_value += Holding::getPortfolioValue($pid);
                $unrealized_pl += Holding::getUnrealizedPL($pid);
            }

            $realized_pl = RealizedPL::getCombinedPL($portfolio_ids);
        }

        $total_pl = $unrealized_pl + $realized_pl;

        return [
            'total_invested' => $total_invested,
            'current_value' => $current_value,
            'unrealized_pl' => $unrealized_pl,
            'realized_pl' => $realized_pl,
            'total_pl' => $total_pl,
            'total_pl_pct' => $total_invested > 0 ? ($total_pl / $total_invested) * 100 : 0
        ];
    }

    /**
     * Get portfolio metrics
     * @param int $portfolio_id
     * @return array
     */
    private function getPortfolioMetrics($portfolio_id) {
        $invested = Holding::getTotalInvested($portfolio_id);
        $current = Holding::getPortfolioValue($portfolio_id);
        $unrealized = Holding::getUnrealizedPL($portfolio_id);
        $realized = RealizedPL::getTotalPL($portfolio_id);
        $total_pl = $unrealized + $realized;
        $xirr = XIRRCalculator::calculatePortfolioXIRR($portfolio_id);

        return [
            'total_invested' => $invested,
            'current_value' => $current,
            'unrealized_pl' => $unrealized,
            'realized_pl' => $realized,
            'total_pl' => $total_pl,
            'total_pl_pct' => $invested > 0 ? ($total_pl / $invested) * 100 : 0,
            'xirr' => $xirr !== false ? $xirr * 100 : null
        ];
    }

    /**
     * Get all holdings
     * @return array
     */
    private function getAllHoldings() {
        $portfolios = Portfolio::getList([
            'filters' => [
                ['field' => 'status', 'value' => 'Active']
            ]
        ]);

        if (!empty($portfolios)) {
            $portfolio_ids = array_column($portfolios, 'portfolio_id');
            return Holding::getCombinedHoldings($portfolio_ids);
        }

        return [];
    }

    /**
     * Get all transactions
     * @return array
     */
    private function getAllTransactions() {
        $options = [
            'order_by' => [
                ['field' => 'transaction_date', 'type' => 'DESC']
            ]
        ];

        if ($this->start_date) {
            $options['filters'][] = ['field' => 'transaction_date', 'value' => $this->start_date, 'operator' => '>='];
        }

        if ($this->end_date) {
            $options['filters'][] = ['field' => 'transaction_date', 'value' => $this->end_date, 'operator' => '<='];
        }

        if (!empty($this->portfolio_ids)) {
            $options['filters'][] = ['field' => 'portfolio_id', 'value' => $this->portfolio_ids[0]];
        }

        return Transaction::getList($options);
    }

    /**
     * Get portfolio transactions
     * @param int $portfolio_id
     * @return array
     */
    private function getPortfolioTransactions($portfolio_id) {
        return Transaction::getList([
            'filters' => [
                ['field' => 'portfolio_id', 'value' => $portfolio_id]
            ],
            'order_by' => [
                ['field' => 'transaction_date', 'type' => 'DESC']
            ]
        ]);
    }

    /**
     * Get realized P&L data
     * @return array
     */
    private function getRealizedPLData() {
        $options = [
            'order_by' => [
                ['field' => 'sell_date', 'type' => 'DESC']
            ]
        ];

        if ($this->start_date) {
            $options['filters'][] = ['field' => 'sell_date', 'value' => $this->start_date, 'operator' => '>='];
        }

        if ($this->end_date) {
            $options['filters'][] = ['field' => 'sell_date', 'value' => $this->end_date, 'operator' => '<='];
        }

        if (!empty($this->portfolio_ids)) {
            $options['filters'][] = ['field' => 'portfolio_id', 'value' => $this->portfolio_ids[0]];
        }

        return RealizedPL::getList($options);
    }

    /**
     * Build HTML for PDF report
     * @param array $data
     * @return string
     */
    private function buildHTMLReport($data) {
        $html = '<h1>Portfolio Report</h1>';
        $html .= '<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';

        // Add report content based on type
        if ($this->report_type === 'consolidated' && isset($data['summary'])) {
            $html .= '<h2>Summary</h2>';
            $html .= '<table border="1">';
            $html .= '<tr><td>Total Invested</td><td>₹' . number_format($data['summary']['total_invested'], 2) . '</td></tr>';
            $html .= '<tr><td>Current Value</td><td>₹' . number_format($data['summary']['current_value'], 2) . '</td></tr>';
            $html .= '<tr><td>Total P&L</td><td>₹' . number_format($data['summary']['total_pl'], 2) . '</td></tr>';
            $html .= '</table>';
        }

        return $html;
    }
}
