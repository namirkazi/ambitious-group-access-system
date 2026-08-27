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

$pdo = getDB();


/*
|--------------------------------------------------------------------------
| Trip ID
|--------------------------------------------------------------------------
*/

$tripId =
    filter_input(
        INPUT_GET,
        'id',
        FILTER_VALIDATE_INT
    );

if (!$tripId) {
    http_response_code(400);
    exit('Invalid trip ID');
}


/*
|--------------------------------------------------------------------------
| Get Trip
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT

        t.id,

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

    WHERE t.id = ?

    LIMIT 1
");

$stmt->execute([
    $tripId
]);

$trip =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );

if (!$trip) {
    http_response_code(404);
    exit('Trip not found');
}


/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function voucherValue($value): string
{
    return htmlspecialchars(
        trim(
            (string)($value ?? '')
        ),
        ENT_QUOTES,
        'UTF-8'
    );
}


function voucherDate($value): string
{
    if (!$value) {
        return '-';
    }

    return date(
        'd-m-Y',
        strtotime($value)
    );
}


function voucherTime($value): string
{
    if (!$value) {
        return '-';
    }

    return date(
        'h:i A',
        strtotime($value)
    );
}


function voucherKm($value): string
{
    if (
        $value === null ||
        $value === ''
    ) {
        return '-';
    }

    return number_format(
        (float)$value,
        2
    ) . ' KM';
}


/*
|--------------------------------------------------------------------------
| Duration
|--------------------------------------------------------------------------
*/

$duration = '-';

if (
    !empty($trip['trip_start']) &&
    !empty($trip['trip_end'])
) {

    $start =
        new DateTime(
            $trip['trip_start']
        );

    $end =
        new DateTime(
            $trip['trip_end']
        );

    $seconds =
        $end->getTimestamp()
        -
        $start->getTimestamp();

    if ($seconds >= 0) {

        $hours =
            floor(
                $seconds / 3600
            );

        $minutes =
            floor(
                ($seconds % 3600) / 60
            );

        $duration =
            $hours . 'h '
            . $minutes . 'm';
    }
}


/*
|--------------------------------------------------------------------------
| Status
|--------------------------------------------------------------------------
*/

$status = match ($trip['status']) {

    'started'
    => 'In Trip',

    'completed'
    => 'Completed',

    'cancelled'
    => 'Cancelled',

    default
    => ucfirst(
        $trip['status'] ?? '-'
    )
};


/*
|--------------------------------------------------------------------------
| PDF HTML
|--------------------------------------------------------------------------
*/

$html = '

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<style>

@page {
    margin: 22px;
}

body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 9px;
    color: #202124;
    margin: 0;
}


/* -------------------------------------------------
   HEADER
------------------------------------------------- */

.header {
    width: 100%;
    border-bottom: 2px solid #1f2937;
    padding-bottom: 13px;
    margin-bottom: 16px;
}

.company {
    font-size: 19px;
    font-weight: bold;
    color: #111827;
}

.document-title {
    font-size: 9px;
    letter-spacing: 1.5px;
    color: #6b7280;
    margin-top: 4px;
}

.voucher-meta {
    text-align: right;
}

.voucher-label {
    font-size: 7px;
    color: #6b7280;
    text-transform: uppercase;
}

.voucher-number {
    font-size: 12px;
    font-weight: bold;
    margin-top: 2px;
}

.voucher-date {
    font-size: 8px;
    color: #6b7280;
    margin-top: 3px;
}


/* -------------------------------------------------
   SECTION
------------------------------------------------- */

.section {
    margin-top: 14px;
    margin-bottom: 6px;
    font-size: 8px;
    font-weight: bold;
    letter-spacing: 1px;
    color: #374151;
    text-transform: uppercase;
}


/* -------------------------------------------------
   INFORMATION TABLE
------------------------------------------------- */

.details {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    border: 1px solid #e5e7eb;
}

.details td {
    width: 25%;
    padding: 8px 9px;
    border-right: 1px solid #e5e7eb;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: top;
}

.details tr:last-child td {
    border-bottom: none;
}

.details td:last-child {
    border-right: none;
}

.label {
    font-size: 7px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .6px;
    margin-bottom: 3px;
}

.value {
    font-size: 9px;
    font-weight: bold;
    color: #1f2937;
}


/* -------------------------------------------------
   ROUTE
------------------------------------------------- */

.route {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.route td {
    width: 50%;
    padding: 11px 12px;
    border: 1px solid #e5e7eb;
    vertical-align: top;
}

.route td:first-child {
    border-right: none;
}

.route-label {
    font-size: 7px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .7px;
}

.route-value {
    font-size: 10px;
    font-weight: bold;
    color: #111827;
    margin-top: 5px;
}


/* -------------------------------------------------
   KM / TIME
------------------------------------------------- */

.metric-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 6px 0;
    margin-left: -6px;
    margin-right: -6px;
}

.metric {
    border: 1px solid #e5e7eb;
    padding: 9px 10px;
    vertical-align: top;
}

.metric-label {
    font-size: 7px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .6px;
}

.metric-value {
    font-size: 10px;
    font-weight: bold;
    color: #111827;
    margin-top: 4px;
}


/* -------------------------------------------------
   STATUS
------------------------------------------------- */

.status {
    font-size: 9px;
    font-weight: bold;
    color: #166534;
}


/* -------------------------------------------------
   REMARKS
------------------------------------------------- */

.remarks {
    border: 1px solid #e5e7eb;
    padding: 10px;
    min-height: 42px;
    font-size: 9px;
    line-height: 1.5;
    color: #374151;
}


/* -------------------------------------------------
   SIGNATURES
------------------------------------------------- */

.signature-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 12px 0;
    margin-left: -12px;
    margin-top: 25px;
}

.signature-box {
    width: 50%;
    border: 1px solid #e5e7eb;
    height: 72px;
    vertical-align: bottom;
    text-align: center;
    padding: 0 15px 10px 15px;
}

.signature-line {
    border-top: 1px solid #6b7280;
    padding-top: 5px;
    font-size: 8px;
    font-weight: bold;
    color: #374151;
}

.signature-subtitle {
    font-size: 7px;
    color: #9ca3af;
    margin-top: 2px;
}


/* -------------------------------------------------
   FOOTER
------------------------------------------------- */

.footer {
    margin-top: 18px;
    padding-top: 7px;
    border-top: 1px solid #e5e7eb;
    text-align: center;
    font-size: 7px;
    color: #9ca3af;
}

</style>

</head>

<body>


<!-- HEADER -->

<table class="header">

<tr>

<td width="65%">

    <div class="company">
        AMBITIOUS GROUP
    </div>

    <div class="document-title">
        DRIVER TRIP VOUCHER
    </div>

</td>


<td width="35%" class="voucher-meta">

    <div class="voucher-label">
        Voucher Number
    </div>

    <div class="voucher-number">
        #'
    . voucherValue(
        $trip['id']
    )
    . '
    </div>

    <div class="voucher-date">
        '
    . voucherDate(
        $trip['trip_start']
    )
    . '
    </div>

</td>

</tr>

</table>


<!-- TRIP DETAILS -->

<div class="section">
    Trip Details
</div>


<table class="details">

<tr>

<td>

    <div class="label">
        Driver
    </div>

    <div class="value">
        '
    . voucherValue(
        $trip['driver_name']
    )
    . '
    </div>

</td>


<td>

    <div class="label">
        Vehicle
    </div>

    <div class="value">
        '
    . voucherValue(
        $trip['vehicle_name']
    )
    . '
    </div>

</td>


<td>

    <div class="label">
        Vehicle Number
    </div>

    <div class="value">
        '
    . voucherValue(
        $trip['vehicle_number']
    )
    . '
    </div>

</td>


<td>

    <div class="label">
        Requestor
    </div>

    <div class="value">
        '
    . voucherValue(
        $trip['requestor_name']
            ?: '-'
    )
    . '
    </div>

</td>

</tr>


<tr>

<td colspan="3">

    <div class="label">
        Purpose
    </div>

    <div class="value">
        '
    . voucherValue(
        $trip['purpose']
    )
    . '
    </div>

</td>


<td>

    <div class="label">
        Status
    </div>

    <div class="status">
        '
    . voucherValue(
        $status
    )
    . '
    </div>

</td>

</tr>

</table>


<!-- ROUTE -->

<div class="section">
    Route
</div>


<table class="route">

<tr>

<td>

    <div class="route-label">
        From
    </div>

    <div class="route-value">
        '
    . voucherValue(
        $trip['from_location']
    )
    . '
    </div>

</td>


<td>

    <div class="route-label">
        To
    </div>

    <div class="route-value">
        '
    . voucherValue(
        $trip['to_location']
    )
    . '
    </div>

</td>

</tr>

</table>


<!-- KILOMETERS -->

<div class="section">
    Kilometer Details
</div>


<table class="metric-table">

<tr>

<td class="metric" width="33.33%">

    <div class="metric-label">
        Start KM
    </div>

    <div class="metric-value">
        '
    . voucherKm(
        $trip['start_km']
    )
    . '
    </div>

</td>


<td class="metric" width="33.33%">

    <div class="metric-label">
        End KM
    </div>

    <div class="metric-value">
        '
    . voucherKm(
        $trip['end_km']
    )
    . '
    </div>

</td>


<td class="metric" width="33.33%">

    <div class="metric-label">
        Total Distance
    </div>

    <div class="metric-value">
        '
    . voucherKm(
        $trip['distance']
    )
    . '
    </div>

</td>

</tr>

</table>


<!-- TIME -->

<div class="section">
    Time Details
</div>


<table class="metric-table">

<tr>

<td class="metric" width="33.33%">

    <div class="metric-label">
        Trip Started
    </div>

    <div class="metric-value">

        '
    . (
        $trip['trip_start']
        ? voucherDate(
            $trip['trip_start']
        )
        . ' '
        . voucherTime(
            $trip['trip_start']
        )
        : '-'
    )
    . '

    </div>

</td>


<td class="metric" width="33.33%">

    <div class="metric-label">
        Trip Ended
    </div>

    <div class="metric-value">

        '
    . (
        $trip['trip_end']
        ? voucherDate(
            $trip['trip_end']
        )
        . ' '
        . voucherTime(
            $trip['trip_end']
        )
        : '-'
    )
    . '

    </div>

</td>


<td class="metric" width="33.33%">

    <div class="metric-label">
        Duration
    </div>

    <div class="metric-value">
        '
    . voucherValue(
        $duration
    )
    . '
    </div>

</td>

</tr>

</table>


<!-- REMARKS -->

<div class="section">
    Remarks
</div>


<div class="remarks">

'
    . (
        !empty($trip['remarks'])
        ? nl2br(
            voucherValue(
                $trip['remarks']
            )
        )
        : '-'
    )
    . '

</div>


<!-- SIGNATURES -->

<table class="signature-table">

<tr>

<td class="signature-box">

    <div class="signature-line">
        Requestor Signature
    </div>

    <div class="signature-subtitle">
        Requestor
    </div>

</td>


<td class="signature-box">

    <div class="signature-line">
        Authorized By
    </div>

    <div class="signature-subtitle">
        Authorized Representative
    </div>

</td>

</tr>

</table>


<!-- FOOTER -->

<div class="footer">

    Ambitious Group

</div>


</body>

</html>

';
/*
|--------------------------------------------------------------------------
| Generate PDF
|--------------------------------------------------------------------------
*/

$dompdf =
    new Dompdf();


$dompdf->loadHtml(
    $html
);


$dompdf->setPaper(
    'A4',
    'portrait'
);


$dompdf->render();


$dompdf->stream(
    'trip_voucher_' . $trip['id'] . '.pdf',
    [
        'Attachment' => false
    ]
);


exit;
