<?php
// Hàm ghi log hoạt động của admin
function logAdminActivity($db, $admin_id, $action) {
    try {
        $query = "INSERT INTO admin_logs (admin_id, action, ip_address, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $db->prepare($query);
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
        $stmt->bindParam(1, $admin_id);
        $stmt->bindParam(2, $action);
        $stmt->bindParam(3, $ip);
        return $stmt->execute();
    } catch (Exception $e) {
        // Ghi log lỗi hoặc xử lý lỗi
        error_log("Error in logAdminActivity: " . $e->getMessage());
        return false;
    }
}

// Hàm tạo thông báo
function createNotification($db, $type, $title, $message, $link = null, $admin_id = null) {
    try {
        $query = "INSERT INTO notifications (admin_id, type, title, message, link, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $admin_id);
        $stmt->bindParam(2, $type);
        $stmt->bindParam(3, $title);
        $stmt->bindParam(4, $message);
        $stmt->bindParam(5, $link);
        return $stmt->execute();
    } catch (Exception $e) {
        // Ghi log lỗi hoặc xử lý lỗi
        error_log("Error in createNotification: " . $e->getMessage());
        return false;
    }
}

// Hàm đếm số thông báo chưa đọc
function countUnreadNotifications($db, $admin_id) {
    try {
        $query = "SELECT COUNT(*) as count FROM notifications WHERE is_read = 0 AND (admin_id = ? OR admin_id IS NULL)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $admin_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return isset($result['count']) ? $result['count'] : 0;
    } catch (Exception $e) {
        // Ghi log lỗi hoặc xử lý lỗi
        error_log("Error in countUnreadNotifications: " . $e->getMessage());
        return 0;
    }
}

// Hàm lấy danh sách thông báo mới nhất
function getLatestNotifications($db, $admin_id, $limit = 5) {
    try {
        $query = "SELECT * FROM notifications WHERE (admin_id = ? OR admin_id IS NULL) ORDER BY created_at DESC LIMIT ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $admin_id);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Ghi log lỗi hoặc xử lý lỗi
        error_log("Error in getLatestNotifications: " . $e->getMessage());
        return [];
    }
}

// Hàm tạo license key ngẫu nhiên
function generateLicenseKey($prefix = 'AURAE') {
    $segments = [
        $prefix,
        strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4)),
        strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4)),
        strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4)),
        strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4))
    ];
    
    return implode('-', $segments);
}

// Hàm tính số ngày còn lại trước khi hết hạn
function getDaysLeft($expiration_date) {
    if (empty($expiration_date)) {
        return null;
    }
    
    $expiration = strtotime($expiration_date);
    $now = time();
    
    return ceil(($expiration - $now) / (60 * 60 * 24));
}

// Hàm định dạng trạng thái license
function formatLicenseStatus($status) {
    switch ($status) {
        case 'active':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"><i class="fas fa-check-circle mr-1"></i> Đã kích hoạt</span>';
        case 'pending':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"><i class="fas fa-clock mr-1"></i> Chờ kích hoạt</span>';
        case 'expired':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"><i class="fas fa-times-circle mr-1"></i> Hết hạn</span>';
        case 'revoked':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"><i class="fas fa-ban mr-1"></i> Đã thu hồi</span>';
        default:
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">' . $status . '</span>';
    }
}

// Hàm hiển thị thời gian tương đối
function timeAgo($datetime) {
    if (empty($datetime)) {
        return 'N/A';
    }
    
    $time = strtotime($datetime);
    if ($time === false) {
        return 'N/A';
    }
    
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Vừa xong';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' phút trước';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' giờ trước';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' ngày trước';
    } else {
        return date('d/m/Y H:i', $time);
    }
}

// Kiểm tra xem bảng có tồn tại không
function tableExists($db, $table) {
    try {
        $result = $db->query("SHOW TABLES LIKE '$table'");
        return $result->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Tạo bảng nếu chưa tồn tại
function createTablesIfNotExist($db) {
    // Tạo bảng admin_logs nếu chưa tồn tại
    if (!tableExists($db, 'admin_logs')) {
        $db->exec("CREATE TABLE admin_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            action TEXT NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            created_at DATETIME NOT NULL
        )");
    }
    
    // Tạo bảng notifications nếu chưa tồn tại
    if (!tableExists($db, 'notifications')) {
        $db->exec("CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            link VARCHAR(255) NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL
        )");
    }
    
    // Tạo bảng users nếu chưa tồn tại
    if (!tableExists($db, 'users')) {
        $db->exec("CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NULL,
            status ENUM('active', 'inactive', 'banned') NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL
        )");
    }
}

// Kiểm tra nếu hàm chưa tồn tại thì mới khai báo
if (!function_exists('generateLicenseKey')) {
    /**
     * Tạo khóa license ngẫu nhiên
     * 
     * @param string $prefix Tiền tố cho khóa license
     * @return string Khóa license đã tạo
     */
    function generateLicenseKey($prefix = 'AURAE') {
        $segments = [
            $prefix,
            strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4)),
            strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4)),
            strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4)),
            strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4))
        ];
        
        return implode('-', $segments);
    }
}

// Kiểm tra nếu hàm chưa tồn tại thì mới khai báo
if (!function_exists('calculateExpirationDate')) {
    /**
     * Tính ngày hết hạn dựa trên loại thời hạn
     * 
     * @param string $duration_type Loại thời hạn (3_days, 1_month, v.v.)
     * @param string $start_date Ngày bắt đầu (định dạng Y-m-d H:i:s)
     * @return string|null Ngày hết hạn định dạng Y-m-d H:i:s hoặc null cho license vĩnh viễn
     */
    function calculateExpirationDate($duration_type, $start_date = null) {
        if ($duration_type == 'lifetime') {
            return null;
        }
        
        $date = $start_date ? new DateTime($start_date) : new DateTime();
        
        switch ($duration_type) {
            case '3_days':
                $date->add(new DateInterval('P3D'));
                break;
            case '1_month':
                $date->add(new DateInterval('P1M'));
                break;
            case '3_months':
                $date->add(new DateInterval('P3M'));
                break;
            case '6_months':
                $date->add(new DateInterval('P6M'));
                break;
            case '1_year':
                $date->add(new DateInterval('P1Y'));
                break;
            default:
                return null;
        }
        
        return $date->format('Y-m-d H:i:s');
    }
}

// Kiểm tra nếu hàm chưa tồn tại thì mới khai báo
if (!function_exists('getDurationLabel')) {
    /**
     * Lấy nhãn có thể đọc được cho loại thời hạn
     * 
     * @param string $duration_type Loại thời hạn
     * @return string Nhãn có thể đọc được
     */
    function getDurationLabel($duration_type) {
        $labels = [
            '3_days' => '3 ngày',
            '1_month' => '1 tháng',
            '3_months' => '3 tháng',
            '6_months' => '6 tháng',
            '1_year' => '1 năm',
            'lifetime' => 'Vĩnh viễn'
        ];
        
        return isset($labels[$duration_type]) ? $labels[$duration_type] : $duration_type;
    }
}

// Kiểm tra nếu hàm chưa tồn tại thì mới khai báo
if (!function_exists('getLicenseStatusLabel')) {
    /**
     * Lấy nhãn có thể đọc được cho trạng thái license
     * 
     * @param string $status Trạng thái license
     * @return string Nhãn HTML đã định dạng
     */
    function getLicenseStatusLabel($status) {
        $labels = [
            'pending' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Chờ kích hoạt</span>',
            'active' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Đã kích hoạt</span>',
            'expired' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Hết hạn</span>',
            'revoked' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Đã thu hồi</span>'
        ];
        
        return isset($labels[$status]) ? $labels[$status] : $status;
    }
}
?>