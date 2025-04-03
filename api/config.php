<?php
// Tải API key từ file cấu hình
require_once '../../config/api_key.php';

// Hàm kiểm tra API key
function validateApiKey($provided_key) {
    if (!defined('API_KEY')) {
        return false;
    }
    
    return $provided_key === API_KEY;
}
?>