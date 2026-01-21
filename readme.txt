=== SyncPoint CRM ===
Contributors: gatilab
Donate link: https://gatilab.com/donate
Tags: crm, customer relationship management, invoicing, paypal, stripe, contacts, billing
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.2.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight, extensible WordPress CRM with PayPal & Stripe integration, invoicing, and powerful automation capabilities.

== Description ==

**SyncPoint CRM** is a powerful yet lightweight customer relationship management system built specifically for WordPress. Perfect for freelancers, small agencies, and solopreneurs who need to manage customers, track transactions, and create invoices without the complexity of enterprise CRM solutions.

= Key Features =

**Contact & Company Management**
* Create and manage contacts (customers, leads, prospects)
* Organize contacts by company
* Custom ID generation (CUST-001, LEAD-042, etc.)
* Flexible tagging system with color coding
* Custom fields for additional data
* Contact-company linking
* Activity logging for complete history

**Transaction Tracking**
* Record payments, refunds, subscriptions
* Multi-currency support
* Per-contact currency settings
* Lifetime value calculation
* Transaction history per contact

**Payment Gateway Integration**
* **PayPal** - OAuth 2.0 authentication, transaction sync, webhooks
* **Stripe** - Customer sync, charge tracking, subscription support
* Manual transaction entry
* Webhook support for real-time updates

**Invoicing**
* Create professional invoices
* Line items with quantities and prices
* Tax and discount support
* Multiple payment methods per invoice
* PayPal & Stripe payment links
* PDF generation
* Email delivery
* Public invoice view for clients

**Data Import/Export**
* CSV import with field mapping
* ID matching for transaction import
* Duplicate detection
* Export contacts and transactions

**Dashboard & Analytics**
* Revenue overview with charts
* Contact statistics
* Period comparisons (7 days, 30 days, 90 days, year)
* Recent activity feed

**Developer Friendly**
* 50+ action hooks
* 40+ filters
* REST API for all data
* Webhook endpoints for integrations
* Template override system
* PSR-4 autoloading
* Extensive documentation

= Why SyncPoint CRM? =

* **Lightweight** - Only loads resources when needed
* **Extensible** - Designed for customization
* **WordPress Native** - Uses WP patterns and conventions
* **Privacy Focused** - Your data stays on your server
* **No Monthly Fees** - One-time purchase, no recurring costs

= Use Cases =

* **Freelancers** - Track clients and projects
* **Agencies** - Manage client relationships
* **Consultants** - Record payments and send invoices
* **Service Providers** - Keep customer records organized
* **Anyone** - Who needs a simple CRM without the bloat

== Installation ==

1. Upload the `syncpoint-crm` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to CRM → Settings to configure your options
4. Start adding contacts!

= Minimum Requirements =

* WordPress 6.0 or higher
* PHP 7.4 or higher
* MySQL 5.7 or MariaDB 10.3 or higher

== Frequently Asked Questions ==

= Can I import my existing contacts? =

Yes! SyncPoint CRM includes a CSV import wizard with field mapping. You can import contacts, companies, and transactions from any system that exports to CSV.

= Does this work with WooCommerce? =

WooCommerce integration is planned for a future release. Currently, you can use webhooks or the REST API to sync WooCommerce data.

= Can I use this without PayPal or Stripe? =

Absolutely! Payment gateway integration is optional. You can record transactions manually or use the webhook API to integrate with any payment system.

= Is my data safe? =

Yes. All data is stored in your WordPress database on your server. Sensitive API keys are encrypted. We follow WordPress security best practices.

= Can I customize the invoice template? =

Yes! Invoice templates can be overridden in your theme. Copy the template from `syncpoint-crm/templates/invoices/` to `your-theme/syncpoint-crm/invoices/` and customize.

= Is there a REST API? =

Yes! SyncPoint CRM provides a comprehensive REST API for all data types. See the documentation for endpoints and authentication.

= Can I extend the plugin? =

Definitely! We provide 50+ hooks and filters for customization. Check the Developer Guide in the docs folder for examples.

== Screenshots ==

1. Dashboard with revenue overview and statistics
2. Contact list with filtering and search
3. Contact profile with transaction history
4. Invoice builder with line items
5. Settings page with gateway configuration
6. CSV import wizard

== Changelog ==

= 1.2.2 =
* Fixed PayPal NVP import pagination to fetch all historical transactions (not just 100)
* Fixed PayPal NVP import to work independently from PayPal REST API integration
* Fixed syntax errors with stray spaces in array keys in scrm-functions.php
* Fixed incomplete helper functions (scrm_get_sync_logs, scrm_get_last_sync, etc.)
* Fixed incomplete render_sync_history() method in admin settings
* Added more PayPal transaction types support (Express Checkout, Donations, etc.)
* Improved progress reporting during PayPal historical import

= 1.2.1 =
* Fixed syntax errors in scrm-functions.php (stray spaces in array keys)
* Fixed Stripe gateway missing is_available() method implementation
* Code cleanup and stability improvements

= 1.2.0 =
* Fixed critical error: "Cannot redeclare function scrm_update_company()"
* Fixed critical error: "Cannot redeclare function scrm_get_company_by_name()"
* Fixed critical error: "Cannot redeclare function scrm_cache_delete_group()"
* Added Stripe gateway class with transaction sync and webhook support

= 1.1.7 =
* Added genuine data sources for dashboard charts
* Added option to cancel running PayPal transaction imports
* Fixed PHP fatal error: "Class 'SCRM\Gateways\PayPal' not found"
* Updated namespaced gateway initialization logic

= 1.1.6 =
* Bug fixes and performance improvements
* Fixed class namespacing issues

= 1.1.5 =
* Fixed PayPal NVP historical import getting stuck in 'Running' status
* Fixed PayPal NVP sync logic to correctly paginate through transactions
* Fixed sync log status updates to properly mark completion or failure in all scenarios
* Improved error handling in gateway synchronization

= 1.1.4 =
* Bug fixes and performance improvements

= 1.1.1 =
* Fixed settings page not showing PayPal Import and Tools tabs
* Consolidated settings rendering to use SCRM_Admin_Settings class
* Removed duplicate settings methods from SCRM_Admin class

= 1.1.0 =
* Added automatic transaction sync scheduling for PayPal and Stripe
* Added "Sync Now" button for manual gateway synchronization
* Added sync history and status tracking
* Added Legacy NVP API support for historical PayPal transaction import
* Added email functionality for sending to single or multiple contacts
* Added email compose interface with contact search and merge tags
* Added email log tracking
* Added bulk "Send Email" action to contacts list
* Added Tools tab with database status, recreate tables, optimize, and export all data
* Added GitHub Actions workflow for automated plugin releases

= 1.0.0 =
* Initial release
* Contact and company management
* Transaction tracking
* PayPal integration
* Stripe integration
* Invoicing with PDF generation
* CSV import
* REST API
* Webhook support

== Upgrade Notice ==

= 1.1.1 =
Fix: Settings page now correctly shows all tabs including PayPal Import and Tools.

= 1.1.0 =
New features: PayPal Import tab for historical transactions, Email functionality, automatic gateway sync scheduling, and database Tools tab.

= 1.0.0 =
Initial release of SyncPoint CRM.

== Developer Documentation ==

Complete developer documentation is included in the `docs/` folder:

* **IMPLEMENTATION-PLAN.md** - Architecture and database schema
* **HOOKS-REFERENCE.md** - All available hooks and filters
* **REST-API.md** - API endpoint documentation
* **WEBHOOKS.md** - Webhook integration guide
* **DEVELOPER-GUIDE.md** - Extension development
* **CHANGELOG.md** - Version history

== Credits ==

* Built with love for the WordPress community
* Chart.js for dashboard graphs
* DOMPDF for PDF generation (optional)
