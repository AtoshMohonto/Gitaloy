<?php
require_once __DIR__ . '/auth.php';

function getDivisions(): array
{
    $pdo = getDbConnection();
    return $pdo->query(
        'SELECT dv.*,
                (SELECT COUNT(*) FROM districts d WHERE d.division_id = dv.id) AS district_count
         FROM divisions dv ORDER BY dv.name'
    )->fetchAll();
}

function getDistricts(?int $divisionId = null): array
{
    $pdo = getDbConnection();
    if ($divisionId !== null) {
        $stmt = $pdo->prepare(
            'SELECT d.*,
                    (SELECT COUNT(*) FROM upazilas u WHERE u.district_id = d.id) AS upazila_count
             FROM districts d WHERE d.division_id = ? ORDER BY d.name'
        );
        $stmt->execute([$divisionId]);
        return $stmt->fetchAll();
    }
    return $pdo->query(
        'SELECT d.*,
                (SELECT COUNT(*) FROM upazilas u WHERE u.district_id = d.id) AS upazila_count
         FROM districts d ORDER BY d.name'
    )->fetchAll();
}

function getUpazilas(?int $districtId = null): array
{
    $pdo = getDbConnection();
    if ($districtId !== null) {
        $stmt = $pdo->prepare(
            'SELECT u.*,
                    (SELECT COUNT(*) FROM villages v WHERE v.upazila_id = u.id) AS village_count
             FROM upazilas u WHERE u.district_id = ? ORDER BY u.name'
        );
        $stmt->execute([$districtId]);
        return $stmt->fetchAll();
    }
    return $pdo->query(
        'SELECT u.*,
                (SELECT COUNT(*) FROM villages v WHERE v.upazila_id = u.id) AS village_count
         FROM upazilas u ORDER BY u.name'
    )->fetchAll();
}

function getVillages(?int $upazilaId = null): array
{
    $pdo = getDbConnection();
    if ($upazilaId !== null) {
        $stmt = $pdo->prepare('SELECT * FROM villages WHERE upazila_id = ? ORDER BY name');
        $stmt->execute([$upazilaId]);
        return $stmt->fetchAll();
    }
    return $pdo->query('SELECT * FROM villages ORDER BY name')->fetchAll();
}

function getCenters(?int $villageId = null): array
{
    $pdo = getDbConnection();
    if ($villageId !== null) {
        $stmt = $pdo->prepare('SELECT * FROM centers WHERE village_id = ? ORDER BY name');
        $stmt->execute([$villageId]);
        return $stmt->fetchAll();
    }
    return $pdo->query('SELECT * FROM centers ORDER BY name')->fetchAll();
}

function getClasses(): array
{
    $pdo = getDbConnection();
    return $pdo->query('SELECT * FROM classes ORDER BY name')->fetchAll();
}

function getSubjects(): array
{
    $pdo = getDbConnection();
    return $pdo->query('SELECT * FROM subjects ORDER BY name')->fetchAll();
}

function getAcademicYears(): array
{
    $pdo = getDbConnection();
    return $pdo->query('SELECT * FROM academic_years ORDER BY is_active DESC, id DESC')->fetchAll();
}

function getFeeHeads(): array
{
    $pdo = getDbConnection();
    return $pdo->query('SELECT * FROM fee_heads ORDER BY name')->fetchAll();
}

function getDistributionItems(): array
{
    $pdo = getDbConnection();
    return $pdo->query('SELECT * FROM distribution_items ORDER BY name')->fetchAll();
}

function getTeachers(): array
{
    $pdo = getDbConnection();
    return $pdo->query('SELECT * FROM users WHERE role_id = ' . ROLE_TEACHER . ' ORDER BY name')->fetchAll();
}

/**
 * Zone scope filter for student queries.
 * Requires aliases: s = students, v = villages, up = upazilas, d = districts, dv = divisions.
 */
function getStudentScopeFilter(string $studentAlias = 's'): string
{
    $role = (int) ($_SESSION['role_id'] ?? 0);

    if ($role === ROLE_ADMIN) {
        return ' 1=1 ';
    }

    $user = currentUser();

    if ($role === ROLE_TEACHER) {
        return ' ' . $studentAlias . '.center_id = ' . (int) ($user['center_id'] ?? 0) . ' ';
    }

    if (($user['scope_type'] ?? '') === 'district') {
        return ' d.id = ' . (int) ($user['scope_id'] ?? 0) . ' ';
    }

    if (($user['scope_type'] ?? '') === 'division') {
        return ' dv.id = ' . (int) ($user['scope_id'] ?? 0) . ' ';
    }

    return ' 0 ';
}

/**
 * Join clause that must accompany getStudentScopeFilter().
 */
function getStudentScopeJoins(): string
{
    return 'LEFT JOIN villages v ON v.id = s.village_id
            LEFT JOIN upazilas up ON up.id = v.upazila_id
            LEFT JOIN districts d ON d.id = up.district_id
            LEFT JOIN divisions dv ON dv.id = d.division_id';
}

/**
 * Zone filter for sessions/centers queries.
 * Requires alias c = centers and village joins c->village->upazila->district->division.
 */
function getCenterScopeFilter(string $centerAlias = 'c'): string
{
    $role = (int) ($_SESSION['role_id'] ?? 0);

    if ($role === ROLE_ADMIN) {
        return ' 1=1 ';
    }

    $user = currentUser();

    if ($role === ROLE_TEACHER) {
        return ' ' . $centerAlias . '.id = ' . (int) ($user['center_id'] ?? 0) . ' ';
    }

    if (($user['scope_type'] ?? '') === 'district') {
        return ' d.id = ' . (int) ($user['scope_id'] ?? 0) . ' ';
    }

    if (($user['scope_type'] ?? '') === 'division') {
        return ' dv.id = ' . (int) ($user['scope_id'] ?? 0) . ' ';
    }

    return ' 0 ';
}

function getCenterScopeJoins(string $centerAlias = 'c'): string
{
    return 'LEFT JOIN villages v ON v.id = ' . $centerAlias . '.village_id
            LEFT JOIN upazilas up ON up.id = v.upazila_id
            LEFT JOIN districts d ON d.id = up.district_id
            LEFT JOIN divisions dv ON dv.id = d.division_id';
}

function geoLabels(?array $student): array
{
    if (empty($student['village_id'])) {
        return ['', '', '', ''];
    }
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'SELECT v.name AS village, up.name AS upazila, d.name AS district, dv.name AS division
         FROM villages v
         LEFT JOIN upazilas up ON up.id = v.upazila_id
         LEFT JOIN districts d ON d.id = up.district_id
         LEFT JOIN divisions dv ON dv.id = d.division_id
         WHERE v.id = ?'
    );
    $stmt->execute([$student['village_id']]);
    $row = $stmt->fetch() ?: [];
    return [
        $row['village'] ?? '',
        $row['upazila'] ?? '',
        $row['district'] ?? '',
        $row['division'] ?? '',
    ];
}

function getSettings(): array
{
    $pdo = getDbConnection();
    $rows = $pdo->query('SELECT skey, svalue FROM settings')->fetchAll();
    $out = [];
    foreach ($rows as $r) {
        $out[$r['skey']] = $r['svalue'];
    }
    return $out;
}

function saveSetting(string $key, ?string $value): void
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'INSERT INTO settings (skey, svalue) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)'
    );
    $stmt->execute([$key, $value]);
}
function getAllRoles(): array
{
    $pdo = getDbConnection();
    return $pdo->query(
        'SELECT r.*,
                (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id) AS perm_count,
                (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id) AS user_count
         FROM roles r ORDER BY r.id'
    )->fetchAll();
}

function getRoleById(int $id): ?array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT * FROM roles WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function createRole(string $name, string $description): int
{
    $pdo = getDbConnection();
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '_', $name), '_'));
    $slug = $slug !== '' ? $slug : 'role_' . time();
    $stmt = $pdo->prepare('INSERT INTO roles (name, slug, description, is_system) VALUES (?, ?, ?, 0)');
    $stmt->execute([$name, $slug, $description]);
    return (int) $pdo->lastInsertId();
}

function updateRole(int $id, string $name, string $description): bool
{
    $role = getRoleById($id);
    if ($role === null || (int) $role['is_system'] === 1) {
        return false;
    }
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('UPDATE roles SET name = ?, description = ? WHERE id = ?');
    $stmt->execute([$name, $description, $id]);
    return true;
}

function deleteRole(int $id): bool
{
    $role = getRoleById($id);
    if ($role === null || (int) $role['is_system'] === 1) {
        return false;
    }
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role_id = ?');
    $stmt->execute([$id]);
    if ((int) $stmt->fetchColumn() > 0) {
        return false;
    }
    $pdo->prepare('DELETE FROM role_permissions WHERE role_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM roles WHERE id = ?')->execute([$id]);
    return true;
}

function getAllPermissions(): array
{
    $pdo = getDbConnection();
    return $pdo->query('SELECT * FROM permissions ORDER BY pgroup, sort, id')->fetchAll();
}

function rolePermissionIds(int $roleId): array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT permission_id FROM role_permissions WHERE role_id = ?');
    $stmt->execute([$roleId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function saveRolePermissions(int $roleId, array $permissionIds): void
{
    $pdo = getDbConnection();
    $pdo->prepare('DELETE FROM role_permissions WHERE role_id = ?')->execute([$roleId]);
    $stmt = $pdo->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)');
    foreach (array_unique(array_map('intval', $permissionIds)) as $permissionId) {
        $stmt->execute([$roleId, $permissionId]);
    }
}

function hasPermission(string $key, ?int $roleId = null): bool
{
    static $cache = [];
    $roleId = $roleId ?? (int) ($_SESSION['role_id'] ?? 0);
    if ($roleId === ROLE_ADMIN) {
        return true;
    }
    if ($roleId <= 0) {
        return false;
    }
    $cacheKey = $roleId . '|' . $key;
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT 1 FROM role_permissions rp JOIN permissions p ON p.id = rp.permission_id WHERE rp.role_id = ? AND p.pkey = ?');
    $stmt->execute([$roleId, $key]);
    return $cache[$cacheKey] = $stmt->fetch() !== false;
}

function hasAnyPermission(array $keys): bool
{
    foreach ($keys as $key) {
        if (hasPermission($key)) {
            return true;
        }
    }
    return false;
}

function requirePermission(string $key): void
{
    if (!hasPermission($key)) {
        header('Location: ' . appBaseUrl() . '/modules/dashboard/index.php');
        exit;
    }
}

/**
 * Saves an uploaded JPG photo and returns its relative path (e.g. "uploads/photos/abc.jpg").
 * Throws a RuntimeException with a friendly message when the file is not a valid JPG.
 */
function handlePhotoUpload(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Please choose a JPG photo to upload.');
    }
    if ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Photo must be 5 MB or smaller.');
    }
    $info = @getimagesize($file['tmp_name']);
    if ($info === false || ($info['mime'] ?? '') !== 'image/jpeg') {
        throw new RuntimeException('Photo must be a JPG image.');
    }
    $dir = __DIR__ . '/../uploads/photos/';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    if (!is_dir($dir)) {
        throw new RuntimeException('The uploads directory is not writable.');
    }
    $fileName = 'photo_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.jpg';
    if (!move_uploaded_file($file['tmp_name'], $dir . $fileName)) {
        throw new RuntimeException('The photo could not be saved.');
    }
    return 'uploads/photos/' . $fileName;
}