<?php
require_once 'config.php';

// Получаем данные
$data = json_decode(file_get_contents('php://input'), true);

// Если это вебхук от Telegram
if (isset($data['message'])) {
    $message = $data['message']['text'] ?? '';
    $chatId = $data['message']['chat']['id'];
    
    // Простой эхо-бот для тестирования
    if ($message === '/start') {
        sendTelegramMessage($chatId, "🌸 Добро пожаловать в INZZO Sakura Collection!\n\nЯ бот для обработки заказов. Новые заказы будут приходить сюда автоматически.");
    }
    
    exit;
}

// Функция отправки сообщения
function sendTelegramMessage($chatId, $text) {
    $token = TELEGRAM_BOT_TOKEN;
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    
    $postData = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}

// Для тестирования
if (isset($_GET['test'])) {
    $testMessage = "🌸 *ТЕСТОВОЕ СООБЩЕНИЕ*\n\nЭто тестовое сообщение от бота INZZO Sakura Collection.\n\nЕсли вы видите это сообщение, значит бот работает корректно!";
    
    $result = sendTelegramMessage(TELEGRAM_CHAT_ID, $testMessage);
    
    echo '<pre>';
    echo "Отправка тестового сообщения...\n";
    echo "Результат: " . $result;
    echo '</pre>';
}
?>