<?php
namespace eBizIndia;

/**
 * DataImporter class handles file parsing, validation, and ETL for portfolio transactions
 * Supports Excel (XLSX, XLS) and CSV file formats
 */
class DataImporter {
    private $file_path;
    private $file_type;
    private $validation_errors = [];
    private $records_processed = 0;
    private $portfolio_cache = [];

    public function __construct(string $file_path, string $file_type) {
        $this->file_path = $file_path;
        $this->file_type = strtolower($file_type);
    }

    /**
     * Validate file structure and data
     * @return array ['valid' => bool, 'errors' => array, 'data' => array, 'warnings' => array]
     */
    public function validate() {
        $result = ['valid' => true, 'errors' => [], 'warnings' => [], 'data' => []];

        try {
            // Check if file exists
            if (!file_exists($this->file_path)) {
                $result['valid'] = false;
                $result['errors'][] = 'File not found: ' . $this->file_path;
                return $result;
            }

            // Parse file based on type
            if ($this->file_type === 'csv') {
                $data = $this->parseCSV();
            } elseif (in_array($this->file_type, ['xlsx', 'xls'])) {
                $data = $this->parseExcel();
            } else {
                $result['valid'] = false;
                $result['errors'][] = 'Unsupported file type: ' . $this->file_type;
                return $result;
            }

            if (empty($data)) {
                $result['valid'] = false;
                $result['errors'][] = 'No data found in file';
                return $result;
            }

            // Validate required columns
            $required_columns = [
                'transaction_date', 'portfolio_name', 'stock_code',
                'stock_name', 'transaction_type', 'quantity', 'price'
            ];

            $headers = array_keys($data[0]);
            $missing_columns = array_diff($required_columns, $headers);

            if (!empty($missing_columns)) {
                $result['valid'] = false;
                $result['errors'][] = 'Missing required columns: ' .
                    implode(', ', $missing_columns);
                return $result;
            }

            // Validate each row
            $duplicate_tracker = [];
            foreach ($data as $idx => $row) {
                $row_num = $idx + 2; // +2 for header and 0-index

                // Validate transaction date
                if (empty($row['transaction_date'])) {
                    $result['errors'][] = "Row {$row_num}: Transaction date is required";
                    $result['valid'] = false;
                } else {
                    $parsed_date = $this->parseDate($row['transaction_date']);
                    if (!$parsed_date) {
                        $result['errors'][] = "Row {$row_num}: Invalid transaction date format";
                        $result['valid'] = false;
                    }
                }

                // Validate portfolio
                if (empty($row['portfolio_name'])) {
                    $result['errors'][] = "Row {$row_num}: Portfolio name is required";
                    $result['valid'] = false;
                } else {
                    // Check if portfolio exists
                    if (!$this->portfolioExists($row['portfolio_name'])) {
                        $result['errors'][] = "Row {$row_num}: Portfolio '{$row['portfolio_name']}' does not exist";
                        $result['valid'] = false;
                    }
                }

                // Validate stock
                if (empty($row['stock_code'])) {
                    $result['errors'][] = "Row {$row_num}: Stock code is required";
                    $result['valid'] = false;
                }
                if (empty($row['stock_name'])) {
                    $result['errors'][] = "Row {$row_num}: Stock name is required";
                    $result['valid'] = false;
                }

                // Validate transaction type
                $transaction_type = strtoupper(trim($row['transaction_type']));
                if (!in_array($transaction_type, ['BUY', 'SELL'])) {
                    $result['errors'][] = "Row {$row_num}: Invalid transaction type '{$row['transaction_type']}'. Must be BUY or SELL";
                    $result['valid'] = false;
                }

                // Validate quantity
                if (!is_numeric($row['quantity']) || $row['quantity'] <= 0) {
                    $result['errors'][] = "Row {$row_num}: Invalid quantity '{$row['quantity']}'. Must be a positive number";
                    $result['valid'] = false;
                }

                // Validate price
                if (!is_numeric($row['price']) || $row['price'] <= 0) {
                    $result['errors'][] = "Row {$row_num}: Invalid price '{$row['price']}'. Must be a positive number";
                    $result['valid'] = false;
                }

                // Check for duplicates within the file
                $dup_key = $row['transaction_date'] . '|' . $row['portfolio_name'] . '|' .
                          $row['stock_code'] . '|' . $row['transaction_type'] . '|' .
                          $row['quantity'] . '|' . $row['price'];

                if (isset($duplicate_tracker[$dup_key])) {
                    $result['warnings'][] = "Row {$row_num}: Possible duplicate transaction (matches row {$duplicate_tracker[$dup_key]})";
                } else {
                    $duplicate_tracker[$dup_key] = $row_num;
                }

                // Check for duplicates in database
                if ($this->checkDuplicateInDB($row)) {
                    $result['warnings'][] = "Row {$row_num}: Transaction may already exist in database";
                }
            }

            $result['data'] = $data;

        } catch (\Exception $e) {
            $result['valid'] = false;
            $result['errors'][] = 'File parsing error: ' . $e->getMessage();
            ErrorHandler::logError(['function' => __METHOD__], $e);
        }

        return $result;
    }

    /**
     * Import validated data to database
     * @param array $data
     * @param bool $skip_duplicates
     * @return array ['success' => bool, 'imported' => int, 'skipped' => int, 'errors' => array]
     */
    public function import(array $data, $skip_duplicates = true) {
        $result = [
            'success' => true,
            'imported' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        if (empty($data)) {
            $result['success'] = false;
            $result['errors'][] = 'No data to import';
            return $result;
        }

        try {
            $conn = PDOConn::getInstance();
            $conn->beginTransaction();

            $transaction = new Transaction();

            foreach ($data as $idx => $row) {
                $row_num = $idx + 2;

                try {
                    // Check for duplicate if skip_duplicates is enabled
                    if ($skip_duplicates && $this->checkDuplicateInDB($row)) {
                        $result['skipped']++;
                        continue;
                    }

                    // Get portfolio ID
                    $portfolio_name = trim($row['portfolio_name']);
                    $portfolio_id = $this->getPortfolioId($portfolio_name);

                    if (!$portfolio_id) {
                        $result['errors'][] = "Row {$row_num}: Portfolio '{$portfolio_name}' not found";
                        continue;
                    }

                    // Prepare transaction data
                    $transaction_data = [
                        'portfolio_id' => $portfolio_id,
                        'transaction_date' => date('Y-m-d', strtotime($this->parseDate($row['transaction_date']))),
                        'stock_code' => strtoupper(trim($row['stock_code'])),
                        'stock_name' => trim($row['stock_name']),
                        'instrument_type' => !empty($row['instrument_type']) ? trim($row['instrument_type']) : 'Spot',
                        'transaction_type' => strtoupper(trim($row['transaction_type'])),
                        'quantity' => (float)$row['quantity'],
                        'price' => (float)$row['price'],
                        'transaction_value' => (float)$row['quantity'] * (float)$row['price'],
                        'source_file' => basename($this->file_path)
                    ];

                    // Add optional fields if present
                    if (!empty($row['expiry_date'])) {
                        $transaction_data['expiry_date'] = date('Y-m-d', strtotime($row['expiry_date']));
                    }
                    if (!empty($row['strike_price'])) {
                        $transaction_data['strike_price'] = (float)$row['strike_price'];
                    }

                    // Insert transaction
                    if ($transaction->add($transaction_data)) {
                        $result['imported']++;
                        $this->records_processed++;
                    } else {
                        $result['errors'][] = "Row {$row_num}: Failed to insert transaction";
                    }

                } catch (\Exception $e) {
                    $result['errors'][] = "Row {$row_num}: " . $e->getMessage();
                }
            }

            $conn->commit();

        } catch (\Exception $e) {
            if (isset($conn) && $conn->inTransaction()) {
                $conn->rollBack();
            }
            $result['success'] = false;
            $result['errors'][] = 'Import failed: ' . $e->getMessage();
            ErrorHandler::logError(['function' => __METHOD__], $e);
        }

        return $result;
    }

    /**
     * Parse CSV file
     * @return array
     */
    private function parseCSV() {
        $data = [];

        if (($handle = fopen($this->file_path, 'r')) !== false) {
            $headers = fgetcsv($handle);
            if (!$headers) {
                fclose($handle);
                return $data;
            }

            // Clean headers
            $headers = array_map('trim', $headers);
            $headers = array_map('strtolower', $headers);
            $headers = array_map(function($h) {
                return str_replace([' ', '-'], '_', $h);
            }, $headers);

            while (($row = fgetcsv($handle)) !== false) {
                // Skip empty rows
                if (count(array_filter($row)) === 0) {
                    continue;
                }

                if (count($row) === count($headers)) {
                    $data[] = array_combine($headers, $row);
                }
            }
            fclose($handle);
        }

        return $data;
    }

    /**
     * Parse Excel file (XLSX or XLS)
     * @return array
     */
    private function parseExcel() {
        $data = [];

        // Check if PhpSpreadsheet is available
        if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            // Fallback: Try to use a simple Excel reader or return error
            throw new \Exception('PhpSpreadsheet library not available. Please install it via Composer.');
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($this->file_path);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (empty($rows)) {
                return $data;
            }

            // First row as headers
            $headers = array_shift($rows);
            $headers = array_map('trim', $headers);
            $headers = array_map('strtolower', $headers);
            $headers = array_map(function($h) {
                return str_replace([' ', '-'], '_', $h);
            }, $headers);

            foreach ($rows as $row) {
                // Skip empty rows
                if (count(array_filter($row)) === 0) {
                    continue;
                }

                if (count($row) === count($headers)) {
                    $data[] = array_combine($headers, $row);
                }
            }

        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            throw $e;
        }

        return $data;
    }

    /**
     * Parse date from various formats
     * @param mixed $date_value
     * @return string|false
     */
    private function parseDate($date_value) {
        if (empty($date_value)) {
            return false;
        }

        // Handle Excel date serial numbers
        if (is_numeric($date_value) && $date_value > 1) {
            try {
                $unix_date = ($date_value - 25569) * 86400;
                return date('Y-m-d', $unix_date);
            } catch (\Exception $e) {
                return false;
            }
        }

        // Try various date formats
        $formats = [
            'Y-m-d',
            'd-m-Y',
            'd/m/Y',
            'm/d/Y',
            'Y/m/d',
            'd-M-Y',
            'd M Y',
            'd-m-y',
            'd/m/y'
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $date_value);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        // Try strtotime as fallback
        $timestamp = strtotime($date_value);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return false;
    }

    /**
     * Check if portfolio exists (with caching)
     * @param string $portfolio_name
     * @return bool
     */
    private function portfolioExists($portfolio_name) {
        $portfolio_name = trim($portfolio_name);

        if (!isset($this->portfolio_cache[$portfolio_name])) {
            $options = [
                'filters' => [
                    ['field' => 'portfolio_name', 'value' => $portfolio_name, 'type' => '=']
                ]
            ];
            $portfolios = Portfolio::getList($options);
            $this->portfolio_cache[$portfolio_name] = !empty($portfolios);
        }

        return $this->portfolio_cache[$portfolio_name];
    }

    /**
     * Get portfolio ID (with caching)
     * @param string $portfolio_name
     * @return int|false
     */
    private function getPortfolioId($portfolio_name) {
        $portfolio_name = trim($portfolio_name);

        if (!isset($this->portfolio_cache[$portfolio_name . '_id'])) {
            $options = [
                'filters' => [
                    ['field' => 'portfolio_name', 'value' => $portfolio_name, 'type' => '=']
                ]
            ];
            $portfolios = Portfolio::getList($options);

            if (!empty($portfolios)) {
                $this->portfolio_cache[$portfolio_name . '_id'] = $portfolios[0]['portfolio_id'];
            } else {
                return false;
            }
        }

        return $this->portfolio_cache[$portfolio_name . '_id'];
    }

    /**
     * Check if transaction already exists in database
     * @param array $row
     * @return bool
     */
    private function checkDuplicateInDB($row) {
        try {
            $portfolio_id = $this->getPortfolioId(trim($row['portfolio_name']));
            if (!$portfolio_id) {
                return false;
            }

            $transaction_date = date('Y-m-d', strtotime($this->parseDate($row['transaction_date'])));
            $stock_code = strtoupper(trim($row['stock_code']));
            $transaction_type = strtoupper(trim($row['transaction_type']));
            $quantity = (float)$row['quantity'];
            $price = (float)$row['price'];

            $sql = "SELECT COUNT(*) as count FROM `transactions`
                    WHERE portfolio_id = :portfolio_id
                    AND transaction_date = :transaction_date
                    AND stock_code = :stock_code
                    AND transaction_type = :transaction_type
                    AND quantity = :quantity
                    AND price = :price";

            $stmt = PDOConn::query($sql, [
                ':transaction_date' => $transaction_date,
                ':stock_code' => $stock_code,
                ':transaction_type' => $transaction_type
            ], [
                ':portfolio_id' => $portfolio_id,
                ':quantity' => $quantity,
                ':price' => $price
            ]);

            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return ($result['count'] > 0);

        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Get records processed count
     * @return int
     */
    public function getRecordsProcessed() {
        return $this->records_processed;
    }

    /**
     * Get validation errors
     * @return array
     */
    public function getValidationErrors() {
        return $this->validation_errors;
    }
}
