<?php
$page = 'holdings';
require_once 'inc.php';

$page_title = 'Consolidated Holdings' . CONST_TITLE_AFX;
$body_template_file = CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'holdings.tpl';
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

// AJAX Handler: Get All Holdings
if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getAllHoldings') {
    $result = ['error_code' => 0, 'data' => []];

    try {
        $view_type = $_POST['view_type'] ?? 'all'; // all, portfolio, combination
        $filter_id = (int)($_POST['filter_id'] ?? 0);

        if ($view_type === 'portfolio' && $filter_id > 0) {
            // Get holdings for specific portfolio
            $holdings = \eBizIndia\Holding::getPortfolioHoldings($filter_id);
        } elseif ($view_type === 'combination' && $filter_id > 0) {
            // Get combined holdings for combination
            $combination = new \eBizIndia\PortfolioCombination($filter_id);
            $holdings = $combination->getCombinedHoldings();
        } else {
            // Get all holdings across all portfolios
            $portfolios = \eBizIndia\Portfolio::getList([
                'filters' => [
                    ['field' => 'status', 'value' => 'Active']
                ]
            ]);

            if (!empty($portfolios)) {
                $portfolio_ids = array_column($portfolios, 'portfolio_id');
                $holdings = \eBizIndia\Holding::getCombinedHoldings($portfolio_ids);
            } else {
                $holdings = [];
            }
        }

        if ($holdings === false) {
            $result['error_code'] = 2;
            $result['message'] = 'Error loading holdings';
        } else {
            $result['data'] = $holdings;
        }
    } catch (\Exception $e) {
        $result['error_code'] = 3;
        $result['message'] = 'Error: ' . $e->getMessage();
        \eBizIndia\ErrorHandler::logError(['function' => __METHOD__], $e);
    }

    echo json_encode($result);
    exit;
}

// AJAX Handler: Export Holdings to Excel
if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'exportHoldings') {
    try {
        $view_type = $_POST['view_type'] ?? 'all';
        $filter_id = (int)($_POST['filter_id'] ?? 0);

        if ($view_type === 'portfolio' && $filter_id > 0) {
            $holdings = \eBizIndia\Holding::getPortfolioHoldings($filter_id);
        } elseif ($view_type === 'combination' && $filter_id > 0) {
            $combination = new \eBizIndia\PortfolioCombination($filter_id);
            $holdings = $combination->getCombinedHoldings();
        } else {
            $portfolios = \eBizIndia\Portfolio::getList([
                'filters' => [
                    ['field' => 'status', 'value' => 'Active']
                ]
            ]);

            if (!empty($portfolios)) {
                $portfolio_ids = array_column($portfolios, 'portfolio_id');
                $holdings = \eBizIndia\Holding::getCombinedHoldings($portfolio_ids);
            } else {
                $holdings = [];
            }
        }

        if (empty($holdings)) {
            echo json_encode(['error_code' => 1, 'message' => 'No holdings found']);
            exit;
        }

        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="holdings_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        // Write headers
        fputcsv($output, [
            'Stock Code',
            'Stock Name',
            'Portfolio',
            'Quantity',
            'Avg Cost Price',
            'Total Invested',
            'Current Price',
            'Current Value',
            'Unrealized P&L',
            'Unrealized P&L %'
        ]);

        // Write data
        foreach ($holdings as $holding) {
            $unrealized_pct = $holding['total_invested'] > 0
                ? (($holding['unrealized_pl'] ?? 0) / $holding['total_invested']) * 100
                : 0;

            fputcsv($output, [
                $holding['stock_code'],
                $holding['stock_name'],
                $holding['portfolio_name'] ?? '',
                $holding['current_quantity'],
                number_format($holding['avg_cost_price'], 2),
                number_format($holding['total_invested'], 2),
                number_format($holding['current_market_price'] ?? 0, 2),
                number_format($holding['current_value'] ?? 0, 2),
                number_format($holding['unrealized_pl'] ?? 0, 2),
                number_format($unrealized_pct, 2)
            ]);
        }

        fclose($output);
        exit;

    } catch (\Exception $e) {
        \eBizIndia\ErrorHandler::logError(['function' => __METHOD__], $e);
        echo json_encode(['error_code' => 2, 'message' => 'Export failed']);
        exit;
    }
}

// Get portfolios and combinations for filter
$portfolios = \eBizIndia\Portfolio::getList([
    'filters' => [
        ['field' => 'status', 'value' => 'Active']
    ],
    'order_by' => [
        ['field' => 'portfolio_name', 'type' => 'ASC']
    ]
]);

$combinations = \eBizIndia\PortfolioCombination::getList([]);

// Render page
$page_renderer->updateBaseTemplateData([
    'page_title' => $page_title,
    'module_name' => $page
]);

$page_renderer->updateBodyTemplateData([
    'can_view' => $can_view,
    'portfolios' => $portfolios ?: [],
    'combinations' => $combinations ?: []
]);

$page_renderer->renderPage();
