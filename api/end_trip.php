<?php

session_start();

require_once '../includes/config.php';

header('Content-Type: application/json');


/*
|--------------------------------------------------------------------------
| JSON response helper
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
| Get ending KM
|--------------------------------------------------------------------------
*/

$endKm =
    isset($_POST['end_km'])
    ? trim($_POST['end_km'])
    : '';


if (
    $endKm === '' ||
    !is_numeric($endKm) ||
    (float) $endKm < 0
) {

    respond(
        false,
        'Please enter a valid current KM.'
    );
}
$remarks =
    isset($_POST['remarks'])
    ? trim($_POST['remarks'])
    : '';

$endKm =
    (float) $endKm;


/*
|--------------------------------------------------------------------------
| Ending odometer photo
|--------------------------------------------------------------------------
*/

if (
    !isset($_FILES['end_photo']) ||
    $_FILES['end_photo']['error'] !== UPLOAD_ERR_OK
) {

    respond(
        false,
        'Please upload the current KM photo.'
    );
}


$uploadedFile =
    $_FILES['end_photo']['tmp_name'];


/*
|--------------------------------------------------------------------------
| Validate image
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

$pdo = null;

$savedPhotoPath = null;


try {

    $pdo =
        getDB();


    $pdo->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | Get active trip
    |--------------------------------------------------------------------------
    */

    $tripStmt = $pdo->prepare("
        SELECT
            id,
            vehicle_id,
            start_km,
            start_photo,
            trip_start

        FROM driver_trips

        WHERE driver_id = ?

          AND status = 'started'

        ORDER BY trip_start DESC

        LIMIT 1

        FOR UPDATE
    ");


    $tripStmt->execute([
        $driverId
    ]);


    $trip =
        $tripStmt->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$trip) {

        throw new Exception(
            'No active trip was found.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Validate ending KM
    |--------------------------------------------------------------------------
    */

    $startKm =
        (float) $trip['start_km'];


    if ($endKm < $startKm) {

        throw new Exception(
            'Ending KM cannot be lower than starting KM.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Calculate distance
    |--------------------------------------------------------------------------
    */

    $distance =
        $endKm - $startKm;


    /*
    |--------------------------------------------------------------------------
    | Lock vehicle
    |--------------------------------------------------------------------------
    */

    $vehicleStmt = $pdo->prepare("
        SELECT
            id,
            in_use

        FROM vehicles

        WHERE id = ?

        LIMIT 1

        FOR UPDATE
    ");


    $vehicleStmt->execute([
        (int) $trip['vehicle_id']
    ]);


    $vehicle =
        $vehicleStmt->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$vehicle) {

        throw new Exception(
            'Vehicle associated with this trip was not found.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Compress ending odometer image
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
    |--------------------------------------------------------------------------
    | Resize if necessary
    |--------------------------------------------------------------------------
    */

    $maxDimension =
        1280;


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
    |--------------------------------------------------------------------------
    | White background
    |--------------------------------------------------------------------------
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


    /*
    |--------------------------------------------------------------------------
    | Resize image
    |--------------------------------------------------------------------------
    */

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


    imagedestroy(
        $source
    );


    /*
    |--------------------------------------------------------------------------
    | Upload directory
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

        imagedestroy(
            $compressed
        );

        throw new Exception(
            'Unable to create trip image directory.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Unique filename
    |--------------------------------------------------------------------------
    */

    $fileName =
        'trip_end_' .
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


    /*
    |--------------------------------------------------------------------------
    | Save compressed JPEG
    |--------------------------------------------------------------------------
    */

    if (
        !imagejpeg(
            $compressed,
            $fullPath,
            70
        )
    ) {

        imagedestroy(
            $compressed
        );

        throw new Exception(
            'Unable to save compressed odometer image.'
        );
    }


    imagedestroy(
        $compressed
    );


    /*
    |--------------------------------------------------------------------------
    | Relative database path
    |--------------------------------------------------------------------------
    */

    $savedPhotoPath =
        'driver_trips/' .
        $fileName;


    /*
    |--------------------------------------------------------------------------
    | Complete trip
    |--------------------------------------------------------------------------
    */

    $updateTrip = $pdo->prepare("
        UPDATE driver_trips

        SET
    end_km = :end_km,
    distance = :distance,
    end_photo = :end_photo,
    remarks = :remarks,
    trip_end = NOW(),
    status = 'completed',
    updated_at = NOW()

        WHERE id = :trip_id

          AND driver_id = :driver_id

          AND status = 'started'
    ");


    $updateTrip->execute([

        ':end_km' =>
        $endKm,

        ':distance' =>
        $distance,

        ':end_photo' =>
        $savedPhotoPath,
        ':remarks' =>
        $remarks,
        ':trip_id' =>
        (int) $trip['id'],

        ':driver_id' =>
        $driverId



    ]);


    if (
        $updateTrip->rowCount() !== 1
    ) {

        throw new Exception(
            'The trip could not be completed.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Free vehicle
    |--------------------------------------------------------------------------
    */

    $updateVehicle = $pdo->prepare("
        UPDATE vehicles

        SET
            in_use = 0,
            updated_at = NOW()

        WHERE id = ?
    ");


    $updateVehicle->execute([
        (int) $trip['vehicle_id']
    ]);


    /*
    |--------------------------------------------------------------------------
    | Commit
    |--------------------------------------------------------------------------
    */

    $pdo->commit();


    /*
    |--------------------------------------------------------------------------
    | Clear driver trip session
    |--------------------------------------------------------------------------
    */

    unset(
        $_SESSION['driver_id']
    );


    /*
    |--------------------------------------------------------------------------
    | Success
    |--------------------------------------------------------------------------
    */

    respond(
        true,
        'Trip ended successfully.',
        [
            'trip_id' =>
            (int) $trip['id'],

            'start_km' =>
            $startKm,

            'end_km' =>
            $endKm,

            'distance' =>
            $distance,

            'redirect' =>
            'driver.php'
        ]
    );
} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | Rollback
    |--------------------------------------------------------------------------
    */

    if (
        $pdo &&
        $pdo->inTransaction()
    ) {

        $pdo->rollBack();
    }


    /*
    |--------------------------------------------------------------------------
    | Delete image if DB operation failed
    |--------------------------------------------------------------------------
    */

    if (
        $savedPhotoPath
    ) {

        $savedFilePath =
            DRIVER_TRIP_DIR .
            basename($savedPhotoPath);

        if (
            file_exists(
                $savedFilePath
            )
        ) {

            @unlink(
                $savedFilePath
            );
        }
    }


    error_log(
        'End trip error: ' .
            $e->getMessage()
    );


    respond(
        false,
        $e->getMessage()
    );
}
