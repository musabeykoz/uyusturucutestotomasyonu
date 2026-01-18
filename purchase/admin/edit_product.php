<?php
require_once '../config/auth.php';
require_once '../config/database.php';
requireLogin();

$conn = getDBConnection();
$error = '';
$success = '';
$product = null;

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    
    if (!$product) {
        header('Location: dashboard.php?error=Ürün bulunamadı');
        exit;
    }
} else {
    header('Location: dashboard.php');
    exit;
}

// Ürün görsellerini getir
$images_stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, image_order ASC");
$images_stmt->bind_param("i", $id);
$images_stmt->execute();
$product_images = $images_stmt->get_result();
$images_stmt->close();

// Admin paneli için görsel path'ini düzeltme fonksiyonu
function getAdminImagePath($image_path) {
    if (empty($image_path)) {
        return '';
    }
    
    // URL ise direkt kullan
    if (strpos($image_path, 'http://') === 0 || strpos($image_path, 'https://') === 0) {
        return $image_path;
    }
    
    // Path'i temizle
    $image_path = trim($image_path);
    $image_path = ltrim($image_path, '/');
    
    // Base URL'i al (purchase klasörü için)
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    
    // Script path'den base path'i çıkar (purchase klasörü için)
    // Örnek: /17.11.2025/purchase/admin/edit_product.php -> /17.11.2025/purchase/
    $basePath = dirname(dirname($script)); // purchase/admin -> purchase
    $basePath = rtrim($basePath, '/\\') . '/';
    
    // Base URL oluştur
    $baseUrl = $protocol . "://" . $host . $basePath;
    
    // uploads/ ile başlıyorsa base URL'e ekle
    // Veritabanında: uploads/product_images/filename.jpg
    // Sonuç: http://host/purchase/uploads/product_images/filename.jpg
    if (strpos($image_path, 'uploads/') === 0) {
        return $baseUrl . $image_path;
    }
    
    // Eğer path zaten ../../ ile başlıyorsa relative path olarak kullan
    if (strpos($image_path, '../../') === 0) {
        return $image_path;
    }
    
    // Eğer sadece dosya adı varsa, uploads/product_images/ ekle
    if (!empty($image_path) && strpos($image_path, '/') === false) {
        return $baseUrl . 'uploads/product_images/' . $image_path;
    }
    
    // Diğer durumlarda base URL'e ekle
    return $baseUrl . $image_path;
}

// Görsel ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_image') {
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    
    if (!verifyCSRFToken($csrf_token)) {
        header('Location: edit_product.php?id=' . $id . '&error=' . urlencode('Geçersiz istek. Sayfayı yenileyip tekrar deneyin.'));
        exit;
    } else {
        $image_path = '';
        $is_primary = isset($_POST['is_primary']) ? 1 : 0;
        $error = '';
        
        // Dosya yükleme kontrolü
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/product_images/';
            
            // Klasör yoksa oluştur
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file = $_FILES['image_file'];
            $file_name = $file['name'];
            $file_tmp = $file['tmp_name'];
            $file_size = $file['size'];
            
            // Dosya tipi kontrolü
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = mime_content_type($file_tmp);
            
            if (!in_array($file_type, $allowed_types)) {
                $error = 'Sadece JPG, PNG, GIF ve WEBP formatları desteklenmektedir.';
            } elseif ($file_size > 5 * 1024 * 1024) { // 5MB limit
                $error = 'Dosya boyutu 5MB\'dan küçük olmalıdır.';
            } else {
                // Güvenli dosya adı oluştur
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $safe_filename = 'product_' . $id . '_' . time() . '_' . uniqid() . '.' . strtolower($file_extension);
                $target_path = $upload_dir . $safe_filename;
                
                // Dosyayı yükle
                if (move_uploaded_file($file_tmp, $target_path)) {
                    $image_path = 'uploads/product_images/' . $safe_filename;
                } else {
                    $error = 'Dosya yüklenirken bir hata oluştu.';
                }
            }
        } elseif (isset($_POST['image_url']) && !empty($_POST['image_url'])) {
            // URL ile görsel ekleme
            $image_path = sanitizeInput($_POST['image_url']);
        } else {
            $error = 'Lütfen bir dosya seçin veya görsel URL\'si girin.';
        }
        
        if (empty($error) && !empty($image_path)) {
            // Görsel sırasını belirle
            $order_stmt = $conn->prepare("SELECT MAX(image_order) as max_order FROM product_images WHERE product_id = ?");
            $order_stmt->bind_param("i", $id);
            $order_stmt->execute();
            $order_result = $order_stmt->get_result();
            $max_order = $order_result->fetch_assoc()['max_order'] ?? 0;
            $order_stmt->close();
            
            // Eğer ana görsel seçildiyse, diğerlerini ana görsel yapma
            if ($is_primary) {
                $update_primary_stmt = $conn->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?");
                $update_primary_stmt->bind_param("i", $id);
                $update_primary_stmt->execute();
                $update_primary_stmt->close();
            }
            
            $insert_stmt = $conn->prepare("INSERT INTO product_images (product_id, image_path, image_order, is_primary) VALUES (?, ?, ?, ?)");
            $new_order = $max_order + 1;
            $insert_stmt->bind_param("isii", $id, $image_path, $new_order, $is_primary);
            
            if ($insert_stmt->execute()) {
                $insert_stmt->close();
                header('Location: edit_product.php?id=' . $id . '&success=' . urlencode('Görsel başarıyla eklendi!'));
                exit;
            } else {
                $error = 'Veritabanına kaydedilirken bir hata oluştu.';
            }
            $insert_stmt->close();
        }
        
        // Hata durumunda da redirect yap
        if (!empty($error)) {
            header('Location: edit_product.php?id=' . $id . '&error=' . urlencode($error));
            exit;
        }
    }
}

// Görsel silme
if (isset($_GET['delete_image'])) {
    $image_id = intval($_GET['delete_image']);
    
    // Önce görsel bilgisini al (dosyayı silmek için)
    $get_image_stmt = $conn->prepare("SELECT image_path FROM product_images WHERE id = ? AND product_id = ?");
    $get_image_stmt->bind_param("ii", $image_id, $id);
    $get_image_stmt->execute();
    $image_result = $get_image_stmt->get_result();
    $image_data = $image_result->fetch_assoc();
    $get_image_stmt->close();
    
    if ($image_data) {
        // Veritabanından sil
        $delete_stmt = $conn->prepare("DELETE FROM product_images WHERE id = ? AND product_id = ?");
        $delete_stmt->bind_param("ii", $image_id, $id);
        
        if ($delete_stmt->execute()) {
            // Dosyayı da sil (eğer uploads klasöründeyse)
            $image_path = $image_data['image_path'];
            if (strpos($image_path, 'uploads/product_images/') === 0) {
                $file_path = __DIR__ . '/../' . $image_path;
                if (file_exists($file_path)) {
                    @unlink($file_path);
                }
            }
            
            $success = 'Görsel başarıyla silindi!';
            header('Location: edit_product.php?id=' . $id . '&success=' . urlencode($success));
            exit;
        }
        $delete_stmt->close();
    }
}

// Ana görsel ayarlama
if (isset($_GET['set_primary'])) {
    $image_id = intval($_GET['set_primary']);
    // Önce tüm görselleri ana görsel yapma
    $reset_primary_stmt = $conn->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?");
    $reset_primary_stmt->bind_param("i", $id);
    $reset_primary_stmt->execute();
    $reset_primary_stmt->close();
    
    // Seçilen görseli ana görsel yap
    $primary_stmt = $conn->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?");
    $primary_stmt->bind_param("ii", $image_id, $id);
    if ($primary_stmt->execute()) {
        $success = 'Ana görsel ayarlandı!';
        header('Location: edit_product.php?id=' . $id . '&success=' . urlencode($success));
        exit;
    }
    $primary_stmt->close();
}

// Ürün güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] !== 'add_image')) {
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    
    if (!verifyCSRFToken($csrf_token)) {
        header('Location: edit_product.php?id=' . $id . '&error=' . urlencode('Geçersiz istek. Sayfayı yenileyip tekrar deneyin.'));
        exit;
    } else {
        $name = sanitizeInput($_POST['name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $category = sanitizeInput($_POST['category'] ?? '');
        $purchase_link = sanitizeInput($_POST['purchase_link'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $error = '';
        
        if (empty($name) || $price <= 0) {
            $error = 'Lütfen tüm gerekli alanları doldurun!';
        } else {
            $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, category = ?, purchase_link = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("ssdssii", $name, $description, $price, $category, $purchase_link, $is_active, $id);
            
            if ($stmt->execute()) {
                $stmt->close();
                header('Location: edit_product.php?id=' . $id . '&success=' . urlencode('Ürün başarıyla güncellendi!'));
                exit;
            } else {
                $error = 'Bir hata oluştu: ' . $stmt->error;
            }
            $stmt->close();
        }
        
        // Hata durumunda da redirect yap
        if (!empty($error)) {
            header('Location: edit_product.php?id=' . $id . '&error=' . urlencode($error));
            exit;
        }
    }
}

// Success ve error mesajlarını GET'ten al
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ürün Düzenle - CROMTEST</title>
    <link rel="icon" type="image/png" href="../../QRpanel/assets/images/logo.png">
    <link rel="stylesheet" href="../../QRpanel/assets/css/style.css">
    <link rel="stylesheet" href="../../QRpanel/assets/css/admin.css">
    <style>
        /* Alert Stilleri */
        .error-alert,
        .success-alert {
            padding: 15px 20px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .error-alert {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #fca5a5;
        }
        
        .success-alert {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.35);
            color: #34d399;
        }
        
        /* Form Container */
        .page-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .content-wrapper {
            display: flex;
            flex-direction: column;
            gap: 30px;
            margin-top: 20px;
        }
        
        /* Form ve Görseller için container */
        .form-images-container {
            display: flex;
            flex-direction: column;
            gap: 30px;
            width: 100%;
        }
        
        /* Form Card */
        .form-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 30px;
            box-shadow: var(--shadow-light);
            width: 100%;
            box-sizing: border-box;
        }
        
        .form-card h2 {
            margin: 0 0 25px 0;
            color: var(--text-primary);
            font-size: 1.5rem;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 10px;
        }
        
        /* Ürün Bilgileri kartı için özel stil */
        .product-info-card {
            /* Varsayılan: tek sütun */
        }
        
        /* Görseller kartı için özel stil */
        .images-section {
            /* Varsayılan: tek sütun */
        }
        
        /* Form Elements */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            font-size: 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.04);
            color: var(--text-primary);
            font-family: inherit;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.22);
            background: rgba(255, 255, 255, 0.08);
        }
        
        .form-control[type="checkbox"] {
            width: auto;
            margin-right: 8px;
            cursor: pointer;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        
        small {
            display: block;
            margin-top: 5px;
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
            flex-wrap: wrap;
        }
        
        /* Image Upload Form */
        .image-upload-form {
            background: rgba(255, 255, 255, 0.02);
            padding: 25px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            margin-bottom: 30px;
        }
        
        /* Images Grid */
        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .image-item {
            position: relative;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 15px;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .image-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
            border-color: var(--primary);
        }
        
        .image-item img {
            width: 100%;
            max-height: 250px;
            min-height: 150px;
            object-fit: contain;
            border-radius: var(--radius-md);
            margin-bottom: 10px;
            border: 1px solid var(--border);
            display: block;
            background: rgba(255, 255, 255, 0.02);
            cursor: pointer;
            transition: opacity 0.3s ease;
        }
        
        .image-item img:hover {
            opacity: 0.9;
        }
        
        .image-actions {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .primary-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.75rem;
            font-weight: 600;
            z-index: 1;
        }
        
        /* File Preview */
        #filePreview {
            text-align: center;
            margin-top: 15px;
        }
        
        #previewImage {
            max-width: 250px;
            width: 100%;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
        }
        
        input[type="file"] {
            padding: 8px;
            cursor: pointer;
            width: 100%;
        }
        
        input[type="file"]::-webkit-file-upload-button {
            padding: 8px 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            margin-right: 10px;
        }
        
        .divider {
            text-align: center;
            margin: 20px 0;
            color: var(--text-muted);
            position: relative;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: var(--border);
        }
        
        .divider::before {
            left: 0;
        }
        
        .divider::after {
            right: 0;
        }
        
        /* Responsive - Desktop (1200px+) */
        @media (min-width: 1200px) {
            .form-images-container {
                display: grid;
                grid-template-columns: 500px 1fr;
                gap: 30px;
                align-items: start;
            }
            
            /* Ürün Bilgileri kartı - Sol tarafta */
            .product-info-card {
                grid-column: 1;
                grid-row: 1;
            }
            
            /* Görseller kartı - Sağ tarafta */
            .images-section {
                grid-column: 2;
                grid-row: 1;
            }
            
            .images-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        /* Responsive - Tablet (769px - 1199px) */
        @media (min-width: 769px) and (max-width: 1199px) {
            .images-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        /* Responsive - Mobile (481px - 768px) */
        @media (max-width: 768px) {
            .page-container {
                padding: 0 15px;
            }
            
            .form-card {
                padding: 20px;
            }
            
            .form-card h2 {
                font-size: 1.3rem;
            }
            
            .header-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .user-info {
                width: 100%;
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .images-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            
            .image-upload-form {
                padding: 20px;
            }
            
            .form-control {
                padding: 10px 14px;
                font-size: 0.95rem;
            }
        }
        
        /* Responsive - Small Mobile (480px ve altı) */
        @media (max-width: 480px) {
            .admin-header {
                padding: 15px 0;
            }
            
            .admin-logo-title {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .form-card {
                padding: 15px;
            }
            
            .form-card h2 {
                font-size: 1.2rem;
                margin-bottom: 20px;
            }
            
            .images-grid {
                grid-template-columns: 1fr;
            }
            
            .image-item {
                padding: 12px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .form-actions .btn-primary,
            .form-actions .btn-secondary {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <div class="header-content">
                <div class="admin-logo-title">
                    <img src="../../QRpanel/assets/images/logo.png" alt="CROMTEST Logo" class="admin-logo">
                    <h1>Ürün Düzenle</h1>
                </div>
                <div class="user-info">
                    <a href="dashboard.php" class="btn-secondary">← Geri</a>
                </div>
            </div>
        </header>

        <main class="admin-main">
            <div class="page-container">
                <?php if ($error): ?>
                    <div class="error-alert">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-alert">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <div class="content-wrapper">
                    <div class="form-images-container">
                        <!-- Ürün Bilgileri Formu -->
                        <div class="form-card product-info-card">
                        <h2>Ürün Bilgileri</h2>
                        <form method="POST" action="edit_product.php?id=<?php echo $id; ?>">
                            <?php echo csrfTokenField(); ?>
                            
                            <div class="form-group">
                                <label>Ürün Adı *</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label>Açıklama</label>
                                <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Fiyat (₺) *</label>
                                <input type="number" name="price" step="0.01" min="0" value="<?php echo $product['price']; ?>" required class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label>Kategori</label>
                                <input type="text" name="category" value="<?php echo htmlspecialchars($product['category'] ?? ''); ?>" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label>Satın Alma Linki</label>
                                <input type="url" name="purchase_link" value="<?php echo htmlspecialchars($product['purchase_link'] ?? ''); ?>" class="form-control" placeholder="https://example.com/satin-al">
                                <small>Müşterilerin ürünü satın alabileceği link</small>
                            </div>
                            
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="is_active" value="1" <?php echo $product['is_active'] == 1 ? 'checked' : ''; ?>> Aktif
                                </label>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Güncelle</button>
                                <a href="dashboard.php" class="btn-secondary">İptal</a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Görsel Yönetimi -->
                    <div class="form-card images-section">
                        <h2>Ürün Görselleri</h2>
                        
                        <form method="POST" action="edit_product.php?id=<?php echo $id; ?>" enctype="multipart/form-data" class="image-upload-form">
                            <?php echo csrfTokenField(); ?>
                            <input type="hidden" name="action" value="add_image">
                            
                            <div class="form-group">
                                <label>Dosya Seç *</label>
                                <input type="file" name="image_file" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" class="form-control" id="imageFileInput">
                                <small>JPG, PNG, GIF veya WEBP formatında, maksimum 5MB</small>
                                <div id="filePreview" style="display: none;">
                                    <img id="previewImage" src="" alt="Önizleme">
                                </div>
                            </div>
                            
                            <div class="divider">veya</div>
                            
                            <div class="form-group">
                                <label>Görsel URL</label>
                                <input type="url" name="image_url" class="form-control" placeholder="https://example.com/image.jpg">
                                <small>URL ile görsel eklemek için (opsiyonel)</small>
                            </div>
                            
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="is_primary" value="1"> Ana görsel olarak ayarla
                                </label>
                            </div>
                            
                            <button type="submit" class="btn-primary">Görsel Ekle</button>
                        </form>

                        <div class="images-grid">
                            <?php if ($product_images && $product_images->num_rows > 0): ?>
                                <?php while ($image = $product_images->fetch_assoc()): ?>
                                    <div class="image-item">
                                        <?php if ($image['is_primary']): ?>
                                            <span class="primary-badge">Ana Görsel</span>
                                        <?php endif; ?>
                                        <?php 
                                        // Görsel path'ini düzelt (admin paneli için)
                                        $admin_image_path = getAdminImagePath($image['image_path']);
                                        ?>
                                        <img src="<?php echo htmlspecialchars($admin_image_path); ?>" 
                                             alt="Görsel" 
                                             title="DB Path: <?php echo htmlspecialchars($image['image_path']); ?> | Display Path: <?php echo htmlspecialchars($admin_image_path); ?>"
                                             loading="lazy"
                                             onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div style="display: none; width: 100%; height: 200px; align-items: center; justify-content: center; background: rgba(255,255,255,0.05); border: 1px solid var(--border); border-radius: var(--radius-md); color: var(--muted); flex-direction: column;">
                                            <div style="font-size: 2rem; margin-bottom: 10px;">⚠️</div>
                                            <div style="text-align: center; font-size: 0.9rem;">Görsel yüklenemedi</div>
                                            <div style="font-size: 0.75rem; margin-top: 5px; color: var(--text-muted); word-break: break-all; padding: 0 10px;"><?php echo htmlspecialchars($image['image_path']); ?></div>
                                        </div>
                                        <div class="image-actions">
                                            <?php if (!$image['is_primary']): ?>
                                                <a href="edit_product.php?id=<?php echo $id; ?>&set_primary=<?php echo $image['id']; ?>" 
                                                   class="btn-action btn-edit" title="Ana Görsel Yap">
                                                    ⭐
                                                </a>
                                            <?php endif; ?>
                                            <a href="edit_product.php?id=<?php echo $id; ?>&delete_image=<?php echo $image['id']; ?>" 
                                               class="btn-action btn-delete" 
                                               title="Sil"
                                               onclick="return confirm('Bu görseli silmek istediğinize emin misiniz?');">
                                                🗑️
                                            </a>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p style="color: var(--muted); grid-column: 1 / -1; text-align: center; padding: 40px;">Henüz görsel eklenmemiş.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Dosya seçildiğinde önizleme göster
        document.getElementById('imageFileInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('filePreview');
            const previewImage = document.getElementById('previewImage');
            
            if (file) {
                // Dosya tipi kontrolü
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Sadece JPG, PNG, GIF ve WEBP formatları desteklenmektedir.');
                    e.target.value = '';
                    preview.style.display = 'none';
                    return;
                }
                
                // Dosya boyutu kontrolü (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Dosya boyutu 5MB\'dan küçük olmalıdır.');
                    e.target.value = '';
                    preview.style.display = 'none';
                    return;
                }
                
                // Önizleme göster
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });
    </script>
</body>
</html>
