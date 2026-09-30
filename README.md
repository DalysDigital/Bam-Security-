# BAM Security Website — Version 1

**Bouncer Association of Meghalaya (BAM)**

A complete PHP/MySQL website and administration system for BAM, including the public security website, member management, digital ID cards, public member verification, events, invoices, letters, frontend editing, audit logs, administrator management and the admin PWA.

---

## 1. Version 1 includes

### Public website
- Professional black/red BAM security design.
- Responsive desktop, tablet and mobile layout.
- Initial home-page skeleton/loading screen only during the first page render.
- Fast navigation without the skeleton appearing again when internal links are clicked.
- About, Services, Capabilities, Gallery and Contact sections.
- **Verify Member** public link.
- Member verification page connected to the MySQL member database.
- SEO metadata, canonical URL and Open Graph metadata.
- Day/night visual theme support.

### Admin system
- Secure PHP/MySQL administration panel.
- Dashboard.
- Member management.
- Member photo uploads.
- Membership status and expiry handling.
- Digital ID generation and QR verification.
- Events.
- Invoices.
- Letters and letterheads.
- Frontend website editor.
- ID field settings.
- Site settings.
- Administrator management.
- Audit logs.
- Admin day/night theme.
- High-contrast dark theme for readable text, labels, fields, tables and buttons.
- Password Show/Hide controls.
- Administrator passwords require a minimum of **4 characters**.
- Admin PWA under `/admin/`.

---

## 2. Server requirements

Recommended:

- PHP **8.0 or newer**.
- MySQL 5.7+ / MySQL 8+ or compatible MariaDB.
- PDO MySQL enabled.
- HTTPS/SSL enabled.
- PHP file uploads enabled.
- PHP `fileinfo` extension recommended for secure image MIME detection.
- Writable `uploads/` and `logs/` directories.

For the admin PWA, HTTPS is strongly recommended and is required by browsers for normal service-worker/PWA behaviour outside localhost.

---

## 3. Fresh installation on a new domain/server

### Step 1 — Create the MySQL database

Create a new MySQL database from your hosting control panel.

Record:

- Database host
- Database port, normally `3306`
- Database name
- Database username
- Database password

**Do not manually import the SQL first.** The Version 1 installer creates the tables for you.

### Step 2 — Upload the website

Upload the **contents** of this package to the document root of the new domain/subdomain.

Example:

```text
public_html/
├── install.php
├── index.php
├── home.html
├── admin/
├── assets/
├── config/
├── database/
├── member/
├── uploads/
└── ...
```

### Step 3 — Open the installer

Open:

```text
https://YOUR-DOMAIN.COM/install.php
```

The installer asks for:

1. Website URL
2. MySQL host
3. MySQL port
4. Database name
5. Database username
6. Database password
7. First administrator name
8. Administrator username
9. Administrator email
10. Administrator password

The administrator password can be **4 characters or longer**.

### Step 4 — Install

Click **Install BAM Version 1**.

The installer will:

- Test the MySQL connection.
- Create the BAM database tables.
- Create the site settings table.
- Create the frontend content/gallery table.
- Create supporting events, invoice, letters and audit tables.
- Create the first administrator using the password you entered.
- Write the database credentials into `config/config.php`.
- Set the configured domain as the application's URL.
- Update the public canonical/OG URLs.
- Create `uploads/` and `logs/` if needed.
- Create an `install.lock` file.
- Remove the old setup/debug files that are not required for a fresh Version 1 installation.

### Step 5 — Delete the installer

After confirming the website works, **delete `install.php` from the server**.

The installer also creates `install.lock` so it cannot be run again accidentally.

---

## 4. URLs after installation

Replace `YOUR-DOMAIN.COM` with your actual domain.

### Public website

```text
https://YOUR-DOMAIN.COM/
```

### Admin login

```text
https://YOUR-DOMAIN.COM/admin/login.php
```

### Member verification

```text
https://YOUR-DOMAIN.COM/verify.php
```

### Member login

```text
https://YOUR-DOMAIN.COM/member/login.php
```

### Digital ID

```text
https://YOUR-DOMAIN.COM/id.php?id=BAM-0001
```

The exact member ID depends on the member records in the database.

---

## 5. Administrator login

The administrator account is created during installation.

Login uses the **administrator email address** entered in the installer, not the username.

Password minimum:

```text
4 characters
```

The admin login and administrator creation screens include a Show/Hide password control.

---

## 6. Moving an existing BAM installation

Version 1 is designed for a **fresh domain/server installation**.

For an existing production BAM installation with members, invoices, letters, settings or administrators, do **not** overwrite the production database with a fresh schema.

Instead:

1. Back up the existing database.
2. Back up `uploads/`.
3. Copy the existing `config/config.php` credentials into the new server or configure the new server for the same database.
4. Move the existing database and uploads as a migration.
5. Update `APP_URL` to the new domain.
6. Test member verification, admin login, IDs, invoices and letters before switching DNS.

---

## 7. Security checklist

After installation:

- Enable HTTPS.
- Delete `install.php`.
- Never publish `config/config.php`.
- Never publish database credentials.
- Use a strong database password.
- Use a unique administrator email/password.
- Keep the administrator password at least 4 characters; a longer password is recommended.
- Keep `uploads/` protected from executing uploaded scripts.
- Keep regular database backups.
- Keep PHP and the hosting server updated.
- Do not upload old deployment READMEs or credentials from previous versions.

---

## 8. Important configuration file

After installation the main configuration is:

```text
config/config.php
```

It contains:

- Database host
- Database port
- Database name
- Database username
- Database password
- Application URL

Do not share this file publicly.

---

## 9. Database

The fresh-install schema used by the installer is:

```text
database/schema_bamId.sql
```

The installer creates the schema automatically, so manual SQL import is normally unnecessary.

The application also creates supporting tables safely when required.

---

## 10. Admin PWA

The admin application is scoped to:

```text
/admin/
```

It has its own:

- Manifest
- Service worker
- App icons
- Offline page
- Install handling

The public website does not need to be installed as the admin application.

If Chrome does not show a native install prompt, this is controlled by browser PWA eligibility and engagement rules; the website cannot force Chrome to display that native prompt.

---

## 11. Member verification

Each member can have an official digital ID with a QR code.

The QR code points to the public verification page using the member's verification/member ID information.

The public verification page reads the member record from the database and displays the configured verification information.

Use:

```text
https://YOUR-DOMAIN.COM/verify.php
```

---

## 12. Recommended deployment order

```text
1. Create domain/subdomain
        ↓
2. Enable HTTPS
        ↓
3. Create MySQL database
        ↓
4. Upload Version 1 files
        ↓
5. Open /install.php
        ↓
6. Enter domain + MySQL + admin details
        ↓
7. Install
        ↓
8. Test public website
        ↓
9. Test Member Verification
        ↓
10. Test Admin Login
        ↓
11. Test member/ID/invoice/letter functions
        ↓
12. Delete install.php
        ↓
13. Take a database backup
```

---

## 13. Version identity

**Project:** Bouncer Association of Meghalaya Security Website

**Release:** Version 1

**Architecture:** PHP + MySQL + HTML5 + CSS3 + JavaScript

**Admin:** PHP/MySQL secure administration panel + admin PWA

**Public verification:** PHP/MySQL member verification

**Database:** MySQL-compatible

---

## 14. Important note about this release

This Version 1 package is intended to be the clean baseline release for deployment to a **new domain/server**. It does not contain the previous production database credentials.

The installer creates the connection configuration from the values entered during installation.
