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

require '../vendor/autoload.php';

use Dompdf\Dompdf;


$pdo =
    getDB();


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
| Summary
|--------------------------------------------------------------------------
*/

$total =
    count($rows);


$completed = 0;

$inTrip = 0;

$cancelled = 0;


foreach ($rows as $row) {

    if (
        $row['status']
        ===
        'completed'
    ) {

        $completed++;

    }
    elseif (
        $row['status']
        ===
        'started'
    ) {

        $inTrip++;

    }
    elseif (
        $row['status']
        ===
        'cancelled'
    ) {

        $cancelled++;

    }

}


/*
|--------------------------------------------------------------------------
| PDF HTML
|--------------------------------------------------------------------------
*/

$html = '

<style>

body {

    font-family:
        DejaVu Sans,
        Arial,
        sans-serif;

    font-size: 10px;

    color: #222;

}


.header {

    text-align: center;

}


.title {

    font-size: 24px;

    font-weight: bold;

    color: #1f2937;

}


.subtitle {

    font-size: 16px;

    color: #666;

    margin-bottom: 15px;

}


table {

    width: 100%;

    border-collapse: collapse;

}


th {

    background: #1f2937;

    color: white;

    padding: 8px;

    border: 1px solid #1f2937;

    text-align: center;

}


td {

    padding: 7px;

    border: 1px solid #ddd;

    text-align: center;

}


.summary {

    margin-top: 20px;

    margin-bottom: 20px;

}


.footer {

    margin-top: 40px;

    text-align: center;

    color: #777;

    font-size: 10px;

}

</style>


<div class="header">

    <div class="title">
        AMBITIOUS GROUP
    </div>

    <div class="subtitle">
        Driver Trip Report
    </div>

</div>


<hr>


<b>From:</b>
' . htmlspecialchars(
    date(
        'd M Y',
        strtotime($from)
    )
) . '


&nbsp;&nbsp;&nbsp;


<b>To:</b>
' . htmlspecialchars(
    date(
        'd M Y',
        strtotime($to)
    )
) . '


<br><br>


<b>Generated:</b>
' . date(
    'd M Y h:i A'
) . '


<hr>


<table class="summary">

<tr>

    <th>
        Total Trips
    </th>

    <th>
        In Trip
    </th>

    <th>
        Completed
    </th>

    <th>
        Cancelled
    </th>

</tr>

<tr>

    <td>
        ' . $total . '
    </td>

    <td>
        ' . $inTrip . '
    </td>

    <td>
        ' . $completed . '
    </td>

    <td>
        ' . $cancelled . '
    </td>

</tr>

</table>


<br>


<table>

<tr>

    <th>
        Vehicle
    </th>

    <th>
        Driver
    </th>

    <th>
        Requestor
    </th>

    <th>
        Purpose
    </th>

    <th>
        From
    </th>

    <th>
        To
    </th>

    <th>
        Start KM
    </th>

    <th>
        End KM
    </th>

    <th>
        Distance
    </th>

    <th>
        Start
    </th>

    <th>
        End
    </th>

    <th>
        Status
    </th>

    <th>
        Remarks
    </th>

</tr>
';


foreach ($rows as $row) {

    $status =
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

        };


    $html .= '

    <tr>

        <td>
            ' .
            htmlspecialchars(
                $row['vehicle_name']
            )
            .
            '<br>
            <small>' .
            htmlspecialchars(
                $row['vehicle_number']
            )
            .
            '</small>
        </td>

        <td>
            ' .
            htmlspecialchars(
                $row['driver_name']
            )
            . '
        </td>

        <td>
            ' .
            htmlspecialchars(
                $row['requestor_name']
                ?: '-'
            )
            . '
        </td>

        <td>
            ' .
            htmlspecialchars(
                $row['purpose']
            )
            . '
        </td>

        <td>
            ' .
            htmlspecialchars(
                $row['from_location']
            )
            . '
        </td>

        <td>
            ' .
            htmlspecialchars(
                $row['to_location']
            )
            . '
        </td>

        <td>
            ' .
            (
                $row['start_km']
                !== null
                    ? number_format(
                        (float)
                        $row['start_km'],
                        2
                    )
                    : '-'
            )
            . '
        </td>

        <td>
            ' .
            (
                $row['end_km']
                !== null
                    ? number_format(
                        (float)
                        $row['end_km'],
                        2
                    )
                    : '-'
            )
            . '
        </td>

        <td>
            ' .
            (
                $row['distance']
                !== null
                    ? number_format(
                        (float)
                        $row['distance'],
                        2
                    )
                    : '-'
            )
            . '
        </td>

        <td>
            ' .
            (
                $row['trip_start']
                    ? date(
                        'h:i A',
                        strtotime(
                            $row['trip_start']
                        )
                    )
                    : '-'
            )
            . '
        </td>

        <td>
            ' .
            (
                $row['trip_end']
                    ? date(
                        'h:i A',
                        strtotime(
                            $row['trip_end']
                        )
                    )
                    : '-'
            )
            . '
        </td>

        <td>
            ' .
            htmlspecialchars(
                $status
            )
            . '
        </td>

        <td>
            ' .
            htmlspecialchars(
                $row['remarks']
                ?: '-'
            )
            . '
        </td>

    </tr>

    ';

}


if (
    empty($rows)
) {

    $html .= '

    <tr>

        <td
            colspan="13"
            style="
                text-align:center;
                padding:20px;
            "
        >
            No trips found.
        </td>

    </tr>

    ';

}


$html .= '

</table>


<br><br>


<div>

    <b>
        HR Manager Signature
    </b>

    <br><br>

    _____________________

</div>


<div class="footer">

    Generated by Ambitious Group
    Visitor & Employee Management System

    <br>

    Dubai, UAE

</div>
';


/*
|--------------------------------------------------------------------------
| Generate
|--------------------------------------------------------------------------
*/

$dompdf =
    new Dompdf();


$dompdf->loadHtml(
    $html
);


$dompdf->setPaper(
    'A4',
    'landscape'
);


$dompdf->render();


$dompdf->stream(
    'driver_trips_report.pdf',
    [
        'Attachment' => true
    ]
);


exit;