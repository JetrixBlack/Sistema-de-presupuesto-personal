<?php
namespace Core;

/**
 * ActivityLogger — Registra automáticamente acciones del sistema.
 * Llamar desde los controladores tras operaciones exitosas.
 */
class ActivityLogger {

    public static function log(string $action, string $entity = null, int $entityId = null): void {
        try {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $userId   = $_SESSION['user_id'] ?? 0;
            $fullName = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Sistema');
            $role     = $_SESSION['role'] ?? 'user';

            if (!$userId) return;

            $pdo  = \Core\Database::getInstance()->getDbh();
            $stmt = $pdo->prepare(
                "INSERT INTO activity_log (user_id, full_name, role, action, entity, entity_id)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$userId, $fullName, $role, $action, $entity, $entityId]);
        } catch (\Exception $e) {
            // Silently fail — no romper la app por log
        }
    }
}
