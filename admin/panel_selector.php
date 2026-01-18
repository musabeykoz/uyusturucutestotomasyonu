<?php
require_once __DIR__ . '/../QRpanel/config/auth.php';
require_once __DIR__ . '/../QRpanel/config/security.php';

// Giriş kontrolü
requireLogin();

$username = $_SESSION['username'] ?? '';
$full_name = $_SESSION['full_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Seçimi - CROMTEST</title>
    <link rel="icon" type="image/png" href="../QRpanel/assets/images/logo.png">
    <link rel="apple-touch-icon" href="../QRpanel/assets/images/logo.png">
    <link rel="stylesheet" href="../QRpanel/assets/css/style.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #a5b4fc;
            --secondary: #8b5cf6;
            --bg: #0f172a;
            --card: rgba(15, 23, 42, 0.92);
            --glass: rgba(30, 41, 59, 0.8);
            --text: #f8fafc;
            --muted: #cbd5e1;
            --border: rgba(255, 255, 255, 0.1);
            --shadow: 0 18px 48px rgba(0, 0, 0, 0.45);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
            background: radial-gradient(600px circle at 20% 20%, rgba(99, 102, 241, 0.35), transparent 50%), 
                        radial-gradient(500px circle at 80% 10%, rgba(139, 92, 246, 0.35), transparent 52%), 
                        radial-gradient(520px circle at 70% 80%, rgba(34, 211, 238, 0.25), transparent 55%), 
                        var(--bg);
        }

        .selector-container {
            width: 100%;
            max-width: 900px;
        }

        .selector-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .selector-header .logo-container {
            margin-bottom: 20px;
        }

        .selector-header .logo {
            max-width: 110px;
            height: auto;
            filter: drop-shadow(0 12px 24px rgba(0,0,0,0.35));
        }

        .selector-header h1 {
            font-size: 2.4rem;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .selector-header p {
            color: var(--muted);
            font-size: 1.1rem;
        }

        .user-info {
            background: var(--glass);
            padding: 15px 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
            border: 1px solid var(--border);
        }

        .user-info strong {
            color: var(--primary-light);
        }

        .panels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .panel-card {
            background: var(--card);
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            backdrop-filter: blur(16px);
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--text);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .panel-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
            transition: left 0.5s;
        }

        .panel-card:hover::before {
            left: 100%;
        }

        .panel-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: 0 25px 60px rgba(99, 102, 241, 0.3);
        }

        .panel-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            filter: drop-shadow(0 8px 16px rgba(0,0,0,0.3));
        }

        .panel-card h2 {
            font-size: 1.8rem;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .panel-card p {
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.6;
        }

        .logout-link {
            text-align: center;
            margin-top: 30px;
        }

        .logout-link a {
            color: var(--primary-light);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .logout-link a:hover {
            color: var(--text);
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .panels-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="selector-container">
        <div class="selector-header">
            <div class="logo-container">
                <img src="../QRpanel/assets/images/logo.png" alt="CROMTEST Logo" class="logo">
            </div>
            <h1>CROMTEST</h1>
            <p>Yönetim Paneli</p>
        </div>

        <div class="user-info">
            Hoş geldiniz, <strong><?php echo htmlspecialchars($full_name); ?></strong> (<?php echo htmlspecialchars($username); ?>)
        </div>

        <div class="panels-grid">
            <a href="../QRpanel/admin/dashboard.php" class="panel-card">
                <div class="panel-icon">📱</div>
                <h2>QR Panel</h2>
                <p>QR kod tabanlı test sonuçları yönetim sistemi. Test oluşturma, düzenleme ve takip işlemleri.</p>
            </a>

            <a href="../purchase/admin/dashboard.php" class="panel-card">
                <div class="panel-icon">🛒</div>
                <h2>Mağaza</h2>
                <p>E-ticaret mağaza yönetim sistemi. Ürün yönetimi ve takip işlemleri.</p>
            </a>
        </div>

        <div class="logout-link">
            <a href="../QRpanel/logout.php">Çıkış Yap</a>
        </div>
    </div>
</body>
</html>

