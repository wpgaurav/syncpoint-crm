# Changelog

All notable changes to Starter CRM will be documented in this file.

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

## [1.0.0] - TBD

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
