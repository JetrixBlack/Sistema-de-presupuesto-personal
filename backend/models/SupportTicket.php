<?php
use Core\Model;

class SupportTicket extends Model {
    protected $table = 'support_tickets';

    /** Tipos de problema disponibles */
    public static function getTypes(): array {
        return [
            'general'      => 'Problema General',
            'financiero'   => 'Problema Financiero',
            'acceso'       => 'Problema de Acceso',
            'rendimiento'  => 'Lentitud / Rendimiento',
            'sugerencia'   => 'Sugerencia de Mejora',
            'otro'         => 'Otro',
        ];
    }

    /** Tickets del usuario actual */
    public function getByUserId(int $userId): array {
        $stmt = $this->db->prepare(
            "SELECT st.*, u.username, u.full_name
             FROM {$this->table} st
             JOIN users u ON u.id = st.user_id
             WHERE st.user_id = ?
             ORDER BY st.created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /** Todos los tickets (admin) */
    public function getAll(): array {
        $stmt = $this->db->query(
            "SELECT st.*, u.username, u.full_name
             FROM {$this->table} st
             JOIN users u ON u.id = st.user_id
             ORDER BY
               FIELD(st.status, 'pendiente', 'en_proceso', 'resuelto'),
               st.created_at DESC"
        );
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /** Un ticket por ID */
    public function findById(int $id): ?object {
        $stmt = $this->db->prepare(
            "SELECT st.*, u.username, u.full_name
             FROM {$this->table} st
             JOIN users u ON u.id = st.user_id
             WHERE st.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_OBJ) ?: null;
    }

    /** Crear ticket */
    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} (user_id, type, description, image_path)
             VALUES (:user_id, :type, :description, :image_path)"
        );
        $stmt->execute($data);
        return (int)$this->db->lastInsertId();
    }

    /** Editar ticket (solo si pendiente) */
    public function updateTicket(int $id, int $userId, array $data): bool {
        $sets = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($data)));
        $data['id']      = $id;
        $data['user_id'] = $userId;
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET $sets
             WHERE id = :id AND user_id = :user_id AND status = 'pendiente'"
        );
        return $stmt->execute($data);
    }

    /** Cambiar estado (admin) */
    public function updateStatus(int $id, string $status, string $adminNote = ''): bool {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET status = ?, admin_note = ? WHERE id = ?"
        );
        return $stmt->execute([$status, $adminNote, $id]);
    }

    /** Eliminar ticket (solo si pendiente y es dueño) */
    public function deleteTicket(int $id, int $userId): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table}
             WHERE id = ? AND user_id = ? AND status = 'pendiente'"
        );
        return $stmt->execute([$id, $userId]);
    }

    /**
     * Estadísticas para el gráfico de pastel.
     * @param string $range 'today'|'week'|'month'|'all'
     */
    public function getStats(string $range = 'all'): array {
        $where = match($range) {
            'today' => "WHERE DATE(created_at) = CURDATE()",
            'week'  => "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            'month' => "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            default => "",
        };
        $stmt = $this->db->query(
            "SELECT status, COUNT(*) as total
             FROM {$this->table} $where
             GROUP BY status"
        );
        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $stats = ['pendiente' => 0, 'en_proceso' => 0, 'resuelto' => 0];
        foreach ($rows as $r) {
            $stats[$r->status] = (int)$r->total;
        }
        return $stats;
    }
}
