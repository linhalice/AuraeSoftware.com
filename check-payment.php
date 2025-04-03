<?php
// Thiết lập header
header('Content-Type: application/json');

require_once 'config/database.php';

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// Kiểm tra đơn hàng
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    echo json_encode(['error' => 'Thiếu mã đơn hàng']);
    exit();
}

$order_id = $_GET['order_id'];

// Lấy thông tin đơn hàng
$query = "SELECT * FROM orders WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $order_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    echo json_encode(['error' => 'Đơn hàng không tồn tại']);
    exit();
}

$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Kiểm tra nếu đơn hàng đã hết hạn
if ($order['status'] == 'pending' && strtotime($order['expires_at']) < time()) {
    // Cập nhật trạng thái đơn hàng thành hết hạn
    $update_query = "UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(1, $order_id);
    $update_stmt->execute();
    
    $order['status'] = 'cancelled';
}

// Trả về trạng thái đơn hàng
echo json_encode([
    'order_id' => $order['id'],
    'order_code' => $order['order_code'],
    'status' => $order['status'],
    'amount' => $order['amount'],
    'expires_at' => $order['expires_at']
]);
?>