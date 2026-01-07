<?php
require_once 'core/App.php';
$app = App::init();

// Проверяем корзину
$cartItems = $app->getCartItems();
$cartCount = $app->getCartCount();

// Обработка действий с корзиной
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update':
                $cartId = (int)($_POST['cart_id'] ?? 0);
                $quantity = (int)($_POST['quantity'] ?? 1);
                
                if ($quantity <= 0) {
                    $app->execute("DELETE FROM cart WHERE id = ?", [$cartId]);
                } else {
                    $app->execute("UPDATE cart SET quantity = ? WHERE id = ?", [$quantity, $cartId]);
                }
                break;
                
            case 'remove':
                $cartId = (int)($_POST['cart_id'] ?? 0);
                $app->execute("DELETE FROM cart WHERE id = ?", [$cartId]);
                break;
                
            case 'clear':
                $sessionId = $_SESSION['session_id'] ?? session_id();
                $app->execute("DELETE FROM cart WHERE session_id = ?", [$sessionId]);
                break;
        }
        
        // Обновляем данные после изменения
        $cartItems = $app->getCartItems();
        $cartCount = $app->getCartCount();
    }
}

// Расчет общей суммы
$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            <?= get_css_vars() ?>
        }
        
        .cart-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .cart-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .cart-title {
            font-size: 2.5rem;
            font-weight: 300;
            margin-bottom: 1rem;
            color: var(--text);
        }
        
        .cart-empty {
            text-align: center;
            padding: 5rem 2rem;
        }
        
        .cart-empty-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }
        
        .cart-items {
            background: var(--surface);
            border-radius: 16px;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: var(--sakura-shadow);
        }
        
        .cart-item {
            display: grid;
            grid-template-columns: 120px 1fr auto;
            gap: 2rem;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            align-items: center;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .cart-item-image {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .cart-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .cart-item-info {
            flex: 1;
        }
        
        .cart-item-name {
            font-size: 1.2rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text);
        }
        
        .cart-item-price {
            color: var(--deep);
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .cart-item-stock {
            font-size: 0.9rem;
            color: var(--accent);
            margin-top: 0.5rem;
        }
        
        .cart-item-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .cart-summary {
            background: var(--surface);
            border-radius: 16px;
            padding: 2rem;
            border: 1px solid var(--border);
            margin-top: 2rem;
            box-shadow: var(--sakura-shadow);
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            color: var(--text);
        }
        
        .summary-total {
            font-size: 1.5rem;
            font-weight: 500;
            color: var(--deep);
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .quantity-btn {
            width: 36px;
            height: 36px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .quantity-btn:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        
        .quantity-input {
            width: 60px;
            height: 36px;
            text-align: center;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--surface);
            color: var(--text);
        }
        
        .remove-btn {
            background: transparent;
            border: none;
            color: var(--accent);
            cursor: pointer;
            font-size: 1.2rem;
            transition: color 0.3s;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }
        
        .remove-btn:hover {
            color: var(--error);
            background: rgba(229, 115, 115, 0.1);
        }
        
        .cart-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }
        
        .stock-warning {
            color: var(--error);
            font-size: 0.9rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .cart-item {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 1rem;
            }
            
            .cart-item-image {
                margin: 0 auto;
            }
            
            .cart-actions {
                flex-direction: column;
            }
            
            .cart-actions .btn {
                width: 100%;
            }
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
                <a href="<?= url('about.php') ?>" class="nav-link">О нас</a>
                <a href="<?= url('cart.php') ?>" class="nav-link active">
                    Корзина
                    <?php if ($cartCount > 0): ?>
                    <span class="cart-count"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </nav>
    </header>

    <main class="section container cart-container">
        <div class="cart-header animate">
            <h1 class="cart-title">Ваша корзина</h1>
            <p style="color: var(--accent);">Нежность ждет своего момента</p>
        </div>
        
        <?php if (empty($cartItems)): ?>
        <div class="cart-empty animate">
            <div class="cart-empty-icon">🛒</div>
            <h2 style="font-size: 1.8rem; margin-bottom: 1rem; color: var(--text);">Корзина пуста</h2>
            <p style="color: var(--accent); margin-bottom: 2rem; max-width: 500px; margin-left: auto; margin-right: auto;">
                Добавьте товары из нашей коллекции Sakura, чтобы они оказались здесь
            </p>
            <a href="<?= url('catalog.php') ?>" class="btn">Перейти в каталог</a>
        </div>
        <?php else: ?>
        <form method="POST" id="cart-form">
            <div class="cart-items animate">
                <?php foreach ($cartItems as $item): ?>
                <div class="cart-item">
                    <div class="cart-item-image">
                        <a href="<?= url('product.php') ?>?slug=<?= urlencode($item['slug']) ?>">
                            <img src="<?= asset('img/products/' . $app->sanitize($item['image'])) ?>" 
                                 alt="<?= $app->sanitize($item['name']) ?>"
                                 onerror="this.src='<?= asset('img/placeholder.jpg') ?>'">
                        </a>
                    </div>
                    
                    <div class="cart-item-info">
                        <h3 class="cart-item-name">
                            <a href="<?= url('product.php') ?>?slug=<?= urlencode($item['slug']) ?>" 
                               style="color: inherit; text-decoration: none;">
                                <?= $app->sanitize($item['name']) ?>
                            </a>
                        </h3>
                        <div class="cart-item-price">
                            <?= $app->formatPrice($item['price']) ?>
                        </div>
                        
                        <div class="cart-item-stock">
                            <?php if ($item['stock'] > 0): ?>
                                <span style="color: var(--success);">✓ В наличии</span>
                            <?php else: ?>
                                <span style="color: var(--error);">✗ Нет в наличии</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($item['stock'] > 0 && $item['stock'] < $item['quantity']): ?>
                        <div class="stock-warning">
                            ⚠️ Максимальное количество: <?= $item['stock'] ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="cart-item-actions">
                        <div class="quantity-control">
                            <button type="button" class="quantity-btn minus" data-id="<?= $item['cart_id'] ?>">−</button>
                            <input type="number" 
                                   name="quantity[<?= $item['cart_id'] ?>]" 
                                   class="quantity-input" 
                                   value="<?= $item['quantity'] ?>" 
                                   min="1" 
                                   max="<?= max($item['stock'], 1) ?>"
                                   data-id="<?= $item['cart_id'] ?>">
                            <button type="button" class="quantity-btn plus" data-id="<?= $item['cart_id'] ?>">+</button>
                        </div>
                        
                        <button type="submit" 
                                name="action" 
                                value="remove" 
                                class="remove-btn"
                                onclick="return confirm('Удалить товар из корзины?')"
                                style="background: none; border: none;">
                            🗑️
                        </button>
                        <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="cart-summary animate">
                <div class="summary-row">
                    <span>Товары (<?= array_sum(array_column($cartItems, 'quantity')) ?>)</span>
                    <span><?= $app->formatPrice($total) ?></span>
                </div>
                
                <div class="summary-row">
                    <span>Доставка</span>
                    <span style="color: var(--success); font-weight: 500;">Бесплатно</span>
                </div>
                
                <div class="summary-row summary-total">
                    <span>Общая сумма</span>
                    <span><?= $app->formatPrice($total) ?></span>
                </div>
                
                <div class="cart-actions">
                    <a href="<?= url('catalog.php') ?>" class="btn btn-secondary">Продолжить покупки</a>
                    <button type="submit" name="action" value="clear" class="btn btn-secondary"
                            onclick="return confirm('Очистить всю корзину?')">
                        Очистить корзину
                    </button>
                    <button type="submit" name="action" value="update" class="btn">Обновить корзину</button>
                    <a href="<?= url('order.php') ?>" class="btn" style="background: var(--deep); color: white;">
                        Оформить заказ →
                    </a>
                </div>
            </div>
        </form>
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
        document.querySelectorAll('.quantity-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const cartId = this.dataset.id;
                const input = document.querySelector(`.quantity-input[data-id="${cartId}"]`);
                const max = parseInt(input.max);
                const current = parseInt(input.value);
                
                if (this.classList.contains('minus') && current > 1) {
                    input.value = current - 1;
                } else if (this.classList.contains('plus') && current < max) {
                    input.value = current + 1;
                }
                
                // Подсвечиваем изменение
                input.style.boxShadow = '0 0 0 2px var(--primary)';
                setTimeout(() => {
                    input.style.boxShadow = '';
                }, 300);
            });
        });
        
        // Валидация ввода количества
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                const max = parseInt(this.max);
                const min = parseInt(this.min);
                let value = parseInt(this.value);
                
                if (isNaN(value)) value = min;
                if (value < min) value = min;
                if (value > max) value = max;
                
                this.value = value;
            });
        });
        
        // Предупреждение при удалении
        document.querySelectorAll('.remove-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Удалить товар из корзины?')) {
                    e.preventDefault();
                }
            });
        });
        
        // Анимация при обновлении
        const form = document.getElementById('cart-form');
        form.addEventListener('submit', function(e) {
            if (e.submitter && e.submitter.value === 'update') {
                const submitBtn = e.submitter;
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Обновляем...';
            }
        });
    });
    </script>
</body>
</html>