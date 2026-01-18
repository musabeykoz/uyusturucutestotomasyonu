<?php
require_once '../config/auth.php';
require_once '../config/database.php';
requireLogin();

$conn = getDBConnection();

// İstatistikler
$total_products = $conn->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];
$active_products = $conn->query("SELECT COUNT(*) as total FROM products WHERE is_active = 1")->fetch_assoc()['total'];
$products_with_link = $conn->query("SELECT COUNT(*) as total FROM products WHERE purchase_link IS NOT NULL AND purchase_link != ''")->fetch_assoc()['total'] ?? 0;
$total_views = $conn->query("SELECT SUM(click_count) as total FROM products")->fetch_assoc()['total'] ?? 0;
$total_purchase_clicks = $conn->query("SELECT SUM(purchase_click_count) as total FROM products")->fetch_assoc()['total'] ?? 0;

// Son ürünler
$recent_products = $conn->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mağaza Yönetim Paneli - CROMTEST</title>
    <link rel="icon" type="image/png" href="../../QRpanel/assets/images/logo.png">
    <link rel="stylesheet" href="../../QRpanel/assets/css/style.css">
    <link rel="stylesheet" href="../../QRpanel/assets/css/admin.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .stat-icon {
            font-size: 3rem;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
        }
        
        .stat-info h3 {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-light);
        }
        
        .status-badge.status-active {
            background: linear-gradient(135deg, rgba(220, 252, 231, 0.2) 0%, rgba(187, 247, 208, 0.25) 100%);
            color: #bbf7d0;
            border: 1px solid rgba(16, 185, 129, 0.35);
        }
        
        .status-badge.status-inactive {
            background: linear-gradient(135deg, rgba(254, 226, 226, 0.2) 0%, rgba(254, 202, 202, 0.25) 100%);
            color: #fecdd3;
            border: 1px solid rgba(239, 68, 68, 0.35);
        }
        
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .data-table {
            min-width: 800px;
        }
        
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .data-table {
                font-size: 0.85rem;
            }
            
            .data-table th,
            .data-table td {
                padding: 10px 8px;
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
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
            
            .data-table {
                font-size: 0.8rem;
                min-width: 700px;
            }
            
            .data-table th,
            .data-table td {
                padding: 8px 6px;
            }
            
            .stat-card {
                padding: 20px;
            }
            
            .stat-icon {
                font-size: 2.5rem;
            }
            
            .stat-number {
                font-size: 1.5rem;
            }
            
            .action-buttons {
                flex-wrap: wrap;
                gap: 5px;
            }
            
            .btn-action {
                padding: 6px 8px;
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 480px) {
            .data-table {
                font-size: 0.75rem;
                min-width: 600px;
            }
            
            .data-table th,
            .data-table td {
                padding: 6px 4px;
            }
            
            .admin-header {
                padding: 15px 0;
            }
            
            .admin-logo-title {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .stat-card {
                flex-direction: column;
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
                    <h1>Mağaza Yönetim Paneli</h1>
                </div>
                <div class="user-info">
                    <span>Hoş geldiniz, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></span>
                    <a href="../../admin/panel_selector.php" class="btn-secondary">Panel Seçimi</a>
                    <a href="../../QRpanel/logout.php" class="btn-secondary">Çıkış Yap</a>
                </div>
            </div>
        </header>

        <main class="admin-main">
            <div class="header-content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📦</div>
                        <div class="stat-info">
                            <h3>Toplam Ürün</h3>
                            <p class="stat-number"><?php echo $total_products; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-info">
                            <h3>Aktif Ürün</h3>
                            <p class="stat-number"><?php echo $active_products; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">👁️</div>
                        <div class="stat-info">
                            <h3>Toplam Görüntülenme</h3>
                            <p class="stat-number"><?php echo number_format($total_views); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🛒</div>
                        <div class="stat-info">
                            <h3>Satın Alma Tıklaması</h3>
                            <p class="stat-number"><?php echo number_format($total_purchase_clicks); ?></p>
                        </div>
                    </div>
                </div>

                <div class="recent-tests">
                    <div class="tests-header">
                        <h2>Ürün Yönetimi</h2>
                        <a href="add_product.php" class="btn-primary">Yeni Ürün Ekle</a>
                    </div>
                    
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Ürün Adı</th>
                                    <th>Kategori</th>
                                    <th>Fiyat</th>
                                    <th>Görüntülenme</th>
                                    <th>Satın Al Tıklaması</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_products && $recent_products->num_rows > 0): ?>
                                    <?php while ($product = $recent_products->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $product['id']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($product['category'] ?? '-'); ?></td>
                                            <td><?php echo number_format($product['price'], 2); ?> ₺</td>
                                            <td><?php echo number_format($product['click_count'] ?? 0); ?></td>
                                            <td><?php echo number_format($product['purchase_click_count'] ?? 0); ?></td>
                                            <td>
                                                <?php if ($product['is_active'] == 1): ?>
                                                    <span class="status-badge status-active">Aktif</span>
                                                <?php else: ?>
                                                    <span class="status-badge status-inactive">Pasif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" 
                                                       class="btn-action btn-edit" title="Düzenle">
                                                        <span class="btn-icon">✏️</span>
                                                    </a>
                                                    <a href="delete_product.php?id=<?php echo $product['id']; ?>" 
                                                       class="btn-action btn-delete" 
                                                       title="Sil"
                                                       onclick="return confirm('Bu ürünü silmek istediğinize emin misiniz?');">
                                                        <span class="btn-icon">🗑️</span>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 40px;">
                                            Henüz ürün bulunmamaktadır.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

