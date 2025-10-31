<?php
$page = 'items';
require_once 'inc.php';

$template_type = '';
$page_title = 'Manage Items' . CONST_TITLE_AFX;
$page_description = 'Manage items for product inventory.';
$body_template_file = CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'items.tpl';
$body_template_data = [];
$page_renderer->registerBodyTemplate($body_template_file, $body_template_data);
$can_add = $can_edit = true;
$_cu_role = $loggedindata[0]['profile_details']['assigned_roles'][0]['role'] ?? '';

// Define fields for add/edit
$rec_fields = [
    'name' => '',
    'make' => '',
    'unit' => '',
];

// Create item
if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'createrec') {
    $result = ['error_code' => 0, 'message' => [], 'elemid' => [], 'other_data' => []];

    if ($can_add === false) {
        $result['error_code'] = 403;
        $result['message'] = "Sorry, you are not authorized to perform this action.";
    } else {
        $data = \eBizIndia\trim_deep(\eBizIndia\striptags_deep(array_intersect_key($_POST, $rec_fields)));
        $item = new \eBizIndia\Item();

        // Updated Validation
        if ($data['name'] == '') {
            $result['error_code'] = 2;
            $result['message'][] = "Name is required.";
            $result['error_fields'][] = "#add_name";
        } elseif (strlen($data['name']) > 100) {
            $result['error_code'] = 2;
            $result['message'][] = "Name must not exceed 100 characters.";
            $result['error_fields'][] = "#add_name";
        } elseif (\eBizIndia\Item::nameExists($data['name'])) {
            $result['error_code'] = 2;
            $result['message'][] = "An item with this name already exists.";
            $result['error_fields'][] = "#add_name";
        } elseif ($data['unit'] == '') {
            $result['error_code'] = 2;
            $result['message'][] = "Unit is required.";
            $result['error_fields'][] = "#add_unit";
        }else {
            try {
                $conn = \eBizIndia\PDOConn::getInstance();
                $conn->beginTransaction();
                
                // Set additional fields for creation
                $data['created_on'] = date('Y-m-d H:i:s');
                $data['created_by'] = $loggedindata[0]['id'];
                $data['created_from_ip'] = \eBizIndia\getRemoteIP();
                
                // Insert record
                $recordid = $item->add($data);
                if (!$recordid) {
                    throw new Exception('Error adding the item.');
                }
                
                $result['error_code'] = 0;
                $result['message'] = 'The item has been saved.';
                
                $conn->commit();
                
            } catch (\Exception $e) {
                $last_error = \eBizIndia\PDOConn::getLastError();
                $result['error_code'] = 1;
                
                if ($last_error[1] == 1062) {
                    $result['message'] = "Process failed. An item with this name already exists.";
                } elseif ($result['message'] == '') {
                    $result['message'] = "The item could not be added due to server error.";
                }
                
                $error_details_to_log['last_error'] = $last_error;
                $error_details_to_log['result'] = $result;
                \eBizIndia\ErrorHandler::logError($error_details_to_log, $e);
                
                if ($conn && $conn->inTransaction()) {
                    $conn->rollBack();
                }
            }
        }
    }
    
    $_SESSION['create_rec_result'] = $result;
    header("Location:?");
    exit;
}

// Update item
elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'updaterec') {
    $result = ['error_code' => 0, 'message' => [], 'other_data' => []];
    
    if ($can_edit === false) {
        $result['error_code'] = 403;
        $result['message'] = "Sorry, you are not authorized to update items.";
    } else {
        $recordid = (int)$_POST['recordid'];
        // Validate record ID
        if ($recordid == '') {
            $result['error_code'] = 2;
            $result['message'][] = "Invalid item reference.";
        } else {
            $options = [];
            $options['filters'] = [
                ['field' => 'id', 'type' => '=', 'value' => $recordid],
            ];
            
            $recorddetails = \eBizIndia\Item::getList($options);
            $item = new \eBizIndia\Item($recordid);
            
            if ($recorddetails === false) {
                $result['error_code'] = 1;
                $result['message'] = "Failed to verify the item details due to server error.";
                $result['error_fields'][] = "#add_sku";
            } elseif (empty($recorddetails)) {
                $result['error_code'] = 3;
                $result['message'] = "The item you are trying to modify was not found.";
                $result['error_fields'][] = "#add_sku";
            } else {
                $data = \eBizIndia\trim_deep(\eBizIndia\striptags_deep(array_intersect_key($_POST, $rec_fields)));
                
                // Validation
                if ($data['name'] == '') {
                    $result['error_code'] = 2;
                    $result['message'][] = "Name is required.";
                    $result['error_fields'][] = "#add_name";
                } elseif (strlen($data['name']) > 100) {
                    $result['error_code'] = 2;
                    $result['message'][] = "Name must not exceed 100 characters.";
                    $result['error_fields'][] = "#add_name";
                } elseif (\eBizIndia\Item::nameExists($data['name'], $recordid)) {
                    $result['error_code'] = 2;
                    $result['message'][] = "An item with this name already exists.";
                    $result['error_fields'][] = "#add_name";
                } elseif ($data['unit'] == '') {
                    $result['error_code'] = 2;
                    $result['message'][] = "Unit is required.";
                    $result['error_fields'][] = "#add_unit";
                }else {
                    // $result['other_data']['post'] = $data;
                    $data_to_update = [];
                    $data_changed = false;

                    // Check which fields have changed
                    foreach ($rec_fields as $fld => $val) {
                        if ($data[$fld] !== $recorddetails[0][$fld]) {
                            $data_changed = true;
                            $data_to_update[$fld] = $data[$fld];
                        }
                    }
                    
                    try {
                        $conn = \eBizIndia\PDOConn::getInstance();
                        $conn->beginTransaction();
                        
                        // Only process updates if there are changes
                        if ($data_changed || !empty($data_to_update)) {
                            // Add update metadata to data_to_update
                            $data_to_update['updated_on'] = date('Y-m-d H:i:s');
                            $data_to_update['updated_by'] = $loggedindata[0]['id'];
                            $data_to_update['updated_from_ip'] = \eBizIndia\getRemoteIP();
                            
                            // Update the database record
                            $r = $item->update($data_to_update);
                            if (!$r && $r !== null) {
                                throw new Exception('Error updating the item in the database.');
                            }
                            
                            $conn->commit();
                            
                            $result['error_code'] = 0;
                            $result['message'] = 'The changes have been saved.';
                        } else {
                            $result['error_code'] = 4;
                            $result['message'] = 'There were no changes to save.';
                            $conn->rollBack(); // No need to commit if no changes
                        }
                    } catch (\Exception $e) {
                        $last_error = \eBizIndia\PDOConn::getLastError();
                        $result['error_code'] = 1;
                        
                        // Log detailed error for debugging
                        $error_details_to_log = [];
                        $error_details_to_log['exception_message'] = $e->getMessage();
                        $error_details_to_log['exception_trace'] = $e->getTraceAsString();

                        if ($last_error[1] == 1062) {
                            $result['message'] = "Process failed. An item with this name already exists.";
                        } else {
                            $result['message'] = "The item could not be updated due to server error: " . $e->getMessage();
                        }
                        
                        $error_details_to_log['last_error'] = $last_error;
                        $error_details_to_log['result'] = $result;
                        \eBizIndia\ErrorHandler::logError($error_details_to_log, $e);
                        
                        if ($conn && $conn->inTransaction()) {
                            $conn->rollBack();
                        }
                    }
                }
            }
        }
    }
    
    $_SESSION['update_rec_result'] = $result;
    header("Location:?");
    exit;
}

// Handle update response
elseif (isset($_SESSION['update_rec_result']) && is_array($_SESSION['update_rec_result'])) {
    header("Content-Type: text/html; charset=UTF-8");
    echo "<script type='text/javascript'>\n";
    echo "parent.itemfuncs.handleUpdateRecResponse(" . json_encode($_SESSION['update_rec_result']) . ");\n";
    echo "</script>";
    unset($_SESSION['update_rec_result']);
    exit;
}

// Handle create response
elseif (isset($_SESSION['create_rec_result']) && is_array($_SESSION['create_rec_result'])) {
    header("Content-Type: text/html; charset=UTF-8");
    echo "<script type='text/javascript'>\n";
    echo "parent.itemfuncs.handleAddRecResponse(" . json_encode($_SESSION['create_rec_result']) . ");\n";
    echo "</script>";
    unset($_SESSION['create_rec_result']);
    exit;
}

// Delete item
elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'deleteItem') {
    $result = [];
    
    if ($can_add === false) {
        $result['error_code'] = 403;
        $result['message'] = "Sorry, you are not authorized to perform this action.";
    } else if ($_POST['rec_id'] == '') {
        $result['error_code'] = 2;
        $result['message'] = "The item ID reference was not found.";
    } else {
        $item_id = (int)$_POST['rec_id'];
        
        // Check if item is in use
        if (\eBizIndia\Item::delete([$item_id])) {
            $result['error_code'] = 0;
            $result['message'] = "The item was deleted successfully.";
        } else {
            $last_error = \eBizIndia\PDOConn::getLastError();
            if ($last_error[1] == 1451 || $last_error[1] == 1452) {
                $result['error_code'] = 1;
                $result['message'] = "The item could not be deleted as it is in use in other records.";
            } else {
                $result['error_code'] = 1;
                $result['message'] = "The item could not be deleted due to server error.";
            }
        }
    }
    
    echo json_encode($result);
    exit;
}

// Export items
elseif (filter_has_var(INPUT_GET, 'mode') && $_GET['mode'] === 'export') {
    if (strcasecmp($_cu_role, 'ADMIN') !== 0) {
        header('HTTP/1.0 403 Forbidden', true, 403);
        die;
    }

    $item_export_fields = [
        'id' => 'ID',
        'name' => 'Name',
        'make' => 'Make',
        'unit' => 'Unit',
    ];
    
    $options = [];
    $options['filters'] = [];
    
    // Handle search parameters
    if (filter_has_var(INPUT_GET, 'searchdata') && $_GET['searchdata'] != '') {
        $searchdata = json_decode($_GET['searchdata'], true);
        if (is_array($searchdata) && !empty($searchdata)) {
            foreach ($searchdata as $filter) {
                $field = $filter['searchon'];
                $type = $filter['searchtype'] ?? '';
                $value = trim($filter['searchtext'] ?? '');
                $options['filters'][] = ['field' => $field, 'type' => $type, 'value' => $value];
            }
        }
    }
    
    // Handle sort parameters
    if (filter_has_var(INPUT_GET, 'sortdata') && $_GET['sortdata'] != '') {
        $options['order_by'] = [];
        $sortdata = json_decode($_GET['sortdata'], true);
        foreach ($sortdata as $sort_param) {
            $options['order_by'][] = ['field' => $sort_param['sorton'], 'type' => $sort_param['sortorder']];
        }
    }
    
    $records = \eBizIndia\Item::getList($options);
    
    if ($records === false) {
        header('HTTP/1.0 500 Internal Server Error', true, 500);
        die;
    } elseif (empty($records)) {
        header('HTTP/1.0 204 No Content', true, 204);
        die;
    } else {
        ob_clean();
        header('Content-Description: File Transfer');
        header('Content-Type: application/csv');
        header("Content-Disposition: attachment; filename=items.csv");
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        $fh = fopen('php://output', 'w');
        
        if (!$fh) {
            header('HTTP/1.0 500 Internal Server Error', true, 500);
            die;
        }
        
        $col_headers = array_values($item_export_fields);
        $data_row_flds = array_fill_keys(array_keys($item_export_fields), '');
        fputcsv($fh, $col_headers);
        
        foreach ($records as $rec) {
            $data_row = array_intersect_key(array_replace($data_row_flds, $rec), $data_row_flds);
            fputcsv($fh, array_values($data_row));
        }
        
        ob_flush();
        fclose($fh);
        die;
    }
}

// Get List of items
elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getList') {
    $result = [0, []]; // error code and list html
    $options = [];
    $options['filters'] = [];
    $filterparams = [];
    $sortparams = [];
    $pno = (isset($_POST['pno']) && $_POST['pno'] != '' && is_numeric($_POST['pno'])) ? $_POST['pno'] : ((isset($_GET['pno']) && $_GET['pno'] != '' && is_numeric($_GET['pno'])) ? $_GET['pno'] : 1);
    $recsperpage = (isset($_POST['recsperpage']) && $_POST['recsperpage'] != '' && is_numeric($_POST['recsperpage'])) ? $_POST['recsperpage'] : ((isset($_GET['recsperpage']) && $_GET['recsperpage'] != '' && is_numeric($_GET['recsperpage'])) ? $_GET['recsperpage'] : CONST_RECORDS_PER_PAGE_COMMON);
    $filtertext = [];
    
    // Handle search parameters
    if (filter_has_var(INPUT_POST, 'searchdata') && $_POST['searchdata'] != '') {
        $searchdata = json_decode($_POST['searchdata'], true);
        if (!is_array($searchdata)) {
            $error = 2; // invalid search parameters
        } else if (!empty($searchdata)) {
            $options['filters'] = [];
            foreach ($searchdata as $filter) {
                $field = $filter['searchon'];
                $type = $filter['searchtype'] ?? '';
                $value = \eBizIndia\trim_deep($filter['searchtext'] ?? '');
                $options['filters'][] = ['field' => $field, 'type' => $type, 'value' => $value];
                
                // Create filter text for display
                switch (strtolower($field)) {
                    case 'name': $fltr_text = 'Name '; break;
                    case 'unit': $fltr_text = 'Unit '; break;
                    default: $fltr_text = ucfirst($field) . ' '; break;
                }

                switch ($type) {
                    case 'CONTAINS': $fltr_text .= 'has '; break;
                    case 'EQUAL': $fltr_text .= 'is '; break;
                    case 'STARTS_WITH': $fltr_text .= 'starts with '; break;
                    default: break;
                }
                
                $filtertext[] = '<span class="searched_elem">' . $fltr_text . '<b>' . \eBizIndia\_esc(!empty($filter['disp_text'])?$filter['disp_text']:$value, true) . '</b><span class="remove_filter" data-fld="' . $field . '">X</span></span>';
            }
            $result[1]['filtertext'] = implode($filtertext);
        }
    }
    
    // Get total record count
    $tot_rec_options = [
        'fieldstofetch' => ['recordcount'],
        'filters' => [],
    ];
    $options['fieldstofetch'] = ['recordcount'];
    $tot_rec_cnt = \eBizIndia\Item::getList($tot_rec_options);
    $result[1]['tot_rec_cnt'] = $tot_rec_cnt[0]['recordcount'];
    
    // Get filtered record count
    $recordcount = \eBizIndia\Item::getList($options);
    $recordcount = $recordcount[0]['recordcount'];
    $paginationdata = \eBizIndia\getPaginationData($recordcount, $recsperpage, $pno, CONST_PAGE_LINKS_COUNT);
    $result[1]['paginationdata'] = $paginationdata;
    if ($recordcount > 0) {
        $noofrecords = $paginationdata['recs_per_page'];
        unset($options['fieldstofetch']);
        $options['page'] = $pno;
        $options['recs_per_page'] = $noofrecords;
        
        // Handle sort parameters
        if (isset($_POST['sortdata']) && $_POST['sortdata'] != '') {
            $options['order_by'] = [];
            $sortdata = json_decode($_POST['sortdata'], true);
            foreach ($sortdata as $sort_param) {
                $options['order_by'][] = ['field' => $sort_param['sorton'], 'type' => $sort_param['sortorder']];
            }
        }
        
        // Get records
        $records = \eBizIndia\Item::getList($options);
        if ($records === false) {
            $error = 1; // db error
        } else {
            $result[1]['list'] = $records;
        }
    }
    
    $result[0] = $error ?? 0;
    $result[1]['reccount'] = $recordcount;
    
    // Return HTML or JSON
    if ($_POST['listformat'] == 'html') {
        $get_list_template_data = [];
        $get_list_template_data['mode'] = $_POST['mode'];
        $get_list_template_data[$_POST['mode']] = [];
        $get_list_template_data[$_POST['mode']]['error'] = $error ?? 0;
        $get_list_template_data[$_POST['mode']]['records'] = $records ?? [];
        $get_list_template_data[$_POST['mode']]['records_count'] = count($records ?? []);
        $get_list_template_data[$_POST['mode']]['cu_id'] = $loggedindata[0]['id'];
        $get_list_template_data[$_POST['mode']]['filtertext'] = $result[1]['filtertext'] ?? '';
        $get_list_template_data[$_POST['mode']]['filtercount'] = count($filtertext);
        $get_list_template_data[$_POST['mode']]['tot_col_count'] = count($records[0] ?? []) + 1; // +1 for the action column
        $paginationdata['link_data'] = "";
        $paginationdata['page_link'] = '#';
        $get_list_template_data[$_POST['mode']]['pagination_html'] = $page_renderer->fetchContent(CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'pagination-bar.tpl', $paginationdata);
        $get_list_template_data['logged_in_user'] = $loggedindata[0];
        $page_renderer->updateBodyTemplateData($get_list_template_data);
        $result[1]['list'] = $page_renderer->fetchContent();
    }
    
    echo json_encode($result, JSON_HEX_TAG);
    exit;
}// Get item details
elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getRecordDetails') {
    $result = [];
    $error = 0;
    $can_edit = true;
    
    if ($_POST['recordid'] == '') {
        $error = 1; // Record ID missing
    } else {
        $options = [];
        $options['filters'] = [
            ['field' => 'id', 'type' => '=', 'value' => $_POST['recordid']],
        ];
        
        $recorddetails = \eBizIndia\Item::getList($options);
        
        if ($recorddetails === false) {
            $error = 2; // db error
        } elseif (count($recorddetails) == 0) {
            $error = 3; // Rec ID does not exist
        } else {
            $recorddetails = $recorddetails[0];
            $recorddetails['item_disp'] = \eBizIndia\_esc($recorddetails['name'], true);
            
            $edit_restricted_fields = [];
        }
    }
    
    $result[0] = $error;
    $result[1]['can_edit'] = $can_edit;
    $result[1]['cuid'] = $loggedindata[0]['id'];
    $result[1]['record_details'] = $recorddetails;
    $result[1]['edit_restricted_fields'] = $edit_restricted_fields;
    
    echo json_encode($result);
    exit;
}

// Default page rendering
// Get categories for dropdown
$categories_options = [
    'order_by' => [['field' => 'name', 'type' => 'ASC']]
];
$dom_ready_data['items'] = ['field_meta' => CONST_FIELD_META ?? []];
$additional_base_template_data = [
    'page_title' => $page_title,
    'page_description' => $page_description,
    'template_type' => $template_type,
    'dom_ready_code' => \scriptProviderFuncs\getDomReadyJsCode($page, $dom_ready_data),
    'other_js_code' => $jscode ?? '',
    'module_name' => $page
];

$additional_body_template_data = [
    'can_add' => $can_add,
];

$page_renderer->updateBodyTemplateData($additional_body_template_data);
$page_renderer->updateBaseTemplateData($additional_base_template_data);
$page_renderer->addCss(\scriptProviderFuncs\getCss($page));
$js_files = \scriptProviderFuncs\getJavascripts($page);
$page_renderer->addJavascript($js_files['BSH'], 'BEFORE_SLASH_HEAD');
$page_renderer->addJavascript($js_files['BSB'], 'BEFORE_SLASH_BODY');
$page_renderer->renderPage();