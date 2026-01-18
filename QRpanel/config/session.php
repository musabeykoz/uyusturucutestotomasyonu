<?php
/**
 * Güvenli Session Yönetimi
 */

// Security fonksiyonlarını dahil et (getClientIP için)
if (!function_exists('getClientIP')) {
    require_once __DIR__ . '/security.php';
}

// Session'ı güvenli şekilde başlat
function startSecureSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    
    // Session güvenlik ayarları
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
    ini_set('session.cookie_samesite', 'Strict');
    
    // Session adını özelleştir
    session_name('CROMTEST_SESSION');
    
    // Session timeout (30 dakika)
    ini_set('session.gc_maxlifetime', 1800);
    
    session_start();
    
    // Session fixation önleme
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
        $_SESSION['created_at'] = time();
        $_SESSION['user_ip'] = getClientIP();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    // Session hijacking kontrolü
    validateSession();
    
    // Session timeout kontrolü
    checkSessionTimeout();
}

// Session hijacking kontrolü
function validateSession() {
    if (!isset($_SESSION['user_ip']) || !isset($_SESSION['user_agent'])) {
        return;
    }
    
    // IP değişikliği kontrolü (bazı durumlarda IP değişebilir, dikkatli kullanılmalı)
    $currentIP = getClientIP();
    $currentAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // User agent değişikliği daha güvenilir bir kontrol
    if ($_SESSION['user_agent'] !== $currentAgent) {
        destroySession();
        $script = $_SERVER['SCRIPT_NAME'];
        if (strpos($script, '/QRpanel/') !== false || strpos($script, '/purchase/') !== false) {
            header('Location: ../admin/login.php?error=session_invalid');
        } else {
            header('Location: admin/login.php?error=session_invalid');
        }
        exit;
    }
}

// Session timeout kontrolü
function checkSessionTimeout($timeout = 1800) { // 30 dakika
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
        return;
    }
    
    $elapsed = time() - $_SESSION['last_activity'];
    
    if ($elapsed > $timeout) {
        destroySession();
        $script = $_SERVER['SCRIPT_NAME'];
        if (strpos($script, '/QRpanel/') !== false || strpos($script, '/purchase/') !== false) {
            header('Location: ../admin/login.php?error=session_timeout');
        } else {
            header('Location: admin/login.php?error=session_timeout');
        }
        exit;
    }
    
    $_SESSION['last_activity'] = time();
}

// Session'ı güvenli şekilde yok et
function destroySession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = array();
        
        if (isset($_COOKIE[session_name()])) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        session_destroy();
    }
}

// Session yenileme (önemli işlemler öncesi)
function regenerateSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

// getClientIP() fonksiyonu security.php'de tanımlı
?>

