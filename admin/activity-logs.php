<?php
require_once '../config/database.php';
include 'includes/admin-header.php';

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// Xử lý lọc và tìm kiếm
$where_conditions = [];
$params = [];

// Lọc theo admin
if (isset($_GET['admin_id']) && !empty($_GET['admin_id'])) {
    $where_conditions[] = "al.admin_id = ?";
    $params[] = $_GET['admin_id'];
}

// Lọc theo ngày
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $where_conditions[] = "al.created_at >= ?";
    $params[] = $_GET['date_from'] . ' 00:00:00';
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $where_conditions[] = "al.created_at <= ?";
    $params[] = $_GET['date_to'] . ' 23:59:59';
}

// Tìm kiếm
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = '%' . $_GET['search'] . '%';
    $where_conditions[] = "(al.action LIKE ? OR al.ip_address LIKE ? OR a.username LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Xây dựng câu truy vấn
$where_clause = !empty($where_conditions) ? " WHERE " . implode(" AND ", $where_conditions) : "";

// Phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 20;
$offset = ($page - 1) * $records_per_page;

// Lấy tổng số bản ghi
$count_query = "SELECT COUNT(*) as total FROM admin_logs al
                JOIN admins a ON al.admin_id = a.id" . $where_clause;
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

// Lấy danh sách nhật ký hoạt động
$query = "SELECT al.*, a.username, a.name as admin_name
          FROM admin_logs al
          JOIN admins a ON al.admin_id = a.id" . 
          $where_clause . 
          " ORDER BY al.created_at DESC LIMIT $offset, $records_per_page";

$stmt = $db->prepare($query);

// Bind params cho main query
if (!empty($params)) {
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindParam($i + 1, $params[$i]);
    }
}

$stmt->execute();

// Lấy danh sách admin cho bộ lọc
$admin_query = "SELECT id, username, name FROM admins ORDER BY name ASC";
$admin_stmt = $db->prepare($admin_query);
$admin_stmt->execute();
$admins = [];
while ($row = $admin_stmt->fetch(PDO::FETCH_ASSOC)) {
    $admins[] = $row;
}
?>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="flex justify-between items-center p-6 border-b">
        <h1 class="text-xl font-semibold text-gray-800">Nhật ký hoạt động</h1>
    </div>
    
    <div class="p-6">
        <!-- Bộ lọc và tìm kiếm -->
        <form method="GET" action="activity-logs.php" class="bg-gray-50 p-4 rounded-lg mb-6">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label for="admin_id" class="block text-sm font-medium text-gray-700 mb-1">Admin</label>
                    <select id="admin_id" name="admin_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <option value="">Tất cả admin</option>
                        <?php foreach ($admins as $admin): ?>
                            <option value="<?php echo $admin['id']; ?>" <?php echo (isset($_GET['admin_id']) && $_GET['admin_id'] == $admin['id']) ? 'selected' : ''; ?>><?php echo $admin['name'] . ' (' . $admin['username'] . ')'; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Từ ngày</label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo isset($_GET['date_from']) ? $_GET['date_from'] : ''; ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>
                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Đến ngày</label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo isset($_GET['date_to']) ? $_GET['date_to'] : ''; ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Tìm kiếm</label>
                    <input type="text" id="search" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" placeholder="Hành động, IP..." class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg flex items-center transition-colors duration-200">
                        <i class="fas fa-filter mr-2"></i> Lọc
                    </button>
                    <a href="activity-logs.php" class="ml-2 bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center transition-colors duration-200">
                        <i class="fas fa-sync-alt mr-2"></i> Reset
                    </a>
                </div>
            </div>
        </form>
        
        <!-- Bảng nhật ký hoạt động -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white rounded-lg overflow-hidden">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="py-3 px-4 text-left font-medium text-sm uppercase">ID</th>
                        <th class="py-3 px-4 text-left font-medium text-sm uppercase">Admin</th>
                        <th class="py-3 px-4 text-left font-medium text-sm uppercase">Hành động</th>
                        <th class="py-3 px-4 text-left font-medium text-sm uppercase">IP</th>
                        <th class="py-3 px-4 text-left font-medium text-sm uppercase">Thời gian</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if ($stmt->rowCount() > 0): ?>
                        <?php while ($log = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-4 text-gray-500"><?php echo $log['id']; ?></td>
                                <td class="py-3 px-4">
                                    <div class="font-medium text-gray-900"><?php echo $log['admin_name']; ?></div>
                                    <div class="text-xs text-gray-500"><?php echo $log['username']; ?></div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="text-gray-800"><?php echo $log['action']; ?></div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-mono text-sm text-gray-600"><?php echo $log['ip_address']; ?></div>
                                </td>
                                <td class="py-3 px-4">
                                    <?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="py-4 px-4 text-center text-gray-500">Không tìm thấy nhật ký hoạt động nào</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Phân trang -->
        <?php if ($total_pages > 1): ?>
        <div class="mt-6 flex justify-between items-center">
            <div class="text-sm text-gray-500">
                Hiển thị <?php echo min($records_per_page, $stmt->rowCount()); ?> / <?php echo $total_records; ?> bản ghi
            </div>
            <div class="flex space-x-1">
                <?php
                // Xây dựng URL cho phân trang
                $params = $_GET;
                unset($params['page']);
                $query_string = http_build_query($params);
                $url = 'activity-logs.php?' . ($query_string ? $query_string . '&' : '');
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

<?php include 'includes/admin-footer.php'; ?>