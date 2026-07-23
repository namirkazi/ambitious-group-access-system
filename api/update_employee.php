<?php

require_once '../includes/config.php';

$pdo = getDB();

$pdo->beginTransaction();

try {

    if (empty($_POST['id'])) {
        throw new Exception("Employee ID missing.");
    }

    $stmt = $pdo->prepare("
        SELECT *
        FROM employees
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$_POST['id']]);

    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        throw new Exception("Employee not found.");
    }

    $oldPhoto = $employee['photo_path'];
    $oldDocument = $employee['document_path'];

    

    /* -----------------------------
   SERVER SIDE VALIDATION
------------------------------ */

    $title = trim($_POST['title'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $legalName = trim($_POST['legal_name'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $dob = $_POST['dob'] ?? null;
    $nationality = trim($_POST['nationality'] ?? '');

    $department = trim($_POST['department'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $joiningDate = $_POST['joining_date'] ?? null;

    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $documentType = trim($_POST['document_type'] ?? '');
    $documentNumber = trim($_POST['document_number'] ?? '');
    $documentIssueDate = $_POST['document_issue_date'] ?? null;
    $documentExpiryDate = $_POST['document_expiry_date'] ?? null;

    if ($title == '')
        throw new Exception("Title is required.");

    if ($fullName == '')
        throw new Exception("Full name is required.");

    if (!preg_match('/^\+971 \d{2} \d{7}$/', $phone)) {

        throw new Exception("Invalid UAE mobile number.");

    }

    if ($email != '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        throw new Exception("Invalid email address.");

    }
    /* -----------------------------
       BUILD UPDATE QUERY
    ------------------------------ */

    $update = [];

    $params = [];

    /* Personal */

    $update[] = "title=?";
    $params[] = $title;

    $update[] = "full_name=?";
    $params[] = $fullName;

    $update[] = "legal_name=?";
    $params[] = $legalName;

    $update[] = "gender=?";
    $params[] = $gender;

    $update[] = "dob=?";
    $params[] = ($dob ?: null);

    $update[] = "nationality=?";
    $params[] = $nationality;

    /* Employment */

    $update[] = "department=?";
    $params[] = $department;

    $update[] = "designation=?";
    $params[] = $designation;

    $update[] = "joining_date=?";
    $params[] = ($joiningDate ?: null);

    /* Contact */

    $update[] = "phone=?";
    $params[] = $phone;

    $update[] = "email=?";
    $params[] = $email;

    /* Documents */

    $update[] = "document_type=?";
    $params[] = $documentType;

    $update[] = "document_number=?";
    $params[] = $documentNumber;

    $update[] = "document_issue_date=?";
    $params[] = ($documentIssueDate ?: null);

    $update[] = "document_expiry_date=?";
    $params[] = ($documentExpiryDate ?: null);


    /* -----------------------------
       UPDATE PHOTO (OPTIONAL)
    ------------------------------ */

    $newPhoto = false;

    if (!empty($_POST['photo_data'])) {

        $photoData = $_POST['photo_data'];

        if (
            !preg_match(
                '/^data:image\/(jpeg|jpg|png);base64,/',
                $photoData
            )
        ) {

            throw new Exception("Invalid employee photo.");

        }

        $image = preg_replace(
            '/^data:image\/(jpeg|jpg|png);base64,/',
            '',
            $photoData
        );

        $image = str_replace(' ', '+', $image);

        $imageBinary = base64_decode($image);

        if ($imageBinary === false) {

            throw new Exception("Invalid photo encoding.");

        }

        $photoFileName =
            uniqid('EMP_') .
            '.jpg';

        $photoFullPath =
            EMPLOYEE_PHOTO_DIR .
            $photoFileName;

        /* Path stored in database */

        $photoPath =
            'employees/photos/' .
            $photoFileName;

        if (
            file_put_contents(
                $photoFullPath,
                $imageBinary
            ) === false
        ) {

            throw new Exception("Unable to save employee photo.");

        }

        $update[] = "photo_path=?";
        $params[] = $photoPath;

        if (!empty($_POST['face_descriptor'])) {

            json_decode($_POST['face_descriptor']);

            if (
                json_last_error() !== JSON_ERROR_NONE
            ) {

                throw new Exception("Invalid face descriptor.");

            }

            $update[] = "face_descriptor=?";
            $params[] = $_POST['face_descriptor'];

        }

        $newPhoto = true;

    }

    /* -----------------------------
       UPDATE DOCUMENT (OPTIONAL)
    ------------------------------ */

    $newDocument = false;

    if (
        isset($_FILES['employee_document']) &&
        $_FILES['employee_document']['error'] === UPLOAD_ERR_OK
    ) {

        $allowedExtensions = [
            'pdf',
            'jpg',
            'jpeg',
            'png'
        ];

        $extension = strtolower(
            pathinfo(
                $_FILES['employee_document']['name'],
                PATHINFO_EXTENSION
            )
        );

        if (!in_array($extension, $allowedExtensions)) {

            throw new Exception(
                "Only PDF, JPG, JPEG and PNG documents are allowed."
            );

        }

        if ($_FILES['employee_document']['size'] > 10 * 1024 * 1024) {

            throw new Exception(
                "Document must be less than 10 MB."
            );

        }

        $documentFileName =
            uniqid('DOC_') .
            "." .
            $extension;

        $documentFullPath =
            EMPLOYEE_DOCUMENT_DIR .
            $documentFileName;

        /* Path stored in database */

        $documentPath =
            'employees/documents/' .
            $documentFileName;

        if (
            !move_uploaded_file(
                $_FILES['employee_document']['tmp_name'],
                $documentFullPath
            )
        ) {

            throw new Exception(
                "Failed to save employee document."
            );

        }

        $update[] = "document_path=?";
        $params[] = $documentPath;

        $newDocument = true;

    }


    /* -----------------------------
       EXECUTE UPDATE
    ------------------------------ */

    $params[] = $_POST['id'];

    $sql = "
    UPDATE employees
    SET
        " . implode(",\n        ", $update) . "
    WHERE id=?
";

    $stmt = $pdo->prepare($sql);

    $stmt->execute($params);








    /* -----------------------------
       COMMIT
    ------------------------------ */

    $pdo->commit();

    /* -----------------------------
       DELETE OLD FILES
    ------------------------------ */

    if (
        $newPhoto &&
        !empty($oldPhoto)
    ) {

        $oldPhotoFile = STORAGE_ROOT . ltrim($oldPhoto, '/');

        if (file_exists($oldPhotoFile)) {

            @unlink($oldPhotoFile);

        }

    }

    if (
        $newDocument &&
        !empty($oldDocument)
    ) {

        $oldDocumentFile = STORAGE_ROOT . ltrim($oldDocument, '/');

        if (file_exists($oldDocumentFile)) {

            @unlink($oldDocumentFile);

        }

    }

    header("Location: ../edit_employee.php?id=" . $_POST['id'] . "&updated=1");
    exit();

} catch (Exception $e) {

    if ($pdo->inTransaction()) {

        $pdo->rollBack();

    }

    die(
        "Update failed: " .
        $e->getMessage()
    );

}