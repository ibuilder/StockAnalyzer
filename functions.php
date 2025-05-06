<?php
/**
 * Core functionality for AlphaVantage Stock Analyzer
 * 
 * This file contains all the functions for interacting with the Alpha Vantage API
 * and analyzing stock data.
 */

require_once 'config.php';

/**
 * Initialize a cURL client for API requests
 * 
 * @return resource cURL handle
 */
function createClient() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    return $ch;
}

/**
 * Fetch data from Alpha Vantage API
 * 
 * @param string $url API endpoint URL
 * @param resource $ch cURL handle
 * @return array|null JSON decoded response or null on error
 */
function fetchData($url, $ch) {
    curl_setopt($ch, CURLOPT_URL, $url);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        logError("cURL Error: " . curl_error($ch) . " for URL: $url");
        return null;
    }
    
    return json_decode($response, true);
}

/**
 * Get list of stock symbols from Alpha Vantage
 * 
 * @param string $apiKey Alpha Vantage API key
 * @param resource $ch cURL handle
 * @return array List of stock symbols
 */
function getStockList($apiKey, $ch) {
    global $config;
    
    logMessage("Fetching stock listing...");
    $url = "{$config['base_url']}?function=LISTING_STATUS&apikey={$apiKey}";
    
    curl_setopt($ch, CURLOPT_URL, $url);
    $response = curl_exec($ch);
    
    if (empty($response)) {
        logError("Could not retrieve stock listing");
        return [];
    }
    
    // Parse CSV response
    $lines = explode("\n", $response);
    $symbols = [];
    $headers = str_getcsv($lines[0]);
    
    // Find column indices
    $symbolIndex = array_search('symbol', $headers);
    $nameIndex = array_search('name', $headers);
    $typeIndex = array_search('type', $headers);
    
    if ($symbolIndex === false || $nameIndex === false || $typeIndex === false) {
        logError("Unexpected format in stock listing response");
        return [];
    }
    
    // Skip header line
    for ($i = 1; $i < count($lines); $i++) {
        $parts = str_getcsv($lines[$i]);
        if (isset($parts[$symbolIndex]) && !empty($parts[$symbolIndex])) {
            // Only include common stocks
            if (isset($parts[$typeIndex]) && $parts[$typeIndex] === 'Common Stock') {
                $symbols[] = [
                    'symbol' => $parts[$symbolIndex],
                    'name' => isset($parts[$nameIndex]) ? $parts[$nameIndex] : ''
                ];
            }
        }
    }
    
    logMessage("Retrieved " . count($symbols) . " stock symbols");
    return $symbols;
}

/**
 * Get company overview data
 * 
 * @param string $symbol Stock symbol
 * @param string $apiKey Alpha Vantage API key
 * @param resource $ch cURL handle
 * @return array|null Company overview data
 */
function getCompanyOverview($symbol, $apiKey, $ch) {
    global $config;
    
    logMessage("Fetching company overview for {$symbol}...");
    $url = "{$config['base_url']}?function=OVERVIEW&symbol={$symbol}&apikey={$apiKey}";
    return fetchData($url, $ch);
}

/**
 * Get balance sheet data
 * 
 * @param string $symbol Stock symbol
 * @param string $apiKey Alpha Vantage API key
 * @param resource $ch cURL handle
 * @return array|null Balance sheet data
 */
function getBalanceSheet($symbol, $apiKey, $ch) {
    global $config;
    
    logMessage("Fetching balance sheet for {$symbol}...");
    $url = "{$config['base_url']}?function=BALANCE_SHEET&symbol={$symbol}&apikey={$apiKey}";
    return fetchData($url, $ch);
}

/**
 * Get latest stock price
 * 
 * @param string $symbol Stock symbol
 * @param string $apiKey Alpha Vantage API key
 * @param resource $ch cURL handle
 * @return float|null Latest stock price
 */
function getLatestPrice($symbol, $apiKey, $ch) {
    global $config;
    
    logMessage("Fetching latest price for {$symbol}...");
    $url = "{$config['base_url']}?function=GLOBAL_QUOTE&symbol={$symbol}&apikey={$apiKey}";
    $data = fetchData($url, $ch);
    
    if (isset($data['Global Quote']) && isset($data['Global Quote']['05. price'])) {
        return floatval($data['Global Quote']['05. price']);
    }
    
    return null;
}

/**
 * Calculate asset to debt ratio
 * 
 * @param array $balanceSheet Balance sheet data
 * @return float|null Asset to debt ratio
 */
function calculateAssetDebtRatio($balanceSheet) {
    if (!isset($balanceSheet['annualReports']) || empty($balanceSheet['annualReports'])) {
        return null;
    }
    
    // Get the most recent annual report
    $report = $balanceSheet['annualReports'][0];
    
    // Check if necessary fields exist
    if (!isset($report['totalAssets']) || !isset($report['totalLiabilities'])) {
        return null;
    }
    
    $totalAssets = floatval($report['totalAssets']);
    $totalLiabilities = floatval($report['totalLiabilities']);
    
    // Avoid division by zero
    if ($totalLiabilities == 0) {
        return $totalAssets > 0 ? PHP_FLOAT_MAX : 0;
    }
    
    return $totalAssets / $totalLiabilities;
}

/**
 * Get total assets
 * 
 * @param array $balanceSheet Balance sheet data
 * @return float Total assets
 */
function getTotalAssets($balanceSheet) {
    if (!isset($balanceSheet['annualReports']) || empty($balanceSheet['annualReports'])) {
        return 0;
    }
    
    $report = $balanceSheet['annualReports'][0];
    
    if (!isset($report['totalAssets'])) {
        return 0;
    }
    
    return floatval($report['totalAssets']);
}

/**
 * Get total debt
 * 
 * @param array $balanceSheet Balance sheet data
 * @return float Total debt
 */
function getTotalDebt($balanceSheet) {
    if (!isset($balanceSheet['annualReports']) || empty($balanceSheet['annualReports'])) {
        return 0;
    }
    
    $report = $balanceSheet['annualReports'][0];
    
    // We'll use totalLiabilities as a measure of debt
    if (!isset($report['totalLiabilities'])) {
        return 0;
    }
    
    return floatval($report['totalLiabilities']);
}

/**
 * Process stock data and analyze financial metrics
 * 
 * @param array $options Processing options
 * @return array Analyzed stock data
 */
function processStockData($options = []) {
    global $config;
    
    // Default options
    $defaults = [
        'refresh_cache' => false,
        'limit' => $config['stock_limit'],
    ];
    
    $options = array_merge($defaults, $options);
    
    // Check if cache exists and is not expired
    if (!$options['refresh_cache'] && file_exists(CACHE_FILE)) {
        $cacheAge = time() - filemtime(CACHE_FILE);
        
        if ($cacheAge < CACHE_EXPIRY) {
            logMessage("Using cached data (age: " . formatTime($cacheAge) . ")");
            return json_decode(file_get_contents(CACHE_FILE), true);
        }
    }
    
    $apiKey = $config['api_key'];
    $maxRequestsPerMinute = $config['max_requests_per_minute'];
    $sleepTimeBetweenRequests = $config['sleep_time'];
    
    $ch = createClient();
    
    // Initialize results arrays
    $allStocks = [];
    $stocksBySector = [];
    $stocksByPrice = [
        '10' => [],
        '20' => [],
        '50' => [],
        '100' => [],
        '9999' => [] // All stocks
    ];
    
    // Get list of stocks
    $symbols = getStockList($apiKey, $ch);
    $symbols = array_slice($symbols, 0, $options['limit']);
    
    $requestsCount = 1; // Already made one request for the stock list
    
    foreach ($symbols as $index => $symbolData) {
        $symbol = $symbolData['symbol'];
        $progress = sprintf("%d/%d", $index + 1, count($symbols));
        logMessage("Processing {$progress}: {$symbol}");
        
        // Check if we need to pause for API rate limiting
        if ($requestsCount >= $maxRequestsPerMinute) {
            logMessage("Rate limit reached. Sleeping for {$sleepTimeBetweenRequests} seconds...");
            sleep($sleepTimeBetweenRequests);
            $requestsCount = 0;
        }
        
        // Get company overview
        $overview = getCompanyOverview($symbol, $apiKey, $ch);
        $requestsCount++;
        
        // Check if we need to pause for API rate limiting
        if ($requestsCount >= $maxRequestsPerMinute) {
            logMessage("Rate limit reached. Sleeping for {$sleepTimeBetweenRequests} seconds...");
            sleep($sleepTimeBetweenRequests);
            $requestsCount = 0;
        }
        
        // Get balance sheet
        $balanceSheet = getBalanceSheet($symbol, $apiKey, $ch);
        $requestsCount++;
        
        // Check for API errors or missing data
        if (isset($overview['Note']) || isset($balanceSheet['Note'])) {
            logMessage("API limit reached. Processing what we have so far.");
            break;
        }
        
        // Skip if missing critical data
        if (empty($overview) || empty($balanceSheet) || 
            !isset($overview['Sector']) || !isset($overview['Symbol']) || 
            !isset($overview['Name']) || !isset($balanceSheet['annualReports'])) {
            logMessage("Missing data for {$symbol}, skipping.");
            continue;
        }
        
        // Extract relevant data
        $name = $overview['Name'];
        $sector = !empty($overview['Sector']) ? $overview['Sector'] : 'Uncategorized';
        $price = 0;
        
        // Try to get price from different sources
        if (isset($overview['AnalystTargetPrice']) && !empty($overview['AnalystTargetPrice'])) {
            $price = floatval($overview['AnalystTargetPrice']);
        }
        
        // If price is still 0, get it from quote endpoint
        if ($price == 0) {
            if ($requestsCount >= $maxRequestsPerMinute) {
                logMessage("Rate limit reached. Sleeping for {$sleepTimeBetweenRequests} seconds...");
                sleep($sleepTimeBetweenRequests);
                $requestsCount = 0;
            }
            
            $price = getLatestPrice($symbol, $apiKey, $ch);
            $requestsCount++;
            
            if ($price === null) {
                $price = 0;
            }
        }
        
        $totalAssets = getTotalAssets($balanceSheet);
        $totalDebt = getTotalDebt($balanceSheet);
        $assetDebtRatio = calculateAssetDebtRatio($balanceSheet);
        $marketCap = isset($overview['MarketCapitalization']) ? floatval($overview['MarketCapitalization']) : 0;
        
        // Skip if we couldn't calculate the ratio
        if ($assetDebtRatio === null) {
            logMessage("Couldn't calculate asset/debt ratio for {$symbol}, skipping.");
            continue;
        }
        
        // Add to main results array
        $stockData = [
            'symbol' => $symbol,
            'name' => $name,
            'sector' => $sector,
            'price' => $price,
            'totalAssets' => $totalAssets,
            'totalDebt' => $totalDebt,
            'assetDebtRatio' => $assetDebtRatio,
            'marketCap' => $marketCap,
            'exchange' => isset($overview['Exchange']) ? $overview['Exchange'] : 'Unknown',
            'reportDate' => isset($balanceSheet['annualReports'][0]['fiscalDateEnding']) ? 
                           $balanceSheet['annualReports'][0]['fiscalDateEnding'] : 'Unknown'
        ];
        
        $allStocks[] = $stockData;
        
        // Add to sector-specific array
        if (!isset($stocksBySector[$sector])) {
            $stocksBySector[$sector] = [];
        }
        $stocksBySector[$sector][] = $stockData;
        
        // Add to price-specific arrays
        $stocksByPrice['9999'][] = $stockData; // All stocks
        if ($price > 0) {
            if ($price < 10) {
                $stocksByPrice['10'][] = $stockData;
            }
            if ($price < 20) {
                $stocksByPrice['20'][] = $stockData;
            }
            if ($price < 50) {
                $stocksByPrice['50'][] = $stockData;
            }
            if ($price < 100) {
                $stocksByPrice['100'][] = $stockData;
            }
        }
    }
    
    curl_close($ch);
    
    // Sort all arrays by asset/debt ratio (descending)
    usort($allStocks, function($a, $b) {
        return $b['assetDebtRatio'] <=> $a['assetDebtRatio'];
    });
    
    foreach ($stocksBySector as $sector => $stocks) {
        usort($stocksBySector[$sector], function($a, $b) {
            return $b['assetDebtRatio'] <=> $a['assetDebtRatio'];
        });
    }
    
    foreach ($stocksByPrice as $pricePoint => $stocks) {
        usort($stocksByPrice[$pricePoint], function($a, $b) {
            return $b['assetDebtRatio'] <=> $a['assetDebtRatio'];
        });
    }
    
    // Prepare final results
    $results = [
        'allStocks' => $allStocks,
        'stocksBySector' => $stocksBySector,
        'stocksByPrice' => $stocksByPrice,
        'timestamp' => time()
    ];
    
    // Cache results
    file_put_contents(CACHE_FILE, json_encode($results, JSON_PRETTY_PRINT));
    
    logMessage("Analysis complete. Results cached to " . CACHE_FILE);
    
    return $results;
}

/**
 * Sort stock data by specified criterion
 * 
 * @param array $stocks Stock data array
 * @param string $sortBy Sort criterion
 * @param string $sortOrder Sort order (asc/desc)
 * @return array Sorted stock data
 */
function sortStockData($stocks, $sortBy = 'asset_debt_ratio', $sortOrder = 'desc') {
    if (empty($stocks)) {
        return [];
    }
    
    // Define sorting functions
    $sortFunctions = [
        'asset_debt_ratio' => function($a, $b) {
            return $a['assetDebtRatio'] <=> $b['assetDebtRatio'];
        },
        'total_assets' => function($a, $b) {
            return $a['totalAssets'] <=> $b['totalAssets'];
        },
        'lowest_debt' => function($a, $b) {
            return $a['totalDebt'] <=> $b['totalDebt'];
        },
        'price' => function($a, $b) {
            return $a['price'] <=> $b['price'];
        },
        'market_cap' => function($a, $b) {
            return $a['marketCap'] <=> $b['marketCap'];
        }
    ];
    
    if (!isset($sortFunctions[$sortBy])) {
        $sortBy = 'asset_debt_ratio';
    }
    
    // Sort the array
    usort($stocks, $sortFunctions[$sortBy]);
    
    // Reverse the array for descending order
    if ($sortOrder === 'desc') {
        $stocks = array_reverse($stocks);
    }
    
    return $stocks;
}

/**
 * Filter stock data by specified criteria
 * 
 * @param array $stocks Stock data
 * @param array $filters Filter criteria
 * @return array Filtered stock data
 */
function filterStockData($stocks, $filters = []) {
    if (empty($stocks) || empty($filters)) {
        return $stocks;
    }
    
    $results = [];
    
    foreach ($stocks as $stock) {
        $include = true;
        
        // Filter by sector
        if (isset($filters['sector']) && !empty($filters['sector']) && $filters['sector'] !== 'all') {
            if ($stock['sector'] !== $filters['sector']) {
                $include = false;
            }
        }
        
        // Filter by price
        if (isset($filters['price']) && !empty($filters['price']) && $filters['price'] !== '9999') {
            if ($stock['price'] >= floatval($filters['price'])) {
                $include = false;
            }
        }
        
        // Filter by min asset value
        if (isset($filters['min_assets']) && !empty($filters['min_assets'])) {
            if ($stock['totalAssets'] < floatval($filters['min_assets'])) {
                $include = false;
            }
        }
        
        // Filter by max debt value
        if (isset($filters['max_debt']) && !empty($filters['max_debt'])) {
            if ($stock['totalDebt'] > floatval($filters['max_debt'])) {
                $include = false;
            }
        }
        
        // Filter by min asset/debt ratio
        if (isset($filters['min_ratio']) && !empty($filters['min_ratio'])) {
            if ($stock['assetDebtRatio'] < floatval($filters['min_ratio'])) {
                $include = false;
            }
        }
        
        if ($include) {
            $results[] = $stock;
        }
    }
    
    return $results;
}

/**
 * Get unique sectors from stock data
 * 
 * @param array $stocks Stock data
 * @return array Unique sectors
 */
function getUniqueSectors($stocks) {
    $sectors = [];
    
    foreach ($stocks as $stock) {
        if (!in_array($stock['sector'], $sectors)) {
            $sectors[] = $stock['sector'];
        }
    }
    
    sort($sectors);
    return $sectors;
}

/**
 * Format large numbers for display
 * 
 * @param float $number Number to format
 * @param int $decimals Number of decimal places
 * @return string Formatted number
 */
function formatNumber($number, $decimals = 0) {
    if ($number == 0) {
        return '0';
    }
    
    $negative = $number < 0;
    $number = abs($number);
    
    if ($number >= 1e12) {
        return ($negative ? '-' : '') . number_format($number / 1e12, $decimals) . 'T';
    } else if ($number >= 1e9) {
        return ($negative ? '-' : '') . number_format($number / 1e9, $decimals) . 'B';
    } else if ($number >= 1e6) {
        return ($negative ? '-' : '') . number_format($number / 1e6, $decimals) . 'M';
    } else if ($number >= 1e3) {
        return ($negative ? '-' : '') . number_format($number / 1e3, $decimals) . 'K';
    } else {
        return ($negative ? '-' : '') . number_format($number, $decimals);
    }
}

/**
 * Format time difference
 * 
 * @param int $seconds Time in seconds
 * @return string Formatted time difference
 */
function formatTime($seconds) {
    if ($seconds < 60) {
        return $seconds . " seconds";
    } else if ($seconds < 3600) {
        return floor($seconds / 60) . " minutes";
    } else if ($seconds < 86400) {
        return floor($seconds / 3600) . " hours";
    } else {
        return floor($seconds / 86400) . " days";
    }
}

/**
 * Log message to console
 * 
 * @param string $message Message to log
 * @return void
 */
function logMessage($message) {
    $timestamp = date('[Y-m-d H:i:s]');
    error_log("{$timestamp} {$message}");
}

/**
 * Log error message
 * 
 * @param string $message Error message
 * @return void
 */
function logError($message) {
    $timestamp = date('[Y-m-d H:i:s]');
    error_log("{$timestamp} ERROR: {$message}");
}

/**
 * Get pagination links
 * 
 * @param int $total Total number of items
 * @param int $page Current page
 * @param int $perPage Items per page
 * @param array $params URL parameters
 * @return string HTML for pagination links
 */
function getPaginationLinks($total, $page, $perPage, $params = []) {
    $totalPages = ceil($total / $perPage);
    
    if ($totalPages <= 1) {
        return '';
    }
    
    // Build query string
    $queryParams = [];
    foreach ($params as $key => $value) {
        if ($key !== 'page') {
            $queryParams[] = urlencode($key) . '=' . urlencode($value);
        }
    }
    $queryString = !empty($queryParams) ? '&' . implode('&', $queryParams) : '';
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($page > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="?page=' . ($page - 1) . $queryString . '">Previous</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><a class="page-link">Previous</a></li>';
    }
    
    // Page numbers
    $startPage = max(1, $page - 2);
    $endPage = min($totalPages, $page + 2);
    
    if ($startPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="?page=1' . $queryString . '">1</a></li>';
        if ($startPage > 2) {
            $html .= '<li class="page-item disabled"><a class="page-link">...</a></li>';
        }
    }
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i === $page) {
            $html .= '<li class="page-item active"><a class="page-link">' . $i . '</a></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="?page=' . $i . $queryString . '">' . $i . '</a></li>';
        }
    }
    
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><a class="page-link">...</a></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . $queryString . '">' . $totalPages . '</a></li>';
    }
    
    // Next button
    if ($page < $totalPages) {
        $html .= '<li class="page-item"><a class="page-link" href="?page=' . ($page + 1) . $queryString . '">Next</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><a class="page-link">Next</a></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}
