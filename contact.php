<?php
require_once 'config/database.php';

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// Lấy thông tin sản phẩm nếu có
$product_info = "";
if (isset($_GET['product']) && !empty($_GET['product'])) {
    $id = $_GET['product'];
    
    $query = "SELECT title FROM products WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $id);
    $stmt->execute();
    
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        $product_info = "Sản phẩm: " . $product['title'];
    }
}

include 'includes/header.php';
?>

<main class="flex-1">
    <section class="py-12 px-4 md:px-6">
        <div class="container mx-auto max-w-3xl">
            <h1 class="text-3xl font-bold text-white mb-8 text-center">Liên Hệ Với Chúng Tôi</h1>
            
            <div class="bg-gray-900 bg-opacity-50 border border-gray-800 rounded-lg overflow-hidden p-6 md:p-8">
                <p class="text-gray-400 mb-6">Vui lòng điền thông tin bên dưới để liên hệ với chúng tôi. Chúng tôi sẽ phản hồi trong thời gian sớm nhất.</p>
                
                <form class="admin-form">
                    <div class="mb-4">
                        <label for="name" class="text-white">Họ và tên</label>
                        <input type="text" id="name" name="name" class="bg-gray-800 text-white" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="email" class="text-white">Email</label>
                        <input type="email" id="email" name="email" class="bg-gray-800 text-white" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="phone" class="text-white">Số điện thoại</label>
                        <input type="tel" id="phone" name="phone" class="bg-gray-800 text-white" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="subject" class="text-white">Chủ đề</label>
                        <input type="text" id="subject" name="subject" class="bg-gray-800 text-white" value="<?php echo $product_info; ?>" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="message" class="text-white">Nội dung</label>
                        <textarea id="message" name="message" rows="5" class="bg-gray-800 text-white" required></textarea>
                    </div>
                    
                    <button type="submit" class="w-full bg-cyan-500 hover:bg-cyan-600 text-gray-900 py-2 rounded-md font-medium">Gửi tin nhắn</button>
                </form>
                
                <div class="mt-8 pt-6 border-t border-gray-800">
                    <h2 class="text-xl font-semibold text-white mb-4">Thông tin liên hệ khác</h2>
                    <div class="space-y-3">
                        <p class="flex items-center text-gray-400">
                            <i class="fas fa-envelope w-6 text-cyan-500"></i>
                            <span class="ml-2">Email: contact@aurae.com</span>
                        </p>
                        <p class="flex items-center text-gray-400">
                            <i class="fas fa-phone w-6 text-cyan-500"></i>
                            <span class="ml-2">Điện thoại: 0123 456 789</span>
                        </p>
                        <p class="flex items-center text-gray-400">
                            <i class="fab fa-facebook w-6 text-cyan-500"></i>
                            <span class="ml-2">Facebook: Aurae Software</span>
                        </p>
                        <p class="flex items-center text-gray-400">
                            <i class="fab fa-telegram w-6 text-cyan-500"></i>
                            <span class="ml-2">Telegram: @aurae_software</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>