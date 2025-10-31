<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-file-alt"></i> Generate Portfolio Reports</h4>
                </div>
                <div class="card-body">
                    <form id="report_form">
                        <!-- Report Type Selection -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h5>Select Report Type</h5>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="report_type" id="report_consolidated" value="consolidated" checked>
                                    <label class="form-check-label" for="report_consolidated">
                                        <strong>Consolidated Portfolio Report</strong> - All portfolios with summary metrics
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="report_type" id="report_combination" value="combination">
                                    <label class="form-check-label" for="report_combination">
                                        <strong>Combination Report</strong> - Specific portfolio combination analysis
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="report_type" id="report_individual" value="individual">
                                    <label class="form-check-label" for="report_individual">
                                        <strong>Individual Portfolio Report</strong> - Detailed report for single portfolio
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="report_type" id="report_holdings" value="holdings">
                                    <label class="form-check-label" for="report_holdings">
                                        <strong>Holdings Report</strong> - Current holdings across all/selected portfolios
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="report_type" id="report_transactions" value="transactions">
                                    <label class="form-check-label" for="report_transactions">
                                        <strong>Transaction History Report</strong> - All transactions with filters
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="report_type" id="report_realized_pl" value="realized_pl">
                                    <label class="form-check-label" for="report_realized_pl">
                                        <strong>Realized P&L Report</strong> - All realized profit/loss transactions
                                    </label>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Report Parameters -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h5>Report Parameters</h5>
                            </div>

                            <!-- Combination Selector -->
                            <div class="col-md-6 mb-3" id="combination_div" style="display: none;">
                                <label>Select Combination</label>
                                <select name="combination_id" id="combination_id" class="form-control">
                                    <option value="">-- Select Combination --</option>
                                    <?php if (!empty($combinations)): ?>
                                        <?php foreach ($combinations as $combination): ?>
                                            <option value="<?php echo $combination['combination_id']; ?>">
                                                <?php echo htmlspecialchars($combination['combination_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Portfolio Selector -->
                            <div class="col-md-6 mb-3" id="portfolio_div" style="display: none;">
                                <label>Select Portfolio</label>
                                <select name="portfolio_ids[]" id="portfolio_ids" class="form-control">
                                    <option value="">-- Select Portfolio --</option>
                                    <?php if (!empty($portfolios)): ?>
                                        <?php foreach ($portfolios as $portfolio): ?>
                                            <option value="<?php echo $portfolio['portfolio_id']; ?>">
                                                <?php echo htmlspecialchars($portfolio['portfolio_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Date Range -->
                            <div class="col-md-3 mb-3" id="date_range_div" style="display: none;">
                                <label>Start Date</label>
                                <input type="date" name="start_date" id="start_date" class="form-control">
                            </div>

                            <div class="col-md-3 mb-3" id="end_date_div">
                                <label>End Date</label>
                                <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>

                        <hr>

                        <!-- Format Selection -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h5>Select Format</h5>
                                <div class="btn-group" role="group">
                                    <input type="radio" class="btn-check" name="format" id="format_csv" value="csv" checked>
                                    <label class="btn btn-outline-primary" for="format_csv">
                                        <i class="fas fa-file-csv"></i> CSV
                                    </label>

                                    <input type="radio" class="btn-check" name="format" id="format_excel" value="excel">
                                    <label class="btn btn-outline-success" for="format_excel">
                                        <i class="fas fa-file-excel"></i> Excel
                                    </label>

                                    <input type="radio" class="btn-check" name="format" id="format_pdf" value="pdf">
                                    <label class="btn btn-outline-danger" for="format_pdf">
                                        <i class="fas fa-file-pdf"></i> PDF
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Generate Button -->
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" id="btn_generate" class="btn btn-primary btn-lg">
                                    <i class="fas fa-cog"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Report Preview/Download Area -->
                    <div id="report_result" class="mt-4" style="display: none;">
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle"></i> Report Generated Successfully!</h5>
                            <p class="mb-0">
                                <a href="#" id="download_link" class="btn btn-success" target="_blank">
                                    <i class="fas fa-download"></i> Download Report
                                </a>
                            </p>
                        </div>
                    </div>

                    <div id="report_error" class="mt-4" style="display: none;">
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle"></i> Error</h5>
                            <p class="mb-0" id="error_message"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Templates Info -->
            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Report Descriptions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-chart-line"></i> Consolidated Portfolio Report</h6>
                            <p class="small">Summary of all active portfolios with total invested, current value, P&L, and XIRR metrics.</p>

                            <h6><i class="fas fa-layer-group"></i> Combination Report</h6>
                            <p class="small">Detailed analysis of a specific portfolio combination including breakdown by portfolio and top holdings.</p>

                            <h6><i class="fas fa-folder"></i> Individual Portfolio Report</h6>
                            <p class="small">Comprehensive report for a single portfolio including all holdings, transactions, and performance metrics.</p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-list"></i> Holdings Report</h6>
                            <p class="small">Current holdings across all or selected portfolios with stock-wise details and P&L.</p>

                            <h6><i class="fas fa-exchange-alt"></i> Transaction History Report</h6>
                            <p class="small">Complete transaction history with date range filters, showing all buy and sell transactions.</p>

                            <h6><i class="fas fa-money-bill-wave"></i> Realized P&L Report</h6>
                            <p class="small">All realized profit/loss transactions from completed sell transactions.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/reports.js"></script>
