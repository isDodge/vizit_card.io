<?php
require_once '../core/App.php';
require_once '../core/Auth.php';

session_start();

// Инициализируем приложение
try {
    $app = App::init();
} catch (Exception $e) {
    die('Ошибка инициализации приложения: ' . $e->getMessage());
}

// Инициализируем аутентификацию
$auth = new Auth($app->db());

// Проверяем авторизацию
if (!$auth->isLoggedIn()) {
    $app->redirect('login.php');
    exit;
}

// Получаем действие
$action = $_GET['action'] ?? 'dashboard';

// Обработка действий
switch ($action) {
    case 'products':
        $products = $app->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();
        $view = 'products';
        break;
        
    case 'orders':
        $status = $_GET['status'] ?? '';
        $where = [];
        $params = [];
        
        if ($status) {
            $where[] = "status = ?";
            $params[] = $status;
        }
        
        $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";
        $orders = $app->query(
            "SELECT * FROM orders {$whereClause} ORDER BY created_at DESC", 
            $params
        )->fetchAll();
        $view = 'orders';
        break;
        
    case 'edit':
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $product = $app->query("SELECT * FROM products WHERE id = ?", [$id])->fetch();
            if (!$product) {
                $app->redirect('?action=products');
            }
        }
        $view = 'edit';
        break;
        
    case 'save_product':
        // Обработка сохранения товара
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $original_price = !empty($_POST['original_price']) ? (float)$_POST['original_price'] : null;
        $stock = (int)($_POST['stock'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_new = isset($_POST['is_new']) ? 1 : 0;
        
        // Валидация
        $errors = [];
        if (empty($name)) {
            $errors[] = 'Название товара обязательно';
        }
        if ($price <= 0) {
            $errors[] = 'Цена должна быть больше 0';
        }
        
        if (empty($errors)) {
            // Генерация slug
            $slug = $app->generateSlug($name);
            
            // Обработка загрузки изображения
            $image = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../assets/img/products/';
                
                // Создаем папку если не существует
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
                $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
                $uploadFile = $uploadDir . $fileName;
                
                // Проверяем тип файла
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $fileType = mime_content_type($_FILES['image']['tmp_name']);
                
                if (in_array($fileType, $allowedTypes) && move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
                    $image = $fileName;
                    
                    // Если обновляем товар, удаляем старое изображение
                    if ($id > 0) {
                        $oldProduct = $app->query("SELECT image FROM products WHERE id = ?", [$id])->fetch();
                        if ($oldProduct['image'] && file_exists($uploadDir . $oldProduct['image'])) {
                            unlink($uploadDir . $oldProduct['image']);
                        }
                    }
                }
            } elseif ($id > 0 && empty($_FILES['image']['name'])) {
                // Получаем текущее изображение при редактировании
                $oldProduct = $app->query("SELECT image FROM products WHERE id = ?", [$id])->fetch();
                $image = $oldProduct['image'] ?? '';
            }
            
            if ($id > 0) {
                // Обновление существующего товара
                if ($image) {
                    $app->execute(
                        "UPDATE products SET name = ?, slug = ?, description = ?, price = ?, original_price = ?, stock = ?, image = ?, is_active = ?, is_new = ?, updated_at = NOW() WHERE id = ?",
                        [$name, $slug, $description, $price, $original_price, $stock, $image, $is_active, $is_new, $id]
                    );
                } else {
                    $app->execute(
                        "UPDATE products SET name = ?, slug = ?, description = ?, price = ?, original_price = ?, stock = ?, is_active = ?, is_new = ?, updated_at = NOW() WHERE id = ?",
                        [$name, $slug, $description, $price, $original_price, $stock, $is_active, $is_new, $id]
                    );
                }
                $_SESSION['success'] = 'Товар успешно обновлен';
            } else {
                // Создание нового товара
                $app->execute(
                    "INSERT INTO products (name, slug, description, price, original_price, stock, image, is_active, is_new) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$name, $slug, $description, $price, $original_price, $stock, $image, $is_active, $is_new]
                );
                $newId = $app->lastInsertId();
                $_SESSION['success'] = 'Товар успешно добавлен';
            }
            
            // Перенаправляем на страницу товаров
            $app->redirect('?action=products');
            exit;
        } else {
            // Сохраняем ошибки и возвращаем к форме
            $_SESSION['errors'] = $errors;
            $_SESSION['form_data'] = [
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'original_price' => $original_price,
                'stock' => $stock,
                'is_active' => $is_active,
                'is_new' => $is_new
            ];
            
            if ($id > 0) {
                $app->redirect('?action=edit&id=' . $id);
            } else {
                $app->redirect('?action=edit');
            }
            exit;
        }
        break;
        
    case 'delete':
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            // Удаляем товар
            $app->execute("DELETE FROM products WHERE id = ?", [$id]);
            // Также удаляем связанные записи из корзины
            $app->execute("DELETE FROM cart WHERE product_id = ?", [$id]);
        }
        $app->redirect('?action=products');
        exit;
        break;
        
    case 'view_order':
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $order = $app->query("SELECT * FROM orders WHERE id = ?", [$id])->fetch();
            if (!$order) {
                $app->redirect('?action=orders');
            }
            // Пытаемся получить товары заказа
            try {
                $orderItems = $app->query("
                    SELECT oi.*, p.name as product_name, p.image 
                    FROM order_items oi 
                    LEFT JOIN products p ON oi.product_id = p.id 
                    WHERE oi.order_id = ?
                ", [$id])->fetchAll();
            } catch (Exception $e) {
                // Если таблицы order_items нет, создаем пустой массив
                $orderItems = [];
            }
            $view = 'view_order';
        } else {
            $app->redirect('?action=orders');
        }
        break;
        
    case 'edit_order':
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $order = $app->query("SELECT * FROM orders WHERE id = ?", [$id])->fetch();
            if (!$order) {
                $app->redirect('?action=orders');
            }
            $view = 'edit_order';
        } else {
            $app->redirect('?action=orders');
        }
        break;
        
    case 'update_order_status':
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        if ($id > 0 && $status) {
            if (!empty($notes)) {
                $app->execute("UPDATE orders SET status = ?, notes = ? WHERE id = ?", [$status, $notes, $id]);
            } else {
                $app->execute("UPDATE orders SET status = ? WHERE id = ?", [$status, $id]);
            }
        }
        $app->redirect('?action=view_order&id=' . $id);
        exit;
        break;
        
    case 'dashboard':
    default:
        // Статистика для дашборда
        $stats = [
            'products' => $app->query("SELECT COUNT(*) as total FROM products")->fetchColumn(),
            'orders' => $app->query("SELECT COUNT(*) as total FROM orders")->fetchColumn(),
            'revenue' => $app->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status = 'delivered'")->fetchColumn(),
            'revenue_all' => $app->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status != 'cancelled'")->fetchColumn(),
            'low_stock' => $app->query("SELECT COUNT(*) as total FROM products WHERE stock <= 5 AND stock > 0")->fetchColumn(),
            'new_orders' => $app->query("SELECT COUNT(*) as total FROM orders WHERE status = 'new'")->fetchColumn(),
            'confirmed_orders' => $app->query("SELECT COUNT(*) as total FROM orders WHERE status = 'confirmed'")->fetchColumn(),
            'processing_orders' => $app->query("SELECT COUNT(*) as total FROM orders WHERE status = 'processing'")->fetchColumn(),
            'shipped_orders' => $app->query("SELECT COUNT(*) as total FROM orders WHERE status = 'shipped'")->fetchColumn(),
            'delivered_orders' => $app->query("SELECT COUNT(*) as total FROM orders WHERE status = 'delivered'")->fetchColumn(),
            'cancelled_orders' => $app->query("SELECT COUNT(*) as total FROM orders WHERE status = 'cancelled'")->fetchColumn()
        ];
        
        // Последние заказы
        $orders = $app->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5")->fetchAll();
        
        // Последние товары
        $products = $app->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 5")->fetchAll();
        
        $view = 'dashboard';
        break;
}

// Получаем сохраненные данные формы при ошибках
$formData = $_SESSION['form_data'] ?? null;
if ($formData && $view === 'edit') {
    if (isset($product)) {
        // Объединяем данные из БД с данными формы (приоритет у формы)
        $product = array_merge($product, $formData);
    } else {
        // Создаем объект продукта из данных формы
        $product = $formData;
        $product['id'] = 0;
    }
}

// Очищаем временные данные сессии
if (isset($_SESSION['form_data'])) unset($_SESSION['form_data']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель | INZZO Sakura</title>
    <style>
        :root {
            --bg: #0a0a0a;
            --surface: #1a1a1a;
            --text: #ffffff;
            --text-light: #cccccc;
            --text-muted: #888888;
            --accent: #E8B4B8;
            --accent-dark: #D4A5A5;
            --border: #333333;
            --success: #4CAF50;
            --error: #F44336;
            --warning: #FF9800;
            --info: #2196F3;
            --new: #2196F3;
            --confirmed: #9C27B0;
            --processing: #FF9800;
            --shipped: #00BCD4;
            --delivered: #4CAF50;
            --cancelled: #F44336;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
            font-size: 14px;
            line-height: 1.6;
            font-weight: 400;
        }
        
        .sidebar {
            width: 240px;
            background: var(--surface);
            border-right: 1px solid var(--border);
            padding: 1.5rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .main {
            margin-left: 240px;
            flex: 1;
            padding: 2rem;
            max-width: calc(100vw - 240px);
            overflow-x: auto;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 300;
            margin-bottom: 2rem;
            color: var(--accent);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            padding: 0.5rem 0;
        }
        
        .logo:hover {
            opacity: 0.9;
        }
        
        .logo::before {
            content: '🌸';
            font-size: 1.3rem;
        }
        
        .nav-section {
            margin-bottom: 2rem;
        }
        
        .nav-title {
            color: var(--text-light);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.75rem;
            padding-left: 0.5rem;
            font-weight: 500;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-light);
            text-decoration: none;
            padding: 0.75rem;
            margin-bottom: 0.25rem;
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: 400;
        }
        
        .nav-link:hover {
            background: rgba(232, 180, 184, 0.1);
            color: var(--text);
        }
        
        .nav-link.active {
            background: rgba(232, 180, 184, 0.15);
            color: var(--accent);
            font-weight: 500;
        }
        
        .user-info {
            margin-top: auto;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }
        
        .user-name {
            color: var(--text);
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .logout-link {
            color: var(--error);
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 400;
            padding: 0.5rem;
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .logout-link:hover {
            background: rgba(244, 67, 54, 0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            border-color: var(--accent);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 300;
            color: var(--accent);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--text-light);
            font-size: 0.9rem;
            font-weight: 400;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--surface);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border);
            margin-bottom: 2rem;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        th {
            background: rgba(232, 180, 184, 0.05);
            color: var(--text);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            color: var(--text-light);
            font-size: 0.9rem;
            font-weight: 400;
        }
        
        tr:hover {
            background: rgba(232, 180, 184, 0.03);
        }
        
        .btn {
            background: var(--accent);
            color: var(--bg);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-family: inherit;
            font-weight: 500;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .btn:hover {
            background: var(--accent-dark);
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: transparent;
            color: var(--text-light);
            border: 1px solid var(--border);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text);
        }
        
        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }
        
        .status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-new { 
            background: rgba(33, 150, 243, 0.1); 
            color: var(--new); 
            border: 1px solid rgba(33, 150, 243, 0.3);
        }
        .status-confirmed { 
            background: rgba(156, 39, 176, 0.1); 
            color: var(--confirmed); 
            border: 1px solid rgba(156, 39, 176, 0.3);
        }
        .status-processing { 
            background: rgba(255, 152, 0, 0.1); 
            color: var(--processing); 
            border: 1px solid rgba(255, 152, 0, 0.3);
        }
        .status-shipped { 
            background: rgba(0, 188, 212, 0.1); 
            color: var(--shipped); 
            border: 1px solid rgba(0, 188, 212, 0.3);
        }
        .status-delivered { 
            background: rgba(76, 175, 80, 0.1); 
            color: var(--delivered); 
            border: 1px solid rgba(76, 175, 80, 0.3);
        }
        .status-cancelled { 
            background: rgba(244, 67, 54, 0.1); 
            color: var(--cancelled); 
            border: 1px solid rgba(244, 67, 54, 0.3);
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background: rgba(232, 180, 184, 0.1);
            border: 1px solid rgba(232, 180, 184, 0.2);
            color: var(--text-light);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .action-btn:hover {
            background: rgba(232, 180, 184, 0.2);
            color: var(--accent);
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 1.8rem;
            font-weight: 300;
            color: var(--text);
        }
        
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .card-title {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: var(--text);
            font-weight: 500;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text);
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--text);
            font-family: inherit;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(232, 180, 184, 0.2);
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .form-check-input {
            width: 18px;
            height: 18px;
        }
        
        .form-check-label {
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid;
        }
        
        .alert-success {
            background: rgba(76, 175, 80, 0.1);
            border-color: rgba(76, 175, 80, 0.3);
            color: var(--success);
        }
        
        .alert-error {
            background: rgba(244, 67, 54, 0.1);
            border-color: rgba(244, 67, 54, 0.3);
            color: var(--error);
        }
        
        .alert-warning {
            background: rgba(255, 152, 0, 0.1);
            border-color: rgba(255, 152, 0, 0.3);
            color: var(--warning);
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-success {
            background: rgba(76, 175, 80, 0.1);
            color: var(--success);
        }
        
        .badge-error {
            background: rgba(244, 67, 54, 0.1);
            color: var(--error);
        }
        
        .badge-warning {
            background: rgba(255, 152, 0, 0.1);
            color: var(--warning);
        }
        
        .badge-info {
            background: rgba(33, 150, 243, 0.1);
            color: var(--info);
        }
        
        .badge-new {
            background: rgba(33, 150, 243, 0.1);
            color: var(--new);
        }
        
        .badge-confirmed {
            background: rgba(156, 39, 176, 0.1);
            color: var(--confirmed);
        }
        
        .badge-shipped {
            background: rgba(0, 188, 212, 0.1);
            color: var(--shipped);
        }
        
        @media (max-width: 1024px) {
            .sidebar {
                width: 200px;
            }
            .main {
                margin-left: 200px;
                max-width: calc(100vw - 200px);
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 60px;
                padding: 1rem;
            }
            .sidebar .nav-title,
            .sidebar .logo span,
            .sidebar .user-name span,
            .sidebar .logout-link span {
                display: none;
            }
            .main {
                margin-left: 60px;
                max-width: calc(100vw - 60px);
                padding: 1rem;
            }
            .nav-link {
                justify-content: center;
                padding: 0.75rem;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Сайдбар -->
    <div class="sidebar">
        <a href="?action=dashboard" class="logo">
            <span>INZZO Admin</span>
        </a>
        
        <div class="nav-section">
            <div class="nav-title">Основное</div>
            <a href="?action=dashboard" class="nav-link <?= $action === 'dashboard' ? 'active' : '' ?>">
                📊 <span>Дашборд</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-title">Каталог</div>
            <a href="?action=products" class="nav-link <?= $action === 'products' ? 'active' : '' ?>">
                📦 <span>Товары</span>
            </a>
            <a href="?action=edit" class="nav-link <?= $action === 'edit' ? 'active' : '' ?>">
                ➕ <span>Добавить товар</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-title">Продажи</div>
            <a href="?action=orders" class="nav-link <?= $action === 'orders' ? 'active' : '' ?>">
                📋 <span>Заказы</span>
            </a>
            <a href="?action=orders&status=new" class="nav-link">
                ⚡ <span>Новые</span>
            </a>
            <a href="?action=orders&status=processing" class="nav-link">
                🔄 <span>В обработке</span>
            </a>
        </div>
        
        <div class="user-info">
            <div class="user-name">
                <span><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Администратор') ?></span>
            </div>
            <a href="logout.php" class="logout-link">
                🚪 <span>Выйти</span>
            </a>
        </div>
    </div>
    
    <!-- Основной контент -->
    <div class="main">
        <?php if ($view === 'dashboard'): ?>
        <div class="page-header">
            <h1 class="page-title">Дашборд</h1>
            <div style="color: var(--text-light); font-size: 0.9rem; font-weight: 400;">
                <?= date('d.m.Y H:i') ?>
            </div>
        </div>
        
        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['products'] ?></div>
                <div class="stat-label">Товаров в каталоге</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['orders'] ?></div>
                <div class="stat-label">Всего заказов</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['revenue_all'], 0, '.', ' ') ?>₸</div>
                <div class="stat-label">Общая выручка (все заказы)</div>
                <?php if ($stats['revenue'] > 0): ?>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem;">
                    Доставленные: <?= number_format($stats['revenue'], 0, '.', ' ') ?>₸
                </div>
                <?php endif; ?>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['low_stock'] ?></div>
                <div class="stat-label">Мало остатков</div>
            </div>
        </div>
        
        <!-- Статистика по статусам заказов -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['new_orders'] ?></div>
                <div class="stat-label">
                    <span class="badge badge-new">Новые</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['confirmed_orders'] ?></div>
                <div class="stat-label">
                    <span class="badge badge-confirmed">Подтвержденные</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['processing_orders'] ?></div>
                <div class="stat-label">
                    <span class="badge badge-warning">В обработке</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['shipped_orders'] ?></div>
                <div class="stat-label">
                    <span class="badge badge-shipped">Отправленные</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['delivered_orders'] ?></div>
                <div class="stat-label">
                    <span class="badge badge-success">Доставленные</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['cancelled_orders'] ?></div>
                <div class="stat-label">
                    <span class="badge badge-error">Отмененные</span>
                </div>
            </div>
        </div>
        
        <!-- Последние заказы -->
        <div class="card">
            <h2 class="card-title">Последние заказы</h2>
            <table>
                <thead>
                    <tr>
                        <th>Номер</th>
                        <th>Клиент</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                        <th>Дата</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td style="font-weight: 500; color: var(--accent);"><?= htmlspecialchars($order['order_number']) ?></td>
                        <td>
                            <div style="font-weight: 500;"><?= htmlspecialchars($order['customer_name']) ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars($order['customer_email']) ?></div>
                        </td>
                        <td style="font-weight: 500;">
                            <?= number_format($order['total_amount'], 0, '.', ' ') ?>₸
                        </td>
                        <td>
                            <span class="status status-<?= $order['status'] ?>"><?= $order['status'] ?></span>
                        </td>
                        <td style="color: var(--text-muted); font-size: 0.8rem;">
                            <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="?action=view_order&id=<?= $order['id'] ?>" class="action-btn" title="Просмотр">
                                    👁️
                                </a>
                                <a href="?action=edit_order&id=<?= $order['id'] ?>" class="action-btn" title="Изменить статус">
                                    ✏️
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div style="text-align: right; margin-top: 1rem;">
                <a href="?action=orders" class="btn btn-secondary btn-small">Все заказы →</a>
            </div>
        </div>
        
        <!-- Последние товары -->
        <div class="card">
            <h2 class="card-title">Последние товары</h2>
            <table>
                <thead>
                    <tr>
                        <th>Название</th>
                        <th>Цена</th>
                        <th>Статус</th>
                        <th>Остаток</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td style="font-weight: 500;"><?= htmlspecialchars(mb_substr($product['name'], 0, 30)) ?></td>
                        <td style="color: var(--accent); font-weight: 500;">
                            <?= number_format($product['price'], 0, '.', ' ') ?>₸
                        </td>
                        <td>
                            <?php if ($product['is_active']): ?>
                                <span class="badge badge-success">✓ Активен</span>
                            <?php else: ?>
                                <span class="badge badge-error">✗ Скрыт</span>
                            <?php endif; ?>
                            <?php if ($product['is_new']): ?>
                                <span class="badge badge-warning" style="margin-left: 0.25rem;">NEW</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($product['stock'] >= 0): ?>
                                <?= $product['stock'] ?>
                                <?php if ($product['stock'] <= 5): ?>
                                    <span class="badge badge-error" style="margin-left: 0.25rem;">Мало</span>
                                <?php endif; ?>
                            <?php else: ?>
                                ∞
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="?action=edit&id=<?= $product['id'] ?>" class="action-btn" title="Редактировать">
                                    ✏️
                                </a>
                                <a href="?action=delete&id=<?= $product['id'] ?>" 
                                   class="action-btn" 
                                   title="Удалить"
                                   onclick="return confirm('Удалить товар «<?= htmlspecialchars($product['name']) ?>»?')"
                                   style="color: var(--error);">
                                    🗑️
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div style="text-align: right; margin-top: 1rem;">
                <a href="?action=products" class="btn btn-secondary btn-small">Все товары →</a>
            </div>
        </div>
        
        <?php elseif ($view === 'products'): ?>
        <div class="page-header">
            <h1 class="page-title">Товары</h1>
            <a href="?action=edit" class="btn">➕ Добавить товар</a>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success'] ?>
            <?php unset($_SESSION['success']); ?>
        </div>
        <?php endif; ?>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Изображение</th>
                    <th>Название</th>
                    <th>Цена</th>
                    <th>Статус</th>
                    <th>Остаток</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td style="color: var(--text-muted); font-size: 0.8rem;"><?= $product['id'] ?></td>
                    <td>
                        <?php if ($product['image']): ?>
                        <div style="width: 40px; height: 40px; border-radius: 6px; overflow: hidden;">
                            <img src="<?= $app->config('base_url') ?>/assets/img/products/<?= htmlspecialchars($product['image']) ?>" 
                                 alt="" 
                                 style="width: 100%; height: 100%; object-fit: cover;"
                                 onerror="this.style.display='none'; this.parentElement.innerHTML='🖼️';">
                        </div>
                        <?php else: ?>
                        🖼️
                        <?php endif; ?>
                    </td>
                    <td style="font-weight: 500;"><?= htmlspecialchars($product['name']) ?></td>
                    <td style="color: var(--accent); font-weight: 500;">
                        <?= number_format($product['price'], 0, '.', ' ') ?>₸
                    </td>
                    <td>
                        <?php if ($product['is_active']): ?>
                            <span class="badge badge-success">✓ Активен</span>
                        <?php else: ?>
                            <span class="badge badge-error">✗ Скрыт</span>
                        <?php endif; ?>
                        <?php if ($product['is_new']): ?>
                            <span class="badge badge-warning" style="margin-left: 0.25rem;">NEW</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($product['stock'] >= 0): ?>
                            <?= $product['stock'] ?>
                            <?php if ($product['stock'] <= 5): ?>
                                <span class="badge badge-error" style="margin-left: 0.25rem;">Мало</span>
                            <?php endif; ?>
                        <?php else: ?>
                            ∞
                        <?php endif; ?>
                    </td>
                    <td style="color: var(--text-muted); font-size: 0.8rem;">
                        <?= date('d.m.Y', strtotime($product['created_at'])) ?>
                    </td>
                    <td>
                        <div class="actions">
                            <a href="?action=edit&id=<?= $product['id'] ?>" class="action-btn" title="Редактировать">
                                ✏️
                            </a>
                            <a href="?action=delete&id=<?= $product['id'] ?>" 
                               class="action-btn" 
                               title="Удалить"
                               onclick="return confirm('Удалить товар «<?= htmlspecialchars($product['name']) ?>»?')"
                               style="color: var(--error);">
                                🗑️
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                        Нет товаров. <a href="?action=edit" style="color: var(--accent);">Добавить первый товар</a>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php elseif ($view === 'orders'): ?>
        <div class="page-header">
            <h1 class="page-title">Заказы</h1>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <a href="?action=orders" class="btn btn-secondary <?= !isset($_GET['status']) ? 'active' : '' ?>" 
                   style="<?= !isset($_GET['status']) ? 'background: var(--accent); color: var(--bg);' : '' ?>">
                    Все
                </a>
                <a href="?action=orders&status=new" class="btn btn-secondary <?= ($_GET['status'] ?? '') === 'new' ? 'active' : '' ?>"
                   style="<?= ($_GET['status'] ?? '') === 'new' ? 'background: var(--accent); color: var(--bg);' : '' ?>">
                    Новые
                </a>
                <a href="?action=orders&status=confirmed" class="btn btn-secondary <?= ($_GET['status'] ?? '') === 'confirmed' ? 'active' : '' ?>"
                   style="<?= ($_GET['status'] ?? '') === 'confirmed' ? 'background: var(--accent); color: var(--bg);' : '' ?>">
                    Подтвержденные
                </a>
                <a href="?action=orders&status=processing" class="btn btn-secondary <?= ($_GET['status'] ?? '') === 'processing' ? 'active' : '' ?>"
                   style="<?= ($_GET['status'] ?? '') === 'processing' ? 'background: var(--accent); color: var(--bg);' : '' ?>">
                    В обработке
                </a>
                <a href="?action=orders&status=shipped" class="btn btn-secondary <?= ($_GET['status'] ?? '') === 'shipped' ? 'active' : '' ?>"
                   style="<?= ($_GET['status'] ?? '') === 'shipped' ? 'background: var(--accent); color: var(--bg);' : '' ?>">
                    Отправленные
                </a>
                <a href="?action=orders&status=delivered" class="btn btn-secondary <?= ($_GET['status'] ?? '') === 'delivered' ? 'active' : '' ?>"
                   style="<?= ($_GET['status'] ?? '') === 'delivered' ? 'background: var(--accent); color: var(--bg);' : '' ?>">
                    Доставленные
                </a>
                <a href="?action=orders&status=cancelled" class="btn btn-secondary <?= ($_GET['status'] ?? '') === 'cancelled' ? 'active' : '' ?>"
                   style="<?= ($_GET['status'] ?? '') === 'cancelled' ? 'background: var(--accent); color: var(--bg);' : '' ?>">
                    Отмененные
                </a>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Номер</th>
                    <th>Клиент</th>
                    <th>Телефон</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td style="font-weight: 500; color: var(--accent);"><?= htmlspecialchars($order['order_number']) ?></td>
                    <td>
                        <div style="font-weight: 500;"><?= htmlspecialchars($order['customer_name']) ?></div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars($order['customer_email']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($order['customer_phone']) ?></td>
                    <td style="font-weight: 500;">
                        <?= number_format($order['total_amount'], 0, '.', ' ') ?>₸
                    </td>
                    <td>
                        <span class="status status-<?= $order['status'] ?>"><?= $order['status'] ?></span>
                    </td>
                    <td style="color: var(--text-muted); font-size: 0.8rem;">
                        <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                    </td>
                    <td>
                        <div class="actions">
                            <a href="?action=view_order&id=<?= $order['id'] ?>" class="action-btn" title="Просмотр">
                                👁️
                            </a>
                            <a href="?action=edit_order&id=<?= $order['id'] ?>" class="action-btn" title="Изменить статус">
                                ✏️
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                        Нет заказов по выбранному фильтру
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php elseif ($view === 'view_order'): ?>
        <div class="page-header">
            <h1 class="page-title">Заказ #<?= htmlspecialchars($order['order_number']) ?></h1>
            <div>
                <a href="?action=orders" class="btn btn-secondary">← Назад к заказам</a>
                <a href="?action=edit_order&id=<?= $order['id'] ?>" class="btn" style="margin-left: 0.5rem;">Изменить статус</a>
            </div>
        </div>

        <div class="card">
            <h3 class="card-title">Информация о заказе</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div>
                    <h4 style="margin-bottom: 1rem; color: var(--accent);">Данные клиента</h4>
                    <p><strong>Имя:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($order['customer_email']) ?></p>
                    <p><strong>Телефон:</strong> <?= htmlspecialchars($order['customer_phone']) ?></p>
                    <p><strong>Адрес:</strong> <?= htmlspecialchars($order['customer_address']) ?></p>
                    <?php if (!empty($order['notes'])): ?>
                    <p><strong>Примечания клиента:</strong> <?= htmlspecialchars($order['notes']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <h4 style="margin-bottom: 1rem; color: var(--accent);">Информация о заказе</h4>
                    <p><strong>Номер заказа:</strong> <?= htmlspecialchars($order['order_number']) ?></p>
                    <p><strong>Дата создания:</strong> <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></p>
                    <?php if ($order['updated_at']): ?>
                    <p><strong>Дата обновления:</strong> <?= date('d.m.Y H:i', strtotime($order['updated_at'])) ?></p>
                    <?php endif; ?>
                    <p><strong>Статус:</strong> <span class="status status-<?= $order['status'] ?>"><?= $order['status'] ?></span></p>
                    <p><strong>Общая сумма:</strong> <span style="font-weight: 500; color: var(--accent);"><?= number_format($order['total_amount'], 0, '.', ' ') ?>₸</span></p>
                </div>
            </div>
        </div>

        <?php if (!empty($orderItems)): ?>
        <div class="card">
            <h3 class="card-title">Товары в заказе</h3>
            <table>
                <thead>
                    <tr>
                        <th>Товар</th>
                        <th>Цена</th>
                        <th>Количество</th>
                        <th>Сумма</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $item): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <?php if (!empty($item['image'])): ?>
                                <div style="width: 50px; height: 50px; border-radius: 6px; overflow: hidden;">
                                    <img src="<?= $app->config('base_url') ?>/assets/img/products/<?= htmlspecialchars($item['image']) ?>" 
                                         alt="" 
                                         style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <?php endif; ?>
                                <span style="font-weight: 500;"><?= htmlspecialchars($item['product_name'] ?? 'Товар #' . $item['product_id']) ?></span>
                            </div>
                        </td>
                        <td><?= number_format($item['product_price'], 0, '.', ' ') ?>₸</td>
                        <td><?= $item['quantity'] ?></td>
                        <td style="font-weight: 500; color: var(--accent);">
                            <?= number_format($item['subtotal'], 0, '.', ' ') ?>₸
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="border-top: 2px solid var(--border);">
                        <td colspan="3" style="text-align: right; font-weight: 500; padding-top: 1.5rem;">Итого:</td>
                        <td style="font-weight: 600; color: var(--accent); font-size: 1.1rem; padding-top: 1.5rem;">
                            <?= number_format($order['total_amount'], 0, '.', ' ') ?>₸
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="card">
            <h3 class="card-title">Товары в заказе</h3>
            <p style="color: var(--text-muted);">Детали товаров не найдены. Возможно, таблица order_items не создана.</p>
            <p><strong>Общая сумма заказа:</strong> <?= number_format($order['total_amount'], 0, '.', ' ') ?>₸</p>
        </div>
        <?php endif; ?>

        <div class="card">
            <h3 class="card-title">Быстрое изменение статуса</h3>
            <form method="POST" action="?action=update_order_status" style="display: flex; gap: 1rem; align-items: flex-end;">
                <div style="flex: 1;">
                    <label class="form-label">Новый статус</label>
                    <select name="status" class="form-control">
                        <option value="new" <?= $order['status'] == 'new' ? 'selected' : '' ?>>Новый</option>
                        <option value="confirmed" <?= $order['status'] == 'confirmed' ? 'selected' : '' ?>>Подтвержден</option>
                        <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>В обработке</option>
                        <option value="shipped" <?= $order['status'] == 'shipped' ? 'selected' : '' ?>>Отправлен</option>
                        <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>Доставлен</option>
                        <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Отменен</option>
                    </select>
                </div>
                <div style="flex: 2;">
                    <label class="form-label">Примечание (необязательно)</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Добавьте примечание..."><?= htmlspecialchars($order['notes'] ?? '') ?></textarea>
                </div>
                <input type="hidden" name="id" value="<?= $order['id'] ?>">
                <button type="submit" class="btn">Обновить статус</button>
            </form>
        </div>

        <?php elseif ($view === 'edit_order'): ?>
        <div class="page-header">
            <h1 class="page-title">Изменение статуса заказа #<?= htmlspecialchars($order['order_number']) ?></h1>
            <a href="?action=view_order&id=<?= $order['id'] ?>" class="btn btn-secondary">← Назад к заказу</a>
        </div>

        <div class="card">
            <div style="margin-bottom: 2rem; padding: 1.5rem; background: rgba(232, 180, 184, 0.05); border-radius: 8px;">
                <p><strong>Номер заказа:</strong> <?= htmlspecialchars($order['order_number']) ?></p>
                <p><strong>Клиент:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                <p><strong>Телефон:</strong> <?= htmlspecialchars($order['customer_phone']) ?></p>
                <p><strong>Сумма:</strong> <span style="font-weight: 500; color: var(--accent);"><?= number_format($order['total_amount'], 0, '.', ' ') ?>₸</span></p>
                <p><strong>Текущий статус:</strong> <span class="status status-<?= $order['status'] ?>"><?= $order['status'] ?></span></p>
            </div>
            
            <form method="POST" action="?action=update_order_status">
                <input type="hidden" name="id" value="<?= $order['id'] ?>">
                
                <div class="form-group">
                    <label class="form-label">Новый статус</label>
                    <select name="status" class="form-control" required>
                        <option value="">Выберите статус...</option>
                        <option value="new" <?= $order['status'] == 'new' ? 'selected' : '' ?>>Новый</option>
                        <option value="confirmed" <?= $order['status'] == 'confirmed' ? 'selected' : '' ?>>Подтвержден</option>
                        <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>В обработке</option>
                        <option value="shipped" <?= $order['status'] == 'shipped' ? 'selected' : '' ?>>Отправлен</option>
                        <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>Доставлен</option>
                        <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Отменен</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Примечание (необязательно)</label>
                    <textarea name="notes" class="form-control" rows="4" placeholder="Добавьте примечание к заказу..."><?= htmlspecialchars($order['notes'] ?? '') ?></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn">💾 Сохранить изменения</button>
                    <a href="?action=view_order&id=<?= $order['id'] ?>" class="btn btn-secondary">Отмена</a>
                </div>
            </form>
        </div>
        
        <?php elseif ($view === 'edit'): ?>
        <div class="page-header">
            <h1 class="page-title">
                <?= isset($product['id']) && $product['id'] > 0 ? 'Редактирование товара' : 'Добавление товара' ?>
            </h1>
            <a href="?action=products" class="btn btn-secondary">← Назад</a>
        </div>
        
        <div class="card">
            <?php if (isset($_SESSION['errors'])): ?>
                <div class="alert alert-error">
                    <strong>Ошибки:</strong>
                    <ul style="margin-top: 0.5rem;">
                        <?php foreach ($_SESSION['errors'] as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php unset($_SESSION['errors']); ?>
            <?php endif; ?>
            
            <form method="POST" action="?action=save_product" enctype="multipart/form-data">
                <?php if (isset($product['id']) && $product['id'] > 0): ?>
                <input type="hidden" name="id" value="<?= $product['id'] ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label">Название товара *</label>
                    <input type="text" 
                           name="name" 
                           value="<?= isset($product['name']) ? htmlspecialchars($product['name']) : '' ?>" 
                           required
                           class="form-control"
                           placeholder="Например: Худи Sakura Pink">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label class="form-label">Цена (₸) *</label>
                        <input type="number" 
                               name="price" 
                               value="<?= isset($product['price']) ? $product['price'] : '' ?>" 
                               required
                               min="0"
                               step="0.01"
                               class="form-control"
                               placeholder="9999">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Оригинальная цена (₸)</label>
                        <input type="number" 
                               name="original_price" 
                               value="<?= isset($product['original_price']) ? $product['original_price'] : '' ?>" 
                               min="0"
                               step="0.01"
                               class="form-control"
                               placeholder="12999">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Описание</label>
                    <textarea name="description" 
                              rows="5"
                              class="form-control"
                              placeholder="Подробное описание товара..."><?= isset($product['description']) ? htmlspecialchars($product['description']) : '' ?></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label class="form-label">Остаток на складе</label>
                        <input type="number" 
                               name="stock" 
                               value="<?= isset($product['stock']) ? $product['stock'] : '0' ?>" 
                               min="-1"
                               class="form-control">
                        <small style="color: var(--text-muted); font-size: 0.8rem; display: block; margin-top: 0.25rem;">
                            -1 для неограниченного количества
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Изображение</label>
                        <input type="file" 
                               name="image"
                               accept="image/*"
                               class="form-control">
                        <?php if (isset($product['image']) && !empty($product['image'])): ?>
                        <div style="margin-top: 0.5rem;">
                            <div style="width: 80px; height: 80px; border-radius: 6px; overflow: hidden; border: 1px solid var(--border);">
                                <img src="<?= $app->config('base_url') ?>/assets/img/products/<?= htmlspecialchars($product['image']) ?>" 
                                     alt="" 
                                     style="width: 100%; height: 100%; object-fit: cover;"
                                     onerror="this.style.display='none'; this.parentElement.innerHTML='<div style=\'width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; background: var(--surface); color: var(--text-muted); border: 1px solid var(--border); border-radius: 6px;\'>🖼️</div>';">
                            </div>
                            <small style="color: var(--text-muted); font-size: 0.8rem; display: block; margin-top: 0.25rem;">
                                Текущее изображение
                            </small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div style="display: flex; gap: 2rem; margin-bottom: 2rem;">
                    <div>
                        <div class="form-check">
                            <input type="checkbox" 
                                   name="is_active" 
                                   value="1" 
                                   id="is_active"
                                   <?= (isset($product['is_active']) && $product['is_active']) || !isset($product['id']) ? 'checked' : '' ?>
                                   class="form-check-input">
                            <label for="is_active" class="form-check-label">Активный товар</label>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" 
                                   name="is_new" 
                                   value="1" 
                                   id="is_new"
                                   <?= isset($product['is_new']) && $product['is_new'] ? 'checked' : '' ?>
                                   class="form-check-input">
                            <label for="is_new" class="form-check-label">Новинка</label>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn">
                        <?= isset($product['id']) && $product['id'] > 0 ? '💾 Сохранить изменения' : '➕ Добавить товар' ?>
                    </button>
                    <a href="?action=products" class="btn btn-secondary">Отмена</a>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>