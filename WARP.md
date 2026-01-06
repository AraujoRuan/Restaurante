# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Common commands

### Install & initial setup

- Install PHP and Composer, then from the project root:
  - `composer install`
  - Copy environment file and generate app key (only needed once):
    - `copy .env.example .env` (Windows) or `cp .env.example .env` (Unix)
    - `php artisan key:generate`
  - Run database migrations:
    - `php artisan migrate`
  - Install and build frontend assets:
    - `npm install`
    - `npm run build`
- Alternatively, the Composer script `setup` runs the full sequence above:
  - `composer run setup`

### Local development

- Full dev environment (HTTP server + queue worker + log viewer + Vite dev server) via Composer script defined in `composer.json`:
  - `composer run dev`
- Manual equivalent if you need separate processes:
  - HTTP server: `php artisan serve`
  - Queue worker (if jobs are used): `php artisan queue:listen --tries=1`
  - Log viewer: `php artisan pail --timeout=0`
  - Frontend dev server: `npm run dev`

### Tests

- Run the full test suite (uses the `phpunit.xml` configuration with in-memory SQLite):
  - `composer run test`
  - or `php artisan test`
- Run a single test file (path-based filter):
  - `php artisan test tests/Feature/ExampleTest.php`

### Linting / formatting

- PHP code style (Laravel Pint is installed as a dev dependency):
  - `php vendor/bin/pint`
  - To automatically fix style issues: `php vendor/bin/pint --test` for CI-style checks, omit `--test` for auto-fix.

### Database utilities

- Apply migrations:
  - `php artisan migrate`
- Roll back last batch:
  - `php artisan migrate:rollback`

### Frontend build

- Production build of assets (Vite):
  - `npm run build`

## Architecture overview

### Framework & entrypoints

- This is a Laravel 12 application (`composer.json`) organized with the default Laravel directory layout.
- HTTP entrypoint is `public/index.php`; the CLI entrypoint for framework tasks is `artisan` in the project root.
- Environment and configuration use the standard Laravel pattern: `.env` with overrides for files under `config/`.

### Routing & HTTP flow

- Web routes are centralized in `routes/web.php`:
  - Public/guest routes are grouped under the `guest` middleware:
    - `/login` → `AuthController@showLogin` (GET) and `AuthController@login` (POST).
    - `/terms` and `/privacy` → `RegisterController` static pages.
  - Registration is exposed at `/register` via `RegisterController@showRegister` (GET) and `RegisterController@register` (POST) and is intentionally accessible even for authenticated users (e.g., creation from dashboard).
  - An `auth` middleware group protects the application:
    - `/logout` → `AuthController@logout`.
    - `/dashboard` → `DashboardController@index`.
    - `/profile` → `UserController@profile` and `UserController@updateProfile`.
    - `/users/*` → `UserController` for user management (index/create/store/show/edit/update/destroy/toggleStatus/bulkActions).
    - `/pos/*` → `POSController` for point-of-sale operations (UI and JSON helpers).
    - `/reports/*` → `ReportController` for sales and profit reports and PDF generation.
    - `/settings/*` → simple closure-based routes rendering views under a `settings` namespace.
  - Public API-style endpoint for product listing:
    - `/api/products` → `POSController@getProducts`.
  - A `Route::fallback` redirects unknown URLs back to the dashboard for authenticated users or to the login page for guests.

### Authentication, users, and authorization

- Configuration:
  - `config/auth.php` defines a single `web` guard using the `App\Models\User` model via the `eloquent` provider.
- User model (`app/Models/User.php`):
  - Extends `Illuminate\Foundation\Auth\User` and is the primary authentication model.
  - Key attributes:
    - Mass-assignable: `name`, `email`, `password`, `role`, `active`, `phone`, `cpf`, `birth_date`, `address`.
    - Casts: `password` as `hashed`, `birth_date` as `date`, `active` as `boolean`.
  - Domain helpers:
    - `approve()` / `reject()` toggle the `active` flag, used to manage account approval status.
- Authentication controllers:
  - `AuthController`:
    - `showLogin()` renders the login form view `resources/views/auth/login.blade.php`.
    - `login()` validates email/password and uses `Auth::attempt($credentials)`; successful logins regenerate the session and redirect to `/dashboard`, failures return to the login form with an error.
    - `logout()` logs out, invalidates the session, regenerates the CSRF token, and redirects to `/login`.
  - `RegisterController`:
    - `showRegister()` renders `resources/views/auth/register.blade.php`.
    - `register()` performs a simplified validation (`name`, `email`, `password` + confirmation), creates a `User` with those fields, logs the user in via `Auth::login($user)`, then redirects to `route('dashboard')` with a success flash message.
    - `showTerms()` / `showPrivacy()` return simple views with embedded HTML content for terms and privacy policy.
    - `checkEmail()` and `checkPasswordStrength()` are JSON endpoints intended for AJAX validation (email uniqueness and password strength feedback).
- Role-based access:
  - `app/Http/Middleware/CheckRole.php` provides a `CheckRole` middleware that ensures the authenticated user's `role` attribute is in a given list; otherwise the request is aborted with `403`.
  - `UserController` enforces business rules based heavily on the `role` field:
    - Only `admin` and `gerente` can manage other users (index/create/store/edit/update/destroy/bulkActions).
    - Additional protections prevent managers from creating admins, editing higher-privilege accounts, or deleting themselves.
  - `DashboardController` selects different dashboard views and metrics depending on `User::role` (e.g., `cliente` vs. staff roles vs. admin/manager), so role values are central to UI behavior.

### Domain modules

#### Dashboard (`DashboardController`)

- Aggregates metrics using `Order`, `Product`, `Expense`, and `User` models:
  - For customers (`role === 'cliente'`): recent orders, active orders count, lifetime spend, and today’s orders.
  - For staff roles (e.g., `atendente`, `garcom`, `cozinha`): daily sales (today vs. yesterday), counts by order status (`pendente`, `preparando`, `pronto`), and recent orders.
  - For admins/managers: monthly sales, expenses, profit, low-stock product counts, active users, new customers, total orders, and average ticket size.
- Renders different Blade views (`dashboard.cliente`, `dashboard.index`, `dashboard.admin`) based on the current user’s role.

#### Point of Sale (`POSController`)

- Main responsibilities:
  - `index()` loads active `Product`s with their `Category`, available `Table`s, and renders `pos.index` for the POS UI.
  - `storeOrder()` validates a request payload describing order items and metadata (type, table, discount), calculates subtotal/discount/tax/final amount, then:
    - Creates an `Order` record with computed totals and metadata (including a generated `order_code`).
    - Creates related `OrderItem` records for each item.
    - Optionally updates the associated `Table` to `occupied` when applicable.
    - Wraps all operations in a DB transaction and returns a JSON response with success or error.
  - `getProducts()` returns JSON of active products with their categories.
  - `filterProducts()` applies filters by category and search term, returning a filtered product list as JSON.
- Related models include `Product`, `Category`, `Table`, `Order`, and `OrderItem` (see `database/migrations/*` for schema details).

#### Reporting (`ReportController`)

- Provides sales and profit analysis over a date range:
  - `sales()` groups `Order` data by day (for `status = 'completed'`) and passes daily totals to `reports.sales`.
  - `profit()` computes revenue (completed orders), expenses (`Expense` records), and an approximate cost of goods sold (COGS) based on ingredients linked to ordered products, then derives gross and net profit and profit margin for a period.
  - `generate()` creates PDF reports (sales or profit) via `Barryvdh\DomPDF` and returns them as downloads, using Blade templates under `resources/views/reports/pdf/*`.

#### User management (`UserController`)

- Administrative management of staff and users:
  - CRUD for users with strong role-based access checks.
  - Bulk actions (`bulkActions`) for activating, deactivating, or deleting multiple users, with per-user permission checks and DB transactions.
  - Self-service profile management (`profile`, `updateProfile`) allowing users to edit their own personal data and optionally update passwords after confirming the current password.
- Depends heavily on the `role`, `active`, `phone`, `cpf`, and other profile fields defined on `User`.

### Views and layout

- Authentication views (`resources/views/auth/*.blade.php`) are standalone HTML pages using Bootstrap and Font Awesome; they do not extend a shared Blade layout.
- Application views generally extend `resources/views/layouts/app.blade.php`:
  - `layouts.app` currently contains a login-focused Bootstrap layout. When editing dashboards or internal pages, verify that this layout matches the desired UI for authenticated areas.
- Key view namespaces:
  - `dashboard/*` for dashboards per role.
  - `users/*` for user management and profile.
  - `pos/index.blade.php` for the POS interface.
  - `reports/*` and `reports/pdf/*` for reporting UIs and PDFs.

### Assets and build system

- Vite is used for bundling (`vite.config.js`), with Tailwind CSS 4 configured via `@tailwindcss/vite` and `tailwindcss` dependencies.
- Asset entrypoints follow the default Laravel Vite structure:
  - CSS: `resources/css/app.css`.
  - JS: `resources/js/app.js` (and `resources/js/bootstrap.js` for bootstrap-related setup).
- In production, `npm run build` generates versioned assets consumed via Laravel’s Vite integration.

### Testing setup

- Tests are organized under `tests/Unit` and `tests/Feature` as per the default Laravel structure.
- `phpunit.xml` configures an isolated test environment:
  - Database uses SQLite in-memory (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`) and `SESSION_DRIVER=array`.
  - Queue, mail, and other side-effectful services are configured to use in-memory or no-op drivers during tests.
- When adding new tests, place feature tests under `tests/Feature` and unit tests under `tests/Unit` to ensure they are picked up by `php artisan test` and `composer run test`.
