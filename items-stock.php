<?php
$page = 'items-stock';
require_once 'inc.php';

$template_type = '';
$page_title = 'Items Stock Management' . CONST_TITLE_AFX;
$page_description = 'View and import items stock data from CSV files.';
$body_template_file = CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'items-stock.tpl';
$body_template_data = [];
$page_renderer->registerBodyTemplate($body_template_file, $body_template_data);

// Check user permissions
$can_view = $can_import = false;
$upload_max_file_size = ini_get('upload_max_filesize');
$disp_size = '';
if(preg_match("/^(\d+)(M|K|G|B)$/", $upload_max_file_size, $matches)){
    if($matches[2]==='M'){
        $upload_max_file_size = $matches[1]*1024*1024; // MB in Bytes
        $disp_size = $matches[1].'MB';
    }else if($matches[2]==='K'){
        $upload_max_file_size = $matches[1]*1024; // KB in Bytes
        $disp_size = $matches[1].'KB';
    }else if($matches[2]==='G'){
        $upload_max_file_size = $matches[1]*1024*1024*1024; // GB in Bytes
        $disp_size = $matches[1].'GB';
    }else if($matches[2]==='B'){
        $upload_max_file_size = $matches[1]; // Bytes
        $disp_size = $matches[1].'B';
    }
}else{
    $upload_max_file_size = 2097152; // 2*1024*1024;
    $disp_size = '2MB';
}
if($upload_max_file_size<10485760)
    ini_set('upload_max_filesize', '10M');

define('STOCK_IMPORT_MAX_FILE_SIZE',  [
    'EXCEL' => [
      'disp' => $upload_max_file_size<8388608?$disp_size:'8MB',
      'bytes' => $upload_max_file_size<8388608?$upload_max_file_size:8388608, // 8MB
    ],
    'CSV' => [
      'disp' => $upload_max_file_size<10485760?$disp_size:'10MB',
      'bytes' => $upload_max_file_size<10485760?$upload_max_file_size:10485760, // 10MB
    ],
]);

if (in_array('ALL', $allowed_menu_perms)) {
    $can_view = $can_import = true;
} else {
    if (in_array('VIEW', $allowed_menu_perms)) {
        $can_view = true;
    }
    if (in_array('IMPORT', $allowed_menu_perms)) {
        $can_import = true;
    }
}

// Handle CSV Import
if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'importCSV') {
    $result = ['error_code' => 0, 'message' => '', 'other_data' => []];

    if ($can_import === false) {
        $result['error_code'] = 403;
        $result['message'] = "Sorry, you are not authorized to import stock data.";
    } else {
        $warehouse_id = (int)($_POST['warehouse_id'] ?? 0);

        if ($warehouse_id <= 0) {
            $result['error_code'] = 2;
            $result['message'] = "Please select a warehouse.";
            $result['error_fields'] = ['#import_warehouse_id'];
        } elseif (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $result['error_code'] = 2;
            $result['message'] = "Please select a valid CSV file.";
            $result['error_fields'] = ['#csv_file'];
        } else {
            try {
                $imported_count = \eBizIndia\ItemsStock::importFromCSV(
                    $_FILES['csv_file'],
                    $warehouse_id,
                    $loggedindata[0]['id'],
                    STOCK_IMPORT_MAX_FILE_SIZE
                );

                $result['error_code'] = 0;
                $result['message'] = "Successfully imported $imported_count records.";
                $result['imported_count'] = $imported_count;

            } catch (\Exception $e) {
                $result['error_code'] = 1;
                $result['message'] = $e->getMessage();

                $error_details_to_log['result'] = $result;
                \eBizIndia\ErrorHandler::logError($error_details_to_log, $e);
            }
        }
    }

    echo json_encode($result);
    exit;
}

// Handle Get Warehouses List
elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getWarehousesList') {
    $result = [0, []];
    $warehouses = \eBizIndia\ItemsStock::getWarehouseList();

    if ($warehouses === false) {
        $result[0] = 1; // Error
    } else {
        $result[1] = $warehouses;
    }

    echo json_encode($result);
    exit;
}
// Handle CSV/Excel Template Download
elseif (filter_has_var(INPUT_GET, 'mode') && $_GET['mode'] == 'downloadTemplate') {
    if (!$can_import) {
        header('HTTP/1.0 403 Forbidden', true, 403);
        die('Access denied');
    }

    $format = $_GET['format'] ?? 'csv';

    if ($format === 'excel') {
        \eBizIndia\ItemsStock::downloadExcelTemplate();
    } else {
        // Existing CSV template generation
        $csv_template = \eBizIndia\ItemsStock::getCSVTemplate();

        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="items_stock_template.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Create file handle
        $output = fopen('php://output', 'w');

        // Write headers
        fputcsv($output, $csv_template['headers']);

        // Write sample data
        foreach ($csv_template['sample_data'] as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }
}
// Handle List Items Stock (AJAX)
elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getList') {
    $result = [0, []];
    $options = [];
    $options['filters'] = [];

    $pno = isset($_POST['pno']) && is_numeric($_POST['pno']) ? $_POST['pno'] : 1;
    $recsperpage = isset($_POST['recsperpage']) && is_numeric($_POST['recsperpage'])
        ? $_POST['recsperpage']
        : CONST_RECORDS_PER_PAGE_COMMON;

    $filtertext = [];
    if (filter_has_var(INPUT_POST, 'searchdata') && $_POST['searchdata'] != '') {
        $searchdata = json_decode($_POST['searchdata'], true);
        if (!is_array($searchdata)) {
            $error = 2; // invalid search parameters
        } else if (!empty($searchdata)) {
            $options['filters'] = [];
            foreach ($searchdata as $filter) {
                $field = $filter['searchon'];
                $type = $filter['searchtype'] ?? '';
                $value = \eBizIndia\trim_deep($filter['searchtext']);

                $options['filters'][] = [
                    'field' => $field,
                    'type' => $type,
                    'value' => $value
                ];

                switch (strtolower($field)) {
                    case 'item_name':
                        $fltr_text = 'Item name ';
                        break;
                    case 'warehouse_id':
                        // Get warehouse name for display
                        $warehouses = \eBizIndia\ItemsStock::getWarehouseList();
                        $warehouse_name = $value;
                        if ($warehouses) {
                            foreach ($warehouses as $wh) {
                                if ($wh['id'] == $value) {
                                    $warehouse_name = $wh['name'];
                                    break;
                                }
                            }
                        }
                        $fltr_text = 'Warehouse ';
                        $value = $warehouse_name;
                        break;
                    default:
                        $fltr_text = ucfirst($field) . ' ';
                        break;
                }

                switch ($type) {
                    case 'CONTAINS':
                        $fltr_text .= 'has ';
                        break;
                    case 'EQUAL':
                        $fltr_text .= 'is ';
                        break;
                    case 'STARTS_WITH':
                        $fltr_text .= 'starts with ';
                        break;
                }

                $filtertext[] = '<span class="searched_elem">' .
                    \eBizIndia\_esc($fltr_text, true) . ' <b>' . \eBizIndia\_esc($value, true) . '</b>' .
                    '<span class="remove_filter" data-fld="' . $field . '">X</span></span>';
            }

            $result[1]['filtertext'] = implode($filtertext);
        }
    }

    $tot_rec_options = [
        'fieldstofetch' => ['recordcount'],
        'filters' => $options['filters'],
    ];

    $options['fieldstofetch'] = ['recordcount'];

    // Get total stock count
    $tot_rec_cnt = \eBizIndia\ItemsStock::getList($tot_rec_options);
    $result[1]['tot_rec_cnt'] = $tot_rec_cnt[0]['recordcount'];

    // Get record count based on filters
    $recordcount = \eBizIndia\ItemsStock::getList($options);
    $recordcount = $recordcount[0]['recordcount'];

    $paginationdata = \eBizIndia\getPaginationData(
        $recordcount,
        $recsperpage,
        $pno,
        CONST_PAGE_LINKS_COUNT
    );
    $result[1]['paginationdata'] = $paginationdata;

    if ($recordcount > 0) {
        $noofrecords = $paginationdata['recs_per_page'];
        unset($options['fieldstofetch']);
        $options['page'] = $pno;
        $options['recs_per_page'] = $noofrecords;

        if (isset($_POST['sortdata']) && $_POST['sortdata'] != '') {
            $options['order_by'] = [];
            $sortdata = json_decode($_POST['sortdata'], true);
            foreach ($sortdata as $sort_param) {
                $options['order_by'][] = [
                    'field' => $sort_param['sorton'],
                    'type' => $sort_param['sortorder']
                ];
            }
        } else {
            // Default sort by warehouse name, item name
            $options['order_by'] = [
                ['field' => 'warehouse_name', 'type' => 'ASC'],
                ['field' => 'item_name', 'type' => 'ASC']
            ];
        }

        $records = \eBizIndia\ItemsStock::getList($options);

        if ($records === false) {
            $error = 1; // db error
        } else {
            $result[1]['list'] = $records;
        }
    }

    $result[0] = $error ?? 0;
    $result[1]['reccount'] = $recordcount;

    if ($_POST['listformat'] == 'html') {
        $get_list_template_data = [
            'mode' => $_POST['mode'],
            $_POST['mode'] => [
                'error' => $error ?? 0,
                'records' => $records ?? [],
                'records_count' => count($records ?? []),
                'cu_id' => $loggedindata[0]['id'],
                'filtertext' => $result[1]['filtertext'] ?? '',
                'filtercount' => count($filtertext),
                'tot_col_count' => 6, // 6 columns in the table
                'can_view' => $can_view,
                'can_import' => $can_import
            ],
            'logged_in_user' => $loggedindata[0]
        ];

        $paginationdata['link_data'] = "";
        $paginationdata['page_link'] = '#';

        $get_list_template_data[$_POST['mode']]['pagination_html'] =
            $page_renderer->fetchContent(
                CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'pagination-bar.tpl',
                $paginationdata
            );

        $page_renderer->updateBodyTemplateData($get_list_template_data);
        $result[1]['list'] = $page_renderer->fetchContent();
    }

    echo json_encode($result, JSON_HEX_TAG);
    exit;
}

// Prepare warehouses for dropdown
$warehouses = \eBizIndia\ItemsStock::getWarehouseList();

// JavaScript initialization data
$dom_ready_data[$page] = [
    'field_meta' => CONST_FIELD_META,
    'max_allowed_size' => STOCK_IMPORT_MAX_FILE_SIZE,
];

// Additional template data
$additional_base_template_data = [
    'page_title' => $page_title,
    'page_description' => $page_description,
    'template_type' => $template_type,
    'dom_ready_code' => \scriptProviderFuncs\getDomReadyJsCode($page, $dom_ready_data),
    'module_name' => $page
];

// Items Stock specific template data
$additional_body_template_data = [
    'can_view' => $can_view,
    'can_import' => $can_import,
    'warehouses' => $warehouses ?: [],
    'csv_headers' => \eBizIndia\ItemsStock::CSV_HEADERS,
    'max_allowed_sizes' => STOCK_IMPORT_MAX_FILE_SIZE,
];

// Finalize
$page_renderer->updateBodyTemplateData($additional_body_template_data);
$page_renderer->updateBaseTemplateData($additional_base_template_data);
$page_renderer->addCss(\scriptProviderFuncs\getCss($page));

$js_files = \scriptProviderFuncs\getJavascripts($page);
$page_renderer->addJavascript($js_files['BSH'], 'BEFORE_SLASH_HEAD');
$page_renderer->addJavascript($js_files['BSB'], 'BEFORE_SLASH_BODY');
$page_renderer->renderPage();