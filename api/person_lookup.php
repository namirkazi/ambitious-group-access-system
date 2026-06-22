<?php

require_once '../includes/config.php';

header('Content-Type: application/json');
$pdo = getDB();
$data = json_decode(
    file_get_contents('php://input'),
    true
);

if(
    !$data
    ||
    !isset($data['descriptor'])
){

    echo json_encode([

        'success'=>false,

        'message'=>'No descriptor'

    ]);

    exit;

}
$descriptor = $data['descriptor'];
$stmtEmp = $pdo->query("
SELECT
    id,
    title,
    full_name,
    face_descriptor
FROM employees
WHERE active=1
");

$bestEmployee = null;
$bestEmployeeDistance = 999;

while($row = $stmtEmp->fetch(PDO::FETCH_ASSOC)){

    $known = json_decode(
        $row['face_descriptor'],
        true
    );

    if(!$known){
        continue;
    }

    $distance = 0;

    for($i=0;$i<count($known);$i++){

        $distance += pow(

            $known[$i] - $descriptor[$i],

            2

        );

    }

    $distance = sqrt($distance);

    if($distance < $bestEmployeeDistance){

        $bestEmployeeDistance = $distance;

        $bestEmployee = $row;

    }

}


$stmt = $pdo->query("
    SELECT
        id,
        face_descriptor
    FROM visitors
    WHERE face_descriptor IS NOT NULL
");

$bestVisitor = null;
$bestDistance = 999;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $known = json_decode(
        $row['face_descriptor'],
        true
    );

    if (!$known) {
        continue;
    }

    $distance = 0;

    for ($i = 0; $i < count($known); $i++) {

        $distance += pow(
            $known[$i] - $descriptor[$i],
            2
        );
    }

    $distance = sqrt($distance);

    if ($distance < $bestDistance) {

        $bestDistance = $distance;
        $bestVisitor = $row['id'];
    }
}

if(

    $bestEmployee !== null

    &&

    $bestEmployeeDistance < 0.55

){

    echo json_encode([

        'success'=>true,

        'type'=>'employee',

        'employee_id'=>$bestEmployee['id'],

        'name'=>

        $bestEmployee['title']

        .' '.

        $bestEmployee['full_name'],

        'distance'=>

        round(
            $bestEmployeeDistance,
            4
        )

    ]);

    exit;

}
if (
    $bestVisitor !== null &&
    $bestDistance < 0.55
) {

    // Check whether visitor is currently inside
$stmt2 = $pdo->prepare("
    SELECT id
    FROM visit_logs
    WHERE visitor_id = ?
      AND status = 'checked_in'
    ORDER BY check_in DESC
    LIMIT 1
");

$stmt2->execute([$bestVisitor]);

$currentVisit = $stmt2->fetch(PDO::FETCH_ASSOC);

echo json_encode([

    'success'=>true,

    'type'=>'visitor',

    'visitor_id'=>$bestVisitor,

    'distance'=>round(
        $bestDistance,
        4
    ),

    'checked_in'=>

    $currentVisit ? true : false,

    'log_id'=>

    $currentVisit['id'] ?? null

]);

} else {

echo json_encode([

    'success'=>false,

    'type'=>'new',

    'message'=>'No match'

]);
}