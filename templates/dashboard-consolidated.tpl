<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Consolidated Dashboard</h4>
                </div>
                <div class="card-body">
                    <!-- Filter Section -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label>End Date</label>
                            <input type="date" id="end_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label>Benchmark</label>
                            <select id="benchmark" class="form-control">
                                <option value="NIFTY50">NIFTY 50</option>
                                <option value="SENSEX">SENSEX</option>
                                <option value="NIFTY500">NIFTY 500</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>&nbsp;</label>
                            <button id="btn_refresh" class="btn btn-primary form-control">
                                <i class="fas fa-sync"></i> Refresh
                            </button>
                        </div>
                    </div>

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

                    <!-- Portfolio Summary Table -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5>Portfolio Summary</h5>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="portfolio_summary_table">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Portfolio Name</th>
                                            <th>Type</th>
                                            <th class="text-end">Invested</th>
                                            <th class="text-end">Current Value</th>
                                            <th class="text-end">Unrealized P&L</th>
                                            <th class="text-end">Realized P&L</th>
                                            <th class="text-end">Total P&L</th>
                                            <th class="text-end">P&L %</th>
                                        </tr>
                                    </thead>
                                    <tbody id="portfolio_summary_tbody">
                                        <tr>
                                            <td colspan="8" class="text-center">Loading...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Year-wise Comparison -->
                    <div class="row">
                        <div class="col-12">
                            <h5>Year-wise Comparison</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Year</th>
                                            <th class="text-end">Invested</th>
                                            <th class="text-end">Current Value</th>
                                            <th class="text-end">P&L</th>
                                            <th class="text-end">P&L %</th>
                                        </tr>
                                    </thead>
                                    <tbody id="yearwise_tbody">
                                        <tr>
                                            <td colspan="5" class="text-center">Loading...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">Portfolio Performance Chart</div>
                                <div class="card-body">
                                    <canvas id="performance_chart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">Portfolio Distribution</div>
                                <div class="card-body">
                                    <canvas id="distribution_chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/dashboard.js"></script>
<script src="js/dashboard-charts.js"></script>
