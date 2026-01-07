<?php
declare(strict_types=1);

// Загружаем конфиг только если он еще не загружен
if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../config.php';
}

class App {
    private static $instance = null;
    private $db;
    private $config = [];
    
    public static function init(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public static function get(): self {
        if (self::$instance === null) {
            throw new RuntimeException('App не инициализирован');
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Стартуем сессию
        if (session_status() === PHP_SESSION_NONE) {
            $this->initSession();
            session_start();
        }
        
        $this->config = [
            'db_host' => DB_HOST,
            'db_name' => DB_NAME,
            'db_user' => DB_USER,
            'db_pass' => DB_PASS,
            'site_name' => SITE_NAME,
            'base_url' => BASE_URL,
            'currency' => CURRENCY,
            'items_per_page' => ITEMS_PER_PAGE,
            'primary_color' => PRIMARY_COLOR,
            'secondary_color' => SECONDARY_COLOR
        ];
        
        $this->initDatabase();
    }
    
    private function initSession(): void {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }
    
    private function initDatabase(): void {
        try {
            $this->db = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            if (ini_get('display_errors')) {
                die('Ошибка подключения к базе данных: ' . $e->getMessage());
            } else {
                error_log("Ошибка подключения к БД: " . $e->getMessage());
                die('Ошибка подключения к базе данных');
            }
        }
    }
    
    public function db(): PDO {
        return $this->db;
    }
    
    public function config(string $key, $default = null) {
        return $this->config[$key] ?? $default;
    }
    
    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function execute(string $sql, array $params = []): bool {
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function lastInsertId(): string {
        return $this->db->lastInsertId();
    }
    
    public function redirect(string $url): void {
        redirect($url);
    }
    
    public function json(array $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    public function sanitize(string $input): string {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    public function formatPrice(float $price): string {
        return number_format($price, 0, '', ' ') . CURRENCY;
    }
    
    public function generateSlug(string $string): string {
        $string = mb_strtolower($string, 'UTF-8');
        $string = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $string);
        $string = preg_replace('/[\s-]+/', '-', $string);
        $string = trim($string, '-');
        return $string;
    }
    
    public function validateEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public function getCartCount(): int {
        if (!isset($_SESSION['session_id'])) {
            return 0;
        }
        
        $sessionId = $_SESSION['session_id'];
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(quantity), 0) as total FROM cart WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $result = $stmt->fetch();
        return (int)($result['total'] ?? 0);
    }
    
    public function getCartItems() {
        if (!isset($_SESSION['session_id'])) {
            return [];
        }
        
        $sessionId = $_SESSION['session_id'];
        return $this->query("
            SELECT p.id, p.name, p.slug, p.price, p.image, p.stock, c.quantity, c.id as cart_id
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.session_id = ? AND p.is_active = 1
            ORDER BY c.added_at DESC
        ", [$sessionId])->fetchAll();
    }
    
    public function sendToTelegram(array $data): bool {
        $token = TELEGRAM_BOT_TOKEN;
        $chatId = TELEGRAM_CHAT_ID;
        
        if (!$token || !$chatId || $token === 'YOUR_BOT_TOKEN_HERE') {
            // Если токен не настроен, просто возвращаем true для тестов
            error_log('Telegram не настроен, заказ пропущен: ' . print_r($data, true));
            return true;
        }
        
        $message = "🌸 *НОВЫЙ ЗАКАЗ* 🌸\n\n";
        $message .= "👤 *Клиент:* " . $data['name'] . "\n";
        $message .= "📧 *Email:* " . $data['email'] . "\n";
        $message .= "📱 *Телефон:* " . $data['phone'] . "\n";
        $message .= "🏙️ *Город:* " . $data['city'] . "\n";
        $message .= "📍 *Адрес:* " . $data['address'] . "\n";
        $message .= "🎟️ *Промокод:* " . ($data['promo'] ?: 'не указан') . "\n";
        $message .= "📱 *Telegram:* @" . $data['telegram'] . "\n\n";
        
        $total = 0;
        $message .= "🛒 *Товары:*\n";
        foreach ($data['items'] as $item) {
            $itemTotal = $item['price'] * $item['quantity'];
            $total += $itemTotal;
            $message .= "• " . $item['name'] . " × " . $item['quantity'] . " = " . $this->formatPrice($itemTotal) . "\n";
        }
        
        $message .= "\n💰 *Итого:* " . $this->formatPrice($total) . "\n";
        $message .= "📅 *Дата:* " . date('d.m.Y H:i') . "\n";
        $message .= "🆔 *ID заказа:* " . uniqid('INZ-');
        
        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        $postData = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'Markdown'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $response !== false && $httpCode === 200;
    }
}
?>