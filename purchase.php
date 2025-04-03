<?php
require_once 'config/database.php';
require_once 'functions/auth.php';
require_once 'functions/helpers.php';

// Kiểm tra đăng nhập
if (!isUserLoggedIn()) {
    header("Location: login.php?redirect=purchase.php" . (isset($_GET['product_id']) ? "&product_id=" . $_GET['product_id'] : "") . (isset($_GET['pricing_id']) ? "&pricing_id=" . $_GET['pricing_id'] : ""));
    exit();
}

// Kiểm tra tham số
if (!isset($_GET['product_id']) || !isset($_GET['pricing_id'])) {
    header("Location: products.php");
    exit();
}

$product_id = $_GET['product_id'];
$pricing_id = $_GET['pricing_id'];

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// Lấy thông tin sản phẩm và gói giá
$query = "SELECT p.*, pp.id as pricing_id, pp.price, pp.duration_type 
          FROM products p
          JOIN product_pricing pp ON p.id = pp.product_id
          WHERE p.id = ? AND pp.id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $product_id);
$stmt->bindParam(2, $pricing_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: products.php");
    exit();
}

$product = $stmt->fetch(PDO::FETCH_ASSOC);

// Chuyển đổi loại thời hạn thành text
$duration_text = '';
switch ($product['duration_type']) {
    case '3_days': $duration_text = '3 ngày'; break;
    case '1_month': $duration_text = '1 tháng'; break;
    case '3_months': $duration_text = '3 tháng'; break;
    case '6_months': $duration_text = '6 tháng'; break;
    case '1_year': $duration_text = '1 năm'; break;
    case 'lifetime': $duration_text = 'Vĩnh viễn'; break;
}

// Tạo đơn hàng mới nếu có yêu cầu
$order_code = '';
$order_id = 0;

if (isset($_POST['create_order'])) {
    // Tạo mã đơn hàng ngẫu nhiên 10 ký tự
    $order_code = generateRandomCode(10);
    
    // Lấy thông tin người dùng
    $user_id = $_SESSION['user_id'];
    
    // Tạo đơn hàng mới
    $query = "INSERT INTO payment_orders (order_code, user_id, product_id, pricing_id, amount, status, expired_at) 
              VALUES (?, ?, ?, ?, ?, 'pending', DATE_ADD(NOW(), INTERVAL 24 HOUR))";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $order_code);
    $stmt->bindParam(2, $user_id);
    $stmt->bindParam(3, $product_id);
    $stmt->bindParam(4, $pricing_id);
    $stmt->bindParam(5, $product['price']);
    
    if ($stmt->execute()) {
        $order_id = $db->lastInsertId();
        header("Location: payment.php?order_id=" . $order_id);
        exit();
    }
}

include 'includes/header.php';
?>

<main class="flex-1 bg-gray-900">
    <div class="py-16 px-4 md:px-6">
        <div class="container mx-auto max-w-3xl">
            <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                <div class="p-6 md:p-8">
                    <h2 class="text-2xl font-bold text-white mb-6">Xác nhận mua hàng</h2>
                    
                    <div class="bg-gray-700 rounded-lg p-6 mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-xl font-semibold text-white"><?php echo $product['title']; ?></h3>
                                <?php if (!empty($product['badge'])): ?>
                                <span class="inline-block mt-1 bg-cyan-500 bg-opacity-10 text-cyan-500 px-2 py-0.5 rounded-full text-xs font-medium">
                                    <?php echo $product['badge']; ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-<?php echo $product['icon_color']; ?> bg-opacity-10">
                                    <i class="fas fa-<?php echo $product['icon']; ?> text-<?php echo $product['icon_color']; ?>"></i>
                                </div>
                            </div>
                        </div>
                        
                        <p class="text-gray-300 mb-4"><?php echo $product['description']; ?></p>
                        
                        <div class="border-t border-gray-600 pt-4 mt-4">
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-300">Gói dịch vụ:</span>
                                <span class="text-white"><?php echo $duration_text; ?></span>
                            </div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-300">Giá:</span>
                                <span class="text-cyan-400 font-semibold"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</span>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="bg-gray-700 rounded-lg p-6 mb-6">
                            <h4 class="text-lg font-medium text-white mb-4">Thông tin thanh toán</h4>
                            <p class="text-gray-300 mb-4">
                                Sau khi xác nhận, bạn sẽ được chuyển đến trang thanh toán để hoàn tất giao dịch.
                            </p>
                            <div class="border-t border-gray-600 pt-4 mt-4">
                                <div class="flex justify-between mb-2">
                                    <span class="text-gray-300">Tổng thanh toán:</span>
                                    <span class="text-cyan-400 font-bold text-xl"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-between">
                            <a href="products.php" class="px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-md transition-colors">
                                Quay lại
                            </a>
                            <button type="submit" name="create_order" class="px-6 py-3 bg-cyan-500 hover:bg-cyan-600 text-white font-medium rounded-md transition-colors">
                                Tiếp tục thanh toán
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>