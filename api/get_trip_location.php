<?php

session_start();

require_once '../includes/config.php';

header('Content-Type: application/json');


/*
|--------------------------------------------------------------------------
| Admin / HR authentication
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['role']) ||
    !in_array(
        $_SESSION['role'],
        ['admin', 'hr'],
        true
    )
) {

    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Trip ID
|--------------------------------------------------------------------------
*/

$tripId = isset($_GET['trip_id'])
    ? (int) $_GET['trip_id']
    : 0;


if ($tripId <= 0) {

    http_response_code(400);

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
    | Get latest location
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            latitude,
            longitude,
            accuracy,
            recorded_at
        FROM driver_trip_locations
        WHERE trip_id = ?
        ORDER BY recorded_at DESC, id DESC
        LIMIT 1
    ");

    $stmt->execute([
        $tripId
    ]);

    $location = $stmt->fetch(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | Get trip status
    |--------------------------------------------------------------------------
    */

    $tripStmt = $pdo->prepare("
        SELECT status
        FROM driver_trips
        WHERE id = ?
        LIMIT 1
    ");

    $tripStmt->execute([
        $tripId
    ]);

    $trip = $tripStmt->fetch(PDO::FETCH_ASSOC);


    echo json_encode([
        'success' => true,

        'active' =>
        $trip &&
            $trip['status'] === 'started',

        'location' =>
        $location ?: null
    ]);
} catch (Throwable $e) {

    error_log(
        'Get trip location error: ' .
            $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to get trip location.'
    ]);
}
