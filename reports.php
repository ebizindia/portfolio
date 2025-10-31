<?php
$page = 'reports';
require_once 'inc.php';

$page_title = 'Portfolio Reports' . CONST_TITLE_AFX;
$body_template_file = CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'reports.tpl';
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

// AJAX Handler: Generate Report
if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'generateReport') {
    $result = ['error_code' => 0, 'message' => '', 'file_url' => ''];

    try {
        $report_type = $_POST['report_type'] ?? '';
        $format = $_POST['format'] ?? 'csv';
        $portfolio_ids = isset($_POST['portfolio_ids']) ? (array)$_POST['portfolio_ids'] : [];
        $combination_id = isset($_POST['combination_id']) ? (int)$_POST['combination_id'] : null;
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;

        if (empty($report_type)) {
            $result['error_code'] = 1;
            $result['message'] = 'Please select a report type';
        } else {
            $generator = new \eBizIndia\ReportGenerator();
            $generator->setParameters([
                'report_type' => $report_type,
                'portfolio_ids' => $portfolio_ids,
                'combination_id' => $combination_id,
                'start_date' => $start_date,
                'end_date' => $end_date
            ]);

            $file_path = false;

            switch ($format) {
                case 'pdf':
                    $file_path = $generator->generatePDF();
                    break;
                case 'excel':
                    $file_path = $generator->generateExcel();
                    break;
                case 'csv':
                default:
                    $file_path = $generator->generateCSV();
                    break;
            }

            if ($file_path === false) {
                $result['error_code'] = 2;
                $result['message'] = 'Failed to generate report';
            } else {
                $result['message'] = 'Report generated successfully';
                $result['file_url'] = str_replace(CONST_UPLOAD_PATH, 'uploads', $file_path);
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

// Get portfolios and combinations for selectors
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
