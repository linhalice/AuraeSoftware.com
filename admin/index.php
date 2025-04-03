<?php
require_once '../config/database.php';
include 'includes/admin-header.php';

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// Đếm số lượng sản phẩm
$query = "SELECT COUNT(*) as total FROM products";
$stmt = $db->prepare($query);
$stmt->execute();
$product_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Đếm số lượng license
$query = "SELECT COUNT(*) as total FROM licenses";
$stmt = $db->prepare($query);
$stmt->execute();
$license_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Đếm số lượng license theo trạng thái
$query = "SELECT status, COUNT(*) as count FROM licenses GROUP BY status";
$stmt = $db->prepare($query);
$stmt->execute();
$license_stats = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $license_stats[$row['status']] = $row['count'];
}

// Lấy license mới nhất
$query = "SELECT l.*, p.title as product_name FROM licenses l 
          JOIN products p ON l.product_id = p.id 
          ORDER BY l.created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_licenses = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $recent_licenses[] = $row;
}

// Tính tổng số license theo từng trạng thái
$active_count = isset($license_stats['active']) ? $license_stats['active'] : 0;
$pending_count = isset($license_stats['pending']) ? $license_stats['pending'] : 0;
$expired_count = isset($license_stats['expired']) ? $license_stats['expired'] : 0;
$revoked_count = isset($license_stats['revoked']) ? $license_stats['revoked'] : 0;

// Tính phần trăm cho biểu đồ
$total_licenses = $license_count > 0 ? $license_count : 1; // Tránh chia cho 0
$active_percent = round(($active_count / $total_licenses) * 100);
$pending_percent = round(($pending_count / $total_licenses) * 100);
$expired_percent = round(($expired_count / $total_licenses) * 100);
$revoked_percent = round(($revoked_count / $total_licenses) * 100);
?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Card 1: Tổng sản phẩm -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium uppercase">Tổng sản phẩm</p>
                    <h2 class="text-gray-800 text-3xl font-bold mt-2"><?php echo $product_count; ?></h2>
                </div>
                <div class="h-12 w-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-box text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3 border-t">
            <a href="products.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center">
                Xem chi tiết <i class="fas fa-arrow-right ml-2 text-xs"></i>
            </a>
        </div>
    </div>
    
    <!-- Card 2: Tổng license -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium uppercase">Tổng license</p>
                    <h2 class="text-gray-800 text-3xl font-bold mt-2"><?php echo $license_count; ?></h2>
                </div>
                <div class="h-12 w-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-key text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3 border-t">
            <a href="licenses.php" class="text-green-600 hover:text-green-800 text-sm font-medium flex items-center">
                Xem chi tiết <i class="fas fa-arrow-right ml-2 text-xs"></i>
            </a>
        </div>
    </div>
    
    <!-- Card 3: License đã kích hoạt -->
    <div class="bg-purple-600 rounded-lg shadow-sm overflow-hidden text-white">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-200 text-sm font-medium uppercase">LICENSE ĐÃ KÍCH HOẠT</p>
                    <h2 class="text-white text-3xl font-bold mt-2"><?php echo $active_count; ?></h2>
                </div>
                <div class="h-12 w-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-white text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-purple-700 px-6 py-3">
            <a href="licenses.php?status=active" class="text-purple-200 hover:text-white text-sm font-medium flex items-center">
                Xem chi tiết <i class="fas fa-arrow-right ml-2 text-xs"></i>
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- License gần đây -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-sm">
            <div class="flex justify-between items-center p-6 border-b">
                <h2 class="text-lg font-semibold text-gray-800">License gần đây</h2>
                <a href="licenses.php" class="text-sm text-blue-600 hover:text-blue-800 flex items-center">
                    Xem tất cả <i class="fas fa-arrow-right ml-1 text-xs"></i>
                </a>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">LICENSE KEY</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SẢN PHẨM</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">KHÁCH HÀNG</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">TRẠNG THÁI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (count($recent_licenses) > 0): ?>
                            <?php foreach ($recent_licenses as $license): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $license['license_key']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $license['product_name']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $license['customer_name']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php
                                        switch ($license['status']) {
                                            case 'active':
                                                echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Đã kích hoạt</span>';
                                                break;
                                            case 'pending':
                                                echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Chờ kích hoạt</span>';
                                                break;
                                            case 'expired':
                                                echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Hết hạn</span>';
                                                break;
                                            case 'revoked':
                                                echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Đã thu hồi</span>';
                                                break;
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">Không có license nào</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (count($recent_licenses) > 0): ?>
            <div class="bg-gray-50 px-6 py-3 border-t text-right">
                <a href="licenses.php" class="text-sm text-blue-600 hover:text-blue-800">
                    Xem tất cả <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Thống kê license -->
    <div>
        <div class="bg-white rounded-lg shadow-sm">
            <div class="p-6 border-b">
                <h2 class="text-lg font-semibold text-gray-800">Thống kê license</h2>
            </div>
            
            <div class="p-6 space-y-6">
                <!-- Đã kích hoạt -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center">
                            <div class="w-3 h-3 rounded-full bg-green-500 mr-2"></div>
                            <span class="text-sm text-gray-600">Đã kích hoạt</span>
                        </div>
                        <div class="flex items-center">
                            <span class="text-sm font-medium text-gray-900"><?php echo $active_count; ?></span>
                            <span class="text-xs text-gray-500 ml-1">(<?php echo $active_percent; ?>%)</span>
                        </div>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo $active_percent; ?>%"></div>
                    </div>
                </div>
                
                <!-- Chờ kích hoạt -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center">
                            <div class="w-3 h-3 rounded-full bg-yellow-500 mr-2"></div>
                            <span class="text-sm text-gray-600">Chờ kích hoạt</span>
                        </div>
                        <div class="flex items-center">
                            <span class="text-sm font-medium text-gray-900"><?php echo $pending_count; ?></span>
                            <span class="text-xs text-gray-500 ml-1">(<?php echo $pending_percent; ?>%)</span>
                        </div>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-yellow-500 h-2 rounded-full" style="width: <?php echo $pending_percent; ?>%"></div>
                    </div>
                </div>
                
                <!-- Hết hạn -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center">
                            <div class="w-3 h-3 rounded-full bg-red-500 mr-2"></div>
                            <span class="text-sm text-gray-600">Hết hạn</span>
                        </div>
                        <div class="flex items-center">
                            <span class="text-sm font-medium text-gray-900"><?php echo $expired_count; ?></span>
                            <span class="text-xs text-gray-500 ml-1">(<?php echo $expired_percent; ?>%)</span>
                        </div>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-red-500 h-2 rounded-full" style="width: <?php echo $expired_percent; ?>%"></div>
                    </div>
                </div>
                
                <!-- Đã thu hồi -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center">
                            <div class="w-3 h-3 rounded-full bg-gray-500 mr-2"></div>
                            <span class="text-sm text-gray-600">Đã thu hồi</span>
                        </div>
                        <div class="flex items-center">
                            <span class="text-sm font-medium text-gray-900"><?php echo $revoked_count; ?></span>
                            <span class="text-xs text-gray-500 ml-1">(<?php echo $revoked_percent; ?>%)</span>
                        </div>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gray-500 h-2 rounded-full" style="width: <?php echo $revoked_percent; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Thêm một card thống kê nhanh -->
        <div class="bg-white rounded-lg shadow-sm mt-6">
            <div class="p-6">
                <h3 class="text-base font-medium text-gray-800 mb-4">Hoạt động gần đây</h3>
                
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                            <i class="fas fa-plus text-blue-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">License mới được tạo</p>
                            <p class="text-xs text-gray-500 mt-1">Vài phút trước</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex-shrink-0 h-8 w-8 rounded-full bg-green-100 flex items-center justify-center mr-3">
                            <i class="fas fa-check text-green-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">License được kích hoạt</p>
                            <p class="text-xs text-gray-500 mt-1">1 giờ trước</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex-shrink-0 h-8 w-8 rounded-full bg-purple-100 flex items-center justify-center mr-3">
                            <i class="fas fa-user text-purple-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Khách hàng mới đăng ký</p>
                            <p class="text-xs text-gray-500 mt-1">3 giờ trước</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Thêm biểu đồ thống kê -->
<div class="mt-6 bg-white rounded-lg shadow-sm p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-6">Thống kê license theo tháng</h2>
    
    <div class="h-80">
        <canvas id="licenseChart"></canvas>
    </div>
</div>

<!-- Thêm script Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Dữ liệu mẫu cho biểu đồ
    const months = ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'];
    const activeData = [3, 5, 8, 13, 17, 20, 22, 25, 28, 30, 32, <?php echo $active_count; ?>];
    const pendingData = [2, 3, 5, 7, 9, 11, 12, 13, 14, 15, 16, <?php echo $pending_count; ?>];
    
    // Tạo biểu đồ
    const ctx = document.getElementById('licenseChart').getContext('2d');
    const licenseChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'Đã kích hoạt',
                    data: activeData,
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Chờ kích hoạt',
                    data: pendingData,
                    borderColor: '#F59E0B',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    tension: 0.3,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
</script>

<?php include 'includes/admin-footer.php'; ?>