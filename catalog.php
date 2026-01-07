<?php
require_once 'core/App.php';
$app = App::init();

// Параметры
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;
$filter = $_GET['filter'] ?? '';
$search = trim($_GET['search'] ?? '');

// Построение запроса
$where = ['is_active = 1'];
$params = [];

if ($filter === 'new') {
    $where[] = 'is_new = 1';
}

if (!empty($search)) {
    $where[] = '(name LIKE ? OR description LIKE ?)';
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Получаем товары
$products = $app->query("
    SELECT SQL_CALC_FOUND_ROWS * FROM products 
    {$whereClause} 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
", array_merge($params, [$limit, $offset]))->fetchAll();

// Общее количество
$total = $app->query("SELECT FOUND_ROWS()")->fetchColumn();
$totalPages = ceil($total / $limit);

$cartCount = $app->getCartCount();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            <?= get_css_vars() ?>
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
                <a href="<?= url('catalog.php') ?>" class="nav-link active">Каталог</a>
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

    <main class="section container">
        <!-- Заголовок и поиск -->
        <div class="animate" style="margin-bottom: 3rem;">
            <h1 style="font-size: 2.5rem; font-weight: 300; margin-bottom: 1rem;">Каталог</h1>
            
            <!-- Поиск -->
            <form method="GET" style="margin-bottom: 1.5rem;">
                <input type="text" 
                       name="search" 
                       placeholder="Поиск товаров..." 
                       value="<?= $app->sanitize($search) ?>"
                       class="form-input"
                       style="width: 300px; display: inline-block;">
                <button type="submit" class="btn" style="margin-left: 0.5rem;">Найти</button>
                
                <?php if (!empty($search)): ?>
                <a href="?page=1" class="btn btn-secondary" style="margin-left: 0.5rem;">Сбросить</a>
                <?php endif; ?>
            </form>
            
            <!-- Фильтры -->
            <div style="display: flex; gap: 0.5rem; margin-bottom: 2rem;">
                <a href="?page=1" class="btn <?= empty($filter) ? '' : 'btn-secondary' ?>">Все</a>
                <a href="?filter=new&page=1" class="btn <?= $filter === 'new' ? '' : 'btn-secondary' ?>">Новинки</a>
            </div>
            
            <!-- Результаты поиска -->
            <?php if (!empty($search)): ?>
            <p style="color: #999;">
                Найдено товаров: <?= $total ?>
                <?php if (!empty($search)): ?>
                по запросу "<?= $app->sanitize($search) ?>"
                <?php endif; ?>
            </p>
            <?php endif; ?>
        </div>
        
        <!-- Товары -->
        <?php if (empty($products)): ?>
        <div class="animate" style="text-align: center; padding: 5rem 0;">
            <p style="font-size: 1.25rem; margin-bottom: 1rem;">Товары не найдены</p>
            <a href="?page=1" class="btn">Показать все товары</a>
        </div>
        <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $index => $product): ?>
            <div class="product-card animate" style="animation-delay: <?= $index * 0.05 ?>s;">
                <?php if ($product['is_new']): ?>
                <div class="product-badge">NEW</div>
                <?php endif; ?>
                
                <a href="<?= url('product.php') ?>?slug=<?= urlencode($product['slug']) ?>" style="text-decoration: none; color: inherit;">
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
                
                <div style="padding: 0 1.5rem 1.5rem;">
                    <button class="btn add-to-cart" 
                            data-id="<?= $product['id'] ?>"
                            style="width: 100%;">
                        Добавить в корзину
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Пагинация -->
        <?php if ($totalPages > 1): ?>
        <div class="animate" style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 3rem;">
            <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn btn-secondary">← Назад</a>
            <?php endif; ?>
            
            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            
            if ($start > 1) {
                echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '" class="btn btn-secondary">1</a>';
                if ($start > 2) echo '<span style="color: #999; padding: 0.5rem 1rem;">...</span>';
            }
            
            for ($i = $start; $i <= $end; $i++):
            ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                   class="btn <?= $i === $page ? '' : 'btn-secondary' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1): ?>
                <span style="color: #999; padding: 0.5rem 1rem;">...</span>
                <?php endif; ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" class="btn btn-secondary"><?= $totalPages ?></a>
            <?php endif; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn btn-secondary">Вперёд →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
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

    <script src="<?= asset('js/app.js') ?>"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Добавление в корзину
        document.querySelectorAll('.add-to-cart').forEach(btn => {
            btn.addEventListener('click', async function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const productId = this.dataset.id;
                
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
                            quantity: 1
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
        });
    });
    </script>
</body>
</html>