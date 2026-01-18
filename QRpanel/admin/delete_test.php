<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/security.php';
requireLogin();

$test_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$confirm = isset($_GET['confirm']) && $_GET['confirm'] == 'yes';
$csrf_token = isset($_GET['csrf_token']) ? $_GET['csrf_token'] : '';

if ($test_id > 0) {
    $conn = getDBConnection();
    
    if ($confirm) {
        // CSRF Token kontrolü
        if (!verifyCSRFToken($csrf_token)) {
            logSecurity("CSRF token doğrulama hatası - Delete Test", [
                'user_id' => $_SESSION['user_id'],
                'test_id' => $test_id,
                'ip' => getClientIP()
            ]);
            header('Location: dashboard.php?error=' . urlencode('Geçersiz istek. Sayfayı yenileyip tekrar deneyin.'));
            exit;
        }
        
        // Test bilgilerini al (Prepared statement)
        $test = getTestResultById($test_id);
        
        if ($test) {
            // QR kod görselini sil
            if (!empty($test['qr_code_image'])) {
                $qr_file = dirname(dirname(__FILE__)) . '/' . $test['qr_code_image'];
                if (file_exists($qr_file)) {
                    @unlink($qr_file);
                }
            }
            
            // Testi sil (database.php'deki güvenli fonksiyon)
            if (deleteTestById($test_id, $_SESSION['user_id'])) {
                header('Location: dashboard.php?deleted=1');
                exit;
            } else {
                logError("Test silme hatası", [
                    'test_id' => $test_id,
                    'user_id' => $_SESSION['user_id']
                ]);
                header('Location: dashboard.php?error=' . urlencode('Test silinemedi.'));
                exit;
            }
        } else {
            header('Location: dashboard.php?error=' . urlencode('Test bulunamadı'));
            exit;
        }
    } else {
        // Onay sayfası göster
        $test = getTestResultById($test_id);
        
        if (!$test) {
            header('Location: dashboard.php?error=' . urlencode('Test bulunamadı'));
            exit;
        }
    }
} else {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Sil - CROMTEST</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link rel="apple-touch-icon" href="../assets/images/logo.png">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <div class="header-content">
                <div class="admin-logo-title">
                    <img src="../assets/images/logo.png" alt="CROMTEST Logo" class="admin-logo">
                    <h1>Test Sil</h1>
                </div>
                <div class="user-info">
                    <a href="dashboard.php" class="btn-secondary">← Geri Dön</a>
                </div>
            </div>
        </header>

        <main class="admin-main">
            <div class="delete-confirm">
                <h2>Test Silme Onayı</h2>
                <p>Bu işlem geri alınamaz!</p>
                <div class="test-info">
                    <p><strong>QR Kod:</strong> <?php echo escape($test['qr_code']); ?></p>
                    <p><strong>Test Tarihi:</strong> <?php echo date('d.m.Y H:i', strtotime($test['test_date'])); ?></p>
                </div>
                <div class="confirm-actions">
                    <a href="delete_test.php?id=<?php echo $test_id; ?>&confirm=yes&csrf_token=<?php echo urlencode(generateCSRFToken()); ?>" class="btn-danger">Evet, Sil</a>
                    <a href="dashboard.php" class="btn-secondary">İptal</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

