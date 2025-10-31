<?php
/**
 * Performance API
 *
 * Provides JSON API endpoints for portfolio performance metrics
 *
 * Endpoints:
 * - getPerformance: Get complete performance metrics for a portfolio
 * - getCombinedPerformance: Get performance for a combination of portfolios
 * - getHoldings: Get current holdings for a portfolio
 * - getRealizedPL: Get realized P&L records
 * - recalculate: Trigger recalculation for a portfolio
 */

$page = 'performance-api';
require_once dirname(__DIR__) . '/inc.php';

// Set JSON header
header('Content-Type: application/json');

// Check permissions - API access only for logged-in users
if (empty($loggedindata)) {
    echo json_encode(['error_code' => 401, 'message' => 'Unauthorized']);
    exit;
}

// GET PERFORMANCE METRICS
if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getPerformance') {
    $result = ['error_code' => 0, 'message' => '', 'data' => []];

    $portfolio_id = (int)($_POST['portfolio_id'] ?? 0);

    if ($portfolio_id <= 0) {
        $result['error_code'] = 2;
        $result['message'] = 'Invalid portfolio ID';
    } else {
        $end_date = $_POST['end_date'] ?? null;
        $metrics = \eBizIndia\PerformanceCalculator::calculateAll($portfolio_id, $end_date);

        if ($metrics === false) {
            $result['error_code'] = 1;
            $result['message'] = 'Failed to calculate performance metrics';
        } else {
            $result['data'] = $metrics;
            $result['message'] = 'Performance metrics calculated successfully';
        }
    }

    echo json_encode($result);
    exit;
}

// GET COMBINED PERFORMANCE
elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getCombinedPerformance') {
    $result = ['error_code' => 0, 'message' => '', 'data' => []];

    $portfolio_ids = $_POST['portfolio_ids'] ?? [];

    if (!is_array($portfolio_ids) || empty($portfolio_ids)) {
        $result['error_code'] = 2;
        $result['message'] = 'Invalid portfolio IDs';
    } else {
        $end_date = $_POST['end_date'] ?? null;
        $metrics = \eBizIndia\PerformanceCalculator::calculateCombined($portfolio_ids, $end_date);

        if ($metrics === false) {
            $result['error_code'] = 1;
            $result['message'] = 'Failed to calculate combined performance';
        } else {
            $result['data'] = $metrics;
            $result['message'] = 'Combined performance calculated successfully';
        }
    }

    echo json_encode($result);
    exit;
}

// GET HOLDINGS
elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getHoldings') {
    $result = ['error_code' => 0, 'message' => '', 'data' => []];

    $portfolio_id = (int)($_POST['portfolio_id'] ?? 0);

    if ($portfolio_id <= 0) {
        $result['error_code'] = 2;
        $result['message'] = 'Invalid portfolio ID';
    } else {
        $holdings = \eBizIndia\Holding::getList([
            'filters' => [
                ['field' => 'portfolio_id', 'value' => $portfolio_id]
            ],
            'order_by' => [
                ['field' => 'stock_code', 'type' => 'ASC']
            ]
        ]);

        if ($holdings === false) {
            $result['error_code'] = 1;
            $result['message'] = 'Failed to retrieve holdings';
        } else {
            $result['data'] = $holdings;
            $result['message'] = 'Holdings retrieved successfully';
        }
    }

    echo json_encode($result);
    exit;
}

// GET REALIZED P&L
elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getRealizedPL') {
    $result = ['error_code' => 0, 'message' => '', 'data' => []];

    $portfolio_id = (int)($_POST['portfolio_id'] ?? 0);

    if ($portfolio_id <= 0) {
        $result['error_code'] = 2;
        $result['message'] = 'Invalid portfolio ID';
    } else {
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;

        $options = [
            'filters' => [
                ['field' => 'portfolio_id', 'value' => $portfolio_id]
            ],
            'order_by' => [
                ['field' => 'sell_date', 'type' => 'DESC']
            ]
        ];

        if ($start_date || $end_date) {
            $options['filters'][] = [
                'field' => 'date_range',
                'start_date' => $start_date,
                'end_date' => $end_date
            ];
        }

        $pl_records = \eBizIndia\RealizedPL::getList($options);

        if ($pl_records === false) {
            $result['error_code'] = 1;
            $result['message'] = 'Failed to retrieve realized P&L';
        } else {
            $result['data'] = $pl_records;
            $result['message'] = 'Realized P&L retrieved successfully';
        }
    }

    echo json_encode($result);
    exit;
}

// RECALCULATE HOLDINGS
elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'recalculate') {
    $result = ['error_code' => 0, 'message' => '', 'data' => []];

    // Only admin can trigger recalculation
    $_cu_role = $loggedindata[0]['profile_details']['assigned_roles'][0]['role'] ?? '';
    if ($_cu_role !== 'Admin') {
        $result['error_code'] = 403;
        $result['message'] = 'Only administrators can trigger recalculation';
        echo json_encode($result);
        exit;
    }

    $portfolio_id = (int)($_POST['portfolio_id'] ?? 0);

    if ($portfolio_id <= 0) {
        // Recalculate all portfolios
        $calc_result = \eBizIndia\FIFOCalculator::recalculateAll();
        $result['data'] = $calc_result;
        $result['message'] = "Recalculation completed. Success: {$calc_result['success']}, Failed: {$calc_result['failed']}";
    } else {
        // Recalculate specific portfolio
        $holdings_updated = \eBizIndia\FIFOCalculator::updateHoldingsTable($portfolio_id);
        $pl_updated = \eBizIndia\FIFOCalculator::updateRealizedPLTable($portfolio_id);

        if ($holdings_updated && $pl_updated) {
            // Clear cache for this portfolio
            \eBizIndia\PerformanceCalculator::clearCache($portfolio_id);

            $result['message'] = 'Portfolio recalculated successfully';
            $result['data'] = [
                'portfolio_id' => $portfolio_id,
                'holdings_updated' => true,
                'pl_updated' => true
            ];
        } else {
            $result['error_code'] = 1;
            $result['message'] = 'Recalculation failed';
        }
    }

    echo json_encode($result);
    exit;
}

// GET PORTFOLIO SUMMARY
elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getSummary') {
    $result = ['error_code' => 0, 'message' => '', 'data' => []];

    $portfolio_id = (int)($_POST['portfolio_id'] ?? 0);

    if ($portfolio_id <= 0) {
        $result['error_code'] = 2;
        $result['message'] = 'Invalid portfolio ID';
    } else {
        $summary = \eBizIndia\Holding::getPortfolioSummary($portfolio_id);

        if ($summary === false) {
            $result['error_code'] = 1;
            $result['message'] = 'Failed to retrieve summary';
        } else {
            // Add realized P&L summary
            $pl_summary = \eBizIndia\RealizedPL::getSummary($portfolio_id);
            if ($pl_summary) {
                $summary['realized_pl_summary'] = $pl_summary;
            }

            $result['data'] = $summary;
            $result['message'] = 'Summary retrieved successfully';
        }
    }

    echo json_encode($result);
    exit;
}

// GET YEAR-WISE PERFORMANCE
elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getYearWise') {
    $result = ['error_code' => 0, 'message' => '', 'data' => []];

    $portfolio_id = (int)($_POST['portfolio_id'] ?? 0);

    if ($portfolio_id <= 0) {
        $result['error_code'] = 2;
        $result['message'] = 'Invalid portfolio ID';
    } else {
        $year_wise = \eBizIndia\PerformanceCalculator::getYearWisePerformance($portfolio_id);

        if ($year_wise === false) {
            $result['error_code'] = 1;
            $result['message'] = 'Failed to retrieve year-wise performance';
        } else {
            $result['data'] = $year_wise;
            $result['message'] = 'Year-wise performance retrieved successfully';
        }
    }

    echo json_encode($result);
    exit;
}

// Invalid mode
else {
    echo json_encode([
        'error_code' => 400,
        'message' => 'Invalid API mode',
        'available_modes' => [
            'getPerformance',
            'getCombinedPerformance',
            'getHoldings',
            'getRealizedPL',
            'recalculate',
            'getSummary',
            'getYearWise'
        ]
    ]);
    exit;
}
