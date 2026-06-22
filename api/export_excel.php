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

fputcsv(
$output,
[
'Employee',
'Department',
'Date',
'Check In',
'Check Out',
'Hours Worked'
]
);

$stmt = $pdo->query("

SELECT

CONCAT(
e.title,
' ',
e.full_name
) AS employee_name,

e.department,

ea.attendance_date,

ea.check_in,

ea.check_out,

ea.total_hours

FROM employee_attendance ea

JOIN employees e
ON ea.employee_id=e.id

ORDER BY
ea.attendance_date DESC,
ea.check_in DESC

");

while(
$row =
$stmt->fetch(
PDO::FETCH_ASSOC
)
){

    fputcsv(
        $output,
        $row
    );

}

fclose(
$output
);

exit;