<?php
require_once __DIR__ . '/../../includes/helpers.php';

function getStudents(array $filters = [], string $columns = null): array
{
    $pdo = getDbConnection();
    $scope = getStudentScopeFilter();
    $joins = getStudentScopeJoins();

    $select = $columns ?? 's.*, c.name AS center_name, cl.name AS class_name,
                   v.name AS village_name, up.name AS upazila_name,
                   d.name AS district_name, dv.name AS division_name';

    $sql = "SELECT $select
            FROM students s
            LEFT JOIN centers c ON c.id = s.center_id
            LEFT JOIN classes cl ON cl.id = s.class_id
            $joins
            WHERE $scope";

    $params = [];

    if (!empty($filters['keyword'])) {
        $sql .= ' AND (s.name LIKE ? OR s.student_id LIKE ? OR s.guardian_name LIKE ? OR s.guardian_phone LIKE ?)';
        $like = '%' . $filters['keyword'] . '%';
        array_push($params, $like, $like, $like, $like);
    }
    if (!empty($filters['division_id'])) {
        $sql .= ' AND dv.id = ?';
        $params[] = (int) $filters['division_id'];
    }
    if (!empty($filters['district_id'])) {
        $sql .= ' AND d.id = ?';
        $params[] = (int) $filters['district_id'];
    }
    if (!empty($filters['upazila_id'])) {
        $sql .= ' AND up.id = ?';
        $params[] = (int) $filters['upazila_id'];
    }
    if (!empty($filters['village_id'])) {
        $sql .= ' AND s.village_id = ?';
        $params[] = (int) $filters['village_id'];
    }
    if (!empty($filters['center_id'])) {
        $sql .= ' AND s.center_id = ?';
        $params[] = (int) $filters['center_id'];
    }
    if (!empty($filters['class_id'])) {
        $sql .= ' AND s.class_id = ?';
        $params[] = (int) $filters['class_id'];
    }
    if (!empty($filters['status'])) {
        $sql .= ' AND s.status = ?';
        $params[] = $filters['status'];
    }

    $sql .= ' ORDER BY s.student_id ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getStudentById(int $id): ?array
{
    $pdo = getDbConnection();
    $scope = getStudentScopeFilter();
    $joins = getStudentScopeJoins();
    $stmt = $pdo->prepare("SELECT s.*, c.name AS center_name, cl.name AS class_name,
                                  v.name AS village_name, up.name AS upazila_name,
                                  d.name AS district_name, dv.name AS division_name,
                                  u.username AS login_username
                           FROM students s
                           LEFT JOIN centers c ON c.id = s.center_id
                           LEFT JOIN classes cl ON cl.id = s.class_id
                           LEFT JOIN users u ON u.id = s.user_id
                           $joins
                           WHERE s.id = ? AND $scope");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function getStudentByUserId(int $userId): ?array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT * FROM students WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function nextStudentId(): string
{
    $pdo = getDbConnection();
    $stmt = $pdo->query('SELECT student_id FROM students ORDER BY id DESC LIMIT 1');
    $last = $stmt->fetchColumn();
    if ($last && preg_match('/(\d+)$/', $last, $m)) {
        return 'GIT-' . str_pad((int) $m[1] + 1, 5, '0', STR_PAD_LEFT);
    }
    return 'GIT-00001';
}

function studentUsernameExists(string $username, ?int $excludeUserId = null): bool
{
    $pdo = getDbConnection();
    if ($excludeUserId === null) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id <> ?');
        $stmt->execute([$username, $excludeUserId]);
    }
    return $stmt->fetch() !== false;
}

function createStudent(array $data): int
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'INSERT INTO students (student_id, name, guardian_name, guardian_phone, dob, gender, village_id, center_id, class_id, admission_date, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $data['student_id'],
        $data['name'],
        $data['guardian_name'],
        $data['guardian_phone'],
        $data['dob'],
        $data['gender'],
        $data['village_id'],
        $data['center_id'],
        $data['class_id'],
        $data['admission_date'],
        $data['status'],
    ]);
    return (int) $pdo->lastInsertId();
}

function updateStudent(int $id, array $data): bool
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'UPDATE students SET name = ?, guardian_name = ?, guardian_phone = ?, dob = ?, gender = ?,
                village_id = ?, center_id = ?, class_id = ?, admission_date = ?, status = ?
         WHERE id = ?'
    );
    return $stmt->execute([
        $data['name'],
        $data['guardian_name'],
        $data['guardian_phone'],
        $data['dob'],
        $data['gender'],
        $data['village_id'],
        $data['center_id'],
        $data['class_id'],
        $data['admission_date'],
        $data['status'],
        $id,
    ]);
}

function deleteStudent(int $id): bool
{
    $pdo = getDbConnection();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT user_id FROM students WHERE id = ?');
        $stmt->execute([$id]);
        $userId = $stmt->fetchColumn();
        $pdo->prepare('DELETE FROM students WHERE id = ?')->execute([$id]);
        if ($userId) {
            $pdo->prepare('DELETE FROM users WHERE id = ? AND role_id = ' . ROLE_STUDENT)->execute([$userId]);
        }
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function createStudentLogin(int $studentId, string $username, string $plainPassword): int
{
    $pdo = getDbConnection();
    $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (name, username, password, role_id, is_active) VALUES (?, ?, ?, ' . ROLE_STUDENT . ', 1)');
    $stmt->execute([$username, $username, $hash]);
    $userId = (int) $pdo->lastInsertId();
    $pdo->prepare('UPDATE students SET user_id = ? WHERE id = ?')->execute([$userId, $studentId]);
    return $userId;
}

function getStudentDocuments(int $studentId): array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT * FROM student_documents WHERE student_id = ? ORDER BY id');
    $stmt->execute([$studentId]);
    return $stmt->fetchAll();
}

function addStudentDocument(int $studentId, string $filePath): bool
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('INSERT INTO student_documents (student_id, file_path) VALUES (?, ?)');
    return $stmt->execute([$studentId, $filePath]);
}

function studentStats(): array
{
    $pdo = getDbConnection();
    $scope = getStudentScopeFilter();
    $joins = getStudentScopeJoins();

    $sql = "SELECT COUNT(*) AS total,
                   COALESCE(SUM(status = 'Active'), 0) AS active,
                   COUNT(DISTINCT s.center_id) AS centers,
                   COUNT(DISTINCT s.class_id) AS classes
            FROM students s $joins WHERE $scope";
    return $pdo->query($sql)->fetch() ?: [];
}

function getStudentAttendanceSummary(int $studentId, ?int $yearId = null): array
{
    $pdo = getDbConnection();
    $params = [$studentId];
    $yearFilter = '';
    if ($yearId) {
        $yearFilter = ' AND ss.year_id = ?';
        $params[] = $yearId;
    }
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS total,
                COALESCE(SUM(a.status = 'Present'), 0) AS present
         FROM attendance a
         JOIN sessions ss ON ss.id = a.session_id
         WHERE a.student_id = ?$yearFilter"
    );
    $stmt->execute($params);
    $row = $stmt->fetch() ?: ['total' => 0, 'present' => 0];
    $row['rate'] = ((int) $row['total'] > 0) ? round(((int) $row['present'] / (int) $row['total']) * 100, 1) : 0;
    return $row;
}

function getStudentMarksSummary(int $studentId, ?int $yearId = null): array
{
    $pdo = getDbConnection();
    $params = [$studentId];
    $yearFilter = '';
    if ($yearId) {
        $yearFilter = ' AND t.year_id = ?';
        $params[] = $yearId;
    }
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS tasks,
                COALESCE(SUM(tr.completed), 0) AS completed,
                COALESCE(SUM(tr.obtained_marks), 0) AS obtained,
                COALESCE(SUM(t.total_marks), 0) AS possible
         FROM task_results tr
         JOIN tasks t ON t.id = tr.task_id
         WHERE tr.student_id = ?$yearFilter"
    );
    $stmt->execute($params);
    $row = $stmt->fetch() ?: ['tasks' => 0, 'completed' => 0, 'obtained' => 0, 'possible' => 0];
    $row['avg'] = ((int) $row['tasks'] > 0) ? round((float) $row['obtained'] / (int) $row['tasks'], 1) : 0;
    $row['pct'] = ((float) $row['possible'] > 0) ? round(((float) $row['obtained'] / (float) $row['possible']) * 100, 1) : 0;
    return $row;
}

function getStudentFeeSummary(int $studentId, ?int $yearId = null): array
{
    $pdo = getDbConnection();
    $params = [$studentId];
    $yearFilter = '';
    if ($yearId) {
        $yearFilter = ' AND year_id = ?';
        $params[] = $yearId;
    }
    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(amount), 0) AS billed,
                COALESCE(SUM(paid_amount), 0) AS paid,
                COUNT(*) AS records
         FROM fees WHERE student_id = ?$yearFilter"
    );
    $stmt->execute($params);
    $row = $stmt->fetch() ?: ['billed' => 0, 'paid' => 0, 'records' => 0];
    $row['due'] = round((float) $row['billed'] - (float) $row['paid'], 2);
    return $row;
}
