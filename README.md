# Shaunti Water Refilling Station — Management System

A Laravel-based web system for managing daily operations at Shaunti Water Refilling Station (San Roque, Bato) — inventory, point-of-sale, deliveries, water production, and reporting, with role-based access for Admins, Staff, and Customers.

## Tech Stack

- **Backend:** Laravel 13, PHP 8.3+
- **Database:** MySQL (via XAMPP)
- **Frontend:** Blade templates, Tailwind CSS 4, Alpine.js, Vite
- **Key packages:** `spatie/laravel-permission` (roles & permissions), `spatie/laravel-activitylog` (audit trail), Chart.js (dashboards/reports), Tom Select & Flatpickr (form UI), JsBarcode (product barcodes), SweetAlert2 (UI alerts)

## Features / Modules

- **Authentication & Roles** — Admin, Staff, and Customer accounts with role-based access (`spatie/laravel-permission`)
- **Products & Consumables** — catalog management, stock levels, restocking, barcodes
- **Stock Movements** — unified in/out ledger for consumable and product stock
- **Gallon Stocks & Water Production Logs** — tracks refill gallon inventory and production runs
- **Suppliers & Purchase Orders** — purchase order creation, items, and receiving
- **Point of Sale (POS)** — staff-facing sales transaction entry with cash handling
- **Deliveries** — delivery order management and staff delivery workflow
- **Customer Payments** — payment recording and customer-facing order/payment views
- **Maintenance Logs** — equipment maintenance tracking
- **Staff Management** — staff records and accounts
- **Reports & Dashboard** — Chart.js-powered dashboards for Admin/Staff/Customer views
- **Notifications & Activity Log** — in-app notifications and an audit trail of user actions

## Requirements

- [XAMPP](https://www.apachefriends.org/) with PHP **8.3+** and MySQL — or PHP 8.3+ and MySQL installed separately
- [Composer](https://getcomposer.org/)
- [Node.js](https://nodejs.org/) (18+) and npm, for the Vite frontend build

## Running the system on localhost

### 1. Get the project into XAMPP

Place the project inside XAMPP's `htdocs` folder, e.g. `c:\xampp\htdocs\capstone_project_anthus`, then start **Apache** and **MySQL** from the XAMPP Control Panel.

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Configure the environment

```bash
cp .env.example .env
php artisan key:generate
```

`.env.example` is already pre-configured for local MySQL via XAMPP:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=capstone_project_anthus
DB_USERNAME=root
DB_PASSWORD=
```

If your local MySQL uses different credentials, update `DB_USERNAME` / `DB_PASSWORD` accordingly.

Also review the default admin account block in `.env` — this account is created automatically when you seed the database:

```
ADMIN_NAME="Administrator"
ADMIN_EMAIL=admin@shauntiwater.com
ADMIN_PASSWORD=ChangeMe123!
```

### 4. Create the database

In phpMyAdmin (`http://localhost/phpmyadmin`) or the MySQL CLI, create a database with the name from `DB_DATABASE` (default: `capstone_project_anthus`). No tables are needed yet — migrations will create the schema.

```sql
CREATE DATABASE capstone_project_anthus;
```

### 5. Run migrations and seed data

```bash
php artisan migrate --seed
```

This creates all tables (users, products, consumables, sales, deliveries, purchase orders, etc.), sets up roles/permissions, and creates the default admin account from step 3.

### 6. Start the app

The bundled dev script runs the PHP server, the queue worker, and the Vite dev server together in one terminal:

```bash
composer run dev
```

The app will be available at **http://localhost:8000**.

Alternatively, run each process in its own terminal:

```bash
php artisan serve          # app server — http://localhost:8000
php artisan queue:listen   # background jobs (notifications, etc.)
npm run dev                # Vite dev server (hot-reloading CSS/JS)
```

### 7. Log in

Go to `http://localhost:8000` and log in with the admin credentials from your `.env` (`ADMIN_EMAIL` / `ADMIN_PASSWORD`). **Change the password immediately after first login.**

## Building assets for production

```bash
npm run build
```

Compiled assets are output to `public/build`. Serve the app through Apache/Nginx pointed at `public/` (rather than `php artisan serve`) for a production-like setup.

## Useful commands

```bash
php artisan migrate:fresh --seed   # rebuild the database from scratch
php artisan db:seed                # re-run seeders only
php artisan test                   # run the Pest test suite
php artisan pail                   # tail application logs in real time
php artisan route:list             # list all registered routes
composer run test                  # clear config cache, then run tests
```

## Scheduled credit monitoring

Credit purchases are due 14 days after the sale. The hourly scheduler suspends accounts with an unpaid due credit and sends database notifications to the customer, admin, and staff. In development, keep it running with:

```bash
php artisan schedule:work
```

In production, configure the server scheduler to run `php artisan schedule:run` every minute.

## Troubleshooting

- **"SQLSTATE[HY000] [1049] Unknown database"** — the database in `DB_DATABASE` hasn't been created yet; see step 4.
- **"SQLSTATE[HY000] [2002]"** or connection refused — MySQL isn't running; start it from the XAMPP Control Panel.
- **Blank/unstyled pages** — Vite assets aren't built/served; run `npm run dev` (local) or `npm run build` (production).
- **419 Page Expired on login** — clear cookies/cache, or make sure `APP_URL` in `.env` matches the URL you're browsing to.
- **Changes to `.env` not taking effect** — run `php artisan config:clear`.

## Notes

- Sessions and cache are stored in the database (`SESSION_DRIVER=database`, `CACHE_STORE=database`), so migrations must be run before first use.
- Application logs are written to `storage/logs/laravel.log`.
- Uploaded files are stored under `storage/app/public`; run `php artisan storage:link` if `public/storage` doesn't already exist.
