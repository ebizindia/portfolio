<?php
namespace eBizIndia;
require_once CONST_CLASS_DIR.'phpspreadsheet/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
class ItemsStock {

    // CSV column headers mapping - easily configurable
    // Each key maps to an array of acceptable header names
    const CSV_HEADERS = [
        'item_name' => ['Item Name', 'Item', 'Name', 'Product', 'Product Name'],
        'quantity' => ['Quantity', 'Qty', 'Stock'],
        'unit' => ['Unit', 'UOM'],
        'expiry' => ['Expiry', 'Expiry Date', 'Expire', 'Best Before'],
        'as_on_date' => ['As On Date', 'As On', 'Date', 'Stock Date','As of Date', 'Report Date']
    ];


    private $stock_id;

    public function __construct(?int $stock_id = null) {
        $this->stock_id = $stock_id;
    }

    /**
     * Get primary header names for CSV template
     */
    public static function getPrimaryHeaders() {
        $primary_headers = [];
        foreach (self::CSV_HEADERS as $field => $headers) {
            $primary_headers[$field] = $headers[0]; // First header is primary
        }
        return $primary_headers;
    }


    /**
     * Get CSV template data for download
     */
    public static function getCSVTemplate() {
        return [
            'headers' => array_values(self::getPrimaryHeaders()),
            'sample_data' => [
                ['Titanium Dioxide Rutile Grade', '25.500', 'MT', 'Mar-26', '2025-05-27'],
                ['Basic Blue Dyestuff', '15.750', 'KG', 'Feb-26', '2025-05-27'],
                ['Polyethylene Glycol 400', '50.000', 'KG', 'Apr-26', '2025-05-27'],
                ['Iron Ore Fines 64% Fe', '500.000', 'MT', 'Dec-25', '2025-05-27'],
                ['Epoxy Resin Liquid', '75.500', 'KG', 'Nov-25', '2025-05-27']
            ]
        ];
    }

    /**
     * Get list of stock records with filtering and pagination
     */
    public static function getList($options = []) {
        $data = [];
        $fields_mapper = [];

        // Define field mappings
        $fields_mapper['*'] = "s.id, s.warehouse_id, s.item_name, s.quantity, s.unit, 
                              s.expiry_month, s.expiry_year, s.as_on_date, s.imported_on,
                              w.name AS warehouse_name,
                              CASE 
                                  WHEN s.expiry_month IS NULL OR s.expiry_year IS NULL THEN 'N/A'
                                  ELSE CONCAT(CASE s.expiry_month 
                                      WHEN 1 THEN 'Jan' WHEN 2 THEN 'Feb' WHEN 3 THEN 'Mar'
                                      WHEN 4 THEN 'Apr' WHEN 5 THEN 'May' WHEN 6 THEN 'Jun'
                                      WHEN 7 THEN 'Jul' WHEN 8 THEN 'Aug' WHEN 9 THEN 'Sep'
                                      WHEN 10 THEN 'Oct' WHEN 11 THEN 'Nov' WHEN 12 THEN 'Dec'
                                      ELSE '' END, '-', 
                                      RIGHT(s.expiry_year, 2))
                              END AS expiry_display";

        $fields_mapper['recordcount'] = 'count(distinct s.id)';
        $fields_mapper['id'] = 's.id';
        $fields_mapper['warehouse_id'] = 's.warehouse_id';
        $fields_mapper['warehouse_name'] = 'w.name';
        $fields_mapper['item_name'] = 's.item_name';
        $fields_mapper['quantity'] = 's.quantity';
        $fields_mapper['unit'] = 's.unit';
        $fields_mapper['expiry_month'] = 's.expiry_month';
        $fields_mapper['expiry_year'] = 's.expiry_year';
        $fields_mapper['as_on_date'] = 's.as_on_date';
        $fields_mapper['imported_on'] = 's.imported_on';

        // Build where clause
        $where_clause = [];
        $str_params_to_bind = [];
        $int_params_to_bind = [];

        if (array_key_exists('filters', $options) && is_array($options['filters'])) {
            $field_counter = 0;
            foreach ($options['filters'] as $filter) {
                ++$field_counter;
                switch ($filter['field']) {
                    // Integer fields
                    case 'warehouse_id':
                        $fld = $fields_mapper[$filter['field']];
                        switch ($filter['type']) {
                            case 'IN':
                                if (is_array($filter['value'])) {
                                    $place_holders = [];
                                    $k = 0;
                                    foreach ($filter['value'] as $val) {
                                        $k++;
                                        $ph = ":whr{$field_counter}_{$k}_";
                                        $place_holders[] = $ph;
                                        $int_params_to_bind[$ph] = $val;
                                    }
                                    $where_clause[] = $fld . ' in(' . implode(',', $place_holders) . ') ';
                                }
                                break;
                            default:
                                if($filter['type']=='EQUAL'){
                                    $filter['type'] = '=';
                                }
                                $val = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $ph = ":whr{$field_counter}_";
                                $where_clause[] = $fld . ' ' . $filter['type'] . ' ' . $ph;
                                $int_params_to_bind[$ph] = $val;
                        }
                        break;

                    // String fields
                    case 'item_name':
                    case 'warehouse_name':
                        $fld = $fields_mapper[$filter['field']];
                        switch ($filter['type']) {
                            case 'CONTAINS':
                                $v = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $where_clause[] = $fld . ' like :whr' . $field_counter . '_';
                                $str_params_to_bind[':whr' . $field_counter . '_'] = '%' . $v . '%';
                                break;
                            case 'STARTS_WITH':
                                $v = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $where_clause[] = $fld . ' like :whr' . $field_counter . '_';
                                $str_params_to_bind[':whr' . $field_counter . '_'] = $v . '%';
                                break;
                            default:
                                $v = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $where_clause[] = $fld . '=:whr' . $field_counter . '_';
                                $str_params_to_bind[':whr' . $field_counter . '_'] = $v;
                        }
                        break;

                    // Date fields
                    case 'as_on_date':
                        $fld = $fields_mapper[$filter['field']];
                        $v = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                        switch ($filter['type']) {
                            case '>=':
                            case '<=':
                            case '=':
                                $where_clause[] = $fld . ' ' . $filter['type'] . ' :whr' . $field_counter . '_';
                                $str_params_to_bind[':whr' . $field_counter . '_'] = $v;
                                break;
                        }
                        break;
                }
            }
        }

        // Build select
        $select_string = $fields_mapper['*'];
        $record_count = false;

        if (array_key_exists('fieldstofetch', $options) && is_array($options['fieldstofetch'])) {
            $fields_to_fetch_count = count($options['fieldstofetch']);
            if ($fields_to_fetch_count > 0) {
                $selected_fields = [];
                if (in_array('recordcount', $options['fieldstofetch'])) {
                    $record_count = true;
                    $selected_fields[] = $fields_mapper['recordcount'] . ' as recordcount';
                } else {
                    if (!in_array('*', $options['fieldstofetch'])) {
                        if (!in_array('id', $options['fieldstofetch'])) {
                            $options['fieldstofetch'][] = 'id';
                            $fields_to_fetch_count += 1;
                        }
                    }

                    for ($i = 0; $i < $fields_to_fetch_count; $i++) {
                        if (array_key_exists($options['fieldstofetch'][$i], $fields_mapper)) {
                            $selected_fields[] = $fields_mapper[$options['fieldstofetch'][$i]] .
                                (($options['fieldstofetch'][$i] != '*') ? ' as ' . $options['fieldstofetch'][$i] : '');
                        }
                    }
                }

                if (count($selected_fields) > 0) {
                    $select_string = implode(', ', $selected_fields);
                }
            }
        }

        // Order by - default to warehouse_name, item_name ASC
        $order_by_clause = ' ORDER BY w.name ASC, s.item_name ASC';

        if (array_key_exists('order_by', $options) && is_array($options['order_by'])) {
            $order_by_parts = [];
            foreach ($options['order_by'] as $order) {
                if (array_key_exists($order['field'], $fields_mapper)) {
                    $order_by_parts[] = $fields_mapper[$order['field']] .
                        (isset($order['type']) && $order['type'] == 'DESC' ? ' DESC' : ' ASC');
                }
            }

            if (!empty($order_by_parts)) {
                $order_by_clause = ' ORDER BY ' . implode(', ', $order_by_parts);
            }
        }

        // Pagination
        $limit_clause = '';
        if (array_key_exists('page', $options) &&
            filter_var($options['page'], FILTER_VALIDATE_INT) && $options['page'] > 0 &&
            array_key_exists('recs_per_page', $options) &&
            filter_var($options['recs_per_page'], FILTER_VALIDATE_INT) && $options['recs_per_page'] > 0) {
            $limit_clause = "LIMIT " . (($options['page'] - 1) * $options['recs_per_page']) . ", " . $options['recs_per_page'];
        }

        // Finalize where clause
        $where_clause_string = '';
        if (!empty($where_clause)) {
            $where_clause_string = ' WHERE ' . implode(' AND ', $where_clause);
        }

        // Complete SQL
        $sql = "SELECT " . ($record_count ? '' : 'DISTINCT ') . "$select_string 
                FROM `" . CONST_TBL_PREFIX . "items_stock` s
                INNER JOIN `" . CONST_TBL_PREFIX . "warehouses` w ON s.warehouse_id = w.id
                $where_clause_string 
                $order_by_clause 
                $limit_clause";

        try {
            $pdo_stmt_obj = PDOConn::query($sql, $str_params_to_bind, $int_params_to_bind);

            $data = [];
            while ($row = $pdo_stmt_obj->fetch(\PDO::FETCH_ASSOC)) {
                $data[] = $row;
            }

            return $data;
        } catch (\Exception $e) {
            ErrorHandler::logError(
                [
                    'function' => __METHOD__,
                    'sql' => $sql,
                    'str_params' => $str_params_to_bind,
                    'int_params' => $int_params_to_bind
                ],
                $e
            );
            return false;
        }
    }


    private static function detectFileType($file_path, $file_name = '') {
        $mime_type = mime_content_type($file_path);
        $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $excel_types = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-excel' => 'xls',
            'application/excel' => 'xls'
        ];

        $csv_types = [
            'text/csv', 'text/plain', 'application/csv'
        ];

        if (in_array($mime_type, array_keys($excel_types)) || in_array($extension, ['xlsx', 'xls'])) {
            return 'excel';
        } elseif (in_array($mime_type, $csv_types) || $extension === 'csv') {
            return 'csv';
        }

        return false;
    }

    /**
     * Parse Excel file and return data array
     */
    private static function parseExcelFile($file_path) {
        try {
            // Set higher memory limit and time limit
            $original_memory_limit = ini_get('memory_limit');
            $original_time_limit = ini_get('max_execution_time');

            ini_set('memory_limit', '1024M'); // Increased to 1GB
            ini_set('max_execution_time', 600); // 10 minutes

            // Configure reader for memory efficiency
            $reader = IOFactory::createReaderForFile($file_path);

            // Set read filter to optimize memory usage
//            $reader->setReadDataOnly(true); turned off to allow reading the cell's data type too as we have a datetime check below for the expiry and As on date columns
            $reader->setReadEmptyCells(false);

            // Load only the first worksheet
            $reader->setLoadSheetsOnly($reader->listWorksheetNames($file_path)[0]);

            // Load the Excel file
            $spreadsheet = $reader->load($file_path);
            $worksheet = $spreadsheet->getActiveSheet();

            // Get basic info
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            if ($highestRow < 2) {
                throw new \Exception('Excel file must contain at least headers and one data row');
            }

            // Check file size estimate
            if ($highestRow > 10000) {
                throw new \Exception('Excel file is too large.');
            }

            // Read headers from first row only
            $headers = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '1';
                $cellValue = $worksheet->getCell($cellCoordinate)->getValue();
                $headers[] = trim($cellValue ?? '');
            }

            // Remove empty headers from the end
            while (end($headers) === '') {
                array_pop($headers);
            }

            if (empty($headers)) {
                throw new \Exception('No headers found in Excel file');
            }

            // Validate headers
            $header_mapping = self::validateAndMapHeaders($headers);
            if ($header_mapping === false) {
                throw new \Exception('Invalid or missing headers in Excel file. Please ensure your Excel contains the required columns with acceptable header names.');
            }

            // Process data rows one by one (memory efficient)
            $csv_data = [];
            $processed_rows = 0;
            $max_rows_to_process = 5000; // Limit processing to prevent memory issues

            for ($row = 2; $row <= $highestRow && $processed_rows < $max_rows_to_process; $row++) {
                $row_data = [];
                $has_data = false;

                // Read each cell in the current row
                for ($col = 1; $col <= count($headers); $col++) {
                    $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row;
                    $cell = $worksheet->getCell($cellCoordinate);
                    $cellValue = $cell->getValue();

                    // Handle Excel date formatting
                    if (Date::isDateTime($cell) && is_numeric($cellValue)) {
                        try {
                            if($col===($header_mapping['expiry']+1))
                                $cellValue = Date::excelToDateTimeObject($cellValue)->format('M-y');
                            else if($col===($header_mapping['as_on_date']+1))
                                $cellValue = Date::excelToDateTimeObject($cellValue)->format('Y-m-d');
                        } catch (\Exception $e) {
                            // Keep original value if conversion fails
                        }
                    }

                    $cellValue = trim($cellValue ?? '');
                    $row_data[] = $cellValue;

                    if (!empty($cellValue)) {
                        $has_data = true;
                    }
                }

                // Skip empty rows
                if (!$has_data) {
                    continue;
                }

                // Map data to expected format
                $mapped_row = [
                    'item_name' => $row_data[$header_mapping['item_name']] ?? '',
                    'quantity' => $row_data[$header_mapping['quantity']] ?? '',
                    'unit' => $row_data[$header_mapping['unit']] ?? '',
                    'expiry' => $row_data[$header_mapping['expiry']] ?? '',
                    'as_on_date' => $row_data[$header_mapping['as_on_date']] ?? ''
                ];

                // Validate row data
                self::validateImportRow($mapped_row, $row);

                $csv_data[] = $mapped_row;
                $processed_rows++;

                // Garbage collection every 100 rows
                if ($processed_rows % 100 === 0) {
                    gc_collect_cycles();
                }
            }

            if ($processed_rows >= $max_rows_to_process && $row <= $highestRow) {
                throw new \Exception("File too large. Only first {$max_rows_to_process} rows were processed. Please split your data into smaller files.");
            }

            // Clean up
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $worksheet, $reader);
            gc_collect_cycles();

            // Restore original limits
            ini_set('memory_limit', $original_memory_limit);
            ini_set('max_execution_time', $original_time_limit);

            return $csv_data;

        } catch (\Exception $e) {
            // Clean up on error
            if (isset($spreadsheet)) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            }
            if (isset($worksheet)) {
                unset($worksheet);
            }
            if (isset($reader)) {
                unset($reader);
            }

            // Force garbage collection
            gc_collect_cycles();

            // Restore original limits
            if (isset($original_memory_limit)) {
                ini_set('memory_limit', $original_memory_limit);
            }
            if (isset($original_time_limit)) {
                ini_set('max_execution_time', $original_time_limit);
            }

            // Provide user-friendly error messages
            $user_message = self::getExcelErrorMessage($e);
            throw new \Exception($user_message);
        }
    }


    /**
     * Parse CSV file (extracted from existing importFromCSV method)
     */
    private static function parseCSVFile($file_path) {
        $csv_data = [];

        if (($handle = fopen($file_path, "r")) !== FALSE) {
            $headers = fgetcsv($handle, 1000, ",");

            // Validate and map headers
            $headers_trimmed = array_map('trim', $headers);
            $header_mapping = self::validateAndMapHeaders($headers_trimmed);

            if ($header_mapping === false) {
                fclose($handle);
                throw new \Exception('Invalid or missing CSV headers. Please ensure your CSV contains the required columns with acceptable header names.');
            }

            // Read data rows
            $row_number = 1;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row_number++;

                if (count($data) < count($header_mapping)) {
                    fclose($handle);
                    throw new \Exception("Row $row_number has insufficient columns");
                }

                $row_data = [
                    'item_name' => trim($data[$header_mapping['item_name']]),
                    'quantity' => trim($data[$header_mapping['quantity']]),
                    'unit' => trim($data[$header_mapping['unit']]),
                    'expiry' => trim($data[$header_mapping['expiry']]),
                    'as_on_date' => trim($data[$header_mapping['as_on_date']])
                ];

                // Validate row data
                self::validateImportRow($row_data, $row_number);

                $csv_data[] = $row_data;
            }
            fclose($handle);
        } else {
            throw new \Exception('Unable to read CSV file');
        }

        return $csv_data;
    }

    /**
     * Process import data (extracted from existing importFromCSV method)
     */
    private static function processImportData($csv_data, $warehouse_id, $user_id) {
        if (empty($csv_data)) {
            throw new \Exception('No data found in file');
        }

        // Begin transaction
        $conn = PDOConn::getInstance();
        $conn->beginTransaction();

        try {
            // Delete existing stock data for this warehouse
            $delete_sql = "DELETE FROM `" . CONST_TBL_PREFIX . "items_stock` WHERE warehouse_id = :warehouse_id";
            PDOConn::query($delete_sql, [':warehouse_id' => $warehouse_id]);

            // Process and insert data
            $imported_count = 0;
            $current_datetime = date('Y-m-d H:i:s');
            $current_ip = \eBizIndia\getRemoteIP();

            foreach ($csv_data as $row) {
                // Parse expiry
                $expiry_parts = self::parseExpiry($row['expiry']);

                // Validate and parse as_on_date
                $as_on_date = self::parseDate($row['as_on_date']);

                // Insert stock record
                $insert_sql = "INSERT INTO `" . CONST_TBL_PREFIX . "items_stock` 
                              (warehouse_id, item_name, quantity, unit, expiry_month, expiry_year, 
                               as_on_date, imported_on, imported_by, imported_from_ip) 
                              VALUES (:warehouse_id, :item_name, :quantity, :unit, :expiry_month, 
                                     :expiry_year, :as_on_date, :imported_on, :imported_by, :imported_from_ip)";

                $params = [
                    ':warehouse_id' => $warehouse_id,
                    ':item_name' => $row['item_name'],
                    ':quantity' => $row['quantity'],
                    ':unit' => $row['unit'],
                    ':expiry_month' => $expiry_parts['month'],
                    ':expiry_year' => $expiry_parts['year'],
                    ':as_on_date' => $as_on_date,
                    ':imported_on' => $current_datetime,
                    ':imported_by' => $user_id,
                    ':imported_from_ip' => $current_ip
                ];

                PDOConn::query($insert_sql, $params);
                $imported_count++;
            }

            // Commit transaction
            $conn->commit();

            // Log import action
            self::logImportAction($warehouse_id, $imported_count, $user_id);

            return $imported_count;

        } catch (\Exception $e) {
            // Rollback transaction
            if ($conn && $conn->inTransaction()) {
                $conn->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Get user-friendly Excel error messages
     */
    private static function getExcelErrorMessage(\Exception $e) {
        $message = $e->getMessage();

        if (stripos($message, 'memory') !== false || stripos($message, 'exhausted') !== false) {
            return 'Excel file is too large to process. Please reduce file size, split data into smaller files, or use CSV format instead.';
        } elseif (stripos($message, 'too large') !== false) {
            return $message; // Our custom size message
        } elseif (stripos($message, 'not found') !== false || stripos($message, 'not readable') !== false) {
            return 'Excel file is corrupted or cannot be read. Please check the file and try again.';
        } elseif (stripos($message, 'protected') !== false || stripos($message, 'password') !== false) {
            return 'Password-protected Excel files are not supported. Please remove password protection and try again.';
        } /*elseif (stripos($message, 'format') !== false) {
            return 'Invalid Excel file format. Please ensure you are uploading a valid .xlsx or .xls file.';
        }*/

        return 'Error processing Excel file: ' . $message;
    }

    /**
     * Validate file before processing
     */
    private static function validateFileBeforeProcessing($file, $max_file_size) {
        // Check file size
        $max_csv_size =   $max_file_size['CSV']['bytes']??2097152; //10485760; // 10MB as bytes
        $max_excel_size = $max_file_size['EXCEL']['bytes']??2097152;  //8388608; // 8MB for Excel due to memory overhead. In bytes

        $file_name = $file['name'];
        $file_size = $file['size'];

        $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($extension, ['csv'])) {
            if ($file_size > $max_csv_size) {
                throw new \Exception('CSV file size exceeds 10MB limit. Current size: ' . round($file_size / (1048576), 2) . 'MB'); // 1024 * 1024 = 1048576
            }
        } elseif (in_array($extension, ['xlsx', 'xls'])) {
            if ($file_size > $max_excel_size) {
                throw new \Exception('Excel file size exceeds 8MB limit. Current size: ' . round($file_size / (1048576), 2) . 'MB. Please use CSV format for larger files.');
            }
        }

        return true;
    }

    /**
     * Import stock data from CSV or Excel file
     */
    public static function importFromCSV($file, $warehouse_id, $user_id, $max_file_size) {
        if (empty($file) || empty($warehouse_id) || empty($user_id)) {
            throw new \Exception('Missing required parameters for import');
        }

        // Validate CSV file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('File upload error');
        }

        // Validate file size before processing
        self::validateFileBeforeProcessing($file, $max_file_size);

        $file_path = $file['tmp_name'];
        $file_name = $file['name'];

        // Detect file type
        $file_type = self::detectFileType($file_path, $file_name);

        if (!$file_type) {
            throw new \Exception('Invalid file type. Only CSV and Excel files (.csv, .xlsx, .xls) are allowed');
        }

        // Parse file based on type
        if ($file_type === 'excel') {
            $csv_data = self::parseExcelFile($file_path);
        } else {
            $csv_data = self::parseCSVFile($file_path);
        }

        // Process the data
        return self::processImportData($csv_data, $warehouse_id, $user_id);
    }

    /**
     * Generate and download Excel template
     */
    public static function downloadExcelTemplate() {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set headers
            $headers = array_values(self::getPrimaryHeaders());
            $sheet->fromArray($headers, null, 'A1');

            // Style headers
            $headerStyle = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2EFDA']
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                    ]
                ]
            ];
            $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);

            // Add sample data
            $sample_data = self::getCSVTemplate()['sample_data'];
            $sheet->fromArray($sample_data, null, 'A2');

            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(30); // Item Name
            $sheet->getColumnDimension('B')->setWidth(15); // Quantity
            $sheet->getColumnDimension('C')->setWidth(10); // Unit
            $sheet->getColumnDimension('D')->setWidth(12); // Expiry
            $sheet->getColumnDimension('E')->setWidth(15); // As On Date

            // Set worksheet name
            $sheet->setTitle('Items Stock Template');

            // Add instructions sheet
            $instructionsSheet = $spreadsheet->createSheet();
            $instructionsSheet->setTitle('Instructions');
            $instructions = [
                ['Column', 'Description', 'Format/Example'],
                ['Item Name', 'Name of the item', 'Titanium Dioxide Rutile Grade'],
                ['Quantity', 'Numeric quantity', '25.500 (numbers only)'],
                ['Unit', 'Unit of measurement', 'MT, KG, PCS, LTR'],
                ['Expiry', 'Month-Year format', 'Mar-26, Jan-25, 03-26'],
                ['As On Date', 'Date format', '2025-05-27 or 27-05-2025'],
                ['', '', ''],
                ['Important Notes:', '', ''],
                ['1. Use the first sheet (Items Stock Template) for your data', '', ''],
                ['2. Do not modify the header row', '', ''],
                ['3. Quantity must be numeric (decimals allowed)', '', ''],
                ['4. Expiry can be MMM-YY or MM-YY format', '', ''],
                ['5. Date can be YYYY-MM-DD or DD-MM-YYYY format', '', '']
            ];
            $instructionsSheet->fromArray($instructions, null, 'A1');

            // Style instructions header
            $instructionsSheet->getStyle('A1:C1')->applyFromArray($headerStyle);
            $instructionsSheet->getColumnDimension('A')->setWidth(20);
            $instructionsSheet->getColumnDimension('B')->setWidth(35);
            $instructionsSheet->getColumnDimension('C')->setWidth(30);

            // Set active sheet back to template
            $spreadsheet->setActiveSheetIndex(0);

            // Output Excel file
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="items_stock_template.xlsx"');
            header('Cache-Control: max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');

            // Clean up
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            exit;

        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            header('HTTP/1.0 500 Internal Server Error');
            die('Error generating Excel template');
        }
    }

    /**
     * Validate and map CSV headers to field names
     */
    private static function validateAndMapHeaders($csv_headers) {
        $header_mapping = [];
        $found_headers = [];
        $csv_headers_lower = array_map('strtolower', $csv_headers);
        // For each required field, find matching header
        foreach (self::CSV_HEADERS as $field => $acceptable_headers) {
            $header_found = false;
            $acceptable_headers_lower = array_map('strtolower', $acceptable_headers);
            foreach ($acceptable_headers as $key => $acceptable_header) {
                $header_index = array_search($acceptable_headers_lower[$key], $csv_headers_lower);
                if ($header_index !== false) {
                    $header_mapping[$field] = $header_index;
//                    $found_headers[] = $acceptable_header;
                    $header_found = true;
                    break;
                }
            }

            if (!$header_found) {
                // Generate error message with acceptable headers
                $acceptable_list = implode('", "', $acceptable_headers);
                throw new \Exception("Missing required header for ".$acceptable_headers[0].". Acceptable headers: \"$acceptable_list\"");
            }
        }

        return $header_mapping;
    }
    private static function validateImportRow($row_data, $row_number) {
        // Check required fields
        foreach ($row_data as $field => $value) {
            if (empty($value)) {
                throw new \Exception("Row $row_number: $field is required");
            }
        }

        // Validate quantity is numeric
        if (!is_numeric($row_data['quantity'])) {
            throw new \Exception("Row $row_number: Quantity must be numeric");
        }

        // Validate expiry format (MMM-YY)
        if (!preg_match('/^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec|\d{1,2})[\/-]\d{2}$/i', $row_data['expiry'])) {
            throw new \Exception("Row $row_number: Expiry must be in format MMM-YY (e.g., Jan-25)");
        }

        // Validate date format
        if (!self::isValidDate($row_data['as_on_date'])) {
            throw new \Exception("Row $row_number: As On Date must be a valid date (YYYY-MM-DD or DD-MM-YYYY)");
        }
    }

    /**
     * Parse expiry string (MMM-YY) to month/year
     */
    private static function parseExpiry($expiry_str) {
        $months = [
            'Jan' => 1, 'Feb' => 2, 'Mar' => 3, 'Apr' => 4,
            'May' => 5, 'Jun' => 6, 'Jul' => 7, 'Aug' => 8,
            'Sep' => 9, 'Oct' => 10, 'Nov' => 11, 'Dec' => 12
        ];

        $parts = preg_split("/[\/-]/", $expiry_str);
        $month_name = $parts[0];
        $year_short = $parts[1];

        $mnth = $months[ucfirst(strtolower($month_name))];
        if (empty($mnth)) {
            if(preg_match("/^\d{1,2}$/", $month_name)){
                $mnth = (int)$month_name;
                if(!in_array($mnth, array_values($months))){
                    throw new \Exception("Invalid month in expiry: $month_name");
                }
            }else{
                throw new \Exception("Invalid month in expiry: $month_name");
            }
        }

        $year = 2000 + intval($year_short); // Convert YY to YYYY

        return [
            'month' => $mnth,
            'year' => $year
        ];
    }

    /**
     * Parse and validate date
     */
    private static function parseDate($date_str) {
        // Try different date formats
        $formats = ['Y-m-d', 'd-m-Y', 'm/d/Y', 'd/m/Y'];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $date_str);
            if ($date && $date->format($format) === $date_str) {
                return $date->format('Y-m-d');
            }
        }

        throw new \Exception("Invalid date format: $date_str");
    }

    /**
     * Check if date is valid
     */
    private static function isValidDate($date_str) {
        try {
            self::parseDate($date_str);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Log import action to file
     */
    private static function logImportAction($warehouse_id, $imported_count, $user_id) {
        try {
            // Get warehouse and user details
            $warehouse_sql = "SELECT name FROM `" . CONST_TBL_PREFIX . "warehouses` WHERE id = :id";
            $warehouse_result = PDOConn::query($warehouse_sql, [':id' => $warehouse_id]);
            $warehouse_name = '';
            if ($warehouse_row = $warehouse_result->fetch(\PDO::FETCH_ASSOC)) {
                $warehouse_name = $warehouse_row['name'];
            }

            // Get user details
            $user_sql = "SELECT u.username, m.id as profile_id, m.name, m.mobile 
                        FROM `" . CONST_TBL_PREFIX . "users` u
                        INNER JOIN `" . CONST_TBL_PREFIX . "members` m ON u.profile_id = m.id
                        WHERE u.id = :id";
            $user_result = PDOConn::query($user_sql, [':id' => $user_id]);
            $user_details = [];
            if ($user_row = $user_result->fetch(\PDO::FETCH_ASSOC)) {
                $user_details = $user_row;
            }

            // Prepare log entry
            $log_entry = [
                'timestamp' => date('Y-m-d H:i:s'),
                'warehouse_id' => $warehouse_id,
                'warehouse_name' => $warehouse_name,
                'records_imported' => $imported_count,
                'user_id' => $user_id,
                'profile_id' => $user_details['profile_id'] ?? '',
                'user_name' => $user_details['name'] ?? '',
                'user_mobile' => $user_details['mobile'] ?? '',
                'ip_address' => \eBizIndia\getRemoteIP()
            ];

            // Write to log file
            $log_line = json_encode($log_entry) . "\n";
            file_put_contents(CONST_ITEMS_STOCK_LOG_FILE, $log_line, FILE_APPEND | LOCK_EX);

        } catch (\Exception $e) {
            // Log error but don't fail the import
            ErrorHandler::logError([
                'function' => __METHOD__,
                'warehouse_id' => $warehouse_id,
                'imported_count' => $imported_count,
                'user_id' => $user_id
            ], $e);
        }
    }

    /**
     * Get warehouse list for dropdown
     */
    public static function getWarehouseList() {
        $sql = "SELECT id, name FROM `" . CONST_TBL_PREFIX . "warehouses` WHERE active = 'y' ORDER BY name";

        try {
            $result = PDOConn::query($sql);
            $warehouses = [];
            while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {
                $warehouses[] = $row;
            }
            return $warehouses;
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }
}