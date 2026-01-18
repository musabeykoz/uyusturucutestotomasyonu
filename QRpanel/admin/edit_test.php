<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/security.php';
requireLogin();

$test_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';
$success = '';

if ($test_id <= 0) {
    header('Location: dashboard.php?error=' . urlencode('Geçersiz test ID'));
    exit;
}

$conn = getDBConnection();

// Test bilgilerini getir (Prepared statement)
$test = getTestResultById($test_id);

if (!$test) {
    header('Location: dashboard.php?error=' . urlencode('Test bulunamadı'));
    exit;
}

// Form gönderimi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token kontrolü
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    
    if (!verifyCSRFToken($csrf_token)) {
        $error = 'Geçersiz istek. Sayfayı yenileyip tekrar deneyin.';
        logSecurity("CSRF token doğrulama hatası - Edit Test", [
            'user_id' => $_SESSION['user_id'],
            'test_id' => $test_id,
            'ip' => getClientIP()
        ]);
    } else {
        // Input'ları temizle
        $test_date = isset($_POST['test_date']) ? sanitizeInput($_POST['test_date']) : $test['test_date'];
        $control_result = isset($_POST['control_result']) && $_POST['control_result'] !== '' ? (int)$_POST['control_result'] : null;
        $test_result_val = isset($_POST['test_result']) && $_POST['test_result'] !== '' ? (int)$_POST['test_result'] : null;
        $notes = isset($_POST['notes']) ? sanitizeInput($_POST['notes']) : '';
        
        // Tarih formatı validasyonu
        $date_obj = DateTime::createFromFormat('Y-m-d\TH:i', $test_date);
        if (!$date_obj) {
            $error = "Geçersiz tarih formatı.";
        } else {
            $test_date = $date_obj->format('Y-m-d H:i:s');
            
            // Test durumunu güncelle
            $test_status = 'Beklemede';
            $is_filled = 0;
            $filled_at = null;
            
            if ($control_result !== null && $test_result_val !== null) {
                $is_filled = 1;
                $filled_at = date('Y-m-d H:i:s');
                
                // Kontrol çizgisi yoksa test geçersiz
                if ($control_result == 0) {
                    $test_status = 'Geçersiz';
                } else {
                    $test_status = 'Tamamlandı';
                }
            }
            
            // Veritabanını güncelle (Prepared statement)
            if ($is_filled && $filled_at) {
                $update_stmt = $conn->prepare(
                    "UPDATE test_results SET 
                        test_date = ?,
                        control_result = ?,
                        test_result = ?,
                        is_filled = ?,
                        filled_at = ?,
                        test_status = ?,
                        notes = ?,
                        updated_at = NOW()
                    WHERE id = ?"
                );
                
                $update_stmt->bind_param('siiiissi', $test_date, $control_result, $test_result_val, $is_filled, $filled_at, $test_status, $notes, $test_id);
            } else {
                $update_stmt = $conn->prepare(
                    "UPDATE test_results SET 
                        test_date = ?,
                        control_result = NULL,
                        test_result = NULL,
                        is_filled = 0,
                        filled_at = NULL,
                        test_status = ?,
                        notes = ?,
                        updated_at = NOW()
                    WHERE id = ?"
                );
                
                $update_stmt->bind_param('sssi', $test_date, $test_status, $notes, $test_id);
            }
            
            if ($update_stmt->execute()) {
                logTestAction('UPDATE', $test_id, $test['qr_code'], [
                    'updated_by' => $_SESSION['user_id']
                ]);
                
                $update_stmt->close();
                header('Location: dashboard.php?updated=1&id=' . $test_id);
                exit;
            } else {
                logError("Test güncelleme hatası", [
                    'test_id' => $test_id,
                    'error' => $update_stmt->error
                ]);
                $error = "Güncelleme sırasında bir hata oluştu.";
                $update_stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Düzenle - CROMTEST</title>
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
                    <h1>Test Düzenle</h1>
                </div>
                <div class="user-info">
                    <a href="dashboard.php" class="btn-secondary">← Geri Dön</a>
                    <a href="../logout.php" class="btn-secondary">Çıkış Yap</a>
                </div>
            </div>
        </header>

        <main class="admin-main">
            
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

            <div class="test-info-card" style="background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%); padding: 25px; border-radius: 12px; margin-bottom: 25px; border-left: 4px solid #0ea5e9;">
                <h3 style="color: #0c4a6e; margin-bottom: 15px;">Test Bilgileri</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div>
                        <strong style="color: #0c4a6e;">QR Kod:</strong><br>
                        <span style="color: #0c4a6e; font-size: 1.1rem;"><?php echo htmlspecialchars($test['qr_code']); ?></span>
                    </div>
                    <div>
                        <strong style="color: #0c4a6e;">Oluşturulma:</strong><br>
                        <span style="color: #0c4a6e;"><?php echo date('d.m.Y H:i', strtotime($test['created_at'])); ?></span>
                    </div>
                    <div>
                        <strong style="color: #0c4a6e;">Durum:</strong><br>
                        <span class="status-badge" style="display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 0.9rem; 
                              background: <?php echo $test['test_status'] == 'Tamamlandı' ? '#dcfce7' : ($test['test_status'] == 'Geçersiz' ? '#fee2e2' : '#fef3c7'); ?>; 
                              color: <?php echo $test['test_status'] == 'Tamamlandı' ? '#166534' : ($test['test_status'] == 'Geçersiz' ? '#991b1b' : '#92400e'); ?>;">
                            <?php echo htmlspecialchars($test['test_status']); ?>
                        </span>
                    </div>
                    <?php if ($test['is_filled'] == 1 && $test['filled_at']): ?>
                    <div>
                        <strong style="color: #0c4a6e;">Sonuç Girildi:</strong><br>
                        <span style="color: #0c4a6e;"><?php echo date('d.m.Y H:i', strtotime($test['filled_at'])); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($test['qr_code_image'])): ?>
                <div style="margin-top: 20px; text-align: center;">
                    <?php
                    require_once '../config/qr_generator.php';
                    echo displayQRCode($test['qr_code_image']);
                    ?>
                    <br>
                    <a href="../<?php echo htmlspecialchars($test['qr_code_image']); ?>" 
                       download="qr_<?php echo htmlspecialchars($test['qr_code']); ?>.png" 
                       class="btn-primary" style="display: inline-block; margin-top: 10px; padding: 8px 16px; font-size: 0.9rem;">
                        QR Kod İndir
                    </a>
                    <a href="../result.php?qr=<?php echo urlencode($test['qr_code']); ?>" 
                       class="btn-secondary" target="_blank" style="display: inline-block; margin-top: 10px; padding: 8px 16px; font-size: 0.9rem; margin-left: 10px;">
                        Test Sayfası
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <form method="POST" action="edit_test.php?id=<?php echo $test_id; ?>" class="test-form">
                <?php echo csrfTokenField(); ?>
                
                <div class="form-section">
                    <h2>Test Tarihi</h2>
                    <div class="form-group">
                        <label for="test_date">Test Tarihi ve Saati</label>
                        <input type="datetime-local" id="test_date" name="test_date" 
                               value="<?php echo date('Y-m-d\TH:i', strtotime($test['test_date'])); ?>" required
                               style="width: 100%; padding: 12px; border: 2px solid rgba(99, 102, 241, 0.2); border-radius: 8px; font-size: 1rem;">
                    </div>
                </div>

                <div class="form-section">
                    <h2>Test Sonuçları</h2>
                    <p style="color: #64748b; margin-bottom: 20px;">
                        <strong>Not:</strong> Normalde test sonuçları kullanıcılar tarafından girilir. Ancak gerekirse buradan manuel olarak düzenleyebilirsiniz.
                    </p>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <!-- C - Control Çizgisi -->
                        <div class="result-edit-group">
                            <h3 style="color: #1e293b; margin-bottom: 15px;">C - Control (Kontrol Çizgisi)</h3>
                            <div class="radio-group-vertical">
                                <label class="radio-label-block">
                                    <input type="radio" name="control_result" value="" <?php echo $test['control_result'] === null ? 'checked' : ''; ?>>
                                    <span class="radio-custom-block">Girilmedi</span>
                                </label>
                                <label class="radio-label-block">
                                    <input type="radio" name="control_result" value="1" <?php echo $test['control_result'] == 1 ? 'checked' : ''; ?>>
                                    <span class="radio-custom-block positive">✓ Pozitif (Çizgi Var)</span>
                                </label>
                                <label class="radio-label-block">
                                    <input type="radio" name="control_result" value="0" <?php echo $test['control_result'] == 0 ? 'checked' : ''; ?>>
                                    <span class="radio-custom-block negative">✗ Negatif (Çizgi Yok)</span>
                                </label>
                            </div>
                        </div>

                        <!-- T - Test Çizgisi -->
                        <div class="result-edit-group">
                            <h3 style="color: #1e293b; margin-bottom: 15px;">T - Test (Test Çizgisi)</h3>
                            <div class="radio-group-vertical">
                                <label class="radio-label-block">
                                    <input type="radio" name="test_result" value="" <?php echo $test['test_result'] === null ? 'checked' : ''; ?>>
                                    <span class="radio-custom-block">Girilmedi</span>
                                </label>
                                <label class="radio-label-block">
                                    <input type="radio" name="test_result" value="1" <?php echo $test['test_result'] == 1 ? 'checked' : ''; ?>>
                                    <span class="radio-custom-block positive">✓ Pozitif (Çizgi Var)</span>
                                </label>
                                <label class="radio-label-block">
                                    <input type="radio" name="test_result" value="0" <?php echo $test['test_result'] == 0 ? 'checked' : ''; ?>>
                                    <span class="radio-custom-block negative">✗ Negatif (Çizgi Yok)</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2>Notlar</h2>
                    <div class="form-group">
                        <label for="notes">Test hakkında ek bilgiler</label>
                        <textarea name="notes" id="notes" rows="4" 
                                  style="width: 100%; padding: 12px; border: 2px solid rgba(99, 102, 241, 0.2); border-radius: 8px; font-family: inherit; font-size: 1rem;"><?php echo htmlspecialchars($test['notes']); ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary btn-large">
                        Değişiklikleri Kaydet
                    </button>
                    <a href="dashboard.php" class="btn-secondary">İptal</a>
                </div>
            </form>
        </main>
    </div>
    
    <style>
        .form-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .form-section h2 {
            color: #1e293b;
            margin-bottom: 20px;
            font-size: 1.4rem;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
            font-size: 1rem;
        }
        
        .radio-group-vertical {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .radio-label-block {
            display: block;
            cursor: pointer;
        }
        
        .radio-label-block input[type="radio"] {
            display: none;
        }
        
        .radio-custom-block {
            display: block;
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .radio-label-block input[type="radio"]:checked + .radio-custom-block {
            border-color: #6366f1;
            background: linear-gradient(135deg, #f0f4ff 0%, #e8edff 100%);
        }
        
        .radio-custom-block.positive {
            color: #dc2626;
        }
        
        .radio-custom-block.negative {
            color: #16a34a;
        }
        
        .radio-label-block:hover .radio-custom-block {
            border-color: #6366f1;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
        }
        
        .btn-large {
            padding: 18px 40px;
            font-size: 1.1rem;
        }
    </style>
</body>
</html>
