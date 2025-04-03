<?php
require_once 'config/database.php';
include 'includes/header.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Bạn cần đăng nhập để xem licenses của mình.";
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

// Lấy danh sách licenses
$licenses_query = "SELECT l.*, p.title as product_name, p.icon, p.icon_color 
                  FROM licenses l 
                  JOIN products p ON l.product_id = p.id 
                  WHERE l.customer_email = ? 
                  ORDER BY l.created_at DESC";
$licenses_stmt = $db->prepare($licenses_query);
$licenses_stmt->bindParam(1, $user['email']);
$licenses_stmt->execute();
$licenses = $licenses_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex min-h-screen bg-gray-900">
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1">
        <div class="py-8 px-4 md:px-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-white">Licenses của tôi</h1>
                
                <!-- Mobile menu button -->
                <button class="md:hidden bg-gray-800 p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none" id="mobile-menu-button">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <?php if (count($licenses) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($licenses as $license): ?>
                <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="h-10 w-10 rounded-full bg-opacity-20 flex items-center justify-center mr-3" style="background-color: <?php echo $license['icon_color']; ?>20; color: <?php echo $license['icon_color']; ?>">
                                <i class="fas fa-<?php echo $license['icon']; ?>"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white"><?php echo htmlspecialchars($license['product_name']); ?></h3>
                                <?php
                                switch ($license['status']) {
                                    case 'active':
                                        echo '<span class="px-2 py-1 bg-green-900 text-green-300 rounded-full text-xs">Đã kích hoạt</span>';
                                        break;
                                    case 'pending':
                                        echo '<span class="px-2 py-1 bg-yellow-900 text-yellow-300 rounded-full text-xs">Chờ kích hoạt</span>';
                                        break;
                                    case 'expired':
                                        echo '<span class="px-2 py-1 bg-red-900 text-red-300 rounded-full text-xs">Hết hạn</span>';
                                        break;
                                    default:
                                        echo '<span class="px-2 py-1 bg-gray-700 text-gray-300 rounded-full text-xs">' . htmlspecialchars($license['status']) . '</span>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="text-gray-400 text-sm mb-1">License Key:</div>
                            <div class="bg-gray-900 p-3 rounded-lg text-white font-mono text-sm break-all"><?php echo htmlspecialchars($license['license_key']); ?></div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <div class="text-gray-400 text-sm">Ngày tạo:</div>
                                <div class="text-white"><?php echo date('d/m/Y', strtotime($license['created_at'])); ?></div>
                            </div>
                            
                            <?php if ($license['status'] == 'active'): ?>
                            <div>
                                <div class="text-gray-400 text-sm">Ngày hết hạn:</div>
                                <div class="text-white">
                                    <?php 
                                    if (!empty($license['expiration_date'])) {
                                        echo date('d/m/Y', strtotime($license['expiration_date']));
                                    } else {
                                        echo 'Vĩnh viễn';
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <?php if ($license['status'] == 'pending'): ?>
                            <a href="product-detail.php?id=<?php echo $license['product_id']; ?>" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-lg transition-colors text-sm">
                                Kích hoạt ngay
                            </a>
                            <?php elseif ($license['status'] == 'expired'): ?>
                            <a href="product-detail.php?id=<?php echo $license['product_id']; ?>" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-lg transition-colors text-sm">
                                Gia hạn
                            </a>
                            <?php else: ?>
                            <button class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors text-sm copy-button" data-license="<?php echo htmlspecialchars($license['license_key']); ?>">
                                <i class="fas fa-copy mr-2"></i> Sao chép
                            </button>
                            <?php endif; ?>
                            
                            <a href="#" class="text-gray-400 hover:text-white transition-colors">
                                <i class="fas fa-question-circle"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 text-center">
                <div class="w-16 h-16 rounded-full bg-gray-700 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-key text-gray-500 text-2xl"></i>
                </div>
                <h2 class="text-xl font-semibold text-white mb-2">Bạn chưa có license nào</h2>
                <p class="text-gray-400 mb-6">Hãy mua sản phẩm và kích hoạt license để sử dụng các tính năng đầy đủ.</p>
                <a href="products.php" class="bg-cyan-600 hover:bg-cyan-700 text-white px-6 py-3 rounded-lg transition-colors inline-block">
                    Xem sản phẩm
                </a>
            </div>
            <?php endif; ?>
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
    
    // Copy license key functionality
    const copyButtons = document.querySelectorAll('.copy-button');
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const licenseKey = this.getAttribute('data-license');
            navigator.clipboard.writeText(licenseKey).then(() => {
                // Change button text temporarily
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check mr-2"></i> Đã sao chép';
                this.classList.remove('bg-gray-700', 'hover:bg-gray-600');
                this.classList.add('bg-green-700', 'hover:bg-green-600');
                
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.classList.remove('bg-green-700', 'hover:bg-green-600');
                    this.classList.add('bg-gray-700', 'hover:bg-gray-600');
                }, 2000);
            });
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>