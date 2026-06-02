<?php
require_once '../backend/config/config.php';
require_once '../vendor/autoload.php';

// ── Encoding ────────────────────────────────────────────────────
header('Content-Type: text/html; charset=UTF-8');

// ── CORS — Solo permite peticiones desde tu app ──────────────────
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = defined('ALLOWED_ORIGIN') ? ALLOWED_ORIGIN : 'http://localhost';

if ($origin === $allowed || strpos($origin, 'localhost') !== false) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
}

// Responder preflight OPTIONS y terminar
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Security Headers ─────────────────────────────────────────────
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://fonts.googleapis.com https://fonts.gstatic.com https://cdn.jsdelivr.net data:; img-src 'self' data: https:;");

// ── Autocarga de Clases ──────────────────────────────────────────
spl_autoload_register(function($className) {
    $parts = explode('\\', $className);
    $class = end($parts);

    if (file_exists('../backend/core/' . $class . '.php')) {
        require_once '../backend/core/' . $class . '.php';
    } elseif (file_exists('../backend/models/' . $class . '.php')) {
        require_once '../backend/models/' . $class . '.php';
    } elseif (file_exists('../backend/controllers/' . $class . '.php')) {
        require_once '../backend/controllers/' . $class . '.php';
    }
});

use Core\App;
use Core\Auth;

Auth::init();

// ── Inicializar la App ───────────────────────────────────────────
$init = new App();
