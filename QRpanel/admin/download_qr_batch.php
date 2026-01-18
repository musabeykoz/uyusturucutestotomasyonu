<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/security.php';
requireLogin();

$batch_time = isset($_GET['batch_time']) ? sanitizeInput($_GET['batch_time']) : '';
$csrf_token = isset($_GET['csrf_token']) ? $_GET['csrf_token'] : '';

// CSRF Token kontrolü
if (!verifyCSRFToken($csrf_token)) {
    logSecurity("CSRF token doğrulama hatası - Bulk QR Download", [
        'user_id' => $_SESSION['user_id'],
        'ip' => getClientIP()
    ]);
    header('Location: qr_system.php?error=' . urlencode('Geçersiz istek. Sayfayı yenileyip tekrar deneyin.'));
    exit;
}

// Batch time validasyonu (format: YYYY-MM-DD HH:MM)
if (empty($batch_time) || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $batch_time)) {
    header('Location: qr_system.php?error=' . urlencode('Geçersiz zaman formatı.'));
    exit;
}

$conn = getDBConnection();

// Bu batch time'daki testleri al (aynı dakika içinde oluşturulanlar)
$stmt = $conn->prepare("SELECT id, qr_code, qr_code_image FROM test_results WHERE DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') = ? ORDER BY id ASC");
$stmt->bind_param('s', $batch_time);
$stmt->execute();
$result = $stmt->get_result();
$tests = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (count($tests) == 0) {
    header('Location: qr_system.php?error=' . urlencode('Bu toplu oluşturma grubunda hiç test bulunamadı.'));
    exit;
}

// ZIP dosyası oluştur
$zip_filename = 'QR_Kodlari_Toplu_' . date('d-m-Y_H-i', strtotime($batch_time)) . '_' . count($tests) . 'adet.zip';
$zip_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zip_filename;

// Eğer dosya varsa sil
if (file_exists($zip_path)) {
    @unlink($zip_path);
}

$zip = new ZipArchive();
if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    logError("ZIP oluşturma hatası", ['path' => $zip_path]);
    header('Location: qr_system.php?error=' . urlencode('ZIP dosyası oluşturulamadı.'));
    exit;
}

$success_count = 0;
$base_path = dirname(dirname(__FILE__));

// Her test için QR kodunu ZIP'e ekle
foreach ($tests as $test) {
    if (!empty($test['qr_code_image'])) {
        // Yolu düzgün oluştur (Windows/Linux uyumlu)
        $qr_file_path = $base_path . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $test['qr_code_image']);
        
        if (file_exists($qr_file_path)) {
            // Dosya içeriğini oku ve ZIP'e ekle (daha güvenli yöntem)
            $file_content = file_get_contents($qr_file_path);
            
            if ($file_content !== false) {
                // Dosya adını oluştur: QR_CROMTEST-2025-123456.png
                $file_extension = pathinfo($qr_file_path, PATHINFO_EXTENSION);
                $zip_file_name = 'QR_' . $test['qr_code'] . '.' . $file_extension;
                
                // İçerik olarak ekle (dosya kilitleme problemi olmuyor)
                if ($zip->addFromString($zip_file_name, $file_content)) {
                    $success_count++;
                }
            }
        }
    }
}

// ZIP'i kapat
$zip->close();

// Log kaydet
logTestAction('BULK_QR_DOWNLOAD', 0, '', [
    'user_id' => $_SESSION['user_id'],
    'batch_time' => $batch_time,
    'total_tests' => count($tests),
    'success_count' => $success_count
]);

// ZIP dosyasını indir
if ($success_count > 0 && file_exists($zip_path)) {
    // Dosya boyutunu kontrol et
    $filesize = filesize($zip_path);
    
    if ($filesize === false || $filesize == 0) {
        @unlink($zip_path);
        header('Location: qr_system.php?error=' . urlencode('ZIP dosyası boş veya okunamıyor.'));
        exit;
    }
    
    // Output buffer'ı temizle (önemli!)
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Header'ları gönder
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
    header('Content-Length: ' . $filesize);
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: public');
    header('Expires: 0');
    
    // Dosyayı oku ve gönder
    readfile($zip_path);
    
    // Geçici ZIP dosyasını sil
    @unlink($zip_path);
    exit;
} else {
    // Geçici ZIP dosyasını sil
    if (file_exists($zip_path)) {
        @unlink($zip_path);
    }
    
    $error_msg = $success_count == 0 ? 
        'Hiçbir QR kod dosyası bulunamadı.' : 
        'ZIP dosyası oluşturulamadı.';
    
    header('Location: qr_system.php?error=' . urlencode($error_msg));
    exit;
}

