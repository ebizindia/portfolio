-- ============================================================================
-- Setup Test Portfolios for Phase 2 Testing
-- ============================================================================
-- This script creates 3 sample portfolios for testing the data import module
-- Run this script before uploading the sample_transactions.csv file
-- ============================================================================

-- Portfolio 1: My Portfolio (Personal/Own)
INSERT INTO `portfolios`
    (`portfolio_name`, `portfolio_type`, `description`, `status`, `created_at`, `created_by`, `created_from`)
VALUES
    ('My Portfolio', 'Own', 'Personal investment portfolio for testing Phase 2 data import', 'Active', NOW(), 1, '127.0.0.1');

-- Portfolio 2: PM Portfolio (Portfolio Manager)
INSERT INTO `portfolios`
    (`portfolio_name`, `portfolio_type`, `description`, `status`, `created_at`, `created_by`, `created_from`)
VALUES
    ('PM Portfolio', 'Portfolio Manager', 'Portfolio managed by Portfolio Manager - testing Phase 2', 'Active', NOW(), 1, '127.0.0.1');

-- Portfolio 3: AIF Portfolio (Unlisted & AIF)
INSERT INTO `portfolios`
    (`portfolio_name`, `portfolio_type`, `description`, `status`, `created_at`, `created_by`, `created_from`)
VALUES
    ('AIF Portfolio', 'Unlisted & AIF', 'Alternative Investment Fund portfolio - testing Phase 2', 'Active', NOW(), 1, '127.0.0.1');

-- Verify portfolios were created
SELECT
    portfolio_id,
    portfolio_name,
    portfolio_type,
    status,
    created_at
FROM `portfolios`
WHERE portfolio_name IN ('My Portfolio', 'PM Portfolio', 'AIF Portfolio')
ORDER BY portfolio_name;

-- Expected Output:
-- 3 rows showing the created portfolios with Active status
