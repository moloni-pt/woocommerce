# AGENTS.md — Moloni for WooCommerce

Guidance for AI coding agents (Claude Code, Cursor, Copilot, …) and new developers working in this plugin. This is the canonical context file; `CLAUDE.md` imports it.

## What this plugin is

**Moloni** (`moloni.php`, WordPress.org slug `moloni`) is a WordPress/WooCommerce plugin that connects a WooCommerce store to Moloni (moloni.pt) invoicing. It turns WooCommerce orders into Moloni fiscal documents (invoices, invoice-receipts, receipts, credit notes on refunds…), creates the customers and products those documents need, and syncs products/stock between the two platforms. It is a PHP module that talks to Moloni **only over the public API** (`https://api.moloni.pt/v2/`). It does not run alongside any Moloni platform or call internal services.

## Responsibilities & boundaries

**Owns** (reacts to WordPress/WooCommerce hooks, never webhooks):
- **Document generation:** `woocommerce_order_status_changed` (automatic, per settings) and the admin pending-orders list / bulk actions (manual) → `Services\Orders\CreateMoloniDocument`, orchestrating `Controllers\Documents` + `OrderCustomer` / `OrderProduct` / `OrderShipping` / `OrderFees` / `Payment` / `DeliveryMethod`.
- **Credit notes:** `woocommerce_refund_created` → `Services\Orders\CreateCreditNote`.
- **Product sync:** `woocommerce_update_product(_variation)` → push to Moloni (`Hooks\ProductUpdate`). The `moloniProductsSync` cron pulls stock from Moloni (`Crons`, `Services\Stocks\SyncStockFromMoloni`). There are also manual import/export tools (`Services\WcProducts`, `Services\MoloniProducts`).
- **Admin UI:** the Moloni menu (`Menus\Admin`, `src/Templates/`), order and product meta boxes, the orders-list column, and the document link on the customer's order page.

**Must not:**
- **double-issue a document** (the pending list + `Services\Orders\DiscardOrder` "mark as generated" flow is the idempotency guard).
- invent tax/exemption/document-type mappings. Those are fiscal-legal.
- hard-code credentials, a company or an endpoint.

**Moloni API surface used:** `grant` (password + refresh token), `companies`, `documentSets`, `documents` plus the per-type endpoints (`<documentType>/insert|update`, the type taken from `Enums\DocumentTypes`), `customers`, `products`, `productCategories`, `taxes` (`getAll` / `insert`), `taxExemptions`, `stockMovements`, `warehouses`, `paymentMethods`, `deliveryMethods`, `maturityDates`, `measurementUnits`, `countries`, `currencies`, `currencyExchange`.

## Architecture map

| Path | What it is |
| --- | --- |
| `moloni.php` | Plugin header (version), constants, activation/deactivation hooks, boots `Start()` → `Plugin` on `plugins_loaded` |
| `src/Plugin.php` | Wires admin pages, Ajax and every hook class, plus the cron; handles admin actions |
| `src/Start.php` | Login / company-select gate before the main admin UI |
| `src/Hooks/` | One class per WP/WC hook group (order status, refunds, product update, meta boxes, orders list, HPOS compat, upgrade) |
| `src/Controllers/` | Map WooCommerce entities → Moloni payloads (`Documents`, `Order*`, `Product`, `ProductCategory`, `Payment`, `DeliveryMethod`) |
| `src/Services/` | Use-case services (orders → documents/credit notes, stock sync, product import/export, mails, document download/open) |
| `src/Curl.php` | Moloni API client (`Curl::simple($action, $values)`), login/refresh, request log, per-request cache |
| `src/Model.php` | Tokens + settings persistence (custom DB tables) and `defineConfigs()` |
| `src/Tools.php` | Shared helpers (tax lookup/creation, countries, currency, …) |
| `src/Activators/` | Install / remove / updater (table creation and migrations, multisite aware) |
| `src/Templates/` | Admin views (PHP templates) |
| `.dev/` | Front-end asset sources + gulp/Tailwind/Sass toolchain (own `package.json`) |
| `assets/` | **Compiled** CSS/JS loaded by the plugin (build output of `.dev/`) |
| `vendor/` | Committed Composer autoloader (PSR-4 `Moloni\` → `src/`; there are no runtime dependencies) |

No git submodules.

## Running locally

Full walkthrough: `dev.md`.

1. **PHP:** `composer install` at the root. `vendor/` is committed, so this only matters if you change autoloading. Commit the regenerated autoloader if you do.
2. **Assets** (only when touching CSS/JS): `cd .dev && npm install && npm run build-prod`. This compiles into `assets/`.
3. **Store:** `docker compose up -d` at the root. It runs the official `wordpress:php8.2-apache` + MariaDB images on `http://localhost:8081/wp-admin` (`admin` / `123456789`). The checkout is bind-mounted at `wp-content/plugins/moloni`, so edits are live. On first run a one-shot `setup` (wp-cli) service installs WordPress + WooCommerce (EUR, base country PT, taxes enabled) and activates the plugin. `docker compose down -v` resets the store.
4. In WP admin, open **Moloni**, log in with a Moloni account and pick the company. Settings are saved in the plugin's DB tables. Nothing is committed.

## Commands

| Command | Where | What it does |
| --- | --- | --- |
| `composer install` | root | Regenerate the autoloader |
| `npm install` | `.dev/` | Install the asset toolchain |
| `npm run build-prod` | `.dev/` | Compile CSS/JS into `assets/` (gulp). Rerun after any CSS/JS change |
| `docker compose up -d` / `down -v` | root | Start / reset the local WordPress + WooCommerce store |
| `php -l <file>` | root | Syntax check (the only static check available) |

## Verification

**There is no automated test or lint gate.** The repo has no `phpunit`, `phpcs` or `phpstan` config, and the only workflow (`.github/workflows/release.yml`) deploys to WordPress.org SVN on `v*` tags. Nothing runs on PRs. To verify a change:

1. `php -l` every touched PHP file. The plugin declares `php >= 7.2`, so don't use syntax newer than 7.2: no typed properties, enums, `match`, constructor promotion, nullsafe `?->` or `readonly`.
2. **Exercise the behaviour in the Docker store**: generate a document from an order, refund → credit note, product save → Moloni product, stock sync, settings save. This is the real check for any behaviour change. Use a Moloni test company and keep documents as **drafts** unless closing them is the point of the test.
3. Rebuild assets if you touched `.dev/`.

## Conventions

- **Namespace `Moloni\`**, PSR-4 → `src/`, one class per file. Hook classes register themselves in their constructor and are instantiated from `Plugin::actions()`.
- **WordPress idioms:** `add_action`/`add_filter`, `wc_get_order`, `sanitize_text_field` on request input, capability checks on admin actions.
- **Settings are PHP constants.** `Model::defineConfigs()` turns every row of `{prefix}moloni_api_config` into an upper-cased constant (`EXEMPTION_REASON`, `MEASURE_UNIT`, `DOCUMENT_STATUS`, …). Always read them with a `defined('X') ? X : default` guard.
- **API calls** go through `Curl::simple('<resource>/<method>', $values)`. `company_id` is injected automatically, and errors surface as `APIException`.
- **HPOS and legacy orders are both supported.** Branch on `Storage::$USES_NEW_ORDERS_SYSTEM` (see `Hooks\OrderList`). Never assume `shop_order` posts.
- **Merchant-facing UI uses WordPress/WooCommerce admin conventions.** Match what `src/Templates/` already renders.
- **User-facing strings are pt-PT**, wrapped in `__()`.
- **Assets are compiled artifacts.** Edit sources in `.dev/`, never hand-edit `assets/`, and commit the rebuilt output.
- **Branch flow:** cut branches from `devel` and open PRs against `devel`. `master` is the release branch, and a `v*` tag deploys to WordPress.org.
- **Every fix/feature PR carries its release bump:**
  - `moloni.php` header `Version:`
  - `readme.txt` `Stable tag:` plus a `== changelog ==` entry
  - `README.md` `**Stable tag:**` plus a `## Changelog` entry

  Changelog lines are pt-PT, prefixed `FIX:` / `UPDATE:`, and the two changelogs must match.
- **Idempotency + fiscal correctness are load-bearing.** Document creation must not double-issue, and tax/document-type/exemption mappings are fiscal-legal.

## Gotchas

Curated, verified conventions and traps. This is the default-trusted source — every agent reads it.

- **`Tools::getTaxFromRate($rate, $zone)` creates taxes.** If no active IVA percentage tax with that value exists in that fiscal zone, it **inserts one into the merchant's Moloni company** (`Tools::createTax`). A wrong `(rate, zone)` pair leaves a permanent, fiscally invalid tax in the merchant's account. Make sure the rate and the zone come from the same location.
- **The fiscal zone follows `woocommerce_tax_based_on`.** `Documents::setFiscalData()` uses the billing country, the shipping country, or (for `base`) the Moloni company's country. That zone is passed to every line controller (`OrderProduct`, `OrderShipping`, `OrderFees`) and onto products created on the fly.
- **`Curl::simple` caches some reads per request** (`companies/getOne`, `taxes/getAll`, `countries/getAll`, currencies, `paymentMethods/getAll`). The only invalidation is `taxes/insert` → `taxes/getAll` (`$resetCacheMethods`), so other cached lists stay stale for the rest of the request.
- **Settings are constants, defined once per request.** Changing a setting mid-request has no effect until the next request, and a missing row means the constant is undefined, not empty.
- **Multisite:** tables are per blog prefix (`$wpdb->get_blog_prefix()`), created in `Activators\Install` and migrated in `Activators\Updater`. New tables or columns need both.
- **Release packaging:** the WordPress.org deploy honours `.distignore`. Add any new dev-only file there, or it ships to every store.
- **No submodules** (`.gitmodules` absent).

> Recent, **unverified** findings live in `.claude/journal/` (one dated file per finding). They are promoted into this section by manual curation once re-verified against the code — treat them as leads, not yet rules.
