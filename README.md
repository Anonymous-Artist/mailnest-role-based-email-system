# ✉ MailNest
### Role-Based Email & Subscription Management System

---

## Overview

MailNest is aPHP application for managing users, email templates, bulk/manual email campaigns, and subscription billing reminders — built with **Core PHP**, **MySQL (PDO)**, **PHPMailer**, and session-based authentication.

---

## Tech Stack

| Layer        | Technology                          |
|-------------|--------------------------------------|
| Language    | PHP 8.0+ (no framework)              |
| Database    | MySQL 5.7+ / MariaDB 10+            |
| Mailer      | PHPMailer 6.x                        |
| Env Config  | vlucas/phpdotenv 5.x                 |
| Frontend    | Vanilla HTML/CSS/JS (fetch API)      |
| Auth        | Session-based, BCRYPT passwords      |

---

## Folder Structure

```
mailnest/
├── assets/
│   ├── css/app.css            # Main stylesheet
│   └── js/app.js              # Core JS (AJAX, toast, modal)
├── auth/
│   ├── login.php              # Login page
│   └── logout.php             # Logout handler
├── admin/
│   ├── partials/              # layout.php, layout_end.php
│   ├── dashboard.php          # Stats overview
│   ├── users.php              # User list + search
│   ├── add_user.php           # Create user
│   ├── edit_user.php          # Edit user
│   ├── delete_user.php        # Delete user (POST only)
│   ├── templates.php          # Template list
│   ├── add_template.php       # Create template
│   ├── edit_template.php      # Edit template
│   ├── delete_template.php    # Delete template
│   ├── send_email.php         # Email composer UI
│   ├── send_email_process.php # Email send processor
│   ├── preview_email.php      # AJAX preview endpoint
│   ├── logs.php               # Email log viewer
│   └── subscriptions.php      # View all subscriptions
├── user/
│   ├── partials/              # layout.php, layout_end.php
│   ├── dashboard.php          # User home
│   ├── subscriptions.php      # CRUD subscriptions
│   ├── profile.php            # Update profile/password
│   └── toggle_reminder.php    # AJAX reminder toggle
├── mail/
│   ├── mailer.php             # PHPMailer wrapper + logger
│   └── cron_reminder.php      # Scheduled reminder script
├── config/
│   ├── bootstrap.php          # App boot (dotenv + autoload)
│   ├── db.php                 # PDO singleton
│   └── auth.php               # Session/auth/CSRF helpers
├── vendor/                    # Composer packages (gitignored)
├── index.php                  # Entry point / router
├── .env                       # Environment config (gitignored)
├── .htaccess                  # Security rules
├── composer.json
└── schema.sql                 # Database schema
```

---

## Quick Start

### 1. Clone / Download

```bash
git clone https://github.com/yourname/mailnest.git
cd mailnest
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Create Database

```sql
mysql -u root -p
source schema.sql
```

Or import `schema.sql` through phpMyAdmin.

### 4. Configure Environment

Copy and edit the `.env` file:

```bash
cp .env.example .env
nano .env
```

Key settings:

```ini
DB_HOST=127.0.0.1
DB_NAME=mailnest
DB_USER=root
DB_PASS=your_password

SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=you@gmail.com
SMTP_PASSWORD=your-app-password   # Gmail App Password (NOT your login password)
SMTP_FROM_EMAIL=you@gmail.com
SMTP_FROM_NAME=MailNest
```

> **Gmail users:** Enable 2FA → Generate an App Password at  
> https://myaccount.google.com/apppasswords

### 5. Set Web Root

Point Apache/Nginx to the `mailnest/` folder, or access it via:  
`http://localhost/mailnest/`

Update `APP_URL` in `.env` to match.

### 6. First Login

```
Email:    admin@mailnest.local
Password: password
```

> **Change this immediately** via Admin → Manage Users → Edit.

---

## Features by Role

### Admin
- Dashboard with sent/failed email stats
- Create, edit, delete users
- Search users by name or email
- Create HTML email templates with placeholders
- Send emails to: single user / all users / active subscribers
- Preview emails before sending
- View paginated email logs filtered by status
- View all subscriptions across users

### User
- View active subscriptions with upcoming billing alerts
- Add, edit, delete subscriptions (CRUD)
- Toggle billing reminder emails on/off
- Update profile name, email, and password

---

## Email Placeholders

Use these in template subject and body:

| Placeholder        | Replaced With                      |
|--------------------|------------------------------------|
| `{{name}}`         | Recipient's full name              |
| `{{email}}`        | Recipient's email address          |
| `{{date}}`         | Today's date (e.g., February 16, 2026) |
| `{{subscription}}` | User's first active service name   |

---

## Cron Job Setup

Send daily billing reminders at 9:00 AM:

```bash
# Open crontab
crontab -e

# Add this line
0 9 * * * /usr/bin/php /var/www/html/mailnest/mail/cron_reminder.php >> /var/log/mailnest_reminders.log 2>&1
```

The script:
- Finds all active subscriptions billing within the next 3 days
- Only sends to users with `reminder_enabled = 1`
- Uses any template with "reminder" in the title (or the first available template)
- Logs all activity to stdout

---

## Security Checklist

- [x] PDO prepared statements on all queries (no raw SQL)
- [x] BCRYPT password hashing with cost=12
- [x] CSRF tokens on all forms and AJAX POST actions
- [x] Session regeneration on login (prevents fixation)
- [x] `htmlspecialchars()` on all output (`e()` helper)
- [x] `.htaccess` blocks direct access to `/config`, `/mail`, `/vendor`, `.env`
- [x] Role middleware on every page (`requireAdmin()`, `requireUser()`)
- [x] HTTP-only, SameSite session cookies
- [x] `.env` never exposed (denied in `.htaccess`)

---

## Bulk Send Limits

Configure in `.env`:

```ini
BULK_LIMIT=50
```

- Default: 50 recipients per execution
- `set_time_limit(0)` is set during bulk sends
- Failed emails are individually logged without stopping the batch

---

## Requirements

- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Composer
- Apache with `mod_rewrite` enabled (or Nginx equivalent)

---

## License

MIT — free for personal and commercial use.
