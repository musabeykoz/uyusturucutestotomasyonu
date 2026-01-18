<?php
/**
 * Basit Loglama Sistemi
 */

// Security fonksiyonlarını dahil et
if (!function_exists('getClientIP')) {
    require_once __DIR__ . '/security.php';
}

// Log seviyesi sabitleri (çakışmayı önlemek için kontrol eklendi)
if (!defined('LOG_INFO')) define('LOG_INFO', 'INFO');
if (!defined('LOG_WARNING')) define('LOG_WARNING', 'WARNING');
if (!defined('LOG_ERROR')) define('LOG_ERROR', 'ERROR');
if (!defined('LOG_SECURITY')) define('LOG_SECURITY', 'SECURITY');

// Log dosyası yolu
function getLogFilePath() {
    $logDir = dirname(__DIR__) . '/logs';
    
    // Log klasörü yoksa oluştur
    if (!file_exists($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    // Günlük log dosyası
    $logFile = $logDir . '/app_' . date('Y-m-d') . '.log';
    
    return $logFile;
}

// Log yazma fonksiyonu
function writeLog($message, $level = LOG_INFO, $context = []) {
    $logFile = getLogFilePath();
    
    // Log mesajı formatı
    $timestamp = date('Y-m-d H:i:s');
    $ip = getClientIP();
    $user = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
    
    $logEntry = sprintf(
        "[%s] [%s] [IP: %s] [User: %s] %s",
        $timestamp,
        $level,
        $ip,
        $user,
        $message
    );
    
    // Context varsa ekle
    if (!empty($context)) {
        $logEntry .= ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    
    $logEntry .= PHP_EOL;
    
    // Production'da hassas bilgileri loglamak için dikkatli ol
    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Kısa yol fonksiyonları
function logInfo($message, $context = []) {
    writeLog($message, LOG_INFO, $context);
}

function logWarning($message, $context = []) {
    writeLog($message, LOG_WARNING, $context);
}

function logError($message, $context = []) {
    writeLog($message, LOG_ERROR, $context);
}

function logSecurity($message, $context = []) {
    writeLog($message, LOG_SECURITY, $context);
}

// Login denemesi loglama
function logLoginAttempt($username, $success, $reason = '') {
    $message = $success 
        ? "Başarılı giriş: $username" 
        : "Başarısız giriş: $username - $reason";
    
    logSecurity($message, [
        'username' => $username,
        'success' => $success,
        'reason' => $reason
    ]);
}

// Test işlemleri loglama
function logTestAction($action, $testId, $qrCode, $details = []) {
    $message = "Test işlemi: $action - QR: $qrCode (ID: $testId)";
    
    logInfo($message, array_merge([
        'action' => $action,
        'test_id' => $testId,
        'qr_code' => $qrCode
    ], $details));
}

// getClientIP() fonksiyonu security.php'de tanımlı
?>

