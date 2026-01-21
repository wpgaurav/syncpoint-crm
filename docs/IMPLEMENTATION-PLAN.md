# Starter CRM - Implementation Plan

**Version**: 1.0.0  
**Status**: Planning Phase  
**Last Updated**: 2026-01-21

---

## Executive Summary

Starter CRM is a lightweight, extensible WordPress CRM plugin designed for freelancers, small agencies, and solopreneurs. It focuses on customer relationship management with integrated payment gateway support (PayPal, Stripe), transaction tracking, and simple invoicing.

### Core Philosophy

1. **Lightweight First** — No bloat, loads only what's needed
2. **Extensible Architecture** — Hooks, filters, and modular design
3. **Native WordPress Patterns** — Uses WP conventions, not reinventing the wheel
4. **Future-Ready** — Room for expansion without breaking changes

---

## Database Schema

### Custom Tables

```sql
-- Contacts (Customers, Leads, Prospects)
{prefix}scrm_contacts
├── id (BIGINT, AUTO_INCREMENT, PRIMARY KEY)
├── contact_id (VARCHAR 50, UNIQUE) — Custom readable ID (e.g., CUST-001)
├── type (ENUM: customer, lead, prospect)
├── status (ENUM: active, inactive, archived)
├── first_name (VARCHAR 100)
├── last_name (VARCHAR 100)
├── email (VARCHAR 255, INDEX)
├── phone (VARCHAR 50)
├── company_id (BIGINT, FK → scrm_companies)
├── currency (VARCHAR 3, DEFAULT from settings)
├── tax_id (VARCHAR 100)
├── address_line_1 (VARCHAR 255)
├── address_line_2 (VARCHAR 255)
├── city (VARCHAR 100)
├── state (VARCHAR 100)
├── postal_code (VARCHAR 20)
├── country (VARCHAR 2) — ISO 3166-1 alpha-2
├── custom_fields (LONGTEXT, JSON)
├── source (VARCHAR 100) — Where they came from
├── created_at (DATETIME)
├── updated_at (DATETIME)
└── created_by (BIGINT, FK → users)

-- Companies/Organizations
{prefix}scrm_companies
├── id (BIGINT, AUTO_INCREMENT, PRIMARY KEY)
├── company_id (VARCHAR 50, UNIQUE) — Custom readable ID (e.g., COMP-001)
├── name (VARCHAR 255)
├── website (VARCHAR 255)
├── email (VARCHAR 255)
├── phone (VARCHAR 50)
├── tax_id (VARCHAR 100)
├── address_line_1 (VARCHAR 255)
├── address_line_2 (VARCHAR 255)
├── city (VARCHAR 100)
├── state (VARCHAR 100)
├── postal_code (VARCHAR 20)
├── country (VARCHAR 2)
├── industry (VARCHAR 100)
├── custom_fields (LONGTEXT, JSON)
├── created_at (DATETIME)
├── updated_at (DATETIME)
└── created_by (BIGINT, FK → users)

-- Transactions
{prefix}scrm_transactions
├── id (BIGINT, AUTO_INCREMENT, PRIMARY KEY)
├── transaction_id (VARCHAR 100, UNIQUE) — Gateway transaction ID or custom
├── contact_id (BIGINT, FK → scrm_contacts, INDEX)
├── invoice_id (BIGINT, FK → scrm_invoices, nullable)
├── type (ENUM: payment, refund, subscription, payout)
├── gateway (VARCHAR 50) — paypal, stripe, manual, webhook
├── gateway_transaction_id (VARCHAR 255)
├── amount (DECIMAL 15,2)
├── currency (VARCHAR 3)
├── status (ENUM: pending, completed, failed, refunded)
├── description (TEXT)
├── metadata (LONGTEXT, JSON) — Gateway-specific data
├── created_at (DATETIME)
└── updated_at (DATETIME)

-- Tags
{prefix}scrm_tags
├── id (BIGINT, AUTO_INCREMENT, PRIMARY KEY)
├── name (VARCHAR 100)
├── slug (VARCHAR 100, UNIQUE)
├── color (VARCHAR 7) — Hex color
├── description (TEXT)
└── created_at (DATETIME)

-- Tag Relationships (Polymorphic)
{prefix}scrm_tag_relationships
├── id (BIGINT, AUTO_INCREMENT, PRIMARY KEY)
├── tag_id (BIGINT, FK → scrm_tags)
├── object_id (BIGINT)
├── object_type (ENUM: contact, company, transaction, invoice)
└── UNIQUE KEY (tag_id, object_id, object_type)

-- Invoices
{prefix}scrm_invoices
├── id (BIGINT, AUTO_INCREMENT, PRIMARY KEY)
├── invoice_number (VARCHAR 50, UNIQUE) — e.g., INV-2026-001
├── contact_id (BIGINT, FK → scrm_contacts)
├── company_id (BIGINT, FK → scrm_companies, nullable)
├── status (ENUM: draft, sent, viewed, paid, overdue, cancelled)
├── issue_date (DATE)
├── due_date (DATE)
├── subtotal (DECIMAL 15,2)
├── tax_rate (DECIMAL 5,2)
├── tax_amount (DECIMAL 15,2)
├── discount_type (ENUM: fixed, percentage)
├── discount_value (DECIMAL 15,2)
├── total (DECIMAL 15,2)
├── currency (VARCHAR 3)
├── notes (TEXT)
├── terms (TEXT)
├── payment_methods (TEXT, JSON) — ['paypal', 'stripe', 'bank']
├── paypal_payment_link (VARCHAR 500)
├── stripe_payment_link (VARCHAR 500)
├── pdf_path (VARCHAR 500)
├── viewed_at (DATETIME, nullable)
├── paid_at (DATETIME, nullable)
├── created_at (DATETIME)
├── updated_at (DATETIME)
└── created_by (BIGINT, FK → users)

-- Invoice Line Items
{prefix}scrm_invoice_items
├── id (BIGINT, AUTO_INCREMENT, PRIMARY KEY)
├── invoice_id (BIGINT, FK → scrm_invoices)
├── description (TEXT)
├── quantity (DECIMAL 10,2)
├── unit_price (DECIMAL 15,2)
├── total (DECIMAL 15,2)
├── sort_order (INT)
└── created_at (DATETIME)

-- Activity Log
{prefix}scrm_activity_log
├── id (BIGINT, AUTO_INCREMENT, PRIMARY KEY)
├── object_id (BIGINT)
├── object_type (VARCHAR 50)
├── action (VARCHAR 100) — created, updated, deleted, email_sent, etc.
├── description (TEXT)
├── metadata (LONGTEXT, JSON)
├── user_id (BIGINT, FK → users)
├── ip_address (VARCHAR 45)
└── created_at (DATETIME)

-- Webhook Log
{prefix}scrm_webhook_log
├── id (BIGINT, AUTO_INCREMENT, PRIMARY KEY)
├── source (VARCHAR 100) — paypal, stripe, custom, form
├── endpoint (VARCHAR 255)
├── payload (LONGTEXT, JSON)
├── status (ENUM: success, failed, pending)
├── response (TEXT)
├── processed_at (DATETIME)
└── created_at (DATETIME)
```

---

## File Structure

```
starter-crm/
├── starter-crm.php                 # Main plugin file
├── uninstall.php                   # Clean uninstall
├── readme.txt                      # WordPress.org readme
│
├── includes/
│   ├── class-scrm-loader.php       # Hooks and filters registration
│   ├── class-scrm-activator.php    # Activation (create tables, defaults)
│   ├── class-scrm-deactivator.php  # Deactivation cleanup
│   │
│   ├── admin/
│   │   ├── class-scrm-admin.php           # Admin controller
│   │   ├── class-scrm-admin-menu.php      # Menu registration
│   │   ├── class-scrm-admin-contacts.php  # Contacts list/edit
│   │   ├── class-scrm-admin-companies.php # Companies management
│   │   ├── class-scrm-admin-transactions.php
│   │   ├── class-scrm-admin-invoices.php
│   │   ├── class-scrm-admin-tags.php
│   │   ├── class-scrm-admin-settings.php
│   │   ├── class-scrm-admin-import.php    # CSV import
│   │   └── class-scrm-admin-dashboard.php # Overview & graphs
│   │
│   ├── core/
│   │   ├── class-scrm-contact.php         # Contact model
│   │   ├── class-scrm-company.php         # Company model
│   │   ├── class-scrm-transaction.php     # Transaction model
│   │   ├── class-scrm-invoice.php         # Invoice model
│   │   ├── class-scrm-tag.php             # Tag model
│   │   ├── class-scrm-activity.php        # Activity logger
│   │   └── class-scrm-custom-fields.php   # Custom fields handler
│   │
│   ├── gateways/
│   │   ├── abstract-scrm-gateway.php      # Gateway interface
│   │   ├── class-scrm-paypal.php          # PayPal API integration
│   │   ├── class-scrm-stripe.php          # Stripe API integration
│   │   └── class-scrm-manual.php          # Manual transactions
│   │
│   ├── api/
│   │   ├── class-scrm-rest-api.php        # REST API controller
│   │   ├── class-scrm-rest-contacts.php   # Contacts endpoints
│   │   ├── class-scrm-rest-transactions.php
│   │   ├── class-scrm-rest-invoices.php
│   │   └── class-scrm-webhooks.php        # Incoming webhooks
│   │
│   ├── import/
│   │   ├── class-scrm-csv-importer.php    # CSV parsing & import
│   │   ├── class-scrm-paypal-importer.php # PayPal data sync
│   │   └── class-scrm-stripe-importer.php # Stripe data sync
│   │
│   ├── export/
│   │   ├── class-scrm-pdf-generator.php   # Invoice PDF generation
│   │   └── class-scrm-csv-exporter.php    # Data export
│   │
│   └── utils/
│       ├── class-scrm-currency.php        # Currency handling
│       ├── class-scrm-id-generator.php    # Custom ID generation
│       ├── class-scrm-sanitizer.php       # Input sanitization
│       └── class-scrm-cache.php           # Transient caching
│
├── assets/
│   ├── css/
│   │   ├── admin.css                # Admin styles (only on CRM pages)
│   │   └── invoice-pdf.css          # PDF-specific styles
│   │
│   └── js/
│       ├── admin.js                 # Admin functionality
│       ├── charts.js                # Chart.js wrapper (lazy loaded)
│       └── import.js                # Import wizard
│
├── templates/
│   ├── emails/
│   │   ├── invoice-sent.php
│   │   └── payment-received.php
│   │
│   ├── invoices/
│   │   ├── invoice-view.php         # Public invoice view
│   │   └── invoice-pdf.php          # PDF template
│   │
│   └── admin/
│       ├── dashboard.php
│       ├── contacts-list.php
│       ├── contact-edit.php
│       ├── companies-list.php
│       ├── company-edit.php
│       ├── transactions-list.php
│       ├── invoices-list.php
│       ├── invoice-edit.php
│       ├── tags.php
│       ├── import.php
│       └── settings.php
│
├── languages/
│   └── starter-crm.pot              # Translation template
│
└── docs/
    ├── IMPLEMENTATION-PLAN.md       # This file
    ├── HOOKS-REFERENCE.md           # All hooks & filters
    ├── REST-API.md                  # API documentation
    ├── WEBHOOKS.md                  # Webhook integration guide
    ├── CHANGELOG.md                 # Version history
    └── DEVELOPER-GUIDE.md           # Extension development
```

---

## Implementation Phases

### Phase 1: Foundation (Priority: Critical)

**Goal**: Core plugin structure, database, and basic contact management.

| Task | File(s) | Complexity |
|------|---------|------------|
| Main plugin file with PSR-4 autoloading | `starter-crm.php` | Medium |
| Plugin activation/deactivation handlers | `class-scrm-activator.php`, `class-scrm-deactivator.php` | Medium |
| Database table creation | `class-scrm-activator.php` | High |
| Admin menu structure | `class-scrm-admin-menu.php` | Low |
| Contact model (CRUD) | `class-scrm-contact.php` | Medium |
| Contact list table (WP_List_Table) | `class-scrm-admin-contacts.php` | High |
| Contact add/edit forms | `templates/admin/contact-edit.php` | Medium |
| Custom ID generator | `class-scrm-id-generator.php` | Low |
| Basic sanitization utilities | `class-scrm-sanitizer.php` | Low |

**Hooks to Implement**:
```php
// Actions
do_action( 'scrm_contact_created', $contact_id, $contact_data );
do_action( 'scrm_contact_updated', $contact_id, $contact_data, $old_data );
do_action( 'scrm_contact_deleted', $contact_id );
do_action( 'scrm_before_contact_save', $contact_data );
do_action( 'scrm_after_contact_save', $contact_id, $contact_data );

// Filters
apply_filters( 'scrm_contact_types', array( 'customer', 'lead', 'prospect' ) );
apply_filters( 'scrm_contact_statuses', array( 'active', 'inactive', 'archived' ) );
apply_filters( 'scrm_contact_id_format', 'CUST-{number}', $contact_type );
apply_filters( 'scrm_contact_columns', $columns );
apply_filters( 'scrm_contact_data_before_save', $data );
```

---

### Phase 2: Companies & Tags (Priority: High)

**Goal**: Company management and tagging system.

| Task | File(s) | Complexity |
|------|---------|------------|
| Company model (CRUD) | `class-scrm-company.php` | Medium |
| Company list and editing | `class-scrm-admin-companies.php` | Medium |
| Tag model | `class-scrm-tag.php` | Low |
| Tag management UI | `class-scrm-admin-tags.php` | Medium |
| Tag assignment (contacts, companies) | Multiple | Medium |
| Contact-Company linking | `class-scrm-contact.php` | Low |

**Hooks to Implement**:
```php
// Actions
do_action( 'scrm_company_created', $company_id, $company_data );
do_action( 'scrm_company_updated', $company_id, $company_data );
do_action( 'scrm_tag_assigned', $tag_id, $object_id, $object_type );
do_action( 'scrm_tag_removed', $tag_id, $object_id, $object_type );

// Filters
apply_filters( 'scrm_company_id_format', 'COMP-{number}' );
apply_filters( 'scrm_tag_colors', $default_colors );
apply_filters( 'scrm_taggable_types', array( 'contact', 'company', 'transaction', 'invoice' ) );
```

---

### Phase 3: Transactions (Priority: High)

**Goal**: Transaction tracking with manual entry.

| Task | File(s) | Complexity |
|------|---------|------------|
| Transaction model | `class-scrm-transaction.php` | Medium |
| Transaction list table | `class-scrm-admin-transactions.php` | Medium |
| Manual transaction entry | Templates | Medium |
| Currency handling utility | `class-scrm-currency.php` | Medium |
| Per-contact currency support | `class-scrm-contact.php` update | Low |

**Hooks to Implement**:
```php
// Actions
do_action( 'scrm_transaction_created', $transaction_id, $transaction_data );
do_action( 'scrm_transaction_updated', $transaction_id, $transaction_data );
do_action( 'scrm_payment_received', $transaction_id, $contact_id, $amount, $currency );
do_action( 'scrm_refund_processed', $transaction_id, $original_transaction_id );

// Filters
apply_filters( 'scrm_transaction_types', array( 'payment', 'refund', 'subscription', 'payout' ) );
apply_filters( 'scrm_transaction_gateways', $gateways );
apply_filters( 'scrm_supported_currencies', $currencies );
apply_filters( 'scrm_default_currency', 'USD' );
apply_filters( 'scrm_format_currency', $formatted, $amount, $currency );
```

---

### Phase 4: PayPal Integration (Priority: High)

**Goal**: Connect to PayPal API, import contacts and transactions.

| Task | File(s) | Complexity |
|------|---------|------------|
| PayPal API client | `class-scrm-paypal.php` | High |
| OAuth 2.0 authentication | `class-scrm-paypal.php` | High |
| Transaction sync | `class-scrm-paypal-importer.php` | High |
| Contact creation from PayPal | `class-scrm-paypal-importer.php` | Medium |
| PayPal settings UI | `class-scrm-admin-settings.php` | Medium |
| Webhook listener for PayPal | `class-scrm-webhooks.php` | High |

**Hooks to Implement**:
```php
// Actions
do_action( 'scrm_paypal_connected' );
do_action( 'scrm_paypal_disconnected' );
do_action( 'scrm_paypal_sync_started' );
do_action( 'scrm_paypal_sync_completed', $imported_count );
do_action( 'scrm_paypal_webhook_received', $event_type, $payload );
do_action( 'scrm_paypal_transaction_imported', $transaction_id, $paypal_data );

// Filters
apply_filters( 'scrm_paypal_api_url', $url );
apply_filters( 'scrm_paypal_sync_limit', 100 );
apply_filters( 'scrm_paypal_transaction_mapping', $mapping );
```

---

### Phase 5: Stripe Integration (Priority: High)

**Goal**: Connect to Stripe API, sync customers and transactions.

| Task | File(s) | Complexity |
|------|---------|------------|
| Stripe API client | `class-scrm-stripe.php` | High |
| API key management | `class-scrm-admin-settings.php` | Medium |
| Customer sync | `class-scrm-stripe-importer.php` | High |
| Transaction/charge sync | `class-scrm-stripe-importer.php` | High |
| Subscription tracking | `class-scrm-stripe-importer.php` | High |
| Stripe webhook handler | `class-scrm-webhooks.php` | High |

**Hooks to Implement**:
```php
// Actions
do_action( 'scrm_stripe_connected' );
do_action( 'scrm_stripe_sync_started' );
do_action( 'scrm_stripe_sync_completed', $imported_count );
do_action( 'scrm_stripe_webhook_received', $event_type, $payload );
do_action( 'scrm_stripe_customer_imported', $contact_id, $stripe_customer );
do_action( 'scrm_stripe_transaction_imported', $transaction_id, $stripe_charge );

// Filters
apply_filters( 'scrm_stripe_customer_mapping', $mapping );
apply_filters( 'scrm_stripe_sync_types', array( 'charges', 'subscriptions', 'refunds' ) );
```

---

### Phase 6: CSV Import (Priority: Medium)

**Goal**: Import contacts and transactions from CSV with ID matching.

| Task | File(s) | Complexity |
|------|---------|------------|
| CSV parser | `class-scrm-csv-importer.php` | Medium |
| Import wizard UI | `templates/admin/import.php`, `assets/js/import.js` | High |
| Field mapping interface | JS + PHP | High |
| Duplicate detection | `class-scrm-csv-importer.php` | Medium |
| Transaction-Contact matching by ID | `class-scrm-csv-importer.php` | Medium |
| Import progress/status | AJAX handlers | Medium |

**Hooks to Implement**:
```php
// Actions
do_action( 'scrm_import_started', $import_type, $file_info );
do_action( 'scrm_import_row_processed', $row_number, $data, $result );
do_action( 'scrm_import_completed', $import_type, $stats );
do_action( 'scrm_import_failed', $import_type, $error );

// Filters
apply_filters( 'scrm_import_field_mapping', $mapping, $import_type );
apply_filters( 'scrm_import_row_data', $data, $row_number, $import_type );
apply_filters( 'scrm_import_batch_size', 50 );
apply_filters( 'scrm_import_supported_types', array( 'contacts', 'companies', 'transactions' ) );
```

---

### Phase 7: Invoicing (Priority: Medium)

**Goal**: Create and send simple invoices with payment links.

| Task | File(s) | Complexity |
|------|---------|------------|
| Invoice model | `class-scrm-invoice.php` | High |
| Invoice list table | `class-scrm-admin-invoices.php` | Medium |
| Invoice builder UI | `templates/admin/invoice-edit.php` | High |
| Public invoice view | `templates/invoices/invoice-view.php` | Medium |
| PDF generation | `class-scrm-pdf-generator.php` | High |
| PayPal payment link generation | `class-scrm-paypal.php` | Medium |
| Stripe payment link generation | `class-scrm-stripe.php` | Medium |
| Email sending | Multiple | Medium |

**Hooks to Implement**:
```php
// Actions
do_action( 'scrm_invoice_created', $invoice_id, $invoice_data );
do_action( 'scrm_invoice_sent', $invoice_id, $contact_id );
do_action( 'scrm_invoice_viewed', $invoice_id );
do_action( 'scrm_invoice_paid', $invoice_id, $transaction_id );
do_action( 'scrm_invoice_cancelled', $invoice_id );
do_action( 'scrm_invoice_pdf_generated', $invoice_id, $pdf_path );

// Filters
apply_filters( 'scrm_invoice_number_format', 'INV-{year}-{number}' );
apply_filters( 'scrm_invoice_statuses', $statuses );
apply_filters( 'scrm_invoice_payment_methods', array( 'paypal', 'stripe', 'bank' ) );
apply_filters( 'scrm_invoice_pdf_template', $template_path, $invoice_id );
apply_filters( 'scrm_invoice_email_subject', $subject, $invoice_id );
apply_filters( 'scrm_invoice_email_body', $body, $invoice_id );
apply_filters( 'scrm_invoice_line_item', $item_data, $invoice_id );
```

---

### Phase 8: Dashboard & Graphs (Priority: Medium)

**Goal**: Overview dashboard with visual analytics.

| Task | File(s) | Complexity |
|------|---------|------------|
| Dashboard controller | `class-scrm-admin-dashboard.php` | Medium |
| Revenue overview widget | `templates/admin/dashboard.php` | Medium |
| Contact statistics widget | Dashboard | Low |
| Chart.js integration (lazy load) | `assets/js/charts.js` | Medium |
| Recent activity feed | Dashboard | Low |
| Period comparison | Dashboard | Medium |

**Hooks to Implement**:
```php
// Actions
do_action( 'scrm_dashboard_widgets' );
do_action( 'scrm_before_dashboard_render' );
do_action( 'scrm_after_dashboard_render' );

// Filters
apply_filters( 'scrm_dashboard_periods', array( '7days', '30days', '90days', 'year', 'all' ) );
apply_filters( 'scrm_dashboard_widgets', $widgets );
apply_filters( 'scrm_dashboard_stats', $stats, $period );
apply_filters( 'scrm_chart_colors', $colors );
```

---

### Phase 9: Webhooks & REST API (Priority: Medium)

**Goal**: Accept incoming data from external services.

| Task | File(s) | Complexity |
|------|---------|------------|
| REST API base controller | `class-scrm-rest-api.php` | Medium |
| Contact endpoints | `class-scrm-rest-contacts.php` | Medium |
| Transaction endpoints | `class-scrm-rest-transactions.php` | Medium |
| Generic webhook endpoint | `class-scrm-webhooks.php` | High |
| Webhook authentication | `class-scrm-webhooks.php` | Medium |
| Webhook logging | `class-scrm-webhooks.php` | Low |
| Form integration examples | Docs | Low |

**REST Endpoints**:
```
GET    /wp-json/scrm/v1/contacts
POST   /wp-json/scrm/v1/contacts
GET    /wp-json/scrm/v1/contacts/{id}
PUT    /wp-json/scrm/v1/contacts/{id}
DELETE /wp-json/scrm/v1/contacts/{id}

GET    /wp-json/scrm/v1/transactions
POST   /wp-json/scrm/v1/transactions
GET    /wp-json/scrm/v1/transactions/{id}

POST   /wp-json/scrm/v1/webhooks/inbound      # Generic webhook receiver
POST   /wp-json/scrm/v1/webhooks/paypal       # PayPal IPM/webhooks
POST   /wp-json/scrm/v1/webhooks/stripe       # Stripe webhooks
```

**Hooks to Implement**:
```php
// Actions
do_action( 'scrm_api_contact_created', $contact_id, $request );
do_action( 'scrm_webhook_received', $source, $payload );
do_action( 'scrm_webhook_processed', $source, $payload, $result );
do_action( 'scrm_webhook_failed', $source, $payload, $error );

// Filters
apply_filters( 'scrm_api_contact_response', $response, $contact );
apply_filters( 'scrm_webhook_sources', array( 'paypal', 'stripe', 'custom' ) );
apply_filters( 'scrm_webhook_authentication', $is_valid, $source, $request );
apply_filters( 'scrm_webhook_payload_mapping', $mapping, $source );
```

---

### Phase 10: Polish & Optimization (Priority: Low)

**Goal**: Performance, UX improvements, and documentation.

| Task | File(s) | Complexity |
|------|---------|------------|
| Conditional asset loading | `class-scrm-admin.php` | Low |
| Caching layer | `class-scrm-cache.php` | Medium |
| Activity logging | `class-scrm-activity.php` | Medium |
| Export functionality | `class-scrm-csv-exporter.php` | Medium |
| Uninstall cleanup | `uninstall.php` | Low |
| Translation file | `languages/starter-crm.pot` | Low |
| Complete documentation | `docs/` | Medium |

---

## Asset Loading Strategy

To avoid loading unnecessary scripts:

```php
class SCRM_Admin {
    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'conditional_enqueue' ) );
    }

    public function conditional_enqueue( $hook ) {
        // Only load on our plugin pages
        if ( ! $this->is_scrm_page( $hook ) ) {
            return;
        }

        // Core admin styles (always on CRM pages)
        wp_enqueue_style( 'scrm-admin', SCRM_URL . 'assets/css/admin.css', array(), SCRM_VERSION );
        wp_enqueue_script( 'scrm-admin', SCRM_URL . 'assets/js/admin.js', array( 'jquery' ), SCRM_VERSION, true );

        // Charts only on dashboard
        if ( $this->is_dashboard_page( $hook ) ) {
            wp_enqueue_script( 'scrm-charts', SCRM_URL . 'assets/js/charts.js', array(), SCRM_VERSION, true );
        }

        // Import wizard scripts
        if ( $this->is_import_page( $hook ) ) {
            wp_enqueue_script( 'scrm-import', SCRM_URL . 'assets/js/import.js', array( 'jquery' ), SCRM_VERSION, true );
        }
    }

    private function is_scrm_page( $hook ) {
        $scrm_pages = array(
            'toplevel_page_starter-crm',
            'crm_page_scrm-contacts',
            'crm_page_scrm-companies',
            'crm_page_scrm-transactions',
            'crm_page_scrm-invoices',
            'crm_page_scrm-import',
            'crm_page_scrm-settings',
        );
        return in_array( $hook, $scrm_pages, true );
    }
}
```

---

## Settings Structure

```php
// Option key: scrm_settings
array(
    'general' => array(
        'default_currency'     => 'USD',
        'date_format'          => 'Y-m-d',
        'contact_id_prefix'    => 'CUST',
        'company_id_prefix'    => 'COMP',
        'invoice_prefix'       => 'INV',
        'next_contact_number'  => 1,
        'next_company_number'  => 1,
        'next_invoice_number'  => 1,
    ),
    'paypal' => array(
        'enabled'       => false,
        'mode'          => 'sandbox', // sandbox or live
        'client_id'     => '',
        'client_secret' => '', // encrypted
        'webhook_id'    => '',
    ),
    'stripe' => array(
        'enabled'          => false,
        'mode'             => 'test',
        'test_publishable' => '',
        'test_secret'      => '', // encrypted
        'live_publishable' => '',
        'live_secret'      => '', // encrypted
        'webhook_secret'   => '', // encrypted
    ),
    'invoices' => array(
        'company_name'    => '',
        'company_address' => '',
        'company_tax_id'  => '',
        'company_logo'    => '', // attachment ID
        'default_terms'   => '',
        'default_notes'   => '',
        'payment_methods' => array( 'paypal', 'stripe' ),
    ),
    'webhooks' => array(
        'enabled'     => true,
        'secret_key'  => '', // auto-generated
        'allowed_ips' => '',
    ),
    'custom_fields' => array(
        'contact' => array(),
        'company' => array(),
    ),
);
```

---

## Custom Fields Schema

```php
// Example custom field definition
array(
    'id'          => 'linkedin_url',
    'label'       => 'LinkedIn Profile',
    'type'        => 'url', // text, textarea, url, email, number, select, checkbox, date
    'required'    => false,
    'placeholder' => 'https://linkedin.com/in/username',
    'options'     => array(), // For select type
    'default'     => '',
    'position'    => 10, // Sort order
);
```

---

## Security Considerations

### Capability Checks

```php
// Define capabilities
'scrm_manage_contacts'     => true,
'scrm_manage_companies'    => true,
'scrm_manage_transactions' => true,
'scrm_manage_invoices'     => true,
'scrm_manage_settings'     => true,
'scrm_view_dashboard'      => true,
'scrm_import_data'         => true,
'scrm_export_data'         => true,

// By default, map to 'manage_options' (admins)
// Allow filtering for custom role assignment
apply_filters( 'scrm_capability_mappings', $mappings );
```

### Data Sanitization

- All user inputs sanitized via `sanitize_text_field()`, `sanitize_email()`, etc.
- Database queries use `$wpdb->prepare()`
- Nonce verification on all form submissions
- API authentication via WordPress application passwords or custom API keys
- Webhook signature verification for PayPal/Stripe

### Encryption

Sensitive data (API keys, secrets) encrypted using WordPress's `wp_salt()`:

```php
class SCRM_Encryption {
    public static function encrypt( $data ) {
        if ( empty( $data ) ) return '';
        $key = wp_salt( 'auth' );
        return base64_encode( openssl_encrypt( $data, 'AES-256-CBC', $key, 0, substr( $key, 0, 16 ) ) );
    }

    public static function decrypt( $data ) {
        if ( empty( $data ) ) return '';
        $key = wp_salt( 'auth' );
        return openssl_decrypt( base64_decode( $data ), 'AES-256-CBC', $key, 0, substr( $key, 0, 16 ) );
    }
}
```

---

## Future Expansion Ideas

1. **Email Marketing Integration** — Mailchimp, ConvertKit sync
2. **Automation Rules** — If/then triggers (e.g., tag added → send email)
3. **Deal/Pipeline Management** — Sales pipeline tracking
4. **Recurring Invoices** — Scheduled invoice generation
5. **Multi-currency Reports** — Real-time conversion
6. **WooCommerce Integration** — Sync WooCommerce customers/orders
7. **Zapier/Make Integration** — Custom trigger/action support
8. **Team Assignments** — Assign contacts to team members
9. **Notes & Comments** — Contact timeline with notes
10. **File Attachments** — Attach files to contacts/invoices
11. **Email Tracking** — Open/click tracking for sent invoices
12. **SMS Notifications** — Twilio integration
13. **Client Portal** — Frontend area for clients to view invoices

---

## Dependencies

### Required
- WordPress 6.0+
- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.3+

### Optional (for full functionality)
- **DOMPDF** or **mPDF** for PDF generation (bundled or composer)
- **Chart.js** for dashboard graphs (CDN or bundled, lazy-loaded)

### Composer Dependencies (if used)
```json
{
    "require": {
        "php": ">=7.4",
        "dompdf/dompdf": "^2.0",
        "stripe/stripe-php": "^10.0"
    }
}
```

*Note: For WordPress.org distribution, avoid Composer and bundle dependencies manually.*

---

## Development Guidelines

1. **Coding Standards**: Follow WordPress Coding Standards
2. **Documentation**: PHPDoc all classes and methods
3. **Hooks**: Add hooks liberally for extensibility
4. **Translations**: Use `__()` and `_e()` for all strings
5. **Escaping**: Escape all output with `esc_html()`, `esc_attr()`, `esc_url()`, etc.
6. **Database**: Use `$wpdb` methods, never raw queries
7. **AJAX**: Use `wp_ajax_` hooks, verify nonces
8. **REST API**: Use WP REST API standards

---

## Next Steps

1. [ ] Create main plugin file with basic structure
2. [ ] Implement activation/deactivation with table creation
3. [ ] Build Contact model and admin interface
4. [ ] Add tagging system
5. [ ] Implement transaction tracking
6. [ ] Integrate PayPal API
7. [ ] Integrate Stripe API
8. [ ] Build CSV import wizard
9. [ ] Create invoicing system
10. [ ] Build dashboard with graphs
11. [ ] Add REST API and webhooks
12. [ ] Final polish and documentation
