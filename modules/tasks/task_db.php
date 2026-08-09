<?php
require_once __DIR__ . '/../../includes/helpers.php';

function getTasks(array $filters = []): array
{
    $pdo = getDbConnection();
    $sql = 'SELECT t.*, cl.name AS class_name, su.name AS subject_name, u.name AS teacher_name, ay.name AS year_name,
                   (SELECT COUNT(*) FROM task_results tr WHERE tr.task_id = t.id) AS marked_count
            FROM tasks t
            LEFT JOIN classes cl ON cl.id = t.class_id
            LEFT JOIN subjects su ON su.id = t.subject_id
            LEFT JOIN users u ON u.id = t.teacher_id
            LEFT JOIN academic_years ay ON ay.id = t.year_id
            WHERE 1=1';
    $params = [];
    if (!empty($filters['class_id'])) {
        $sql .= ' AND t.class_id = ?';
        $params[] = (int) $filters['class_id'];
    }
    if (!empty($filters['year_id'])) {
        $sql .= ' AND t.year_id = ?';
        $params[] = (int) $filters['year_id'];
    }
    if (!empty($filters['teacher_id']) && isTeacher()) {
        $sql .= ' AND t.teacher_id = ?';
        $params[] = (int) $filters['teacher_id'];
    }
    $sql .= ' ORDER BY t.created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function createTask(array $data): int
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('INSERT INTO tasks (class_id, subject_id, year_id, teacher_id, title, description, due_date, total_marks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $data['class_id'],
        $data['subject_id'],
        $data['year_id'],
        $data['teacher_id'],
        $data['title'],
        $data['description'],
        $data['due_date'],
        $data['total_marks'],
    ]);
    return (int) $pdo->lastInsertId();
}

function deleteTask(int $id): bool
{
    $pdo = getDbConnection();
    return $pdo->prepare('DELETE FROM tasks WHERE id = ?')->execute([$id]);
}

function getTaskById(int $id): ?array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function getTaskResults(int $taskId): array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'SELECT tr.*, s.name AS student_name, s.student_id, c.name AS center_name
         FROM task_results tr
         JOIN students s ON s.id = tr.student_id
         LEFT JOIN centers c ON c.id = s.center_id
         WHERE tr.task_id = ? ORDER BY s.name'
    );
    $stmt->execute([$taskId]);
    return $stmt->fetchAll();
}

function getUnmarkedStudents(int $taskId): array
{
    $pdo = getDbConnection();
    $task = getTaskById($taskId);
    $centerFilter = getStudentScopeFilter('s');
    $joins = getStudentScopeJoins();

    $sql = "SELECT s.*, cl.name AS class_name
            FROM students s
            LEFT JOIN classes cl ON cl.id = s.class_id
            $joins
            WHERE s.status = 'Active' AND $centerFilter";
    $params = [];

    if ($task && !empty($task['class_id'])) {
        $sql .= ' AND s.class_id = ?';
        $params[] = (int) $task['class_id'];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();

    $existing = getTaskResults($taskId);
    $existingIds = array_map('intval', array_column($existing, 'student_id'));

    return array_values(array_filter($students, fn($s) => !in_array((int) $s['id'], $existingIds, true)));
}

function saveTaskResult(int $taskId, int $studentId, float $obtained, bool $completed, ?string $remarks): bool
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'INSERT INTO task_results (task_id, student_id, obtained_marks, completed, remarks, marked_at)
         VALUES (?, ?, ?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE obtained_marks = VALUES(obtained_marks), completed = VALUES(completed), remarks = VALUES(remarks), marked_at = NOW()'
    );
    return $stmt->execute([$taskId, $studentId, $obtained, $completed ? 1 : 0, $remarks]);
}

function deleteTaskResult(int $taskId, int $studentId): bool
{
    $pdo = getDbConnection();
    return $pdo->prepare('DELETE FROM task_results WHERE task_id = ? AND student_id = ?')->execute([$taskId, $studentId]);
}
