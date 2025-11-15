<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../../config/database.php';

$auth = new AuthController();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];
    $user_id = $auth->getCurrentUser()['user_id'];
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get product details
    $product = $conn->query("SELECT * FROM products WHERE product_id = $product_id")->fetch_assoc();
    
    if ($product) {
        // Insert reorder alert
        $stmt = $conn->prepare("INSERT INTO reorder_alerts (product_id, user_id, current_stock, reorder_level, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->bind_param("iiii", $product_id, $user_id, $product['stock_quantity'], $product['reorder_level']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Reorder alert generated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to generate alert']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>