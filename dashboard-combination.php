<?php
$page = 'dashboard-combination';
require_once 'inc.php';

$page_title = 'Combination Dashboard' . CONST_TITLE_AFX;
$body_template_file = CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'dashboard-combination.tpl';
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

// AJAX Handler: Get Combination Metrics
if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getCombinationMetrics') {
    $result = ['error_code' => 0, 'data' => []];

    try {
        $combination_id = (int)($_POST['combination_id'] ?? 0);

        if ($combination_id <= 0) {
            $result['error_code'] = 1;
            $result['message'] = 'Invalid combination ID';
        } else {
            $combination = new \eBizIndia\PortfolioCombination($combination_id);
            $summary = $combination->getSummary();

            if ($summary === false) {
                $result['error_code'] = 2;
                $result['message'] = 'Error loading combination metrics';
            } else {
                $result['data'] = $summary;
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

// AJAX Handler: Get Portfolio Breakdown
if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getPortfolioBreakdown') {
    $result = ['error_code' => 0, 'data' => []];

    try {
        $combination_id = (int)($_POST['combination_id'] ?? 0);

        if ($combination_id <= 0) {
            $result['error_code'] = 1;
            $result['message'] = 'Invalid combination ID';
        } else {
            $combination = new \eBizIndia\PortfolioCombination($combination_id);
            $breakdown = $combination->getPortfolioBreakdown();

            if ($breakdown === false) {
                $result['error_code'] = 2;
                $result['message'] = 'Error loading portfolio breakdown';
            } else {
                $result['data'] = $breakdown;
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

// AJAX Handler: Get Combined Holdings
if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getCombinedHoldings') {
    $result = ['error_code' => 0, 'data' => []];

    try {
        $combination_id = (int)($_POST['combination_id'] ?? 0);

        if ($combination_id <= 0) {
            $result['error_code'] = 1;
            $result['message'] = 'Invalid combination ID';
        } else {
            $combination = new \eBizIndia\PortfolioCombination($combination_id);
            $holdings = $combination->getCombinedHoldings();

            if ($holdings === false) {
                $result['error_code'] = 2;
                $result['message'] = 'Error loading combined holdings';
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

// AJAX Handler: Get Top Holdings
if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getTopHoldings') {
    $result = ['error_code' => 0, 'data' => []];

    try {
        $combination_id = (int)($_POST['combination_id'] ?? 0);
        $limit = (int)($_POST['limit'] ?? 10);

        if ($combination_id <= 0) {
            $result['error_code'] = 1;
            $result['message'] = 'Invalid combination ID';
        } else {
            $combination = new \eBizIndia\PortfolioCombination($combination_id);
            $holdings = $combination->getTopHoldings($limit);

            if ($holdings === false) {
                $result['error_code'] = 2;
                $result['message'] = 'Error loading top holdings';
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

// Get all combinations for selector
$combinations = \eBizIndia\PortfolioCombination::getList([]);

// Render page
$page_renderer->updateBaseTemplateData([
    'page_title' => $page_title,
    'module_name' => $page
]);

$page_renderer->updateBodyTemplateData([
    'can_view' => $can_view,
    'combinations' => $combinations ?: []
]);

$page_renderer->renderPage();
