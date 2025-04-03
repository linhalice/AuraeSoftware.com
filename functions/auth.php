<?php
// Bắt đầu session nếu chưa bắt đầu
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Hàm kiểm tra đăng nhập admin
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Hàm kiểm tra đăng nhập người dùng
function isUserLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Hàm lấy thông tin admin đang đăng nhập
function getLoggedInAdmin() {
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM admins WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $_SESSION['admin_id']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    return null;
}

// Hàm lấy thông tin người dùng đang đăng nhập
function getLoggedInUser() {
    if (!isUserLoggedIn()) {
        return null;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    return null;
}

// Hàm kiểm tra đăng nhập admin và chuyển hướng nếu chưa đăng nhập
function checkAdminLogin() {
    if (!isAdminLoggedIn()) {
        header("Location: login.php");
        exit();
    }
    
    return getLoggedInAdmin();
}

// Hàm kiểm tra đăng nhập người dùng và chuyển hướng nếu chưa đăng nhập
function checkUserLogin() {
    if (!isUserLoggedIn()) {
        header("Location: login.php");
        exit();
    }
    
    return getLoggedInUser();
}

// Hàm đăng xuất
function logout() {
    // Xóa tất cả các biến session
    $_SESSION = array();
    
    // Xóa cookie session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Hủy session
    session_destroy();
}
?>