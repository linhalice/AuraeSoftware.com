<?php
// Tắt hiển thị lỗi
error_reporting(0);

// Thiết lập header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Kiểm tra phương thức yêu cầu
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Lấy dữ liệu từ request
$data = json_decode(file_get_contents('php://input'), true);

// Kiểm tra dữ liệu
if (!isset($data['license_key']) || !isset($data['product_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$license_key = $data['license_key'];
$product_id = $data['product_id'];
$machine_id = isset($data['machine_id']) ? $data['machine_id'] : null;

// Kết nối database
require_once '../../config/database.php';
$database = new Database();
$db = $database->getConnection();

try {
    // Kiểm tra license có tồn tại không
    $query = "SELECT l.*, p.title as product_name 
              FROM licenses l 
              JOIN products p ON l.product_id = p.id 
              WHERE l.license_key = ? AND l.product_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $license_key);
    $stmt->bindParam(2, $product_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        echo json_encode([
            'valid' => false,
            'message' => 'License key không hợp lệ hoặc không tồn tại cho sản phẩm này'
        ]);
        exit;
    }
    
    $license = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Kiểm tra trạng thái license
    if ($license['status'] === 'pending') {
        echo json_encode([
            'valid' => false,
            'message' => 'License key chưa được kích hoạt'
        ]);
        exit;
    }
    
    if ($license['status'] === 'revoked') {
        echo json_encode([
            'valid' => false,
            'message' => 'License key đã bị thu hồi'
        ]);
        exit;
    }
    
    // Kiểm tra ngày hết hạn
    if ($license['status'] === 'expired' || ($license['expiration_date'] && strtotime($license['expiration_date']) < time())) {
        // Cập nhật trạng thái nếu license đã hết hạn nhưng chưa được đánh dấu
        if ($license['status'] !== 'expired' && strtotime($license['expiration_date']) < time()) {
            $update_query = "UPDATE licenses SET status = 'expired' WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(1, $license['id']);
            $update_stmt->execute();
        }
        
        echo json_encode([
            'valid' => false,
            'message' => 'License key đã hết hạn',
            'expiration_date' => $license['expiration_date']
        ]);
        exit;
    }
    
    // Nếu có machine_id, lưu lại thông tin
    if ($machine_id) {
        // Kiểm tra xem machine_id đã được lưu chưa
        $check_query = "SELECT id FROM license_activations WHERE license_id = ? AND machine_id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(1, $license['id']);
        $check_stmt->bindParam(2, $machine_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() === 0) {
            // Lưu thông tin kích hoạt mới
            $insert_query = "INSERT INTO license_activations (license_id, machine_id, activation_date, last_check_date) VALUES (?, ?, NOW(), NOW())";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(1, $license['id']);
            $insert_stmt->bindParam(2, $machine_id);
            $insert_stmt->execute();
        } else {
            // Cập nhật ngày kiểm tra gần nhất
            $update_query = "UPDATE license_activations SET last_check_date = NOW() WHERE license_id = ? AND machine_id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(1, $license['id']);
            $update_stmt->bindParam(2, $machine_id);
            $update_stmt->execute();
        }
    }
    
    // Tính số ngày còn lại
    $days_left = null;
    if ($license['expiration_date']) {
        $days_left = ceil((strtotime($license['expiration_date']) - time()) / (60 * 60 * 24));
    }
    
    // Trả về kết quả
    echo json_encode([
        'valid' => true,
        'message' => 'License key hợp lệ',
        'license' => [
            'key' => $license['license_key'],
            'product_id' => $license['product_id'],
            'product_name' => $license['product_name'],
            'customer_name' => $license['customer_name'],
            'customer_email' => $license['customer_email'],
            'status' => $license['status'],
            'activation_date' => $license['activation_date'],
            'expiration_date' => $license['expiration_date'],
            'days_left' => $days_left
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
?>