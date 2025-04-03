<?php
require_once '../config/database.php';
require_once 'includes/functions.php';
include 'includes/admin-header.php';

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// Lấy thông tin license
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    
    $query = "SELECT l.*, p.title as product_name FROM licenses l 
              JOIN products p ON l.product_id = p.id 
              WHERE l.id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $id);
    $stmt->execute();
    
    $license = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$license) {
        header("Location: licenses.php");
        exit();
    }
    
    // Kiểm tra trạng thái license - cho phép kích hoạt nếu là pending hoặc expired
    if ($license['status'] != 'pending' && $license['status'] != 'expired') {
        $_SESSION['error_message'] = "License này không thể kích hoạt vì đang ở trạng thái " . $license['status'];
        header("Location: licenses.php");
        exit();
    }
} else {
    header("Location: licenses.php");
    exit();
}

// Xử lý kích hoạt license
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $duration_type = $_POST['duration_type'];
    $expiration_date = null;
    
    // Tính ngày hết hạn dựa trên loại thời hạn
    if ($duration_type != 'lifetime') {
        // Sử dụng hàm calculateExpirationDate nếu có, nếu không thì tính thủ công
        if (function_exists('calculateExpirationDate')) {
            $expiration_date = calculateExpirationDate($duration_type);
        } else {
            // Tính thủ công
            $now = new DateTime();
            switch ($duration_type) {
                case '3_days':
                    $now->add(new DateInterval('P3D'));
                    break;
                case '1_month':
                    $now->add(new DateInterval('P1M'));
                    break;
                case '3_months':
                    $now->add(new DateInterval('P3M'));
                    break;
                case '6_months':
                    $now->add(new DateInterval('P6M'));
                    break;
                case '1_year':
                    $now->add(new DateInterval('P1Y'));
                    break;
                default:
                    // Mặc định 1 tháng
                    $now->add(new DateInterval('P1M'));
            }
            $expiration_date = $now->format('Y-m-d H:i:s');
        }
    }
    
    // Chuẩn bị ghi chú
    $admin_note = "Kích hoạt bởi admin: " . (isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin') . " vào " . date('d/m/Y H:i:s');
    
    // Kiểm tra xem đã có ghi chú chưa
    if (!empty($license['notes'])) {
        $notes = $license['notes'] . "\n" . $admin_note;
    } else {
        $notes = $admin_note;
    }
    
    // Cập nhật license
    $query = "UPDATE licenses SET 
                status = 'active', 
                duration_type = ?, 
                activated_at = NOW(), 
                expires_at = ?, 
                notes = ?
              WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $duration_type);
    $stmt->bindParam(2, $expiration_date);
    $stmt->bindParam(3, $notes);
    $stmt->bindParam(4, $id);
    
    if ($stmt->execute()) {
        // Ghi log hoạt động nếu hàm tồn tại
        if (function_exists('logAdminActivity') && isset($_SESSION['admin_id'])) {
            logAdminActivity($db, $_SESSION['admin_id'], "Kích hoạt license: {$license['license_key']} (ID: $id) với thời hạn: " . $duration_type);
        }
        
        $success_message = "License đã được kích hoạt thành công!";
    } else {
        $error_message = "Không thể kích hoạt license!";
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

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Kích hoạt License</h1>
        <a href="licenses.php" class="text-primary-600 hover:text-primary-800 flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Quay lại danh sách
        </a>
    </div>
    
    <?php if (isset($success_message)): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2 text-green-500"></i>
            <p><?php echo $success_message; ?></p>
        </div>
        <div class="mt-2">
            <a href="licenses.php" class="text-green-700 font-medium hover:underline">Quay lại danh sách license</a>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2 text-red-500"></i>
            <p><?php echo $error_message; ?></p>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!isset($success_message)): ?>
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-6">
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 mb-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Thông tin kích hoạt</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>Bạn đang kích hoạt license <strong><?php echo htmlspecialchars($license['license_key']); ?></strong> cho sản phẩm <strong><?php echo htmlspecialchars($license['product_name']); ?></strong>.</p>
                            <p class="mt-1">Vui lòng chọn thời hạn cho license này. Sau khi kích hoạt, license sẽ có hiệu lực ngay lập tức.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-4 rounded-lg border border-gray-200 mb-6">
                <h3 class="text-md font-medium text-gray-800 mb-3">Thông tin license</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">License Key:</p>
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($license['license_key']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Sản phẩm:</p>
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($license['product_name']); ?></p>
                    </div>
                    <?php if (!empty($license['customer_name'])): ?>
                    <div>
                        <p class="text-sm text-gray-500">Khách hàng:</p>
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($license['customer_name']); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($license['customer_email'])): ?>
                    <div>
                        <p class="text-sm text-gray-500">Email:</p>
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($license['customer_email']); ?></p>
                    </div>
                    <?php endif; ?>
                    <div>
                        <p class="text-sm text-gray-500">Ngày tạo:</p>
                        <p class="text-sm font-medium text-gray-900"><?php echo date('d/m/Y H:i', strtotime($license['created_at'])); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Trạng thái hiện tại:</p>
                        <p class="text-sm font-medium text-yellow-600"><?php echo $license['status'] == 'pending' ? 'Chờ kích hoạt' : 'Hết hạn'; ?></p>
                    </div>
                </div>
            </div>
            
            <form method="POST" action="activate-license.php?id=<?php echo $id; ?>" class="space-y-6">
                <div>
                    <label for="duration_type" class="block text-sm font-medium text-gray-700 mb-1">Thời hạn license</label>
                    <select id="duration_type" name="duration_type" class="w-full md:w-1/2 rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent" required>
                        <option value="3_days">3 ngày</option>
                        <option value="1_month">1 tháng</option>
                        <option value="3_months">3 tháng</option>
                        <option value="6_months">6 tháng</option>
                        <option value="1_year" selected>1 năm</option>
                        <option value="lifetime">Vĩnh viễn</option>
                    </select>
                    <p class="mt-1 text-sm text-gray-500">Chọn thời hạn cho license này.</p>
                </div>
                
                <div class="pt-4">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg flex items-center transition-colors duration-200">
                        <i class="fas fa-check-circle mr-2"></i> Kích hoạt license
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/admin-footer.php'; ?>