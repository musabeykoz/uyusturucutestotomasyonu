<?php
// QR kod oluşturma fonksiyonu
// PHP QR Code kütüphanesi kullanılacak

function generateQRCode($qr_code, $test_id) {
    // QR kod görseli için klasör (root dizinden)
    $base_dir = dirname(dirname(__FILE__));
    $qr_dir = $base_dir . '/uploads/qr_codes/';
    
    // Klasör yoksa oluştur
    if (!file_exists($qr_dir)) {
        if (!mkdir($qr_dir, 0777, true)) {
            return false;
        }
    }
    
    // Klasör yazılabilir mi kontrol et
    if (!is_writable($qr_dir)) {
        return false;
    }
    
    // QR kod görsel dosya adı
    $filename = 'qr_' . $test_id . '_' . time() . '.png';
    $filepath = $qr_dir . $filename;
    
    // QR kod içeriği (sonuç sayfasına yönlendirme URL'i)
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    
    // Base URL'i oluştur - root dizindeki result.php'ye işaret etmeli
    // SCRIPT_NAME kullanarak base path'i bul (admin klasörünü çıkar)
    $script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    
    // script_name örneği: /17.11.2025/admin/new_test.php
    // Base path: /17.11.2025/ (admin klasörü çıkarılmalı)
    if (!empty($script_name)) {
        $script_parts = array_filter(explode('/', $script_name));
        $script_parts = array_values($script_parts);
        
        // Admin, config, api gibi alt klasörleri atla, sadece root dizini al
        $base_parts = [];
        foreach ($script_parts as $part) {
            // Alt klasörleri atla (admin, config, api, assets vb.)
            if (in_array($part, ['admin', 'config', 'api', 'assets', 'uploads'])) {
                break;
            }
            // PHP dosyası varsa dur
            if (strpos($part, '.php') !== false || strpos($part, '.html') !== false) {
                break;
            }
            $base_parts[] = $part;
        }
        
        if (count($base_parts) > 0) {
            // İlk dizin adını al (17.11.2025 gibi) - bu root dizin
            $base_path = '/' . $base_parts[0] . '/';
        } else {
            $base_path = '/';
        }
    } else {
        $base_path = '/';
    }
    
    // Windows path'lerini temizle
    $base_path = str_replace('\\', '/', $base_path);
    
    // Base path'i normalize et (çift slash'ları temizle)
    $base_path = '/' . trim($base_path, '/') . '/';
    $base_path = preg_replace('#/+#', '/', $base_path);
    
    // URL'i oluştur - her zaman root dizindeki result.php'ye işaret et
    $host = rtrim($host, '/');
    $qr_content = $protocol . "://" . $host . $base_path . "result.php?qr=" . urlencode($qr_code);
    
    // QR kod oluşturma - Birden fazla API denemesi
    $qr_apis = [
        "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qr_content),
        "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . urlencode($qr_content),
        "https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=10&data=" . urlencode($qr_content)
    ];
    
    $qr_image = false;
    $last_error = '';
    
    foreach ($qr_apis as $api_url) {
        // cURL kullanarak dene (file_get_contents yerine)
        if (function_exists('curl_init')) {
            $ch = curl_init($api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $qr_image = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($qr_image !== false && $http_code == 200) {
                break;
            }
        } else {
            // file_get_contents ile dene
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'follow_location' => true
                ]
            ]);
            $qr_image = @file_get_contents($api_url, false, $context);
            if ($qr_image !== false) {
                break;
            }
        }
    }
    
    // QR kod görselini kaydet
    if ($qr_image !== false && strlen($qr_image) > 0) {
        $saved = @file_put_contents($filepath, $qr_image);
        if ($saved !== false) {
            return 'uploads/qr_codes/' . $filename;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

// QR kod görselini göster
function displayQRCode($qr_image_path) {
    if ($qr_image_path) {
        $base_dir = dirname(dirname(__FILE__));
        $full_path = $base_dir . '/' . $qr_image_path;
        if (file_exists($full_path)) {
            return '<img src="' . htmlspecialchars($qr_image_path) . '" alt="QR Kod">';
        }
    }
    return '';
}
?>