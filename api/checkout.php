<?php


require_once '../includes/config.php';
header('Content-Type: application/json');

$log_id = intval($_POST['log_id'] ?? 0);
if (!$log_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid log ID']);
    exit;
}

$pdo  = getDB();
$pdo = getDB();

try {

    $pdo->beginTransaction();

    // Get the card assigned to this visit
    $stmt = $pdo->prepare("
        SELECT card_number
        FROM visit_logs
        WHERE id = ?
    ");

    $stmt->execute([$log_id]);

    $visit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$visit) {

        throw new Exception(
            'Visit not found'
        );

    }

    // Checkout visitor
    $stmt = $pdo->prepare("
        UPDATE visit_logs
        SET
            check_out = NOW(),
            status = 'checked_out'
        WHERE id = ?
    ");

    $stmt->execute([
        $log_id
    ]);

    // Make card available again
    if (!empty($visit['card_number'])) {

        $stmt = $pdo->prepare("
            UPDATE visitor_cards
            SET status = 'available'
            WHERE card_number = ?
        ");

        $stmt->execute([
            $visit['card_number']
        ]);

    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Checked out successfully',
        'card_number' => $visit['card_number'],
        'time' => date('h:i A')
    ]);

}
catch (Exception $e) {

    $pdo->rollBack();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

}