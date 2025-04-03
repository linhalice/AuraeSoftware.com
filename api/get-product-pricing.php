<?php
header('Content-Type: application/json');

require_once '../config/database.php';

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// Lấy product_id từ request
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if ($product_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID sản phẩm không hợp lệ'
    ]);
    exit;
}

// Lấy thông tin giá của sản phẩm
$query = "SELECT duration_type, price FROM product_pricing WHERE product_id = ? ORDER BY price ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $product_id);
$stmt->execute();

$pricing = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $pricing[] = [
        'duration_type' => $row['duration_type'],
        'price' => $row['price']
    ];
}

echo json_encode([
    'success' => true,
    'product_id' => $product_id,
    'pricing' => $pricing
]);
?>