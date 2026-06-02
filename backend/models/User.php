<?php
use Core\Model;

class User extends Model
{
    protected $table = 'users';



    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    public function findByUsername($username)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    public function update($id, $data)
    {
        $fields = "";
        $values = [];
        foreach ($data as $key => $value) {
            $fields .= "$key = ?, ";
            $values[] = $value;
        }
        $fields = rtrim($fields, ", ");
        $values[] = $id;

        $stmt = $this->db->prepare("UPDATE {$this->table} SET $fields WHERE id = ?");
        return $stmt->execute($values);
    }

    public function getActiveUsers($limit = 5) {
        $stmt = $this->db->prepare(
            "SELECT id, username, full_name, role, last_login
             FROM {$this->table}
             WHERE is_active = 1
             ORDER BY last_login DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public function getAll() {
        $stmt = $this->db->query(
            "SELECT id, username, full_name, role, is_active, last_login,
                    created_at, username_changed_at, profile_changed_at
             FROM {$this->table}
             ORDER BY created_at DESC"
        );
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public function getUsersForSelect() {
        // Excluir el administrador raíz (username='admin')
        $stmt = $this->db->query(
            "SELECT id, full_name, username 
             FROM {$this->table} 
             WHERE username != 'admin' AND is_active = 1
             ORDER BY full_name ASC"
        );
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /** Contar usuarios para autogenerar número de username */
    public function getNextUserNumber(): int {
        $stmt = $this->db->query("SELECT MAX(CAST(SUBSTRING(username, 8) AS UNSIGNED)) as max_num FROM {$this->table} WHERE username LIKE 'Usuario%'");
        $row  = $stmt->fetch(\PDO::FETCH_OBJ);
        return (int)($row->max_num ?? 0) + 1;
    }

    public function create($data) {
        $cols   = implode(', ', array_keys($data));
        $places = ':' . implode(', :', array_keys($data));
        $stmt   = $this->db->prepare("INSERT INTO {$this->table} ($cols) VALUES ($places)");
        return $stmt->execute($data);
    }

    public function setActive($id, $status) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET is_active = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function updateLastLogin($id) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET last_login = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function updateLastActivity($id) {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("UPDATE {$this->table} SET last_activity = ? WHERE id = ?");
        return $stmt->execute([$now, $id]);
    }

    /** Retorna usuarios con actividad en los últimos N minutos (online) */
    public function getOnlineUsers(int $minutesThreshold = 5): array {
        $limitTime = date('Y-m-d H:i:s', time() - ($minutesThreshold * 60));
        $stmt = $this->db->prepare(
            "SELECT id, username, full_name, role, last_activity
             FROM {$this->table}
             WHERE last_activity >= ?
               AND is_active = 1
             ORDER BY last_activity DESC"
        );
        $stmt->execute([$limitTime]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public function completeOnboarding($id) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET is_first_login = 0 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /** Verificar si pueden editarse nombre/usuario (14 días desde último cambio) */
    public function canEditProfile(int $userId): array {
        $user = $this->findById($userId);
        if (!$user) return ['name' => true, 'username' => true];

        $now = time();
        $limit = 14 * 24 * 3600; // 14 días en segundos

        $canName = !$user->profile_changed_at ||
                   ($now - strtotime($user->profile_changed_at)) >= $limit;
        $canUser = !$user->username_changed_at ||
                   ($now - strtotime($user->username_changed_at)) >= $limit;

        $daysName = $user->profile_changed_at
            ? max(0, 14 - floor(($now - strtotime($user->profile_changed_at)) / 86400))
            : 0;
        $daysUser = $user->username_changed_at
            ? max(0, 14 - floor(($now - strtotime($user->username_changed_at)) / 86400))
            : 0;

        return [
            'name'      => $canName,
            'username'  => $canUser,
            'days_name' => $daysName,
            'days_user' => $daysUser,
        ];
    }

    /** Métricas para el dashboard del admin */
    public function getMetrics(): array {
        $stmt = $this->db->query(
            "SELECT
               COUNT(*) as total,
               SUM(is_active = 1) as activos,
               SUM(is_active = 0) as inactivos
             FROM {$this->table}"
        );
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: ['total' => 0, 'activos' => 0, 'inactivos' => 0];
    }
}
