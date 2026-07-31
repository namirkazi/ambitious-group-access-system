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
    &&
    $_SESSION['role'] != 'admin'
) {
    http_response_code(403);
    exit('Insufficient privileges');
}

require_once '../includes/config.php';
require_once '../includes/attendance_report.php';
$pdo = getDB();

header(
    'Content-Type: text/csv'
);

header(
    'Content-Disposition: attachment; filename="attendance_report.csv"'
);

$output = fopen(
    'php://output',
    'w'
);

fputcsv($output, [
    'Employee',
    'Department',
    'Date',
    'Check In',
    'Check Out',
    'Hours Worked',
    'Status'
]);

$from =
    $_GET['from'] ??
    date('Y-m-01');

$to =
    $_GET['to'] ??
    date('Y-m-d');
$search =
    trim($_GET['search'] ?? '');

$rows = generateAttendanceReport(
    $pdo,
    $from,
    $to,
    $search
);

foreach ($rows as $row) {

    fputcsv($output, [

        trim($row['title'] . ' ' . $row['full_name']),

        $row['department'],

        date('d-m-Y', strtotime($row['attendance_date'])),

        $row['check_in']
            ? date('h:i A', strtotime($row['check_in']))
            : '-',

        $row['check_out']
            ? date('h:i A', strtotime($row['check_out']))
            : '-',

        $row['total_hours'] ?: '-',

        $row['status']

    ]);
}
fclose(
    $output
);

exit;
