/**
 * AlphaVantage Stock Analyzer
 * 
 * Main JavaScript functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // Filter controls
    const filterControls = document.querySelectorAll('.filter-control');
    filterControls.forEach(control => {
        control.addEventListener('change', applyFilters);
    });

    // Stock details modal
    initStockDetailsModal();
});

/**
 * Apply filters and reload the stock table
 */
function applyFilters() {
    // Get filter values
    const sector = document.getElementById('sectorFilter').value;
    const price = document.getElementById('priceFilter').value;
    const sortBy = document.getElementById('sortByFilter').value;
    const sortOrder = document.getElementById('sortOrderFilter').value;
    
    // Show loading state
    const tableContainer = document.getElementById('stockTableContainer');
    tableContainer.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Loading stock data...</p></div>';
    
    // Build query string
    const params = new URLSearchParams({
        sector: sector,
        price: price,
        sort: sortBy,
        order: sortOrder
    });
    
    // Fetch updated data
    fetch(`index.php?${params.toString()}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(html => {
        tableContainer.innerHTML = html;
        
        // Update URL to reflect current filters
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('sector', sector);
        urlParams.set('price', price);
        urlParams.set('sort', sortBy);
        urlParams.set('order', sortOrder);
        urlParams.set('page', '1'); // Reset to first page
        
        const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
        window.history.pushState({ path: newUrl }, '', newUrl);
        
        // Re-initialize stock details after table update
        initStockDetailsModal();
    })
    .catch(error => {
        console.error('Error fetching stock data:', error);
        tableContainer.innerHTML = `<div class="alert alert-danger">Error loading data: ${error.message}</div>`;
    });
}

/**
 * Initialize stock details modal functionality
 */
function initStockDetailsModal() {
    const detailButtons = document.querySelectorAll('.stock-details-btn');
    const modalTitle = document.getElementById('stockDetailsTitle');
    const modalContent = document.getElementById('stockDetailsContent');
    
    detailButtons.forEach(button => {
        button.addEventListener('click', function() {
            const symbol = this.getAttribute('data-symbol');
            
            // Update modal title
            modalTitle.textContent = `Loading details for ${symbol}...`;
            
            // Show loading spinner
            modalContent.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Loading stock details...</p></div>';
            
            // Fetch stock details
            fetch(`partials/stock_details.php?symbol=${symbol}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(html => {
                    modalContent.innerHTML = html;
                    modalTitle.textContent = `${symbol} Details`;
                })
                .catch(error => {
                    console.error('Error fetching stock details:', error);
                    modalContent.innerHTML = `<div class="alert alert-danger">Error loading details: ${error.message}</div>`;
                });
        });
    });
}

/**
 * Format currency value with commas and decimals
 * 
 * @param {number} value - The number to format
 * @param {number} decimals - Number of decimal places (default: 2)
 * @return {string} Formatted currency string
 */
function formatCurrency(value, decimals = 2) {
    if (isNaN(value) || value === null) {
        return '$0.00';
    }
    
    return ' + value.toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

/**
 * Format large numbers with K, M, B, T suffixes
 * 
 * @param {number} value - The number to format
 * @param {number} decimals - Number of decimal places (default: 1)
 * @return {string} Formatted number string
 */
function formatNumber(value, decimals = 1) {
    if (isNaN(value) || value === null) {
        return '0';
    }
    
    if (value === 0) {
        return '0';
    }
    
    const absValue = Math.abs(value);
    const sign = value < 0 ? '-' : '';
    
    if (absValue >= 1e12) {
        return sign + (absValue / 1e12).toFixed(decimals) + 'T';
    } else if (absValue >= 1e9) {
        return sign + (absValue / 1e9).toFixed(decimals) + 'B';
    } else if (absValue >= 1e6) {
        return sign + (absValue / 1e6).toFixed(decimals) + 'M';
    } else if (absValue >= 1e3) {
        return sign + (absValue / 1e3).toFixed(decimals) + 'K';
    } else {
        return sign + absValue.toFixed(decimals);
    }
}