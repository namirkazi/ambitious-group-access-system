<?php

require_once '../includes/config.php';

header('Content-Type: application/json');

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
$stmt = $pdo->prepare(

"
SELECT *
FROM employee_attendance
WHERE employee_id=?
AND attendance_date=?
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

    $stmt =
    $pdo->prepare(

"
INSERT INTO employee_attendance(

employee_id,
attendance_date,
check_in,
status

)

VALUES(

?,
?,
NOW(),
'present'

)

"

);

    $stmt->execute([

        $employeeId,
        $today

    ]);

    echo json_encode([

        'success'=>true,

        'action'=>'checkin'

    ]);

    exit;

}
if(

$row['check_out'] === null

){

    echo json_encode([

        'success'=>true,

        'action'=>'checkout_prompt',

        'check_in'=>

        date(

            'h:i A',

            strtotime(
                $row['check_in']
            )

        )

    ]);

    exit;

}
echo json_encode([

    'success'=>true,

    'action'=>'done'

]);