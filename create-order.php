<?php
require_once 'config/database.php';
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Bạn cần đăng nhập để tạo đơn hàng.";
    header("Location: login.php");
    exit();
}

// Kiểm tra dữ liệu POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['product_id']) || !isset($_POST['pricing_id'])) {
    $_SESSION['error_message'] = "Dữ liệu không hợp lệ.";
    header("Location: products.php");
    exit();
}

// Lấy dữ liệu từ form
$product_id = $_POST['product_id'];
$pricing_id = $_POST['pricing_id'];
$user_id = $_SESSION['user_id'];

// Kết nối database
$database = new Database();
$db = $database->getConnection();

try {
    // Bắt đầu transaction
    $db->beginTransaction();
    
    // Lấy thông tin người dùng
    $user_query = "SELECT * FROM users WHERE id = ?";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->bindParam(1, $user_id);
    $user_stmt->execute();
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception("Không tìm thấy thông tin người dùng.");
    }
    
    // Lấy thông tin sản phẩm
    $product_query = "SELECT * FROM products WHERE id = ?";
    $product_stmt = $db->prepare($product_query);
    $product_stmt->bindParam(1, $product_id);
    $product_stmt->execute();
    $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception("Không tìm thấy thông tin sản phẩm.");
    }
    
    // Lấy thông tin pricing
    $pricing_query = "SELECT * FROM product_pricing WHERE id = ? AND product_id = ?";
    $pricing_stmt = $db->prepare($pricing_query);
    $pricing_stmt->bindParam(1, $pricing_id);
    $pricing_stmt->bindParam(2, $product_id);
    $pricing_stmt->execute();
    $pricing = $pricing_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pricing) {
        throw new Exception("Không tìm thấy thông tin gói giá.");
    }
    
    // Tạo license key mới
    $license_key = generateLicenseKey();
    
    // Thêm license mới
    $license_query = "INSERT INTO licenses (license_key, product_id, customer_name, customer_email, status, created_at) 
                     VALUES (?, ?, ?, ?, 'pending', NOW())";
    $license_stmt = $db->prepare($license_query);
    $license_stmt->bindParam(1, $license_key);
    $license_stmt->bindParam(2, $product_id);
    $license_stmt->bindParam(3, $user['name']);
    $license_stmt->bindParam(4, $user['email']);
    $license_stmt->execute();
    
    $license_id = $db->lastInsertId();
    
    // Tạo mã đơn hàng
    $order_code = 'ORD-' . date('YmdHis') . '-' . rand(1000, 9999);
    
    // Thêm đơn hàng mới
    $order_query = "INSERT INTO orders (order_code, user_id, product_id, pricing_id, license_id, amount, status, created_at) 
                   VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";
    $order_stmt = $db->prepare($order_query);
    $order_stmt->bindParam(1, $order_code);
    $order_stmt->bindParam(2, $user_id);
    $order_stmt->bindParam(3, $product_id);
    $order_stmt->bindParam(4, $pricing_id);
    $order_stmt->bindParam(5, $license_id);
    $order_stmt->bindParam(6, $pricing['price']);
    $order_stmt->execute();
    
    $order_id = $db->lastInsertId();
    
    // Commit transaction
    $db->commit();
    
    // Chuyển hướng đến trang thanh toán
    $_SESSION['success_message'] = "Đơn hàng đã được tạo thành công. Vui lòng thanh toán để kích hoạt license.";
    header("Location: payment.php?order_id=" . $order_id);
    exit();
    
} catch (Exception $e) {
    // Rollback transaction nếu có lỗi
    $db->rollBack();
    
    $_SESSION['error_message'] = "Đã xảy ra lỗi: " . $e->getMessage();
    header("Location: product-detail.php?id=" . $product_id);
    exit();
}

// Hàm tạo license key
function generateLicenseKey() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $key = '';
    
    // Tạo 5 nhóm, mỗi nhóm 5 ký tự
    for ($group = 0; $group < 5; $group++) {
        for ($i = 0; $i < 5; $i++) {
            $key .= $chars[rand(0, strlen($chars) - 1)];
        }
        
        if ($group < 4) {
            $key .= '-';
        }
    }
    
    return $key;
}
?>