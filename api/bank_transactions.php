<?php
// API này sẽ được sử dụng để lấy danh sách giao dịch ngân hàng
// Trong thực tế, bạn sẽ cần tích hợp với API của ngân hàng hoặc dịch vụ thanh toán

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Kết nối database
require_once '../config/database.php';
require_once '../functions/auth.php';

// Kiểm tra xác thực API (trong thực tế, bạn sẽ cần một hệ thống xác thực API mạnh hơn)
$api_key = $_GET['api_key'] ?? '';
if ($api_key != 'YOUR_SECRET_API_KEY') {
    http_response_code(401);
    echo json_encode(array("success" => false, "message" => "Unauthorized"));
    exit();
}

// Lấy tham số
$from_date = $_GET['from_date'] ?? date('Y-m-d');
$to_date = $_GET['to_date'] ?? date('Y-m-d');

// Mô phỏng danh sách giao dịch ngân hàng
// Trong thực tế, bạn sẽ lấy dữ liệu từ API của ngân hàng
$transactions = array();

// Kết nối database để lấy danh sách đơn hàng đang chờ thanh toán
$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM payment_orders WHERE status = 'pending' AND created_at BETWEEN ? AND ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $from_date . ' 00:00:00');
$stmt->bindParam(2, $to_date . ' 23:59:59');
$stmt->execute();

// Mô phỏng một số giao dịch ngân hàng dựa trên đơn hàng đang chờ
while ($order = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Giả định 50% đơn hàng đã được thanh toán
    if (rand(0, 1) == 1) {
        $transactions[] = array(
            "transaction_id" => "BK" . rand(1000000, 9999999),
            "amount" => $order['amount'],
            "description" => $order['order_code'],
            "transaction_date" => date('Y-m-d H:i:s', strtotime('-' . rand(1, 60) . ' minutes')),
            "account_number" => "12345678900",
            "bank_name" => "BIDV"
        );
    }
}

// Trả về danh sách giao dịch
http_response_code(200);
echo json_encode(array(
    "success" => true,
    "data" => $transactions
));
?>