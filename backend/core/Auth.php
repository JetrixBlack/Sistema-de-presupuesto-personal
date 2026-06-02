<?php
namespace Core;

class Auth {

    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            // Session Hardening
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.use_strict_mode',  '1');
            ini_set('session.gc_maxlifetime',   SESSION_LIFETIME);
            ini_set('session.cookie_lifetime',  '0'); // Cookie de sesión (no persistente)

            session_name(SESSION_NAME);
            session_start();
        }
    }

    public static function check() {
        self::init();
        return isset($_SESSION['user_id']);
    }

    public static function id() {
        self::init();
        return $_SESSION['user_id'] ?? null;
    }

    public static function user() {
        self::init();
        return $_SESSION['user_data'] ?? null;
    }

    public static function login($user) {
        self::init();
        // Regenerar ID para prevenir session fixation
        session_regenerate_id(true);

        $_SESSION['user_id']   = $user->id;
        $_SESSION['user_data'] = $user;
        $_SESSION['username']  = $user->username;
        $_SESSION['role']      = $user->role ?? 'user';
        $_SESSION['full_name'] = $user->full_name ?? $user->username;

        // Registrar actividad inicial
        self::updateActivity($user->id);
    }

    /** Actualiza last_activity en BD para tracking en tiempo real */
    public static function updateActivity(?int $userId = null): void {
        $id = $userId ?? (self::check() ? self::id() : null);
        if (!$id) return;
        try {
            $pdo  = \Core\Database::getInstance()->getDbh();
            $now  = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("UPDATE users SET last_activity = ? WHERE id = ?");
            $stmt->execute([$now, $id]);
        } catch (\Exception $e) { /* silenciar */ }
    }

    public static function logout() {
        self::init();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    // ── CSRF Protection ─────────────────────────────────────────
    public static function generateCsrfToken(): string {
        self::init();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrfToken(string $token): bool {
        self::init();
        $stored = $_SESSION['csrf_token'] ?? '';
        return !empty($stored) && hash_equals($stored, $token);
    }
}
