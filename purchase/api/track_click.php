<?php
// Ürün tıklanma sayısını artırma API
require_once '../config/database.php';
require_once '../config/auth.php';
require_once __DIR__ . '/../../QRpanel/config/security.php';
require_once __DIR__ . '/../../QRpanel/config/session.php';

// Güvenli session başlat
startSecureSession();

header('Content-Type: application/json');

$conn = getDBConnection();
$response = ['success' => false, 'message' => ''];

// Rate limiting kontrolü
$rateLimitKey = 'api_track_click_' . getClientIP();
if (!checkRateLimit($rateLimitKey, 100, 60)) { // 60 saniyede 100 istek
    echo json_encode(['success' => false, 'message' => 'Çok fazla istek. Lütfen biraz bekleyin.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $click_type = isset($_POST['click_type']) ? sanitizeInput($_POST['click_type']) : 'view'; // 'view' veya 'purchase'
    
    // Click type validasyonu
    if (!in_array($click_type, ['view', 'purchase'])) {
        $click_type = 'view';
    }
    
    if ($product_id > 0) {
        if ($click_type === 'purchase') {
            // Satın alma linki tıklanması
            $stmt = $conn->prepare("UPDATE products SET purchase_click_count = purchase_click_count + 1 WHERE id = ?");
        } else {
            // Ürün detay sayfası görüntülenmesi
            $stmt = $conn->prepare("UPDATE products SET click_count = click_count + 1 WHERE id = ?");
        }
        
        $stmt->bind_param("i", $product_id);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Tıklanma sayısı güncellendi';
        } else {
            $response['message'] = 'Veritabanı hatası: ' . $stmt->error;
        }
        
        $stmt->close();
    } else {
        $response['message'] = 'Geçersiz ürün ID';
    }
} else {
    $response['message'] = 'Geçersiz istek metodu';
}

echo json_encode($response);
exit;

