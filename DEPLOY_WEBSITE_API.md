# Deploy website programmes API to cPanel

The public site (`uosj.ac.ug`) calls:

`https://e-learning.uosj.ac.ug/api/website-programmes?category=undergraduate`

If you see **"The route api/website-programmes could not be found"**, the Laravel backend on cPanel is missing the new files.

## 1. Upload these files to `e-learning.uosj.ac.ug`

Upload into your Laravel project root (same folder as `artisan`):

| File | Action |
|------|--------|
| `routes/api.php` | Replace |
| `app/Http/Controllers/ProgramsApiController.php` | Replace |
| `app/Http/Controllers/WebsiteProgrammeApiController.php` | Upload (new) |
| `app/Services/WebsiteCatalogueService.php` | Upload (new) |
| `app/Models/Department.php` | Replace |
| `database/migrations/2026_07_08_200000_add_website_fields_to_departments_table.php` | Upload (new) |

Optional (admin can edit duration/mode on departments):

- `app/Http/Controllers/Settings/DepartmentController.php`
- `resources/views/settings/departments.blade.php`

## 2. Run in cPanel Terminal (or SSH)

```bash
cd ~/e-learning.uosj.ac.ug   # adjust to your Laravel path
php artisan migrate --force
php artisan cache:clear
php artisan route:clear
php artisan config:clear
php artisan optimize:clear
```

Programmes are cached for 15 minutes on the server. After editing departments, cache clears automatically. To refresh manually:

```bash
php artisan cache:forget website_programmes.undergraduate
php artisan cache:forget website_programmes.diploma
php artisan cache:forget website_programmes.short_course
```

## 3. Verify API

Open in browser:

```
https://e-learning.uosj.ac.ug/api/website-programmes?category=undergraduate
```

You should see JSON with `"total": 34` (or your current department count).

## 4. Rebuild and upload the React site

On your PC:

```bash
cd usj-website
npm run build
```

Upload everything inside `dist/` to `public_html` on `uosj.ac.ug`.

`.env.production` must contain:

```
VITE_API_BASE_URL=https://e-learning.uosj.ac.ug/api
```

## Temporary fallback

The website no longer makes dozens of slow fallback API calls. It uses one fast endpoint with an 8-second timeout and shows a clear error if the API is missing or unreachable.
