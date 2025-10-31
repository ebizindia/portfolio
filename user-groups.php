<?php
$page = 'user-groups';
require_once 'inc.php';

$template_type = '';
$page_title = 'Manage User Groups' . CONST_TITLE_AFX;
$page_description = 'One can manage the user groups.';
$body_template_file = CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'user-groups.tpl';
$body_template_data = [];
$page_renderer->registerBodyTemplate($body_template_file, $body_template_data);

$can_add = $can_edit = $can_delete = $can_view = false;
$_cu_role = $loggedindata[0]['profile_details']['assigned_roles'][0]['role'];

if (in_array('ALL', $allowed_menu_perms)) {
    $can_add = $can_edit = $can_delete = $can_view = true;
} else {
    $can_add = in_array('ADD', $allowed_menu_perms);
    $can_edit = in_array('EDIT', $allowed_menu_perms);
    $can_delete = in_array('DELETE', $allowed_menu_perms);
    $can_view = in_array('VIEW', $allowed_menu_perms);
}

$rec_fields = [
    'name' => '',
    'active' => '',
];

// Create new user group(s)
if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] === 'createrec') {
    $result = [
        'error_code' => 0,
        'message' => [],
        'elemid' => [],
        'other_data' => []
    ];

    if (!$can_add) {
        $result['error_code'] = 403;
        $result['message'] = "Sorry, you are not authorised to perform this action.";
    } else {
        $user_groups = trim($_POST['name'] ?? '');

        if (empty($user_groups)) {
            $result['error_code'] = 2;
            $result['message'] = 'User Group is required.';
            $result['error_fields'][] = "#add_form_field_name";
        } else {
            $result['other_data']['user_groups_prev'] = $user_groups;
            $user_groups = \eBizIndia\trim_deep(\eBizIndia\striptags_deep(preg_split("/(\r?\n)+/", $user_groups)));

            $invalid_user_groups = array_filter($user_groups, fn($grp) => mb_strlen($grp) > 100);
            $valid_user_groups = array_filter($user_groups, fn($grp) => !empty($grp));

            $result['other_data']['user_groups'] = $user_groups;

            if (!empty($invalid_user_groups)) {
                $result['error_code'] = 2;
                $result['message'] = 'One or more of the user group values exceed the allowed number of characters.';
                $result['error_fields'][] = "#add_form_field_name";
            } elseif (empty($valid_user_groups)) {
                $result['error_code'] = 2;
                $result['message'] = 'Please enter one or more valid user group values.';
                $result['error_fields'][] = "#add_form_field_name";
            } else {
                $created_on = date('Y-m-d H:i:s');
                $ip = \eBizIndia\getRemoteIP();
                $data = [
                    'created_on' => $created_on,
                    'created_by' => $loggedindata[0]['id'],
                    'created_from' => $ip
                ];

                try {
                    $res = \eBizIndia\UserGroup::add($valid_user_groups);
                    if (empty($res)) {
                        throw new Exception('Error adding user groups.');
                    }

                    $result['error_code'] = 0;
                    $result['message'] = count($valid_user_groups) > 1
                        ? 'The user groups were added successfully.'
                        : 'The user group was added successfully.';
                } catch (\Exception $e) {
                    $last_error = \eBizIndia\PDOConn::getLastError();
                    if ($result['error_code'] === 0) {
                        $result['error_code'] = 1; // DB error
                        $result['message'] = "The user groups could not be added due to server error.";
                    }
                    $error_details_to_log = [
                        'function' => __FUNCTION__,
                        'input_data' => $valid_user_groups,
                        'result' => $result,
                        'last_error' => $last_error
                    ];
                    \eBizIndia\ErrorHandler::logError($error_details_to_log, $e);
                }
            }
        }
    }

    $_SESSION['create_rec_result'] = $result;
    header("Location: ?");
    exit;
}

// Update existing user group
if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] === 'updaterec') {
    $result = [
        'error_code' => 0,
        'message' => [],
        'other_data' => []
    ];

    if (!$can_edit) {
        $result['error_code'] = 403;
        $result['message'] = "Sorry, you are not authorised to update the user groups.";
    } else {
        $recordid = (int)($_POST['recordid'] ?? 0);

        if ($recordid === 0) {
            $result['error_code'] = 2;
            $result['message'] = "Invalid user group reference.";
        } else {
            $options = [
                'filters' => [
                    ['field' => 'id', 'type' => 'EQUAL', 'value' => $recordid],
                ]
            ];

            $recorddetails = \eBizIndia\UserGroup::getList($options);

            if ($recorddetails === false) {
                $result['error_code'] = 1;
                $result['message'] = "Failed to verify the user group details due to server error.";
                $result['error_fields'][] = "#edit_form_field_name";
            } elseif (empty($recorddetails)) {
                $result['error_code'] = 3;
                $result['message'] = "The user group you are trying to modify was not found.";
                $result['error_fields'][] = "#edit_form_field_name";
            } else {
                $edit_restricted_fields = [];
                $allowed_fields = array_diff_key($rec_fields, array_fill_keys($edit_restricted_fields, ''));
                $data = \eBizIndia\trim_deep(\eBizIndia\striptags_deep(array_intersect_key($_POST, $allowed_fields)));

                if (empty($data['name'])) {
                    $result['error_code'] = 2;
                    $result['message'] = "User group name is required.";
                    $result['error_fields'][] = "#edit_form_field_name";
                } elseif (mb_strlen($data['name']) > 100) {
                    $result['error_code'] = 2;
                    $result['message'] = "User group name exceeds the allowed number of characters.";
                    $result['error_fields'][] = "#edit_form_field_name";
                } elseif (!in_array($data['active'], ['y', 'n'])) {
                    $result['error_code'] = 2;
                    $result['message'] = "Please select a status for the user group.";
                    $result['error_fields'][] = "input[name=active]:eq(0)";
                } else {
                    $result['other_data']['post'] = $data;
                    $data_to_update = [];

                    foreach ($allowed_fields as $field => $default_value) {
                        if (($data[$field] ?? '') !== ($recorddetails[0][$field] ?? '')) {
                            $data_to_update[$field] = $data[$field];
                        }
                    }

                    try {
                        if (!empty($data_to_update)) {
                            $data_to_update['updated_on'] = date('Y-m-d H:i:s');
                            $data_to_update['updated_by'] = $loggedindata[0]['id'];
                            $data_to_update['updated_from'] = \eBizIndia\getRemoteIP();

                            if (!\eBizIndia\UserGroup::update($data_to_update, $recordid)) {
                                throw new Exception('Error updating the user group.');
                            }

                            $result['error_code'] = 0;
                            $result['message'] = 'The changes have been saved.';
                        } else {
                            $result['error_code'] = 4;
                            $result['message'] = 'There were no changes to save.';
                        }
                    } catch (\Exception $e) {
                        $last_error = \eBizIndia\PDOConn::getLastError();
                        $result['error_code'] = 1;

                        if (($last_error[1] ?? 0) === 1062) {
                            $result['message'] = "Process failed. A user group with this name already exists.";
                        } else {
                            $result['message'] = "The user group could not be updated due to server error.";
                        }

                        $error_details_to_log = [
                            'function' => __FUNCTION__,
                            'input_data' => $data_to_update,
                            'record_id' => $recordid,
                            'last_error' => $last_error,
                            'result' => $result
                        ];
                        \eBizIndia\ErrorHandler::logError($error_details_to_log, $e);
                    }
                }
            }
        }
    }

    $_SESSION['update_rec_result'] = $result;
    header("Location: ?");
    exit;
}

// Handle update response via iframe
if (isset($_SESSION['update_rec_result']) && is_array($_SESSION['update_rec_result'])) {
    header("Content-Type: text/html; charset=UTF-8");
    echo "<script type='text/javascript'>\n";
    echo "parent.usergroupfuncs.handleUpdateRecResponse(" . json_encode($_SESSION['update_rec_result']) . ");\n";
    echo "</script>";
    unset($_SESSION['update_rec_result']);
    exit;
}

// Handle create response via iframe
if (isset($_SESSION['create_rec_result']) && is_array($_SESSION['create_rec_result'])) {
    header("Content-Type: text/html; charset=UTF-8");
    echo "<script type='text/javascript'>\n";
    echo "parent.usergroupfuncs.handleAddRecResponse(" . json_encode($_SESSION['create_rec_result']) . ");\n";
    echo "</script>";
    unset($_SESSION['create_rec_result']);
    exit;
}

// Delete user group
if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] === 'deleteUserGroup') {
    $result = ['error_code' => 0, 'message' => ''];

    if (!$can_delete) {
        $result['error_code'] = 403;
        $result['message'] = "Sorry, you are not authorised to perform this action.";
    } elseif (empty($_POST['rec_id'])) {
        $result['error_code'] = 2;
        $result['message'] = "The user group ID reference was not found.";
    } else {
        try {
            if (\eBizIndia\UserGroup::delete([(int)$_POST['rec_id']])) {
                $result['error_code'] = 0;
                $result['message'] = "The user group was deleted successfully.";
            } else {
                throw new Exception('Delete operation failed');
            }
        } catch (\Exception $e) {
            $last_error = \eBizIndia\PDOConn::getLastError();
            $result['error_code'] = 1;

            if (in_array($last_error[1] ?? 0, [1451, 1452])) {
                $result['message'] = "The user group could not be deleted as it is in use in one or more user profiles.";
            } else {
                $result['message'] = "The user group could not be deleted due to server error.";
            }

            $error_details_to_log = [
                'function' => __FUNCTION__,
                'record_id' => $_POST['rec_id'],
                'last_error' => $last_error
            ];
            \eBizIndia\ErrorHandler::logError($error_details_to_log, $e);
        }
    }

    echo json_encode($result);
    exit;
}

// Get record details for editing
if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] === 'getRecordDetails') {
    $result = [0, ['can_edit' => $can_edit, 'record_details' => null, 'edit_restricted_fields' => []]];
    $error = 0;

    if (empty($_POST['recordid'])) {
        $error = 1; // Record ID missing
    } else {
        $options = [
            'filters' => [
                ['field' => 'id', 'type' => 'EQUAL', 'value' => (int)$_POST['recordid']],
            ]
        ];

        $recorddetails = \eBizIndia\UserGroup::getList($options);

        if ($recorddetails === false) {
            $error = 2; // DB error
        } elseif (empty($recorddetails)) {
            $error = 3; // Record does not exist
        } else {
            $recorddetails = $recorddetails[0];
            $recorddetails['name_disp'] = \eBizIndia\_esc($recorddetails['name'], true);
            $edit_restricted_fields = [];
            $result[1]['record_details'] = $recorddetails;
            $result[1]['edit_restricted_fields'] = $edit_restricted_fields;
        }
    }

    $result[0] = $error;
    $result[1]['cuid'] = $loggedindata[0]['id'];

    echo json_encode($result);
    exit;
}

// Get list of user groups
if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] === 'getList') {
    $result = [0, []]; // error code and data
    $error = 0;
    $options = ['filters' => []];

    $pno = filter_var($_POST['pno'] ?? $_GET['pno'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
    $recsperpage = filter_var($_POST['recsperpage'] ?? $_GET['recsperpage'] ?? CONST_RECORDS_PER_PAGE, FILTER_VALIDATE_INT) ?: CONST_RECORDS_PER_PAGE;

    $filtertext = [];

    // Process search data
    if (!empty($_POST['searchdata'])) {
        $searchdata = json_decode($_POST['searchdata'], true);
        if (!is_array($searchdata)) {
            $error = 2; // Invalid search parameters
        } elseif (!empty($searchdata)) {
            foreach ($searchdata as $filter) {
                $field = $filter['searchon'] ?? '';
                $type = $filter['searchtype'] ?? '';
                $value = \eBizIndia\trim_deep($filter['searchtext'] ?? '');

                if (!empty($field) && !empty($value)) {
                    $options['filters'][] = ['field' => $field, 'type' => $type, 'value' => $value];

                    $filter_text = match($field) {
                        'name' => 'Group name ',
                        default => ucfirst($field) . ' '
                    };

                    $filter_text .= match($type) {
                        'CONTAINS' => 'has ',
                        'EQUAL' => 'is ',
                        'STARTS_WITH' => 'starts with ',
                        'AFTER' => 'after ',
                        default => ''
                    };

                    $filtertext[] = '<span class="searched_elem">' . $filter_text .
                        '<b>' . \eBizIndia\_esc($value, true) . '</b>' .
                        '<span class="remove_filter" data-fld="' . $field . '">X</span></span>';
                }
            }
            $result[1]['filtertext'] = implode('', $filtertext);
        }
    }

    // Get total record count
    $tot_rec_options = [
        'fieldstofetch' => ['recordcount'],
        'filters' => [],
    ];
    $tot_rec_cnt = \eBizIndia\UserGroup::getList($tot_rec_options);
    $result[1]['tot_rec_cnt'] = $tot_rec_cnt[0]['recordcount'] ?? 0;

    // Get filtered record count
    $count_options = $options;
    $count_options['fieldstofetch'] = ['recordcount'];
    $recordcount_result = \eBizIndia\UserGroup::getList($count_options);
    $recordcount = $recordcount_result[0]['recordcount'] ?? 0;

    $paginationdata = \eBizIndia\getPaginationData($recordcount, $recsperpage, $pno, CONST_PAGE_LINKS_COUNT);
    $result[1]['paginationdata'] = $paginationdata;

    $records = [];
    if ($recordcount > 0) {
        $options['page'] = $pno;
        $options['recs_per_page'] = $paginationdata['recs_per_page'];

        // Process sort data
        if (!empty($_POST['sortdata'])) {
            $sortdata = json_decode($_POST['sortdata'], true);
            if (is_array($sortdata)) {
                $options['order_by'] = array_map(fn($sort) => [
                    'field' => $sort['sorton'] ?? 'name',
                    'type' => $sort['sortorder'] ?? 'ASC'
                ], $sortdata);
            }
        }

        $records = \eBizIndia\UserGroup::getList($options);

        if ($records === false) {
            $error = 1; // DB error
            $records = [];
        }
    }

    $result[0] = $error;
    $result[1]['reccount'] = $recordcount;
    $result[1]['list'] = $records;

    // Generate HTML if requested
    if (($_POST['listformat'] ?? '') === 'html') {
        $get_list_template_data = [
            'mode' => $_POST['mode'],
            $_POST['mode'] => [
                'error' => $error,
                'records' => $records,
                'records_count' => count($records),
                'cu_id' => $loggedindata[0]['id'],
                'filtertext' => $result[1]['filtertext'] ?? '',
                'filtercount' => count($filtertext),
                'tot_col_count' => count($records[0] ?? []) + 1, // +1 for action column
            ],
            'logged_in_user' => $loggedindata[0],
            'can_edit' => $can_edit,
            'can_delete' => $can_delete,
        ];

        $paginationdata['link_data'] = "";
        $paginationdata['page_link'] = '#';
        $get_list_template_data[$_POST['mode']]['pagination_html'] =
            $page_renderer->fetchContent(CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'pagination-bar.tpl', $paginationdata);

        $page_renderer->updateBodyTemplateData($get_list_template_data);
        $result[1]['list'] = $page_renderer->fetchContent();
    }

    echo json_encode($result, JSON_HEX_TAG);
    exit;
}

// Page rendering setup
$dom_ready_data['user-groups'] = [
    'field_meta' => CONST_FIELD_META,
];

$jscode = "const CAN_ADD = " . var_export($can_add, true) . ";\n";
$jscode .= "const CAN_EDIT = " . var_export($can_edit, true) . ";\n";
$jscode .= "const CAN_DELETE = " . var_export($can_delete, true) . ";\n";

$additional_base_template_data = [
    'page_title' => $page_title,
    'page_description' => $page_description,
    'template_type' => $template_type,
    'dom_ready_code' => \scriptProviderFuncs\getDomReadyJsCode($page, $dom_ready_data),
    'other_js_code' => $jscode,
    'module_name' => $page
];

$additional_body_template_data = ['can_add' => $can_add];

$page_renderer->updateBodyTemplateData($additional_body_template_data);
$page_renderer->updateBaseTemplateData($additional_base_template_data);
$page_renderer->addCss(\scriptProviderFuncs\getCss($page));

$js_files = \scriptProviderFuncs\getJavascripts($page);
$page_renderer->addJavascript($js_files['BSH'], 'BEFORE_SLASH_HEAD');
$page_renderer->addJavascript($js_files['BSB'], 'BEFORE_SLASH_BODY');
$page_renderer->renderPage();
?>