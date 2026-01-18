<?php
// Geliştirme ortamında hataları göster
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// QRpanel'deki auth sistemini kullan
require_once __DIR__ . '/../QRpanel/config/auth.php';
require_once __DIR__ . '/../QRpanel/config/security.php';

$error = '';
$success = '';

// Zaten giriş yapmışsa panel seçim sayfasına yönlendir
if (isLoggedIn()) {
    header('Location: panel_selector.php');
    exit;
}

// Session timeout mesajı
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'session_timeout') {
        $error = 'Oturumunuz zaman aşımına uğradı. Lütfen tekrar giriş yapın.';
    } elseif ($_GET['error'] === 'session_invalid') {
        $error = 'Güvenlik nedeniyle oturumunuz sonlandırıldı. Lütfen tekrar giriş yapın.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token kontrolü
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    
    if (!verifyCSRFToken($csrf_token)) {
        $error = 'Geçersiz istek. Sayfayı yenileyip tekrar deneyin.';
        logSecurity("CSRF token doğrulama hatası - Login", ['ip' => getClientIP()]);
    } else {
        $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : ''; // Şifre sanitize edilmez
        
        if (!empty($username) && !empty($password)) {
            $login_result = login($username, $password);
            
            if ($login_result['success']) {
                // Başarılı giriş - Panel seçim sayfasına yönlendir
                header('Location: panel_selector.php');
                exit;
            } else {
                $error = $login_result['error'];
            }
        } else {
            $error = 'Lütfen tüm alanları doldurun!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş - CROMTEST</title>
    <link rel="icon" type="image/png" href="../QRpanel/assets/images/logo.png">
    <link rel="apple-touch-icon" href="../QRpanel/assets/images/logo.png">
    <link rel="stylesheet" href="../QRpanel/assets/css/style.css">
    <link rel="stylesheet" href="../QRpanel/assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-container">
                    <img src="../QRpanel/assets/images/logo.png" alt="CROMTEST Logo" class="logo">
                </div>
                <h1>CROMTEST</h1>
                <p>Yetkili Girişi</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="login.php" class="login-form">
                <?php echo csrfTokenField(); ?>
                
                <div class="form-group">
                    <label for="username">Kullanıcı Adı</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required 
                        autofocus
                        placeholder="Kullanıcı adınızı girin"
                        value="<?php echo isset($_POST['username']) ? escape($_POST['username']) : ''; ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Şifre</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        placeholder="Şifrenizi girin"
                    >
                </div>
                
                <button type="submit" class="btn-login">Giriş Yap</button>
            </form>
            
            <div class="login-footer">
                <a href="../index.php" class="back-link">← Ana Sayfaya Dön</a>
            </div>
        </div>
    </div>
</body>
</html>

