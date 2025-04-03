<?php
require_once 'config/database.php';
include 'includes/header.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Bạn cần đăng nhập để xem thông tin cá nhân.";
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

// Xử lý cập nhật thông tin
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validate input
    if (empty($name)) {
        $error_message = "Vui lòng nhập họ tên.";
    } elseif (empty($email)) {
        $error_message = "Vui lòng nhập email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Email không hợp lệ.";
    } else {
        // Kiểm tra email đã tồn tại chưa (nếu thay đổi email)
        if ($email != $user['email']) {
            $check_email_query = "SELECT id FROM users WHERE email = ? AND id != ?";
            $check_email_stmt = $db->prepare($check_email_query);
            $check_email_stmt->bindParam(1, $email);
            $check_email_stmt->bindParam(2, $user_id);
            $check_email_stmt->execute();
            
            if ($check_email_stmt->rowCount() > 0) {
                $error_message = "Email đã được sử dụng bởi tài khoản khác.";
            }
        }
        
        // Nếu không có lỗi, tiến hành cập nhật
        if (empty($error_message)) {
            // Nếu có thay đổi mật khẩu
            if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
                // Kiểm tra mật khẩu hiện tại
                if (empty($current_password)) {
                    $error_message = "Vui lòng nhập mật khẩu hiện tại.";
                } elseif (!password_verify($current_password, $user['password'])) {
                    $error_message = "Mật khẩu hiện tại không đúng.";
                } elseif (empty($new_password)) {
                    $error_message = "Vui lòng nhập mật khẩu mới.";
                } elseif (strlen($new_password) < 6) {
                    $error_message = "Mật khẩu mới phải có ít nhất 6 ký tự.";
                } elseif ($new_password != $confirm_password) {
                    $error_message = "Xác nhận mật khẩu không khớp.";
                } else {
                    // Cập nhật thông tin và mật khẩu
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_query = "UPDATE users SET name = ?, email = ?, phone = ?, password = ? WHERE id = ?";
                    $update_stmt = $db->prepare($update_query);
                    $update_stmt->bindParam(1, $name);
                    $update_stmt->bindParam(2, $email);
                    $update_stmt->bindParam(3, $phone);
                    $update_stmt->bindParam(4, $hashed_password);
                    $update_stmt->bindParam(5, $user_id);
                    
                    if ($update_stmt->execute()) {
                        $success_message = "Thông tin cá nhân và mật khẩu đã được cập nhật thành công.";
                        // Cập nhật lại thông tin người dùng
                        $user_stmt->execute();
                        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
                    } else {
                        $error_message = "Đã xảy ra lỗi khi cập nhật thông tin.";
                    }
                }
            } else {
                // Chỉ cập nhật thông tin cá nhân
                $update_query = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(1, $name);
                $update_stmt->bindParam(2, $email);
                $update_stmt->bindParam(3, $phone);
                $update_stmt->bindParam(4, $user_id);
                
                if ($update_stmt->execute()) {
                    $success_message = "Thông tin cá nhân đã được cập nhật thành công.";
                    // Cập nhật lại thông tin người dùng
                    $user_stmt->execute();
                    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error_message = "Đã xảy ra lỗi khi cập nhật thông tin.";
                }
            }
        }
    }
}
?>

<div class="flex min-h-screen bg-gray-900">
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1">
        <div class="py-8 px-4 md:px-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-white">Thông tin cá nhân</h1>
                
                <!-- Mobile menu button -->
                <button class="md:hidden bg-gray-800 p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none" id="mobile-menu-button">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <?php if (!empty($success_message)): ?>
            <div class="bg-green-900 border border-green-700 text-green-100 px-4 py-3 rounded-lg mb-6">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span><?php echo $success_message; ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
            <div class="bg-red-900 border border-red-700 text-red-100 px-4 py-3 rounded-lg mb-6">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span><?php echo $error_message; ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                <div class="p-6">
                    <form method="POST" action="profile.php">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="name" class="block text-white font-medium mb-2">Họ tên</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-cyan-500" required>
                            </div>
                            
                            <div>
                                <label for="email" class="block text-white font-medium mb-2">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-cyan-500" required>
                            </div>
                            
                            <div>
                                <label for="phone" class="block text-white font-medium mb-2">Số điện thoại</label>
                                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-cyan-500">
                            </div>
                            
                            <div>
                                <label for="created_at" class="block text-white font-medium mb-2">Ngày đăng ký</label>
                                <input type="text" id="created_at" value="<?php echo date('d/m/Y', strtotime($user['created_at'])); ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white" readonly>
                            </div>
                        </div>
                        
                        <h2 class="text-xl font-semibold text-white mb-4">Đổi mật khẩu</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <div>
                                <label for="current_password" class="block text-white font-medium mb-2">Mật khẩu hiện tại</label>
                                <input type="password" id="current_password" name="current_password" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-cyan-500">
                            </div>
                            
                            <div>
                                <label for="new_password" class="block text-white font-medium mb-2">Mật khẩu mới</label>
                                <input type="password" id="new_password" name="new_password" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-cyan-500">
                            </div>
                            
                            <div>
                                <label for="confirm_password" class="block text-white font-medium mb-2">Xác nhận mật khẩu mới</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-cyan-500">
                            </div>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white px-6 py-3 rounded-lg transition-colors">
                                <i class="fas fa-save mr-2"></i> Lưu thay đổi
                            </button>
                        </div>
                    </form>
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