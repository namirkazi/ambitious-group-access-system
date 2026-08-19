<?php

session_start();

/*
|--------------------------------------------------------------------------
| Admin / HR authentication
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

if (
    $_SESSION['role'] != 'hr' &&
    $_SESSION['role'] != 'admin'
) {
    header("Location: admin.php");
    exit();
}

require_once 'includes/config.php';

$pdo = getDB();


/*
|--------------------------------------------------------------------------
| Vehicle statistics
|--------------------------------------------------------------------------
*/

$totalCars = $pdo->query("
    SELECT COUNT(*)
    FROM vehicles
")->fetchColumn();


$activeCars = $pdo->query("
    SELECT COUNT(*)
    FROM vehicles
    WHERE active = 1
")->fetchColumn();


$inUseCars = $pdo->query("
    SELECT COUNT(*)
    FROM vehicles
    WHERE active = 1
      AND in_use = 1
")->fetchColumn();


$availableCarRows = $pdo->query("
    SELECT
        vehicle_name,
        vehicle_number
    FROM vehicles
    WHERE active = 1
      AND in_use = 0
    ORDER BY vehicle_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$availableCars =
    count($availableCarRows);


/*
|--------------------------------------------------------------------------
| Date filters
|--------------------------------------------------------------------------
*/

$from =
    $_GET['from']
    ??
    date('Y-m-d');


$to =
    $_GET['to']
    ??
    date('Y-m-d');


/*
|--------------------------------------------------------------------------
| Trip records
|--------------------------------------------------------------------------
|
| We use trip_start for the selected date.
|
*/

$stmt = $pdo->prepare("
    SELECT

        t.id,

        t.driver_id,
        t.vehicle_id,
        t.requestor_id,

        t.start_km,
        t.end_km,
        t.distance,

        t.purpose,

        t.from_location,
        t.to_location,

        t.start_photo,
        t.end_photo,

        t.trip_start,
        t.trip_end,
        t.status,
        t.remarks,

        d.full_name AS driver_name,

        v.vehicle_name,
        v.vehicle_number,

        r.name AS requestor_name

    FROM driver_trips t

    INNER JOIN drivers d
        ON d.id = t.driver_id

    INNER JOIN vehicles v
        ON v.id = t.vehicle_id

    LEFT JOIN requestors r
        ON r.id = t.requestor_id

    WHERE DATE(t.trip_start)
        BETWEEN ? AND ?

    ORDER BY
        t.trip_start DESC
");


$stmt->execute([
    $from,
    $to
]);


$trips =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| Convert trip data for JavaScript
|--------------------------------------------------------------------------
*/

$tripJson =
    json_encode(
        $trips,
        JSON_HEX_TAG |
            JSON_HEX_APOS |
            JSON_HEX_QUOT |
            JSON_HEX_AMP
    );

?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Driver Trips — Ambitious Group
    </title>


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

            --warn: #f59e0b;

            --danger: #ef4444;

            --text: #e2e8f0;

            --muted: #94a3b8;

            --border: #2d3148;

            --font:
                'Segoe UI',
                system-ui,
                sans-serif;

        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }


        * {

            box-sizing: border-box;

            margin: 0;

            padding: 0;

        }


        body {

            background: var(--bg);

            color: var(--text);

            font-family: var(--font);

            min-height: 100vh;

        }


        header {

            background: var(--surface);

            border-bottom:
                1px solid var(--border);

            padding:
                1rem 2rem;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 1rem;

        }


        header .logo {

            display: flex;

            align-items: center;

            gap: .75rem;

        }


        header h1 {

            font-size: 1.2rem;

            font-weight: 700;

            letter-spacing: -.02em;

        }


        header h1 span {

            color: var(--accent2);

        }


        header .nav-links {

            display: flex;

            gap: .75rem;

            align-items: center;

            flex-wrap: wrap;

        }


        header a {

            background: var(--accent);

            color: #fff;

            padding:
                .5rem 1.2rem;

            border-radius: 8px;

            text-decoration: none;

            font-size: .875rem;

            font-weight: 600;

        }


        .container {

            max-width: 1300px;

            margin: 0 auto;

            padding: 2rem;

        }


        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        .stats-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 1rem;

            margin-bottom: 2rem;

        }


        .stat-card {

            background: var(--surface);

            border:
                1px solid var(--border);

            border-radius: 12px;

            padding: 1.5rem;

        }


        .stat-card .label {

            font-size: .8rem;

            color: var(--muted);

            text-transform: uppercase;

            letter-spacing: .05em;

            margin-bottom: .5rem;

        }


        .stat-card .value {

            font-size: 2.2rem;

            font-weight: 800;

            letter-spacing: -.04em;

        }


        .stat-card.blue .value {

            color: var(--accent2);

        }


        .stat-card.green .value {

            color: var(--success);

        }


        .stat-card.orange .value {

            color: var(--warn);

        }


        .stat-card.red .value {

            color: var(--danger);

        }


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        .search-bar {

            margin-bottom: 1.5rem;

        }


        .search-container {

            position: relative;

            width: 100%;

        }


        #searchInput {

            width: 100%;

            background: var(--surface);

            border:
                1px solid var(--border);

            color: var(--text);

            padding:
                .75rem 3rem .75rem 1rem;

            border-radius: 10px;

            font-size: .9rem;

        }


        #searchInput:focus {

            outline: none;

            border-color:
                var(--accent);

        }


        #clearSearch {

            position: absolute;

            top: 50%;

            right: 12px;

            transform:
                translateY(-50%);

            width: 28px;

            height: 28px;

            border: none;

            border-radius: 50%;

            background: var(--surface2);

            color: var(--muted);

            cursor: pointer;

            display: none;

        }


        #clearSearch:hover {

            background: var(--accent);

            color: white;

        }


        /*
        |--------------------------------------------------------------------------
        | Toolbar
        |--------------------------------------------------------------------------
        */

        .table-card {

            background: var(--surface);

            border:
                1px solid var(--border);

            border-radius: 12px;

            overflow: hidden;

        }


        .card-header {

            padding:
                1rem 1.5rem;

            border-bottom:
                1px solid var(--border);

            display: flex;

            align-items: flex-end;

            justify-content: space-between;

            gap: 1rem;

            flex-wrap: wrap;

        }


        .card-header h2 {

            font-size: 1.1rem;

            font-weight: 700;

        }


        .date-filter {

            display: flex;

            align-items: flex-end;

            gap: 12px;

            flex-wrap: wrap;

        }


        .date-group {

            display: flex;

            flex-direction: column;

            gap: 6px;

        }


        .date-group label {

            font-size: .75rem;

            color: var(--muted);

            text-transform: uppercase;

            letter-spacing: .05em;

        }


        .date-group input {

            background: var(--surface2);

            color: var(--text);

            border:
                1px solid var(--border);

            border-radius: 10px;

            padding:
                .65rem .85rem;

            font-size: .85rem;

            min-width: 170px;

        }


        .date-group input:focus {

            outline: none;

            border-color:
                var(--accent);

        }


        .action-btn {

            background:
                linear-gradient(135deg,
                    var(--accent),
                    var(--accent2));

            color: white;

            border: none;

            border-radius: 8px;

            padding:
                .55rem .9rem;

            font-size: .75rem;

            font-weight: 600;

            cursor: pointer;

        }


        .action-btn:hover {

            opacity: .9;

        }


        .range-info {

            padding:
                1rem 1.5rem;

            background:
                rgba(108, 99, 255, .08);

            border-bottom:
                1px solid var(--border);

            color: var(--accent2);

            font-weight: 600;

        }


        /*
        |--------------------------------------------------------------------------
        | Table
        |--------------------------------------------------------------------------
        */

        .table-wrapper {

            overflow-x: auto;

        }


        table {

            width: 100%;

            border-collapse: collapse;

        }


        thead th {

            background: var(--surface2);

            padding:
                .75rem 1rem;

            text-align: left;

            font-size: .75rem;

            text-transform: uppercase;

            letter-spacing: .06em;

            color: var(--muted);

            font-weight: 600;

            white-space: nowrap;

        }


        tbody tr {

            border-top:
                1px solid var(--border);

        }


        tbody tr:hover {

            background: var(--surface2);

        }


        tbody td {

            padding:
                .85rem 1rem;

            font-size: .875rem;

            vertical-align: middle;

            white-space: nowrap;

        }


        .vehicle-name {

            font-weight: 600;

        }


        .vehicle-number {

            font-size: .75rem;

            color: var(--muted);

            margin-top: 2px;

        }


        .driver-name {

            font-weight: 600;

        }


        .time {

            font-variant-numeric:
                tabular-nums;

        }


        .status-badge {

            display: inline-flex;

            align-items: center;

            gap: .3rem;

            padding:
                .25rem .7rem;

            border-radius: 999px;

            font-size: .75rem;

            font-weight: 600;

        }


        .status-badge.completed {

            background:
                rgba(34, 197, 94, .15);

            color:
                var(--success);

        }


        .status-badge.started {

            background:
                rgba(245, 158, 11, .15);

            color:
                var(--warn);

        }


        .status-badge.cancelled {

            background:
                rgba(239, 68, 68, .15);

            color:
                var(--danger);

        }


        .view-btn {

            background:
                var(--surface2);

            color:
                var(--accent2);

            border:
                1px solid var(--border);

            border-radius: 7px;

            padding:
                .45rem .75rem;

            font-size: .75rem;

            font-weight: 600;

            cursor: pointer;

        }


        .view-btn:hover {

            background:
                var(--accent);

            color: white;

            border-color:
                var(--accent);

        }


        .empty {

            text-align: center;

            padding: 3rem 1rem;

            color: var(--muted);

        }


        /*
        |--------------------------------------------------------------------------
        | Modal
        |--------------------------------------------------------------------------
        */

        .modal-overlay {

            position: fixed;

            inset: 0;

            background:
                rgba(0, 0, 0, .75);

            display: none;

            align-items: center;

            justify-content: center;

            padding: 1rem;

            z-index: 1000;

        }


        .modal-overlay.show {

            display: flex;

        }


        .modal-box {

            width: 100%;

            max-width: 700px;

            max-height: 90vh;

            overflow-y: auto;

            background: var(--surface);

            border:
                1px solid var(--border);

            border-radius: 12px;

            padding: 1.5rem;

        }


        .modal-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 1rem;

            margin-bottom: 1.25rem;

        }


        .modal-header h3 {

            font-size: 1.2rem;

        }


        .close-btn {

            width: 32px;

            height: 32px;

            border: none;

            border-radius: 50%;

            background: var(--surface2);

            color: var(--muted);

            cursor: pointer;

            font-size: 1rem;

        }


        .close-btn:hover {

            background: var(--danger);

            color: white;

        }


        .details-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: .75rem;

        }


        .detail {

            background: var(--surface2);

            border-radius: 8px;

            padding: .8rem;

        }


        .detail.full {

            grid-column:
                1 / -1;

        }


        .detail-label {

            color: var(--muted);

            font-size: .72rem;

            text-transform: uppercase;

            letter-spacing: .05em;

            margin-bottom: .3rem;

        }


        .detail-value {

            font-size: .9rem;

            font-weight: 600;

            word-break: break-word;

        }


        .photos {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 1rem;

            margin-top: 1rem;

        }


        .photo-card {

            background: var(--surface2);

            border-radius: 8px;

            padding: .75rem;

        }


        .photo-card h4 {

            font-size: .8rem;

            color: var(--muted);

            margin-bottom: .6rem;

        }


        .photo-card img {

            width: 100%;

            height: 200px;

            object-fit: contain;

            background: #000;

            border-radius: 6px;

            display: block;

        }


        .photo-missing {

            height: 200px;

            display: flex;

            align-items: center;

            justify-content: center;

            color: var(--muted);

            background: #11131a;

            border-radius: 6px;

            font-size: .8rem;

        }


        /*
        |--------------------------------------------------------------------------
        | Responsive
        |--------------------------------------------------------------------------
        */

        @media (max-width: 900px) {

            .stats-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

            .card-header {

                align-items: stretch;

            }

            .date-filter {

                width: 100%;

            }

            .date-group {

                flex: 1;

            }

            .date-group input {

                width: 100%;

                min-width: 0;

            }

        }


        @media (max-width: 600px) {

            header {

                padding:
                    1rem;

                flex-direction: column;

                align-items: flex-start;

            }


            header .nav-links {

                width: 100%;

            }


            header a {

                flex: 1;

                text-align: center;

            }


            .container {

                padding: 1rem;

            }


            .stats-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }


            .stat-card {

                padding: 1rem;

            }


            .stat-card .value {

                font-size: 1.7rem;

            }


            .details-grid {

                grid-template-columns: 1fr;

            }


            .detail.full {

                grid-column: auto;

            }


            .photos {

                grid-template-columns: 1fr;

            }


            .modal-box {

                padding: 1rem;

            }

        }
    </style>

</head>


<body>


    <header>

        <div class="logo">

            <svg
                width="24"
                height="24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                viewBox="0 0 24 24">

                <path
                    d="M5 17h14l1.5-5.5A2 2 0 0 0 18.6 10H5.4a2 2 0 0 0-1.9 1.5L2 17" />

                <circle
                    cx="6.5"
                    cy="18"
                    r="1.5" />

                <circle
                    cx="17.5"
                    cy="18"
                    r="1.5" />

            </svg>


            <h1>
                Ambitious<span>Group</span>
                — Driver Trips
            </h1>

        </div>


        <div class="nav-links">

            <?php if ($_SESSION['role'] == 'hr'): ?>

                <div class="header-actions">
                    <a class="checkout-btn" href="driver_register.php">
                        Add Driver
                    </a>
                </div>
            <?php endif; ?>


            <?php if ($_SESSION['role'] == 'admin'): ?>

                <a href="admin.php">
                    Visitors History
                </a>

            <?php endif; ?>


            <a href="logout.php">
                ⏻ Logout
            </a>

        </div>

    </header>


    <div class="container">


        <!-- VEHICLE STATISTICS -->

        <div class="stats-grid">


            <div class="stat-card blue">

                <div class="label">
                    Total Cars
                </div>

                <div class="value">
                    <?= $totalCars ?>
                </div>

            </div>


            <div class="stat-card green">

                <div class="label">
                    Active Cars
                </div>

                <div class="value">
                    <?= $activeCars ?>
                </div>

            </div>


            <div class="stat-card orange">

                <div class="label">
                    In Use
                </div>

                <div class="value">
                    <?= $inUseCars ?>
                </div>

            </div>


            <div class="stat-card red">

                <div class="label">
                    Available Cars
                </div>

                <div class="value">
                    <?= $availableCars ?>
                </div>

                <?php if (!empty($availableCarRows)): ?>

                    <div
                        style="
                margin-top:.6rem;
                color:var(--muted);
                font-size:.75rem;
                line-height:1.5;
            ">

                        <?php foreach ($availableCarRows as $car): ?>

                            <div>
                                <?= htmlspecialchars(
                                    $car['vehicle_name']
                                ) ?>

                                <span style="opacity:.7">
                                    —
                                    <?= htmlspecialchars(
                                        $car['vehicle_number']
                                    ) ?>
                                </span>
                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div
                        style="
                margin-top:.6rem;
                color:var(--muted);
                font-size:.75rem;
            ">
                        No cars available
                    </div>

                <?php endif; ?>

            </div>

        </div>


        <!-- SEARCH -->

        <div class="search-bar">

            <div class="search-container">

                <input
                    type="text"
                    id="searchInput"
                    placeholder="Search vehicle, driver, requestor, destination...">

                <button
                    id="clearSearch"
                    type="button">
                    ✕
                </button>

            </div>

        </div>


        <!-- TABLE -->

        <div class="table-card">


            <div class="card-header">


                <h2>
                    Trip History
                </h2>


                <div class="date-filter">


                    <div class="date-group">

                        <label>
                            From Date
                        </label>

                        <input
                            type="date"
                            id="fromDate"
                            value="<?= htmlspecialchars($from) ?>">

                    </div>


                    <div class="date-group">

                        <label>
                            To Date
                        </label>

                        <input
                            type="date"
                            id="toDate"
                            value="<?= htmlspecialchars($to) ?>">

                    </div>


                    <button
                        class="action-btn"
                        type="button"
                        onclick="applyDateFilter()">
                        Apply
                    </button>


                    <button
                        class="action-btn"
                        type="button"
                        onclick="resetFilter()">
                        Reset
                    </button>
                    <button
                        class="action-btn"
                        type="button"
                        onclick="exportDriverTripsPDF()">
                        PDF
                    </button>

                    <button
                        class="action-btn"
                        type="button"
                        onclick="exportDriverTripsExcel()">
                        Excel
                    </button>


                </div>


            </div>


            <div class="range-info">

                Showing trips from

                <?= date(
                    'd M Y',
                    strtotime($from)
                ) ?>

                to

                <?= date(
                    'd M Y',
                    strtotime($to)
                ) ?>

            </div>


            <div class="table-wrapper">


                <table>

                    <thead>

                        <tr>

                            <th>
                                Vehicle
                            </th>

                            <th>
                                Driver
                            </th>

                            <th>
                                Start Time
                            </th>

                            <th>
                                End Time
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody id="tripTableBody">


                        <?php if (empty($trips)): ?>

                            <tr>

                                <td
                                    colspan="6"
                                    class="empty">
                                    No trips found for the
                                    selected dates.
                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach ($trips as $index => $trip): ?>


                                <tr>


                                    <!-- VEHICLE -->

                                    <td>

                                        <div class="vehicle-name">

                                            <?= htmlspecialchars(
                                                $trip['vehicle_name']
                                            ) ?>

                                        </div>


                                        <div class="vehicle-number">

                                            <?= htmlspecialchars(
                                                $trip['vehicle_number']
                                            ) ?>

                                        </div>

                                    </td>


                                    <!-- DRIVER -->

                                    <td>

                                        <div class="driver-name">

                                            <?= htmlspecialchars(
                                                $trip['driver_name']
                                            ) ?>

                                        </div>

                                    </td>


                                    <!-- START -->

                                    <td class="time">

                                        <?= $trip['trip_start']
                                            ? date(
                                                'h:i A',
                                                strtotime(
                                                    $trip['trip_start']
                                                )
                                            )
                                            : '-'
                                        ?>

                                    </td>


                                    <!-- END -->

                                    <td class="time">

                                        <?= $trip['trip_end']
                                            ? date(
                                                'h:i A',
                                                strtotime(
                                                    $trip['trip_end']
                                                )
                                            )
                                            : '-'
                                        ?>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <?php

                                        $statusClass =
                                            match ($trip['status']) {

                                                'completed'
                                                => 'completed',

                                                'started'
                                                => 'started',

                                                'cancelled'
                                                => 'cancelled',

                                                default
                                                => 'started'
                                            };

                                        ?>


                                        <span
                                            class="status-badge <?= $statusClass ?>">

                                            <?php

                                            echo match ($trip['status']) {

                                                'completed'
                                                => 'Completed',

                                                'started'
                                                => 'In Trip',

                                                'cancelled'
                                                => 'Cancelled',

                                                default
                                                => ucfirst(
                                                    $trip['status']
                                                )
                                            };

                                            ?>

                                        </span>

                                    </td>


                                    <!-- ACTION -->

                                    <td>

                                        <button
                                            type="button"
                                            class="view-btn"
                                            onclick="viewTrip(<?= $index ?>)">
                                            View Trip Info
                                        </button>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php endif; ?>


                    </tbody>

                </table>


            </div>

        </div>

    </div>


    <!--
|--------------------------------------------------------------------------
| TRIP DETAILS MODAL
|--------------------------------------------------------------------------
-->

    <div
        class="modal-overlay"
        id="tripModal"
        onclick="closeModal(event)">


        <div
            class="modal-box"
            onclick="event.stopPropagation()">


            <div class="modal-header">

                <h3>
                    Trip Information
                </h3>


                <button
                    type="button"
                    class="close-btn"
                    onclick="closeTripModal()">
                    ✕
                </button>

            </div>


            <div
                class="details-grid"
                id="tripDetails"></div>


            <div
                class="photos"
                id="tripPhotos"></div>


        </div>

    </div>


    <script>
        /*
|--------------------------------------------------------------------------
| Trip data
|--------------------------------------------------------------------------
*/

        const trips =
            <?= $tripJson ?: '[]' ?>;


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        const searchInput =
            document.getElementById(
                'searchInput'
            );


        const clearBtn =
            document.getElementById(
                'clearSearch'
            );


        searchInput.addEventListener(
            'input',
            function() {

                const value =
                    this.value
                    .toLowerCase()
                    .trim();


                clearBtn.style.display =
                    value ?
                    'block' :
                    'none';


                document
                    .querySelectorAll(
                        '#tripTableBody tr'
                    )
                    .forEach(
                        row => {

                            row.style.display =
                                row.innerText
                                .toLowerCase()
                                .includes(value) ?
                                '' :
                                'none';

                        }
                    );

            }
        );


        clearBtn.onclick =
            function() {

                searchInput.value = '';

                searchInput.dispatchEvent(
                    new Event('input')
                );

            };


        /*
        |--------------------------------------------------------------------------
        | Date filtering
        |--------------------------------------------------------------------------
        */

        function applyDateFilter() {

            const from =
                document.getElementById(
                    'fromDate'
                ).value;


            const to =
                document.getElementById(
                    'toDate'
                ).value;


            if (!from || !to) {

                return;

            }


            window.location =
                `driver_trips.php?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`;

        }


        function resetFilter() {

            window.location =
                'driver_trips.php';

        }

        function exportDriverTripsExcel() {

            const from =
                document.getElementById(
                    'fromDate'
                ).value;

            const to =
                document.getElementById(
                    'toDate'
                ).value;

            const search =
                document.getElementById(
                    'searchInput'
                ).value;


            window.open(
                `api/export_driver_trips_excel.php?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}&search=${encodeURIComponent(search)}`
            );

        }


        function exportDriverTripsPDF() {

            const from =
                document.getElementById(
                    'fromDate'
                ).value;

            const to =
                document.getElementById(
                    'toDate'
                ).value;

            const search =
                document.getElementById(
                    'searchInput'
                ).value;


            window.open(
                `api/export_driver_trips_pdf.php?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}&search=${encodeURIComponent(search)}`
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Trip modal
        |--------------------------------------------------------------------------
        */

        function viewTrip(index) {

            const trip =
                trips[index];


            if (!trip) {

                return;

            }


            const details =
                document.getElementById(
                    'tripDetails'
                );


            const photos =
                document.getElementById(
                    'tripPhotos'
                );


            /*
            |--------------------------------------------------------------------------
            | Details
            |--------------------------------------------------------------------------
            */

            details.innerHTML = `

        <div class="detail">

            <div class="detail-label">
                Vehicle
            </div>

            <div class="detail-value">
                ${escapeHtml(trip.vehicle_name)}
                —
                ${escapeHtml(trip.vehicle_number)}
            </div>

        </div>


        <div class="detail">

            <div class="detail-label">
                Driver
            </div>

            <div class="detail-value">
                ${escapeHtml(trip.driver_name)}
            </div>

        </div>


        <div class="detail">

            <div class="detail-label">
                Requestor
            </div>

            <div class="detail-value">
                ${escapeHtml(
                    trip.requestor_name || '-'
                )}
            </div>

        </div>


        <div class="detail">

            <div class="detail-label">
                Reason
            </div>

            <div class="detail-value">
                ${escapeHtml(
                    trip.purpose || '-'
                )}
            </div>

        </div>


        <div class="detail">

            <div class="detail-label">
                From
            </div>

            <div class="detail-value">
                ${escapeHtml(
                    trip.from_location || '-'
                )}
            </div>

        </div>


        <div class="detail">

            <div class="detail-label">
                To
            </div>

            <div class="detail-value">
                ${escapeHtml(
                    trip.to_location || '-'
                )}
            </div>

        </div>


        <div class="detail">

            <div class="detail-label">
                Starting KM
            </div>

            <div class="detail-value">
                ${formatKm(trip.start_km)}
            </div>

        </div>


        <div class="detail">

            <div class="detail-label">
                Ending KM
            </div>

            <div class="detail-value">
                ${formatKm(trip.end_km)}
            </div>

        </div>


        <div class="detail">

            <div class="detail-label">
                Distance
            </div>

            <div class="detail-value">
                ${formatKm(trip.distance)}
            </div>

        </div>


        <div class="detail">

            <div class="detail-label">
                Status
            </div>

            <div class="detail-value">
                ${formatStatus(trip.status)}
            </div>

        </div>


        <div class="detail">

            <div class="detail-label">
                Trip Started
            </div>

            <div class="detail-value">
                ${formatDateTime(trip.trip_start)}
            </div>

        </div>


        <div class="detail">

            <div class="detail-label">
                Trip Ended
            </div>

            <div class="detail-value">
                ${formatDateTime(trip.trip_end)}
            </div>

        </div>
        <div class="detail full">

    <div class="detail-label">
        Remarks
    </div>

    <div class="detail-value">
        ${escapeHtml(
            trip.remarks || '-'
        )}
    </div>

</div>

    `;


            /*
            |--------------------------------------------------------------------------
            | Photos
            |--------------------------------------------------------------------------
            */

            photos.innerHTML = `

        <div class="photo-card">

            <h4>
                Starting KM Photo
            </h4>

            ${
                trip.start_photo

                    ? `
                        <img
                            src="${getPhotoUrl(trip.start_photo)}"
                            alt="Starting KM"
                        >
                    `

                    : `
                        <div class="photo-missing">
                            No starting photo
                        </div>
                    `
            }

        </div>


        <div class="photo-card">

            <h4>
                Ending KM Photo
            </h4>

            ${
                trip.end_photo

                    ? `
                        <img
                            src="${getPhotoUrl(trip.end_photo)}"
                            alt="Ending KM"
                        >
                    `

                    : `
                        <div class="photo-missing">
                            No ending photo
                        </div>
                    `
            }

        </div>

    `;


            document
                .getElementById(
                    'tripModal'
                )
                .classList
                .add('show');

        }


        function closeTripModal() {

            document
                .getElementById(
                    'tripModal'
                )
                .classList
                .remove('show');

        }


        function closeModal(event) {

            if (
                event.target.id ===
                'tripModal'
            ) {

                closeTripModal();

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Helpers
        |--------------------------------------------------------------------------
        */

        function escapeHtml(value) {

            if (
                value === null ||
                value === undefined
            ) {

                return '';

            }


            return String(value)
                .replace(
                    /&/g,
                    '&amp;'
                )
                .replace(
                    /</g,
                    '&lt;'
                )
                .replace(
                    />/g,
                    '&gt;'
                )
                .replace(
                    /"/g,
                    '&quot;'
                )
                .replace(
                    /'/g,
                    '&#039;'
                );

        }


        function formatKm(value) {

            if (
                value === null ||
                value === undefined ||
                value === ''
            ) {

                return '-';

            }


            return (
                Number(value)
                .toLocaleString(
                    undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }
                ) +
                ' KM'
            );

        }


        function formatDateTime(value) {

            if (!value) {

                return '-';

            }


            const date =
                new Date(
                    value.replace(
                        ' ',
                        'T'
                    )
                );


            if (
                Number.isNaN(
                    date.getTime()
                )
            ) {

                return escapeHtml(value);

            }


            return date.toLocaleString(
                undefined, {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }
            );

        }


        function formatStatus(status) {

            switch (status) {

                case 'completed':
                    return 'Completed';

                case 'started':
                    return 'In Trip';

                case 'cancelled':
                    return 'Cancelled';

                default:
                    return escapeHtml(
                        status || '-'
                    );

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Photo URL
        |--------------------------------------------------------------------------
        |
        | start_photo/end_photo contain paths such as:
        |
        | uploads/driver_trips/trip_start_...
        |
        | UPLOAD_URL is already used by attendance.php
        | for uploaded files.
        |
        */

        function getPhotoUrl(path) {

            if (!path) {

                return '';

            }

            return <?= json_encode(
                        rtrim(BASE_URL, '/') . '/' .
                            trim(UPLOAD_URL, '/') . '/'
                    ) ?> + path;

        }


        /*
        |--------------------------------------------------------------------------
        | Escape key closes modal
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            'keydown',
            function(event) {

                if (
                    event.key === 'Escape'
                ) {

                    closeTripModal();

                }

            }
        );
    </script>


</body>

</html>