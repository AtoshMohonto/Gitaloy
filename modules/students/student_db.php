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
        'INSERT INTO students (student_id, name, guardian_name, guardian_phone, dob, gender, village_id, center_id, class_id, admission_date, status, photo)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
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
        $data['photo'] ?? null,
    ]);
    return (int) $pdo->lastInsertId();
}

function updateStudent(int $id, array $data): bool
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'UPDATE students SET name = ?, guardian_name = ?, guardian_phone = ?, dob = ?, gender = ?,
                village_id = ?, center_id = ?, class_id = ?, admission_date = ?, status = ?, photo = ?
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
        $data['photo'] ?? null,
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

/**
 * Returns true when the given center is inside the current user's zone scope.
 */
function centerInScope(int $centerId): bool
{
    $pdo = getDbConnection();
    $filter = getCenterScopeFilter('c');
    $joins = getCenterScopeJoins('c');
    $stmt = $pdo->prepare("SELECT c.id FROM centers c $joins WHERE c.id = ? AND $filter");
    $stmt->execute([$centerId]);
    return $stmt->fetch() !== false;
}

/**
 * Reads an uploaded CSV file into a 2D array of rows.
 */
function readCsvRows(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Please choose a CSV file to upload.');
    }
    if ((int) ($file['size'] ?? 0) > 2097152) {
        throw new RuntimeException('CSV file is too large. Maximum size is 2 MB.');
    }
    $handle = fopen($file['tmp_name'], 'r');
    if ($handle === false) {
        throw new RuntimeException('Could not read the uploaded CSV file.');
    }
    $rows = [];
    while (($row = fgetcsv($handle)) !== false) {
        $rows[] = $row;
    }
    fclose($handle);
    return $rows;
}

/**
 * Normalizes a CSV header cell for flexible column matching.
 * Example: "Date of Birth (YYYY-MM-DD)" -> "date_of_birth"
 */
function normalizeCsvHeader(string $header): string
{
    $header = str_replace("\xEF\xBB\xBF", '', $header);
    $header = strtolower(trim($header));
    $header = preg_replace('/\([^)]*\)/', '', $header);
    $header = preg_replace('/[^a-z0-9]+/', '_', $header);
    return trim($header, '_');
}

/**
 * Finds the index of a column given acceptable alias names, or null.
 */
function csvColumnIndex(array $headers, array $aliases): ?int
{
    foreach ($aliases as $alias) {
        $index = array_search($alias, $headers, true);
        if ($index !== false) {
            return $index;
        }
    }
    return null;
}

/**
 * Imports students from parsed CSV rows.
 *
 * $context keys:
 *   - is_teacher: bool
 *   - teacher_center_id: int (teacher's current center id)
 *
 * Returns ['added' => int, 'added_names' => array, 'errors' => array].
 */
function importStudentsFromCsv(array $rows, array $context = []): array
{
    $pdo = getDbConnection();
    $result = ['added' => 0, 'added_names' => [], 'errors' => []];

    if (count($rows) < 2) {
        throw new RuntimeException('The CSV must have a header row followed by at least one student row.');
    }
    if (count($rows) - 1 > 500) {
        throw new RuntimeException('Too many rows. A single import is limited to 500 students.');
    }

    $headers = array_map('normalizeCsvHeader', array_map('strval', $rows[0]));
    $col = [
        'name' => csvColumnIndex($headers, ['name', 'student_name', 'studentname']),
        'guardian_name' => csvColumnIndex($headers, ['guardian_name', 'guardian', 'parent_name', 'parentname', 'father_name']),
        'guardian_phone' => csvColumnIndex($headers, ['guardian_phone', 'guardianphone', 'phone', 'guardian_mobile', 'mobile']),
        'dob' => csvColumnIndex($headers, ['dob', 'date_of_birth', 'birth_date', 'birthdate']),
        'gender' => csvColumnIndex($headers, ['gender', 'sex']),
        'village' => csvColumnIndex($headers, ['village', 'village_name', 'villagename']),
        'center' => csvColumnIndex($headers, ['center', 'centre', 'center_name', 'centrename', 'study_center']),
        'class' => csvColumnIndex($headers, ['class', 'class_name', 'classname', 'grade']),
        'admission_date' => csvColumnIndex($headers, ['admission_date', 'admissiondate', 'admission']),
        'status' => csvColumnIndex($headers, ['status']),
    ];

    if ($col['name'] === null) {
        throw new RuntimeException('The CSV must contain a "Name" column. Use the demo template to see the expected format.');
    }

    $isTeacher = (bool) ($context['is_teacher'] ?? false);
    $teacherCenterId = (int) ($context['teacher_center_id'] ?? 0);

    $villageByName = [];
    foreach (getVillages() as $village) {
        $villageByName[strtolower($village['name'])] = (int) $village['id'];
    }
    $classByName = [];
    foreach (getClasses() as $class) {
        $classByName[strtolower($class['name'])] = (int) $class['id'];
    }
    $centerByName = [];
    foreach (getCenters() as $center) {
        $centerByName[strtolower($center['name'])] = (int) $center['id'];
    }

    $teacherCenterName = '';
    if ($isTeacher && $teacherCenterId > 0) {
        $stmt = $pdo->prepare('SELECT name FROM centers WHERE id = ?');
        $stmt->execute([$teacherCenterId]);
        $teacherCenterName = (string) ($stmt->fetchColumn() ?: '');
    }

    for ($i = 1, $n = count($rows); $i < $n; $i++) {
        $row = $rows[$i];
        $rowNo = $i + 1;
        $cell = function (string $key) use ($row, $col) {
            $index = $col[$key];
            return $index !== null && isset($row[$index]) ? trim((string) $row[$index]) : '';
        };

        $name = $cell('name');
        if ($name === '') {
            $result['errors'][] = "Row $rowNo: Name is required.";
            continue;
        }

        $centerId = 0;
        $centerName = $cell('center');
        if ($isTeacher) {
            if ($centerName !== '' && $teacherCenterName !== '' && strtolower($centerName) !== strtolower($teacherCenterName)) {
                $result['errors'][] = "Row $rowNo: You can only add students to your own center (" . $teacherCenterName . ').';
                continue;
            }
            $centerId = $teacherCenterId;
        } else {
            if ($centerName === '') {
                $result['errors'][] = "Row $rowNo: Center is required.";
                continue;
            }
            $centerKey = strtolower($centerName);
            if (!isset($centerByName[$centerKey])) {
                $result['errors'][] = "Row $rowNo: Center '" . $centerName . "' not found.";
                continue;
            }
            $centerId = $centerByName[$centerKey];
            if (!centerInScope($centerId)) {
                $result['errors'][] = "Row $rowNo: Center '" . $centerName . "' is outside your area.";
                continue;
            }
        }

        $villageId = null;
        $villageName = $cell('village');
        if ($villageName !== '') {
            $villageKey = strtolower($villageName);
            if (!isset($villageByName[$villageKey])) {
                $result['errors'][] = "Row $rowNo: Village '" . $villageName . "' not found.";
                continue;
            }
            $villageId = $villageByName[$villageKey];
        }

        $classId = null;
        $className = $cell('class');
        if ($className !== '') {
            $classKey = strtolower($className);
            if (!isset($classByName[$classKey])) {
                $result['errors'][] = "Row $rowNo: Class '" . $className . "' not found.";
                continue;
            }
            $classId = $classByName[$classKey];
        }

        $gender = null;
        $genderRaw = $cell('gender');
        if ($genderRaw !== '') {
            $g = strtolower($genderRaw);
            if ($g === 'male' || $g === 'm') {
                $gender = 'Male';
            } elseif ($g === 'female' || $g === 'f') {
                $gender = 'Female';
            } else {
                $result['errors'][] = "Row $rowNo: Gender must be Male or Female.";
                continue;
            }
        }

        $dob = null;
        $dobRaw = $cell('dob');
        if ($dobRaw !== '') {
            if (!isValidDateString($dobRaw)) {
                $result['errors'][] = "Row $rowNo: Date of birth must be in YYYY-MM-DD format.";
                continue;
            }
            $dob = $dobRaw;
        }

        $admissionDate = $cell('admission_date') !== '' ? $cell('admission_date') : date('Y-m-d');
        if (!isValidDateString($admissionDate)) {
            $result['errors'][] = "Row $rowNo: Admission date must be in YYYY-MM-DD format.";
            continue;
        }

        $status = 'Active';
        $statusRaw = $cell('status');
        if ($statusRaw !== '') {
            $s = strtolower($statusRaw);
            if (in_array($s, ['active', 'a', '1'], true)) {
                $status = 'Active';
            } elseif (in_array($s, ['inactive', 'i', '0'], true)) {
                $status = 'Inactive';
            } else {
                $result['errors'][] = "Row $rowNo: Status must be Active or Inactive.";
                continue;
            }
        }

        try {
            createStudent([
                'student_id' => nextStudentId(),
                'name' => $name,
                'guardian_name' => $cell('guardian_name') !== '' ? $cell('guardian_name') : null,
                'guardian_phone' => $cell('guardian_phone') !== '' ? $cell('guardian_phone') : null,
                'dob' => $dob,
                'gender' => $gender,
                'village_id' => $villageId,
                'center_id' => $centerId,
                'class_id' => $classId,
                'admission_date' => $admissionDate,
                'status' => $status,
            ]);
            $result['added']++;
            $result['added_names'][] = $name;
        } catch (Throwable $e) {
            $result['errors'][] = "Row $rowNo: Could not save ($name): " . $e->getMessage();
        }
    }

    return $result;
}

function isValidDateString(string $value): bool
{
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}
