<?php
require_once '../config/database.php';
require_once 'includes/functions.php';
include 'includes/admin-header.php';

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// Xử lý xóa người dùng
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = $_GET['delete'];
    
    $query = "DELETE FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $id);
    
    if ($stmt->execute()) {
        // Ghi log hoạt động
        logAdminActivity($db, $_SESSION['admin_id'], "Xóa người dùng ID: $id");
        
        // Tạo thông báo
        createNotification(
            $db, 
            'user', 
            'Người dùng đã bị xóa', 
            "Người dùng ID: $id đã bị xóa khỏi hệ thống.", 
            null
        );
        
        $success_message = "Người dùng đã được xóa thành công!";
    } else {
        $error_message = "Không thể xóa người dùng!";
    }
}

// Xử lý lọc và tìm kiếm
$where_conditions = [];
$params = [];

// Lọc theo trạng thái
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $where_conditions[] = "status = ?";
    $params[] = $_GET['status'];
}

// Tìm kiếm
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = '%' . $_GET['search'] . '%';
    $where_conditions[] = "(username LIKE ? OR email LIKE ? OR name LIKE ? OR phone LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Xây dựng câu truy vấn
$where_clause = !empty($where_conditions) ? " WHERE " . implode(" AND ", $where_conditions) : "";

// Phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Lấy tổng số người dùng
$count_query = "SELECT COUNT(*) as total FROM users" . $where_clause;
$count_stmt = $db->prepare($count_query);

// Bind params cho count query
if (!empty($params)) {
    for ($i = 0; $i < count($params); $i++) {
        $count_stmt->bindParam($i + 1, $params[$i]);
    }
}

$count_stmt->execute();
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Lấy danh sách người dùng
$query = "SELECT * FROM users" . 
          $where_clause . 
          " ORDER BY id DESC LIMIT $offset, $records_per_page";

$stmt = $db->prepare($query);

// Bind params cho main query
if (!empty($params)) {
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindParam($i + 1, $params[$i]);
    }
}

$stmt->execute();
?>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center p-6 border-b">
        <h1 class="text-xl font-semibold text-gray-800">Quản lý người dùng</h1>
        <a href="add-user.php" class="mt-3 md:mt-0 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg flex items-center transition-colors duration-200">
            <i class="fas fa-plus mr-2"></i> Thêm người dùng mới
        </a>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div class="mx-6 mt-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2 text-green-500"></i>
                <p><?php echo $success_message; ?></p>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="mx-6 mt-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2 text-red-500"></i>
                <p><?php echo $error_message; ?></p>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="p-6">
        <!-- Bộ lọc và tìm kiếm -->
        <form method="GET" action="users.php" class="bg-gray-50 p-4 rounded-lg mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                    <select id="status" name="status" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] == 'active') ? 'selected' : ''; ?>>Hoạt động</option>
                        <option value="inactive" <?php echo (isset($_GET['status']) && $_GET['status'] == 'inactive') ? 'selected' : ''; ?>>Không hoạt động</option>
                        <option value="banned" <?php echo (isset($_GET['status']) && $_GET['status'] == 'banned') ? 'selected' : ''; ?>>Bị cấm</option>
                    </select>
                </div>
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Tìm kiếm</label>
                    <input type="text" id="search" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" placeholder="Tên, email, số điện thoại..." class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg flex items-center transition-colors duration-200">
                        <i class="fas fa-filter mr-2"></i> Lọc
                    </button>
                    <a href="users.php" class="ml-2 bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center transition-colors duration-200">
                        <i class="fas fa-sync-alt mr-2"></i> Reset
                    </a>
                </div>
            </div>
        </form>
        
        <!-- Thống kê nhanh -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <?php
            // Đếm số người dùng theo trạng thái
            $status_counts = [
                'active' => 0,
                'inactive' => 0,
                'banned' => 0
            ];
            
            $status_query = "SELECT status, COUNT(*) as count FROM users GROUP BY status";
            $status_stmt = $db->prepare($status_query);
            $status_stmt->execute();
            
            while ($row = $status_stmt->fetch(PDO::FETCH_ASSOC)) {
                if (isset($status_counts[$row['status']])) {
                    $status_counts[$row['status']] = $row['count'];
                }
            }
            ?>
            
            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                <div class="flex items-center">
                    <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center mr-3">
                        <i class="fas fa-user-check text-green-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Đang hoạt động</p>
                        <p class="text-lg font-semibold text-gray-900"><?php echo $status_counts['active']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                <div class="flex items-center">
                    <div class="h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center mr-3">
                        <i class="fas fa-user-clock text-yellow-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Không hoạt động</p>
                        <p class="text-lg font-semibold text-gray-900"><?php echo $status_counts['inactive']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                <div class="flex items-center">
                    <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center mr-3">
                        <i class="fas fa-user-slash text-red-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Bị cấm</p>
                        <p class="text-lg font-semibold text-gray-900"><?php echo $status_counts['banned']; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bảng danh sách người dùng -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white rounded-lg overflow-hidden">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="py-3 px-4 text-left font-medium text-sm uppercase">ID</th>
                        <th class="py-3 px-4 text-left font-medium text-sm uppercase">Tên người dùng</th>
                        <th class="py-3 px-4 text-left font-medium text-sm uppercase">Email</th>
                        <th class="py-3 px-4 text-left font-medium text-sm uppercase">Số điện thoại</th>
                        <th class="py-3 px-4 text-left font-medium text-sm uppercase">Trạng thái</th>
                        <th class="py-3 px-4 text-left font-medium text-sm uppercase">Ngày đăng ký</th>
                        <th class="py-3 px-4 text-center font-medium text-sm uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if ($stmt->rowCount() > 0): ?>
                        <?php while ($user = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-4"><?php echo $user['id']; ?></td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-semibold mr-3">
                                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900"><?php echo $user['name']; ?></div>
                                            <div class="text-xs text-gray-500"><?php echo $user['username']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4"><?php echo $user['email']; ?></td>
                                <td class="py-3 px-4"><?php echo $user['phone'] ?: 'N/A'; ?></td>
                                <td class="py-3 px-4">
                                    <?php
                                    switch ($user['status']) {
                                        case 'active':
                                            echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"><i class="fas fa-check-circle mr-1"></i> Hoạt động</span>';
                                            break;
                                        case 'inactive':
                                            echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"><i class="fas fa-clock mr-1"></i> Không hoạt động</span>';
                                            break;
                                        case 'banned':
                                            echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"><i class="fas fa-ban mr-1"></i> Bị cấm</span>';
                                            break;
                                        default:
                                            echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">' . $user['status'] . '</span>';
                                    }
                                    ?>
                                </td>
                                <td class="py-3 px-4">
                                    <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex justify-center space-x-2">
                                        <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="text-primary-600 hover:text-primary-800" title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $user['id']; ?>)" class="text-red-600 hover:text-red-800" title="Xóa">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="py-4 px-4 text-center text-gray-500">Không tìm thấy người dùng nào</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Phân trang -->
        <?php if ($total_pages > 1): ?>
        <div class="mt-6 flex justify-between items-center">
            <div class="text-sm text-gray-500">
                Hiển thị <?php echo min($records_per_page, $stmt->rowCount()); ?> / <?php echo $total_records; ?> người dùng
            </div>
            <div class="flex space-x-1">
                <?php
                // Xây dựng URL cho phân trang
                $params = $_GET;
                unset($params['page']);
                $query_string = http_build_query($params);
                $url = 'users.php?' . ($query_string ? $query_string . '&' : '');
                ?>
                
                <?php if ($page > 1): ?>
                <a href="<?php echo $url; ?>page=1" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-angle-double-left"></i>
                </a>
                <a href="<?php echo $url; ?>page=<?php echo $page - 1; ?>" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-angle-left"></i>
                </a>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                <a href="<?php echo $url; ?>page=<?php echo $i; ?>" class="px-3 py-1 rounded-md <?php echo $i == $page ? 'bg-primary-600 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50'; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <a href="<?php echo $url; ?>page=<?php echo $page + 1; ?>" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-angle-right"></i>
                </a>
                <a href="<?php echo $url; ?>page=<?php echo $total_pages; ?>" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-angle-double-right"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal xác nhận xóa -->
<div id="delete-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
        <div class="text-center">
            <i class="fas fa-exclamation-triangle text-red-500 text-5xl mb-4"></i>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Xác nhận xóa</h3>
            <p class="text-gray-600 mb-6">Bạn có chắc chắn muốn xóa người dùng này? Hành động này không thể hoàn tác.</p>
        </div>
        <div class="flex justify-center space-x-4">
            <button id="cancel-delete" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg transition-colors duration-200">
                Hủy bỏ
            </button>
            <a id="confirm-delete" href="#" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors duration-200">
                Xóa
            </a>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id) {
        document.getElementById('delete-modal').classList.remove('hidden');
        document.getElementById('confirm-delete').href = 'users.php?delete=' + id;
    }
    
    document.getElementById('cancel-delete').addEventListener('click', function() {
        document.getElementById('delete-modal').classList.add('hidden');
    });
</script>

<?php include 'includes/admin-footer.php'; ?>