# Auto-Sync Setup for InfinityFree

## Method 1: Manual Sync (Easiest)

1. **Update FTP credentials in `sync.php`:**
   ```php
   $ftp_server = "files.infinityfree.net";
   $ftp_user = "if0_XXXXXXX"; // Your actual username
   $ftp_pass = "your_password"; // Your actual password
   ```

2. **Run sync manually:**
   - Double-click `quick-sync.bat`
   - Or run: `php sync.php`

## Method 2: File Watcher (Auto-sync)

1. **Install Node.js** (if not installed)
2. **Run:** `node watch.js`
3. **Make changes** - files auto-sync after 2 seconds

## Method 3: IDE Integration

### VS Code:
1. Install "SFTP" extension
2. Create `.vscode/sftp.json`:
```json
{
    "name": "InfinityFree",
    "host": "files.infinityfree.net",
    "protocol": "ftp",
    "port": 21,
    "username": "if0_XXXXXXX",
    "password": "your_password",
    "remotePath": "/htdocs/",
    "uploadOnSave": true
}
```

### FileZilla (Manual):
1. Set up site with your FTP credentials
2. Drag & drop changed files

## Quick Commands:

- **Sync now:** `quick-sync.bat`
- **Watch files:** `node watch.js`
- **Deploy all:** `deploy.bat`

## Files to Update:
- `sync.php` - Add your FTP credentials
- `config/database.php` - Add your database credentials

## Test:
1. Make a small change to `index.php`
2. Run sync
3. Refresh your website
4. See the change instantly!