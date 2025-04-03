<?php
require_once '../config/database.php';
require_once 'includes/functions.php';
include 'includes/admin-header.php';

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// Lấy thông tin sản phẩm
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    
    $query = "SELECT * FROM products WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $id);
    $stmt->execute();
    
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        header("Location: products.php");
        exit();
    }
    
    // Kiểm tra xem trường status có tồn tại không
    if (!isset($product['status'])) {
        // Nếu không tồn tại, thiết lập giá trị mặc định
        $product['status'] = 'active';
    }
    
    // Lấy thông tin giá sản phẩm
    $pricing_query = "SELECT * FROM product_pricing WHERE product_id = ?";
    $pricing_stmt = $db->prepare($pricing_query);
    $pricing_stmt->bindParam(1, $id);
    $pricing_stmt->execute();
    
    $pricing = [];
    while ($row = $pricing_stmt->fetch(PDO::FETCH_ASSOC)) {
        $pricing[$row['duration_type']] = $row['price'];
    }
} else {
    header("Location: products.php");
    exit();
}

// Xử lý cập nhật sản phẩm
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $features = $_POST['features'];
    $badge = $_POST['badge'];
    $icon = $_POST['icon'];
    $icon_color = $_POST['icon_color'];
    $status = $_POST['status'];
    
    // Validate dữ liệu
    if (empty($title)) {
        $error_message = 'Vui lòng nhập tên sản phẩm';
    } elseif (empty($description)) {
        $error_message = 'Vui lòng nhập mô tả sản phẩm';
    } elseif (empty($features)) {
        $error_message = 'Vui lòng nhập tính năng sản phẩm';
    } elseif (empty($icon)) {
        $error_message = 'Vui lòng chọn biểu tượng';
    } elseif (empty($icon_color)) {
        $error_message = 'Vui lòng chọn màu biểu tượng';
    } else {
        try {
            // Bắt đầu transaction
            $db->beginTransaction();
            
            // Kiểm tra xem bảng products có trường status không
            $table_info_query = "SHOW COLUMNS FROM products LIKE 'status'";
            $table_info_stmt = $db->prepare($table_info_query);
            $table_info_stmt->execute();
            $has_status_field = $table_info_stmt->rowCount() > 0;
            
            // Cập nhật sản phẩm
            if ($has_status_field) {
                $query = "UPDATE products SET title = ?, description = ?, features = ?, badge = ?, icon = ?, icon_color = ?, status = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $title);
                $stmt->bindParam(2, $description);
                $stmt->bindParam(3, $features);
                $stmt->bindParam(4, $badge);
                $stmt->bindParam(5, $icon);
                $stmt->bindParam(6, $icon_color);
                $stmt->bindParam(7, $status);
                $stmt->bindParam(8, $id);
            } else {
                $query = "UPDATE products SET title = ?, description = ?, features = ?, badge = ?, icon = ?, icon_color = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $title);
                $stmt->bindParam(2, $description);
                $stmt->bindParam(3, $features);
                $stmt->bindParam(4, $badge);
                $stmt->bindParam(5, $icon);
                $stmt->bindParam(6, $icon_color);
                $stmt->bindParam(7, $id);
            }
            
            if ($stmt->execute()) {
                // Cập nhật các gói giá
                $pricing_types = [
                    '3_days' => $_POST['price_3_days'],
                    '1_month' => $_POST['price_1_month'],
                    '3_months' => $_POST['price_3_months'],
                    '6_months' => $_POST['price_6_months'],
                    '1_year' => $_POST['price_1_year'],
                    'lifetime' => $_POST['price_lifetime']
                ];
                
                // Xóa các gói giá cũ
                $delete_pricing_query = "DELETE FROM product_pricing WHERE product_id = ?";
                $delete_pricing_stmt = $db->prepare($delete_pricing_query);
                $delete_pricing_stmt->bindParam(1, $id);
                $delete_pricing_stmt->execute();
                
                // Thêm các gói giá mới
                $pricing_query = "INSERT INTO product_pricing (product_id, duration_type, price, created_at) VALUES (?, ?, ?, NOW())";
                $pricing_stmt = $db->prepare($pricing_query);
                
                $has_pricing = false;
                foreach ($pricing_types as $duration_type => $price) {
                    if (!empty($price) && is_numeric($price)) {
                        $pricing_stmt->bindParam(1, $id);
                        $pricing_stmt->bindParam(2, $duration_type);
                        $pricing_stmt->bindParam(3, $price);
                        $pricing_stmt->execute();
                        $has_pricing = true;
                    }
                }
                
                if (!$has_pricing) {
                    $db->rollBack();
                    $error_message = "Vui lòng nhập ít nhất một gói giá cho sản phẩm!";
                } else {
                    // Commit transaction
                    $db->commit();
                    
                    $success_message = "Sản phẩm đã được cập nhật thành công!";
                    
                    // Ghi log hoạt động
                    logAdminActivity($db, $_SESSION['admin_id'], "Cập nhật sản phẩm: {$title} (ID: {$id})");
                    
                    // Cập nhật lại thông tin sản phẩm
                    $query = "SELECT * FROM products WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(1, $id);
                    $stmt->execute();
                    $product = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Kiểm tra lại trường status
                    if (!isset($product['status'])) {
                        $product['status'] = 'active';
                    }
                    
                    // Cập nhật lại thông tin giá
                    $pricing_query = "SELECT * FROM product_pricing WHERE product_id = ?";
                    $pricing_stmt = $db->prepare($pricing_query);
                    $pricing_stmt->bindParam(1, $id);
                    $pricing_stmt->execute();
                    
                    $pricing = [];
                    while ($row = $pricing_stmt->fetch(PDO::FETCH_ASSOC)) {
                        $pricing[$row['duration_type']] = $row['price'];
                    }
                }
            } else {
                $db->rollBack();
                $error_message = "Không thể cập nhật sản phẩm!";
            }
        } catch (Exception $e) {
            $db->rollBack();
            $error_message = "Lỗi: " . $e->getMessage();
        }
    }
}

// Danh sách icon và màu sắc
$icons = [
    'box', 'code', 'cog', 'desktop', 'download', 'file-alt', 'globe', 'laptop', 
    'mobile-alt', 'robot', 'server', 'shield-alt', 'tools', 'user-shield', 'wrench'
];

$colors = [
    'red', 'orange', 'yellow', 'green', 'teal', 'blue', 'cyan', 'indigo', 'purple', 'pink'
];
?>

<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Chỉnh sửa sản phẩm</h1>
        <a href="products.php" class="text-cyan-500 hover:text-cyan-600">
            <i class="fas fa-arrow-left mr-2"></i> Quay lại danh sách
        </a>
    </div>
    
    <?php if (!empty($success_message)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
            <p><?php echo $success_message; ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
            <p><?php echo $error_message; ?></p>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="edit-product.php?id=<?php echo $id; ?>" class="admin-form">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Thông tin cơ bản -->
            <div class="md:col-span-2">
                <h2 class="text-lg font-semibold text-gray-700 mb-4">Thông tin cơ bản</h2>
            </div>
            
            <div>
                <label for="title" class="block text-gray-700 font-medium mb-2">Tên sản phẩm <span class="text-red-500">*</span></label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($product['title']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-cyan-500" required>
            </div>
            
            <div>
                <label for="badge" class="block text-gray-700 font-medium mb-2">Badge (để trống nếu không có)</label>
                <input type="text" id="badge" name="badge" value="<?php echo isset($product['badge']) ? htmlspecialchars($product['badge']) : ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-cyan-500" placeholder="Ví dụ: Mới, Hot, Giảm giá">
            </div>
            
            <div class="md:col-span-2">
                <label for="description" class="block text-gray-700 font-medium mb-2">Mô tả sản phẩm <span class="text-red-500">*</span></label>
                <textarea id="description" name="description" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-cyan-500" required><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>
            
            <div class="md:col-span-2">
                <label for="features" class="block text-gray-700 font-medium mb-2">Tính năng sản phẩm <span class="text-red-500">*</span></label>
                <textarea id="features" name="features" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-cyan-500" placeholder="Nhập mỗi tính năng trên một dòng hoặc phân cách bằng dấu phẩy" required><?php echo isset($product['features']) ? htmlspecialchars($product['features']) : ''; ?></textarea>
                <p class="text-sm text-gray-500 mt-1">Nhập mỗi tính năng trên một dòng hoặc phân cách bằng dấu phẩy</p>
            </div>
            
            <!-- Biểu tượng và màu sắc -->
            <div>
                <label class="block text-gray-700 font-medium mb-2">Biểu tượng <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-5 gap-2">
                    <?php foreach ($icons as $icon_name): ?>
                    <label class="cursor-pointer">
                        <input type="radio" name="icon" value="<?php echo $icon_name; ?>" class="sr-only" <?php echo (isset($product['icon']) && $product['icon'] == $icon_name) ? 'checked' : ''; ?> required>
                        <div class="flex items-center justify-center h-12 border border-gray-300 rounded-md hover:border-cyan-500 <?php echo (isset($product['icon']) && $product['icon'] == $icon_name) ? 'border-cyan-500 bg-cyan-50' : ''; ?>">
                            <i class="fas fa-<?php echo $icon_name; ?> text-gray-700"></i>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div>
                <label class="block text-gray-700 font-medium mb-2">Màu biểu tượng <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-5 gap-2">
                    <?php foreach ($colors as $color_name): ?>
                    <label class="cursor-pointer">
                        <input type="radio" name="icon_color" value="<?php echo $color_name; ?>" class="sr-only" <?php echo (isset($product['icon_color']) && $product['icon_color'] == $color_name) ? 'checked' : ''; ?> required>
                        <div class="flex items-center justify-center h-12 border border-gray-300 rounded-md hover:border-<?php echo $color_name; ?>-500 <?php echo (isset($product['icon_color']) && $product['icon_color'] == $color_name) ? "border-{$color_name}-500 bg-{$color_name}-50" : ''; ?>">
                            <div class="w-6 h-6 rounded-full bg-<?php echo $color_name; ?>-500"></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Giá sản phẩm -->
            <div class="md:col-span-2">
                <h2 class="text-lg font-semibold text-gray-700 mb-4">Giá sản phẩm</h2>
                <p class="text-sm text-gray-500 mb-4">Thiết lập giá cho các gói dịch vụ khác nhau. Vui lòng nhập ít nhất một gói giá.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="price_3_days" class="block text-gray-700 font-medium mb-2">Giá gói 3 ngày (VND)</label>
                        <input type="number" id="price_3_days" name="price_3_days" value="<?php echo isset($pricing['3_days']) ? htmlspecialchars($pricing['3_days']) : ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-cyan-500" placeholder="Ví dụ: 50000">
                    </div>
                    
                    <div>
                        <label for="price_1_month" class="block text-gray-700 font-medium mb-2">Giá gói 1 tháng (VND)</label>
                        <input type="number" id="price_1_month" name="price_1_month" value="<?php echo isset($pricing['1_month']) ? htmlspecialchars($pricing['1_month']) : ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-cyan-500" placeholder="Ví dụ: 100000">
                    </div>
                    
                    <div>
                        <label for="price_3_months" class="block text-gray-700 font-medium mb-2">Giá gói 3 tháng (VND)</label>
                        <input type="number" id="price_3_months" name="price_3_months" value="<?php echo isset($pricing['3_months']) ? htmlspecialchars($pricing['3_months']) : ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-cyan-500" placeholder="Ví dụ: 250000">
                    </div>
                    
                    <div>
                        <label for="price_6_months" class="block text-gray-700 font-medium mb-2">Giá gói 6 tháng (VND)</label>
                        <input type="number" id="price_6_months" name="price_6_months" value="<?php echo isset($pricing['6_months']) ? htmlspecialchars($pricing['6_months']) : ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-cyan-500" placeholder="Ví dụ: 450000">
                    </div>
                    
                    <div>
                        <label for="price_1_year" class="block text-gray-700 font-medium mb-2">Giá gói 1 năm (VND)</label>
                        <input type="number" id="price_1_year" name="price_1_year" value="<?php echo isset($pricing['1_year']) ? htmlspecialchars($pricing['1_year']) : ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-cyan-500" placeholder="Ví dụ: 800000">
                    </div>
                    
                    <div>
                        <label for="price_lifetime" class="block text-gray-700 font-medium mb-2">Giá gói vĩnh viễn (VND)</label>
                        <input type="number" id="price_lifetime" name="price_lifetime" value="<?php echo isset($pricing['lifetime']) ? htmlspecialchars($pricing['lifetime']) : ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-cyan-500" placeholder="Ví dụ: 2000000">
                    </div>
                </div>
            </div>
            
            <!-- Trạng thái -->
            <div class="md:col-span-2">
                <label class="block text-gray-700 font-medium mb-2">Trạng thái</label>
                <div class="flex items-center space-x-6">
                    <label class="inline-flex items-center">
                        <input type="radio" name="status" value="active" class="text-cyan-500 focus:ring-cyan-500" <?php echo (!isset($product['status']) || $product['status'] == 'active') ? 'checked' : ''; ?>>
                        <span class="ml-2">Hoạt động</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="status" value="inactive" class="text-cyan-500 focus:ring-cyan-500" <?php echo (isset($product['status']) && $product['status'] == 'inactive') ? 'checked' : ''; ?>>
                        <span class="ml-2">Không hoạt động</span>
                    </label>
                </div>
            </div>
        </div>
        
        <div class="mt-8 flex justify-end">
            <button type="submit" class="bg-cyan-500 hover:bg-cyan-600 text-white px-6 py-2 rounded-md">
                <i class="fas fa-save mr-2"></i> Cập nhật sản phẩm
            </button>
        </div>
    </form>
</div>

<script>
// Hiển thị biểu tượng đã chọn
document.querySelectorAll('input[name="icon"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.querySelectorAll('input[name="icon"]').forEach(function(r) {
            const parent = r.parentElement.querySelector('div');
            if (r.checked) {
                parent.classList.add('border-cyan-500', 'bg-cyan-50');
            } else {
                parent.classList.remove('border-cyan-500', 'bg-cyan-50');
            }
        });
    });
});

// Hiển thị màu đã chọn
document.querySelectorAll('input[name="icon_color"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        const color = this.value;
        document.querySelectorAll('input[name="icon_color"]').forEach(function(r) {
            const parent = r.parentElement.querySelector('div');
            const currentColor = r.value;
            if (r.checked) {
                parent.classList.add(`border-${currentColor}-500`, `bg-${currentColor}-50`);
            } else {
                parent.classList.remove(`border-${currentColor}-500`, `bg-${currentColor}-50`);
            }
        });
    });
});
</script>

<?php include 'includes/admin-footer.php'; ?>