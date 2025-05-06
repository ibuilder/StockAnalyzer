<?php
/**
 * Main entry point for AlphaVantage Stock Analyzer
 * 
 * This file handles user requests and renders the UI
 */

require_once 'config.php';
require_once 'functions.php';

// Initialize variables
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$sector = isset($_GET['sector']) ? $_GET['sector'] : 'all';
$priceCategory = isset($_GET['price']) ? $_GET['price'] : '9999';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : $config['default_sort'];
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'desc';
$refresh = isset($_GET['refresh']) && $_GET['refresh'] === '1';
$perPage = $config['records_per_page'];

// Calculate cache age if exists
$cacheAge = '';
$cacheFresh = false;
if (file_exists(CACHE_FILE)) {
    $cacheAgeSeconds = time() - filemtime(CACHE_FILE);
    $cacheAge = formatTime($cacheAgeSeconds);
    $cacheFresh = $cacheAgeSeconds < CACHE_EXPIRY;
}

// Check if we need to process data
if (!file_exists(CACHE_FILE) || $refresh) {
    $allData = processStockData(['refresh_cache' => true]);
} else {
    $allData = json_decode(file_get_contents(CACHE_FILE), true);
}

// Get sectors for filter dropdown
$sectors = ['all' => 'All Sectors'];
if (isset($allData['stocksBySector']) && !empty($allData['stocksBySector'])) {
    foreach ($allData['stocksBySector'] as $sectorName => $stocks) {
        $sectors[$sectorName] = $sectorName;
    }
}

// Get stock data based on filters
$stocks = [];

// First filter by price
if ($priceCategory !== '9999' && isset($allData['stocksByPrice'][$priceCategory])) {
    $stocks = $allData['stocksByPrice'][$priceCategory];
} else {
    $stocks = $allData['allStocks'];
}

// Then filter by sector
if ($sector !== 'all') {
    $stocks = array_filter($stocks, function($stock) use ($sector) {
        return $stock['sector'] === $sector;
    });
}

// Sort data based on selected criteria
$stocks = sortStockData(array_values($stocks), $sortBy, $sortOrder);

// Pagination
$total = count($stocks);
$offset = ($page - 1) * $perPage;
$paginatedStocks = array_slice($stocks, $offset, $perPage);

// Page title and description
$pageTitle = $config['app_name'];
$pageDescription = 'Analyze stocks with highest asset-to-debt ratio by sector and price';

// Determine if this is an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Only send the table data for AJAX requests
if ($isAjax) {
    include 'partials/stock_table.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="styles.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-chart-line me-2"></i><?= htmlspecialchars($config['app_name']) ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#aboutModal">About</a>
                    </li>
                </ul>
                <span class="navbar-text">
                    <?php if (!empty($cacheAge)): ?>
                        <span class="badge <?= $cacheFresh ? 'bg-success' : 'bg-warning' ?>">
                            Data age: <?= htmlspecialchars($cacheAge) ?>
                        </span>
                    <?php endif; ?>
                    <a href="?refresh=1" class="btn btn-sm btn-light ms-2">
                        <i class="fas fa-sync-alt me-1"></i>Refresh Data
                    </a>
                </span>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-4">
        <div class="row">
            <div class="col-lg-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h1 class="h5 mb-0">
                            <i class="fas fa-search me-2"></i>Stock Analysis Results
                        </h1>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="sectorFilter" class="form-label">Sector</label>
                                        <select id="sectorFilter" class="form-select filter-control">
                                            <?php foreach ($sectors as $sectorKey => $sectorName): ?>
                                                <option value="<?= htmlspecialchars($sectorKey) ?>" <?= $sector === $sectorKey ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($sectorName) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="priceFilter" class="form-label">Price Range</label>
                                        <select id="priceFilter" class="form-select filter-control">
                                            <?php foreach ($config['price_categories'] as $priceKey => $priceName): ?>
                                                <option value="<?= htmlspecialchars($priceKey) ?>" <?= $priceCategory === $priceKey ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($priceName) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="sortByFilter" class="form-label">Sort By</label>
                                        <select id="sortByFilter" class="form-select filter-control">
                                            <?php foreach ($config['sort_options'] as $sortKey => $sortName): ?>
                                                <option value="<?= htmlspecialchars($sortKey) ?>" <?= $sortBy === $sortKey ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($sortName) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="sortOrderFilter" class="form-label">Sort Order</label>
                                        <select id="sortOrderFilter" class="form-select filter-control">
                                            <option value="desc" <?= $sortOrder === 'desc' ? 'selected' : '' ?>>Highest First</option>
                                            <option value="asc" <?= $sortOrder === 'asc' ? 'selected' : '' ?>>Lowest First</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="stockTableContainer">
                            <?php include 'partials/stock_table.php'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- About Modal -->
    <div class="modal fade" id="aboutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">About <?= htmlspecialchars($config['app_name']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>This application analyzes stock data from Alpha Vantage to identify companies with the most favorable asset-to-debt ratios.</p>
                    <p>It helps investors find financially healthy companies by:</p>
                    <ul>
                        <li>Analyzing balance sheet data</li>
                        <li>Calculating asset-to-debt ratios</li>
                        <li>Filtering by market sector and price ranges</li>
                        <li>Sorting by various financial metrics</li>
                    </ul>
                    <p>Version: <?= htmlspecialchars($config['app_version']) ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Details Modal -->
    <div class="modal fade" id="stockDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="stockDetailsTitle">Stock Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="stockDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container text-center">
            <span class="text-muted">
                &copy; <?= date('Y') ?> <?= htmlspecialchars($config['app_name']) ?> | 
                Powered by <a href="https://www.alphavantage.co/" target="_blank">Alpha Vantage API</a>
            </span>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="scripts.js"></script>
</body>
</html>