<?php

header('Content-Type: application/json');

require_once '../includes/config.php';

$pdo = getDB();

$title = trim($_POST['title'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$department = trim($_POST['department'] ?? '');
$designation = trim($_POST['designation'] ?? '');
$face_descriptor = $_POST['face_descriptor'] ?? '';
$photo_data = $_POST['photo_data'] ?? '';

$allowedDepartments = [
    'HR',
    'IT',
    'Accounts',
    'Sales',
    'Operations',
    'Administration'
];

if (
    !in_array(
        $title,
        ['Mr', 'Mrs', 'Ms']
    )
) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid title'
    ]);

    exit;
}
if (
    !preg_match(
        '/^[A-Za-z ]{3,100}$/',
        $full_name
    )
) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid name'
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
        'message' => 'Invalid phone number'
    ]);

    exit;

}
if (
    !filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )
) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid email'
    ]);

    exit;

}
if (
    !in_array(
        $department,
        $allowedDepartments
    )
) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid department'
    ]);

    exit;

}
if (
    !preg_match(
        '/^[A-Za-z ]{2,50}$/',
        $designation
    )
) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid designation'
    ]);

    exit;

}
if (
    empty($photo_data)
) {

    echo json_encode([
        'success' => false,
        'message' => 'Photo required'
    ]);

    exit;

}
$uploadDir = '../uploads/employees/';

if (!is_dir($uploadDir)) {

    if (!mkdir($uploadDir, 0755, true)) {

        echo json_encode([
            'success' => false,
            'message' => 'Failed to create upload directory.'
        ]);

        exit;
    }
}

if (!is_writable($uploadDir)) {

    echo json_encode([
        'success' => false,
        'message' => 'Upload directory is not writable.'
    ]);

    exit;
}
$image = str_replace(
    'data:image/jpeg;base64,',
    '',
    $photo_data
);

$image = str_replace(
    ' ',
    '+',
    $image
);

$fileName =
    uniqid() .
    '.jpg';

$filePath =
    $uploadDir .
    $fileName;

$imageData = base64_decode($image);

if ($imageData === false) {

    echo json_encode([
        'success' => false,
        'message' => 'Failed to decode image.'
    ]);

    exit;
}

$result = file_put_contents($filePath, $imageData);
if (!file_exists($filePath)) {

    echo json_encode([
        'success' => false,
        'message' => 'Image file was not created.'
    ]);

    exit;
}

if ($result === false) {

    echo json_encode([
        'success' => false,
        'message' => 'Unable to save image to: ' . $filePath
    ]);

    exit;
}
$stmt =
    $pdo->prepare(

        "SELECT id
FROM employees
WHERE email=?"

    );

$stmt->execute([
    $email
]);

if (
    $stmt->fetch()
) {

    echo json_encode([

        'success' => false,

        'message' => 'Employee already exists'

    ]);

    exit;

}
$stmt = $pdo->prepare(
    "
INSERT INTO employees(

title,
full_name,
department,
designation,
phone,
email,
photo_path,
face_descriptor

)

VALUES(

?,?,?,?,?,?,?,?

)
"
);

try{

    $stmt->execute([

        $title,
        $full_name,
        $department,
        $designation,
        $phone,
        $email,
        'uploads/employees/'.$fileName,
        $face_descriptor

    ]);

    echo json_encode([

        'success'=>true

    ]);

}
catch(PDOException $e){

    echo json_encode([

        'success'=>false,

        'message'=>$e->getMessage()

    ]);

}