# Changelog

All notable changes to SyncPoint CRM will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Planned
- WooCommerce integration
- Mailchimp/ConvertKit sync
- Automation rules engine
- Deal/Pipeline management
- Recurring invoices
- Client portal (frontend)

---

## [1.2.2] - 2026-01-22

### Fixed
- Fixed PayPal NVP import pagination to properly fetch all historical transactions (was limited to 100)
- Fixed PayPal NVP import to work independently from PayPal REST API integration
- Fixed syntax errors with stray spaces in array keys (`$data['address']['state' ]` → `$data['address']['state']`)
- Fixed incomplete `scrm_get_sync_logs()` function
- Fixed incomplete `scrm_get_last_sync()` function
- Fixed incomplete `render_sync_history()` method in admin settings
- Added missing `scrm_get_next_sync_time()` function
- Added missing `scrm_reschedule_sync()` function
- Added missing `scrm_get_email_template()` function
- Added missing `scrm_log_email()` function

### Improved
- Added more PayPal transaction types support (Express Checkout Payment, Donation, eBay Auction Payment, etc.)
- Improved progress reporting during PayPal historical import with batch count and total fetched
- Added duplicate transaction prevention during pagination
- Added rate limiting protection (0.25s delay between API calls)
- Increased safety limit to 500 pages (50,000 transactions max)

## [1.2.1] - 2026-01-22

### Fixed
- Fixed syntax errors in `scrm-functions.php` with stray spaces in array keys causing parse errors.
- Fixed Stripe gateway class missing `is_available()` method implementation.
- Code cleanup and stability improvements.

## [1.2.0] - 2026-01-22

### Fixed
- Fixed critical error: "Cannot redeclare function scrm_update_company()" by removing duplicate function definitions.
- Fixed critical error: "Cannot redeclare function scrm_get_company_by_name()" by consolidating function definitions.
- Fixed critical error: "Cannot redeclare function scrm_cache_delete_group()" by removing duplicate definitions.
- Moved `scrm_get_company_by_name()` to `scrm-functions.php` where other company functions reside.
- Created missing `SCRM\Gateways\Stripe` class for Stripe payment gateway integration.

### Added
- Stripe gateway class with transaction sync and webhook support.

## [1.1.9] - 2026-01-22

### Fixed
- Fixed "Class SCRM\Gateways\PayPal not found" error by creating missing PayPal gateway class.
- Fixed "Class SCRM\Gateways\Gateway not found" error by creating base Gateway abstract class.
- Fixed "Class SCRM\Import\CSV_Importer not found" error by creating CSV importer class.
- Fixed autoloader to correctly map namespaced classes to file paths.
- Fixed CSV import functionality for contacts, companies, and transactions.
- Fixed data export with ZipArchive availability check.
- Added missing `scrm_get_company_by_name()` helper function.
- Added missing `scrm_update_company()` helper function.
- Fixed `scrm_cache_delete_group()` SQL query.

### Added
- Manual payment gateway class for offline transactions.

## [1.1.8] - 2026-01-21

### Added
- Genuine data sources for dashboard revenue and contact charts.
- "Cancel" option for running PayPal synchronization processes.

### Fixed
- Fatal error: `Class "SCRM\Gateways\PayPal" not found` in `init_gateways()`.
- Namespace mapping for gateway classes in the plugin core.

## [1.1.6] - 2026-01-21

### Fixed
- Internal bug fixes and preparation for Namespacing.

## [1.1.5] - 2026-01-21

### Fixed
- PayPal NVP historical import getting stuck in 'Running' status.
- PayPal NVP sync logic to correctly paginate through transactions by updating the end date.
- Sync log status updates to properly mark completion/failure in error scenarios.
- Improved error feedback in the PayPal import UI.
- Corrected PayPal NVP search status parameter.

## [1.1.4] - 2026-01-21

### Fixed
- General bug fixes and performance improvements.

## [1.1.1] - 2026-01-14

### Fixed
- Settings page not showing PayPal Import and Tools tabs.
- Consolidated settings rendering to use SCRM_Admin_Settings class.
- Removed duplicate settings methods from SCRM_Admin class.

## [1.1.0] - 2026-01-14

### Added
- Automatic transaction sync scheduling for PayPal and Stripe.
- "Sync Now" button for manual gateway synchronization.
- Sync history and status tracking.
- Legacy NVP API support for historical PayPal transaction import.
- Email functionality for sending to single or multiple contacts.
- Email compose interface with contact search and merge tags.
- Email log tracking.
- Bulk "Send Email" action to contacts list.
- Tools tab with database status, recreate tables, optimize, and export all data.

## [1.0.0] - 2026-01-10

### Added
- **Core Features**
  - Contact management (customers, leads, prospects)
  - Company management with contact linking
  - Custom ID generation (CUST-001, COMP-001, INV-2026-001)
  - Tagging system with color coding
  - Activity logging

- **Transaction Tracking**
  - Manual transaction entry
  - Multi-currency support
  - Per-contact currency settings
  - Lifetime value calculation

- **Payment Gateway Integration**
  - PayPal API integration (OAuth 2.0)
  - PayPal transaction sync
  - PayPal webhook handling
  - Stripe API integration
  - Stripe customer/charge sync
  - Stripe webhook handling

- **Invoicing**
  - Invoice creation with line items
  - Tax and discount support
  - PayPal payment links
  - Stripe payment links
  - PDF generation
  - Email sending
  - Public invoice view

- **Import/Export**
  - CSV import wizard
  - Field mapping interface
  - ID matching for transactions
  - Duplicate detection
  - CSV export

- **Dashboard**
  - Revenue overview
  - Contact statistics
  - Chart.js graphs
  - Period comparisons
  - Recent activity feed

- **REST API**
  - Contacts endpoints
  - Companies endpoints
  - Transactions endpoints
  - Invoices endpoints
  - Tags endpoints
  - Dashboard stats endpoint

- **Webhooks**
  - Generic inbound webhook
  - PayPal webhook endpoint
  - Stripe webhook endpoint
  - Outgoing webhook configuration
  - Webhook logging

- **Custom Fields**
  - Contact custom fields
  - Company custom fields
  - Multiple field types (text, url, email, select, checkbox, date)

- **Developer Tools**
  - 50+ action hooks
  - 40+ filters
  - PSR-4 autoloading
  - Template override system
  - Add-on support

- **Performance**
  - Conditional asset loading
  - Transient caching
  - Optimized database queries

### Security
- Nonce verification on all forms
- Capability checks
- Prepared database statements
- API key encryption
- Webhook signature verification

---

## Version History

### Phase 1: Foundation
- [x] Plugin structure
- [x] Database schema design
- [x] Documentation framework

### Phase 2-10: Implementation
- [ ] Contact management
- [ ] Company management
- [ ] Tagging system
- [ ] Transaction tracking
- [ ] PayPal integration
- [ ] Stripe integration
- [ ] CSV import
- [ ] Invoicing system
- [ ] Dashboard & graphs
- [ ] REST API & webhooks

---

## Upgrade Notes

### From 0.x to 1.0.0
This is the initial release. No upgrade path required.

### Database Migrations
Database schema changes are handled automatically on plugin activation. Always backup before updating.

---

## Support

- **Documentation**: See `/docs/` folder
- **Issues**: GitHub Issues (TBD)
- **Email**: support@example.com
