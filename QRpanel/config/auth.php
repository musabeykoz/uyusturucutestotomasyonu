<?php
// Güvenlik fonksiyonlarını önce yükle
require_once __DIR__ . '/security.php';
// Logger'ı yükle (security'ye bağımlı)
require_once __DIR__ . '/logger.php';
// Session'ı yükle (security'ye bağımlı)
require_once __DIR__ . '/session.php';

// Güvenli session başlat
startSecureSession();

// Oturum kontrolü
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Admin kontrolü
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Giriş yapmamış kullanıcıyı login sayfasına yönlendir
function requireLogin() {
    if (!isLoggedIn()) {
        // Mevcut URL'i kaydet (giriş sonrası dönmek için)
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // QRpanel içindeyse ../admin/login.php, değilse admin/login.php
        $script = $_SERVER['SCRIPT_NAME'];
        if (strpos($script, '/QRpanel/') !== false || strpos($script, '/purchase/') !== false) {
            header('Location: ../admin/login.php');
        } else {
            header('Location: ' . getBaseUrl() . 'admin/login.php');
        }
        exit;
    }
}

// Sadece admin erişimi
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        logSecurity("Yetkisiz admin erişim denemesi", [
            'user_id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null
        ]);
        header('Location: ' . getBaseUrl() . 'index.php');
        exit;
    }
}

// Base URL al
function getBaseUrl() {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    
    // Script path'den base path'i çıkar
    $basePath = rtrim(dirname($script), '/\\') . '/';
    
    // Eğer /admin veya /api içindeyse bir üst dizini al
    if (strpos($basePath, '/admin') !== false || strpos($basePath, '/api') !== false) {
        $basePath = dirname($basePath) . '/';
    }
    
    return $protocol . "://" . $host . $basePath;
}

// Kullanıcı girişi (Güvenli - Prepared Statement ve Rate Limiting)
function login($username, $password) {
    require_once __DIR__ . '/database.php';
    
    // Input temizleme
    $username = sanitizeInput($username);
    $password = sanitizeInput($password);
    
    // Rate limiting kontrolü (5 deneme, 5 dakika)
    $rateLimitKey = 'login_' . getClientIP() . '_' . $username;
    
    if (!checkRateLimit($rateLimitKey, 5, 300)) {
        $waitTime = getRateLimitWaitTime($rateLimitKey, 300);
        logSecurity("Rate limit aşıldı - Login denemesi", [
            'username' => $username,
            'ip' => getClientIP(),
            'wait_time' => $waitTime
        ]);
        return [
            'success' => false,
            'error' => 'Çok fazla başarısız giriş denemesi. Lütfen ' . ceil($waitTime / 60) . ' dakika sonra tekrar deneyin.'
        ];
    }
    
    $conn = getDBConnection();
    
    // Prepared statement ile güvenli sorgu
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1");
    
    if (!$stmt) {
        logError("Login sorgu hazırlama hatası: " . $conn->error);
        return [
            'success' => false,
            'error' => 'Bir sistem hatası oluştu.'
        ];
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Başarılı giriş
            regenerateSession(); // Session fixation önleme
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            
            // Son giriş zamanını güncelle (Prepared statement)
            $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $update_stmt->bind_param("i", $user['id']);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Rate limit sıfırla
            resetRateLimit($rateLimitKey);
            
            // Başarılı girişi logla
            logLoginAttempt($username, true);
            
            $stmt->close();
            return [
                'success' => true,
                'user' => $user
            ];
        }
    }
    
    $stmt->close();
    
    // Başarısız girişi logla
    logLoginAttempt($username, false, 'Kullanıcı adı veya şifre hatalı');
    
    return [
        'success' => false,
        'error' => 'Kullanıcı adı veya şifre hatalı!'
    ];
}

// Çıkış yap
function logout() {
    if (isLoggedIn()) {
        logInfo("Kullanıcı çıkış yaptı", [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username']
        ]);
    }
    
    destroySession();
    
    // QRpanel içindeyse ../admin/login.php, değilse admin/login.php
    $script = $_SERVER['SCRIPT_NAME'];
    if (strpos($script, '/QRpanel/') !== false || strpos($script, '/purchase/') !== false) {
        header('Location: ../admin/login.php');
    } else {
        header('Location: ' . getBaseUrl() . 'admin/login.php');
    }
    exit;
}

// Şifre değiştirme (Güvenli)
function changePassword($user_id, $old_password, $new_password) {
    require_once __DIR__ . '/database.php';
    
    // Yeni şifre güçlü mü kontrol et
    if (!isStrongPassword($new_password)) {
        return [
            'success' => false,
            'error' => 'Şifre en az 8 karakter olmalı ve büyük harf, küçük harf ve rakam içermelidir.'
        ];
    }
    
    $conn = getDBConnection();
    
    // Mevcut kullanıcıyı getir
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result || $result->num_rows === 0) {
        $stmt->close();
        return [
            'success' => false,
            'error' => 'Kullanıcı bulunamadı.'
        ];
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Eski şifre doğru mu?
    if (!password_verify($old_password, $user['password'])) {
        logSecurity("Başarısız şifre değiştirme denemesi", ['user_id' => $user_id]);
        return [
            'success' => false,
            'error' => 'Mevcut şifreniz hatalı.'
        ];
    }
    
    // Yeni şifreyi hashle
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Şifreyi güncelle
    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update_stmt->bind_param("si", $new_password_hash, $user_id);
    $success = $update_stmt->execute();
    $update_stmt->close();
    
    if ($success) {
        logSecurity("Şifre başarıyla değiştirildi", ['user_id' => $user_id]);
        return [
            'success' => true,
            'message' => 'Şifreniz başarıyla değiştirildi.'
        ];
    }
    
    return [
        'success' => false,
        'error' => 'Şifre değiştirme sırasında bir hata oluştu.'
    ];
}
?>

