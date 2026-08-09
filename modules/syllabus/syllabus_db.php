<?php
require_once __DIR__ . '/../../includes/helpers.php';

function getSyllabuses(array $filters = []): array
{
    $pdo = getDbConnection();
    $sql = 'SELECT sy.*, cl.name AS class_name, su.name AS subject_name, ay.name AS year_name
            FROM syllabuses sy
            LEFT JOIN classes cl ON cl.id = sy.class_id
            LEFT JOIN subjects su ON su.id = sy.subject_id
            LEFT JOIN academic_years ay ON ay.id = sy.year_id
            WHERE 1=1';
    $params = [];
    if (!empty($filters['class_id'])) {
        $sql .= ' AND sy.class_id = ?';
        $params[] = (int) $filters['class_id'];
    }
    if (!empty($filters['subject_id'])) {
        $sql .= ' AND sy.subject_id = ?';
        $params[] = (int) $filters['subject_id'];
    }
    if (!empty($filters['year_id'])) {
        $sql .= ' AND sy.year_id = ?';
        $params[] = (int) $filters['year_id'];
    }
    $sql .= ' ORDER BY ay.is_active DESC, sy.class_id, sy.subject_id, sy.created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function createSyllabus(array $data): int
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('INSERT INTO syllabuses (class_id, subject_id, year_id, title, description, term) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $data['class_id'],
        $data['subject_id'],
        $data['year_id'],
        $data['title'],
        $data['description'],
        $data['term'],
    ]);
    return (int) $pdo->lastInsertId();
}

function updateSyllabus(int $id, array $data): bool
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('UPDATE syllabuses SET class_id = ?, subject_id = ?, year_id = ?, title = ?, description = ?, term = ? WHERE id = ?');
    return $stmt->execute([
        $data['class_id'],
        $data['subject_id'],
        $data['year_id'],
        $data['title'],
        $data['description'],
        $data['term'],
        $id,
    ]);
}

function deleteSyllabus(int $id): bool
{
    $pdo = getDbConnection();
    return $pdo->prepare('DELETE FROM syllabuses WHERE id = ?')->execute([$id]);
}
