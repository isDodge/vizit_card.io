class InzzoApp {
    constructor() {
        this.init();
    }
    
    init() {
        this.initSakuraParticles();
        this.initCarousel();
        this.initCart();
        this.initAnimations();
        this.initNotifications();
        this.initJapaneseAesthetics();
    }
    
    // Японская эстетика
    initJapaneseAesthetics() {
        // Эффект при скролле для хедера
        window.addEventListener('scroll', () => {
            const header = document.querySelector('.header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
        
        // Плавное появление элементов
        this.initScrollAnimations();
        
        // Эффект для кнопок
        this.initButtonEffects();
    }
    
    initScrollAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    
                    // Добавляем задержку для дочерних элементов
                    if (entry.target.classList.contains('stagger')) {
                        const children = entry.target.children;
                        Array.from(children).forEach((child, index) => {
                            child.style.transitionDelay = `${index * 0.1}s`;
                        });
                    }
                }
            });
        }, { 
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });
        
        document.querySelectorAll('.animate').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.8s var(--ease-out), transform 0.8s var(--ease-out)';
            observer.observe(el);
        });
    }
    
    initButtonEffects() {
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                // Создаем волновой эффект
                const ripple = document.createElement('span');
                const rect = btn.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.cssText = `
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.7);
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                `;
                
                btn.appendChild(ripple);
                setTimeout(() => ripple.remove(), 600);
            });
        });
        
        // Добавляем стиль для ripple эффекта
        if (!document.getElementById('ripple-style')) {
            const style = document.createElement('style');
            style.id = 'ripple-style';
            style.textContent = `
                @keyframes ripple {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    // Частицы сакуры
    initSakuraParticles() {
        const container = document.getElementById('sakura-container');
        if (!container) return;
        
        // Очищаем существующие частицы
        container.innerHTML = '';
        
        // Создаем 20 частиц сакуры
        for (let i = 0; i < 20; i++) {
            this.createSakuraParticle(container);
        }
    }
    
    createSakuraParticle(container) {
        const petal = document.createElement('div');
        petal.className = 'sakura-particle';
        
        const size = 8 + Math.random() * 12;
        const delay = Math.random() * 20;
        const duration = 15 + Math.random() * 15;
        const opacity = 0.2 + Math.random() * 0.3;
        const color = Math.random() > 0.5 ? 'var(--primary)' : 'var(--accent)';
        
        petal.style.cssText = `
            position: fixed;
            width: ${size}px;
            height: ${size}px;
            background: ${color};
            border-radius: 50% 0 50% 50%;
            transform: rotate(45deg);
            left: ${Math.random() * 100}%;
            top: ${-size}px;
            animation: sakuraDrift ${duration}s linear infinite;
            animation-delay: ${delay}s;
            pointer-events: none;
            z-index: -1;
            opacity: ${opacity};
            filter: blur(${Math.random() * 0.8}px);
        `;
        
        container.appendChild(petal);
    }
    
    // Карусель
    initCarousel() {
        const carouselTrack = document.getElementById('carousel-track');
        if (!carouselTrack) return;
        
        let isPaused = false;
        
        // Пауза при наведении
        carouselTrack.addEventListener('mouseenter', () => {
            carouselTrack.style.animationPlayState = 'paused';
            isPaused = true;
        });
        
        carouselTrack.addEventListener('mouseleave', () => {
            if (!isPaused) {
                carouselTrack.style.animationPlayState = 'running';
            }
        });
        
        // Кнопки управления
        const pauseBtn = document.querySelector('[onclick*="pauseCarousel"]');
        const resumeBtn = document.querySelector('[onclick*="resumeCarousel"]');
        
        if (pauseBtn) {
            pauseBtn.addEventListener('click', () => {
                carouselTrack.style.animationPlayState = 'paused';
                isPaused = true;
            });
        }
        
        if (resumeBtn) {
            resumeBtn.addEventListener('click', () => {
                carouselTrack.style.animationPlayState = 'running';
                isPaused = false;
            });
        }
        
        // Анимация для элементов карусели
        const carouselItems = document.querySelectorAll('.carousel-item');
        carouselItems.forEach((item, index) => {
            item.style.animationDelay = `${index * 0.05}s`;
        });
    }
    
    // Корзина
    initCart() {
        document.addEventListener('click', async (e) => {
            if (e.target.closest('.add-to-cart')) {
                const btn = e.target.closest('.add-to-cart');
                await this.addToCart(btn);
            }
        });
    }
    
    async addToCart(btn) {
        const productId = btn.dataset.id;
        
        // Анимация кнопки
        btn.disabled = true;
        const originalHTML = btn.innerHTML;
        btn.innerHTML = `
            <span style="
                display: inline-block;
                width: 20px;
                height: 20px;
                border: 2px solid rgba(255,255,255,0.3);
                border-top-color: white;
                border-radius: 50%;
                animation: spin 0.6s linear infinite;
                margin-right: 8px;
            "></span>
            Добавляем...
        `;
        
        try {
            const response = await fetch('<?= url("api/cart.php") ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'add',
                    product_id: productId,
                    quantity: 1
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Эффект успешного добавления
                btn.innerHTML = '✓ Добавлено';
                btn.style.background = 'var(--success)';
                btn.style.borderColor = 'var(--success)';
                
                // Обновляем счетчик
                this.updateCartCount(result.count);
                
                // Показываем уведомление
                this.showNotification('🌸 Товар добавлен в корзину', 'success');
                
                // Возвращаем исходное состояние через 2 секунды
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.style.background = '';
                    btn.style.borderColor = '';
                    btn.disabled = false;
                }, 2000);
            } else {
                this.showNotification(result.message || 'Ошибка', 'error');
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Ошибка соединения', 'error');
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        }
    }
    
    updateCartCount(count) {
        const cartLinks = document.querySelectorAll('a[href*="cart"]');
        cartLinks.forEach(cartLink => {
            let counter = cartLink.querySelector('.cart-count');
            
            if (count > 0) {
                if (!counter) {
                    counter = document.createElement('span');
                    counter.className = 'cart-count';
                    cartLink.appendChild(counter);
                }
                counter.textContent = count;
                counter.classList.add('pulse');
                setTimeout(() => counter.classList.remove('pulse'), 300);
            } else if (counter) {
                counter.remove();
            }
        });
    }
    
    // Уведомления
    initNotifications() {
        window.showNotification = (message, type = 'info') => {
            this.showNotification(message, type);
        };
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        
        const icon = type === 'success' ? '🌸' : type === 'error' ? '❌' : '💡';
        
        notification.innerHTML = `
            <div class="notification-icon">${icon}</div>
            <div>${message}</div>
        `;
        
        document.body.appendChild(notification);
        
        // Показываем с небольшой задержкой
        setTimeout(() => notification.classList.add('show'), 10);
        
        // Автоматическое скрытие через 4 секунды
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 500);
        }, 4000);
    }
}

// Глобальный объект
window.Inzzo = new InzzoApp();

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', () => {
    // Добавляем стиль для спиннера
    if (!document.getElementById('spinner-style')) {
        const style = document.createElement('style');
        style.id = 'spinner-style';
        style.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            .sakura-particle:nth-child(odd) {
                animation-timing-function: ease-in-out;
            }
        `;
        document.head.appendChild(style);
    }
    
    // Анимация иконок сакуры
    document.querySelectorAll('.sakura-icon').forEach((icon, index) => {
        icon.style.animationDelay = `${index * 0.5}s`;
    });
    
    // Анимация цветовых элементов
    document.querySelectorAll('.color-item').forEach((item, index) => {
        item.style.animationDelay = `${index * 0.1}s`;
        item.style.transitionDelay = `${index * 0.1}s`;
    });
});