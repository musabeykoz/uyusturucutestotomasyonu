<?php
require_once '../config/auth.php';
require_once '../config/database.php';
requireLogin();

$conn = getDBConnection();
$error = '';
$success = '';

// Ürün ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    
    if (!verifyCSRFToken($csrf_token)) {
        header('Location: add_product.php?error=' . urlencode('Geçersiz istek. Sayfayı yenileyip tekrar deneyin.'));
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
            $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, purchase_link, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdssi", $name, $description, $price, $category, $purchase_link, $is_active);
            
            if ($stmt->execute()) {
                $new_product_id = $conn->insert_id;
                $stmt->close();
                
                // Görselleri ekle
                $image_order = 0;
                $has_primary = false;
                $primary_selected = isset($_POST['primary_image']) && $_POST['primary_image'] !== '' ? intval($_POST['primary_image']) : null;
                $primary_url_selected = isset($_POST['primary_url']) && $_POST['primary_url'] !== '' ? $_POST['primary_url'] : null;
                $first_image_set = false;
                
                // Dosya yükleme ile görseller
                if (isset($_FILES['image_files']) && is_array($_FILES['image_files']['name'])) {
                    $upload_dir = __DIR__ . '/../uploads/product_images/';
                    
                    // Klasör yoksa oluştur
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_count = count($_FILES['image_files']['name']);
                    for ($i = 0; $i < $file_count; $i++) {
                        if ($_FILES['image_files']['error'][$i] === UPLOAD_ERR_OK) {
                            $file = [
                                'name' => $_FILES['image_files']['name'][$i],
                                'tmp_name' => $_FILES['image_files']['tmp_name'][$i],
                                'size' => $_FILES['image_files']['size'][$i]
                            ];
                            
                            // Dosya tipi kontrolü
                            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                            $file_type = mime_content_type($file['tmp_name']);
                            
                            if (in_array($file_type, $allowed_types) && $file['size'] <= 5 * 1024 * 1024) {
                                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                                $safe_filename = 'product_' . $new_product_id . '_' . time() . '_' . uniqid() . '_' . $i . '.' . strtolower($file_extension);
                                $target_path = $upload_dir . $safe_filename;
                                
                                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                                    $image_path = 'uploads/product_images/' . $safe_filename;
                                    // Ana görsel kontrolü: seçilmişse o, değilse ilk görsel
                                    $is_primary = (!$has_primary && (($primary_selected !== null && $primary_selected == $i) || ($primary_selected === null && $primary_url_selected === null && !$first_image_set))) ? 1 : 0;
                                    if ($is_primary) {
                                        $has_primary = true;
                                        $first_image_set = true;
                                    }
                                    
                                    $image_order++;
                                    $insert_stmt = $conn->prepare("INSERT INTO product_images (product_id, image_path, image_order, is_primary) VALUES (?, ?, ?, ?)");
                                    $insert_stmt->bind_param("isii", $new_product_id, $image_path, $image_order, $is_primary);
                                    $insert_stmt->execute();
                                    $insert_stmt->close();
                                }
                            }
                        }
                    }
                }
                
                // URL ile görseller
                if (isset($_POST['image_urls']) && is_array($_POST['image_urls'])) {
                    foreach ($_POST['image_urls'] as $url) {
                        $url = trim($url);
                        if (!empty($url)) {
                            $image_path = sanitizeInput($url);
                            // Ana görsel kontrolü: seçilmişse o, değilse ilk görsel (eğer dosya yoksa ve primary_selected yoksa)
                            $is_primary = (!$has_primary && (($primary_url_selected !== null && $primary_url_selected == $url) || ($primary_selected === null && $primary_url_selected === null && !$first_image_set))) ? 1 : 0;
                            if ($is_primary) {
                                $has_primary = true;
                                $first_image_set = true;
                            }
                            
                            $image_order++;
                            $insert_stmt = $conn->prepare("INSERT INTO product_images (product_id, image_path, image_order, is_primary) VALUES (?, ?, ?, ?)");
                            $insert_stmt->bind_param("isii", $new_product_id, $image_path, $image_order, $is_primary);
                            $insert_stmt->execute();
                            $insert_stmt->close();
                        }
                    }
                }
                
                header('Location: dashboard.php?success=' . urlencode('Ürün başarıyla eklendi!'));
                exit;
            } else {
                $error = 'Bir hata oluştu: ' . $stmt->error;
            }
            $stmt->close();
        }
        
        // Hata durumunda da redirect yap
        if (!empty($error)) {
            header('Location: add_product.php?error=' . urlencode($error));
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
    <title>Yeni Ürün Ekle - CROMTEST</title>
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
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .content-wrapper {
            display: flex;
            flex-direction: column;
            gap: 30px;
            margin-top: 20px;
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
        
        /* Responsive - Mobile */
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
            
            .form-control {
                padding: 10px 14px;
                font-size: 0.95rem;
            }
        }
        
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
                    <h1>Yeni Ürün Ekle</h1>
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
                    <div class="form-card">
                        <h2>Ürün Bilgileri</h2>
                        <form method="POST" action="add_product.php" enctype="multipart/form-data">
                            <?php echo csrfTokenField(); ?>
                            
                            <div class="form-group">
                                <label>Ürün Adı *</label>
                                <input type="text" name="name" required class="form-control" placeholder="Ürün adını girin">
                            </div>
                            
                            <div class="form-group">
                                <label>Açıklama</label>
                                <textarea name="description" class="form-control" rows="4" placeholder="Ürün açıklamasını girin"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Fiyat (₺) *</label>
                                <input type="number" name="price" step="0.01" min="0" required class="form-control" placeholder="0.00">
                            </div>
                            
                            <div class="form-group">
                                <label>Kategori</label>
                                <input type="text" name="category" class="form-control" placeholder="Kategori adı">
                            </div>
                            
                            <div class="form-group">
                                <label>Satın Alma Linki</label>
                                <input type="url" name="purchase_link" class="form-control" placeholder="https://example.com/satin-al">
                                <small>Müşterilerin ürünü satın alabileceği link</small>
                            </div>
                            
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="is_active" value="1" checked> Aktif
                                </label>
                            </div>
                            
                            <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--border);">
                            
                            <h3 style="margin-bottom: 20px; color: var(--text-primary); font-size: 1.2rem;">Ürün Görselleri (Opsiyonel)</h3>
                            
                            <div class="form-group">
                                <label>Görsel Dosyaları</label>
                                <input type="file" name="image_files[]" multiple accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" class="form-control">
                                <small>JPG, PNG, GIF veya WEBP formatında, maksimum 5MB. Birden fazla görsel seçebilirsiniz.</small>
                            </div>
                            
                            <div class="form-group" id="urlInputs">
                                <label>Görsel URL'leri</label>
                                <div id="urlContainer">
                                    <div class="url-input-group" style="display: flex; gap: 10px; margin-bottom: 10px;">
                                        <input type="url" name="image_urls[]" class="form-control" placeholder="https://example.com/image.jpg" onchange="updatePrimarySelects()">
                                        <button type="button" class="btn-secondary" onclick="removeUrlInput(this)" style="padding: 10px 15px;">-</button>
                                    </div>
                                </div>
                                <button type="button" class="btn-secondary" onclick="addUrlInput()" style="margin-top: 10px;">+ URL Ekle</button>
                                <small>URL ile görsel eklemek için</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Ana Görsel</label>
                                <select name="primary_image" class="form-control" id="primaryImageSelect" style="display: none;">
                                    <option value="">Seçiniz</option>
                                </select>
                                <select name="primary_url" class="form-control" id="primaryUrlSelect" style="display: none;">
                                    <option value="">Seçiniz</option>
                                </select>
                                <small id="primaryImageHelp">Görseller eklendikten sonra ana görsel seçilebilir</small>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Ürün Ekle</button>
                                <a href="dashboard.php" class="btn-secondary">İptal</a>
                            </div>
                        </form>
                        
                        <script>
                            function addUrlInput() {
                                const container = document.getElementById('urlContainer');
                                const div = document.createElement('div');
                                div.className = 'url-input-group';
                                div.style.cssText = 'display: flex; gap: 10px; margin-bottom: 10px;';
                                div.innerHTML = `
                                    <input type="url" name="image_urls[]" class="form-control" placeholder="https://example.com/image.jpg" onchange="updatePrimarySelects()">
                                    <button type="button" class="btn-secondary" onclick="removeUrlInput(this)" style="padding: 10px 15px;">-</button>
                                `;
                                container.appendChild(div);
                            }
                            
                            function removeUrlInput(btn) {
                                const container = document.getElementById('urlContainer');
                                if (container.children.length > 1) {
                                    btn.parentElement.remove();
                                    updatePrimarySelects();
                                }
                            }
                            
                            function updatePrimarySelects() {
                                const fileInput = document.querySelector('input[name="image_files[]"]');
                                const urlInputs = document.querySelectorAll('input[name="image_urls[]"]');
                                const primaryImageSelect = document.getElementById('primaryImageSelect');
                                const primaryUrlSelect = document.getElementById('primaryUrlSelect');
                                const helpText = document.getElementById('primaryImageHelp');
                                
                                // Dosya sayısını kontrol et
                                const fileCount = fileInput.files.length;
                                
                                // URL sayısını kontrol et
                                let urlCount = 0;
                                urlInputs.forEach(input => {
                                    if (input.value.trim() !== '') urlCount++;
                                });
                                
                                if (fileCount > 0 || urlCount > 0) {
                                    primaryImageSelect.innerHTML = '<option value="">Seçiniz</option>';
                                    for (let i = 0; i < fileCount; i++) {
                                        const option = document.createElement('option');
                                        option.value = i;
                                        option.textContent = 'Dosya ' + (i + 1) + ': ' + fileInput.files[i].name;
                                        primaryImageSelect.appendChild(option);
                                    }
                                    
                                    primaryUrlSelect.innerHTML = '<option value="">Seçiniz</option>';
                                    urlInputs.forEach((input, index) => {
                                        if (input.value.trim() !== '') {
                                            const option = document.createElement('option');
                                            option.value = input.value;
                                            option.textContent = 'URL ' + (index + 1) + ': ' + input.value;
                                            primaryUrlSelect.appendChild(option);
                                        }
                                    });
                                    
                                    primaryImageSelect.style.display = fileCount > 0 ? 'block' : 'none';
                                    primaryUrlSelect.style.display = urlCount > 0 ? 'block' : 'none';
                                    helpText.style.display = 'none';
                                } else {
                                    primaryImageSelect.style.display = 'none';
                                    primaryUrlSelect.style.display = 'none';
                                    helpText.style.display = 'block';
                                }
                            }
                            
                            document.querySelector('input[name="image_files[]"]').addEventListener('change', updatePrimarySelects);
                        </script>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

