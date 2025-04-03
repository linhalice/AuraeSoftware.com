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
if ($order['status'] != 'completed') {
    if ($order['status'] == 'pending') {
        header("Location: payment.php?order_id=" . $order_id);
        exit();
    } else if ($order['status'] == 'cancelled') {
        header("Location: order-cancelled.php?order_id=" . $order_id);
        exit();
    }
}

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

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-green-500 p-6 text-white text-center">
            <i class="fas fa-check-circle text-5xl mb-4"></i>
            <h1 class="text-2xl font-bold">Thanh toán thành công!</h1>
            <p class="text-lg">Cảm ơn bạn đã mua sản phẩm của chúng tôi</p>
        </div>
        
        <div class="p-6">
            <div class="mb-6">
                <h2 class="text-xl font-semibold mb-4">Thông tin đơn hàng</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Mã đơn hàng:</p>
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($order['order_code']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Ngày thanh toán:</p>
                        <p class="text-sm font-medium text-gray-900"><?php echo date('d/m/Y H:i', strtotime($order['updated_at'])); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Sản phẩm:</p>
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($order['product_name']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Thời hạn:</p>
                        <p class="text-sm font-medium text-gray-900"><?php echo getDurationLabel($order['duration_type']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">License Key:</p>
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($order['license_key']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Số tiền:</p>
                        <p class="text-sm font-medium text-gray-900"><?php echo number_format($order['amount'], 0, ',', '.'); ?> VNĐ</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-green-50 p-4 rounded-lg border border-green-100 mb-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-green-500 mt-0.5"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">License đã được kích hoạt</h3>
                        <div class="mt-2 text-sm text-green-700">
                            <p>License của bạn đã được kích hoạt thành công. Bạn có thể sử dụng phần mềm ngay bây giờ.</p>
                            <p class="mt-1">Nếu bạn gặp bất kỳ vấn đề nào, vui lòng liên hệ với chúng tôi qua email <a href="mailto:support@aurae.com" class="text-green-800 underline">support@aurae.com</a>.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-center">
                <a href="products.php" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg flex items-center transition-colors duration-200">
                    <i class="fas fa-arrow-left mr-2"></i> Quay lại trang sản phẩm
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>