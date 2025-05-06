<?php
/**
 * Stock table partial for AlphaVantage Stock Analyzer
 * 
 * This file displays the table of stock data
 */
?>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-light">
            <tr>
                <th>Symbol</th>
                <th>Name</th>
                <th>Sector</th>
                <th>Price</th>
                <th>Total Assets</th>
                <th>Total Debt</th>
                <th>Asset/Debt Ratio</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($paginatedStocks)): ?>
                <tr>
                    <td colspan="8" class="text-center">No stocks found matching the criteria.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($paginatedStocks as $stock): ?>
                    <tr>
                        <td class="fw-bold">
                            <a href="https://www.alphavantage.co/query?function=OVERVIEW&symbol=<?= htmlspecialchars($stock['symbol']) ?>&apikey=demo" 
                               target="_blank" class="text-decoration-none">
                                <?= htmlspecialchars($stock['symbol']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($stock['name']) ?></td>
                        <td>
                            <span class="badge bg-secondary">
                                <?= htmlspecialchars($stock['sector']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            $<?= number_format($stock['price'], 2) ?>
                        </td>
                        <td class="text-end">
                            $<?= formatNumber($stock['totalAssets'], 2) ?>
                        </td>
                        <td class="text-end">
                            $<?= formatNumber($stock['totalDebt'], 2) ?>
                        </td>
                        <td class="text-end fw-bold">
                            <?= number_format($stock['assetDebtRatio'], 2) ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary stock-details-btn" 
                                    data-bs-toggle="modal" data-bs-target="#stockDetailsModal" 
                                    data-symbol="<?= htmlspecialchars($stock['symbol']) ?>">
                                <i class="fas fa-chart-bar me-1"></i> Details
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if (!empty($paginatedStocks)): ?>
    <div class="d-flex justify-content-between align-items-center">
        <div>
            Showing <?= ($offset + 1) ?>-<?= min($offset + $perPage, $total) ?> of <?= $total ?> stocks
        </div>
        <?= getPaginationLinks(
            $total, 
            $page, 
            $perPage, 
            [
                'sector' => $sector,
                'price' => $priceCategory,
                'sort' => $sortBy,
                'order' => $sortOrder
            ]
        ) ?>
    </div>
<?php endif; ?>
