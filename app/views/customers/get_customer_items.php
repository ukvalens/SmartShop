<?php
require_once __DIR__ . '/../../../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Customer ID required']);
    exit;
}

$customer_id = $_GET['customer_id'];
$db = new Database();
$conn = $db->getConnection();

// Get recent sale items for the customer
$query = "
    SELECT p.product_name, si.quantity, si.unit_price
    FROM sale_items si
    JOIN sales s ON si.sale_id = s.sale_id
    JOIN products p ON si.product_id = p.product_id
    WHERE s.customer_id = ?
    ORDER BY s.sale_date DESC
    LIMIT 10
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row['quantity'] . 'x ' . $row['product_name'] . ' (@' . number_format($row['unit_price']) . ' RWF)';
}

if (empty($items)) {
    echo json_encode(['success' => false, 'message' => 'No recent purchases']);
} else {
    echo json_encode(['success' => true, 'items' => implode(', ', $items)]);
}
?>