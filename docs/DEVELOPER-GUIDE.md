# Starter CRM - Developer Guide

**Version**: 1.0.0  
**Last Updated**: 2026-01-21

This guide covers how to extend Starter CRM with custom functionality, add-ons, and integrations.

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Helper Functions](#helper-functions)
3. [Extending Models](#extending-models)
4. [Custom Fields](#custom-fields)
5. [Creating Add-ons](#creating-add-ons)
6. [Custom Gateways](#custom-gateways)
7. [Custom Importers](#custom-importers)
8. [Dashboard Widgets](#dashboard-widgets)
9. [Templates & Overrides](#templates--overrides)
10. [Database Access](#database-access)
11. [Caching](#caching)
12. [Testing](#testing)

---

## Architecture Overview

### Namespace & Autoloading

Starter CRM uses PSR-4 autoloading with the `SCRM` namespace:

```
SCRM\           → includes/
SCRM\Admin\     → includes/admin/
SCRM\Core\      → includes/core/
SCRM\Gateways\  → includes/gateways/
SCRM\API\       → includes/api/
SCRM\Import\    → includes/import/
SCRM\Export\    → includes/export/
SCRM\Utils\     → includes/utils/
```

### Main Classes

| Class | Purpose |
|-------|---------|
| `SCRM\Plugin` | Main plugin class, singleton |
| `SCRM\Loader` | Hooks and filters registration |
| `SCRM\Core\Contact` | Contact model |
| `SCRM\Core\Company` | Company model |
| `SCRM\Core\Transaction` | Transaction model |
| `SCRM\Core\Invoice` | Invoice model |
| `SCRM\Core\Tag` | Tag model |
| `SCRM\Admin\Admin` | Admin controller |
| `SCRM\API\REST_API` | REST API controller |

### Initialization

```php
// Get plugin instance
$scrm = SCRM\Plugin::instance();

// Access components
$scrm->admin;      // Admin controller
$scrm->api;        // REST API controller
$scrm->gateways;   // Payment gateways manager
```

---

## Helper Functions

Starter CRM provides helper functions for common operations.

### Contacts

```php
// Get contact by ID (database ID or custom ID)
$contact = scrm_get_contact( 123 );
$contact = scrm_get_contact( 'CUST-001' );

// Get contact by email
$contact = scrm_get_contact_by_email( 'john@example.com' );

// Create contact
$contact_id = scrm_create_contact( array(
    'first_name' => 'John',
    'last_name'  => 'Doe',
    'email'      => 'john@example.com',
    'type'       => 'customer',
) );

// Update contact
scrm_update_contact( $contact_id, array(
    'phone' => '+1-555-123-4567',
) );

// Delete contact
scrm_delete_contact( $contact_id );

// Archive contact (soft delete)
scrm_archive_contact( $contact_id );

// Get contacts with query
$contacts = scrm_get_contacts( array(
    'type'   => 'customer',
    'status' => 'active',
    'tag'    => 'vip',
    'limit'  => 50,
    'offset' => 0,
    'orderby' => 'created_at',
    'order'   => 'DESC',
) );

// Count contacts
$count = scrm_count_contacts( array( 'type' => 'lead' ) );
```

### Companies

```php
// Get company
$company = scrm_get_company( 5 );
$company = scrm_get_company( 'COMP-005' );

// Create company
$company_id = scrm_create_company( array(
    'name'  => 'Acme Corp',
    'email' => 'hello@acme.com',
) );

// Update company
scrm_update_company( $company_id, array(
    'website' => 'https://acme.com',
) );

// Get company contacts
$contacts = scrm_get_company_contacts( $company_id );

// Get companies
$companies = scrm_get_companies( array(
    'industry' => 'technology',
    'limit'    => 20,
) );
```

### Transactions

```php
// Get transaction
$transaction = scrm_get_transaction( $id );

// Create transaction
$txn_id = scrm_create_transaction( array(
    'contact_id'  => 123,
    'type'        => 'payment',
    'gateway'     => 'manual',
    'amount'      => 500.00,
    'currency'    => 'USD',
    'status'      => 'completed',
    'description' => 'Consulting services',
) );

// Get contact transactions
$transactions = scrm_get_contact_transactions( $contact_id, array(
    'type'   => 'payment',
    'status' => 'completed',
) );

// Calculate contact lifetime value
$ltv = scrm_get_contact_ltv( $contact_id, 'USD' );
```

### Invoices

```php
// Get invoice
$invoice = scrm_get_invoice( $id );
$invoice = scrm_get_invoice( 'INV-2026-050' );

// Create invoice
$invoice_id = scrm_create_invoice( array(
    'contact_id' => 123,
    'issue_date' => '2026-01-21',
    'due_date'   => '2026-02-20',
    'line_items' => array(
        array(
            'description' => 'Web Development',
            'quantity'    => 1,
            'unit_price'  => 2500.00,
        ),
    ),
    'tax_rate'   => 10,
    'currency'   => 'USD',
) );

// Send invoice email
scrm_send_invoice( $invoice_id );

// Mark as paid
scrm_mark_invoice_paid( $invoice_id, $transaction_id );

// Generate PDF
$pdf_path = scrm_generate_invoice_pdf( $invoice_id );
```

### Tags

```php
// Get tag
$tag = scrm_get_tag( $id );
$tag = scrm_get_tag_by_slug( 'vip' );

// Create tag
$tag_id = scrm_create_tag( array(
    'name'        => 'VIP',
    'color'       => '#EF4444',
    'description' => 'High-value customers',
) );

// Assign tag to object
scrm_assign_tag( $tag_id, $contact_id, 'contact' );

// Remove tag from object
scrm_remove_tag( $tag_id, $contact_id, 'contact' );

// Get tags for object
$tags = scrm_get_object_tags( $contact_id, 'contact' );

// Check if object has tag
$has_tag = scrm_object_has_tag( $contact_id, 'contact', 'vip' );

// Get objects with tag
$contacts = scrm_get_tagged_objects( $tag_id, 'contact' );
```

### Currency

```php
// Get default currency
$currency = scrm_get_default_currency(); // 'USD'

// Format currency
$formatted = scrm_format_currency( 1500.00, 'USD' ); // '$1,500.00'
$formatted = scrm_format_currency( 1500.00, 'EUR' ); // '€1,500.00'

// Get currency symbol
$symbol = scrm_get_currency_symbol( 'USD' ); // '$'

// Get supported currencies
$currencies = scrm_get_currencies();

// Convert currency (requires exchange rate data)
$converted = scrm_convert_currency( 100, 'USD', 'EUR' );
```

### Settings

```php
// Get setting
$currency = scrm_get_setting( 'general', 'default_currency', 'USD' );

// Get all settings in group
$paypal_settings = scrm_get_settings( 'paypal' );

// Update setting
scrm_update_setting( 'general', 'default_currency', 'EUR' );

// Check if feature is enabled
$paypal_enabled = scrm_is_enabled( 'paypal' );
```

### Utilities

```php
// Generate custom ID
$contact_id = scrm_generate_id( 'contact' ); // 'CUST-001'
$company_id = scrm_generate_id( 'company' ); // 'COMP-001'
$invoice_no = scrm_generate_id( 'invoice' ); // 'INV-2026-001'

// Log activity
scrm_log_activity( 'contact', $contact_id, 'email_sent', 'Invoice sent to contact', array(
    'invoice_id' => $invoice_id,
) );

// Get activity log
$activities = scrm_get_activity_log( 'contact', $contact_id, 20 );

// Check capability
if ( scrm_current_user_can( 'manage_contacts' ) ) {
    // User can manage contacts
}
```

---

## Extending Models

### Adding Custom Methods to Contact

```php
add_action( 'scrm_init', function() {
    // Add method to Contact class
    SCRM\Core\Contact::extend( 'get_full_name', function() {
        return trim( $this->first_name . ' ' . $this->last_name );
    } );
    
    SCRM\Core\Contact::extend( 'get_open_invoices', function() {
        return scrm_get_invoices( array(
            'contact_id' => $this->id,
            'status'     => array( 'sent', 'viewed', 'overdue' ),
        ) );
    } );
} );

// Usage
$contact = scrm_get_contact( 123 );
echo $contact->get_full_name();
$invoices = $contact->get_open_invoices();
```

### Custom Contact Types

```php
// Add custom contact type
add_filter( 'scrm_contact_types', function( $types ) {
    $types['partner'] = array(
        'label'  => 'Partner',
        'icon'   => 'dashicons-groups',
        'color'  => '#8B5CF6',
    );
    $types['vendor'] = array(
        'label'  => 'Vendor',
        'icon'   => 'dashicons-store',
        'color'  => '#F59E0B',
    );
    return $types;
} );

// Custom ID format for new types
add_filter( 'scrm_contact_id_format', function( $format, $type ) {
    $formats = array(
        'partner' => 'PART-{year}-{number}',
        'vendor'  => 'VEND-{number}',
    );
    return $formats[ $type ] ?? $format;
}, 10, 2 );
```

---

## Custom Fields

### Registering Custom Fields

```php
// Add custom fields for contacts
add_filter( 'scrm_contact_custom_fields', function( $fields ) {
    $fields[] = array(
        'id'          => 'linkedin_url',
        'label'       => 'LinkedIn Profile',
        'type'        => 'url',
        'placeholder' => 'https://linkedin.com/in/username',
        'position'    => 10,
    );
    
    $fields[] = array(
        'id'       => 'preferred_contact',
        'label'    => 'Preferred Contact Method',
        'type'     => 'select',
        'options'  => array(
            'email' => 'Email',
            'phone' => 'Phone',
            'text'  => 'Text Message',
        ),
        'default'  => 'email',
        'position' => 20,
    );
    
    $fields[] = array(
        'id'       => 'newsletter_opt_in',
        'label'    => 'Newsletter Opt-in',
        'type'     => 'checkbox',
        'default'  => false,
        'position' => 30,
    );
    
    return $fields;
} );

// Add custom fields for companies
add_filter( 'scrm_company_custom_fields', function( $fields ) {
    $fields[] = array(
        'id'    => 'annual_revenue',
        'label' => 'Annual Revenue',
        'type'  => 'select',
        'options' => array(
            'under_100k'   => 'Under $100K',
            '100k_500k'    => '$100K - $500K',
            '500k_1m'      => '$500K - $1M',
            '1m_5m'        => '$1M - $5M',
            '5m_plus'      => '$5M+',
        ),
    );
    return $fields;
} );
```

### Custom Field Types

```php
// Register custom field type
add_filter( 'scrm_custom_field_types', function( $types ) {
    $types['color_picker'] = array(
        'label'    => 'Color Picker',
        'callback' => 'my_render_color_picker',
        'sanitize' => 'sanitize_hex_color',
    );
    return $types;
} );

function my_render_color_picker( $field, $value ) {
    ?>
    <input 
        type="color" 
        name="custom_fields[<?php echo esc_attr( $field['id'] ); ?>]"
        value="<?php echo esc_attr( $value ); ?>"
        id="<?php echo esc_attr( $field['id'] ); ?>"
    />
    <?php
}
```

### Accessing Custom Fields

```php
// Get custom field value
$linkedin = scrm_get_contact_meta( $contact_id, 'linkedin_url' );

// Set custom field value
scrm_update_contact_meta( $contact_id, 'linkedin_url', 'https://linkedin.com/in/johndoe' );

// Delete custom field
scrm_delete_contact_meta( $contact_id, 'linkedin_url' );

// Get all custom fields at once
$contact = scrm_get_contact( $contact_id );
$custom_fields = $contact->custom_fields; // JSON decoded array
```

---

## Creating Add-ons

### Add-on Structure

```
starter-crm-my-addon/
├── starter-crm-my-addon.php    # Main plugin file
├── includes/
│   └── class-my-addon.php
├── assets/
│   ├── css/
│   └── js/
└── readme.txt
```

### Main Plugin File

```php
<?php
/**
 * Plugin Name: Starter CRM - My Add-on
 * Description: Extends Starter CRM with additional features
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Your Name
 * Text Domain: scrm-my-addon
 */

defined( 'ABSPATH' ) || exit;

// Check if Starter CRM is active
function scrm_my_addon_check_dependencies() {
    if ( ! class_exists( 'SCRM\Plugin' ) ) {
        add_action( 'admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong>Starter CRM - My Add-on</strong> requires 
                    <strong>Starter CRM</strong> to be installed and activated.
                </p>
            </div>
            <?php
        } );
        return false;
    }
    return true;
}

// Initialize add-on
add_action( 'plugins_loaded', function() {
    if ( ! scrm_my_addon_check_dependencies() ) {
        return;
    }
    
    // Check minimum version
    if ( version_compare( SCRM_VERSION, '1.0.0', '<' ) ) {
        add_action( 'admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong>Starter CRM - My Add-on</strong> requires 
                    Starter CRM version 1.0.0 or higher.
                </p>
            </div>
            <?php
        } );
        return;
    }
    
    // Load add-on
    require_once __DIR__ . '/includes/class-my-addon.php';
    SCRM_My_Addon::instance();
} );
```

### Add-on Class

```php
<?php
class SCRM_My_Addon {
    
    private static $instance = null;
    
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Add menu item
        add_action( 'scrm_admin_menu', array( $this, 'add_menu_page' ) );
        
        // Add custom fields
        add_filter( 'scrm_contact_custom_fields', array( $this, 'add_custom_fields' ) );
        
        // Hook into contact creation
        add_action( 'scrm_contact_created', array( $this, 'on_contact_created' ), 10, 2 );
        
        // Enqueue assets
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }
    
    public function add_menu_page( $parent_slug ) {
        add_submenu_page(
            $parent_slug,
            __( 'My Add-on', 'scrm-my-addon' ),
            __( 'My Add-on', 'scrm-my-addon' ),
            'scrm_manage_settings',
            'scrm-my-addon',
            array( $this, 'render_page' )
        );
    }
    
    public function add_custom_fields( $fields ) {
        $fields[] = array(
            'id'    => 'my_addon_field',
            'label' => __( 'My Addon Field', 'scrm-my-addon' ),
            'type'  => 'text',
        );
        return $fields;
    }
    
    public function on_contact_created( $contact_id, $data ) {
        // Do something when contact is created
        scrm_log_activity( 
            'contact', 
            $contact_id, 
            'my_addon_action', 
            'My addon processed this contact'
        );
    }
    
    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'scrm-my-addon' ) === false ) {
            return;
        }
        
        wp_enqueue_style( 
            'scrm-my-addon', 
            plugins_url( 'assets/css/style.css', __DIR__ ),
            array(),
            '1.0.0'
        );
        
        wp_enqueue_script(
            'scrm-my-addon',
            plugins_url( 'assets/js/script.js', __DIR__ ),
            array( 'jquery' ),
            '1.0.0',
            true
        );
    }
    
    public function render_page() {
        ?>
        <div class="wrap scrm-wrap">
            <h1><?php esc_html_e( 'My Add-on', 'scrm-my-addon' ); ?></h1>
            <p>Add-on content here.</p>
        </div>
        <?php
    }
}
```

---

## Custom Gateways

### Gateway Interface

Create a custom payment gateway by extending the abstract gateway class:

```php
<?php
namespace SCRM\Gateways;

class My_Gateway extends Abstract_Gateway {
    
    public function __construct() {
        $this->id          = 'my_gateway';
        $this->title       = __( 'My Payment Gateway', 'starter-crm' );
        $this->description = __( 'Accept payments via My Gateway', 'starter-crm' );
        $this->icon        = ''; // URL to icon
        
        parent::__construct();
    }
    
    /**
     * Get settings fields
     */
    public function get_settings_fields() {
        return array(
            array(
                'id'    => 'enabled',
                'label' => __( 'Enable', 'starter-crm' ),
                'type'  => 'checkbox',
            ),
            array(
                'id'    => 'api_key',
                'label' => __( 'API Key', 'starter-crm' ),
                'type'  => 'password',
            ),
            array(
                'id'    => 'sandbox_mode',
                'label' => __( 'Sandbox Mode', 'starter-crm' ),
                'type'  => 'checkbox',
            ),
        );
    }
    
    /**
     * Check if gateway is available
     */
    public function is_available() {
        return $this->get_setting( 'enabled' ) && $this->get_setting( 'api_key' );
    }
    
    /**
     * Generate payment link for invoice
     */
    public function get_payment_url( $invoice ) {
        $api_key = $this->get_setting( 'api_key' );
        $sandbox = $this->get_setting( 'sandbox_mode' );
        
        // Call your gateway API to create payment link
        $response = wp_remote_post( 'https://api.mygateway.com/create-payment', array(
            'body' => array(
                'api_key'     => $api_key,
                'amount'      => $invoice->total,
                'currency'    => $invoice->currency,
                'description' => sprintf( 'Invoice %s', $invoice->invoice_number ),
                'return_url'  => $this->get_return_url( $invoice ),
                'webhook_url' => $this->get_webhook_url(),
            ),
        ) );
        
        if ( is_wp_error( $response ) ) {
            return '';
        }
        
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        return $data['payment_url'] ?? '';
    }
    
    /**
     * Process webhook notification
     */
    public function process_webhook( $payload ) {
        $transaction_id = $payload['transaction_id'] ?? '';
        $status         = $payload['status'] ?? '';
        
        if ( $status === 'completed' ) {
            // Find invoice and mark as paid
            $invoice = scrm_get_invoice_by_meta( 'gateway_reference', $transaction_id );
            if ( $invoice ) {
                $txn_id = scrm_create_transaction( array(
                    'contact_id'             => $invoice->contact_id,
                    'invoice_id'             => $invoice->id,
                    'type'                   => 'payment',
                    'gateway'                => $this->id,
                    'gateway_transaction_id' => $transaction_id,
                    'amount'                 => $payload['amount'],
                    'currency'               => $payload['currency'],
                    'status'                 => 'completed',
                ) );
                
                scrm_mark_invoice_paid( $invoice->id, $txn_id );
            }
        }
    }
    
    /**
     * Sync transactions from gateway
     */
    public function sync_transactions( $date_from = null, $date_to = null ) {
        // Implement transaction sync logic
    }
}
```

### Registering Gateway

```php
add_filter( 'scrm_payment_gateways', function( $gateways ) {
    $gateways['my_gateway'] = 'SCRM\Gateways\My_Gateway';
    return $gateways;
} );
```

---

## Custom Importers

### Importer Class

```php
<?php
namespace SCRM\Import;

class My_Service_Importer {
    
    private $api_key;
    
    public function __construct( $api_key ) {
        $this->api_key = $api_key;
    }
    
    /**
     * Import contacts from external service
     */
    public function import_contacts( $options = array() ) {
        $defaults = array(
            'limit'         => 100,
            'offset'        => 0,
            'update_existing' => true,
        );
        $options = wp_parse_args( $options, $defaults );
        
        // Fetch from external API
        $response = wp_remote_get( 'https://api.myservice.com/contacts', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
            ),
            'body' => array(
                'limit'  => $options['limit'],
                'offset' => $options['offset'],
            ),
        ) );
        
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        
        $stats = array(
            'total'   => count( $data['contacts'] ),
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors'  => 0,
        );
        
        foreach ( $data['contacts'] as $external_contact ) {
            $result = $this->import_single_contact( $external_contact, $options );
            
            if ( is_wp_error( $result ) ) {
                $stats['errors']++;
            } elseif ( $result['action'] === 'created' ) {
                $stats['created']++;
            } elseif ( $result['action'] === 'updated' ) {
                $stats['updated']++;
            } else {
                $stats['skipped']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Import single contact
     */
    private function import_single_contact( $external_data, $options ) {
        // Map external fields to SCRM fields
        $contact_data = array(
            'first_name' => $external_data['given_name'] ?? '',
            'last_name'  => $external_data['family_name'] ?? '',
            'email'      => $external_data['email_address'] ?? '',
            'phone'      => $external_data['phone_number'] ?? '',
            'type'       => 'customer',
            'source'     => 'my_service',
            'custom_fields' => array(
                'my_service_id' => $external_data['id'],
            ),
        );
        
        // Check for existing contact
        $existing = scrm_get_contact_by_email( $contact_data['email'] );
        
        if ( $existing ) {
            if ( $options['update_existing'] ) {
                scrm_update_contact( $existing->id, $contact_data );
                return array( 'action' => 'updated', 'id' => $existing->id );
            }
            return array( 'action' => 'skipped', 'id' => $existing->id );
        }
        
        // Create new contact
        $contact_id = scrm_create_contact( $contact_data );
        
        if ( is_wp_error( $contact_id ) ) {
            return $contact_id;
        }
        
        return array( 'action' => 'created', 'id' => $contact_id );
    }
}
```

### Registering Importer

```php
add_filter( 'scrm_import_sources', function( $sources ) {
    $sources['my_service'] = array(
        'label'       => __( 'My Service', 'starter-crm' ),
        'description' => __( 'Import contacts from My Service', 'starter-crm' ),
        'class'       => 'SCRM\Import\My_Service_Importer',
        'settings'    => array(
            array(
                'id'    => 'api_key',
                'label' => __( 'API Key', 'starter-crm' ),
                'type'  => 'password',
            ),
        ),
    );
    return $sources;
} );
```

---

## Dashboard Widgets

### Adding Custom Widget

```php
add_action( 'scrm_dashboard_widgets', function() {
    ?>
    <div class="scrm-dashboard-widget scrm-widget--standard">
        <div class="scrm-widget__header">
            <h3><?php esc_html_e( 'My Custom Widget', 'starter-crm' ); ?></h3>
        </div>
        <div class="scrm-widget__content">
            <?php
            // Your widget content
            $recent_leads = scrm_get_contacts( array(
                'type'    => 'lead',
                'orderby' => 'created_at',
                'order'   => 'DESC',
                'limit'   => 5,
            ) );
            
            if ( $recent_leads ) {
                echo '<ul>';
                foreach ( $recent_leads as $lead ) {
                    echo '<li>' . esc_html( $lead->first_name . ' ' . $lead->last_name ) . '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p>' . esc_html__( 'No recent leads.', 'starter-crm' ) . '</p>';
            }
            ?>
        </div>
    </div>
    <?php
} );
```

### Widget with Chart

```php
add_action( 'scrm_dashboard_widgets', function() {
    ?>
    <div class="scrm-dashboard-widget scrm-widget--chart">
        <div class="scrm-widget__header">
            <h3><?php esc_html_e( 'Lead Sources', 'starter-crm' ); ?></h3>
        </div>
        <div class="scrm-widget__content">
            <canvas id="my-lead-sources-chart"></canvas>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Chart !== 'undefined') {
            const ctx = document.getElementById('my-lead-sources-chart');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Website', 'Referral', 'Social', 'Other'],
                    datasets: [{
                        data: [45, 25, 20, 10],
                        backgroundColor: [
                            '#3B82F6',
                            '#10B981',
                            '#F59E0B',
                            '#6B7280'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    });
    </script>
    <?php
} );
```

---

## Templates & Overrides

### Template Hierarchy

Starter CRM looks for templates in the following order:

1. `your-theme/starter-crm/{template-name}.php`
2. `your-theme/starter-crm/templates/{template-name}.php`
3. `starter-crm/templates/{template-name}.php`

### Overriding Templates

Copy the template from `starter-crm/templates/` to your theme:

```
your-theme/
└── starter-crm/
    └── invoices/
        └── invoice-view.php    # Override invoice public view
```

### Template Functions

```php
// Get template part
scrm_get_template( 'invoices/invoice-view.php', array(
    'invoice' => $invoice,
    'contact' => $contact,
) );

// Locate template file
$template_path = scrm_locate_template( 'invoices/invoice-view.php' );

// Check if template exists in theme
if ( scrm_template_exists_in_theme( 'invoices/invoice-view.php' ) ) {
    // Using theme override
}
```

### Template Hooks

Templates include hooks for customization:

```php
// Before invoice header
add_action( 'scrm_before_invoice_header', function( $invoice ) {
    echo '<div class="custom-banner">Special Offer!</div>';
} );

// After invoice line items
add_action( 'scrm_after_invoice_items', function( $invoice ) {
    echo '<p class="custom-note">Thank you for your business!</p>';
} );

// Invoice footer
add_action( 'scrm_invoice_footer', function( $invoice ) {
    echo '<p>Questions? Contact us at support@example.com</p>';
} );
```

---

## Database Access

### Using SCRM Database Class

```php
// Get database instance
$db = SCRM\Plugin::instance()->db;

// Query contacts table directly (raw query)
global $wpdb;
$table = $wpdb->prefix . 'scrm_contacts';
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$table} WHERE type = %s AND status = %s ORDER BY created_at DESC LIMIT %d",
        'customer',
        'active',
        10
    )
);

// Using query builder (if available)
$contacts = $db->contacts()
    ->where( 'type', 'customer' )
    ->where( 'status', 'active' )
    ->orderBy( 'created_at', 'DESC' )
    ->limit( 10 )
    ->get();
```

### Table Names

```php
global $wpdb;

$tables = array(
    'contacts'          => $wpdb->prefix . 'scrm_contacts',
    'companies'         => $wpdb->prefix . 'scrm_companies',
    'transactions'      => $wpdb->prefix . 'scrm_transactions',
    'invoices'          => $wpdb->prefix . 'scrm_invoices',
    'invoice_items'     => $wpdb->prefix . 'scrm_invoice_items',
    'tags'              => $wpdb->prefix . 'scrm_tags',
    'tag_relationships' => $wpdb->prefix . 'scrm_tag_relationships',
    'activity_log'      => $wpdb->prefix . 'scrm_activity_log',
    'webhook_log'       => $wpdb->prefix . 'scrm_webhook_log',
);
```

### Safe Database Operations

Always use prepared statements:

```php
// Insert
$wpdb->insert(
    $wpdb->prefix . 'scrm_contacts',
    array(
        'first_name' => 'John',
        'email'      => 'john@example.com',
        'type'       => 'customer',
        'created_at' => current_time( 'mysql' ),
    ),
    array( '%s', '%s', '%s', '%s' )
);
$contact_id = $wpdb->insert_id;

// Update
$wpdb->update(
    $wpdb->prefix . 'scrm_contacts',
    array( 'status' => 'inactive' ),
    array( 'id' => $contact_id ),
    array( '%s' ),
    array( '%d' )
);

// Delete
$wpdb->delete(
    $wpdb->prefix . 'scrm_contacts',
    array( 'id' => $contact_id ),
    array( '%d' )
);
```

---

## Caching

### Using SCRM Cache

```php
// Get cached value
$contacts = scrm_cache_get( 'recent_customers_10' );

if ( false === $contacts ) {
    $contacts = scrm_get_contacts( array(
        'type'  => 'customer',
        'limit' => 10,
    ) );
    
    // Cache for 1 hour
    scrm_cache_set( 'recent_customers_10', $contacts, HOUR_IN_SECONDS );
}

// Delete cached value
scrm_cache_delete( 'recent_customers_10' );

// Delete all cache with prefix
scrm_cache_delete_group( 'contacts' );
```

### Cache Groups

```php
// Contact cache is automatically invalidated when contacts change
add_action( 'scrm_contact_created', function( $contact_id ) {
    scrm_cache_delete_group( 'contacts' );
} );

add_action( 'scrm_contact_updated', function( $contact_id ) {
    scrm_cache_delete( 'contact_' . $contact_id );
    scrm_cache_delete_group( 'contact_lists' );
} );
```

---

## Testing

### Unit Testing Setup

```php
<?php
// tests/bootstrap.php
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
    $_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
    require dirname( dirname( __FILE__ ) ) . '/starter-crm.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
```

### Test Example

```php
<?php
class Test_SCRM_Contact extends WP_UnitTestCase {
    
    public function test_create_contact() {
        $contact_id = scrm_create_contact( array(
            'first_name' => 'Test',
            'last_name'  => 'User',
            'email'      => 'test@example.com',
            'type'       => 'lead',
        ) );
        
        $this->assertIsInt( $contact_id );
        $this->assertGreaterThan( 0, $contact_id );
        
        $contact = scrm_get_contact( $contact_id );
        
        $this->assertEquals( 'Test', $contact->first_name );
        $this->assertEquals( 'test@example.com', $contact->email );
        $this->assertEquals( 'lead', $contact->type );
    }
    
    public function test_contact_custom_id() {
        $contact_id = scrm_create_contact( array(
            'email' => 'custom@example.com',
            'type'  => 'customer',
        ) );
        
        $contact = scrm_get_contact( $contact_id );
        
        $this->assertMatchesRegularExpression( '/^CUST-\d+$/', $contact->contact_id );
    }
    
    public function test_contact_tags() {
        $contact_id = scrm_create_contact( array(
            'email' => 'tagged@example.com',
        ) );
        
        $tag_id = scrm_create_tag( array(
            'name'  => 'Test Tag',
            'color' => '#FF0000',
        ) );
        
        scrm_assign_tag( $tag_id, $contact_id, 'contact' );
        
        $this->assertTrue( scrm_object_has_tag( $contact_id, 'contact', 'test-tag' ) );
        
        $tags = scrm_get_object_tags( $contact_id, 'contact' );
        $this->assertCount( 1, $tags );
    }
}
```

### Running Tests

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test class
./vendor/bin/phpunit --filter Test_SCRM_Contact

# Run specific test method
./vendor/bin/phpunit --filter test_create_contact
```

---

## Coding Standards

### PHP Standards

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- Use PSR-4 autoloading for classes
- Document all functions with PHPDoc

### Naming Conventions

```php
// Functions use snake_case with scrm_ prefix
function scrm_get_contact( $id ) {}
function scrm_create_invoice( $data ) {}

// Classes use PascalCase in SCRM namespace
class SCRM\Core\Contact {}
class SCRM\Admin\Settings_Page {}

// Hooks use scrm_ prefix
do_action( 'scrm_contact_created', $id, $data );
apply_filters( 'scrm_contact_types', $types );

// Database tables use scrm_ prefix
$wpdb->prefix . 'scrm_contacts'

// Options use scrm_ prefix
get_option( 'scrm_settings' );

// Transients use scrm_ prefix
get_transient( 'scrm_dashboard_stats' );
```

### Security

```php
// Always verify nonces
if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'scrm_save_contact' ) ) {
    wp_die( 'Security check failed' );
}

// Check capabilities
if ( ! scrm_current_user_can( 'manage_contacts' ) ) {
    wp_die( 'Unauthorized' );
}

// Sanitize input
$email = sanitize_email( $_POST['email'] );
$name  = sanitize_text_field( $_POST['name'] );
$url   = esc_url_raw( $_POST['website'] );

// Escape output
echo esc_html( $contact->first_name );
echo esc_attr( $contact->email );
echo esc_url( $contact->website );
```
