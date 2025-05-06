<?php
/**
 * Stock details partial for AlphaVantage Stock Analyzer
 * 
 * This file displays detailed information about a single stock
 * for use in the modal popup
 */

require_once '../config.php';
require_once '../functions.php';

// Get stock symbol from request
$symbol = isset($_GET['symbol']) ? $_GET['symbol'] : '';

if (empty($symbol)) {
    echo '<div class="alert alert-danger">No stock symbol provided</div>';
    exit;
}

// Load cached data
if (!file_exists(CACHE_FILE)) {
    echo '<div class="alert alert-warning">No cached data available. Please refresh the main page.</div>';
    exit;
}

$allData = json_decode(file_get_contents(CACHE_FILE), true);

// Find the stock in the cached data
$stockData = null;
foreach ($allData['allStocks'] as $stock) {
    if ($stock['symbol'] === $symbol) {
        $stockData = $stock;
        break;
    }
}

if (!$stockData) {
    echo '<div class="alert alert-danger">Stock not found in cached data</div>';
    exit;
}

// Format dates
$reportDate = isset($stockData['reportDate']) ? $stockData['reportDate'] : 'N/A';
?>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <?= htmlspecialchars($stockData['name']) ?> (<?= htmlspecialchars($stockData['symbol']) ?>)
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tbody>
                        <tr>
                            <th>Exchange</th>
                            <td><?= htmlspecialchars($stockData['exchange']) ?></td>
                        </tr>
                        <tr>
                            <th>Sector</th>
                            <td><?= htmlspecialchars($stockData['sector']) ?></td>
                        </tr>
                        <tr>
                            <th>Current Price</th>
                            <td>$<?= number_format($stockData['price'], 2) ?></td>
                        </tr>
                        <tr>
                            <th>Market Cap</th>
                            <td>$<?= formatNumber($stockData['marketCap'], 2) ?></td>
                        </tr>
                        <tr>
                            <th>Report Date</th>
                            <td><?= htmlspecialchars($reportDate) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">Financial Health</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Asset/Debt Ratio</label>
                    <div class="progress">
                        <?php 
                        // Calculate a reasonable progress bar percentage
                        $ratio = $stockData['assetDebtRatio'];
                        $percentage = min(100, ($ratio / 5) * 100); // 5:1 ratio = 100% 
                        ?>
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: <?= $percentage ?>%" 
                             aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100">
                            <?= number_format($ratio, 2) ?>
                        </div>
                    </div>
                    <small class="text-muted">
                        Ratio of total assets to total debt. Higher is better.
                    </small>
                </div>
                
                <table class="table table-sm">
                    <tbody>
                        <tr>
                            <th>Total Assets</th>
                            <td>$<?= formatNumber($stockData['totalAssets'], 2) ?></td>
                        </tr>
                        <tr>
                            <th>Total Debt</th>
                            <td>$<?= formatNumber($stockData['totalDebt'], 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">External Resources</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <a href="https://www.alphavantage.co/query?function=OVERVIEW&symbol=<?= htmlspecialchars($symbol) ?>&apikey=demo" 
                           target="_blank" class="btn btn-outline-primary w-100">
                            <i class="fas fa-info-circle me-1"></i> Alpha Vantage Overview
                        </a>
                    </div>
                    <div class="col-md-4 mb-2">
                        <a href="https://finance.yahoo.com/quote/<?= htmlspecialchars($symbol) ?>" 
                           target="_blank" class="btn btn-outline-primary w-100">
                            <i class="fas fa-chart-line me-1"></i> Yahoo Finance
                        </a>
                    </div>
                    <div class="col-md-4 mb-2">
                        <a href="https://www.marketwatch.com/investing/stock/<?= htmlspecialchars($symbol) ?>" 
                           target="_blank" class="btn btn-outline-primary w-100">
                            <i class="fas fa-newspaper me-1"></i> MarketWatch
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
