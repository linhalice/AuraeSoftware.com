<?php
require_once '../config/database.php';
require_once 'includes/functions.php';
include 'includes/admin-header.php';

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// Xử lý xóa license
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $license_id = $_GET['delete'];
    
    $query = "DELETE FROM licenses WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $license_id);
    
    if ($stmt->execute()) {
        // Ghi log hoạt động
        logAdminActivity($db, $_SESSION['admin_id'], "Xóa license (ID: $license_id)");
        
        $success_message = "License đã được xóa thành công!";
    } else {
        $error_message = "Không thể xóa license!";
    }
}

// Thiết lập phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Thiết lập tìm kiếm và lọc
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$product_filter = isset($_GET['product_id']) ? $_GET['product_id'] : '';

// Xây dựng câu truy vấn cơ sở
$base_query = "FROM licenses l 
               JOIN products p ON l.product_id = p.id";

// Thêm điều kiện tìm kiếm và lọc
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(l.license_key LIKE ? OR l.customer_name LIKE ? OR l.customer_email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($status_filter)) {
    $where_conditions[] = "l.status = ?";
    $params[] = $status_filter;
}

if (!empty($product_filter)) {
    $where_conditions[] = "l.product_id = ?";
    $params[] = $product_filter;
}

// Kết hợp các điều kiện
$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Truy vấn đếm tổng số bản ghi
$count_query = "SELECT COUNT(*) as total $base_query $where_clause";
$count_stmt = $db->prepare($count_query);

// Gán tham số cho truy vấn đếm
for ($i = 0; $i < count($params); $i++) {
    $count_stmt->bindParam($i + 1, $params[$i]);
}

$count_stmt->execute();
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Truy vấn lấy dữ liệu với phân trang
$query = "SELECT l.*, p.title as product_name ";

// Kiểm tra xem cột user_id có tồn tại trong bảng licenses không
$check_column_query = "SHOW COLUMNS FROM licenses LIKE 'user_id'";
$check_column_stmt = $db->prepare($check_column_query);
$check_column_stmt->execute();
$has_user_id = $check_column_stmt->rowCount() > 0;

// Nếu có cột user_id, thêm join với bảng users
if ($has_user_id) {
    $query .= ", IFNULL(u.name, 'N/A') as user_name ";
    $base_query .= " LEFT JOIN users u ON l.user_id = u.id ";
}

$query .= $base_query . " " . $where_clause . " ORDER BY l.created_at DESC LIMIT $offset, $records_per_page";
$stmt = $db->prepare($query);

// Gán tham số cho truy vấn chính
for ($i = 0; $i < count($params); $i++) {
    $stmt->bindParam($i + 1, $params[$i]);
}

$stmt->execute();

// Lấy danh sách sản phẩm cho bộ lọc
$products_query = "SELECT id, title FROM products ORDER BY title ASC";
$products_stmt = $db->prepare($products_query);
$products_stmt->execute();
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Quản lý License</h1>
    </div>
    
    <?php if (isset($success_message)): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2 text-green-500"></i>
            <p><?php echo $success_message; ?></p>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2 text-red-500"></i>
            <p><?php echo $error_message; ?></p>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Bộ lọc và tìm kiếm -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <form method="GET" action="licenses.php" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Tìm kiếm</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="License key, tên, email..." class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
            </div>
            
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Chờ kích hoạt</option>
                    <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Đã kích hoạt</option>
                    <option value="expired" <?php echo $status_filter == 'expired' ? 'selected' : ''; ?>>Hết hạn</option>
                    <option value="revoked" <?php echo $status_filter == 'revoked' ? 'selected' : ''; ?>>Đã thu hồi</option>
                </select>
            </div>
            
            <div>
                <label for="product_id" class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm</label>
                <select id="product_id" name="product_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                    <option value="">Tất cả sản phẩm</option>
                    <?php foreach ($products as $product): ?>
                    <option value="<?php echo $product['id']; ?>" <?php echo $product_filter == $product['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($product['title']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-lg mr-2">
                    <i class="fas fa-search mr-2"></i> Lọc
                </button>
                <a href="licenses.php" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg">
                    <i class="fas fa-redo mr-2"></i> Đặt lại
                </a>
            </div>
        </form>
    </div>
    
    <!-- Bảng danh sách license -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">License Key</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sản phẩm</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thông tin khách hàng</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời hạn</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày hết hạn</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if ($stmt->rowCount() > 0): ?>
                        <?php while ($license = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($license['license_key']); ?></div>
                                    <?php if (!empty($license['machine_id'])): ?>
                                    <div class="text-xs text-gray-500">Machine ID: <?php echo htmlspecialchars($license['machine_id']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($license['product_name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if (!empty($license['customer_name']) || !empty($license['customer_email'])): ?>
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($license['customer_name'] ?? 'N/A'); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($license['customer_email'] ?? 'N/A'); ?></div>
                                    <?php else: ?>
                                    <div class="text-sm text-gray-500">Không có thông tin</div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo !empty($license['duration_type']) ? getDurationLabel($license['duration_type']) : 'Chưa thiết lập'; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if (!empty($license['duration_type']) && $license['duration_type'] == 'lifetime'): ?>
                                    <div class="text-sm text-gray-900">Vĩnh viễn</div>
                                    <?php elseif (!empty($license['expires_at'])): ?>
                                    <div class="text-sm text-gray-900"><?php echo date('d/m/Y', strtotime($license['expires_at'])); ?></div>
                                    <?php else: ?>
                                    <div class="text-sm text-gray-500">Chưa thiết lập</div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php echo getLicenseStatusLabel($license['status']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <?php if ($license['status'] == 'pending'): ?>
                                        <a href="activate-license.php?id=<?php echo $license['id']; ?>" class="text-green-600 hover:text-green-900" title="Kích hoạt">
                                            <i class="fas fa-check-circle"></i>
                                        </a>
                                        <?php endif; ?>
                                        <a href="edit-license.php?id=<?php echo $license['id']; ?>" class="text-primary-600 hover:text-primary-900" title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $license['id']; ?>)" class="text-red-600 hover:text-red-900" title="Xóa">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                Không tìm thấy license nào
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Phân trang -->
    <?php if ($total_pages > 1): ?>
    <div class="mt-6 flex justify-between items-center">
        <div class="text-sm text-gray-500">
            Hiển thị <?php echo min($total_records, $offset + 1); ?> đến <?php echo min($total_records, $offset + $records_per_page); ?> của <?php echo $total_records; ?> license
        </div>
        <div class="flex space-x-1">
            <?php if ($page > 1): ?>
            <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($product_filter) ? '&product_id=' . urlencode($product_filter) : ''; ?>" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                <i class="fas fa-angle-double-left"></i>
            </a>
            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($product_filter) ? '&product_id=' . urlencode($product_filter) : ''; ?>" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                <i class="fas fa-angle-left"></i>
            </a>
            <?php endif; ?>
            
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++):
            ?>
            <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($product_filter) ? '&product_id=' . urlencode($product_filter) : ''; ?>" class="px-3 py-1 <?php echo $i == $page ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?> border border-gray-300 rounded-md text-sm font-medium">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($product_filter) ? '&product_id=' . urlencode($product_filter) : ''; ?>" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                <i class="fas fa-angle-right"></i>
            </a>
            <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($product_filter) ? '&product_id=' . urlencode($product_filter) : ''; ?>" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                <i class="fas fa-angle-double-right"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Script xác nhận xóa -->
<script>
function confirmDelete(id) {
    if (confirm('Bạn có chắc chắn muốn xóa license này không?')) {
        window.location.href = 'licenses.php?delete=' + id;
    }
}
</script>

<?php include 'includes/admin-footer.php'; ?>