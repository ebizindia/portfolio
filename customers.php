<?php
$page = 'customers';
require_once 'inc.php';

$template_type = '';
$page_title = 'Manage Customers List' . CONST_TITLE_AFX;
$page_description = 'One can manage the customers list.';
$body_template_file = CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'customers.tpl';
$body_template_data = [];
$page_renderer->registerBodyTemplate($body_template_file, $body_template_data);

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
    'name' => '',
    'industry_id' => '',
    'customer_group_id' => '',
//    'pan' => '',
//    'gstin' => '',
    'address_1' => '',
    'address_2' => '',
    'address_3' => '',
    'city' => '',
    'state' => '',
    'pin' => '',
    'website' => '',
    'business_details' => '',
    'active' => ''
];

if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'createrec') {
    $result = ['error_code' => 0, 'message' => [], 'elemid' => [], 'other_data' => []];
    
    if ($can_add === false) {
        $result['error_code'] = 403;
        $result['message'] = "Sorry, you are not authorized to perform this action.";
    } else {
        $data = \eBizIndia\trim_deep(\eBizIndia\striptags_deep(array_intersect_key($_POST, $rec_fields)));
        $customer = new \eBizIndia\Customer();
        
        if ($data['name'] == '') {
            $result['error_code'] = 2;
            $result['message'][] = "Customer name is required.";
            $result['error_fields'][] = "#add_comp_name";
        } elseif ($data['active'] != 'y' && $data['active'] != 'n') {
            $result['error_code'] = 2;
            $result['message'][] = "Please select a status for the customer.";
            $result['error_fields'][] = "input[name=active]:eq(0)";
        } else {
            try {
                $conn = \eBizIndia\PDOConn::getInstance();
                $conn->beginTransaction();
                
                // Add customer record
                $recordid = $customer->add($data);
                if (!$recordid) {
                    throw new Exception('Error adding the customer.');
                }
                
                // Add contacts if present
                $contacts = [];
                if (isset($_POST['contact_name'])) {
                    foreach ($_POST['contact_name'] as $idx => $name) {
                        if (!empty($name)) {
                            $contacts[] = [
                                'name' => $name,
                                'department' => $_POST['contact_department'][$idx] ?? '',
                                'designation' => $_POST['contact_designation'][$idx] ?? '',
                                'email' => $_POST['contact_email'][$idx] ?? '',
                                'phone' => $_POST['contact_phone'][$idx] ?? ''
                            ];
                        }
                    }

                    $customer = new \eBizIndia\Customer($recordid);
                    if (!empty($contacts)) {
                        if(!$customer->addContacts($contacts))
                            throw new Exception("Error adding the contacts");
                            
                    }
                }
                
                $conn->commit();
                
                $result['error_code'] = 0;
                $result['message'] = 'The customer has been saved.';
            } catch (\Exception $e) {
                $last_error = \eBizIndia\PDOConn::getLastError();
                $result['error_code'] = 1;
                
                if ($last_error[1] == 1062) {
                    $result['message'] = "Process failed. A customer with this name already exists.";
                } elseif ($result['message'] == '') {
                    $result['message'] = "The customer could not be added due to server error.";
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
} // Inside the updaterec mode handling section in customers.php
elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'updaterec') {
    $result = ['error_code' => 0, 'message' => [], 'other_data' => []];
    
    if ($can_edit === false) {
        $result['error_code'] = 403;
        $result['message'] = "Sorry, you are not authorized to update the customers.";
    } else {
        $data = [];
        $recordid = (int)$_POST['recordid'];
        
        if ($recordid == '') {
            $result['error_code'] = 2;
            $result['message'][] = "Invalid customer reference.";
        } else {
            $options = [
                'filters' => [
                    ['field' => 'id', 'type' => '=', 'value' => $recordid],
                ]
            ];
            
            $recorddetails = \eBizIndia\Customer::getList($options);
            $customer = new \eBizIndia\Customer($recordid);
            
            if ($recorddetails === false) {
                $result['error_code'] = 1;
                $result['message'][] = "Failed to verify the customer details due to server error.";
            } elseif (empty($recorddetails)) {
                $result['error_code'] = 3;
                $result['message'][] = "The customer you are trying to modify was not found.";
            } else {
                $data = \eBizIndia\trim_deep(\eBizIndia\striptags_deep(array_intersect_key($_POST, $rec_fields)));
                
                if ($data['name'] == '') {
                    $result['error_code'] = 2;
                    $result['message'][] = "Customer name is required.";
                    $result['error_fields'][] = "#add_comp_name";
                } elseif ($data['active'] != 'y' && $data['active'] != 'n') {
                    $result['error_code'] = 2;
                    $result['message'][] = "Please select a status for the customer.";
                    $result['error_fields'][] = "input[name=active]:eq(0)";
                } else {
                    try {
                        $conn = \eBizIndia\PDOConn::getInstance();
                        $conn->beginTransaction();
                        
                        // Track changes in both customer data and contacts
                        $customer_data_changed = false;
                        $contact_data_changed = false;
                        
                        // Process customer data changes
                        $data_to_update = [];
                        foreach ($rec_fields as $fld => $val) {
                            if(($fld=='industry_id' || $fld=='customer_group_id') && !empty($data[$fld]))
                                $data[$fld] = (int)$data[$fld];
                            if ($data[$fld] !== ($recorddetails[0][$fld]??'') ) {
                                $data_to_update[$fld] = $data[$fld];
                            }
                        }
                        $result['$data_to_update'] = $data_to_update;
                        $result['$recorddetails'] = $recorddetails[0];
                        
                        if (!empty($data_to_update)) {
                            
                            $customer_data_changed = true;
                        }
                        
                        // Process contacts (always get existing contacts for comparison)
                        $existingContacts = $customer->getContacts() ?: [];
                        $existingContactIds = array_column($existingContacts, 'id');
                        $existingContacts_id_indexed = array_combine($existingContactIds, $existingContacts);
                        
                        // Track processed contact IDs and new contacts
                        $processedContactIds = [];
                        $newContacts = [];
                        
                        // Only process contact submissions if names are sent
                        if (isset($_POST['contact_name']) && is_array($_POST['contact_name'])) {
                            foreach ($_POST['contact_name'] as $idx => $name) {
                                if (!empty($name)) {
                                    $contactData = [
                                        'name' => $name,
                                        'department' => $_POST['contact_department'][$idx] ?? '',
                                        'designation' => $_POST['contact_designation'][$idx] ?? '',
                                        'email' => $_POST['contact_email'][$idx] ?? '',
                                        'phone' => $_POST['contact_phone'][$idx] ?? ''
                                    ];
                                    
                                    // If contact has ID, check for changes and update
                                    if (!empty($_POST['contact_id'][$idx])) {
                                        $contact_id = (int)$_POST['contact_id'][$idx];
                                        $processedContactIds[] = $contact_id;
                                        
                                        // Only update if data has changed
                                        if ($customer->contactDataChanged($existingContacts_id_indexed[$contact_id]??[], $contactData)) {
                                            if (!$customer->updateContact($contact_id, $contactData)) {
                                                throw new Exception("Error updating contact");
                                            }
                                            $contact_data_changed = true;
                                        }
                                    } else {
                                        // Add new contact
                                        $newContacts[] = $contactData;
                                        $contact_data_changed = true;
                                    }
                                }
                            }
                        }
                        
                        // Add any new contacts
                        if (!empty($newContacts)) {
                            if (!$customer->addContacts($newContacts)) {
                                throw new Exception("Error adding new contacts");
                            }
                            $contact_data_changed = true;
                        }
                        
                        // Calculate contacts to delete (regardless of whether new contacts were submitted)
                        $contactsToDelete = array_diff($existingContactIds, $processedContactIds);
                        if (!empty($contactsToDelete)) {
                            if (!$customer->deleteContacts($contactsToDelete)) {
                                throw new Exception("Error deleting removed contacts");
                            }
                            $contact_data_changed = true;
                        }
                        
                        // Set appropriate success/error message based on what changed
                        if ($customer_data_changed || $contact_data_changed) {
                            $result['customer_data_changed'] = (int)$customer_data_changed;
                            $result['contact_data_changed'] = (int)$contact_data_changed;
                            // Add tracking information
                            $data_to_update['updated_on'] = date('Y-m-d H:i:s');
                            $data_to_update['updated_by'] = $loggedindata[0]['id'] ?? null;
                            $data_to_update['updated_from'] = \eBizIndia\getRemoteIP();
                            $r = $customer->update($data_to_update);
                            if (!$r && $r !== null) {
                                throw new Exception('Error updating the customer.');
                            }

                            $conn->commit();

                            $result['error_code'] = 0;
                            if ($customer_data_changed && $contact_data_changed) {
                                $result['message'] = 'Customer details and contacts have been updated.';
                            } elseif ($customer_data_changed) {
                                $result['message'] = 'Customer details have been updated.';
                            } else {
                                $result['message'] = 'Customer contacts have been updated.';
                            }
                        } else {
                            $result['error_code'] = 4;
                            $result['message'] = 'There were no changes to save.';
                        }
                    } catch (\Exception $e) {
                        $last_error = \eBizIndia\PDOConn::getLastError();
                        $result['error_code'] = 1;
                        
                        if ($last_error[1] == 1062) {
                            $result['message'] = "Process failed. A customer with this name already exists.";
                        } else {
                            $result['message'] = "The customer could not be updated due to server error.";
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
}elseif(isset($_SESSION['update_rec_result']) && is_array($_SESSION['update_rec_result'])){
    header("Content-Type: text/html; charset=UTF-8");
    echo "<script type='text/javascript' >\n";
    echo "parent.customers.handleUpdateRecResponse(".json_encode($_SESSION['update_rec_result']).");\n";
    echo "</script>";
    unset($_SESSION['update_rec_result']);
    exit;

}elseif(isset($_SESSION['create_rec_result']) && is_array($_SESSION['create_rec_result'])){
    header("Content-Type: text/html; charset=UTF-8");
    echo "<script type='text/javascript' >\n";
    echo "parent.customers.handleAddRecResponse(".json_encode($_SESSION['create_rec_result']).");\n";
    echo "</script>";
    unset($_SESSION['create_rec_result']);
    exit;

} elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getRecordDetails') {
    $result = [];
    $error = 0;
    $can_edit = true;
    
    if ($_POST['recordid'] == '') {
        $error = 1; // Record ID missing
    } else {
        $options = [
            'filters' => [
                ['field' => 'id', 'type' => '=', 'value' => $_POST['recordid']],
            ]
        ];
        
        $recorddetails = \eBizIndia\Customer::getList($options);
        
        if ($recorddetails === false) {
            $error = 2; // db error
        } elseif (count($recorddetails) == 0) {
            $error = 3; // Rec ID does not exist
        } else {
            $recorddetails = $recorddetails[0];
            $recorddetails['customer_disp'] = \eBizIndia\_esc($recorddetails['name'], true);
            
            // Fetch contacts for this customer
            $customer = new \eBizIndia\Customer($_POST['recordid']);
            $contacts = $customer->getContacts();
            $recorddetails['contacts'] = $contacts ?: [];
        }
    }
    
    $result[0] = $error;
    $result[1]['can_edit'] = $can_edit;
    $result[1]['cuid'] = $loggedindata[0]['id'];
    $result[1]['record_details'] = $recorddetails;
    
    echo json_encode($result);
    exit;
} elseif (filter_has_var(INPUT_GET, 'mode') && $_GET['mode'] === 'export') {
    if (strcasecmp($_cu_role, 'ADMIN') !== 0) {
        header('HTTP/1.0 403 Forbidden', true, 403);
        die;
    }
    
    $customer_export_fields = [
        'name' => 'Customer Name',
        'industry_name' => 'Industry',
        'customer_group_name' => 'Customer Group',
//        'pan' => 'PAN',
//        'gstin' => 'GSTIN',
        'business_details' => 'Business Details',
        'address_1' => 'Address Line 1',
        'address_2' => 'Address Line 2',
        'address_3' => 'Address Line 3',
        'state' => 'State',
        'city' => 'City',
        'pin' => 'PIN',
        'website' => 'Website',
        'active' => 'Active',
        'contact_name' => 'Contact Name',
        'contact_department' => 'Contact Department',
        'contact_designation' => 'Contact Designation',
        'contact_email' => 'Contact Email',
        'contact_phone' => 'Contact Phone'
    ];
    
    $options = [];
    $options['filters'] = [];
    
    if (filter_has_var(INPUT_GET, 'searchdata') && $_GET['searchdata'] != '') {
        $searchdata = json_decode($_GET['searchdata'], true);
        if (is_array($searchdata) && !empty($searchdata)) {
            $options['filters'] = [];
            foreach ($searchdata as $filter) {
                $field = $filter['searchon'];
                $type = $filter['searchtype'] ?? '';
                $value = trim($filter['searchtext']);
                
                $options['filters'][] = [
                    'field' => $field,
                    'type' => $type,
                    'value' => $value
                ];
            }
        }
    }
    
    if (filter_has_var(INPUT_GET, 'sortdata') && $_GET['sortdata'] != '') {
        $options['order_by'] = [];
        $sortdata = json_decode($_GET['sortdata'], true);
        foreach ($sortdata as $sort_param) {
            $options['order_by'][] = [
                'field' => $sort_param['sorton'],
                'type' => $sort_param['sortorder']
            ];
        }
    }
    
    $records = \eBizIndia\Customer::getList($options);
    
    if ($records === false) {
        header('HTTP/1.0 500 Internal Server Error', true, 500);
        die;
    } elseif (empty($records)) {
        header('HTTP/1.0 204 No Content', true, 204);
        die;
    } else {
        // Get all customer IDs from the records
        $customerIds = array_column($records, 'id');
        
        // Fetch all contacts for these customers in a single query
        $allContacts = [];
        if (!empty($customerIds)) {
            try {
                $placeholders = [];
                $params = [];
                
                foreach ($customerIds as $idx => $id) {
                    $key = ":id_$idx";
                    $placeholders[] = $key;
                    $params[$key] = $id;
                }
                
                $sql = "SELECT * FROM `" . CONST_TBL_PREFIX . "customer_contacts` 
                        WHERE customer_id IN (" . implode(',', $placeholders) . ")
                        ORDER BY customer_id, name";
                
                $stmt_obj = \eBizIndia\PDOConn::query($sql, $params);
                $contactsData = $stmt_obj->fetchAll(\PDO::FETCH_ASSOC);
                
                // Group contacts by customer_id for easy access
                foreach ($contactsData as $contact) {
                    $allContacts[$contact['customer_id']][] = $contact;
                }
            } catch (\Exception $e) {
                \eBizIndia\ErrorHandler::logError(['function' => 'export_customer_contacts'], $e);
                // Continue with export even if contacts fetch fails
            }
        }
        
        ob_clean();
        header('Content-Description: File Transfer');
        header('Content-Type: application/csv');
        header("Content-Disposition: attachment; filename=customers.csv");
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        
        $fh = fopen('php://output', 'w');
        if (!$fh) {
            header('HTTP/1.0 500 Internal Server Error', true, 500);
            die;
        }
        
        $col_headers = array_values($customer_export_fields);
        $data_row_flds = array_fill_keys(array_keys($customer_export_fields), '');
        
        fputcsv($fh, $col_headers);
        
        foreach ($records as $rec) {
            $customerContacts = $allContacts[$rec['id']] ?? [];
            
            if (empty($customerContacts)) {
                // If no contacts, still output the customer row with empty contact fields
                $data_row = array_intersect_key(array_replace($data_row_flds, $rec), $data_row_flds);
                fputcsv($fh, array_values($data_row));
            } else {
                // Output one row per contact with repeated customer data
                foreach ($customerContacts as $contact) {
                    $contact_data = [
                        'contact_name' => $contact['name'],
                        'contact_department' => $contact['department'],
                        'contact_designation' => $contact['designation'],
                        'contact_email' => $contact['email'],
                        'contact_phone' => $contact['phone']
                    ];
                    
                    // Merge customer and contact data
                    $row_data = array_merge($rec, $contact_data);
                    $data_row = array_intersect_key(array_replace($data_row_flds, $row_data), $data_row_flds);
                    fputcsv($fh, array_values($data_row));
                }
            }
        }
        
        ob_flush();
        fclose($fh);
        die;
    }
} elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getList') {
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
                    case 'name': 
                        $fltr_text = 'Customer name '; 
                        break;
                    case 'customer_group_name': 
                        $fltr_text = 'Customer group '; 
                        break;
                    case 'industry_name': 
                        $fltr_text = 'Industry '; 
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
                    case 'AFTER':
                        $fltr_text .= 'after ';
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
        'filters' => [],
    ];
    $options['fieldstofetch'] = ['recordcount'];
    
    // Get total customer count
    $tot_rec_cnt = \eBizIndia\Customer::getList($tot_rec_options);
    $result[1]['tot_rec_cnt'] = $tot_rec_cnt[0]['recordcount'];
    
    // Get record count based on filters
    $recordcount = \eBizIndia\Customer::getList($options);
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
        }
        
        $records = \eBizIndia\Customer::getList($options);
        
        if ($records === false) {
            $error = 1; // db error
        } else {
            $result[1]['list'] = $records;
        }
    }
    
    $result[0] = $error;
    $result[1]['reccount'] = $recordcount;
    
    if ($_POST['listformat'] == 'html') {
        $get_list_template_data = [
            'mode' => $_POST['mode'],
            $_POST['mode'] => [
                'error' => $error,
                'records' => $records,
                'records_count' => count($records ?? []),
                'cu_id' => $loggedindata[0]['id'],
                'filtertext' => $result[1]['filtertext'],
                'filtercount' => count($filtertext),
                'tot_col_count' => count($records[0] ?? []) + 1 // +1 for action column
            ],
            'logged_in_user' => $loggedindata[0],
            'can_edit' => $can_edit,
            'can_delete' => $can_delete,
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
} elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'deleterec') {
    $result = ['error_code' => 0, 'message' => ''];
    if ($can_delete === false) {
        $result['error_code'] = 403;
        $result['message'] = "Sorry, you are not authorized to delete customers.";
    }else if ($_POST['recordid'] == '') {
        $result['error_code'] = 2;
        $result['message'] = "Invalid customer reference.";
    } else {
        try {
            $conn = \eBizIndia\PDOConn::getInstance();
            $conn->beginTransaction();
            
            // Then delete customer
            $sql = "DELETE FROM `" . CONST_TBL_PREFIX . "customers` WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':id' => $_POST['recordid']]);
            
            $conn->commit();
            $result['message'] = 'Customer deleted successfully.';
        } catch (\Exception $e) {
            if ($conn && $conn->inTransaction()) {
                $conn->rollBack();
            }
            $result['error_code'] = 1;
            $result['message'] = "The customer could not be deleted due to a server error.";
            \eBizIndia\ErrorHandler::logError(['function' => __METHOD__], $e);
        }
    }
    
    echo json_encode($result);
    exit;
}    

$dom_ready_data['customers'] = [
    'field_meta' => CONST_FIELD_META
];
$jscode .= "const CAN_ADD = ".var_export($can_add,true).";\n";
$jscode .= "const CAN_EDIT = ".var_export($can_edit, true).";\n";
$jscode .= "const CAN_DELETE = ".var_export($can_delete, true).";\n";
$additional_base_template_data = [
    'page_title' => $page_title,
    'page_description' => $page_description,
    'template_type' => $template_type,
    'dom_ready_code' => \scriptProviderFuncs\getDomReadyJsCode($page, $dom_ready_data),
    'other_js_code' => $jscode,
    'module_name' => $page
];

$additional_body_template_data = [
    'can_add' => $can_add, 
    'can_edit' => $can_edit,
    'customer_groups' => \eBizIndia\CustomerGroup::getList(),
    'industries' => \eBizIndia\Industry::getList()
];

$page_renderer->updateBodyTemplateData($additional_body_template_data);
$page_renderer->updateBaseTemplateData($additional_base_template_data);
$page_renderer->addCss(\scriptProviderFuncs\getCss($page));

$js_files = \scriptProviderFuncs\getJavascripts($page);
$page_renderer->addJavascript($js_files['BSH'], 'BEFORE_SLASH_HEAD');
$page_renderer->addJavascript($js_files['BSB'], 'BEFORE_SLASH_BODY');
$page_renderer->renderPage();