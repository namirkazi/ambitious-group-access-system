<?php
session_start();

if (
    !isset($_SESSION['admin_logged_in'])
) {
    http_response_code(403);
    exit('Access denied');
}

require_once '../includes/config.php';
header('Content-Type: application/json');

$phone = trim($_GET['phone'] ?? '');
if (!$phone) {
    echo json_encode(['found' => false]);
    exit;
}

$pdo = getDB();

// Look up visitor by phone
$stmt = $pdo->prepare("
    SELECT v.*, 
           COUNT(vl.id) as total_visits,
           MAX(vl.check_in) as last_visit
    FROM visitors v
    LEFT JOIN visit_logs vl ON vl.visitor_id = v.id
    WHERE v.phone = ?
    GROUP BY v.id
");
$stmt->execute([$phone]);
$visitor = $stmt->fetch();

if ($visitor) {
    echo json_encode([
        'found'        => true,
        'id'           => $visitor['id'],
        'full_name'    => $visitor['full_name'],
        'email'        => $visitor['email'],
        'phone'        => $visitor['phone'],
        'photo_path'   => $visitor['photo_path'] ? BASE_URL . $visitor['photo_path'] : null,
        'total_visits' => $visitor['total_visits'],
        'last_visit'   => $visitor['last_visit'] ? date('d M Y, h:i A', strtotime($visitor['last_visit'])) : 'First visit',
    ]);
} else {
    echo json_encode(['found' => false]);
}
