<?php

session_start();

require_once '../includes/config.php';

header('Content-Type: application/json');


/*
|--------------------------------------------------------------------------
| Read request
|--------------------------------------------------------------------------
*/

$data = json_decode(
    file_get_contents('php://input'),
    true
);


if (
    !$data ||
    !isset($data['descriptor']) ||
    !is_array($data['descriptor'])
) {

    echo json_encode([
        'success' => false,
        'message' => 'No valid face descriptor received'
    ]);

    exit;
}


$descriptor = $data['descriptor'];


/*
|--------------------------------------------------------------------------
| Database
|--------------------------------------------------------------------------
*/

try {

    $pdo = getDB();

}
catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);

    exit;

}


/*
|--------------------------------------------------------------------------
| Get active drivers with face descriptors
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT
            id,
            full_name,
            face_descriptor
        FROM drivers
        WHERE active = 1
          AND face_descriptor IS NOT NULL
    ");

}
catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to load drivers'
    ]);

    exit;

}


/*
|--------------------------------------------------------------------------
| Find closest driver
|--------------------------------------------------------------------------
*/

$bestDriver = null;

$bestDistance = 999;


while (
    $row = $stmt->fetch(PDO::FETCH_ASSOC)
) {

    $known = json_decode(
        $row['face_descriptor'],
        true
    );


    if (
        !is_array($known) ||
        count($known) === 0
    ) {

        continue;

    }


    /*
     * A face descriptor should contain
     * the same number of values as the
     * descriptor received from face-api.js.
     */

    if (
        count($known) !== count($descriptor)
    ) {

        continue;

    }


    $distance = 0;


    for (
        $i = 0;
        $i < count($known);
        $i++
    ) {

        $difference =
            $known[$i] -
            $descriptor[$i];

        $distance +=
            $difference *
            $difference;

    }


    $distance =
        sqrt($distance);


    if (
        $distance <
        $bestDistance
    ) {

        $bestDistance =
            $distance;

        $bestDriver =
            $row;

    }

}


/*
|--------------------------------------------------------------------------
| Check match
|--------------------------------------------------------------------------
*/

if (
    $bestDriver !== null &&
    $bestDistance < 0.55
) {

    /*
     * Driver has been successfully
     * recognized.
     */

    $_SESSION['driver_id'] =
        (int) $bestDriver['id'];


    /*
     * Check whether this driver already
     * has an active trip.
     *
     * We will use this when trip_start.php
     * and trip_end.php are built.
     */

    $tripStmt = $pdo->prepare("
        SELECT
            id
        FROM driver_trips
        WHERE driver_id = ?
          AND status = 'started'
        ORDER BY trip_start DESC
        LIMIT 1
    ");


    $tripStmt->execute([
        $bestDriver['id']
    ]);


    $activeTrip =
        $tripStmt->fetch(
            PDO::FETCH_ASSOC
        );


    /*
     * If an active trip exists, send the
     * driver to trip_end.php.
     *
     * Otherwise start a new trip.
     */

    $redirect =
        $activeTrip
            ? 'trip_end.php'
            : 'trip_start.php';


    echo json_encode([

        'success' => true,

        'driver_id' =>
            (int) $bestDriver['id'],

        'name' =>
            $bestDriver['full_name'],

        'distance' =>
            round(
                $bestDistance,
                4
            ),

        'active_trip' =>
            $activeTrip
                ? true
                : false,

        'trip_id' =>
            $activeTrip
                ? (int) $activeTrip['id']
                : null,

        'redirect' =>
            $redirect

    ]);

    exit;

}


/*
|--------------------------------------------------------------------------
| No matching driver
|--------------------------------------------------------------------------
*/

echo json_encode([

    'success' => false,

    'message' =>
        'Driver not recognized'

]);