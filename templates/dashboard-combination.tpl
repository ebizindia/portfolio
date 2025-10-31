<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Combination Dashboard</h4>
                </div>
                <div class="card-body">
                    <!-- Combination Selector -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label>Select Combination</label>
                            <select id="combination_id" class="form-control">
                                <option value="">-- Select Combination --</option>
                                <?php if (!empty($combinations)): ?>
                                    <?php foreach ($combinations as $combination): ?>
                                        <option value="<?php echo $combination['combination_id']; ?>">
                                            <?php echo htmlspecialchars($combination['combination_name']); ?>
                                            (<?php echo $combination['portfolio_count'] ?? 0; ?> portfolios)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>&nbsp;</label>
                            <button id="btn_load" class="btn btn-primary form-control">
                                <i class="fas fa-search"></i> Load
                            </button>
                        </div>
                    </div>

                    <div id="combination_content" style="display: none;">
                        <!-- Metrics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted">Portfolios</h6>
                                        <h3 id="portfolio_count">0</h3>
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
                                        <h6 class="text-muted">Total P&L</h6>
                                        <h3 id="total_pl">₹0.00</h3>
                                        <small id="total_pl_pct" class="text-muted">(0.00%)</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted">Unrealized P&L</h6>
                                        <h3 id="unrealized_pl">₹0.00</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted">Realized P&L</h6>
                                        <h3 id="realized_pl">₹0.00</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted">XIRR</h6>
                                        <h3 id="xirr">0.00%</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Portfolio Breakdown -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5>Portfolio Breakdown</h5>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Portfolio Name</th>
                                                <th>Type</th>
                                                <th class="text-end">Invested</th>
                                                <th class="text-end">Current Value</th>
                                                <th class="text-end">Unrealized P&L</th>
                                                <th class="text-end">Realized P&L</th>
                                            </tr>
                                        </thead>
                                        <tbody id="breakdown_tbody">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Top Holdings -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5>Top 10 Holdings</h5>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Stock Code</th>
                                                <th>Stock Name</th>
                                                <th class="text-end">Quantity</th>
                                                <th class="text-end">Avg Price</th>
                                                <th class="text-end">Current Price</th>
                                                <th class="text-end">Current Value</th>
                                                <th class="text-end">P&L</th>
                                            </tr>
                                        </thead>
                                        <tbody id="holdings_tbody">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Charts -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">Portfolio Distribution</div>
                                    <div class="card-body">
                                        <canvas id="portfolio_chart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">Top Holdings</div>
                                    <div class="card-body">
                                        <canvas id="holdings_chart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="no_selection_msg" class="alert alert-info text-center">
                        <i class="fas fa-info-circle"></i> Please select a combination to view details
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/dashboard.js"></script>
<script src="js/dashboard-charts.js"></script>
