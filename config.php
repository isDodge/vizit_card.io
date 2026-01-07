<?php
// Базовые настройки
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', 'http://localhost/inzzo');
define('SITE_NAME', 'INZZO Sakura Collection');

// Настройки базы данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'inzzo_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Настройки приложения
define('CURRENCY', '₸');
define('ITEMS_PER_PAGE', 12);
define('SESSION_NAME', 'INZZO_SESS');
define('SESSION_LIFETIME', 86400);

// Эстетичная японская светлая палитра "Сакура"
define('PRIMARY_COLOR', '#E8B4B8');    // Нежно-розовый сакуры
define('SECONDARY_COLOR', '#F5E6E8');  // Светло-розовый фон
define('ACCENT_COLOR', '#D4A5A5');     // Акцентный пыльно-розовый
define('BACKGROUND_COLOR', '#FFF9FB'); // Фон с розовым оттенком
define('TEXT_COLOR', '#5D4037');       // Текст - теплый коричневый
define('BORDER_COLOR', '#F0D9DA');     // Границы - светлый розовый
define('SURFACE_COLOR', '#FFFFFF');    // Поверхность - чистый белый
define('SUBTLE_COLOR', '#FAF3F4');     // Едва уловимый розовый
define('DEEP_COLOR', '#A67C7C');       // Глубокий розовый для акцентов

// Telegram бот
define('TELEGRAM_BOT_TOKEN', 'YOUR_BOT_TOKEN_HERE');
define('TELEGRAM_CHAT_ID', 'YOUR_CHAT_ID_HERE');

// Пути
define('ASSETS_URL', BASE_URL . '/assets');
define('API_URL', BASE_URL . '/api');

// Функции-хелперы (ДОБАВЛЕНО)
function url($path = '') {
    $path = ltrim($path, '/');
    if (empty($path)) {
        return BASE_URL;
    }
    return BASE_URL . '/' . $path;
}

function asset($path) {
    return ASSETS_URL . '/' . ltrim($path, '/');
}

function redirect($url, $code = 302) {
    http_response_code($code);
    header("Location: {$url}");
    exit;
}

// Стилизация для эстетичной светлой темы "Сакура"
function get_css_vars() {
    return "
        --primary: " . PRIMARY_COLOR . ";
        --secondary: " . SECONDARY_COLOR . ";
        --accent: " . ACCENT_COLOR . ";
        --bg: " . BACKGROUND_COLOR . ";
        --text: " . TEXT_COLOR . ";
        --border: " . BORDER_COLOR . ";
        --surface: " . SURFACE_COLOR . ";
        --subtle: " . SUBTLE_COLOR . ";
        --deep: " . DEEP_COLOR . ";
        --success: #81C784;
        --error: #E57373;
        --warning: #FFB74D;
        --ease-out: cubic-bezier(0.4, 0, 0.2, 1);
        --sakura-gradient: linear-gradient(135deg, #FCE4EC 0%, #F8BBD0 50%, #F48FB1 100%);
        --sakura-shadow: 0 4px 20px rgba(232, 180, 184, 0.15);
    ";
}

// Функция для безопасного вывода
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>