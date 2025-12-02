# SmartShop - InfinityFree Hosting Setup Guide

## Step 1: Database Configuration

1. **Login to your InfinityFree control panel**
2. **Create a MySQL database:**
   - Go to "MySQL Databases"
   - Create a new database (note the database name, username, and password)
   - The format will be: `if0_XXXXXXX_smartshop`

3. **Update database credentials in `config/database.php`:**
   ```php
   // Replace these values with your actual InfinityFree database credentials:
   $this->host = 'sql200.infinityfree.com'; // Your actual host
   $this->username = 'if0_37123456'; // Your actual username  
   $this->password = 'your_password'; // Your actual password
   $this->database = 'if0_37123456_smartshop'; // Your actual database name
   ```

## Step 2: Upload Files

1. **Upload all files to your InfinityFree file manager or via FTP**
2. **Make sure the file structure is:**
   ```
   htdocs/
   ├── app/
   ├── config/
   ├── public/
   ├── uploads/
   ├── .htaccess
   ├── index.php
   └── other files...
   ```

## Step 3: Database Setup

1. **Import the database schema:**
   - Go to phpMyAdmin in your InfinityFree control panel
   - Select your database
   - Import the SQL file from `database/create_tables.sql`

2. **Or run the setup script:**
   - Visit: `https://yourdomain.infinityfreeapp.com/database/setup_database.php`

## Step 4: File Permissions

1. **Set proper permissions for uploads directory:**
   - uploads/ folder should be writable (755 or 777)
   - uploads/profiles/ should be writable

## Step 5: Test the Application

1. **Visit your site:** `https://yourdomain.infinityfreeapp.com`
2. **Test login:** `https://yourdomain.infinityfreeapp.com/app/views/auth/login.php`

## Common Issues and Solutions

### Issue 1: Database Connection Failed
- **Solution:** Double-check database credentials in `config/database.php`
- Make sure the database exists and is accessible

### Issue 2: File Not Found Errors
- **Solution:** Check file paths and ensure all files are uploaded correctly
- Verify .htaccess file is present

### Issue 3: CSS/JS Not Loading
- **Solution:** Check file paths in HTML files
- Ensure public/ directory is accessible

### Issue 4: Session Issues
- **Solution:** Make sure session.save_path is writable
- Check PHP version compatibility (PHP 7.4+ required)

## Security Notes

1. **Remove or secure sensitive files:**
   - Delete `database/` folder after setup (or restrict access)
   - Secure `config/` folder with .htaccess

2. **Change default admin credentials immediately after setup**

3. **Enable HTTPS if available**

## Support

If you encounter issues:
1. Check InfinityFree's error logs
2. Verify PHP version (7.4+ required)
3. Ensure all required PHP extensions are available
4. Check file permissions

## Important Files Modified for InfinityFree:

- `config/database.php` - Environment-aware database configuration
- `config/config.php` - New configuration file for environment detection
- `.htaccess` - URL rewriting and security rules
- `app/controllers/AuthController.php` - Updated to use Database class

Remember to update the database credentials in `config/database.php` with your actual InfinityFree database details!