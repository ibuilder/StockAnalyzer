# Troubleshooting Guide

## Common Issues and Solutions

### MIME Type Errors

**Problem**: Browser console shows errors like:
```
Refused to apply style from 'https://yoursite.com/assets/css/styles.css' because its MIME type ('text/html') is not a supported stylesheet MIME type, and strict MIME checking is enabled.
```

**Solution**:

1. **Check File Existence**: 
   - Make sure the CSS and JS files exist in the correct directories:
   - `/assets/css/styles.css`
   - `/assets/js/scripts.js`

2. **Check Directory Structure**:
   - Create the directory structure exactly as shown below:
   ```
   /your-website-root/
   ├── assets/
   │   ├── css/
   │   │   └── styles.css
   │   └── js/
   │       └── scripts.js
   ├── data/
   ├── partials/
   │   ├── stock_details.php
   │   └── stock_table.php
   ├── .htaccess
   ├── config.php
   ├── functions.php
   ├── index.php
   └── README.md
   ```

3. **Fix Server MIME Type Configuration**:
   - Update your `.htaccess` file with the provided MIME type configuration
   - If using a different web server, update its configuration to serve `.css` files as `text/css` and `.js` files as `application/javascript`

4. **File Permissions**:
   - Ensure files have correct permissions (typically 644) to be readable by the web server
   - Ensure directories have correct permissions (typically 755)

5. **Restart Web Server**:
   - After making changes, restart your web server

### API Connection Issues

**Problem**: No data appears or API errors are shown.

**Solution**:

1. **Check API Key**:
   - Verify your Alpha Vantage API key in `config.php`
   - Test your API key with a simple curl request:
   ```bash
   curl "https://www.alphavantage.co/query?function=OVERVIEW&symbol=IBM&apikey=YOUR_API_KEY"
   ```

2. **API Rate Limits**:
   - Check if you've exceeded API rate limits (5 calls per minute for free tier)
   - Increase the `sleep_time` in config.php to add more delay between API calls

3. **Network Connectivity**:
   - Ensure your server can connect to the Alpha Vantage API
   - Check for any firewall restrictions

### Data Not Updating with Filters

**Problem**: Changing filters doesn't update the displayed data.

**Solution**:

1. **Check JavaScript Errors**:
   - Open browser console to see any JavaScript errors
   - Ensure jQuery and Bootstrap JS are properly loaded

2. **Use the Apply Filters Button**:
   - Click the "Apply Filters" button after making your selection

3. **Clear Browser Cache**:
   - Try clearing your browser cache and reloading the page

4. **Verify AJAX Functionality**:
   - Check if the AJAX request is working by looking at the Network tab in browser dev tools

### Permissions Issues with Cache

**Problem**: Error messages about not being able to write to cache file.

**Solution**:

1. **Check Directory Permissions**:
   - Ensure the `data` directory is writable by the web server:
   ```bash
   chmod 755 data
   ```

2. **Check File Permissions**:
   - If cache file already exists, make it writable:
   ```bash
   chmod 644 data/stock_cache.json
   ```

3. **Web Server User**:
   - Make sure the directory is owned by the web server user (e.g., www-data, apache, nginx)
   ```bash
   chown www-data:www-data data
   ```

## Additional Resources

- [Alpha Vantage API Documentation](https://www.alphavantage.co/documentation/)
- [PHP cURL Documentation](https://www.php.net/manual/en/book.curl.php)
- [Apache MIME Types Configuration](https://httpd.apache.org/docs/current/mod/mod_mime.html)

## Getting Support

If you continue to experience issues after trying the solutions above, please:

1. Check for any error messages in your PHP error log
2. Review the browser console for JavaScript errors
3. Open an issue on the project repository with detailed information about your problem
