<?php
declare(strict_types=1);

class Auth {
    private $db;
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_TIME = 900; // 15 минут
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    public function login(string $username, string $password): bool {
        // Проверка блокировки
        if ($this->isLocked($username)) {
            return false;
        }
        
        $stmt = $this->db->prepare("SELECT id, username, password_hash FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            $this->recordFailedAttempt($username);
            return false;
        }
        
        // Успешный вход
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_logged_in'] = time();
        
        // Сброс счетчика попыток
        $this->resetAttempts($username);
        
        // Обновление времени последнего входа
        $stmt = $this->db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$admin['id']]);
        
        return true;
    }
    
    public function logout(): void {
        unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_logged_in']);
        session_destroy();
    }
    
    public function isLoggedIn(): bool {
        if (!isset($_SESSION['admin_id'], $_SESSION['admin_logged_in'])) {
            return false;
        }
        
        // Проверка таймаута сессии (24 часа)
        if (time() - $_SESSION['admin_logged_in'] > 86400) {
            $this->logout();
            return false;
        }
        
        return true;
    }
    
    public function requireLogin(): void {
        if (!$this->isLoggedIn()) {
            App::get()->redirect('/admin/login.php');
        }
    }
    
    private function isLocked(string $username): bool {
        $stmt = $this->db->prepare("SELECT attempts, locked_until FROM login_attempts WHERE username = ?");
        $stmt->execute([$username]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return false;
        }
        
        // Проверка времени блокировки
        if ($result['locked_until'] && strtotime($result['locked_until']) > time()) {
            return true;
        }
        
        // Если блокировка истекла, сбрасываем
        if ($result['locked_until'] && strtotime($result['locked_until']) <= time()) {
            $this->resetAttempts($username);
        }
        
        return false;
    }
    
    private function recordFailedAttempt(string $username): void {
        $stmt = $this->db->prepare("
            INSERT INTO login_attempts (username, attempts, last_attempt) 
            VALUES (?, 1, NOW()) 
            ON DUPLICATE KEY UPDATE 
            attempts = attempts + 1, 
            last_attempt = NOW(),
            locked_until = CASE 
                WHEN attempts + 1 >= ? THEN DATE_ADD(NOW(), INTERVAL ? SECOND)
                ELSE NULL 
            END
        ");
        $stmt->execute([$username, self::MAX_ATTEMPTS, self::LOCKOUT_TIME]);
    }
    
    private function resetAttempts(string $username): void {
        $stmt = $this->db->prepare("DELETE FROM login_attempts WHERE username = ?");
        $stmt->execute([$username]);
    }
}
?>