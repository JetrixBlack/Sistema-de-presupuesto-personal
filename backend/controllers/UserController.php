<?php
use Core\Controller;
use Core\Auth;
use Core\ActivityLogger;

class UserController extends Controller {
    private $userModel;

    public function __construct() {
        if (!Auth::check()) { $this->redirect(URL_ROOT . '/auth/login'); }
        if (($_SESSION['role'] ?? '') !== 'admin') { $this->redirect(URL_ROOT . '/dashboard'); }
        $this->userModel = $this->model('User');
    }

    public function index() {
        $users   = $this->userModel->getAll();
        $metrics = $this->userModel->getMetrics();
        $success = $_SESSION['success'] ?? null;
        $error   = $_SESSION['error']   ?? null;
        unset($_SESSION['success'], $_SESSION['error']);

        $this->render('users', [
            'pageTitle'      => 'Usuarios',
            'activeMenu'     => 'users',
            'users'          => $users,
            'metrics'        => $metrics,
            'success'        => $success,
            'error'          => $error,
            'unreadMessages' => 0,
        ]);
    }

    public function store() {
        $fullName = trim($_POST['full_name'] ?? '');
        if (empty($fullName)) {
            $_SESSION['error'] = 'El nombre completo es obligatorio.';
            $this->redirect(URL_ROOT . '/users');
        }

        // Autogenerar username único
        $num      = $this->userModel->getNextUserNumber();
        $username = 'Usuario' . $num;
        
        $password = 'Usuario123$';
        $role     = 'user'; // Rol fijo a user según petición

        $this->userModel->create([
            'full_name' => $fullName,
            'username'  => $username,
            'password'  => password_hash($password, PASSWORD_DEFAULT),
            'role'      => $role,
            'is_active' => 1
        ]);

        ActivityLogger::log("Creó usuario '{$username}' ({$fullName})", 'user');
        $_SESSION['success'] = "Usuario <strong>{$username}</strong> creado correctamente.";
        $this->redirect(URL_ROOT . '/users');
    }

    public function update() {
        $id       = $_POST['id'] ?? null;
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');

        if ($id && !empty($fullName) && !empty($username)) {
            // Verificar si el username ya existe en otro usuario
            $existing = $this->userModel->findByUsername($username);
            if ($existing && $existing->id != $id) {
                $_SESSION['error'] = 'El nombre de usuario ya está en uso.';
            } else {
                $this->userModel->update($id, [
                    'full_name' => $fullName,
                    'username'  => $username
                ]);
                ActivityLogger::log("Editó datos del usuario ID {$id}", 'user', $id);
                $_SESSION['success'] = 'Usuario actualizado correctamente.';
            }
        }
        $this->redirect(URL_ROOT . '/users');
    }

    public function toggle() {
        $id     = $_POST['id']     ?? null;
        $status = $_POST['status'] ?? 0;
        if ($id) {
            $this->userModel->setActive($id, $status);
            ActivityLogger::log($status ? "Activó usuario ID {$id}" : "Desactivó usuario ID {$id}", 'user', $id);
        }
        $this->redirect(URL_ROOT . '/users');
    }

    public function delete() {
        $id = $_POST['id'] ?? null;
        if ($id && $id != Auth::id()) {
            $u = $this->userModel->findById($id);
            if ($u && $u->username !== 'admin') {
                $pdo = \Core\Database::getInstance()->getDbh();
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
                ActivityLogger::log("Eliminó usuario '{$u->username}'", 'user', $id);
            }
        }
        $this->redirect(URL_ROOT . '/users');
    }
}
