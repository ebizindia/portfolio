<?php
$page = 'data-upload';
require_once 'inc.php';

$template_type = '';
$page_title = 'Data Upload' . CONST_TITLE_AFX;
$page_description = 'Upload and import transaction data from Excel or CSV files';
$body_template_file = CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'data-upload.tpl';
$body_template_data = [];
$page_renderer->registerBodyTemplate($body_template_file, $body_template_data);

// Permission checks - Only admin can upload
$can_upload = false;
$_cu_role = $loggedindata[0]['profile_details']['assigned_roles'][0]['role'];
if (in_array('ALL', $allowed_menu_perms) || $_cu_role === 'Admin') {
    $can_upload = true;
}

// Define upload directory paths
$upload_base_dir = __DIR__ . '/uploads/portfolio-data/';
$upload_pending_dir = $upload_base_dir . 'pending/';
$upload_processed_dir = $upload_base_dir . 'processed/';
$upload_failed_dir = $upload_base_dir . 'failed/';

// FILE UPLOAD HANDLER
if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'uploadfile' && $_FILES) {
    $result = ['error_code' => 0, 'message' => '', 'upload_id' => null];

    if ($can_upload === false) {
        $result['error_code'] = 403;
        $result['message'] = "Sorry, you are not authorized to upload files.";
    } else {
        $upload_file = $_FILES['upload_file'] ?? null;

        if (!$upload_file || $upload_file['error'] !== UPLOAD_ERR_OK) {
            $result['error_code'] = 2;
            $result['message'] = "File upload failed. Please try again.";
        } else {
            $file_name = $upload_file['name'];
            $file_size = $upload_file['size'];
            $file_tmp = $upload_file['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Validate file extension
            if (!in_array($file_ext, ['xlsx', 'xls', 'csv'])) {
                $result['error_code'] = 2;
                $result['message'] = "Invalid file format. Only Excel (.xlsx, .xls) and CSV files are allowed.";
            }
            // Validate file size (max 10MB)
            elseif ($file_size > 10 * 1024 * 1024) {
                $result['error_code'] = 2;
                $result['message'] = "File size exceeds 10MB limit.";
            } else {
                // Create unique file name
                $unique_name = date('Ymd_His') . '_' . uniqid() . '.' . $file_ext;
                $target_path = $upload_pending_dir . $unique_name;

                if (move_uploaded_file($file_tmp, $target_path)) {
                    // Save file record
                    $file_upload = new \eBizIndia\FileUpload();
                    $upload_id = $file_upload->add([
                        'file_name' => $file_name,
                        'file_path' => $target_path,
                        'file_size' => $file_size,
                        'upload_date' => date('Y-m-d'),
                        'status' => 'Pending'
                    ]);

                    if ($upload_id) {
                        $result['message'] = 'File uploaded successfully.';
                        $result['upload_id'] = $upload_id;
                    } else {
                        $result['error_code'] = 1;
                        $result['message'] = 'File uploaded but failed to save record.';
                    }
                } else {
                    $result['error_code'] = 1;
                    $result['message'] = 'Failed to save uploaded file.';
                }
            }
        }
    }

    echo json_encode($result);
    exit;
}

// VALIDATE FILE HANDLER
elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'validatefile') {
    $result = ['error_code' => 0, 'message' => '', 'validation' => []];

    if ($can_upload === false) {
        $result['error_code'] = 403;
        $result['message'] = "Sorry, you are not authorized.";
    } else {
        $upload_id = (int)($_POST['upload_id'] ?? 0);

        if ($upload_id <= 0) {
            $result['error_code'] = 2;
            $result['message'] = "Invalid upload reference.";
        } else {
            $file_upload = new \eBizIndia\FileUpload($upload_id);
            $upload_details = $file_upload->getDetails();

            if (!$upload_details) {
                $result['error_code'] = 2;
                $result['message'] = "Upload record not found.";
            } else {
                $file_path = $upload_details['file_path'];
                $file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

                // Validate file
                $importer = new \eBizIndia\DataImporter($file_path, $file_ext);
                $validation = $importer->validate();

                if ($validation['valid']) {
                    // Update status
                    $file_upload->update([
                        'status' => 'Validated',
                        'records_count' => count($validation['data'])
                    ]);

                    $result['message'] = 'File validated successfully. ' .
                                       count($validation['data']) . ' records found.';
                } else {
                    // Update status with errors
                    $file_upload->update([
                        'status' => 'Failed',
                        'validation_errors' => json_encode($validation['errors'])
                    ]);

                    $result['error_code'] = 2;
                    $result['message'] = 'File validation failed.';
                }

                $result['validation'] = $validation;
            }
        }
    }

    echo json_encode($result);
    exit;
}

// IMPORT FILE HANDLER
elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'importfile') {
    $result = ['error_code' => 0, 'message' => '', 'import' => []];

    if ($can_upload === false) {
        $result['error_code'] = 403;
        $result['message'] = "Sorry, you are not authorized.";
    } else {
        $upload_id = (int)($_POST['upload_id'] ?? 0);
        $skip_duplicates = isset($_POST['skip_duplicates']) ? (bool)$_POST['skip_duplicates'] : true;

        if ($upload_id <= 0) {
            $result['error_code'] = 2;
            $result['message'] = "Invalid upload reference.";
        } else {
            $file_upload = new \eBizIndia\FileUpload($upload_id);
            $upload_details = $file_upload->getDetails();

            if (!$upload_details) {
                $result['error_code'] = 2;
                $result['message'] = "Upload record not found.";
            } elseif ($upload_details['status'] !== 'Validated') {
                $result['error_code'] = 2;
                $result['message'] = "File must be validated before import.";
            } else {
                $file_path = $upload_details['file_path'];
                $file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

                // Import file
                $importer = new \eBizIndia\DataImporter($file_path, $file_ext);
                $validation = $importer->validate();

                if (!$validation['valid']) {
                    $result['error_code'] = 2;
                    $result['message'] = 'File validation failed. Please re-upload.';
                    $result['import'] = ['errors' => $validation['errors']];
                } else {
                    $import = $importer->import($validation['data'], $skip_duplicates);

                    if ($import['success']) {
                        // Move file to processed folder
                        $new_path = str_replace('/pending/', '/processed/', $file_path);
                        rename($file_path, $new_path);

                        // Update status
                        $file_upload->update([
                            'status' => 'Imported',
                            'file_path' => $new_path,
                            'records_count' => $import['imported']
                        ]);

                        $result['message'] = "Import completed. {$import['imported']} records imported";
                        if ($import['skipped'] > 0) {
                            $result['message'] .= ", {$import['skipped']} duplicates skipped.";
                        }
                    } else {
                        // Move file to failed folder
                        $new_path = str_replace('/pending/', '/failed/', $file_path);
                        rename($file_path, $new_path);

                        $file_upload->update([
                            'status' => 'Failed',
                            'file_path' => $new_path,
                            'validation_errors' => json_encode($import['errors'])
                        ]);

                        $result['error_code'] = 1;
                        $result['message'] = 'Import failed.';
                    }

                    $result['import'] = $import;
                }
            }
        }
    }

    echo json_encode($result);
    exit;
}

// GET UPLOAD HISTORY HANDLER
elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'getUploadHistory') {
    $result = [0, []];
    $options = ['filters' => []];

    $pno = isset($_POST['pno']) && is_numeric($_POST['pno']) ? $_POST['pno'] : 1;
    $recsperpage = CONST_RECORDS_PER_PAGE;

    // Filter by status
    if (!empty($_POST['status'])) {
        $options['filters'][] = [
            'field' => 'status',
            'value' => $_POST['status']
        ];
    }

    // Filter by date range
    if (!empty($_POST['start_date']) || !empty($_POST['end_date'])) {
        $options['filters'][] = [
            'field' => 'date_range',
            'start_date' => $_POST['start_date'] ?? '',
            'end_date' => $_POST['end_date'] ?? ''
        ];
    }

    // Get total count
    $count_options = array_merge($options, ['fieldstofetch' => ['recordcount']]);
    $count_result = \eBizIndia\FileUpload::getList($count_options);
    $recordcount = $count_result[0]['recordcount'] ?? 0;

    // Get paginated records
    $options['page'] = $pno;
    $options['recs_per_page'] = $recsperpage;

    $records = \eBizIndia\FileUpload::getList($options);

    if ($records === false) {
        $result[0] = 1; // DB error
    } else {
        $result[1]['list'] = $records;
        $result[1]['reccount'] = $recordcount;
    }

    echo json_encode($result);
    exit;
}

// DELETE UPLOAD HANDLER
elseif (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'deleteupload') {
    $result = ['error_code' => 0, 'message' => ''];

    if ($can_upload === false) {
        $result['error_code'] = 403;
        $result['message'] = "Sorry, you are not authorized.";
    } elseif (empty($_POST['recordid'])) {
        $result['error_code'] = 2;
        $result['message'] = "Invalid upload reference.";
    } else {
        $upload_id = (int)$_POST['recordid'];
        $file_upload = new \eBizIndia\FileUpload($upload_id);
        $upload_details = $file_upload->getDetails();

        if ($upload_details && file_exists($upload_details['file_path'])) {
            // Delete physical file
            unlink($upload_details['file_path']);
        }

        if ($file_upload->delete()) {
            $result['message'] = 'Upload record deleted successfully.';
        } else {
            $result['error_code'] = 1;
            $result['message'] = 'Failed to delete upload record.';
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
    'can_upload' => $can_upload
]);

$page_renderer->renderPage();
