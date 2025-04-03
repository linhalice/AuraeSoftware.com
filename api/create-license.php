<?php
// Cho phép truy cập từ bất kỳ nguồn nào
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Bao gồm các file cần thiết
require_once '../config/database.php';
require_once '../functions/license.php';

// Kết nối cơ sở dữ liệu
$database = new Database();
$db = $database->getConnection();

// Lấy dữ liệu từ yêu cầu
$data = json_decode(file_get_contents("php://input"));

// Kiểm tra dữ liệu bắt buộc
if (
    empty($data->product_id) || 
    empty($data->license_key)
) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Thiếu dữ liệu bắt buộc. Cần product_id và license_key."
    ]);
    exit();
}

// Trích xuất dữ liệu
$product_id = $data->product_id;
$license_key = $data->license_key;
$machine_id = isset($data->machine_id) ? $data->machine_id : null;
$customer_name = isset($data->customer_name) ? $data->customer_name : null;
$customer_email = isset($data->customer_email) ? $data->customer_email : null;

// Kiểm tra sản phẩm tồn tại
$product_query = "SELECT id, title FROM products WHERE id = ?";
$product_stmt = $db->prepare($product_query);
$product_stmt->bindParam(1, $product_id);
$product_stmt->execute();

if ($product_stmt->rowCount() == 0) {
    http_response_code(404);
    echo json_encode([
        "success" => false,
        "message" => "Không tìm thấy sản phẩm."
    ]);
    exit();
}

$product = $product_stmt->fetch(PDO::FETCH_ASSOC);

// Kiểm tra license key đã tồn tại chưa
$check_query = "SELECT id FROM licenses WHERE license_key = ?";
$check_stmt = $db->prepare($check_query);
$check_stmt->bindParam(1, $license_key);
$check_stmt->execute();

if ($check_stmt->rowCount() > 0) {
    http_response_code(409);
    echo json_encode([
        "success" => false,
        "message" => "License key đã tồn tại."
    ]);
    exit();
}

try {
    // Bắt đầu giao dịch
    $db->beginTransaction();
    
    // Tạo license với trạng thái pending
    $query = "INSERT INTO licenses (
                license_key, 
                product_id, 
                machine_id,
                customer_name, 
                customer_email, 
                status, 
                created_at
            ) VALUES (
                ?, ?, ?, ?, ?, 'pending', NOW()
            )";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $license_key);
    $stmt->bindParam(2, $product_id);
    $stmt->bindParam(3, $machine_id);
    $stmt->bindParam(4, $customer_name);
    $stmt->bindParam(5, $customer_email);
    
    $stmt->execute();
    $license_id = $db->lastInsertId();
    
    // Xác nhận giao dịch
    $db->commit();
    
    // Phản hồi thành công
    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Đã tạo license thành công.",
        "data" => [
            "license_id" => $license_id,
            "license_key" => $license_key,
            "product_id" => $product_id,
            "product_name" => $product['title'],
            "status" => "pending"
        ]
    ]);
    
} catch (Exception $e) {
    // Hoàn tác giao dịch trong trường hợp lỗi
    $db->rollBack();
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Lỗi khi tạo license: " . $e->getMessage()
    ]);
}
?>