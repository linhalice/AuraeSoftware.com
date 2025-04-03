<?php
// Script này sẽ được chạy định kỳ (ví dụ: mỗi 5 phút) để kiểm tra các giao dịch thanh toán

// Kết nối database
require_once '../config/database.php';
require_once '../functions/license.php';
require_once '../functions/helpers.php';

$database = new Database();
$db = $database->getConnection();

// Lấy danh sách đơn hàng đang chờ thanh toán
$query = "SELECT * FROM payment_orders WHERE status = 'pending' AND expired_at > NOW()";
$stmt = $db->prepare($query);
$stmt->execute();

$pending_orders = array();
while ($order = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $pending_orders[] = $order;
}

if (count($pending_orders) == 0) {
    echo "Không có đơn hàng nào đang chờ thanh toán.\n";
    exit();
}

// Gọi API lấy danh sách giao dịch ngân hàng
$api_url = "http://yourdomain.com/api/bank_transactions.php?api_key=YOUR_SECRET_API_KEY&from_date=" . date('Y-m-d', strtotime('-1 day')) . "&to_date=" . date('Y-m-d');
$response = file_get_contents($api_url);
$transactions = json_decode($response, true);

if (!$transactions['success']) {
    echo "Lỗi khi lấy danh sách giao dịch: " . $transactions['message'] . "\n";
    exit();
}

// Kiểm tra từng đơn hàng
foreach ($pending_orders as $order) {
    // Tìm giao dịch tương ứng
    foreach ($transactions['data'] as $transaction) {
        // Kiểm tra nếu nội dung chuyển khoản khớp với mã đơn hàng và số tiền khớp
        if ($transaction['description'] == $order['order_code'] && $transaction['amount'] == $order['amount']) {
            echo "Tìm thấy giao dịch cho đơn hàng " . $order['order_code'] . "\n";
            
            // Gọi API xác nhận thanh toán
            $verify_url = "http://yourdomain.com/api/verify_payment.php";
            $verify_data = array(
                "order_code" => $order['order_code'],
                "amount" => $order['amount'],
                "transaction_code" => $transaction['transaction_id']
            );
            
            $options = array(
                'http' => array(
                    'header'  => "Content-type: application/json\r\n",
                    'method'  => 'POST',
                    'content' => json_encode($verify_data)
                )
            );
            $context  = stream_context_create($options);
            $verify_response = file_get_contents($verify_url, false, $context);
            $verify_result = json_decode($verify_response, true);
            
            if ($verify_result['success']) {
                echo "Đã xác nhận thanh toán cho đơn hàng " . $order['order_code'] . "\n";
            } else {
                echo "Lỗi khi xác nhận thanh toán: " . $verify_result['message'] . "\n";
            }
            
            break;
        }
    }
}

echo "Hoàn tất kiểm tra thanh toán.\n";
?>