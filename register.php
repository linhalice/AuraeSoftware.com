<?php
require_once 'config/database.php';
require_once 'functions/auth.php';

// Kiểm tra nếu đã đăng nhập thì chuyển hướng đến dashboard
if (isUserLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

// Xử lý đăng ký
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    
    // Validate dữ liệu
    if (empty($name)) {
        $error_message = 'Vui lòng nhập họ tên';
    } elseif (empty($email)) {
        $error_message = 'Vui lòng nhập email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Email không hợp lệ';
    } elseif (empty($password)) {
        $error_message = 'Vui lòng nhập mật khẩu';
    } elseif (strlen($password) < 6) {
        $error_message = 'Mật khẩu phải có ít nhất 6 ký tự';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Xác nhận mật khẩu không khớp';
    } else {
        // Kết nối database
        $database = new Database();
        $db = $database->getConnection();
        
        // Kiểm tra email đã tồn tại chưa
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $error_message = 'Email đã được sử dụng bởi tài khoản khác';
        } else {
            // Mã hóa mật khẩu
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Thêm người dùng mới
            $query = "INSERT INTO users (name, email, password, phone, created_at) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $name);
            $stmt->bindParam(2, $email);
            $stmt->bindParam(3, $hashed_password);
            $stmt->bindParam(4, $phone);
            
            if ($stmt->execute()) {
                // Đăng ký thành công
                $success_message = 'Đăng ký tài khoản thành công! Bạn có thể đăng nhập ngay bây giờ.';
                
                // Tự động đăng nhập sau khi đăng ký
                $user_id = $db->lastInsertId();
                
                // Lấy thông tin user
                $query = "SELECT * FROM users WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $user_id);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Lưu thông tin đăng nhập vào session
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user'] = $user;
                
                // Chuyển hướng đến trang được yêu cầu hoặc dashboard
                $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'dashboard.php';
                header("Location: $redirect");
                exit();
            } else {
                $error_message = 'Có lỗi xảy ra, vui lòng thử lại sau';
            }
        }
    }
}

// Lấy thông tin redirect nếu có
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';
$redirect_params = '';

// Lấy các tham số khác nếu có
foreach ($_GET as $key => $value) {
    if ($key != 'redirect') {
        $redirect_params .= "&{$key}={$value}";
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - Aurae Software</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-gray-800 shadow-md">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <a href="index.php" class="flex items-center">
                    <span class="text-2xl font-bold text-cyan-500">Aurae</span>
                    <span class="text-xl font-medium ml-1">Software</span>
                </a>
                <div>
                    <a href="login.php" class="text-gray-300 hover:text-white mr-4">Đăng nhập</a>
                    <a href="products.php" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-md transition-colors">
                        Xem sản phẩm
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                <div class="p-8">
                    <div class="text-center mb-8">
                        <h1 class="text-2xl font-bold text-white">Đăng ký tài khoản</h1>
                        <p class="text-gray-400 mt-2">Tạo tài khoản mới để sử dụng dịch vụ của chúng tôi</p>
                    </div>
                    
                    <?php if (!empty($error_message)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-2 text-red-500"></i>
                            <p><?php echo $error_message; ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success_message)): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle mr-2 text-green-500"></i>
                            <p><?php echo $success_message; ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="register.php<?php echo !empty($redirect) ? '?redirect=' . $redirect . $redirect_params : ''; ?>">
                        <div class="mb-6">
                            <label for="name" class="block text-sm font-medium text-gray-300 mb-2">Họ tên</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-500"></i>
                                </div>
                                <input type="text" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" class="w-full pl-10 pr-4 py-3 bg-gray-700 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent" placeholder="Nguyễn Văn A">
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-envelope text-gray-500"></i>
                                </div>
                                <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" class="w-full pl-10 pr-4 py-3 bg-gray-700 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent" placeholder="your@email.com">
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label for="phone" class="block text-sm font-medium text-gray-300 mb-2">Số điện thoại (tùy chọn)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-phone text-gray-500"></i>
                                </div>
                                <input type="text" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" class="w-full pl-10 pr-4 py-3 bg-gray-700 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent" placeholder="0912345678">
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Mật khẩu</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-500"></i>
                                </div>
                                <input type="password" id="password" name="password" class="w-full pl-10 pr-4 py-3 bg-gray-700 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent" placeholder="••••••••">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <button type="button" id="toggle-password" class="text-gray-500 hover:text-gray-400 focus:outline-none">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <p class="mt-1 text-sm text-gray-400">Mật khẩu phải có ít nhất 6 ký tự</p>
                        </div>
                        
                        <div class="mb-6">
                            <label for="confirm_password" class="block text-sm font-medium text-gray-300 mb-2">Xác nhận mật khẩu</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-500"></i>
                                </div>
                                <input type="password" id="confirm_password" name="confirm_password" class="w-full pl-10 pr-4 py-3 bg-gray-700 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent" placeholder="••••••••">
                            </div>
                        </div>
                        
                        <div class="flex items-center mb-6">
                            <input type="checkbox" id="terms" name="terms" required class="h-4 w-4 text-cyan-500 focus:ring-cyan-500 border-gray-600 rounded bg-gray-700">
                            <label for="terms" class="ml-2 block text-sm text-gray-300">
                                Tôi đồng ý với <a href="#" class="text-cyan-500 hover:text-cyan-400">Điều khoản dịch vụ</a> và <a href="#" class="text-cyan-500 hover:text-cyan-400">Chính sách bảo mật</a>
                            </label>
                        </div>
                        
                        <button type="submit" class="w-full bg-cyan-500 hover:bg-cyan-600 text-white font-medium py-3 px-4 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500">
                            Đăng ký
                        </button>
                    </form>
                    
                    <div class="mt-6 text-center">
                        <p class="text-gray-400">
                            Đã có tài khoản? 
                            <a href="login.php<?php echo !empty($redirect) ? '?redirect=' . $redirect . $redirect_params : ''; ?>" class="text-cyan-500 hover:text-cyan-400 font-medium">
                                Đăng nhập
                            </a>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="mt-8 text-center">
                <p class="text-gray-500 text-sm">
                    &copy; <?php echo date('Y'); ?> Aurae Software. Tất cả quyền được bảo lưu.
                </p>
            </div>
        </div>
    </main>

    <script>
        // Toggle password visibility
        document.getElementById('toggle-password').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>