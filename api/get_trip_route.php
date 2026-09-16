<?php

session_start();

header('Content-Type: application/json');

require_once '../includes/config.php';


/*
|--------------------------------------------------------------------------
| Admin / HR authentication
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized.'
    ]);

    exit;
}


if (
    $_SESSION['role'] !== 'hr' &&
    $_SESSION['role'] !== 'admin'
) {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'Access denied.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Trip ID
|--------------------------------------------------------------------------
*/

$tripId = filter_input(
    INPUT_GET,
    'trip_id',
    FILTER_VALIDATE_INT
);


if (!$tripId || $tripId <= 0) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid trip ID.'
    ]);

    exit;
}


try {

    $pdo = getDB();


    /*
    |--------------------------------------------------------------------------
    | Make sure trip exists
    |--------------------------------------------------------------------------
    */

    $tripStmt = $pdo->prepare("
        SELECT
            id,
            driver_id,
            status
        FROM driver_trips
        WHERE id = ?
        LIMIT 1
    ");

    $tripStmt->execute([
        $tripId
    ]);

    $trip = $tripStmt->fetch(PDO::FETCH_ASSOC);


    if (!$trip) {

        echo json_encode([
            'success' => false,
            'message' => 'Trip not found.'
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Get GPS history
    |--------------------------------------------------------------------------
    */

    $locationStmt = $pdo->prepare("
        SELECT
            latitude,
            longitude,
            accuracy,
            recorded_at
        FROM driver_trip_locations
        WHERE trip_id = ?
        ORDER BY recorded_at ASC, id ASC
    ");

    $locationStmt->execute([
        $tripId
    ]);


    $locations = [];


    while ($row = $locationStmt->fetch(PDO::FETCH_ASSOC)) {

        $locations[] = [
            'latitude' =>
            (float) $row['latitude'],

            'longitude' =>
            (float) $row['longitude'],

            'accuracy' =>
            $row['accuracy'] !== null
                ? (float) $row['accuracy']
                : null,

            'recorded_at' =>
            $row['recorded_at']
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        'success' => true,

        'trip_id' =>
        (int) $trip['id'],

        'status' =>
        $trip['status'],

        'locations' =>
        $locations,

        'count' =>
        count($locations)
    ]);
} catch (Throwable $e) {

    error_log(
        'Get trip route error: ' .
            $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to load trip route.'
    ]);
}
