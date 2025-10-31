<?php
$page = 'dashboard';
require_once 'inc.php';

$page_title = 'Portfolio Dashboard' . CONST_TITLE_AFX;
$body_template_file = CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'dashboard.tpl';
$page_renderer->registerBodyTemplate($body_template_file, []);

// Permission checks
$can_view = false;
$_cu_role = $loggedindata[0]['profile_details']['assigned_roles'][0]['role'] ?? '';
if (in_array('ALL', $allowed_menu_perms) || in_array('VIEW', $allowed_menu_perms)) {
    $can_view = true;
}

if (!$can_view) {
    header('Location: 404.php');
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

// Get all combinations for selector
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
