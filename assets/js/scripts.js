/**
 * AlphaVantage Stock Analyzer
 * 
 * Main JavaScript functionality
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Apply Filters button
    const applyFiltersBtn = document.getElementById('applyFiltersBtn');
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', handleApplyFilters);
    }

    // Initialize stock details modal functionality
    initializeStockDetailsModal();
});

/**
 * Handle applying filters
 */
function handleApplyFilters() {
    // Get filter values
    const sector = document.getElementById('sectorFilter')?.value || 'all';
    const price = document.getElementById('priceFilter')?.value || '9999';
    const sortBy = document.getElementById('sortByFilter')?.value || 'asset_debt_ratio';
    const sortOrder = document.getElementById('sortOrderFilter')?.value || 'desc';
    
    // Show loading state
    const tableContainer = document.getElementById('stockTableContainer');
    if (tableContainer) {
        tableContainer.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Loading stock data...</p></div>';
    
        // Build query string
        const params = new URLSearchParams();
        params.append('sector', sector);
        params.append('price', price);
        params.append('sort', sortBy);
        params.append('order', sortOrder);
        
        // Fetch updated data
        fetch(`index.php?${params.toString()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(function(html) {
            tableContainer.innerHTML = html;
            
            // Update URL to reflect current filters
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('sector', sector);
            urlParams.set('price', price);
            urlParams.set('sort', sortBy);
            urlParams.set('order', sortOrder);
            urlParams.set('page', '1'); // Reset to first page
            
            const newUrl = window.location.pathname + '?' + urlParams.toString();
            window.history.pushState({ path: newUrl }, '', newUrl);
            
            // Re-initialize stock details after table update
            initializeStockDetailsModal();
        })
        .catch(function(error) {
            console.error('Error fetching stock data:', error);
            tableContainer.innerHTML = '<div class="alert alert-danger">Error loading data: ' + error.message + '</div>';
        });
    }
}

/**
 * Initialize stock details modal functionality
 */
function initializeStockDetailsModal() {
    const detailButtons = document.querySelectorAll('.stock-details-btn');
    const modalTitle = document.getElementById('stockDetailsTitle');
    const modalContent = document.getElementById('stockDetailsContent');
    
    if (!modalTitle || !modalContent) {
        return;
    }
    
    detailButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const symbol = this.getAttribute('data-symbol');
            
            // Update modal title
            modalTitle.textContent = 'Loading details for ' + symbol + '...';
            
            // Show loading spinner
            modalContent.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Loading stock details...</p></div>';
            
            // Fetch stock details
            fetch('partials/stock_details.php?symbol=' + encodeURIComponent(symbol))
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(function(html) {
                    modalContent.innerHTML = html;
                    modalTitle.textContent = symbol + ' Details';
                })
                .catch(function(error) {
                    console.error('Error fetching stock details:', error);
                    modalContent.innerHTML = '<div class="alert alert-danger">Error loading details: ' + error.message + '</div>';
                });
        });
    });
}

/**
 * Format a number with commas as thousands separators
 * 
 * @param {number} num - The number to format
 * @param {number} decimals - Number of decimal places (default: 0)
 * @return {string} Formatted number string
 */
function addCommas(num, decimals) {
    if (num === null || num === undefined || isNaN(num)) {
        return '0';
    }
    
    // Convert to string with fixed decimal places
    var str = parseFloat(num).toFixed(decimals || 0);
    
    // Split into integer and decimal parts
    var parts = str.split('.');
    var intPart = parts[0];
    var decPart = parts.length > 1 ? '.' + parts[1] : '';
    
    // Add commas to integer part
    var regex = /(\d+)(\d{3})/;
    while (regex.test(intPart)) {
        intPart = intPart.replace(regex, '$1,$2');
    }
    
    return intPart + decPart;
}

/**
 * Format currency value with dollar sign and commas
 * 
 * @param {number} value - The number to format
 * @param {number} decimals - Number of decimal places (default: 2)
 * @return {string} Formatted currency string
 */
function formatCurrency(value, decimals) {
    if (value === null || value === undefined || isNaN(value)) {
        return '$0.00';
    }
    return '$' + addCommas(value, decimals || 2);
}

/**
 * Format large numbers with K, M, B, T suffixes
 * 
 * @param {number} value - The number to format
 * @param {number} decimals - Number of decimal places (default: 1)
 * @return {string} Formatted number string
 */
function formatNumber(value, decimals) {
    if (value === null || value === undefined || isNaN(value)) {
        return '0';
    }
    
    decimals = decimals || 1;
    
    if (value === 0) {
        return '0';
    }
    
    var absValue = Math.abs(value);
    var sign = value < 0 ? '-' : '';
    var formatted;
    
    if (absValue >= 1e12) {
        formatted = sign + (absValue / 1e12).toFixed(decimals) + 'T';
    } else if (absValue >= 1e9) {
        formatted = sign + (absValue / 1e9).toFixed(decimals) + 'B';
    } else if (absValue >= 1e6) {
        formatted = sign + (absValue / 1e6).toFixed(decimals) + 'M';
    } else if (absValue >= 1e3) {
        formatted = sign + (absValue / 1e3).toFixed(decimals) + 'K';
    } else {
        formatted = sign + absValue.toFixed(decimals);
    }
    
    return formatted;
}
