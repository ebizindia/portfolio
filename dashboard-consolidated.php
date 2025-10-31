<?php
$page = 'dashboard-consolidated';
require_once 'inc.php';

$page_title = 'Consolidated Dashboard' . CONST_TITLE_AFX;
$body_template_file = CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'dashboard-consolidated.tpl';
$page_renderer->registerBodyTemplate($body_template_file, []);

// Permission checks
$can_view = false;
if (in_array('ALL', $allowed_menu_perms) || in_array('VIEW', $allowed_menu_perms)) {
    $can_view = true;
}

if (!$can_view) {
    header('Location: 404.php');
    exit;
}

// AJAX Handler: Get Consolidated Metrics
if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getConsolidatedMetrics') {
    $result = ['error_code' => 0, 'data' => []];

    try {
        $end_date = $_POST['end_date'] ?? null;
        $benchmark = $_POST['benchmark'] ?? 'NIFTY50';

        // Get all active portfolios
        $portfolios = \eBizIndia\Portfolio::getList([
            'filters' => [
                ['field' => 'status', 'value' => 'Active']
            ]
        ]);

        if (empty($portfolios)) {
            $result['error_code'] = 1;
            $result['message'] = 'No active portfolios found';
        } else {
            $portfolio_ids = array_column($portfolios, 'portfolio_id');

            // Calculate consolidated metrics
            $total_invested = 0;
            $current_value = 0;
            $unrealized_pl = 0;

            foreach ($portfolio_ids as $pid) {
                $total_invested += \eBizIndia\Holding::getTotalInvested($pid);
                $current_value += \eBizIndia\Holding::getPortfolioValue($pid);
                $unrealized_pl += \eBizIndia\Holding::getUnrealizedPL($pid);
            }

            $realized_pl = \eBizIndia\RealizedPL::getCombinedPL($portfolio_ids);
            $total_pl = $unrealized_pl + $realized_pl;

            // Performance metrics
            $xirr = \eBizIndia\XIRRCalculator::calculateCombinedXIRR($portfolio_ids);
            $performance = \eBizIndia\PerformanceCalculator::calculateCombined($portfolio_ids, $end_date);

            $result['data'] = [
                'total_invested' => $total_invested,
                'current_value' => $current_value,
                'unrealized_pl' => $unrealized_pl,
                'realized_pl' => $realized_pl,
                'total_pl' => $total_pl,
                'total_pl_pct' => $total_invested > 0 ? ($total_pl / $total_invested) * 100 : 0,
                'xirr' => $xirr !== false ? $xirr * 100 : null,
                'performance' => $performance
            ];
        }
    } catch (\Exception $e) {
        $result['error_code'] = 2;
        $result['message'] = 'Error calculating metrics: ' . $e->getMessage();
        \eBizIndia\ErrorHandler::logError(['function' => __METHOD__], $e);
    }

    echo json_encode($result);
    exit;
}

// AJAX Handler: Get Portfolio Summary
if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getPortfolioSummary') {
    $result = ['error_code' => 0, 'data' => []];

    try {
        // Get all active portfolios with their metrics
        $portfolios = \eBizIndia\Portfolio::getList([
            'filters' => [
                ['field' => 'status', 'value' => 'Active']
            ],
            'order_by' => [
                ['field' => 'portfolio_name', 'type' => 'ASC']
            ]
        ]);

        if (!empty($portfolios)) {
            $summary = [];

            foreach ($portfolios as $portfolio) {
                $pid = $portfolio['portfolio_id'];

                $invested = \eBizIndia\Holding::getTotalInvested($pid);
                $current = \eBizIndia\Holding::getPortfolioValue($pid);
                $unrealized = \eBizIndia\Holding::getUnrealizedPL($pid);
                $realized = \eBizIndia\RealizedPL::getTotalPL($pid);
                $total_pl = $unrealized + $realized;

                $summary[] = [
                    'portfolio_id' => $pid,
                    'portfolio_name' => $portfolio['portfolio_name'],
                    'portfolio_type' => $portfolio['portfolio_type'],
                    'total_invested' => $invested,
                    'current_value' => $current,
                    'unrealized_pl' => $unrealized,
                    'realized_pl' => $realized,
                    'total_pl' => $total_pl,
                    'total_pl_pct' => $invested > 0 ? ($total_pl / $invested) * 100 : 0
                ];
            }

            $result['data'] = $summary;
        }
    } catch (\Exception $e) {
        $result['error_code'] = 2;
        $result['message'] = 'Error loading portfolio summary: ' . $e->getMessage();
        \eBizIndia\ErrorHandler::logError(['function' => __METHOD__], $e);
    }

    echo json_encode($result);
    exit;
}

// AJAX Handler: Get Year-wise Comparison
if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getYearwiseComparison') {
    $result = ['error_code' => 0, 'data' => []];

    try {
        $portfolios = \eBizIndia\Portfolio::getList([
            'filters' => [
                ['field' => 'status', 'value' => 'Active']
            ]
        ]);

        if (!empty($portfolios)) {
            $portfolio_ids = array_column($portfolios, 'portfolio_id');

            // Get year-wise data (last 5 years)
            $current_year = date('Y');
            $yearwise_data = [];

            for ($i = 0; $i < 5; $i++) {
                $year = $current_year - $i;
                $end_date = $year . '-12-31';

                $total_invested = 0;
                $current_value = 0;

                foreach ($portfolio_ids as $pid) {
                    // Get historical data for this date
                    $total_invested += \eBizIndia\Holding::getTotalInvested($pid, $end_date);
                    $current_value += \eBizIndia\Holding::getPortfolioValue($pid, $end_date);
                }

                $pl = $current_value - $total_invested;
                $pl_pct = $total_invested > 0 ? ($pl / $total_invested) * 100 : 0;

                $yearwise_data[] = [
                    'year' => $year,
                    'total_invested' => $total_invested,
                    'current_value' => $current_value,
                    'pl' => $pl,
                    'pl_pct' => $pl_pct
                ];
            }

            $result['data'] = array_reverse($yearwise_data);
        }
    } catch (\Exception $e) {
        $result['error_code'] = 2;
        $result['message'] = 'Error loading year-wise data: ' . $e->getMessage();
        \eBizIndia\ErrorHandler::logError(['function' => __METHOD__], $e);
    }

    echo json_encode($result);
    exit;
}

// Render page
$page_renderer->updateBaseTemplateData([
    'page_title' => $page_title,
    'module_name' => $page
]);

$page_renderer->updateBodyTemplateData([
    'can_view' => $can_view
]);

$page_renderer->renderPage();
