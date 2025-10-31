<?php
$page = 'portfolios';
require_once 'inc.php';

$template_type = '';
$page_title = 'Manage Portfolios' . CONST_TITLE_AFX;
$page_description = 'Manage investment portfolios and track performance';
$body_template_file = CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'portfolios.tpl';
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
    'portfolio_name' => '',
    'portfolio_type' => '',
    'description' => '',
    'status' => 'Active'
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

        // Validation
        if (empty($data['portfolio_name'])) {
            $result['error_code'] = 2;
            $result['message'][] = "Portfolio name is required.";
            $result['error_fields'][] = "#portfolio_name";
        } elseif (empty($data['portfolio_type'])) {
            $result['error_code'] = 2;
            $result['message'][] = "Portfolio type is required.";
            $result['error_fields'][] = "#portfolio_type";
        } elseif (!in_array($data['status'], ['Active', 'Inactive'])) {
            $result['error_code'] = 2;
            $result['message'][] = "Please select a valid status.";
            $result['error_fields'][] = "#status";
        } else {
            $portfolio = new \eBizIndia\Portfolio();
            $portfolio_id = $portfolio->add($data);

            if ($portfolio_id) {
                $result['message'] = 'Portfolio has been created successfully.';
            } else {
                $result['error_code'] = 1;
                $result['message'] = 'Failed to create portfolio due to server error.';
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
        $result['message'] = "Sorry, you are not authorized to update portfolios.";
    } else {
        $portfolio_id = (int)$_POST['recordid'];

        if ($portfolio_id <= 0) {
            $result['error_code'] = 2;
            $result['message'][] = "Invalid portfolio reference.";
        } else {
            $options = [
                'filters' => [
                    ['field' => 'portfolio_id', 'value' => $portfolio_id]
                ]
            ];

            $recorddetails = \eBizIndia\Portfolio::getList($options);
            $portfolio = new \eBizIndia\Portfolio($portfolio_id);

            if ($recorddetails === false) {
                $result['error_code'] = 1;
                $result['message'][] = "Failed to verify portfolio details due to server error.";
            } elseif (empty($recorddetails)) {
                $result['error_code'] = 3;
                $result['message'][] = "The portfolio you are trying to modify was not found.";
            } else {
                $data = \eBizIndia\trim_deep(\eBizIndia\striptags_deep(
                    array_intersect_key($_POST, $rec_fields)
                ));

                if (empty($data['portfolio_name'])) {
                    $result['error_code'] = 2;
                    $result['message'][] = "Portfolio name is required.";
                    $result['error_fields'][] = "#portfolio_name";
                } elseif (empty($data['portfolio_type'])) {
                    $result['error_code'] = 2;
                    $result['message'][] = "Portfolio type is required.";
                    $result['error_fields'][] = "#portfolio_type";
                } else {
                    // Track changes
                    $data_to_update = [];
                    foreach ($rec_fields as $fld => $val) {
                        if ($data[$fld] !== ($recorddetails[0][$fld] ?? '')) {
                            $data_to_update[$fld] = $data[$fld];
                        }
                    }

                    if (!empty($data_to_update)) {
                        $update_result = $portfolio->update($data_to_update);

                        if ($update_result === true) {
                            $result['message'] = 'Portfolio has been updated successfully.';
                        } elseif ($update_result === null) {
                            $result['error_code'] = 4;
                            $result['message'] = 'No changes were made.';
                        } else {
                            $result['error_code'] = 1;
                            $result['message'] = 'Failed to update portfolio.';
                        }
                    } else {
                        $result['error_code'] = 4;
                        $result['message'] = 'No changes detected.';
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
    $count_result = \eBizIndia\Portfolio::getList($count_options);
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

    $records = \eBizIndia\Portfolio::getList($options);

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
        $portfolio = new \eBizIndia\Portfolio($_POST['recordid']);
        $details = $portfolio->getDetails();

        if ($details === false) {
            $error = 2;
        } elseif (empty($details)) {
            $error = 3;
        } else {
            $recorddetails = $details[0];
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
        $result['message'] = "Sorry, you are not authorized to delete portfolios.";
    } elseif (empty($_POST['recordid'])) {
        $result['error_code'] = 2;
        $result['message'] = "Invalid portfolio reference.";
    } else {
        $portfolio = new \eBizIndia\Portfolio($_POST['recordid']);

        if ($portfolio->delete()) {
            $result['message'] = 'Portfolio deleted successfully.';
        } else {
            $result['error_code'] = 1;
            $result['message'] = 'Failed to delete portfolio.';
        }
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
