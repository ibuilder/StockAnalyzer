# AlphaVantage Stock Analyzer

A PHP-based web application for analyzing stocks based on their asset-to-debt ratio using the Alpha Vantage API.

## Features

- Find stocks with the highest asset-to-debt ratios
- Filter by market sector and price range
- Sort by various financial metrics
- Responsive design with Bootstrap 5
- Detailed stock information with visual indicators
- Client-side filtering without page reloads
- Data caching system to minimize API calls

## Requirements

- PHP 7.4 or higher
- cURL PHP Extension
- Alpha Vantage API Key

## Installation

1. Clone or download this repository to your web server
2. Create a `data` directory with write permissions for caching
3. Edit `config.php` to add your Alpha Vantage API key
4. Access the application through your web browser

```bash
# Example installation commands
git clone https://github.com/yourusername/alphavantage-stock-analyzer.git
cd alphavantage-stock-analyzer
mkdir data
chmod 755 data
# Edit config.php to add your API key
```

## Configuration

Edit the `config.php` file to customize the application:

- Set your Alpha Vantage API key
- Adjust API rate limiting settings
- Configure the number of stocks to analyze
- Customize price categories and sort options
- Set cache expiry time

## File Structure

```
alphavantage-stock-analyzer/
├── assets/
│   ├── css/
│   │   └── styles.css
│   └── js/
│       └── scripts.js
├── data/
│   └── stock_cache.json
├── partials/
│   ├── stock_details.php
│   └── stock_table.php
├── .htaccess
├── config.php
├── functions.php
├── index.php
└── README.md
```

## Usage

1. Access the application through your web browser
2. Use the filters to select specific market sectors and price ranges
3. Click the "Apply Filters" button to update the results based on your selections
4. Sort stocks by asset-to-debt ratio, total assets, or total debt
5. Click on the "Details" button to view more information about a stock
6. Click the "Refresh Data" button in the top navigation to fetch fresh data from the API

## API Rate Limiting

Alpha Vantage's free API tier has strict rate limits (typically 5 calls per minute and 500 calls per day). The application implements several strategies to work within these limits:

- Data caching with configurable expiry time
- Batch processing with sleep intervals between API calls
- Limiting the number of stocks analyzed

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments

- [Alpha Vantage](https://www.alphavantage.co/) for providing financial data API
- [Bootstrap](https://getbootstrap.com/) for the responsive UI framework
- [Font Awesome](https://fontawesome.com/) for icons

## About Asset-to-Debt Ratio

The asset-to-debt ratio (total assets divided by total liabilities) is a key financial metric that indicates a company's financial health. A higher ratio suggests the company has more assets relative to its debt, potentially indicating better financial stability.
