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


$stmt = $pdo->prepare("

SELECT *

FROM employees

WHERE active = 1
ORDER BY full_name ASC;

");
$stmt->execute();
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="active_employees_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'Title',
    'Full Name',
    'Legal Name',
    'Gender',
    'Date of Birth',
    'Nationality',
    'Mobile Number',
    'Email Address',
    'Joining Date',
    'Department',
    'Designation',
    'Document Type',
    'Document Number',
    'Document Issue Date',
    'Document Expiry Date'
]);

while ($employee = $stmt->fetch(PDO::FETCH_ASSOC)) {

    fputcsv($output, [
        $employee['title'] ?? '-',
        $employee['full_name'] ?? '-',
        $employee['legal_name'] ?? '-',
        $employee['gender'] ?? '-',
        !empty($employee['dob']) ? date('d M Y', strtotime($employee['dob'])) : '-',
        $employee['nationality'] ?? '-',
        $employee['phone'] ?? '-',
        $employee['email'] ?? '-',
        !empty($employee['joining_date']) ? date('d M Y', strtotime($employee['joining_date'])) : '-',
        $employee['department'] ?? '-',
        $employee['designation'] ?? '-',
        $employee['document_type'] ?? '-',
        "\t" . ($employee['document_number'] ?? '-'),
        !empty($employee['document_issue_date']) ? date('d M Y', strtotime($employee['document_issue_date'])) : '-',
        !empty($employee['document_expiry_date']) ? date('d M Y', strtotime($employee['document_expiry_date'])) : '-'
    ]);

}

fclose($output);
exit;