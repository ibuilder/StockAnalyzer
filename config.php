<?php
/**
 * Configuration file for AlphaVantage Stock Analyzer
 * 
 * This file contains all the configuration parameters for the application
 */

// Database of processed results (to avoid hitting API limits repeatedly)
define('CACHE_FILE', 'data/stock_cache.json');
define('CACHE_EXPIRY', 86400); // Cache expiry in seconds (24 hours)

// Directory structure
define('DATA_DIR', 'data');
if (!file_exists(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

// AlphaVantage API Configuration
$config = [
    // API Configuration
    'api_key' => 'YOUR_API_KEY', // Replace with your Alpha Vantage API key
    'base_url' => 'https://www.alphavantage.co/query',
    
    // API Rate Limiting
    'max_requests_per_minute' => 5, // Alpha Vantage free tier limit
    'sleep_time' => 13, // Sleep time in seconds between API calls
    
    // Application Settings
    'stock_limit' => 50, // Maximum number of stocks to process (adjust based on API tier)
    'default_sector' => 'All Sectors',
    
    // Price Categories
    'price_categories' => [
        '10' => 'Under $10',
        '20' => 'Under $20',
        '50' => 'Under $50',
        '100' => 'Under $100',
        '9999' => 'All Prices'
    ],
    
    // Analysis Criteria
    'sort_options' => [
        'asset_debt_ratio' => 'Highest Asset-to-Debt Ratio',
        'total_assets' => 'Highest Total Assets',
        'lowest_debt' => 'Lowest Total Debt',
        'price' => 'Lowest Price',
        'market_cap' => 'Highest Market Cap'
    ],
    
    // UI Settings
    'records_per_page' => 10,
    'default_sort' => 'asset_debt_ratio',
    'app_name' => 'AlphaVantage Stock Analyzer',
    'app_version' => '1.0.0'
];

// Environment settings
$environment = 'production'; // 'development' or 'production'
if ($environment === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// Session settings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
