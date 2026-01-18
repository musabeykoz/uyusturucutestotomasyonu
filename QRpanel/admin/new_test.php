<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/security.php';
requireLogin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token kontrolü
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    
    if (!verifyCSRFToken($csrf_token)) {
        $error = 'Geçersiz istek. Sayfayı yenileyip tekrar deneyin.';
        logSecurity("CSRF token doğrulama hatası - New Test", [
            'user_id' => $_SESSION['user_id'],
            'ip' => getClientIP()
        ]);
    } else {
        $conn = getDBConnection();
        
        // Input'ları temizle
        $test_date = isset($_POST['test_date']) ? sanitizeInput($_POST['test_date']) : date('Y-m-d H:i:s');
        $notes = isset($_POST['notes']) ? sanitizeInput($_POST['notes']) : '';
        $test_count = isset($_POST['test_count']) ? intval($_POST['test_count']) : 1;
        
        // Test sayısı kontrolü (1-100 arası)
        if ($test_count < 1 || $test_count > 100) {
            $error = "Test sayısı 1 ile 100 arasında olmalıdır.";
        } else {
        // Tarih formatı validasyonu
        $date_obj = DateTime::createFromFormat('Y-m-d\TH:i', $test_date);
        if (!$date_obj) {
            $error = "Geçersiz tarih formatı.";
        } else {
            $test_date = $date_obj->format('Y-m-d H:i:s');
            
                $created_tests = [];
                $failed_count = 0;
                
                // Belirtilen sayı kadar test oluştur
                for ($i = 0; $i < $test_count; $i++) {
            // QR kod oluştur - benzersiz olana kadar dene
            $qr_code = '';
            $max_attempts = 10;
            $attempt = 0;
            
            do {
                $qr_code = 'CROMTEST-' . date('Y') . '-' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
                
                // Prepared statement ile kontrol
                $check_stmt = $conn->prepare("SELECT id FROM test_results WHERE qr_code = ?");
                $check_stmt->bind_param("s", $qr_code);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $attempt++;
                
                $exists = $check_result->num_rows > 0;
                $check_stmt->close();
                
                if (!$exists || $attempt >= $max_attempts) {
                    break;
                }
            } while ($attempt < $max_attempts);
            
            // Veritabanına kaydet - Prepared statement kullan
            $stmt = $conn->prepare(
                "INSERT INTO test_results (
                    qr_code, test_date, control_result, test_result, is_filled, test_status, notes, created_by
                ) VALUES (
                    ?, ?, NULL, NULL, 0, 'Beklemede', ?, ?
                )"
            );
            
            $stmt->bind_param('sssi', $qr_code, $test_date, $notes, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $test_id = $stmt->insert_id;
                $stmt->close();
                
                // QR kod görseli oluştur
                require_once '../config/qr_generator.php';
                $qr_image_path = generateQRCode($qr_code, $test_id);
                
                if ($qr_image_path !== false) {
                    // QR kod görsel yolunu güncelle (Prepared statement)
                    $update_stmt = $conn->prepare("UPDATE test_results SET qr_code_image = ? WHERE id = ?");
                    $update_stmt->bind_param('si', $qr_image_path, $test_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
                
                // Log kaydet
                logTestAction('CREATE', $test_id, $qr_code, [
                    'created_by' => $_SESSION['user_id'],
                            'test_date' => $test_date,
                            'batch_number' => $i + 1,
                            'batch_total' => $test_count
                        ]);
                        
                        $created_tests[] = $qr_code;
                    } else {
                        logError("Test oluşturma hatası", ['error' => $stmt->error, 'batch_number' => $i + 1]);
                        $failed_count++;
                        $stmt->close();
                    }
                }
                
                // Sonuç bildirimi
                if (count($created_tests) > 0) {
                    $success_msg = count($created_tests) . " adet test başarıyla oluşturuldu!";
                    if ($failed_count > 0) {
                        $success_msg .= " (" . $failed_count . " adet başarısız)";
                    }
                
                // Başarılı oldu, dashboard'a yönlendir
                    header('Location: dashboard.php?success=' . urlencode($success_msg) . '&count=' . count($created_tests));
                exit;
            } else {
                    $error = "Test oluşturma sırasında bir hata oluştu. Hiçbir test oluşturulamadı.";
                }
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
    <title>Yeni Test Ekle - CROMTEST</title>
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
                    <h1>Yeni Test Oluştur</h1>
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

            <form method="POST" action="new_test.php" class="test-form">
                <?php echo csrfTokenField(); ?>
                
                <div class="form-section">
                    <h2>📋 Test Bilgileri</h2>
                    
                    <div class="form-row" style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px; margin-top: 20px;">
                        <div class="form-group">
                            <label for="test_date">
                                <span class="label-icon">📅</span>
                                Test Tarihi ve Saati
                            </label>
                            <input type="datetime-local" id="test_date" name="test_date" 
                                   value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                            <small class="input-hint">
                                Test kitinin kullanıldığı tarih ve saat
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="test_count">
                                <span class="label-icon">🔢</span>
                                Test Sayısı
                            </label>
                            <input type="number" id="test_count" name="test_count" 
                                   value="1" min="1" max="100" required class="number-input">
                            <small class="input-hint">
                                1-100 arası test oluşturabilirsiniz
                            </small>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2>📝 Ek Notlar <span class="optional-badge">Opsiyonel</span></h2>
                    <div class="form-group">
                        <label for="notes">
                            <span class="label-icon">💬</span>
                            Açıklama / Notlar
                        </label>
                        <textarea name="notes" id="notes" rows="4" 
                                  placeholder="Test hakkında ek notlar, hasta bilgisi veya diğer açıklamalar ekleyebilirsiniz..."></textarea>
                        <small class="input-hint">
                            Bu alan zorunlu değildir, boş bırakabilirsiniz
                        </small>
                    </div>
                </div>

                <div class="info-steps">
                    <div class="step-item">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <strong>Test Oluştur</strong>
                            <span>Form bilgilerini doldurup test(ler) oluşturun</span>
                        </div>
                    </div>
                    <div class="step-arrow">→</div>
                    <div class="step-item">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <strong>QR Kod İndir</strong>
                            <span>Oluşturulan QR kodları indirin ve yazdırın</span>
                        </div>
                    </div>
                    <div class="step-arrow">→</div>
                    <div class="step-item">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <strong>Sonuç Girişi</strong>
                            <span>Kullanıcılar QR kod okutarak sonucu girecek</span>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary btn-large">
                        <span class="btn-icon">✓</span>
                        Test Oluştur ve QR Kod Üret
                    </button>
                    <a href="dashboard.php" class="btn-secondary btn-large">
                        <span class="btn-icon">←</span>
                        İptal Et
                    </a>
                </div>
            </form>
        </main>
    </div>
    
    <style>
        .admin-main {
            max-width: 1000px;
            margin: 0 auto;
        }

        .test-form {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .form-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .form-section:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }
        
        .form-section h2 {
            color: #1e293b;
            margin-bottom: 20px;
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .optional-badge {
            font-size: 0.75rem;
            background: #f1f5f9;
            color: #64748b;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 500;
            margin-left: auto;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 10px;
            font-size: 1.05rem;
        }

        .label-icon {
            font-size: 1.2rem;
        }
        
        .form-group input[type="datetime-local"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            background: white;
        }

        .form-group input[type="number"].number-input {
            font-size: 1.3rem;
            font-weight: 700;
            text-align: center;
            color: #6366f1;
        }
        
        .form-group input[type="datetime-local"]:hover,
        .form-group input[type="number"]:hover,
        .form-group select:hover,
        .form-group textarea:hover {
            border-color: #cbd5e1;
        }

        .form-group input[type="datetime-local"]:focus,
        .form-group input[type="number"]:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .input-hint {
            display: block;
            color: #64748b;
            font-size: 0.9rem;
            margin-top: 8px;
            font-style: italic;
        }

        .info-steps {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 25px;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-radius: 12px;
            margin-bottom: 25px;
            border: 2px solid #bae6fd;
        }

        .step-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 12px;
        }

        .step-number {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .step-content {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .step-content strong {
            color: #0c4a6e;
            font-size: 1rem;
            font-weight: 700;
        }

        .step-content span {
            color: #0369a1;
            font-size: 0.85rem;
        }

        .step-arrow {
            color: #0ea5e9;
            font-size: 2rem;
            font-weight: 700;
            flex-shrink: 0;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid #e2e8f0;
        }
        
        .btn-large {
            padding: 16px 35px;
            font-size: 1.1rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            border-radius: 10px;
        }

        .btn-icon {
            font-size: 1.3rem;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(100, 116, 139, 0.3);
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr !important;
            }

            .info-steps {
                flex-direction: column;
            }

            .step-arrow {
                transform: rotate(90deg);
            }

            .form-actions {
                flex-direction: column;
            }

            .btn-large {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</body>
</html>
