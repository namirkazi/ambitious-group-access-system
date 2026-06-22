<?php

require_once '../includes/config.php';

header('Content-Type: application/json');

$data = json_decode(
    file_get_contents('php://input'),
    true
);

if (
    !$data ||
    !isset($data['descriptor'])
) {
    echo json_encode([
        'success' => false,
        'message' => 'No descriptor received'
    ]);
    exit;
}

$descriptor = $data['descriptor'];

$pdo = getDB();

$stmt = $pdo->query("
    SELECT
        id,
        face_descriptor
    FROM visitors
    WHERE face_descriptor IS NOT NULL
");

$bestVisitor = null;
$bestDistance = 999;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $known = json_decode(
        $row['face_descriptor'],
        true
    );

    if (!$known) {
        continue;
    }

    $distance = 0;

    for ($i = 0; $i < count($known); $i++) {

        $distance += pow(
            $known[$i] - $descriptor[$i],
            2
        );
    }

    $distance = sqrt($distance);

    if ($distance < $bestDistance) {

        $bestDistance = $distance;
        $bestVisitor = $row['id'];
    }
}

if (
    $bestVisitor !== null &&
    $bestDistance < 0.55
) {

    // Check whether visitor is currently inside
$stmt2 = $pdo->prepare("
    SELECT id
    FROM visit_logs
    WHERE visitor_id = ?
      AND status = 'checked_in'
    ORDER BY check_in DESC
    LIMIT 1
");

$stmt2->execute([$bestVisitor]);

$currentVisit = $stmt2->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success'     => true,
    'visitor_id'  => $bestVisitor,
    'distance'    => round($bestDistance,4),
    'checked_in'  => $currentVisit ? true : false,
    'log_id'      => $currentVisit['id'] ?? null
]);

} else {

    echo json_encode([
        'success' => false,
        'message' => 'No matching visitor'
    ]);
}