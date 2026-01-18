<?php
// Veritabanı bağlantı ayarları - .env dosyasından veya sabit değerlerden
// Production ortamında .env dosyası kullanılmalı
// Kendi veritabanı bilgilerinizi buraya girin
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'cromtest_db');

// Hata gösterimini kapat (production)
define('SHOW_DB_ERRORS', getenv('APP_DEBUG') === 'true' || true); // Development için aktif

// Logger'ı dahil et
require_once __DIR__ . '/logger.php';

// Veritabanı bağlantısı (Singleton pattern)
function getDBConnection() {
    static $conn = null;
    
    if ($conn !== null) {
        return $conn;
    }
    
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            logError("Veritabanı bağlantı hatası: " . $conn->connect_error);
            throw new Exception("Veritabanı bağlantısı kurulamadı.");
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        logError("Veritabanı hatası: " . $e->getMessage());
        
        if (SHOW_DB_ERRORS) {
            die("Veritabanı bağlantı hatası: " . $e->getMessage());
        } else {
            die("Bir sistem hatası oluştu. Lütfen daha sonra tekrar deneyin.");
        }
    }
}

// QR kod ile test sonucu getir (Prepared Statement ile SQL Injection koruması)
function getTestResultByQR($qr_code) {
    $conn = getDBConnection();
    
    // QR kod'u temizle ve normalize et
    $qr_code = trim($qr_code);
    
    // Prepared statement kullan
    $stmt = $conn->prepare("SELECT * FROM test_results WHERE qr_code = ? LIMIT 1");
    
    if (!$stmt) {
        logError("Sorgu hazırlama hatası: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("s", $qr_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    }
    
    $stmt->close();
    return null;
}

// Test ID ile test sonucu getir (Güvenli)
function getTestResultById($test_id) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT * FROM test_results WHERE id = ? LIMIT 1");
    
    if (!$stmt) {
        logError("Sorgu hazırlama hatası: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("i", $test_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    }
    
    $stmt->close();
    return null;
}

// Test silme (Güvenli)
function deleteTestById($test_id, $user_id) {
    $conn = getDBConnection();
    
    // Önce test'in var olup olmadığını kontrol et
    $test = getTestResultById($test_id);
    if (!$test) {
        return false;
    }
    
    $stmt = $conn->prepare("DELETE FROM test_results WHERE id = ?");
    
    if (!$stmt) {
        logError("Sorgu hazırlama hatası: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("i", $test_id);
    $success = $stmt->execute();
    $stmt->close();
    
    if ($success) {
        logTestAction('DELETE', $test_id, $test['qr_code'], ['deleted_by' => $user_id]);
    }
    
    return $success;
}
?>
