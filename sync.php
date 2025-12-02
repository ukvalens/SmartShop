<?php
// Simple PHP sync script - run this to upload changes
$ftp_server = "files.infinityfree.net";
$ftp_user = "if0_37123456"; // Replace with your username
$ftp_pass = "your_password"; // Replace with your password

$connection = ftp_connect($ftp_server);
if (!$connection) {
    die("Could not connect to FTP server");
}

$login = ftp_login($connection, $ftp_user, $ftp_pass);
if (!$login) {
    die("Could not login to FTP server");
}

ftp_pasv($connection, true);

function uploadFile($connection, $local_file, $remote_file) {
    if (ftp_put($connection, $remote_file, $local_file, FTP_BINARY)) {
        echo "✅ Uploaded: $local_file\n";
    } else {
        echo "❌ Failed: $local_file\n";
    }
}

// Upload key files
$files = [
    'index.php' => '/htdocs/index.php',
    'app/views/auth/login.php' => '/htdocs/app/views/auth/login.php',
    'config/database.php' => '/htdocs/config/database.php'
];

foreach ($files as $local => $remote) {
    if (file_exists($local)) {
        uploadFile($connection, $local, $remote);
    }
}

ftp_close($connection);
echo "Sync complete!\n";
?>