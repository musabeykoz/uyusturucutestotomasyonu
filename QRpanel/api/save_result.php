<?php
header('Content-Type: application/json');

// Güvenlik ve veritabanı kütüphanelerini dahil et
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../config/session.php';
require_once '../config/logger.php';

// Güvenli session başlat
startSecureSession();

// POST verilerini al
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Sadece POST istekleri kabul edilir.']);
    logSecurity("Hatalı API isteği - Metod: " . $_SERVER['REQUEST_METHOD'], ['endpoint' => 'save_result']);
    exit;
}

// Rate limiting kontrolü
$rateLimitKey = 'api_save_result_' . getClientIP();
if (!checkRateLimit($rateLimitKey, 10, 60)) {
    echo json_encode(['success' => false, 'message' => 'Çok fazla istek. Lütfen biraz bekleyin.']);
    logSecurity("Rate limit aşıldı - API", ['endpoint' => 'save_result', 'ip' => getClientIP()]);
    exit;
}

// CSRF Token kontrolü
$csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
if (!verifyCSRFToken($csrf_token)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek. Sayfayı yenileyip tekrar deneyin.']);
    logSecurity("CSRF token doğrulama hatası", ['endpoint' => 'save_result', 'ip' => getClientIP()]);
    exit;
}

// Input'ları temizle ve al
$qr_code = isset($_POST['qr_code']) ? sanitizeInput($_POST['qr_code']) : '';
$control_result = isset($_POST['control_result']) ? (int)$_POST['control_result'] : null;
$test_result = isset($_POST['test_result']) ? (int)$_POST['test_result'] : null;

// Validasyon
if (empty($qr_code)) {
    echo json_encode(['success' => false, 'message' => 'QR kod gerekli.']);
    exit;
}

// QR kod formatı kontrolü
if (!preg_match('/^CROMTEST-\d{4}-\d+$/', $qr_code)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz QR kod formatı.']);
    logSecurity("Geçersiz QR kod formatı", ['qr_code' => $qr_code, 'ip' => getClientIP()]);
    exit;
}

if ($control_result === null || $test_result === null) {
    echo json_encode(['success' => false, 'message' => 'Tüm alanları doldurmanız gerekiyor.']);
    exit;
}

// Sonuç değerleri 0 veya 1 olmalı
if (!in_array($control_result, [0, 1]) || !in_array($test_result, [0, 1])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz sonuç değerleri.']);
    exit;
}

// Veritabanı bağlantısı
$conn = getDBConnection();

// Test kaydını bul (Prepared statement ile güvenli)
$stmt = $conn->prepare("SELECT * FROM test_results WHERE qr_code = ? LIMIT 1");
$stmt->bind_param("s", $qr_code);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Test kaydı bulunamadı.']);
    $stmt->close();
    $conn->close();
    logWarning("Test kaydı bulunamadı", ['qr_code' => $qr_code]);
    exit;
}

$test = $result->fetch_assoc();
$stmt->close();

// Eğer zaten doldurulmuşsa
if ($test['is_filled'] == 1) {
    echo json_encode(['success' => false, 'message' => 'Bu test sonuçları zaten doldurulmuş.']);
    $conn->close();
    logWarning("Dolu test tekrar doldurulmaya çalışıldı", ['qr_code' => $qr_code, 'test_id' => $test['id']]);
    exit;
}

// Test durumunu belirle
$test_status = 'Tamamlandı';
if ($control_result == 0) {
    $test_status = 'Geçersiz'; // Kontrol çizgisi yoksa test geçersiz
}

// Sonuçları güncelle (Prepared statement)
$update_stmt = $conn->prepare(
    "UPDATE test_results SET 
        control_result = ?,
        test_result = ?,
        is_filled = 1,
        filled_at = NOW(),
        test_status = ?,
        updated_at = NOW()
    WHERE qr_code = ?"
);

$update_stmt->bind_param('iiss', $control_result, $test_result, $test_status, $qr_code);

if ($update_stmt->execute()) {
    // Başarılı güncelleme
    logTestAction('FILL_RESULT', $test['id'], $qr_code, [
        'control_result' => $control_result,
        'test_result' => $test_result,
        'test_status' => $test_status
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Test sonuçları başarıyla kaydedildi.',
        'data' => [
            'qr_code' => $qr_code,
            'control_result' => $control_result,
            'test_result' => $test_result,
            'test_status' => $test_status
        ]
    ]);
} else {
    logError("Test sonucu kaydetme hatası", [
        'qr_code' => $qr_code,
        'error' => $update_stmt->error
    ]);
    
    echo json_encode([
        'success' => false, 
        'message' => 'Kayıt sırasında bir hata oluştu. Lütfen tekrar deneyin.'
    ]);
}

$update_stmt->close();
$conn->close();
?>

