# ClothesTed

ClothesTed is a small fashion e-commerce store built for version 1.0 per the
ClothesTed PRD: a storefront catalog with live filters, a cart and checkout
powered by Midtrans Snap, order and stock handling with a strict lifecycle,
an admin area for catalog, orders, reports, and accounts, plus real-time
customer–admin chat.

## Stack

- **Laravel 13** (PHP 8.4) with **Livewire 4** for interactive pages
- **Tailwind CSS v4** compiled with **Vite**
- **PostgreSQL** as the production database (SQLite in tests)
- **Laravel Reverb** for the real-time chat WebSocket
- **Midtrans Snap** for payments

## Requirements

- PHP 8.4 with the `pdo_pgsql` extension
- Composer
- Node.js (for Vite)
- PostgreSQL 15+
- A Midtrans sandbox account (for checkout)

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Fill the `.env` values that matter:

- `DB_*` — PostgreSQL connection details, then:
  ```bash
  php artisan migrate --seed
  php artisan storage:link
  ```
- `MIDTRANS_*` — the **server key never leaves the server**; only the client
  key is exposed to the browser through the Snap popup. Keep
  `MIDTRANS_IS_PRODUCTION=false` while testing with sandbox keys.
- `REVERB_APP_*` — the app id/key/secret that `reverb:start` serves; the
  frontend reads them through the mirrored `VITE_REVERB_*` variables.

The `composer.json` also exposes a one-shot `composer setup` script that runs
install + migration + asset build.

### Demo accounts

`php artisan db:seed` creates:

| Role        | Email                      | Password       |
|-------------|----------------------------|----------------|
| Super admin | `superadmin@clothested.test` | `superadmin123` |
| Admin       | `admin@clothested.test`      | `admin123`      |
| Customer    | `customer@clothested.test`   | `customer123`   |

## Running

```bash
php artisan serve            # the web app on http://127.0.0.1:8000
php artisan reverb:start     # the WebSocket server used by the chat
php artisan schedule:work    # payment expiry checks + daily 01:00 DB backup
npm run dev                  # Vite dev server (or `npm run build` for prod)
```

During development, `composer dev` bundles serve + queue/log-dev commands.

## Tests

The suite runs on SQLite in memory:

```bash
php artisan test
```

## Scheduled jobs

- `midtrans:expire-pending` every 5 minutes: expires stale Midtrans
  transactions and releases their reserved stock.
- `db:backup` daily at 01:00: dumps the database through `pg_dump` into
  `storage/app/private/backups` (keeps files for 14 days; pass
  `--keep-days=` to change).

## Backup and recovery

Make a manual dump at any time:

```bash
php artisan db:backup
```

To restore on a fresh database (tested procedure):

```bash
createdb -h 127.0.0.1 -U clothested clothested_restored
pg_restore -h 127.0.0.1 -U clothested -d clothested_restored storage/app/private/backups/backup-YYYYMMDD_HHMMSS.dump
```

Then point `DB_DATABASE` at the restored name, or rename the databases. Drop
scratch databases after verification.

## Operating notes

- HTTPS is forced in production: generated URLs always use the secure scheme.
- Registration is rate-limited to 5 attempts per 10 minutes per IP, and login
  to 5 failures per minute per account.
- Order status transitions (cancel, ready for pickup, ship, receipt confirm)
  each leave one structured log line; Midtrans webhook processing logs its
  results too. Daily log files are kept for 30 days (`LOG_DAILY_DAYS=30`).
- Product detail pages expose per-page titles, meta descriptions, and
  Product JSON-LD structured data; catalog and category pages set their own
  title and description.
- Account deletion is manual and only performed by the super admin from
  **Admin → Accounts**; deleted accounts are removed along with their carts
  and wishlists while their orders stay for bookkeeping.

## Status

The phase-by-phase implementation progress lives in
[`docs/IMPLEMENTATION_STATUS.md`](docs/IMPLEMENTATION_STATUS.md).
