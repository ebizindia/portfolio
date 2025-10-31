# Phase 3 Quick Start Guide - Calculation Engine

## Overview
Phase 3 implements the core calculation engine for FIFO, P&L tracking, and performance metrics.

---

## Quick Test (5 Minutes)

### Prerequisites
- Phase 2 completed (transactions imported)
- Sample data from Phase 2 testing (60 transactions)

### Step 1: Run Holdings Update Script

```bash
cd /path/to/portfolio
php cron/update_holdings.php
```

**Expected Output:**
```
[2024-10-31 16:00:00] Starting holdings update...
[2024-10-31 16:00:01] Update completed:
  - Successfully updated: 3 portfolios
  - Failed: 0 portfolios
[2024-10-31 16:00:01] Clearing performance cache...
[2024-10-31 16:00:01] Cleared 0 cache files
[2024-10-31 16:00:01] Holdings update completed successfully
```

### Step 2: Verify Holdings Table

```sql
-- Check holdings were created
SELECT portfolio_id, stock_code, current_quantity, avg_cost_price, total_invested
FROM holdings
ORDER BY portfolio_id, stock_code
LIMIT 10;

-- Expected: Multiple rows showing current holdings with FIFO-calculated average costs
```

### Step 3: Check Realized P&L

```sql
-- Check realized P&L from SELL transactions
SELECT portfolio_id, stock_code, sell_date, quantity_sold, realized_pl
FROM realized_pl
ORDER BY sell_date DESC
LIMIT 10;

-- Expected: Rows for each SELL transaction with calculated P&L
```

### Step 4: Test Performance API

Create a test file: `test_api.php`

```php
<?php
require_once 'inc.php';

// Test getPerformance
$_POST['mode'] = 'getPerformance';
$_POST['portfolio_id'] = 1; // Replace with actual portfolio ID

require 'api/performance.php';
```

Run: `php test_api.php`

**Expected JSON Output:**
```json
{
  "error_code": 0,
  "message": "Performance metrics calculated successfully",
  "data": {
    "total_invested": 150000.00,
    "current_value": 165000.00,
    "unrealized_pl": 15000.00,
    "realized_pl": 5000.00,
    "total_pl": 20000.00,
    "xirr": 12.5,
    "roce": 13.33,
    "annualized_return": 11.8,
    "simple_return": 13.33,
    "days_invested": 289,
    "win_rate": 66.67
  }
}
```

---

## Core Components Testing

### 1. FIFO Calculator

**Test FIFO Logic:**
```php
<?php
require_once 'inc.php';

// Test for a specific stock
$calculator = new \eBizIndia\FIFOCalculator();
$result = $calculator->calculateFIFO(1, 'RELIANCE'); // portfolio_id, stock_code

print_r($result);
// Expected output shows:
// - current_quantity
// - avg_cost (FIFO weighted average)
// - total_invested
// - realized_pl array
```

**Test Scenarios:**
- ✓ Single BUY (should create holding)
- ✓ Multiple BUYs at different prices (should calculate weighted average)
- ✓ BUY then SELL (should calculate realized P&L using FIFO)
- ✓ Multiple BUYs then partial SELL (should match oldest first)

### 2. Holdings

**Get Portfolio Summary:**
```php
<?php
require_once 'inc.php';

$summary = \eBizIndia\Holding::getPortfolioSummary(1);
print_r($summary);

// Expected:
// Array(
//   [total_invested] => 150000.00
//   [current_value] => 165000.00
//   [unrealized_pl] => 15000.00
//   [unrealized_pl_pct] => 10.00
//   [holdings_count] => 15
// )
```

### 3. Realized P&L

**Get P&L Summary:**
```php
<?php
require_once 'inc.php';

$pl_summary = \eBizIndia\RealizedPL::getSummary(1);
print_r($pl_summary);

// Expected:
// Array(
//   [total_realized_pl] => 5000.00
//   [total_profit] => 6500.00
//   [total_loss] => 1500.00
//   [profitable_trades] => 4
//   [losing_trades] => 2
//   [total_trades] => 6
//   [win_rate] => 66.67
// )
```

### 4. XIRR Calculator

**Calculate XIRR:**
```php
<?php
require_once 'inc.php';

$xirr = \eBizIndia\XIRRCalculator::calculatePortfolioXIRR(1);
echo "XIRR: " . ($xirr * 100) . "%\n";

// Expected: XIRR between -50% to +100% (typically 5-30% for equity)
```

### 5. Performance Calculator

**Get All Metrics:**
```php
<?php
require_once 'inc.php';

$metrics = \eBizIndia\PerformanceCalculator::calculateAll(1);
print_r($metrics);

// Expected: Complete set of performance metrics
```

---

## API Endpoints Testing

### Using cURL

**1. Get Performance:**
```bash
curl -X POST http://yourserver/api/performance.php \
  -d "mode=getPerformance" \
  -d "portfolio_id=1" \
  -b "PHPSESSID=your_session_id"
```

**2. Get Holdings:**
```bash
curl -X POST http://yourserver/api/performance.php \
  -d "mode=getHoldings" \
  -d "portfolio_id=1" \
  -b "PHPSESSID=your_session_id"
```

**3. Recalculate (Admin only):**
```bash
curl -X POST http://yourserver/api/performance.php \
  -d "mode=recalculate" \
  -d "portfolio_id=1" \
  -b "PHPSESSID=your_session_id"
```

### Using JavaScript (AJAX)

```javascript
// Get performance metrics
$.ajax({
    url: 'api/performance.php',
    type: 'POST',
    data: {
        mode: 'getPerformance',
        portfolio_id: 1
    },
    success: function(response) {
        console.log(response);
        if (response.error_code === 0) {
            console.log('XIRR:', response.data.xirr + '%');
            console.log('Total P&L:', response.data.total_pl);
        }
    }
});
```

---

## Verification Queries

### Check FIFO Calculations

```sql
-- Verify holdings match transaction history
SELECT
    h.stock_code,
    h.current_quantity,
    h.avg_cost_price,
    h.total_invested,
    SUM(CASE WHEN t.transaction_type = 'BUY' THEN t.quantity ELSE -t.quantity END) as calc_quantity
FROM holdings h
JOIN transactions t ON h.portfolio_id = t.portfolio_id AND h.stock_code = t.stock_code
WHERE h.portfolio_id = 1
GROUP BY h.stock_code, h.current_quantity, h.avg_cost_price, h.total_invested;

-- current_quantity should match calc_quantity
```

### Check Realized P&L

```sql
-- Verify realized P&L calculations
SELECT
    rpl.stock_code,
    rpl.quantity_sold,
    rpl.sell_price,
    rpl.avg_buy_price,
    rpl.realized_pl,
    (rpl.sell_price - rpl.avg_buy_price) * rpl.quantity_sold as calc_pl
FROM realized_pl rpl
WHERE portfolio_id = 1;

-- realized_pl should match calc_pl
```

### Check Data Integrity

```sql
-- All holdings should have non-negative quantities
SELECT * FROM holdings WHERE current_quantity < 0;
-- Expected: 0 rows

-- All holdings should have positive cost
SELECT * FROM holdings WHERE avg_cost_price <= 0;
-- Expected: 0 rows

-- Total invested should match quantity * avg_cost
SELECT * FROM holdings
WHERE ABS(total_invested - (current_quantity * avg_cost_price)) > 0.01;
-- Expected: 0 rows
```

---

## Common Issues & Solutions

### Issue: Holdings table is empty after update
**Solution:**
- Check if transactions exist: `SELECT COUNT(*) FROM transactions;`
- Check for errors in logs
- Verify BUY transactions exist
- Run manually: `php cron/update_holdings.php`

### Issue: XIRR returns false/null
**Possible causes:**
- No transactions in portfolio
- Only BUY or only SELL transactions (need both)
- Portfolio value is 0
- Extreme return rates preventing convergence

**Solution:**
- Add more transactions with realistic data
- Ensure portfolio has current value > 0

### Issue: Realized P&L is 0 but SELLs exist
**Solution:**
- Check if SELL transactions have matching BUY transactions before them
- FIFO requires BUY before SELL chronologically
- Verify transaction dates are correct

### Issue: Performance API returns 401 Unauthorized
**Solution:**
- Ensure user is logged in
- Check session is valid
- Test with admin user

---

## Cron Job Setup

**Daily Update (2 AM):**
```bash
crontab -e

# Add this line:
0 2 * * * /usr/bin/php /path/to/portfolio/cron/update_holdings.php >> /var/log/holdings_update.log 2>&1
```

**After Every Import:**
```bash
# Run after data import
php cron/update_holdings.php
```

---

## Performance Optimization

### Enable Caching
Caching is enabled by default with 1-hour TTL.

**Clear cache manually:**
```php
\eBizIndia\PerformanceCalculator::clearCache($portfolio_id);
```

**Clear all cache:**
```bash
rm /tmp/portfolio_cache_*.json
```

### Database Indexes

Ensure these indexes exist:
```sql
-- Transactions
CREATE INDEX idx_portfolio_stock ON transactions(portfolio_id, stock_code, transaction_date);
CREATE INDEX idx_transaction_date ON transactions(transaction_date);

-- Holdings
CREATE INDEX idx_portfolio ON holdings(portfolio_id);

-- Realized P&L
CREATE INDEX idx_portfolio_date ON realized_pl(portfolio_id, sell_date);
```

---

## Next Steps

After successful Phase 3 testing:

1. **Verify all calculations** are accurate with known test cases
2. **Test with production data** (if available)
3. **Monitor performance** for large portfolios
4. **Proceed to Phase 4**: Combination Management Enhancement
5. **Proceed to Phase 5**: Dashboard Development

---

## Support

**Documentation:**
- DEVELOPMENT_PLAN.md - Architecture details
- PHASE2_TESTING_GUIDE.md - Data import testing

**Classes:**
- FIFOCalculator.php - FIFO logic
- Holding.php - Holdings management
- RealizedPL.php - P&L tracking
- XIRRCalculator.php - XIRR calculations
- PerformanceCalculator.php - Main metrics engine
- BenchmarkData.php - Benchmark comparison

**Key Metrics:**
- **XIRR**: Annualized return accounting for cash flow timing
- **ROCE**: Return on Capital Employed (simple return %)
- **Annualized Return**: Compounded annual growth rate
- **Win Rate**: % of profitable trades
- **Alpha**: Excess return vs benchmark

---

**Last Updated:** October 31, 2024
**Phase:** 3 - Calculation Engine
