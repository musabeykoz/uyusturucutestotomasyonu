// QR Kod Okuma Fonksiyonu
let html5QrcodeScanner = null;
let html5Qrcode = null;
let html5QrcodeCamera = null;

function onScanSuccess(decodedText, decodedResult) {
    // QR kod başarıyla okundu
    // Sonuç sayfasına yönlendir
    window.location.href = `result.php?qr=${encodeURIComponent(decodedText)}`;
    
    // Tarayıcıyı durdur
    if (html5QrcodeScanner) {
        html5QrcodeScanner.clear();
    }
}

function onScanFailure(error) {
    // Hata durumunda sessizce devam et
}

// Dosyadan QR kod okuma
async function scanQRFromFile(file) {
    if (!file) {
        return;
    }
    
    const fileStatus = document.getElementById('file-status');
    const previewImage = document.getElementById('preview-image');
    const filePreview = document.getElementById('file-preview');
    
    // Önizleme göster
    const reader = new FileReader();
    reader.onload = function(e) {
        previewImage.src = e.target.result;
        filePreview.style.display = 'block';
        fileStatus.innerHTML = '<span style="color: #2563eb;">⏳ QR kod okunuyor...</span>';
    };
    reader.readAsDataURL(file);
    
    try {
        if (!html5Qrcode) {
            html5Qrcode = new Html5Qrcode("file-preview");
        }
        
        const decodedText = await html5Qrcode.scanFile(file, true);
        
        if (decodedText) {
            fileStatus.innerHTML = '<span style="color: green;">✓ QR kod başarıyla okundu!</span>';
            setTimeout(() => {
                window.location.href = `result.php?qr=${encodeURIComponent(decodedText)}`;
            }, 500);
        }
    } catch (error) {
        fileStatus.innerHTML = '<span style="color: red;">✗ QR kod okunamadı. Lütfen geçerli bir QR kod resmi seçin.</span>';
    }
}

// Sayfa yüklendiğinde QR kod okuyucuyu başlat
document.addEventListener('DOMContentLoaded', function() {
    // Mobil menü
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });
        navMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => navMenu.classList.remove('active'));
        });
    }

    // Tab değiştirme
    const tabButtons = document.querySelectorAll('.qr-tab-btn');
    const tabContents = document.querySelectorAll('.qr-tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Tüm tabları gizle
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Tüm butonları pasif yap
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Seçilen tabı göster
            document.getElementById(targetTab + '-tab').classList.add('active');
            this.classList.add('active');
            
            // Kamera tabına geçildiğinde tarayıcıyı başlat
            if (targetTab === 'camera' && !html5QrcodeScanner) {
                initCameraScanner();
            }
        });
    });
    
    // Kamera tarayıcısını başlat
    async function initCameraScanner() {
        const qrReaderElement = document.getElementById('qr-reader');
        
        if (qrReaderElement && !html5QrcodeCamera) {
            try {
                // Mobil cihaz kontrolü
                const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
                
                html5QrcodeCamera = new Html5Qrcode("qr-reader");
                
                // Kamerayı başlat - arka kamerayı tercih et
                const cameraId = await getBackCameraId();
                
                await html5QrcodeCamera.start(
                    cameraId,
                    {
                        fps: 10,
                        qrbox: function(viewfinderWidth, viewfinderHeight) {
                            const minEdgePercentage = isMobile ? 0.7 : 0.6;
                            const minEdgeSize = Math.min(viewfinderWidth, viewfinderHeight);
                            const qrboxSize = Math.floor(minEdgeSize * minEdgePercentage);
                            return {
                                width: qrboxSize,
                                height: qrboxSize
                            };
                        },
                        aspectRatio: 1.0
                    },
                    onScanSuccess,
                    onScanFailure
                );
            } catch (err) {
                // Hata durumunda varsayılan scanner'ı kullan
                if (!html5QrcodeScanner) {
                    html5QrcodeScanner = new Html5QrcodeScanner(
                        "qr-reader",
                        {
                            fps: 10,
                            qrbox: { width: 250, height: 250 },
                            aspectRatio: 1.0,
                            supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA]
                        },
                        false
                    );
                    html5QrcodeScanner.render(onScanSuccess, onScanFailure);
                }
            }
        }
    }
    
    // Arka kamerayı bul
    async function getBackCameraId() {
        try {
            const devices = await Html5Qrcode.getCameras();
            
            // Arka kamerayı bul (environment facingMode)
            for (let device of devices) {
                if (device.label && device.label.toLowerCase().includes('back')) {
                    return device.id;
                }
            }
            
            // Facing mode ile kontrol et
            for (let device of devices) {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { deviceId: { exact: device.id }, facingMode: 'environment' }
                });
                stream.getTracks().forEach(track => track.stop());
                return device.id;
            }
            
            // İlk kamerayı kullan
            return devices[0]?.id || null;
        } catch (err) {
            // Hata durumunda null döndür
            return null;
        }
    }
    
    // İlk yüklemede kamera tarayıcısını başlat
    initCameraScanner();
    
    // Dosya seçme
    const fileInput = document.getElementById('qr-file-input');
    const selectFileBtn = document.querySelector('.btn-select-file');
    
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                scanQRFromFile(file);
            }
        });
    }
    
    if (selectFileBtn) {
        selectFileBtn.addEventListener('click', function() {
            fileInput.click();
        });
    }
    
    // Manuel form gönderimi
    const manualForm = document.getElementById('manual-qr-form');
    if (manualForm) {
        manualForm.addEventListener('submit', function(e) {
            const qrInput = document.getElementById('qr-code-input');
            if (qrInput && qrInput.value.trim() === '') {
                e.preventDefault();
                alert('Lütfen bir QR kod numarası girin.');
                return false;
            }
        });
    }
    
    // QR kod input'una odaklan
    const qrInput = document.getElementById('qr-code-input');
    if (qrInput) {
        qrInput.focus();
    }
});

// Sayfa kapatılırken tarayıcıyı temizle
window.addEventListener('beforeunload', function() {
    if (html5QrcodeScanner) {
        html5QrcodeScanner.clear();
    }
    if (html5Qrcode) {
        html5Qrcode.clear();
    }
    if (html5QrcodeCamera) {
        html5QrcodeCamera.stop().catch(() => {});
    }
});

