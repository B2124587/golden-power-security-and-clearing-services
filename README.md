# Golden Power Security — Website

A PHP + MySQL website for Golden Power Security (GPS): a public marketing
site plus a fully independent, password-protected admin panel.

## Structure

```
/                     Public site (index.php, services.php, gallery.php, contact.php)
/admin/               Admin panel — separate login, separate session, own URL
/includes/            Shared PHP: config, DB connection, helper functions, header/footer
/assets/css/          style.css (public) + admin.css (admin panel)
/assets/js/           main.js (public) + admin.js (admin panel)
/uploads/              Uploaded logo, hero image, service-related images, videos
/sql/schema.sql       Full database schema + starter content
```

The **public site** and the **admin panel** are independent:
- Different entry points (`/index.php` vs `/admin/login.php`).
- Different session cookies (`PHPSESSID` for the public site, `gps_admin_session` for admin), so being logged into one never affects the other.
- Every `/admin/*` page (other than `login.php`) requires an authenticated session — visiting it directly redirects to the login form.

## 1. Setup

1. Create a MySQL database and import the schema:
   ```
   mysql -u root -p < sql/schema.sql
   ```
   This creates the `gps_website` database, all tables, and seeds it with
   the company's real services, offices, escort countries, and two starter
   quotes (matching the company profile documents).

2. Edit `includes/config.php` with your database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'gps_website');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   ```
   Set `APP_DEBUG` to `0` before going live.

3. Point your web server's document root at this folder (Apache/Nginx +
   PHP 8+). Make sure `uploads/` is writable by the web server
   (`chmod 755 uploads` and its subfolders).

4. To enable the quote calculator, import `sql/migrations/001_quote_foundation.sql`
   after the base schema. Then use **Admin → Quote Pricing** to adjust the
   configurable ZMW estimate values.

5. Import `sql/migrations/002_access_control_audit.sql` to enable staff roles,
   permissions, active-account checks, and administrator audit logging.

6. Import `sql/migrations/003_operations_foundation.sql` to enable the protected
   **Admin → Operations** workspace for employees, security sites, shifts,
   attendance, incidents, and cleaning jobs.

## 2. Default admin login

- URL: `/admin/login.php`
- Username: `admin`
- Password: `GoldenPower2026!`

**Change this password immediately after first login** using the
**Change Password** screen in the admin panel sidebar.

> ⚠️ **Before going live, you must:**
> 1. Log in with the default credentials above and change the password immediately.
> 2. Set `APP_DEBUG` to `0` in `includes/config.php` — leaving it at `1` in
>    production will leak database errors and stack traces to visitors.
> 3. Create a dedicated, least-privilege MySQL user for `DB_USER`/`DB_PASS`
>    instead of using `root` with a blank password.

## 3. What the admin can do

- **Services** — add, edit, delete, reorder, and publish/hide services.
- **Daily Quotes** — add quotes and set which one is live on the homepage.
- **Contact & Offices** — edit regional office addresses, phone numbers and emails.
- **Site Settings** — upload the company logo, upload the homepage hero background image, edit the site name/tagline/about text.
- **Video Gallery** — add YouTube/Vimeo links or upload MP4 files, publish/hide, delete.
- **Daily Achievements** — publish convoy updates with a starting point, destination, journey summary, and multiple attached journey photos. Posts can be published or hidden from the public home page.
- **Inquiries** — view every contact-form submission, search, update status (Pending/Contacted/Closed), export to CSV, delete.

## 4. Notes

- Every public form (contact/inquiry) is CSRF-protected and uses prepared
  statements (PDO) — no raw SQL concatenation anywhere.
- Passwords are hashed with `password_hash()` / verified with `password_verify()`.
- `uploads/.htaccess` disables PHP execution inside the uploads folder as a
  defense-in-depth measure against malicious file uploads.
- The homepage hero background, logo, and daily quote are all pulled live
  from the database — editing them in the admin panel updates the public
  site immediately, no redeploy needed.
