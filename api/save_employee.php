<?php

header('Content-Type: application/json');

require_once '../includes/config.php';

$pdo = getDB();

$title = trim($_POST['title'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');
$legal_name = trim($_POST['legal_name'] ?? '');
$gender = trim($_POST['gender'] ?? '');
$dob = trim($_POST['dob'] ?? '');
$nationality = trim($_POST['nationality'] ?? '');
$joining_date = trim($_POST['joining_date'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$department = trim($_POST['department'] ?? '');
$designation = trim($_POST['designation'] ?? '');
$document_type = trim($_POST['document_type'] ?? '');
$document_number = trim($_POST['document_number'] ?? '');
$document_issue_date = trim($_POST['document_issue_date'] ?? '');
$document_expiry_date = trim($_POST['document_expiry_date'] ?? '');
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
    !in_array(
        $gender,
        ['Male', 'Female', 'Other']
    )
) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid gender'
    ]);

    exit;

}
if (
    !preg_match(
        '/^[A-Za-z ]{2,100}$/',
        $nationality
    )
) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid nationality'
    ]);

    exit;

}
if (
    strlen($document_number) < 5
) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid document number'
    ]);

    exit;

}
if (
    empty($dob)
    ||
    strtotime($dob) >= time()
) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid date of birth'
    ]);

    exit;

}
if (
    empty($joining_date)
) {

    echo json_encode([
        'success' => false,
        'message' => 'Joining date required'
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
if (
        !in_array(
            $document_type,
            ['Emirates ID', 'Passport']
        )
    ) {

        echo json_encode([
            'success' => false,
            'message' => 'Invalid document type'
        ]);

        exit;

    }
$image = str_replace(
    'data:image/jpeg;base64,',
    '',
    $photo_data
);

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
$image = str_replace(
    ' ',
    '+',
    $image
);

$fileName = uniqid() . '.jpg';

$filePath = EMPLOYEE_PHOTO_DIR . $fileName;

$imageData = base64_decode($image);

if ($imageData === false) {

    echo json_encode([
        'success' => false,
        'message' => 'Failed to decode image.'
    ]);

    exit;
}
$document_path = null;
$result = file_put_contents($filePath, $imageData);

if ($result === false) {

    echo json_encode([
        'success' => false,
        'message' => 'Unable to save image.'
    ]);

    exit;
}

if (!file_exists($filePath)) {

    echo json_encode([
        'success' => false,
        'message' => 'Image file was not created.'
    ]);

    exit;
}

if (
    isset($_FILES['document']) &&
    $_FILES['document']['error'] === UPLOAD_ERR_OK
) {

    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    $mime = finfo_file(
        $finfo,
        $_FILES['document']['tmp_name']
    );

    finfo_close($finfo);

    $allowedMime = [

        'application/pdf',

        'image/jpeg',

        'image/png'

    ];

    if (
        !in_array(
            $mime,
            $allowedMime
        )
    ) {

        unlink($filePath);

        echo json_encode([

            'success' => false,

            'message' => 'Invalid document.'

        ]);

        exit;

    }

    $allowedExtensions = [
    'pdf',
    'jpg',
    'jpeg',
    'png'
];
    $extension = strtolower(
        pathinfo(
            $_FILES['document']['name'],
            PATHINFO_EXTENSION
        )
    );

if (!in_array($extension, $allowedExtensions)) {

    if (file_exists($filePath)) {
        unlink($filePath);
    }

    echo json_encode([
        'success' => false,
        'message' => 'Only PDF, JPG, JPEG and PNG files are allowed.'
    ]);

    exit;
}

if ($_FILES['document']['size'] > 500 * 1024) {

    if (file_exists($filePath)) {
        unlink($filePath);
    }

    echo json_encode([
        'success' => false,
        'message' => 'Document must be less than 500KB.'
    ]);

    exit;
}

    $documentFilename =
        uniqid('doc_') .
        '.' .
        $extension;

    $documentFullPath =
        EMPLOYEE_DOCUMENT_DIR .
        $documentFilename;

    if (
        !move_uploaded_file(
            $_FILES['document']['tmp_name'],
            $documentFullPath
        )
    ) {
        if (file_exists($filePath)) {

            unlink($filePath);

        }

        echo json_encode([
            'success' => false,
            'message' => 'Unable to save employee document.'
        ]);

        exit;
    }

    $document_path =
        'employees/documents/' .
        $documentFilename;

}

$stmt = $pdo->prepare(
    "
INSERT INTO employees(

title,

full_name,

legal_name,

gender,

dob,

nationality,

joining_date,

department,

designation,

phone,

email,

document_type,

document_number,

document_issue_date,

document_expiry_date,

document_path,

photo_path,

face_descriptor

)

VALUES(

?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?

)
"
);

try {

    $stmt->execute([

        $title,

        $full_name,

        $legal_name,

        $gender,

        $dob,

        $nationality,

        $joining_date,

        $department,

        $designation,

        $phone,

        $email,

        $document_type,

        $document_number,

        $document_issue_date,

        $document_expiry_date,

        $document_path,

        'employees/photos/' . $fileName,

        $face_descriptor

    ]);

    echo json_encode([

        'success' => true

    ]);

} catch (PDOException $e) {
    if (file_exists($filePath)) {

        unlink($filePath);

    }

    if (
        !empty($document_path)
    ) {

        $fullDocumentPath =
            EMPLOYEE_DOCUMENT_DIR .
            basename($document_path);

        if (file_exists($fullDocumentPath)) {

            unlink($fullDocumentPath);

        }

    }

    echo json_encode([

        'success' => false,

        'message' => $e->getMessage()

    ]);

}