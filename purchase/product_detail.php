<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once __DIR__ . '/../QRpanel/config/security.php';

$conn = getDBConnection();
$product = null;
$product_images = [];

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // Ürün bilgilerini getir
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    
    if ($product) {
        // Ürün görüntülenme sayısını artır
        $click_stmt = $conn->prepare("UPDATE products SET click_count = click_count + 1 WHERE id = ?");
        $click_stmt->bind_param("i", $id);
        $click_stmt->execute();
        $click_stmt->close();
        
        // Ürün görsellerini getir
        $images_stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, image_order ASC");
        $images_stmt->bind_param("i", $id);
        $images_stmt->execute();
        $images_result = $images_stmt->get_result();
        while ($img = $images_result->fetch_assoc()) {
            $product_images[] = $img;
        }
        $images_stmt->close();
        
        // Eğer görsel yoksa, eski image alanını kullan
        if (empty($product_images) && !empty($product['image'])) {
            $product_images[] = [
                'image_path' => $product['image'],
                'is_primary' => 1
            ];
        }
    }
}

if (!$product) {
    header('Location: index.php?error=Ürün bulunamadı');
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - CROMTEST Mağaza</title>
    <link rel="icon" type="image/png" href="../QRpanel/assets/images/logo.png">
    <link rel="stylesheet" href="../assets/css/landing.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .product-detail-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .product-detail {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .product-images {
            position: relative;
        }
        
        .main-image {
            width: 100%;
            max-height: 450px;
            min-height: 250px;
            object-fit: contain;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            margin-bottom: 15px;
            display: block;
        }
        
        .thumbnail-images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 10px;
        }
        
        .thumbnail {
            width: 100%;
            height: 80px;
            object-fit: contain;
            border-radius: 8px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.05);
        }
        
        .thumbnail:hover,
        .thumbnail.active {
            border-color: var(--primary);
            transform: scale(1.05);
        }
        
        .product-info h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .product-price {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-light);
            margin-bottom: 20px;
        }
        
        .product-description {
            color: var(--muted);
            line-height: 1.8;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }
        
        .product-meta {
            margin-bottom: 30px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            color: var(--muted);
        }
        
        .purchase-section {
            margin-top: 30px;
        }
        
        .btn-purchase {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            text-align: center;
            box-shadow: 0 4px 15px -3px rgba(99, 102, 241, 0.4);
        }
        
        .btn-purchase:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px -5px rgba(99, 102, 241, 0.5);
        }
        
        .btn-back {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--primary-light);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .btn-back:hover {
            color: var(--text);
        }
        
        @media (max-width: 768px) {
            .product-detail {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="store-main">
        <div class="product-detail-container">
            <a href="index.php" class="btn-back">← Ürünlere Dön</a>
            
            <div class="product-detail">
                <div class="product-images">
                    <?php if (!empty($product_images)): ?>
                        <?php 
                        // Görsel path'ini mutlak URL'ye çevirme fonksiyonu
                        function getImagePath($image_path) {
                            if (empty($image_path)) return '';
                            
                            // URL ise direkt kullan
                            if (strpos($image_path, 'http://') === 0 || strpos($image_path, 'https://') === 0) {
                                return $image_path;
                            }
                            
                            // Mutlak URL oluştur
                            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
                            $host = $_SERVER['HTTP_HOST'];
                            $script = $_SERVER['SCRIPT_NAME'];
                            
                            // Script path'den base path'i çıkar (purchase klasörü için)
                            $basePath = dirname($script); // purchase/product_detail.php -> purchase/
                            $basePath = rtrim($basePath, '/\\') . '/';
                            
                            // Base URL oluştur
                            $baseUrl = $protocol . "://" . $host . $basePath;
                            
                            // uploads/ ile başlıyorsa base URL'e ekle
                            if (strpos($image_path, 'uploads/') === 0 || strpos($image_path, '/uploads/') === 0) {
                                return $baseUrl . ltrim($image_path, '/');
                            }
                            
                            return $image_path;
                        }
                        
                        // İlk görseli path'ini düzelt
                        $first_image = getImagePath($product_images[0]['image_path']);
                        ?>
                        <img id="mainImage" src="<?php echo htmlspecialchars($first_image); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" class="main-image">
                        
                        <?php if (count($product_images) > 1): ?>
                            <div class="thumbnail-images">
                                <?php foreach ($product_images as $index => $img): ?>
                                    <?php 
                                    // Görsel path'ini düzelt
                                    $img_path = getImagePath($img['image_path']);
                                    ?>
                                    <img src="<?php echo htmlspecialchars($img_path); ?>" 
                                         alt="Görsel <?php echo $index + 1; ?>" 
                                         class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                         onclick="changeMainImage('<?php echo htmlspecialchars($img_path); ?>', this)">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="main-image" style="display: flex; align-items: center; justify-content: center; color: var(--muted);">
                            Görsel Yok
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="product-info">
                    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                    <div class="product-price"><?php echo number_format($product['price'], 2); ?> ₺</div>
                    
                    <div class="product-meta">
                        <?php if (!empty($product['category'])): ?>
                            <div class="meta-item">
                                <strong>Kategori:</strong> <?php echo htmlspecialchars($product['category']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-description">
                        <?php echo nl2br(htmlspecialchars($product['description'] ?? 'Açıklama bulunmamaktadır.')); ?>
                    </div>
                    
                    <div class="purchase-section">
                        <?php if (!empty($product['purchase_link'])): ?>
                            <a href="<?php echo htmlspecialchars($product['purchase_link']); ?>" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="btn-purchase"
                               onclick="trackPurchaseClick(<?php echo $product['id']; ?>, this)">
                                🛒 Satın Al
                            </a>
                        <?php else: ?>
                            <button class="btn-purchase" style="cursor: not-allowed; opacity: 0.6;" disabled>
                                Satın Alma Linki Henüz Eklenmemiş
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="../assets/js/landing.js"></script>
    <script>
        function changeMainImage(imagePath, thumbnail) {
            document.getElementById('mainImage').src = imagePath;
            
            // Tüm thumbnail'lerden active class'ını kaldır
            document.querySelectorAll('.thumbnail').forEach(thumb => {
                thumb.classList.remove('active');
            });
            
            // Tıklanan thumbnail'e active class'ını ekle
            thumbnail.classList.add('active');
        }
        
        // Satın alma linki tıklanmasını kaydet
        function trackPurchaseClick(productId, linkElement) {
            // AJAX ile tıklanma sayısını artır
            fetch('api/track_click.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId + '&click_type=purchase'
            }).catch(function(error) {
                console.log('Tıklanma sayısı kaydedilemedi:', error);
            });
            
            // Link normal şekilde çalışmaya devam eder
            return true;
        }
    </script>
</body>
</html>

