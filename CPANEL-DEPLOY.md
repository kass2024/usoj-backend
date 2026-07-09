# cPanel deploy — e-learning.uosj.ac.ug

## Fix HTTP 500 (most common causes)

### 1. Document root stays as subdomain folder (fixed via `.htaccess`)

Keep document root as:

```
/home/visawgnz/e-learning.uosj.ac.ug
```

The root `.htaccess` routes all traffic to `public/index.php` — no cPanel change needed.

Ensure `.env` has:

```
ASSET_URL=https://e-learning.uosj.ac.ug/public
```

### 2. Install PHP dependencies (required — `vendor/` is NOT in git)

cPanel → **Terminal** (or SSH):

```bash
cd ~/e-learning.uosj.ac.ug
cp .env.cpanel .env
composer install --no-dev --optimize-autoloader
```

If `composer` is not on PATH, use:

```bash
php composer.phar install --no-dev --optimize-autoloader
```

Bulk course Excel upload uses built-in PHP spreadsheet helpers (no extra Composer package required).

```bash
php artisan storage:link
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
chmod -R 775 storage bootstrap/cache
```

### 2b. cPanel Gemini settings (required for AI Transcript Studio)

Shared hosting often cannot resolve DNS for many parallel Google API calls.
Add these to `.env` on the server (do **not** overwrite your existing `.env` — only append):

```
# Recommended for cPanel — one request at a time with retries
GEMINI_SEQUENTIAL_MODE=true
GEMINI_PARALLEL_REQUESTS=1
GEMINI_CONNECT_TIMEOUT=30
GEMINI_TIMEOUT=60
GEMINI_RETRY_ATTEMPTS=3
GEMINI_REQUEST_DELAY_MS=750

# If DNS still fails, skip Gemini entirely (transcript still works with built-in questions):
# GEMINI_FALLBACK_ONLY=true
```

Then:

```bash
php artisan config:clear && php artisan config:cache
```

Test DNS from cPanel terminal:

```bash
curl -I --max-time 15 https://generativelanguage.googleapis.com
```

If that times out, contact your host or use `GEMINI_FALLBACK_ONLY=true`.

### 3. PHP version

cPanel → **Select PHP Version** → choose **PHP 8.1** or **8.2** for this domain.

### 4. If still failing — read the error log

In File Manager open:

```
~/e-learning.uosj.ac.ug/storage/logs/laravel.log
```

Or temporarily in `.env`:

```
APP_DEBUG=true
```

(revert to `false` after fixing)

### 5. Remove duplicate folder

If you cloned git into `usoj-backend/` inside the site folder, either:
- Delete the extra `usoj-backend` folder, OR
- Point the subdomain to `~/e-learning.uosj.ac.ug/usoj-backend/public`

Do **not** keep two copies — use one project root only.

## Folder layout (correct)

```
e-learning.uosj.ac.ug/
├── app/
├── bootstrap/
├── public/          ← document root should point HERE
│   ├── index.php
│   ├── .htaccess
│   └── images/
├── storage/
├── vendor/          ← created by composer install
├── .env
├── artisan
└── composer.json
```
