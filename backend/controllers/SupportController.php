<?php
use Core\Controller;
use Core\Auth;
use Core\ActivityLogger;

class SupportController extends Controller {

    private $ticketModel;
    public function __construct() {
        if (!Auth::check()) { $this->redirect(URL_ROOT . '/auth/login'); }
        $this->ticketModel = $this->model('SupportTicket');
    }

    /** Vista principal */
    public function index() {
        $isAdmin = ($_SESSION['role'] ?? '') === 'admin';
        $userId  = Auth::id();
        $range   = $_GET['range'] ?? 'all';

        if ($isAdmin) {
            $tickets = $this->ticketModel->getAll();
            $stats   = $this->ticketModel->getStats($range);
        } else {
            $tickets = $this->ticketModel->getByUserId($userId);
            $stats   = [];
        }

        $success = $_SESSION['success'] ?? null;
        $error   = $_SESSION['error']   ?? null;
        unset($_SESSION['success'], $_SESSION['error']);

        $this->render('support', [
            'pageTitle'      => 'Soporte',
            'activeMenu'     => 'support',
            'isAdmin'        => $isAdmin,
            'tickets'        => $tickets,
            'stats'          => $stats,
            'range'          => $range,
            'ticketTypes'    => \SupportTicket::getTypes(),
            'success'        => $success,
            'error'          => $error,
            'unreadMessages' => $this->getUnreadCount(),
        ]);
    }

    /** Crear ticket (usuario) */
    public function store() {
        if (!Auth::check()) { $this->redirect(URL_ROOT . '/auth/login'); }

        $userId      = Auth::id();
        $type        = trim($_POST['type'] ?? 'general');
        $description = trim($_POST['description'] ?? '');

        if (empty($description)) {
            $_SESSION['error'] = 'La descripción no puede estar vacía.';
            $this->redirect(URL_ROOT . '/support');
        }

        $id = $this->ticketModel->create([
            'user_id'     => $userId,
            'type'        => $type,
            'description' => $description,
            'image_path'  => null,
        ]);

        ActivityLogger::log('Creó un ticket de soporte', 'support_ticket', $id);
        $_SESSION['success'] = 'Ticket enviado correctamente.';
        $this->redirect(URL_ROOT . '/support');
    }

    /** Editar ticket (usuario — solo si pendiente) */
    public function edit() {
        if (!Auth::check()) { $this->redirect(URL_ROOT . '/auth/login'); }

        $id     = (int)($_POST['id'] ?? 0);
        $userId = Auth::id();
        $ticket = $this->ticketModel->findById($id);

        if (!$ticket || $ticket->user_id != $userId || $ticket->status !== 'pendiente') {
            $_SESSION['error'] = 'No puedes editar este ticket.';
            $this->redirect(URL_ROOT . '/support');
        }

        $description = trim($_POST['description'] ?? '');
        if (empty($description)) {
            $_SESSION['error'] = 'La descripción no puede estar vacía.';
            $this->redirect(URL_ROOT . '/support');
        }

        $data = [
            'type'        => trim($_POST['type'] ?? $ticket->type),
            'description' => $description,
        ];



        $this->ticketModel->updateTicket($id, $userId, $data);
        ActivityLogger::log('Editó su ticket de soporte', 'support_ticket', $id);
        $_SESSION['success'] = 'Ticket actualizado.';
        $this->redirect(URL_ROOT . '/support');
    }

    /** Eliminar ticket (usuario — solo si pendiente) */
    public function delete() {
        if (!Auth::check()) { $this->redirect(URL_ROOT . '/auth/login'); }

        $id     = (int)($_POST['id'] ?? 0);
        $userId = Auth::id();
        $ticket = $this->ticketModel->findById($id);



        $this->ticketModel->deleteTicket($id, $userId);
        ActivityLogger::log('Eliminó un ticket de soporte', 'support_ticket', $id);
        $_SESSION['success'] = 'Ticket eliminado.';
        $this->redirect(URL_ROOT . '/support');
    }

    /** Cambiar estado del ticket (admin) */
    public function status() {
        if (($_SESSION['role'] ?? '') !== 'admin') {
            $this->redirect(URL_ROOT . '/dashboard');
        }

        $id        = (int)($_POST['id'] ?? 0);
        $status    = $_POST['status'] ?? 'pendiente';
        $adminNote = trim($_POST['admin_note'] ?? '');

        $allowed = ['pendiente', 'en_proceso', 'resuelto'];
        if (!in_array($status, $allowed)) $status = 'pendiente';

        $this->ticketModel->updateStatus($id, $status, $adminNote);
        ActivityLogger::log("Cambió estado de ticket a '{$status}'", 'support_ticket', $id);
        $_SESSION['success'] = 'Estado del ticket actualizado.';
        $this->redirect(URL_ROOT . '/support');
    }

    /** Estadísticas JSON para el gráfico (admin) */
    public function stats() {
        header('Content-Type: application/json');
        if (($_SESSION['role'] ?? '') !== 'admin') {
            echo json_encode(['error' => 'forbidden']); return;
        }
        $range = $_GET['range'] ?? 'all';
        echo json_encode($this->ticketModel->getStats($range));
    }

    // ── Helpers ───────────────────────────────────────────────



    private function getUnreadCount(): int {
        try {
            $pdo  = \Core\Database::getInstance()->getDbh();
            $stmt = $pdo->query(
                "SELECT COUNT(*) FROM support_tickets WHERE status = 'pendiente'"
            );
            return (int)$stmt->fetchColumn();
        } catch (\Exception $e) { return 0; }
    }
}
