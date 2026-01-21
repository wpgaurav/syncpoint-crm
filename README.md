# Starter CRM

A lightweight, extensible WordPress CRM plugin with PayPal & Stripe integration, invoicing, and powerful automation capabilities.

## Features

- **Contact Management** — Customers, leads, prospects with custom IDs
- **Company Management** — Link contacts to companies
- **Transaction Tracking** — Multi-currency, per-contact settings
- **Payment Integration** — PayPal & Stripe API support
- **Invoicing** — Create, send, and track invoices with PDF generation
- **Tagging System** — Color-coded tags for organization
- **CSV Import** — Field mapping and duplicate detection
- **Dashboard** — Charts and statistics
- **REST API** — Full CRUD for all data types
- **Webhooks** — Receive data from external services
- **Developer Friendly** — 50+ hooks, template overrides

## Requirements

- WordPress 6.0+
- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.3+

## Installation

1. Upload the `starter-crm` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu
3. Go to CRM → Settings to configure

## File Structure

```
starter-crm/
├── starter-crm.php          # Main plugin file
├── uninstall.php            # Clean uninstall handler
├── readme.txt               # WordPress.org readme
│
├── includes/
│   ├── scrm-functions.php        # Core CRUD functions
│   ├── scrm-helper-functions.php # Utility functions
│   ├── class-scrm-activator.php  # DB table creation
│   ├── class-scrm-deactivator.php
│   ├── admin/
│   │   └── class-scrm-admin.php  # Admin UI controller
│   └── api/
│       ├── class-scrm-rest-api.php  # REST endpoints
│       └── class-scrm-webhooks.php  # Webhook handlers
│
├── assets/
│   ├── css/
│   │   └── admin.css        # Admin styles
│   └── js/
│       ├── admin.js         # Admin scripts
│       ├── charts.js        # Dashboard charts
│       └── import.js        # CSV import wizard
│
├── templates/               # Overridable templates
│
└── docs/
    ├── IMPLEMENTATION-PLAN.md  # Database schema & architecture
    ├── HOOKS-REFERENCE.md      # All hooks & filters
    ├── REST-API.md             # API documentation
    ├── WEBHOOKS.md             # Webhook integration guide
    ├── DEVELOPER-GUIDE.md      # Extension development
    └── CHANGELOG.md            # Version history
```

## Documentation

See the `/docs` folder for complete documentation:

- **[Implementation Plan](docs/IMPLEMENTATION-PLAN.md)** — Database schema, file structure, phased roadmap
- **[Hooks Reference](docs/HOOKS-REFERENCE.md)** — 50+ action hooks and 40+ filters with examples
- **[REST API](docs/REST-API.md)** — Complete API documentation with examples
- **[Webhooks](docs/WEBHOOKS.md)** — Integration guide for PayPal, Stripe, forms, Zapier
- **[Developer Guide](docs/DEVELOPER-GUIDE.md)** — How to extend the plugin

## Quick Start

### Create a Contact

```php
$contact_id = scrm_create_contact( array(
    'first_name' => 'John',
    'last_name'  => 'Doe',
    'email'      => 'john@example.com',
    'type'       => 'customer',
) );
```

### Get Contacts

```php
$customers = scrm_get_contacts( array(
    'type'   => 'customer',
    'status' => 'active',
    'limit'  => 50,
) );
```

### Create Transaction

```php
$txn_id = scrm_create_transaction( array(
    'contact_id' => $contact_id,
    'type'       => 'payment',
    'amount'     => 500.00,
    'currency'   => 'USD',
    'gateway'    => 'manual',
) );
```

### Use Hooks

```php
// After contact created
add_action( 'scrm_contact_created', function( $id, $data ) {
    // Send welcome email
    wp_mail( $data['email'], 'Welcome!', 'Thanks for joining.' );
}, 10, 2 );

// Filter contact types
add_filter( 'scrm_contact_types', function( $types ) {
    $types['partner'] = 'Partner';
    return $types;
} );
```

## REST API

```bash
# Get contacts
curl -X GET "https://site.com/wp-json/scrm/v1/contacts" \
  -u "user:app-password"

# Create contact
curl -X POST "https://site.com/wp-json/scrm/v1/contacts" \
  -u "user:app-password" \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","type":"lead"}'
```

## Webhooks

```bash
# Send data from external service
curl -X POST "https://site.com/wp-json/scrm/v1/webhooks/inbound" \
  -H "X-SCRM-Webhook-Key: your-key" \
  -H "Content-Type: application/json" \
  -d '{
    "action": "create_contact",
    "data": {
      "email": "jane@example.com",
      "first_name": "Jane"
    }
  }'
```

## License

GPL v2 or later

## Credits

Built for the WordPress community.
