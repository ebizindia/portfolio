<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Portfolio Dashboard</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="btn-group" role="group">
                                <a href="dashboard-consolidated.php" class="btn btn-outline-primary">
                                    <i class="fas fa-chart-line"></i> Consolidated View
                                </a>
                                <a href="dashboard-combination.php" class="btn btn-outline-primary">
                                    <i class="fas fa-layer-group"></i> Combination View
                                </a>
                                <a href="dashboard-individual.php" class="btn btn-outline-primary">
                                    <i class="fas fa-folder"></i> Individual Portfolio
                                </a>
                                <a href="holdings.php" class="btn btn-outline-primary">
                                    <i class="fas fa-list"></i> Holdings
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Consolidated View</h5>
                                    <p class="card-text">View all portfolios together with combined metrics and performance</p>
                                    <a href="dashboard-consolidated.php" class="btn btn-primary">Open</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Combination View</h5>
                                    <p class="card-text">Analyze specific portfolio combinations and their breakdown</p>
                                    <a href="dashboard-combination.php" class="btn btn-primary">Open</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Individual Portfolio</h5>
                                    <p class="card-text">Deep dive into individual portfolio performance and holdings</p>
                                    <a href="dashboard-individual.php" class="btn btn-primary">Open</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5>Quick Stats</h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="card border-primary">
                                        <div class="card-body">
                                            <h6 class="text-muted">Total Portfolios</h6>
                                            <h3><?php echo count($portfolios ?? []); ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card border-success">
                                        <div class="card-body">
                                            <h6 class="text-muted">Combinations</h6>
                                            <h3><?php echo count($combinations ?? []); ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card border-info">
                                        <div class="card-body">
                                            <h6 class="text-muted">Quick Links</h6>
                                            <a href="portfolios.php" class="btn btn-sm btn-outline-primary">Manage Portfolios</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card border-warning">
                                        <div class="card-body">
                                            <h6 class="text-muted">Reports</h6>
                                            <a href="reports.php" class="btn btn-sm btn-outline-primary">Generate Reports</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
