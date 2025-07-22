# School Manager Lite - Import/Export Enhancements

## Overview
This document details the enhancements made to the School Manager Lite plugin's import/export functionality, including chunked imports, test CSV generation, and improved error handling.

## Features

### 1. Test CSV Generation
- Generate test CSV files with sample student or teacher data
- Configurable record counts (50, 100, 250, 500, 1000)
- Immediate download via secure transient system
- No files stored permanently on server

### 2. Chunked Import System
- Handles large CSV files with thousands of records
- Configurable chunk sizes (10-100 records per chunk)
- Real-time progress tracking with visual feedback
- Memory-efficient processing
- Automatic retry on failure

### 3. Enhanced Error Handling
- Detailed error messages with context
- Visual feedback for all operations
- Debug logging for troubleshooting
- Nonce verification for security

## Usage

### Generating Test CSV Files
1. Navigate to **School Manager → Import/Export**
2. Select record count from dropdown
3. Click "Generate Test Student CSV" or "Generate Test Teacher CSV"
4. File will download automatically

### Importing Large CSV Files
1. Navigate to **School Manager → Import/Export**
2. Select import type (Students/Teachers)
3. Choose CSV file to import
4. (Optional) Adjust chunk size (default: 25)
5. Click "Start Chunked Import"
6. Monitor progress in real-time

## Technical Implementation

### File Structure
```
mu-plugins/
├── import-export-enhancement.php  # Main enhancement plugin
└── admin-menu-remover.php        # Admin menu cleanup
```

### AJAX Endpoints
- `generate_test_csv`: Generates test CSV files
- `start_chunked_import`: Starts the chunked import process
- `process_chunk`: Processes a single chunk of records
- `get_import_progress`: Gets current import progress

### Database Tables
- `wp_school_students`: Stores student data
- `wp_school_classes`: Manages class assignments
- `wp_options`: Stores import progress and temporary data

## Error Handling

### Common Issues
1. **CSV Generation Fails**
   - Check PHP memory limit (recommended: 512M+)
   - Verify write permissions in uploads directory
   - Check browser console for JavaScript errors

2. **Import Stalls**
   - Increase PHP max_execution_time
   - Reduce chunk size for better reliability
   - Check server error logs

3. **Permission Errors**
   - Ensure proper user capabilities
   - Verify nonce is valid and not expired
   - Check user role permissions

## Security
- All AJAX requests verify nonces
- User capability checks for all operations
- Input validation and sanitization
- No direct file system access for downloads
- Temporary data automatically cleaned up

## Performance
- Memory usage optimized for large imports
- Database queries optimized for batch operations
- Caching where appropriate
- Minimal impact on server resources

## Troubleshooting

### Debug Mode
Enable WordPress debug mode in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Checking Logs
Check the following log files for errors:
- `wp-content/debug.log`
- Web server error logs
- Browser console for JavaScript errors

## Support
For assistance, please contact the development team with:
- Error messages from logs
- Steps to reproduce the issue
- Screenshots if applicable
- CSV sample (if related to import issues)

## Version History
- 1.0.0 - Initial release with chunked import and test CSV generation
- 1.0.1 - Fixed nonce verification issues
- 1.0.2 - Enhanced error handling and UI feedback

---
*Documentation last updated: 2025-07-22*
