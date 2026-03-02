# Hostinger Deployment Guide

## Note Application - Hostinger Installation

This guide will help you deploy the Note application on Hostinger's shared hosting.

## Prerequisites

- Hostinger hosting account (Shared, Business, or Cloud)
- FTP/SFTP access (or File Manager in Hostinger Control Panel)
- MySQL database credentials
- PHP 7.4+ (usually available by default on Hostinger)
- Domain name pointed to your Hostinger account

## Step 1: Upload Files to public_html

1. Connect to your Hostinger account via FTP or File Manager
2. Upload all files to the `public_html` folder:

```
public_html/
├── index.html              (Landing page)
├── robots.txt              (SEO)
├── sitemap.xml             (SEO)
├── .htaccess               (URL rewriting)
├── app/
│   └── index.php           (Main app)
├── assets/
│   ├── style.css
│   ├── app.js
│   └── ...
├── api/
│   ├── api.php             (API endpoints)
│   ├── config.php          (Configuration)
│   ├── database.php        (Database schema)
│   └── ...
├── logs/                   (Create this folder)
├── tmp/                    (Create this folder)
└── uploads/                (Create this folder)
```

## Step 2: Create Required Directories

Create these folders in `public_html`:

```bash
# Via File Manager or FTP
mkdir logs/
mkdir logs/php-errors.log
mkdir tmp/
mkdir tmp/sessions/
mkdir uploads/
mkdir uploads/temp/
```

Set permissions:
```bash
chmod 755 logs/
chmod 755 tmp/
chmod 755 tmp/sessions/
chmod 755 uploads/
chmod 755 uploads/temp/
```

## Step 3: Configure Database

1. Go to Hostinger Control Panel
2. Navigate to **Databases > MySQL**
3. Create new database:
   - **Database Name**: `u757840095_note`
   - **Database User**: `u757840095_note2`
   - **Password**: `MB?EM6aTa7&M` (use your password)

4. Note the database host (usually `localhost`)

## Step 4: Update Configuration

1. Edit `/api/config.php`:
```php
// Update APP_URL to your domain
define('APP_URL', 'https://note.websweos.com');

// Database settings (usually localhost on Hostinger)
define('DB_HOST', 'localhost');
define('DB_USER', 'u757840095_note2');
define('DB_PASS', 'MB?EM6aTa7&M');
define('DB_NAME', 'u757840095_note');

// Production environment
define('ENVIRONMENT', 'production');
```

## Step 5: Initialize Database

1. Upload `api/database.php` to your server
2. Visit: `https://note.websweos.com/api/database.php` in your browser
3. Check for success message
4. Delete this file after initialization (for security)

## Step 6: Verify .htaccess

Ensure `.htaccess` is in `public_html` root with proper rewriting rules:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ /app/index.php?request=$1 [QSA,L]
</IfModule>
```

## Step 7: Enable HTTPS

1. Hostinger provides free SSL certificates
2. Enable SSL in Control Panel:
   - Go to **Domains > Manage > SSL**
   - Click **Activate SSL**

3. Update `app/index.php` header:
```html
<meta name="canonical" content="https://note.websweos.com">
```

## Step 8: Test the Application

1. Visit `https://note.websweos.com/` - Should see landing page
2. Click "Get Started" - Should redirect to app
3. Register new account
4. Create a test note
5. Check that notes save properly

## Step 9: Configure Email (Optional)

For user notifications, add to `api/config.php`:

```php
define('MAIL_FROM', 'noreply@note.websweos.com');
define('MAIL_HOST', 'your-mail-server');
define('MAIL_USERNAME', '');
define('MAIL_PASSWORD', '');
define('MAIL_PORT', 587);
```

## Step 10: Monitor Application

### Enable Error Logging

Errors are logged to `logs/php-errors.log`

Check via File Manager:
- `public_html/logs/php-errors.log` - PHP errors
- `public_html/logs/access.log` - Access logs (optional)

### Check Database Connection

Create a test file `test-db.php`:

```php
<?php
require_once 'api/config.php';
$conn = getDBConnection();
if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error;
} else {
    echo "Connected successfully!";
    $conn->close();
}
?>
```

Visit `https://note.websweos.com/test-db.php` then delete the file.

## Hostinger-Specific Tips

### 1. Session Configuration
If you get session errors, creates `/tmp/sessions/` with proper permissions:
```bash
chmod 777 /tmp/sessions/
```

### 2. PHP Version
Hostinger allows PHP version selection:
- Control Panel > Settings > PHP Version
- Recommended: PHP 8.0 or higher

### 3. Max Upload Size
To increase upload limits, add to `.htaccess`:
```apache
php_value upload_max_filesize 100M
php_value post_max_size 100M
```

### 4. Cron Jobs
For scheduled tasks, set up in Control Panel:
- **Advanced > Cron Jobs**
- Example: Run cleanup every day
```
0 0 * * * php /home/username/public_html/api/cleanup.php
```

### 5. Backups
Enable automatic backups in Hostinger:
- Control Panel > Backups
- Set to daily or weekly

### 6. Database Backups
Backup your database regularly:
- Control Panel > Databases > MySQL
- Click database > Backup

## Troubleshooting

### Issue: "Cannot connect to database"
- Check credentials in `api/config.php`
- Verify database was created
- Check database user permissions

### Issue: "500 Internal Server Error"
- Check `logs/php-errors.log`
- Verify `.htaccess` is uploaded
- Check PHP version compatibility

### Issue: "CSS/JS not loading"
- Verify file permissions (755)
- Check `.htaccess` allows static files
- Clear browser cache

### Issue: "Sessions not working"
- Check `/tmp/sessions/` folder exists
- Verify folder has write permissions (777)
- Check `session.save_path` in `api/config.php`

### Issue: "Pages not redirecting properly"
- Verify mod_rewrite is enabled
- Check `.htaccess` in public_html root
- Ensure all files are in correct folders

## File Structure Reference

```
/home/username/public_html/
├── index.html                 # Landing page
├── robots.txt                 # SEO robots
├── sitemap.xml               # SEO sitemap
├── .htaccess                 # URL rewrites
├── app/
│   └── index.php             # Main application
├── api/
│   ├── api.php              # API endpoints
│   ├── config.php           # Configuration
│   └── database.php         # Schema (delete after init)
├── assets/
│   ├── style.css            # Styles
│   ├── app.js               # JavaScript
│   └── favicon.ico
├── logs/
│   └── php-errors.log       # Error logs
├── tmp/
│   └── sessions/            # Session storage
└── uploads/
    └── temp/                # Temporary files
```

## Security Checklist

- [ ] SSL certificate enabled
- [ ] Database credentials updated
- [ ] `database.php` deleted
- [ ] Error logging configured
- [ ] `.htaccess` protection rules in place
- [ ] Sensitive files protected
- [ ] Backups automated
- [ ] Admin panel secured
- [ ] Passwords strong
- [ ] CORS configured for your domain

## Performance Optimization

1. **Enable Gzip Compression** - in `.htaccess`
2. **Browser Caching** - set in `.htaccess`
3. **Database Indexing** - already configured
4. **Asset Minification** - included in setup
5. **CDN** - use Hostinger's CDN for static files

## Support & Resources

- Hostinger Help Center: https://support.hostinger.com
- PHP Documentation: https://www.php.net/docs.php
- MySQL Documentation: https://dev.mysql.com/doc/

## Regular Maintenance

1. **Weekly**: Check `logs/php-errors.log`
2. **Monthly**: Backup database
3. **Quarterly**: Update passwords
4. **Yearly**: SSL certificate renewal (auto on Hostinger)

---

**Last Updated**: March 2, 2026
**Application Version**: 1.0.0
**Compatible with Hostinger**: Yes
