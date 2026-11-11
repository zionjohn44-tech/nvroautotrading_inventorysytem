# NVRO Auto Trading Inventory System

Local XAMPP application using PHP OOP, HTML/CSS/JavaScript, and the MySQL PDO driver. The database is **`nvroinventory_db`**. The entry page is the staff login; there is no marketing website and customers do not log in.

## Open the application

1. Start Apache and MySQL in XAMPP.
2. Open `http://localhost/nvroautotrading_inventorysytem/`.
3. On an empty account database, complete the one-time administrator setup from localhost. There is no default username or password.
4. Log in. Add vehicles and record inquiries with customer details; brand names are saved automatically from vehicle forms. Administrators can add staff under **Staff accounts**.

The application database has already been migrated in this workspace. For a fresh installation, put the folder under `htdocs`, then run:

```powershell
& C:\xampp\php\php.exe database/migrate.php
```

This creates the database if missing, creates missing tables, and adds the application columns without deleting existing records. It can be run again safely. `database/schema.sql` preserves the original eight-table schema; the migration adds session versioning, vehicle photo paths, test-drive duration/creation time, and login throttling.

The XAMPP server in this workspace reports MariaDB 10.4.32 through its MySQL service. The application uses `pdo_mysql` and MySQL-compatible queries; it does not install or replace your server. The code targets the installed PHP 8.0 runtime and uses no framework or package dependencies.

## Configuration

Defaults are in `config/database.php`: `127.0.0.1:3306`, database `nvroinventory_db`, user `root`, empty password (the existing local XAMPP configuration). For different credentials, set `NVRO_DB_HOST`, `NVRO_DB_PORT`, `NVRO_DB_NAME`, `NVRO_DB_USER`, and `NVRO_DB_PASSWORD` in the PHP process environment, or create an ignored `config/local.php` returning an array with `host`, `port`, `name`, `username`, and `password`.

All times are stored/displayed in Philippine time (UTC+8). PHP and MySQL connections set the timezone explicitly. Apache must allow `.htaccess` files. Sensitive directories deny web access; the Apache configuration in this workspace was checked.

## Workflows

- **Login:** server-validated credentials, regenerated session ID, 30-minute inactivity timeout, logout, five failed attempts per client IP within 15 minutes. “Remember username” stores only the username on that browser.
- **Workspace:** compact icon navigation expands to show labels and remembers the preference. The topbar vehicle search opens matching inventory. The dashboard starts with a personalized welcome, followed by four summary cards, recent vehicle photos, the first five pending requests in FIFO order, and the next five appointments by date. Panels and forms use NVRO red and navy.
- **Appearance:** the light/dark switch beside the topbar date remembers the choice in the browser; the initial theme follows the device preference.
- **Dashboard:** available/sold counts, pending + in-progress inquiries, and pending test drives. Recent inventory includes cover photos. Queue positions reflect arrival order; the dated appointment list is chronological.
- **Inventory:** edit forms prefill saved details; typed brands are saved and available in a dropdown for reuse. Vehicle cards and edit forms include Mark as sold, with active test-drive checks. Upload up to three JPG, PNG, or WebP photos (5 MB each), shown above vehicle details in inventory cards. Editing without selecting files keeps existing photos; selecting files replaces the photo set. Photos are optional for legacy records and vehicles awaiting photography. Files are stored in `uploads/vehicles/` and should be included with database backups.
- **Inventory actions:** add/edit/view, keyword or exact-ID search, sort, filter, pagination, status updates, and removal of unlinked non-sold records. Sold and referenced records are retained. Existing `Reserved` availability is supported alongside `Available` and `Sold`.
- **Customer details:** entered inside Record inquiry. Select an existing customer to autofill their details, or create one together with the inquiry. Customer and inquiry changes save in one transaction. The standalone Customers module is removed.
- **Inquiries:** staff record and update customer questions. Statuses: Pending, In Progress, Resolved, Cancelled.
- **Test drives:** pending requests appear as FIFO cards with the front request highlighted, customer contact, vehicle, appointment time, duration, and rescheduling. Cards scroll horizontally on narrow screens; Process next approves the oldest request and advances the queue. Future schedules with 15–120-minute durations; overlapping active vehicle or customer bookings are rejected. Pending requests are approved in original request-ID order. Rescheduling returns a request to Pending without changing its original order. Complete after the approved appointment’s end; cancel pending or approved requests. Finished records remain in the system.
- **Staff accounts:** only Administrator and Dealership Staff roles are supported. Migration converts existing Sales Staff accounts to Dealership Staff without deleting them. Administrators create accounts, change roles/status, and reset passwords. Password changes are handled in Staff accounts; there is no separate Change password module. Deactivation and account changes invalidate existing sessions. Administrators cannot demote/deactivate themselves; the last active administrator is protected.
- **Password recovery:** an administrator resets an account through Staff accounts. There is no email/SMS reset service.

Vehicle availability cannot change to Sold or Reserved while it has active test-drive requests. Complete or cancel them first. A vehicle with an existing `sold_vehicle` sale record cannot be returned to Available. The pre-existing sales table is retained for historical data; creating sales transactions is outside this inventory/inquiry/test-drive scope.

## Code map

`index.php` is the front controller with explicit route and method allowlists. `index.html` forwards old links to it. `app/bootstrap.php` autoloads the `NVRO\` namespace and supplies escaped template helpers.

| Directory | Responsibility |
|---|---|
| `app/Core` | PDO connections, transactions, sessions, HTTP errors |
| `app/Models` | Encapsulated user identity and role behavior |
| `app/Repositories` | Parameterized database reads behind a common interface |
| `app/Services` | Business rules, writes, scheduling, authentication |
| `app/Controllers` | HTTP request handling, validation feedback, redirects, view composition |
| `app/Middleware` | Session and administrator access checks |
| `app/Validation` | Shared server-side validation and field errors |
| `app/Algorithms` | Linear search, stable merge sort, vehicle BST, linked-list queue |
| `views` | Escaped PHP templates that render HTML |
| `assets` | Internal CSS/JS and editable logo approximation |
| `database` | Required database schema and migration |

Login styling remains in `styles.css` and `script.js`. Internal screens use `assets/app.css` for base styles, `assets/workspace.css` for the shared layout and both themes, and `assets/app.js` for interactions. Dashboard and test-drive queue styles are loaded only on their respective screens. The logo is still the editable SVG approximation made from your reference; replace `assets/nvro-logo.svg` with your original artwork if desired. The pages do not depend on online fonts, a CDN, or a JavaScript build step.

## Maintenance

Runtime failures are logged to `storage/php-error.log`; detailed SQL errors and passwords are not shown to users. Back up the database before importing into another environment. For a publicly exposed deployment, configure HTTPS and a dedicated database account; this delivery is for your local XAMPP system.
