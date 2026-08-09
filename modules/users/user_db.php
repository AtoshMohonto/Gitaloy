<?php
require_once __DIR__ . '/../../includes/auth.php';

function createUser(array $data): int
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'INSERT INTO users (name, username, email, password, role_id, phone, scope_type, scope_id, center_id, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $data['name'],
        $data['username'] !== '' ? $data['username'] : null,
        $data['email'] !== '' ? $data['email'] : null,
        password_hash($data['password'], PASSWORD_DEFAULT),
        (int) $data['role_id'],
        $data['phone'] !== '' ? $data['phone'] : null,
        $data['scope_type'] !== '' ? $data['scope_type'] : null,
        !empty($data['scope_id']) ? (int) $data['scope_id'] : null,
        !empty($data['center_id']) ? (int) $data['center_id'] : null,
        $data['is_active'] ?? 1,
    ]);
    return (int) $pdo->lastInsertId();
}

function userEmailExists(string $email, ?int $excludeId = null): bool
{
    $pdo = getDbConnection();
    if ($excludeId !== null) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stmt->execute([$email, $excludeId]);
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
    }
    return $stmt->fetch() !== false;
}

function userUsernameExists(string $username, ?int $excludeId = null): bool
{
    $pdo = getDbConnection();
    if ($excludeId !== null) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
        $stmt->execute([$username, $excludeId]);
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
    }
    return $stmt->fetch() !== false;
}

function getUserById(int $id): ?array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function getAllUsers(): array
{
    $pdo = getDbConnection();
    return $pdo->query(
        'SELECT u.*,
                COALESCE(r.name, "Unknown") AS role_name,
                d.name AS division_name, dd.name AS district_name, c.name AS center_name
         FROM users u
         LEFT JOIN roles r ON r.id = u.role_id
         LEFT JOIN divisions d ON d.id = u.scope_id AND u.scope_type = "division"
         LEFT JOIN districts dd ON dd.id = u.scope_id AND u.scope_type = "district"
         LEFT JOIN centers c ON c.id = u.center_id
         ORDER BY u.created_at DESC'
    )->fetchAll();
}

function getScopedUsers(?array $user = null): array
{
    $pdo = getDbConnection();
    $user = $user ?? currentUser();

    if ((int) $user['role_id'] === ROLE_ADMIN) {
        $filter = '1=1';
    } elseif (($user['scope_type'] ?? '') === 'division') {
        $filter = 'dv.id = ' . (int) $user['scope_id'];
    } elseif (($user['scope_type'] ?? '') === 'district') {
        $filter = 'dd.id = ' . (int) $user['scope_id'];
    } else {
        $filter = '0';
    }

    return $pdo->query(
        'SELECT u.*,
                COALESCE(r.name, "Unknown") AS role_name,
                c.name AS center_name, v.name AS village_name
         FROM users u
         LEFT JOIN roles r ON r.id = u.role_id
         LEFT JOIN centers c ON c.id = u.center_id
         LEFT JOIN villages v ON v.id = c.village_id
         LEFT JOIN upazilas up ON up.id = v.upazila_id
         LEFT JOIN districts dd ON dd.id = up.district_id
         LEFT JOIN divisions dv ON dv.id = dd.division_id
         WHERE ' . $filter . ' ORDER BY u.created_at DESC'
    )->fetchAll();
}

function toggleUserActive(int $id): bool
{
    $pdo = getDbConnection();
    return $pdo->prepare('UPDATE users SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
}

function resetUserPassword(int $id, string $plain): bool
{
    $pdo = getDbConnection();
    return $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($plain, PASSWORD_DEFAULT), $id]);
}
