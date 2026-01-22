# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

SyncPoint CRM is a WordPress plugin for contact/customer relationship management with PayPal & Stripe integration, invoicing, and automation. Version 1.2.2. Requires PHP 7.4+ and WordPress 6.0+.

## Build & Development

```bash
# Install dev dependencies
composer install

# Create distribution ZIP (uses current version from plugin header)
./build.sh

# Create ZIP with specific version
./build.sh 1.2.3
```

No composer dependencies in production. The build script excludes dev files and creates a clean WordPress.org-ready zip.

## Testing & Linting

```bash
# Run PHPCS (WordPress Coding Standards)
composer phpcs

# Auto-fix PHPCS violations
composer phpcbf

# Run PHPUnit tests (requires WordPress test suite)
composer test

# Run tests with coverage report
composer test:coverage

# Install WordPress test suite (one-time setup)
bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version]
```

Tests are in `tests/Unit/`. The bootstrap supports running without WordPress for basic unit tests.

## Architecture

### Entry Point & Singleton
- [syncpoint-crm.php](syncpoint-crm.php) — Main plugin file with `SyncPoint_CRM` singleton
- Access via `scrm()` function

### Autoloading
PSR-4 style autoloader in main class handles two patterns:
- Namespaced: `SCRM\Gateways\PayPal` → `includes/gateways/class-scrm-paypal.php`
- Non-namespaced: `SCRM_Admin` → `includes/class-scrm-admin.php`

Namespace-to-directory mapping: `Admin`, `Core`, `Gateways`, `API`, `Import`, `Export`, `Utils`

### Core Files

| File | Purpose |
|------|---------|
| [includes/scrm-functions.php](includes/scrm-functions.php) | CRUD functions (`scrm_create_contact`, `scrm_get_contacts`, etc.) |
| [includes/scrm-helper-functions.php](includes/scrm-helper-functions.php) | Utility functions (`scrm_get_settings`, formatting) |
| [includes/class-scrm-activator.php](includes/class-scrm-activator.php) | Database table creation on activation |
| [includes/class-scrm-ajax.php](includes/class-scrm-ajax.php) | Admin AJAX handlers |
| [includes/class-scrm-cron.php](includes/class-scrm-cron.php) | Scheduled tasks (sync jobs) |

### Payment Gateways
Abstract gateway pattern in `includes/gateways/`:
- [class-scrm-gateway.php](includes/gateways/class-scrm-gateway.php) — Base `SCRM\Gateways\Gateway` abstract class
- [class-scrm-paypal.php](includes/gateways/class-scrm-paypal.php) — PayPal API integration
- [class-scrm-stripe.php](includes/gateways/class-scrm-stripe.php) — Stripe API integration
- [class-scrm-manual.php](includes/gateways/class-scrm-manual.php) — Manual transaction entry

Gateways must implement: `get_settings_fields()`, `is_available()`, `sync_transactions()`, `process_webhook()`

### Admin Layer
In `includes/admin/`:
- [class-scrm-admin.php](includes/admin/class-scrm-admin.php) — Main admin controller (menus, list tables, forms)
- [class-scrm-admin-settings.php](includes/admin/class-scrm-admin-settings.php) — Settings pages
- [class-scrm-dashboard.php](includes/admin/class-scrm-dashboard.php) — Dashboard widgets and charts
- [class-scrm-contacts-list-table.php](includes/admin/class-scrm-contacts-list-table.php) — WP_List_Table for contacts

### REST API & Webhooks
In `includes/api/`:
- [class-scrm-rest-api.php](includes/api/class-scrm-rest-api.php) — REST endpoints at `/wp-json/scrm/v1/`
- [class-scrm-webhooks.php](includes/api/class-scrm-webhooks.php) — Inbound webhook processing

## Database Tables

All tables prefixed with `{wp_prefix}scrm_`:
- `scrm_contacts` — Customers, leads, prospects
- `scrm_companies` — Organizations linked to contacts
- `scrm_transactions` — Payments, refunds (linked to gateways)
- `scrm_invoices` — Invoice records
- `scrm_invoice_items` — Invoice line items
- `scrm_tags` — Color-coded tags
- `scrm_tag_relationships` — Polymorphic tagging
- `scrm_activity_log` — Audit trail

Schema details in [docs/IMPLEMENTATION-PLAN.md](docs/IMPLEMENTATION-PLAN.md)

## Key Patterns

### Function Naming
All public functions prefixed `scrm_`:
```php
scrm_create_contact( $data )
scrm_get_contact( $id )
scrm_update_contact( $id, $data )
scrm_delete_contact( $id )
scrm_get_contacts( $args )
```
Same pattern for companies, transactions, invoices, tags.

### Hooks
50+ action hooks and 40+ filters. Key lifecycle hooks:
- `scrm_contact_created`, `scrm_contact_updated`, `scrm_contact_deleted`
- `scrm_transaction_created`, `scrm_invoice_created`
- `scrm_before_init`, `scrm_init`, `scrm_loaded`

Full reference: [docs/HOOKS-REFERENCE.md](docs/HOOKS-REFERENCE.md)

### Settings
```php
scrm_get_settings( $section )  // Get all settings for a section
scrm_get_option( $key, $default )  // Get single option
```

## Documentation

- [docs/IMPLEMENTATION-PLAN.md](docs/IMPLEMENTATION-PLAN.md) — Database schema, architecture
- [docs/HOOKS-REFERENCE.md](docs/HOOKS-REFERENCE.md) — All hooks with examples
- [docs/REST-API.md](docs/REST-API.md) — API endpoints
- [docs/WEBHOOKS.md](docs/WEBHOOKS.md) — Webhook integration
- [docs/DEVELOPER-GUIDE.md](docs/DEVELOPER-GUIDE.md) — Extending the plugin

## Coding Standards

- Follow WordPress Coding Standards
- Escape output: `esc_html()`, `esc_attr()`, `esc_url()`
- Sanitize input: `sanitize_text_field()`, `sanitize_email()`, `absint()`
- Use `$wpdb->prepare()` for all database queries
- Prefix everything with `scrm_` or `SCRM`
