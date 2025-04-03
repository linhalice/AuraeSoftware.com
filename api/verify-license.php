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
if (empty($data->license_key)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Thiếu khóa license."
    ]);
    exit();
}

$license_key = $data->license_key;
$machine_id = isset($data->machine_id) ? $data->machine_id : null;

// Sử dụng hàm verifyLicense từ file license.php
$result = verifyLicense($db, $license_key);

// Nếu có machine_id, cập nhật vào database
if ($machine_id && $result['valid']) {
    $update_query = "UPDATE licenses SET machine_id = ? WHERE license_key = ? AND (machine_id IS NULL OR machine_id = '')";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(1, $machine_id);
    $update_stmt->bindParam(2, $license_key);
    $update_stmt->execute();
}

if (!$result['valid']) {
    // Trả về lỗi nếu license không hợp lệ
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "message" => $result['message'],
        "status" => isset($result['status']) ? $result['status'] : null,
        "expires_at" => isset($result['expires_at']) ? $result['expires_at'] : null
    ]);
    exit();
}

// Phản hồi thành công nếu license hợp lệ
http_response_code(200);
echo json_encode([
    "success" => true,
    "message" => "License hợp lệ.",
    "data" => [
        "license_id" => $result['license']['id'],
        "license_key" => $result['license']['license_key'],
        "product_id" => $result['license']['product_id'],
        "product_name" => $result['license']['product_name'],
        "status" => $result['license']['status'],
        "duration_type" => $result['license']['duration_type'],
        "expires_at" => $result['license']['expires_at']
    ]
]);
?>