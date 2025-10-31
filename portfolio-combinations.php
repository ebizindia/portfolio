<?php
$page = 'portfolio-combinations';
require_once 'inc.php';

$template_type = '';
$page_title = 'Manage Portfolio Combinations' . CONST_TITLE_AFX;
$page_description = 'Create and manage portfolio combinations for consolidated reporting';
$body_template_file = CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'portfolio-combinations.tpl';
$body_template_data = [];
$page_renderer->registerBodyTemplate($body_template_file, $body_template_data);

// Permission checks
$can_add = $can_edit = $can_delete = $can_view = false;
$_cu_role = $loggedindata[0]['profile_details']['assigned_roles'][0]['role'];
if (in_array('ALL', $allowed_menu_perms)) {
    $can_add = $can_edit = $can_delete = $can_view = true;
} else {
    if (in_array('ADD', $allowed_menu_perms)) {
        $can_add = true;
    }
    if (in_array('EDIT', $allowed_menu_perms)) {
        $can_edit = true;
    }
    if (in_array('DELETE', $allowed_menu_perms)) {
        $can_delete = true;
    }
    if (in_array('VIEW', $allowed_menu_perms)) {
        $can_view = true;
    }
}

$rec_fields = [
    'combination_name' => '',
    'description' => ''
];

// CREATE RECORD HANDLER
if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'createrec') {
    $result = ['error_code' => 0, 'message' => [], 'elemid' => [], 'other_data' => []];

    if ($can_add === false) {
        $result['error_code'] = 403;
        $result['message'] = "Sorry, you are not authorized to perform this action.";
    } else {
        $data = \eBizIndia\trim_deep(\eBizIndia\striptags_deep(
            array_intersect_key($_POST, $rec_fields)
        ));

        // Get selected portfolios
        $portfolio_ids = [];
        if (!empty($_POST['portfolio_ids']) && is_array($_POST['portfolio_ids'])) {
            $portfolio_ids = array_map('intval', $_POST['portfolio_ids']);
        }

        // Validation
        if (empty($data['combination_name'])) {
            $result['error_code'] = 2;
            $result['message'][] = "Combination name is required.";
            $result['error_fields'][] = "#combination_name";
        } elseif (empty($portfolio_ids)) {
            $result['error_code'] = 2;
            $result['message'][] = "Please select at least one portfolio.";
            $result['error_fields'][] = "#portfolio_ids";
        } else {
            $combination = new \eBizIndia\PortfolioCombination();
            $combination_id = $combination->add($data, $portfolio_ids);

            if ($combination_id) {
                $result['message'] = 'Portfolio combination has been created successfully.';
            } else {
                $result['error_code'] = 1;
                $result['message'] = 'Failed to create combination due to server error.';
            }
        }
    }

    $_SESSION['create_rec_result'] = $result;
    header("Location:?");
    exit;
}

// UPDATE RECORD HANDLER
elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'updaterec') {
    $result = ['error_code' => 0, 'message' => [], 'other_data' => []];

    if ($can_edit === false) {
        $result['error_code'] = 403;
        $result['message'] = "Sorry, you are not authorized to update combinations.";
    } else {
        $combination_id = (int)$_POST['recordid'];

        if ($combination_id <= 0) {
            $result['error_code'] = 2;
            $result['message'][] = "Invalid combination reference.";
        } else {
            $options = [
                'filters' => [
                    ['field' => 'combination_id', 'value' => $combination_id]
                ]
            ];

            $recorddetails = \eBizIndia\PortfolioCombination::getList($options);
            $combination = new \eBizIndia\PortfolioCombination($combination_id);

            if ($recorddetails === false) {
                $result['error_code'] = 1;
                $result['message'][] = "Failed to verify combination details due to server error.";
            } elseif (empty($recorddetails)) {
                $result['error_code'] = 3;
                $result['message'][] = "The combination you are trying to modify was not found.";
            } else {
                $data = \eBizIndia\trim_deep(\eBizIndia\striptags_deep(
                    array_intersect_key($_POST, $rec_fields)
                ));

                // Get selected portfolios
                $portfolio_ids = [];
                if (!empty($_POST['portfolio_ids']) && is_array($_POST['portfolio_ids'])) {
                    $portfolio_ids = array_map('intval', $_POST['portfolio_ids']);
                }

                if (empty($data['combination_name'])) {
                    $result['error_code'] = 2;
                    $result['message'][] = "Combination name is required.";
                    $result['error_fields'][] = "#combination_name";
                } elseif (empty($portfolio_ids)) {
                    $result['error_code'] = 2;
                    $result['message'][] = "Please select at least one portfolio.";
                    $result['error_fields'][] = "#portfolio_ids";
                } else {
                    $update_result = $combination->update($data, $portfolio_ids);

                    if ($update_result === true) {
                        $result['message'] = 'Combination has been updated successfully.';
                    } elseif ($update_result === null) {
                        $result['error_code'] = 4;
                        $result['message'] = 'No changes were made.';
                    } else {
                        $result['error_code'] = 1;
                        $result['message'] = 'Failed to update combination.';
                    }
                }
            }
        }
    }

    $_SESSION['update_rec_result'] = $result;
    header("Location:?");
    exit;
}

// GET LIST HANDLER
elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getList') {
    $result = [0, []];
    $options = ['filters' => []];

    $pno = isset($_POST['pno']) && is_numeric($_POST['pno']) ? $_POST['pno'] : 1;
    $recsperpage = CONST_RECORDS_PER_PAGE;

    // Search filters
    if (!empty($_POST['searchdata'])) {
        $searchdata = json_decode($_POST['searchdata'], true);
        if (is_array($searchdata)) {
            foreach ($searchdata as $filter) {
                $options['filters'][] = [
                    'field' => $filter['searchon'],
                    'value' => $filter['searchtext'],
                    'type' => $filter['searchtype'] ?? 'CONTAINS'
                ];
            }
        }
    }

    // Get total count
    $count_options = array_merge($options, ['fieldstofetch' => ['recordcount']]);
    $count_result = \eBizIndia\PortfolioCombination::getList($count_options);
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

    $records = \eBizIndia\PortfolioCombination::getList($options);

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
    $recorddetails = [];

    if (empty($_POST['recordid'])) {
        $error = 1;
    } else {
        $combination = new \eBizIndia\PortfolioCombination($_POST['recordid']);
        $details = $combination->getDetails();

        if ($details === false) {
            $error = 2;
        } elseif (empty($details)) {
            $error = 3;
        } else {
            $recorddetails = $details[0];
            // Get portfolio IDs for this combination
            $recorddetails['portfolio_ids'] = $combination->getPortfolioIds();
        }
    }

    $result[0] = $error;
    $result[1]['record_details'] = $recorddetails;

    echo json_encode($result);
    exit;
}

// DELETE RECORD HANDLER
elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'deleterec') {
    $result = ['error_code' => 0, 'message' => ''];

    if ($can_delete === false) {
        $result['error_code'] = 403;
        $result['message'] = "Sorry, you are not authorized to delete combinations.";
    } elseif (empty($_POST['recordid'])) {
        $result['error_code'] = 2;
        $result['message'] = "Invalid combination reference.";
    } else {
        $combination = new \eBizIndia\PortfolioCombination($_POST['recordid']);

        if ($combination->delete()) {
            $result['message'] = 'Combination deleted successfully.';
        } else {
            $result['error_code'] = 1;
            $result['message'] = 'Failed to delete combination.';
        }
    }

    echo json_encode($result);
    exit;
}

// GET PORTFOLIOS LIST HANDLER
elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getPortfoliosList') {
    $result = ['error_code' => 0, 'portfolios' => []];

    // Get all active portfolios
    $options = [
        'filters' => [
            ['field' => 'status', 'value' => 'Active']
        ],
        'order_by' => [
            ['field' => 'portfolio_name', 'type' => 'ASC']
        ]
    ];

    $portfolios = \eBizIndia\Portfolio::getList($options);

    if ($portfolios === false) {
        $result['error_code'] = 1;
    } else {
        $result['portfolios'] = $portfolios;
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
    'can_add' => $can_add,
    'can_edit' => $can_edit,
    'can_delete' => $can_delete,
    'can_view' => $can_view
]);

$page_renderer->renderPage();
