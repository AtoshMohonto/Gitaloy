<?php
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../admin/admin_db.php';

requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request. Please refresh and try again.']);
        exit;
    }
    if (!hasPermission('students.manage')) {
        http_response_code(403);
        echo json_encode(['error' => 'You do not have permission to do this.']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create_village') {
        $name = trim($_POST['name'] ?? '');
        $upazilaId = (int) ($_POST['upazila_id'] ?? 0);
        if ($name === '' || $upazilaId <= 0) {
            http_response_code(422);
            echo json_encode(['error' => 'Village name and upazila are required.']);
            exit;
        }
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('SELECT id FROM upazilas WHERE id = ?');
        $stmt->execute([$upazilaId]);
        if ($stmt->fetch() === false) {
            http_response_code(422);
            echo json_encode(['error' => 'Please choose a valid upazila.']);
            exit;
        }
        try {
            $id = addVillage($upazilaId, $name);
            logActivity('Added village: ' . $name . ' (from student form)', 'students');
            echo json_encode(['id' => $id, 'name' => $name]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'create_class') {
        $name = trim($_POST['name'] ?? '');
        $ageMin = isset($_POST['age_min']) && $_POST['age_min'] !== '' ? (int) $_POST['age_min'] : null;
        $ageMax = isset($_POST['age_max']) && $_POST['age_max'] !== '' ? (int) $_POST['age_max'] : null;
        if ($name === '') {
            http_response_code(422);
            echo json_encode(['error' => 'Class name is required.']);
            exit;
        }
        if ($ageMin !== null && ($ageMin < 1 || $ageMin > 25)) {
            http_response_code(422);
            echo json_encode(['error' => 'Please enter a valid age range (1–25).']);
            exit;
        }
        if ($ageMax !== null && ($ageMax < 1 || $ageMax > 25)) {
            http_response_code(422);
            echo json_encode(['error' => 'Please enter a valid age range (1–25).']);
            exit;
        }
        if ($ageMin !== null && $ageMax !== null && $ageMin > $ageMax) {
            http_response_code(422);
            echo json_encode(['error' => '"Age from" cannot be greater than "Age to".']);
            exit;
        }
        try {
            $id = addClass($name, $ageMin, $ageMax);
            logActivity('Added class: ' . $name . ' (from student form)', 'students');
            echo json_encode(['id' => $id, 'name' => $name]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'create_center') {        $name = trim($_POST['name'] ?? '');
        $villageId = (int) ($_POST['village_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        if ($name === '') {
            http_response_code(422);
            echo json_encode(['error' => 'Center name is required.']);
            exit;
        }
        if ($villageId > 0) {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare('SELECT id FROM villages WHERE id = ?');
            $stmt->execute([$villageId]);
            if ($stmt->fetch() === false) {
                http_response_code(422);
                echo json_encode(['error' => 'Please choose a valid village.']);
                exit;
            }
        }
        try {
            $id = addCenter($name, $villageId > 0 ? $villageId : null, $description !== '' ? $description : null);
            $teacherCenterChanged = false;
            if (isTeacher()) {
                $pdo = getDbConnection();
                $pdo->prepare('UPDATE users SET center_id = ? WHERE id = ?')->execute([$id, (int) ($_SESSION['user_id'] ?? 0)]);
                $teacherCenterChanged = true;
            }
            logActivity('Added center: ' . $name . ' (from student form)', 'students');
            echo json_encode(['id' => $id, 'name' => $name, 'teacher_center_changed' => $teacherCenterChanged]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Unknown action']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'districts':
        $rows = getDistricts(isset($_GET['division_id']) && $_GET['division_id'] !== '' ? (int) $_GET['division_id'] : null);
        echo json_encode($rows);
        break;
    case 'upazilas':
        $rows = getUpazilas(isset($_GET['district_id']) && $_GET['district_id'] !== '' ? (int) $_GET['district_id'] : null);
        echo json_encode($rows);
        break;
    case 'villages':
        $rows = getVillages(isset($_GET['upazila_id']) && $_GET['upazila_id'] !== '' ? (int) $_GET['upazila_id'] : null);
        echo json_encode($rows);
        break;
    case 'centers':
        $rows = getCenters(isset($_GET['village_id']) && $_GET['village_id'] !== '' ? (int) $_GET['village_id'] : null);
        echo json_encode($rows);
        break;
    default:
        echo json_encode(['error' => 'Unknown action']);
}
