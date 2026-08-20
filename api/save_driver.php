<?php

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'hr') {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);

    exit();
}

require_once '../includes/config.php';
$pdo = getDB();

function respond($success, $message = '', $extra = [])
{
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit();
}

// ── Collect + validate input ─────────────────────────────
$fullName       = trim($_POST['full_name'] ?? '');
$phone          = trim($_POST['phone'] ?? '');
$email          = trim($_POST['email'] ?? '');
$emiratesId     = trim($_POST['emirates_id'] ?? '');
$licenseNumber  = trim($_POST['license_number'] ?? '');
$licenseExpiry  = trim($_POST['license_expiry'] ?? '');
$photoData      = $_POST['photo_data'] ?? '';
$faceDescriptor = $_POST['face_descriptor'] ?? '';


if (!preg_match('/^\+971 \d{2} \d{7}$/', $phone)) {
    respond(false, 'Invalid phone number');
}
if (!preg_match('/^\d{3}-\d{4}-\d{7}-\d{1}$/', $emiratesId)) {
    respond(false, 'Invalid Emirates ID');
}

if (strlen($licenseNumber) < 3) {
    respond(false, 'Invalid driving license number');
}

$expiryDate = DateTime::createFromFormat('Y-m-d', $licenseExpiry);
if (!$expiryDate) {
    respond(false, 'Invalid license expiry date');
}

if (empty($photoData)) {
    respond(false, 'Driver photo is required');
}

if (empty($faceDescriptor)) {
    respond(false, 'Face descriptor is required');
}

$decodedDescriptor = json_decode($faceDescriptor, true);
if (!is_array($decodedDescriptor)) {
    respond(false, 'Invalid face descriptor');
}

// ── Duplicate checks ──────────────────────────────────────
try {
    $dupStmt = $pdo->prepare(
        "SELECT id FROM drivers WHERE emirates_id = :eid OR license_number = :lic LIMIT 1"
    );
    $dupStmt->execute([
        ':eid'   => $emiratesId,
        ':lic'   => $licenseNumber,
    ]);
    if ($dupStmt->fetch()) {
        respond(false, 'A driver with this Emirates ID or license number already exists');
    }
} catch (Exception $e) {
    respond(false, 'Database error while checking duplicates: ' . $e->getMessage());
}

// ── Save photo to disk ────────────────────────────────────
$uploadDir = '../assets/uploads/drivers/photos/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$photoPath = null;
if (preg_match('/^data:image\/(\w+);base64,/', $photoData, $matches)) {
    $imageType = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
    $rawData = base64_decode(substr($photoData, strpos($photoData, ',') + 1));

    if ($rawData === false) {
        respond(false, 'Failed to decode photo');
    }

    $safeName = preg_replace(
        '/[^A-Za-z0-9]+/',
        '_',
        trim($fullName)
    );

    $fileName =
        $safeName .
        '_' .
        time() .
        '.' .
        $imageType;
    $fullPath = $uploadDir . $fileName;

    if (!file_put_contents($fullPath, $rawData)) {
        respond(false, 'Failed to save photo');
    }

    // Stored relative to the web root that serves /uploads/drivers/*
    $photoPath =
        'assets/uploads/drivers/photos/' .
        $fileName;
} else {
    respond(false, 'Invalid photo data');
}

// ── Insert into database ──────────────────────────────────
// The `drivers` table (as currently structured) does not have a
// vehicle_id column. This attempts the insert with vehicle_id first;
// if that column doesn't exist yet, it falls back to inserting
// without it so the rest of the form still works.
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "INSERT INTO drivers
        (full_name, phone, email, emirates_id,
         license_number, license_expiry,
         face_descriptor, photo_path,
         created_at, updated_at)
     VALUES
        (:full_name, :phone, :email, :emirates_id,
         :license_number, :license_expiry,
         :face_descriptor, :photo_path,
         NOW(), NOW())"
    );

    $stmt->execute([
        ':full_name'       => $fullName,
        ':phone'           => $phone,
        ':email'           => $email,
        ':emirates_id'     => $emiratesId,
        ':license_number'  => $licenseNumber,
        ':license_expiry'  => $licenseExpiry,
        ':face_descriptor' => $faceDescriptor,
        ':photo_path'      => $photoPath,
    ]);
    $driverId = $pdo->lastInsertId();
    $pdo->commit();

    respond(true, 'Driver registered successfully', ['driver_id' => $driverId]);
} catch (Exception $e) {
    $pdo->rollBack();
    // Clean up the saved photo if the DB insert failed
    if ($photoPath && file_exists('../' . $photoPath)) {
        unlink('../' . $photoPath);
    }
    respond(false, 'Database error: ' . $e->getMessage());
}
