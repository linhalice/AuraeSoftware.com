<?php
/**
 * Funciones para la gestión de licencias
 */

/**
 * Genera una clave de licencia única
 * 
 * @param string $prefix Prefijo para la clave de licencia
 * @return string Clave de licencia generada
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

/**
 * Calcula la fecha de expiración basada en el tipo de duración
 * 
 * @param string $duration_type Tipo de duración (3_days, 1_month, etc.)
 * @param string $start_date Fecha de inicio (formato Y-m-d H:i:s)
 * @return string|null Fecha de expiración en formato Y-m-d H:i:s o null para licencias vitalicias
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

/**
 * Verifica si una licencia es válida
 * 
 * @param PDO $db Conexión a la base de datos
 * @param string $license_key Clave de licencia a verificar
 * @return array Información sobre la validez de la licencia
 */
function verifyLicense($db, $license_key) {
    // Buscar la licencia
    $query = "SELECT l.*, p.title as product_name 
              FROM licenses l 
              JOIN products p ON l.product_id = p.id 
              WHERE l.license_key = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $license_key);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        return [
            'valid' => false,
            'message' => 'Licencia no encontrada'
        ];
    }
    
    $license = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar estado
    if ($license['status'] != 'active') {
        return [
            'valid' => false,
            'message' => 'La licencia no está activa',
            'status' => $license['status']
        ];
    }
    
    // Verificar expiración
    if ($license['duration_type'] != 'lifetime' && !empty($license['expires_at'])) {
        $now = new DateTime();
        $expires_at = new DateTime($license['expires_at']);
        
        if ($now > $expires_at) {
            // Actualizar estado a expirado
            $update_query = "UPDATE licenses SET status = 'expired', last_check_at = NOW() WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(1, $license['id']);
            $update_stmt->execute();
            
            return [
                'valid' => false,
                'message' => 'La licencia ha expirado',
                'status' => 'expired',
                'expires_at' => $license['expires_at']
            ];
        }
    }
    
    // Actualizar última verificación
    $update_query = "UPDATE licenses SET last_check_at = NOW() WHERE id = ?";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(1, $license['id']);
    $update_stmt->execute();
    
    return [
        'valid' => true,
        'message' => 'Licencia válida',
        'license' => $license
    ];
}

/**
 * Activa una licencia
 * 
 * @param PDO $db Conexión a la base de datos
 * @param int $license_id ID de la licencia a activar
 * @return bool True si la activación fue exitosa, False en caso contrario
 */
function activateLicense($db, $license_id) {
    $query = "UPDATE licenses SET status = 'active', activated_at = NOW() WHERE id = ? AND status = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $license_id);
    
    return $stmt->execute() && $stmt->rowCount() > 0;
}

/**
 * Revoca una licencia
 * 
 * @param PDO $db Conexión a la base de datos
 * @param int $license_id ID de la licencia a revocar
 * @return bool True si la revocación fue exitosa, False en caso contrario
 */
function revokeLicense($db, $license_id) {
    $query = "UPDATE licenses SET status = 'revoked' WHERE id = ? AND status != 'revoked'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $license_id);
    
    return $stmt->execute() && $stmt->rowCount() > 0;
}

/**
 * Obtiene la etiqueta legible para un tipo de duración
 * 
 * @param string $duration_type Tipo de duración
 * @return string Etiqueta legible
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

/**
 * Obtiene la etiqueta legible para un estado de licencia
 * 
 * @param string $status Estado de la licencia
 * @return string Etiqueta HTML formateada
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
?>