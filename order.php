<?php
require_once 'core/App.php';
$app = App::init();

// Проверяем корзину
$cartItems = $app->getCartItems();
if (empty($cartItems)) {
    $app->redirect(url('cart.php'));
}

$cartCount = $app->getCartCount();
$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => $app->sanitize($_POST['name'] ?? ''),
        'email' => $app->sanitize($_POST['email'] ?? ''),
        'phone' => $app->sanitize($_POST['phone'] ?? ''),
        'city' => $app->sanitize($_POST['city'] ?? ''),
        'address' => $app->sanitize($_POST['address'] ?? ''),
        'promo' => $app->sanitize($_POST['promo'] ?? ''),
        'telegram' => $app->sanitize($_POST['telegram'] ?? ''),
        'items' => $cartItems
    ];
    
    // Валидация
    $errors = [];
    if (empty($data['name'])) $errors[] = 'Введите ФИО';
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Введите корректный Email';
    if (empty($data['phone'])) $errors[] = 'Введите номер телефона';
    if (empty($data['city'])) $errors[] = 'Введите город';
    if (empty($data['address'])) $errors[] = 'Введите адрес доставки';
    if (empty($data['telegram'])) $errors[] = 'Введите username Telegram';
    
    if (empty($errors)) {
        // Отправляем в Telegram
        $success = $app->sendToTelegram($data);
        
        if ($success) {
            // Очищаем корзину
            $sessionId = $_SESSION['session_id'] ?? session_id();
            $app->execute("DELETE FROM cart WHERE session_id = ?", [$sessionId]);
            
            // Сохраняем в базу данных
            $orderNumber = 'INZ-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), 7, 6));
            $app->execute("
                INSERT INTO orders (order_number, customer_name, customer_email, customer_phone, customer_address, total_amount, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ", [
                $orderNumber,
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['city'] . ', ' . $data['address'],
                $total,
                "Промокод: {$data['promo']}, Telegram: @{$data['telegram']}"
            ]);
            
            $orderId = $app->lastInsertId();
            
            // Сохраняем товары заказа
            foreach ($cartItems as $item) {
                $app->execute("
                    INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, subtotal)
                    VALUES (?, ?, ?, ?, ?, ?)
                ", [
                    $orderId,
                    $item['id'],
                    $item['name'],
                    $item['price'],
                    $item['quantity'],
                    $item['price'] * $item['quantity']
                ]);
            }
            
            // Редирект на страницу успеха
            $_SESSION['order_success'] = true;
            $_SESSION['order_number'] = $orderNumber;
            $app->redirect(url('order.php?success=1'));
        } else {
            $errors[] = 'Ошибка отправки заказа. Попробуйте позже.';
        }
    }
}

// Успешное оформление
if (isset($_GET['success']) && isset($_SESSION['order_success'])) {
    $orderNumber = $_SESSION['order_number'] ?? '';
    unset($_SESSION['order_success'], $_SESSION['order_number']);
    
    echo '<!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Заказ оформлен | ' . SITE_NAME . '</title>
        <link rel="stylesheet" href="' . asset('css/style.css') . '">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    </head>
    <body>
        <header class="header">
            <nav class="nav">
                <a href="' . url() . '" class="logo">INZZO</a>
            </nav>
        </header>
        
        <main class="section container" style="text-align: center; padding: 5rem 0;">
            <div class="animate" style="max-width: 600px; margin: 0 auto;">
                <div style="font-size: 4rem; margin-bottom: 2rem;"></div>
                <h1 style="font-size: 2.5rem; margin-bottom: 1rem; color: var(--primary);">Заказ оформлен!</h1>
                <p style="color: #666; margin-bottom: 2rem;">
                    Ваш заказ <strong>' . $app->sanitize($orderNumber) . '</strong> успешно принят.<br>
                    Наш менеджер свяжется с вами в Telegram в течение 15 минут.
                </p>
                <p style="margin-bottom: 2rem; color: #555;">
                    Спасибо за покупку в INZZO Sakura Collection!
                </p>
                <div style="display: flex; gap: 1rem; justify-content: center;">
                    <a href="' . url() . '" class="btn">На главную</a>
                    <a href="' . url('catalog.php') . '" class="btn btn-secondary">Продолжить покупки</a>
                </div>
            </div>
        </main>
    </body>
    </html>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформление заказа | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <header class="header">
        <nav class="nav">
            <a href="<?= url() ?>" class="logo">INZZO</a>
            <div class="nav-links">
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
        <h1 class="animate" style="font-size: 2.5rem; font-weight: 400; margin-bottom: 2rem; color: var(--text);">Оформление заказа</h1>
        
        <?php if (!empty($errors)): ?>
        <div class="notification notification-error show" style="position: relative; margin-bottom: 2rem; background: #f8d7da; color: #721c24; border-left-color: #dc3545;">
            <div style="font-size: 1.25rem; font-weight: bold;">✗</div>
            <div>
                <strong>Ошибки:</strong>
                <ul style="margin-top: 0.5rem; padding-left: 1rem;">
                    <?php foreach ($errors as $error): ?>
                    <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 1fr 400px; gap: 4rem;">
            <!-- Форма -->
            <div class="animate">
                <form method="POST" id="order-form">
                    <div class="form-group">
                        <label class="form-label">ФИО *</label>
                        <input type="text" name="name" class="form-input" required 
                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-input" required
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Номер телефона *</label>
                        <input type="tel" name="phone" class="form-input" required 
                               placeholder="+7 (999) 999-99-99"
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Ваш город *</label>
                        <input type="text" name="city" class="form-input" required
                               value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Адрес пункта выдачи CDEK/KAZPOST *</label>
                        <textarea name="address" class="form-textarea" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Промокод (если есть)</label>
                        <input type="text" name="promo" class="form-input"
                               value="<?= htmlspecialchars($_POST['promo'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">@username в Telegram *</label>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="color: #999;">@</span>
                            <input type="text" name="telegram" class="form-input" required
                                   placeholder="username"
                                   value="<?= htmlspecialchars($_POST['telegram'] ?? '') ?>">
                        </div>
                        <small style="color: #666; display: block; margin-top: 0.5rem;">
                            Наш менеджер свяжется с вами в Telegram для подтверждения заказа
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Комментарий к заказу</label>
                        <textarea name="comment" class="form-textarea"><?= htmlspecialchars($_POST['comment'] ?? '') ?></textarea>
                    </div>
                </form>
            </div>
            
            <!-- Итог -->
            <div style="position: sticky; top: 5rem; align-self: start;">
                <div style="
                    background: white;
                    border-radius: 12px;
                    padding: 2rem;
                    border: 1px solid var(--border);
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
                ">
                    <h2 style="font-size: 1.5rem; font-weight: 400; margin-bottom: 1.5rem; color: var(--text);">Ваш заказ</h2>
                    
                    <div style="max-height: 300px; overflow-y: auto; margin-bottom: 1rem;">
                        <?php foreach ($cartItems as $item): ?>
                        <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border);">
                            <div>
                                <div style="font-weight: 500; color: var(--text);"><?= $app->sanitize($item['name']) ?></div>
                                <div style="font-size: 0.875rem; color: #666;">×<?= $item['quantity'] ?></div>
                            </div>
                            <div style="font-weight: 500; color: var(--primary);"><?= $app->formatPrice($item['price'] * $item['quantity']) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; color: #555;">
                        <span>Товары (<?= array_sum(array_column($cartItems, 'quantity')) ?>)</span>
                        <span><?= $app->formatPrice($total) ?></span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; color: #555;">
                        <span>Доставка</span>
                        <span style="color: var(--success); font-weight: 500;">Бесплатно</span>
                    </div>
                    
                    <div style="height: 1px; background: var(--border); margin: 1.5rem 0;"></div>
                    
                    <div style="display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: 500; margin-bottom: 2rem; color: var(--text);">
                        <span>Общая сумма</span>
                        <span style="color: var(--primary);"><?= $app->formatPrice($total) ?></span>
                    </div>
                    
                    <button type="submit" form="order-form" class="btn" style="width: 100%;">
                        Подтвердить заказ
                    </button>
                    
                    <div style="text-align: center; margin-top: 1.5rem;">
                        <a href="<?= url('cart.php') ?>" style="color: #666; text-decoration: none; font-size: 0.875rem;">
                            ← Вернуться в корзину
                        </a>
                    </div>
                    
                    <div style="margin-top: 1.5rem; padding: 1rem; background: rgba(139, 0, 0, 0.05); border-radius: 8px;">
                        <div style="font-size: 0.875rem; color: var(--primary); font-weight: 500; margin-bottom: 0.5rem;">
                            Как работает заказ?
                        </div>
                        <div style="font-size: 0.75rem; color: #666; line-height: 1.5;">
                            1. Заполните форму<br>
                            2. Наш менеджер свяжется в Telegram<br>
                            3. Подтвердите заказ<br>
                            4. Получите на пункте выдачи
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Маска для телефона
        const phoneInput = document.querySelector('input[name="phone"]');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                let value = this.value.replace(/\D/g, '');
                if (value.length > 0) {
                    if (!value.startsWith('7')) {
                        value = '7' + value;
                    }
                    let formatted = '+7 (';
                    if (value.length > 1) formatted += value.substring(1, 4);
                    if (value.length > 4) formatted += ') ' + value.substring(4, 7);
                    if (value.length > 7) formatted += '-' + value.substring(7, 9);
                    if (value.length > 9) formatted += '-' + value.substring(9, 11);
                    this.value = formatted.substring(0, 18);
                }
            });
        }
        
        // Валидация формы
        const form = document.getElementById('order-form');
        form.addEventListener('submit', function(e) {
            const telegram = document.querySelector('input[name="telegram"]');
            if (telegram && telegram.value.startsWith('@')) {
                telegram.value = telegram.value.substring(1);
            }
            
            // Проверка Telegram
            if (telegram && !/^[a-zA-Z0-9_]{5,32}$/.test(telegram.value)) {
                e.preventDefault();
                alert('Введите корректный Telegram username (только латинские буквы, цифры и нижние подчеркивания)');
                telegram.focus();
                return;
            }
            
            // Показать загрузку
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Отправляем заказ...';
            }
        });
    });
    </script>
</body>
</html>