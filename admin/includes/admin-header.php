<?php
// Bắt đầu session nếu chưa được bắt đầu
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Kết nối database để lấy thông báo
require_once '../config/database.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->getConnection();

// Tạo các bảng cần thiết nếu chưa tồn tại
createTablesIfNotExist($db);

// Lấy thông tin admin
$admin_name = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin';
$admin_email = isset($_SESSION['admin_email']) ? $_SESSION['admin_email'] : 'admin@example.com';

// Lấy tên trang hiện tại
$current_page = basename($_SERVER['PHP_SELF']);

// Đếm số thông báo chưa đọc
try {
    $unread_notifications = countUnreadNotifications($db, $_SESSION['admin_id']);
} catch (Exception $e) {
    $unread_notifications = 0;
}

// Lấy danh sách thông báo mới nhất
try {
    $latest_notifications = getLatestNotifications($db, $_SESSION['admin_id'], 5);
} catch (Exception $e) {
    $latest_notifications = [];
}

// Định nghĩa các mục menu
$menu_items = [
    [
        'title' => 'Dashboard',
        'icon' => 'fas fa-tachometer-alt',
        'url' => 'index.php',
        'active' => $current_page == 'index.php'
    ],
    [
        'title' => 'Sản phẩm',
        'icon' => 'fas fa-box',
        'url' => 'products.php',
        'active' => $current_page == 'products.php' || $current_page == 'add-product.php' || $current_page == 'edit-product.php'
    ],
    [
        'title' => 'Licenses',
        'icon' => 'fas fa-key',
        'url' => 'licenses.php',
        'active' => $current_page == 'licenses.php' || $current_page == 'add-license.php' || $current_page == 'edit-license.php' || $current_page == 'activate-license.php'
    ],
    [
        'title' => 'Người dùng',
        'icon' => 'fas fa-users',
        'url' => 'users.php',
        'active' => $current_page == 'users.php' || $current_page == 'add-user.php' || $current_page == 'edit-user.php'
    ],
    [
        'title' => 'Lịch sử kích hoạt',
        'icon' => 'fas fa-history',
        'url' => 'activation-logs.php',
        'active' => $current_page == 'activation-logs.php'
    ],
    [
        'title' => 'Nhật ký hoạt động',
        'icon' => 'fas fa-clipboard-list',
        'url' => 'activity-logs.php',
        'active' => $current_page == 'activity-logs.php'
    ],
    [
        'title' => 'Thông báo',
        'icon' => 'fas fa-bell',
        'url' => 'notifications.php',
        'active' => $current_page == 'notifications.php'
    ],
    [
        'title' => 'Thiết lập Cronjob',
        'icon' => 'fas fa-clock',
        'url' => 'cronjob-setup.php',
        'active' => $current_page == 'cronjob-setup.php'
    ],
    [
        'title' => 'Cài đặt',
        'icon' => 'fas fa-cog',
        'url' => 'settings.php',
        'active' => $current_page == 'settings.php'
    ]
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aurae Software - Quản trị</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        },
                        dark: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                            950: '#020617',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom Styles -->
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        .sidebar {
            transition: all 0.3s ease;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
        }
        .dropdown-menu {
            display: none;
        }
        .dropdown-menu.show {
            display: block;
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            padding: 2px 5px;
            border-radius: 50%;
            background-color: #ef4444;
            color: white;
            font-size: 0.75rem;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }
    </style>
</head>
<body class="flex flex-col min-h-screen">
    <!-- Header -->
    <header class="bg-white text-gray-800 sticky top-0 z-30 shadow-sm">
        <div class="flex justify-between items-center px-4 py-3">
            <div class="flex items-center">
                <button id="sidebar-toggle" class="text-gray-600 hover:text-primary-500 focus:outline-none md:hidden">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <a href="index.php" class="flex items-center ml-2 md:ml-0">
                    <span class="text-xl font-bold text-primary-600">Aurae</span>
                    <span class="text-xl font-bold text-gray-800">Software</span>
                </a>
            </div>
            <div class="flex items-center space-x-4">
                <a href="../index.php" target="_blank" class="text-gray-600 hover:text-primary-500 hidden md:flex items-center">
                    <i class="fas fa-globe mr-1"></i>
                    <span class="text-sm">Trang chủ</span>
                </a>
                <div class="relative">
                    <button id="notifications-toggle" class="text-gray-600 hover:text-primary-500 focus:outline-none relative">
                        <i class="fas fa-bell text-xl"></i>
                        <?php if ($unread_notifications > 0): ?>
                        <span class="notification-badge"><?php echo $unread_notifications; ?></span>
                        <?php endif; ?>
                    </button>
                    <div id="notifications-dropdown" class="dropdown-menu absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                        <div class="p-3 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-800">Thông báo</h3>
                            <a href="notifications.php" class="text-xs text-primary-600 hover:text-primary-800">Xem tất cả</a>
                        </div>
                        <div class="max-h-64 overflow-y-auto">
                            <?php if (count($latest_notifications) > 0): ?>
                                <?php foreach ($latest_notifications as $notification): ?>
                                    <?php
                                    $icon_class = '';
                                    $icon_bg = '';
                                    switch ($notification['type']) {
                                        case 'license':
                                            $icon_class = 'fas fa-key text-blue-600';
                                            $icon_bg = 'bg-blue-100';
                                            break;
                                        case 'system':
                                            $icon_class = 'fas fa-server text-purple-600';
                                            $icon_bg = 'bg-purple-100';
                                            break;
                                        case 'user':
                                            $icon_class = 'fas fa-user text-green-600';
                                            $icon_bg = 'bg-green-100';
                                            break;
                                        default:
                                            $icon_class = 'fas fa-bell text-gray-600';
                                            $icon_bg = 'bg-gray-100';
                                    }
                                    ?>
                                    <a href="<?php echo !empty($notification['link']) ? $notification['link'] : 'notifications.php?mark_read=' . $notification['id']; ?>" class="block p-3 border-b border-gray-200 hover:bg-gray-50 <?php echo $notification['is_read'] ? '' : 'bg-blue-50'; ?>">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 <?php echo $icon_bg; ?> rounded-full p-2">
                                                <i class="<?php echo $icon_class; ?>"></i>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-900"><?php echo $notification['title']; ?></p>
                                                <p class="text-sm text-gray-500"><?php echo $notification['message']; ?></p>
                                                <p class="text-xs text-gray-400 mt-1"><?php echo timeAgo($notification['created_at']); ?></p>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-4 text-center text-gray-500">
                                    <p>Không có thông báo nào</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-3 border-t border-gray-200 text-center">
                            <a href="notifications.php" class="text-sm font-medium text-primary-600 hover:text-primary-800">Xem tất cả thông báo</a>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <button id="profile-toggle" class="flex items-center focus:outline-none">
                        <div class="h-8 w-8 rounded-full bg-primary-500 flex items-center justify-center text-white font-semibold">
                            <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                        </div>
                        <span class="ml-2 text-sm font-medium text-gray-700 hidden md:block"><?php echo $admin_name; ?></span>
                        <i class="fas fa-chevron-down ml-1 text-gray-400 text-xs hidden md:block"></i>
                    </button>
                    <div id="profile-dropdown" class="dropdown-menu absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                        <div class="p-3 border-b border-gray-200">
                            <p class="text-sm font-medium text-gray-900"><?php echo $admin_name; ?></p>
                            <p class="text-xs text-gray-500"><?php echo $admin_email; ?></p>
                        </div>
                        <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-user mr-2 text-gray-500"></i> Hồ sơ
                        </a>
                        <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-cog mr-2 text-gray-500"></i> Cài đặt
                        </a>
                        <div class="border-t border-gray-200"></div>
                        <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                            <i class="fas fa-sign-out-alt mr-2 text-red-600"></i> Đăng xuất
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="flex flex-1">
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar bg-dark-800 text-white w-64 fixed h-full z-20 md:translate-x-0">
            <div class="h-full flex flex-col overflow-y-auto">
                <div class="p-4">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fas fa-search text-gray-400"></i>
                        </span>
                        <input type="text" placeholder="Tìm kiếm..." class="w-full pl-10 pr-4 py-2 rounded-lg bg-dark-700 border border-dark-600 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                </div>
                <nav class="flex-1 px-2 py-4 space-y-1">
                    <?php foreach ($menu_items as $item): ?>
                    <a href="<?php echo $item['url']; ?>" class="flex items-center px-4 py-2 text-gray-300 hover:bg-dark-700 hover:text-white rounded-lg transition-colors duration-200 <?php echo $item['active'] ? 'bg-primary-700 text-white' : ''; ?>">
                        <i class="<?php echo $item['icon']; ?> w-5 h-5 mr-2"></i>
                        <span><?php echo $item['title']; ?></span>
                        <?php if ($item['title'] == 'Thông báo' && $unread_notifications > 0): ?>
                        <span class="ml-auto bg-red-500 text-white text-xs font-medium px-2 py-0.5 rounded-full"><?php echo $unread_notifications; ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </nav>
                <div class="p-4 border-t border-dark-700">
                    <a href="../index.php" target="_blank" class="flex items-center px-4 py-2 text-gray-300 hover:bg-dark-700 hover:text-white rounded-lg transition-colors duration-200">
                        <i class="fas fa-external-link-alt w-5 h-5 mr-2"></i>
                        <span>Xem trang chủ</span>
                    </a>
                    <a href="logout.php" class="flex items-center px-4 py-2 text-red-400 hover:bg-dark-700 hover:text-red-300 rounded-lg transition-colors duration-200 mt-2">
                        <i class="fas fa-sign-out-alt w-5 h-5 mr-2"></i>
                        <span>Đăng xuất</span>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Overlay for mobile sidebar -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black opacity-50 z-10 hidden md:hidden"></div>

        <!-- Main Content -->
        <main class="flex-1 p-4 md:ml-64 bg-gray-50">
            <div class="container mx-auto">
                <!-- Breadcrumbs -->
                <nav class="mb-4 text-sm bg-white p-3 rounded-lg shadow-sm">
                    <ol class="list-none p-0 inline-flex">
                        <li class="flex items-center">
                            <a href="index.php" class="text-primary-600 hover:text-primary-800">
                                <i class="fas fa-home"></i>
                            </a>
                            <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                        </li>
                        <?php
                        $page_title = '';
                        switch ($current_page) {
                            case 'index.php':
                                $page_title = 'Dashboard';
                                break;
                            case 'products.php':
                                $page_title = 'Sản phẩm';
                                break;
                            case 'add-product.php':
                                $page_title = 'Thêm sản phẩm';
                                echo '<li class="flex items-center"><a href="products.php" class="text-primary-600 hover:text-primary-800">Sản phẩm</a><i class="fas fa-chevron-right text-gray-400 mx-2"></i></li>';
                                break;
                            case 'edit-product.php':
                                $page_title = 'Chỉnh sửa sản phẩm';
                                echo '<li class="flex items-center"><a href="products.php" class="text-primary-600 hover:text-primary-800">Sản phẩm</a><i class="fas fa-chevron-right text-gray-400 mx-2"></i></li>';
                                break;
                            case 'licenses.php':
                                $page_title = 'Licenses';
                                break;
                            case 'add-license.php':
                                $page_title = 'Thêm license';
                                echo '<li class="flex items-center"><a href="licenses.php" class="text-primary-600 hover:text-primary-800">Licenses</a><i class="fas fa-chevron-right text-gray-400 mx-2"></i></li>';
                                break;
                            case 'edit-license.php':
                                $page_title = 'Chỉnh sửa license';
                                echo '<li class="flex items-center"><a href="licenses.php" class="text-primary-600 hover:text-primary-800">Licenses</a><i class="fas fa-chevron-right text-gray-400 mx-2"></i></li>';
                                break;
                            case 'activate-license.php':
                                $page_title = 'Kích hoạt license';
                                echo '<li class="flex items-center"><a href="licenses.php" class="text-primary-600 hover:text-primary-800">Licenses</a><i class="fas fa-chevron-right text-gray-400 mx-2"></i></li>';
                                break;
                            case 'users.php':
                                $page_title = 'Người dùng';
                                break;
                            case 'add-user.php':
                                $page_title = 'Thêm người dùng';
                                echo '<li class="flex items-center"><a href="users.php" class="text-primary-600 hover:text-primary-800">Người dùng</a><i class="fas fa-chevron-right text-gray-400 mx-2"></i></li>';
                                break;
                            case 'edit-user.php':
                                $page_title = 'Chỉnh sửa người dùng';
                                echo '<li class="flex items-center"><a href="users.php" class="text-primary-600 hover:text-primary-800">Người dùng</a><i class="fas fa-chevron-right text-gray-400 mx-2"></i></li>';
                                break;
                            case 'activation-logs.php':
                                $page_title = 'Lịch sử kích hoạt';
                                break;
                            case 'activity-logs.php':
                                $page_title = 'Nhật ký hoạt động';
                                break;
                            case 'notifications.php':
                                $page_title = 'Thông báo';
                                break;
                            case 'settings.php':
                                $page_title = 'Cài đặt';
                                break;
                            case 'cronjob-setup.php':
                                $page_title = 'Thiết lập Cronjob';
                                break;
                            default:
                                $page_title = 'Trang quản trị';
                        }
                        ?>
                        <li class="text-gray-700 font-medium"><?php echo $page_title; ?></li>
                    </ol>
                </nav>

<script>
    // Toggle sidebar on mobile
    document.getElementById('sidebar-toggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebar-overlay').classList.toggle('hidden');
    });

    // Hide sidebar when clicking on overlay
    document.getElementById('sidebar-overlay').addEventListener('click', function() {
        document.getElementById('sidebar').classList.remove('open');
        this.classList.add('hidden');
    });

    // Toggle profile dropdown
    document.getElementById('profile-toggle').addEventListener('click', function() {
        document.getElementById('profile-dropdown').classList.toggle('show');
        // Hide notifications dropdown if open
        document.getElementById('notifications-dropdown').classList.remove('show');
    });

    // Toggle notifications dropdown
    document.getElementById('notifications-toggle').addEventListener('click', function() {
        document.getElementById('notifications-dropdown').classList.toggle('show');
        // Hide profile dropdown if open
        document.getElementById('profile-dropdown').classList.remove('show');
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        const profileToggle = document.getElementById('profile-toggle');
        const profileDropdown = document.getElementById('profile-dropdown');
        const notificationsToggle = document.getElementById('notifications-toggle');
        const notificationsDropdown = document.getElementById('notifications-dropdown');

        if (!profileToggle.contains(event.target) && !profileDropdown.contains(event.target)) {
            profileDropdown.classList.remove('show');
        }

        if (!notificationsToggle.contains(event.target) && !notificationsDropdown.contains(event.target)) {
            notificationsDropdown.classList.remove('show');
        }
    });
</script>