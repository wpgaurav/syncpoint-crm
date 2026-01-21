# Starter CRM - Hooks & Filters Reference

**Version**: 1.0.0  
**Last Updated**: 2026-01-21

This document lists all action hooks and filters available in Starter CRM for developers to extend functionality.

---

## Table of Contents

1. [Contact Hooks](#contact-hooks)
2. [Company Hooks](#company-hooks)
3. [Transaction Hooks](#transaction-hooks)
4. [Invoice Hooks](#invoice-hooks)
5. [Tag Hooks](#tag-hooks)
6. [PayPal Integration Hooks](#paypal-integration-hooks)
7. [Stripe Integration Hooks](#stripe-integration-hooks)
8. [Import Hooks](#import-hooks)
9. [Dashboard Hooks](#dashboard-hooks)
10. [API & Webhook Hooks](#api--webhook-hooks)
11. [General Hooks](#general-hooks)

---

## Contact Hooks

### Actions

#### `scrm_contact_created`
Fired after a new contact is created.

```php
do_action( 'scrm_contact_created', int $contact_id, array $contact_data );
```

**Parameters:**
- `$contact_id` (int) — The new contact's database ID
- `$contact_data` (array) — The contact data that was saved

**Example:**
```php
add_action( 'scrm_contact_created', function( $contact_id, $data ) {
    // Send welcome email to new customers
    if ( $data['type'] === 'customer' ) {
        wp_mail( $data['email'], 'Welcome!', 'Thanks for becoming a customer.' );
    }
}, 10, 2 );
```

---

#### `scrm_contact_updated`
Fired after a contact is updated.

```php
do_action( 'scrm_contact_updated', int $contact_id, array $contact_data, array $old_data );
```

**Parameters:**
- `$contact_id` (int) — The contact's database ID
- `$contact_data` (array) — The new contact data
- `$old_data` (array) — The previous contact data before update

**Example:**
```php
add_action( 'scrm_contact_updated', function( $contact_id, $new_data, $old_data ) {
    // Log status changes
    if ( $new_data['status'] !== $old_data['status'] ) {
        error_log( "Contact {$contact_id} status changed from {$old_data['status']} to {$new_data['status']}" );
    }
}, 10, 3 );
```

---

#### `scrm_contact_deleted`
Fired after a contact is deleted.

```php
do_action( 'scrm_contact_deleted', int $contact_id );
```

---

#### `scrm_before_contact_save`
Fired before a contact is saved (create or update).

```php
do_action( 'scrm_before_contact_save', array $contact_data );
```

---

#### `scrm_after_contact_save`
Fired after a contact is saved (create or update).

```php
do_action( 'scrm_after_contact_save', int $contact_id, array $contact_data );
```

---

### Filters

#### `scrm_contact_types`
Filter the available contact types.

```php
apply_filters( 'scrm_contact_types', array $types );
```

**Default:**
```php
array( 'customer', 'lead', 'prospect' )
```

**Example:**
```php
add_filter( 'scrm_contact_types', function( $types ) {
    $types[] = 'partner';
    $types[] = 'vendor';
    return $types;
} );
```

---

#### `scrm_contact_statuses`
Filter the available contact statuses.

```php
apply_filters( 'scrm_contact_statuses', array $statuses );
```

**Default:**
```php
array( 'active', 'inactive', 'archived' )
```

---

#### `scrm_contact_id_format`
Filter the format of auto-generated contact IDs.

```php
apply_filters( 'scrm_contact_id_format', string $format, string $contact_type );
```

**Default:** `'CUST-{number}'`

**Placeholders:**
- `{number}` — Auto-incrementing number
- `{year}` — Current 4-digit year
- `{month}` — Current 2-digit month

**Example:**
```php
add_filter( 'scrm_contact_id_format', function( $format, $type ) {
    if ( $type === 'lead' ) {
        return 'LEAD-{year}-{number}';
    }
    return $format;
}, 10, 2 );
```

---

#### `scrm_contact_columns`
Filter the columns displayed in the contacts list table.

```php
apply_filters( 'scrm_contact_columns', array $columns );
```

---

#### `scrm_contact_data_before_save`
Filter contact data before saving to database.

```php
apply_filters( 'scrm_contact_data_before_save', array $data );
```

**Example:**
```php
add_filter( 'scrm_contact_data_before_save', function( $data ) {
    // Always capitalize names
    $data['first_name'] = ucfirst( strtolower( $data['first_name'] ) );
    $data['last_name'] = ucfirst( strtolower( $data['last_name'] ) );
    return $data;
} );
```

---

## Company Hooks

### Actions

#### `scrm_company_created`
Fired after a new company is created.

```php
do_action( 'scrm_company_created', int $company_id, array $company_data );
```

---

#### `scrm_company_updated`
Fired after a company is updated.

```php
do_action( 'scrm_company_updated', int $company_id, array $company_data );
```

---

#### `scrm_company_deleted`
Fired after a company is deleted.

```php
do_action( 'scrm_company_deleted', int $company_id );
```

---

### Filters

#### `scrm_company_id_format`
Filter the format of auto-generated company IDs.

```php
apply_filters( 'scrm_company_id_format', string $format );
```

**Default:** `'COMP-{number}'`

---

#### `scrm_company_industries`
Filter the list of available industries.

```php
apply_filters( 'scrm_company_industries', array $industries );
```

**Example:**
```php
add_filter( 'scrm_company_industries', function( $industries ) {
    return array(
        'technology'    => 'Technology',
        'healthcare'    => 'Healthcare',
        'finance'       => 'Finance',
        'education'     => 'Education',
        'retail'        => 'Retail',
        'manufacturing' => 'Manufacturing',
        'other'         => 'Other',
    );
} );
```

---

## Transaction Hooks

### Actions

#### `scrm_transaction_created`
Fired after a transaction is recorded.

```php
do_action( 'scrm_transaction_created', int $transaction_id, array $transaction_data );
```

---

#### `scrm_transaction_updated`
Fired after a transaction is updated.

```php
do_action( 'scrm_transaction_updated', int $transaction_id, array $transaction_data );
```

---

#### `scrm_payment_received`
Fired when a payment is successfully received.

```php
do_action( 'scrm_payment_received', int $transaction_id, int $contact_id, float $amount, string $currency );
```

**Example:**
```php
add_action( 'scrm_payment_received', function( $txn_id, $contact_id, $amount, $currency ) {
    // Send thank you email
    $contact = scrm_get_contact( $contact_id );
    wp_mail( 
        $contact->email, 
        'Payment Received', 
        "Thank you for your payment of {$currency} {$amount}!"
    );
}, 10, 4 );
```

---

#### `scrm_refund_processed`
Fired when a refund is processed.

```php
do_action( 'scrm_refund_processed', int $refund_transaction_id, int $original_transaction_id );
```

---

### Filters

#### `scrm_transaction_types`
Filter the available transaction types.

```php
apply_filters( 'scrm_transaction_types', array $types );
```

**Default:**
```php
array( 'payment', 'refund', 'subscription', 'payout' )
```

---

#### `scrm_transaction_gateways`
Filter the available payment gateways.

```php
apply_filters( 'scrm_transaction_gateways', array $gateways );
```

**Default:**
```php
array( 'paypal', 'stripe', 'manual', 'webhook' )
```

---

#### `scrm_supported_currencies`
Filter the list of supported currencies.

```php
apply_filters( 'scrm_supported_currencies', array $currencies );
```

**Example:**
```php
add_filter( 'scrm_supported_currencies', function( $currencies ) {
    // Add Indian Rupee
    $currencies['INR'] = array(
        'name'     => 'Indian Rupee',
        'symbol'   => '₹',
        'decimals' => 2,
    );
    return $currencies;
} );
```

---

#### `scrm_default_currency`
Filter the default currency.

```php
apply_filters( 'scrm_default_currency', string $currency );
```

**Default:** `'USD'`

---

#### `scrm_format_currency`
Filter the formatted currency output.

```php
apply_filters( 'scrm_format_currency', string $formatted, float $amount, string $currency );
```

---

## Invoice Hooks

### Actions

#### `scrm_invoice_created`
Fired after an invoice is created.

```php
do_action( 'scrm_invoice_created', int $invoice_id, array $invoice_data );
```

---

#### `scrm_invoice_sent`
Fired after an invoice email is sent.

```php
do_action( 'scrm_invoice_sent', int $invoice_id, int $contact_id );
```

---

#### `scrm_invoice_viewed`
Fired when a client views an invoice.

```php
do_action( 'scrm_invoice_viewed', int $invoice_id );
```

---

#### `scrm_invoice_paid`
Fired when an invoice is marked as paid.

```php
do_action( 'scrm_invoice_paid', int $invoice_id, int $transaction_id );
```

---

#### `scrm_invoice_cancelled`
Fired when an invoice is cancelled.

```php
do_action( 'scrm_invoice_cancelled', int $invoice_id );
```

---

#### `scrm_invoice_pdf_generated`
Fired after a PDF is generated for an invoice.

```php
do_action( 'scrm_invoice_pdf_generated', int $invoice_id, string $pdf_path );
```

---

### Filters

#### `scrm_invoice_number_format`
Filter the invoice number format.

```php
apply_filters( 'scrm_invoice_number_format', string $format );
```

**Default:** `'INV-{year}-{number}'`

**Placeholders:**
- `{number}` — Auto-incrementing number (zero-padded)
- `{year}` — Current 4-digit year
- `{month}` — Current 2-digit month

---

#### `scrm_invoice_statuses`
Filter available invoice statuses.

```php
apply_filters( 'scrm_invoice_statuses', array $statuses );
```

**Default:**
```php
array( 'draft', 'sent', 'viewed', 'paid', 'overdue', 'cancelled' )
```

---

#### `scrm_invoice_payment_methods`
Filter available payment methods for invoices.

```php
apply_filters( 'scrm_invoice_payment_methods', array $methods );
```

**Default:**
```php
array( 'paypal', 'stripe', 'bank' )
```

---

#### `scrm_invoice_pdf_template`
Filter the PDF template path.

```php
apply_filters( 'scrm_invoice_pdf_template', string $template_path, int $invoice_id );
```

---

#### `scrm_invoice_email_subject`
Filter the invoice email subject.

```php
apply_filters( 'scrm_invoice_email_subject', string $subject, int $invoice_id );
```

---

#### `scrm_invoice_email_body`
Filter the invoice email body.

```php
apply_filters( 'scrm_invoice_email_body', string $body, int $invoice_id );
```

---

#### `scrm_invoice_line_item`
Filter line item data before rendering.

```php
apply_filters( 'scrm_invoice_line_item', array $item_data, int $invoice_id );
```

---

## Tag Hooks

### Actions

#### `scrm_tag_created`
Fired after a tag is created.

```php
do_action( 'scrm_tag_created', int $tag_id, array $tag_data );
```

---

#### `scrm_tag_assigned`
Fired after a tag is assigned to an object.

```php
do_action( 'scrm_tag_assigned', int $tag_id, int $object_id, string $object_type );
```

---

#### `scrm_tag_removed`
Fired after a tag is removed from an object.

```php
do_action( 'scrm_tag_removed', int $tag_id, int $object_id, string $object_type );
```

---

### Filters

#### `scrm_tag_colors`
Filter the default tag color options.

```php
apply_filters( 'scrm_tag_colors', array $colors );
```

**Example:**
```php
add_filter( 'scrm_tag_colors', function( $colors ) {
    return array(
        '#EF4444' => 'Red',
        '#F59E0B' => 'Amber',
        '#10B981' => 'Green',
        '#3B82F6' => 'Blue',
        '#8B5CF6' => 'Purple',
        '#EC4899' => 'Pink',
        '#6B7280' => 'Gray',
    );
} );
```

---

#### `scrm_taggable_types`
Filter which object types can have tags.

```php
apply_filters( 'scrm_taggable_types', array $types );
```

**Default:**
```php
array( 'contact', 'company', 'transaction', 'invoice' )
```

---

## PayPal Integration Hooks

### Actions

#### `scrm_paypal_connected`
Fired when PayPal account is successfully connected.

```php
do_action( 'scrm_paypal_connected' );
```

---

#### `scrm_paypal_disconnected`
Fired when PayPal account is disconnected.

```php
do_action( 'scrm_paypal_disconnected' );
```

---

#### `scrm_paypal_sync_started`
Fired when PayPal data sync begins.

```php
do_action( 'scrm_paypal_sync_started' );
```

---

#### `scrm_paypal_sync_completed`
Fired when PayPal data sync completes.

```php
do_action( 'scrm_paypal_sync_completed', int $imported_count );
```

---

#### `scrm_paypal_webhook_received`
Fired when a PayPal webhook is received.

```php
do_action( 'scrm_paypal_webhook_received', string $event_type, array $payload );
```

---

#### `scrm_paypal_transaction_imported`
Fired for each transaction imported from PayPal.

```php
do_action( 'scrm_paypal_transaction_imported', int $transaction_id, array $paypal_data );
```

---

### Filters

#### `scrm_paypal_api_url`
Filter the PayPal API URL.

```php
apply_filters( 'scrm_paypal_api_url', string $url );
```

---

#### `scrm_paypal_sync_limit`
Filter the number of transactions to sync per batch.

```php
apply_filters( 'scrm_paypal_sync_limit', int $limit );
```

**Default:** `100`

---

#### `scrm_paypal_transaction_mapping`
Filter the field mapping for PayPal transactions.

```php
apply_filters( 'scrm_paypal_transaction_mapping', array $mapping );
```

---

## Stripe Integration Hooks

### Actions

#### `scrm_stripe_connected`
Fired when Stripe is successfully connected.

```php
do_action( 'scrm_stripe_connected' );
```

---

#### `scrm_stripe_sync_started`
Fired when Stripe data sync begins.

```php
do_action( 'scrm_stripe_sync_started' );
```

---

#### `scrm_stripe_sync_completed`
Fired when Stripe data sync completes.

```php
do_action( 'scrm_stripe_sync_completed', int $imported_count );
```

---

#### `scrm_stripe_webhook_received`
Fired when a Stripe webhook is received.

```php
do_action( 'scrm_stripe_webhook_received', string $event_type, object $event );
```

---

#### `scrm_stripe_customer_imported`
Fired for each customer imported from Stripe.

```php
do_action( 'scrm_stripe_customer_imported', int $contact_id, object $stripe_customer );
```

---

#### `scrm_stripe_transaction_imported`
Fired for each transaction imported from Stripe.

```php
do_action( 'scrm_stripe_transaction_imported', int $transaction_id, object $stripe_charge );
```

---

### Filters

#### `scrm_stripe_customer_mapping`
Filter the field mapping for Stripe customers.

```php
apply_filters( 'scrm_stripe_customer_mapping', array $mapping );
```

---

#### `scrm_stripe_sync_types`
Filter which Stripe data types to sync.

```php
apply_filters( 'scrm_stripe_sync_types', array $types );
```

**Default:**
```php
array( 'charges', 'subscriptions', 'refunds' )
```

---

## Import Hooks

### Actions

#### `scrm_import_started`
Fired when an import begins.

```php
do_action( 'scrm_import_started', string $import_type, array $file_info );
```

---

#### `scrm_import_row_processed`
Fired for each row processed during import.

```php
do_action( 'scrm_import_row_processed', int $row_number, array $data, array $result );
```

---

#### `scrm_import_completed`
Fired when an import completes.

```php
do_action( 'scrm_import_completed', string $import_type, array $stats );
```

**Stats array:**
```php
array(
    'total'    => 100,
    'created'  => 80,
    'updated'  => 15,
    'skipped'  => 3,
    'errors'   => 2,
)
```

---

#### `scrm_import_failed`
Fired when an import fails.

```php
do_action( 'scrm_import_failed', string $import_type, WP_Error $error );
```

---

### Filters

#### `scrm_import_field_mapping`
Filter the field mapping for imports.

```php
apply_filters( 'scrm_import_field_mapping', array $mapping, string $import_type );
```

---

#### `scrm_import_row_data`
Filter row data before processing.

```php
apply_filters( 'scrm_import_row_data', array $data, int $row_number, string $import_type );
```

---

#### `scrm_import_batch_size`
Filter the number of rows processed per batch.

```php
apply_filters( 'scrm_import_batch_size', int $size );
```

**Default:** `50`

---

#### `scrm_import_supported_types`
Filter which object types can be imported.

```php
apply_filters( 'scrm_import_supported_types', array $types );
```

**Default:**
```php
array( 'contacts', 'companies', 'transactions' )
```

---

## Dashboard Hooks

### Actions

#### `scrm_dashboard_widgets`
Add custom dashboard widgets.

```php
do_action( 'scrm_dashboard_widgets' );
```

**Example:**
```php
add_action( 'scrm_dashboard_widgets', function() {
    ?>
    <div class="scrm-widget">
        <h3>Custom Widget</h3>
        <p>Your custom widget content here.</p>
    </div>
    <?php
} );
```

---

#### `scrm_before_dashboard_render`
Fired before dashboard rendering.

```php
do_action( 'scrm_before_dashboard_render' );
```

---

#### `scrm_after_dashboard_render`
Fired after dashboard rendering.

```php
do_action( 'scrm_after_dashboard_render' );
```

---

### Filters

#### `scrm_dashboard_periods`
Filter available time periods for dashboard.

```php
apply_filters( 'scrm_dashboard_periods', array $periods );
```

**Default:**
```php
array(
    '7days'  => 'Last 7 Days',
    '30days' => 'Last 30 Days',
    '90days' => 'Last 90 Days',
    'year'   => 'This Year',
    'all'    => 'All Time',
)
```

---

#### `scrm_dashboard_widgets`
Filter dashboard widgets.

```php
apply_filters( 'scrm_dashboard_widgets', array $widgets );
```

---

#### `scrm_dashboard_stats`
Filter dashboard statistics.

```php
apply_filters( 'scrm_dashboard_stats', array $stats, string $period );
```

---

#### `scrm_chart_colors`
Filter chart color palette.

```php
apply_filters( 'scrm_chart_colors', array $colors );
```

**Default:**
```php
array( '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899' )
```

---

## API & Webhook Hooks

### Actions

#### `scrm_api_contact_created`
Fired when a contact is created via REST API.

```php
do_action( 'scrm_api_contact_created', int $contact_id, WP_REST_Request $request );
```

---

#### `scrm_webhook_received`
Fired when any webhook is received.

```php
do_action( 'scrm_webhook_received', string $source, array $payload );
```

---

#### `scrm_webhook_processed`
Fired after a webhook is successfully processed.

```php
do_action( 'scrm_webhook_processed', string $source, array $payload, array $result );
```

---

#### `scrm_webhook_failed`
Fired when webhook processing fails.

```php
do_action( 'scrm_webhook_failed', string $source, array $payload, WP_Error $error );
```

---

### Filters

#### `scrm_api_contact_response`
Filter REST API contact response.

```php
apply_filters( 'scrm_api_contact_response', array $response, object $contact );
```

---

#### `scrm_webhook_sources`
Filter allowed webhook sources.

```php
apply_filters( 'scrm_webhook_sources', array $sources );
```

**Default:**
```php
array( 'paypal', 'stripe', 'custom' )
```

---

#### `scrm_webhook_authentication`
Filter webhook authentication validation.

```php
apply_filters( 'scrm_webhook_authentication', bool $is_valid, string $source, WP_REST_Request $request );
```

---

#### `scrm_webhook_payload_mapping`
Filter webhook payload field mapping.

```php
apply_filters( 'scrm_webhook_payload_mapping', array $mapping, string $source );
```

---

## General Hooks

### Actions

#### `scrm_init`
Fired after Starter CRM is fully initialized.

```php
do_action( 'scrm_init' );
```

---

#### `scrm_loaded`
Fired when the plugin is loaded.

```php
do_action( 'scrm_loaded' );
```

---

#### `scrm_admin_init`
Fired on admin pages after initialization.

```php
do_action( 'scrm_admin_init' );
```

---

#### `scrm_activity_logged`
Fired when an activity is logged.

```php
do_action( 'scrm_activity_logged', int $log_id, string $action, int $object_id, string $object_type );
```

---

### Filters

#### `scrm_capability_mappings`
Filter capability to role mappings.

```php
apply_filters( 'scrm_capability_mappings', array $mappings );
```

**Default:**
```php
array(
    'scrm_manage_contacts'     => 'manage_options',
    'scrm_manage_companies'    => 'manage_options',
    'scrm_manage_transactions' => 'manage_options',
    'scrm_manage_invoices'     => 'manage_options',
    'scrm_manage_settings'     => 'manage_options',
    'scrm_view_dashboard'      => 'manage_options',
    'scrm_import_data'         => 'manage_options',
    'scrm_export_data'         => 'manage_options',
)
```

---

#### `scrm_admin_menu_capability`
Filter the capability required to access admin menus.

```php
apply_filters( 'scrm_admin_menu_capability', string $capability );
```

**Default:** `'manage_options'`

---

#### `scrm_custom_field_types`
Filter available custom field types.

```php
apply_filters( 'scrm_custom_field_types', array $types );
```

**Default:**
```php
array(
    'text'     => 'Text',
    'textarea' => 'Textarea',
    'url'      => 'URL',
    'email'    => 'Email',
    'number'   => 'Number',
    'select'   => 'Select',
    'checkbox' => 'Checkbox',
    'date'     => 'Date',
)
```

---

#### `scrm_date_format`
Filter the date format used throughout the plugin.

```php
apply_filters( 'scrm_date_format', string $format );
```

**Default:** Uses WordPress date format from settings.

---

## Practical Examples

### Example 1: Add a Custom Contact Type

```php
// Add "Affiliate" as a contact type
add_filter( 'scrm_contact_types', function( $types ) {
    $types[] = 'affiliate';
    return $types;
} );

// Custom ID format for affiliates
add_filter( 'scrm_contact_id_format', function( $format, $type ) {
    if ( $type === 'affiliate' ) {
        return 'AFF-{year}-{number}';
    }
    return $format;
}, 10, 2 );
```

---

### Example 2: Auto-tag High-value Customers

```php
add_action( 'scrm_payment_received', function( $txn_id, $contact_id, $amount, $currency ) {
    // Tag as VIP if payment is over $1000
    if ( $amount >= 1000 ) {
        $vip_tag = scrm_get_tag_by_slug( 'vip' );
        if ( $vip_tag ) {
            scrm_assign_tag( $vip_tag->id, $contact_id, 'contact' );
        }
    }
}, 10, 4 );
```

---

### Example 3: Custom Webhook Processing

```php
// Add custom webhook source
add_filter( 'scrm_webhook_sources', function( $sources ) {
    $sources[] = 'gravity_forms';
    return $sources;
} );

// Process Gravity Forms webhook
add_action( 'scrm_webhook_received', function( $source, $payload ) {
    if ( $source !== 'gravity_forms' ) {
        return;
    }
    
    // Create contact from form submission
    $contact_data = array(
        'first_name' => $payload['first_name'] ?? '',
        'last_name'  => $payload['last_name'] ?? '',
        'email'      => $payload['email'] ?? '',
        'type'       => 'lead',
        'source'     => 'Gravity Forms',
    );
    
    scrm_create_contact( $contact_data );
}, 10, 2 );
```

---

### Example 4: Custom Dashboard Widget

```php
add_action( 'scrm_dashboard_widgets', function() {
    $pending_invoices = scrm_get_invoices( array( 
        'status' => 'sent', 
        'count'  => true 
    ) );
    ?>
    <div class="scrm-widget scrm-widget--warning">
        <h3>Pending Invoices</h3>
        <p class="scrm-widget__number"><?php echo esc_html( $pending_invoices ); ?></p>
        <a href="<?php echo admin_url( 'admin.php?page=scrm-invoices&status=sent' ); ?>">View All</a>
    </div>
    <?php
} );
```

---

## Best Practices

1. **Always use all parameters** — Even if you don't need them, accept all parameters for forward compatibility.

2. **Return filtered values** — When using filters, always return a value (modified or not).

3. **Priority matters** — Use appropriate priorities (default is 10) to ensure proper execution order.

4. **Check context** — Verify you're in the right context before executing code in hooks.

5. **Handle errors gracefully** — Wrap hook callbacks in try/catch for complex operations.

```php
add_action( 'scrm_payment_received', function( $txn_id, $contact_id, $amount, $currency ) {
    try {
        // Your code here
    } catch ( Exception $e ) {
        error_log( 'SCRM Hook Error: ' . $e->getMessage() );
    }
}, 10, 4 );
```
