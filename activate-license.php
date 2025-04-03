<?php
// Cho phép truy cập API từ bất kỳ nguồn nào
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Kết nối database
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Lấy dữ liệu từ request
$data = json_decode(file_get_contents("php://input"));

// Kiểm tra dữ liệu
if (empty($data->license_key) || empty($data->machine_id)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Thiếu thông tin license key hoặc machine ID."));
    exit();
}

$license_key = $data->license_key;
$machine_id = $data->machine_id;

// Kiểm tra license key
$query = "SELECT l.*, p.title as product_name FROM licenses l 
          JOIN products p ON l.product_id = p.id 
          WHERE l.license_key = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $license_key);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $license = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Kiểm tra trạng thái license
    if ($license['status'] == 'pending') {
        // Kích hoạt license
        $query = "UPDATE licenses SET status = 'active', activation_date = NOW() WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $license['id']);
        
        if ($stmt->execute()) {
            // Kích hoạt thành công
            http_response_code(200);
            echo json_encode(array(
                "success" => true,
                "message" => "License đã được kích hoạt thành công.",
                "data" => array(
                    "license_key" => $license['license_key'],
                    "product_id" => $license['product_id'],
                    "product_name" => $license['product_name'],
                    "customer_name" => $license['customer_name'],
                    "status" => "active",
                    "activation_date" => date('Y-m-d H:i:s')
                )
            ));
        } else {
            // Lỗi khi kích hoạt
            http_response_code(500);
            echo json_encode(array("success" => false, "message" => "Không thể kích hoạt license."));
        }
    } else if ($license['status'] == 'active') {
        // License đã được kích hoạt
        http_response_code(200);
        echo json_encode(array(
            "success" => true,
            "message" => "License đã được kích hoạt trước đó.",
            "data" => array(
                "license_key" => $license['license_key'],
                "product_id" => $license['product_id'],
                "product_name" => $license['product_name'],
                "customer_name" => $license['customer_name'],
                "status" => $license['status'],
                "activation_date" => $license['activation_date']
            )
        ));
    } else {
        // License không thể kích hoạt
        http_response_code(400);
        echo json_encode(array(
            "success" => false,
            "message" => "License không thể kích hoạt. Trạng thái: " . $license['status'],
            "status" => $license['status']
        ));
    }
} else {
    // License không tồn tại
    http_response_code(404);
    echo json_encode(array("success" => false, "message" => "License không tồn tại."));
}
?>