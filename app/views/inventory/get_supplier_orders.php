<?php
require_once __DIR__ . '/../../../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['supplier_id']) || empty($_GET['supplier_id'])) {
    echo json_encode([]);
    exit;
}

$supplier_id = intval($_GET['supplier_id']);
$db = new Database();
$conn = $db->getConnection();

// Get pending orders for the supplier with product details
$query = "
    SELECT 
        po.purchase_order_id,
        po.order_date,
        po.status,
        GROUP_CONCAT(
            CONCAT(p.product_name, ':', pod.quantity_ordered, ':', pod.cost_price) 
            SEPARATOR '|'
        ) as products_data
    FROM purchase_orders po
    JOIN purchase_order_details pod ON po.purchase_order_id = pod.purchase_order_id
    JOIN products p ON pod.product_id = p.product_id
    WHERE po.supplier_id = ? AND po.status = 'Pending'
    GROUP BY po.purchase_order_id
    ORDER BY po.order_date DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $products = [];
    if ($row['products_data']) {
        $products_data = explode('|', $row['products_data']);
        foreach ($products_data as $product_data) {
            $parts = explode(':', $product_data);
            if (count($parts) >= 3) {
                $products[] = [
                    'product_name' => $parts[0],
                    'quantity_ordered' => intval($parts[1]),
                    'cost_price' => floatval($parts[2])
                ];
            }
        }
    }
    
    $orders[] = [
        'purchase_order_id' => $row['purchase_order_id'],
        'order_date' => $row['order_date'],
        'status' => $row['status'],
        'products' => $products
    ];
}

echo json_encode($orders);
?>