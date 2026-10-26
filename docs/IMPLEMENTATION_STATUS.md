# ClothesTed v1.0 — Implementation Status

Tracker of functional requirements (FR-01..FR-64) from
`docs/ClothesTed-Product-Requirements-v1.0` mapped to the modules that implement
them. Updated at the end of every phase.

Status values: `done` / `partial` / `not started`.

## Phase Progress

| Phase | Scope | Status |
|---|---|---|
| 0 | Plan + requirement checklist | done |
| 1 | Project setup, migrations per ERD 2.1-2.5, models, factories, seeders | done |
| 2 | Authentication, roles, middleware, profile (FR-01..FR-08) | not started |
| 3 | Storefront catalog and product detail (FR-09..FR-24) | not started |
| 4 | Cart and wishlist (FR-25..FR-31) | not started |
| 5 | Checkout, OrderService, stock reservation (FR-32..FR-40) | not started |
| 6 | Midtrans payment, webhook, expiry (FR-41..FR-47) | not started |
| 7 | Order lifecycle and admin panel (FR-48..FR-57) | not started |
| 8 | Real-time chat with Reverb (FR-58..FR-64) | not started |
| 9 | Non-functional hardening, full test pass, README | not started |

## Database Foundation (Phase 1)

- Migrations: `database/migrations/0001_01_01_000000_create_users_table.php` (users + role),
  `2026_10_26_081239_create_categories_table.php`,
  `2026_10_26_081240_create_products_table.php`,
  `2026_10_26_081241_create_orders_table.php`,
  `2026_10_26_081242_create_payments_table.php`,
  `2026_10_26_081243_create_cart_items_table.php`,
  `2026_10_26_081245_create_chat_sessions_table.php` — mirror ERD 2.1-2.5
  (money `decimal(12,2)`, enum status columns, snapshot columns on `order_items`,
  `payments.order_id` UNIQUE, `shipping_addresses.order_id` UNIQUE, cart quantity check).
- Models: `app/Models/{User,Order,OrderItem,Payment,ShippingAddress,Category,Product,ProductVariant,ProductImage,CartItem,Wishlist,ChatSession,ChatMessage}.php`
  with `Product::active()` (FR-09/FR-24), `ProductVariant::isStockAvailable()` and `label()`,
  `Order::isCancellable()` (FR-40).
- Factories: `database/factories/{User,Category,Product,ProductVariant,Order}Factory.php`.
- Seeders: `database/seeders/{AdminUserSeeder,CategorySeeder,ProductSeeder}.php`
  — demo accounts for the three roles, 5 categories, 8 products (1 inactive, 4 featured),
  46 SKUs, 24 placeholder gallery images on the public disk.
- Tests: `tests/Feature/ErdSchemaTest.php`, `DemoSeedersTest.php`, `ModelInvariantsTest.php`.

## Functional Requirements

| FR | Requirement | Module | Status | Files |
|---|---|---|---|---|
| FR-01 | Registration with inline validation | Auth pages | not started | — |
| FR-02 | Automatic sign-in after registration, customer role | Auth pages | not started | — |
| FR-03 | Login with generic error + rate limit | Auth pages | not started | — |
| FR-04 | Post-login redirect by role | Route middleware | not started | — |
| FR-05 | Session handling, logout everywhere | Session config | not started | — |
| FR-06 | Profile: change name/phone | Profile page | not started | — |
| FR-07 | Admin area protected by role middleware | Middleware | not started | — |
| FR-08 | No automated password reset in v1.0 | Scope decision | not started | — |
| FR-09 | Catalog shows only active products | CatalogPage | not started | — |
| FR-10 | Product card: image, name, base price, featured badge | CatalogPage | not started | — |
| FR-11 | Text search over name/description | CatalogPage | not started | — |
| FR-12 | Combined filters (category, size, color, price) | CatalogPage | not started | — |
| FR-13 | Sorting newest/price | CatalogPage | not started | — |
| FR-14 | Livewire updates without full reload, URL state | CatalogPage | not started | — |
| FR-15 | Pagination 24 per page | CatalogPage | not started | — |
| FR-16 | Category management, unique slugs | CatalogPage + admin CRUD | not started | — |
| FR-17 | Product page with slug and gallery order | ProductDetail | not started | — |
| FR-18 | Variant picker maps to SKU price/stock | ProductDetail | not started | — |
| FR-19 | Final price updates live | ProductDetail | not started | — |
| FR-20 | Add to cart blocks zero stock SKU | ProductDetail | not started | — |
| FR-21 | Wishlist toggle at product level | ProductDetail | not started | — |
| FR-22 | Inactive product page 404 | ProductDetail | not started | — |
| FR-23 | Order item snapshot columns | OrderService (FR-37) | not started | — |
| FR-24 | Publish only with at least one variant | Product scope | partial | `app/Models/Product.php` |
| FR-25 | Login-required add to cart with return URL | CartManager | not started | — |
| FR-26 | Same SKU merges into one row | CartManager | not started | — |
| FR-27 | Quantity clipped to 1..30 and stock | CartManager | not started | — |
| FR-28 | Instant subtotal updates | CartManager | not started | — |
| FR-29 | Stock recheck at checkout | OrderService | not started | — |
| FR-30 | Wishlist move to cart uses first available variant | Wishlist | not started | — |
| FR-31 | Cart and wishlist persist per account | Tables | partial | `database/migrations/2026_10_26_081243_create_cart_items_table.php` |
| FR-32 | Checkout requires login; empty cart guidance | CheckoutForm | not started | — |
| FR-33 | Pickup or courier method | CheckoutForm | not started | — |
| FR-34 | Address form required fields | CheckoutForm | not started | — |
| FR-35 | Flat shipping fee from config | OrderService | not started | — |
| FR-36 | Subtotal + fee = total, customer note | CheckoutForm | not started | — |
| FR-37 | Atomic order creation with TRX code + stock reserve | OrderService | not started | — |
| FR-38 | Straight to Snap page; order stays pending | CheckoutForm | not started | — |
| FR-39 | Customer sees only own orders | Orders pages | not started | — |
| FR-40 | Customer cancellation only while pending | Orders pages | partial | `app/Models/Order.php` |
| FR-41 | Snap payment types, gross_amount = total | MidtransPaymentService | not started | — |
| FR-42 | One payment per order, unique midtrans id | Schema + service | partial | `database/migrations/2026_10_26_081242_create_payments_table.php` |
| FR-43 | Waiting page with check-status button | CheckoutForm | not started | — |
| FR-44 | Webhook verifies SHA-512, idempotent, always HTTP 200 | WebhookController | not started | — |
| FR-45 | Settlement/capture → processing, filled paid_at | MidtransPaymentService | not started | — |
| FR-46 | Expire/cancel/deny → cancelled + stock release | MidtransPaymentService | not started | — |
| FR-47 | raw_payload stored, failures logged | WebhookController | not started | — |
| FR-48 | Dashboard sales summary + queue | Dashboard | not started | — |
| FR-49 | Catalog CRUD with unique SKU | Admin catalog | not started | — |
| FR-50 | Stock adjustments | Admin catalog | not started | — |
| FR-51 | Order manager list/detail | AdminOrderManager | not started | — |
| FR-52 | Waybill required before shipped | AdminOrderManager | not started | — |
| FR-53 | Pickup flow: ready_for_pickup → completed | AdminOrderManager | not started | — |
| FR-54 | Delivered confirmation before completed | AdminOrderManager | not started | — |
| FR-55 | Admin cancel + manual refund note | AdminOrderManager | not started | — |
| FR-56 | Sales report on screen | Reports page | not started | — |
| FR-57 | Admin accounts, super_admin only | Admin accounts | not started | — |
| FR-58 | One open chat session per customer | ChatService | not started | — |
| FR-59 | Text-only, 2000 chars, last 50 messages | ChatService | not started | — |
| FR-60 | Real-time over chat.{sessionId} | CustomerChat | not started | — |
| FR-61 | Admin panel, first reply claims admin_id | AdminChatPanel | not started | — |
| FR-62 | ChatRead event, unread counters | Chat events | not started | — |
| FR-63 | Admin can close session | ChatService | not started | — |
| FR-64 | No file attachments in v1.0 | Scope decision | not started | — |

## Acceptance Checklist (PRD section 8)

| Check | Status |
|---|---|
| Registration and login redirect by role | not started |
| Admin area rejects customer role | not started |
| Courier checkout stock deduction + total | not started |
| order_code format and uniqueness | not started |
| Settlement webhook idempotent, paid_at filled | not started |
| Wrong signature changes nothing | not started |
| 24h expiry cancels and releases stock | not started |
| Pickup flow visible to customer | not started |
| Waybill required before shipped | not started |
| Customer cancel only while pending, own orders only | not started |
| Chat messages realtime both sides, one open session | not started |
