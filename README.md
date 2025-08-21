# Grimpsa Cotizaciones Component

A Joomla 4/5 component for managing quotes/cotizations with Odoo integration.

## Overview

This component allows sales agents to:
- View their assigned quotes from Odoo
- Create new quotes
- Edit existing quotes and their line items
- Search and filter quotes by status and client name

## Features

- **Odoo Integration**: Connects to Odoo via XML-RPC API
- **User Authentication**: Requires user login to access quotes
- **Quote Management**: Create, edit, and view quotes
- **Line Items**: Add, edit, and delete quote line items
- **Search & Filter**: Search by client name and filter by quote status
- **Responsive Design**: Works on desktop and mobile devices
- **Accessibility**: WCAG compliant with proper focus indicators and screen reader support

## Installation

1. Install the component via Joomla's extension manager
2. Configure Odoo connection settings in the component configuration
3. Set up the API key and database credentials
4. Test the connection using the diagnostic tools

## Configuration

### Required Settings

- **Odoo URL**: The XML-RPC endpoint URL (e.g., `https://your-odoo.com/xmlrpc/2/object`)
- **Database**: Your Odoo database name
- **User ID**: The Odoo user ID for API authentication
- **Username**: The Odoo username (usually 'admin')
- **API Key**: The Odoo API key for authentication

### Optional Settings

- **Quotes per Page**: Number of quotes to display per page (default: 20)
- **Debug Mode**: Enable debug logging for troubleshooting

## Recent Fixes and Improvements

### Security Fixes
- ✅ Removed hardcoded API credentials from configuration
- ✅ Added proper input validation and sanitization
- ✅ Improved CSRF protection across all forms
- ✅ Added secure error handling without exposing sensitive information

### Error Handling Improvements
- ✅ Added missing Exception class imports across all files
- ✅ Implemented comprehensive try-catch blocks
- ✅ Added proper error logging and user-friendly error messages
- ✅ Improved cURL error handling with detailed diagnostics

### Form Validation Enhancements
- ✅ Enhanced client-side form validation with real-time feedback
- ✅ Added email and number field validation
- ✅ Implemented proper required field validation
- ✅ Added visual feedback for valid/invalid fields

### Accessibility Improvements
- ✅ Added skip links for screen readers
- ✅ Implemented proper focus indicators
- ✅ Added high contrast mode support
- ✅ Reduced motion support for users with vestibular disorders
- ✅ Improved keyboard navigation

### Code Quality Improvements
- ✅ Added proper timeout handling for API calls
- ✅ Implemented connection validation in constructor
- ✅ Added comprehensive input sanitization
- ✅ Improved responsive design for mobile devices

## File Structure

```
com_cotizaciones/
├── admin/                    # Administrator files
│   ├── src/                 # PHP source files
│   ├── tmpl/                # Admin templates
│   └── language/            # Language files
├── site/                    # Site files
│   ├── src/                 # PHP source files
│   │   ├── Controller/      # MVC Controllers
│   │   ├── Model/          # MVC Models
│   │   ├── View/           # MVC Views
│   │   ├── Helper/         # Helper classes
│   │   └── Service/        # Service classes
│   ├── tmpl/               # Site templates
│   └── language/           # Language files
├── media/                   # CSS, JS, and other assets
└── cotizaciones.xml        # Component manifest
```

## Usage

### For Sales Agents

1. **View Quotes**: Navigate to the component to see your assigned quotes
2. **Search**: Use the search box to find quotes by client name
3. **Filter**: Use the status filter to view quotes by state
4. **Create Quote**: Click "Nueva Cotización" to create a new quote
5. **Edit Quote**: Click on any quote number to edit it
6. **Add Lines**: Add product lines to quotes with descriptions and prices

### For Administrators

1. **Configure**: Set up Odoo connection in component configuration
2. **Test Connection**: Use the diagnostic tools to verify connectivity
3. **Monitor**: Check debug logs if issues arise

## Troubleshooting

### Common Issues

1. **Connection Failed**: Check Odoo URL and API credentials
2. **No Quotes Displayed**: Verify user has assigned clients in Odoo
3. **Form Validation Errors**: Ensure all required fields are completed
4. **Permission Denied**: Check user login status and permissions

### Debug Mode

Enable debug mode in component configuration to see detailed error messages and API responses.

## Requirements

- Joomla 4.0 or higher
- PHP 8.0 or higher
- cURL extension enabled
- Valid Odoo instance with API access

## Support

For support and issues, please contact the development team at admin@grimpsa.com

## License

GNU General Public License version 2 or later
