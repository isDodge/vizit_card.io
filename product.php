<?php
require_once 'core/App.php';
$app = App::init();

if (!isset($_GET['slug'])) {
    $app->redirect(url('catalog.php'));
}

$slug = $app->sanitize($_GET['slug']);

// Получаем товар
$product = $app->query("
    SELECT * FROM products 
    WHERE slug = ? AND is_active = 1
", [$slug])->fetch();

if (!$product) {
    $app->redirect(url('catalog.php'));
}

// Получаем похожие товары
$similar = $app->query("
    SELECT * FROM products 
    WHERE is_active = 1 AND id != ? 
    ORDER BY RAND() 
    LIMIT 4
", [$product['id']])->fetchAll();

$cartCount = $app->getCartCount();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $app->sanitize($product['name']) ?> | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            <?= get_css_vars() ?>
        }
        
        .image-zoom {
            overflow: hidden;
            cursor: zoom-in;
            position: relative;
            border-radius: 12px;
        }
        
        .image-zoom img {
            transition: transform 0.6s var(--ease-out);
            width: 100%;
            height: auto;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .quantity-btn {
            width: 40px;
            height: 40px;
            background: var(--surface);
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            transition: all 0.3s;
        }
        
        .quantity-btn:hover {
            background: var(--primary);
            border-color: var(--primary);
        }
        
        .quantity-input {
            width: 60px;
            height: 40px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text);
            text-align: center;
            font-size: 1rem;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <!-- Хедер -->
    <header class="header">
        <nav class="nav">
            <a href="<?= url() ?>" class="logo">INZZO</a>
            <div class="nav-links">
                <a href="<?= url('catalog.php') ?>" class="nav-link">Каталог</a>
                <a href="<?= url('cart.php') ?>" class="nav-link">
                    Корзина
                    <?php if ($cartCount > 0): ?>
                    <span class="cart-count"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </nav>
    </header>

    <!-- Товар -->
    <main class="section container">
        <div class="animate" style="display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: start;">
            <!-- Изображение -->
            <div class="image-zoom" style="position: sticky; top: 5rem;">
                <img src="<?= asset('img/products/' . $app->sanitize($product['image'])) ?>" 
                     alt="<?= $app->sanitize($product['name']) ?>"
                     onerror="this.src='<?= asset('img/placeholder.jpg') ?>'">
                
                <?php if ($product['is_new']): ?>
                <div class="product-badge" style="position: absolute; top: 1rem; left: 1rem;">NEW</div>
                <?php endif; ?>
            </div>
            
            <!-- Информация -->
            <div>
                <h1 style="font-size: 2.5rem; font-weight: 300; margin-bottom: 1rem; line-height: 1.2;">
                    <?= $app->sanitize($product['name']) ?>
                </h1>
                
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                    <span style="color: #999; font-size: 0.875rem;">
                        Артикул: INZ-<?= str_pad($product['id'], 6, '0', STR_PAD_LEFT) ?>
                    </span>
                    <span style="color: <?= $product['stock'] > 0 ? 'var(--success)' : 'var(--error)' ?>; font-size: 0.875rem;">
                        <?= $product['stock'] > 0 ? '✓ В наличии' : '✗ Нет в наличии' ?>
                    </span>
                </div>
                
                <div style="margin-bottom: 2rem;">
                    <div style="font-size: 2.5rem; font-weight: 500; color: var(--primary);">
                        <?= $app->formatPrice($product['price']) ?>
                    </div>
                    <?php if ($product['original_price'] && $product['original_price'] > $product['price']): ?>
                    <div style="font-size: 1.25rem; color: #999; text-decoration: line-through;">
                        <?= $app->formatPrice($product['original_price']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div style="margin-bottom: 2rem;">
                    <h3 style="font-size: 1.125rem; margin-bottom: 0.5rem;">Описание</h3>
                    <p style="line-height: 1.8; color: #ccc; white-space: pre-line;">
                        <?= $app->sanitize($product['description']) ?>
                    </p>
                </div>
                
                <?php if ($product['stock'] > 0): ?>
                <div style="margin-bottom: 2rem;">
                    <h3 style="font-size: 1.125rem; margin-bottom: 1rem;">Количество</h3>
                    <div class="quantity-control">
                        <button class="quantity-btn minus">−</button>
                        <input type="number" class="quantity-input" value="1" min="1" max="<?= $product['stock'] ?>" id="quantity">
                        <button class="quantity-btn plus">+</button>
                    </div>
                </div>
                
                <button class="btn add-to-cart" 
                        data-id="<?= $product['id'] ?>"
                        style="width: 100%; padding: 1rem; font-size: 1rem;">
                    Добавить в корзину
                </button>
                <?php else: ?>
                <div style="
                    background: rgba(239, 68, 68, 0.1);
                    border: 1px solid rgba(239, 68, 68, 0.3);
                    color: var(--error);
                    padding: 1rem;
                    border-radius: 4px;
                    margin-bottom: 2rem;
                ">
                    Товар временно отсутствует
                </div>
                <button disabled class="btn" style="
                    background: #666;
                    color: #999;
                    width: 100%;
                    padding: 1rem;
                    cursor: not-allowed;
                ">
                    Нет в наличии
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Похожие товары -->
        <?php if (!empty($similar)): ?>
        <section style="margin-top: 5rem;">
            <h2 style="font-size: 2rem; font-weight: 300; margin-bottom: 2rem;">Похожие товары</h2>
            <div class="product-grid">
                <?php foreach ($similar as $item): ?>
                <a href="<?= url('product.php') ?>?slug=<?= urlencode($item['slug']) ?>" 
                   class="product-card"
                   style="text-decoration: none; color: inherit;">
                    <div class="product-image">
                        <img src="<?= asset('img/products/' . $app->sanitize($item['image'])) ?>" 
                             alt="<?= $app->sanitize($item['name']) ?>"
                             onerror="this.src='<?= asset('img/placeholder.jpg') ?>'">
                    </div>
                    <div class="product-info">
                        <h3 class="product-name"><?= $app->sanitize($item['name']) ?></h3>
                        <div class="product-price"><?= $app->formatPrice($item['price']) ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <!-- Футер -->
    <footer class="footer">
        <p style="margin-bottom: 1rem; opacity: 0.7;">INZZO Sakura Collection © <?= date('Y') ?></p>
        <p style="color: #999; font-size: 0.9rem;">
            Токио • Москва • Париж<br>
            contact@inzzo.com
        </p>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Управление количеством
        const quantityInput = document.getElementById('quantity');
        const minusBtn = document.querySelector('.quantity-btn.minus');
        const plusBtn = document.querySelector('.quantity-btn.plus');
        const addToCartBtn = document.querySelector('.add-to-cart');
        
        if (minusBtn && plusBtn && quantityInput) {
            minusBtn.addEventListener('click', () => {
                let value = parseInt(quantityInput.value);
                if (value > parseInt(quantityInput.min)) {
                    quantityInput.value = value - 1;
                }
            });
            
            plusBtn.addEventListener('click', () => {
                let value = parseInt(quantityInput.value);
                if (value < parseInt(quantityInput.max)) {
                    quantityInput.value = value + 1;
                }
            });
            
            quantityInput.addEventListener('change', function() {
                let value = parseInt(this.value);
                const min = parseInt(this.min);
                const max = parseInt(this.max);
                
                if (isNaN(value) || value < min) value = min;
                if (value > max) value = max;
                
                this.value = value;
            });
        }
        
        // Добавление в корзину
        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', async function() {
                const productId = this.dataset.id;
                const quantity = quantityInput ? parseInt(quantityInput.value) : 1;
                
                // Анимация кнопки
                this.disabled = true;
                const originalText = this.textContent;
                this.textContent = 'Добавляем...';
                
                try {
                    const response = await fetch('<?= url("api/cart.php") ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'add',
                            product_id: productId,
                            quantity: quantity
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Успешное добавление
                        this.textContent = '✓ Добавлено';
                        this.style.background = 'var(--success)';
                        
                        // Показываем уведомление
                        if (window.Inzzo && window.Inzzo.showNotification) {
                            window.Inzzo.showNotification('Товар добавлен в корзину', 'success');
                        } else {
                            alert('Товар добавлен в корзину');
                        }
                        
                        setTimeout(() => {
                            this.textContent = originalText;
                            this.style.background = '';
                            this.disabled = false;
                        }, 2000);
                    } else {
                        alert(result.message || 'Ошибка');
                        this.textContent = originalText;
                        this.disabled = false;
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Ошибка соединения');
                    this.textContent = originalText;
                    this.disabled = false;
                }
            });
        }
        
        // Эффект увеличения изображения
        const imageZoom = document.querySelector('.image-zoom');
        if (imageZoom) {
            let isZoomed = false;
            
            imageZoom.addEventListener('click', function() {
                const img = this.querySelector('img');
                if (!isZoomed) {
                    img.style.transform = 'scale(2)';
                    img.style.cursor = 'zoom-out';
                    this.style.overflow = 'auto';
                } else {
                    img.style.transform = 'scale(1.05)';
                    img.style.cursor = 'zoom-in';
                    this.style.overflow = 'hidden';
                }
                isZoomed = !isZoomed;
            });
        }
    });
    </script>
</body>
</html>