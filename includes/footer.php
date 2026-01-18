<?php
$script = $_SERVER['SCRIPT_NAME'];
$isPanel = strpos($script, '/QRpanel/') !== false;
$isPurchase = strpos($script, '/purchase/') !== false;
$assetBase = ($isPanel || $isPurchase) ? '../QRpanel/assets/' : 'QRpanel/assets/';
$homePrefix = ($isPanel || $isPurchase) ? '../' : '';

// Admin login linkini güvenli şekilde oluştur
if ($isPanel || $isPurchase) {
    $adminLoginLink = '../admin/login.php';
} else {
    $adminLoginLink = 'admin/login.php';
}
?>
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <div class="footer-logo">
                    <img src="<?php echo $assetBase; ?>images/logo.png" alt="CROMTEST Logo" class="logo-img">
                    <span class="logo-text">CROMTEST</span>
                </div>
                <p class="footer-description">
                    Profesyonel QR kod tabanlı uyuşturucu tarama testi sistemi
                </p>
            </div>
            <div class="footer-section">
                <h4 class="footer-title">Hızlı Linkler</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo $homePrefix; ?>#home">Ana Sayfa</a></li>
                    <li><a href="<?php echo $homePrefix; ?>#features">Özellikler</a></li>
                    <li><a href="<?php echo $homePrefix; ?>#contact">İletişim</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4 class="footer-title">Sistem</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo $isPanel ? '#qr-section' : ($homePrefix . 'QRpanel/'); ?>">Test Sistemi</a></li>
                    <li><a href="<?php echo $adminLoginLink; ?>">Yönetim Paneli</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4 class="footer-title">Yasal</h4>
                <ul class="footer-links">
                    <li><a href="#">Gizlilik Politikası</a></li>
                    <li><a href="#">Kullanım Şartları</a></li>
                    <li><a href="#">KVKK</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 CROMTEST. Tüm hakları saklıdır.</p>
        </div>
    </div>
</footer>
