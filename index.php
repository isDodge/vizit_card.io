<?php
require_once 'core/App.php';
$app = App::init();

// Получаем все новинки для карусели
$newProducts = $app->query("
    SELECT * FROM products 
    WHERE is_active = 1 AND is_new = 1
    ORDER BY created_at DESC
")->fetchAll();

$cartCount = $app->getCartCount();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <style>
        :root {
            <?= get_css_vars() ?>
        }
        
        /* Дополнительные стили для главной страницы */
        .hero {
            position: relative;
            overflow: hidden;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .hero-bg-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }
        
        .hero-bg-element {
            position: absolute;
            border-radius: 50%;
            background: var(--sakura-gradient);
            opacity: 0.08;
            filter: blur(60px);
            animation: float 20s infinite ease-in-out;
        }
        
        .hero-bg-element:nth-child(1) {
            width: 400px;
            height: 400px;
            top: -200px;
            left: -200px;
        }
        
        .hero-bg-element:nth-child(2) {
            width: 300px;
            height: 300px;
            bottom: -150px;
            right: -150px;
            animation-delay: -5s;
        }
        
        .hero-bg-element:nth-child(3) {
            width: 200px;
            height: 200px;
            top: 50%;
            left: 80%;
            animation-delay: -10s;
        }
        
        .section-title {
            font-size: 2.8rem;
            font-weight: 300;
            margin-bottom: 1rem;
            color: var(--text);
            letter-spacing: -0.5px;
        }
        
        .section-subtitle {
            color: var(--text-light);
            font-size: 1.1rem;
            font-weight: 400;
            margin-bottom: 3rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2.5rem;
            margin-top: 3rem;
        }
        
        .info-card {
            background: var(--surface);
            border-radius: 20px;
            padding: 2.5rem;
            border: 1px solid var(--border);
            transition: all 0.4s var(--ease-out);
            box-shadow: var(--soft-shadow);
        }
        
        .info-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--hover-shadow);
            border-color: var(--primary);
        }
        
        .info-icon {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: var(--primary);
        }
        
        .info-title {
            font-size: 1.4rem;
            font-weight: 500;
            margin-bottom: 1rem;
            color: var(--text);
        }
        
        .info-text {
            color: var(--text);
            line-height: 1.8;
            font-weight: 400;
        }
        
        .cta-section {
            background: var(--sakura-gradient);
            text-align: center;
            border-radius: 24px;
            position: relative;
            overflow: hidden;
        }
        
        .cta-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .cta-content {
            position: relative;
            z-index: 1;
        }
        
        .cta-title {
            font-size: 3rem;
            font-weight: 300;
            margin-bottom: 1.5rem;
            color: var(--deep);
        }
        
        .cta-subtitle {
            color: var(--text);
            font-size: 1.2rem;
            margin-bottom: 3rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            font-weight: 400;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <!-- Сакура частицы -->
    <div id="sakura-container"></div>
    
    <!-- Хедер -->
    <header class="header">
        <nav class="nav">
            <a href="<?= url() ?>" class="logo">INZZO</a>
            <div class="nav-links">
                <a href="<?= url() ?>" class="nav-link active">Главная</a>
                <a href="<?= url('catalog.php') ?>" class="nav-link">Каталог</a>
                <a href="<?= url('about.php') ?>" class="nav-link">О нас</a>
                <a href="<?= url('cart.php') ?>" class="nav-link">
                    Корзина
                    <?php if ($cartCount > 0): ?>
                    <span class="cart-count"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </nav>
    </header>

    <!-- Hero секция -->
    <section class="hero">
        <div class="hero-bg-elements">
            <div class="hero-bg-element"></div>
            <div class="hero-bg-element"></div>
            <div class="hero-bg-element"></div>
        </div>
        
        <div class="hero-content animate">
            <h1 class="hero-title">
                <span class="gradient-text">Sakura Collection</span>
            </h1>
            <p class="hero-subtitle">
                Нежность цветущей сакуры в современном дизайне.<br>
                Ограниченные тиражи, созданные с любовью в Японии.
            </p>
            <a href="#new-products" class="btn">Открыть коллекцию</a>
        </div>
    </section>

    <!-- Карусель новинок -->
    <section id="new-products" class="section carousel-section">
        <div class="container">
            <div class="carousel-header animate">
                <h2 class="section-title">
                    <span class="gradient-text">Новинки</span> Коллекции
                </h2>
                <p class="section-subtitle">
                    Эксклюзивные модели, созданные мастерами Киото
                </p>
            </div>
            
            <?php if (!empty($newProducts)): ?>
            <div class="carousel-container">
                <div class="carousel-track" id="carousel-track">
                    <!-- Первая группа -->
                    <div class="product-carousel">
                        <?php foreach ($newProducts as $index => $product): ?>
                        <div class="carousel-item">
                            <div class="product-card animate" style="animation-delay: <?= $index * 0.05 ?>s;">
                                <?php if ($product['is_new']): ?>
                                <div class="product-badge">NEW</div>
                                <?php endif; ?>
                                
                                <a href="<?= url('product.php') ?>?slug=<?= urlencode($product['slug']) ?>" 
                                   style="text-decoration: none; color: inherit;">
                                    <div class="product-image">
                                        <img src="<?= asset('img/products/' . $app->sanitize($product['image'])) ?>" 
                                             alt="<?= $app->sanitize($product['name']) ?>"
                                             onerror="this.src='<?= asset('img/placeholder.jpg') ?>'">
                                    </div>
                                    
                                    <div class="product-info">
                                        <h3 class="product-name"><?= $app->sanitize($product['name']) ?></h3>
                                        <div class="product-price"><?= $app->formatPrice($product['price']) ?></div>
                                    </div>
                                </a>
                                
                                <div style="padding: 0 2rem 2rem;">
                                    <button class="btn add-to-cart" 
                                            data-id="<?= $product['id'] ?>"
                                            style="width: 100%;">
                                        Добавить в корзину
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Дубликат для бесконечной анимации -->
                    <div class="product-carousel">
                        <?php foreach ($newProducts as $index => $product): ?>
                        <div class="carousel-item">
                            <div class="product-card" style="opacity: 0.9;">
                                <?php if ($product['is_new']): ?>
                                <div class="product-badge">NEW</div>
                                <?php endif; ?>
                                
                                <a href="<?= url('product.php') ?>?slug=<?= urlencode($product['slug']) ?>" 
                                   style="text-decoration: none; color: inherit;">
                                    <div class="product-image">
                                        <img src="<?= asset('img/products/' . $app->sanitize($product['image'])) ?>" 
                                             alt="<?= $app->sanitize($product['name']) ?>"
                                             onerror="this.src='<?= asset('img/placeholder.jpg') ?>'">
                                    </div>
                                    
                                    <div class="product-info">
                                        <h3 class="product-name"><?= $app->sanitize($product['name']) ?></h3>
                                        <div class="product-price"><?= $app->formatPrice($product['price']) ?></div>
                                    </div>
                                </a>
                                
                                <div style="padding: 0 2rem 2rem;">
                                    <button class="btn add-to-cart" 
                                            data-id="<?= $product['id'] ?>"
                                            style="width: 100%;">
                                        Добавить в корзину
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="carousel-controls">
                <button class="carousel-control" onclick="Inzzo.pauseCarousel()">⏸</button>
                <button class="carousel-control" onclick="Inzzo.resumeCarousel()">▶</button>
            </div>
            <?php else: ?>
            <div class="animate" style="text-align: center; padding: 4rem 0;">
                <div class="sakura-icon">🌸</div>
                <p style="color: var(--accent); font-size: 1.2rem; margin-top: 1rem; font-weight: 400;">
                    Новинки скоро появятся...
                </p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- О нас -->
    <section class="section container">
        <div class="animate" style="text-align: center; margin-bottom: 4rem;">
            <h2 class="section-title">
                <span class="gradient-text">Философия</span> INZZO
            </h2>
            <p class="section-subtitle">
                Гармония японской эстетики и современного дизайна
            </p>
        </div>
        
        <div class="info-grid">
            <!-- Философия -->
            <div class="info-card animate">
                <div class="sakura-icon">🌸</div>
                <h3 class="info-title">Wabi-Sabi</h3>
                <p class="info-text">
                    Красота в несовершенстве, простоте и естественности. 
                    Каждая вещь уникальна, как лепесток сакуры.
                </p>
            </div>
            
            <!-- Материалы -->
            <div class="info-card animate" style="animation-delay: 0.1s">
                <div class="sakura-icon">🌿</div>
                <h3 class="info-title">Натуральные материалы</h3>
                <p class="info-text">
                    Используем только натуральные ткани и красители. 
                    Экологичность и комфорт — наш приоритет.
                </p>
            </div>
            
            <!-- Производство -->
            <div class="info-card animate" style="animation-delay: 0.2s">
                <div class="sakura-icon">🎨</div>
                <h3 class="info-title">Ручная работа</h3>
                <p class="info-text">
                    Каждый элемент создается вручную мастерами Киото. 
                    Ограниченные тиражи гарантируют уникальность.
                </p>
            </div>
        </div>
    </section>

    <!-- Призыв к действию -->
    <section class="section container">
        <div class="cta-section">
            <div class="cta-content animate">
                <h2 class="cta-title">Прикоснитесь к красоте</h2>
                <p class="cta-subtitle">
                    Откройте для себя коллекцию, созданную для тех, кто ценит эстетику, 
                    качество и неповторимый стиль
                </p>
                <div style="display: flex; gap: 1.5rem; justify-content: center;">
                    <a href="<?= url('catalog.php') ?>" class="btn">Смотреть каталог</a>
                    <a href="<?= url('about.php') ?>" class="btn btn-secondary">Узнать больше</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Футер -->
    <footer class="footer">
        <a href="<?= url() ?>" class="logo">INZZO</a>
        <p style="margin: 2rem 0 1rem; font-weight: 400; color: var(--text-light);">
            Sakura Collection © <?= date('Y') ?>
        </p>
        <p style="color: var(--text-light); font-size: 0.95rem; line-height: 1.8; font-weight: 400;">
            Киото • Токио • Москва • Париж<br>
            contact@inzzo.com • @inzzo_sakura
        </p>
        
        <div class="delicate-line" style="margin: 3rem auto; width: 200px;"></div>
        
        <p style="color: var(--text-light); font-size: 0.85rem; opacity: 0.8; font-weight: 400;">
            Вдохновлено красотой цветущей сакуры
        </p>
        
        <!-- Скрытая ссылка в админку -->
        <a href="<?= url('admin/dashboard.php') ?>" style="
            display: block;
            margin-top: 3rem;
            color: var(--subtle);
            text-decoration: none;
            font-size: 2rem;
            line-height: 1;
            opacity: 0.3;
        " target="_blank" title="Админ-панель">・</a>
    </footer>

    <script src="<?= asset('js/app.js') ?>"></script>
    <script>
    // Дополнительные функции для карусели
    Inzzo.pauseCarousel = function() {
        const track = document.getElementById('carousel-track');
        if (track) {
            track.style.animationPlayState = 'paused';
        }
    };
    
    Inzzo.resumeCarousel = function() {
        const track = document.getElementById('carousel-track');
        if (track) {
            track.style.animationPlayState = 'running';
        }
    };
    
    // Инициализация при загрузке
    document.addEventListener('DOMContentLoaded', () => {
        // Плавный скролл для ссылок с якорями
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Анимация при наведении на карточки товаров
        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                const image = card.querySelector('.product-image img');
                if (image) {
                    image.style.transform = 'scale(1.05)';
                }
            });
            
            card.addEventListener('mouseleave', () => {
                const image = card.querySelector('.product-image img');
                if (image) {
                    image.style.transform = 'scale(1)';
                }
            });
        });
    });
    </script>
</body>
</html>