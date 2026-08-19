<?php

session_start();

require_once 'includes/config.php';


/*
|--------------------------------------------------------------------------
| Driver authentication
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['driver_id']) ||
    !is_numeric($_SESSION['driver_id'])
) {

    header('Location: driver.php');
    exit;
}


$driverId = (int) $_SESSION['driver_id'];

$pdo = getDB();


/*
|--------------------------------------------------------------------------
| Get driver
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        full_name
    FROM drivers
    WHERE id = ?
      AND active = 1
    LIMIT 1
");

$stmt->execute([
    $driverId
]);

$driver = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$driver) {

    unset($_SESSION['driver_id']);

    header('Location: driver.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Check for an existing active trip
|--------------------------------------------------------------------------
*/

$tripStmt = $pdo->prepare("
    SELECT
        id
    FROM driver_trips
    WHERE driver_id = ?
      AND status = 'started'
    LIMIT 1
");

$tripStmt->execute([
    $driverId
]);

$activeTrip = $tripStmt->fetch(PDO::FETCH_ASSOC);


if ($activeTrip) {

    header('Location: trip_end.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Get available vehicles
|--------------------------------------------------------------------------
|
| Vehicle table contains only:
|
| id
| vehicle_name
| vehicle_number
| active
| in_use
|
*/

$vehicleStmt = $pdo->query("
    SELECT
        id,
        vehicle_name,
        vehicle_number
    FROM vehicles
    WHERE active = 1
      AND in_use = 0
    ORDER BY vehicle_name ASC
");

$vehicles = $vehicleStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Start Trip — Ambitious Group</title>
    <link
        rel="apple-touch-icon"
        sizes="180x180"
        href="assets/favicon/apple-touch-icon.png">

    <link
        rel="icon"
        type="image/png"
        sizes="32x32"
        href="assets/favicon/favicon-32x32.png">

    <link
        rel="icon"
        type="image/png"
        sizes="16x16"
        href="assets/favicon/favicon-16x16.png">

    <link
        rel="icon"
        href="assets/favicon/favicon.ico">
    <style>
        :root {
            --bg: #0f1117;
            --surface: #1a1d27;
            --surface2: #222538;
            --accent: #6c63ff;
            --accent2: #a78bfa;
            --success: #22c55e;
            --danger: #ef4444;
            --text: #e2e8f0;
            --muted: #94a3b8;
            --border: #2d3148;
        }

        * {
            box-sizing: border-box;
        }

        body {

            margin: 0;

            min-height: 100vh;

            background: var(--bg);

            color: var(--text);

            font-family:
                'Segoe UI',
                system-ui,
                sans-serif;

        }

        header {

            background: var(--surface);

            border-bottom:
                1px solid var(--border);

            padding:
                1rem 1.25rem;

        }

        .header-inner {

            width: 100%;

            max-width: 650px;

            margin: auto;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 1rem;

        }

        .logo {

            font-size: 1.05rem;

            font-weight: 800;

        }

        .driver-name {

            color: var(--muted);

            font-size: .9rem;

            text-align: right;

        }

        main {

            width: 100%;

            max-width: 650px;

            margin: auto;

            padding: 1.25rem;

        }

        h1 {

            margin:
                .5rem 0 .35rem;

            font-size: 1.6rem;

        }

        .subtitle {

            margin:
                0 0 1.5rem;

            color: var(--muted);

        }

        .form-card {

            background: var(--surface);

            border:
                1px solid var(--border);

            border-radius: 12px;

            padding: 1.25rem;

        }

        .form-group {

            margin-bottom: 1rem;

        }

        label {

            display: block;

            margin-bottom: .45rem;

            color: var(--muted);

            font-size: .85rem;

        }

        input,
        select {

            width: 100%;

            padding: .85rem;

            border:
                1px solid var(--border);

            border-radius: 8px;

            background: var(--surface2);

            color: var(--text);

            font-family: inherit;

            font-size: 1rem;

        }

        input:focus,
        select:focus {

            outline: none;

            border-color: var(--accent);

        }

        input[type="file"] {

            padding: .7rem;

        }

        .photo-preview {

            display: none;

            width: 100%;

            max-height: 260px;

            margin-top: .75rem;

            border-radius: 8px;

            object-fit: contain;

            background: #000;

        }

        .start-btn {

            width: 100%;

            margin-top: .25rem;

            padding: .95rem;

            border: none;

            border-radius: 8px;

            background: var(--accent);

            color: #fff;

            font-family: inherit;

            font-size: 1rem;

            font-weight: 700;

            cursor: pointer;

        }

        .start-btn:disabled {

            opacity: .5;

            cursor: not-allowed;

        }

        .error-message {

            display: none;

            margin-bottom: 1rem;

            padding: .8rem;

            border-radius: 8px;

            background:
                rgba(239, 68, 68, .12);

            border:
                1px solid rgba(239, 68, 68, .3);

            color: #fca5a5;

            font-size: .9rem;

        }

        .no-vehicles {

            padding: 1rem;

            border-radius: 8px;

            background: var(--surface2);

            color: var(--muted);

            text-align: center;

        }

        @media (max-width: 600px) {

            main {

                padding: 1rem;

            }

            .form-card {

                padding: 1rem;

            }

            .header-inner {

                align-items: flex-start;

            }

            .driver-name {

                font-size: .8rem;

            }

        }
    </style>

</head>

<body>


    <header>

        <div class="header-inner">

            <div class="logo">
                Ambitious Group
            </div>

            <div class="driver-name">
                <?= htmlspecialchars($driver['full_name']) ?>
            </div>

        </div>

    </header>


    <main>

        <h1>Start Trip</h1>

        <p class="subtitle">
            Enter the trip details before starting.
        </p>


        <div
            id="errorMessage"
            class="error-message"></div>


        <form
            id="tripForm"
            class="form-card"
            enctype="multipart/form-data">


            <!-- VEHICLE -->

            <div class="form-group">

                <label for="vehicle_id">
                    Vehicle
                </label>

                <select
                    id="vehicle_id"
                    name="vehicle_id"
                    required>

                    <option value="">
                        Select vehicle
                    </option>

                    <?php foreach ($vehicles as $vehicle): ?>

                        <option
                            value="<?= (int) $vehicle['id'] ?>">
                            <?= htmlspecialchars($vehicle['vehicle_name']) ?>
                            —
                            <?= htmlspecialchars($vehicle['vehicle_number']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>


                <?php if (empty($vehicles)): ?>

                    <div class="no-vehicles">
                        No vehicles are currently available.
                    </div>

                <?php endif; ?>

            </div>


            <!-- CURRENT KM -->

            <div class="form-group">

                <label for="start_km">
                    Current KM
                </label>

                <input
                    type="number"
                    id="start_km"
                    name="start_km"
                    min="0"
                    step="1"
                    inputmode="numeric"
                    placeholder="Enter current KM"
                    required>

            </div>


            <!-- ODOMETER PHOTO -->

            <div class="form-group">

                <label for="start_photo">
                    Current KM Photo
                </label>

                <input
                    type="file"
                    id="start_photo"
                    name="start_photo"
                    accept="image/*"
                    capture="environment"
                    required>

                <img
                    id="photoPreview"
                    class="photo-preview"
                    alt="Odometer preview">

            </div>


            <!-- PURPOSE -->

            <div class="form-group">

                <label for="purpose">
                    Reason
                </label>

                <select
                    id="purpose"
                    name="purpose"
                    required>

                    <option value="">
                        Select reason
                    </option>

                    <option value="Pickup">
                        Pickup
                    </option>

                    <option value="Drop">
                        Drop
                    </option>

                    <option value="Other">
                        Other
                    </option>

                </select>

            </div>


            <!-- REQUESTOR -->

            <div class="form-group">

                <label for="requestor_id">
                    Requestor
                </label>

                <select
                    id="requestor_id"
                    name="requestor_id"
                    required>

                    <option value="">
                        Select requestor
                    </option>

                    <?php
                    $requestorStmt = $pdo->query("
            SELECT
                id,
                name
            FROM requestors
            ORDER BY name ASC
        ");

                    $requestors =
                        $requestorStmt->fetchAll(
                            PDO::FETCH_ASSOC
                        );
                    ?>

                    <?php foreach ($requestors as $requestor): ?>

                        <option
                            value="<?= (int) $requestor['id'] ?>">
                            <?= htmlspecialchars($requestor['name']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- FROM -->

            <div class="form-group">

                <label for="from_location">
                    From
                </label>

                <input
                    type="text"
                    id="from_location"
                    name="from_location"
                    maxlength="255"
                    placeholder="Starting location"
                    required>

            </div>


            <!-- TO -->

            <div class="form-group">

                <label for="to_location">
                    To
                </label>

                <input
                    type="text"
                    id="to_location"
                    name="to_location"
                    maxlength="255"
                    placeholder="Destination"
                    required>

            </div>


            <button
                type="submit"
                id="startBtn"
                class="start-btn"
                <?= empty($vehicles) ? 'disabled' : '' ?>>
                Start Trip
            </button>

        </form>

    </main>


    <script>
        /*
|--------------------------------------------------------------------------
| Odometer photo preview
|--------------------------------------------------------------------------
*/

        const photoInput =
            document.getElementById(
                'start_photo'
            );

        const photoPreview =
            document.getElementById(
                'photoPreview'
            );


        photoInput.addEventListener(
            'change',
            function() {

                const file =
                    this.files[0];

                if (!file) {

                    photoPreview.style.display =
                        'none';

                    photoPreview.removeAttribute(
                        'src'
                    );

                    return;

                }


                photoPreview.src =
                    URL.createObjectURL(file);

                photoPreview.style.display =
                    'block';

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Submit trip
        |--------------------------------------------------------------------------
        */

        document
            .getElementById('tripForm')
            .addEventListener(
                'submit',
                async function(event) {

                    event.preventDefault();

                    const form = this;

                    const button =
                        document.getElementById(
                            'startBtn'
                        );

                    const error =
                        document.getElementById(
                            'errorMessage'
                        );

                    error.style.display = 'none';

                    if (!form.checkValidity()) {

                        form.reportValidity();

                        return;

                    }

                    button.disabled = true;

                    button.textContent =
                        'Starting Trip...';


                    try {

                        const formData =
                            new FormData(form);


                        /*
                         * Compress odometer photo before
                         * sending it to the server.
                         */

                        const photoInput =
                            document.getElementById(
                                'start_photo'
                            );


                        if (
                            photoInput.files &&
                            photoInput.files[0]
                        ) {

                            const compressedFile =
                                await compressImage(
                                    photoInput.files[0]
                                );


                            formData.delete(
                                'start_photo'
                            );


                            formData.append(
                                'start_photo',
                                compressedFile,
                                'odometer.jpg'
                            );

                        }


                        const response =
                            await fetch(
                                'api/start_trip.php', {
                                    method: 'POST',
                                    body: formData
                                }
                            );


                        const data =
                            await response.json();


                        if (!data.success) {

                            throw new Error(
                                data.message ||
                                'Unable to start trip.'
                            );

                        }


                        window.location.href =
                            data.redirect ||
                            'trip_stop.php';

                    } catch (errorObject) {

                        console.error(
                            'Start trip error:',
                            errorObject
                        );


                        error.textContent =
                            errorObject.message ||
                            'Unable to start trip.';

                        error.style.display =
                            'block';


                        button.disabled =
                            false;

                        button.textContent =
                            'Start Trip';

                    }

                }
            );


        /*
        |--------------------------------------------------------------------------
        | Compress image in browser
        |--------------------------------------------------------------------------
        */

        function compressImage(file) {

            return new Promise(
                function(resolve, reject) {

                    const reader =
                        new FileReader();


                    reader.onload =
                        function(event) {

                            const image =
                                new Image();


                            image.onload =
                                function() {

                                    const maxSize =
                                        1280;


                                    let width =
                                        image.width;

                                    let height =
                                        image.height;


                                    if (
                                        width >
                                        maxSize ||
                                        height >
                                        maxSize
                                    ) {

                                        const ratio =
                                            Math.min(
                                                maxSize / width,
                                                maxSize / height
                                            );

                                        width =
                                            Math.round(
                                                width * ratio
                                            );

                                        height =
                                            Math.round(
                                                height * ratio
                                            );

                                    }


                                    const canvas =
                                        document.createElement(
                                            'canvas'
                                        );


                                    canvas.width =
                                        width;

                                    canvas.height =
                                        height;


                                    const context =
                                        canvas.getContext(
                                            '2d'
                                        );


                                    context.drawImage(
                                        image,
                                        0,
                                        0,
                                        width,
                                        height
                                    );


                                    canvas.toBlob(
                                        function(blob) {

                                            if (!blob) {

                                                reject(
                                                    new Error(
                                                        'Image compression failed.'
                                                    )
                                                );

                                                return;

                                            }


                                            resolve(
                                                new File(
                                                    [blob],
                                                    'odometer.jpg', {
                                                        type: 'image/jpeg'
                                                    }
                                                )
                                            );

                                        },
                                        'image/jpeg',
                                        0.70
                                    );

                                };


                            image.onerror =
                                function() {

                                    reject(
                                        new Error(
                                            'Unable to read image.'
                                        )
                                    );

                                };


                            image.src =
                                event.target.result;

                        };


                    reader.onerror =
                        function() {

                            reject(
                                new Error(
                                    'Unable to read image.'
                                )
                            );

                        };


                    reader.readAsDataURL(file);

                }
            );

        }
    </script>

</body>

</html>