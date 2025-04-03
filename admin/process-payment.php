<?php
require_once '../config/database.php';
require_once 'includes/functions.php';
require_once '../functions/license.php';

// Thiết lập header
header('Content-Type: application/json');

// Kiểm tra phương thức yêu cầu
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
    exit();
}

// Kiểm tra đăng nhập admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit();
}

// Lấy dữ liệu từ yêu cầu
$data = json_decode(file_get_contents('php://input'), true);

// Kiểm tra dữ liệu bắt buộc
if (empty($data['order_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Thiếu mã đơn hàng']);
    exit();
}

$order_id = $data['order_id'];
$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'] ?? 'Admin';

// Kết nối database
$database = new Database();
$db = $database->getConnection();

try {
    // Bắt đầu giao dịch
    $db->beginTransaction();
    
    // Lấy thông tin đơn hàng
    $order_query = "SELECT o.*, l.id as license_id, l.license_key, pp.duration_type 
                   FROM orders o 
                   JOIN licenses l ON o.license_id = l.id 
                   JOIN product_pricing pp ON o.pricing_id = pp.id 
                   WHERE o.id = ? AND o.status = 'pending'";
    $order_stmt = $db->prepare($order_query);
    $order_stmt->bindParam(1, $order_id);
    $order_stmt->execute();
    
    if ($order_stmt->rowCount() == 0) {
        throw new Exception("Đơn hàng không tồn tại hoặc không ở trạng thái chờ thanh toán");
    }
    
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Cập nhật trạng thái đơn hàng
    $update_order_query = "UPDATE orders SET 
                          status = 'completed', 
                          payment_date = NOW(), 
                          updated_at = NOW(), 
                          admin_id = ? 
                          WHERE id = ?";
    $update_order_stmt = $db->prepare($update_order_query);
    $update_order_stmt->bindParam(1, $admin_id);
    $update_order_stmt->bindParam(2, $order_id);
    $update_order_stmt->execute();
    
    // Tính ngày hết hạn
    $expiration_date = null;
    if ($order['duration_type'] != 'lifetime') {
        if (function_exists('calculateExpirationDate')) {
            $expiration_date = calculateExpirationDate($order['duration_type']);
        } else {
            // Tính thủ công
            $now = new DateTime();
            switch ($order['duration_type']) {
                case '3_days':
                    $now->add(new DateInterval('P3D'));
                    break;
                case '1_month':
                    $now->add(new DateInterval('P1M'));
                    break;
                case '3_months':
                    $now->add(new DateInterval('P3M'));
                    break;
                case '6_months':
                    $now->add(new DateInterval('P6M'));
                    break;
                case '1_year':
                    $now->add(new DateInterval('P1Y'));
                    break;
                default:
                    // Mặc định 1 tháng
                    $now->add(new DateInterval('P1M'));
            }
            $expiration_date = $now->format('Y-m-d H:i:s');
        }
    }
    
    // Kích hoạt license
    $update_license_query = "UPDATE licenses SET 
                            status = 'active', 
                            duration_type = ?, 
                            activated_at = NOW(), 
                            expires_at = ?, 
                            notes = CONCAT(IFNULL(notes, ''), '\nKích hoạt tự động từ đơn hàng #" . $order['order_code'] . " bởi " . $admin_name . " vào " . date('d/m/Y H:i:s') . "')
                            WHERE id = ?";
    $update_license_stmt = $db->prepare($update_license_query);
    $update_license_stmt->bindParam(1, $order['duration_type']);
    $update_license_stmt->bindParam(2, $expiration_date);
    $update_license_stmt->bindParam(3, $order['license_id']);
    $update_license_stmt->execute();
    
    // Ghi log thanh toán
    $payment_log_query = "INSERT INTO payment_logs (
                         order_id, 
                         license_id, 
                         amount, 
                         payment_method, 
                         transaction_id, 
                         status, 
                         admin_id, 
                         created_at
                         ) VALUES (
                         ?, ?, ?, 'bank_transfer', ?, 'completed', ?, NOW()
                         )";
    $payment_log_stmt = $db->prepare($payment_log_query);
    $transaction_id = 'ADMIN_' . time();
    $payment_log_stmt->bindParam(1, $order_id);
    $payment_log_stmt->bindParam(2, $order['license_id']);
    $payment_log_stmt->bindParam(3, $order['amount']);
    $payment_log_stmt->bindParam(4, $transaction_id);
    $payment_log_stmt->bindParam(5, $admin_id);
    $payment_log_stmt->execute();
    
    // Ghi log hoạt động admin
    if (function_exists('logAdminActivity')) {
        logAdminActivity($db, $admin_id, "Xác nhận thanh toán đơn hàng #" . $order['order_code'] . " và kích hoạt license " . $order['license_key']);
    }
    
    // Xác nhận giao dịch
    $db->commit();
    
    // Trả về kết quả thành công
    echo json_encode([
        'success' => true,
        'message' => 'Thanh toán đã được xử lý thành công',
        'data' => [
            'order_id' => $order_id,
            'order_code' => $order['order_code'],
            'license_key' => $order['license_key'],
            'amount' => $order['amount'],
            'duration_type' => $order['duration_type'],
            'expires_at' => $expiration_date
        ]
    ]);
    
} catch (Exception $e) {
    // Hoàn tác giao dịch nếu có lỗi
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi xử lý thanh toán: ' . $e->getMessage()
    ]);
}
?>