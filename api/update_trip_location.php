<?php

session_start();

require_once '../includes/config.php';

header('Content-Type: application/json');


/*
|--------------------------------------------------------------------------
| Driver authentication
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['driver_id']) ||
    !is_numeric($_SESSION['driver_id'])
) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Driver authentication required.'
    ]);

    exit;
}


$driverId = (int) $_SESSION['driver_id'];


/*
|--------------------------------------------------------------------------
| Read input
|--------------------------------------------------------------------------
*/

$tripId = isset($_POST['trip_id'])
    ? (int) $_POST['trip_id']
    : 0;

$latitude = isset($_POST['latitude'])
    ? (float) $_POST['latitude']
    : 0;

$longitude = isset($_POST['longitude'])
    ? (float) $_POST['longitude']
    : 0;

$accuracy = isset($_POST['accuracy']) &&
    $_POST['accuracy'] !== ''
    ? (float) $_POST['accuracy']
    : null;


/*
|--------------------------------------------------------------------------
| Validate
|--------------------------------------------------------------------------
*/

if ($tripId <= 0) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid trip.'
    ]);

    exit;
}


if (
    $latitude < -90 ||
    $latitude > 90 ||
    $longitude < -180 ||
    $longitude > 180
) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid GPS coordinates.'
    ]);

    exit;
}


if (
    $accuracy !== null &&
    ($accuracy < 0 || $accuracy > 100000)
) {

    $accuracy = null;
}


/*
|--------------------------------------------------------------------------
| Database
|--------------------------------------------------------------------------
*/

try {

    $pdo = getDB();


    /*
    |--------------------------------------------------------------------------
    | Verify active trip belongs to this driver
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT id
        FROM driver_trips
        WHERE id = ?
          AND driver_id = ?
          AND status = 'started'
        LIMIT 1
    ");

    $stmt->execute([
        $tripId,
        $driverId
    ]);

    $trip = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$trip) {

        http_response_code(403);

        echo json_encode([
            'success' => false,
            'message' => 'Active trip not found.'
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Store location
    |--------------------------------------------------------------------------
    */

    $insert = $pdo->prepare("
        INSERT INTO driver_trip_locations
        (
            trip_id,
            driver_id,
            latitude,
            longitude,
            accuracy,
            recorded_at
        )
        VALUES
        (
            :trip_id,
            :driver_id,
            :latitude,
            :longitude,
            :accuracy,
            NOW()
        )
    ");

    $insert->execute([
        ':trip_id' =>
        $tripId,

        ':driver_id' =>
        $driverId,

        ':latitude' =>
        $latitude,

        ':longitude' =>
        $longitude,

        ':accuracy' =>
        $accuracy
    ]);


    echo json_encode([
        'success' => true,
        'message' => 'Location updated.'
    ]);
} catch (Throwable $e) {

    error_log(
        'Trip location update error: ' .
            $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to update location.'
    ]);
}
