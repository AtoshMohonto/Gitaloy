<?php
require_once __DIR__ . '/../../includes/helpers.php';

function getPlans(array $filters = []): array
{
    $pdo = getDbConnection();
    $sql = 'SELECT p.*, di.name AS item_name, di.unit, u.name AS created_by_name,
                   COALESCE((SELECT SUM(dst.quantity) FROM distributions dst WHERE dst.plan_id = p.id), 0) AS distributed_qty,
                   (SELECT COUNT(DISTINCT dst.student_id) FROM distributions dst WHERE dst.plan_id = p.id) AS recipient_count
            FROM distribution_plans p
            LEFT JOIN distribution_items di ON di.id = p.item_id
            LEFT JOIN users u ON u.id = p.created_by
            WHERE 1=1';
    $params = [];
    if (!empty($filters['status'])) {
        $sql .= ' AND p.status = ?';
        $params[] = $filters['status'];
    }
    if (!empty($filters['year_id'])) {
        $sql .= ' AND p.year_id = ?';
        $params[] = (int) $filters['year_id'];
    }
    $sql .= ' ORDER BY p.created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getPlanById(int $id): ?array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'SELECT p.*, di.name AS item_name, di.unit
         FROM distribution_plans p
         LEFT JOIN distribution_items di ON di.id = p.item_id
         WHERE p.id = ?'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function createPlan(array $data): int
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'INSERT INTO distribution_plans (title, scope_type, scope_id, item_id, quantity, year_id, status, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $data['title'],
        $data['scope_type'],
        $data['scope_id'],
        $data['item_id'],
        $data['quantity'],
        $data['year_id'],
        $data['status'],
        $data['created_by'],
    ]);
    return (int) $pdo->lastInsertId();
}

function updatePlanStatus(int $id, string $status): bool
{
    $pdo = getDbConnection();
    return $pdo->prepare('UPDATE distribution_plans SET status = ? WHERE id = ?')->execute([$status, $id]);
}

function deletePlan(int $id): bool
{
    $pdo = getDbConnection();
    return $pdo->prepare('DELETE FROM distribution_plans WHERE id = ?')->execute([$id]);
}

function getPlanStudents(int $planId): array
{
    $pdo = getDbConnection();
    $plan = getPlanById($planId);
    $params = [];

    $joins = getStudentScopeJoins();
    $sql = "SELECT s.*, cl.name AS class_name, c.name AS center_name, v.name AS village_name
            FROM students s
            LEFT JOIN classes cl ON cl.id = s.class_id
            LEFT JOIN centers c ON c.id = s.center_id
            $joins
            WHERE s.status = 'Active'";
    $sql .= ' AND 1=1';

    if ($plan && $plan['scope_type'] === 'division' && !empty($plan['scope_id'])) {
        $sql .= ' AND dv.id = ?';
        $params[] = (int) $plan['scope_id'];
    } elseif ($plan && $plan['scope_type'] === 'district' && !empty($plan['scope_id'])) {
        $sql .= ' AND d.id = ?';
        $params[] = (int) $plan['scope_id'];
    }

    $sql .= ' ORDER BY s.name';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getPlanDistributions(int $planId): array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'SELECT dst.*, s.name AS student_name, s.student_id, di.name AS item_name,
                c.name AS center_name, v.name AS village_name
         FROM distributions dst
         JOIN students s ON s.id = dst.student_id
         LEFT JOIN distribution_items di ON di.id = dst.item_id
         LEFT JOIN centers c ON c.id = s.center_id
         LEFT JOIN villages v ON v.id = s.village_id
         WHERE dst.plan_id = ? ORDER BY dst.distributed_at DESC'
    );
    $stmt->execute([$planId]);
    return $stmt->fetchAll();
}

function saveDistributions(int $planId, array $qtys, ?string $notes, int $addedBy): void
{
    $pdo = getDbConnection();
    $plan = getPlanById($planId);
    $itemId = (int) ($plan['item_id'] ?? 0);
    if ($itemId <= 0) {
        throw new RuntimeException('Plan has no item assigned.');
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM distributions WHERE plan_id = ?')->execute([$planId]);
        $insert = $pdo->prepare('INSERT INTO distributions (plan_id, student_id, item_id, quantity, added_by, notes) VALUES (?, ?, ?, ?, ?, ?)');
        $total = 0;
        foreach ($qtys as $studentId => $qty) {
            $qty = (int) $qty;
            if ($qty > 0) {
                $insert->execute([$planId, (int) $studentId, $itemId, $qty, $addedBy, $notes]);
                $total += $qty;
            }
        }
        if ($total >= (int) ($plan['quantity'] ?? 0)) {
            updatePlanStatus($planId, 'Completed');
        } else {
            updatePlanStatus($planId, 'In Progress');
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function distributionStats(?int $yearId = null): array
{
    $pdo = getDbConnection();
    $yearFilter = $yearId ? ' AND dst.distributed_at IS NOT NULL AND p.year_id = ' . (int) $yearId : '';
    $stmt = $pdo->query(
        "SELECT COALESCE(SUM(dst.quantity), 0) AS total_qty,
                COUNT(DISTINCT dst.student_id) AS students,
                COUNT(DISTINCT dst.item_id) AS items
         FROM distributions dst
         LEFT JOIN distribution_plans p ON p.id = dst.plan_id
         WHERE dst.id IS NOT NULL$yearFilter"
    );
    return $stmt->fetch() ?: ['total_qty' => 0, 'students' => 0, 'items' => 0];
}
