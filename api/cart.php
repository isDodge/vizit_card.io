<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Проверяем наличие сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Инициализируем приложение
require_once __DIR__ . '/../core/App.php';

try {
    $app = App::init();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка инициализации приложения'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'message' => 'Метод не разрешен'
    ]);
    exit;
}

// Получаем данные
$input = json_decode(file_get_contents('php://input'), true);
if (!$input && $_POST) {
    $input = $_POST;
}

if (!$input) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Некорректные данные'
    ]);
    exit;
}

$action = $input['action'] ?? '';
$sessionId = $_SESSION['session_id'] ?? session_id();

// Сохраняем session_id в сессии для дальнейшего использования
if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = $sessionId;
}

try {
    switch ($action) {
        case 'add':
            $productId = (int)($input['product_id'] ?? 0);
            $quantity = max(1, (int)($input['quantity'] ?? 1));
            
            if ($productId <= 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Неверный ID товара'
                ]);
                exit;
            }
            
            // Проверяем существование товара
            $product = $app->query(
                "SELECT id, name, stock, price FROM products WHERE id = ? AND is_active = 1", 
                [$productId]
            )->fetch();
            
            if (!$product) {
                http_response_code(404);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Товар не найден'
                ]);
                exit;
            }
            
            // Проверяем наличие на складе
            if ($product['stock'] !== -1 && $product['stock'] < $quantity) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Недостаточно товара на складе. Доступно: ' . $product['stock']
                ]);
                exit;
            }
            
            // Проверяем, есть ли товар уже в корзине
            $existing = $app->query(
                "SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ?", 
                [$sessionId, $productId]
            )->fetch();
            
            if ($existing) {
                $newQuantity = $existing['quantity'] + $quantity;
                if ($product['stock'] !== -1 && $newQuantity > $product['stock']) {
                    $newQuantity = $product['stock'];
                }
                $app->execute(
                    "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?", 
                    [$newQuantity, $existing['id']]
                );
            } else {
                $app->execute(
                    "INSERT INTO cart (session_id, product_id, quantity) VALUES (?, ?, ?)", 
                    [$sessionId, $productId, $quantity]
                );
            }
            
            $count = $app->getCartCount();
            echo json_encode([
                'success' => true, 
                'count' => $count,
                'message' => 'Товар добавлен в корзину'
            ]);
            break;
            
        case 'update':
            $cartId = (int)($input['cart_id'] ?? 0);
            $quantity = (int)($input['quantity'] ?? 0);
            
            if ($cartId <= 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Неверный ID корзины'
                ]);
                exit;
            }
            
            if ($quantity <= 0) {
                $app->execute(
                    "DELETE FROM cart WHERE id = ? AND session_id = ?", 
                    [$cartId, $sessionId]
                );
            } else {
                // Проверяем наличие товара
                $cartItem = $app->query(
                    "SELECT c.*, p.stock FROM cart c 
                     JOIN products p ON c.product_id = p.id 
                     WHERE c.id = ? AND c.session_id = ?", 
                    [$cartId, $sessionId]
                )->fetch();
                
                if (!$cartItem) {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Товар не найден в корзине'
                    ]);
                    exit;
                }
                
                if ($cartItem['stock'] !== -1 && $quantity > $cartItem['stock']) {
                    $quantity = $cartItem['stock'];
                }
                
                $app->execute(
                    "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ? AND session_id = ?", 
                    [$quantity, $cartId, $sessionId]
                );
            }
            
            $count = $app->getCartCount();
            echo json_encode([
                'success' => true, 
                'count' => $count,
                'message' => 'Корзина обновлена'
            ]);
            break;
            
        case 'remove':
            $cartId = (int)($input['cart_id'] ?? 0);
            
            if ($cartId <= 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Неверный ID корзины'
                ]);
                exit;
            }
            
            $app->execute(
                "DELETE FROM cart WHERE id = ? AND session_id = ?", 
                [$cartId, $sessionId]
            );
            
            $count = $app->getCartCount();
            echo json_encode([
                'success' => true, 
                'count' => $count,
                'message' => 'Товар удален из корзины'
            ]);
            break;
            
        case 'clear':
            $app->execute("DELETE FROM cart WHERE session_id = ?", [$sessionId]);
            echo json_encode([
                'success' => true, 
                'count' => 0,
                'message' => 'Корзина очищена'
            ]);
            break;
            
        case 'get':
            $cartItems = $app->getCartItems();
            echo json_encode([
                'success' => true, 
                'items' => $cartItems,
                'count' => count($cartItems)
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Неизвестное действие'
            ]);
    }
} catch (PDOException $e) {
    error_log("Database error in cart API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Внутренняя ошибка сервера'
    ]);
} catch (Exception $e) {
    error_log("Error in cart API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Ошибка при обработке запроса'
    ]);
}
?>