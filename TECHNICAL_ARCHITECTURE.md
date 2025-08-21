# Grimpsa Cotizaciones Component - Technical Architecture

## Overview

The **Grimpsa Cotizaciones Component** is a Joomla 4/5 extension that provides a comprehensive quote management system with Odoo ERP integration. It allows sales agents to create, edit, and manage quotes directly from Joomla while maintaining synchronization with Odoo's sales order system.

## Component Information

- **Name**: com_cotizaciones
- **Version**: 1.0.0
- **Joomla Compatibility**: 4.0+
- **PHP Version**: 8.0+
- **License**: GNU General Public License version 2 or later
- **Author**: Grimpsa
- **Namespace**: `Grimpsa\Component\Cotizaciones`

## Core Functionality

### Primary Features
1. **Quote Management**: Create, edit, and view sales quotes
2. **Odoo Integration**: Real-time synchronization with Odoo ERP
3. **User-Specific Access**: Sales agents see only their assigned clients
4. **Quote Line Items**: Add, edit, and delete product lines within quotes
5. **Search & Filtering**: Advanced search and filtering capabilities
6. **Responsive Design**: Mobile-friendly interface

### User Roles
- **Sales Agents**: Create and manage quotes for their assigned clients
- **Administrators**: Configure Odoo connection and component settings

## Technical Architecture

### File Structure

```
com_cotizaciones/
├── admin/                          # Administrator files
│   ├── forms/                      # Form definitions
│   │   ├── config.xml             # Component configuration form
│   │   └── cotizacion.xml         # Quote form definition
│   ├── language/                   # Admin language files
│   │   ├── en-GB/
│   │   └── es-ES/
│   ├── services/
│   │   └── provider.php           # Service provider registration
│   ├── src/                       # Admin PHP source files
│   │   ├── Controller/
│   │   │   ├── ConfigController.php    # Configuration management
│   │   │   └── DisplayController.php   # Admin display logic
│   │   ├── Extension/
│   │   │   └── CotizacionesComponent.php # Component entry point
│   │   ├── Model/
│   │   │   └── ConfigModel.php    # Configuration data model
│   │   └── View/
│   │       ├── Config/
│   │       │   └── HtmlView.php   # Configuration view
│   │       └── Dashboard/
│   │           └── HtmlView.php   # Admin dashboard view
│   └── tmpl/                      # Admin templates
│       ├── config/
│       │   └── default.php        # Configuration template
│       └── dashboard/
│           └── default.php        # Dashboard template
├── site/                          # Site (frontend) files
│   ├── forms/
│   │   └── cotizacion.xml         # Quote form definition
│   ├── language/                   # Site language files
│   │   ├── en-GB/
│   │   └── es-ES/
│   ├── src/                       # Site PHP source files
│   │   ├── Controller/
│   │   │   ├── CotizacionController.php  # Quote CRUD operations
│   │   │   └── DisplayController.php     # Site display logic
│   │   ├── Dispatcher/
│   │   │   └── Dispatcher.php     # Request dispatcher
│   │   ├── Helper/
│   │   │   └── OdooHelper.php     # Odoo API integration
│   │   ├── Model/
│   │   │   ├── CotizacionesModel.php     # Quote list model
│   │   │   └── CotizacionModel.php       # Single quote model
│   │   ├── Service/
│   │   │   └── Router.php         # URL routing service
│   │   └── View/
│   │       ├── Cotizacion/
│   │       │   └── HtmlView.php   # Single quote view
│   │       └── Cotizaciones/
│   │           └── HtmlView.php   # Quote list view
│   └── tmpl/                      # Site templates
│       ├── cotizacion/
│       │   ├── default.php        # Quote display template
│       │   ├── default.xml        # Quote layout definition
│       │   ├── edit.php           # Quote edit template
│       │   └── edit.xml           # Edit layout definition
│       └── cotizaciones/
│           ├── default.php        # Quote list template
│           ├── default.xml        # List layout definition
│           ├── diagnostics.php    # Diagnostic template
│           └── diagnostics.xml    # Diagnostics layout
├── media/                         # CSS, JS, and assets
│   ├── css/
│   │   └── cotizaciones.css       # Component styles
│   └── js/
│       └── cotizaciones.js        # Component JavaScript
└── cotizaciones.xml               # Component manifest
```

## Core Components

### 1. Odoo Integration Layer (`OdooHelper.php`)

**Purpose**: Handles all communication with Odoo ERP system via XML-RPC API.

**Key Methods**:
- `getClients($search, $agentName)`: Retrieve clients filtered by sales agent
- `getQuotesByAgent($agentName, $page, $limit, $search, $stateFilter)`: Get quotes for specific agent
- `createQuote($data)`: Create new quote in Odoo
- `updateQuote($quoteId, $data)`: Update existing quote
- `getQuoteLines($quoteId)`: Get line items for a quote
- `createQuoteLine($quoteId, $productName, $description, $quantity, $price)`: Add line item
- `updateQuoteLine($lineId, $description, $quantity, $price)`: Update line item
- `deleteQuoteLine($lineId)`: Remove line item

**Configuration Parameters**:
- `odoo_url`: Odoo XML-RPC endpoint URL
- `odoo_database`: Odoo database name
- `odoo_user_id`: Odoo user ID for API authentication
- `odoo_username`: Odoo username
- `odoo_api_key`: Odoo API key for authentication
- `quotes_per_page`: Pagination limit
- `enable_debug`: Debug mode toggle

**Data Flow**:
1. **Authentication**: Uses API key-based authentication
2. **Request Format**: XML-RPC with `execute_kw` method
3. **Response Handling**: Parses XML responses and converts to PHP arrays
4. **Error Handling**: Comprehensive error catching and logging

### 2. Model Layer

#### `CotizacionesModel.php` (List Model)
**Purpose**: Manages quote listing, pagination, and filtering.

**Key Features**:
- Pagination support
- Search functionality
- State filtering (draft, sent, sale, done, cancel)
- User-specific quote filtering

#### `CotizacionModel.php` (Single Quote Model)
**Purpose**: Manages individual quote operations.

**Key Methods**:
- `getItem($pk)`: Retrieve single quote
- `getQuoteLines($quoteId)`: Get quote line items
- `createQuote($data)`: Create new quote
- `updateQuote($quoteId, $data)`: Update quote
- `getAvailableClients()`: Get user-specific clients

### 3. Controller Layer

#### `CotizacionController.php`
**Purpose**: Handles quote CRUD operations and line item management.

**Key Methods**:
- `save()`: Create or update quotes
- `addLine()`: Add quote line item
- `updateLine()`: Update quote line item
- `deleteLine()`: Remove quote line item
- `edit()`: Redirect to edit form
- `add()`: Redirect to new quote form

#### `DisplayController.php`
**Purpose**: Manages view display and access control.

**Key Features**:
- User authentication checks
- View routing
- Layout management
- Error handling

### 4. View Layer

#### Quote List View (`cotizaciones/default.php`)
**Features**:
- Responsive table layout
- Search and filter controls
- Pagination
- Status badges
- Action buttons

#### Quote Edit View (`cotizacion/edit.php`)
**Features**:
- Client selection with searchable dropdown
- Quote line management
- Form validation
- Real-time updates

### 5. Frontend Assets

#### CSS (`cotizaciones.css`)
**Features**:
- Bootstrap-based responsive design
- Custom component styling
- Accessibility improvements
- Mobile optimization
- Loading states and animations

#### JavaScript (`cotizaciones.js`)
**Features**:
- Form validation
- Client search functionality
- Loading state management
- Error handling
- User interaction enhancements

## Data Models

### Quote Structure
```php
[
    'id' => integer,           // Quote ID
    'name' => string,          // Quote number
    'partner_id' => integer,   // Client ID
    'contact_name' => string,  // Client name
    'date_order' => string,    // Quote date (Y-m-d)
    'amount_total' => float,   // Total amount
    'state' => string,         // Status (draft, sent, sale, done, cancel)
    'note' => string           // Notes
]
```

### Quote Line Structure
```php
[
    'id' => integer,           // Line ID
    'product_id' => integer,   // Product ID
    'product_name' => string,  // Product name
    'name' => string,          // Description
    'product_uom_qty' => float, // Quantity
    'price_unit' => float,     // Unit price
    'price_subtotal' => float  // Line total
]
```

### Client Structure
```php
[
    'id' => string,            // Client ID
    'name' => string,          // Client name
    'email' => string,         // Email address
    'phone' => string          // Phone number
]
```

## Odoo Integration Details

### API Endpoints
- **Base URL**: `https://grupoimpre.odoo.com/xmlrpc/2/object`
- **Method**: `execute_kw`
- **Authentication**: API key-based

### Key Odoo Models
- `sale.order`: Quotes/orders
- `sale.order.line`: Quote line items
- `res.partner`: Clients/partners
- `product.product`: Products

### Sales Agent Filtering
- **Field**: `x_studio_agente_de_ventas`
- **Logic**: Filters clients by assigned sales agent
- **Fallback**: Multiple search strategies for reliability

## Security Features

### Authentication & Authorization
- Joomla user authentication required
- User-specific data filtering
- CSRF protection on all forms
- Input validation and sanitization

### Data Protection
- Secure API key storage
- HTTPS communication with Odoo
- Input sanitization
- XSS prevention

## Error Handling

### Comprehensive Error Management
- Try-catch blocks throughout
- User-friendly error messages
- Debug mode for troubleshooting
- Graceful fallbacks

### Common Error Scenarios
- Odoo connection failures
- Authentication errors
- Data validation failures
- Network timeouts

## Configuration Management

### Component Configuration
- Odoo connection settings
- Pagination limits
- Debug mode toggle
- User interface preferences

### Language Support
- English (en-GB)
- Spanish (es-ES)
- Extensible language system

## Performance Considerations

### Optimization Strategies
- Efficient Odoo API calls
- Client-side search filtering
- Pagination for large datasets
- Caching where appropriate

### Scalability
- Modular architecture
- Separation of concerns
- Extensible design patterns

## Integration Points

### Joomla Integration
- MVC architecture compliance
- Joomla form system integration
- Language system integration
- User system integration

### Odoo Integration
- XML-RPC API communication
- Real-time data synchronization
- Error handling and recovery
- Authentication management

## Development Guidelines

### Code Standards
- PSR-4 autoloading
- Joomla coding standards
- Comprehensive error handling
- Extensive documentation

### Best Practices
- Separation of concerns
- Dependency injection
- Input validation
- Security-first approach

## Future Enhancement Opportunities

### Potential Features
1. **Advanced Reporting**: Quote analytics and reporting
2. **Email Integration**: Automated quote sending
3. **PDF Generation**: Quote PDF creation
4. **Multi-language Support**: Additional languages
5. **Advanced Filtering**: More sophisticated search options
6. **Bulk Operations**: Mass quote operations
7. **API Endpoints**: REST API for external integrations
8. **Mobile App**: Native mobile application

### Technical Improvements
1. **Caching Layer**: Redis/Memcached integration
2. **Queue System**: Background job processing
3. **Webhook Support**: Real-time Odoo updates
4. **Advanced Security**: Two-factor authentication
5. **Performance Monitoring**: Application metrics

## Troubleshooting Guide

### Common Issues
1. **Connection Errors**: Check Odoo URL and API credentials
2. **Authentication Failures**: Verify API key and user permissions
3. **Data Display Issues**: Check Odoo field mappings
4. **Performance Problems**: Review API call optimization

### Debug Mode
- Enable debug mode in component configuration
- Check Joomla error logs
- Monitor Odoo API responses
- Verify data transformations

## Deployment Considerations

### Requirements
- Joomla 4.0+
- PHP 8.0+
- cURL extension
- Valid Odoo instance with API access

### Installation Steps
1. Install component via Joomla extension manager
2. Configure Odoo connection settings
3. Test connection using diagnostic tools
4. Verify user permissions and access

### Maintenance
- Regular Odoo API key rotation
- Monitor error logs
- Update component as needed
- Backup configuration settings

---

**Document Version**: 1.0  
**Last Updated**: January 2025  
**Maintainer**: Grimpsa Development Team
