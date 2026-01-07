<?php
require_once '../core/App.php';
require_once '../core/Auth.php';

session_start();

// Инициализируем приложение
$app = App::init();
$auth = new Auth($app->db());

// Если уже авторизован, перенаправляем в админку
if ($auth->isLoggedIn()) {
    $app->redirect('dashboard.php');
    exit;
}

$error = '';
$username = '';

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Заполните все поля';
    } else {
        if ($auth->login($username, $password)) {
            $app->redirect('dashboard.php');
            exit;
        } else {
            $error = 'Неверное имя пользователя или пароль';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админ-панель | INZZO Sakura</title>
    <style>
        :root {
            --bg: #0a0a0a;
            --surface: #1a1a1a;
            --text: #ffffff;
            --text-light: #cccccc;
            --accent: #E8B4B8;
            --border: #333333;
            --error: #F44336;
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            line-height: 1.6;
            font-weight: 400;
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
        }
        
        .login-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 3rem;
            text-align: center;
        }
        
        .logo {
            font-size: 2rem;
            font-weight: 300;
            margin-bottom: 2rem;
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }
        
        .logo::before {
            content: '🌸';
            font-size: 1.8rem;
        }
        
        .login-title {
            font-size: 1.5rem;
            font-weight: 400;
            margin-bottom: 2rem;
            color: var(--text);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-light);
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-family: inherit;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(232, 180, 184, 0.2);
        }
        
        .btn {
            width: 100%;
            padding: 1rem;
            background: var(--accent);
            color: var(--bg);
            border: none;
            border-radius: 8px;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1rem;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .error {
            background: rgba(244, 67, 54, 0.1);
            border: 1px solid rgba(244, 67, 54, 0.3);
            color: var(--error);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            text-align: left;
        }
        
        .back-link {
            display: block;
            margin-top: 2rem;
            color: var(--text-light);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: var(--accent);
        }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 2rem;
            }
            
            body {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">INZZO Admin</div>
            
            <h1 class="login-title">Вход в админ-панель</h1>
            
            <?php if (!empty($error)): ?>
            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Имя пользователя</label>
                    <input type="text" 
                           name="username" 
                           value="<?= htmlspecialchars($username) ?>" 
                           required
                           class="form-input"
                           placeholder="admin">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Пароль</label>
                    <input type="password" 
                           name="password" 
                           required
                           class="form-input"
                           placeholder="••••••••">
                </div>
                
                <button type="submit" class="btn">Войти</button>
            </form>
            
            <a href="<?= $app->config('base_url') ?>" class="back-link">
                ← Вернуться на сайт
            </a>
        </div>
    </div>
</body>
</html>