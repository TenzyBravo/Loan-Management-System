# Brian Investments - Production Deployment Checklist

## Pre-Deployment Steps

### 1. Database Setup
- [ ] Create MySQL database on production server
- [ ] Create a dedicated database user (NOT root)
- [ ] Grant only necessary permissions (SELECT, INSERT, UPDATE, DELETE)
- [ ] Import `database/loan_db.sql`

### 2. Configuration
- [ ] Copy `config.production.php` to `config.php`
- [ ] Update database credentials in `config.php`:
  - `DB_HOST` - your database host
  - `DB_USER` - database username (not root!)
  - `DB_PASS` - strong password
  - `DB_NAME` - database name
- [ ] Update `BASE_URL` to your domain with HTTPS
- [ ] Verify `ENVIRONMENT` is set to `'production'`

### 3. Password Migration
- [ ] Run `database/upgrade_passwords.php` once from localhost
- [ ] DELETE `database/upgrade_passwords.php` after running
- [ ] Change default admin password to a strong one

### 4. File Permissions (Linux/cPanel)
```bash
# Directories: 755
find . -type d -exec chmod 755 {} \;

# Files: 644
find . -type f -exec chmod 644 {} \;

# Config file: more restrictive
chmod 640 config.php

# Uploads directory: writable
chmod 755 uploads/
```

### 5. SSL/HTTPS Setup
- [ ] Install SSL certificate (Let's Encrypt is free)
- [ ] Uncomment HTTPS redirect in `.htaccess`:
  ```apache
  RewriteEngine On
  RewriteCond %{HTTPS} off
  RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
  ```
- [ ] Uncomment HSTS header in `.htaccess`

### 6. Security Verification
- [ ] Verify `display_errors` is OFF in production
- [ ] Test that config files are not accessible from web
- [ ] Verify CSRF protection is working
- [ ] Test rate limiting on login page
- [ ] Ensure `/logs/` directory is not web-accessible

### 7. Create Required Directories
```bash
mkdir -p logs
mkdir -p uploads/documents
chmod 755 logs uploads uploads/documents
```

### 8. Protect Sensitive Files
Add to `.htaccess` if not already present:
```apache
<FilesMatch "^(config\.php|\.env)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>
```

---

## Post-Deployment Testing

### Authentication
- [ ] Admin login works
- [ ] Customer login works
- [ ] Logout works
- [ ] Password change works
- [ ] Rate limiting blocks after 5 failed attempts

### Core Features
- [ ] Create new borrower
- [ ] Create new loan application
- [ ] Approve loan
- [ ] Record payment
- [ ] Mark loan as paid
- [ ] Apply overdue penalty

### Admin Features
- [ ] Dashboard loads correctly
- [ ] Reports display data
- [ ] Backup/Export downloads files
- [ ] CSV exports work correctly

### Customer Portal
- [ ] Customer registration
- [ ] Customer dashboard displays correctly
- [ ] Loan application submission
- [ ] Document upload

---

## Hosting Recommendations

### Minimum Requirements
- PHP 7.4+ (PHP 8.0+ recommended)
- MySQL 5.7+ or MariaDB 10.3+
- mod_rewrite enabled
- OpenSSL for HTTPS

### Recommended Hosting Providers (Zambia-friendly)
1. **Shared Hosting** (Budget-friendly)
   - Bluehost
   - Namecheap
   - HostGator

2. **VPS** (More control)
   - DigitalOcean
   - Vultr
   - Linode

3. **Local Zambian Hosts**
   - Zambia.co.zm
   - ZamHost

---

## Maintenance Tasks

### Daily
- [ ] Check error logs (`logs/php_errors.log`)
- [ ] Monitor failed login attempts (`logs/security.log`)

### Weekly
- [ ] Backup database
- [ ] Review overdue loans

### Monthly
- [ ] Update PHP and MySQL if needed
- [ ] Review user accounts
- [ ] Check disk space usage

---

## Emergency Contacts

- **Developer**: [Your contact]
- **Hosting Support**: [Provider contact]
- **SSL Certificate**: [Provider/Let's Encrypt]

---

## Files to DELETE Before Production

- [ ] `database/upgrade_passwords.php` (after running)
- [ ] `database/cleanup_orphans.php`
- [ ] `database/migrate_v2.php`
- [ ] Any `.backup` files
- [ ] Any test files

---

## Quick Commands

### Backup Database
```bash
mysqldump -u [user] -p [database] > backup_$(date +%Y%m%d).sql
```

### Restore Database
```bash
mysql -u [user] -p [database] < backup_file.sql
```

### Check PHP Version
```bash
php -v
```

### Test Database Connection
```php
<?php
$conn = new mysqli('localhost', 'user', 'pass', 'db');
echo $conn->connect_error ? 'Failed' : 'Success';
?>
```
