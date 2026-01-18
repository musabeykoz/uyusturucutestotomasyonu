<?php
/**
 * Güvenlik ve CSRF Token Yönetimi
 */

// CSRF Token oluşturma
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

// CSRF Token doğrulama
function verifyCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

// CSRF Token HTML input
function csrfTokenField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

// XSS Koruması - Güvenli output
function escape($data) {
    if (is_array($data)) {
        return array_map('escape', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Input temizleme ve validasyon
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    return $data;
}

// Email validasyonu
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Güçlü şifre kontrolü
function isStrongPassword($password) {
    // En az 8 karakter, 1 büyük harf, 1 küçük harf, 1 rakam
    if (strlen($password) < 8) {
        return false;
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    
    return true;
}

// Rate Limiting (Basit implementasyon)
function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $key = 'rate_limit_' . $identifier;
    $now = time();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 1,
            'first_attempt' => $now
        ];
        return true;
    }
    
    $data = $_SESSION[$key];
    
    // Zaman penceresi geçmişse sıfırla
    if (($now - $data['first_attempt']) > $timeWindow) {
        $_SESSION[$key] = [
            'attempts' => 1,
            'first_attempt' => $now
        ];
        return true;
    }
    
    // Deneme sayısını kontrol et
    if ($data['attempts'] >= $maxAttempts) {
        return false;
    }
    
    // Deneme sayısını artır
    $_SESSION[$key]['attempts']++;
    return true;
}

// Rate limit kalan süre
function getRateLimitWaitTime($identifier, $timeWindow = 300) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $key = 'rate_limit_' . $identifier;
    
    if (!isset($_SESSION[$key])) {
        return 0;
    }
    
    $data = $_SESSION[$key];
    $elapsed = time() - $data['first_attempt'];
    $remaining = $timeWindow - $elapsed;
    
    return max(0, $remaining);
}

// Rate limit sıfırlama
function resetRateLimit($identifier) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $key = 'rate_limit_' . $identifier;
    unset($_SESSION[$key]);
}

// Güvenli yönlendirme
function safeRedirect($url, $basePath = '') {
    // Sadece iç URL'lere izin ver
    $parsed = parse_url($url);
    
    if (isset($parsed['host']) && $parsed['host'] !== $_SERVER['HTTP_HOST']) {
        // Harici URL'lere izin verme
        $url = '/';
    }
    
    header('Location: ' . $url);
    exit;
}

// IP Adresi alma (proxy arkasında çalışma)
function getClientIP() {
    $ip = '';
    
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED'];
    } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
        $ip = $_SERVER['HTTP_FORWARDED'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    // İlk IP'yi al (proxy zinciri varsa)
    if (strpos($ip, ',') !== false) {
        $ip = explode(',', $ip)[0];
    }
    
    return trim($ip);
}
?>

