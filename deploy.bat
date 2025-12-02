@echo off
echo Deploying SmartShop to InfinityFree...

:: Upload via FTP using WinSCP or similar
:: Replace with your actual FTP credentials
set FTP_HOST=files.infinityfree.net
set FTP_USER=if0_37123456
set FTP_PASS=your_password

:: Using curl for FTP upload (if available)
curl -T "index.php" ftp://%FTP_HOST%/htdocs/ --user %FTP_USER%:%FTP_PASS%
curl -T "app/" ftp://%FTP_HOST%/htdocs/app/ --user %FTP_USER%:%FTP_PASS% -r
curl -T "config/" ftp://%FTP_HOST%/htdocs/config/ --user %FTP_USER%:%FTP_PASS% -r
curl -T "public/" ftp://%FTP_HOST%/htdocs/public/ --user %FTP_USER%:%FTP_PASS% -r

echo Deployment complete!
pause