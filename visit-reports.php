<?php
$page = 'visit-reports';
require_once 'inc.php';

$template_type = '';
$page_title = 'Manage Customer Visit Reports' . CONST_TITLE_AFX;
$page_description = 'Manage customer visit reports and track sales activities.';
$body_template_file = CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'visit-reports.tpl';
$body_template_data = [];
$page_renderer->registerBodyTemplate($body_template_file, $body_template_data);

$can_add = true;
$can_edit_admin_notes = false;
$_cu_role = $loggedindata[0]['profile_details']['assigned_roles'][0]['role'];

if (strcasecmp($_cu_role, 'ADMIN') === 0) {
    $can_edit_admin_notes = true;
}

$rec_fields = [
    'customer_id' => '',
    'department' => '',
    'type' => '',
    'visit_date' => '',
    'meeting_title' => '',
    'detailed_notes' => ''
];

if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'createrec') {
    $result = ['error_code' => 0, 'message' => [], 'elemid' => [], 'other_data' => []];

    if ($can_add === false) {
        $result['error_code'] = 403;
        $result['message'] = "Sorry, you are not authorized to perform this action.";
    } else {
        // Get all fields except detailed_notes first
        $data = \eBizIndia\trim_deep(\eBizIndia\striptags_deep(array_intersect_key($_POST, array_diff_key($rec_fields, ['detailed_notes' => '']))));

        // Handle detailed_notes separately to preserve HTML
        if (isset($_POST['detailed_notes'])) {
            // Only trim whitespace, don't strip HTML tags for detailed notes
            $data['detailed_notes'] = trim($_POST['detailed_notes']);
        }

        if (empty($data['customer_id'])) {
            $result['error_code'] = 2;
            $result['message'][] = "Customer is required.";
            $result['error_fields'][] = "#add_customer_id";
        } elseif (empty($data['department'])) {
            $result['error_code'] = 2;
            $result['message'][] = "Department is required.";
            $result['error_fields'][] = "#add_department";
        } elseif (!in_array($data['department'], [1, 2, 3])) {
            $result['error_code'] = 2;
            $result['message'][] = "Please select a valid department.";
            $result['error_fields'][] = "#add_department";
        } elseif (empty($data['type'])) {
            $result['error_code'] = 2;
            $result['message'][] = "Type is required.";
            $result['error_fields'][] = "#add_type";
        } elseif (!in_array($data['type'], [1, 2])) {
            $result['error_code'] = 2;
            $result['message'][] = "Please select a valid type.";
            $result['error_fields'][] = "#add_type";
        } elseif (empty($data['visit_date'])) {
            $result['error_code'] = 2;
            $result['message'][] = "Visit date is required.";
            $result['error_fields'][] = "#add_visit_date";
        } elseif (!\eBizIndia\isDateValid($data['visit_date'])) {
            $result['error_code'] = 2;
            $result['message'][] = "Visit date is invalid.";
            $result['error_fields'][] = "#add_visit_date";
        } elseif (empty($data['meeting_title'])) {
            $result['error_code'] = 2;
            $result['message'][] = "Meeting title is required.";
            $result['error_fields'][] = "#add_meeting_title";
        } elseif (empty($data['detailed_notes']) || trim(strip_tags($data['detailed_notes'])) === '') {
            // Validate detailed notes - check for meaningful content
            $result['error_code'] = 2;
            $result['message'][] = "Detailed notes are required.";
            $result['error_fields'][] = "#add_detailed_notes";
        } else {
            // Validate people met - check if at least one contact is provided
            $has_contacts = false;
            if (isset($_POST['contact_name']) && is_array($_POST['contact_name'])) {
                foreach ($_POST['contact_name'] as $name) {
                    if (!empty(trim($name))) {
                        $has_contacts = true;
                        break;
                    }
                }
            }
            if (!$has_contacts) {
                $result['error_code'] = 2;
                $result['message'][] = "At least one person met is required.";
                $result['error_fields'][] = "#contacts-table";
            } else{

                try {
                    $conn = \eBizIndia\PDOConn::getInstance();
                    $conn->beginTransaction();

                    // Create visit report
                    $visit_report = new \eBizIndia\CustomerVisitReport();
                    $recordid = $visit_report->add($data);
                    if (!$recordid) {
                        throw new Exception('Error adding the visit report.');
                    }

                    // Handle file upload
                    if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
                        $visit_report = new \eBizIndia\CustomerVisitReport($recordid);
                        $upload_result = $visit_report->uploadAttachment($_FILES['attachment']);

                        if ($upload_result['success']) {
                            // Update record with file details
                            $file_data = [
                                'attachment_file_name' => $upload_result['file_name'],
                                'attachment_file_path' => $upload_result['file_path'],
                                'attachment_file_type' => $upload_result['file_type'],
                                'attachment_file_size' => $upload_result['file_size']
                            ];

                            $sql = "UPDATE `" . CONST_TBL_PREFIX . "customer_visit_reports` SET 
                                attachment_file_name = :file_name,
                                attachment_file_path = :file_path,
                                attachment_file_type = :file_type,
                                attachment_file_size = :file_size
                                WHERE id = :id";

                            $params = [
                                ':id' => $recordid,
                                ':file_name' => $file_data['attachment_file_name'],
                                ':file_path' => $file_data['attachment_file_path'],
                                ':file_type' => $file_data['attachment_file_type'],
                                ':file_size' => $file_data['attachment_file_size']
                            ];

                            \eBizIndia\PDOConn::query($sql, $params);
                        }
                    }

                    // Add contacts
                    $contacts = [];
                    if (isset($_POST['contact_name'])) {
                        foreach ($_POST['contact_name'] as $idx => $name) {
                            if (!empty($name)) {
                                $contacts[] = [
                                    'contact_id' => !empty($_POST['contact_id'][$idx]) ? $_POST['contact_id'][$idx] : null,
                                    'name' => $name,
                                    'department' => $_POST['contact_department'][$idx] ?? '',
                                    'designation' => $_POST['contact_designation'][$idx] ?? '',
                                    'email' => $_POST['contact_email'][$idx] ?? '',
                                    'phone' => $_POST['contact_phone'][$idx] ?? '',
                                    'is_new_contact' => empty($_POST['contact_id'][$idx]) ? 1 : 0
                                ];
                            }
                        }

                        $visit_report = new \eBizIndia\CustomerVisitReport($recordid);
                        if (!empty($contacts)) {
                            if (!$visit_report->addContacts($contacts)) {
                                throw new Exception("Error adding the contacts");
                            }
                        }
                    }

                    $conn->commit();

                    $result['error_code'] = 0;
                    $result['message'] = 'The visit report has been saved.';
                } catch (\Exception $e) {
                    $last_error = \eBizIndia\PDOConn::getLastError();
                    $result['error_code'] = 1;

                    if ($result['message'] == '') {
                        $result['message'] = "The visit report could not be added due to server error.";
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

    $_SESSION['create_rec_result'] = $result;
    header("Location:?");
    exit;
} elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'updateAdminNotes') {
    $result = ['error_code' => 0, 'message' => []];

    if ($can_edit_admin_notes === false) {
        $result['error_code'] = 403;
        $result['message'] = "Sorry, you are not authorized to update admin notes.";
    } else {
        $recordid = (int)$_POST['recordid'];
        // Don't trim HTML content for admin notes - preserve HTML formatting
        $admin_notes = $_POST['admin_notes'] ?? '';

        if ($recordid == '') {
            $result['error_code'] = 2;
            $result['message'][] = "Invalid visit report reference.";
        } else {
            try {
                $visit_report = new \eBizIndia\CustomerVisitReport($recordid);

                if ($visit_report->updateAdminNotes($admin_notes)) {
                    $result['error_code'] = 0;
                    $result['message'] = 'Admin notes have been updated.';

                    // Return the updated data with the response
                    $result['updated_data'] = [
                        'admin_notes' => $admin_notes,
                        'admin_notes_updated_by_name' => $loggedindata[0]['profile_details']['name'],
                        'admin_notes_updated_on_disp' => date('d-m-Y, g:i a')
                    ];
                } else {
                    $result['error_code'] = 1;
                    $result['message'] = 'No changes were made to admin notes.';
                }
            } catch (\Exception $e) {
                $result['error_code'] = 1;
                $result['message'] = "The admin notes could not be updated due to server error.";
                \eBizIndia\ErrorHandler::logError(['function' => __METHOD__], $e);
            }
        }
    }

    $_SESSION['update_admin_notes_result'] = $result;
    header("Location:?");
    exit;
} elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'deleteRecord') {
    $result = ['error_code' => 0, 'message' => ''];

    if (strcasecmp($_cu_role, 'ADMIN') !== 0) {
        $result['error_code'] = 403;
        $result['message'] = "Sorry, you are not authorized to delete records.";
    } else {
        $recordid = (int)$_POST['recordid'];

        if ($recordid <= 0) {
            $result['error_code'] = 2;
            $result['message'] = "Invalid record ID.";
        } else {
            try {
                $visit_report = new \eBizIndia\CustomerVisitReport($recordid);

                // Check if record exists and user has access
                if (!$visit_report->canUserAccess($loggedindata[0]['id'], $_cu_role)) {
                    $result['error_code'] = 403;
                    $result['message'] = "Record not found or access denied.";
                } else {
                    if ($visit_report->delete()) {
                        $result['error_code'] = 0;
                        $result['message'] = 'Visit report has been successfully deleted.';
                    } else {
                        $result['error_code'] = 1;
                        $result['message'] = 'Visit report could not be deleted due to server error.';
                    }
                }
            } catch (\Exception $e) {
                $result['error_code'] = 1;
                $result['message'] = "Visit report could not be deleted due to server error.";
                \eBizIndia\ErrorHandler::logError(['function' => __METHOD__], $e);
            }
        }
    }

    echo json_encode($result);
    exit;
} elseif (isset($_SESSION['update_admin_notes_result']) && is_array($_SESSION['update_admin_notes_result'])) {
    header("Content-Type: text/html; charset=UTF-8");
    echo "<script type='text/javascript' >\n";
    echo "parent.visitReports.handleUpdateAdminNotesResponse(".json_encode($_SESSION['update_admin_notes_result']).");\n";
    echo "</script>";
    unset($_SESSION['update_admin_notes_result']);
    exit;
} elseif (isset($_SESSION['create_rec_result']) && is_array($_SESSION['create_rec_result'])) {
    header("Content-Type: text/html; charset=UTF-8");
    echo "<script type='text/javascript' >\n";
    echo "parent.visitReports.handleAddRecResponse(".json_encode($_SESSION['create_rec_result']).");\n";
    echo "</script>";
    unset($_SESSION['create_rec_result']);
    exit;
} elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getRecordDetails') {
    $result = [];
    $error = 0;

    if ($_POST['recordid'] == '') {
        $error = 1; // Record ID missing
    } else {
        $options = [
            'filters' => [
                ['field' => 'id', 'type' => '=', 'value' => $_POST['recordid']],
            ]
        ];

        // Apply role-based access control
        if (strcasecmp($_cu_role, 'ADMIN') !== 0) {
            if(!empty($loggedindata[0]['profile_details']['user_group_id']))
                $options['filters'][] = ['field' => 'user_group_id', 'type' => '=', 'value' => $loggedindata[0]['profile_details']['user_group_id']];
            else
                $options['filters'][] = ['field' => 'created_by', 'type' => '=', 'value' => $loggedindata[0]['id']];
        }

        $recorddetails = \eBizIndia\CustomerVisitReport::getList($options);

        if ($recorddetails === false) {
            $error = 2; // db error
        } elseif (count($recorddetails) == 0) {
            $error = 3; // Rec ID does not exist or access denied
        } else {
            $recorddetails = $recorddetails[0];
            $recorddetails['visit_date_disp'] = date('d-m-Y', strtotime($recorddetails['visit_date']));
            $recorddetails['created_on_disp'] = date('d-m-Y, g:i a', strtotime($recorddetails['created_on']));
            $recorddetails['admin_notes_updated_on_disp'] = date('d-m-Y, g:i a', strtotime($recorddetails['admin_notes_updated_on']));

            // Fetch contacts for this visit report
            $visit_report = new \eBizIndia\CustomerVisitReport($_POST['recordid']);
            $contacts = $visit_report->getContacts();
            $recorddetails['contacts'] = $contacts ?: [];
        }
    }

    $result[0] = $error;
    $result[1]['can_edit_admin_notes'] = $can_edit_admin_notes;
    $result[1]['cuid'] = $loggedindata[0]['id'];
    $result[1]['record_details'] = $recorddetails;

    echo json_encode($result);
    exit;
} elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getCustomerContacts') {
    $result = ['error_code' => 0, 'contacts' => []];

    if (empty($_POST['customer_id'])) {
        $result['error_code'] = 1;
        $result['message'] = 'Customer ID is required.';
    } else {
        try {
            $customer = new \eBizIndia\Customer($_POST['customer_id']);
            $contacts = $customer->getContacts();
            $result['contacts'] = $contacts ?: [];
        } catch (\Exception $e) {
            $result['error_code'] = 1;
            $result['message'] = 'Error fetching customer contacts.';
            \eBizIndia\ErrorHandler::logError(['function' => __METHOD__], $e);
        }
    }

    echo json_encode($result);
    exit;
} elseif (filter_has_var(INPUT_GET, 'mode') && $_GET['mode'] === 'export') {
    $visit_report_export_fields = [
        'visit_date' => 'Visit Date',
        'customer_group_name' => 'Group',
        'customer_name' => 'Customer',
        'department' => 'Department',
        'type' => 'Type',
        'meeting_title' => 'Meeting Title',
        'detailed_notes' => 'Detailed Notes',
        'created_by_name' => 'Salesperson',
        'created_on' => 'Created On',
        'admin_notes' => 'Admin Notes',
        'contact_names' => 'People Met'
    ];

    $options = [];
    $options['filters'] = [];

    // Apply role-based access control
    if (strcasecmp($_cu_role, 'ADMIN') !== 0) {
        if(!empty($loggedindata[0]['profile_details']['user_group_id']))
            $options['filters'][] = ['field' => 'user_group_id', 'type' => '=', 'value' => $loggedindata[0]['profile_details']['user_group_id']];
        else
            $options['filters'][] = ['field' => 'created_by', 'type' => '=', 'value' => $loggedindata[0]['id']];
    }

    if (filter_has_var(INPUT_GET, 'searchdata') && $_GET['searchdata'] != '') {
        $searchdata = json_decode($_GET['searchdata'], true);
        if (is_array($searchdata) && !empty($searchdata)) {
            foreach ($searchdata as $filter) {
                $field = $filter['searchon'];
                $type = $filter['searchtype'] ?? '';
                $value = trim($filter['searchtext']);

                if ($field === 'visit_date_range' && !empty($filter['start_date']) && !empty($filter['end_date'])) {
                    $options['filters'][] = [
                        'field' => 'visit_date',
                        'type' => 'BETWEEN',
                        'value' => [$filter['start_date'], $filter['end_date']]
                    ];
                } else {
                    $options['filters'][] = [
                        'field' => $field,
                        'type' => $type,
                        'value' => $value
                    ];
                }
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

    $records = \eBizIndia\CustomerVisitReport::getList($options);

    if ($records === false) {
        header('HTTP/1.0 500 Internal Server Error', true, 500);
        die;
    } elseif (empty($records)) {
        header('HTTP/1.0 204 No Content', true, 204);
        die;
    } else {
        // Get all visit report IDs from the records
        $visitReportIds = array_column($records, 'id');

        // Fetch all contacts for these visit reports in a single query
        $allContacts = [];
        if (!empty($visitReportIds)) {
            try {
                $placeholders = [];
                $params = [];

                foreach ($visitReportIds as $idx => $id) {
                    $key = ":id_$idx";
                    $placeholders[] = $key;
                    $params[$key] = $id;
                }

                $sql = "SELECT visit_report_id, name FROM `" . CONST_TBL_PREFIX . "visit_report_contacts` 
                        WHERE visit_report_id IN (" . implode(',', $placeholders) . ")
                        ORDER BY visit_report_id, name";

                $stmt_obj = \eBizIndia\PDOConn::query($sql, $params);
                $contactsData = $stmt_obj->fetchAll(\PDO::FETCH_ASSOC);

                // Group contacts by visit_report_id for easy access
                foreach ($contactsData as $contact) {
                    $allContacts[$contact['visit_report_id']][] = $contact['name'];
                }
            } catch (\Exception $e) {
                \eBizIndia\ErrorHandler::logError(['function' => 'export_visit_report_contacts'], $e);
            }
        }

        ob_clean();
        header('Content-Description: File Transfer');
        header('Content-Type: application/csv');
        header("Content-Disposition: attachment; filename=visit-reports.csv");
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

        $fh = fopen('php://output', 'w');
        if (!$fh) {
            header('HTTP/1.0 500 Internal Server Error', true, 500);
            die;
        }

        $col_headers = array_values($visit_report_export_fields);
        fputcsv($fh, $col_headers);

        foreach ($records as $rec) {
            $visitContacts = $allContacts[$rec['id']] ?? [];
            $rec['contact_names'] = implode(', ', $visitContacts);

            // Convert department enum to text
            switch($rec['department']) {
                case 1: $rec['department'] = 'Supply Chain'; break;
                case 2: $rec['department'] = 'R & D'; break;
                case 3: $rec['department'] = 'Others'; break;
                default: $rec['department'] = '';
            }
            
            // Convert type enum to text
            switch($rec['type']) {
                case 1: $rec['type'] = 'New'; break;
                case 2: $rec['type'] = 'Existing'; break;
                default: $rec['type'] = '';
            }

            $data_row = [];
            foreach (array_keys($visit_report_export_fields) as $field) {
                $data_row[] = $rec[$field] ?? '';
            }

            fputcsv($fh, $data_row);
        }

        ob_flush();
        fclose($fh);
        die;
    }
} elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getList') {
    $result = [0, []];
    $options = [];
    $options['filters'] = [];

    // Apply role-based access control
    if (strcasecmp($_cu_role, 'ADMIN') !== 0) {
        if(!empty($loggedindata[0]['profile_details']['user_group_id']))
            $options['filters'][] = ['field' => 'user_group_id', 'type' => '=', 'value' => $loggedindata[0]['profile_details']['user_group_id']];
        else
            $options['filters'][] = ['field' => 'created_by', 'type' => '=', 'value' => $loggedindata[0]['id']];
    }

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
            foreach ($searchdata as $filter) {
                $field = $filter['searchon'];
                $type = $filter['searchtype'] ?? '';
                $value = \eBizIndia\trim_deep($filter['searchtext']);
                $disp_text = !empty($filter['disptext'])?$filter['disptext']:$value;

                if ($field === 'visit_date_range' && !empty($filter['start_date']) && !empty($filter['end_date'])) {
                    $options['filters'][] = [
                        'field' => 'visit_date',
                        'type' => 'BETWEEN',
                        'value' => [$filter['start_date'], $filter['end_date']]
                    ];

                    $filtertext[] = '<span class="searched_elem">Visit date between ' .
                        '<b>' . \eBizIndia\_esc(date('d-m-Y',strtotime($filter['start_date'])), true) . '</b> and <b>' . \eBizIndia\_esc(date('d-m-Y',strtotime($filter['end_date'])), true) . '</b>' .
                        '<span class="remove_filter" data-fld="visit_date_range">X</span></span>';
                } else {
                    $options['filters'][] = [
                        'field' => $field,
                        'type' => $type,
                        'value' => $value
                    ];

                    switch (strtolower($field)) {
                        case 'customer_group_id':
                            $fltr_text = 'Customer Group ';
                            break;
                        case 'customer_id':
                            $fltr_text = 'Customer ';
                            break;
                        case 'department':
                            $fltr_text = 'Department ';
                            break;
                        case 'type':
                            $fltr_text = 'Type ';
                            break;
                        case 'meeting_title':
                            $fltr_text = 'Meeting title ';
                            break;
                        case 'created_by':
                            $fltr_text = 'Salesperson ';
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
                        \eBizIndia\_esc($fltr_text, true) . ' <b>' . \eBizIndia\_esc($disp_text, true) . '</b>' .
                        '<span class="remove_filter" data-fld="' . $field . '">X</span></span>';
                }
            }

            $result[1]['filtertext'] = implode($filtertext);
        }
    }

    $tot_rec_options = [
        'fieldstofetch' => ['recordcount'],
        'filters' => $options['filters'],
    ];
    $options['fieldstofetch'] = ['recordcount'];

    // Get record count based on filters
    $recordcount = \eBizIndia\CustomerVisitReport::getList($options);
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
        $options['fieldstofetch'] = ['id', 'customer_id', 'customer_name', 'customer_group_name', 'department', 'type', 'visit_date', 'meeting_title', 'detailed_notes', 'created_by_name', 'attachment_file_path', 'attachment_file_name', 'created_on'];
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

        $records = \eBizIndia\CustomerVisitReport::getList($options);

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
                'user_role' => $_cu_role,
                'cu_id' => $loggedindata[0]['id'],
                'filtertext' => $result[1]['filtertext'],
                'filtercount' => count($filtertext),
                'tot_col_count' => count($records[0] ?? []) + 1 // +1 for action column
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

$dom_ready_data[$page] = [
    'field_meta' => CONST_FIELD_META,
    'init_ckeditor' => true,
    'visit_report_attach_url' => CONST_UPLOAD_DIR_URL.CONST_VISIT_REPORT_DIR.'/',
];

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
    'can_edit_admin_notes' => $can_edit_admin_notes,
    'user_role' => $_cu_role,
    'departments' => \eBizIndia\enums\Department::getOptions(),
    'visit_types' => \eBizIndia\enums\VisitType::getOptions(),
    'customer_groups' => \eBizIndia\CustomerGroup::getList([  // ADD THIS
//        'filters' => [
//            ['field' => 'active', 'type' => '=', 'value' => 'y']
//        ],
        'order_by' => [
            ['field' => 'name', 'type' => 'ASC']
        ]
    ]),
    'customers' => \eBizIndia\Customer::getList([
        'filters' => [
            ['field' => 'active', 'type' => 'yes', 'value' => '']
        ],
        'order_by' => [
            ['field' => 'name', 'type' => 'ASC']
        ]
    ]),
    'salespersons' => \eBizIndia\Member::getList([
        'filters' => [
            ['field' => 'active', 'type' => '=', 'value' => 'y']
        ],
        'order_by' => [
            ['field' => 'name', 'type' => 'ASC']
        ]
    ])
];

$page_renderer->updateBodyTemplateData($additional_body_template_data);
$page_renderer->updateBaseTemplateData($additional_base_template_data);
$page_renderer->addCss(\scriptProviderFuncs\getCss($page));

$js_files = \scriptProviderFuncs\getJavascripts($page);
$page_renderer->addJavascript($js_files['BSH'], 'BEFORE_SLASH_HEAD');
$page_renderer->addJavascript($js_files['BSB'], 'BEFORE_SLASH_BODY');
$page_renderer->renderPage();