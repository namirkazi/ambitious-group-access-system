<?php

session_start();


/*
|--------------------------------------------------------------------------
| ACCESS CONTROL
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    exit('Access denied');
}

if (
    $_SESSION['role'] != 'hr' &&
    $_SESSION['role'] != 'admin'
) {
    http_response_code(403);
    exit('Insufficient privileges');
}


/*
|--------------------------------------------------------------------------
| CONFIG / DOMPDF
|--------------------------------------------------------------------------
*/

require_once '../includes/config.php';
require '../vendor/autoload.php';

use Dompdf\Dompdf;

$pdo = getDB();


/*
|--------------------------------------------------------------------------
| COMPANY DETAILS
|--------------------------------------------------------------------------
|
| Replace the placeholder contact details with your real details.
|
*/

$companyName =
    'Ambitious Tourism';

$companyAddress =
    'Saraya Avenue Block B, Al Garhoud, Dubai, UAE';

$companyPhone =
    '+971 XX XXX XXXX';

$companyEmail =
    'info@ambitioustourism.com';

$companyWebsite =
    'https://www.example.com';

$companyWebsiteLabel =
    'www.example.com';

$instagramUrl =
    'https://www.instagram.com/';

$instagramLabel =
    '@ambitioustourism';

$facebookUrl =
    'https://www.facebook.com/';

$facebookLabel =
    '@AmbitiousTourism';

$whatsappNumber =
    '+971 XX XXX XXXX';

$whatsappUrl =
    'https://wa.me/971XXXXXXXXX';


/*
|--------------------------------------------------------------------------
| LOGO
|--------------------------------------------------------------------------
|
| Put the high-resolution logo here:
|
| assets/images/ambitious-tourism-logo.png
|
*/

$logoPath =
    realpath(
        __DIR__ .
            '/../assets/images/ambitious-tourism-logo.png'
    );


/*
|--------------------------------------------------------------------------
| IMAGE → DATA URI
|--------------------------------------------------------------------------
|
| Only the logo is loaded.
| No background/header images are used.
|
*/

function imageDataUri($path)
{
    if (
        !$path ||
        !file_exists($path)
    ) {
        return '';
    }

    $extension =
        strtolower(
            pathinfo(
                $path,
                PATHINFO_EXTENSION
            )
        );

    $mime = match ($extension) {

        'jpg',
        'jpeg'
        => 'image/jpeg',

        'png'
        => 'image/png',

        'webp'
        => 'image/webp',

        default
        => ''
    };

    if (!$mime) {
        return '';
    }

    $data =
        file_get_contents($path);

    if ($data === false) {
        return '';
    }

    return
        'data:' .
        $mime .
        ';base64,' .
        base64_encode($data);
}

$logo =
    imageDataUri(
        $logoPath
    );


/*
|--------------------------------------------------------------------------
| TRIP ID
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
| FETCH TRIP
|--------------------------------------------------------------------------
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
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

function voucherValue($value)
{
    return htmlspecialchars(
        trim(
            (string)($value ?? '')
        ),
        ENT_QUOTES,
        'UTF-8'
    );
}


function voucherDate($value)
{
    if (!$value) {
        return '-';
    }

    return date(
        'd-m-Y',
        strtotime($value)
    );
}


function voucherTime($value)
{
    if (!$value) {
        return '-';
    }

    return date(
        'h:i A',
        strtotime($value)
    );
}


function voucherKm($value)
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
| DURATION
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
| STATUS
|--------------------------------------------------------------------------
*/

$statusText = match ($trip['status']) {

    'started'
    => 'IN PROGRESS',

    'completed'
    => 'COMPLETED',

    'cancelled'
    => 'CANCELLED',

    default
    => strtoupper(
        $trip['status'] ?? '-'
    )
};


/*
|--------------------------------------------------------------------------
| STATUS CLASS
|--------------------------------------------------------------------------
*/

$statusClass = match ($trip['status']) {

    'completed'
    => 'status-completed',

    'started'
    => 'status-started',

    'cancelled'
    => 'status-cancelled',

    default
    => 'status-default'
};


/*
|--------------------------------------------------------------------------
| LOGO HTML
|--------------------------------------------------------------------------
*/

$logoHtml = '';

if ($logo) {

    $logoHtml = '
        <img
            src="' . $logo . '"
            class="logo"
            alt="Ambitious Tourism"
        >
    ';
}


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
    margin: 4mm;
    size: A4 portrait;
}

html, body {
    margin: 0;
    padding: 0;
}

body {
    font-family: DejaVu Sans, Arial, sans-serif;
    background: #f4f3ef;
    color: #111111;
    font-size: 11px;
    line-height: 1.4;
}

* {
    box-sizing: border-box;
}

.page {
    width: 100%;
    max-width: 100%;
    background: #ffffff;
    border: 1px solid #e5e2dc;
    overflow: hidden;
}

.header {
    background: linear-gradient(135deg, #0a5a45 0%, #0f6a51 35%, #0c4338 100%);
    padding: 8px 12px 7px;
    border-bottom: 3px solid #d7ac42;
}

.header-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.header-left {
    width: 58%;
    vertical-align: middle;
}

.header-right {
    width: 42%;
    text-align: right;
    vertical-align: middle;
}

.header-address {
    margin-top: 4px;
    color: rgba(255,255,255,0.95);
    font-size: 7px;
    line-height: 1.4;
    letter-spacing: 0.2px;
    text-align: right;
    max-width: 190px;
    margin-left: auto;
}

.header-contact {
    margin-top: 2px;
    color: rgba(255,255,255,0.88);
    font-size: 6.5px;
    letter-spacing: 0.3px;
    text-align: right;
}

.logo {
    display: block;
    max-width: 160px;
    max-height: 48px;
    width: auto;
    height: auto;
}

.brand-name {
    color: #ffffff;
    font-size: 30px;
    font-weight: 800;
    letter-spacing: -1.5px;
    line-height: 0.9;
    margin: 0;
}

.brand-name span {
    display: inline-block;
    color: #f3c75e;
    margin-left: 2px;
}

.brand-sub {
    letter-spacing: 5px;
    color: rgba(255,255,255,0.8);
    font-size: 8px;
    font-weight: 700;
    text-transform: uppercase;
    margin-top: 6px;
}

.headline {
    color: #ffffff;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.3px;
}

.headline-line {
    margin-top: 3px;
    color: #f3c75e;
    font-size: 7px;
    letter-spacing: 1.8px;
    text-transform: uppercase;
}

.intro {
    padding: 6px 12px 0;
}

.intro-table {
    width: 100%;
    border-collapse: collapse;
}

.intro-title {
    font-size: 26px;
    font-weight: 800;
    color: #111111;
    letter-spacing: -0.4px;
}

.intro-copy {
    margin-top: 6px;
    color: #3a3a3a;
    font-size: 9px;
}

.meta-box {
    background: #f7f7f4;
    border: 1px solid #e4dfd7;
    border-radius: 5px;
    padding: 6px 8px;
    text-align: left;
    min-width: 135px;
}

.meta-label {
    color: #4a4a4a;
    font-size: 7px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.meta-number {
    color: #111111;
    font-size: 20px;
    font-weight: 800;
    margin-top: 3px;
    letter-spacing: -0.4px;
}

.meta-date {
    margin-top: 6px;
    color: #111111;
    font-size: 9px;
    font-weight: 700;
}

.meta-status {
    margin-top: 6px;
    font-size: 7.5px;
    font-weight: 700;
    letter-spacing: 0.7px;
    text-transform: uppercase;
}

.status-completed { color: #0d7d52; }
.status-started { color: #a96d00; }
.status-cancelled { color: #a43a3a; }
.status-default { color: #1b422f; }

.section {
    padding: 5px 12px 0;
}

.section-title {
    color: #111111;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 1.2px;
    text-transform: uppercase;
}

.section-line {
    height: 2px;
    width: 36px;
    background: #d6ad43;
    margin-top: 5px;
}

.card {
    border: 1px solid #e7e0d6;
    border-radius: 8px;
    background: #ffffff;
    overflow: hidden;
}

.info-table,
.route-table,
.metrics-table,
.sign-table,
.footer-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.info-table {
    margin-top: 10px;
}

.info-table td {
    width: 25%;
    padding: 6px 8px;
    border-right: 1px solid #eff0eb;
    border-bottom: 1px solid #eff0eb;
    vertical-align: top;
}

.info-table tr:last-child td {
    border-bottom: none;
}

.info-table td:last-child {
    border-right: none;
}

.label {
    color: #4a4a4a;
    font-size: 7px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.value {
    margin-top: 4px;
    color: #111111;
    font-size: 9px;
    font-weight: 700;
    line-height: 1.4;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.route-table {
    margin-top: 10px;
}

.route-table td {
    width: 50%;
    padding: 7px 10px;
    vertical-align: top;
}

.route-from {
    border-right: 1px solid #efeae0;
}

.route-label {
    color: #4a4a4a;
    font-size: 7px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.route-value {
    margin-top: 5px;
    color: #111111;
    font-size: 11px;
    font-weight: 800;
    line-height: 1.4;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.route-arrow {
    margin-top: 6px;
    color: #d7ac42;
    font-size: 7.5px;
    font-weight: 800;
    letter-spacing: 1.2px;
    text-transform: uppercase;
}

.metrics-table {
    margin-top: 8px;
    border-spacing: 4px;
}

.metric {
    background: #f8f9f6;
    border: 1px solid #e5e0d6;
    border-radius: 5px;
    padding: 5px 6px;
    vertical-align: top;
}

.metric-label {
    color: #4a4a4a;
    font-size: 7px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.metric-value {
    margin-top: 5px;
    color: #111111;
    font-size: 9px;
    font-weight: 800;
}

.remark-box {
    margin-top: 5px;
    background: #fbfaf7;
    border: 1px solid #e6e1d9;
    border-left: 3px solid #d7ac42;
    border-radius: 5px;
    padding: 6px 8px;
    color: #111111;
    font-size: 8.5px;
    line-height: 1.4;
    min-height: 28px;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.auth-title {
    margin-top: 6px;
    text-align: center;
    color: #111111;
    font-size: 8.5px;
    font-weight: 800;
    letter-spacing: 1.1px;
    text-transform: uppercase;
}

.sign-table {
    margin-top: 5px;
}

.signature {
    width: 50%;
    height: 48px;
    vertical-align: bottom;
    text-align: center;
    background: #fff;
    border: 1px solid #e4ddd2;
    border-radius: 5px;
    padding: 12px 8px 5px;
}

.signature-line {
    color: #111111;
    font-size: 7.5px;
    font-weight: 800;
    letter-spacing: 0.7px;
    text-transform: uppercase;
    border-top: 1px solid #b1b7b3;
    padding-top: 6px;
}

.signature-sub {
    color: #4a4a4a;
    font-size: 6.5px;
    margin-top: 4px;
}

.footer {
    margin-top: 6px;
    background: linear-gradient(135deg, #083f33 0%, #0d4b3f 100%);
    padding: 7px 10px 5px;
    color: #ffffff;
}

.footer-item {
    width: 25%;
    vertical-align: top;
    padding-right: 8px;
}

.footer-label {
    color: #dfeae6;
    font-size: 6.5px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.footer-value {
    margin-top: 4px;
    color: #ffffff;
    font-size: 7.5px;
    line-height: 1.4;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.footer-copy {
    margin-top: 10px;
    padding-top: 8px;
    border-top: 1px solid rgba(255,255,255,0.18);
    color: rgba(255,255,255,0.8);
    text-align: center;
    font-size: 6px;
}
</style>
</head>
<body>
<div class="page">
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-left">
                    ' . ($logo ? $logoHtml : '<div class="brand-name">Ambitious<span>Tourism</span></div><div class="brand-sub">Travel</div>') . '
                </td>
                <td class="header-right">
                    <div class="headline">Trip Voucher</div>
                    <div class="headline-line">Official Travel Record</div>
                    <div class="header-address">' . voucherValue($companyAddress) . '</div>
                    <div class="header-contact">' . voucherValue($companyPhone) . ' • ' . voucherValue($companyEmail) . '</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="intro">
        <table class="intro-table">
            <tr>
                <td width="62%" valign="middle">
                    <div class="intro-title">Travel Authorization</div>
                    <div class="intro-copy">Official trip record and journey approval document.</div>
                </td>
                <td width="38%" valign="middle" align="right">
                    <div class="meta-box">
                        <div class="meta-label">Voucher No.</div>
                        <div class="meta-number">#' . voucherValue($trip['id']) . '</div>
                        <div class="meta-date">' . voucherDate($trip['trip_start']) . '</div>
                        <div class="meta-status ' . $statusClass . '">' . voucherValue($statusText) . '</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Trip Details</div>
        <div class="section-line"></div>
    </div>

    <table class="info-table card" style="margin: 5px 12px 0; width: calc(100% - 24px);">
        <tr>
            <td><div class="label">Driver</div><div class="value">' . voucherValue($trip['driver_name']) . '</div></td>
            <td><div class="label">Vehicle</div><div class="value">' . voucherValue($trip['vehicle_name']) . '</div></td>
            <td><div class="label">Vehicle No.</div><div class="value">' . voucherValue($trip['vehicle_number']) . '</div></td>
            <td><div class="label">Requestor</div><div class="value">' . voucherValue($trip['requestor_name'] ?: '-') . '</div></td>
        </tr>
        <tr>
            <td colspan="3"><div class="label">Purpose</div><div class="value">' . voucherValue($trip['purpose']) . '</div></td>
            <td><div class="label">Status</div><div class="value ' . $statusClass . '">' . voucherValue($statusText) . '</div></td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Route</div>
        <div class="section-line"></div>
    </div>

    <table class="route-table card" style="margin: 5px 12px 0; width: calc(100% - 24px);">
        <tr>
            <td class="route-from">
                <div class="route-label">From</div>
                <div class="route-value">' . voucherValue($trip['from_location']) . '</div>
                <div class="route-arrow">Start</div>
            </td>
            <td>
                <div class="route-label">To</div>
                <div class="route-value">' . voucherValue($trip['to_location']) . '</div>
                <div class="route-arrow">Destination</div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Journey Metrics</div>
        <div class="section-line"></div>
    </div>

    <table class="metrics-table" style="margin: 5px 12px 0; width: calc(100% - 24px);">
        <tr>
            <td class="metric" width="16.66%"><div class="metric-label">Start KM</div><div class="metric-value">' . voucherKm($trip['start_km']) . '</div></td>
            <td class="metric" width="16.66%"><div class="metric-label">End KM</div><div class="metric-value">' . voucherKm($trip['end_km']) . '</div></td>
            <td class="metric" width="16.66%"><div class="metric-label">Distance</div><div class="metric-value">' . voucherKm($trip['distance']) . '</div></td>
            <td class="metric" width="16.66%"><div class="metric-label">Started</div><div class="metric-value">' . ($trip['trip_start'] ? voucherTime($trip['trip_start']) : '-') . '</div></td>
            <td class="metric" width="16.66%"><div class="metric-label">Ended</div><div class="metric-value">' . ($trip['trip_end'] ? voucherTime($trip['trip_end']) : '-') . '</div></td>
            <td class="metric" width="16.66%"><div class="metric-label">Duration</div><div class="metric-value">' . voucherValue($duration) . '</div></td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Remarks</div>
        <div class="section-line"></div>
    </div>

    <div style="margin: 5px 12px 0;">
        <div class="remark-box">' . (!empty($trip['remarks']) ? nl2br(voucherValue($trip['remarks'])) : '-') . '</div>
    </div>

    <div class="auth-title">Authorizations</div>
    <table class="sign-table" style="margin: 5px 12px 0; width: calc(100% - 24px);">
        <tr>
            <td class="signature"><div class="signature-line">Requestor Signature</div><div class="signature-sub">Requestor</div></td>
            <td class="signature"><div class="signature-line">Authorized By</div><div class="signature-sub">Authorized Representative</div></td>
        </tr>
    </table>

    <div class="footer">
        <table class="footer-table">
            <tr>
                <td class="footer-item"><div class="footer-label">Instagram</div><div class="footer-value">' . voucherValue($instagramLabel) . '</div></td>
                <td class="footer-item"><div class="footer-label">Facebook</div><div class="footer-value">' . voucherValue($facebookLabel) . '</div></td>
                <td class="footer-item"><div class="footer-label">WhatsApp</div><div class="footer-value">' . voucherValue($whatsappNumber) . '</div></td>
                <td class="footer-item"><div class="footer-label">Website</div><div class="footer-value">' . voucherValue($companyWebsiteLabel) . '</div></td>
            </tr>
        </table>
        <div class="footer-copy">© ' . date('Y') . ' Ambitious Tourism • ' . voucherValue($companyAddress) . '</div>
    </div>
</div>
</body>
</html>
';

/*
|--------------------------------------------------------------------------
| GENERATE PDF
|--------------------------------------------------------------------------
*/

$dompdf =
    new Dompdf([
        'isRemoteEnabled' => false
    ]);


$dompdf->loadHtml(
    $html
);


$dompdf->setPaper(
    'A4',
    'portrait'
);


$dompdf->render();


$dompdf->stream(
    'trip_voucher_' .
        $trip['id'] .
        '.pdf',
    [
        'Attachment' => false
    ]
);


exit;
