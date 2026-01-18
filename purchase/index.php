<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once __DIR__ . '/../QRpanel/config/security.php';

$conn = getDBConnection();

// Kategori filtresi
$category_filter = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';

// Ürünleri getir (Prepared Statement ile güvenli)
if (!empty($category_filter)) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE is_active = 1 AND category = ? ORDER BY created_at DESC");
    $stmt->bind_param("s", $category_filter);
    $stmt->execute();
    $products_result = $stmt->get_result();
} else {
    $products_result = $conn->query("SELECT * FROM products WHERE is_active = 1 ORDER BY created_at DESC");
}

// Kategori listesi
$categories_query = "SELECT DISTINCT category FROM products WHERE is_active = 1 AND category IS NOT NULL";
$categories_result = $conn->query($categories_query);
$categories = [];
if ($categories_result) {
    while ($cat = $categories_result->fetch_assoc()) {
        $categories[] = $cat['category'];
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mağaza - CROMTEST</title>
    <link rel="icon" type="image/png" href="../QRpanel/assets/images/logo.png">
    <link rel="stylesheet" href="../assets/css/landing.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="store-main">
        <div class="container">
            <div class="store-filters">
                <h2>Kategoriler</h2>
                <div class="category-filters">
                    <a href="index.php" class="category-btn <?php echo empty($category_filter) ? 'active' : ''; ?>">Tümü</a>
                    <?php foreach ($categories as $category): ?>
                        <a href="index.php?category=<?php echo urlencode($category); ?>" class="category-btn <?php echo $category_filter === $category ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($category); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="products-grid">
                <?php if ($products_result && $products_result->num_rows > 0): ?>
                    <?php while ($product = $products_result->fetch_assoc()): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <?php
                                // Ürünün ana görselini getir
                                $img_stmt = $conn->prepare("SELECT image_path FROM product_images WHERE product_id = ? AND is_primary = 1 ORDER BY image_order ASC LIMIT 1");
                                $img_stmt->bind_param("i", $product['id']);
                                $img_stmt->execute();
                                $img_result = $img_stmt->get_result();
                                $primary_image = $img_result->fetch_assoc();
                                $img_stmt->close();
                                
                                // Ana görsel yoksa, ilk görseli al
                                if (!$primary_image) {
                                    $img_stmt2 = $conn->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY image_order ASC LIMIT 1");
                                    $img_stmt2->bind_param("i", $product['id']);
                                    $img_stmt2->execute();
                                    $img_result2 = $img_stmt2->get_result();
                                    $primary_image = $img_result2->fetch_assoc();
                                    $img_stmt2->close();
                                }
                                
                                // Hala görsel yoksa, eski image alanını kullan
                                $display_image = $primary_image['image_path'] ?? $product['image'] ?? null;
                                
                                // Görsel path'ini mutlak URL'ye çevir
                                if ($display_image) {
                                    if (strpos($display_image, 'http://') === 0 || strpos($display_image, 'https://') === 0) {
                                        // URL ise direkt kullan
                                        $display_image = $display_image;
                                    } elseif (strpos($display_image, 'uploads/') === 0 || strpos($display_image, '/uploads/') === 0) {
                                        // Mutlak URL oluştur
                                        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
                                        $host = $_SERVER['HTTP_HOST'];
                                        $script = $_SERVER['SCRIPT_NAME'];
                                        
                                        // Script path'den base path'i çıkar (purchase klasörü için)
                                        $basePath = dirname($script); // purchase/index.php -> purchase/
                                        $basePath = rtrim($basePath, '/\\') . '/';
                                        
                                        // Base URL oluştur
                                        $baseUrl = $protocol . "://" . $host . $basePath;
                                        
                                        // Path'i temizle ve base URL'e ekle
                                        $display_image = $baseUrl . ltrim($display_image, '/');
                                    } else {
                                        // Diğer durumlarda direkt kullan
                                        $display_image = $display_image;
                                    }
                                }
                                ?>
                                <?php if ($display_image): ?>
                                    <a href="product_detail.php?id=<?php echo $product['id']; ?>">
                                        <img src="<?php echo htmlspecialchars($display_image); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    </a>
                                <?php else: ?>
                                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--muted);">
                                        Görsel Yok
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-description"><?php 
                                    $desc = $product['description'] ?? '';
                                    echo htmlspecialchars(strlen($desc) > 100 ? substr($desc, 0, 100) . '...' : $desc); 
                                ?></p>
                                <span class="product-price"><?php echo number_format($product['price'], 2); ?> ₺</span>
                                <div class="product-footer">
                                    <div style="display: flex; gap: 10px;">
                                        <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn-detail">
                                            Detay
                                        </a>
                                        <?php if (!empty($product['purchase_link'])): ?>
                                            <a href="<?php echo htmlspecialchars($product['purchase_link']); ?>" 
                                               target="_blank" 
                                               rel="noopener noreferrer"
                                               class="btn-add-cart"
                                               onclick="trackPurchaseClick(<?php echo $product['id']; ?>, this); return true;">
                                                Satın Al
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-products">
                        <p>Henüz ürün bulunmamaktadır.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="../assets/js/landing.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
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

