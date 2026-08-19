<?php

session_start();

if (
    !isset($_SESSION['admin_logged_in'])
) {
    http_response_code(403);
    exit('Access denied');
}

if (
    $_SESSION['role'] != 'hr'
    &&
    $_SESSION['role'] != 'admin'
) {
    http_response_code(403);
    exit('Insufficient privileges');
}


require_once '../includes/config.php';

$pdo = getDB();


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$from =
    $_GET['from']
    ??
    date('Y-m-01');

$to =
    $_GET['to']
    ??
    date('Y-m-d');

$search =
    trim(
        $_GET['search']
        ??
        ''
    );


/*
|--------------------------------------------------------------------------
| Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT

        v.vehicle_name,
        v.vehicle_number,

        d.full_name AS driver_name,

        r.name AS requestor_name,

        t.purpose,

        t.from_location,
        t.to_location,

        t.start_km,
        t.end_km,
        t.distance,

        t.trip_start,
        t.trip_end,

        t.status,

        t.remarks

    FROM driver_trips t

    INNER JOIN vehicles v
        ON v.id = t.vehicle_id

    INNER JOIN drivers d
        ON d.id = t.driver_id

    LEFT JOIN requestors r
        ON r.id = t.requestor_id

    WHERE DATE(t.trip_start)
        BETWEEN :from AND :to
";


$params = [

    ':from' => $from,

    ':to' => $to

];


if ($search !== '') {

    $sql .= "
        AND (
            v.vehicle_name LIKE :search

            OR v.vehicle_number LIKE :search

            OR d.full_name LIKE :search

            OR r.name LIKE :search

            OR t.purpose LIKE :search

            OR t.from_location LIKE :search

            OR t.to_location LIKE :search

            OR t.status LIKE :search

            OR t.remarks LIKE :search
        )
    ";

    $params[':search'] =
        '%' . $search . '%';

}


$sql .= "
    ORDER BY
        t.trip_start DESC
";


$stmt =
    $pdo->prepare(
        $sql
    );


$stmt->execute(
    $params
);


$rows =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| CSV download
|--------------------------------------------------------------------------
*/

header(
    'Content-Type: text/csv'
);

header(
    'Content-Disposition: attachment; filename="driver_trips_report.csv"'
);


$output =
    fopen(
        'php://output',
        'w'
    );


/*
|--------------------------------------------------------------------------
| Column headers
|--------------------------------------------------------------------------
*/

fputcsv(
    $output,
    [

        'Vehicle',

        'Vehicle Number',

        'Driver',

        'Requestor',

        'Purpose',

        'From',

        'To',

        'Start KM',

        'End KM',

        'Distance',

        'Start Time',

        'End Time',

        'Status',

        'Remarks'

    ]
);


/*
|--------------------------------------------------------------------------
| Data
|--------------------------------------------------------------------------
*/

foreach ($rows as $row) {

    fputcsv(
        $output,
        [

            $row['vehicle_name'],

            $row['vehicle_number'],

            $row['driver_name'],

            $row['requestor_name']
                ?: '-',

            $row['purpose'],

            $row['from_location'],

            $row['to_location'],

            $row['start_km']
                !== null
                    ? number_format(
                        (float)
                        $row['start_km'],
                        2
                    )
                    : '-',

            $row['end_km']
                !== null
                    ? number_format(
                        (float)
                        $row['end_km'],
                        2
                    )
                    : '-',

            $row['distance']
                !== null
                    ? number_format(
                        (float)
                        $row['distance'],
                        2
                    )
                    : '-',

            $row['trip_start']
                ? date(
                    'h:i A',
                    strtotime(
                        $row['trip_start']
                    )
                )
                : '-',

            $row['trip_end']
                ? date(
                    'h:i A',
                    strtotime(
                        $row['trip_end']
                    )
                )
                : '-',

            match (
                $row['status']
            ) {

                'started'
                    => 'In Trip',

                'completed'
                    => 'Completed',

                'cancelled'
                    => 'Cancelled',

                default
                    => $row['status']

            },

            $row['remarks']
                ?: '-'

        ]
    );

}


fclose(
    $output
);


exit;