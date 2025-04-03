<?php
require_once '../config/database.php';
include 'includes/admin-header.php';

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// Xử lý đánh dấu đã đọc
if (isset($_GET['mark_read']) && !empty($_GET['mark_read'])) {
    $id = $_GET['mark_read'];
    
    if ($id == 'all') {
        // Đánh dấu tất cả là đã đọc
        $query = "UPDATE notifications SET is_read = 1 WHERE admin_id = ? OR admin_id IS NULL";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $_SESSION['admin_id']);
        
        if ($stmt->execute()) {
            $success_message = "Tất cả thông báo đã được đánh dấu là đã đọc!";
        }
    } else {
        // Đánh dấu một thông báo là đã đọc
        $query = "UPDATE notifications SET is_read = 1 WHERE id = ? AND (admin_id = ? OR admin_id IS NULL)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->bindParam(2, $_SESSION['admin_id']);
        
        if ($stmt->execute()) {
            $success_message = "Thông báo đã được đánh dấu là đã đọc!";
        }
    }
}

// Xử lý xóa thông báo
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = $_GET['delete'];
    
    if ($id == 'all') {
        // Xóa tất cả thông báo
        $query = "DELETE FROM notifications WHERE admin_id = ? OR admin_id IS NULL";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $_SESSION['admin_id']);
        
        if ($stmt->execute()) {
            $success_message = "Tất cả thông báo đã được xóa!";
        }
    } else {
        // Xóa một thông báo
        $query = "DELETE FROM notifications WHERE id = ? AND (admin_id = ? OR admin_id IS NULL)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->bindParam(2, $_SESSION['admin_id']);
        
        if ($stmt->execute()) {
            $success_message = "Thông báo đã được xóa!";
        }
    }
}

// Xử lý lọc và tìm kiếm
$where_conditions = ["(admin_id = ? OR admin_id IS NULL)"]; // Chỉ hiển thị thông báo của admin hiện tại hoặc thông báo chung
$params = [$_SESSION['admin_id']];

// Lọc theo loại
if (isset($_GET['type']) && !empty($_GET['type'])) {
    $where_conditions[] = "type = ?";
    $params[] = $_GET['type'];
}

// Lọc theo trạng thái đọc
if (isset($_GET['is_read']) && $_GET['is_read'] !== '') {
    $where_conditions[] = "is_read = ?";
    $params[] = $_GET['is_read'];
}

// Tìm kiếm
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = '%' . $_GET['search'] . '%';
    $where_conditions[] = "(title LIKE ? OR message LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
}

// Xây dựng câu truy vấn
$where_clause = " WHERE " . implode(" AND ", $where_conditions);

// Phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 15;
$offset = ($page - 1) * $records_per_page;

// Lấy tổng số bản ghi
$count_query = "SELECT COUNT(*) as total FROM notifications" . $where_clause;
$count_stmt = $db->prepare($count_query);

// Bind params cho count query
for ($i = 0; $i < count($params); $i++) {
    $count_stmt->bindParam($i + 1, $params[$i]);
}

$count_stmt->execute();
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Lấy danh sách thông báo
$query = "SELECT * FROM notifications" . 
          $where_clause . 
          " ORDER BY created_at DESC LIMIT $offset, $records_per_page";

$stmt = $db->prepare($query);

// Bind params cho main query
for ($i = 0; $i < count($params); $i++) {
    $stmt->bindParam($i + 1, $params[$i]);
}

$stmt->execute();
?>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="flex justify-between items-center p-6 border-b">
        <h1 class="text-xl font-semibold text-gray-800">Thông báo</h1>
        <div class="flex space-x-2">
            <a href="notifications.php?mark_read=all" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg flex items-center transition-colors duration-200">
                <i class="fas fa-check-double mr-2"></i> Đánh dấu tất cả đã đọc
            </a>
            <a href="javascript:void(0)" onclick="confirmDeleteAll()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center transition-colors duration-200">
                <i class="fas fa-trash-alt mr-2"></i> Xóa tất cả
            </a>
        </div>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div class="mx-6 mt-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2 text-green-500"></i>
                <p><?php echo $success_message; ?></p>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="p-6">
        <!-- Bộ lọc và tìm kiếm -->
        <form method="GET" action="notifications.php" class="bg-gray-50 p-4 rounded-lg mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Loại thông báo</label>
                    <select id="type" name="type" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <option value="">Tất cả loại</option>
                        <option value="license" <?php echo (isset($_GET['type']) && $_GET['type'] == 'license') ? 'selected' : ''; ?>>License</option>
                        <option value="system" <?php echo (isset($_GET['type']) && $_GET['type'] == 'system') ? 'selected' : ''; ?>>Hệ thống</option>
                        <option value="user" <?php echo (isset($_GET['type']) && $_GET['type'] == 'user') ? 'selected' : ''; ?>>Người dùng</option>
                    </select>
                </div>
                <div>
                    <label for="is_read" class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                    <select id="is_read" name="is_read" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <option value="">Tất cả trạng thái</option>
                        <option value="0" <?php echo (isset($_GET['is_read']) && $_GET['is_read'] === '0') ? 'selected' : ''; ?>>Chưa đọc</option>
                        <option value="1" <?php echo (isset($_GET['is_read']) && $_GET['is_read'] === '1') ? 'selected' : ''; ?>>Đã đọc</option>
                    </select>
                </div>
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Tìm kiếm</label>
                    <input type="text" id="search" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" placeholder="Tiêu đề, nội dung..." class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg flex items-center transition-colors duration-200">
                        <i class="fas fa-filter mr-2"></i> Lọc
                    </button>
                    <a href="notifications.php" class="ml-2 bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center transition-colors duration-200">
                        <i class="fas fa-sync-alt mr-2"></i> Reset
                    </a>
                </div>
            </div>
        </form>
        
        <!-- Danh sách thông báo -->
        <div class="space-y-4">
            <?php if ($stmt->rowCount() > 0): ?>
                <?php while ($notification = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <div class="bg-white border rounded-lg overflow-hidden <?php echo $notification['is_read'] ? 'border-gray-200' : 'border-primary-300 bg-primary-50'; ?>">
                        <div class="p-4">
                            <div class="flex justify-between items-start">
                                <div class="flex items-start space-x-3">
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
                                    <div class="h-10 w-10 rounded-full <?php echo $icon_bg; ?> flex items-center justify-center">
                                        <i class="<?php echo $icon_class; ?>"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-md font-medium text-gray-900"><?php echo $notification['title']; ?></h3>
                                        <p class="text-sm text-gray-600 mt-1"><?php echo $notification['message']; ?></p>
                                        <p class="text-xs text-gray-500 mt-2">
                                            <?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?>
                                            <?php if (!$notification['is_read']): ?>
                                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-800">Mới</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <?php if (!$notification['is_read']): ?>
                                    <a href="notifications.php?mark_read=<?php echo $notification['id']; ?>" class="text-primary-600 hover:text-primary-800" title="Đánh dấu đã đọc">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $notification['id']; ?>)" class="text-red-600 hover:text-red-800" title="Xóa">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </div>
                            <?php if (!empty($notification['link'])): ?>
                            <div class="mt-3">
                                <a href="<?php echo $notification['link']; ?>" class="text-primary-600 hover:text-primary-800 text-sm flex items-center">
                                    <i class="fas fa-external-link-alt mr-1"></i> Xem chi tiết
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="bg-gray-50 p-8 rounded-lg text-center">
                    <div class="h-16 w-16 mx-auto bg-gray-200 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-bell-slash text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-1">Không có thông báo nào</h3>
                    <p class="text-gray-500">Bạn sẽ nhận được thông báo khi có hoạt động mới.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Phân trang -->
        <?php if ($total_pages > 1): ?>
        <div class="mt-6 flex justify-between items-center">
            <div class="text-sm text-gray-500">
                Hiển thị <?php echo min($records_per_page, $stmt->rowCount()); ?> / <?php echo $total_records; ?> thông báo
            </div>
            <div class="flex space-x-1">
                <?php
                // Xây dựng URL cho phân trang
                $params = $_GET;
                unset($params['page']);
                $query_string = http_build_query($params);
                $url = 'notifications.php?' . ($query_string ? $query_string . '&' : '');
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
            <p class="text-gray-600 mb-6">Bạn có chắc chắn muốn xóa thông báo này? Hành động này không thể hoàn tác.</p>
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

<!-- Modal xác nhận xóa tất cả -->
<div id="delete-all-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
        <div class="text-center">
            <i class="fas fa-exclamation-triangle text-red-500 text-5xl mb-4"></i>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Xác nhận xóa tất cả</h3>
            <p class="text-gray-600 mb-6">Bạn có chắc chắn muốn xóa tất cả thông báo? Hành động này không thể hoàn tác.</p>
        </div>
        <div class="flex justify-center space-x-4">
            <button id="cancel-delete-all" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg transition-colors duration-200">
                Hủy bỏ
            </button>
            <a href="notifications.php?delete=all" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors duration-200">
                Xóa tất cả
            </a>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id) {
        document.getElementById('delete-modal').classList.remove('hidden');
        document.getElementById('confirm-delete').href = 'notifications.php?delete=' + id;
    }
    
    document.getElementById('cancel-delete').addEventListener('click', function() {
        document.getElementById('delete-modal').classList.add('hidden');
    });
    
    function confirmDeleteAll() {
        document.getElementById('delete-all-modal').classList.remove('hidden');
    }
    
    document.getElementById('cancel-delete-all').addEventListener('click', function() {
        document.getElementById('delete-all-modal').classList.add('hidden');
    });
</script>

<?php include 'includes/admin-footer.php'; ?>