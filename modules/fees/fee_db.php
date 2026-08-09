<?php
require_once __DIR__ . '/../../includes/helpers.php';

function createFee(array $data): int
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'INSERT INTO fees (student_id, head_id, year_id, period_type, session_id, month, amount, paid_amount, status, due_date, paid_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $data['student_id'],
        $data['head_id'],
        $data['year_id'],
        $data['period_type'],
        $data['session_id'],
        $data['month'],
        $data['amount'],
        $data['paid_amount'],
        $data['status'],
        $data['due_date'],
        $data['paid_at'],
    ]);
    return (int) $pdo->lastInsertId();
}

function getFees(array $filters = []): array
{
    $pdo = getDbConnection();
    $scope = getStudentScopeFilter('s');
    $joins = getStudentScopeJoins();

    $sql = "SELECT f.*, s.name AS student_name, s.student_id, c.name AS center_name,
                   fh.name AS head_name, ss.session_date
            FROM fees f
            JOIN students s ON s.id = f.student_id
            LEFT JOIN centers c ON c.id = s.center_id
            LEFT JOIN fee_heads fh ON fh.id = f.head_id
            LEFT JOIN sessions ss ON ss.id = f.session_id
            $joins
            WHERE $scope";

    $params = [];
    if (!empty($filters['student_id'])) {
        $sql .= ' AND f.student_id = ?';
        $params[] = (int) $filters['student_id'];
    }
    if (!empty($filters['session_id'])) {
        $sql .= ' AND f.session_id = ?';
        $params[] = (int) $filters['session_id'];
    }
    if (!empty($filters['center_id'])) {
        $sql .= ' AND s.center_id = ?';
        $params[] = (int) $filters['center_id'];
    }
    if (!empty($filters['period_type'])) {
        $sql .= ' AND f.period_type = ?';
        $params[] = $filters['period_type'];
    }
    if (!empty($filters['status'])) {
        $sql .= ' AND f.status = ?';
        $params[] = $filters['status'];
    }
    if (!empty($filters['year_id'])) {
        $sql .= ' AND f.year_id = ?';
        $params[] = (int) $filters['year_id'];
    }

    $sql .= ' ORDER BY f.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getFeeById(int $id): ?array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT f.*, s.name AS student_name, s.student_id FROM fees f JOIN students s ON s.id = f.student_id WHERE f.id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function updateFeePayment(int $feeId, float $paidAmount, string $status): bool
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('UPDATE fees SET paid_amount = ?, status = ?, paid_at = NOW() WHERE id = ?');
    return $stmt->execute([$paidAmount, $status, $feeId]);
}

function deleteFee(int $id): bool
{
    $pdo = getDbConnection();
    return $pdo->prepare('DELETE FROM fees WHERE id = ?')->execute([$id]);
}

function createExpense(array $data): int
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('INSERT INTO expenses (center_id, year_id, user_id, category, description, amount, expense_date) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $data['center_id'],
        $data['year_id'],
        $data['user_id'],
        $data['category'],
        $data['description'],
        $data['amount'],
        $data['expense_date'],
    ]);
    return (int) $pdo->lastInsertId();
}

function getExpenses(array $filters = []): array
{
    $pdo = getDbConnection();
    $centerFilter = getCenterScopeFilter('c');
    $centerJoins = 'LEFT JOIN villages v ON v.id = c.village_id
                    LEFT JOIN upazilas up ON up.id = v.upazila_id
                    LEFT JOIN districts d ON d.id = up.district_id
                    LEFT JOIN divisions dv ON dv.id = d.division_id';

    $sql = "SELECT e.*, e.center_id, c.name AS center_name, u.name AS user_name
            FROM expenses e
            LEFT JOIN centers c ON c.id = e.center_id
            LEFT JOIN users u ON u.id = e.user_id
            $centerJoins
            WHERE $centerFilter";

    $params = [];
    if (!empty($filters['year_id'])) {
        $sql .= ' AND e.year_id = ?';
        $params[] = (int) $filters['year_id'];
    }
    if (!empty($filters['center_id'])) {
        $sql .= ' AND e.center_id = ?';
        $params[] = (int) $filters['center_id'];
    }
    if (!empty($filters['from'])) {
        $sql .= ' AND e.expense_date >= ?';
        $params[] = $filters['from'];
    }
    if (!empty($filters['to'])) {
        $sql .= ' AND e.expense_date <= ?';
        $params[] = $filters['to'];
    }

    $sql .= ' ORDER BY e.expense_date DESC, e.id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function deleteExpense(int $id): bool
{
    $pdo = getDbConnection();
    return $pdo->prepare('DELETE FROM expenses WHERE id = ?')->execute([$id]);
}

function feeStats(?int $yearId = null): array
{
    $pdo = getDbConnection();
    $scope = getStudentScopeFilter('s');
    $joins = getStudentScopeJoins();
    $yearFilter = $yearId ? ' AND f.year_id = ' . (int) $yearId : '';

    $stmt = $pdo->query(
        "SELECT COALESCE(SUM(f.amount), 0) AS billed,
                COALESCE(SUM(f.paid_amount), 0) AS collected,
                COUNT(*) AS records,
                COALESCE(SUM(f.status = 'Unpaid'), 0) AS unpaid
         FROM fees f
         JOIN students s ON s.id = f.student_id
         $joins
         WHERE $scope$yearFilter"
    );
    $row = $stmt->fetch() ?: ['billed' => 0, 'collected' => 0, 'records' => 0, 'unpaid' => 0];
    $row['due'] = round((float) $row['billed'] - (float) $row['collected'], 2);
    return $row;
}

function expenseStats(?int $yearId = null): array
{
    $pdo = getDbConnection();
    $centerFilter = getCenterScopeFilter('c');
    $centerJoins = 'LEFT JOIN centers c ON c.id = e.center_id
                    LEFT JOIN villages v ON v.id = c.village_id
                    LEFT JOIN upazilas up ON up.id = v.upazila_id
                    LEFT JOIN districts d ON d.id = up.district_id
                    LEFT JOIN divisions dv ON dv.id = d.division_id';
    $yearFilter = $yearId ? ' AND e.year_id = ' . (int) $yearId : '';

    $stmt = $pdo->query(
        "SELECT COALESCE(SUM(e.amount), 0) AS total, COUNT(*) AS records
         FROM expenses e $centerJoins WHERE $centerFilter$yearFilter"
    );
    return $stmt->fetch() ?: ['total' => 0, 'records' => 0];
}
