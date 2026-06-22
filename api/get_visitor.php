<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing visitor ID'
    ]);
    exit;
}

$pdo = getDB();

$stmt = $pdo->prepare("
    SELECT v.*,
           COUNT(vl.id) as total_visits,
           MAX(vl.check_in) as last_visit
    FROM visitors v
    LEFT JOIN visit_logs vl ON vl.visitor_id = v.id
    WHERE v.id = ?
    GROUP BY v.id
");

$stmt->execute([$id]);

$visitor = $stmt->fetch();

if (!$visitor) {

    echo json_encode([
        'success' => false,
        'message' => 'Visitor not found'
    ]);

    exit;
}

echo json_encode([
    'success'      => true,
    'id'           => $visitor['id'],
    'full_name'    => $visitor['full_name'],
    'email'        => $visitor['email'],
    'phone'        => $visitor['phone'],
    'photo_path'   => $visitor['photo_path']
        ? BASE_URL . $visitor['photo_path']
        : null,
    'total_visits' => $visitor['total_visits'],
    'last_visit'   => $visitor['last_visit']
        ? date('d M Y, h:i A', strtotime($visitor['last_visit']))
        : 'First visit'
]);