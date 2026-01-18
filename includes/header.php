<?php
$script = $_SERVER['SCRIPT_NAME'];
$isPanel = strpos($script, '/QRpanel/') !== false;
$isPurchase = strpos($script, '/purchase/') !== false;
$assetBase = ($isPanel || $isPurchase) ? '../QRpanel/assets/' : 'QRpanel/assets/';
$homePrefix = ($isPanel || $isPurchase) ? '../' : '';
?>
<nav class="navbar" id="navbar">
    <div class="nav-container">
        <a href="<?php echo $homePrefix; ?>" class="nav-logo">
            <img src="<?php echo $assetBase; ?>images/logo.png" alt="CROMTEST Logo" class="logo-img">
            <span class="logo-text">CROMTEST</span>
        </a>
        <ul class="nav-menu" id="navMenu">
            <li><a href="<?php echo $homePrefix; ?>#home" class="nav-link">Ana Sayfa</a></li>
            <li><a href="<?php echo $homePrefix; ?>#features" class="nav-link">Özellikler</a></li>
            <li><a href="<?php echo $homePrefix; ?>#contact" class="nav-link">İletişim</a></li>
            <?php if ($isPanel): ?>
                <li><a href="#qr-section" class="nav-link">QR Okuma</a></li>
            <?php else: ?>
                <li><a href="<?php echo $homePrefix; ?>QRpanel/" class="nav-link">Test Et</a></li>
            <?php endif; ?>
            <?php if (!$isPurchase): ?>
                <li><a href="<?php echo $homePrefix; ?>purchase/" class="nav-link btn-nav-cta">Satın Al</a></li>
            <?php endif; ?>
        </ul>
        <div class="nav-toggle" id="navToggle">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
</nav>
