<?php
require_once __DIR__ . '/../../includes/helpers.php';

/**
 * Scope SQL for session queries. Sessions scope through their center's village chain.
 * Returns [filter, joins].
 */
function getSessionScopeSql(): array
{
    $role = (int) ($_SESSION['role_id'] ?? 0);
    if ($role === ROLE_ADMIN) {
        return [' 1=1 ', ''];
    }
    $user = currentUser();
    if ($role === ROLE_TEACHER) {
        return [' ss.center_id = ' . (int) ($user['center_id'] ?? 0) . ' ', ''];
    }
    if (($user['scope_type'] ?? '') === 'district') {
        return [
            ' d.id = ' . (int) ($user['scope_id'] ?? 0) . ' ',
            'LEFT JOIN centers c ON c.id = ss.center_id
             LEFT JOIN villages v ON v.id = c.village_id
             LEFT JOIN upazilas up ON up.id = v.upazila_id
             LEFT JOIN districts d ON d.id = up.district_id
             LEFT JOIN divisions dv ON dv.id = d.division_id',
        ];
    }
    if (($user['scope_type'] ?? '') === 'division') {
        return [
            ' dv.id = ' . (int) ($user['scope_id'] ?? 0) . ' ',
            'LEFT JOIN centers c ON c.id = ss.center_id
             LEFT JOIN villages v ON v.id = c.village_id
             LEFT JOIN upazilas up ON up.id = v.upazila_id
             LEFT JOIN districts d ON d.id = up.district_id
             LEFT JOIN divisions dv ON dv.id = d.division_id',
        ];
    }
    return [' 0 ', ''];
}

function getSessions(array $filters = []): array
{
    $pdo = getDbConnection();
    [$sessionFilter, $sessionJoins] = getSessionScopeSql();

    $sql = "SELECT ss.*, c.name AS center_name, u.name AS teacher_name,
                   (SELECT COUNT(*) FROM attendance a WHERE a.session_id = ss.id) AS marked_count
            FROM sessions ss
            LEFT JOIN centers c ON c.id = ss.center_id
            LEFT JOIN users u ON u.id = ss.teacher_id
            $sessionJoins
            WHERE $sessionFilter";

    $params = [];
    if (!empty($filters['center_id'])) {
        $sql .= ' AND ss.center_id = ?';
        $params[] = (int) $filters['center_id'];
    }
    if (!empty($filters['year_id'])) {
        $sql .= ' AND ss.year_id = ?';
        $params[] = (int) $filters['year_id'];
    }
    if (!empty($filters['from'])) {
        $sql .= ' AND ss.session_date >= ?';
        $params[] = $filters['from'];
    }
    if (!empty($filters['to'])) {
        $sql .= ' AND ss.session_date <= ?';
        $params[] = $filters['to'];
    }

    $sql .= ' ORDER BY ss.session_date DESC, ss.id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getSessionById(int $id): ?array
{
    $pdo = getDbConnection();
    [$sessionFilter, $sessionJoins] = getSessionScopeSql();
    $stmt = $pdo->prepare("SELECT ss.*, c.name AS center_name, c.village_id
                           FROM sessions ss
                           LEFT JOIN centers c ON c.id = ss.center_id
                           $sessionJoins
                           WHERE ss.id = ? AND $sessionFilter");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function createSession(array $data): int
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('INSERT INTO sessions (center_id, year_id, teacher_id, session_date, type, notes) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $data['center_id'],
        $data['year_id'],
        $data['teacher_id'],
        $data['session_date'],
        $data['type'],
        $data['notes'],
    ]);
    return (int) $pdo->lastInsertId();
}

function deleteSession(int $id): void
{
    $pdo = getDbConnection();
    $pdo->prepare('DELETE FROM sessions WHERE id = ?')->execute([$id]);
}

function getCenterStudents(int $centerId, ?int $classId = null): array
{
    $pdo = getDbConnection();
    $sql = 'SELECT s.*, cl.name AS class_name
            FROM students s
            LEFT JOIN classes cl ON cl.id = s.class_id
            WHERE s.center_id = ? AND s.status = "Active"';
    $params = [$centerId];
    if ($classId) {
        $sql .= ' AND s.class_id = ?';
        $params[] = $classId;
    }
    $sql .= ' ORDER BY s.name';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getAttendanceForSession(int $sessionId): array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT student_id, status FROM attendance WHERE session_id = ?');
    $stmt->execute([$sessionId]);
    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $map[(int) $row['student_id']] = $row['status'];
    }
    return $map;
}

function saveAttendance(int $sessionId, array $statuses): void
{
    $pdo = getDbConnection();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM attendance WHERE session_id = ?')->execute([$sessionId]);
        $insert = $pdo->prepare('INSERT INTO attendance (session_id, student_id, status) VALUES (?, ?, ?)');
        foreach ($statuses as $studentId => $status) {
            if ($status === 'Present' || $status === 'Absent') {
                $insert->execute([$sessionId, (int) $studentId, $status]);
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function attendanceStats(?int $yearId = null): array
{
    $pdo = getDbConnection();
    $centerFilter = getCenterScopeFilter('ss');
    $centerJoins = getCenterScopeJoins('ss');
    $yearFilter = $yearId ? ' AND ss.year_id = ' . (int) $yearId : '';

    $stmt = $pdo->query(
        "SELECT COUNT(DISTINCT ss.id) AS sessions,
                COUNT(a.id) AS records,
                COALESCE(SUM(a.status = 'Present'), 0) AS present
         FROM sessions ss
         $centerJoins
         LEFT JOIN attendance a ON a.session_id = ss.id
         WHERE $centerFilter$yearFilter"
    );
    $row = $stmt->fetch() ?: ['sessions' => 0, 'records' => 0, 'present' => 0];
    $row['rate'] = ((int) $row['records'] > 0) ? round(((int) $row['present'] / (int) $row['records']) * 100, 1) : 0;
    return $row;
}
