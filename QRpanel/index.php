<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CROMTEST - Uyuşturucu Tarama Testi Sonuç Sistemi</title>
    <meta name="description" content="CROMTEST QR kod tabanlı 12'li uyuşturucu tarama test sonuç sistemi">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link rel="apple-touch-icon" href="assets/images/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/landing.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <section class="hero" id="qr-section">
        <div class="hero-background">
            <div class="gradient-orb orb-1"></div>
            <div class="gradient-orb orb-2"></div>
            <div class="gradient-orb orb-3"></div>
        </div>
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-badge">
                    <span class="badge-icon">🔒</span>
                    Güvenli Sonuç Erişimi
                </div>
                <h1 class="hero-title">
                    <span class="title-line">QR Kod ile</span>
                    <span class="title-line highlight">Anında Test Sonucu</span>
                </h1>
                <p class="hero-description">
                    12'li uyuşturucu tarama kitinizin üzerindeki QR kodu okutup saniyeler içinde doğrulanmış sonuca ulaşın. Aynı marka ve deneyim, test sistemine özel arayüz.
                </p>
                <div class="hero-cta" style="display:none;"></div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <div class="stat-number">12</div>
                        <div class="stat-label">Panel Test</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Erişim</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">SSL</div>
                        <div class="stat-label">Şifreleme</div>
                    </div>
                </div>
            </div>

            <div class="hero-visual" id="scanner-card">
                <div class="scanner-card">
                    <div class="card-header">
                        <div class="chip"></div>
                        <div>
                            <p class="card-kicker">Test Sistemi</p>
                            <h2>QR Kod Okutun</h2>
                        </div>
                    </div>
                    <p class="instruction">Test üzerindeki QR kodu okutarak sonuçları görüntüleyin</p>
                    
                    <div class="qr-reader-container">
                        <div class="qr-reader-tabs">
                            <button class="qr-tab-btn active" data-tab="camera">📷 Kamera ile Okut</button>
                            <button class="qr-tab-btn" data-tab="file">📁 Dosya Seç</button>
                        </div>
                        
                        <div id="camera-tab" class="qr-tab-content active">
                            <div id="qr-reader" class="qr-reader-box"></div>
                            <div id="qr-reader-results"></div>
                        </div>
                        
                        <div id="file-tab" class="qr-tab-content">
                            <div class="file-upload-area">
                                <input type="file" id="qr-file-input" accept="image/*" style="display: none;">
                                <label for="qr-file-input" class="file-upload-label">
                                    <div class="upload-icon">📁</div>
                                    <p><strong>QR Kod Resmini Seçin</strong></p>
                                    <p class="upload-hint">PNG, JPG veya JPEG formatında</p>
                                    <button type="button" class="btn-select-file">Dosya Seç</button>
                                </label>
                                <div id="file-preview" style="display: none; margin-top: 20px; text-align: center;">
                                    <img id="preview-image" src="" alt="Önizleme" style="max-width: 300px; border-radius: 8px; margin-bottom: 10px;">
                                    <p id="file-status"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="manual-input">
                        <p class="or-divider">veya</p>
                        <form id="manual-qr-form" method="GET" action="result.php">
                            <input 
                                type="text" 
                                id="qr-code-input" 
                                name="qr" 
                                placeholder="QR kod numarasını manuel olarak girin"
                                required
                            >
                            <button type="submit" class="btn btn-primary btn-block">Sonuçları Görüntüle</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="info-section" id="info">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Test Hakkında</h2>
                <p class="section-subtitle">Klinik standartlarda doğrulanmış 12'li panel</p>
            </div>
            <div class="info-grid">
                <div class="info-card">
                    <h3>Nasıl Çalışır?</h3>
                    <p>CROMTEST, rekabetçi bağlanma prensibine dayalı bir immünoassay testidir. Test sonuçları, numune örneğindeki uyuşturucu maddelerin kesim konsantrasyonlarını belirler.</p>
                    <ul>
                        <li><strong>Negatif Sonuç:</strong> Test çizgisi görünür (renkli çizgi)</li>
                        <li><strong>Pozitif Sonuç:</strong> Test çizgisi görünmez (uyuşturucu tespit edildi)</li>
                        <li><strong>Kontrol Çizgisi:</strong> Her zaman görünür olmalıdır</li>
                    </ul>
                </div>
                <div class="info-card">
                    <h3>Güvenlik & Uyarı</h3>
                    <div class="medical-warning">
                        <strong>⚠ Yalnızca tıbbi ve diğer profesyonel in vitro tanı amaçlı kullanım içindir.</strong>
                    </div>
                    <p class="muted">Sonuçlar doğrulama için laboratuvar onayı gerektirebilir. Ek güvenlik için veriler SSL ile şifrelenir.</p>
                </div>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="../assets/js/landing.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>

