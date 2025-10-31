<?php
$page = 'dashboard-individual';
require_once 'inc.php';

$page_title = 'Individual Portfolio Dashboard' . CONST_TITLE_AFX;
$body_template_file = CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'dashboard-individual.tpl';
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

// AJAX Handler: Get Portfolio Metrics
if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getPortfolioMetrics') {
    $result = ['error_code' => 0, 'data' => []];

    try {
        $portfolio_id = (int)($_POST['portfolio_id'] ?? 0);

        if ($portfolio_id <= 0) {
            $result['error_code'] = 1;
            $result['message'] = 'Invalid portfolio ID';
        } else {
            $end_date = $_POST['end_date'] ?? null;

            $invested = \eBizIndia\Holding::getTotalInvested($portfolio_id);
            $current = \eBizIndia\Holding::getPortfolioValue($portfolio_id);
            $unrealized = \eBizIndia\Holding::getUnrealizedPL($portfolio_id);
            $realized = \eBizIndia\RealizedPL::getTotalPL($portfolio_id);
            $total_pl = $unrealized + $realized;

            // Performance metrics
            $xirr = \eBizIndia\XIRRCalculator::calculatePortfolioXIRR($portfolio_id);
            $performance = \eBizIndia\PerformanceCalculator::calculate($portfolio_id, $end_date);

            $result['data'] = [
                'total_invested' => $invested,
                'current_value' => $current,
                'unrealized_pl' => $unrealized,
                'realized_pl' => $realized,
                'total_pl' => $total_pl,
                'total_pl_pct' => $invested > 0 ? ($total_pl / $invested) * 100 : 0,
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

// AJAX Handler: Get Portfolio Holdings
if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getPortfolioHoldings') {
    $result = ['error_code' => 0, 'data' => []];

    try {
        $portfolio_id = (int)($_POST['portfolio_id'] ?? 0);

        if ($portfolio_id <= 0) {
            $result['error_code'] = 1;
            $result['message'] = 'Invalid portfolio ID';
        } else {
            $holdings = \eBizIndia\Holding::getPortfolioHoldings($portfolio_id);

            if ($holdings === false) {
                $result['error_code'] = 2;
                $result['message'] = 'Error loading holdings';
            } else {
                $result['data'] = $holdings;
            }
        }
    } catch (\Exception $e) {
        $result['error_code'] = 3;
        $result['message'] = 'Error: ' . $e->getMessage();
        \eBizIndia\ErrorHandler::logError(['function' => __METHOD__], $e);
    }

    echo json_encode($result);
    exit;
}

// AJAX Handler: Get Transaction History
if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getTransactionHistory') {
    $result = ['error_code' => 0, 'data' => []];

    try {
        $portfolio_id = (int)($_POST['portfolio_id'] ?? 0);
        $stock_code = $_POST['stock_code'] ?? null;

        if ($portfolio_id <= 0) {
            $result['error_code'] = 1;
            $result['message'] = 'Invalid portfolio ID';
        } else {
            $options = [
                'filters' => [
                    ['field' => 'portfolio_id', 'value' => $portfolio_id]
                ],
                'order_by' => [
                    ['field' => 'transaction_date', 'type' => 'DESC']
                ]
            ];

            if ($stock_code) {
                $options['filters'][] = ['field' => 'stock_code', 'value' => $stock_code];
            }

            $transactions = \eBizIndia\Transaction::getList($options);

            if ($transactions === false) {
                $result['error_code'] = 2;
                $result['message'] = 'Error loading transactions';
            } else {
                $result['data'] = $transactions;
            }
        }
    } catch (\Exception $e) {
        $result['error_code'] = 3;
        $result['message'] = 'Error: ' . $e->getMessage();
        \eBizIndia\ErrorHandler::logError(['function' => __METHOD__], $e);
    }

    echo json_encode($result);
    exit;
}

// Get all active portfolios for selector
$portfolios = \eBizIndia\Portfolio::getList([
    'filters' => [
        ['field' => 'status', 'value' => 'Active']
    ],
    'order_by' => [
        ['field' => 'portfolio_name', 'type' => 'ASC']
    ]
]);

// Render page
$page_renderer->updateBaseTemplateData([
    'page_title' => $page_title,
    'module_name' => $page
]);

$page_renderer->updateBodyTemplateData([
    'can_view' => $can_view,
    'portfolios' => $portfolios ?: []
]);

$page_renderer->renderPage();
