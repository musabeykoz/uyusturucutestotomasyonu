# 🧪 CromTest - QR Kodlu Test Yönetim Sistemi

Modern ve güvenli bir test sonuç takip sistemi. QR kod ile hızlı sonuç sorgulama ve entegre ürün satış modülü.

## ✨ Tanıtım Videosu
https://youtu.be/cu22xeB-0IU

# ✨ Özellikler

### 📱 QR Test Paneli
- **QR Kod ile Sonuç Sorgulama** - Kullanıcılar QR kodu okutarak test sonuçlarına anında ulaşır
- **C ve T Çizgisi Takibi** - Control ve Test çizgilerinin durumunu kaydedin
- **Toplu QR Üretimi** - Tek seferde 50'ye kadar QR kod oluşturabilme
- **Akıllı Durum Yönetimi** - Beklemede, Tamamlandı, Geçersiz durumları
- **Rol Bazlı Yetkilendirme** - Admin ve Operatör rolleri ile güvenli erişim

### 🛒 Ürün Satış Modülü
- **Ürün Katalog Sistemi** - Modern arayüz ile ürün listeleme
- **Çoklu Görsel Desteği** - Her ürün için birden fazla görsel yükleme
- **Tıklama İstatistikleri** - Detay sayfası ve satın alma butonları takibi
- **Harici Link Entegrasyonu** - Amazon, Trendyol gibi platformlara yönlendirme

### 🔒 Güvenlik
- **SQL Injection Koruması** - Prepared statements ile %100 korunma
- **XSS Koruması** - Tüm çıktılar güvenli şekilde filtreleniyor
- **CSRF Koruması** - Token bazlı form güvenliği
- **Session Güvenliği** - Otomatik timeout ve hijacking koruması
- **Rate Limiting** - Brute force saldırılarına karşı koruma
- **Şifreli Parola Saklama** - bcrypt algoritması ile güvenli hash'leme

## 🚀 Kurulum

### Gereksinimler
```
PHP 7.4+
MySQL 5.7+ / MariaDB 10.4+
Apache/Nginx
GD Library (QR kod için)
```

### 3 Adımda Kurulum

**1. Projeyi İndirin**
```bash
git clone https://github.com/yourusername/cromtest.git
```

**2. Veritabanını Kurun**
- phpMyAdmin'de yeni bir veritabanı oluşturun
- `database.sql` dosyasını import edin
- Veritabanı adı: `cromtest_db`

**3. Bağlantı Ayarlarını Yapın**
`QRpanel/config/database.php` dosyasını açın ve bilgilerinizi girin:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'veritabani_kullanici');
define('DB_PASS', 'sifreniz');
define('DB_NAME', 'cromtest_db');
```

**Hepsi Bu Kadar!** 🎉

## 👤 Giriş Bilgileri

**Admin Hesabı:**
- Kullanıcı: `admin`
- Şifre: `(database.sql'deki hash ile)`

**Operatör Hesabı:**
- Kullanıcı: `operator`
- Şifre: `(database.sql'deki hash ile)`

## 📂 Panel Erişimi

| Panel | URL | Açıklama |
|-------|-----|----------|
| QR Test Paneli | `/QRpanel/admin/dashboard.php` | Test sonuçları yönetimi |
| Ürün Yönetimi | `/purchase/admin/dashboard.php` | Ürün ekleme/düzenleme |
| Test Sorgulama | `/QRpanel/` | Kullanıcı tarafı sorgulama |
| Ürün Kataloğu | `/purchase/` | Ürün listesi |

## 🎯 Kullanım Senaryosu

1. **Admin** sisteme giriş yapar
2. **Toplu QR kod** üretir (örn: 20 test için)
3. QR kodları **indirir ve yazdırır**
4. Test sonuçları **QR kodu ile sorgulanır**
5. Admin/Operatör **sonuçları sisteme girer**
6. Kullanıcı QR kodu okutarak **sonucunu görür**

## 💡 Teknik Detaylar

**Mimari:**
- MVC benzeri yapı
- Modüler tasarım
- API endpoint'leri
- Güvenli session yönetimi

**Veritabanı:**
- 4 ana tablo (users, test_results, products, product_images)
- Foreign key ilişkileri
- Index optimizasyonu

**Güvenlik:**
- OWASP Top 10 standartları
- Prepared statements
- Input sanitization
- Output encoding
- CSRF token validation

## 📊 Proje Yapısı

```
cromtest/
├── QRpanel/              # Test yönetim sistemi
│   ├── admin/            # Admin paneli
│   ├── api/              # API endpoint'leri
│   ├── config/           # Veritabanı ve güvenlik ayarları
│   ├── includes/         # QR kod kütüphanesi
│   └── uploads/          # QR kod görselleri
├── purchase/             # Ürün satış modülü
│   ├── admin/            # Ürün yönetimi
│   ├── api/              # Tıklama tracking
│   └── uploads/          # Ürün görselleri
└── database.sql          # Veritabanı şeması
```

## ⚠️ Önemli Notlar

- ✅ Production ortamında **HTTPS kullanın**
- ✅ İlk girişte **şifreleri değiştirin**
- ✅ `uploads/` ve `logs/` klasörlerine **yazma izni** verin
- ✅ Düzenli **veritabanı yedeği** alın
- ⚠️ Debug modunu production'da **kapatın**

## 📞 Destek

Sorularınız için issue açabilir veya iletişime geçebilirsiniz.

---

**Not:** Bu sistem demo kullanıcı verileri ile gelir. Production'a geçmeden önce güvenlik incelemesi yapılması önerilir.

⭐ Projeyi beğendiyseniz yıldız vermeyi unutmayın!

