<?php
use Core\Model;

class Budget extends Model
{
    protected $table = 'monthly_budgets';

    public function getBudgetForMonth($userId, $monthYear)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = ? AND month_year = ?");
        $stmt->execute([$userId, $monthYear]);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    public function setBudget($userId, $monthYear, $amount)
    {
        $existing = $this->getBudgetForMonth($userId, $monthYear);
        if ($existing) {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET amount_limit = ? WHERE id = ?");
            return $stmt->execute([$amount, $existing->id]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO {$this->table} (user_id, month_year, amount_limit) VALUES (?, ?, ?)");
            return $stmt->execute([$userId, $monthYear, $amount]);
        }
    }
}
