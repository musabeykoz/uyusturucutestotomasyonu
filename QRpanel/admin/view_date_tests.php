<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/security.php';
requireLogin();

$date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : '';

// Tarih validasyonu
if (empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    header('Location: qr_system.php?error=' . urlencode('Geçersiz tarih formatı.'));
    exit;
}

$conn = getDBConnection();

// Bu tarihteki testleri al
$stmt = $conn->prepare("SELECT * FROM test_results WHERE DATE(test_date) = ? ORDER BY test_date ASC");
$stmt->bind_param('s', $date);
$stmt->execute();
$tests_result = $stmt->get_result();
$stmt->close();

// İstatistikler
$total_count = 0;
$filled_count = 0;
$pending_count = 0;

$tests = [];
while ($test = $tests_result->fetch_assoc()) {
    $tests[] = $test;
    $total_count++;
    if ($test['is_filled'] == 1) {
        $filled_count++;
    } else {
        $pending_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo date('d.m.Y', strtotime($date)); ?> Tarihli Testler - CROMTEST</title>
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
                    <h1>📅 <?php echo date('d.m.Y', strtotime($date)); ?> Tarihli Testler</h1>
                </div>
                <div class="user-info">
                    <a href="qr_system.php" class="btn-secondary">← QR Sistemi</a>
                    <a href="dashboard.php" class="btn-secondary">Ana Sayfa</a>
                </div>
            </div>
        </header>

        <main class="admin-main">
            <!-- İstatistikler -->
            <div class="stats-grid" style="margin-bottom: 30px;">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-info">
                        <h3>Toplam Test</h3>
                        <p class="stat-number"><?php echo $total_count; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <h3>Tamamlanan</h3>
                        <p class="stat-number"><?php echo $filled_count; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <h3>Bekleyen</h3>
                        <p class="stat-number"><?php echo $pending_count; ?></p>
                    </div>
                </div>
                
                <div class="stat-card" style="background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%);">
                    <div class="stat-icon">📥</div>
                    <div class="stat-info">
                        <a href="download_qr_batch.php?date=<?php echo urlencode($date); ?>&csrf_token=<?php echo urlencode(generateCSRFToken()); ?>" 
                           style="text-decoration: none; color: inherit;">
                            <h3 style="color: #581c87;">Toplu İndir</h3>
                            <p class="stat-number" style="color: #7c3aed;"><?php echo $total_count; ?> QR</p>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Test Listesi -->
            <div class="recent-tests">
                <div class="tests-header">
                    <h2>Test Detayları</h2>
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="🔍 Test ara (QR kod, durum...)" class="search-input">
                    </div>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>QR Kod</th>
                                <th>Test Saati</th>
                                <th>Durum</th>
                                <th>Sonuç Durumu</th>
                                <th>C Çizgisi</th>
                                <th>T Çizgisi</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($tests) > 0): ?>
                                <?php foreach ($tests as $test): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($test['qr_code']); ?></strong></td>
                                        <td><?php echo date('H:i:s', strtotime($test['test_date'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $test['test_status'])); ?>">
                                                <?php echo htmlspecialchars($test['test_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($test['is_filled'] == 1): ?>
                                                <span class="filled-badge">✓ Dolduruldu</span>
                                            <?php else: ?>
                                                <span class="not-filled-badge">⏳ Bekliyor</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($test['control_result'] === null): ?>
                                                <span style="color: #94a3b8;">-</span>
                                            <?php elseif ($test['control_result'] == 1): ?>
                                                <span class="result-positive">✓ Pozitif</span>
                                            <?php else: ?>
                                                <span class="result-negative">✗ Negatif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($test['test_result'] === null): ?>
                                                <span style="color: #94a3b8;">-</span>
                                            <?php elseif ($test['test_result'] == 1): ?>
                                                <span class="result-positive">✓ Pozitif</span>
                                            <?php else: ?>
                                                <span class="result-negative">✗ Negatif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="../result.php?qr=<?php echo urlencode($test['qr_code']); ?>" 
                                                   class="btn-action btn-view" target="_blank" title="Test Sayfası">
                                                    <span class="btn-icon">👁️</span>
                                                </a>
                                                <a href="edit_test.php?id=<?php echo $test['id']; ?>" 
                                                   class="btn-action btn-edit" title="Düzenle">
                                                    <span class="btn-icon">✏️</span>
                                                </a>
                                                <?php if (!empty($test['qr_code_image'])): ?>
                                                    <a href="../<?php echo htmlspecialchars($test['qr_code_image']); ?>" 
                                                       class="btn-action btn-qr" target="_blank" download title="QR İndir">
                                                        <span class="btn-icon">📱</span>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px;">
                                        Bu tarihte hiç test bulunamadı.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Arama fonksiyonu
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase().trim();
            const tableRows = document.querySelectorAll('.data-table tbody tr');
            
            tableRows.forEach(row => {
                if (row.cells.length === 1) {
                    return;
                }
                
                const rowText = Array.from(row.cells).map(cell => cell.textContent.toLowerCase()).join(' ');
                const matches = rowText.includes(searchValue);
                row.style.display = matches ? '' : 'none';
            });
        });
    </script>
    
    <style>
        .filled-badge {
            display: inline-block;
            padding: 6px 12px;
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #166534;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .not-filled-badge {
            display: inline-block;
            padding: 6px 12px;
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .result-positive {
            color: #dc2626;
            font-weight: 600;
        }
        
        .result-negative {
            color: #16a34a;
            font-weight: 600;
        }
        
        .status-beklemede {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
        }
        
        .status-tamamlandı {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #166534;
        }
        
        .status-geçersiz {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
        }
    </style>
</body>
</html>

