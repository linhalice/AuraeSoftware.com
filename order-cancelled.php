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
if ($order['status'] != 'cancelled') {
    if ($order['status'] == 'pending') {
        header("Location: payment.php?order_id=" . $order_id);
        exit();
    } else if ($order['status'] == 'completed') {
        header("Location: order-success.php?order_id=" . $order_id);
        exit();
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-red-500 p-6 text-white text-center">
            <i class="fas fa-times-circle text-5xl mb-4"></i>
            <h1 class="text-2xl font-bold">Đơn hàng đã bị hủy</h1>
            <p class="text-lg">Đơn hàng của bạn đã bị hủy hoặc hết thời gian thanh toán</p>
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
                        <p class="text-sm text-gray-500">Ngày tạo:</p>
                        <p class="text-sm font-medium text-gray-900"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Sản phẩm:</p>
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($order['product_name']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Số tiền:</p>
                        <p class="text-sm font-medium text-gray-900"><?php echo number_format($order['amount'], 0, ',', '.'); ?> VNĐ</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-red-50 p-4 rounded-lg border border-red-100 mb-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-red-500 mt-0.5"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Lý do hủy đơn hàng</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p>Đơn hàng của bạn đã bị hủy do một trong các lý do sau:</p>
                            <ul class="list-disc list-inside mt-1">
                                <li>Hết thời gian thanh toán (15 phút)</li>
                                <li>Bạn đã hủy đơn hàng</li>
                                <li>Có lỗi trong quá trình xử lý thanh toán</li>
                            </ul>
                            <p class="mt-2">Bạn có thể tạo đơn hàng mới để tiếp tục.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-center space-x-4">
                <a href="product-detail.php?id=<?php echo $order['product_id']; ?>" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg flex items-center transition-colors duration-200">
                    <i class="fas fa-redo mr-2"></i> Tạo đơn hàng mới
                </a>
                <a href="products.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg flex items-center transition-colors duration-200">
                    <i class="fas fa-arrow-left mr-2"></i> Quay lại trang sản phẩm
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>