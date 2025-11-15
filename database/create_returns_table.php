<?php
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$sql1 = "CREATE TABLE IF NOT EXISTS returns (
    return_id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    user_id INT NOT NULL,
    return_reason VARCHAR(255) NOT NULL,
    refund_amount DECIMAL(10,2) NOT NULL,
    status ENUM('processed', 'approved', 'rejected') DEFAULT 'processed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(sale_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
)";

$sql2 = "ALTER TABLE sales ADD COLUMN IF NOT EXISTS status ENUM('completed', 'returned', 'cancelled') DEFAULT 'completed'";

if ($conn->query($sql1) === TRUE) {
    echo "Returns table created successfully\n";
} else {
    echo "Error creating returns table: " . $conn->error . "\n";
}

if ($conn->query($sql2) === TRUE) {
    echo "Sales table updated successfully";
} else {
    echo "Error updating sales table: " . $conn->error;
}

$conn->close();
?>