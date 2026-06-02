<?php
use Core\Controller;
use Core\Auth;
use Core\Cache;
use Core\ActivityLogger;

class FinanceController extends Controller {
    private $txModel;
    private $userModel;
    private $cache;

    // Rangos permitidos por tipo (Valores Absolutos)
    const MIN_AMOUNT = 0;
    const MAX_AMOUNT = 10000000;

    // Categorías oficiales
    const CAT_INCOME  = ['Salario', 'Ventas', 'Inversión', 'Bono', 'Otros'];
    const CAT_EXPENSE = [
        'Alimentación', 'Transporte', 'Hogar', 'Servicios', 'Salud',
        'Educación', 'Entretenimiento', 'Vestimenta', 'Calzado', 'Recargas', 'Otros'
    ];

    public function __construct() {
        if (!Auth::check()) { $this->redirect(URL_ROOT . '/auth/login'); }
        if (($_SESSION['role'] ?? '') === 'admin') {
            $_SESSION['error'] = 'Los administradores no tienen acceso al módulo de finanzas personales.';
            $this->redirect(URL_ROOT . '/dashboard');
        }
        $this->txModel   = $this->model('Transaction');
        $this->userModel = $this->model('User');
        $this->cache     = new Cache();
    }

    public function index($tab = 'income') {
        $userId  = Auth::id();
        $isAdmin = ($_SESSION['role'] ?? '') === 'admin';
        $title   = 'Finanzas';

        $pdo = \Core\Database::getInstance()->getDbh();
        $stmt = $pdo->query("SELECT rate FROM bcv_rate_cache WHERE currency = 'USD' ORDER BY fetched_at DESC LIMIT 1");
        $bcvRate = (float)($stmt->fetchColumn() ?: 36.00);
        if ($isAdmin) {
            // Vista Administrador: Gráficos por Usuario
            $globalStats = $this->txModel->getGlobalFinanceStats();
            
            $this->render('finance', [
                'pageTitle'    => 'Métricas Financieras Globales',
                'activeMenu'   => 'finance',
                'isAdmin'      => true,
                'globalStats'  => $globalStats,
                'bcvRate'      => $bcvRate,
                'catIncome'    => self::CAT_INCOME,
                'catExpense'   => self::CAT_EXPENSE,
                'unreadMessages' => 0,
            ]);
            return;
        }

        // Vista Usuario Regular (Existente)
        $mode = 'mov';
        $type = 'income';

        switch ($tab) {
            case 'expense':
                $title = 'Egresos'; $type = 'expense'; break;
            default:
                $tab = 'income'; $title = 'Ingresos'; $type = 'income'; break;
        }

        $all      = $this->txModel->getByUserId($userId);
        $filtered = array_filter($all, function($t) use ($type, $mode) {
            return $t->type === $type && $t->mode === $mode;
        });
        $filtered = array_values($filtered);

        $this->render('finance', [
            'pageTitle'    => $title,
            'activeMenu'   => 'finance',
            'activeTab'    => $tab,
            'isAdmin'      => false,
            'moduleTitle'  => $title,
            'moduleType'   => $type,
            'moduleMode'   => $mode,
            'transactions' => $filtered,
            'bcvRate'      => $bcvRate,
            'catIncome'    => self::CAT_INCOME,
            'catExpense'   => self::CAT_EXPENSE,
            'unreadMessages' => 0,
        ]);
    }

    public function store() {
        if (!Auth::check()) { $this->redirect(URL_ROOT . '/auth/login'); }
        $this->verifyCsrf();

        $userId   = Auth::id();
        $type     = $_POST['type'] ?? 'income';
        $mode     = $_POST['mode'] ?? 'mov';
        $amount   = (float)($_POST['amount'] ?? 0);
        $absAmount = abs($amount);

        if ($absAmount < self::MIN_AMOUNT || $absAmount > self::MAX_AMOUNT) {
            $_SESSION['error'] = "El monto debe estar entre Bs. 0,00 y Bs. 10.000.000,00";
            $this->redirect(URL_ROOT . "/finance");
        }

        $finalAmount = ($type === 'income') ? $absAmount : -$absAmount;
        $details = mb_substr(trim($_POST['details'] ?? ''), 0, 300);

        $data = [
            'user_id'          => $userId,
            'familiar'         => $_POST['familiar'] ?? '',
            'amount'           => $finalAmount,
            'type'             => $type,
            'category'         => $_POST['category'] ?? 'Otros',
            'details'          => $details,
            'mode'             => $mode,
            'status'           => ($mode === 'debt') ? 'pendiente' : 'pagado',
            'acreedor'         => $_POST['acreedor'] ?? '',
            'transaction_date' => date('Y-m-d H:i:s'),
        ];

        $id = $this->txModel->create($data);
        // Limpiar todas las claves de caché del usuario
        $this->cache->clear("dashboard_user_{$userId}_" . date('Y'));
        $this->cache->clear("dashboard_user_{$userId}");
        $this->cache->clear("finance_metrics_{$userId}__");

        ActivityLogger::log(
            "Registró " . ($type === 'income' ? 'ingreso' : 'egreso') . 
            " de Bs. " . number_format($finalAmount, 2),
            'transaction',
            (int)$id
        );

        $redirectTab = $_POST['redirect'] ?? 'income';
        $this->redirect(URL_ROOT . "/finance/index/" . $redirectTab);
    }

    public function update() {
        if (!Auth::check()) { $this->redirect(URL_ROOT . '/auth/login'); }
        $this->verifyCsrf();
        $id = $_POST['id'] ?? null;
        if (!$id) $this->redirect(URL_ROOT . '/finance');

        $userId   = Auth::id();

        // RLS: Verificar que el registro pertenece al usuario
        $tx = $this->txModel->findById((int)$id);
        if ($tx) $this->requireOwnership((int)$tx->user_id);

        $amount   = (float)($_POST['amount'] ?? 0);
        $absAmount = abs($amount);
        $type      = $_POST['type'] ?? 'income';
        $finalAmount = ($type === 'income') ? $absAmount : -$absAmount;

        $data = [
            'familiar' => $_POST['familiar'] ?? '',
            'amount'   => $finalAmount,
            'category' => $_POST['category'] ?? 'Otros',
            'details'  => mb_substr(trim($_POST['details'] ?? ''), 0, 300),
            'acreedor' => $_POST['acreedor'] ?? '',
        ];

        $this->txModel->update($id, $data);
        // Limpiar todas las claves de caché del usuario para evitar retrasos en estadísticas
        $this->cache->clear("dashboard_user_$userId");
        $this->cache->clear("dashboard_user_{$userId}_" . date('Y'));
        $this->cache->clear("finance_metrics_{$userId}__"); // sin filtros (year=null, month=null)
        ActivityLogger::log("Editó registro financiero", 'transaction', (int)$id);
        
        $this->redirect($_SERVER['HTTP_REFERER'] ?? (URL_ROOT . '/finance'));
    }

    public function pay() {
        if (!Auth::check()) { $this->redirect(URL_ROOT . '/auth/login'); }
        $this->verifyCsrf();
        $id = $_POST['id'] ?? null;
        $userId = Auth::id();
        if ($id) {
            // RLS: Verificar propiedad
            $tx = $this->txModel->findById((int)$id);
            if ($tx) $this->requireOwnership((int)$tx->user_id);
            $this->txModel->update($id, ['status' => 'pagado']);
            $this->cache->clear("dashboard_user_$userId");
            ActivityLogger::log("Marcó deuda como pagada", 'transaction', (int)$id);
        }
        $this->redirect(URL_ROOT . '/finance/index/debts');
    }

    public function delete() {
        if (!Auth::check()) { $this->redirect(URL_ROOT . '/auth/login'); }
        $this->verifyCsrf();
        $id = $_POST['id'] ?? null;
        $userId = Auth::id();
        if ($id) {
            // RLS: Verificar propiedad antes de borrar
            $tx = $this->txModel->findById((int)$id);
            if ($tx) $this->requireOwnership((int)$tx->user_id);
            $this->txModel->delete($id);
            // Limpiar todas las claves de caché del usuario para evitar retrasos en estadísticas
            $this->cache->clear("dashboard_user_$userId");
            $this->cache->clear("dashboard_user_{$userId}_" . date('Y'));
            $this->cache->clear("finance_metrics_{$userId}__"); // sin filtros
            ActivityLogger::log("Eliminó registro financiero", 'transaction', (int)$id);
        }
        $this->redirect($_SERVER['HTTP_REFERER'] ?? (URL_ROOT . '/finance'));
    }
}
