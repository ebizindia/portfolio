<?php
$page = 'transactions';
require_once 'inc.php';

$template_type = '';
$page_title = 'Transaction History' . CONST_TITLE_AFX;
$page_description = 'View and manage portfolio transactions';
$body_template_file = CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'transactions.tpl';
$body_template_data = [];
$page_renderer->registerBodyTemplate($body_template_file, $body_template_data);

// Permission checks
$can_view = $can_edit = $can_delete = false;
$_cu_role = $loggedindata[0]['profile_details']['assigned_roles'][0]['role'];
if (in_array('ALL', $allowed_menu_perms)) {
    $can_view = $can_edit = $can_delete = true;
} else {
    if (in_array('VIEW', $allowed_menu_perms)) {
        $can_view = true;
    }
    if (in_array('EDIT', $allowed_menu_perms)) {
        $can_edit = true;
    }
    if (in_array('DELETE', $allowed_menu_perms)) {
        $can_delete = true;
    }
}

// GET LIST HANDLER
if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getList') {
    $result = [0, []];
    $options = ['filters' => []];

    $pno = isset($_POST['pno']) && is_numeric($_POST['pno']) ? $_POST['pno'] : 1;
    $recsperpage = CONST_RECORDS_PER_PAGE;

    // Search filters
    if (!empty($_POST['searchdata'])) {
        $searchdata = json_decode($_POST['searchdata'], true);
        if (is_array($searchdata)) {
            foreach ($searchdata as $filter) {
                $field = $filter['searchon'] ?? '';
                $value = $filter['searchtext'] ?? '';

                if (!empty($field) && !empty($value)) {
                    switch ($field) {
                        case 'portfolio_id':
                            $options['filters'][] = [
                                'field' => 'portfolio_id',
                                'value' => $value
                            ];
                            break;
                        case 'stock_code':
                        case 'stock_name':
                        case 'portfolio_name':
                            $options['filters'][] = [
                                'field' => $field,
                                'value' => $value,
                                'type' => 'CONTAINS'
                            ];
                            break;
                        case 'transaction_type':
                            $options['filters'][] = [
                                'field' => 'transaction_type',
                                'value' => $value
                            ];
                            break;
                    }
                }
            }
        }
    }

    // Date range filter
    if (!empty($_POST['start_date']) || !empty($_POST['end_date'])) {
        $options['filters'][] = [
            'field' => 'date_range',
            'start_date' => $_POST['start_date'] ?? '',
            'end_date' => $_POST['end_date'] ?? ''
        ];
    }

    // Portfolio filter
    if (!empty($_POST['portfolio_id'])) {
        $options['filters'][] = [
            'field' => 'portfolio_id',
            'value' => (int)$_POST['portfolio_id']
        ];
    }

    // Transaction type filter
    if (!empty($_POST['transaction_type'])) {
        $options['filters'][] = [
            'field' => 'transaction_type',
            'value' => $_POST['transaction_type']
        ];
    }

    // Get total count
    $count_options = array_merge($options, ['fieldstofetch' => ['recordcount']]);
    $count_result = \eBizIndia\Transaction::getList($count_options);
    $recordcount = $count_result[0]['recordcount'] ?? 0;

    // Get paginated records
    $options['page'] = $pno;
    $options['recs_per_page'] = $recsperpage;

    // Sorting
    if (!empty($_POST['sortdata'])) {
        $sortdata = json_decode($_POST['sortdata'], true);
        $options['order_by'] = [];
        foreach ($sortdata as $sort) {
            $options['order_by'][] = [
                'field' => $sort['sorton'],
                'type' => $sort['sortorder']
            ];
        }
    }

    $records = \eBizIndia\Transaction::getList($options);

    if ($records === false) {
        $result[0] = 1; // DB error
    } else {
        $result[1]['list'] = $records;
        $result[1]['reccount'] = $recordcount;
    }

    echo json_encode($result);
    exit;
}

// GET RECORD DETAILS HANDLER
elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getRecordDetails') {
    $result = [];
    $error = 0;

    if (empty($_POST['recordid'])) {
        $error = 1;
    } else {
        $transaction = new \eBizIndia\Transaction($_POST['recordid']);
        $details = $transaction->getDetails();

        if ($details === false) {
            $error = 2;
        } elseif (empty($details)) {
            $error = 3;
        } else {
            $recorddetails = $details[0];
        }
    }

    $result[0] = $error;
    $result[1]['record_details'] = $recorddetails ?? [];

    echo json_encode($result);
    exit;
}

// UPDATE RECORD HANDLER
elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'updaterec') {
    $result = ['error_code' => 0, 'message' => []];

    if ($can_edit === false) {
        $result['error_code'] = 403;
        $result['message'] = "Sorry, you are not authorized to update transactions.";
    } else {
        $transaction_id = (int)$_POST['recordid'];

        if ($transaction_id <= 0) {
            $result['error_code'] = 2;
            $result['message'][] = "Invalid transaction reference.";
        } else {
            $transaction = new \eBizIndia\Transaction($transaction_id);

            $rec_fields = [
                'transaction_date' => '',
                'stock_code' => '',
                'stock_name' => '',
                'quantity' => '',
                'price' => ''
            ];

            $data = \eBizIndia\trim_deep(\eBizIndia\striptags_deep(
                array_intersect_key($_POST, $rec_fields)
            ));

            // Validation
            if (empty($data['transaction_date'])) {
                $result['error_code'] = 2;
                $result['message'][] = "Transaction date is required.";
            }
            if (empty($data['stock_code'])) {
                $result['error_code'] = 2;
                $result['message'][] = "Stock code is required.";
            }
            if (!is_numeric($data['quantity']) || $data['quantity'] <= 0) {
                $result['error_code'] = 2;
                $result['message'][] = "Invalid quantity.";
            }
            if (!is_numeric($data['price']) || $data['price'] <= 0) {
                $result['error_code'] = 2;
                $result['message'][] = "Invalid price.";
            }

            if ($result['error_code'] === 0) {
                // Calculate transaction value
                $data['transaction_value'] = (float)$data['quantity'] * (float)$data['price'];

                $update_result = $transaction->update($data);

                if ($update_result === true) {
                    $result['message'] = 'Transaction has been updated successfully.';
                } elseif ($update_result === null) {
                    $result['error_code'] = 4;
                    $result['message'] = 'No changes were made.';
                } else {
                    $result['error_code'] = 1;
                    $result['message'] = 'Failed to update transaction.';
                }
            }
        }
    }

    $_SESSION['update_rec_result'] = $result;
    header("Location:?");
    exit;
}

// DELETE RECORD HANDLER
elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'deleterec') {
    $result = ['error_code' => 0, 'message' => ''];

    if ($can_delete === false) {
        $result['error_code'] = 403;
        $result['message'] = "Sorry, you are not authorized to delete transactions.";
    } elseif (empty($_POST['recordid'])) {
        $result['error_code'] = 2;
        $result['message'] = "Invalid transaction reference.";
    } else {
        $transaction = new \eBizIndia\Transaction($_POST['recordid']);

        if ($transaction->delete()) {
            $result['message'] = 'Transaction deleted successfully.';
        } else {
            $result['error_code'] = 1;
            $result['message'] = 'Failed to delete transaction.';
        }
    }

    echo json_encode($result);
    exit;
}

// GET PORTFOLIO LIST FOR FILTER
elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getPortfolioList') {
    $options = [
        'filters' => [
            ['field' => 'status', 'value' => 'Active']
        ],
        'order_by' => [
            ['field' => 'portfolio_name', 'type' => 'ASC']
        ]
    ];

    $portfolios = \eBizIndia\Portfolio::getList($options);
    echo json_encode($portfolios);
    exit;
}

// EXPORT TO CSV
elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'exportcsv') {
    $options = ['filters' => []];

    // Apply same filters as list
    if (!empty($_POST['portfolio_id'])) {
        $options['filters'][] = [
            'field' => 'portfolio_id',
            'value' => (int)$_POST['portfolio_id']
        ];
    }

    if (!empty($_POST['start_date']) || !empty($_POST['end_date'])) {
        $options['filters'][] = [
            'field' => 'date_range',
            'start_date' => $_POST['start_date'] ?? '',
            'end_date' => $_POST['end_date'] ?? ''
        ];
    }

    $records = \eBizIndia\Transaction::getList($options);

    if ($records !== false && !empty($records)) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="transactions_' . date('YmdHis') . '.csv"');

        $output = fopen('php://output', 'w');

        // Headers
        fputcsv($output, [
            'Transaction Date', 'Portfolio', 'Stock Code', 'Stock Name',
            'Type', 'Quantity', 'Price', 'Transaction Value', 'Source File'
        ]);

        // Data
        foreach ($records as $record) {
            fputcsv($output, [
                $record['transaction_date'],
                $record['portfolio_name'],
                $record['stock_code'],
                $record['stock_name'],
                $record['transaction_type'],
                $record['quantity'],
                $record['price'],
                $record['transaction_value'],
                $record['source_file'] ?? ''
            ]);
        }

        fclose($output);
        exit;
    } else {
        echo "No records to export";
        exit;
    }
}

// Render page
$page_renderer->updateBaseTemplateData([
    'page_title' => $page_title,
    'module_name' => $page
]);

$page_renderer->updateBodyTemplateData([
    'can_view' => $can_view,
    'can_edit' => $can_edit,
    'can_delete' => $can_delete
]);

$page_renderer->renderPage();
