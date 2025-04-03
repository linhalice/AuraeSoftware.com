<?php
require_once 'config/database.php';
include 'includes/header.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Bạn cần đăng nhập để truy cập trang này.";
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

// Đếm số license
$license_query = "SELECT COUNT(*) as total_licenses FROM licenses WHERE customer_email = ?";
$license_stmt = $db->prepare($license_query);
$license_stmt->bindParam(1, $user['email']);
$license_stmt->execute();
$license_count = $license_stmt->fetch(PDO::FETCH_ASSOC)['total_licenses'];

// Đếm số đơn hàng
$order_query = "SELECT COUNT(*) as total_orders FROM orders o 
                JOIN licenses l ON o.license_id = l.id 
                WHERE l.customer_email = ?";
$order_stmt = $db->prepare($order_query);
$order_stmt->bindParam(1, $user['email']);
$order_stmt->execute();
$order_count = $order_stmt->fetch(PDO::FETCH_ASSOC)['total_orders'];

// Lấy các license mới nhất
$recent_licenses_query = "SELECT l.*, p.title as product_name FROM licenses l 
                         JOIN products p ON l.product_id = p.id 
                         WHERE l.customer_email = ? 
                         ORDER BY l.created_at DESC LIMIT 5";
$recent_licenses_stmt = $db->prepare($recent_licenses_query);
$recent_licenses_stmt->bindParam(1, $user['email']);
$recent_licenses_stmt->execute();
$recent_licenses = $recent_licenses_stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy các đơn hàng mới nhất
$recent_orders_query = "SELECT o.*, p.title as product_name FROM orders o 
                       JOIN licenses l ON o.license_id = l.id 
                       JOIN products p ON o.product_id = p.id 
                       WHERE l.customer_email = ? 
                       ORDER BY o.created_at DESC LIMIT 5";
$recent_orders_stmt = $db->prepare($recent_orders_query);
$recent_orders_stmt->bindParam(1, $user['email']);
$recent_orders_stmt->execute();
$recent_orders = $recent_orders_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex min-h-screen bg-gray-900">
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1">
        <div class="py-8 px-4 md:px-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-white">Bảng điều khiển</h1>
                
                <!-- Mobile menu button -->
                <button class="md:hidden bg-gray-800 p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none" id="mobile-menu-button">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <!-- Dashboard Content -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Card: Thông tin người dùng -->
                <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 rounded-full bg-cyan-500 bg-opacity-20 flex items-center justify-center text-cyan-500 mr-4">
                            <i class="fas fa-user text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-white"><?php echo htmlspecialchars($user['name']); ?></h2>
                            <p class="text-gray-400"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                    </div>
                    <div class="border-t border-gray-700 pt-4">
                        <a href="profile.php" class="text-cyan-500 hover:text-cyan-400 flex items-center">
                            <i class="fas fa-edit mr-2"></i> Chỉnh sửa thông tin
                        </a>
                    </div>
                </div>
                
                <!-- Card: Licenses -->
                <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 rounded-full bg-cyan-500 bg-opacity-20 flex items-center justify-center text-cyan-500 mr-4">
                            <i class="fas fa-key text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-white">Licenses</h2>
                            <p class="text-gray-400">Tổng số: <?php echo $license_count; ?></p>
                        </div>
                    </div>
                    <div class="border-t border-gray-700 pt-4">
                        <a href="my-licenses.php" class="text-cyan-500 hover:text-cyan-400 flex items-center">
                            <i class="fas fa-arrow-right mr-2"></i> Xem tất cả licenses
                        </a>
                    </div>
                </div>
                
                <!-- Card: Đơn hàng -->
                <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 rounded-full bg-cyan-500 bg-opacity-20 flex items-center justify-center text-cyan-500 mr-4">
                            <i class="fas fa-shopping-cart text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-white">Đơn hàng</h2>
                            <p class="text-gray-400">Tổng số: <?php echo $order_count; ?></p>
                        </div>
                    </div>
                    <div class="border-t border-gray-700 pt-4">
                        <a href="my-orders.php" class="text-cyan-500 hover:text-cyan-400 flex items-center">
                            <i class="fas fa-arrow-right mr-2"></i> Xem tất cả đơn hàng
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Recent Licenses -->
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700 mb-8">
                <h2 class="text-xl font-semibold text-white mb-4">Licenses gần đây</h2>
                
                <?php if (count($recent_licenses) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-gray-400 border-b border-gray-700">
                                <th class="pb-3 pr-4">License Key</th>
                                <th class="pb-3 pr-4">Sản phẩm</th>
                                <th class="pb-3 pr-4">Trạng thái</th>
                                <th class="pb-3">Ngày tạo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_licenses as $license): ?>
                            <tr class="border-b border-gray-700">
                                <td class="py-3 pr-4 text-white"><?php echo htmlspecialchars($license['license_key']); ?></td>
                                <td class="py-3 pr-4 text-white"><?php echo htmlspecialchars($license['product_name']); ?></td>
                                <td class="py-3 pr-4">
                                    <?php
                                    switch ($license['status']) {
                                        case 'active':
                                            echo '<span class="px-2 py-1 bg-green-900 text-green-300 rounded-full text-xs">Đã kích hoạt</span>';
                                            break;
                                        case 'pending':
                                            echo '<span class="px-2 py-1 bg-yellow-900 text-yellow-300 rounded-full text-xs">Chờ kích hoạt</span>';
                                            break;
                                        case 'expired':
                                            echo '<span class="px-2 py-1 bg-red-900 text-red-300 rounded-full text-xs">Hết hạn</span>';
                                            break;
                                        default:
                                            echo '<span class="px-2 py-1 bg-gray-700 text-gray-300 rounded-full text-xs">' . htmlspecialchars($license['status']) . '</span>';
                                    }
                                    ?>
                                </td>
                                <td class="py-3 text-gray-400"><?php echo date('d/m/Y', strtotime($license['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-gray-400">Bạn chưa có license nào.</p>
                <?php endif; ?>
                
                <div class="mt-4">
                    <a href="my-licenses.php" class="text-cyan-500 hover:text-cyan-400 flex items-center">
                        <i class="fas fa-arrow-right mr-2"></i> Xem tất cả licenses
                    </a>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <h2 class="text-xl font-semibold text-white mb-4">Đơn hàng gần đây</h2>
                
                <?php if (count($recent_orders) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-gray-400 border-b border-gray-700">
                                <th class="pb-3 pr-4">Mã đơn hàng</th>
                                <th class="pb-3 pr-4">Sản phẩm</th>
                                <th class="pb-3 pr-4">Số tiền</th>
                                <th class="pb-3 pr-4">Trạng thái</th>
                                <th class="pb-3">Ngày tạo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                            <tr class="border-b border-gray-700">
                                <td class="py-3 pr-4 text-white"><?php echo htmlspecialchars($order['order_code']); ?></td>
                                <td class="py-3 pr-4 text-white"><?php echo htmlspecialchars($order['product_name']); ?></td>
                                <td class="py-3 pr-4 text-white"><?php echo number_format($order['amount'], 0, ',', '.'); ?> VND</td>
                                <td class="py-3 pr-4">
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
                                <td class="py-3 text-gray-400"><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-gray-400">Bạn chưa có đơn hàng nào.</p>
                <?php endif; ?>
                
                <div class="mt-4">
                    <a href="my-orders.php" class="text-cyan-500 hover:text-cyan-400 flex items-center">
                        <i class="fas fa-arrow-right mr-2"></i> Xem tất cả đơn hàng
                    </a>
                </div>
            </div>
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