<?php

require_once '../config/database.php';

header('Content-Type: application/json');

$phone = trim($_GET['phone'] ?? '');

$stmt = $pdo->prepare("
SELECT
    v.id,
    v.first_name,
    v.last_name,
    (
        SELECT status
        FROM visit_logs
        WHERE visitor_id = v.id
        ORDER BY check_in DESC
        LIMIT 1
    ) AS current_status
FROM visitors v
WHERE phone = ?
LIMIT 1
");

$stmt->execute([$phone]);

$visitor = $stmt->fetch();

if (!$visitor) {
    echo json_encode([
        'exists' => false
    ]);
    exit;
}

echo json_encode([
    'exists' => true,
    'visitor' => $visitor
]);