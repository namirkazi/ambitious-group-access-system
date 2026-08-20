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


$driverId =
    (int) $_SESSION['driver_id'];


$pdo = getDB();


/*
|--------------------------------------------------------------------------
| Get active trip
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        t.id,
        t.start_km,
        t.purpose,
        t.from_location,
        t.to_location,
        t.trip_start,

        v.vehicle_name,
        v.vehicle_number,

        d.full_name

    FROM driver_trips t

    INNER JOIN drivers d
        ON d.id = t.driver_id

    INNER JOIN vehicles v
        ON v.id = t.vehicle_id

    WHERE t.driver_id = ?
      AND t.status = 'started'

    ORDER BY t.trip_start DESC

    LIMIT 1
");


$stmt->execute([
    $driverId
]);


$trip =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );


if (!$trip) {

    header(
        'Location: trip_start.php'
    );

    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Active Trip — Ambitious Group</title>
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

            max-width: 650px;

            margin: auto;

            display: flex;

            justify-content: space-between;

            align-items: center;

        }

        .logo {

            font-weight: 800;

        }

        .driver-name {

            color: var(--muted);

            font-size: .9rem;

        }

        main {

            width: 100%;

            max-width: 650px;

            margin: auto;

            padding: 1.25rem;

        }

        .status {

            display: inline-block;

            padding: .4rem .7rem;

            margin-bottom: .75rem;

            border-radius: 20px;

            background:
                rgba(34, 197, 94, .12);

            color: var(--success);

            font-size: .8rem;

            font-weight: 700;

        }

        h1 {

            margin:
                .25rem 0 .35rem;

            font-size: 1.6rem;

        }

        .started {

            color: var(--muted);

            margin-bottom: 1.25rem;

        }

        .card {

            background: var(--surface);

            border:
                1px solid var(--border);

            border-radius: 12px;

            padding: 1.1rem;

            margin-bottom: 1rem;

        }

        .row {

            display: flex;

            justify-content: space-between;

            gap: 1rem;

            padding: .75rem 0;

            border-bottom:
                1px solid var(--border);

        }

        .row:last-child {

            border-bottom: none;

        }

        .label {

            color: var(--muted);

            font-size: .85rem;

        }

        .value {

            text-align: right;

            font-weight: 600;

        }

        .elapsed {

            font-size: 1.7rem;

            font-weight: 800;

            color: var(--accent);

            text-align: center;

            padding: .75rem 0;

        }

        .end-section {

            background: var(--surface);

            border:
                1px solid var(--border);

            border-radius: 12px;

            padding: 1.1rem;

        }

        .end-section h2 {

            margin:
                0 0 1rem;

            font-size: 1.1rem;

        }

        label {

            display: block;

            margin-bottom: .45rem;

            color: var(--muted);

            font-size: .85rem;

        }

        input,
        textarea {

            width: 100%;

            padding: .85rem;

            border:
                1px solid var(--border);

            border-radius: 8px;

            background: var(--surface2);

            color: var(--text);

            font-family: inherit;

            font-size: 1rem;

            margin-bottom: 1rem;

            resize: vertical;

        }

        textarea {
            min-height: 100px;
        }

        input:focus,
        textarea:focus {

            outline: none;

            border-color: var(--accent);

        }

        input:focus {

            outline: none;

            border-color: var(--accent);

        }

        .end-btn {

            width: 100%;

            padding: .95rem;

            border: none;

            border-radius: 8px;

            background: var(--danger);

            color: #fff;

            font-size: 1rem;

            font-weight: 700;

            cursor: pointer;

        }

        .end-btn:disabled {

            opacity: .5;

            cursor: not-allowed;

        }

        .error {

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
    </style>

</head>

<body>


    <header>

        <div class="header-inner">

            <div class="logo">
                Ambitious Group
            </div>

            <div class="driver-name">
                <?= htmlspecialchars($trip['full_name']) ?>
            </div>

        </div>

    </header>


    <main>

        <div class="status">
            TRIP IN PROGRESS
        </div>


        <h1>
            Active Trip
        </h1>


        <div class="started">

            Started:
            <?= date(
                'd M Y, h:i A',
                strtotime($trip['trip_start'])
            ) ?>

        </div>


        <div class="card">

            <div class="elapsed">
                <span id="elapsed">
                    00:00:00
                </span>
            </div>


            <div class="row">

                <div class="label">
                    Driver
                </div>

                <div class="value">
                    <?= htmlspecialchars($trip['full_name']) ?>
                </div>

            </div>


            <div class="row">

                <div class="label">
                    Vehicle
                </div>

                <div class="value">

                    <?= htmlspecialchars($trip['vehicle_name']) ?>

                    —

                    <?= htmlspecialchars($trip['vehicle_number']) ?>

                </div>

            </div>


            <div class="row">

                <div class="label">
                    Starting KM
                </div>

                <div class="value">
                    <?= htmlspecialchars($trip['start_km']) ?> KM
                </div>

            </div>


            <div class="row">

                <div class="label">
                    Reason
                </div>

                <div class="value">
                    <?= htmlspecialchars($trip['purpose']) ?>
                </div>

            </div>


            <div class="row">

                <div class="label">
                    From
                </div>

                <div class="value">
                    <?= htmlspecialchars($trip['from_location']) ?>
                </div>

            </div>


            <div class="row">

                <div class="label">
                    To
                </div>

                <div class="value">
                    <?= htmlspecialchars($trip['to_location']) ?>
                </div>

            </div>

        </div>


        <div class="end-section">

            <h2>
                End Trip
            </h2>


            <div
                id="errorMessage"
                class="error"></div>


            <form
                id="endTripForm"
                enctype="multipart/form-data">

                <label for="end_km">
                    Current KM
                </label>

                <input
                    type="number"
                    id="end_km"
                    name="end_km"
                    min="0"
                    step="0.01"
                    inputmode="decimal"
                    placeholder="Enter current KM"
                    required>


                <label for="end_photo">
                    Current KM Photo
                </label>

                <input
                    type="file"
                    id="end_photo"
                    name="end_photo"
                    accept="image/*"
                    capture="environment"
                    required>

                <label for="remarks">
                    Remarks
                </label>

                <textarea id="remarks" name="remarks" rows="4" maxlength="1000" placeholder="Add any remarks about the trip or what happened with the client..."></textarea>

                <button
                    type="submit"
                    id="endBtn"
                    class="end-btn">
                    End Trip
                </button>

            </form>

        </div>

    </main>


    <script>
        /*
|--------------------------------------------------------------------------
| Trip elapsed timer
|--------------------------------------------------------------------------
*/

        const tripStart =
            new Date(
                '<?= date(
                        'c',
                        strtotime($trip['trip_start'])
                    ) ?>'
            );


        function updateElapsed() {

            const now =
                new Date();

            const seconds =
                Math.max(
                    0,
                    Math.floor(
                        (now - tripStart) /
                        1000
                    )
                );


            const hours =
                Math.floor(
                    seconds / 3600
                );


            const minutes =
                Math.floor(
                    (seconds % 3600) / 60
                );


            const secs =
                seconds % 60;


            document.getElementById(
                    'elapsed'
                ).textContent =

                String(hours).padStart(2, '0') +
                ':' +
                String(minutes).padStart(2, '0') +
                ':' +
                String(secs).padStart(2, '0');

        }


        setInterval(
            updateElapsed,
            1000
        );

        updateElapsed();


        /*
        |--------------------------------------------------------------------------
        | End photo compression
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


        /*
        |--------------------------------------------------------------------------
        | End trip
        |--------------------------------------------------------------------------
        */

        document
            .getElementById('endTripForm')
            .addEventListener(
                'submit',
                async function(event) {

                    event.preventDefault();


                    const form =
                        this;

                    const button =
                        document.getElementById(
                            'endBtn'
                        );

                    const error =
                        document.getElementById(
                            'errorMessage'
                        );


                    error.style.display =
                        'none';


                    if (!form.checkValidity()) {

                        form.reportValidity();

                        return;

                    }


                    button.disabled =
                        true;

                    button.textContent =
                        'Ending Trip...';


                    try {

                        const formData =
                            new FormData(form);


                        const photoInput =
                            document.getElementById(
                                'end_photo'
                            );


                        if (
                            photoInput.files &&
                            photoInput.files[0]
                        ) {

                            const compressed =
                                await compressImage(
                                    photoInput.files[0]
                                );


                            formData.delete(
                                'end_photo'
                            );


                            formData.append(
                                'end_photo',
                                compressed,
                                'odometer.jpg'
                            );

                        }


                        const response =
                            await fetch(
                                'api/end_trip.php', {
                                    method: 'POST',
                                    body: formData
                                }
                            );


                        const data =
                            await response.json();


                        if (!data.success) {

                            throw new Error(
                                data.message ||
                                'Unable to end trip.'
                            );

                        }


                        window.location.href =
                            data.redirect ||
                            'driver.php';

                    } catch (errorObject) {

                        console.error(
                            'End trip error:',
                            errorObject
                        );


                        error.textContent =
                            errorObject.message ||
                            'Unable to end trip.';

                        error.style.display =
                            'block';


                        button.disabled =
                            false;

                        button.textContent =
                            'End Trip';

                    }

                }
            );
    </script>

</body>

</html>