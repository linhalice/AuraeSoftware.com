<?php
// Hàm tạo mã ngẫu nhiên
function generateRandomCode($length = 10) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $code;
}

// Hàm tạo license key
function generateLicenseKey($product_id) {
    $prefix = 'AURAE';
    $product_code = str_pad($product_id, 2, '0', STR_PAD_LEFT);
    $random1 = generateRandomCode(4);
    $random2 = generateRandomCode(4);
    $random3 = generateRandomCode(4);
    
    return $prefix . '-' . $product_code . '-' . $random1 . '-' . $random2 . '-' . $random3;
}

// Hàm định dạng tiền tệ
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . 'đ';
}

// Hàm định dạng ngày giờ
function formatDateTime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

// Hàm tạo thông báo
function createNotification($db, $type, $title, $message, $link = null, $admin_id = null) {
    $query = "INSERT INTO notifications (admin_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $admin_id);
    $stmt->bindParam(2, $type);
    $stmt->bindParam(3, $title);
    $stmt->bindParam(4, $message);
    $stmt->bindParam(5, $link);
    return $stmt->execute();
}

// Hàm ghi log hoạt động admin
function logAdminActivity($db, $admin_id, $action) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $query = "INSERT INTO admin_logs (admin_id, action, ip_address) VALUES (?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $admin_id);
    $stmt->bindParam(2, $action);
    $stmt->bindParam(3, $ip);
    return $stmt->execute();
}
?>