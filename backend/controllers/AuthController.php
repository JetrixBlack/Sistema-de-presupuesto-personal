<?php
use Core\Controller;
use Core\Auth;

class AuthController extends Controller
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = $this->model('User');
    }

    public function login()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Rate limiting de login (5 intentos, 15 min de bloqueo)
        if (isset($_SESSION['login_blocked_until']) && time() < $_SESSION['login_blocked_until']) {
            $mins = ceil(($_SESSION['login_blocked_until'] - time()) / 60);
            $this->view('login', ['error' => "Demasiados intentos fallidos. Intente de nuevo en $mins minutos."]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');

            $user = $this->userModel->findByUsername($username);

            if ($user && password_verify($password, $user->password)) {
                // Éxito: resetear rate limit
                unset($_SESSION['login_attempts']);
                unset($_SESSION['login_blocked_until']);

                Core\Auth::login($user);
                $_SESSION['role']           = $user->role ?? 'user';
                $_SESSION['full_name']      = $user->full_name ?? $user->username;
                $_SESSION['is_first_login'] = $user->is_first_login ?? 0;

                $this->userModel->updateLastLogin($user->id);
                \Core\ActivityLogger::log('Inició sesión', 'auth');
                header('Location: ' . URL_ROOT . '/dashboard');
                exit;
            } else {
                // Fallo: incrementar intentos
                $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
                
                if ($_SESSION['login_attempts'] >= 5) {
                    $_SESSION['login_blocked_until'] = time() + (15 * 60); // Bloqueo de 15 min
                    $this->view('login', ['error' => 'Demasiados intentos fallidos. Cuenta bloqueada temporalmente (15 min).']);
                } else {
                    $intentosRestantes = 5 - $_SESSION['login_attempts'];
                    $data = ['error' => "Credenciales incorrectas. Quedan $intentosRestantes intento(s)."];
                    $this->view('login', $data);
                }
            }
        } else {
            $this->view('login');
        }
    }

    public function recover()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $step = $_GET['step'] ?? 1;
        $data = ['step' => $step];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if ($step == 1) {
                // Paso 1: Buscar usuario
                $username = trim($_POST['username']);
                $user = $this->userModel->findByUsername($username);
                if ($user) {
                    if (empty($user->security_question)) {
                        $data['error'] = 'Este usuario no tiene configurada una pregunta de seguridad. Contacte al administrador.';
                    } else {
                        $_SESSION['recovery_user_id'] = $user->id;
                        $this->redirect(URL_ROOT . '/auth/recover?step=2');
                    }
                } else {
                    $data['error'] = 'Nombre de usuario no encontrado.';
                }
            } elseif ($step == 2) {
                // Paso 2: Verificar pregunta
                $answer = strtolower(trim($_POST['answer']));
                $userId = $_SESSION['recovery_user_id'] ?? null;
                if ($userId) {
                    $user = $this->userModel->findById($userId);
                    if (password_verify($answer, $user->security_answer)) {
                        $this->redirect(URL_ROOT . '/auth/recover?step=3');
                    } else {
                        $data['error'] = 'La respuesta es incorrecta.';
                        $data['question'] = $user->security_question;
                    }
                } else { $this->redirect(URL_ROOT . '/auth/recover?step=1'); }
            } elseif ($step == 3) {
                // Paso 3: Nueva contraseña
                $pass = $_POST['password'] ?? '';
                $conf = $_POST['confirm_password'] ?? '';
                $userId = $_SESSION['recovery_user_id'] ?? null;
                if ($userId) {
                    if ($pass === $conf) {
                        // Validar fuerza de contraseña
                        if (strlen($pass) < 8 || !preg_match('/[A-Z]/', $pass) || !preg_match('/[a-z]/', $pass) || !preg_match('/[0-9]/', $pass)) {
                            $data['error'] = 'La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número.';
                        } else {
                            $this->userModel->update($userId, ['password' => password_hash($pass, PASSWORD_DEFAULT)]);
                            unset($_SESSION['recovery_user_id']);
                            $this->redirect(URL_ROOT . '/auth/recover?step=4');
                        }
                    } else {
                        $data['error'] = 'Las contraseñas no coinciden.';
                    }
                } else { $this->redirect(URL_ROOT . '/auth/recover?step=1'); }
            }
        }

        if ($step == 2) {
            $userId = $_SESSION['recovery_user_id'] ?? null;
            if ($userId) {
                $user = $this->userModel->findById($userId);
                $data['question'] = $user->security_question;
            } else { $this->redirect(URL_ROOT . '/auth/recover?step=1'); }
        }

        $this->view('recover', $data);
    }

    public function logout()
    {
        Core\Auth::logout();
        $this->view('logged_out');
    }

    public function eliminarDatos()
    {
        $user = Core\Auth::user();
        if (!$user) {
            $this->redirect(URL_ROOT . '/auth/login');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $current     = trim($_POST['current_password'] ?? '');
            $answer      = strtolower(trim($_POST['answer'] ?? ''));
            $passwordOk  = password_verify($current, $user->password ?? '');
            $answerOk    = !empty($user->security_answer) && password_verify($answer, $user->security_answer);

            if (!$passwordOk || !$answerOk) {
                $data['error'] = 'Verificación falló: contraseña actual o respuesta de seguridad incorrectas.';
            } else {
                // Fase 1: borrado inmediato de datos identificativos del usuario
                $this->userModel->update($user->id, [
                    'username'        => 'usuario_eliminado_' . $user->id,
                    'full_name'       => '[Usuario eliminado]',
                    'security_question' => '',
                    'security_answer' => '',
                    'is_active'       => 0,
                ]);
                // Fase 2: retención legal 10 años (Código de Comercio art. 44/132)
                // las transacciones/presupuestos se conservan anonimizados.
                $retencionHasta = date('Y-m-d', strtotime('+10 years'));
                Core\Auth::logout();
                $data['comprobante'] = [
                    'datos_eliminados' => 'Datos básicos eliminados de forma inmediata.',
                    'retencion_legal'  => "Los registros financieros quedan anonimizados y se conservarán "
                                       . "durante 10 años (hasta $retencionHasta) conforme al art. 44 y art. 132 "
                                       . "del Código de Comercio, hasta la purga definitiva tras auditoría.",
                ];
                $this->view('eliminado', $data);
                return;
            }
        }

        $this->view('eliminar_datos', $data ?? []);
    }
}
