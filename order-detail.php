<?php
require_once 'config/database.php';
require_once 'functions/auth.php';

// Kiểm tra đăng nhập
$user = checkUserLogin();

// Kiểm tra tham số
if (!isset($_GET['id'])) {
    header("Location: my-orders.php");
    exit();
}

$order_id = $_GET['id'];

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// Lấy thông tin đơn hàng
$query = "SELECT po.*, p.title as product_name, p.icon, p.icon_color, pp.duration_type 
          FROM payment_orders po
          JOIN products p ON po.product_id = p.id
          JOIN product_pricing pp ON po.pricing_id = pp.id
          WHERE po.id = ? AND po.user_id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $order_id);
$stmt->bindParam(2, $user['id']);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: my-orders.php");
    exit();
}

$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Lấy thông tin giao dịch
$query = "SELECT * FROM payment_transactions WHERE order_id = ? ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $order_id);
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy thông tin license
$query = "SELECT * FROM licenses WHERE order_id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $order_id);
$stmt->execute();
$license = $stmt->fetch(PDO::FETCH_ASSOC);

// Chuyển đổi loại thời hạn thành text
$duration_text = '';
switch ($order['duration_type']) {
    case '3_days': $duration_text = '3 ngày'; break;
    case '1_month': $duration_text = '1 tháng'; break;
    case '3_months': $duration_text = '3 tháng'; break;
    case '6_months': $duration_text = '6 tháng'; break;
    case '1_year': $duration_text = '1 năm'; break;
    case 'lifetime': $duration_text = 'Vĩnh viễn'; break;
}

include 'includes/header.php';
?>

<main class="flex-1 bg-gray-900">
    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row gap-6">
            <!-- Sidebar -->
            <div class="w-full md:w-1/4">
                <div class="bg-gray-800 rounded-lg shadow-lg p-6">
                    <div class="flex items-center space-x-4 mb-6">
                        <div class="w-12 h-12 rounded-full bg-cyan-500 flex items-center justify-center text-white text-xl font-bold">
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        </div>
                        <div>
                            <h3 class="text-white font-medium"><?php echo $user['name']; ?></h3>
                            <p class="text-gray-400 text-sm"><?php echo $user['email']; ?></p>
                        </div>
                    </div>
                    
                    <nav class="space-y-2">
                        <a href="dashboard.php" class="flex items-center space-x-2 text-gray-300 hover:bg-gray-700 p-3 rounded-md">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="my-licenses.php" class="flex items-center space-x-2 text-gray-300 hover:bg-gray-700 p-3 rounded-md">
                            <i class="fas fa-key"></i>
                            <span>Licenses của tôi</span>
                        </a>
                        <a href="my-orders.php" class="flex items-center space-x-2 text-white bg-gray-700 p-3 rounded-md">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Đơn hàng của tôi</span>
                        </a>
                        <a href="products.php" class="flex items-center space-x-2 text-gray-300 hover:bg-gray-700 p-3 rounded-md">
                            <i class="fas fa-shopping-bag"></i>
                            <span>Mua phần mềm</span>
                        </a>
                        <a href="profile.php" class="flex items-center space-x-2 text-gray-300 hover:bg-gray-700 p-3 rounded-md">
                            <i class="fas fa-user-cog"></i>
                            <span>Thông tin cá nhân</span>
                        </a>
                        <a href="logout.php" class="flex items-center space-x-2 text-gray-300 hover:bg-gray-700 p-3 rounded-md">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Đăng xuất</span>
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="w-full md:w-3/4">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold text-white">Chi tiết đơn hàng</h1>
                    <a href="my-orders.php" class="text-cyan-500 hover:text-cyan-400 flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i> Quay lại
                    </a>
                </div>
                
                <!-- Order Info -->
                <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden mb-6">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-12 w-12 flex items-center justify-center rounded-full bg-<?php echo $order['icon_color']; ?> bg-opacity-10 mr-4">
                                    <i class="fas fa-<?php echo $order['icon']; ?> text-<?php echo $order['icon_color']; ?>"></i>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-white"><?php echo $order['product_name']; ?></h3>
                                    <p class="text-gray-300">Gói <?php echo $duration_text; ?></p>
                                </div>
                            </div>
                            
                            <?php if ($order['status'] == 'completed'): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-2"></i> Hoàn thành
                            </span>
                            <?php elseif ($order['status'] == 'pending'): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                <i class="fas fa-clock mr-2"></i> Chờ thanh toán
                            </span>
                            <?php elseif ($order['status'] == 'cancelled'): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                <i class="fas fa-times-circle mr-2"></i> Đã hủy
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                <i class="fas fa-exclamation-circle mr-2"></i> Hết hạn
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div>
                                <h4 class="text-sm font-medium text-gray-400 uppercase mb-3">Thông tin đơn hàng</h4>
                                <div class="bg-gray-700 rounded-lg p-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-gray-400 text-sm">Mã đơn hàng:</p>
                                            <p class="text-white font-mono"><?php echo $order['order_code']; ?></p>
                                        </div>
                                        <div>
                                            <p class="text-gray-400 text-sm">Ngày tạo:</p>
                                            <p class="text-white"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-gray-400 text-sm">Số tiền:</p>
                                            <p class="text-cyan-400 font-semibold"><?php echo number_format($order['amount'], 0, ',', '.'); ?>đ</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-400 text-sm">Thời hạn:</p>
                                            <p class="text-white"><?php echo $duration_text; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($license): ?>
                            <div>
                                <h4 class="text-sm font-medium text-gray-400 uppercase mb-3">Thông tin license</h4>
                                <div class="bg-gray-700 rounded-lg p-4">
                                    <div class="grid grid-cols-1 gap-4">
                                        <div>
                                            <p class="text-gray-400 text-sm">License key:</p>
                                            <p class="text-white font-mono break-all"><?php echo $license['license_key']; ?></p>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <p class="text-gray-400 text-sm">Trạng thái:</p>
                                                <?php if ($license['status'] == 'active'): ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Hoạt động
                                                </span>
                                                <?php elseif ($license['status'] == 'pending'): ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    Chờ kích hoạt
                                                </span>
                                                <?php elseif ($license['status'] == 'expired'): ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    Hết hạn
                                                </span>
                                                <?php else: ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    Thu hồi
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <p class="text-gray-400 text-sm">Ngày hết hạn:</p>
                                                <p class="text-white"><?php echo $license['expiration_date'] ? date('d/m/Y', strtotime($license['expiration_date'])) : 'N/A'; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($order['status'] == 'pending'): ?>
                    <div class="bg-gray-700 p-6 border-t border-gray-600">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-white font-medium">Đơn hàng đang chờ thanh toán</p>
                                <p class="text-gray-400 text-sm">Hạn thanh toán: <?php echo date('d/m/Y H:i', strtotime($order['expired_at'])); ?></p>
                            </div>
                            <a href="payment.php?order_id=<?php echo $order['id']; ?>" class="px-4 py-2 bg-cyan-500 hover:bg-cyan-600 text-white rounded-md transition-colors">
                                Thanh toán ngay
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Transaction History -->
                <?php if (count($transactions) > 0): ?>
                <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-white mb-4">Lịch sử giao dịch</h2>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-700">
                                <thead class="bg-gray-700">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                            Mã giao dịch
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                            Số tiền
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                            Trạng thái
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                            Ngày giao dịch
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-gray-800 divide-y divide-gray-700">
                                    <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-white font-mono"><?php echo $transaction['transaction_code']; ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-cyan-400 font-medium"><?php echo number_format($transaction['amount'], 0, ',', '.'); ?>đ</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($transaction['status'] == 'verified'): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Đã xác nhận
                                            </span>
                                            <?php elseif ($transaction['status'] == 'pending'): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                Đang xử lý
                                            </span>
                                            <?php else: ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Từ chối
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                            <?php echo $transaction['transaction_date'] ? date('d/m/Y H:i', strtotime($transaction['transaction_date'])) : date('d/m/Y H:i', strtotime($transaction['created_at'])); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>