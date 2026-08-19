<?php

session_start();

require_once '../includes/config.php';

header('Content-Type: application/json');


/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
*/

function respond(
    bool $success,
    string $message,
    array $extra = []
): void {

    echo json_encode(
        array_merge(
            [
                'success' => $success,
                'message' => $message
            ],
            $extra
        )
    );

    exit;
}


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

    respond(
        false,
        'Driver session expired. Please scan your face again.'
    );
}


$driverId =
    (int) $_SESSION['driver_id'];


/*
|--------------------------------------------------------------------------
| Input
|--------------------------------------------------------------------------
*/

$vehicleId =
    isset($_POST['vehicle_id'])
    ? (int) $_POST['vehicle_id']
    : 0;

$startKm =
    isset($_POST['start_km'])
    ? trim($_POST['start_km'])
    : '';

$purpose =
    isset($_POST['purpose'])
    ? trim($_POST['purpose'])
    : '';

$requestorId =
    isset($_POST['requestor_id'])
    ? (int) $_POST['requestor_id']
    : 0;

$fromLocation =
    isset($_POST['from_location'])
    ? trim($_POST['from_location'])
    : '';

$toLocation =
    isset($_POST['to_location'])
    ? trim($_POST['to_location'])
    : '';


/*
|--------------------------------------------------------------------------
| Validate
|--------------------------------------------------------------------------
*/

if ($vehicleId <= 0) {

    respond(
        false,
        'Please select a vehicle.'
    );
}


if (
    $startKm === '' ||
    !is_numeric($startKm) ||
    (float) $startKm < 0
) {

    respond(
        false,
        'Please enter a valid current KM.'
    );
}


$startKm =
    (float) $startKm;


if ($purpose === '') {

    respond(
        false,
        'Please select a reason for the trip.'
    );
}


if ($requestorId <= 0) {

    respond(
        false,
        'Please select the requestor.'
    );
}


if ($fromLocation === '') {

    respond(
        false,
        'Please enter the starting location.'
    );
}


if ($toLocation === '') {

    respond(
        false,
        'Please enter the destination.'
    );
}


/*
|--------------------------------------------------------------------------
| Odometer photo
|--------------------------------------------------------------------------
*/

if (
    !isset($_FILES['start_photo']) ||
    $_FILES['start_photo']['error'] !== UPLOAD_ERR_OK
) {

    respond(
        false,
        'Please upload the current KM photo.'
    );
}


$uploadedFile =
    $_FILES['start_photo']['tmp_name'];


/*
|--------------------------------------------------------------------------
| File validation
|--------------------------------------------------------------------------
*/

$imageInfo =
    @getimagesize($uploadedFile);


if ($imageInfo === false) {

    respond(
        false,
        'The uploaded file is not a valid image.'
    );
}


$allowedTypes = [

    IMAGETYPE_JPEG,
    IMAGETYPE_PNG,
    IMAGETYPE_WEBP

];


if (
    !in_array(
        $imageInfo[2],
        $allowedTypes,
        true
    )
) {

    respond(
        false,
        'Only JPG, PNG or WEBP images are allowed.'
    );
}


/*
|--------------------------------------------------------------------------
| Database
|--------------------------------------------------------------------------
*/

$savedPhotoFilePath = null;
try {

    $pdo = getDB();
    $pdo->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | Check driver
    |--------------------------------------------------------------------------
    */

    $driverStmt = $pdo->prepare("
        SELECT id
        FROM drivers
        WHERE id = ?
          AND active = 1
        LIMIT 1
    ");

    $driverStmt->execute([
        $driverId
    ]);


    if (!$driverStmt->fetch()) {

        throw new Exception(
            'Driver account is not active.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Prevent duplicate active trip
    |--------------------------------------------------------------------------
    */

    $activeTripStmt = $pdo->prepare("
        SELECT id
        FROM driver_trips
        WHERE driver_id = ?
          AND status = 'started'
        LIMIT 1
        FOR UPDATE
    ");

    $activeTripStmt->execute([
        $driverId
    ]);


    if ($activeTripStmt->fetch()) {

        throw new Exception(
            'You already have an active trip.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Lock vehicle
    |--------------------------------------------------------------------------
    */

    $vehicleStmt = $pdo->prepare("
        SELECT
            id,
            vehicle_name,
            vehicle_number,
            active,
            in_use
        FROM vehicles
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");

    $vehicleStmt->execute([
        $vehicleId
    ]);


    $vehicle =
        $vehicleStmt->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$vehicle) {

        throw new Exception(
            'Vehicle not found.'
        );
    }


    if ((int) $vehicle['active'] !== 1) {

        throw new Exception(
            'This vehicle is inactive.'
        );
    }


    if ((int) $vehicle['in_use'] === 1) {

        throw new Exception(
            'This vehicle is already in use.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Compress image
    |--------------------------------------------------------------------------
    */

    $source =
        @imagecreatefromstring(
            file_get_contents($uploadedFile)
        );


    if (!$source) {

        throw new Exception(
            'Unable to process the odometer image.'
        );
    }


    $originalWidth =
        imagesx($source);

    $originalHeight =
        imagesy($source);


    /*
     * Maximum stored dimension.
     *
     * Odometer numbers remain readable while
     * keeping the file considerably smaller.
     */

    $maxDimension = 1280;


    $scale =
        min(
            1,
            $maxDimension /
                max(
                    $originalWidth,
                    $originalHeight
                )
        );


    $newWidth =
        (int) round(
            $originalWidth * $scale
        );

    $newHeight =
        (int) round(
            $originalHeight * $scale
        );


    $compressed =
        imagecreatetruecolor(
            $newWidth,
            $newHeight
        );


    /*
     * White background prevents transparent
     * PNG/WEBP images becoming black when
     * converted to JPEG.
     */

    $white =
        imagecolorallocate(
            $compressed,
            255,
            255,
            255
        );


    imagefill(
        $compressed,
        0,
        0,
        $white
    );


    imagecopyresampled(
        $compressed,
        $source,
        0,
        0,
        0,
        0,
        $newWidth,
        $newHeight,
        $originalWidth,
        $originalHeight
    );


    imagedestroy($source);


    /*
    |--------------------------------------------------------------------------
    | Save compressed image
    |--------------------------------------------------------------------------
    */

    $uploadDir = DRIVER_TRIP_DIR;


    if (
        !is_dir($uploadDir) &&
        !mkdir(
            $uploadDir,
            0755,
            true
        )
    ) {

        imagedestroy($compressed);

        throw new Exception(
            'Unable to create trip image directory.'
        );
    }


    $fileName =
        'trip_start_' .
        $driverId .
        '_' .
        time() .
        '_' .
        bin2hex(
            random_bytes(4)
        ) .
        '.jpg';


    $fullPath =
        $uploadDir .
        $fileName;

    $savedPhotoFilePath =
        $fullPath;
    if (
        !imagejpeg(
            $compressed,
            $fullPath,
            70
        )
    ) {

        imagedestroy($compressed);

        throw new Exception(
            'Unable to save compressed odometer image.'
        );
    }


    imagedestroy($compressed);


    /*
    |--------------------------------------------------------------------------
    | Relative path
    |--------------------------------------------------------------------------
    */

    $photoPath =
        'driver_trips/' .
        $fileName;


    /*
    |--------------------------------------------------------------------------
    | Create trip
    |--------------------------------------------------------------------------
    */

    $insertStmt = $pdo->prepare("
        INSERT INTO driver_trips
        (
            driver_id,
            vehicle_id,
            start_km,
            purpose,
            requestor_id,
            from_location,
            to_location,
            start_photo,
            trip_start,
            status,
            created_at,
            updated_at
        )
        VALUES
        (
            :driver_id,
            :vehicle_id,
            :start_km,
            :purpose,
            :requestor_id,
            :from_location,
            :to_location,
            :start_photo,
            NOW(),
            'started',
            NOW(),
            NOW()
        )
    ");


    $insertStmt->execute([

        ':driver_id' =>
        $driverId,

        ':vehicle_id' =>
        $vehicleId,

        ':start_km' =>
        $startKm,

        ':purpose' =>
        $purpose,

        ':requestor_id' =>
        $requestorId,

        ':from_location' =>
        $fromLocation,

        ':to_location' =>
        $toLocation,

        ':start_photo' =>
        $photoPath

    ]);


    $tripId =
        (int) $pdo->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | Mark vehicle in use
    |--------------------------------------------------------------------------
    */

    $updateVehicle =
        $pdo->prepare("
            UPDATE vehicles
            SET in_use = 1,
                updated_at = NOW()
            WHERE id = ?
        ");


    $updateVehicle->execute([
        $vehicleId
    ]);


    $pdo->commit();


    respond(
        true,
        'Trip started successfully.',
        [
            'trip_id' => $tripId,
            'redirect' => 'trip_stop.php'
        ]
    );
} catch (Throwable $e) {

    if (
        isset($pdo) &&
        $pdo->inTransaction()
    ) {

        $pdo->rollBack();
    }


    error_log(
        'Start trip error: ' .
            $e->getMessage()
    );

    if (
        $savedPhotoFilePath &&
        file_exists(
            $savedPhotoFilePath
        )
    ) {

        @unlink(
            $savedPhotoFilePath
        );
    }
    respond(
        false,
        $e->getMessage()
    );
}
