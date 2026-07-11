<?php
require_once '../includes/config.php';
header('Content-Type: application/json');
if (!empty($photoPath)) {

    // TODO:
    // Send image to Flask
    // register-face
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$pdo = getDB();

$visitor_id = intval($_POST['visitor_id'] ?? 0);
$full_name = trim($_POST['full_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$host_name = trim($_POST['host_name'] ?? '');
$host_dept = trim($_POST['host_department'] ?? '');
$purpose = trim($_POST['purpose'] ?? '');
$photo_data = $_POST['photo_data'] ?? '';   // base64 from webcam
$face_descriptor = $_POST['face_descriptor'] ?? null;
// Validate required fields
if (!$phone || !$host_name || !$purpose) {
    echo json_encode(['success' => false, 'message' => 'Phone, host name and purpose are required']);
    exit;
}
// Validate face descriptor
if (empty($face_descriptor)) {

    echo json_encode([
        'success' => false,
        'message' => 'Face scan is required.'
    ]);

    exit;
}

$descriptor = json_decode($face_descriptor, true);

if (
    !is_array($descriptor) ||
    count($descriptor) !== 128
) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid face descriptor.'
    ]);

    exit;
}
if (empty($photo_data)) {

    echo json_encode([
        'success' => false,
        'message' => 'Visitor photo is required.'
    ]);

    exit;
}

try {
    $pdo->beginTransaction();

    $photo_path = null;

    // Save webcam photo if provided
    if ($photo_data && strpos($photo_data, 'data:image') === 0) {
        $parts = explode(',', $photo_data, 2);
        $imgData = base64_decode($parts[1]);
        $filename = 'visitor_' . time() . '_' . rand(1000, 9999) . '.jpg';
        $filepath = UPLOAD_DIR . $filename;

        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        file_put_contents($filepath, $imgData);
        $photo_path = UPLOAD_URL . $filename;
    }

    if ($visitor_id > 0) {
        // Returning visitor — update photo if a new one was taken
        if ($photo_path) {
            $stmt = $pdo->prepare("
    UPDATE visitors
    SET photo_path = ?,
        face_descriptor = ?,
        updated_at = NOW()
    WHERE id = ?
");

            $stmt->execute([
                $photo_path,
                $face_descriptor,
                $visitor_id
            ]);
        }
    } else {
        // New visitor — check if phone already exists (race condition guard)
        $stmt = $pdo->prepare("SELECT id FROM visitors WHERE phone = ?");
        $stmt->execute([$phone]);
        $existing = $stmt->fetch();

        if ($existing) {
            $visitor_id = $existing['id'];
            if ($photo_path) {
                $stmt = $pdo->prepare("
    UPDATE visitors
    SET photo_path = ?,
        email = ?,
        full_name = ?,
        face_descriptor = ?,
        updated_at = NOW()
    WHERE id = ?
");

                $stmt->execute([
                    $photo_path,
                    $email,
                    $full_name,
                    $face_descriptor,
                    $visitor_id
                ]);
            }
        } else {
            $stmt = $pdo->prepare("
    INSERT INTO visitors
    (
        full_name,
        phone,
        email,
        photo_path,
        face_descriptor
    )
    VALUES (?, ?, ?, ?, ?)
");
            $stmt->execute([
                $full_name,
                $phone,
                $email,
                $photo_path,
                $face_descriptor
            ]);
            $visitor_id = $pdo->lastInsertId();
        }
    }
    // Get first available visitor card
    $stmt = $pdo->query("
    SELECT card_number
    FROM visitor_cards
    WHERE status='available'
    LIMIT 1
");

    $card = $stmt->fetch();

    if (!$card) {

        throw new Exception(
            'No visitor cards available.'
        );

    }

    $card_number = $card['card_number'];

    // Reserve the card
    $stmt = $pdo->prepare("
    UPDATE visitor_cards
    SET status='in_use'
    WHERE card_number=?
");

    $stmt->execute([
        $card_number
    ]);

    // Log visit
    $stmt = $pdo->prepare("
    INSERT INTO visit_logs
    (
        visitor_id,
        host_name,
        host_department,
        purpose,
        card_number
    )
    VALUES (?, ?, ?, ?, ?)
");

    $stmt->execute([
        $visitor_id,
        $host_name,
        $host_dept,
        $purpose,
        $card_number
    ]);

    $log_id = $pdo->lastInsertId();
    $log_id = $pdo->lastInsertId();

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'visitor_id' => $visitor_id,
        'log_id' => $log_id,
        'card_number' => $card_number,
        'message' => 'Visit registered successfully',
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
