# Loan Management System - Security & Dashboard Enhancements

## Overview

This update includes comprehensive security improvements and a modern analytics dashboard for your Loan Management System.

## What's Included

### 🔒 Security Enhancements

| Feature | Description |
|---------|-------------|
| **Password Hashing** | Bcrypt with cost factor 12 (automatic migration from plain text) |
| **Prepared Statements** | All SQL queries use prepared statements to prevent injection |
| **CSRF Protection** | Token-based protection for all forms |
| **Session Security** | Secure session configuration, regeneration, and timeout |
| **Rate Limiting** | Login attempt limiting (5 attempts per 15 minutes) |
| **Input Sanitization** | All user inputs are sanitized and validated |
| **Audit Logging** | All security events are logged to file and database |

### 📊 Dashboard Analytics

| Feature | Description |
|---------|-------------|
| **KPI Cards** | Today's payments, monthly totals, receivables, pending approvals |
| **Payment Trends** | 12-month payment chart with Chart.js |
| **Loan Distribution** | Pie chart showing loan status breakdown |
| **Recent Activity** | Latest loans and their status |
| **Upcoming Payments** | Next 7 days payment schedule |
| **Overdue Alerts** | Visual alerts for overdue loans |

---

## Installation Steps

### Step 1: Backup Your Database

```sql
-- Create a backup before making any changes!
mysqldump -u root -p loan_db > loan_db_backup.sql
```

### Step 2: Run Database Migration

1. Open phpMyAdmin or MySQL command line
2. Select your `loan_db` database
3. Import the migration file:

```sql
source database/security_migration.sql
```

Or paste the contents of `database/security_migration.sql` into phpMyAdmin SQL tab.

### Step 3: Create Required Directories

```bash
mkdir -p logs
chmod 755 logs
```

### Step 4: Migrate Existing Passwords

**Option A: Via Browser**
1. Edit `password_migration.php` and remove the die() line
2. Access `http://localhost/Loan/password_migration.php`
3. Check the output for any errors

**Option B: Via Command Line**
```bash
cd /path/to/Loan
php password_migration.php
```

### Step 5: Update Your Files

Replace these files with the secure versions:

| Original File | Secure Version | Action |
|--------------|----------------|--------|
| `admin_class.php` | `admin_class_secure.php` | Rename secure to original |
| `ajax.php` | `ajax_secure.php` | Rename secure to original |
| `home.php` | `home_enhanced.php` | Rename enhanced to original |
| (new) | `includes/security.php` | Keep as is |
| (new) | `includes/database.php` | Keep as is |
| (new) | `change_password.php` | Keep as is |

Or create backups and replace:

```bash
# Backup originals
cp admin_class.php admin_class_original.php
cp ajax.php ajax_original.php
cp home.php home_original.php

# Use secure versions
cp admin_class_secure.php admin_class.php
cp ajax_secure.php ajax.php
cp home_enhanced.php home.php
```

### Step 6: Add Change Password to Navigation

Edit `navbar.php` and add this menu item:

```php
<li class="nav-item">
    <a class="nav-link" href="index.php?page=change_password">
        <i class="nav-icon fa fa-key"></i>
        <p>Change Password</p>
    </a>
</li>
```

### Step 7: Test the System

1. **Login Test**: Try logging in with `admin` / `admin123`
2. **Dashboard Test**: Check that charts load properly
3. **Password Change**: Test changing your password
4. **Security Test**: Check `logs/security.log` for entries

---

## New Files Structure

```
Loan/
├── includes/
│   ├── security.php      # Security helper class
│   └── database.php      # Secure database class
├── database/
│   └── security_migration.sql  # Database updates
├── logs/
│   ├── security.log      # Security event log
│   └── migration.log     # Migration log
├── admin_class_secure.php    # Secure action class
├── ajax_secure.php           # Secure AJAX handler
├── home_enhanced.php         # Enhanced dashboard
├── change_password.php       # Password change page
└── password_migration.php    # Password upgrade script
```

---

## Security Best Practices

### For Administrators

1. **Change default password immediately** after installation
2. **Enable HTTPS** in production
3. **Regularly review** `logs/security.log` for suspicious activity
4. **Limit database user privileges** (don't use root in production)
5. **Keep backups** of the database

### Session Configuration

The system now uses secure session settings:
- `session.use_strict_mode = 1`
- `session.use_only_cookies = 1`
- `session.cookie_httponly = 1`
- Session ID regeneration every 5 minutes

### Password Requirements

Default password requirements:
- Minimum 8 characters
- At least one number
- At least one letter

You can customize these in `security_settings` table.

---

## API Changes

### Login Response

The login function now returns:
- `1` = Success
- `3` = Invalid credentials
- JSON with rate limit message if blocked

### CSRF Protection

All POST requests should include a CSRF token:

```php
<?php echo Security::csrfField(); ?>
```

Or in JavaScript:
```javascript
data: {
    csrf_token: '<?php echo csrf_token(); ?>',
    // other data
}
```

---

## Troubleshooting

### "Class 'Security' not found"
Make sure `includes/security.php` exists and is included at the top of files.

### Charts not loading
Ensure you have internet access for Chart.js CDN, or download and host locally.

### Passwords not working after migration
Check `logs/migration.log` for errors. Users with MD5 passwords need to reset manually.

### Rate limit triggered
Wait 15 minutes or clear the session:
```php
Security::clearRateLimit('login_' . $_SERVER['REMOTE_ADDR']);
```

---

## Support

For issues or questions:
1. Check the logs in `/logs/` directory
2. Verify database migration completed successfully
3. Ensure all new files are in place

---

## Changelog

### Version 2.0.0 (Security Update)
- Added bcrypt password hashing
- Implemented prepared statements throughout
- Added CSRF token protection
- Created audit logging system
- Enhanced dashboard with Chart.js analytics
- Added rate limiting for login attempts
- Secure session handling
- Input sanitization helpers
- Password change functionality
