# Tech-Sphere Wi-fi Billing System

A modern, production-ready **MikroTik Hotspot Voucher Management System** built with Laravel 12, Tailwind CSS, Alpine.js, and RouterOS API integration.

## Features

- **Admin authentication** with role-based access control
- **Internet packages** — create, edit, and manage hotspot plans
- **Voucher management** — single/bulk generation (100–1000), print, PDF/Excel export
- **Sales & payments** — record transactions with payment methods
- **Customer management** — track buyers and history
- **MikroTik integration** — connect router, manage hotspot users, sync sessions
- **Dashboard** — real-time stats and Chart.js analytics
- **Reports** — daily, weekly, monthly, voucher, sales, customer (PDF & Excel)
- **Settings** — business profile, logo, currency, timezone, router credentials
- **Dark mode** — responsive UI with green, white, and navy theme

## Requirements

- PHP 8.2+ (8.3+ recommended)
- Composer
- Node.js 18+
- MySQL 8+ (or SQLite for local testing)
- PHP extensions: `openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `zip`, `sockets`, `gd`

## Installation

### 1. Clone / open the project

```bash
cd "LATEST WIFI SYSTEM"
```

### 2. Install dependencies

```bash
composer install
npm install
npm run build
```

### 3. Environment setup

```bash
copy .env.example .env
php artisan key:generate
```

Configure your database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=techsphere_wifi
DB_USERNAME=root
DB_PASSWORD=
```

Create the MySQL database:

```sql
CREATE DATABASE techsphere_wifi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Migrate & seed

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
```

### 5. Run the application

```bash
php artisan serve
```

Visit: **http://127.0.0.1:8000**

For development with hot reload:

```bash
npm run dev
php artisan serve
```

## Default Login

| Field    | Value                  |
|----------|------------------------|
| Email    | `admin@techsphere.com` |
| Password | `password123`          |

## Pre-seeded Packages

| Package                 | Duration | Price (TSh) |
|-------------------------|----------|-------------|
| Tarangire Lite          | 6 Hours  | 500         |
| Serengeti Daily         | 24 Hours | 1,000       |
| Ruaha Weekly            | 7 Days   | 6,000       |
| Kilimanjaro Unlimited   | 30 Days  | 25,000      |

## MikroTik Router Setup

1. Go to **Settings → Router Settings**
2. Enter router IP, username, password, and hotspot interface
3. Click **Test Connection**
4. Use **Router** menu to manage online users and sync sessions

Ensure API service is enabled on your MikroTik router:

```
/ip service enable api
/ip service set api port=8728
```

## Project Structure

```
app/
├── Http/Controllers/     # Web controllers
├── Http/Requests/        # Form validation
├── Http/Middleware/      # Admin role middleware
├── Models/               # Eloquent models
├── Services/             # Business logic & RouterOS API
├── Repositories/         # Data access layer
├── Policies/             # Authorization
└── Exports/              # Excel exports

resources/views/
├── auth/                 # Login & forgot password
├── dashboard/            # Analytics dashboard
├── packages/             # Package CRUD
├── vouchers/             # Voucher management & printing
├── sales/                # Sales module
├── customers/            # Customer management
├── router/               # MikroTik control panel
├── reports/              # Report views
├── settings/             # Business & router settings
└── components/           # Reusable Blade components
```

## Security

- CSRF protection on all forms
- SQL injection prevention via Eloquent ORM
- XSS protection via Blade escaping
- Encrypted router credentials
- Rate-limited login attempts
- Laravel Policies for authorization

## License

Proprietary — Tech-Sphere Wi-fi Billing System
