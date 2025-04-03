<?php
require_once 'config/database.php';
include 'includes/header.php';

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// Kiểm tra đơn hàng
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    header("Location: products.php");
    exit();
}

$order_id = $_GET['order_id'];

// Lấy thông tin đơn hàng
$query = "SELECT o.*, p.title as product_name, pp.duration_type, l.license_key 
          FROM orders o 
          JOIN products p ON o.product_id = p.id 
          JOIN product_pricing pp ON o.pricing_id = pp.id 
          JOIN licenses l ON o.license_id = l.id 
          WHERE o.id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $order_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: products.php");
    exit();
}

$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Kiểm tra trạng thái đơn hàng
if ($order['status'] != 'pending') {
    if ($order['status'] == 'completed') {
        header("Location: order-success.php?order_id=" . $order_id);
        exit();
    } else if ($order['status'] == 'cancelled') {
        header("Location: order-cancelled.php?order_id=" . $order_id);
        exit();
    }
}

// Lấy thông tin thanh toán từ cấu hình
$config_query = "SELECT * FROM settings WHERE setting_key IN ('bank_name', 'bank_account', 'bank_owner')";
$config_stmt = $db->prepare($config_query);
$config_stmt->execute();
$configs = $config_stmt->fetchAll(PDO::FETCH_ASSOC);

$bank_info = [];
foreach ($configs as $config) {
    $bank_info[$config['setting_key']] = $config['setting_value'];
}

// Tính thời gian còn lại
$expires_at = strtotime($order['expires_at']);
$current_time = time();
$time_left = max(0, $expires_at - $current_time);
$minutes_left = floor($time_left / 60);
$seconds_left = $time_left % 60;

// Hàm hiển thị nhãn thời hạn
function getDurationLabel($duration_type) {
    switch ($duration_type) {
        case '3_days':
            return '3 ngày';
        case '1_month':
            return '1 tháng';
        case '3_months':
            return '3 tháng';
        case '6_months':
            return '6 tháng';
        case '1_year':
            return '1 năm';
        case 'lifetime':
            return 'Vĩnh viễn';
        default:
            return $duration_type;
    }
}
?>

<div class="flex min-h-screen bg-gray-100">
    <!-- Thông tin thanh toán bên trái -->
    <div class="w-full md:w-1/3 bg-gray-900 text-white p-6">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-purple-400 mb-1">Aurae Software</h1>
            <p class="text-sm text-gray-400">GIẢI PHÁP PHẦN MỀM CHUYÊN NGHIỆP</p>
        </div>
        
        <div class="space-y-6">
            <div class="border-t border-gray-700 pt-4">
                <div class="flex items-center">
                    <i class="fas fa-university mr-3 text-gray-400"></i>
                    <div>
                        <p class="text-sm text-gray-400">Ngân Hàng</p>
                        <p class="font-medium"><?php echo htmlspecialchars($bank_info['bank_name'] ?? 'Vietcombank'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-700 pt-4">
                <div class="flex items-center">
                    <i class="fas fa-credit-card mr-3 text-gray-400"></i>
                    <div>
                        <p class="text-sm text-gray-400">Số tài khoản</p>
                        <div class="flex items-center">
                            <p class="font-medium text-yellow-400"><?php echo htmlspecialchars($bank_info['bank_account'] ?? '9310279999'); ?></p>
                            <button onclick="copyToClipboard('<?php echo htmlspecialchars($bank_info['bank_account'] ?? '9310279999'); ?>')" class="ml-2 text-gray-400 hover:text-white">
                                <i class="far fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-700 pt-4">
                <div class="flex items-center">
                    <i class="fas fa-user mr-3 text-gray-400"></i>
                    <div>
                        <p class="text-sm text-gray-400">Chủ tài khoản</p>
                        <p class="font-medium text-green-400"><?php echo htmlspecialchars($bank_info['bank_owner'] ?? 'NGUYEN THANH TRUNG'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-700 pt-4">
                <div class="flex items-center">
                    <i class="fas fa-money-bill-wave mr-3 text-gray-400"></i>
                    <div>
                        <p class="text-sm text-gray-400">Số tiền cần thanh toán</p>
                        <p class="font-medium text-cyan-400"><?php echo number_format($order['amount'], 0, ',', '.'); ?>đ</p>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-700 pt-4">
                <div class="flex items-center">
                    <i class="fas fa-comment-dots mr-3 text-gray-400"></i>
                    <div>
                        <p class="text-sm text-gray-400">Nội dung chuyển khoản (Mã hóa đơn)</p>
                        <div class="flex items-center">
                            <p class="font-medium text-yellow-400"><?php echo htmlspecialchars($order['order_code']); ?></p>
                            <button onclick="copyToClipboard('<?php echo htmlspecialchars($order['order_code']); ?>')" class="ml-2 text-gray-400 hover:text-white">
                                <i class="far fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-700 pt-4">
                <div class="flex items-center">
                    <i class="fas fa-clock mr-3 text-gray-400"></i>
                    <div>
                        <p class="text-sm text-gray-400">Trạng thái</p>
                        <p class="font-medium text-orange-400">Đang chờ thanh toán</p>
                        <p class="text-xs text-gray-400 mt-1">Vui lòng thanh toán trong <span id="countdown"><?php echo $minutes_left; ?> phút <?php echo $seconds_left; ?> giây</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- QR Code bên phải -->
    <div class="w-full md:w-2/3 p-6 flex flex-col items-center justify-center">
        <div class="max-w-md w-full bg-white p-8 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold text-center text-teal-600 mb-6">QUÉT MÃ QR ĐỂ THANH TOÁN</h2>
            
            <p class="text-center mb-6">Sử dụng <strong>App Internet Banking</strong> hoặc ứng dụng camera hỗ trợ QR code để quét mã</p>
            
            <div class="flex justify-center mb-6">
                <img src="/assets/images/vietqr-logo.png" alt="VietQR" class="h-10">
            </div>
            
            <div class="border-2 border-blue-500 p-2 rounded-lg mb-6">
                <?php
                // Tạo nội dung QR code với API VietQR
                $bank_name = strtolower($bank_info['bank_name'] ?? 'vietcombank');
                $bank_account = $bank_info['bank_account'] ?? '9310279999';
                $amount = $order['amount'];
                $content = $order['order_code'];
                $account_name = urlencode($bank_info['bank_owner'] ?? 'NGUYEN THANH TRUNG');
                
                $qr_url = "https://api.vietqr.io/{$bank_name}/{$bank_account}/{$amount}/{$content}/vietqr_net_2.jpg?accountName={$account_name}";
                ?>
                <img src="<?php echo $qr_url; ?>" alt="QR Code thanh toán" class="w-full">
            </div>
            
            <div class="flex justify-center space-x-4">
                <img src="/assets/images/napas247.png" alt="Napas 247" class="h-8">
                <img src="/assets/images/vietcombank.png" alt="Vietcombank" class="h-8">
            </div>
            
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">Đơn hàng của bạn sẽ được xử lý tự động sau khi thanh toán thành công.</p>
                <p class="text-sm text-gray-600 mt-1">Nếu cần hỗ trợ, vui lòng liên hệ <a href="mailto:support@aurae.com" class="text-blue-600 hover:underline">support@aurae.com</a></p>
            </div>
        </div>
    </div>
</div>

<script>
// Hàm sao chép vào clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Đã sao chép: ' + text);
    }, function(err) {
        console.error('Không thể sao chép: ', err);
    });
}

// Đếm ngược thời gian
var timeLeft = <?php echo $time_left; ?>;
var countdownElement = document.getElementById('countdown');

function updateCountdown() {
    if (timeLeft <= 0) {
        countdownElement.textContent = "Hết thời gian";
        location.reload(); // Tải lại trang để kiểm tra trạng thái
        return;
    }
    
    var minutes = Math.floor(timeLeft / 60);
    var seconds = timeLeft % 60;
    countdownElement.textContent = minutes + " phút " + seconds + " giây";
    timeLeft--;
}

// Cập nhật đếm ngược mỗi giây
setInterval(updateCountdown, 1000);

// Kiểm tra trạng thái thanh toán mỗi 5 giây
setInterval(function() {
    fetch('check-payment.php?order_id=<?php echo $order_id; ?>')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'completed') {
                window.location.href = 'order-success.php?order_id=<?php echo $order_id; ?>';
            } else if (data.status === 'cancelled') {
                window.location.href = 'order-cancelled.php?order_id=<?php echo $order_id; ?>';
            }
        })
        .catch(error => console.error('Lỗi kiểm tra thanh toán:', error));
}, 5000);
</script>

<?php include 'includes/footer.php'; ?>