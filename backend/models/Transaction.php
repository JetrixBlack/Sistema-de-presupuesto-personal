<?php
use Core\Model;

class Transaction extends Model {
    protected $table = 'transactions';

    /** Columnas permitidas para INSERT/UPDATE (whitelist SQL-injection prevention) */
    private const ALLOWED_COLUMNS = [
        'user_id', 'type', 'mode', 'amount', 'description',
        'details', 'category', 'transaction_date', 'status',
        'recipient_user_id', 'inventory_items',
    ];

    /** Filtra el array $data dejando solo columnas de la whitelist */
    private function sanitizeData(array $data): array {
        return array_filter($data, fn($key) => in_array($key, self::ALLOWED_COLUMNS, true), ARRAY_FILTER_USE_KEY);
    }

    /** Buscar transacción por ID */
    public function findById(int $id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    /** Transacciones del usuario (vista personal de finanzas) */
    public function getByUserId($userId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY transaction_date ASC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Historial con nombre real del usuario y filtros opcionales de fecha.
     * Privacidad: usuarios normales NO ven las acciones del admin.
     */
    public function getHistory(int $userId, bool $isAdmin, ?int $year = null, ?int $month = null): array {
        $year = $year ?? (int)date('Y');
        $monthSql = $month ? "AND MONTH(t.transaction_date) = ?" : "";

        if ($isAdmin) {
            // Admin ve todo con nombre completo
            $sql = "SELECT t.*, u.full_name, u.username, u.role as user_role
                    FROM {$this->table} t
                    JOIN users u ON u.id = t.user_id
                    WHERE YEAR(t.transaction_date) = ? $monthSql
                    ORDER BY t.transaction_date ASC";
            $params = [$year];
            if ($month) $params[] = $month;
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        } else {
            // RLS: Usuario normal solo ve SUS PROPIOS registros
            $sql = "SELECT t.*, u.full_name, u.username, u.role as user_role
                    FROM {$this->table} t
                    JOIN users u ON u.id = t.user_id
                    WHERE t.user_id = ? AND YEAR(t.transaction_date) = ? $monthSql
                    ORDER BY t.transaction_date ASC";
            $params = [$userId, $year];
            if ($month) $params[] = $month;
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        }
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /** Transacciones globales del sistema para cálculos administrativos */
    public function getAllSystem(): array {
        $stmt = $this->db->query(
            "SELECT * FROM {$this->table} ORDER BY transaction_date ASC"
        );
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public function create($data) {
        $data         = $this->sanitizeData($data); // SQL injection prevention
        $fields       = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} ($fields) VALUES ($placeholders)"
        );
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $data   = $this->sanitizeData($data); // SQL injection prevention
        $fields = "";
        foreach ($data as $key => $value) {
            $fields .= "$key = :$key, ";
        }
        $fields     = rtrim($fields, ", ");
        $data['id'] = $id;

        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET $fields WHERE id = :id"
        );
        return $stmt->execute($data);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /** Métricas de finanzas para un usuario (dashboard) con filtros opcionales */
    public function getFinanceMetrics(int $userId, ?int $year = null, ?int $month = null): array {
        $cache = new \Core\Cache();
        $cacheKey = "finance_metrics_{$userId}_{$year}_{$month}";
        $cachedData = $cache->get($cacheKey);
        if ($cachedData !== null) {
            return $cachedData;
        }

        $sql = "SELECT
               SUM(CASE WHEN type='income' AND mode='mov' THEN amount ELSE 0 END) as ingresos,
               SUM(CASE WHEN (type='expense' AND mode='mov') OR (mode='debt' AND status='pagado') THEN amount ELSE 0 END) as egresos,
               SUM(CASE WHEN mode='debt' AND status='pendiente' THEN amount ELSE 0 END) as deudas_pendientes,
               COUNT(*) as total
             FROM {$this->table}
             WHERE user_id = ?";
             
        $params = [$userId];
        if ($year) {
            $sql .= " AND YEAR(transaction_date) = ?";
            $params[] = $year;
        }
        if ($month) {
            $sql .= " AND MONTH(transaction_date) = ?";
            $params[] = $month;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $ingresos = (float)($row['ingresos'] ?? 0);
        $egresos  = (float)($row['egresos'] ?? 0); 
        $deudas   = (float)($row['deudas_pendientes'] ?? 0);
        
        $result = [
            'ingresos'          => $ingresos,
            'egresos'           => $egresos,
            'deudas_pendientes' => $deudas,
            'balance'           => $ingresos + $egresos + $deudas, 
            'total'             => (int)($row['total'] ?? 0),
        ];

        $cache->set($cacheKey, $result, 300); // 5 minutes TTL
        return $result;
    }

    /** Últimas N transacciones del usuario para el dashboard */
    public function getRecentByUserId(int $userId, int $limit = 8): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table}
             WHERE user_id = ?
             ORDER BY transaction_date ASC
             LIMIT ?"
        );
        $stmt->bindValue(1, $userId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit,  \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /** Estadísticas globales por usuario para el administrador */
    public function getGlobalFinanceStats(): array {
        $stmt = $this->db->query(
            "SELECT 
                u.id, 
                u.full_name, 
                u.username,
                SUM(CASE WHEN t.type='income' AND t.mode='mov' THEN t.amount ELSE 0 END) as ingresos,
                SUM(CASE WHEN t.type='expense' AND t.mode='mov' THEN ABS(t.amount) ELSE 0 END) as egresos
             FROM users u
             LEFT JOIN {$this->table} t ON u.id = t.user_id
             WHERE u.role != 'admin'
             GROUP BY u.id, u.full_name, u.username
             ORDER BY u.full_name ASC"
        );
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /** Datos del reporte para un usuario específico con filtros de fecha */
    public function getMonthlyReportDataByUser(int $userId, ?int $year = null, ?int $month = null): array {
        $year = $year ?? (int)date('Y');
        $monthSql = $month ? "AND MONTH(transaction_date) = ?" : "";
        $params = [$userId, $year];
        if ($month) $params[] = $month;

        // Movimientos
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table}
             WHERE user_id = ? AND YEAR(transaction_date) = ? $monthSql
             ORDER BY transaction_date ASC"
        );
        $stmt->execute($params);
        $transactions = $stmt->fetchAll(\PDO::FETCH_OBJ);
        
        // Métricas
        $stmtMetrics = $this->db->prepare(
            "SELECT
               SUM(CASE WHEN type='income' AND mode='mov' THEN amount ELSE 0 END) as ingresos,
               SUM(CASE WHEN (type='expense' AND mode='mov') OR (mode='debt' AND status='pagado') THEN amount ELSE 0 END) as egresos,
               SUM(CASE WHEN mode='debt' AND status='pendiente' THEN amount ELSE 0 END) as deudas_pendientes
             FROM {$this->table}
             WHERE user_id = ? AND YEAR(transaction_date) = ? $monthSql"
        );
        $stmtMetrics->execute($params);
        $metrics = $stmtMetrics->fetch(\PDO::FETCH_ASSOC);

        $ingresos = (float)($metrics['ingresos'] ?? 0);
        $egresos = (float)($metrics['egresos'] ?? 0);
        $deudas = (float)($metrics['deudas_pendientes'] ?? 0);

        return [
            'transactions' => $transactions,
            'metrics' => [
                'ingresos' => $ingresos,
                'egresos' => abs($egresos),
                'deudas' => abs($deudas),
                'balance' => $ingresos + $egresos + $deudas
            ]
        ];
    }

    /** Datos del reporte mensual global (todos los usuarios) */
    public function getMonthlyReportDataGlobal(?int $year = null, ?int $month = null): array {
        $year = $year ?? (int)date('Y');
        $monthSql = $month ? "AND MONTH(t.transaction_date) = ?" : "";
        $params = [$year];
        if ($month) $params[] = $month;

        $stmt = $this->db->prepare(
            "SELECT 
                u.id, 
                u.full_name, 
                u.username,
                SUM(CASE WHEN t.type='income' AND t.mode='mov' THEN t.amount ELSE 0 END) as ingresos,
                SUM(CASE WHEN (t.type='expense' AND t.mode='mov') OR (t.mode='debt' AND t.status='pagado') THEN t.amount ELSE 0 END) as egresos,
                SUM(CASE WHEN t.mode='debt' AND t.status='pendiente' THEN t.amount ELSE 0 END) as deudas
             FROM users u
             LEFT JOIN {$this->table} t ON u.id = t.user_id AND YEAR(t.transaction_date) = ? $monthSql
             WHERE u.role != 'admin'
             GROUP BY u.id, u.full_name, u.username
             ORDER BY u.full_name ASC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
}
