<?php
use Core\Controller;
use Core\Auth;
use Dompdf\Dompdf;
use Dompdf\Options;

class PdfController extends Controller {
    private $transactionModel;
    private $userModel;

    public function __construct() {
        if (!Auth::check()) {
            header('Location: ' . URL_ROOT . '/auth/login');
            exit;
        }
        // Allow all users, but logic inside methods will separate them.
        $this->transactionModel = $this->model('Transaction');
        $this->userModel = $this->model('User');
    }

    public function export() {
        $isAdmin = ($_SESSION['role'] ?? '') === 'admin';
        if ($isAdmin) {
            $this->generalReport();
        } else {
            $this->userReport(Auth::id());
        }
    }
    public function userReport($id = null) {
        if (!$id) {
            header('Location: ' . URL_ROOT . '/finance');
            exit;
        }

        $userId = (int)$id;
        $year   = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $month  = isset($_GET['month']) && $_GET['month'] !== '' ? (int)$_GET['month'] : (int)date('n');

        // Verify if user is admin or it's their own id
        if (($_SESSION['role'] ?? '') !== 'admin' && $userId !== Auth::id()) {
            header('Location: ' . URL_ROOT . '/dashboard');
            exit;
        }

        $user = $this->userModel->findById($userId);
        if (!$user) {
            header('Location: ' . URL_ROOT . '/finance');
            exit;
        }

        $data = $this->transactionModel->getMonthlyReportDataByUser($userId, $year, $month);
        
        $monthName = $this->getSpanishMonth($month) . ' ' . $year;
        
        $pdo = \Core\Database::getInstance()->getDbh();
        
        $inventory = [];

        // Fetch User Budget (only if monthly)
        $budget = null;
        if ($month) {
            $mY = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("
                SELECT mb.amount_limit,
                (SELECT SUM(ABS(amount)) FROM transactions t WHERE t.user_id = ? AND (t.type='expense' OR t.mode='debt') AND DATE_FORMAT(t.transaction_date, '%Y-%m') = ?) as current_expense
                FROM monthly_budgets mb 
                WHERE mb.user_id = ? AND mb.month_year = ?
            ");
            $stmt->execute([$userId, $mY, $userId, $mY]);
            $budget = $stmt->fetch(PDO::FETCH_OBJ);
        }

        $html = $this->getUserReportHtml($user, $data, $monthName, $inventory, $budget);
        $this->generatePdf($html, 'Reporte_' . $user->username . '_' . ($month ? $month : 'Anual') . '_' . $year . '.pdf');
    }

    public function generalReport() {
        if (($_SESSION['role'] ?? '') !== 'admin') {
            header('Location: ' . URL_ROOT . '/dashboard');
            exit;
        }

        $year  = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $month = isset($_GET['month']) && $_GET['month'] !== '' ? (int)$_GET['month'] : (int)date('n');

        $data = $this->transactionModel->getMonthlyReportDataGlobal($year, $month);
        
        $monthName = $this->getSpanishMonth($month) . ' ' . $year;

        $pdo = \Core\Database::getInstance()->getDbh();

        $inventory = [];

        // Fetch All Budgets (only if monthly)
        $budgets = [];
        if ($month) {
            $mY = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("
                SELECT u.id, u.full_name, u.username, mb.amount_limit,
                (SELECT SUM(ABS(amount)) FROM transactions t WHERE t.user_id = u.id AND (t.type='expense' OR t.mode='debt') AND DATE_FORMAT(t.transaction_date, '%Y-%m') = ?) as current_expense
                FROM users u
                LEFT JOIN monthly_budgets mb ON u.id = mb.user_id AND mb.month_year = ?
                WHERE u.role != 'admin'
            ");
            $stmt->execute([$mY, $mY]);
            $budgets = $stmt->fetchAll(PDO::FETCH_OBJ);
        }
        
        // Fetch Recent Support Tickets
        $stmt = $pdo->query("SELECT s.*, u.username FROM support_tickets s LEFT JOIN users u ON u.id = s.user_id ORDER BY created_at DESC LIMIT 50");
        $support = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        // Fetch History for the period
        $history = $this->transactionModel->getHistory(Auth::id(), true, $year, $month);

        $html = $this->getGeneralReportHtml($data, $monthName, $inventory, $budgets, $support, $history);
        $this->generatePdf($html, 'Reporte_General_' . ($month ? $month : 'Anual') . '_' . $year . '.pdf');
    }

    private function generatePdf($html, $filename) {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($filename, ['Attachment' => true]);
    }

    private function getSpanishMonth($monthNum) {
        $months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        return $months[$monthNum - 1];
    }

    private function getUserReportHtml($user, $data, $monthName, $inventory, $budget) {
        $metrics = $data['metrics'];
        $transactions = $data['transactions'];
        
        $balColor = $metrics['balance'] >= 0 ? '#10b981' : '#ef4444';

        $html = '
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <style>
                body { font-family: Helvetica, Arial, sans-serif; color: #333; margin: 0; padding: 0; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #8b5cf6; padding-bottom: 10px; }
                h1 { color: #4c1d95; margin: 0; font-size: 24px; }
                h2 { color: #6b7280; font-size: 16px; margin-top: 5px; }
                .user-info { margin-bottom: 20px; font-size: 14px; }
                .summary { width: 100%; margin-bottom: 30px; border-collapse: collapse; }
                .summary td { width: 25%; padding: 10px; text-align: center; border: 1px solid #e5e7eb; background: #f9fafb; }
                .summary strong { display: block; font-size: 12px; color: #6b7280; text-transform: uppercase; margin-bottom: 5px; }
                .summary span { font-size: 16px; font-weight: bold; }
                .table { width: 100%; border-collapse: collapse; font-size: 12px; }
                .table th { background: #8b5cf6; color: white; padding: 10px; text-align: left; }
                .table td { border-bottom: 1px solid #e5e7eb; padding: 10px; }
                .type-income { color: #10b981; font-weight: bold; }
                .type-expense { color: #ef4444; font-weight: bold; }
                .type-debt { color: #f59e0b; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Reporte Financiero Personal</h1>
                <h2>' . $monthName . '</h2>
            </div>
            
            <div class="user-info">
                <strong>Usuario:</strong> ' . htmlspecialchars($user->full_name) . ' (@' . htmlspecialchars($user->username) . ')
            </div>

            <table class="summary">
                <tr>
                    <td>
                        <strong>Ingresos</strong>
                        <span style="color: #10b981">Bs. ' . number_format($metrics['ingresos'], 2, ',', '.') . '</span>
                    </td>
                    <td>
                        <strong>Egresos</strong>
                        <span style="color: #ef4444">Bs. ' . number_format($metrics['egresos'], 2, ',', '.') . '</span>
                    </td>
                    <td>
                        <strong>Deudas (Pend.)</strong>
                        <span style="color: #f59e0b">Bs. ' . number_format($metrics['deudas'], 2, ',', '.') . '</span>
                    </td>
                    <td>
                        <strong>Balance Neto</strong>
                        <span style="color: ' . $balColor . '">Bs. ' . number_format($metrics['balance'], 2, ',', '.') . '</span>
                    </td>
                </tr>
            </table>

            <h3 style="color: #4c1d95; font-size: 14px; margin-bottom:10px;">Movimientos del Mes</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Categoría</th>
                        <th>Detalle</th>
                        <th style="text-align: right;">Monto</th>
                    </tr>
                </thead>
                <tbody>';

        if (empty($transactions)) {
            $html .= '<tr><td colspan="5" style="text-align:center; padding: 20px;">No hay movimientos registrados en este mes.</td></tr>';
        } else {
            foreach ($transactions as $t) {
                $typeLabel = '';
                $typeClass = '';
                if ($t->mode === 'debt') {
                    $typeLabel = 'Deuda (' . ucfirst($t->status) . ')';
                    $typeClass = 'type-debt';
                } elseif ($t->type === 'income') {
                    $typeLabel = 'Ingreso';
                    $typeClass = 'type-income';
                } else {
                    $typeLabel = 'Egreso';
                    $typeClass = 'type-expense';
                }

                $html .= '<tr>
                    <td>' . date('d/m/Y', strtotime($t->transaction_date)) . '</td>
                    <td><span class="' . $typeClass . '">' . $typeLabel . '</span></td>
                    <td>' . htmlspecialchars($t->category) . '</td>
                    <td>' . htmlspecialchars($t->details ?: '-') . '</td>
                    <td style="text-align: right;" class="' . $typeClass . '">Bs. ' . number_format($t->amount, 2, ',', '.') . '</td>
                </tr>';
            }
        }

        $html .= '</tbody>
            </table>
            
            <h3 style="color: #4c1d95; font-size: 14px; margin-top:30px; margin-bottom:10px;">Presupuesto Mensual</h3>';
            
        if ($budget) {
            $budgetLimit = $budget->amount_limit;
            $budgetExpense = $budget->current_expense;
            $pct = $budgetLimit > 0 ? ($budgetExpense / $budgetLimit) * 100 : 0;
            $html .= '<p><strong>Límite:</strong> Bs. ' . number_format($budgetLimit, 2, ',', '.') . ' | <strong>Gastado:</strong> Bs. ' . number_format($budgetExpense, 2, ',', '.') . ' (' . number_format($pct, 1) . '%)</p>';
        } else {
            $html .= '<p>No hay presupuesto definido para este mes.</p>';
        }
        $html .= '</body></html>';

        return $html;
    }

    private function getGeneralReportHtml($data, $monthName, $inventory, $budgets, $support, $history) {
        $html = '
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <style>
                body { font-family: Helvetica, Arial, sans-serif; color: #333; margin: 0; padding: 0;}
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #8b5cf6; padding-bottom: 10px; }
                h1 { color: #4c1d95; margin: 0; font-size: 24px; }
                h2 { color: #6b7280; font-size: 16px; margin-top: 5px; }
                .table { width: 100%; border-collapse: collapse; font-size: 12px; }
                .table th { background: #8b5cf6; color: white; padding: 10px; text-align: left; }
                .table td { border-bottom: 1px solid #e5e7eb; padding: 10px; }
                .positive { color: #10b981; font-weight: bold; }
                .negative { color: #ef4444; font-weight: bold; }
                .warning { color: #f59e0b; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Reporte Financiero General</h1>
                <h2>' . $monthName . '</h2>
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th style="text-align: right;">Ingresos</th>
                        <th style="text-align: right;">Egresos</th>
                        <th style="text-align: right;">Deudas (Pend.)</th>
                        <th style="text-align: right;">Balance Neto</th>
                    </tr>
                </thead>
                <tbody>';

        $totalIng = 0;
        $totalEgr = 0;
        $totalDeu = 0;
        $totalBal = 0;

        foreach ($data as $u) {
            $ing = (float)$u->ingresos;
            $egr = abs((float)$u->egresos);
            $deu = abs((float)$u->deudas);
            $bal = $ing - $egr - $deu;

            $totalIng += $ing;
            $totalEgr += $egr;
            $totalDeu += $deu;
            $totalBal += $bal;

            $balClass = $bal >= 0 ? 'positive' : 'negative';

            $html .= '<tr>
                <td><strong>' . htmlspecialchars($u->full_name) . '</strong><br><small style="color:#6b7280;">@' . htmlspecialchars($u->username) . '</small></td>
                <td style="text-align: right;" class="positive">Bs. ' . number_format($ing, 2, ',', '.') . '</td>
                <td style="text-align: right;" class="negative">Bs. ' . number_format($egr, 2, ',', '.') . '</td>
                <td style="text-align: right;" class="warning">Bs. ' . number_format($deu, 2, ',', '.') . '</td>
                <td style="text-align: right;" class="' . $balClass . '">Bs. ' . number_format($bal, 2, ',', '.') . '</td>
            </tr>';
        }

        $html .= '</tbody>
                <tfoot>
                    <tr>
                        <th style="background: #4c1d95; text-align:right;">TOTALES:</th>
                        <th style="background: #4c1d95; text-align:right;">Bs. ' . number_format($totalIng, 2, ',', '.') . '</th>
                        <th style="background: #4c1d95; text-align:right;">Bs. ' . number_format($totalEgr, 2, ',', '.') . '</th>
                        <th style="background: #4c1d95; text-align:right;">Bs. ' . number_format($totalDeu, 2, ',', '.') . '</th>
                        <th style="background: #4c1d95; text-align:right;">Bs. ' . number_format($totalBal, 2, ',', '.') . '</th>
                    </tr>
                </tfoot>
            </table>

            <h3 style="color: #4c1d95; font-size: 14px; margin-top:30px; margin-bottom:10px;">Presupuestos de Usuarios</h3>
            <table class="table">
                <thead><tr><th>Usuario</th><th>Límite</th><th>Gastado</th><th>Progreso</th></tr></thead>
                <tbody>';
        if (empty($budgets)) $html .= '<tr><td colspan="4">Sin presupuestos.</td></tr>';
        foreach ($budgets as $b) {
            $pct = $b->amount_limit > 0 ? ($b->current_expense / $b->amount_limit) * 100 : 0;
            $html .= '<tr><td>' . htmlspecialchars($b->username) . '</td><td>Bs. ' . number_format($b->amount_limit, 2, ',', '.') . '</td><td>Bs. ' . number_format($b->current_expense, 2, ',', '.') . '</td><td>' . number_format($pct, 1) . '%</td></tr>';
        }
        $html .= '</tbody></table>

            <h3 style="color: #4c1d95; font-size: 14px; margin-top:30px; margin-bottom:10px;">Tickets de Soporte (Recientes)</h3>
            <table class="table">
                <thead><tr><th>Fecha</th><th>Usuario</th><th>Estado</th><th>Detalle</th></tr></thead>
                <tbody>';
        if (empty($support)) $html .= '<tr><td colspan="4">Sin tickets.</td></tr>';
        foreach ($support as $s) {
            $html .= '<tr><td>' . date('d/m/Y', strtotime($s->created_at)) . '</td><td>' . htmlspecialchars($s->username) . '</td><td>' . $s->status . '</td><td>' . htmlspecialchars(substr($s->description, 0, 50)) . '...</td></tr>';
        }
        $html .= '</tbody></table>

            <h3 style="color: #4c1d95; font-size: 14px; margin-top:30px; margin-bottom:10px;">Historial del Sistema</h3>
            <table class="table">
                <thead><tr><th>Fecha</th><th>Usuario</th><th>Categoría</th><th>Tipo</th><th>Monto</th></tr></thead>
                <tbody>';
        if (empty($history)) $html .= '<tr><td colspan="5">Sin historial.</td></tr>';
        foreach (array_slice($history, 0, 100) as $h) { // limit to 100 to avoid huge PDFs
            $monto = number_format($h->amount, 2, ',', '.');
            $typeLabel = $h->type === 'income' ? 'Ingreso' : 'Egreso';
            if ($h->mode === 'debt') $typeLabel = 'Deuda';
            $html .= '<tr><td>' . date('d/m/Y', strtotime($h->transaction_date)) . '</td><td>' . htmlspecialchars($h->username ?? '-') . '</td><td>' . htmlspecialchars($h->category) . '</td><td>' . $typeLabel . '</td><td style="text-align:right;">Bs. ' . $monto . '</td></tr>';
        }
        $html .= '</tbody></table>
        </body>
        </html>';

        return $html;
    }
}
