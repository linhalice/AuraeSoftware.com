<?php
// Xác định trang hiện tại nếu chưa được xác định
if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF']);
}
?>

<!-- Sidebar -->
<div class="w-64 bg-gray-900 border-r border-gray-800 hidden md:block">
    <div class="p-6 border-b border-gray-800">
        <a href="index.php" class="flex items-center justify-center">
            <img src="assets/images/logo.png" alt="Logo" class="h-10">
        </a>
    </div>

    <div class="py-4">
        <div class="px-4 py-2 text-xs uppercase tracking-wider text-gray-500 font-semibold">
            Menu
        </div>
        
        <a href="dashboard.php" class="flex items-center px-4 py-3 <?php echo $current_page == 'dashboard.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'; ?> transition-colors">
            <i class="fas fa-tachometer-alt w-6"></i>
            <span>Bảng điều khiển</span>
        </a>
        
        <a href="products.php" class="flex items-center px-4 py-3 <?php echo $current_page == 'products.php' || $current_page == 'product-detail.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'; ?> transition-colors">
            <i class="fas fa-cube w-6"></i>
            <span>Sản phẩm</span>
        </a>
        
        <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
        <a href="my-licenses.php" class="flex items-center px-4 py-3 <?php echo $current_page == 'my-licenses.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'; ?> transition-colors">
            <i class="fas fa-key w-6"></i>
            <span>Licenses của tôi</span>
        </a>
        
        <a href="my-orders.php" class="flex items-center px-4 py-3 <?php echo $current_page == 'my-orders.php' || $current_page == 'order-detail.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'; ?> transition-colors">
            <i class="fas fa-shopping-cart w-6"></i>
            <span>Đơn hàng</span>
        </a>
        
        <a href="profile.php" class="flex items-center px-4 py-3 <?php echo $current_page == 'profile.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'; ?> transition-colors">
            <i class="fas fa-user w-6"></i>
            <span>Thông tin cá nhân</span>
        </a>
        <?php endif; ?>
        
        <div class="px-4 py-2 mt-4 text-xs uppercase tracking-wider text-gray-500 font-semibold">
            Hỗ trợ
        </div>
        
        <a href="faq.php" class="flex items-center px-4 py-3 <?php echo $current_page == 'faq.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'; ?> transition-colors">
            <i class="fas fa-question-circle w-6"></i>
            <span>FAQ</span>
        </a>
        
        <a href="contact.php" class="flex items-center px-4 py-3 <?php echo $current_page == 'contact.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'; ?> transition-colors">
            <i class="fas fa-envelope w-6"></i>
            <span>Liên hệ</span>
        </a>
        
        <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
        <div class="px-4 py-2 mt-4 text-xs uppercase tracking-wider text-gray-500 font-semibold">
            Tài khoản
        </div>
        
        <a href="logout.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
            <i class="fas fa-sign-out-alt w-6"></i>
            <span>Đăng xuất</span>
        </a>
        <?php else: ?>
        <div class="px-4 py-2 mt-4 text-xs uppercase tracking-wider text-gray-500 font-semibold">
            Tài khoản
        </div>
        
        <a href="login.php" class="flex items-center px-4 py-3 <?php echo $current_page == 'login.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'; ?> transition-colors">
            <i class="fas fa-sign-in-alt w-6"></i>
            <span>Đăng nhập</span>
        </a>
        
        <a href="register.php" class="flex items-center px-4 py-3 <?php echo $current_page == 'register.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'; ?> transition-colors">
            <i class="fas fa-user-plus w-6"></i>
            <span>Đăng ký</span>
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Mobile sidebar -->
<div class="fixed inset-0 z-40 bg-gray-900 bg-opacity-90 transform transition-transform duration-300 ease-in-out translate-x-full md:hidden" id="mobile-sidebar">
    <div class="flex justify-end p-4">
        <button class="text-gray-400 hover:text-white focus:outline-none" id="close-sidebar">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>

    <div class="flex flex-col items-center">
        <a href="index.php" class="mb-8">
            <img src="assets/images/logo.png" alt="Logo" class="h-10">
        </a>
        
        <a href="dashboard.php" class="w-full flex items-center justify-center px-4 py-3 <?php echo $current_page == 'dashboard.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'; ?> transition-colors">
            <i class="fas fa-tachometer-alt w-6"></i>
            <span>Bảng điều khiển</span>
        </a>
        
        <a href="products.php" class="w-full flex items-center justify-center px-4 py-3 <?php echo $current_page == 'products.php' || $current_page == 'product-detail.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'; ?> transition-colors">
            <i class="fas fa-cube w-6"></i>
            <span>Sản phẩm</span>
        </a>
        
        <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
        <a href="my-licenses.php" class="w-full flex items-center justify-center px-4 py-3 <?php echo $current_page == 'my-licenses.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'; ?> transition-colors">
            <i class="fas fa-key w-6"></i>
            <span>Licenses của tôi</span>
        </a>
        
        <a href="my-orders.php" class="w-full flex items-center justify-center px-4 py-3 <?php echo $current_page == 'my-orders.php' || $current_page == 'order-detail.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'; ?> transition-colors">
            <i class="fas fa-shopping-cart w-6"></i>
            <span>Đơn hàng</span>
        </a>
        
        <a href="profile.php" class="w-full flex items-center justify-center px-4 py-3 <?php echo $current_page == 'profile.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'; ?> transition-colors">
            <i class="fas fa-user w-6"></i>
            <span>Thông tin cá nhân</span>
        </a>
        <?php endif; ?>
        
        <div class="w-full px-4 py-2 mt-4 text-xs uppercase tracking-wider text-gray-500 font-semibold text-center">
            Hỗ trợ
        </div>
        
        <a href="faq.php" class="w-full flex items-center justify-center px-4 py-3 <?php echo $current_page == 'faq.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'; ?> transition-colors">
            <i class="fas fa-question-circle w-6"></i>
            <span>FAQ</span>
        </a>
        
        <a href="contact.php" class="w-full flex items-center justify-center px-4 py-3 <?php echo $current_page == 'contact.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'; ?> transition-colors">
            <i class="fas fa-envelope w-6"></i>
            <span>Liên hệ</span>
        </a>
        
        <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
        <a href="logout.php" class="w-full flex items-center justify-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
            <i class="fas fa-sign-out-alt w-6"></i>
            <span>Đăng xuất</span>
        </a>
        <?php else: ?>
        <a href="login.php" class="w-full flex items-center justify-center px-4 py-3 <?php echo $current_page == 'login.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'; ?> transition-colors">
            <i class="fas fa-sign-in-alt w-6"></i>
            <span>Đăng nhập</span>
        </a>
        
        <a href="register.php" class="w-full flex items-center justify-center px-4 py-3 <?php echo $current_page == 'register.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'; ?> transition-colors">
            <i class="fas fa-user-plus w-6"></i>
            <span>Đăng ký</span>
        </a>
        <?php endif; ?>
    </div>
</div>