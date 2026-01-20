# Deployment Guide - Secure Loan Management System

## 🚀 Production Deployment Steps

### Step 1: Enable HTTPS Enforcement

For production deployment, add HTTPS enforcement to sensitive pages:

#### Admin Panel Pages
Add this line at the top of these files (after `<?php`):
```php
require_once __DIR__ . '/includes/https_enforce.php';
```

Files to update:
- `admin.php` ✅ (Already includes Security class)
- `login.php`
- `adminlogin.php`

#### Customer Portal Pages
Add to these files:
- `customer_login.php`
- `customer_register.php`
- `customer_dashboard.php`
- `customer_auth.php`
- `customer_apply_loan.php`
- `customer_profile.php`

**Example**:
```php
<?php
require_once __DIR__ . '/includes/https_enforce.php';  // Add this line
session_start();
// ... rest of code
?>
```

---

### Step 2: Configure Database

1. **Update database credentials** in `config/db_connect.php`:
```php
define('DB_HOST', 'your_production_host');
define('DB_USER', 'your_production_user');
define('DB_PASS', 'your_strong_password');
define('DB_NAME', 'loan_system');
```

2. **Set restrictive permissions**:
```bash
chmod 600 config/db_connect.php
```

---

### Step 3: Set File Permissions

```bash
# Application files (read-only)
chmod 644 *.php
chmod 755 *.php  # For executed scripts

# Upload directory (write access for web server)
chmod 755 assets/uploads/
chmod 755 assets/uploads/customer_documents/

# Config files (restricted)
chmod 600 config/db_connect.php
chmod 644 config/constants.php

# Security-sensitive directories
chmod 755 includes/
```

---

### Step 4: PHP Configuration

Update `php.ini` for production:

```ini
# Disable error display
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /path/to/php_errors.log

# Security settings
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off

# File upload limits
upload_max_filesize = 5M
post_max_size = 6M

# Session security
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = Strict
session.use_strict_mode = 1
```

---

### Step 5: Apache/Nginx Configuration

#### Apache (.htaccess)

Create/update `.htaccess` in the root directory:

```apache
# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Prevent directory listing
Options -Indexes

# Protect sensitive files
<FilesMatch "^(db_connect|config|\.env)">
    Order allow,deny
    Deny from all
</FilesMatch>

# Protect includes directory
<DirectoryMatch "^.*/includes/">
    Order deny,allow
    Deny from all
</DirectoryMatch>

# Security headers
<IfModule mod_headers.c>
    Header set X-XSS-Protection "1; mode=block"
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    Header set Permissions-Policy "geolocation=(), microphone=(), camera=()"
</IfModule>
```

#### Nginx

Add to server block:

```nginx
# Force HTTPS
if ($scheme != "https") {
    return 301 https://$server_name$request_uri;
}

# Deny access to sensitive files
location ~ ^/(db_connect|config|\.env) {
    deny all;
}

location /includes/ {
    deny all;
}

# Security headers
add_header X-XSS-Protection "1; mode=block";
add_header X-Content-Type-Options "nosniff";
add_header X-Frame-Options "SAMEORIGIN";
add_header Referrer-Policy "strict-origin-when-cross-origin";
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
```

---

### Step 6: SSL Certificate Installation

#### Option 1: Let's Encrypt (Free)

```bash
# Install Certbot
sudo apt-get update
sudo apt-get install certbot python3-certbot-apache

# Obtain certificate
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Auto-renewal (already set up by certbot)
sudo certbot renew --dry-run
```

#### Option 2: Commercial SSL

1. Purchase SSL certificate from provider
2. Generate CSR (Certificate Signing Request)
3. Install certificate files:
   - `certificate.crt`
   - `private.key`
   - `ca_bundle.crt`

---

### Step 7: Verify Security Settings

Run these checks after deployment:

```bash
# 1. Check file permissions
ls -la config/
ls -la includes/
ls -la assets/uploads/

# 2. Test HTTPS redirect
curl -I http://yourdomain.com
# Should return 301 redirect to HTTPS

# 3. Verify SSL certificate
openssl s_client -connect yourdomain.com:443 -servername yourdomain.com

# 4. Check security headers
curl -I https://yourdomain.com
```

---

### Step 8: Database Security

```sql
-- Create dedicated database user with minimal privileges
CREATE USER 'loan_app'@'localhost' IDENTIFIED BY 'strong_random_password';

-- Grant only necessary permissions
GRANT SELECT, INSERT, UPDATE, DELETE ON loan_system.* TO 'loan_app'@'localhost';

-- Revoke dangerous privileges
REVOKE FILE, PROCESS, SUPER ON *.* FROM 'loan_app'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;
```

---

### Step 9: Backup Strategy

#### Automated Daily Backups

Create backup script (`/backup/daily_backup.sh`):

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/loan_system"
DB_NAME="loan_system"
DB_USER="backup_user"
DB_PASS="backup_password"

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > "$BACKUP_DIR/db_$DATE.sql.gz"

# Files backup
tar -czf "$BACKUP_DIR/files_$DATE.tar.gz" /var/www/html/loan/assets/uploads

# Keep only last 30 days
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete

echo "Backup completed: $DATE"
```

Add to crontab:
```bash
crontab -e
# Daily backup at 2 AM
0 2 * * * /backup/daily_backup.sh >> /var/log/loan_backup.log 2>&1
```

---

### Step 10: Monitoring & Logging

#### Enable Application Logging

Create log directory:
```bash
mkdir -p logs
chmod 755 logs
touch logs/security.log
chmod 644 logs/security.log
```

Update `includes/security.php` to use file logging (already configured).

#### Monitor Failed Logins

```sql
-- Check failed login attempts
SELECT * FROM audit_log
WHERE event_type = 'login_failed'
AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
ORDER BY created_at DESC;

-- Check for suspicious activity
SELECT ip_address, COUNT(*) as attempts
FROM audit_log
WHERE event_type = 'login_failed'
AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
GROUP BY ip_address
HAVING attempts > 10;
```

---

## 🔍 Post-Deployment Testing

### Security Tests

1. **LFI Test** (Should fail):
```
https://yourdomain.com/admin.php?page=../../../etc/passwd
Expected: Redirects to home page
```

2. **CSRF Test** (Should fail):
```bash
# Try to submit form without CSRF token
curl -X POST https://yourdomain.com/ajax.php?action=save_user \
  -d "name=Test&username=test"
Expected: 403 Forbidden with "Invalid CSRF token" message
```

3. **Cookie Security Test**:
```bash
# Check cookie flags
curl -I https://yourdomain.com/customer_auth.php
# Look for: HttpOnly; Secure; SameSite=Strict
```

4. **HTTPS Enforcement**:
```bash
curl -I http://yourdomain.com
# Should return: 301 Moved Permanently to HTTPS
```

5. **File Upload Test**:
```bash
# Try uploading PHP file disguised as image
# Should be rejected with MIME type error
```

---

## 🛡️ Firewall Configuration

### UFW (Ubuntu)

```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 22/tcp  # SSH
sudo ufw enable
```

### Fail2Ban (Brute Force Protection)

```bash
sudo apt-get install fail2ban

# Create jail for application
sudo nano /etc/fail2ban/jail.local
```

Add:
```ini
[loan-system]
enabled = true
filter = loan-system
logpath = /var/www/html/loan/logs/security.log
maxretry = 5
bantime = 3600
findtime = 600
```

---

## 📊 Performance Optimization

### Enable OpCache

In `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
```

### Database Indexing

```sql
-- Add indexes for better performance
CREATE INDEX idx_borrower_id ON loan_list(borrower_id);
CREATE INDEX idx_loan_id ON payments(loan_id);
CREATE INDEX idx_status ON loan_list(status);
CREATE INDEX idx_date_created ON loan_list(date_created);
```

---

## 🔄 Rollback Plan

If issues occur after deployment:

1. **Restore previous version**:
```bash
git checkout previous-stable-tag
```

2. **Restore database**:
```bash
mysql -u root -p loan_system < backup_YYYYMMDD.sql
```

3. **Check error logs**:
```bash
tail -f /var/log/apache2/error.log
tail -f logs/security.log
```

---

## ✅ Final Checklist

Before going live:

- [ ] HTTPS certificate installed and valid
- [ ] All database credentials updated
- [ ] File permissions set correctly
- [ ] HTTPS enforcement enabled
- [ ] PHP error display disabled
- [ ] Security headers configured
- [ ] Backups automated
- [ ] Monitoring in place
- [ ] All security tests passed
- [ ] Admin credentials changed from defaults
- [ ] Test customer registration flow
- [ ] Test loan application flow
- [ ] Test payment recording
- [ ] Verify email notifications work
- [ ] Load testing completed

---

## 🆘 Troubleshooting

### "Invalid CSRF token" errors

**Solution**: Clear browser cache and ensure `$_SESSION['csrf_token']` is being generated.

### File upload fails

**Solution**: Check directory permissions (`chmod 755 assets/uploads/customer_documents/`)

### Redirect loop on HTTPS

**Solution**: Verify web server SSL configuration and check for conflicting redirects.

### Session lost on page refresh

**Solution**: Ensure cookies are set with correct domain and path.

---

## 📧 Support

For deployment assistance:
- Check PHP error logs: `/var/log/php_errors.log`
- Check application logs: `logs/security.log`
- Check web server logs: `/var/log/apache2/` or `/var/log/nginx/`

---

**Deployment completed successfully! Your loan management system is now secure and production-ready.**
