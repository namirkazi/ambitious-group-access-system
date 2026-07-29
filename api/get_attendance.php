<?php

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {

    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();

}

if ($_SESSION['role'] != 'hr') {

    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();

}

require_once '../includes/config.php';

$pdo = getDB();

$employeeId = isset($_GET['employee_id']) ? (int) $_GET['employee_id'] : 0;
$month = isset($_GET['month']) ? (int) $_GET['month'] : 0;
$year = isset($_GET['year']) ? (int) $_GET['year'] : 0;

if (!$employeeId || $month < 1 || $month > 12 || $year < 2000) {

    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit();

}

$stmt = $pdo->prepare("
SELECT attendance_date, check_in, check_out, total_hours, status
FROM employee_attendance
WHERE employee_id = ?
AND YEAR(attendance_date) = ?
AND MONTH(attendance_date) = ?
");

$stmt->execute([$employeeId, $year, $month]);

$rows = $stmt->fetchAll();

$map = [];

foreach ($rows as $row) {

    $map[$row['attendance_date']] = [
        'status' => $row['status'],
        'check_in' => $row['check_in'],
        'check_out' => $row['check_out'],
        'total_hours' => $row['total_hours'],
    ];

}

echo json_encode($map);