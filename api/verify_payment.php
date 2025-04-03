<?php
// API này sẽ được gọi từ hệ thống nội bộ để xác minh thanh toán
// Trong thực tế, bạn sẽ cần một hệ thống webhook hoặc cronjob để kiểm tra giao dịch

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Kết nối database
require_once '../config/database.php';
require_once '../functions/license.php';

// Lấy dữ liệu từ request
$data = json_decode(file_get_contents("php://input"));

// Kiểm tra dữ liệu
if (empty($data->order_code) || empty($data->amount) || empty($data->transaction_code)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Thiếu thông tin giao dịch."));
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Kiểm tra đơn hàng
    $query = "SELECT po.*, pp.duration_type, u.name, u.email 
              FROM payment_orders po
              JOIN product_pricing pp ON po.pricing_id = pp.id
              JOIN users u ON po.user_id = u.id
              WHERE po.order_code = ? AND po.status = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $data->order_code);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        http_response_code(404);
        echo json_encode(array("success" => false, "message" => "Không tìm thấy đơn hàng hoặc đơn hàng đã được xử lý."));
        exit();
    }
    
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Kiểm tra số tiền
    if ($order['amount'] != $data->amount) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "Số tiền thanh toán không khớp."));
        exit();
    }
    
    // Bắt đầu transaction
    $db->beginTransaction();
    
    // Cập nhật trạng thái đơn hàng
    $query = "UPDATE payment_orders SET status = 'completed', updated_at = NOW() WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $order['id']);
    $stmt->execute();
    
    // Lưu thông tin giao dịch
    $query = "INSERT INTO payment_transactions (order_id, transaction_code, amount, status, transaction_date) 
              VALUES (?, ?, ?, 'verified', NOW())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $order['id']);
    $stmt->bindParam(2, $data->transaction_code);
    $stmt->bindParam(3, $data->amount);
    $stmt->execute();
    
    // Tạo license key
    $license_key = generateLicenseKey($order['product_id']);
    
    // Tính ngày hết hạn dựa trên loại gói
    $expiration_date = calculateExpirationDate($order['duration_type']);
    
    // Tạo license mới
    $query = "INSERT INTO licenses (license_key, product_id, order_id, customer_name, customer_email, status, expiration_date) 
              VALUES (?, ?, ?, ?, ?, 'pending', ?)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $license_key);
    $stmt->bindParam(2, $order['product_id']);
    $stmt->bindParam(3, $order['id']);
    $stmt->bindParam(4, $order['name']);
    $stmt->bindParam(5, $order['email']);
    $stmt->bindParam(6, $expiration_date);
    $stmt->execute();
    
    $license_id = $db->lastInsertId();
    
    // Commit transaction
    $db->commit();
    
    // Trả về thông tin license
    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "message" => "Thanh toán đã được xác nhận và license đã được tạo.",
        "data" => array(
            "license_id" => $license_id,
            "license_key" => $license_key,
            "product_id" => $order['product_id'],
            "expiration_date" => $expiration_date
        )
    ));
} catch (Exception $e) {
    // Rollback transaction nếu có lỗi
    $db->rollBack();
    
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Lỗi: " . $e->getMessage()));
}

// Hàm tính ngày hết hạn dựa trên loại gói
function calculateExpirationDate($duration_type) {
    switch ($duration_type) {
        case '3_days':
            return date('Y-m-d H:i:s', strtotime('+3 days'));
        case '1_month':
            return date('Y-m-d H:i:s', strtotime('+1 month'));
        case '3_months':
            return date('Y-m-d H:i:s', strtotime('+3 months'));
        case '6_months':
            return date('Y-m-d H:i:s', strtotime('+6 months'));
        case '1_year':
            return date('Y-m-d H:i:s', strtotime('+1 year'));
        case 'lifetime':
            return date('Y-m-d H:i:s', strtotime('+100 years')); // Giả định vĩnh viễn là 100 năm
        default:
            return date('Y-m-d H:i:s', strtotime('+1 month')); // Mặc định 1 tháng
    }
}
?>