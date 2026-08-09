<?php
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

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
