<?php
require_once '../config/database.php';
require_once 'includes/functions.php';
include 'includes/admin-header.php';

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// Xử lý xóa sản phẩm
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Lấy thông tin sản phẩm trước khi xóa
    $product_query = "SELECT title FROM products WHERE id = ?";
    $product_stmt = $db->prepare($product_query);
    $product_stmt->bindParam(1, $id);
    $product_stmt->execute();
    $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Xóa các gói giá của sản phẩm trước
    $delete_pricing_query = "DELETE FROM product_pricing WHERE product_id = ?";
    $delete_pricing_stmt = $db->prepare($delete_pricing_query);
    $delete_pricing_stmt->bindParam(1, $id);
    $delete_pricing_stmt->execute();
    
    // Xóa sản phẩm
    $query = "DELETE FROM products WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $id);
    
    if ($stmt->execute()) {
        // Ghi log hoạt động
        logAdminActivity($db, $_SESSION['admin_id'], "Xóa sản phẩm: {$product['title']} (ID: $id)");
        
        // Tạo thông báo
        createNotification(
            $db, 
            'system', 
            'Sản phẩm đã bị xóa', 
            "Sản phẩm {$product['title']} đã bị xóa khỏi hệ thống.", 
            null
        );
        
        $success_message = "Sản phẩm đã được xóa thành công!";
    } else {
        $error_message = "Không thể xóa sản phẩm!";
    }
}

// Xử lý lọc và tìm kiếm
$where_conditions = [];
$params = [];

// Lọc theo trạng thái
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $where_conditions[] = "p.status = ?";
    $params[] = $_GET['status'];
}

// Tìm kiếm
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = '%' . $_GET['search'] . '%';
    $where_conditions[] = "(p.title LIKE ? OR p.description LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
}

// Xây dựng câu truy vấn
$where_clause = !empty($where_conditions) ? " WHERE " . implode(" AND ", $where_conditions) : "";

// Phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Đếm tổng số bản ghi
$count_query = "SELECT COUNT(*) as total FROM products p" . $where_clause;
$count_stmt = $db->prepare($count_query);
for ($i = 0; $i < count($params); $i++) {
    $count_stmt->bindParam($i + 1, $params[$i]);
}
$count_stmt->execute();
$total_rows = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_rows / $records_per_page);

// Lấy danh sách sản phẩm
$query = "SELECT p.*, 
          (SELECT COUNT(*) FROM product_pricing WHERE product_id = p.id) as pricing_count,
          (SELECT MIN(price) FROM product_pricing WHERE product_id = p.id) as min_price,
          (SELECT MAX(price) FROM product_pricing WHERE product_id = p.id) as max_price
          FROM products p" . $where_clause . " ORDER BY p.id DESC LIMIT ?, ?";
$stmt = $db->prepare($query);
$position = 1;
foreach ($params as $param) {
    $stmt->bindParam($position, $param);
    $position++;
}
$stmt->bindParam($position, $offset, PDO::PARAM_INT);
$position++;
$stmt->bindParam($position, $records_per_page, PDO::PARAM_INT);
$stmt->execute();
?>

<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Quản lý sản phẩm</h1>
        <a href="add-product.php" class="bg-cyan-500 hover:bg-cyan-600 text-white px-4 py-2 rounded-md">
            <i class="fas fa-plus mr-2"></i> Thêm sản phẩm mới
        </a>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
            <p><?php echo $success_message; ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
            <p><?php echo $error_message; ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Bộ lọc và tìm kiếm -->
    <div class="mb-6 bg-gray-50 p-4 rounded-lg">
        <form method="GET" action="products.php" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Tìm kiếm</label>
                <input type="text" id="search" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" placeholder="Tên sản phẩm..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500">
            </div>
            
            <div class="flex-1 min-w-[200px]">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] == 'active') ? 'selected' : ''; ?>>Hoạt động</option>
                    <option value="inactive" <?php echo (isset($_GET['status']) && $_GET['status'] == 'inactive') ? 'selected' : ''; ?>>Không hoạt động</option>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="bg-cyan-500 hover:bg-cyan-600 text-white px-4 py-2 rounded-md">
                    <i class="fas fa-search mr-2"></i> Lọc
                </button>
                
                <?php if (isset($_GET['search']) || isset($_GET['status'])): ?>
                <a href="products.php" class="ml-2 bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">
                    <i class="fas fa-times mr-2"></i> Xóa bộ lọc
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- Danh sách sản phẩm -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sản phẩm</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mô tả</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Khoảng giá</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if ($stmt->rowCount() > 0): ?>
                    <?php while ($product = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-4 px-4 text-sm text-gray-500"><?php echo $product['id']; ?></td>
                        <td class="py-4 px-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-full bg-<?php echo $product['icon_color']; ?>-100">
                                    <i class="fas fa-<?php echo $product['icon']; ?> text-<?php echo $product['icon_color']; ?>-500"></i>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo $product['title']; ?></div>
                                    <?php if (!empty($product['badge'])): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-800">
                                        <?php echo $product['badge']; ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-4 text-sm text-gray-500">
                            <?php echo mb_strlen($product['description']) > 100 ? mb_substr($product['description'], 0, 100) . '...' : $product['description']; ?>
                        </td>
                        <td class="py-4 px-4 text-sm text-gray-500">
                            <?php if ($product['pricing_count'] > 0): ?>
                                <?php if ($product['min_price'] == $product['max_price']): ?>
                                    <?php echo number_format($product['min_price'], 0, ',', '.'); ?>đ
                                <?php else: ?>
                                    <?php echo number_format($product['min_price'], 0, ',', '.'); ?>đ - <?php echo number_format($product['max_price'], 0, ',', '.'); ?>đ
                                <?php endif; ?>
                                <div class="text-xs text-gray-400 mt-1"><?php echo $product['pricing_count']; ?> gói giá</div>
                            <?php else: ?>
                                <span class="text-red-500">Chưa có giá</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-4 text-sm">
                            <?php if (isset($product['status']) && $product['status'] == 'active'): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Hoạt động
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Không hoạt động
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-4 text-sm font-medium">
                            <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="text-cyan-600 hover:text-cyan-900 mr-3">
                                <i class="fas fa-edit"></i> Sửa
                            </a>
                            <a href="#" onclick="confirmDelete(<?php echo $product['id']; ?>, '<?php echo $product['title']; ?>')" class="text-red-600 hover:text-red-900">
                                <i class="fas fa-trash-alt"></i> Xóa
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="py-4 px-4 text-center text-gray-500">Không có sản phẩm nào</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Phân trang -->
    <?php if ($total_pages > 1): ?>
    <div class="mt-6 flex justify-between items-center">
        <div class="text-sm text-gray-500">
            Hiển thị <?php echo min($records_per_page, $total_rows); ?> / <?php echo $total_rows; ?> sản phẩm
        </div>
        <div class="flex space-x-1">
            <?php if ($page > 1): ?>
            <a href="products.php?page=<?php echo $page - 1; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?>" class="px-3 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $total_pages); $i++): ?>
            <a href="products.php?page=<?php echo $i; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?>" class="px-3 py-1 <?php echo $i == $page ? 'bg-cyan-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> rounded-md">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <a href="products.php?page=<?php echo $page + 1; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?>" class="px-3 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function confirmDelete(id, title) {
    if (confirm('Bạn có chắc chắn muốn xóa sản phẩm "' + title + '" không?')) {
        window.location.href = 'products.php?delete=' + id;
    }
}
</script>

<?php include 'includes/admin-footer.php'; ?>