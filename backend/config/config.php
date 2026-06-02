<?php
// Cargar variables de entorno manualmente
$envFile = dirname(__DIR__, 2) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
        [$name, $value] = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value, " \t\n\r\0\x0B\"'");
    }
}

// Configuración de la Base de Datos
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'presupuesto_bd');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// Configuración de la App
$http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
// Si tiene puerto personalizado (VirtualHost dedicado), no se usa subcarpeta
$port = (int)($_SERVER['SERVER_PORT'] ?? 80);
if ($port !== 80 && $port !== 443) {
    define('URL_ROOT', $_ENV['APP_URL'] ?? "http://{$http_host}");
} else {
    define('URL_ROOT', $_ENV['APP_URL'] ?? "http://{$http_host}/Sistema-de-presupuesto-personal");
}
define('APP_NAME',       $_ENV['APP_NAME']       ?? 'Sistema de Presupuesto Personal');
define('APP_ENV',        $_ENV['APP_ENV']        ?? 'production');

// Seguridad
define('APP_SECRET',      $_ENV['APP_SECRET']      ?? bin2hex(random_bytes(16)));
define('SESSION_NAME',    $_ENV['SESSION_NAME']    ?? 'sistema_presupuesto_personal_session');
define('SESSION_LIFETIME',  (int)($_ENV['SESSION_LIFETIME'] ?? 7200));
define('ALLOWED_ORIGIN',  $_ENV['ALLOWED_ORIGIN']  ?? 'http://localhost');

// Rutas de archivos
define('APPROOT',  dirname(dirname(__FILE__)));
define('FRONTROOT', dirname(dirname(__FILE__)) . '/../frontend');

// Encoding global
mb_internal_encoding('UTF-8');
mb_language('uni');

// ── Funciones Globales ──────────────────────────────────────────────────
if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        $token = \Core\Auth::generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}
