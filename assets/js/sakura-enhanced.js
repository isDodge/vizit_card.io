class SakuraEnhancer {
    constructor() {
        this.branches = [];
        this.observers = [];
        this.scrollPosition = 0;
        this.mousePosition = { x: 0, y: 0 };
        this.init();
    }
    
    init() {
        this.createBranches();
        this.setupObservers();
        this.setupEventListeners();
        this.animateBranches();
        console.log('🌸 Sakura Enhancer initialized');
    }
    
    createBranches() {
        // Основные веточки для героя
        this.createHeroBranches();
        
        // Случайные веточки для других секций
        this.createRandomBranches();
        
        // Фоновые веточки
        this.createBackgroundBranches();
    }
    
    createHeroBranches() {
        const heroSection = document.querySelector('.hero-section');
        if (!heroSection) return;
        
        // Левая веточка
        const leftBranch = this.createBranchElement('large', 'left-center');
        leftBranch.style.cssText = `
            left: -100px;
            top: 50%;
            transform: translateY(-50%) rotate(90deg);
            z-index: 2;
        `;
        heroSection.appendChild(leftBranch);
        this.branches.push(leftBranch);
        
        // Правая веточка
        const rightBranch = this.createBranchElement('large', 'right-center');
        rightBranch.style.cssText = `
            right: -100px;
            top: 50%;
            transform: translateY(-50%) rotate(-90deg);
            z-index: 2;
        `;
        heroSection.appendChild(rightBranch);
        this.branches.push(rightBranch);
        
        // Верхние декоративные веточки
        const topLeftBranch = this.createBranchElement('medium', 'top-left');
        topLeftBranch.style.cssText = `
            top: -50px;
            left: -50px;
            transform: rotate(45deg);
            opacity: 0.8;
        `;
        heroSection.appendChild(topLeftBranch);
        this.branches.push(topLeftBranch);
        
        const topRightBranch = this.createBranchElement('medium', 'top-right');
        topRightBranch.style.cssText = `
            top: -50px;
            right: -50px;
            transform: rotate(-45deg);
            opacity: 0.8;
        `;
        heroSection.appendChild(topRightBranch);
        this.branches.push(topRightBranch);
    }
    
    createRandomBranches() {
        // Создаем случайные веточки для секций
        const sections = document.querySelectorAll('.section:not(.hero-section)');
        sections.forEach((section, index) => {
            if (index % 2 === 0) {
                this.addBranchToSection(section, 'small', 'top-left');
            } else {
                this.addBranchToSection(section, 'small', 'bottom-right');
            }
            
            // Добавляем маленькие декоративные веточки
            if (index % 3 === 0) {
                this.addTinyBranch(section);
            }
        });
    }
    
    createBackgroundBranches() {
        // Создаем фоновые веточки для всей страницы
        for (let i = 0; i < 4; i++) {
            this.createFloatingBranch();
        }
    }
    
    createBranchElement(size, type) {
        const branch = document.createElement('div');
        branch.className = `sakura-branch sakura-branch-${size}`;
        
        let imageUrl = '/inzzo/assets/img/sakura-branch.png';
        switch(type) {
            case 'left-center':
                imageUrl = '/inzzo/assets/img/sakura-branch-left.png';
                break;
            case 'right-center':
                imageUrl = '/inzzo/assets/img/sakura-branch-right.png';
                break;
            case 'tiny':
                imageUrl = '/inzzo/assets/img/sakura-branch-small.png';
                break;
        }
        
        branch.innerHTML = `
            <img src="${imageUrl}" 
                 alt="Веточка сакуры"
                 loading="lazy"
                 onerror="this.onerror=null; this.src='/inzzo/assets/img/sakura-pattern.png'">
        `;
        
        // Добавляем индивидуальную анимацию
        const delay = Math.random() * 10;
        const duration = 20 + Math.random() * 20;
        branch.style.animationDelay = `${delay}s`;
        branch.style.animationDuration = `${duration}s`;
        
        return branch;
    }
    
    addBranchToSection(section, size, position) {
        const branch = this.createBranchElement(size, position);
        
        section.style.position = 'relative';
        section.appendChild(branch);
        
        // Позиционирование
        switch(position) {
            case 'top-left':
                branch.style.top = '-40px';
                branch.style.left = '-40px';
                branch.style.transform = 'rotate(45deg)';
                break;
            case 'top-right':
                branch.style.top = '-40px';
                branch.style.right = '-40px';
                branch.style.transform = 'rotate(-45deg)';
                break;
            case 'bottom-left':
                branch.style.bottom = '-40px';
                branch.style.left = '-40px';
                branch.style.transform = 'rotate(-45deg)';
                break;
            case 'bottom-right':
                branch.style.bottom = '-40px';
                branch.style.right = '-40px';
                branch.style.transform = 'rotate(45deg)';
                break;
        }
        
        this.branches.push(branch);
    }
    
    addTinyBranch(container) {
        const branch = this.createBranchElement('tiny', 'tiny');
        branch.classList.add('content-branch');
        
        // Случайное позиционирование
        const top = 20 + Math.random() * 60;
        const left = 10 + Math.random() * 80;
        
        branch.style.cssText = `
            position: absolute;
            top: ${top}%;
            left: ${left}%;
            z-index: 0;
            opacity: 0.25;
        `;
        
        container.appendChild(branch);
        this.branches.push(branch);
    }
    
    createFloatingBranch() {
        const branch = this.createBranchElement('small', 'tiny');
        branch.classList.add('floating-branch');
        
        // Позиционируем случайно по всему экрану
        const top = Math.random() * 100;
        const left = Math.random() * 100;
        const rotation = Math.random() * 360;
        
        branch.style.cssText = `
            position: fixed;
            top: ${top}vh;
            left: ${left}vw;
            transform: rotate(${rotation}deg);
            z-index: -1;
            opacity: 0.15;
            pointer-events: none;
        `;
        
        document.body.appendChild(branch);
        this.branches.push(branch);
    }
    
    setupObservers() {
        // Intersection Observer для анимации при появлении
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                const branches = entry.target.querySelectorAll('.sakura-branch');
                branches.forEach(branch => {
                    if (entry.isIntersecting) {
                        branch.classList.add('scroll-visible');
                        branch.style.opacity = '0.95';
                    } else {
                        branch.classList.remove('scroll-visible');
                        branch.style.opacity = '0.7';
                    }
                });
            });
        }, {
            threshold: 0.1,
            rootMargin: '50px'
        });
        
        // Наблюдаем за всеми секциями с веточками
        document.querySelectorAll('.section, .section-with-branches').forEach(section => {
            observer.observe(section);
        });
        
        this.observers.push(observer);
    }
    
    setupEventListeners() {
        // Параллакс эффект при скролле
        window.addEventListener('scroll', () => {
            this.scrollPosition = window.pageYOffset;
            this.updateParallax();
        });
        
        // Эффект при движении мыши
        document.addEventListener('mousemove', (e) => {
            this.mousePosition.x = e.clientX;
            this.mousePosition.y = e.clientY;
            this.updateMouseEffects();
        });
        
        // Эффект при наведении на веточки
        document.addEventListener('mouseover', (e) => {
            if (e.target.closest('.sakura-branch')) {
                const branch = e.target.closest('.sakura-branch');
                this.enhanceBranch(branch);
            }
        });
        
        document.addEventListener('mouseout', (e) => {
            if (e.target.closest('.sakura-branch')) {
                const branch = e.target.closest('.sakura-branch');
                this.resetBranch(branch);
            }
        });
        
        // Ресайз окна
        window.addEventListener('resize', () => {
            this.handleResize();
        });
    }
    
    updateParallax() {
        const speed = 0.3;
        this.branches.forEach((branch, index) => {
            const yPos = -(this.scrollPosition * speed * (0.5 + index * 0.1));
            const currentTransform = branch.style.transform;
            const rotationMatch = currentTransform.match(/rotate\(([^)]+)\)/);
            
            if (rotationMatch) {
                branch.style.transform = `translateY(${yPos}px) rotate(${rotationMatch[1]})`;
            }
        });
    }
    
    updateMouseEffects() {
        this.branches.forEach(branch => {
            const rect = branch.getBoundingClientRect();
            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top + rect.height / 2;
            const distance = Math.sqrt(
                Math.pow(this.mousePosition.x - centerX, 2) + 
                Math.pow(this.mousePosition.y - centerY, 2)
            );
            
            if (distance < 250) {
                const intensity = 1 - (distance / 250);
                this.applyMouseEffect(branch, intensity);
            }
        });
    }
    
    applyMouseEffect(branch, intensity) {
        // Мягкий эффект при приближении мыши
        const scale = 1 + intensity * 0.1;
        const brightness = 1.35 + intensity * 0.15;
        
        branch.style.transform = `${branch.style.transform} scale(${scale})`;
        branch.style.filter = `
            drop-shadow(0 ${6 + intensity * 4}px ${12 + intensity * 8}px rgba(232, 180, 184, ${0.7 + intensity * 0.2}))
            drop-shadow(0 ${12 + intensity * 8}px ${24 + intensity * 16}px rgba(166, 124, 124, ${0.5 + intensity * 0.2}))
            brightness(${brightness})
            contrast(1.25)
        `;
    }
    
    enhanceBranch(branch) {
        // Сохраняем исходные значения
        if (!branch.dataset.originalTransform) {
            branch.dataset.originalTransform = branch.style.transform;
            branch.dataset.originalFilter = branch.style.filter;
        }
        
        // Усиливаем эффекты
        branch.style.transform = `${branch.dataset.originalTransform} scale(1.15)`;
        branch.style.filter = `
            drop-shadow(0 12px 24px rgba(232, 180, 184, 0.9))
            drop-shadow(0 24px 48px rgba(166, 124, 124, 0.7))
            drop-shadow(0 0 60px rgba(232, 180, 184, 0.5))
            brightness(1.6)
            contrast(1.4)
            saturate(1.3)
        `;
        branch.style.zIndex = '10';
        branch.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
    }
    
    resetBranch(branch) {
        if (branch.dataset.originalTransform) {
            branch.style.transform = branch.dataset.originalTransform;
            branch.style.filter = branch.dataset.originalFilter;
            branch.style.zIndex = '';
            branch.style.transition = 'all 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
        }
    }
    
    handleResize() {
        // Пересчитываем позиции при изменении размера окна
        this.branches.forEach(branch => {
            if (branch.classList.contains('floating-branch')) {
                // Обновляем позиции плавающих веточек
                const top = Math.random() * 100;
                const left = Math.random() * 100;
                branch.style.top = `${top}vh`;
                branch.style.left = `${left}vw`;
            }
        });
    }
    
    animateBranches() {
        // Дополнительная анимация для веточек
        let lastTime = 0;
        const animate = (timestamp) => {
            if (!lastTime) lastTime = timestamp;
            const deltaTime = timestamp - lastTime;
            
            this.branches.forEach((branch, index) => {
                // Легкое дрожание для реалистичности
                const waveIntensity = 0.5;
                const waveSpeed = 0.002;
                const waveOffset = index * 0.5;
                
                const waveX = Math.sin(timestamp * waveSpeed + waveOffset) * waveIntensity;
                const waveY = Math.cos(timestamp * waveSpeed * 0.7 + waveOffset) * waveIntensity;
                
                const currentTransform = branch.style.transform;
                const baseTransform = currentTransform.replace(/translate\([^)]*\)/g, '').trim();
                
                if (!branch.classList.contains('floating-branch')) {
                    branch.style.transform = `${baseTransform} translate(${waveX}px, ${waveY}px)`;
                }
            });
            
            lastTime = timestamp;
            requestAnimationFrame(animate);
        };
        
        requestAnimationFrame(animate);
    }
    
    // Публичные методы для внешнего использования
    addBranchToElement(element, options = {}) {
        const {
            size = 'medium',
            position = 'top-left',
            opacity = 0.8,
            customClass = ''
        } = options;
        
        const branch = this.createBranchElement(size, position);
        branch.style.opacity = opacity;
        
        if (customClass) {
            branch.classList.add(customClass);
        }
        
        element.style.position = 'relative';
        element.appendChild(branch);
        this.branches.push(branch);
        
        return branch;
    }
    
    removeAllBranches() {
        this.branches.forEach(branch => {
            if (branch.parentNode) {
                branch.parentNode.removeChild(branch);
            }
        });
        this.branches = [];
        
        this.observers.forEach(observer => {
            observer.disconnect();
        });
        this.observers = [];
    }
    
    // Создание SVG веточки как фолбэк
    static createSvgBranch(width = 200, height = 200) {
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('width', width);
        svg.setAttribute('height', height);
        svg.setAttribute('viewBox', '0 0 200 200');
        svg.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        
        const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
        
        const gradient = document.createElementNS('http://www.w3.org/2000/svg', 'linearGradient');
        gradient.setAttribute('id', 'branchGradient');
        gradient.setAttribute('x1', '0%');
        gradient.setAttribute('y1', '0%');
        gradient.setAttribute('x2', '100%');
        gradient.setAttribute('y2', '100%');
        
        const stop1 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
        stop1.setAttribute('offset', '0%');
        stop1.setAttribute('style', 'stop-color:#E8B4B8;stop-opacity:0.9');
        
        const stop2 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
        stop2.setAttribute('offset', '50%');
        stop2.setAttribute('style', 'stop-color:#F48FB1;stop-opacity:1');
        
        const stop3 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
        stop3.setAttribute('offset', '100%');
        stop3.setAttribute('style', 'stop-color:#D4A5A5;stop-opacity:0.8');
        
        gradient.appendChild(stop1);
        gradient.appendChild(stop2);
        gradient.appendChild(stop3);
        
        const filter = document.createElementNS('http://www.w3.org/2000/svg', 'filter');
        filter.setAttribute('id', 'branchShadow');
        filter.setAttribute('x', '-30%');
        filter.setAttribute('y', '-30%');
        filter.setAttribute('width', '160%');
        filter.setAttribute('height', '160%');
        
        const feDropShadow1 = document.createElementNS('http://www.w3.org/2000/svg', 'feDropShadow');
        feDropShadow1.setAttribute('dx', '0');
        feDropShadow1.setAttribute('dy', '6');
        feDropShadow1.setAttribute('stdDeviation', '12');
        feDropShadow1.setAttribute('flood-color', '#E8B4B8');
        feDropShadow1.setAttribute('flood-opacity', '0.5');
        
        const feDropShadow2 = document.createElementNS('http://www.w3.org/2000/svg', 'feDropShadow');
        feDropShadow2.setAttribute('dx', '0');
        feDropShadow2.setAttribute('dy', '12');
        feDropShadow2.setAttribute('stdDeviation', '24');
        feDropShadow2.setAttribute('flood-color', '#A67C7C');
        feDropShadow2.setAttribute('flood-opacity', '0.4');
        
        filter.appendChild(feDropShadow1);
        filter.appendChild(feDropShadow2);
        
        defs.appendChild(gradient);
        defs.appendChild(filter);
        svg.appendChild(defs);
        
        // Веточка
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', 'M50,150 C30,130 20,100 30,70 C40,40 70,30 100,40 C130,50 140,80 130,110 C120,140 90,150 70,130');
        path.setAttribute('stroke', 'url(#branchGradient)');
        path.setAttribute('stroke-width', '8');
        path.setAttribute('fill', 'none');
        path.setAttribute('stroke-linecap', 'round');
        path.setAttribute('filter', 'url(#branchShadow)');
        
        // Лепестки
        const petals = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        
        const petalsData = [
            { cx: 60, cy: 80, r: 12, fill: '#F48FB1', opacity: 0.9 },
            { cx: 90, cy: 70, r: 10, fill: '#F8BBD0', opacity: 0.85 },
            { cx: 110, cy: 90, r: 11, fill: '#E8B4B8', opacity: 0.95 },
            { cx: 80, cy: 110, r: 9, fill: '#F48FB1', opacity: 0.8 },
            { cx: 120, cy: 120, r: 10, fill: '#F8BBD0', opacity: 0.85 }
        ];
        
        petalsData.forEach(petal => {
            const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            circle.setAttribute('cx', petal.cx);
            circle.setAttribute('cy', petal.cy);
            circle.setAttribute('r', petal.r);
            circle.setAttribute('fill', petal.fill);
            circle.setAttribute('opacity', petal.opacity);
            petals.appendChild(circle);
        });
        
        svg.appendChild(path);
        svg.appendChild(petals);
        
        return svg;
    }
}

// Экспорт для использования в других файлах
if (typeof window !== 'undefined') {
    window.SakuraEnhancer = SakuraEnhancer;
}

// Автоматическая инициализация при загрузке DOM
document.addEventListener('DOMContentLoaded', () => {
    // Проверяем, нужны ли веточки на этой странице
    if (document.querySelector('.hero-section, .section-with-branches')) {
        const sakura = new SakuraEnhancer();
        
        // Экспортируем глобально для доступа из консоли
        window.sakura = sakura;
        
        // Добавляем обработчик для динамического контента
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.addedNodes.length) {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1 && 
                            (node.matches('.section') || node.matches('.section-with-branches'))) {
                            sakura.addBranchToElement(node);
                        }
                    });
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
});

// Предзагрузка изображений веточек
(function preloadSakuraImages() {
    const images = [
        '/inzzo/assets/img/sakura-branch.png',
        '/inzzo/assets/img/sakura-branch-left.png',
        '/inzzo/assets/img/sakura-branch-right.png',
        '/inzzo/assets/img/sakura-branch-small.png',
        '/inzzo/assets/img/sakura-pattern.png'
    ];
    
    images.forEach(src => {
        const img = new Image();
        img.src = src;
        img.onload = () => {
            console.log(`✅ Sakura image loaded: ${src}`);
        };
        img.onerror = () => {
            console.warn(`⚠️ Failed to load sakura image: ${src}`);
        };
    });
})();