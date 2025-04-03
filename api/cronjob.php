<?php
// Tắt hiển thị lỗi
error_reporting(0);

// Thiết lập header
header('Content-Type: application/json');

// Kiểm tra phương thức yêu cầu
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Kiểm tra API key từ tham số URL
$api_key = isset($_GET['api_key']) ? $_GET['api_key'] : '';
$config_api_key = 'YOUR_SECRET_API_KEY'; // Thay đổi thành API key của bạn

if ($api_key !== $config_api_key) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Kết nối database
require_once '../../config/database.php';
$database = new Database();
$db = $database->getConnection();

try {
    // Cập nhật các license đã hết hạn
    $query = "UPDATE licenses 
              SET status = 'expired' 
              WHERE status = 'active' 
              AND expiration_date IS NOT NULL 
              AND expiration_date < CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $affected_rows = $stmt->rowCount();
    
    // Lấy danh sách license vừa được cập nhật
    $query = "SELECT id, license_key, customer_email, product_id 
              FROM licenses 
              WHERE status = 'expired' 
              AND expiration_date < CURDATE() 
              AND expiration_date >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $expired_licenses = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $expired_licenses[] = $row;
    }
    
    // Trả về kết quả
    echo json_encode([
        'success' => true,
        'message' => 'Cron job executed successfully',
        'updated_licenses' => $affected_rows,
        'expired_licenses' => $expired_licenses,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
?>