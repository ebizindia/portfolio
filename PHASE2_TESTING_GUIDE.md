# Phase 2 Testing Guide - Data Import Module

## Overview
This guide provides step-by-step instructions for testing the Phase 2 Data Import Module functionality.

---

## Prerequisites

### 1. Database Setup
Ensure the following tables exist in your database:
- `portfolios`
- `portfolio_combinations`
- `portfolio_combination_mapping`
- `transactions`
- `file_uploads`

### 2. File Permissions
Verify upload directories exist with proper permissions:
```bash
chmod -R 755 /path/to/portfolio/uploads/portfolio-data/
```

### 3. PHP Extensions
Required PHP extensions:
- PDO
- PDO_MySQL
- (Optional) PhpSpreadsheet for Excel support

---

## Step 1: Create Sample Portfolios

Before importing transactions, you need to create portfolios in the system.

### Using the Web Interface:

1. **Navigate to Portfolios Page**
   - Go to: `http://yourserver/portfolios.php`

2. **Create Three Portfolios:**

   **Portfolio 1: My Portfolio**
   - Portfolio Name: `My Portfolio`
   - Portfolio Type: `Own`
   - Description: `Personal investment portfolio`
   - Status: `Active`

   **Portfolio 2: PM Portfolio**
   - Portfolio Name: `PM Portfolio`
   - Portfolio Type: `Portfolio Manager`
   - Description: `Portfolio managed by PM`
   - Status: `Active`

   **Portfolio 3: AIF Portfolio**
   - Portfolio Name: `AIF Portfolio`
   - Portfolio Type: `Unlisted & AIF`
   - Description: `Alternative Investment Fund portfolio`
   - Status: `Active`

### Using SQL (Alternative):

```sql
INSERT INTO `portfolios`
    (`portfolio_name`, `portfolio_type`, `description`, `status`, `created_at`, `created_by`, `created_from`)
VALUES
    ('My Portfolio', 'Own', 'Personal investment portfolio', 'Active', NOW(), 1, '127.0.0.1'),
    ('PM Portfolio', 'Portfolio Manager', 'Portfolio managed by PM', 'Active', NOW(), 1, '127.0.0.1'),
    ('AIF Portfolio', 'Unlisted & AIF', 'Alternative Investment Fund portfolio', 'Active', NOW(), 1, '127.0.0.1');
```

---

## Step 2: Prepare Test Data File

A sample CSV file has been created: **`sample_transactions.csv`**

### File Contents:
- **60 transactions** across 3 portfolios
- **35+ different stocks** (Indian equity market)
- Mix of **BUY and SELL** transactions
- Date range: **January 2024 to October 2024**
- Realistic stock prices and quantities

### File Structure:
```csv
transaction_date,portfolio_name,stock_code,stock_name,transaction_type,quantity,price,instrument_type,expiry_date,strike_price
2024-01-15,My Portfolio,RELIANCE,Reliance Industries Ltd,BUY,50,2450.50,Spot,,
...
```

### Creating Your Own Test File:

**Required Columns:**
1. `transaction_date` - Date in YYYY-MM-DD or DD-MM-YYYY format
2. `portfolio_name` - Must match existing portfolio (case-sensitive)
3. `stock_code` - Stock symbol (e.g., RELIANCE, TCS)
4. `stock_name` - Full company name
5. `transaction_type` - Either "BUY" or "SELL"
6. `quantity` - Positive number (can have decimals)
7. `price` - Positive number (price per share)

**Optional Columns:**
8. `instrument_type` - Default: "Spot" (for derivatives: "Future", "Option")
9. `expiry_date` - For futures/options (YYYY-MM-DD)
10. `strike_price` - For options (numeric)

---

## Step 3: Test File Upload

### 3.1 Access Upload Page
Navigate to: `http://yourserver/data-upload.php`

### 3.2 Upload the File

**Method 1: Drag and Drop**
1. Open file explorer and locate `sample_transactions.csv`
2. Drag the file over the upload area
3. Drop when the area highlights blue

**Method 2: Click to Browse**
1. Click anywhere in the upload area
2. Select `sample_transactions.csv` from file picker
3. Click "Open"

### 3.3 Monitor Upload Progress
You should see:
```
Uploading file... [30%]
Upload successful. Validating... [50%]
Validation successful! [100%]
```

### 3.4 Review Validation Results

**Success Case:**
- Green box with checkmark
- "Found 60 records ready to import"
- May show warnings for potential duplicates
- "Import Data" button becomes enabled

**Error Case:**
- Red box with X mark
- List of validation errors
- Common errors:
  - Missing required columns
  - Portfolio not found
  - Invalid transaction type
  - Invalid date format
  - Negative quantity/price

---

## Step 4: Test Data Import

### 4.1 Configure Import Options
- [x] **Skip duplicate transactions** - Recommended for first import

### 4.2 Import the Data
1. Click **"Import Data"** button
2. Confirm the import dialog
3. Wait for import completion

### 4.3 Expected Results
```
Import completed. 60 records imported.
```

If re-importing:
```
Import completed. 0 records imported, 60 duplicates skipped.
```

---

## Step 5: Verify Imported Data

### 5.1 Check Upload History
On the data-upload page, scroll to "Upload History" section:

| File Name | Upload Date | Status | Records |
|-----------|-------------|--------|---------|
| sample_transactions.csv | 31-Oct-2024 | Imported | 60 |

### 5.2 View Transactions
Navigate to: `http://yourserver/transactions.php`

**Expected:**
- Header shows: "Transactions (60)"
- Table displays all 60 transactions
- Transactions sorted by date (newest first)

### 5.3 Test Filters

**Filter by Portfolio:**
1. Click "Filters" button
2. Select "My Portfolio" from dropdown
3. Click "Apply"
4. Should show 35 transactions

**Filter by Transaction Type:**
1. Select "BUY" from Transaction Type dropdown
2. Click "Apply"
3. Should show only BUY transactions

**Filter by Date Range:**
1. Start Date: `2024-01-01`
2. End Date: `2024-03-31`
3. Click "Apply"
4. Should show Q1 2024 transactions only

**Clear Filters:**
1. Click "Clear" button
2. All transactions should appear again

### 5.4 Test Sorting
Click on column headers to sort:
- **Date** - Sort by transaction date
- **Portfolio** - Alphabetical by portfolio name
- **Stock Code** - Alphabetical by stock symbol
- **Type** - BUY transactions first or SELL first

### 5.5 Test Export
1. Apply desired filters (optional)
2. Click **"Export CSV"** button
3. File should download: `transactions_YYYYMMDDHHMMSS.csv`
4. Open in Excel/spreadsheet to verify data

---

## Step 6: Test Error Scenarios

### 6.1 Invalid File Format
**Test:** Upload a `.txt` file
**Expected:** Error message "Invalid file format. Only Excel (.xlsx, .xls) and CSV files are allowed."

### 6.2 Large File
**Test:** Upload a file > 10MB
**Expected:** Error message "File size exceeds 10MB limit."

### 6.3 Missing Columns
Create a test CSV without required columns:
```csv
transaction_date,stock_code,quantity
2024-01-01,RELIANCE,100
```
**Expected:** Validation error listing missing columns

### 6.4 Invalid Portfolio
Create a test CSV with non-existent portfolio:
```csv
transaction_date,portfolio_name,stock_code,stock_name,transaction_type,quantity,price
2024-01-01,NonExistent Portfolio,RELIANCE,Reliance,BUY,100,2500
```
**Expected:** Validation error "Portfolio 'NonExistent Portfolio' does not exist"

### 6.5 Invalid Transaction Type
```csv
transaction_date,portfolio_name,stock_code,stock_name,transaction_type,quantity,price
2024-01-01,My Portfolio,RELIANCE,Reliance,HOLD,100,2500
```
**Expected:** Validation error "Invalid transaction type 'HOLD'. Must be BUY or SELL"

### 6.6 Invalid Quantity/Price
```csv
transaction_date,portfolio_name,stock_code,stock_name,transaction_type,quantity,price
2024-01-01,My Portfolio,RELIANCE,Reliance,BUY,-100,2500
```
**Expected:** Validation error "Invalid quantity '-100'. Must be a positive number"

---

## Step 7: Test Duplicate Detection

### 7.1 Re-upload Same File
1. Upload `sample_transactions.csv` again
2. Should validate successfully
3. Import with "Skip duplicates" checked
4. Result: "0 records imported, 60 duplicates skipped"

### 7.2 Import with Duplicates Unchecked
1. Upload same file
2. Uncheck "Skip duplicate transactions"
3. Import
4. Result: All records imported again (database will have duplicates)

---

## Step 8: Test Delete Functionality

### 8.1 Delete Single Transaction
1. Go to transactions.php
2. Click delete (trash icon) on any transaction
3. Confirm deletion
4. Expected: "Transaction deleted successfully"
5. Verify transaction no longer appears in list

### 8.2 Delete Upload Record
1. Go to data-upload.php
2. In Upload History, click "Delete" on a record
3. Confirm deletion
4. Expected: Upload record removed from history

---

## Step 9: Performance Testing

### 9.1 Large File Import
Create a CSV with 1,000+ records:
- Monitor upload time
- Monitor validation time
- Monitor import time
- Check for timeouts or errors

### 9.2 Multiple Simultaneous Uploads
- Open data-upload.php in two browser tabs
- Upload different files simultaneously
- Verify both complete successfully

---

## Step 10: Database Verification

### 10.1 Check Transactions Table
```sql
SELECT COUNT(*) FROM transactions;
-- Expected: 60 (or more if tested multiple imports)

SELECT portfolio_id, COUNT(*) as count
FROM transactions
GROUP BY portfolio_id;
-- Should show distribution across 3 portfolios

SELECT transaction_type, COUNT(*) as count
FROM transactions
GROUP BY transaction_type;
-- Should show BUY vs SELL counts
```

### 10.2 Check File Uploads Table
```sql
SELECT * FROM file_uploads ORDER BY uploaded_at DESC;
-- Should show upload history with status
```

### 10.3 Verify Data Integrity
```sql
-- Check for correct transaction values
SELECT transaction_id, quantity, price, transaction_value,
       (quantity * price) as calculated_value
FROM transactions
WHERE ABS(transaction_value - (quantity * price)) > 0.01;
-- Should return 0 rows (all calculated correctly)

-- Check for invalid dates
SELECT * FROM transactions
WHERE transaction_date > CURDATE();
-- Should return 0 rows

-- Check for invalid quantities/prices
SELECT * FROM transactions
WHERE quantity <= 0 OR price <= 0;
-- Should return 0 rows
```

---

## Expected Test Results Summary

✅ **Successful Tests:**
- Portfolio creation (3 portfolios)
- CSV file upload (drag-and-drop and click)
- File validation (60 records validated)
- Data import (60 transactions imported)
- Transaction listing (60 records displayed)
- Filtering (by portfolio, type, date)
- Sorting (all columns)
- CSV export (download works)
- Duplicate detection (skips duplicates)
- Delete transaction (removes record)
- Delete upload record (removes from history)

❌ **Error Scenarios (Expected to Fail):**
- Invalid file format (.txt, .pdf, etc.)
- File too large (>10MB)
- Missing required columns
- Non-existent portfolio
- Invalid transaction type
- Invalid quantity/price values

---

## Troubleshooting

### Issue: Upload Fails Immediately
**Solution:**
- Check file permissions on uploads directory
- Verify max upload size in php.ini
- Check error logs

### Issue: Validation Takes Too Long
**Solution:**
- Check database connection
- Verify indexes on portfolio_name
- Check for portfolio table locks

### Issue: Import Fails with Database Error
**Solution:**
- Check transaction table structure
- Verify foreign key constraints
- Check database user permissions
- Review error logs

### Issue: Transactions Don't Appear
**Solution:**
- Check if import actually completed
- Verify file_uploads status is "Imported"
- Check transactions table directly via SQL
- Clear browser cache

### Issue: Upload History Empty
**Solution:**
- Check file_uploads table exists
- Verify database connection
- Check for JavaScript errors in console

---

## Test Data Summary

### Portfolios Required:
1. My Portfolio (35 transactions)
2. PM Portfolio (15 transactions)
3. AIF Portfolio (10 transactions)

### Stock Coverage:
- **Banking:** HDFCBANK, ICICIBANK, SBIN, AXISBANK, KOTAKBANK
- **IT:** TCS, INFY, WIPRO, TECHM, HCLTECH
- **Energy:** RELIANCE, ONGC, IOC, NTPC, POWERGRID
- **Auto:** MARUTI, TATAMOTORS, M&M
- **Pharma:** SUNPHARMA, DRREDDY, CIPLA
- **FMCG:** HINDUNILVR, ITC, NESTLEIND, ASIANPAINT
- **Others:** LT, BAJFINANCE, BHARTIARTL, ADANIPORTS

### Transaction Types:
- **BUY:** Majority of transactions
- **SELL:** Scattered throughout for testing

### Date Range:
- Start: January 15, 2024
- End: October 25, 2024
- Covers ~10 months of trading activity

---

## Next Steps After Testing

Once Phase 2 testing is complete:

1. **Document any bugs found** - Create issues for fixes needed
2. **Performance benchmarks** - Record import times for various file sizes
3. **User acceptance** - Get feedback from end users
4. **Move to Phase 3** - Begin implementation of Calculation Engine (FIFO, P&L, Performance Metrics)

---

## Support

For issues or questions:
- Check error logs in the system
- Review database for data integrity
- Consult DEVELOPMENT_PLAN.md for architecture details

---

**Document Version:** 1.0
**Last Updated:** October 31, 2024
**Phase:** 2 - Data Import Module
