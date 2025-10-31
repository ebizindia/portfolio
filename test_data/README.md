# Test Data for Portfolio Management System

## Overview
This directory contains test data files for validating Phase 2 (Data Import Module) functionality.

## Files

### 1. `setup_test_portfolios.sql`
SQL script to create 3 sample portfolios required for testing.

**Usage:**
```bash
mysql -u username -p database_name < test_data/setup_test_portfolios.sql
```

**Creates:**
- **My Portfolio** - Personal/Own portfolio type (35 transactions)
- **PM Portfolio** - Portfolio Manager type (15 transactions)
- **AIF Portfolio** - Unlisted & AIF type (10 transactions)

### 2. `sample_transactions.csv`
Sample CSV file containing 60 realistic transactions for testing the import functionality.

**Contents:**
- 60 transactions across 3 portfolios
- 35+ different Indian equity stocks (RELIANCE, TCS, INFY, HDFCBANK, etc.)
- Date range: January 2024 - October 2024
- Mix of BUY and SELL transactions
- Realistic prices and quantities

**File Structure:**
```csv
transaction_date,portfolio_name,stock_code,stock_name,transaction_type,quantity,price,instrument_type,expiry_date,strike_price
2024-01-15,My Portfolio,RELIANCE,Reliance Industries Ltd,BUY,50,2450.50,Spot,,
...
```

## Quick Start Testing

### Step 1: Create Portfolios
```bash
# Option A: Using SQL script
mysql -u root -p portfolio_db < test_data/setup_test_portfolios.sql

# Option B: Using web interface
# Navigate to: http://yourserver/portfolios.php
# Manually create the 3 portfolios listed above
```

### Step 2: Import Transactions
1. Navigate to: `http://yourserver/data-upload.php`
2. Upload `test_data/sample_transactions.csv`
3. Wait for validation (should show: "Found 60 records ready to import")
4. Click "Import Data"
5. Verify: "Import completed. 60 records imported."

### Step 3: View Imported Data
1. Navigate to: `http://yourserver/transactions.php`
2. Should show 60 transactions
3. Test filters, sorting, and export functionality

## Data Distribution

### By Portfolio:
- **My Portfolio:** 35 transactions
- **PM Portfolio:** 15 transactions
- **AIF Portfolio:** 10 transactions

### By Sector:
- Banking: HDFCBANK, ICICIBANK, SBIN, AXISBANK, KOTAKBANK
- IT Services: TCS, INFY, WIPRO, TECHM, HCLTECH
- Energy: RELIANCE, ONGC, IOC, NTPC, POWERGRID
- Automotive: MARUTI, TATAMOTORS, M&M
- Pharmaceuticals: SUNPHARMA, DRREDDY, CIPLA
- FMCG: HINDUNILVR, ITC, NESTLEIND, ASIANPAINT
- Financials: BAJFINANCE
- Telecom: BHARTIARTL
- Infrastructure: LT, ADANIPORTS

### By Transaction Type:
- BUY: ~85% (51 transactions)
- SELL: ~15% (9 transactions)

## Validation Tests

The sample data is designed to test:
- ✅ Multiple portfolios
- ✅ Different stock codes
- ✅ BUY and SELL transactions
- ✅ Date range validation
- ✅ Quantity and price validation
- ✅ Transaction value calculation
- ✅ Duplicate detection (if re-imported)

## Creating Custom Test Data

### Required Columns:
1. `transaction_date` - YYYY-MM-DD or DD-MM-YYYY
2. `portfolio_name` - Must exist in portfolios table
3. `stock_code` - Stock symbol
4. `stock_name` - Company name
5. `transaction_type` - BUY or SELL
6. `quantity` - Positive decimal number
7. `price` - Positive decimal number

### Optional Columns:
8. `instrument_type` - Default: "Spot"
9. `expiry_date` - For derivatives
10. `strike_price` - For options

### Example:
```csv
transaction_date,portfolio_name,stock_code,stock_name,transaction_type,quantity,price
2024-10-31,My Portfolio,RELIANCE,Reliance Industries Ltd,BUY,100,2500.50
2024-10-31,My Portfolio,TCS,Tata Consultancy Services,BUY,50,3800.75
```

## Cleanup Test Data

To remove all test data after testing:

```sql
-- Remove imported transactions
DELETE FROM transactions
WHERE portfolio_id IN (
    SELECT portfolio_id FROM portfolios
    WHERE portfolio_name IN ('My Portfolio', 'PM Portfolio', 'AIF Portfolio')
);

-- Remove test portfolios
DELETE FROM portfolios
WHERE portfolio_name IN ('My Portfolio', 'PM Portfolio', 'AIF Portfolio');

-- Remove upload records
DELETE FROM file_uploads
WHERE file_name = 'sample_transactions.csv';
```

## Expected Results

### After Import:
- **Transactions Table:** 60 new records
- **File Uploads Table:** 1 record with status "Imported"
- **Upload History:** Shows successful import with 60 records

### Verification Queries:
```sql
-- Count transactions by portfolio
SELECT p.portfolio_name, COUNT(t.transaction_id) as transaction_count
FROM portfolios p
LEFT JOIN transactions t ON p.portfolio_id = t.portfolio_id
WHERE p.portfolio_name IN ('My Portfolio', 'PM Portfolio', 'AIF Portfolio')
GROUP BY p.portfolio_name;

-- Expected Output:
-- My Portfolio: 35
-- PM Portfolio: 15
-- AIF Portfolio: 10

-- Count by transaction type
SELECT transaction_type, COUNT(*) as count
FROM transactions
GROUP BY transaction_type;

-- Expected Output:
-- BUY: 51
-- SELL: 9
```

## Troubleshooting

### Portfolio Not Found Error
**Problem:** Validation fails with "Portfolio 'X' does not exist"
**Solution:** Run `setup_test_portfolios.sql` first to create portfolios

### Duplicate Transaction Warnings
**Problem:** Import shows duplicate warnings
**Solution:** Expected if re-importing same file. Use "Skip duplicates" option.

### File Upload Fails
**Problem:** File upload returns error
**Solution:**
- Check file permissions on `uploads/portfolio-data/` directory
- Verify file size < 10MB
- Ensure file format is CSV or Excel

## Related Documentation
- See `PHASE2_TESTING_GUIDE.md` for comprehensive testing instructions
- See `DEVELOPMENT_PLAN.md` for architecture and design details

---

**Last Updated:** October 31, 2024
**Phase:** 2 - Data Import Module
