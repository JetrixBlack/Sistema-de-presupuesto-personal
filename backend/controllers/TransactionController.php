<?php
use Core\Controller;
use Core\Auth;
use Core\Cache;

class TransactionController extends Controller {
    private $transactionModel;
    private $cache;

    public function __construct() {
        if (!Auth::check()) {
            header('Location: ' . URL_ROOT . '/auth/login');
            exit;
        }
        $this->transactionModel = $this->model('Transaction');
        $this->cache = new Cache();
    }

    public function store() {
        $userId = Auth::id();
        $mode = !empty($_POST['acreedor']) ? 'debt' : 'mov';
        $data = [
            'user_id' => $userId,
            'familiar' => $_POST['familiar'] ?? '',
            'amount' => floatval($_POST['amount'] ?? 0),
            'type' => $_POST['type'] ?? 'expense',
            'category' => $_POST['category'] ?? 'Otros',
            'details' => $_POST['details'] ?? '',
            'mode' => $mode,
            'status' => $mode === 'debt' ? 'pendiente' : 'pagado',
            'acreedor' => $_POST['acreedor'] ?? '',
            'transaction_date' => date('Y-m-d H:i:s')
        ];

        $this->transactionModel->create($data);
        
        $this->cache->clearAll(); // Limpiar toda la caché para actualizar dashboards al instante

        header('Location: ' . URL_ROOT . '/dashboard');
        exit;
    }

    public function pay() {
        $userId = Auth::id();
        $id = $_POST['id'] ?? null;
        if ($id) {
            $this->transactionModel->update($id, ['status' => 'pagado']);
            $this->cache->clearAll();
        }
        header('Location: ' . URL_ROOT . '/dashboard');
        exit;
    }

    public function delete() {
        $userId = Auth::id();
        $id = $_POST['id'] ?? null;
        if ($id) {
            $this->transactionModel->delete($id);
            $this->cache->clearAll();
        }
        header('Location: ' . URL_ROOT . '/dashboard');
        exit;
    }
}
