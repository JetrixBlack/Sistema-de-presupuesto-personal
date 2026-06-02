<?php
namespace Core;

class Controller {

    // Cargar modelo
    public function model($model) {
        require_once '../backend/models/' . $model . '.php';
        return new $model();
    }



    // Cargar vista simple (sin layout)
    public function view($view, $data = []) {
        extract($data);
        $viewPath = '../frontend/views/' . $view . '.php';
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            die('Vista no encontrada: ' . htmlspecialchars($view));
        }
    }

    // Cargar vista dentro del layout principal
    public function render($view, $data = [], $layout = 'layouts/app') {
        // Actualizar last_activity automáticamente en cada navegación
        \Core\Auth::updateActivity();

        extract($data);

        ob_start();
        $viewPath = '../frontend/views/' . $view . '.php';
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo '<div style="color:red;padding:2rem;">Vista no encontrada: ' . htmlspecialchars($view) . '</div>';
        }
        $content = ob_get_clean();

        $layoutPath = '../frontend/views/' . $layout . '.php';
        if (file_exists($layoutPath)) {
            require $layoutPath;
        } else {
            echo $content;
        }
    }

    // Redirección
    public function redirect($url) {
        header('Location: ' . $url);
        exit;
    }

    // ── CSRF Verification ──────────────────────────────────────────
    /**
     * Verifica el token CSRF en peticiones POST.
     * Llama esto al inicio de cualquier bloque POST en los controladores.
     */
    protected function verifyCsrf(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!\Core\Auth::verifyCsrfToken($token)) {
                http_response_code(403);
                die('<h1>403 - Acceso Denegado</h1><p>Token de seguridad inválido. Por favor, recarga la página e intenta de nuevo.</p>');
            }
        }
    }

    // ── Row Level Security — Ownership check ──────────────────────
    /**
     * Verifica que el recurso pertenece al usuario actual.
     * Los admins siempre tienen acceso.
     * Aborta con 403 si el usuario no es propietario.
     *
     * @param int|null $resourceUserId user_id del recurso en cuestión
     */
    protected function requireOwnership(?int $resourceUserId): void {
        $currentUserId = \Core\Auth::id();
        $role = $_SESSION['role'] ?? 'user';

        // Admin tiene acceso total
        if ($role === 'admin') return;

        if ((int)$resourceUserId !== (int)$currentUserId) {
            http_response_code(403);
            die('<h1>403 - Acceso Denegado</h1><p>No tienes permiso para acceder a este recurso.</p>');
        }
    }
}
