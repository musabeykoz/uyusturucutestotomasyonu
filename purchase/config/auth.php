<?php
// Purchase sistemi için auth
// QRpanel'deki auth.php'yi kullan
require_once __DIR__ . '/../../QRpanel/config/auth.php';
require_once __DIR__ . '/../../QRpanel/config/security.php';
require_once __DIR__ . '/../../QRpanel/config/session.php';

// Güvenli session başlat
startSecureSession();
?>

