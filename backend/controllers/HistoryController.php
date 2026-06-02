<?php
use Core\Controller;
use Core\Auth;
use Core\ActivityLogger;

class HistoryController extends Controller {
    private $txModel;

    public function __construct() {
        if (!Auth::check()) { $this->redirect(URL_ROOT . '/auth/login'); }
        $this->txModel = $this->model('Transaction');
    }

    public function index() {
        $userId  = Auth::id();
        $isAdmin = ($_SESSION['role'] ?? '') === 'admin';

        // Obtener historial con nombres reales + filtro de privacidad
        $transactions = $this->txModel->getHistory($userId, $isAdmin);

        $this->render('history', [
            'pageTitle'      => 'Historial',
            'activeMenu'     => 'history',
            'transactions'   => $transactions,
            'isAdmin'        => $isAdmin,
            'unreadMessages' => 0,
        ]);
    }

    public function search() {
        header('Content-Type: application/json');
        $q       = trim($_GET['q'] ?? '');
        $userId  = Auth::id();
        $isAdmin = ($_SESSION['role'] ?? '') === 'admin';

        if (strlen($q) < 2) { echo json_encode([]); return; }

        $all    = $this->txModel->getHistory($userId, $isAdmin);
        $result = array_filter($all, fn($t) =>
            stripos($t->full_name ?? '', $q) !== false ||
            stripos($t->familiar  ?? '', $q) !== false ||
            stripos($t->details   ?? '', $q) !== false ||
            stripos($t->category  ?? '', $q) !== false
        );
        echo json_encode(array_values(array_slice($result, 0, 8)));
    }


}
