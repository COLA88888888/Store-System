# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project layout: backend/ + frontend/

```
websizewlaodev/
├── backend/    the POS / admin system (everything this file describes below)
├── frontend/   the public website: company pages + online shop — read frontend/README.md
└── .htaccess   rewrites every URL except backend/ into frontend/ (no root index.php)
```

- **Every path in the sections below is relative to `backend/`** (`config/db.php` = `backend/config/db.php`).
- `frontend/` is plain PHP + vanilla JS (no jQuery, no AdminLTE, no build). Each page requires `frontend/includes/bootstrap.php` only — it never includes `backend/config/db.php` (that runs staff auth, the idle timeout and migrations). Both sides share the `webwlaodev` DB.
- Separate sessions: backend cookie `WLAODEVSSID` (path `/`), frontend cookie `WLAOWEBSSID` (set in `frontend/config/session.php`). Don't make one side read the other's session.
- Website tables (`w_web_orders`, `w_web_order_items`, `w_web_contacts`) are created by `backend/config/migrations/schema_website.php` (v22). New frontend tables go there too; frontend never creates tables. Web orders do **not** decrement stock — they stay `pending` until staff handle them. There is no backend page for them yet.
- The contact page is a chat room (`w_web_chats`, `w_web_chat_messages`, v25): visitor text/voice → `frontend/api/chat.php`; the bot is Claude (raw curl, key in gitignored `frontend/config/ai.php`) or keyword rules when there is no key; staff reply at `backend/pages/web_chats/` (gated on the `customers` permission). Voice files go to `backend/assets/web_chat_audio/` (gitignored). Multilingual (v26): `frontend/includes/translate.php` (dependency-free, also required by the backend) detects the visitor's language → `w_web_chats.lang`, bot replies via `tl()`/AI in that language; staff see visitor messages translated to Lao and can send replies translated out (`body_tr` keeps the other side). Translator = Claude when a key is set, else the free MyMemory API.
- Members (`w_web_members`, v28/v38): sign-up **only in the popup** `frontend/components/member_modal.php` (there is no register page; old `pages/register` URLs 302 to `/#register`, which opens the popup) (real Lao mobile only — `laoPhoneNormalize()` stores `020XXXXXXXX`/`030XXXXXXX`), login by phone **or** email, or Google (`models/member_google.php`, Client ID in gitignored `frontend/config/google.php`; empty = no Google button; Google members have `tel` NULL and must add one before ordering). **Only members see prices** (`canSeePrices()`); guests get the quote form. Members order with address + courier branch + bank + slip (`models/member_orders.php`, `components/member_order_form.php`, `pages/checkout.php`), see history/tracking at `pages/my_orders.php` / `my_order.php`, edit their profile at `pages/account.php`. Orders carry `w_web_orders.member_id`; staff move them through all 6 statuses + `tracking_no` in `backend/pages/web_orders/`. Slips go to `backend/assets/web_slips/` (gitignored). Member emails (welcome, new order, order status/tracking, price drop) are queued in `w_web_email_queue` (v41) and sent in the background by `frontend/api/mail_queue.php` via SMTP from gitignored `frontend/config/mail.php` (missing = Laragon Mailpit 127.0.0.1:1025, UI :8025); backend queues through `config/helpers/web_mail.php`. Members opt out with `w_web_members.email_notify`.
- Product images live in `backend/assets/product_img/`; the frontend links to them via `productImage()`.
- Uploaded files: one folder per kind, never mixed, and replacing or deleting an image also deletes its old file. Website: `backend/assets/web_slips/` (transfer slips), `web_receipts/` (courier receipts staff upload in `pages/web_orders/`), `img/members/` (member avatars: `m<id>_*.jpg` + `_full.jpg`), `web_chat_files/<Ym>/`, `web_chat_audio/`. Slips and receipts are `Require all denied` and served only through `pages/web_orders/slip.php` (staff) and `frontend/api/slip.php` (order owner), each with `?type=receipt` for receipts. Back office: `product_img/`, `img/users/`, `img/banks/`, `img/qr/` (shop QR + bank QR), `img/logo/`, `img/clients/`, `img/works/<slug>/`.

## What this is

MiniPos — a point-of-sale system for a Lao retail shop. Plain PHP 8.2 + MySQL on XAMPP, jQuery + AdminLTE 3 (Bootstrap 4) on the front end. No Composer, no npm, no build step, no test suite. Files are edited and served directly.

UI text, DB seed data, and most code comments are in Lao (some Thai). Keep new user-facing strings in Lao to match.

## Running it

Served by XAMPP Apache from the working directory:

- App: `http://localhost/websizewlaodev/backend/` (Laragon; redirects to `auth/login.php`). Website: `http://localhost/websizewlaodev/` — clean URLs without `.php` (`/websizewlaodev/pages/shop`) are rewritten to `frontend/pages/shop.php` by the root `.htaccess`; a GET to `pages/x.php` 301s to `pages/x`, and old `/frontend/...` URLs 302 to them. `url('pages/x.php?…')` strips the `.php` itself (`api/*.php` keep it). Always build frontend links with `url()` / `asset()` so they stay clean
- DB: MySQL `webwlaodev`, user `root`, empty password — hardcoded in `config/bootstrap/connection.php`. **Every table carries the `w_` prefix** (`w_products`, `w_tbuser`, `w_tbsale_save`, …); write new SQL with it. This folder is a separate copy of `Project/StoreMini`: never read or write that folder, its `storepos` DB (`st_` tables), or `minipos` (`Project/MiniPosStoreandShwe`). Session cookie is `WLAODEVSSID`, browser storage keys start with `WlaoDev.`
- Timezone is forced to `Asia/Vientiane`; invoice numbers and reports depend on it

There is nothing to build. Edit a file, reload the page.

Debugging goes through the server logs, since there are no tests:

```bash
# PHP fatals / warnings (this is the fastest way to find a broken page or AJAX call)
tail -c 200000 "D:/laragon/tmp/php_errors.log" | grep -a "PHP"

# Which endpoints the browser actually hit
tail -c 500000 "D:/laragon/bin/apache/<version>/logs/access.log" | grep -a "pos_backend"
```

To exercise an endpoint without a browser, forge a session file and POST to it. Apache keeps sessions in `D:/laragon/tmp` (the CLI `php` reports `D:/xampp/tmp`, which is the wrong one):

```bash
php -r 'file_put_contents("D:/laragon/tmp/sess_testsession0000000000abc","user_id|i:1;checked|i:1;last_activity|i:".time().";");'
curl -s -b "WLAODEVSSID=testsession0000000000abc" -X POST "http://localhost/websizewlaodev/backend/api/pos_backend.php" --data-urlencode 'action=checkout' --data-urlencode 'cart=[...]'
```

Checkout writes real rows to `w_tbsale_save`, `w_tbsale_save_detail`, `w_sales` and decrements `w_products.qty`. A nonexistent `product_id` does **not** roll back — the bill and a detail line are still saved — so delete the rows of any test invoice afterwards.

## Architecture

### The iframe shell

`home/dashboard.php` is the master layout and the only page that renders `layouts/sidebar.php`. Everything under `pages/` loads inside `<iframe name="frame">`. Consequences that matter constantly:

- Sidebar links use `target="frame"`; pages themselves never include the sidebar
- A page that needs to redirect the whole app must break out of the frame: `echo "<script>window.top.location.href = '...'</script>"` — a plain `header('Location:')` only navigates the iframe
- `dashboard.php` picks the initial iframe `src` by walking the user's permissions, so a user with no `dashboard` permission lands on the first module they can see
- Root-level `dashboard.php`, `home.php`, `logout.php`, `check_user.php` are one-line `require_once` shims to the real files in `home/` and `auth/`

### File layout: no file over 500 lines

Large files are split into pieces that the original file `require`s **at the exact spot the code used to be**, so the URL, the variable scope and the rendered output are all unchanged. Conventions (full Lao guide: `docs/ໂຄງສ້າງລະບົບ.md`):

- Pieces live in a subfolder next to the original, usually `partials/<file-name>/`, `actions/`, or `<file-name>/` for JS partials (e.g. `api/pos/`, `pages/pos/partials/js/checkout/`, `pages/products/actions/`)
- Every piece starts with a Lao header (what it does, which file it came from); every slimmed entry file starts with a Lao map of its pieces in `require` order. **The order matters** — later pieces use variables and functions earlier ones created
- A piece cut from inline HTML/JS is included as `<?php require __DIR__ . '/…'; ?>` on its own line (PHP eats the newline after `?>`, so output stays byte-identical); a PHP piece is a plain `require`. Inside a piece, `__DIR__` is the piece's folder — use `dirname(__DIR__)` to reach the original's folder
- Folders holding only pieces have an `.htaccess` with `Require all denied` (direct browser access → 403). Copy it into any new pieces folder
### config/db.php does far more than connect

Every request includes it. The file itself is now a short ordered list of `require`s into `config/bootstrap/` (connection, session + permissions, branch switcher), `config/migrations/` (schema auto-migrations) and `config/helpers/` (global functions). Together they:

- Opens **both** `$conn` (mysqli) and `$pdo` (PDO). New code should use `$pdo` with prepared statements; `$conn` survives in older paths
- Starts the session, enforces a 15-minute idle timeout, and re-reads `tbuser` to rebuild `$_SESSION['permissions']`, `status`, `store_id` on **every** request — so a permission change takes effect on the user's next page load, and stale session data is not a thing to work around
- Logs a non-admin out if their branch's `tbstore.status` is not `active`
- Holds the **idempotent migrations**: `CREATE TABLE IF NOT EXISTS`, `SHOW COLUMNS` + `ALTER TABLE ADD`, column renames, index creation, and `DROP TABLE IF EXISTS` for a deprecated-table list. This is how the schema evolves — the steps live in `config/migrations/schema_*.php` and **all eight files** run only while `config/.db_schema_vN` is missing (since 2026-10-31 loyalty/stock_check/shifts/held_orders no longer run per session). The shop owner does not want tables or data appearing on their own: migrations insert no sample rows (only the admin account and first branch on a completely empty DB), and no page creates tables at runtime. `logActivity()` is a no-op and `w_activity_logs` was dropped — don't reintroduce either. To add a column, append a guarded block to the matching migration file (and bump `vN` in `config/db.php`) rather than altering the DB by hand, or it will be missing on other installs. Note the flip side: adding a table name to the `$unusedTables` array (in `schema_core.php`) silently drops it on the next request.
- Defines the global helpers every page relies on (`config/helpers/`): `hasPermission()`, `getActiveStoreId()`, `getPosStoreId()`, `isMainBranch()`, `generateProductId()`, `getSetting()`, `formatCurrency()`, `logActivity()`, `resolveBankLogo()`, `resolveBankQr()`, `getLowStockAlerts()`

### Permissions

`hasPermission($module, $action)` is the single gate, checked both at the top of each page (to block access) and inline around buttons (to hide actions). It resolves in this order:

1. Admin bypass — `user_id === 1`, or `status` is `ຜູ້ບໍລິຫານ` / `admin` / `super admin`
2. A per-user override row in `user_permission_switch_states`, keyed `perm_<module>_<action>_<userId>` where action is one of `view|add|edit|del`
3. Fall back to the boolean column on `tbuser` for the module

`$module` goes through an alias map inside the function (`pos`→`sale`, all the report pages→`report`, all the settings pages→`setup`, …). When adding a module, add it to the alias map (`config/helpers/permissions.php`), to the `$_SESSION['permissions']` array built in `config/bootstrap/session_auth.php` (and the login copy in `auth/check_user.php`), and to `layouts/sidebar.php`.

### Multi-branch

`tbstore` holds branches; `store_id` is carried on `products`, `sales`, `tbsale_save`, `customers`, `imports`, `accounting_records`, `tbuser`.

Always scope queries with `getActiveStoreId($pdo)` — never `$_SESSION['store_id']` directly. Cross-branch visibility is decided by `canViewAllBranches($pdo)`: user Id 1 and any user (admin or staff) whose own branch is `is_main` pass it, and only they can switch via `?switch_store_id=N` (parked in `$_SESSION['active_store_id']`). Every user assigned to a sub-branch, admins included, is hard-locked to their own `store_id`. Gate branch dropdowns and "all branches" queries on that helper, not on `$isAdmin || $isMain`.

### Page composition

Every page under `pages/` follows the same shape:

```php
session_start();
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';
require_once __DIR__ . '/../../api/<module>_backend.php';  // auth + POST handling + data fetch
require_once __DIR__ . '/../../layouts/header.php';
// ... HTML, reading the $variables the backend prepared ...
require_once __DIR__ . '/../../layouts/footer.php';
```

`$base_path` must be right or every asset 404s. Pages one level deep (`pages/pos/`) use `../../`; pages under `pages/settings/*/` hardcode `../../../`.

`layouts/header.php` opens `<html>` and `<body>` and bails to the login page if the session is missing; `layouts/footer.php` closes them. Header also loads jQuery/Bootstrap/SweetAlert2 **in `<head>`** so inline page scripts can use `$` and `Swal` immediately, monkey-patches `Swal.fire` to auto-dismiss success toasts after 1.8s, and actively unregisters any leftover service worker (`manifest.json` still advertises offline mode, but the SW is deliberately disabled).

Setting `$pos_page = true` before including the header suppresses the global navigation preloader.

### api/*_backend.php is dual-purpose

These files are **not** a REST layer. Each one is both:

- the controller `require_once`'d by its page — handles `$_POST['action']`, sets `$message`/`$message_type`, and prepares the `$variables` the view renders
- a direct AJAX target — when hit with an action it recognises, it sets `Content-Type: application/json`, echoes, and `exit()`s

So the same file must stay safe to include mid-page *and* to call standalone. Guard AJAX branches on both the method and the action, and `exit()` before any HTML would be emitted. `pos_backend.php` is the clearest example: `checkout` and `add_customer_ajax` exit early with JSON, and everything past them is page-load data fetching.

Purely-JSON endpoints exist too, and don't follow the pattern: `pages/{categories,products,users_manage}/api_*.php` and `auth/check_user.php`.

Reports are the other variation — `pages/reports/partials/reports_backend.php` is shared by several report pages, each seeding `$_GET['view_mode']` (`invoice` or `item`) before including it.

### The POS page

`pages/pos/pos.php` composes `partials/` (markup) and `partials/js/*.php` (behaviour, split by concern: cart, checkout, barcode, customer, bills — the big ones further split into `partials/js/{checkout,cart,bills}/`). The POS always works in the user's **own** branch via `getPosStoreId()`, never the branch picked in the switcher. Its backend `api/pos_backend.php` delegates to `api/pos/` (checkout in two steps: `checkout_totals.php` then `checkout_save.php`). Server state reaches JS through `window.*` globals set in `pos.php` (`POS_BACKEND_URL`, `POS_STORE_ID`, `POS_TODAY_SALE_COUNT`, `CURRENT_USER_NAME`) and through `var allProducts = <?php echo json_encode($products); ?>` in `partials/pos_js.php`.

Checkout posts `cart` as a **JSON string** and the backend `json_decode`s it. Keep it a string — passing the array through jQuery makes PHP receive `cart[0][product_id]=...` as an array instead.

The whole sale runs in one PDO transaction: insert bill → per item `SELECT ... FOR UPDATE`, validate stock, insert detail, `UPDATE products SET qty = qty - ?` → commit. Any shortfall rolls back and returns `{success: false, message}`. Products with `cut_qty = 0` (services, non-stock items) are sold without decrementing. The response carries `updated_stocks`, which the client uses to repaint stock counts without a reload.

### Assets and cache busting

Third-party libs live in `plugins/` (jQuery, Bootstrap, SweetAlert2, Select2, FontAwesome, overlayScrollbars, tempusdominus, iCheck), AdminLTE in `dist/`, plus a top-level `ionicons-2.0.1/`. Per-page stylesheets are in `themes/<page>.css`, shared ones in `assets/css/`. Stylesheets over 500 lines are split into numbered files linked in order (cascade order = file order): `themes/pos/0{1,2,3}-*.css`, `assets/css/global-custom/0{1,2}-*.css`, `assets/css/pages/menu-sidebar/0{1,2,3}-*.css`. `assets/css/main.css` is a vendor theme source and is left as is (the unused `css/` and `js/` copies were deleted on 2026-11-09).

Speed (2026-11-09): the root `.htaccess` turns on `zlib.output_compression` for all PHP output and sets browser cache headers — any URL with `?v=` is cached for a year, so always version local CSS/JS with `filemtime()`, never `time()` (that re-downloads on every page). An endpoint that sends its own `Content-Length` (file download, early-close response) must call `ini_set('zlib.output_compression', '0')` first. All-language translations for the dashboard come from the cacheable `home/i18n_all.php` (`window.I18N_ALL_MERGED`), not inline. Background polls skip hidden tabs: notifications 15s, permissions 10s, POS stock 8s.

Stylesheets are cache-busted with `?v=<?php echo filemtime(__DIR__ . '/../../themes/x.css'); ?>`. A wrong relative depth here doesn't just 404 the stylesheet — `filemtime()` warns into the Apache log and prints into the page.

Page CSS does not live in inline `<style>` blocks any more: it sits in `themes/` and is linked with `<?php echo themeCssLink('x.css'); ?>` (`config/helpers/theme_css.php`), which builds the path from `$base_path` and adds the `filemtime` cache-buster. Each former block is linked at the exact spot it used to be, so cascade order is unchanged — keep new CSS the same way. The only inline `<style>` left is inside JS strings that build a separate document (receipt/kitchen/barcode print windows, Excel/PDF exports in `reports_js.php` and `stock_check/partials/export_js.php`); those must stay inline because an external stylesheet won't load in time in a print iframe or at all in an exported `.xls`.

## Gotchas

- **`pages/settings/*/` entry points.** The sidebar always links to `index.php`. In some folders `index.php` is a shim that requires `<name>.php`; in others (`stores`, `printers`, `print_barcode`) `index.php` holds the live code. The stale near-copies were deleted on 2026-10-31. Trace from `index.php` before editing.
- **Absolute paths are hardcoded.** `resolveBankLogo()` / `resolveBankQr()` in `config/helpers/bank.php` embed `D:/xampp/htdocs/MiniPos`.
- **Backups are a pure-PHP dump, forced to `utf8mb4_general_ci`.** `api/database_backend.php` doesn't use `mysqldump`; it rewrites every charset/collation in the `CREATE` statements (the live DB mixes `0900_ai_ci`, `unicode_ci`, `utf8mb3`) so the file imports on older MySQL/MariaDB. Restoring one converts the live tables too. The functions live in `api/database/backup_functions.php`. A backup is either full (DROP/CREATE/INSERT) or one branch (first line `-- StoreMini branch backup: store_id=N`; `DELETE` that branch's rows, then `INSERT IGNORE`). Every file records `-- rows \`table\` N`, and the restore re-counts and reports any mismatch. Only skip `VIRTUAL|STORED GENERATED` columns: MySQL 8 flags `DEFAULT CURRENT_TIMESTAMP` columns as `DEFAULT_GENERATED`, and skipping those once lost every `created_at`. The clear-data page deliberately does not reset `AUTO_INCREMENT`, so restored branch rows never collide on id.
- **`hasPermission()` ignores its `$action` argument for the column fallback.** Only the `user_permission_switch_states` path distinguishes view/add/edit/delete; without an override row, any action resolves to plain module access.
- **Passwords accept several formats.** `auth/check_user.php` tries bcrypt, SHA-256, plaintext, and legacy MySQL `PASSWORD()` in turn.
- **The migration block in `config/db.php` only runs while `config/.db_schema_vN` is missing**, and any request (including a browser tab polling in the background) runs it and writes the flag. Bump the version and finish every edit to the block before the next request, or a half-edited migration runs once and is never repeated. Deleting the flag file forces a re-run.
- Auto-migration blocks in `config/db.php` swallow every exception (`catch (Throwable $ex) {}`). A migration that fails fails silently — confirm with `SHOW COLUMNS` rather than assuming it applied.

## Commits

Recent history uses conventional prefixes with an English summary: `fix: disable HTTP cache on get_live_stocks API`, `feat: dynamically pull selected bank QR code ...`. Older commits are bare `update`; follow the newer style.
