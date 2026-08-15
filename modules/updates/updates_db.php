<?php
require_once __DIR__ . '/../../includes/helpers.php';

function classUpdateJoins(): string
{
    return 'LEFT JOIN users u ON u.id = cu.teacher_id
            LEFT JOIN centers c ON c.id = cu.center_id
            LEFT JOIN classes cl ON cl.id = cu.class_id
            LEFT JOIN villages v ON v.id = c.village_id
            LEFT JOIN upazilas up ON up.id = v.upazila_id
            LEFT JOIN districts d ON d.id = up.district_id
            LEFT JOIN divisions dv ON dv.id = d.division_id';
}

function getClassUpdates(?int $limit = 50): array
{
    $pdo = getDbConnection();
    $filter = getCenterScopeFilter('c');
    $sql = "SELECT cu.*, u.name AS teacher_name, c.name AS center_name, cl.name AS class_name
            FROM class_updates cu
            " . classUpdateJoins() . "
            WHERE $filter
            ORDER BY cu.created_at DESC";
    if ($limit !== null) {
        $sql .= ' LIMIT ' . (int) $limit;
    }
    return $pdo->query($sql)->fetchAll();
}

function getClassUpdateById(int $id): ?array
{
    $pdo = getDbConnection();
    $filter = getCenterScopeFilter('c');
    $stmt = $pdo->prepare(
        "SELECT cu.* FROM class_updates cu
         " . classUpdateJoins() . "
         WHERE cu.id = ? AND $filter"
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function getClassUpdatesForStudent(int $centerId, ?int $classId, int $limit = 20): array
{
    $pdo = getDbConnection();
    $params = [$centerId];
    $classFilter = '';
    if ($classId) {
        $classFilter = ' AND (cu.class_id IS NULL OR cu.class_id = ?)';
        $params[] = $classId;
    }
    $stmt = $pdo->prepare(
        "SELECT cu.*, u.name AS teacher_name, cl.name AS class_name
         FROM class_updates cu
         LEFT JOIN users u ON u.id = cu.teacher_id
         LEFT JOIN classes cl ON cl.id = cu.class_id
         WHERE cu.is_active = 1 AND cu.center_id = ?$classFilter
         ORDER BY cu.created_at DESC LIMIT " . (int) $limit
    );
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function createClassUpdate(array $data): int
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'INSERT INTO class_updates (teacher_id, center_id, class_id, title, body, photo, update_type, custom_label, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        (int) $data['teacher_id'],
        !empty($data['center_id']) ? (int) $data['center_id'] : null,
        !empty($data['class_id']) ? (int) $data['class_id'] : null,
        $data['title'],
        $data['body'] !== '' ? $data['body'] : null,
        $data['photo'] ?? null,
        $data['update_type'],
        !empty($data['custom_label']) ? $data['custom_label'] : null,
        1,
    ]);
    return (int) $pdo->lastInsertId();
}

function toggleClassUpdate(int $id): bool
{
    $pdo = getDbConnection();
    return $pdo->prepare('UPDATE class_updates SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
}

function deleteClassUpdate(int $id): bool
{
    $pdo = getDbConnection();
    return $pdo->prepare('DELETE FROM class_updates WHERE id = ?')->execute([$id]);
}
