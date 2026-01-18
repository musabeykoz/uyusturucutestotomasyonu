class SpriteAnimator {
    constructor(canvas, imagePath, frameWidth, frameHeight, frameCount, fps = 10) {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
        this.image = new Image();
        this.image.src = imagePath;
        this.frameWidth = frameWidth;
        this.frameHeight = frameHeight;
        this.frameCount = frameCount;
        this.currentFrame = 0;
        this.fps = fps;
        this.frameInterval = 1000 / fps;
        this.lastTime = 0;
        this.isPlaying = false;
        this.isPaused = false;
        this.animationId = null;
        
        this.image.onload = () => {
            this.setupCanvas();
            this.draw();
        };
    }
    
    setupCanvas() {
        // Canvas boyutunu frame boyutuna göre ayarla
        this.canvas.width = this.frameWidth;
        this.canvas.height = this.frameHeight;
    }
    
    draw() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        const sx = this.currentFrame * this.frameWidth;
        const sy = 0;
        
        this.ctx.drawImage(
            this.image,
            sx, sy, this.frameWidth, this.frameHeight,
            0, 0, this.frameWidth, this.frameHeight
        );
    }
    
    animate(currentTime) {
        if (!this.isPlaying || this.isPaused) return;
        
        if (currentTime - this.lastTime >= this.frameInterval) {
            this.currentFrame = (this.currentFrame + 1) % this.frameCount;
            this.draw();
            this.lastTime = currentTime;
            
            // FPS güncelleme
            if (window.updateFPS) {
                window.updateFPS(this.fps);
            }
            
            // Frame bilgisi güncelleme
            if (window.updateFrameInfo) {
                window.updateFrameInfo(this.currentFrame + 1, this.frameCount);
            }
        }
        
        this.animationId = requestAnimationFrame((time) => this.animate(time));
    }
    
    play() {
        if (this.isPlaying && !this.isPaused) return;
        
        this.isPlaying = true;
        this.isPaused = false;
        this.lastTime = performance.now();
        this.animate(this.lastTime);
    }
    
    pause() {
        this.isPaused = true;
    }
    
    resume() {
        if (this.isPlaying) {
            this.isPaused = false;
            this.lastTime = performance.now();
            this.animate(this.lastTime);
        }
    }
    
    stop() {
        this.isPlaying = false;
        this.isPaused = false;
        this.currentFrame = 0;
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
        }
        this.draw();
        
        if (window.updateFrameInfo) {
            window.updateFrameInfo(0, this.frameCount);
        }
    }
    
    setFPS(fps) {
        this.fps = fps;
        this.frameInterval = 1000 / fps;
    }
}

// Animasyon konfigürasyonları
const animations = {
    skeleton1: {
        idle: { frames: 4, fps: 8 },
        movement: { frames: 8, fps: 12 },
        attack: { frames: 6, fps: 15 },
        take_damage: { frames: 3, fps: 10 },
        death: { frames: 5, fps: 8 }
    },
    skeleton2: {
        idle: { frames: 4, fps: 8 },
        movement: { frames: 8, fps: 12 },
        attack: { frames: 6, fps: 15 },
        take_damage: { frames: 3, fps: 10 },
        death: { frames: 5, fps: 8 },
        death2: { frames: 5, fps: 8 }
    },
    vampire: {
        idle: { frames: 4, fps: 8 },
        movement: { frames: 8, fps: 12 },
        attack: { frames: 6, fps: 15 },
        take_damage: { frames: 3, fps: 10 },
        death: { frames: 5, fps: 8 }
    }
};

// Varsayılan frame boyutları (sprite sheet'lere göre ayarlanabilir)
const frameSizes = {
    skeleton1: { width: 64, height: 64 },
    skeleton2: { width: 64, height: 64 },
    vampire: { width: 64, height: 64 }
};

let currentAnimator = null;
let currentEnemy = 'skeleton1';
let currentAnimation = 'idle';

// DOM elementleri
const enemySelect = document.getElementById('enemy-select');
const animationSelect = document.getElementById('animation-select');
const playBtn = document.getElementById('play-btn');
const pauseBtn = document.getElementById('pause-btn');
const stopBtn = document.getElementById('stop-btn');
const canvas = document.getElementById('animation-canvas');
const fpsDisplay = document.getElementById('fps');
const currentFrameDisplay = document.getElementById('current-frame');
const totalFramesDisplay = document.getElementById('total-frames');
const gallery = document.getElementById('gallery');

// FPS güncelleme fonksiyonu
window.updateFPS = (fps) => {
    fpsDisplay.textContent = fps;
};

// Frame bilgisi güncelleme fonksiyonu
window.updateFrameInfo = (current, total) => {
    currentFrameDisplay.textContent = current;
    totalFramesDisplay.textContent = total;
};

// Animasyon yükleme fonksiyonu
function loadAnimation(enemy, animation) {
    // Önceki animasyonu durdur
    if (currentAnimator) {
        currentAnimator.stop();
    }
    
    // Dosya adını oluştur
    let filename = `enemies-${enemy}_${animation}.png`;
    
    // Özel durumlar için kontrol
    if (enemy === 'skeleton2' && animation === 'movement') {
        filename = 'enemies-skeleton2_movemen.png'; // Dosya adında typo var
    }
    
    const config = animations[enemy][animation];
    const frameSize = frameSizes[enemy];
    
    if (!config) {
        console.error(`Animasyon bulunamadı: ${enemy}.${animation}`);
        return;
    }
    
    // Yeni animatör oluştur
    currentAnimator = new SpriteAnimator(
        canvas,
        filename,
        frameSize.width,
        frameSize.height,
        config.frames,
        config.fps
    );
    
    totalFramesDisplay.textContent = config.frames;
    currentFrameDisplay.textContent = '0';
}

// Event listener'lar
enemySelect.addEventListener('change', (e) => {
    currentEnemy = e.target.value;
    // Mevcut animasyonu yükle
    loadAnimation(currentEnemy, currentAnimation);
});

animationSelect.addEventListener('change', (e) => {
    currentAnimation = e.target.value;
    loadAnimation(currentEnemy, currentAnimation);
});

playBtn.addEventListener('click', () => {
    if (currentAnimator) {
        if (currentAnimator.isPaused) {
            currentAnimator.resume();
        } else {
            currentAnimator.play();
        }
    }
});

pauseBtn.addEventListener('click', () => {
    if (currentAnimator) {
        currentAnimator.pause();
    }
});

stopBtn.addEventListener('click', () => {
    if (currentAnimator) {
        currentAnimator.stop();
    }
});

// Galeri oluşturma
function createGallery() {
    const enemies = ['skeleton1', 'skeleton2', 'vampire'];
    const animTypes = ['idle', 'movement', 'attack', 'take_damage', 'death'];
    
    enemies.forEach(enemy => {
        animTypes.forEach(anim => {
            // Özel durumlar
            if (enemy === 'skeleton2' && anim === 'movement') {
                return; // Bu animasyon için dosya adı farklı
            }
            
            const config = animations[enemy][anim];
            if (!config) return;
            
            const frameSize = frameSizes[enemy];
            let filename = `enemies-${enemy}_${anim}.png`;
            
            const galleryItem = document.createElement('div');
            galleryItem.className = 'gallery-item';
            
            const galleryCanvas = document.createElement('canvas');
            galleryCanvas.width = frameSize.width;
            galleryCanvas.height = frameSize.height;
            
            const img = new Image();
            img.onload = () => {
                const ctx = galleryCanvas.getContext('2d');
                // İlk frame'i göster
                ctx.drawImage(img, 0, 0, frameSize.width, frameSize.height, 0, 0, frameSize.width, frameSize.height);
            };
            img.src = filename;
            
            galleryItem.appendChild(galleryCanvas);
            
            const title = document.createElement('h3');
            title.textContent = `${enemy.charAt(0).toUpperCase() + enemy.slice(1)} - ${anim}`;
            galleryItem.appendChild(title);
            
            const info = document.createElement('p');
            info.textContent = `${config.frames} frame, ${config.fps} FPS`;
            galleryItem.appendChild(info);
            
            galleryItem.addEventListener('click', () => {
                enemySelect.value = enemy;
                animationSelect.value = anim;
                currentEnemy = enemy;
                currentAnimation = anim;
                loadAnimation(enemy, anim);
            });
            
            gallery.appendChild(galleryItem);
        });
    });
}

// Sayfa yüklendiğinde
window.addEventListener('DOMContentLoaded', () => {
    loadAnimation(currentEnemy, currentAnimation);
    createGallery();
});



