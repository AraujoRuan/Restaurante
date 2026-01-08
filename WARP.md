# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Common commands

### Initial setup

These commands assume you are in the project root (`C:\Php\Restaurante`).

- One-shot project setup (PHP deps, `.env`, key generation, migrations, frontend deps, build):

```bash
composer run setup
```

If you prefer to run steps manually:

```bash
composer install
php -r "file_exists('.env') || copy('.env.example', '.env');"
php artisan key:generate
php artisan migrate --force
npm install
npm run build
```

### Day-to-day development

- Full development stack (Laravel HTTP server, queue listener, log viewer, Vite dev server) via Composer script:

```bash
composer run dev
```

This runs, via `npx concurrently` (see `composer.json`):
- `php artisan serve`
- `php artisan queue:listen --tries=1`
- `php artisan pail --timeout=0`
- `npm run dev`

You can also run any of those processes individually if you want more control.

### Frontend assets

The frontend uses Vite with Tailwind CSS 4 (`package.json`).

- Development build with HMR:

```bash
npm run dev
```

- Production build:

```bash
npm run build
```

### Tests

PHPUnit is configured via `phpunit.xml` to run unit and feature tests with an in-memory SQLite database.

- Run the full test suite (preferred):

```bash
composer run test
```

This will clear configuration and then run:

```bash
php artisan config:clear --ansi
php artisan test
```

- Run tests directly (alternative):

```bash
php artisan test
```

- Run a single test class or method using Laravel's test runner:

```bash
php artisan test --filter=YourTestName
# or
php artisan test tests/Feature/YourFeatureTest.php
```

### Linting / code style

Laravel Pint is installed as a dev dependency (`laravel/pint`). Run it via the vendor binary:

```bash
php vendor/bin/pint
```

## Architecture overview

### Framework and runtime

- This is a Laravel 12 application (see `composer.json`) using the standard Laravel directory layout.
- HTTP entrypoint is `public/index.php`; application bootstrapping lives in `bootstrap/app.php`.
- Autoloading is PSR-4, with the main app namespace `App\` mapped to `app/` and database namespaces mapped to `database/factories` and `database/seeders`.
- `phpunit.xml` defines `tests/Unit` and `tests/Feature` suites and configures an in-memory SQLite connection for tests, overriding the usual database configuration.

### Routing and HTTP layer

- All web routes are defined in `routes/web.php` and use controller-based routing.
- Public/guest routes:
  - Root (`/`) redirects to the login route.
  - `/login` (GET/POST) is handled by `AuthController` for authentication.
  - `/terms` and `/privacy` are served by `RegisterController` for legal content.
- Registration routes (`/register`) remain accessible even when authenticated so users can create accounts from the dashboard if needed.
- A public products API endpoint (`/api/products`) exposes the current product catalog via `POSController@getProducts`.
- Auth-protected routes are grouped under `Route::middleware('auth')` and include:
  - Logout (`/logout`).
  - Dashboard (`/dashboard`) via `DashboardController@index`.
  - Profile management under `prefix('profile')` using `UserController@profile` and `updateProfile`.
  - User management CRUD under `prefix('users')` using `UserController` (listing, create/store, show, edit/update, delete, bulk actions, and status toggling).
  - POS endpoints under `prefix('pos')` via `POSController` (screen, order creation, product listing/filtering).
  - Reporting endpoints under `prefix('reports')` via `ReportController` (sales and profit reports, plus PDF generation).
  - Settings pages under `prefix('settings')`, currently implemented as simple closure routes returning views (e.g., `settings.index`, `settings.restaurant`).
- A global fallback route redirects authenticated users to the dashboard and guests to the login page instead of showing a raw 404.

### Domain model and core business logic

The application models a restaurant/POS domain with orders, tables, products, ingredients, inventory, and user roles. Much of the structure is inferred from controller usage and Eloquent relationships.

#### Orders, tables, and order items

Key classes and flows:

- `App\Models\Order` represents a customer order. While the model is currently minimal, it is used throughout:
  - `DashboardController` aggregates daily/monthly sales, counts orders, and tracks pending orders.
  - `ReportController` queries completed orders for sales and profit calculations.
  - `POSController` creates orders, manages statuses, and exposes order data as JSON for the POS UI and dashboards.
- Orders are associated with:
  - A table (`table_id`) when the order is dine-in.
  - A user (`user_id`) for the staff member or customer creating the order.
  - Multiple order items (`OrderItem`), each linked to a `Product`.
- Order lifecycle and status values (used across controllers and views):
  - Creation: new orders start with `status = 'pending'`.
  - Progression: `pending → preparing → ready → served → completed`.
  - Cancellation: `cancelled` triggers stock reversion.
- Table management:
  - `POSController@storeOrder` marks a table as `occupied` when a dine-in order is placed.
  - `POSController@updateOrderStatus` sets the table back to `available` when a dine-in order is completed.
  - `DashboardController` and `POSController` both query the `tables` table to count/inspect active vs total tables.
  - Table-related status labels and CSS classes for the frontend are normalized through helper methods in `POSController` (e.g., `getTableStatusLabel`, `getTableStatusClass`).

When changing order status semantics or table behavior, check all usages in `DashboardController`, `POSController`, the dashboard Blade view, and any JSON APIs that expose status labels.

#### Products, categories, ingredients, and inventory

- `App\Models\Product` represents menu items and is frequently loaded with related data:
  - `Product::with('category')` is used for listing active products in the POS.
  - The more feature-complete POS implementation also expects a `ingredients` relationship.
- Categories (`Category` model) organize products into groups; they are used for filtering and seeding sample data for the POS.
- Ingredients and inventory:
  - `Ingredient` and `StockMovement` are referenced in the more advanced POS logic and in `ReportController`.
  - `POSController` (the more complete implementation) assumes each product has many ingredients with a pivot quantity (`product_ingredient` table).
  - When an order is created, `updateInventory` reduces ingredient `current_stock` and records an `StockMovement` of type `exit`.
  - When an order is cancelled, `revertInventory` restores stock and records an `StockMovement` of type `entry`.
- Reporting cost of goods sold (COGS):
  - `ReportController@calculateCOGS` walks through completed orders, their items, and each product's ingredients to compute total cost based on `ingredient.last_cost` and pivot quantities.

Any change to ingredient or inventory modeling should keep `POSController` and `ReportController` in sync so COGS and stock movements remain consistent.

#### Expenses and profit reporting

- `App\Models\Expense` stores non-COGS expenses.
- `ReportController@profit` aggregates:
  - Revenue from completed orders (`Order.final_amount`).
  - Expenses (`Expense.amount`).
  - COGS via `calculateCOGS`.
- It then calculates gross profit, net profit, and profit margin, and passes them to a Blade view (`reports.profit`).
- `ReportController@generate` produces PDF reports using DomPDF (see `Barryvdh\DomPDF\Facade\Pdf`), loading specialized report views under `resources/views/reports/pdf/` for sales and profit.

#### Users, authentication, and roles

- `App\Models\User` extends Laravel's `Authenticatable` and adds fields specific to this app:
  - Fillable attributes include profile and access fields: `role`, `active`, `phone`, `cpf`, `birth_date`, and `address` in addition to the usual name/email/password.
  - Casts ensure booleans/dates/password hashing are handled correctly.
- Role semantics and access control:
  - Roles are simple strings stored on the user (`admin`, `gerente`, `garcom`, `caixa`, `cozinha`).
  - `App\Http\Middleware\CheckRole` is a reusable middleware that checks a user's `role` against allowed roles for a route and aborts with 403 on mismatch.
  - `UserController` additionally inlines many role checks for finer-grained control (e.g., gerentes cannot create admins, cannot edit other managers/admins, cannot change their own role or deactivate themselves).
- Authentication and registration:
  - `AuthController` handles login (`showLogin`, `login`) and logout.
  - `RegisterController` provides a simplified registration flow using `Validator` directly, creates a minimal user record, logs the user in, and redirects to the dashboard.
  - `RegisterController` also serves static-ish legal content for terms and privacy pages via Blade views, embedding HTML content in the controller.

When modifying roles or introducing new permissions, update both `CheckRole` middleware usages and the explicit checks within `UserController` to avoid inconsistent authorization behavior.

### Dashboard and reporting UI

- The main dashboard view is `resources/views/dashboard/index.blade.php` and is rendered by `DashboardController@index`.
- It shows:
  - High-level metrics (orders today, active orders, total spent, user info).
  - A table of "Meus Pedidos Recentes" (recent orders for the authenticated user).
  - A table of recent orders across the system, including order code, table, total, status, and a link to view details.
- The dashboard relies on Eloquent queries in `DashboardController` and on the `Order` and `tables` schema. Status strings (`pending`, `completed`, etc.) are used directly in Blade to determine badge colors and labels.
- `POSController` also exposes JSON endpoints (`getActiveOrders`, `getDashboardData`, `getTablesStatus`) that provide the data needed for any real-time dashboard widgets or POS screens.

### Views, layout, and frontend stack

- The base layout is `resources/views/layouts/app.blade.php` and:
  - Uses Bootstrap 5 and Font Awesome via CDN.
  - Shows a top navbar with the application name and authenticated user name/logout button.
  - Contains a single `@yield('content')` section that pages extend.
- Feature views (e.g., dashboard, POS, auth, user management, reports) extend this layout.
- Asset compilation and bundling are handled by Vite (`vite` and `laravel-vite-plugin` in `package.json`) with Tailwind CSS 4; check the Vite/Tailwind configuration files in the project root when modifying the frontend pipeline.

### Notable quirks and cautions

- There are two different implementations related to the POS:
  - `app/Http/Controllers/POSController.php` contains a simpler POS controller.
  - `resources/views/pos/index.blade.php` currently contains a more feature-complete `POSController` class (PHP code inside what is nominally a Blade view file).
- Before changing POS behavior, decide which implementation is the source of truth. If you refactor, it is recommended to consolidate the POS logic into `app/Http/Controllers/POSController.php` and ensure `resources/views/pos/index.blade.php` contains only Blade markup for the UI.

Keeping these architectural relationships in mind will help ensure changes to orders, inventory, roles, and reports remain consistent across controllers, views, and JSON APIs.