<?php
require_once 'core/App.php';
$app = App::init();

$cartCount = $app->getCartCount();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>О нас | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            <?= get_css_vars() ?>
        }
        
        .sakura-bg {
            background: linear-gradient(135deg, 
                rgba(90, 84, 152, 0.1) 0%, 
                rgba(155, 139, 199, 0.05) 50%, 
                rgba(232, 215, 255, 0.02) 100%);
            border-radius: 16px;
            padding: 2rem;
            margin: 2rem 0;
            border: 1px solid rgba(90, 84, 152, 0.2);
        }
        
        .color-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .color-item {
            height: 100px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            transition: transform 0.3s;
            border: 1px solid var(--border);
        }
        
        .color-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(90, 84, 152, 0.2);
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            padding: 0.75rem 0;
            position: relative;
            padding-left: 2.5rem;
            font-size: 1.1rem;
        }
        
        .feature-list li:before {
            content: '🌸';
            position: absolute;
            left: 0;
            font-size: 1.2rem;
        }
        
        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .contact-item {
            padding: 2rem;
            background: var(--surface);
            border-radius: 16px;
            text-align: center;
            border: 1px solid var(--border);
            transition: transform 0.3s;
        }
        
        .contact-item:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
        }
        
        .sakura-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            display: inline-block;
        }
        
        .philosophy-image {
            height: 300px;
            border-radius: 16px;
            background: linear-gradient(45deg, var(--primary), var(--secondary), var(--accent));
            background-size: 200% 200%;
            animation: gradientShift 10s ease infinite;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .production-image {
            height: 300px;
            border-radius: 16px;
            background: url('<?= asset('img/production.jpg') ?>') center/cover;
            position: relative;
        }
        
        .production-image:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(90, 84, 152, 0.3), rgba(155, 139, 199, 0.1));
            border-radius: 16px;
        }
    </style>
</head>
<body>
    <!-- Хедер -->
    <header class="header">
        <nav class="nav">
            <a href="<?= url() ?>" class="logo">INZZO</a>
            <div class="nav-links">
                <a href="<?= url() ?>" class="nav-link">Главная</a>
                <a href="<?= url('catalog.php') ?>" class="nav-link">Каталог</a>
                <a href="<?= url('about.php') ?>" class="nav-link active">О нас</a>
                <a href="<?= url('cart.php') ?>" class="nav-link">
                    Корзина
                    <?php if ($cartCount > 0): ?>
                    <span class="cart-count"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </nav>
    </header>

    <main class="section container">
        <!-- Герой -->
        <section class="animate" style="text-align: center; margin-bottom: 4rem;">
            <h1 style="font-size: 3rem; font-weight: 300; margin-bottom: 1rem;">
                <span class="gradient-text">INZZO Sakura Collection</span>
            </h1>
            <p style="font-size: 1.25rem; color: var(--accent); max-width: 600px; margin: 0 auto; line-height: 1.6;">
                Нежность цветущей сакуры в современном streetwear
            </p>
        </section>
        
        <!-- Философия -->
        <section class="animate sakura-bg" style="margin-bottom: 4rem;">
            <h2 style="font-size: 2rem; font-weight: 300; margin-bottom: 1.5rem;"> Философия</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: center;">
                <div>
                    <p style="line-height: 1.8; color: var(--text); margin-bottom: 1rem; font-weight: 400;">
                        INZZO — это диалог между эфемерной красотой цветущей сакуры и современным streetwear. 
                        Каждая вещь в Sakura Collection создана с уважением к японской философии моно-но аварэ: 
                        осознание прекрасного в мимолетности момента.
                    </p>
                    <p style="line-height: 1.8; color: var(--text); font-weight: 400;">
                        Мы верим, что истинная красота — в нежности и хрупкости. 
                        Каждый шов, каждый оттенок, каждый материал подчинен одной цели: 
                        создать одежду, которая напоминает о мимолетности и ценности каждого момента.
                    </p>
                </div>
                <div class="philosophy-image"></div>
            </div>
        </section>
        
        <!-- Материалы и качество -->
        <section class="animate sakura-bg" style="margin-bottom: 4rem;">
            <h2 style="font-size: 2rem; font-weight: 300; margin-bottom: 1.5rem;">✨ Материалы и качество</h2>
            <ul class="feature-list">
                <li>Премиальный японский хлопок высшей категории</li>
                <li>Натуральные растительные красители из лепестков сакуры</li>
                <li>Ручная вышивка и отделка каждого изделия</li>
                <li>Экологичная упаковка из переработанной бумаги с лепестками</li>
                <li>Многоуровневый контроль качества мастерами из Киото</li>
                <li>Биоразлагаемые материалы, безопасные для природы</li>
            </ul>
        </section>
        
        <!-- Производство -->
        <section class="animate" style="margin-bottom: 4rem;">
            <h2 style="font-size: 2rem; font-weight: 300; margin-bottom: 1.5rem;">🏭 Производство</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: center;">
                <div class="production-image"></div>
                <div>
                    <p style="line-height: 1.8; color: var(--text); margin-bottom: 1rem; font-weight: 400;">
                        Каждая вещь производится ограниченным тиражом в наших ателье в Киото и Токио. 
                        Мы сознательно ограничиваем количество, чтобы сохранить уникальность каждого изделия 
                        и обеспечить высочайшее качество ручной работы.
                    </p>
                    <p style="line-height: 1.8; color: var(--text); font-weight: 400;">
                        Наши мастера совмещают традиционные японские техники шитья кимоно с современными технологиями, 
                        создавая одежду, которая будет служить годами и станет семейной реликвией.
                    </p>
                </div>
            </div>
        </section>
        
        <!-- Контакты -->
        <section class="animate sakura-bg">
            <h2 style="font-size: 2rem; font-weight: 300; margin-bottom: 1.5rem;"> Контакты</h2>
            <div class="contact-grid">
                <div class="contact-item">
                    <div class="sakura-icon">📍</div>
                    <h3 style="margin-bottom: 0.5rem; color: var(--text); font-weight: 500;">Адрес</h3>
                    <p style="color: var(--text-light); font-weight: 400;">Киото • Токио • Москва • Париж</p>
                </div>
                <div class="contact-item">
                    <div class="sakura-icon">✉️</div>
                    <h3 style="margin-bottom: 0.5rem; color: var(--text); font-weight: 500;">Email</h3>
                    <a href="mailto:contact@inzzo.com" style="color: var(--primary); text-decoration: none; font-weight: 500;">contact@inzzo.com</a>
                </div>
                <div class="contact-item">
                    <div class="sakura-icon">📱</div>
                    <h3 style="margin-bottom: 0.5rem; color: var(--text); font-weight: 500;">Telegram</h3>
                    <a href="https://t.me/inzzo_sakura" style="color: var(--primary); text-decoration: none; font-weight: 500;">@inzzo_sakura</a>
                </div>
            </div>
        </section>
        
        <!-- Миссия -->
        <section class="animate" style="margin-top: 4rem; padding: 3rem; background: linear-gradient(135deg, rgba(90, 84, 152, 0.05), rgba(155, 139, 199, 0.02)); border-radius: 16px; text-align: center;">
            <div class="sakura-icon" style="font-size: 3rem;">🌸</div>
            <h2 style="font-size: 1.75rem; font-weight: 300; margin-bottom: 1rem; color: var(--accent);">Наша миссия</h2>
            <p style="color: var(--text); max-width: 800px; margin: 0 auto; font-size: 1.1rem; line-height: 1.8; font-weight: 400;">
                Мы создаем не просто одежду — мы создаем моменты. Моменты нежности, красоты и осознанности. 
                Каждое изделие INZZO — это напоминание о том, что прекрасное мимолетно, и именно поэтому бесценно.
            </p>
        </section>
    </main>

    <!-- Футер -->
    <footer class="footer">
        <p style="margin-bottom: 1rem; opacity: 0.7; font-weight: 400;">INZZO Sakura Collection © <?= date('Y') ?></p>
        <p style="color: var(--text-light); font-size: 0.9rem; font-weight: 400;">
            Киото • Токио • Москва • Париж<br>
            contact@inzzo.com • @inzzo_sakura
        </p>
        <!-- Скрытая ссылка в админку -->
        <a href="<?= url('admin/dashboard.php') ?>" style="
            display: block;
            margin-top: 2rem;
            color: var(--surface);
            text-decoration: none;
            font-size: 2rem;
            line-height: 1;
            opacity: 0.3;
        " target="_blank" title="Админ-панель">・</a>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Анимация градиента
        const style = document.createElement('style');
        style.textContent = `
            @keyframes gradientShift {
                0% { background-position: 0% 50%; }
                50% { background-position: 100% 50%; }
                100% { background-position: 0% 50%; }
            }
        `;
        document.head.appendChild(style);
        
        // Анимация появления
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate');
                }
            });
        }, { threshold: 0.1 });
        
        document.querySelectorAll('.animate').forEach(el => {
            el.style.opacity = '0';
            observer.observe(el);
        });
        
        // Анимация сакуры при наведении на цветовые блоки
        document.querySelectorAll('.color-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px) scale(1.05)';
                this.style.boxShadow = '0 15px 30px rgba(90, 84, 152, 0.3)';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
                this.style.boxShadow = '';
            });
        });
        
        // Анимация контактных карточек
        document.querySelectorAll('.contact-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
        
        // Создание частиц сакуры
        function createSakuraParticles() {
            const container = document.createElement('div');
            container.id = 'sakura-container';
            container.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                pointer-events: none;
                z-index: -1;
                overflow: hidden;
            `;
            document.body.appendChild(container);
            
            for (let i = 0; i < 10; i++) {
                const petal = document.createElement('div');
                petal.style.cssText = `
                    position: absolute;
                    width: ${10 + Math.random() * 15}px;
                    height: ${10 + Math.random() * 15}px;
                    background: rgba(232, 215, 255, ${0.2 + Math.random() * 0.3});
                    border-radius: 50% 0 50% 50%;
                    transform: rotate(45deg);
                    left: ${Math.random() * 100}%;
                    top: ${-20}px;
                    animation: sakura-fall ${10 + Math.random() * 10}s linear infinite;
                    animation-delay: ${Math.random() * 15}s;
                `;
                container.appendChild(petal);
            }
            
            // Добавляем анимацию для лепестков
            if (!document.getElementById('sakura-animation-style')) {
                const style = document.createElement('style');
                style.id = 'sakura-animation-style';
                style.textContent = `
                    @keyframes sakura-fall {
                        0% {
                            transform: translateY(0) rotate(0deg);
                            opacity: 0;
                        }
                        10% {
                            opacity: 0.5;
                        }
                        90% {
                            opacity: 0.5;
                        }
                        100% {
                            transform: translateY(100vh) rotate(360deg);
                            opacity: 0;
                        }
                    }
                `;
                document.head.appendChild(style);
            }
        }
        
        createSakuraParticles();
    });
    </script>
</body>
</html>