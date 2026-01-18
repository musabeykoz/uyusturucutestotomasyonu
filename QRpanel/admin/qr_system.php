<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/security.php';
requireLogin();

$conn = getDBConnection();

// Toplu oluşturulma zamanına göre testleri grupla (aynı dakika içinde oluşturulanlar)
// created_at'e göre gruplama yapıyoruz
$query = "SELECT 
            DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as batch_time,
            MIN(created_at) as exact_time,
            COUNT(*) as test_count,
            SUM(CASE WHEN is_filled = 1 THEN 1 ELSE 0 END) as filled_count,
            MIN(test_date) as test_date_display,
            GROUP_CONCAT(id ORDER BY id ASC) as test_ids
          FROM test_results 
          GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:%i')
          HAVING test_count >= 1
          ORDER BY exact_time DESC";

$grouped_tests = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Sistemi - CROMTEST</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link rel="apple-touch-icon" href="../assets/images/logo.png">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <div class="header-content">
                <div class="admin-logo-title">
                    <img src="../assets/images/logo.png" alt="CROMTEST Logo" class="admin-logo">
                    <h1>📦 QR Kod Toplu İndirme Sistemi</h1>
                </div>
                <div class="user-info">
                    <a href="dashboard.php" class="btn-secondary">← Geri Dön</a>
                    <a href="../logout.php" class="btn-secondary">Çıkış Yap</a>
                </div>
            </div>
        </header>

        <main class="admin-main">
            <div class="info-box" style="margin-bottom: 30px; padding: 20px; background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%); border-radius: 12px; border-left: 4px solid #8b5cf6;">
                <h3 style="margin-bottom: 10px; color: #581c87;">ℹ️ QR Sistemi Hakkında</h3>
                <p style="color: #581c87; margin-bottom: 10px;">
                    Bu sistem, <strong>toplu olarak oluşturulan</strong> testlerin QR kodlarını gruplar halinde indirmenizi sağlar.
                </p>
                <p style="color: #581c87;">
                    <strong>Kullanım:</strong> Aynı anda oluşturulan test gruplarını görebilir ve o grubun tüm QR kodlarını ZIP olarak indirebilirsiniz.
                </p>
            </div>

            <div class="recent-tests">
                <div class="tests-header">
                    <h2>📦 Toplu Oluşturulan Test Grupları</h2>
                    <p style="color: #64748b; font-size: 0.9rem; margin-top: 8px;">
                        Aynı dakika içinde birlikte oluşturulan testler gruplandırılmıştır
                    </p>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>🕐 Oluşturulma Zamanı</th>
                                <th>📊 Test Sayısı</th>
                                <th>✅ Tamamlanan</th>
                                <th>⏳ Bekleyen</th>
                                <th>📅 Test Tarihi</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($grouped_tests && $grouped_tests->num_rows > 0): ?>
                                <?php while ($group = $grouped_tests->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo date('d.m.Y H:i', strtotime($group['exact_time'])); ?></strong>
                                            <br>
                                            <small style="color: #64748b;">
                                                <?php echo date('H:i:s', strtotime($group['exact_time'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge-count"><?php echo $group['test_count']; ?> adet</span>
                                        </td>
                                        <td>
                                            <span class="badge-filled"><?php echo $group['filled_count']; ?> adet</span>
                                        </td>
                                        <td>
                                            <span class="badge-pending"><?php echo ($group['test_count'] - $group['filled_count']); ?> adet</span>
                                        </td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($group['test_date_display'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="download_qr_batch.php?batch_time=<?php echo urlencode($group['batch_time']); ?>&csrf_token=<?php echo urlencode(generateCSRFToken()); ?>" 
                                                   class="btn-download-qr"
                                                   title="Bu toplu oluşturma grubundaki tüm QR kodlarını indir">
                                                    📥 QR Kodları İndir (<?php echo $group['test_count']; ?> adet)
                                                </a>
                                                <a href="view_batch_tests.php?batch_time=<?php echo urlencode($group['batch_time']); ?>" 
                                                   class="btn-view-tests"
                                                   title="Bu gruptaki testleri görüntüle">
                                                    👁️ Testleri Görüntüle
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px;">
                                        Henüz test sonucu bulunmamaktadır.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <style>
        .badge-count {
            display: inline-block;
            padding: 8px 16px;
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border-radius: 6px;
            font-weight: 700;
            font-size: 1rem;
        }
        
        .badge-filled {
            display: inline-block;
            padding: 8px 16px;
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #166534;
            border-radius: 6px;
            font-weight: 700;
            font-size: 1rem;
        }
        
        .badge-pending {
            display: inline-block;
            padding: 8px 16px;
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border-radius: 6px;
            font-weight: 700;
            font-size: 1rem;
        }
        
        .btn-download-qr {
            display: inline-block;
            padding: 12px 20px;
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(139, 92, 246, 0.3);
            margin-right: 10px;
        }
        
        .btn-download-qr:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(139, 92, 246, 0.4);
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
        }
        
        .btn-view-tests {
            display: inline-block;
            padding: 12px 20px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
        }
        
        .btn-view-tests:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.4);
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
        }
    </style>
</body>
</html>

