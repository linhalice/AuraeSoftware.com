<?php
// Bắt đầu session nếu chưa được bắt đầu
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Xác định trang hiện tại
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aurae Software - Phần mềm chuyên nghiệp</title>
    
    <!-- Favicon -->
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        body {
            background-color: #111827;
            color: #f3f4f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .badge {
            background-color: #06b6d4;
            color: #111827;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="flex flex-col min-h-screen">
    <!-- Header -->
    <header class="bg-gray-900 border-b border-gray-800">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <!-- Logo (visible only on mobile) -->
                <div class="md:hidden">
                    <a href="index.php" class="flex items-center">
                        <img src="assets/images/logo.png" alt="Logo" class="h-8">
                    </a>
                </div>
                
                <!-- Navigation -->
                <nav class="hidden md:flex items-center space-x-6">
                    <a href="index.php" class="text-gray-300 hover:text-white transition-colors">Trang chủ</a>
                    <a href="products.php" class="text-gray-300 hover:text-white transition-colors">Sản phẩm</a>
                    <a href="faq.php" class="text-gray-300 hover:text-white transition-colors">FAQ</a>
                    <a href="contact.php" class="text-gray-300 hover:text-white transition-colors">Liên hệ</a>
                </nav>
                
                <!-- User Actions -->
                <div class="flex items-center space-x-4">
                    <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
                    <!-- User Dropdown -->
                    <div class="relative group">
                        <button class="flex items-center text-gray-300 hover:text-white transition-colors focus:outline-none">
                            <span class="mr-2"><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Tài khoản'; ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 bg-gray-800 border border-gray-700 rounded-lg shadow-lg py-2 z-10 hidden group-hover:block">
                            <a href="dashboard.php" class="block px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors">
                                <i class="fas fa-tachometer-alt mr-2"></i> Bảng điều khiển
                            </a>
                            <a href="my-licenses.php" class="block px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors">
                                <i class="fas fa-key mr-2"></i> Licenses của tôi
                            </a>
                            <a href="my-orders.php" class="block px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors">
                                <i class="fas fa-shopping-cart mr-2"></i> Đơn hàng
                            </a>
                            <a href="profile.php" class="block px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors">
                                <i class="fas fa-user mr-2"></i> Thông tin cá nhân
                            </a>
                            <div class="border-t border-gray-700 my-2"></div>
                            <a href="logout.php" class="block px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors">
                                <i class="fas fa-sign-out-alt mr-2"></i> Đăng xuất
                            </a>
                        </div>
                    </div>
                    <?php else: ?>
                    <a href="login.php" class="text-gray-300 hover:text-white transition-colors">
                        <i class="fas fa-sign-in-alt mr-1"></i> Đăng nhập
                    </a>
                    <a href="register.php" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-lg transition-colors">
                        Đăng ký
                    </a>
                    <?php endif; ?>
                    
                    <!-- Mobile Menu Button -->
                    <button class="md:hidden text-gray-300 hover:text-white focus:outline-none" id="mobile-menu-button">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Mobile Menu -->
    <div class="fixed inset-0 z-50 bg-gray-900 bg-opacity-90 transform transition-transform duration-300 ease-in-out translate-x-full md:hidden" id="mobile-menu">
        <div class="flex justify-end p-4">
            <button class="text-gray-400 hover:text-white focus:outline-none" id="close-mobile-menu">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div class="flex flex-col items-center">
            <a href="index.php" class="mb-8">
                <img src="assets/images/logo.png" alt="Logo" class="h-10">
            </a>
            
            <nav class="flex flex-col items-center w-full">
                <a href="index.php" class="w-full text-center py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
                    Trang chủ
                </a>
                <a href="products.php" class="w-full text-center py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
                    Sản phẩm
                </a>
                <a href="faq.php" class="w-full text-center py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
                    FAQ
                </a>
                <a href="contact.php" class="w-full text-center py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
                    Liên hệ
                </a>
                
                <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
                <div class="w-full border-t border-gray-800 my-4"></div>
                
                <a href="dashboard.php" class="w-full text-center py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
                    <i class="fas fa-tachometer-alt mr-2"></i> Bảng điều khiển
                </a>
                <a href="my-licenses.php" class="w-full text-center py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
                    <i class="fas fa-key mr-2"></i> Licenses của tôi
                </a>
                <a href="my-orders.php" class="w-full text-center py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
                    <i class="fas fa-shopping-cart mr-2"></i> Đơn hàng
                </a>
                <a href="profile.php" class="w-full text-center py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
                    <i class="fas fa-user mr-2"></i> Thông tin cá nhân
                </a>
                <a href="logout.php" class="w-full text-center py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
                    <i class="fas fa-sign-out-alt mr-2"></i> Đăng xuất
                </a>
                <?php else: ?>
                <div class="w-full border-t border-gray-800 my-4"></div>
                
                <a href="login.php" class="w-full text-center py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
                    <i class="fas fa-sign-in-alt mr-2"></i> Đăng nhập
                </a>
                <a href="register.php" class="w-full text-center py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
                    <i class="fas fa-user-plus mr-2"></i> Đăng ký
                </a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
    
    <!-- Notifications -->
    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="bg-green-900 border border-green-700 text-green-100 px-4 py-3 mx-4 my-4 rounded-lg">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span><?php echo $_SESSION['success_message']; ?></span>
        </div>
    </div>
    <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="bg-red-900 border border-red-700 text-red-100 px-4 py-3 mx-4 my-4 rounded-lg">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <span><?php echo $_SESSION['error_message']; ?></span>
        </div>
    </div>
    <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    
    <main class="flex-1">