# Starter CRM - Webhooks Integration Guide

**Version**: 1.0.0  
**Last Updated**: 2026-01-21

This guide covers how to receive data from external services via webhooks and how to configure outgoing webhooks to notify external systems.

---

## Table of Contents

1. [Incoming Webhooks](#incoming-webhooks)
2. [Payment Gateway Webhooks](#payment-gateway-webhooks)
3. [Form Integration Examples](#form-integration-examples)
4. [Outgoing Webhooks](#outgoing-webhooks)
5. [Security](#security)
6. [Troubleshooting](#troubleshooting)

---

## Incoming Webhooks

Starter CRM provides endpoints to receive data from external services, allowing you to automatically create contacts, log transactions, and more.

### Generic Webhook Endpoint

```
POST https://yoursite.com/wp-json/scrm/v1/webhooks/inbound
```

This endpoint accepts JSON payloads and can create contacts, companies, or transactions.

### Authentication

All webhook requests must include authentication. Choose one method:

#### Method 1: API Key Header

```bash
curl -X POST "https://yoursite.com/wp-json/scrm/v1/webhooks/inbound" \
  -H "X-SCRM-Webhook-Key: your-webhook-secret-key" \
  -H "Content-Type: application/json" \
  -d '{"action": "create_contact", "data": {...}}'
```

#### Method 2: Query Parameter

```
POST https://yoursite.com/wp-json/scrm/v1/webhooks/inbound?key=your-webhook-secret-key
```

#### Method 3: Signature Verification (Recommended)

For services that sign their payloads (like Stripe), the signature is verified automatically.

### Finding Your Webhook Key

1. Go to **CRM → Settings → Webhooks**
2. Copy the **Webhook Secret Key**
3. Use this key in your webhook configuration

---

## Webhook Payload Format

### Standard Payload Structure

```json
{
  "action": "create_contact",
  "source": "my_integration",
  "data": {
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com"
  },
  "metadata": {
    "form_id": "contact_form",
    "submitted_at": "2026-01-21T10:00:00Z"
  }
}
```

### Supported Actions

| Action | Description |
|--------|-------------|
| `create_contact` | Create a new contact |
| `update_contact` | Update existing contact (requires `contact_id` or `email`) |
| `create_company` | Create a new company |
| `create_transaction` | Record a transaction |
| `tag_contact` | Add tags to a contact |
| `custom` | Custom action (handled by filters) |

---

## Create Contact via Webhook

### Minimal Payload

```json
{
  "action": "create_contact",
  "data": {
    "email": "jane@example.com"
  }
}
```

### Full Payload

```json
{
  "action": "create_contact",
  "source": "landing_page",
  "data": {
    "first_name": "Jane",
    "last_name": "Smith",
    "email": "jane@example.com",
    "phone": "+1-555-123-4567",
    "type": "lead",
    "company_name": "Acme Corp",
    "address": {
      "line_1": "123 Main St",
      "city": "New York",
      "state": "NY",
      "postal_code": "10001",
      "country": "US"
    },
    "custom_fields": {
      "interests": ["email marketing", "automation"],
      "budget": "$5000"
    },
    "tags": ["from-landing-page", "newsletter"]
  },
  "metadata": {
    "utm_source": "google",
    "utm_campaign": "spring2026",
    "landing_page": "/pricing"
  }
}
```

### Response

**Success (201 Created):**
```json
{
  "success": true,
  "data": {
    "id": 500,
    "contact_id": "LEAD-2026-500",
    "email": "jane@example.com",
    "created": true
  },
  "message": "Contact created successfully"
}
```

**Duplicate Contact (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 250,
    "contact_id": "CUST-250",
    "email": "jane@example.com",
    "created": false,
    "updated": true
  },
  "message": "Existing contact updated"
}
```

---

## Create Transaction via Webhook

```json
{
  "action": "create_transaction",
  "data": {
    "contact_email": "john@example.com",
    "type": "payment",
    "amount": 499.00,
    "currency": "USD",
    "description": "Product purchase",
    "gateway": "webhook",
    "status": "completed",
    "gateway_transaction_id": "ext_txn_12345",
    "metadata": {
      "product_id": "prod_123",
      "product_name": "Premium Plan"
    }
  }
}
```

### Contact Identification

You can identify the contact by:

1. `contact_id` — Database ID or custom ID (e.g., `CUST-001`)
2. `contact_email` — Email address (will create contact if not found)

---

## Payment Gateway Webhooks

### PayPal Webhooks

**Endpoint:**
```
POST https://yoursite.com/wp-json/scrm/v1/webhooks/paypal
```

#### Configuring PayPal Webhooks

1. Log in to [PayPal Developer Dashboard](https://developer.paypal.com)
2. Go to **My Apps & Credentials**
3. Select your app
4. Scroll to **Webhooks** and click **Add Webhook**
5. Enter your webhook URL: `https://yoursite.com/wp-json/scrm/v1/webhooks/paypal`
6. Select events to subscribe:
   - `PAYMENT.CAPTURE.COMPLETED`
   - `PAYMENT.CAPTURE.REFUNDED`
   - `BILLING.SUBSCRIPTION.CREATED`
   - `BILLING.SUBSCRIPTION.CANCELLED`
   - `INVOICING.INVOICE.PAID`

#### Supported PayPal Events

| Event Type | SCRM Action |
|------------|-------------|
| `PAYMENT.CAPTURE.COMPLETED` | Creates transaction, links to contact |
| `PAYMENT.CAPTURE.REFUNDED` | Creates refund transaction |
| `BILLING.SUBSCRIPTION.CREATED` | Creates subscription transaction |
| `BILLING.SUBSCRIPTION.CANCELLED` | Updates subscription status |
| `INVOICING.INVOICE.PAID` | Marks invoice as paid |

#### PayPal Webhook Verification

PayPal webhooks are verified using the webhook ID and signature. Configure in **CRM → Settings → PayPal**:

- Webhook ID (from PayPal Developer Dashboard)

---

### Stripe Webhooks

**Endpoint:**
```
POST https://yoursite.com/wp-json/scrm/v1/webhooks/stripe
```

#### Configuring Stripe Webhooks

1. Log in to [Stripe Dashboard](https://dashboard.stripe.com)
2. Go to **Developers → Webhooks**
3. Click **Add Endpoint**
4. Enter your webhook URL: `https://yoursite.com/wp-json/scrm/v1/webhooks/stripe`
5. Select events:
   - `charge.succeeded`
   - `charge.refunded`
   - `customer.created`
   - `customer.updated`
   - `invoice.paid`
   - `invoice.payment_failed`
   - `customer.subscription.created`
   - `customer.subscription.deleted`
6. Copy the **Signing Secret** and add it in **CRM → Settings → Stripe**

#### Supported Stripe Events

| Event Type | SCRM Action |
|------------|-------------|
| `charge.succeeded` | Creates payment transaction |
| `charge.refunded` | Creates refund transaction |
| `customer.created` | Creates contact from Stripe customer |
| `customer.updated` | Updates contact details |
| `invoice.paid` | Marks invoice as paid (if linked) |
| `customer.subscription.created` | Creates subscription record |
| `customer.subscription.deleted` | Updates subscription status |

#### Stripe Signature Verification

All Stripe webhooks are verified using the `Stripe-Signature` header and your webhook signing secret. This is handled automatically.

---

## Form Integration Examples

### Contact Form 7 (WordPress)

Use the [CF7 Webhook](https://wordpress.org/plugins/cf7-webhook/) plugin or custom code:

```php
add_action( 'wpcf7_mail_sent', function( $form ) {
    $submission = WPCF7_Submission::get_instance();
    $data = $submission->get_posted_data();
    
    $webhook_url = 'https://yoursite.com/wp-json/scrm/v1/webhooks/inbound';
    $webhook_key = 'your-webhook-secret-key';
    
    $payload = array(
        'action' => 'create_contact',
        'source' => 'contact_form_7',
        'data'   => array(
            'first_name' => $data['first-name'] ?? '',
            'last_name'  => $data['last-name'] ?? '',
            'email'      => $data['email'] ?? '',
            'phone'      => $data['phone'] ?? '',
            'type'       => 'lead',
        ),
        'metadata' => array(
            'form_id'   => $form->id(),
            'form_name' => $form->title(),
            'message'   => $data['message'] ?? '',
        ),
    );
    
    wp_remote_post( $webhook_url, array(
        'headers' => array(
            'Content-Type'         => 'application/json',
            'X-SCRM-Webhook-Key' => $webhook_key,
        ),
        'body' => wp_json_encode( $payload ),
    ) );
} );
```

---

### Gravity Forms

Using Gravity Forms Webhooks Add-on or custom code:

```php
add_action( 'gform_after_submission', function( $entry, $form ) {
    $webhook_url = 'https://yoursite.com/wp-json/scrm/v1/webhooks/inbound';
    
    $payload = array(
        'action' => 'create_contact',
        'source' => 'gravity_forms',
        'data'   => array(
            'first_name' => rgar( $entry, '1.3' ), // Field IDs vary
            'last_name'  => rgar( $entry, '1.6' ),
            'email'      => rgar( $entry, '2' ),
            'phone'      => rgar( $entry, '3' ),
            'type'       => 'lead',
            'tags'       => array( 'gravity-forms', 'inquiry' ),
        ),
        'metadata' => array(
            'form_id'    => $form['id'],
            'form_title' => $form['title'],
            'entry_id'   => $entry['id'],
        ),
    );
    
    wp_remote_post( $webhook_url, array(
        'headers' => array(
            'Content-Type'         => 'application/json',
            'X-SCRM-Webhook-Key' => 'your-webhook-secret-key',
        ),
        'body' => wp_json_encode( $payload ),
    ) );
}, 10, 2 );
```

---

### WPForms

```php
add_action( 'wpforms_process_complete', function( $fields, $entry, $form_data ) {
    $webhook_url = 'https://yoursite.com/wp-json/scrm/v1/webhooks/inbound';
    
    // Map WPForms fields to contact data
    $contact_data = array(
        'type' => 'lead',
    );
    
    foreach ( $fields as $field ) {
        switch ( $field['type'] ) {
            case 'name':
                $contact_data['first_name'] = $field['first'] ?? '';
                $contact_data['last_name']  = $field['last'] ?? '';
                break;
            case 'email':
                $contact_data['email'] = $field['value'];
                break;
            case 'phone':
                $contact_data['phone'] = $field['value'];
                break;
        }
    }
    
    $payload = array(
        'action' => 'create_contact',
        'source' => 'wpforms',
        'data'   => $contact_data,
    );
    
    wp_remote_post( $webhook_url, array(
        'headers' => array(
            'Content-Type'         => 'application/json',
            'X-SCRM-Webhook-Key' => 'your-webhook-secret-key',
        ),
        'body' => wp_json_encode( $payload ),
    ) );
}, 10, 3 );
```

---

### Typeform

Configure Typeform webhook to post to your endpoint:

1. Open your Typeform
2. Go to **Connect → Webhooks**
3. Add your endpoint URL with the key: `https://yoursite.com/wp-json/scrm/v1/webhooks/inbound?key=your-webhook-key`

Then use a filter to map Typeform data:

```php
add_filter( 'scrm_webhook_payload_mapping', function( $mapping, $source ) {
    if ( $source === 'typeform' ) {
        return array(
            'email'      => 'form_response.answers.0.email',
            'first_name' => 'form_response.answers.1.text',
            'last_name'  => 'form_response.answers.2.text',
        );
    }
    return $mapping;
}, 10, 2 );
```

---

### Zapier

Create a Zap with "Webhooks by Zapier" as the action:

1. **Action**: POST to `https://yoursite.com/wp-json/scrm/v1/webhooks/inbound`
2. **Headers**:
   - `Content-Type`: `application/json`
   - `X-SCRM-Webhook-Key`: `your-webhook-secret-key`
3. **Body**:
```json
{
  "action": "create_contact",
  "source": "zapier",
  "data": {
    "first_name": "{{trigger__first_name}}",
    "last_name": "{{trigger__last_name}}",
    "email": "{{trigger__email}}",
    "type": "lead"
  }
}
```

---

### Make (Integromat)

Create an HTTP module with:

- **URL**: `https://yoursite.com/wp-json/scrm/v1/webhooks/inbound`
- **Method**: POST
- **Headers**:
  - `Content-Type`: `application/json`
  - `X-SCRM-Webhook-Key`: `your-webhook-secret-key`
- **Body**: JSON with mapped fields

---

### n8n

Use the HTTP Request node:

```json
{
  "nodes": [
    {
      "parameters": {
        "url": "https://yoursite.com/wp-json/scrm/v1/webhooks/inbound",
        "method": "POST",
        "headers": {
          "X-SCRM-Webhook-Key": "your-webhook-secret-key"
        },
        "body": {
          "action": "create_contact",
          "source": "n8n",
          "data": {
            "email": "={{$json.email}}",
            "first_name": "={{$json.firstName}}",
            "last_name": "={{$json.lastName}}"
          }
        }
      }
    }
  ]
}
```

---

### Calendly

Configure Calendly webhook:

1. Go to **Integrations → Webhooks**
2. Add your endpoint
3. Select `invitee.created` event

Map Calendly payload:

```php
add_filter( 'scrm_webhook_payload_mapping', function( $mapping, $source ) {
    if ( strpos( $_SERVER['HTTP_USER_AGENT'] ?? '', 'Calendly' ) !== false ) {
        return array(
            'action'     => 'create_contact',
            'email'      => 'payload.invitee.email',
            'first_name' => 'payload.invitee.first_name',
            'last_name'  => 'payload.invitee.last_name',
            'source'     => 'calendly',
            'tags'       => array( 'calendly-booking' ),
        );
    }
    return $mapping;
}, 10, 2 );
```

---

## Outgoing Webhooks

Configure Starter CRM to notify external systems when events occur.

### Configuration

Go to **CRM → Settings → Webhooks → Outgoing**

Add webhook endpoints:
- **URL**: Your external endpoint
- **Events**: Select which events trigger the webhook
- **Secret**: Optional secret for signature verification

### Available Events

| Event | Trigger |
|-------|---------|
| `contact.created` | New contact created |
| `contact.updated` | Contact updated |
| `contact.deleted` | Contact deleted |
| `company.created` | New company created |
| `company.updated` | Company updated |
| `transaction.created` | New transaction recorded |
| `invoice.created` | New invoice created |
| `invoice.sent` | Invoice emailed to contact |
| `invoice.viewed` | Contact viewed invoice |
| `invoice.paid` | Invoice marked as paid |

### Outgoing Payload Format

```json
{
  "event": "contact.created",
  "timestamp": "2026-01-21T10:30:00Z",
  "version": "1.0",
  "data": {
    "id": 500,
    "contact_id": "CUST-500",
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "type": "customer",
    "status": "active",
    "created_at": "2026-01-21T10:30:00Z"
  }
}
```

### Signature Verification

If you configure a webhook secret, each request includes a signature header:

```
X-SCRM-Signature: sha256=5d50... (HMAC-SHA256 of payload with secret)
```

Verify in your receiving application:

```php
$payload = file_get_contents( 'php://input' );
$signature = $_SERVER['HTTP_X_SCRM_SIGNATURE'] ?? '';
$expected = 'sha256=' . hash_hmac( 'sha256', $payload, 'your-secret' );

if ( ! hash_equals( $expected, $signature ) ) {
    http_response_code( 401 );
    exit( 'Invalid signature' );
}
```

```javascript
const crypto = require('crypto');

function verifySignature(payload, signature, secret) {
  const expected = 'sha256=' + crypto
    .createHmac('sha256', secret)
    .update(payload)
    .digest('hex');
  return crypto.timingSafeEqual(
    Buffer.from(signature),
    Buffer.from(expected)
  );
}
```

---

## Custom Webhook Processing

Extend webhook handling with filters:

### Custom Webhook Source

```php
// Register custom webhook source
add_filter( 'scrm_webhook_sources', function( $sources ) {
    $sources[] = 'my_custom_service';
    return $sources;
} );

// Custom authentication for your service
add_filter( 'scrm_webhook_authentication', function( $is_valid, $source, $request ) {
    if ( $source === 'my_custom_service' ) {
        $api_key = $request->get_header( 'X-My-Service-Key' );
        return $api_key === 'expected-key';
    }
    return $is_valid;
}, 10, 3 );

// Custom payload processing
add_action( 'scrm_webhook_received', function( $source, $payload ) {
    if ( $source !== 'my_custom_service' ) {
        return;
    }
    
    // Transform payload to contact data
    $contact_data = array(
        'email'      => $payload['user']['email_address'],
        'first_name' => $payload['user']['given_name'],
        'last_name'  => $payload['user']['family_name'],
        'type'       => 'lead',
        'source'     => 'my_custom_service',
        'tags'       => array( 'from-custom-service' ),
    );
    
    scrm_create_contact( $contact_data );
}, 10, 2 );
```

---

### Field Mapping

Map external field names to SCRM fields:

```php
add_filter( 'scrm_webhook_payload_mapping', function( $mapping, $source ) {
    if ( $source === 'hubspot' ) {
        return array(
            'email'      => 'properties.email',
            'first_name' => 'properties.firstname',
            'last_name'  => 'properties.lastname',
            'phone'      => 'properties.phone',
            'company'    => 'properties.company',
        );
    }
    return $mapping;
}, 10, 2 );
```

---

## Security

### Best Practices

1. **Always use HTTPS** — Never send webhook data over HTTP
2. **Verify signatures** — Use signature verification when available
3. **Validate IP addresses** — Whitelist known service IPs if possible
4. **Use unique webhook keys** — Don't reuse keys across services
5. **Log everything** — Enable webhook logging for debugging

### IP Whitelisting

Configure allowed IPs in **CRM → Settings → Webhooks**:

```
# PayPal IPs
173.0.82.0/24
173.0.84.0/24

# Stripe IPs
3.18.12.63
3.130.192.231

# Your custom service
203.0.113.0/24
```

### Webhook Logging

All webhooks are logged in the database. View logs at **CRM → Settings → Webhooks → Logs**

Log includes:
- Source
- Payload (sanitized)
- Status (success/failed)
- Response time
- Error message (if failed)

---

## Troubleshooting

### Common Issues

#### Webhook Not Receiving Data

1. Check the webhook URL is correct and accessible
2. Verify HTTPS certificate is valid
3. Check WordPress isn't blocking the request (security plugins)
4. Verify the webhook key is correct

#### Signature Verification Failed

1. Ensure you're using the correct signing secret
2. Check the payload wasn't modified in transit
3. Verify timestamp is within acceptable range

#### Contact Not Created

1. Check required fields are present (email is required)
2. Verify the email format is valid
3. Check webhook logs for specific error messages

#### Duplicate Contacts

By default, webhooks update existing contacts if the email matches. To force creation:

```json
{
  "action": "create_contact",
  "options": {
    "skip_duplicate_check": true
  },
  "data": {...}
}
```

### Testing Webhooks

Use these tools to test webhook integration:

1. **Webhook.site** — Free webhook testing and debugging
2. **RequestBin** — Inspect incoming webhook payloads
3. **ngrok** — Tunnel local development to public URL

#### Local Testing with ngrok

```bash
# Start ngrok tunnel
ngrok http 80

# Use the ngrok URL in your webhook configuration
# https://abc123.ngrok.io/wp-json/scrm/v1/webhooks/inbound
```

#### Manual Test Request

```bash
curl -X POST "https://yoursite.com/wp-json/scrm/v1/webhooks/inbound" \
  -H "Content-Type: application/json" \
  -H "X-SCRM-Webhook-Key: your-webhook-key" \
  -d '{
    "action": "create_contact",
    "source": "test",
    "data": {
      "email": "test@example.com",
      "first_name": "Test",
      "last_name": "User",
      "type": "lead"
    }
  }'
```

### Debug Mode

Enable debug mode for detailed logging:

```php
// Add to wp-config.php
define( 'SCRM_WEBHOOK_DEBUG', true );
```

This logs all webhook requests to `wp-content/debug.log`.

---

## Webhook Reference

### Request Headers Sent (Outgoing)

| Header | Description |
|--------|-------------|
| `Content-Type` | `application/json` |
| `X-SCRM-Signature` | HMAC signature (if secret configured) |
| `X-SCRM-Event` | Event type (e.g., `contact.created`) |
| `X-SCRM-Delivery` | Unique delivery ID |
| `User-Agent` | `StarterCRM/1.0` |

### Expected Response

Your webhook endpoint should return:

- **2xx status**: Webhook processed successfully
- **4xx status**: Client error (won't retry)
- **5xx status**: Server error (will retry)

### Retry Policy

Failed webhooks (5xx responses or timeouts) are retried:

- 1st retry: 1 minute
- 2nd retry: 5 minutes
- 3rd retry: 30 minutes
- 4th retry: 2 hours
- 5th retry: 24 hours

After 5 failed attempts, the webhook delivery is marked as failed.
