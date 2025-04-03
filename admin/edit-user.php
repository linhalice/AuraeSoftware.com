<?php
require_once '../config/database.php';
require_once 'includes/functions.php';
include 'includes/admin-header.php';

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// Kiểm tra ID người dùng
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$user_id = $_GET['id'];

// Lấy thông tin người dùng
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $user_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: users.php");
    exit();
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Xử lý form chỉnh sửa người dùng
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $status = $_POST['status'];
    $password = trim($_POST['password']);
    
    // Validate dữ liệu
    $errors = [];
    
    if (empty($email)) {
        $errors[] = "Email không được để trống";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email không hợp lệ";
    } else {
        // Kiểm tra email đã tồn tại chưa (trừ email hiện tại)
        $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(1, $email);
        $check_stmt->bindParam(2, $user_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $errors[] = "Email đã tồn tại";
        }
    }
    
    if (empty($name)) {
        $errors[] = "Họ tên không được để trống";
    }
    
    if (!empty($password) && strlen($password) < 6) {
        $errors[] = "Mật khẩu phải có ít nhất 6 ký tự";
    }
    
    // Nếu không có lỗi, cập nhật thông tin người dùng
    if (empty($errors)) {
        // Chuẩn bị câu truy vấn
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET email = ?, name = ?, phone = ?, status = ?, password = ?, updated_at = NOW() WHERE id = ?";
        } else {
            $query = "UPDATE users SET email = ?, name = ?, phone = ?, status = ?, updated_at = NOW() WHERE id = ?";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->bindParam(2, $name);
        $stmt->bindParam(3, $phone);
        $stmt->bindParam(4, $status);
        
        if (!empty($password)) {
            $stmt->bindParam(5, $hashed_password);
            $stmt->bindParam(6, $user_id);
        } else {
            $stmt->bindParam(5, $user_id);
        }
        
        if ($stmt->execute()) {
            // Ghi log hoạt động
            logAdminActivity($db, $_SESSION['admin_id'], "Cập nhật thông tin người dùng: {$user['username']} (ID: $user_id)");
            
            // Tạo thông báo
            createNotification(
                $db, 
                'user', 
                'Thông tin người dùng đã được cập nhật', 
                "Thông tin người dùng {$user['username']} đã được cập nhật.", 
                "users.php"
            );
            
            $success_message = "Thông tin người dùng đã được cập nhật thành công!";
            
            // Cập nhật lại thông tin người dùng
            $query = "SELECT * FROM users WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $user_id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error_message = "Có lỗi xảy ra khi cập nhật thông tin người dùng!";
        }
    }
}
?>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="flex justify-between items-center p-6 border-b">
        <h1 class="text-xl font-semibold text-gray-800">Chỉnh sửa người dùng</h1>
        <a href="users.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center transition-colors duration-200">
            <i class="fas fa-arrow-left mr-2"></i> Quay lại
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
    
    <?php if (!empty($errors)): ?>
        <div class="mx-6 mt-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
            <div class="flex items-start">
                <i class="fas fa-exclamation-circle mr-2 mt-0.5 text-red-500"></i>
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="p-6">
        <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <div class="flex items-center">
                <div class="h-16 w-16 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-semibold text-xl mr-4">
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                </div>
                <div>
                    <h2 class="text-lg font-medium text-gray-900"><?php echo $user['name']; ?></h2>
                    <p class="text-gray-500"><?php echo $user['username']; ?></p>
                    <p class="text-sm text-gray-500 mt-1">Đăng ký: <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
        </div>
        
        <form method="POST" action="edit-user.php?id=<?php echo $user_id; ?>" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Tên đăng nhập</label>
                    <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled class="w-full rounded-lg border border-gray-300 px-3 py-2 bg-gray-100 text-gray-600">
                    <p class="text-xs text-gray-500 mt-1">Tên đăng nhập không thể thay đổi</p>
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu mới</label>
                    <input type="password" id="password" name="password" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Để trống nếu không muốn thay đổi mật khẩu</p>
                </div>
                
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Họ tên <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>
                
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại</label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>
                
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Trạng thái <span class="text-red-500">*</span></label>
                    <select id="status" name="status" required class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <option value="active" <?php echo ($user['status'] == 'active') ? 'selected' : ''; ?>>Hoạt động</option>
                        <option value="inactive" <?php echo ($user['status'] == 'inactive') ? 'selected' : ''; ?>>Không hoạt động</option>
                        <option value="banned" <?php echo ($user['status'] == 'banned') ? 'selected' : ''; ?>>Bị cấm</option>
                    </select>
                </div>
            </div>
            
            <div class="flex justify-end space-x-4 mt-6">
                <a href="users.php" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg transition-colors duration-200">
                    Hủy bỏ
                </a>
                <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors duration-200">
                    <i class="fas fa-save mr-2"></i> Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/admin-footer.php'; ?>