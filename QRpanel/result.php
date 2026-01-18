<?php
require_once 'config/database.php';
require_once 'config/qr_generator.php';
require_once 'config/security.php';
require_once 'config/session.php';

// Güvenli session başlat
startSecureSession();

// QR kod parametresini al
$qr_code = isset($_GET['qr']) ? trim($_GET['qr']) : '';

if (empty($qr_code) && isset($_SERVER['REQUEST_URI'])) {
    $request_uri = $_SERVER['REQUEST_URI'];
    if (preg_match('/[?&]qr=([^&?#]+)/', $request_uri, $matches)) {
        $qr_code = urldecode(trim($matches[1]));
    }
}

$test_result = null;
$error = null;

// QR kod işleme
if (!empty($qr_code)) {
    if (strpos($qr_code, 'http') === 0 || strpos($qr_code, 'result.php') !== false || strpos($qr_code, '?qr=') !== false || strpos($qr_code, '&qr=') !== false) {
        if (preg_match('/[?&]qr=([^&?#]+)/', $qr_code, $matches)) {
            $qr_code = urldecode(trim($matches[1]));
        } elseif (preg_match('/(CROMTEST-[0-9]{4}-[0-9]+)/', $qr_code, $matches)) {
            $qr_code = $matches[1];
        } else {
            $parts = explode('qr=', $qr_code);
            if (count($parts) > 1) {
                $qr_code = trim(urldecode($parts[1]));
                $qr_code = preg_replace('/[&#?].*$/', '', $qr_code);
            }
        }
    }
    
    $qr_code = trim($qr_code);
    
    if (!empty($qr_code)) {
        $test_result = getTestResultByQR($qr_code);
        if (!$test_result) {
            $error = "Belirtilen QR kod ile test bulunamadı.";
        }
    } else {
        $error = "Geçersiz QR kod formatı.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Sonuçları - CROMTEST</title>
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link rel="apple-touch-icon" href="assets/images/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/result.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --secondary: #8b5cf6;
            --accent: #a78bfa;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-card: rgba(30, 41, 59, 0.95);
            --bg-glass: rgba(30, 41, 59, 0.8);
            --text-primary: #f8fafc;
            --text-secondary: #cbd5e1;
            --text-muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.08);
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
            --shadow-glow: 0 0 25px rgba(99, 102, 241, 0.35);
            --radius-md: 16px;
            --radius-lg: 20px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: radial-gradient(120% 120% at 20% 20%, rgba(99, 102, 241, 0.08), transparent), var(--bg-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--text-primary);
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(99, 102, 241, 0.15), transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(139, 92, 246, 0.15), transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        @media (max-width: 640px) {
            body {
                padding: 10px;
            }
        }

        .container {
            width: 100%;
            max-width: 700px;
            position: relative;
            z-index: 1;
        }

        .result-form-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 40px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            backdrop-filter: blur(12px);
        }

        .logo-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            gap: 20px;
            flex-wrap: wrap;
        }

        .logo-image {
            max-width: 120px;
            height: auto;
            filter: drop-shadow(0 8px 16px rgba(0,0,0,0.35));
        }

        .qr-code-display {
            background: rgba(255, 255, 255, 0.04);
            padding: 12px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        .qr-code-display img {
            display: block;
            width: 100px;
            height: 100px;
            object-fit: contain;
            border-radius: 8px;
        }

        .test-info-box {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.12) 0%, rgba(139, 92, 246, 0.1) 100%);
            border-radius: var(--radius-md);
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid var(--primary);
            border: 1px solid var(--border);
        }

        .test-info-box p {
            margin: 8px 0;
            color: var(--text-primary);
            font-size: 1rem;
            font-weight: 500;
        }

        .test-info-box strong {
            color: var(--text-primary);
            font-weight: 700;
        }

        .form-title {
            text-align: center;
            font-size: 1.8rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .form-subtitle {
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 40px;
            font-size: 1rem;
            line-height: 1.6;
            padding: 0 20px;
        }

        .line-section {
            margin-bottom: 35px;
            padding: 30px;
            background: rgba(255, 255, 255, 0.04);
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
            transition: all 0.3s ease;
        }

        .line-section:hover {
            border-color: rgba(99, 102, 241, 0.35);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.2);
            transform: translateY(-2px);
        }

        .line-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .line-title::before {
            content: "📋";
            font-size: 1.4rem;
        }

        .line-description {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-bottom: 25px;
            padding-left: 35px;
            font-style: italic;
        }

        .button-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .option-button {
            padding: 20px;
            border: 2px solid var(--border);
            border-radius: var(--radius-md);
            background: rgba(255, 255, 255, 0.04);
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            position: relative;
        }

        .option-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.25);
            border-color: rgba(99, 102, 241, 0.4);
        }

        .option-button.selected {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(139, 92, 246, 0.15) 100%);
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.3);
        }

        .option-button input[type="radio"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .option-icon {
            font-size: 3rem;
            margin-bottom: 10px;
            display: block;
        }

        .option-button.positive .option-icon {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .option-button.negative .option-icon {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .option-label {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .option-sublabel {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .submit-button {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
            box-shadow: var(--shadow-glow);
        }

        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 32px rgba(99, 102, 241, 0.5);
        }

        .submit-button:active {
            transform: translateY(0);
        }

        .error-message {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.35);
            border-radius: var(--radius-md);
            padding: 20px;
            text-align: center;
            color: #fecdd3;
        }

        .error-message h2 {
            color: #fca5a5;
            margin-bottom: 10px;
        }

        .success-message {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.4);
            border-radius: var(--radius-md);
            padding: 20px;
            text-align: center;
            color: #bbf7d0;
            margin-bottom: 20px;
        }

        /* Sonuç Görüntüleme Stilleri */
        .result-display {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 40px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            backdrop-filter: blur(12px);
        }

        .result-header {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            margin-bottom: 30px;
        }

        .result-info-item {
            padding: 15px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.12) 0%, rgba(139, 92, 246, 0.1) 100%);
            border-radius: var(--radius-md);
            border-left: 4px solid var(--primary);
            border: 1px solid var(--border);
        }

        .result-info-item strong {
            color: var(--text-primary);
            display: block;
            margin-bottom: 5px;
        }

        .result-info-item span {
            color: var(--text-primary);
            font-weight: 500;
        }

        .result-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 30px;
        }

        .result-item {
            padding: 20px;
            border-radius: var(--radius-md);
            text-align: center;
            border: 2px solid var(--border);
            background: rgba(255, 255, 255, 0.04);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
            transition: all 0.3s ease;
        }

        .result-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.25);
        }

        .result-item.positive {
            border-color: rgba(239, 68, 68, 0.4);
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(220, 38, 38, 0.1) 100%);
        }

        .result-item.negative {
            border-color: rgba(16, 185, 129, 0.4);
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(5, 150, 105, 0.1) 100%);
        }

        .result-item-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .result-item-description {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-bottom: 15px;
            font-style: italic;
        }

        .result-value {
            font-size: 2.5rem;
            margin-bottom: 8px;
        }

        .result-item.positive .result-value {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .result-item.negative .result-value {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .result-label {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .result-item .option-sublabel {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .result-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            justify-content: center;
        }

        .btn-secondary {
            flex: 1;
            max-width: 180px;
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(99, 102, 241, 0.4);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        }

        .result-actions .submit-button {
            flex: 1;
            max-width: 180px;
            padding: 12px 20px;
            font-size: 0.95rem;
            margin-top: 0;
        }

        @media (max-width: 640px) {
            .container {
                max-width: 95%;
                padding: 5px;
            }

            .result-form-card, .result-display {
                padding: 20px 15px;
            }

            .logo-container {
                flex-direction: row;
                align-items: center;
                justify-content: center;
                gap: 15px;
            }

            .logo-image {
                max-width: 80px;
            }

            .qr-code-display {
                padding: 8px;
            }

            .qr-code-display img {
                width: 60px;
                height: 60px;
            }

            .line-section {
                padding: 20px;
            }

            .line-title {
                font-size: 1.1rem;
            }

            .line-description {
                font-size: 0.85rem;
                padding-left: 25px;
            }

            .button-group {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }

            .option-button {
                padding: 12px 8px;
            }

            .option-icon {
                font-size: 2rem;
                margin-bottom: 5px;
            }

            .option-label {
                font-size: 0.9rem;
                margin-bottom: 3px;
            }

            .option-sublabel {
                font-size: 0.75rem;
            }

            .result-grid {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }

            .result-item {
                padding: 15px 10px;
            }

            .result-item-title {
                font-size: 0.9rem;
                margin-bottom: 3px;
            }

            .result-item-description {
                font-size: 0.7rem;
                margin-bottom: 10px;
            }

            .result-value {
                font-size: 2rem;
                margin-bottom: 5px;
            }

            .result-label {
                font-size: 0.95rem;
                margin-bottom: 3px;
            }

            .result-item .option-sublabel {
                font-size: 0.75rem;
            }

            .result-actions {
                display: flex;
                flex-direction: row;
                gap: 10px;
                margin-top: 20px;
                justify-content: center;
            }

            .btn-secondary {
                flex: 1;
                max-width: 140px;
                padding: 10px 12px;
                font-size: 0.85rem;
            }

            .result-actions .submit-button {
                flex: 1;
                max-width: 140px;
                padding: 10px 12px;
                font-size: 0.85rem;
            }
        }

        @media print {
            body {
                background: white;
            }
            
            body::before {
                display: none;
            }
            
            .container {
                max-width: 100%;
            }
            
            .result-form-card,
            .result-display {
                background: white;
                box-shadow: none;
                border: 1px solid #e2e8f0;
            }
            
            .result-actions {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($error): ?>
            <div class="result-form-card">
                <div class="error-message">
                    <h2>Hata</h2>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            </div>
        <?php elseif ($test_result): ?>
            <?php if ($test_result['is_filled'] == 1): ?>
                <!-- Sonuçlar Doldurulmuş - Göster -->
                <div class="result-display">
                    <div class="logo-container">
                        <img src="assets/images/logo.png" alt="Logo" class="logo-image">
                        <?php if (!empty($test_result['qr_code_image'])): ?>
                            <div class="qr-code-display">
                                <?php echo displayQRCode($test_result['qr_code_image']); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="test-info-box">
                        <p><strong>QR Kod:</strong> <?php echo htmlspecialchars($test_result['qr_code']); ?></p>
                        <p><strong>Test Tarihi:</strong> <?php echo date('d.m.Y H:i', strtotime($test_result['test_date'])); ?></p>
                        <p><strong>Sonuç Tarihi:</strong> <?php echo date('d.m.Y H:i', strtotime($test_result['filled_at'])); ?></p>
                    </div>

                    <h2 class="form-title">Test Sonuçları</h2>

                    <div class="result-grid">
                        <div class="result-item <?php echo $test_result['control_result'] == 1 ? 'positive' : 'negative'; ?>">
                            <div class="result-item-title">C - Control</div>
                            <div class="result-item-description">Kontrol Çizgisi</div>
                            <div class="result-value"><?php echo $test_result['control_result'] == 1 ? '✓' : '✗'; ?></div>
                            <div class="result-label">
                                <?php echo $test_result['control_result'] == 1 ? 'POZİTİF' : 'NEGATİF'; ?>
                            </div>
                            <div class="option-sublabel">
                                <?php echo $test_result['control_result'] == 1 ? 'Çizgi Var' : 'Çizgi Yok'; ?>
                            </div>
                        </div>

                        <div class="result-item <?php echo $test_result['test_result'] == 1 ? 'positive' : 'negative'; ?>">
                            <div class="result-item-title">T - Test</div>
                            <div class="result-item-description">Test Çizgisi</div>
                            <div class="result-value"><?php echo $test_result['test_result'] == 1 ? '✓' : '✗'; ?></div>
                            <div class="result-label">
                                <?php echo $test_result['test_result'] == 1 ? 'POZİTİF' : 'NEGATİF'; ?>
                            </div>
                            <div class="option-sublabel">
                                <?php echo $test_result['test_result'] == 1 ? 'Çizgi Var' : 'Çizgi Yok'; ?>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($test_result['notes'])): ?>
                        <div class="test-info-box" style="margin-top: 20px;">
                            <p><strong>Notlar:</strong></p>
                            <p><?php echo nl2br(htmlspecialchars($test_result['notes'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="result-actions">
                        <a href="index.php" class="btn-secondary">Ana Sayfa</a>
                        <button onclick="window.print()" class="submit-button" style="margin-top: 0;">Yazdır</button>
                    </div>
                </div>
            <?php else: ?>
                <!-- Sonuçlar Henüz Doldurulmamış - Form Göster -->
                <div class="result-form-card">
                    <div class="logo-container">
                        <img src="assets/images/logo.png" alt="Logo" class="logo-image">
                        <?php if (!empty($test_result['qr_code_image'])): ?>
                            <div class="qr-code-display">
                                <?php echo displayQRCode($test_result['qr_code_image']); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="test-info-box">
                        <p><strong>QR Kod:</strong> <?php echo htmlspecialchars($test_result['qr_code']); ?></p>
                        <p><strong>Test Tarihi:</strong> <?php echo date('d.m.Y H:i', strtotime($test_result['test_date'])); ?></p>
                    </div>

                    <h2 class="form-title">Test Sonuçlarını Girin</h2>
                    <p class="form-subtitle">Lütfen test kitindeki C (Control) ve T (Test) çizgilerinin durumlarını seçin.</p>

                    <form id="resultForm" method="POST" action="api/save_result.php">
                        <input type="hidden" name="qr_code" value="<?php echo escape($test_result['qr_code']); ?>">
                        <?php echo csrfTokenField(); ?>
                        
                        <!-- C - Control Çizgisi -->
                        <div class="line-section">
                            <div class="line-title">C - Control (Kontrol Çizgisi)</div>
                            <div class="line-description">Testin geçerli olup olmadığını gösterir</div>
                            
                            <div class="button-group">
                                <label class="option-button positive" onclick="selectOption(this, 'control')">
                                    <input type="radio" name="control_result" value="1" required>
                                    <span class="option-icon">✓</span>
                                    <div class="option-label">POZİTİF</div>
                                    <div class="option-sublabel">Çizgi Var</div>
                                </label>
                                
                                <label class="option-button negative" onclick="selectOption(this, 'control')">
                                    <input type="radio" name="control_result" value="0" required>
                                    <span class="option-icon">✗</span>
                                    <div class="option-label">NEGATİF</div>
                                    <div class="option-sublabel">Çizgi Yok</div>
                                </label>
                            </div>
                        </div>

                        <!-- T - Test Çizgisi -->
                        <div class="line-section">
                            <div class="line-title">T - Test (Test Çizgisi)</div>
                            <div class="line-description">Test sonucunu gösterir</div>
                            
                            <div class="button-group">
                                <label class="option-button positive" onclick="selectOption(this, 'test')">
                                    <input type="radio" name="test_result" value="1" required>
                                    <span class="option-icon">✓</span>
                                    <div class="option-label">POZİTİF</div>
                                    <div class="option-sublabel">Çizgi Var</div>
                                </label>
                                
                                <label class="option-button negative" onclick="selectOption(this, 'test')">
                                    <input type="radio" name="test_result" value="0" required>
                                    <span class="option-icon">✗</span>
                                    <div class="option-label">NEGATİF</div>
                                    <div class="option-sublabel">Çizgi Yok</div>
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="submit-button">Sonuçları Kaydet</button>
                    </form>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="result-form-card">
                <div class="error-message">
                    <h2>QR Kod Gerekli</h2>
                    <p>Test sonuçlarını görüntülemek için bir QR kod okutmalısınız.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function selectOption(element, group) {
            // Aynı gruptaki diğer butonlardan selected class'ını kaldır
            const section = element.closest('.line-section');
            const buttons = section.querySelectorAll('.option-button');
            buttons.forEach(btn => btn.classList.remove('selected'));
            
            // Tıklanan butona selected class'ını ekle
            element.classList.add('selected');
            
            // Radio input'u işaretle
            const radio = element.querySelector('input[type="radio"]');
            radio.checked = true;
        }

        // Form submit
        document.getElementById('resultForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton ? submitButton.textContent : '';
            
            // Butonu devre dışı bırak
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Kaydediliyor...';
            }
            
            fetch('api/save_result.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Response'u önce text olarak oku
                return response.text().then(text => {
                    try {
                        // JSON'a parse et
                        const data = JSON.parse(text);
                        return { ok: response.ok, data: data };
                    } catch (e) {
                        // JSON parse hatası - muhtemelen HTML error sayfası
                        console.error('JSON parse error:', text);
                        throw new Error('Sunucu hatası: Geçersiz yanıt formatı');
                    }
                });
            })
            .then(result => {
                if (result.ok && result.data.success) {
                    // Başarılı kayıt - Sayfayı yenile
                    window.location.reload();
                } else {
                    // Butonu tekrar aktif et
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = originalText;
                    }
                    alert('Hata: ' + (result.data.message || 'Bilinmeyen hata'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Butonu tekrar aktif et
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = originalText;
                }
                alert('Bir hata oluştu: ' + error.message);
            });
        });
    </script>
</body>
</html>
