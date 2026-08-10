<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/database.php';

const ROLE_ADMIN = 1;
const ROLE_DIV_MANAGER = 2;
const ROLE_DIST_MANAGER = 3;
const ROLE_ACCOUNTANT = 4;
const ROLE_TEACHER = 5;
const ROLE_STUDENT = 6;

function appBaseUrl(): string
{
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');

    $path = str_replace('\\', '/', dirname($scriptName));
    while ($path !== '/' && $path !== '.' && $path !== '') {
        $checkPath = rtrim($docRoot, '/') . '/' . ltrim($path, '/') . '/config/database.php';
        if (file_exists($checkPath)) {
            return $path;
        }
        $parent = str_replace('\\', '/', dirname($path));
        if ($parent === $path) {
            break;
        }
        $path = $parent;
    }

    return '';
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

function validateCsrf(?string $token): bool
{
    return !empty($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function requireAuth(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . appBaseUrl() . '/modules/auth/login.php');
        exit;
    }
}

function requireRole(int ...$roles): void
{
    requireAuth();
    if (!in_array((int) ($_SESSION['role_id'] ?? 0), $roles, true)) {
        header('Location: ' . appBaseUrl() . '/modules/dashboard/index.php');
        exit;
    }
}

function isAdmin(): bool
{
    return (int) ($_SESSION['role_id'] ?? 0) === ROLE_ADMIN;
}

function isDivManager(): bool
{
    return (int) ($_SESSION['role_id'] ?? 0) === ROLE_DIV_MANAGER;
}

function isDistManager(): bool
{
    return (int) ($_SESSION['role_id'] ?? 0) === ROLE_DIST_MANAGER;
}

function isAccountant(): bool
{
    return (int) ($_SESSION['role_id'] ?? 0) === ROLE_ACCOUNTANT;
}

function isTeacher(): bool
{
    return (int) ($_SESSION['role_id'] ?? 0) === ROLE_TEACHER;
}

function isStudent(): bool
{
    return (int) ($_SESSION['role_id'] ?? 0) === ROLE_STUDENT;
}

function isManager(): bool
{
    $role = (int) ($_SESSION['role_id'] ?? 0);
    return $role === ROLE_DIV_MANAGER || $role === ROLE_DIST_MANAGER;
}

function isStaff(): bool
{
    $role = (int) ($_SESSION['role_id'] ?? 0);
    return in_array($role, [ROLE_ADMIN, ROLE_DIV_MANAGER, ROLE_DIST_MANAGER, ROLE_ACCOUNTANT, ROLE_TEACHER], true);
}

function roleName(int $roleId): string
{
    $name = match ($roleId) {
        ROLE_ADMIN => 'Admin',
        ROLE_DIV_MANAGER => 'Divisional Manager',
        ROLE_DIST_MANAGER => 'District Manager',
        ROLE_ACCOUNTANT => 'Accountant',
        ROLE_TEACHER => 'Teacher',
        ROLE_STUDENT => 'Student',
        default => '',
    };
    if ($name !== '') {
        return $name;
    }
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('SELECT name FROM roles WHERE id = ?');
        $stmt->execute([$roleId]);
        $row = $stmt->fetch();
        if ($row) {
            return $row['name'];
        }
    } catch (Throwable $e) {
        // roles table unavailable — fall through to Unknown
    }
    return 'Unknown';
}

function currentUser(): array
{
    static $user = null;
    if ($user === null && !empty($_SESSION['user_id'])) {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([(int) $_SESSION['user_id']]);
        $user = $stmt->fetch() ?: [];
    }
    return $user ?: [];
}

function activeYearId(): int
{
    static $yearId = null;
    if ($yearId === null) {
        $pdo = getDbConnection();
        $stmt = $pdo->query('SELECT id FROM academic_years WHERE is_active = 1 ORDER BY id DESC LIMIT 1');
        $yearId = (int) ($stmt->fetchColumn() ?: 0);
    }
    return $yearId;
}

function logoutUser(): void
{
    logActivity('Logged out', 'auth');
    session_destroy();
    header('Location: ' . appBaseUrl() . '/modules/auth/login.php');
    exit;
}

/**
 * Records an entry in the activity_log table. Defaults to the current session
 * user; pass explicit user info to log an action for an account that has no
 * session yet (e.g. a brand-new self-registration).
 */
function logActivity(string $description, ?string $module = null, ?int $userId = null, ?string $userName = null, ?int $roleId = null): void
{
    $userId = $userId ?? (!empty($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null);
    $roleId = $roleId ?? (isset($_SESSION['role_id']) ? (int) $_SESSION['role_id'] : null);

    if ($userName === null) {
        $userName = $_SESSION['name'] ?? null;
        if ($userName === null && $userId !== null) {
            $user = currentUser();
            $userName = $user['name'] ?? null;
        }
    }

    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO activity_log (user_id, user_name, role_id, module, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $userName, $roleId, $module, $description, $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Throwable $e) {
        // Activity logging must never break the request.
    }
}
