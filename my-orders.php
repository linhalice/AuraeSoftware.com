<?php
require_once 'config/database.php';
include 'includes/header.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Bạn cần đăng nhập để xem đơn hàng của mình.";
    header("Location: login.php");
    exit();
}

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// Lấy thông tin người dùng
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $db->prepare($user_query);
$user_stmt->bindParam(1, $user_id);
$user_stmt->execute();
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Lấy danh sách đơn hàng
$orders_query = "SELECT o.*, p.title as product_name, p.icon, p.icon_color, pp.duration_type 
                FROM orders o 
                JOIN licenses l ON o.license_id = l.id 
                JOIN products p ON o.product_id = p.id 
                JOIN product_pricing pp ON o.pricing_id = pp.id 
                WHERE l.customer_email = ? 
                ORDER BY o.created_at DESC";
$orders_stmt = $db->prepare($orders_query);
$orders_stmt->bindParam(1, $user['email']);
$orders_stmt->execute();
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

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

<div class="flex min-h-screen bg-gray-900">
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1">
        <div class="py-8 px-4 md:px-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-white">Đơn hàng của tôi</h1>
                
                <!-- Mobile menu button -->
                <button class="md:hidden bg-gray-800 p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none" id="mobile-menu-button">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <?php if (count($orders) > 0): ?>
            <div class="overflow-x-auto bg-gray-800 rounded-lg border border-gray-700">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-gray-400 border-b border-gray-700">
                            <th class="p-4">Mã đơn hàng</th>
                            <th class="p-4">Sản phẩm</th>
                            <th class="p-4">Thời hạn</th>
                            <th class="p-4">Số tiền</th>
                            <th class="p-4">Trạng thái</th>
                            <th class="p-4">Ngày tạo</th>
                            <th class="p-4">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr class="border-b border-gray-700 hover:bg-gray-700">
                            <td class="p-4 text-white"><?php echo htmlspecialchars($order['order_code']); ?></td>
                            <td class="p-4">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-opacity-20 flex items-center justify-center mr-3" style="background-color: <?php echo $order['icon_color']; ?>20; color: <?php echo $order['icon_color']; ?>">
                                        <i class="fas fa-<?php echo $order['icon']; ?>"></i>
                                    </div>
                                    <span class="text-white"><?php echo htmlspecialchars($order['product_name']); ?></span>
                                </div>
                            </td>
                            <td class="p-4 text-white"><?php echo getDurationLabel($order['duration_type']); ?></td>
                            <td class="p-4 text-white"><?php echo number_format($order['amount'], 0, ',', '.'); ?> VND</td>
                            <td class="p-4">
                                <?php
                                switch ($order['status']) {
                                    case 'completed':
                                        echo '<span class="px-2 py-1 bg-green-900 text-green-300 rounded-full text-xs">Hoàn thành</span>';
                                        break;
                                    case 'pending':
                                        echo '<span class="px-2 py-1 bg-yellow-900 text-yellow-300 rounded-full text-xs">Chờ thanh toán</span>';
                                        break;
                                    case 'cancelled':
                                        echo '<span class="px-2 py-1 bg-red-900 text-red-300 rounded-full text-xs">Đã hủy</span>';
                                        break;
                                    default:
                                        echo '<span class="px-2 py-1 bg-gray-700 text-gray-300 rounded-full text-xs">' . htmlspecialchars($order['status']) . '</span>';
                                }
                                ?>
                            </td>
                            <td class="p-4 text-gray-400"><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                            <td class="p-4">
                                <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="text-cyan-500 hover:text-cyan-400">
                                    <i class="fas fa-eye"></i> Xem
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 text-center">
                <div class="w-16 h-16 rounded-full bg-gray-700 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shopping-cart text-gray-500 text-2xl"></i>
                </div>
                <h2 class="text-xl font-semibold text-white mb-2">Bạn chưa có đơn hàng nào</h2>
                <p class="text-gray-400 mb-6">Hãy mua sản phẩm để kích hoạt license và sử dụng các tính năng đầy đủ.</p>
                <a href="products.php" class="bg-cyan-600 hover:bg-cyan-700 text-white px-6 py-3 rounded-lg transition-colors inline-block">
                    Xem sản phẩm
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const closeSidebarButton = document.getElementById('close-sidebar');
    const mobileSidebar = document.getElementById('mobile-sidebar');
    
    mobileMenuButton.addEventListener('click', function() {
        mobileSidebar.classList.remove('translate-x-full');
    });
    
    closeSidebarButton.addEventListener('click', function() {
        mobileSidebar.classList.add('translate-x-full');
    });
});
</script>

<?php include 'includes/footer.php'; ?>