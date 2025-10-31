<?php
/**
 * Update Holdings Script
 *
 * This script recalculates holdings and realized P&L for all active portfolios
 * Should be run via cron job after transaction imports or periodically
 *
 * Usage:
 * php cron/update_holdings.php
 *
 * Or via cron:
 * 0 2 * * * /usr/bin/php /path/to/portfolio/cron/update_holdings.php >> /path/to/logs/holdings_update.log 2>&1
 */

// Include main application file
require_once dirname(__DIR__) . '/inc.php';

// Set time limit for long-running script
set_time_limit(300); // 5 minutes

// Log start
echo "[" . date('Y-m-d H:i:s') . "] Starting holdings update...\n";

try {
    // Recalculate all portfolios
    $result = \eBizIndia\FIFOCalculator::recalculateAll();

    echo "[" . date('Y-m-d H:i:s') . "] Update completed:\n";
    echo "  - Successfully updated: {$result['success']} portfolios\n";
    echo "  - Failed: {$result['failed']} portfolios\n";

    if (!empty($result['errors'])) {
        echo "  - Errors:\n";
        foreach ($result['errors'] as $error) {
            echo "    * {$error}\n";
        }
    }

    // Clear performance cache
    echo "[" . date('Y-m-d H:i:s') . "] Clearing performance cache...\n";
    $cache_pattern = sys_get_temp_dir() . '/portfolio_cache_*.json';
    $cache_files = glob($cache_pattern);
    $cleared = 0;
    foreach ($cache_files as $file) {
        if (unlink($file)) {
            $cleared++;
        }
    }
    echo "[" . date('Y-m-d H:i:s') . "] Cleared {$cleared} cache files\n";

    echo "[" . date('Y-m-d H:i:s') . "] Holdings update completed successfully\n";
    exit(0);

} catch (\Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
