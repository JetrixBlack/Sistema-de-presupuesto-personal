<?php
use Core\Controller;
use Core\Auth;
use Core\Cache;
use Core\ActivityLogger;

class DashboardController extends Controller {
    private $txModel;
    private $userModel;
    private $cache;

    public function __construct() {
        if (!Auth::check()) { $this->redirect(URL_ROOT . '/auth/login'); }
        $this->txModel   = $this->model('Transaction');
        $this->userModel = $this->model('User');
        $this->cache     = new Cache();
    }

    public function index() {
        $userId  = Auth::id();
        $isAdmin = ($_SESSION['role'] ?? '') === 'admin';

        if ($isAdmin) {
            $data = $this->buildAdminDashboard();
        } else {
            $data = $this->buildUserDashboard($userId);
        }

        // Tasas BCV para conversión
        $rateData = $this->getRateData();
        $data['bcvRate']  = $rateData['usd'];
        $data['euroRate'] = $rateData['eur'];

        $data['isAdmin']        = $isAdmin;
        $data['showOnboarding'] = ($_SESSION['is_first_login'] ?? 0) == 1;
        $data['pageTitle']      = 'Dashboard';
        $data['activeMenu']     = 'dashboard';
        $data['unreadMessages'] = $this->getUnreadCount();

        $this->render('dashboard', $data);
    }

    // ── Dashboard Admin ───────────────────────────────────────

    private function buildAdminDashboard(): array {
        $pdo     = \Core\Database::getInstance()->getDbh();
        $metrics = $this->userModel->getMetrics();

        // Tickets de soporte
        $stmt = $pdo->query(
            "SELECT
               SUM(status='pendiente') as pendientes,
               SUM(status='en_proceso') as en_proceso,
               SUM(status='resuelto') as resueltos,
               COUNT(*) as total
             FROM support_tickets"
        );
        $ticketMetrics = $stmt->fetch(\PDO::FETCH_OBJ);

        // Actividad reciente (ahora para notificaciones)
        $stmt = $pdo->query(
            "SELECT al.*, u.full_name
             FROM activity_log al
             LEFT JOIN users u ON u.id = al.user_id
             ORDER BY al.created_at DESC
             LIMIT 15"
        );
        $recentActivity = $stmt->fetchAll(\PDO::FETCH_OBJ);

        // Últimos usuarios registrados
        $recentUsers = $this->userModel->getActiveUsers(5);

        // Gráfico anual del sistema
        $selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $allTx        = $this->txModel->getAllSystem();
        $annual       = ['income' => array_fill(0, 12, 0), 'expense' => array_fill(0, 12, 0)];

        $totalSystemIncome  = 0;
        $totalSystemExpense = 0;

        foreach ($allTx as $t) {
            $tDate  = strtotime($t->transaction_date);
            $tYear  = (int)date('Y', $tDate);
            $tMonth = (int)date('m', $tDate);
            $amt    = abs((float)$t->amount);

            if ($tYear === $selectedYear) {
                if ($t->type === 'income' && $t->mode === 'mov') {
                    $annual['income'][$tMonth - 1] += $amt;
                    $totalSystemIncome += $amt;
                }
                if (($t->type === 'expense' && $t->mode === 'mov') || ($t->mode === 'debt' && $t->status === 'pagado')) {
                    $annual['expense'][$tMonth - 1] += $amt;
                    $totalSystemExpense += $amt;
                }
            }
        }
        $availableYears = range(2020, 2035);
        rsort($availableYears);

        // Usuarios conectados en tiempo real (activos en los últimos 5 min)
        $onlineUsers = $this->userModel->getOnlineUsers(5);

        // Tendencia anual: ingresos vs egresos mes a mes para gráfico de proyección
        $trendStmt = $pdo->prepare(
            "SELECT
               MONTH(transaction_date) as mes,
               SUM(CASE WHEN type='income' AND mode='mov' THEN amount ELSE 0 END) as ingresos,
               SUM(CASE WHEN (type='expense' AND mode='mov') OR (mode='debt' AND status='pagado') THEN ABS(amount) ELSE 0 END) as egresos
             FROM transactions
             WHERE YEAR(transaction_date) = ?
             GROUP BY MONTH(transaction_date)
             ORDER BY mes"
        );
        $trendStmt->execute([$selectedYear]);
        $trendRaw = $trendStmt->fetchAll(\PDO::FETCH_OBJ);

        $trendIncome  = array_fill(0, 12, 0);
        $trendExpense = array_fill(0, 12, 0);
        foreach ($trendRaw as $row) {
            $idx = (int)$row->mes - 1;
            $trendIncome[$idx]  = (float)$row->ingresos;
            $trendExpense[$idx] = (float)$row->egresos;
        }

        return [
            'userMetrics'    => $metrics,
            'ticketMetrics'  => $ticketMetrics,
            'recentActivity' => $recentActivity,
            'recentUsers'    => $recentUsers,
            'onlineUsers'    => $onlineUsers,
            'adminChart'     => ['annual' => $annual],
            'adminPie'       => ['income' => $totalSystemIncome, 'expense' => $totalSystemExpense],
            'trendChart'     => ['income' => $trendIncome, 'expense' => $trendExpense],
            'availableYears' => $availableYears,
            'selectedYear'   => $selectedYear,
        ];
    }

    // ── Dashboard Usuario ──────────────────────────────────────

    private function buildUserDashboard(int $userId): array {
        $selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $cacheKey = "dashboard_user_{$userId}_{$selectedYear}";
        $data     = $this->cache->get($cacheKey);

        if (!$data) {
            $metrics = $this->txModel->getFinanceMetrics($userId);
            $recent  = $this->txModel->getRecentByUserId($userId, 8);

            // Obtener todas las transacciones para calcular años y gráfico
            $all         = $this->txModel->getByUserId($userId);
            $annual      = ['income' => array_fill(0, 12, 0), 'expense' => array_fill(0, 12, 0), 'debt' => array_fill(0, 12, 0)];
            $availableYears = [$selectedYear];

            $currentMonthExpense = 0;
            $currentMonth = date('Y-m');

            foreach ($all as $t) {
                $tDate  = strtotime($t->transaction_date);
                $tYear  = (int)date('Y', $tDate);
                $tMonth = (int)date('m', $tDate);
                $amt    = abs((float)$t->amount);

                if ($tYear === $selectedYear) {
                    if ($t->type === 'income') $annual['income'][$tMonth - 1] += $amt;
                    
                    if (($t->type === 'expense' && $t->mode !== 'debt') || ($t->mode === 'debt' && $t->status === 'pagado')) {
                        $annual['expense'][$tMonth - 1] += $amt;
                    }
                    
                    if ($t->mode === 'debt' && $t->status === 'pendiente') {
                        $annual['debt'][$tMonth - 1] += $amt;
                    }

                    if (date('Y-m', $tDate) === date('Y-m')) {
                         if ($t->type === 'expense' || $t->mode === 'debt') {
                            $currentMonthExpense += $amt;
                         }
                    }
                }
            }
            $availableYears = range(2020, 2035);
            rsort($availableYears);

            // Obtener inventario (alertas)
            $inventoryAlerts = [];
            try {
                $pdo  = \Core\Database::getInstance()->getDbh();
                $stmt = $pdo->prepare("SELECT * FROM inventory WHERE user_id = ? AND quantity <= min_quantity LIMIT 5");
                $stmt->execute([$userId]);
                $inventoryAlerts = $stmt->fetchAll(\PDO::FETCH_OBJ);
            } catch (\Exception $e) {}

            $data = [
                'stats'               => $metrics,
                'transactions'        => $recent,
                'chart'               => ['annual' => $annual],
                'availableYears'      => $availableYears,
                'selectedYear'        => $selectedYear,
                'inventoryAlerts'     => $inventoryAlerts,
                'currentMonthExpense' => $currentMonthExpense
            ];
            $this->cache->set($cacheKey, $data, 300);
        }

        // Fetch budget (outside cache so it updates immediately when changed)
        $budgetModel = $this->model('Budget');
        $monthlyBudgetObj = $budgetModel->getBudgetForMonth($userId, date('Y-m'));
        $data['monthlyBudget'] = $monthlyBudgetObj ? (float)$monthlyBudgetObj->amount_limit : 0;

        return $data;
    }

    // ── BCV Rate ──────────────────────────────────────────────

    public function bcvRate() {
        header('Content-Type: application/json');
        echo json_encode($this->getRateData());
    }

    public function updateBcvRate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(URL_ROOT . '/settings');
        }
        $rate = (float) str_replace(',', '.', $_POST['bcv_rate'] ?? 0);
        if ($rate <= 0) {
            $_SESSION['error'] = 'La tasa BCV debe ser mayor a 0.';
            $this->redirect(URL_ROOT . '/settings');
        }
        $pdo = \Core\Database::getInstance()->getDbh();
        $pdo->prepare("DELETE FROM bcv_rate_cache WHERE currency = 'USD'")->execute();
        $pdo->prepare("INSERT INTO bcv_rate_cache (currency, rate) VALUES ('USD', ?)")->execute([$rate]);
        $_SESSION['success'] = 'Tasa BCV actualizada manualmente a Bs. ' . number_format($rate, 2, ',', '.');
        $this->redirect(URL_ROOT . '/settings');
    }

    public function updateEuroRate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Auth::check()) {
            $this->redirect(URL_ROOT . '/settings');
        }
        $rate = (float) str_replace(',', '.', $_POST['euro_rate'] ?? 0);
        if ($rate <= 0) {
            $_SESSION['error'] = 'La tasa Euro debe ser mayor a 0.';
            $this->redirect(URL_ROOT . '/settings');
        }
        $pdo = \Core\Database::getInstance()->getDbh();
        $pdo->prepare("DELETE FROM bcv_rate_cache WHERE currency = 'EUR'")->execute();
        $pdo->prepare("INSERT INTO bcv_rate_cache (currency, rate) VALUES ('EUR', ?)")->execute([$rate]);
        $_SESSION['success'] = 'Tasa Euro actualizada manualmente a Bs. ' . number_format($rate, 2, ',', '.');
        $this->redirect(URL_ROOT . '/settings');
    }

    private function getRateData() {
        $pdo = \Core\Database::getInstance()->getDbh();
        
        // Intentar obtener USD y EUR de la caché
        $stmt = $pdo->query("SELECT * FROM bcv_rate_cache ORDER BY fetched_at DESC");
        $cachedRows = $stmt->fetchAll(\PDO::FETCH_OBJ);
        
        $usd = null; $eur = null; $lastFetched = null;
        foreach($cachedRows as $row) {
            if ($row->currency === 'USD' && !$usd) $usd = (float)$row->rate;
            if ($row->currency === 'EUR' && !$eur) $eur = (float)$row->rate;
            if (!$lastFetched || strtotime($row->fetched_at) > strtotime($lastFetched)) $lastFetched = $row->fetched_at;
        }

        // Si tenemos ambos y son recientes (< 4 horas), retornamos
        if ($usd && $eur && $lastFetched && strtotime($lastFetched) > time() - 14400) {
            return ['usd' => $usd, 'eur' => $eur, 'cached' => true];
        }

        // Si no, scrapeamos ambos
        $rates = $this->scrapeBcvRates();
        if ($rates['usd'] > 0 || $rates['eur'] > 0) {
            if ($rates['usd'] > 0) {
                $pdo->prepare("DELETE FROM bcv_rate_cache WHERE currency = 'USD'")->execute();
                $pdo->prepare("INSERT INTO bcv_rate_cache (currency, rate) VALUES ('USD', ?)")->execute([$rates['usd']]);
                $usd = $rates['usd'];
            }
            if ($rates['eur'] > 0) {
                $pdo->prepare("DELETE FROM bcv_rate_cache WHERE currency = 'EUR'")->execute();
                $pdo->prepare("INSERT INTO bcv_rate_cache (currency, rate) VALUES ('EUR', ?)")->execute([$rates['eur']]);
                $eur = $rates['eur'];
            }
            return ['usd' => $usd, 'eur' => $eur, 'cached' => false];
        }

        return [
            'usd' => $usd, 
            'eur' => $eur, 
            'cached' => true, 
            'error' => 'Scrape failed, using last cached'
        ];
    }

    private function scrapeBcvRates(): array {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://www.bcv.org.ve/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_FOLLOWLOCATION => true
        ]);
        $html = curl_exec($ch);
        curl_close($ch);

        $rates = ['usd' => 0, 'eur' => 0];
        if (!$html) return $rates;

        // Scrape Dólar
        if (preg_match('/<div[^>]*id=["\']dolar["\'][^>]*>.*?<strong[^>]*>\s*([\d.,]+)\s*<\/strong>/si', $html, $m)) {
            $rates['usd'] = (float)str_replace(',', '.', trim($m[1]));
        }
        // Scrape Euro
        if (preg_match('/<div[^>]*id=["\']euro["\'][^>]*>.*?<strong[^>]*>\s*([\d.,]+)\s*<\/strong>/si', $html, $m)) {
            $rates['eur'] = (float)str_replace(',', '.', trim($m[1]));
        }
        
        return $rates;
    }

    // Método depreciado por scrapeBcvRates
    private function scrapeBcvRate(): ?float {
        $rates = $this->scrapeBcvRates();
        return $rates['usd'] > 0 ? $rates['usd'] : null;
    }

    private function getUnreadCount(): int {
        try {
            $pdo  = \Core\Database::getInstance()->getDbh();
            $stmt = $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status='pendiente'");
            return (int)$stmt->fetchColumn();
        } catch (\Exception $e) { return 0; }
    }
}
