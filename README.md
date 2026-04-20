# WiFi Billing SaaS - CloudBridge Networks Admin

Modern admin portal for ISP and hotspot billing operations with a premium, ops-first experience.  
Designed to reduce operational overhead, speed up customer support, and improve revenue visibility across hotspot and PPPoE networks.

## Value
- Faster triage for support teams (status, expiry, and session context at a glance)
- Cleaner billing operations (vouchers, payments, and tax-ready settings)
- Operator-friendly workflows that scale with network growth

## Modules
- Dashboard: KPIs, revenue charts, package distribution
- Routers: inventory and status management
- Packages: plan setup and pricing
- Vouchers: generate, manage, and track usage
- Payments: history and status tracking
- Clients: Hotspot Clients, PPPoE Clients, All Customers (unified view)
- Settings: M-Pesa, SMS Gateway, Email/SMTP, Branding, MikroTik, System, Backup & Maintenance, Billing & Tax

## Experience Highlights
- AdminLTE 4 + Bootstrap 5 foundation
- Premium cards, badges, status dots, and tabs
- DataTables with filters and bulk actions
- ApexCharts for analytics
- SweetAlert2 for confirmations and flows
- Flatpickr for date inputs

## Tech Stack
- Backend: Laravel 13 (PHP ^8.3)
- Frontend: Vite 8, Bootstrap 5, AdminLTE 4
- JS/UI: Alpine.js, jQuery (DataTables), ApexCharts, SweetAlert2, Flatpickr
- Icons: Font Awesome

## Quick Start
### Requirements
- PHP 8.3+
- Composer
- Node.js 18+ (recommended)
- Database: SQLite/MySQL/PostgreSQL

### Install and Run (Dev)
```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run dev
```

### Run All Dev Processes
```bash
composer run dev
```
This runs: Laravel server, queue worker, pail logs, and Vite.

### Production Build
```bash
npm run build
```

### Queue Worker (Production)
M-Pesa callbacks are queued on `critical`. Keep a persistent worker running:

```bash
php artisan queue:work redis --queue=critical,high,medium,low,default --sleep=1 --tries=5 --timeout=120 --backoff=10 --max-time=3600 --memory=256
```

Recommended (auto-restart, boot persistence) via systemd:

```bash
# edit deploy/systemd/cloudbridge-queue.service first:
# - User / Group
# - WorkingDirectory
# - php binary path in ExecStart
bash scripts/queue/install-systemd.sh
bash scripts/queue/check-worker.sh
```

## RADIUS Production Checklist
Before going live with captive portal authentication:

```bash
php artisan radius:health-check --strict
```

What this now validates:
- RADIUS enabled and non-placeholder shared secret
- Non-loopback RADIUS server in production
- Auth/accounting port sanity
- FreeRADIUS SQL connectivity and required tables
- Remote DB TLS CA configuration

## Captive Portal Activation Debug
When payment is successful but user is not online yet, check:

- `storage/logs/payment.log` (payment confirmation and activation state)
- `storage/logs/mikrotik.log` (router login attempt and activation errors)
- `storage/logs/radius.log` (provisioned username/profile details)

Typical high-signal fields in logs:
- `payment_id`, `session_id`, `checkout_request_id`
- `router_id`, `username`, `client_mac`, `client_ip`
- `missing_client_context` (true means router login had no host binding context)

## Build Notes
This project uses Vite for compiled assets. For critical UI adjustments without a rebuild, a runtime override stylesheet is loaded:
- `public/css/admin-overrides.css`
- Linked in `resources/views/admin/layouts/app.blade.php`

## Known Windows Caveats
If `npm run build` fails on Windows:
- PowerShell may block scripts. Run from `cmd` instead.
- Tailwind oxide binary can fail to load. A clean `node_modules` reinstall and Node LTS usually resolves this.

## Project Layout
- `resources/views/admin/*` - Admin UI pages (Blade)
- `resources/css/*` - Custom styles
- `resources/js/*` - Admin JS
- `routes/web.php` - Web routes
- `public/css/admin-overrides.css` - Runtime UI overrides

## Roadmap (Phase 2)
- Live router integrations (Hotspot/PPPoE sync)
- Billing engine and voucher generation tied to real transactions
- Taxes, invoices, and receipts linked to payments
- Audit logs and role-based access
- Reseller and agent workflows

## License
TBD
