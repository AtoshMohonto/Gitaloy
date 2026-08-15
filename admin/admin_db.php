<?php
require_once __DIR__ . '/../includes/helpers.php';

function addDivision(string $name): int
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('INSERT INTO divisions (name) VALUES (?)');
    $stmt->execute([$name]);
    return (int) $pdo->lastInsertId();
}

function addDistrict(int $divisionId, string $name): int
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('INSERT INTO districts (division_id, name) VALUES (?, ?)');
    $stmt->execute([$divisionId, $name]);
    return (int) $pdo->lastInsertId();
}

function addUpazila(int $districtId, string $name): int
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('INSERT INTO upazilas (district_id, name) VALUES (?, ?)');
    $stmt->execute([$districtId, $name]);
    return (int) $pdo->lastInsertId();
}

function addVillage(int $upazilaId, string $name): int
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('INSERT INTO villages (upazila_id, name) VALUES (?, ?)');
    $stmt->execute([$upazilaId, $name]);
    return (int) $pdo->lastInsertId();
}

function addCenter(string $name, ?int $villageId, ?string $description): int
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('INSERT INTO centers (name, village_id, description) VALUES (?, ?, ?)');
    $stmt->execute([$name, $villageId, $description]);
    return (int) $pdo->lastInsertId();
}

function addClass(string $name, ?int $ageMin = null, ?int $ageMax = null): int
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('INSERT INTO classes (name, age_min, age_max) VALUES (?, ?, ?)');
    $stmt->execute([$name, $ageMin, $ageMax]);
    return (int) $pdo->lastInsertId();
}

function addSubject(string $name): int
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('INSERT INTO subjects (name) VALUES (?)');
    $stmt->execute([$name]);
    return (int) $pdo->lastInsertId();
}

function addAcademicYear(string $name, ?string $startDate, ?string $endDate): int
{
    $pdo = getDbConnection();
    $hasAny = (int) $pdo->query('SELECT COUNT(*) FROM academic_years')->fetchColumn() > 0;
    $stmt = $pdo->prepare('INSERT INTO academic_years (name, start_date, end_date, is_active) VALUES (?, ?, ?, ?)');
    $stmt->execute([$name, $startDate, $endDate, $hasAny ? 0 : 1]);
    return (int) $pdo->lastInsertId();
}

function setActiveYear(int $yearId): bool
{
    $pdo = getDbConnection();
    $pdo->beginTransaction();
    try {
        $pdo->exec('UPDATE academic_years SET is_active = 0');
        $stmt = $pdo->prepare('UPDATE academic_years SET is_active = 1 WHERE id = ?');
        $stmt->execute([$yearId]);
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function addFeeHead(string $name): int
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('INSERT INTO fee_heads (name) VALUES (?)');
    $stmt->execute([$name]);
    return (int) $pdo->lastInsertId();
}

function addDistributionItem(string $name, ?string $unit): int
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('INSERT INTO distribution_items (name, unit) VALUES (?, ?)');
    $stmt->execute([$name, $unit]);
    return (int) $pdo->lastInsertId();
}

function deleteRow(string $table, int $id): void
{
    $pdo = getDbConnection();
    $allowed = [
        'divisions', 'districts', 'upazilas', 'villages', 'centers',
        'classes', 'subjects', 'academic_years', 'fee_heads', 'distribution_items',
    ];
    if (!in_array($table, $allowed, true)) {
        throw new RuntimeException('Invalid table.');
    }
    $pdo->prepare("DELETE FROM `$table` WHERE id = ?")->execute([$id]);
}
