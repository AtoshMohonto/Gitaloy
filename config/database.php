<?php
$databaseHost = getenv('DB_HOST') ?: '127.0.0.1';
$databasePort = getenv('DB_PORT') ?: '3306';
$databaseName = getenv('DB_NAME') ?: 'gitaloy';
$databaseUser = getenv('DB_USER') ?: 'root';
$databasePass = getenv('DB_PASS') ?: '';

function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    global $databaseHost, $databasePort, $databaseName, $databaseUser, $databasePass;

    $dsnCandidates = [
        "mysql:host={$databaseHost};port={$databasePort};charset=utf8mb4",
    ];

    if ($databaseHost === '127.0.0.1' || $databaseHost === 'localhost') {
        $dsnCandidates[] = "mysql:host=localhost;port={$databasePort};charset=utf8mb4";
        $dsnCandidates[] = "mysql:host=127.0.0.1;port={$databasePort};charset=utf8mb4";
    }

    $lastException = null;

    foreach ($dsnCandidates as $dsn) {
        try {
            $pdo = new PDO(
                $dsn,
                $databaseUser,
                $databasePass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
            break;
        } catch (PDOException $exception) {
            $lastException = $exception;
        }
    }

    if (!($pdo instanceof PDO)) {
        throw new RuntimeException('Database connection failed: ' . $lastException->getMessage());
    }

    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$databaseName}`");

        $schemaSql = file_get_contents(__DIR__ . '/../database_schema.sql');
        if ($schemaSql !== false) {
            $statements = array_filter(array_map('trim', explode(';', $schemaSql)));
            foreach ($statements as $statement) {
                if ($statement === '') {
                    continue;
                }
                try {
                    $pdo->exec($statement);
                } catch (Throwable $e) {
                    // Already exists, continue
                }
            }
        }
    } catch (PDOException $exception) {
        throw new RuntimeException('Database connection failed: ' . $exception->getMessage());
    }

    return $pdo;
}

function ensureDefaultAdmin(): void
{
    $pdo = getDbConnection();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role_id = 1');
    $stmt->execute();

    if ((int) $stmt->fetchColumn() === 0) {
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $insert = $pdo->prepare('INSERT INTO users (name, username, email, password, role_id) VALUES (?, ?, ?, ?, 1)');
        $insert->execute(['System Admin', 'admin', 'admin@gitaloy.com', $passwordHash]);
    }
}

function ensureSeedData(): void
{
    $pdo = getDbConnection();

    $stmt = $pdo->query('SELECT COUNT(*) FROM academic_years');
    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec("INSERT INTO academic_years (name, start_date, end_date, is_active) VALUES ('2025-2026', '2025-07-01', '2026-06-30', 1)");
    }

    $stmt = $pdo->query('SELECT COUNT(*) FROM fee_heads');
    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec("INSERT INTO fee_heads (name) VALUES ('Monthly Fee'), ('Friday Contribution'), ('Study Materials')");
    }

    $stmt = $pdo->query('SELECT COUNT(*) FROM distribution_items');
    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec("INSERT INTO distribution_items (name, unit) VALUES ('Notebook', 'piece'), ('Pen', 'piece'), ('Pencil', 'piece'), ('Textbook', 'piece'), ('Bag', 'piece'), ('Uniform', 'set')");
    }

    $stmt = $pdo->query('SELECT COUNT(*) FROM classes');
    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec("INSERT INTO classes (name) VALUES ('Class 1'), ('Class 2'), ('Class 3'), ('Class 4'), ('Class 5')");
    }
}

try {
    ensureDefaultAdmin();
    ensureSeedData();
} catch (Throwable $exception) {
    // The app will display a friendly message on the login or dashboard pages if the schema is not ready.
}
