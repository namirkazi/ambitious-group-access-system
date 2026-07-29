<?php
session_start();

if (
    !isset($_SESSION['admin_logged_in'])
) {
    http_response_code(403);
    exit('Access denied');
}

if (
    $_SESSION['role'] != 'hr'
) {
    http_response_code(403);
    exit('Insufficient privileges');
}

require_once '../includes/config.php';

$pdo = getDB();

$id =
isset($_GET['id']) ?
(int) $_GET['id'] :
0;

$stmt = $pdo->prepare("

SELECT *

FROM employees

WHERE id = ?

");

$stmt->execute([$id]);

$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    http_response_code(404);
    exit('Employee not found');
}

$filenameSafe =
preg_replace(
'/[^A-Za-z0-9_\-]/',
'_',
$employee['full_name'] ?? 'employee'
);

header(
'Content-Type: text/csv'
);

header(
'Content-Disposition: attachment; filename="' . $filenameSafe . '_details.csv"'
);

$output = fopen(
'php://output',
'w'
);

$fields = [
    'Title' => $employee['title'] ?? '-',
    'Full Name' => $employee['full_name'] ?? '-',
    'Legal Name' => $employee['legal_name'] ?? '-',
    'Gender' => $employee['gender'] ?? '-',
    'Date of Birth' => !empty($employee['dob']) ? date('d M Y', strtotime($employee['dob'])) : '-',
    'Nationality' => $employee['nationality'] ?? '-',
    'Mobile Number' => $employee['phone'] ?? '-',
    'Email Address' => $employee['email'] ?? '-',
    'Joining Date' => !empty($employee['joining_date']) ? date('d M Y', strtotime($employee['joining_date'])) : '-',
    'Department' => $employee['department'] ?? '-',
    'Designation' => $employee['designation'] ?? '-',
    'Document Type' => $employee['document_type'] ?? '-',
    'Document Number' =>  "\t" . $employee['document_number'] ?? '-',
    'Document Issue Date' => !empty($employee['document_issue_date']) ? date('d M Y', strtotime($employee['document_issue_date'])) : '-',
    'Document Expiry Date' => !empty($employee['document_expiry_date']) ? date('d M Y', strtotime($employee['document_expiry_date'])) : '-',
];

fputcsv(
$output,
array_keys($fields)
);

fputcsv(
$output,
array_values($fields)
);

fclose(
$output
);

exit;