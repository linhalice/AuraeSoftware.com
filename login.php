<?php
require_once 'config/database.php';
require_once 'functions/auth.php';

// Kiểm tra nếu đã đăng nhập thì chuyển hướng đến dashboard
if (isUserLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

// Xử lý đăng nhập
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validate dữ liệu
    if (empty($email)) {
        $error_message = 'Vui lòng nhập email';
    } elseif (empty($password)) {
        $error_message = 'Vui lòng nhập mật khẩu';
    } else {
        // Kết nối database
        $database = new Database();
        $db = $database->getConnection();
        
        // Kiểm tra thông tin đăng nhập
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Kiểm tra mật khẩu
            if (password_verify($password, $user['password'])) {
                // Đăng nhập thành công
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user'] = $user;
                
                // Chuyển hướng đến trang được yêu cầu hoặc dashboard
                $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'dashboard.php';
                header("Location: $redirect");
                exit();
            } else {
                $error_message = 'Mật khẩu không đúng';
            }
        } else {
            $error_message = 'Email không tồn tại trong hệ thống';
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
    <title>Đăng nhập - Aurae Software</title>
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
                    <a href="register.php" class="text-gray-300 hover:text-white mr-4">Đăng ký</a>
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
                        <h1 class="text-2xl font-bold text-white">Đăng nhập</h1>
                        <p class="text-gray-400 mt-2">Đăng nhập để truy cập vào tài khoản của bạn</p>
                    </div>
                    
                    <?php if (!empty($error_message)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-2 text-red-500"></i>
                            <p><?php echo $error_message; ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="login.php<?php echo !empty($redirect) ? '?redirect=' . $redirect . $redirect_params : ''; ?>">
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
                            <div class="flex items-center justify-between mb-2">
                                <label for="password" class="block text-sm font-medium text-gray-300">Mật khẩu</label>
                                <a href="forgot-password.php" class="text-sm text-cyan-500 hover:text-cyan-400">Quên mật khẩu?</a>
                            </div>
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
                        </div>
                        
                        <div class="flex items-center mb-6">
                            <input type="checkbox" id="remember" name="remember" class="h-4 w-4 text-cyan-500 focus:ring-cyan-500 border-gray-600 rounded bg-gray-700">
                            <label for="remember" class="ml-2 block text-sm text-gray-300">
                                Ghi nhớ đăng nhập
                            </label>
                        </div>
                        
                        <button type="submit" class="w-full bg-cyan-500 hover:bg-cyan-600 text-white font-medium py-3 px-4 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500">
                            Đăng nhập
                        </button>
                    </form>
                    
                    <div class="mt-6 text-center">
                        <p class="text-gray-400">
                            Chưa có tài khoản? 
                            <a href="register.php<?php echo !empty($redirect) ? '?redirect=' . $redirect . $redirect_params : ''; ?>" class="text-cyan-500 hover:text-cyan-400 font-medium">
                                Đăng ký ngay
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