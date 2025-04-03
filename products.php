<?php
require_once 'config/database.php';
include 'includes/header.php';

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// Lấy danh sách sản phẩm
$query = "SELECT * FROM products WHERE status = 'active' ORDER BY title ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex min-h-screen bg-gray-900">
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1">
        <div class="py-8 px-4 md:px-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-white">Sản phẩm</h1>
                
                <!-- Mobile menu button -->
                <button class="md:hidden bg-gray-800 p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none" id="mobile-menu-button">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($products as $product): ?>
                <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden hover:shadow-lg transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="h-12 w-12 rounded-full bg-opacity-20 flex items-center justify-center" style="background-color: <?php echo $product['icon_color']; ?>20; color: <?php echo $product['icon_color']; ?>">
                                <i class="fas fa-<?php echo $product['icon']; ?> text-2xl"></i>
                            </div>
                            <?php if (!empty($product['badge'])): ?>
                            <span class="badge bg-cyan-500 text-gray-900 px-3 py-1 rounded-full text-xs font-bold"><?php echo htmlspecialchars($product['badge']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <h2 class="text-xl font-bold text-white mb-2"><?php echo htmlspecialchars($product['title']); ?></h2>
                        
                        <p class="text-gray-400 mb-6 line-clamp-3"><?php echo htmlspecialchars($product['description']); ?></p>
                        
                        <?php
                        // Lấy giá thấp nhất
                        $pricing_query = "SELECT MIN(price) as min_price FROM product_pricing WHERE product_id = ?";
                        $pricing_stmt = $db->prepare($pricing_query);
                        $pricing_stmt->bindParam(1, $product['id']);
                        $pricing_stmt->execute();
                        $pricing = $pricing_stmt->fetch(PDO::FETCH_ASSOC);
                        ?>
                        
                        <div class="flex items-center justify-between">
                            <?php if (!empty($pricing['min_price'])): ?>
                            <div class="text-cyan-500 font-bold">
                                Từ <?php echo number_format($pricing['min_price'], 0, ',', '.'); ?> VND
                            </div>
                            <?php else: ?>
                            <div class="text-gray-500">
                                Liên hệ để biết giá
                            </div>
                            <?php endif; ?>
                            
                            <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-lg transition-colors">
                                Chi tiết
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (count($products) == 0): ?>
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 text-center">
                <div class="w-16 h-16 rounded-full bg-gray-700 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-cube text-gray-500 text-2xl"></i>
                </div>
                <h2 class="text-xl font-semibold text-white mb-2">Không có sản phẩm nào</h2>
                <p class="text-gray-400 mb-6">Hiện tại chưa có sản phẩm nào được đăng bán.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
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