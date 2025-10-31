<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Consolidated Holdings</h4>
                    <button id="btn_export" class="btn btn-light btn-sm">
                        <i class="fas fa-file-excel"></i> Export to Excel
                    </button>
                </div>
                <div class="card-body">
                    <!-- Filter Section -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label>View Type</label>
                            <select id="view_type" class="form-control">
                                <option value="all">All Holdings</option>
                                <option value="portfolio">By Portfolio</option>
                                <option value="combination">By Combination</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="portfolio_filter_div" style="display: none;">
                            <label>Select Portfolio</label>
                            <select id="filter_portfolio" class="form-control">
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
                        <div class="col-md-4" id="combination_filter_div" style="display: none;">
                            <label>Select Combination</label>
                            <select id="filter_combination" class="form-control">
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
                        <div class="col-md-2">
                            <label>&nbsp;</label>
                            <button id="btn_load" class="btn btn-primary form-control">
                                <i class="fas fa-search"></i> Load
                            </button>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="text-muted">Total Holdings</h6>
                                    <h3 id="total_holdings">0</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="text-muted">Total Invested</h6>
                                    <h3 id="total_invested">₹0.00</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="text-muted">Current Value</h6>
                                    <h3 id="current_value">₹0.00</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="text-muted">Unrealized P&L</h6>
                                    <h3 id="unrealized_pl">₹0.00</h3>
                                    <small id="unrealized_pl_pct" class="text-muted">(0.00%)</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search Bar -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <input type="text" id="search_box" class="form-control" placeholder="Search by stock code or name...">
                        </div>
                    </div>

                    <!-- Holdings Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-sm" id="holdings_table">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Stock Code</th>
                                            <th>Stock Name</th>
                                            <th>Portfolio</th>
                                            <th class="text-end">Quantity</th>
                                            <th class="text-end">Avg Cost Price</th>
                                            <th class="text-end">Total Invested</th>
                                            <th class="text-end">Current Price</th>
                                            <th class="text-end">Current Value</th>
                                            <th class="text-end">Unrealized P&L</th>
                                            <th class="text-end">P&L %</th>
                                            <th class="text-end">% of Portfolio</th>
                                        </tr>
                                    </thead>
                                    <tbody id="holdings_tbody">
                                        <tr>
                                            <td colspan="11" class="text-center">Loading...</td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="3">TOTAL</th>
                                            <th class="text-end" id="footer_quantity">0</th>
                                            <th></th>
                                            <th class="text-end" id="footer_invested">₹0.00</th>
                                            <th></th>
                                            <th class="text-end" id="footer_current">₹0.00</th>
                                            <th class="text-end" id="footer_pl">₹0.00</th>
                                            <th class="text-end" id="footer_pl_pct">0.00%</th>
                                            <th class="text-end">100.00%</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/holdings.js"></script>
