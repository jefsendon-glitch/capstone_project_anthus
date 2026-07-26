# Shaunti Water Refilling Station — Management System

A Laravel-based system for managing inventory, sales, deliveries, and water production for Shaunti Water Refilling Station (San Roque, Bato).

## Requirements

- [XAMPP](https://www.apachefriends.org/) (PHP 8.3+, MySQL) — or PHP 8.3+ and MySQL installed separately
- [Composer](https://getcomposer.org/)
- [Node.js](https://nodejs.org/) (for the Vite frontend build)

## Running the system on localhost

1. **Clone/place the project inside XAMPP's `htdocs`** (e.g. `c:\xampp\htdocs\capstone_project_anthus`) and start **Apache** and **MySQL** from the XAMPP Control Panel.

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Install JS dependencies:**
   ```bash
   npm install
   ```

4. **Set up your environment file:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Create the database.** In phpMyAdmin (or via MySQL CLI), create a database matching `.env`'s `DB_DATABASE` (default: `capstone_project_anthus`). By default `.env.example` is already configured for local MySQL via XAMPP:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=capstone_project_anthus
   DB_USERNAME=root
   DB_PASSWORD=
   ```

6. **Run migrations and seed the database** (creates the default admin account defined by `ADMIN_NAME` / `ADMIN_EMAIL` / `ADMIN_PASSWORD` in `.env`):
   ```bash
   php artisan migrate --seed
   ```

7. **Start the app.** The easiest way is the bundled dev script, which runs the PHP server, queue worker, and Vite dev server together:
   ```bash
   composer run dev
   ```
   The app will be available at **http://localhost:8000**.

   Alternatively, run each piece separately:
   ```bash
   php artisan serve          # app server
   php artisan queue:listen   # background jobs
   npm run dev                # Vite (hot-reloading assets)
   ```

8. **Log in** using the admin credentials from your `.env` (`ADMIN_EMAIL` / `ADMIN_PASSWORD`), then change the password after first login.

## Building assets for production

```bash
npm run build
```

## Notes

- Session and cache are stored in the database (`SESSION_DRIVER=database`, `CACHE_STORE=database`), so migrations must be run before first use.
- Application logs are written to `storage/logs/laravel.log`.
