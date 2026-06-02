<?php
use Core\Controller;
use Core\Auth;
use Core\ActivityLogger;

class SettingsController extends Controller {
    private $userModel;

    public function __construct() {
        if (!Auth::check()) { $this->redirect(URL_ROOT . '/auth/login'); }
        $this->userModel = $this->model('User');
    }

    public function index() {
        $userId  = Auth::id();
        $user    = $this->userModel->findById($userId);
        $canEdit = $this->userModel->canEditProfile($userId);
        $success = $_SESSION['success'] ?? null;
        $error   = $_SESSION['error']   ?? null;
        unset($_SESSION['success'], $_SESSION['error']);

        // Obtener tasas guardadas
        $bcvRate = 0;
        $euroRate = 0;
        try {
            $pdo  = \Core\Database::getInstance()->getDbh();
            
            // USD
            $stmt = $pdo->query("SELECT rate FROM bcv_rate_cache WHERE currency = 'USD' ORDER BY fetched_at DESC LIMIT 1");
            $row  = $stmt->fetch(\PDO::FETCH_OBJ);
            if ($row) $bcvRate = (float)$row->rate;
            
            // EUR
            $stmt = $pdo->query("SELECT rate FROM bcv_rate_cache WHERE currency = 'EUR' ORDER BY fetched_at DESC LIMIT 1");
            $row  = $stmt->fetch(\PDO::FETCH_OBJ);
            if ($row) $euroRate = (float)$row->rate;
            
        } catch (\Exception $e) {}

        // Presupuesto mensual
        $budgetModel = $this->model('Budget');
        $currentMonth = date('Y-m');
        $monthlyBudget = $budgetModel->getBudgetForMonth($userId, $currentMonth);

        $this->render('settings', [
            'pageTitle'      => 'Configuración',
            'activeMenu'     => 'settings',
            'user'           => $user,
            'canEdit'        => $canEdit,
            'success'        => $success,
            'error'          => $error,
            'bcvRate'        => $bcvRate,
            'euroRate'       => $euroRate,
            'monthlyBudget'  => $monthlyBudget ? $monthlyBudget->amount_limit : 0,
            'unreadMessages' => 0,
        ]);
    }

    public function updateProfile() {
        if (!Auth::check()) { $this->redirect(URL_ROOT . '/auth/login'); }

        $userId  = Auth::id();
        $canEdit = $this->userModel->canEditProfile($userId);
        $data    = [];

        // Límites: Nombre (80), Usuario (30)
        $newFullName = mb_substr(trim($_POST['full_name'] ?? ''), 0, 80);
        $newUsername = mb_substr(trim($_POST['username']  ?? ''), 0, 30);

        // Validar y aplicar cambio de nombre completo
        if (!empty($newFullName) && $newFullName !== $_SESSION['full_name']) {
            if ($canEdit['name']) {
                $data['full_name']            = $newFullName;
                $data['profile_changed_at']   = date('Y-m-d H:i:s');
                $_SESSION['full_name']        = $newFullName;
            } else {
                $_SESSION['error'] = "No puedes cambiar tu nombre por {$canEdit['days_name']} días más.";
                $this->redirect(URL_ROOT . '/settings'); return;
            }
        }

        // Validar y aplicar cambio de username
        if (!empty($newUsername) && $newUsername !== ($_SESSION['username'] ?? '')) {
            if ($canEdit['username']) {
                // Verificar que no empiece por "admin" si no es admin
                if (strtolower($newUsername) === 'admin' && $_SESSION['role'] !== 'admin') {
                    $_SESSION['error'] = 'No puedes usar ese nombre de usuario.';
                    $this->redirect(URL_ROOT . '/settings'); return;
                }

                $existing = $this->userModel->findByUsername($newUsername);
                if ($existing && $existing->id != $userId) {
                    $_SESSION['error'] = 'Ese nombre de usuario ya está en uso.';
                    $this->redirect(URL_ROOT . '/settings'); return;
                }
                $data['username']              = $newUsername;
                $data['username_changed_at']   = date('Y-m-d H:i:s');
                $_SESSION['username']          = $newUsername;
            } else {
                $_SESSION['error'] = "No puedes cambiar tu usuario por {$canEdit['days_user']} días más.";
                $this->redirect(URL_ROOT . '/settings'); return;
            }
        }

        if (!empty($data)) {
            $this->userModel->update($userId, $data);
            ActivityLogger::log('Actualizó su perfil (Nombre/Usuario)', 'user', $userId);
            $_SESSION['success'] = 'Perfil actualizado correctamente.';
        }
        $this->redirect(URL_ROOT . '/settings');
    }

    public function updateSecurity() {
        if (!Auth::check()) { $this->redirect(URL_ROOT . '/auth/login'); }
        $data = [];

        if (!empty($_POST['new_password'])) {
            if ($_POST['new_password'] === $_POST['confirm_password']) {
                $data['password'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            } else {
                $_SESSION['error'] = 'Las contraseñas nuevas no coinciden.';
                $this->redirect(URL_ROOT . '/settings'); return;
            }
        }

        if (!empty($_POST['security_question']) && !empty($_POST['security_answer'])) {
            $data['security_question'] = $_POST['security_question'];
            $data['security_answer']   = password_hash(strtolower(trim($_POST['security_answer'])), PASSWORD_DEFAULT);
        }

        if (!empty($data)) {
            $this->userModel->update(Auth::id(), $data);
            ActivityLogger::log('Actualizó contraseña / pregunta de seguridad', 'user', Auth::id());
            $_SESSION['success'] = 'Configuración de seguridad actualizada.';
        }
        $this->redirect(URL_ROOT . '/settings');
    }



    public function updateBudget() {
        if (!Auth::check()) { $this->redirect(URL_ROOT . '/auth/login'); }
        
        $amount = (float)($_POST['amount_limit'] ?? 0);
        if ($amount < 0) {
            $_SESSION['error'] = 'El presupuesto no puede ser negativo.';
        } else {
            $budgetModel = $this->model('Budget');
            $budgetModel->setBudget(Auth::id(), date('Y-m'), $amount);
            ActivityLogger::log('Actualizó su presupuesto mensual', 'budget');
            $_SESSION['success'] = 'Presupuesto mensual actualizado correctamente.';
        }
        
        $this->redirect(URL_ROOT . '/settings');
    }

    public function completeOnboarding() {
        if (!Auth::check()) return;
        $userModel = $this->model('User');
        $userModel->completeOnboarding(Auth::id());
        $_SESSION['is_first_login'] = 0;
        echo json_encode(['success' => true]);
        exit;
    }
}
