<?php
require_once '../config/database.php';
require_once 'includes/functions.php'; // Đảm bảo include này ở đầu file
include 'includes/admin-header.php';

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// Xử lý thêm license mới
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $user_id = !empty($_POST['user_id']) ? $_POST['user_id'] : null;
    $duration_type = $_POST['duration_type'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];
    
    // Tạo license key sử dụng hàm từ file functions.php
    $license_key = generateLicenseKey();
    
    // Tạo activation code nếu được yêu cầu
    $activation_code = null;
    if (isset($_POST['generate_activation_code']) && $_POST['generate_activation_code'] == 1) {
        $activation_code = 'ACT-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 12));
    }
    
    // Tính ngày hết hạn sử dụng hàm từ file functions.php
    $expires_at = null;
    if ($duration_type != 'lifetime') {
        $expires_at = calculateExpirationDate($duration_type);
    }
    
    // Thêm license mới
    $query = "INSERT INTO licenses (
                license_key, 
                product_id, 
                user_id, 
                status, 
                duration_type, 
                activation_code, 
                created_at, 
                expires_at, 
                notes
            ) VALUES (
                ?, ?, ?, ?, ?, ?, NOW(), ?, ?
            )";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $license_key);
    $stmt->bindParam(2, $product_id);
    $stmt->bindParam(3, $user_id);
    $stmt->bindParam(4, $status);
    $stmt->bindParam(5, $duration_type);
    $stmt->bindParam(6, $activation_code);
    $stmt->bindParam(7, $expires_at);
    $stmt->bindParam(8, $notes);
    
    if ($stmt->execute()) {
        $license_id = $db->lastInsertId();
        
        // Ghi log hoạt động
        logAdminActivity($db, $_SESSION['admin_id'], "Thêm license mới: $license_key (ID: $license_id)");
        
        $success_message = "License đã được tạo thành công!";
    } else {
        $error_message = "Không thể tạo license!";
    }
}

// Lấy danh sách sản phẩm
$products_query = "SELECT id, title FROM products ORDER BY title ASC";
$products_stmt = $db->prepare($products_query);
$products_stmt->execute();
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách người dùng
$users_query = "SELECT id, name, email FROM users ORDER BY name ASC";
$users_stmt = $db->prepare($users_query);
$users_stmt->execute();
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Thêm License Mới</h1>
        <a href="licenses.php" class="text-primary-600 hover:text-primary-800 flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Quay lại danh sách
        </a>
    </div>
    
    <?php if (!empty($success_message)): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2 text-green-500"></i>
            <p><?php echo $success_message; ?></p>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2 text-red-500"></i>
            <p><?php echo $error_message; ?></p>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-6">
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 mb-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Thông tin license</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>License key sẽ được tạo tự động. Bạn có thể chọn tạo mã kích hoạt nếu cần thiết.</p>
                            <p class="mt-1">Nếu chọn trạng thái "Đã kích hoạt", license sẽ được kích hoạt ngay lập tức.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <form method="POST" action="add-license.php">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="product_id" class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm <span class="text-red-500">*</span></label>
                        <select id="product_id" name="product_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                            <option value="">Chọn sản phẩm</option>
                            <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['id']; ?>">
                                <?php echo htmlspecialchars($product['title']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">Người dùng</label>
                        <select id="user_id" name="user_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                            <option value="">Chọn người dùng (không bắt buộc)</option>
                            <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>">
                                <?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="duration_type" class="block text-sm font-medium text-gray-700 mb-1">Thời hạn <span class="text-red-500">*</span></label>
                        <select id="duration_type" name="duration_type" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                            <option value="3_days">3 ngày</option>
                            <option value="1_month">1 tháng</option>
                            <option value="3_months">3 tháng</option>
                            <option value="6_months">6 tháng</option>
                            <option value="1_year">1 năm</option>
                            <option value="lifetime">Vĩnh viễn</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Trạng thái <span class="text-red-500">*</span></label>
                        <select id="status" name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                            <option value="pending">Chờ kích hoạt</option>
                            <option value="active">Đã kích hoạt</option>
                        </select>
                    </div>
                    
                    <div class="md:col-span-2">
                        <div class="flex items-center">
                            <input type="checkbox" id="generate_activation_code" name="generate_activation_code" value="1" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="generate_activation_code" class="ml-2 block text-sm text-gray-700">
                                Tạo mã kích hoạt
                            </label>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Mã kích hoạt có thể được sử dụng để kích hoạt license từ phần mềm.</p>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                        <textarea id="notes" name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"></textarea>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <a href="licenses.php" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg mr-2">
                        Hủy
                    </a>
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-lg">
                        <i class="fas fa-save mr-2"></i> Lưu License
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/admin-footer.php'; ?>