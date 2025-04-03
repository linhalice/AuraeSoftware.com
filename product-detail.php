<?php
require_once 'config/database.php';
include 'includes/header.php';

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// Lấy thông tin sản phẩm
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $product_id = $_GET['id'];
    
    $query = "SELECT * FROM products WHERE id = ? AND status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $product_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        header("Location: products.php");
        exit();
    }
    
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Lấy danh sách gói thời hạn và giá
    $pricing_query = "SELECT * FROM product_pricing WHERE product_id = ? ORDER BY price ASC";
    $pricing_stmt = $db->prepare($pricing_query);
    $pricing_stmt->bindParam(1, $product_id);
    $pricing_stmt->execute();
    $pricing_options = $pricing_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy các tính năng
    $features = !empty($product['features']) ? explode(',', $product['features']) : [];
} else {
    header("Location: products.php");
    exit();
}

// Xử lý form
$error_message = '';
$license_key = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_license'])) {
    $license_key = trim($_POST['license_key']);
    
    if (empty($license_key)) {
        $error_message = "Vui lòng nhập license key.";
    } else {
        // Kiểm tra license key
        $license_query = "SELECT * FROM licenses WHERE license_key = ? AND product_id = ?";
        $license_stmt = $db->prepare($license_query);
        $license_stmt->bindParam(1, $license_key);
        $license_stmt->bindParam(2, $product_id);
        $license_stmt->execute();
        
        if ($license_stmt->rowCount() == 0) {
            $error_message = "License key không hợp lệ hoặc không thuộc về sản phẩm này.";
        } else {
            $license = $license_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($license['status'] != 'pending') {
                if ($license['status'] == 'active') {
                    $error_message = "License key này đã được kích hoạt.";
                } else if ($license['status'] == 'expired') {
                    $error_message = "License key này đã hết hạn.";
                } else {
                    $error_message = "License key này không thể sử dụng (trạng thái: " . $license['status'] . ").";
                }
            }
        }
    }
}

// Hàm hiển thị nhãn thời hạn
function getDurationLabel($duration_type) {
    switch ($duration_type) {
        case '3_days':
            return '3 ngày';
        case '1_month':
            return '1 tháng';
        case '3_months':
            return '3 tháng';
        case '6_months':
            return '6 tháng';
        case '1_year':
            return '1 năm';
        case 'lifetime':
            return 'Vĩnh viễn';
        default:
            return $duration_type;
    }
}
?>

<div class="flex min-h-screen bg-gray-900">
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
            
            <a href="index.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
                <i class="fas fa-home w-6"></i>
                <span>Trang chủ</span>
            </a>
            
            <a href="products.php" class="flex items-center px-4 py-3 bg-gray-800 text-white transition-colors">
                <i class="fas fa-cube w-6"></i>
                <span>Sản phẩm</span>
            </a>
            
            <a href="licenses.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
                <i class="fas fa-key w-6"></i>
                <span>Licenses của tôi</span>
            </a>
            
            <a href="orders.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
                <i class="fas fa-shopping-cart w-6"></i>
                <span>Đơn hàng</span>
            </a>
            
            <div class="px-4 py-2 mt-4 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                Hỗ trợ
            </div>
            
            <a href="faq.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
                <i class="fas fa-question-circle w-6"></i>
                <span>FAQ</span>
            </a>
            
            <a href="contact.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
                <i class="fas fa-envelope w-6"></i>
                <span>Liên hệ</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1">
        <div class="py-8 px-4 md:px-8">
            <div class="flex justify-between items-center mb-6">
                <a href="products.php" class="inline-flex items-center text-cyan-500 hover:text-cyan-400">
                    <i class="fas fa-arrow-left mr-2"></i> Quay lại trang sản phẩm
                </a>
                
                <!-- Mobile menu button -->
                <button class="md:hidden bg-gray-800 p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none" id="mobile-menu-button">
                    <i class="fas fa-bars"></i>
                </button>
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
                    
                    <a href="index.php" class="w-full flex items-center justify-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
                        <i class="fas fa-home w-6"></i>
                        <span>Trang chủ</span>
                    </a>
                    
                    <a href="products.php" class="w-full flex items-center justify-center px-4 py-3 bg-gray-800 text-white transition-colors">
                        <i class="fas fa-cube w-6"></i>
                        <span>Sản phẩm</span>
                    </a>
                    
                    <a href="licenses.php" class="w-full flex items-center justify-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
                        <i class="fas fa-key w-6"></i>
                        <span>Licenses của tôi</span>
                    </a>
                    
                    <a href="orders.php" class="w-full flex items-center justify-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
                        <i class="fas fa-shopping-cart w-6"></i>
                        <span>Đơn hàng</span>
                    </a>
                    
                    <div class="w-full px-4 py-2 mt-4 text-xs uppercase tracking-wider text-gray-500 font-semibold text-center">
                        Hỗ trợ
                    </div>
                    
                    <a href="faq.php" class="w-full flex items-center justify-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
                        <i class="fas fa-question-circle w-6"></i>
                        <span>FAQ</span>
                    </a>
                    
                    <a href="contact.php" class="w-full flex items-center justify-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
                        <i class="fas fa-envelope w-6"></i>
                        <span>Liên hệ</span>
                    </a>
                </div>
            </div>
            
            <div class="bg-gray-900 bg-opacity-50 border border-gray-800 rounded-lg overflow-hidden">
                <div class="p-6 md:p-8">
                    <div class="flex items-center justify-between mb-6">
                        <div class="h-12 w-12 rounded-full bg-opacity-20 flex items-center justify-center" style="background-color: <?php echo $product['icon_color']; ?>20; color: <?php echo $product['icon_color']; ?>">
                            <i class="fas fa-<?php echo $product['icon']; ?> text-2xl"></i>
                        </div>
                        <?php if (!empty($product['badge'])) { ?>
                            <span class="badge bg-cyan-500 text-gray-900 px-3 py-1 rounded-full text-xs font-bold"><?php echo $product['badge']; ?></span>
                        <?php } ?>
                    </div>
                    
                    <h1 class="text-3xl font-bold text-white mb-4"><?php echo htmlspecialchars($product['title']); ?></h1>
                    
                    <div class="mb-8">
                        <p class="text-gray-400 text-lg"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>
                    
                    <?php if (!empty($features)): ?>
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold text-white mb-4">Tính năng nổi bật</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($features as $feature): ?>
                            <div class="flex items-center">
                                <div class="w-2 h-2 rounded-full bg-cyan-500 mr-3"></div>
                                <span class="text-gray-300"><?php echo htmlspecialchars(trim($feature)); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="border-t border-gray-800 pt-8 mt-8">
                        <h2 class="text-xl font-semibold text-white mb-6">Chọn gói thời hạn</h2>
                        
                        <?php if (!empty($pricing_options)): ?>
                        <!-- Sửa lại action thành create-order.php -->
                        <form method="POST" action="create-order.php" id="purchase-form">
                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                                <?php foreach ($pricing_options as $index => $option): ?>
                                <label class="cursor-pointer">
                                    <input type="radio" name="pricing_id" value="<?php echo $option['id']; ?>" class="hidden peer" <?php echo $index === 0 ? 'checked' : ''; ?>>
                                    <div class="p-4 rounded-lg border border-gray-700 hover:border-cyan-500 peer-checked:border-cyan-500 peer-checked:bg-gray-800 transition-all duration-200">
                                        <div class="font-medium text-white"><?php echo getDurationLabel($option['duration_type']); ?></div>
                                        <div class="text-lg font-bold text-cyan-500"><?php echo number_format($option['price'], 0, ',', '.'); ?> VND</div>
                                        <?php if ($option['duration_type'] == 'lifetime'): ?>
                                        <div class="text-xs text-cyan-400 mt-1">Sử dụng vĩnh viễn</div>
                                        <?php endif; ?>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="mb-8">
                                <h2 class="text-xl font-semibold text-white mb-4">Nhập License Key</h2>
                                
                                <?php if (!empty($error_message)): ?>
                                <div class="bg-red-900 border border-red-700 text-red-100 px-4 py-3 rounded-lg mb-4">
                                    <div class="flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>
                                        <span><?php echo $error_message; ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="flex flex-col md:flex-row gap-4">
                                    <div class="flex-grow">
                                        <input type="text" id="license_key" name="license_key" value="<?php echo htmlspecialchars($license_key); ?>" placeholder="Nhập license key của bạn (ví dụ: AURAE-XXXX-XXXX-XXXX-XXXX)" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-cyan-500" required>
                                        <p class="mt-1 text-sm text-gray-400">Nhập license key đã được tạo từ phần mềm của bạn.</p>
                                    </div>
                                    
                                    <div class="flex-shrink-0">
                                        <button type="submit" name="check_license" formaction="product-detail.php?id=<?php echo $product_id; ?>" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-3 rounded-lg font-medium transition-colors">
                                            Kiểm tra
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex flex-col md:flex-row items-center justify-between border-t border-gray-800 pt-6 gap-6">
                                <div class="bg-gray-800 p-4 rounded-lg w-full md:w-auto">
                                    <p class="text-gray-300 text-sm">Để kích hoạt sản phẩm, bạn cần:</p>
                                    <ol class="text-gray-400 text-sm list-decimal list-inside space-y-1 mt-2">
                                        <li>Tạo license key từ phần mềm</li>
                                        <li>Nhập license key vào form</li>
                                        <li>Chọn gói thời hạn phù hợp</li>
                                        <li>Thanh toán để kích hoạt license</li>
                                    </ol>
                                </div>
                                
                                <!-- Nút tạo đơn hàng nổi bật hơn với màu khác và chữ trắng -->
                                <button type="submit" class="w-full md:w-auto bg-cyan-600 hover:bg-cyan-700 text-white px-8 py-4 rounded-lg text-lg font-bold transition-colors shadow-lg hover:shadow-xl flex items-center justify-center">
                                    <i class="fas fa-shopping-cart mr-2"></i> Tạo đơn hàng
                                </button>
                            </div>
                        </form>
                        <?php else: ?>
                        <div class="bg-yellow-900 border border-yellow-700 text-yellow-100 px-4 py-3 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <span>Hiện tại không có gói giá nào cho sản phẩm này. Vui lòng liên hệ với chúng tôi để biết thêm chi tiết.</span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <section class="mt-8 bg-gray-800 bg-opacity-50 rounded-lg p-8">
                <div class="mb-8 text-center">
                    <h2 class="text-2xl font-bold text-white mb-2">Hướng dẫn kích hoạt license</h2>
                    <p class="text-gray-400">Quy trình đơn giản để kích hoạt sản phẩm của bạn</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="rounded-xl border border-gray-800 bg-gray-900 bg-opacity-50 p-6 text-center">
                        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-cyan-500 bg-opacity-10">
                            <i class="fas fa-key text-cyan-500 text-xl"></i>
                        </div>
                        <h3 class="mb-2 text-xl font-medium text-white">Tạo License Key</h3>
                        <p class="text-gray-400">
                            Mở phần mềm và tạo license key từ giao diện chính của ứng dụng.
                        </p>
                    </div>
                    
                    <div class="rounded-xl border border-gray-800 bg-gray-900 bg-opacity-50 p-6 text-center">
                        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-cyan-500 bg-opacity-10">
                            <i class="fas fa-credit-card text-cyan-500 text-xl"></i>
                        </div>
                        <h3 class="mb-2 text-xl font-medium text-white">Thanh Toán</h3>
                        <p class="text-gray-400">
                            Nhập license key, chọn gói thời hạn và thanh toán theo hướng dẫn.
                        </p>
                    </div>
                    
                    <div class="rounded-xl border border-gray-800 bg-gray-900 bg-opacity-50 p-6 text-center">
                        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-cyan-500 bg-opacity-10">
                            <i class="fas fa-check-circle text-cyan-500 text-xl"></i>
                        </div>
                        <h3 class="mb-2 text-xl font-medium text-white">Kích Hoạt</h3>
                        <p class="text-gray-400">
                            Sau khi thanh toán thành công, khởi động lại phần mềm để áp dụng license.
                        </p>
                    </div>
                </div>
            </section>
            
            <section class="mt-8 text-center">
                <h2 class="text-2xl font-bold text-white mb-4">Bạn cần hỗ trợ thêm?</h2>
                <p class="text-gray-400 mb-6 max-w-2xl mx-auto">
                    Nếu bạn gặp bất kỳ khó khăn nào trong quá trình mua hàng hoặc kích hoạt sản phẩm, đừng ngần ngại liên hệ với chúng tôi.
                </p>
                <a href="contact.php" class="inline-block bg-gray-700 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                    <i class="fas fa-headset mr-2"></i> Liên hệ hỗ trợ
                </a>
            </section>
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

/* Hiệu ứng nhấp nháy cho nút thanh toán */
@keyframes pulse {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(8, 145, 178, 0.7);
    }
    50% {
        box-shadow: 0 0 0 10px rgba(8, 145, 178, 0);
    }
}

button[type="submit"]:not([name="check_license"]) {
    animation: pulse 2s infinite;
}
</style>

<script>
// Hiệu ứng khi chọn gói thời hạn
document.addEventListener('DOMContentLoaded', function() {
    const radioButtons = document.querySelectorAll('input[name="pricing_id"]');
    const pricingCards = document.querySelectorAll('input[name="pricing_id"] + div');
    
    // Đảm bảo card đầu tiên được highlight
    if (pricingCards.length > 0) {
        pricingCards[0].classList.add('border-cyan-500', 'bg-gray-800');
    }
    
    radioButtons.forEach((radio, index) => {
        radio.addEventListener('change', function() {
            pricingCards.forEach(card => {
                card.classList.remove('border-cyan-500', 'bg-gray-800');
                card.classList.add('border-gray-700');
            });
            
            if (this.checked) {
                pricingCards[index].classList.remove('border-gray-700');
                pricingCards[index].classList.add('border-cyan-500', 'bg-gray-800');
            }
        });
    });
    
    // Mobile menu
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const closeSidebarButton = document.getElementById('close-sidebar');
    const mobileSidebar = document.getElementById('mobile-sidebar');
    
    mobileMenuButton.addEventListener('click', function() {
        mobileSidebar.classList.remove('translate-x-full');
    });
    
    closeSidebarButton.addEventListener('click', function() {
        mobileSidebar.classList.add('translate-x-full');
    });
    
    // Đảm bảo form submit đến create-order.php
    const purchaseForm = document.getElementById('purchase-form');
    if (purchaseForm) {
        purchaseForm.addEventListener('submit', function(e) {
            if (e.submitter && e.submitter.hasAttribute('name') && e.submitter.getAttribute('name') === 'check_license') {
                // Nếu là nút kiểm tra, để form submit đến trang hiện tại
                return true;
            } else {
                // Nếu là nút tạo đơn hàng, đảm bảo form submit đến create-order.php
                this.action = 'create-order.php';
                return true;
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>