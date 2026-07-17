<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

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
$photo_data = $_POST['photo_data'] ?? '';
$document_path = null;   // base64 from webcam
$face_descriptor = $_POST['face_descriptor'] ?? null;
// Validate required fields
// Validate required fields

if (!$phone) {

    echo json_encode([
        'success' => false,
        'message' => 'Phone number is required.'
    ]);

    exit;

}

if (!$host_name) {

    echo json_encode([
        'success' => false,
        'message' => 'Host name is required.'
    ]);

    exit;

}

if (!$host_dept) {

    echo json_encode([
        'success' => false,
        'message' => 'Department is required.'
    ]);

    exit;

}

if (!$purpose) {

    echo json_encode([
        'success' => false,
        'message' => 'Purpose of visit is required.'
    ]);

    exit;

}
if (
    empty($full_name) ||
    strlen($full_name) < 3
) {

    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid visitor name.'
    ]);

    exit;

}
if (
    !preg_match(
        '/^\+971 5[0-6] \d{7}$/',
        $phone
    )
) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid UAE mobile number.'
    ]);

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

    /*
|--------------------------------------------------------------------------
| Find Existing Visitor / Create New Visitor
|--------------------------------------------------------------------------
*/

    $stmt = $pdo->prepare("
    SELECT *
    FROM visitors
    WHERE id = ?
       OR phone = ?
    LIMIT 1
");

    $stmt->execute([
        $visitor_id,
        $phone
    ]);

    $visitor = $stmt->fetch();

    if ($visitor) {

        $visitor_id = $visitor['id'];

    } else {

        $stmt = $pdo->prepare("
        INSERT INTO visitors
        (
            full_name,
            phone,
            email,
            face_descriptor
        )
        VALUES (?, ?, ?, ?)
    ");

        $stmt->execute([
            $full_name,
            $phone,
            $email,
            $face_descriptor
        ]);

        $visitor_id = $pdo->lastInsertId();

        $visitor = [
            'photo_path' => null,
            'document_path' => null,
            'face_descriptor' => null
        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Generate File Names
    |--------------------------------------------------------------------------
    */

    $safeName = preg_replace(
        '/[^A-Za-z0-9]+/',
        '_',
        trim($full_name)
    );

    $safeName = trim($safeName, '_');

    $baseFilename =
        $safeName .
        "_" .
        $visitor_id;

    $photoFilename =
        $baseFilename . ".jpg";

    $photo_path = $visitor['photo_path'];
    $document_path = $visitor['document_path'];

    /*
    |--------------------------------------------------------------------------
    | Save Visitor Photo (Only if one doesn't already exist)
    |--------------------------------------------------------------------------
    */

    if (
        empty($photo_path) &&
        $photo_data &&
        strpos($photo_data, 'data:image') === 0
    ) {

        $parts = explode(',', $photo_data, 2);

        $imageData = base64_decode($parts[1]);

        $image = imagecreatefromstring($imageData);

        if (!$image) {

            throw new Exception("Unable to process visitor photo.");

        }

        $width = imagesx($image);
        $height = imagesy($image);

        $maxWidth = 900;

        if ($width > $maxWidth) {

            $newWidth = $maxWidth;
            $newHeight = intval(($height / $width) * $newWidth);

        } else {

            $newWidth = $width;
            $newHeight = $height;

        }

        $resized = imagecreatetruecolor(
            $newWidth,
            $newHeight
        );

        imagecopyresampled(

            $resized,

            $image,

            0,

            0,

            0,

            0,

            $newWidth,

            $newHeight,

            $width,

            $height

        );

        imagejpeg(

            $resized,

            VISITOR_PHOTO_DIR . $photoFilename,

            75

        );

        imagedestroy($image);
        imagedestroy($resized);

        $photo_path =
            'visitors/photos/' .
            $photoFilename;

    }

    /*
    |--------------------------------------------------------------------------
    | Compress & Save Visitor Document
    |--------------------------------------------------------------------------
    */

    if (
        empty($document_path) &&
        isset($_FILES['document']) &&
        $_FILES['document']['error'] === UPLOAD_ERR_OK
    ) {

        $tmp = $_FILES['document']['tmp_name'];

        $info = getimagesize($tmp);

        if (!$info) {
            throw new Exception("Invalid identity document.");
        }

        switch ($info['mime']) {

            case 'image/jpeg':
                $image = imagecreatefromjpeg($tmp);
                break;

            case 'image/png':
                $image = imagecreatefrompng($tmp);
                break;

            case 'image/webp':
                $image = imagecreatefromwebp($tmp);
                break;

            default:
                throw new Exception("Unsupported image format.");
        }

        // -----------------------------------
        // Fix phone orientation (EXIF)
        // -----------------------------------

        if ($info['mime'] === 'image/jpeg' && function_exists('exif_read_data')) {

            $exif = @exif_read_data($tmp);

            if (!empty($exif['Orientation'])) {

                switch ($exif['Orientation']) {

                    case 3:
                        $image = imagerotate($image, 180, 0);
                        break;

                    case 6:
                        $image = imagerotate($image, -90, 0);
                        break;

                    case 8:
                        $image = imagerotate($image, 90, 0);
                        break;

                }

            }

        }

        $width = imagesx($image);
        $height = imagesy($image);

        $maxWidth = 900;

        if ($width > $maxWidth) {

            $newWidth = $maxWidth;
            $newHeight = intval(($height / $width) * $newWidth);

        } else {

            $newWidth = $width;
            $newHeight = $height;

        }

        $resized = imagecreatetruecolor(
            $newWidth,
            $newHeight
        );

        imagecopyresampled(
            $resized,
            $image,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $width,
            $height
        );

        $documentFilename = $baseFilename . ".jpg";

        imagejpeg(
            $resized,
            VISITOR_DOCUMENT_DIR . $documentFilename,
            65
        );

        imagedestroy($image);
        imagedestroy($resized);

        $document_path =
            'visitors/documents/' .
            $documentFilename;
    }
    /*
    |--------------------------------------------------------------------------
    | Update Visitor Record
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
    UPDATE visitors
    SET
        full_name = ?,
        phone = ?,
        email = ?,
        photo_path = ?,
        document_path = ?,
        face_descriptor = ?,
        updated_at = NOW()
    WHERE id = ?
");

    $stmt->execute([
        $full_name,
        $phone,
        $email,
        $photo_path,
        $document_path,
        $face_descriptor,
        $visitor_id
    ]);
    // Get first available visitor card
    $stmt = $pdo->prepare("
    SELECT card_number
    FROM visitor_cards
    WHERE
        status = 'available'
        AND site_code = ?
    ORDER BY card_number ASC
    LIMIT 1
");

    $stmt->execute([SITE_CODE]);

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
        card_number,
        site_code
    )
    VALUES (?, ?, ?, ?, ?, ?)
");

    $stmt->execute([
        $visitor_id,
        $host_name,
        $host_dept,
        $purpose,
        $card_number,
        SITE_CODE
    ]);

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

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if (!empty($photo_path)) {
        @unlink(VISITOR_PHOTO_DIR . basename($photo_path));
    }

    if (!empty($document_path)) {
        @unlink(VISITOR_DOCUMENT_DIR . basename($document_path));
    }

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}