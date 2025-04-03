<?php
require_once '../config/database.php';
include 'includes/admin-header.php';

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// Lấy thông tin license
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    
    // Kích hoạt license nếu có tham số activate
    if (isset($_GET['activate']) && $_GET['activate'] == 1) {
        // Kiểm tra xem đã có ngày hết hạn chưa
        $check_query = "SELECT expiration_date FROM licenses WHERE id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(1, $id);
        $check_stmt->execute();
        $license_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (empty($license_data['expiration_date'])) {
            // Nếu chưa có ngày hết hạn, chuyển hướng đến trang kích hoạt
            header("Location: activate-license.php?id=" . $id);
            exit();
        } else {
            // Nếu đã có ngày hết hạn, kích hoạt luôn
            $query = "UPDATE licenses SET status = 'active', activation_date = NOW() WHERE id = ? AND status = 'pending'";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $id);
            
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $success_message = "License đã được kích hoạt thành công!";
            }
        }
    }
    
    $query = "SELECT * FROM licenses WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $id);
    $stmt->execute();
    
    $license = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$license) {
        header("Location: licenses.php");
        exit();
    }
} else {
    header("Location: licenses.php");
    exit();
}

// Lấy danh sách sản phẩm
$query = "SELECT id, title FROM products ORDER BY title ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$products = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $products[] = $row;
}

// Xử lý cập nhật license
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $license_key = $_POST['license_key'];
    $product_id = $_POST['product_id'];
    $customer_name = $_POST['customer_name'];
    $customer_email = $_POST['customer_email'];
    $status = $_POST['status'];
    $expiration_date = !empty($_POST['expiration_date']) ? $_POST['expiration_date'] : null;
    
    // Kiểm tra license key đã tồn tại chưa (nếu thay đổi)
    if ($license_key != $license['license_key']) {
        $check_query = "SELECT id FROM licenses WHERE license_key = ? AND id != ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(1, $license_key);
        $check_stmt->bindParam(2, $id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $error_message = "License key đã tồn tại!";
        }
    }
    
    if (!isset($error_message)) {
        // Cập nhật thông tin license
        $query = "UPDATE licenses SET license_key = ?, product_id = ?, customer_name = ?, customer_email = ?, status = ?, expiration_date = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $license_key);
        $stmt->bindParam(2, $product_id);
        $stmt->bindParam(3, $customer_name);
        $stmt->bindParam(4, $customer_email);
        $stmt->bindParam(5, $status);
        $stmt->bindParam(6, $expiration_date);
        $stmt->bindParam(7, $id);
        
        if ($stmt->execute()) {
            // Cập nhật ngày kích hoạt nếu trạng thái thay đổi
            if ($status == 'active' && ($license['status'] != 'active' || $license['activation_date'] == null)) {
                $query = "UPDATE licenses SET activation_date = NOW() WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $id);
                $stmt->execute();
            }
            
            $success_message = "License đã được cập nhật thành công!";
            
            // Cập nhật lại thông tin license
            $query = "SELECT * FROM licenses WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $id);
            $stmt->execute();
            $license = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error_message = "Không thể cập nhật license!";
        }
    }
}
?>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="flex justify-between items-center p-6 border-b">
        <h1 class="text-xl font-semibold text-gray-800">Chỉnh sửa license</h1>
        <a href="licenses.php" class="text-blue-600 hover:text-blue-800 flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Quay lại danh sách
        </a>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div class="mx-6 mt-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2 text-green-500"></i>
                <p><?php echo $success_message; ?></p>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="mx-6 mt-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2 text-red-500"></i>
                <p><?php echo $error_message; ?></p>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white p-4 rounded-lg border border-gray-200 flex items-center">
                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                    <i class="fas fa-key text-blue-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase">License ID</p>
                    <p class="text-sm font-medium text-gray-900">#<?php echo $license['id']; ?></p>
                </div>
            </div>
            
            <div class="bg-white p-4 rounded-lg border border-gray-200 flex items-center">
                <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center mr-3">
                    <i class="fas fa-calendar-alt text-green-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase">Ngày tạo</p>
                    <p class="text-sm font-medium text-gray-900"><?php echo date('d/m/Y H:i', strtotime($license['created_at'])); ?></p>
                </div>
            </div>
            
            <div class="bg-white p-4 rounded-lg border border-gray-200 flex items-center">
                <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center mr-3">
                    <i class="fas fa-clock text-purple-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase">Trạng thái</p>
                    <p class="text-sm font-medium">
                        <?php
                        switch ($license['status']) {
                            case 'active':
                                echo '<span class="text-green-600">Đã kích hoạt</span>';
                                break;
                            case 'pending':
                                echo '<span class="text-yellow-600">Chờ kích hoạt</span>';
                                break;
                            case 'expired':
                                echo '<span class="text-red-600">Hết hạn</span>';
                                break;
                            case 'revoked':
                                echo '<span class="text-gray-600">Đã thu hồi</span>';
                                break;
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>
        
        <form method="POST" action="edit-license.php?id=<?php echo $id; ?>" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="license_key" class="block text-sm font-medium text-gray-700 mb-1">License Key</label>
                    <input type="text" id="license_key" name="license_key" value="<?php echo $license['license_key']; ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                </div>
                
                <div>
                    <label for="product_id" class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm</label>
                    <select id="product_id" name="product_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['id']; ?>" <?php echo ($product['id'] == $license['product_id']) ? 'selected' : ''; ?>><?php echo $product['title']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-1">Tên khách hàng</label>
                    <input type="text" id="customer_name" name="customer_name" value="<?php echo $license['customer_name']; ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                </div>
                
                <div>
                    <label for="customer_email" class="block text-sm font-medium text-gray-700 mb-1">Email khách hàng</label>
                    <input type="email" id="customer_email" name="customer_email" value="<?php echo $license['customer_email']; ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                </div>
                
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                    <select id="status" name="status" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        <option value="pending" <?php echo ($license['status'] == 'pending') ? 'selected' : ''; ?>>Chờ kích hoạt</option>
                        <option value="active" <?php echo ($license['status'] == 'active') ? 'selected' : ''; ?>>Đã kích hoạt</option>
                        <option value="expired" <?php echo ($license['status'] == 'expired') ? 'selected' : ''; ?>>Hết hạn</option>
                        <option value="revoked" <?php echo ($license['status'] == 'revoked') ? 'selected' : ''; ?>>Đã thu hồi</option>
                    </select>
                </div>
                
                <div>
                    <label for="expiration_date" class="block text-sm font-medium text-gray-700 mb-1">Ngày hết hạn</label>
                    <input type="date" id="expiration_date" name="expiration_date" value="<?php echo !empty($license['expiration_date']) ? date('Y-m-d', strtotime($license['expiration_date'])) : date('Y-m-d', strtotime('+1 year')); ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Thông tin thêm</label>
                    <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                        <?php if ($license['activation_date']): ?>
                            <p class="text-sm text-gray-600 flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                <span><strong>Ngày kích hoạt:</strong> <?php echo date('d/m/Y H:i:s', strtotime($license['activation_date'])); ?></span>
                            </p>
                        <?php endif; ?>
                        <?php if ($license['expiration_date']): ?>
                            <p class="text-sm text-gray-600 mt-1 flex items-center">
                                <i class="fas fa-calendar-times text-<?php echo (strtotime($license['expiration_date']) < time()) ? 'red' : 'blue'; ?>-500 mr-2"></i>
                                <span><strong>Ngày hết hạn:</strong> <?php echo date('d/m/Y', strtotime($license['expiration_date'])); ?></span>
                                <?php if (strtotime($license['expiration_date']) < time()): ?>
                                    <span class="ml-2 text-xs bg-red-100 text-red-800 px-2 py-0.5 rounded-full">Đã qua</span>
                                <?php else: ?>
                                    <span class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">
                                        <?php 
                                        $days_left = ceil((strtotime($license['expiration_date']) - time()) / (60 * 60 * 24));
                                        echo "Còn {$days_left} ngày";
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                        <?php if (!$license['activation_date'] && !$license['expiration_date']): ?>
                            <p class="text-sm text-gray-600 flex items-center">
                                <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                <span>License chưa được kích hoạt</span>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="pt-4 flex items-center space-x-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg flex items-center transition-colors duration-200">
                    <i class="fas fa-save mr-2"></i> Cập nhật license
                </button>
                
                <?php if ($license['status'] == 'pending'): ?>
                <a href="activate-license.php?id=<?php echo $id; ?>" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg flex items-center transition-colors duration-200">
                    <i class="fas fa-check mr-2"></i> Kích hoạt ngay
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<script>
    // Hiển thị/ẩn trường ngày hết hạn dựa trên trạng thái
    document.getElementById('status').addEventListener('change', function() {
        const expirationDateField = document.getElementById('expiration_date');
        const expirationDateLabel = document.querySelector('label[for="expiration_date"]');
        
        if (this.value === 'expired') {
            expirationDateLabel.textContent = 'Ngày hết hạn (đã qua)';
            expirationDateField.min = '';
            expirationDateField.max = new Date().toISOString().split('T')[0];
            if (expirationDateField.value > new Date().toISOString().split('T')[0]) {
                expirationDateField.value = new Date().toISOString().split('T')[0];
            }
        } else {
            expirationDateLabel.textContent = 'Ngày hết hạn';
            expirationDateField.min = '';
            expirationDateField.max = '';
        }
    });
</script>

<?php include 'includes/admin-footer.php'; ?>