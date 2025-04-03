<?php
// Cho phép truy cập API từ bất kỳ nguồn nào
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Kết nối database
require_once '../config/database.php';
require_once '../functions/auth.php';
require_once '../functions/helpers.php';

// Kiểm tra đăng nhập
$user = checkUserLogin();
if (!$user) {
    http_response_code(401);
    echo json_encode(array("success" => false, "message" => "Bạn cần đăng nhập để thực hiện thao tác này."));
    exit();
}

// Lấy dữ liệu từ request
$data = json_decode(file_get_contents("php://input"));

// Kiểm tra dữ liệu
if (empty($data->product_id) || empty($data->pricing_id)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Thiếu thông tin sản phẩm hoặc gói giá."));
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Lấy thông tin gói giá
    $query = "SELECT p.*, pp.price, pp.duration_type 
              FROM product_pricing pp
              JOIN products p ON pp.product_id = p.id
              WHERE pp.id = ? AND pp.product_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $data->pricing_id);
    $stmt->bindParam(2, $data->product_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        http_response_code(404);
        echo json_encode(array("success" => false, "message" => "Không tìm thấy thông tin gói giá."));
        exit();
    }
    
    $pricing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Tạo mã đơn hàng ngẫu nhiên 10 ký tự
    $order_code = generateRandomCode(10);
    
    // Tạo đơn hàng mới
    $query = "INSERT INTO payment_orders (order_code, user_id, product_id, pricing_id, amount, status, expired_at) 
              VALUES (?, ?, ?, ?, ?, 'pending', DATE_ADD(NOW(), INTERVAL 24 HOUR))";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $order_code);
    $stmt->bindParam(2, $user['id']);
    $stmt->bindParam(3, $data->product_id);
    $stmt->bindParam(4, $data->pricing_id);
    $stmt->bindParam(5, $pricing['price']);
    
    if ($stmt->execute()) {
        $order_id = $db->lastInsertId();
        
        // Trả về thông tin đơn hàng
        http_response_code(200);
        echo json_encode(array(
            "success" => true,
            "message" => "Đơn hàng đã được tạo thành công.",
            "data" => array(
                "order_id" => $order_id,
                "order_code" => $order_code,
                "product_name" => $pricing['title'],
                "amount" => $pricing['price'],
                "duration_type" => $pricing['duration_type'],
                "expired_at" => date('Y-m-d H:i:s', strtotime('+24 hours'))
            )
        ));
    } else {
        http_response_code(500);
        echo json_encode(array("success" => false, "message" => "Không thể tạo đơn hàng."));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Lỗi: " . $e->getMessage()));
}
?>