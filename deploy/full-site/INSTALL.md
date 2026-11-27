# Install Bear Studio on DirectAdmin

Serve the public website from `https://3earstudio.com` and the React panel from `https://admin.3earstudio.com`. The public website and admin API enter the same private Laravel application. The management UI is built and deployed independently. The panel calls `/api/v1` on its own host and uses a host-only HTTPS session cookie.

## Configure the document roots

1. In DirectAdmin File Manager, locate the parent of `public_html/`, usually `domains/3earstudio.com/`. Set up the three sibling directories below.
2. In **Subdomain Management**, open **Document Root Override** for `admin`. Set its document root to `/domains/3earstudio.com/admin_public_html` relative to the account home. Use the same path for the HTTPS document root where it is configured separately. [DirectAdmin documentation](https://docs.directadmin.com/webservices/apache/customizing.html#how-to-change-the-documentroot-of-only-one-of-your-subdomains)
3. Enable HTTPS and HTTP-to-HTTPS redirects for both hosts. The certificate must cover `3earstudio.com` and `admin.3earstudio.com`.

```text
domains/3earstudio.com/
  public_html/
    index.php
    .htaccess
    site-assets/
    thumbnails/
  admin_public_html/
    index.php
    .htaccess
    .user.ini
    index.html
    assets/
    _release/
  bearstudio-backend/
    .env
    app/
    storage/
    vendor/
```

## Place the prepared files

| Destination | Source |
| --- | --- |
| `public_html/index.php`, `admin_public_html/index.php` | `deploy/full-site/index.php` |
| `public_html/.htaccess` | `deploy/full-site/.htaccess` |
| `public_html/site-assets/` | `public/site-assets/` |
| `public_html/thumbnails/` | `public/thumbnails/` |
| `admin_public_html/` | Static build supplied by the independent management project |
| `admin_public_html/.htaccess` | `deploy/admin.htaccess` |
| `admin_public_html/.user.ini` | `public/.user.ini` |
| `bearstudio-backend/` | Private Laravel application with installed Composer dependencies, a private `.env`, and prepared storage |

Use the two exported PHP entry points and keep `bearstudio-backend/release-id` with the matching backend code. The [release and recovery runbook](../../docs/release-recovery.md) explains how to verify both deployed hosts against that package identifier.

Set `APP_URL=https://3earstudio.com` and `ADMIN_HOST=admin.3earstudio.com` in the private `.env`. For an update, keep the existing application key, MariaDB database and managed media. If the previous site still has `public_html/admin/` or the previous standalone `public_html/api/` entry point, move those old files outside the document root. The new rewrite rules send public API requests to `public_html/index.php`.

Apply the Laravel migrations from `bearstudio-backend/` with `php artisan migrate --force` before opening traffic. Use a private CLI or an authorized deployment runner with database connectivity to apply migrations. Do not copy database server files or use a web-accessible migration endpoint.

Use PHP 8.4.1+ in the 8.4 series, PDO MySQL, GD with WebP support and Laravel's required extensions on both hosts. Apache 2.4 must honor mod_rewrite in the provided `.htaccess` files. Both hosts' PHP processes need read access to `bearstudio-backend/` and write access to its `storage/` and `bootstrap/cache/` directories. Keep application files, runtime data and install archives outside both document roots. [Laravel deployment requirements](https://laravel.com/framework/docs/deployment)

## Verify

- Open `/`, `/projects`, and `/articles` on `https://3earstudio.com`. Verify that `/admin/` and `/api/v1/auth/session` return HTTP 404 on that host.
- Sign in at `https://admin.3earstudio.com/` and reload `/projects` on the same host. Verify the independently deployed panel release according to its own deployment guide.
- Request `https://admin.3earstudio.com/api/v1/auth/session` in a private browser session. Verify that it returns HTTP 401 with JSON.
- Verify that a project saved through the panel appears on the public site and that its image and video load.
- Verify `/services` and `/sitemap.xml` on the public host. Confirm `/robots.txt` references the sitemap on production and omits it where `APP_ALLOW_INDEXING=false`.
- Verify the panel routes `/services`, `/pages/home`, `/faqs` and `/settings`. Use staging data to check saves and public presentation. Verify media permissions through the API.


The web project does not build or implement the React panel. `deploy/admin.htaccess` forwards `/api/v1/` to PHP and other non-file navigation to the panel index.html. Apply equivalent routing when using Nginx or another host. Generate a backend-only release with `php scripts/package-release.php`; copy the exported `admin-api/index.php` and `.user.ini` into the independently deployed panel document root and use the exported rewrite template.
