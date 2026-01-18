<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/security.php';
requireLogin();

$conn = getDBConnection();

// Başarı/hata mesajları
if (isset($_GET['deleted'])) {
    $success_message = "Test başarıyla silindi!";
}
if (isset($_GET['success'])) {
    // Toplu test oluşturma için mesaj kontrolü
    if (isset($_GET['count']) && intval($_GET['count']) > 1) {
        $success_message = htmlspecialchars($_GET['success']);
    } else {
        $qr_code = isset($_GET['qr']) ? htmlspecialchars($_GET['qr']) : '';
        $success_message = "Test başarıyla oluşturuldu!";
        if (!empty($qr_code)) {
            $success_message .= " QR Kod: <strong>$qr_code</strong>";
        }
    }
}
if (isset($_GET['updated'])) {
    $test_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $success_message = "Test başarıyla güncellendi!";
    if ($test_id > 0) {
        $updated_test = $conn->query("SELECT qr_code FROM test_results WHERE id = $test_id");
        if ($updated_test && $updated_test->num_rows > 0) {
            $updated_test_data = $updated_test->fetch_assoc();
            $qr_code = htmlspecialchars($updated_test_data['qr_code']);
        }
    }
}
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}

// Toplam test sayısı
$total_tests = $conn->query("SELECT COUNT(*) as total FROM test_results")->fetch_assoc()['total'];

// Bugünkü test sayısı
$today_tests = $conn->query("SELECT COUNT(*) as total FROM test_results WHERE DATE(test_date) = CURDATE()")->fetch_assoc()['total'];

// Tamamlanan test sayısı
$completed_tests = $conn->query("SELECT COUNT(*) as total FROM test_results WHERE is_filled = 1")->fetch_assoc()['total'];

// Bekleyen test sayısı
$pending_tests = $conn->query("SELECT COUNT(*) as total FROM test_results WHERE is_filled = 0")->fetch_assoc()['total'];

// Son testler
$recent_tests = $conn->query("SELECT * FROM test_results ORDER BY created_at DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli - CROMTEST</title>
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
                    <h1>CROMTEST Admin Paneli</h1>
                </div>
                <div class="user-info">
                    <span>Hoş geldiniz, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></span>
                    <a href="../../admin/panel_selector.php" class="btn-secondary">Panel Seçimi</a>
                    <a href="qr_system.php" class="btn-qr-system">📦 QR Sistemi</a>
                    <a href="new_test.php" class="btn-primary">Yeni Test Ekle</a>
                    <a href="../logout.php" class="btn-secondary">Çıkış Yap</a>
                </div>
            </div>
        </header>

        <main class="admin-main">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-info">
                        <h3>Toplam Test</h3>
                        <p class="stat-number"><?php echo $total_tests; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-info">
                        <h3>Bugünkü Testler</h3>
                        <p class="stat-number"><?php echo $today_tests; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <h3>Tamamlanan</h3>
                        <p class="stat-number"><?php echo $completed_tests; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <h3>Bekleyen</h3>
                        <p class="stat-number"><?php echo $pending_tests; ?></p>
                    </div>
                </div>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="success-alert" id="successAlert" style="margin-bottom: 20px;">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="error-alert" id="errorAlert" style="margin-bottom: 20px;">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="recent-tests">
                <div class="tests-header">
                    <h2>Test Listesi</h2>
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="🔍 Test ara (QR kod, tarih, durum...)" class="search-input">
                    </div>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>QR Kod</th>
                                <th>Test Tarihi</th>
                                <th>Durum</th>
                                <th>Sonuç Durumu</th>
                                <th>C Çizgisi</th>
                                <th>T Çizgisi</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_tests && $recent_tests->num_rows > 0): ?>
                                <?php while ($test = $recent_tests->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($test['qr_code']); ?></strong></td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($test['test_date'])); ?></td>
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
                                                <a href="delete_test.php?id=<?php echo $test['id']; ?>" 
                                                   class="btn-action btn-delete" 
                                                   title="Sil">
                                                    <span class="btn-icon">🗑️</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px;">
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
    
    <script>
        // URL'deki query parametrelerini temizle (sayfa yenilenince mesaj tekrar görünmesin)
        const successAlert = document.getElementById('successAlert');
        const errorAlert = document.getElementById('errorAlert');
        
        if (successAlert || errorAlert) {
            if (window.history.replaceState) {
                const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({path: cleanUrl}, '', cleanUrl);
            }
        }
        
        // Başarı mesajını otomatik kapat (5 saniye sonra)
        if (successAlert) {
            setTimeout(function() {
                successAlert.style.transition = 'opacity 0.5s ease';
                successAlert.style.opacity = '0';
                setTimeout(function() {
                    successAlert.style.display = 'none';
                }, 500);
            }, 5000); // 5000 milisaniye = 5 saniye
        }
        
        // Hata mesajını otomatik kapat (7 saniye sonra)
        if (errorAlert) {
            setTimeout(function() {
                errorAlert.style.transition = 'opacity 0.5s ease';
                errorAlert.style.opacity = '0';
                setTimeout(function() {
                    errorAlert.style.display = 'none';
                }, 500);
            }, 7000); // 7000 milisaniye = 7 saniye
        }

        // Arama fonksiyonu
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase().trim();
            const tableRows = document.querySelectorAll('.data-table tbody tr');
            
            tableRows.forEach(row => {
                // Eğer bu "test yok" satırıysa atla
                if (row.cells.length === 1) {
                    return;
                }
                
                // Tüm hücrelerin içeriğini al
                const rowText = Array.from(row.cells).map(cell => cell.textContent.toLowerCase()).join(' ');
                
                // Arama değeri satırda var mı kontrol et
                const matches = rowText.includes(searchValue);
                
                // Eşleşme varsa göster, yoksa gizle
                row.style.display = matches ? '' : 'none';
            });
            
            // Hiç sonuç yoksa bilgi mesajı göster
            const visibleRows = document.querySelectorAll('.data-table tbody tr:not([style*="display: none"])').length;
            const allRows = document.querySelectorAll('.data-table tbody tr');
            
            if (visibleRows === 0 && allRows.length > 0 && searchValue !== '') {
                if (!document.getElementById('noResultMessage')) {
                    const tbody = document.querySelector('.data-table tbody');
                    const noResultRow = document.createElement('tr');
                    noResultRow.id = 'noResultMessage';
                    noResultRow.innerHTML = '<td colspan="7" style="text-align: center; padding: 40px; color: #6b7280;">🔍 Aradığınız kriterlere uygun test bulunamadı.</td>';
                    tbody.appendChild(noResultRow);
                }
            } else {
                const noResultMsg = document.getElementById('noResultMessage');
                if (noResultMsg) {
                    noResultMsg.remove();
                }
            }
        });
        
        // Arama kutusuna odaklanmak için klavye kısayolu (Ctrl+K veya Cmd+K)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.getElementById('searchInput').focus();
            }
        });
    </script>
    
</body>
</html>
