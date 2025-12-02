<?php
require_once __DIR__ . '/../../../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    echo json_encode([]);
    exit;
}

$order_id = intval($_GET['order_id']);
$db = new Database();
$conn = $db->getConnection();

// Get order details with product information
$query = "
    SELECT 
        p.product_name,
        pod.quantity_ordered,
        pod.cost_price
    FROM purchase_order_details pod
    JOIN products p ON pod.product_id = p.product_id
    WHERE pod.purchase_order_id = ?
    ORDER BY p.product_name
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = [
        'product_name' => $row['product_name'],
        'quantity_ordered' => intval($row['quantity_ordered']),
        'cost_price' => floatval($row['cost_price'])
    ];
}

echo json_encode($products);
?>