# Starter CRM - REST API Documentation

**Version**: 1.0.0  
**Base URL**: `https://yoursite.com/wp-json/scrm/v1/`  
**Last Updated**: 2026-01-21

---

## Overview

Starter CRM provides a comprehensive REST API for integrating with external applications, building custom frontends, or automating workflows.

### Authentication

The API supports multiple authentication methods:

1. **Cookie Authentication** — For logged-in WordPress users (includes nonce)
2. **Application Passwords** — WordPress native app passwords (recommended for external apps)
3. **API Key** — Custom API key (optional, plugin-specific)

#### Using Application Passwords

```bash
curl -X GET "https://yoursite.com/wp-json/scrm/v1/contacts" \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx"
```

#### Using API Key (if enabled)

```bash
curl -X GET "https://yoursite.com/wp-json/scrm/v1/contacts" \
  -H "X-SCRM-API-Key: your-api-key-here"
```

### Response Format

All responses are JSON with the following structure:

**Success:**
```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "total": 100,
    "page": 1,
    "per_page": 20,
    "total_pages": 5
  }
}
```

**Error:**
```json
{
  "success": false,
  "code": "error_code",
  "message": "Human-readable error message",
  "data": null
}
```

### Common HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request (validation error) |
| 401 | Unauthorized |
| 403 | Forbidden (insufficient permissions) |
| 404 | Not Found |
| 422 | Unprocessable Entity |
| 500 | Internal Server Error |

---

## Contacts

### List Contacts

Retrieve a paginated list of contacts.

```http
GET /wp-json/scrm/v1/contacts
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | int | 1 | Page number |
| `per_page` | int | 20 | Items per page (max: 100) |
| `search` | string | - | Search by name or email |
| `type` | string | - | Filter by type: `customer`, `lead`, `prospect` |
| `status` | string | - | Filter by status: `active`, `inactive`, `archived` |
| `company_id` | int | - | Filter by company |
| `tag` | string/int | - | Filter by tag slug or ID |
| `orderby` | string | `created_at` | Sort by: `id`, `first_name`, `last_name`, `email`, `created_at`, `updated_at` |
| `order` | string | `DESC` | Sort order: `ASC` or `DESC` |
| `created_after` | string | - | ISO 8601 date |
| `created_before` | string | - | ISO 8601 date |

**Example Request:**
```bash
curl -X GET "https://yoursite.com/wp-json/scrm/v1/contacts?type=customer&status=active&per_page=50" \
  -u "username:app-password"
```

**Example Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "contact_id": "CUST-001",
      "type": "customer",
      "status": "active",
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com",
      "phone": "+1-555-123-4567",
      "company": {
        "id": 5,
        "company_id": "COMP-005",
        "name": "Acme Corp"
      },
      "currency": "USD",
      "tax_id": null,
      "address": {
        "line_1": "123 Main St",
        "line_2": "Suite 100",
        "city": "New York",
        "state": "NY",
        "postal_code": "10001",
        "country": "US"
      },
      "custom_fields": {
        "linkedin_url": "https://linkedin.com/in/johndoe"
      },
      "tags": [
        {
          "id": 1,
          "name": "VIP",
          "slug": "vip",
          "color": "#EF4444"
        }
      ],
      "source": "website",
      "created_at": "2025-06-15T10:30:00Z",
      "updated_at": "2026-01-20T14:22:00Z",
      "_links": {
        "self": "/wp-json/scrm/v1/contacts/1",
        "transactions": "/wp-json/scrm/v1/contacts/1/transactions",
        "invoices": "/wp-json/scrm/v1/contacts/1/invoices"
      }
    }
  ],
  "meta": {
    "total": 247,
    "page": 1,
    "per_page": 50,
    "total_pages": 5
  }
}
```

---

### Get Contact

Retrieve a single contact by ID.

```http
GET /wp-json/scrm/v1/contacts/{id}
```

**Parameters:**
- `id` (required) — Contact ID (database ID or custom ID like `CUST-001`)

**Example:**
```bash
curl -X GET "https://yoursite.com/wp-json/scrm/v1/contacts/CUST-001" \
  -u "username:app-password"
```

---

### Create Contact

Create a new contact.

```http
POST /wp-json/scrm/v1/contacts
```

**Request Body:**
```json
{
  "type": "customer",
  "status": "active",
  "first_name": "Jane",
  "last_name": "Smith",
  "email": "jane@example.com",
  "phone": "+1-555-987-6543",
  "company_id": 5,
  "currency": "EUR",
  "tax_id": "DE123456789",
  "address": {
    "line_1": "456 Oak Ave",
    "city": "Berlin",
    "country": "DE"
  },
  "custom_fields": {
    "linkedin_url": "https://linkedin.com/in/janesmith"
  },
  "tags": ["vip", "enterprise"],
  "source": "api"
}
```

**Required Fields:**
- `email` — Valid email address

**Example:**
```bash
curl -X POST "https://yoursite.com/wp-json/scrm/v1/contacts" \
  -u "username:app-password" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "lead",
    "first_name": "Bob",
    "last_name": "Wilson",
    "email": "bob@example.com",
    "source": "landing_page"
  }'
```

**Response (201 Created):**
```json
{
  "success": true,
  "data": {
    "id": 248,
    "contact_id": "LEAD-042",
    "type": "lead",
    "status": "active",
    "first_name": "Bob",
    "last_name": "Wilson",
    "email": "bob@example.com",
    ...
  }
}
```

---

### Update Contact

Update an existing contact.

```http
PUT /wp-json/scrm/v1/contacts/{id}
```

**Request Body:** Same fields as create (all optional for update)

**Example:**
```bash
curl -X PUT "https://yoursite.com/wp-json/scrm/v1/contacts/248" \
  -u "username:app-password" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "customer",
    "phone": "+1-555-111-2222"
  }'
```

---

### Delete Contact

Delete a contact.

```http
DELETE /wp-json/scrm/v1/contacts/{id}
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `force` | bool | false | If true, permanently delete. Otherwise, archive. |

**Example:**
```bash
curl -X DELETE "https://yoursite.com/wp-json/scrm/v1/contacts/248?force=true" \
  -u "username:app-password"
```

---

### Get Contact Transactions

Retrieve transactions for a specific contact.

```http
GET /wp-json/scrm/v1/contacts/{id}/transactions
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | int | 1 | Page number |
| `per_page` | int | 20 | Items per page |
| `type` | string | - | Filter by transaction type |
| `gateway` | string | - | Filter by payment gateway |

---

### Get Contact Invoices

Retrieve invoices for a specific contact.

```http
GET /wp-json/scrm/v1/contacts/{id}/invoices
```

---

## Companies

### List Companies

```http
GET /wp-json/scrm/v1/companies
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | int | 1 | Page number |
| `per_page` | int | 20 | Items per page |
| `search` | string | - | Search by name |
| `industry` | string | - | Filter by industry |
| `tag` | string | - | Filter by tag |
| `orderby` | string | `name` | Sort by field |
| `order` | string | `ASC` | Sort order |

**Example Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "company_id": "COMP-005",
      "name": "Acme Corp",
      "website": "https://acme.com",
      "email": "hello@acme.com",
      "phone": "+1-555-000-1111",
      "tax_id": "US12-3456789",
      "address": {
        "line_1": "1000 Corporate Blvd",
        "city": "San Francisco",
        "state": "CA",
        "postal_code": "94105",
        "country": "US"
      },
      "industry": "technology",
      "contacts_count": 12,
      "revenue_total": 45000.00,
      "custom_fields": {},
      "tags": [],
      "created_at": "2025-03-10T08:00:00Z",
      "updated_at": "2026-01-15T11:30:00Z"
    }
  ],
  "meta": {
    "total": 35,
    "page": 1,
    "per_page": 20,
    "total_pages": 2
  }
}
```

---

### Get Company

```http
GET /wp-json/scrm/v1/companies/{id}
```

---

### Create Company

```http
POST /wp-json/scrm/v1/companies
```

**Request Body:**
```json
{
  "name": "TechStartup Inc",
  "website": "https://techstartup.io",
  "email": "hello@techstartup.io",
  "phone": "+1-555-TECH",
  "tax_id": "US99-8765432",
  "address": {
    "line_1": "100 Startup Way",
    "city": "Austin",
    "state": "TX",
    "postal_code": "78701",
    "country": "US"
  },
  "industry": "technology",
  "custom_fields": {
    "annual_revenue": "$5M"
  },
  "tags": ["startup", "saas"]
}
```

**Required Fields:**
- `name` — Company name

---

### Update Company

```http
PUT /wp-json/scrm/v1/companies/{id}
```

---

### Delete Company

```http
DELETE /wp-json/scrm/v1/companies/{id}
```

---

### Get Company Contacts

```http
GET /wp-json/scrm/v1/companies/{id}/contacts
```

---

## Transactions

### List Transactions

```http
GET /wp-json/scrm/v1/transactions
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | int | 1 | Page number |
| `per_page` | int | 20 | Items per page |
| `contact_id` | int | - | Filter by contact |
| `invoice_id` | int | - | Filter by invoice |
| `type` | string | - | `payment`, `refund`, `subscription`, `payout` |
| `gateway` | string | - | `paypal`, `stripe`, `manual`, `webhook` |
| `status` | string | - | `pending`, `completed`, `failed`, `refunded` |
| `currency` | string | - | Filter by currency |
| `amount_min` | float | - | Minimum amount |
| `amount_max` | float | - | Maximum amount |
| `date_from` | string | - | Start date (ISO 8601) |
| `date_to` | string | - | End date (ISO 8601) |
| `orderby` | string | `created_at` | Sort field |
| `order` | string | `DESC` | Sort order |

**Example Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1024,
      "transaction_id": "TXN-2026-001024",
      "contact": {
        "id": 1,
        "contact_id": "CUST-001",
        "name": "John Doe"
      },
      "invoice": {
        "id": 50,
        "invoice_number": "INV-2026-050"
      },
      "type": "payment",
      "gateway": "stripe",
      "gateway_transaction_id": "ch_3OaB2cRG...",
      "amount": 499.00,
      "currency": "USD",
      "status": "completed",
      "description": "Annual subscription payment",
      "metadata": {
        "stripe_payment_method": "pm_card_visa",
        "card_last4": "4242"
      },
      "created_at": "2026-01-20T15:30:00Z",
      "updated_at": "2026-01-20T15:30:00Z"
    }
  ],
  "meta": {
    "total": 1024,
    "page": 1,
    "per_page": 20,
    "total_pages": 52,
    "totals": {
      "sum": 152340.00,
      "currency": "USD"
    }
  }
}
```

---

### Get Transaction

```http
GET /wp-json/scrm/v1/transactions/{id}
```

---

### Create Transaction

Create a manual transaction.

```http
POST /wp-json/scrm/v1/transactions
```

**Request Body:**
```json
{
  "contact_id": 1,
  "invoice_id": 50,
  "type": "payment",
  "gateway": "manual",
  "amount": 500.00,
  "currency": "USD",
  "status": "completed",
  "description": "Bank transfer payment",
  "metadata": {
    "bank_reference": "REF123456"
  }
}
```

**Required Fields:**
- `contact_id` — Contact ID
- `type` — Transaction type
- `amount` — Transaction amount
- `currency` — Currency code

---

### Update Transaction

```http
PUT /wp-json/scrm/v1/transactions/{id}
```

**Note:** Only `status`, `description`, and `metadata` can be updated after creation.

---

## Invoices

### List Invoices

```http
GET /wp-json/scrm/v1/invoices
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | int | 1 | Page number |
| `per_page` | int | 20 | Items per page |
| `contact_id` | int | - | Filter by contact |
| `company_id` | int | - | Filter by company |
| `status` | string | - | `draft`, `sent`, `viewed`, `paid`, `overdue`, `cancelled` |
| `currency` | string | - | Filter by currency |
| `total_min` | float | - | Minimum total |
| `total_max` | float | - | Maximum total |
| `issue_date_from` | string | - | Start date |
| `issue_date_to` | string | - | End date |
| `due_date_from` | string | - | Due date start |
| `due_date_to` | string | - | Due date end |
| `overdue` | bool | - | If true, only overdue invoices |
| `orderby` | string | `created_at` | Sort field |
| `order` | string | `DESC` | Sort order |

**Example Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 50,
      "invoice_number": "INV-2026-050",
      "contact": {
        "id": 1,
        "contact_id": "CUST-001",
        "name": "John Doe",
        "email": "john@example.com"
      },
      "company": {
        "id": 5,
        "company_id": "COMP-005",
        "name": "Acme Corp"
      },
      "status": "sent",
      "issue_date": "2026-01-15",
      "due_date": "2026-02-14",
      "line_items": [
        {
          "id": 1,
          "description": "Website Development",
          "quantity": 1,
          "unit_price": 2500.00,
          "total": 2500.00
        },
        {
          "id": 2,
          "description": "Monthly Hosting (12 months)",
          "quantity": 12,
          "unit_price": 25.00,
          "total": 300.00
        }
      ],
      "subtotal": 2800.00,
      "tax_rate": 10.00,
      "tax_amount": 280.00,
      "discount_type": "percentage",
      "discount_value": 5,
      "discount_amount": 140.00,
      "total": 2940.00,
      "currency": "USD",
      "notes": "Thank you for your business!",
      "terms": "Payment due within 30 days.",
      "payment_methods": ["paypal", "stripe"],
      "payment_links": {
        "paypal": "https://yoursite.com/invoice/INV-2026-050/pay/paypal",
        "stripe": "https://yoursite.com/invoice/INV-2026-050/pay/stripe"
      },
      "pdf_url": "https://yoursite.com/invoice/INV-2026-050/pdf",
      "public_url": "https://yoursite.com/invoice/INV-2026-050",
      "viewed_at": null,
      "paid_at": null,
      "created_at": "2026-01-15T09:00:00Z",
      "updated_at": "2026-01-15T09:15:00Z"
    }
  ],
  "meta": {
    "total": 50,
    "page": 1,
    "per_page": 20,
    "total_pages": 3,
    "totals": {
      "total_invoiced": 125000.00,
      "total_paid": 98000.00,
      "total_outstanding": 27000.00,
      "currency": "USD"
    }
  }
}
```

---

### Get Invoice

```http
GET /wp-json/scrm/v1/invoices/{id}
```

---

### Create Invoice

```http
POST /wp-json/scrm/v1/invoices
```

**Request Body:**
```json
{
  "contact_id": 1,
  "company_id": 5,
  "status": "draft",
  "issue_date": "2026-01-21",
  "due_date": "2026-02-20",
  "line_items": [
    {
      "description": "Consulting Services",
      "quantity": 10,
      "unit_price": 150.00
    },
    {
      "description": "Project Management",
      "quantity": 5,
      "unit_price": 100.00
    }
  ],
  "tax_rate": 10.00,
  "discount_type": "fixed",
  "discount_value": 100,
  "currency": "USD",
  "notes": "Thank you for choosing us!",
  "terms": "Payment due within 30 days.",
  "payment_methods": ["paypal", "stripe"]
}
```

**Required Fields:**
- `contact_id` — Contact ID
- `line_items` — At least one line item
- `issue_date` — Invoice issue date

---

### Update Invoice

```http
PUT /wp-json/scrm/v1/invoices/{id}
```

**Note:** Invoices with status `paid` cannot be modified.

---

### Delete Invoice

```http
DELETE /wp-json/scrm/v1/invoices/{id}
```

**Note:** Invoices with status `paid` cannot be deleted.

---

### Send Invoice

Send invoice email to contact.

```http
POST /wp-json/scrm/v1/invoices/{id}/send
```

**Request Body (optional):**
```json
{
  "subject": "Custom email subject",
  "message": "Custom email body",
  "cc": ["accounting@example.com"]
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "sent_to": "john@example.com",
    "sent_at": "2026-01-21T10:00:00Z"
  }
}
```

---

### Download Invoice PDF

```http
GET /wp-json/scrm/v1/invoices/{id}/pdf
```

Returns the PDF file with `Content-Type: application/pdf`.

---

### Mark Invoice as Paid

```http
POST /wp-json/scrm/v1/invoices/{id}/mark-paid
```

**Request Body:**
```json
{
  "payment_method": "bank_transfer",
  "payment_date": "2026-01-20",
  "notes": "Payment received via bank transfer"
}
```

---

## Tags

### List Tags

```http
GET /wp-json/scrm/v1/tags
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `search` | string | - | Search by name |
| `orderby` | string | `name` | Sort field |
| `order` | string | `ASC` | Sort order |

**Example Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "VIP",
      "slug": "vip",
      "color": "#EF4444",
      "description": "High-value customers",
      "count": {
        "contacts": 25,
        "companies": 5,
        "transactions": 150,
        "invoices": 45
      },
      "created_at": "2025-01-01T00:00:00Z"
    }
  ],
  "meta": {
    "total": 15
  }
}
```

---

### Get Tag

```http
GET /wp-json/scrm/v1/tags/{id}
```

---

### Create Tag

```http
POST /wp-json/scrm/v1/tags
```

**Request Body:**
```json
{
  "name": "Enterprise",
  "color": "#3B82F6",
  "description": "Enterprise-level customers"
}
```

---

### Update Tag

```http
PUT /wp-json/scrm/v1/tags/{id}
```

---

### Delete Tag

```http
DELETE /wp-json/scrm/v1/tags/{id}
```

---

## Dashboard / Statistics

### Get Dashboard Stats

```http
GET /wp-json/scrm/v1/dashboard/stats
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `period` | string | `30days` | `7days`, `30days`, `90days`, `year`, `all` |
| `compare` | bool | false | Include comparison with previous period |

**Example Response:**
```json
{
  "success": true,
  "data": {
    "period": "30days",
    "contacts": {
      "total": 247,
      "new": 15,
      "by_type": {
        "customer": 180,
        "lead": 52,
        "prospect": 15
      }
    },
    "companies": {
      "total": 35,
      "new": 3
    },
    "revenue": {
      "total": 42500.00,
      "currency": "USD",
      "count": 28,
      "average": 1517.86
    },
    "invoices": {
      "sent": 12,
      "paid": 8,
      "overdue": 2,
      "outstanding_amount": 4500.00
    },
    "comparison": {
      "contacts_change": 25.0,
      "revenue_change": 15.5,
      "invoices_change": -10.0
    }
  }
}
```

---

### Get Revenue Chart Data

```http
GET /wp-json/scrm/v1/dashboard/revenue-chart
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `period` | string | `30days` | Time period |
| `granularity` | string | `day` | `day`, `week`, `month` |
| `currency` | string | - | Filter by currency |

**Example Response:**
```json
{
  "success": true,
  "data": {
    "labels": ["Jan 1", "Jan 2", "Jan 3", ...],
    "datasets": [
      {
        "label": "Revenue",
        "data": [1500, 2300, 800, ...],
        "currency": "USD"
      }
    ]
  }
}
```

---

### Get Contact Growth Chart

```http
GET /wp-json/scrm/v1/dashboard/contacts-chart
```

---

## Import

### Start Import

Start a CSV import process.

```http
POST /wp-json/scrm/v1/import
```

**Request:** Multipart form data

| Field | Type | Description |
|-------|------|-------------|
| `file` | file | CSV file |
| `type` | string | `contacts`, `companies`, `transactions` |
| `mapping` | json | Field mapping object |
| `options` | json | Import options |

**Example (using curl):**
```bash
curl -X POST "https://yoursite.com/wp-json/scrm/v1/import" \
  -u "username:app-password" \
  -F "file=@contacts.csv" \
  -F "type=contacts" \
  -F 'mapping={"email":"Email","first_name":"First Name","last_name":"Last Name"}' \
  -F 'options={"update_existing":true,"skip_header":true}'
```

**Response:**
```json
{
  "success": true,
  "data": {
    "import_id": "imp_abc123",
    "status": "processing",
    "total_rows": 150,
    "processed": 0
  }
}
```

---

### Get Import Status

```http
GET /wp-json/scrm/v1/import/{import_id}/status
```

**Response:**
```json
{
  "success": true,
  "data": {
    "import_id": "imp_abc123",
    "status": "completed",
    "total_rows": 150,
    "processed": 150,
    "created": 120,
    "updated": 25,
    "skipped": 3,
    "errors": 2,
    "error_log": [
      {"row": 45, "message": "Invalid email address"},
      {"row": 102, "message": "Duplicate contact ID"}
    ]
  }
}
```

---

## Sync

### Sync PayPal

Trigger PayPal data synchronization.

```http
POST /wp-json/scrm/v1/sync/paypal
```

**Request Body (optional):**
```json
{
  "start_date": "2025-01-01",
  "end_date": "2026-01-21",
  "type": "transactions"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "sync_id": "sync_pp_123",
    "status": "processing",
    "message": "PayPal sync started"
  }
}
```

---

### Sync Stripe

Trigger Stripe data synchronization.

```http
POST /wp-json/scrm/v1/sync/stripe
```

---

### Get Sync Status

```http
GET /wp-json/scrm/v1/sync/{sync_id}/status
```

---

## Error Codes

| Code | Description |
|------|-------------|
| `invalid_request` | Request body is malformed |
| `validation_error` | One or more fields failed validation |
| `not_found` | Requested resource does not exist |
| `duplicate_entry` | A resource with the same unique identifier exists |
| `unauthorized` | Authentication required |
| `forbidden` | Insufficient permissions |
| `rate_limited` | Too many requests |
| `gateway_error` | Payment gateway error |
| `internal_error` | Server error |

---

## Rate Limiting

The API implements rate limiting to prevent abuse:

- **Default**: 120 requests per minute per user
- **Bulk operations**: 10 requests per minute

Rate limit headers are included in responses:

```
X-RateLimit-Limit: 120
X-RateLimit-Remaining: 115
X-RateLimit-Reset: 1705848900
```

---

## Webhooks (Outgoing)

Configure outgoing webhooks to notify external systems of events.

**Supported Events:**
- `contact.created`
- `contact.updated`
- `contact.deleted`
- `company.created`
- `company.updated`
- `transaction.created`
- `invoice.created`
- `invoice.sent`
- `invoice.paid`

**Webhook Payload:**
```json
{
  "event": "invoice.paid",
  "timestamp": "2026-01-21T10:30:00Z",
  "data": {
    "id": 50,
    "invoice_number": "INV-2026-050",
    ...
  },
  "signature": "sha256=abc123..."
}
```

---

## SDK Examples

### JavaScript (Fetch)

```javascript
const SCRM_API = {
  baseUrl: 'https://yoursite.com/wp-json/scrm/v1',
  auth: btoa('username:app-password'),

  async getContacts(params = {}) {
    const query = new URLSearchParams(params).toString();
    const response = await fetch(`${this.baseUrl}/contacts?${query}`, {
      headers: {
        'Authorization': `Basic ${this.auth}`,
      },
    });
    return response.json();
  },

  async createContact(data) {
    const response = await fetch(`${this.baseUrl}/contacts`, {
      method: 'POST',
      headers: {
        'Authorization': `Basic ${this.auth}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    });
    return response.json();
  },
};

// Usage
const contacts = await SCRM_API.getContacts({ type: 'customer', per_page: 50 });
```

---

### PHP (WordPress)

```php
// Using wp_remote_get/post
$response = wp_remote_get(
    'https://yoursite.com/wp-json/scrm/v1/contacts',
    array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( 'username:app-password' ),
        ),
    )
);

$contacts = json_decode( wp_remote_retrieve_body( $response ), true );

// Create contact
$response = wp_remote_post(
    'https://yoursite.com/wp-json/scrm/v1/contacts',
    array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( 'username:app-password' ),
            'Content-Type'  => 'application/json',
        ),
        'body'    => wp_json_encode( array(
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => 'john@example.com',
            'type'       => 'lead',
        ) ),
    )
);
```

---

### Python

```python
import requests
from requests.auth import HTTPBasicAuth

class StarterCRM:
    def __init__(self, base_url, username, password):
        self.base_url = f"{base_url}/wp-json/scrm/v1"
        self.auth = HTTPBasicAuth(username, password)
    
    def get_contacts(self, **params):
        response = requests.get(
            f"{self.base_url}/contacts",
            auth=self.auth,
            params=params
        )
        return response.json()
    
    def create_contact(self, data):
        response = requests.post(
            f"{self.base_url}/contacts",
            auth=self.auth,
            json=data
        )
        return response.json()

# Usage
crm = StarterCRM('https://yoursite.com', 'username', 'app-password')
contacts = crm.get_contacts(type='customer', per_page=50)
```
