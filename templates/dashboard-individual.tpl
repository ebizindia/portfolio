<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Individual Portfolio Dashboard</h4>
                </div>
                <div class="card-body">
                    <!-- Portfolio Selector -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label>Select Portfolio</label>
                            <select id="portfolio_id" class="form-control">
                                <option value="">-- Select Portfolio --</option>
                                <?php if (!empty($portfolios)): ?>
                                    <?php foreach ($portfolios as $portfolio): ?>
                                        <option value="<?php echo $portfolio['portfolio_id']; ?>">
                                            <?php echo htmlspecialchars($portfolio['portfolio_name']); ?>
                                            (<?php echo $portfolio['portfolio_type']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>End Date</label>
                            <input type="date" id="end_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label>&nbsp;</label>
                            <button id="btn_load" class="btn btn-primary form-control">
                                <i class="fas fa-search"></i> Load
                            </button>
                        </div>
                    </div>

                    <div id="portfolio_content" style="display: none;">
                        <!-- Metrics Cards -->
                        <div class="row mb-4">
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
                                        <h6 class="text-muted">Total P&L</h6>
                                        <h3 id="total_pl">₹0.00</h3>
                                        <small id="total_pl_pct" class="text-muted">(0.00%)</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted">XIRR</h6>
                                        <h3 id="xirr">0.00%</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted">Unrealized P&L</h6>
                                        <h3 id="unrealized_pl">₹0.00</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted">Realized P&L</h6>
                                        <h3 id="realized_pl">₹0.00</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Holdings Table -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5>Current Holdings</h5>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover" id="holdings_table">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Stock Code</th>
                                                <th>Stock Name</th>
                                                <th class="text-end">Quantity</th>
                                                <th class="text-end">Avg Cost</th>
                                                <th class="text-end">Total Invested</th>
                                                <th class="text-end">Current Price</th>
                                                <th class="text-end">Current Value</th>
                                                <th class="text-end">Unrealized P&L</th>
                                                <th class="text-end">P&L %</th>
                                            </tr>
                                        </thead>
                                        <tbody id="holdings_tbody">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Transaction History -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5>Recent Transactions</h5>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Date</th>
                                                <th>Stock Code</th>
                                                <th>Type</th>
                                                <th class="text-end">Quantity</th>
                                                <th class="text-end">Price</th>
                                                <th class="text-end">Value</th>
                                            </tr>
                                        </thead>
                                        <tbody id="transactions_tbody">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Charts -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">Portfolio Composition</div>
                                    <div class="card-body">
                                        <canvas id="composition_chart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">Performance Trend</div>
                                    <div class="card-body">
                                        <canvas id="performance_chart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="no_selection_msg" class="alert alert-info text-center">
                        <i class="fas fa-info-circle"></i> Please select a portfolio to view details
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/dashboard.js"></script>
<script src="js/dashboard-charts.js"></script>
