<?php
session_start();
require_once '../config/database.php';

// Nếu đã đăng nhập, chuyển hướng đến trang dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Kiểm tra thông tin đăng nhập
    $query = "SELECT id, username, password FROM admins WHERE username = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $username);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        

        if ($password === $row['password']) {
            // Đăng nhập thành công
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['admin_username'] = $row['username'];
            
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Mật khẩu không chính xác";
        }
    } else {
        $error = "Tài khoản không tồn tại";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Aurae Software</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="bg-gray-900 text-white flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md">
        <div class="flex justify-center mb-8">
            <div class="flex items-center gap-2">
                <div class="h-10 w-10 rounded bg-cyan-500 flex items-center justify-center">
                    <svg viewBox="0 0 24 24" class="h-7 w-7 text-gray-900">
                        <polygon points="12 2 22 8.5 22 15.5 12 22 2 15.5 2 8.5 12 2" fill="currentColor" />
                    </svg>
                </div>
                <span class="text-2xl font-bold tracking-tight text-cyan-500">AURAE ADMIN</span>
            </div>
        </div>
        
        <div class="bg-gray-800 shadow-md rounded-lg px-8 pt-6 pb-8 mb-4">
            <h1 class="text-xl font-bold mb-6 text-center">Đăng Nhập Quản Trị</h1>
            
            <?php if (!empty($error)): ?>
                <div class="bg-red-500 bg-opacity-20 text-red-400 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="login.php" class="admin-form">
                <div class="mb-4">
                    <label for="username" class="block text-gray-300 mb-2">Tên đăng nhập</label>
                    <input type="text" id="username" name="username" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:border-cyan-500" required>
                </div>
                
                <div class="mb-6">
                    <label for="password" class="block text-gray-300 mb-2">Mật khẩu</label>
                    <input type="password" id="password" name="password" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:border-cyan-500" required>
                </div>
                
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-cyan-500 hover:bg-cyan-600 text-gray-900 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full">
                        Đăng nhập
                    </button>
                </div>
            </form>
        </div>
        
        <p class="text-center text-gray-500 text-xs">
            &copy; <?php echo date('Y'); ?> Aurae Software. Tất cả các quyền được bảo lưu.
        </p>
    </div>
</body>
</html>