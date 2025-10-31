# Portfolio Management System - Development Plan

**Project:** Portfolio Management System
**Date:** October 31, 2025
**Version:** 1.0
**Based on:** Portfolio_Management_System_PRD v1.1

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Technology Stack & Existing Architecture](#technology-stack--existing-architecture)
3. [Database Schema Implementation](#database-schema-implementation)
4. [Module Structure & File Organization](#module-structure--file-organization)
5. [Phase-wise Implementation Plan](#phase-wise-implementation-plan)
6. [Detailed Module Development](#detailed-module-development)
7. [Code Patterns & Standards](#code-patterns--standards)
8. [Testing Strategy](#testing-strategy)
9. [Deployment Plan](#deployment-plan)

---

## 1. Executive Summary

This document outlines the detailed development plan for implementing the Portfolio Management System based on the PRD. The system will be built on the existing PHP/MySQL codebase, following established patterns and conventions.

### Key Objectives
- Automate portfolio performance tracking (eliminate manual spreadsheets)
- Implement FIFO calculation engine for P&L
- Create flexible portfolio combination management
- Build comprehensive dashboards with real-time metrics
- Generate professional reports in multiple formats

### Development Timeline
- **Total Duration:** 15 weeks
- **Team Size:** 1-2 developers
- **Deployment Target:** Production server (Linux/Apache/PHP 8.4/MySQL)

---

## 2. Technology Stack & Existing Architecture

### Current Stack
- **Frontend:** HTML5, CSS3, Bootstrap 5, jQuery
- **Backend:** PHP 8.4 with namespaces (`eBizIndia`)
- **Database:** MySQL 8.0+ with InnoDB engine
- **Server:** Linux + Apache
- **Session Management:** Custom DbSessionHandler

### Existing Code Patterns

#### Class Structure (`/cls/` directory)
```php
namespace eBizIndia;

class EntityName {
    private $entity_id;

    public function __construct(?int $entity_id = null) {
        $this->entity_id = $entity_id;
    }

    public static function getList($options = []) {
        // Complex query builder with filters, sorting, pagination
        // Returns array of records or false on error
    }

    public function add(array $data) {
        // Insert new record with audit fields
        // Returns lastInsertId() or false
    }

    public function update($data) {
        // Update existing record
        // Returns true, null (no changes), or false
    }

    public function getDetails($fields_to_fetch = []) {
        // Get specific entity details
    }
}
```

#### Page Structure (root directory)
```php
<?php
$page = 'entity-name';
require_once 'inc.php';

// Template setup
$page_title = 'Entity Management' . CONST_TITLE_AFX;
$body_template_file = CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'entity.tpl';

// Permission checks
$can_add = $can_edit = $can_delete = $can_view = false;
// ... permission logic

// AJAX Handlers
if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'createrec') {
    // Create handler
}
elseif ($_POST['mode'] == 'updaterec') {
    // Update handler
}
elseif ($_POST['mode'] == 'getList') {
    // List handler
}
// ... more handlers

// Page rendering
$page_renderer->renderPage();
```

### Database Conventions
- Table prefix: `CONST_TBL_PREFIX` (from config)
- Audit fields: `created_on`, `created_by`, `created_from`, `updated_on`, `updated_by`, `updated_from`
- Active status: `active` CHAR(1) DEFAULT 'y'
- Primary keys: AUTO_INCREMENT INT/BIGINT UNSIGNED
- Foreign keys: Explicit constraints with ON UPDATE CASCADE

---

## 3. Database Schema Implementation

### New Tables to Create

#### 3.1 portfolios
```sql
CREATE TABLE `portfolios` (
  `portfolio_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `portfolio_name` VARCHAR(100) NOT NULL UNIQUE,
  `portfolio_type` ENUM('Own', 'Portfolio Manager', 'Unlisted & AIF') NOT NULL,
  `description` TEXT,
  `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_from` VARCHAR(40) NULL,
  `updated_at` TIMESTAMP NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `updated_from` VARCHAR(40) NULL,
  INDEX `idx_status` (`status`),
  INDEX `idx_type` (`portfolio_type`),
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 3.2 portfolio_combinations
```sql
CREATE TABLE `portfolio_combinations` (
  `combination_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `combination_name` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT,
  `created_by` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_created_by` (`created_by`),
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 3.3 portfolio_combination_mapping
```sql
CREATE TABLE `portfolio_combination_mapping` (
  `mapping_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `combination_id` INT UNSIGNED NOT NULL,
  `portfolio_id` INT UNSIGNED NOT NULL,
  UNIQUE KEY `unique_mapping` (`combination_id`, `portfolio_id`),
  INDEX `idx_combination` (`combination_id`),
  INDEX `idx_portfolio` (`portfolio_id`),
  FOREIGN KEY (`combination_id`) REFERENCES `portfolio_combinations` (`combination_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`portfolio_id`) REFERENCES `portfolios` (`portfolio_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 3.4 transactions
```sql
CREATE TABLE `transactions` (
  `transaction_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `portfolio_id` INT UNSIGNED NOT NULL,
  `transaction_date` DATE NOT NULL,
  `stock_code` VARCHAR(50) NOT NULL,
  `stock_name` VARCHAR(200) NOT NULL,
  `instrument_type` VARCHAR(50) DEFAULT 'Spot',
  `transaction_type` ENUM('BUY', 'SELL') NOT NULL,
  `quantity` DECIMAL(15,2) NOT NULL,
  `price` DECIMAL(15,4) NOT NULL,
  `transaction_value` DECIMAL(18,2) NOT NULL,
  `expiry_date` DATE NULL,
  `strike_price` DECIMAL(15,2) NULL,
  `upload_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `source_file` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_portfolio_date` (`portfolio_id`, `transaction_date`),
  INDEX `idx_stock` (`stock_code`),
  INDEX `idx_transaction_date` (`transaction_date`),
  FOREIGN KEY (`portfolio_id`) REFERENCES `portfolios` (`portfolio_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 3.5 holdings
```sql
CREATE TABLE `holdings` (
  `holding_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `portfolio_id` INT UNSIGNED NOT NULL,
  `stock_code` VARCHAR(50) NOT NULL,
  `stock_name` VARCHAR(200) NOT NULL,
  `current_quantity` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `avg_cost_price` DECIMAL(15,4) NOT NULL DEFAULT 0,
  `total_invested` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `current_market_price` DECIMAL(15,4) NULL,
  `current_value` DECIMAL(18,2) NULL,
  `unrealized_pl` DECIMAL(18,2) NULL,
  `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_portfolio_stock` (`portfolio_id`, `stock_code`),
  INDEX `idx_stock` (`stock_code`),
  FOREIGN KEY (`portfolio_id`) REFERENCES `portfolios` (`portfolio_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 3.6 realized_pl
```sql
CREATE TABLE `realized_pl` (
  `pl_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `portfolio_id` INT UNSIGNED NOT NULL,
  `stock_code` VARCHAR(50) NOT NULL,
  `sell_date` DATE NOT NULL,
  `quantity_sold` DECIMAL(15,2) NOT NULL,
  `sell_price` DECIMAL(15,4) NOT NULL,
  `avg_buy_price` DECIMAL(15,4) NOT NULL,
  `realized_pl` DECIMAL(18,2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_portfolio_date` (`portfolio_id`, `sell_date`),
  INDEX `idx_stock` (`stock_code`),
  FOREIGN KEY (`portfolio_id`) REFERENCES `portfolios` (`portfolio_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 3.7 benchmark_data
```sql
CREATE TABLE `benchmark_data` (
  `benchmark_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `index_name` VARCHAR(50) NOT NULL,
  `date` DATE NOT NULL,
  `close_value` DECIMAL(15,2) NOT NULL,
  `return_pct` DECIMAL(10,4) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_index_date` (`index_name`, `date`),
  INDEX `idx_date` (`date`),
  INDEX `idx_index` (`index_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 3.8 file_uploads
```sql
CREATE TABLE `file_uploads` (
  `upload_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_size` INT UNSIGNED NOT NULL,
  `upload_date` DATE NOT NULL,
  `status` ENUM('Pending', 'Validated', 'Imported', 'Failed') DEFAULT 'Pending',
  `validation_errors` TEXT NULL,
  `records_count` INT UNSIGNED NULL,
  `uploaded_by` BIGINT UNSIGNED NOT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `uploaded_from` VARCHAR(40) NULL,
  INDEX `idx_status` (`status`),
  INDEX `idx_date` (`upload_date`),
  FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 3.9 performance_cache
```sql
CREATE TABLE `performance_cache` (
  `cache_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `cache_key` VARCHAR(255) NOT NULL UNIQUE,
  `portfolio_id` INT UNSIGNED NULL,
  `combination_id` INT UNSIGNED NULL,
  `calculation_type` VARCHAR(50) NOT NULL,
  `start_date` DATE NULL,
  `end_date` DATE NULL,
  `result_data` TEXT NOT NULL,
  `calculated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NULL,
  INDEX `idx_portfolio` (`portfolio_id`),
  INDEX `idx_combination` (`combination_id`),
  INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Database Migration Script

Create file: `includes/portfolio-schema.sql`

```sql
-- Portfolio Management System Schema
-- Run this script to create all required tables

-- Set SQL mode
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- [Include all CREATE TABLE statements above]

COMMIT;
```

---

## 4. Module Structure & File Organization

### 4.1 Class Files (`/cls/`)

#### New Classes to Create

```
/cls/
├── Portfolio.php                    # Portfolio entity management
├── PortfolioCombination.php        # Combination management
├── Transaction.php                  # Transaction management
├── Holding.php                      # Holdings calculations
├── RealizedPL.php                  # Realized P&L tracking
├── BenchmarkData.php               # Benchmark data management
├── FileUpload.php                  # File upload handling
├── PerformanceCalculator.php       # Performance metrics engine
├── FIFOCalculator.php              # FIFO calculation logic
├── XIRRCalculator.php              # XIRR calculation
├── DataImporter.php                # ETL pipeline
├── ReportGenerator.php             # Report generation
└── enums/
    ├── PortfolioType.php           # Portfolio type enum
    ├── TransactionType.php         # Transaction type enum
    └── InstrumentType.php          # Instrument type enum
```

### 4.2 Page Files (root directory)

```
/
├── portfolios.php                  # Portfolio management page
├── portfolio-combinations.php      # Combination management
├── transactions.php                # Transaction listing/management
├── data-upload.php                 # File upload interface (Admin)
├── dashboard.php                   # Main dashboard
├── dashboard-consolidated.php      # Consolidated view
├── dashboard-combination.php       # Combination view
├── dashboard-individual.php        # Individual portfolio view
├── holdings.php                    # Holdings view
├── reports.php                     # Report generation
└── benchmark-data.php              # Benchmark data management (Admin)
```

### 4.3 Template Files (`/themes/.../`)

```
/themes/default/templates/
├── portfolios.tpl
├── portfolio-combinations.tpl
├── transactions.tpl
├── data-upload.tpl
├── dashboard.tpl
├── dashboard-consolidated.tpl
├── dashboard-combination.tpl
├── dashboard-individual.tpl
├── holdings.tpl
├── reports.tpl
└── benchmark-data.tpl
```

### 4.4 JavaScript Files (`/js/`)

```
/js/
├── portfolios.js
├── portfolio-combinations.js
├── transactions.js
├── data-upload.js
├── dashboard.js
├── dashboard-charts.js
├── holdings.js
└── reports.js
```

### 4.5 Upload Directory Structure

```
/uploads/
└── portfolio-data/
    ├── pending/                    # Uploaded files pending validation
    ├── processed/                  # Successfully processed files
    └── failed/                     # Failed uploads
```

---

## 5. Phase-wise Implementation Plan

### Phase 1: Foundation (Weeks 1-2)

**Goal:** Set up database schema and base classes

#### Week 1
- Create database schema (all tables)
- Create menu entries in database
- Set up role-based permissions
- Create Portfolio.php class
- Create PortfolioCombination.php class
- Create portfolios.php page

#### Week 2
- Create portfolio-combinations.php page
- Implement CRUD for portfolios
- Implement CRUD for combinations
- Create UI templates
- Write JavaScript for portfolio management
- Unit testing for base classes

**Deliverables:**
- Working portfolio and combination management
- Admin can create/edit/delete portfolios
- Admin can create/edit/delete combinations
- Database fully set up

---

### Phase 2: Data Import Module (Weeks 3-4)

**Goal:** Build file upload and ETL pipeline

#### Week 3
- Create FileUpload.php class
- Create DataImporter.php class
- Create data-upload.php page
- Implement file upload interface (drag-drop)
- Implement file parsing (Excel/CSV)
- Build validation logic

#### Week 4
- Implement ETL pipeline
- Create Transaction.php class
- Implement duplicate detection
- Build validation report UI
- Implement file replacement functionality
- Create upload history tracking
- Testing with sample files

**Deliverables:**
- Admin can upload Excel/CSV files
- System validates file format and data
- ETL pipeline imports to transactions table
- Upload history visible to admin

---

### Phase 3: Calculation Engine (Weeks 5-6)

**Goal:** Implement FIFO and performance metrics calculations

#### Week 5
- Create FIFOCalculator.php class
- Implement FIFO logic for holdings
- Create RealizedPL.php class
- Implement P&L calculation
- Create Holding.php class
- Update holdings table from transactions

#### Week 6
- Create PerformanceCalculator.php class
- Implement XIRR calculation (XIRRCalculator.php)
- Implement ROCE calculation
- Implement Annualized Return calculation
- Implement Alpha calculation
- Create BenchmarkData.php class
- Implement combination aggregation logic
- Create performance_cache mechanism

**Deliverables:**
- FIFO calculations working correctly
- Holdings table auto-updated
- All performance metrics calculated
- Cache mechanism for performance

---

### Phase 4: Combination Management Enhancement (Week 7)

**Goal:** Complete combination features with calculations

#### Week 7
- Enhance combination management UI
- Implement combination selector
- Build combination-based filtering
- Implement aggregated calculations for combinations
- Test combination XIRR calculations
- Test combination ROCE calculations
- Create combination preview feature

**Deliverables:**
- Fully functional combination system
- Combinations work with all calculations
- Users can select combinations in dashboards

---

### Phase 5: Dashboard Development (Weeks 8-10)

**Goal:** Build interactive dashboards with all views

#### Week 8
- Create dashboard.php base
- Create dashboard-consolidated.php
- Implement metrics cards
- Implement portfolio summary table
- Build filter interface (date, FY, benchmark)
- Implement year-wise comparison

#### Week 9
- Create dashboard-combination.php
- Implement combination selector
- Build breakdown table
- Implement combined holdings table
- Create dashboard-individual.php
- Implement holdings table

#### Week 10
- Create holdings.php for consolidated stock holdings
- Implement data tables with sorting
- Implement search functionality
- Implement export from tables (Excel/CSV)
- Responsive design testing
- Performance optimization

**Deliverables:**
- All dashboard views working
- Interactive data tables
- Filters and selectors operational
- Mobile-responsive design

---

### Phase 6: Reporting Module (Weeks 11-12)

**Goal:** Implement report generation in multiple formats

#### Week 11
- Create ReportGenerator.php class
- Implement PDF generation (TCPDF/mPDF)
- Create report templates
- Implement consolidated portfolio report
- Implement combination report
- Implement individual portfolio report

#### Week 12
- Implement Excel export with formulas
- Implement CSV export
- Create drawdown report
- Implement multi-report export
- Add company logo/branding to reports
- Testing all report formats

**Deliverables:**
- PDF reports with professional formatting
- Excel reports with working formulas
- CSV exports
- Drawdown reports

---

### Phase 7: Testing & Refinement (Weeks 13-14)

**Goal:** Comprehensive testing and bug fixes

#### Week 13
- Unit testing all classes
- Integration testing
- Test with real data
- Combination functionality testing
- Performance testing
- Security testing (SQL injection, XSS, CSRF)

#### Week 14
- User acceptance testing (UAT)
- Bug fixes
- Performance optimization
- Code refactoring
- Documentation updates
- Final review

**Deliverables:**
- Bug-free system
- Performance optimized
- Security hardened
- Documentation complete

---

### Phase 8: Deployment & Training (Week 15)

**Goal:** Deploy to production and train users

#### Week 15
- Deploy to production server
- Configure automated backups
- Set up monitoring
- Create user documentation
- Create admin documentation
- Conduct user training
- Post-deployment support

**Deliverables:**
- Live production system
- User & admin documentation
- Trained users
- Support procedures in place

---

## 6. Detailed Module Development

### 6.1 Portfolio Module

#### Files
- `/cls/Portfolio.php`
- `/portfolios.php`
- `/themes/.../portfolios.tpl`
- `/js/portfolios.js`

#### Class: Portfolio.php

```php
<?php
namespace eBizIndia;

class Portfolio {
    private $portfolio_id;

    public function __construct(?int $portfolio_id = null) {
        $this->portfolio_id = $portfolio_id;
    }

    /**
     * Get portfolio list with filters, sorting, pagination
     * @param array $options
     * @return array|false
     */
    public static function getList($options = []) {
        $fields_mapper = [
            '*' => 'p.portfolio_id, p.portfolio_name, p.portfolio_type,
                    p.description, p.status, p.created_at',
            'recordcount' => 'COUNT(DISTINCT p.portfolio_id)',
            'portfolio_id' => 'p.portfolio_id',
            'portfolio_name' => 'p.portfolio_name',
            'portfolio_type' => 'p.portfolio_type',
            'status' => 'p.status'
        ];

        $where_clause = [];
        $str_params = $int_params = [];

        // Build filters
        if (!empty($options['filters'])) {
            foreach ($options['filters'] as $idx => $filter) {
                switch ($filter['field']) {
                    case 'portfolio_id':
                        $where_clause[] = $fields_mapper['portfolio_id'] . ' = :pid' . $idx;
                        $int_params[':pid' . $idx] = $filter['value'];
                        break;
                    case 'portfolio_name':
                        $where_clause[] = $fields_mapper['portfolio_name'] . ' LIKE :pname' . $idx;
                        $str_params[':pname' . $idx] = '%' . $filter['value'] . '%';
                        break;
                    case 'portfolio_type':
                        $where_clause[] = $fields_mapper['portfolio_type'] . ' = :ptype' . $idx;
                        $str_params[':ptype' . $idx] = $filter['value'];
                        break;
                    case 'status':
                        $where_clause[] = $fields_mapper['status'] . ' = :status' . $idx;
                        $str_params[':status' . $idx] = $filter['value'];
                        break;
                }
            }
        }

        // Select fields
        $select_string = $fields_mapper['*'];
        $record_count = false;

        if (!empty($options['fieldstofetch'])) {
            if (in_array('recordcount', $options['fieldstofetch'])) {
                $select_string = $fields_mapper['recordcount'];
                $record_count = true;
            } else {
                // Build select string from requested fields
                $selected_fields = [];
                foreach ($options['fieldstofetch'] as $field) {
                    if (isset($fields_mapper[$field])) {
                        $selected_fields[] = $fields_mapper[$field] . ' as ' . $field;
                    }
                }
                if (!empty($selected_fields)) {
                    $select_string = implode(', ', $selected_fields);
                }
            }
        }

        // Order by
        $order_by = '';
        if (!empty($options['order_by'])) {
            $order_parts = [];
            foreach ($options['order_by'] as $order) {
                if (isset($fields_mapper[$order['field']])) {
                    $order_parts[] = $fields_mapper[$order['field']] .
                        ' ' . ($order['type'] ?? 'ASC');
                }
            }
            if (!empty($order_parts)) {
                $order_by = ' ORDER BY ' . implode(', ', $order_parts);
            }
        } else if (!$record_count) {
            $order_by = ' ORDER BY p.portfolio_name ASC';
        }

        // Pagination
        $limit = '';
        if (!empty($options['page']) && !empty($options['recs_per_page'])) {
            $offset = ($options['page'] - 1) * $options['recs_per_page'];
            $limit = " LIMIT {$offset}, {$options['recs_per_page']}";
        }

        // Build query
        $where_string = '';
        if (!empty($where_clause)) {
            $where_string = ' WHERE ' . implode(' AND ', $where_clause);
        }

        $sql = "SELECT {$select_string}
                FROM `" . CONST_TBL_PREFIX . "portfolios` p
                {$where_string}
                {$order_by}
                {$limit}";

        try {
            $stmt = PDOConn::query($sql, $str_params, $int_params);
            $data = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $data[] = $row;
            }
            return $data;
        } catch (\Exception $e) {
            ErrorHandler::logError([
                'function' => __METHOD__,
                'sql' => $sql
            ], $e);
            return false;
        }
    }

    /**
     * Add new portfolio
     * @param array $data
     * @return int|false Last insert ID or false
     */
    public function add(array $data) {
        if (empty($data)) {
            return false;
        }

        // Add audit fields
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = $GLOBALS['loggedindata'][0]['id'] ?? null;
        $data['created_from'] = \eBizIndia\getRemoteIP();

        $sql = "INSERT INTO `" . CONST_TBL_PREFIX . "portfolios` SET ";
        $values = [];
        $str_data = [];

        foreach ($data as $field => $value) {
            $key = ":{$field}";
            if ($value === '' || $value === null) {
                $values[] = "`{$field}` = NULL";
            } else {
                $values[] = "`{$field}` = {$key}";
                $str_data[$key] = $value;
            }
        }

        $sql .= implode(', ', $values);

        try {
            $stmt = PDOConn::query($sql, $str_data);
            return PDOConn::lastInsertId();
        } catch (\Exception $e) {
            ErrorHandler::logError([
                'function' => __METHOD__,
                'sql' => $sql,
                'data' => $data
            ], $e);
            return false;
        }
    }

    /**
     * Update portfolio
     * @param array $data
     * @return bool|null
     */
    public function update($data) {
        if (empty($data) || empty($this->portfolio_id)) {
            return false;
        }

        // Add audit fields
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['updated_by'] = $GLOBALS['loggedindata'][0]['id'] ?? null;
        $data['updated_from'] = \eBizIndia\getRemoteIP();

        $sql = "UPDATE `" . CONST_TBL_PREFIX . "portfolios` SET ";
        $values = [];
        $str_data = [];
        $int_data = [':portfolio_id' => $this->portfolio_id];

        foreach ($data as $field => $value) {
            $key = ":{$field}";
            if ($value === '' || $value === null) {
                $values[] = "`{$field}` = NULL";
            } else {
                $values[] = "`{$field}` = {$key}";
                $str_data[$key] = $value;
            }
        }

        $sql .= implode(', ', $values);
        $sql .= " WHERE `portfolio_id` = :portfolio_id";

        try {
            $stmt = PDOConn::query($sql, $str_data, $int_data);
            $affected = $stmt->rowCount();
            return $affected > 0 ? true : null;
        } catch (\Exception $e) {
            ErrorHandler::logError([
                'function' => __METHOD__,
                'sql' => $sql
            ], $e);
            return false;
        }
    }

    /**
     * Get portfolio details
     * @return array|false
     */
    public function getDetails() {
        if (empty($this->portfolio_id)) {
            return false;
        }

        $options = [
            'filters' => [
                ['field' => 'portfolio_id', 'value' => $this->portfolio_id]
            ]
        ];

        return self::getList($options);
    }

    /**
     * Delete portfolio (soft delete by setting status)
     * @return bool
     */
    public function delete() {
        return $this->update(['status' => 'Inactive']);
    }

    /**
     * Get total invested amount for portfolio
     * @return float|false
     */
    public function getTotalInvested() {
        $sql = "SELECT SUM(transaction_value) as total
                FROM `" . CONST_TBL_PREFIX . "transactions`
                WHERE portfolio_id = :portfolio_id
                AND transaction_type = 'BUY'";

        try {
            $stmt = PDOConn::query($sql, [], [':portfolio_id' => $this->portfolio_id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row['total'] ?? 0;
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Get current value of portfolio
     * @return float|false
     */
    public function getCurrentValue() {
        $sql = "SELECT SUM(current_value) as total
                FROM `" . CONST_TBL_PREFIX . "holdings`
                WHERE portfolio_id = :portfolio_id";

        try {
            $stmt = PDOConn::query($sql, [], [':portfolio_id' => $this->portfolio_id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row['total'] ?? 0;
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }
}
```

**Key Methods:**
- `getList()` - Fetch portfolios with filters, pagination
- `add()` - Create new portfolio
- `update()` - Update portfolio
- `getDetails()` - Get specific portfolio
- `getTotalInvested()` - Calculate total investment
- `getCurrentValue()` - Get current portfolio value

#### Page: portfolios.php

```php
<?php
$page = 'portfolios';
require_once 'inc.php';

$page_title = 'Manage Portfolios' . CONST_TITLE_AFX;
$body_template_file = CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'portfolios.tpl';
$page_renderer->registerBodyTemplate($body_template_file, []);

// Permission checks
$can_add = $can_edit = $can_delete = $can_view = false;
$_cu_role = $loggedindata[0]['profile_details']['assigned_roles'][0]['role'];
if (in_array('ALL', $allowed_menu_perms)) {
    $can_add = $can_edit = $can_delete = $can_view = true;
} else {
    // Check specific permissions
    if (in_array('ADD', $allowed_menu_perms)) $can_add = true;
    if (in_array('EDIT', $allowed_menu_perms)) $can_edit = true;
    if (in_array('DELETE', $allowed_menu_perms)) $can_delete = true;
    if (in_array('VIEW', $allowed_menu_perms)) $can_view = true;
}

$rec_fields = [
    'portfolio_name' => '',
    'portfolio_type' => '',
    'description' => '',
    'status' => 'Active'
];

// CREATE RECORD HANDLER
if (filter_has_var(INPUT_POST, 'mode') && $_POST['mode'] == 'createrec') {
    $result = ['error_code' => 0, 'message' => ''];

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
            $result['message'] = "Portfolio name is required.";
        } elseif (empty($data['portfolio_type'])) {
            $result['error_code'] = 2;
            $result['message'] = "Portfolio type is required.";
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
    $result = ['error_code' => 0, 'message' => ''];

    if ($can_edit === false) {
        $result['error_code'] = 403;
        $result['message'] = "Sorry, you are not authorized to update portfolios.";
    } else {
        $portfolio_id = (int)$_POST['recordid'];

        if ($portfolio_id <= 0) {
            $result['error_code'] = 2;
            $result['message'] = "Invalid portfolio reference.";
        } else {
            $portfolio = new \eBizIndia\Portfolio($portfolio_id);
            $data = \eBizIndia\trim_deep(\eBizIndia\striptags_deep(
                array_intersect_key($_POST, $rec_fields)
            ));

            if (empty($data['portfolio_name'])) {
                $result['error_code'] = 2;
                $result['message'] = "Portfolio name is required.";
            } else {
                $update_result = $portfolio->update($data);

                if ($update_result === true) {
                    $result['message'] = 'Portfolio has been updated successfully.';
                } elseif ($update_result === null) {
                    $result['error_code'] = 4;
                    $result['message'] = 'No changes were made.';
                } else {
                    $result['error_code'] = 1;
                    $result['message'] = 'Failed to update portfolio.';
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
                    'value' => $filter['searchtext']
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
    $result[1]['record_details'] = $recorddetails ?? [];

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
    'can_edit' => $can_edit
]);

$page_renderer->renderPage();
```

---

### 6.2 Portfolio Combination Module

#### Class: PortfolioCombination.php

```php
<?php
namespace eBizIndia;

class PortfolioCombination {
    private $combination_id;

    public function __construct(?int $combination_id = null) {
        $this->combination_id = $combination_id;
    }

    /**
     * Get combination list with portfolios
     * @param array $options
     * @return array|false
     */
    public static function getList($options = []) {
        $fields_mapper = [
            '*' => 'pc.combination_id, pc.combination_name, pc.description,
                    pc.created_at, pc.created_by',
            'recordcount' => 'COUNT(DISTINCT pc.combination_id)',
            'combination_id' => 'pc.combination_id',
            'combination_name' => 'pc.combination_name'
        ];

        // Similar structure to Portfolio::getList()
        // [Implementation follows same pattern]

        // Additionally fetch mapped portfolios
        $sql = "SELECT pc.*,
                GROUP_CONCAT(p.portfolio_name ORDER BY p.portfolio_name SEPARATOR ', ') as portfolio_names,
                COUNT(pcm.portfolio_id) as portfolio_count
                FROM `" . CONST_TBL_PREFIX . "portfolio_combinations` pc
                LEFT JOIN `" . CONST_TBL_PREFIX . "portfolio_combination_mapping` pcm
                    ON pc.combination_id = pcm.combination_id
                LEFT JOIN `" . CONST_TBL_PREFIX . "portfolios` p
                    ON pcm.portfolio_id = p.portfolio_id
                GROUP BY pc.combination_id
                ORDER BY pc.combination_name";

        try {
            $stmt = PDOConn::query($sql, [], []);
            $data = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $data[] = $row;
            }
            return $data;
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Add new combination
     * @param array $data
     * @param array $portfolio_ids
     * @return int|false
     */
    public function add(array $data, array $portfolio_ids = []) {
        if (empty($data['combination_name'])) {
            return false;
        }

        $data['created_by'] = $GLOBALS['loggedindata'][0]['id'] ?? null;
        $data['created_at'] = date('Y-m-d H:i:s');

        try {
            $conn = PDOConn::getInstance();
            $conn->beginTransaction();

            // Insert combination
            $sql = "INSERT INTO `" . CONST_TBL_PREFIX . "portfolio_combinations`
                    SET combination_name = :name, description = :desc,
                        created_by = :created_by, created_at = :created_at";

            $stmt = PDOConn::query($sql, [
                ':name' => $data['combination_name'],
                ':desc' => $data['description'] ?? '',
                ':created_by' => $data['created_by'],
                ':created_at' => $data['created_at']
            ]);

            $combination_id = PDOConn::lastInsertId();

            // Insert portfolio mappings
            if (!empty($portfolio_ids)) {
                $this->combination_id = $combination_id;
                $this->addPortfolios($portfolio_ids);
            }

            $conn->commit();
            return $combination_id;

        } catch (\Exception $e) {
            if ($conn && $conn->inTransaction()) {
                $conn->rollBack();
            }
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Update combination
     * @param array $data
     * @param array $portfolio_ids
     * @return bool|null
     */
    public function update(array $data, array $portfolio_ids = []) {
        if (empty($this->combination_id)) {
            return false;
        }

        try {
            $conn = PDOConn::getInstance();
            $conn->beginTransaction();

            // Update combination
            $sql = "UPDATE `" . CONST_TBL_PREFIX . "portfolio_combinations`
                    SET combination_name = :name, description = :desc,
                        updated_at = :updated_at
                    WHERE combination_id = :id";

            $stmt = PDOConn::query($sql, [
                ':name' => $data['combination_name'],
                ':desc' => $data['description'] ?? '',
                ':updated_at' => date('Y-m-d H:i:s'),
                ':id' => $this->combination_id
            ]);

            // Update portfolio mappings
            if (!empty($portfolio_ids)) {
                // Delete existing mappings
                $this->removeAllPortfolios();
                // Add new mappings
                $this->addPortfolios($portfolio_ids);
            }

            $conn->commit();
            return true;

        } catch (\Exception $e) {
            if ($conn && $conn->inTransaction()) {
                $conn->rollBack();
            }
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Add portfolios to combination
     * @param array $portfolio_ids
     * @return bool
     */
    public function addPortfolios(array $portfolio_ids) {
        if (empty($this->combination_id) || empty($portfolio_ids)) {
            return false;
        }

        $sql = "INSERT IGNORE INTO `" . CONST_TBL_PREFIX . "portfolio_combination_mapping`
                (combination_id, portfolio_id) VALUES ";

        $values = [];
        $params = [];

        foreach ($portfolio_ids as $idx => $pid) {
            $values[] = "(:cid, :pid{$idx})";
            $params[":pid{$idx}"] = $pid;
        }

        $params[':cid'] = $this->combination_id;
        $sql .= implode(', ', $values);

        try {
            PDOConn::query($sql, [], $params);
            return true;
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Remove all portfolios from combination
     * @return bool
     */
    public function removeAllPortfolios() {
        if (empty($this->combination_id)) {
            return false;
        }

        $sql = "DELETE FROM `" . CONST_TBL_PREFIX . "portfolio_combination_mapping`
                WHERE combination_id = :cid";

        try {
            PDOConn::query($sql, [], [':cid' => $this->combination_id]);
            return true;
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Get portfolios in combination
     * @return array|false
     */
    public function getPortfolios() {
        if (empty($this->combination_id)) {
            return false;
        }

        $sql = "SELECT p.*
                FROM `" . CONST_TBL_PREFIX . "portfolios` p
                JOIN `" . CONST_TBL_PREFIX . "portfolio_combination_mapping` pcm
                    ON p.portfolio_id = pcm.portfolio_id
                WHERE pcm.combination_id = :cid
                ORDER BY p.portfolio_name";

        try {
            $stmt = PDOConn::query($sql, [], [':cid' => $this->combination_id]);
            $data = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $data[] = $row;
            }
            return $data;
        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Get portfolio IDs in combination
     * @return array|false
     */
    public function getPortfolioIds() {
        $portfolios = $this->getPortfolios();
        if ($portfolios === false) {
            return false;
        }
        return array_column($portfolios, 'portfolio_id');
    }
}
```

---

### 6.3 Transaction & Data Import Module

#### Class: DataImporter.php

```php
<?php
namespace eBizIndia;

use PhpOffice\PhpSpreadsheet\IOFactory;

class DataImporter {
    private $file_path;
    private $file_type;
    private $validation_errors = [];
    private $records_processed = 0;

    public function __construct(string $file_path, string $file_type) {
        $this->file_path = $file_path;
        $this->file_type = $file_type;
    }

    /**
     * Validate file structure and data
     * @return array ['valid' => bool, 'errors' => array, 'data' => array]
     */
    public function validate() {
        $result = ['valid' => true, 'errors' => [], 'data' => []];

        try {
            if ($this->file_type === 'csv') {
                $data = $this->parseCSV();
            } else {
                $data = $this->parseExcel();
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
            foreach ($data as $idx => $row) {
                $row_num = $idx + 2; // +2 for header and 0-index

                // Validate transaction date
                if (empty($row['transaction_date'])) {
                    $result['errors'][] = "Row {$row_num}: Transaction date is required";
                    $result['valid'] = false;
                }

                // Validate portfolio
                if (empty($row['portfolio_name'])) {
                    $result['errors'][] = "Row {$row_num}: Portfolio name is required";
                    $result['valid'] = false;
                }

                // Validate stock
                if (empty($row['stock_code']) || empty($row['stock_name'])) {
                    $result['errors'][] = "Row {$row_num}: Stock code and name are required";
                    $result['valid'] = false;
                }

                // Validate transaction type
                if (!in_array(strtoupper($row['transaction_type']), ['BUY', 'SELL'])) {
                    $result['errors'][] = "Row {$row_num}: Invalid transaction type. Must be BUY or SELL";
                    $result['valid'] = false;
                }

                // Validate quantity and price
                if (!is_numeric($row['quantity']) || $row['quantity'] <= 0) {
                    $result['errors'][] = "Row {$row_num}: Invalid quantity";
                    $result['valid'] = false;
                }

                if (!is_numeric($row['price']) || $row['price'] <= 0) {
                    $result['errors'][] = "Row {$row_num}: Invalid price";
                    $result['valid'] = false;
                }
            }

            $result['data'] = $data;

        } catch (\Exception $e) {
            $result['valid'] = false;
            $result['errors'][] = 'File parsing error: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Import validated data to database
     * @param array $data
     * @return bool
     */
    public function import(array $data) {
        if (empty($data)) {
            return false;
        }

        try {
            $conn = PDOConn::getInstance();
            $conn->beginTransaction();

            $portfolio_cache = [];
            $transaction = new Transaction();

            foreach ($data as $row) {
                // Get or cache portfolio ID
                $portfolio_name = trim($row['portfolio_name']);

                if (!isset($portfolio_cache[$portfolio_name])) {
                    $portfolio_options = [
                        'filters' => [
                            ['field' => 'portfolio_name', 'value' => $portfolio_name]
                        ]
                    ];
                    $portfolios = Portfolio::getList($portfolio_options);

                    if (!empty($portfolios)) {
                        $portfolio_cache[$portfolio_name] = $portfolios[0]['portfolio_id'];
                    } else {
                        throw new \Exception("Portfolio not found: {$portfolio_name}");
                    }
                }

                $portfolio_id = $portfolio_cache[$portfolio_name];

                // Prepare transaction data
                $transaction_data = [
                    'portfolio_id' => $portfolio_id,
                    'transaction_date' => date('Y-m-d', strtotime($row['transaction_date'])),
                    'stock_code' => strtoupper(trim($row['stock_code'])),
                    'stock_name' => trim($row['stock_name']),
                    'instrument_type' => $row['instrument_type'] ?? 'Spot',
                    'transaction_type' => strtoupper($row['transaction_type']),
                    'quantity' => (float)$row['quantity'],
                    'price' => (float)$row['price'],
                    'transaction_value' => (float)$row['quantity'] * (float)$row['price'],
                    'source_file' => basename($this->file_path)
                ];

                // Insert transaction
                if (!$transaction->add($transaction_data)) {
                    throw new \Exception("Failed to insert transaction");
                }

                $this->records_processed++;
            }

            $conn->commit();
            return true;

        } catch (\Exception $e) {
            if ($conn && $conn->inTransaction()) {
                $conn->rollBack();
            }
            ErrorHandler::logError(['function' => __METHOD__], $e);
            return false;
        }
    }

    /**
     * Parse CSV file
     * @return array
     */
    private function parseCSV() {
        $data = [];

        if (($handle = fopen($this->file_path, 'r')) !== false) {
            $headers = fgetcsv($handle);
            $headers = array_map('trim', $headers);
            $headers = array_map('strtolower', $headers);
            $headers = array_map(function($h) {
                return str_replace([' ', '-'], '_', $h);
            }, $headers);

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) === count($headers)) {
                    $data[] = array_combine($headers, $row);
                }
            }
            fclose($handle);
        }

        return $data;
    }

    /**
     * Parse Excel file
     * @return array
     */
    private function parseExcel() {
        $data = [];

        try {
            $spreadsheet = IOFactory::load($this->file_path);
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
                if (count($row) === count($headers)) {
                    $data[] = array_combine($headers, $row);
                }
            }

        } catch (\Exception $e) {
            ErrorHandler::logError(['function' => __METHOD__], $e);
        }

        return $data;
    }

    /**
     * Get records processed count
     * @return int
     */
    public function getRecordsProcessed() {
        return $this->records_processed;
    }
}
```

---

## 7. Code Patterns & Standards

### 7.1 Naming Conventions

- **Classes:** PascalCase (e.g., `Portfolio`, `DataImporter`)
- **Methods:** camelCase (e.g., `getList()`, `addPortfolios()`)
- **Variables:** snake_case (e.g., `$portfolio_id`, `$transaction_date`)
- **Constants:** UPPERCASE_SNAKE_CASE (e.g., `CONST_TBL_PREFIX`)
- **Database tables:** lowercase_snake_case (e.g., `portfolios`, `portfolio_combinations`)
- **Database columns:** lowercase_snake_case (e.g., `portfolio_id`, `created_at`)

### 7.2 Error Handling

Always use try-catch blocks for database operations:

```php
try {
    $stmt = PDOConn::query($sql, $str_params, $int_params);
    // Process results
} catch (\Exception $e) {
    ErrorHandler::logError([
        'function' => __METHOD__,
        'sql' => $sql,
        'params' => $str_params
    ], $e);
    return false;
}
```

### 7.3 Audit Trail

Always include audit fields in INSERT/UPDATE:

```php
// INSERT
$data['created_at'] = date('Y-m-d H:i:s');
$data['created_by'] = $GLOBALS['loggedindata'][0]['id'] ?? null;
$data['created_from'] = \eBizIndia\getRemoteIP();

// UPDATE
$data['updated_at'] = date('Y-m-d H:i:s');
$data['updated_by'] = $GLOBALS['loggedindata'][0]['id'] ?? null;
$data['updated_from'] = \eBizIndia\getRemoteIP();
```

### 7.4 SQL Injection Prevention

Always use parameterized queries:

```php
// GOOD
$sql = "SELECT * FROM table WHERE id = :id";
PDOConn::query($sql, [], [':id' => $id]);

// BAD - Never do this
$sql = "SELECT * FROM table WHERE id = $id";
```

### 7.5 Permission Checking

Check permissions before every operation:

```php
if ($can_edit === false) {
    $result['error_code'] = 403;
    $result['message'] = "Sorry, you are not authorized.";
    echo json_encode($result);
    exit;
}
```

---

## 8. Testing Strategy

### 8.1 Unit Testing

Test each class method individually:

```php
// Example test for Portfolio::add()
public function testPortfolioAdd() {
    $portfolio = new \eBizIndia\Portfolio();
    $data = [
        'portfolio_name' => 'Test Portfolio',
        'portfolio_type' => 'Own',
        'status' => 'Active'
    ];
    $id = $portfolio->add($data);
    assert($id > 0, 'Portfolio creation failed');
}
```

### 8.2 Integration Testing

Test complete workflows:

1. Create portfolio
2. Upload transaction file
3. Validate data is imported
4. Check holdings are calculated
5. Verify dashboard displays correctly

### 8.3 Performance Testing

Test with large datasets:

- 10,000+ transactions
- 100+ portfolios
- Multiple concurrent users

### 8.4 Security Testing

- SQL injection attempts
- XSS attempts
- CSRF token validation
- Permission bypass attempts
- File upload validation bypass

---

## 9. Deployment Plan

### 9.1 Pre-Deployment Checklist

- [ ] All tables created in database
- [ ] Menu entries added with proper permissions
- [ ] File permissions set correctly
- [ ] Upload directories created
- [ ] Backup configured
- [ ] PHP version verified (8.4+)
- [ ] Required PHP extensions installed
- [ ] Error logging configured

### 9.2 Deployment Steps

1. **Backup existing system**
   ```bash
   mysqldump -u user -p database > backup_$(date +%Y%m%d).sql
   tar -czf code_backup_$(date +%Y%m%d).tar.gz /path/to/code
   ```

2. **Upload new files**
   - Upload class files to `/cls/`
   - Upload page files to root
   - Upload JS files to `/js/`
   - Upload templates

3. **Run database migration**
   ```bash
   mysql -u user -p database < includes/portfolio-schema.sql
   ```

4. **Set file permissions**
   ```bash
   chmod 755 /path/to/uploads/portfolio-data
   chmod 755 /path/to/uploads/portfolio-data/pending
   chmod 755 /path/to/uploads/portfolio-data/processed
   ```

5. **Test in staging environment**

6. **Deploy to production**

7. **Monitor logs for errors**

### 9.3 Post-Deployment

- Verify all features working
- Check error logs
- Monitor performance
- User training sessions
- Collect feedback

---

## Summary

This development plan provides a comprehensive roadmap for implementing the Portfolio Management System. The plan:

- Follows existing codebase patterns and conventions
- Breaks down work into manageable phases
- Provides detailed class and method specifications
- Includes proper error handling and security measures
- Ensures code quality through testing
- Plans for smooth deployment

**Next Steps:**

1. Review and approve this plan
2. Begin Phase 1 implementation
3. Set up regular progress reviews
4. Follow the timeline for 15-week completion

---

**Document Control**

- **Created:** October 31, 2025
- **Author:** Development Team
- **Status:** Draft for Review
- **Next Review:** Start of each phase
