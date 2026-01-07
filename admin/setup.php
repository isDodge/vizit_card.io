<?php
require_once '../core/App.php';

$app = App::init();

// Создание таблиц если они не существуют
$app->execute("
    CREATE TABLE IF NOT EXISTS admins (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        last_login DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$app->execute("
    CREATE TABLE IF NOT EXISTS login_attempts (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL,
        attempts INT DEFAULT 0,
        last_attempt DATETIME,
        locked_until DATETIME,
        INDEX idx_username (username)
    )
");

// Проверяем, есть ли уже администраторы
$adminCount = $app->query("SELECT COUNT(*) as count FROM admins")->fetchColumn();

if ($adminCount == 0) {
    // Создаем администратора по умолчанию
    $username = 'admin';
    $password = 'admin123'; // Измените на свой пароль!
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    $app->execute("INSERT INTO admins (username, password_hash) VALUES (?, ?)", 
                  [$username, $passwordHash]);
    
    echo "✅ Администратор создан!<br>";
    echo "👤 Логин: <strong>admin</strong><br>";
    echo "🔑 Пароль: <strong>admin123</strong><br>";
    echo "<br>⚠️ <strong>ВАЖНО:</strong> Смените пароль после первого входа!<br>";
    echo "<br><a href='login.php'>Перейти к входу</a>";
} else {
    echo "❌ Администратор уже существует. Удалите этот файл.";
}

// Удаляем файл после использования
// unlink(__FILE__);
?>