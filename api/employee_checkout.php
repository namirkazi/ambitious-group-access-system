<?php

require_once '../includes/config.php';

header('Content-Type: application/json');

date_default_timezone_set(
    'Asia/Dubai'
);
$pdo = getDB();

$data = json_decode(
    file_get_contents('php://input'),
    true
);

$employeeId =
$data['employee_id'] ?? 0;

if(!$employeeId){

    echo json_encode([
        'success'=>false
    ]);

    exit;

}

$today =
date('Y-m-d');

$stmt =
$pdo->prepare(

"

SELECT

ea.id,
ea.check_in,

e.title,
e.full_name

FROM employee_attendance ea

JOIN employees e

ON e.id=ea.employee_id

WHERE

ea.employee_id=?

AND

ea.attendance_date=?

LIMIT 1

"

);

$stmt->execute([

$employeeId,
$today

]);

$row =
$stmt->fetch(
PDO::FETCH_ASSOC
);

if(!$row){

    echo json_encode([
        'success'=>false,
        'message'=>'Attendance record not found'
    ]);

    exit;

}

date_default_timezone_set('Asia/Dubai');

$checkInTime =
new DateTime(
    $row['check_in']
);

$checkOutTime =
new DateTime();

$interval =
$checkInTime->diff(
    $checkOutTime
);

$totalHours =
sprintf(

    '%02d:%02d',

    $interval->h +
    ($interval->days * 24),

    $interval->i

);

$stmt =
$pdo->prepare(

"

UPDATE employee_attendance

SET

check_out = NOW(),

total_hours = ?,

status='present'

WHERE id=?

"

);
$stmt->execute([

$totalHours,
$row['id']

]);

echo json_encode([

'success'=>true,

'name'=>

$row['title']

.' '.

$row['full_name'],

'check_out'=>

$checkOutTime->format('h:i A'),

'hours'=>

$totalHours

]);

?>