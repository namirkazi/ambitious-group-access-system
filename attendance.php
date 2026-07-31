<?php

session_start();

if (
    !isset($_SESSION['admin_logged_in'])
) {

    header("Location: login.php");
    exit();
}

if (
    $_SESSION['role'] != 'hr'
    &&
    $_SESSION['role'] != 'admin'
) {

    header("Location: admin.php");
    exit();
}

require_once 'includes/config.php';

$pdo = getDB();
$totalEmployees = $pdo->query("
SELECT COUNT(*)
FROM employees
WHERE active=1
")->fetchColumn();

$presentEmployees = $pdo->query("
SELECT COUNT(DISTINCT ea.employee_id)
FROM employee_attendance ea
JOIN employees e
ON ea.employee_id = e.id
WHERE ea.attendance_date = CURDATE()
AND e.active = 1
")->fetchColumn();

$inOffice = $pdo->query("
SELECT COUNT(*)
FROM employee_attendance
WHERE attendance_date=CURDATE()
AND check_out IS NULL
")->fetchColumn();

$absentEmployees =
    $totalEmployees - $presentEmployees;

$from =
    $_GET['from']
    ??
    date('Y-m-d');

$to =
    $_GET['to']
    ??
    date('Y-m-d');

// Get all active employees
$employees = $pdo->query("
    SELECT
        id,
        title,
        full_name,
        department,
        photo_path
    FROM employees
    WHERE active = 1
    ORDER BY full_name
")->fetchAll();

// Get attendance records for selected range
$stmt = $pdo->prepare("
    SELECT
        employee_id,
        attendance_date,
        check_in,
        check_out,
        total_hours
    FROM employee_attendance
    WHERE attendance_date BETWEEN ? AND ?
");

$stmt->execute([$from, $to]);

$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Index attendance by date then employee
$attendanceIndex = [];

foreach ($records as $record) {

    $date = date('Y-m-d', strtotime($record['attendance_date']));

    $attendanceIndex[$date][$record['employee_id']] = $record;
}

// Generate report
$attendance = [];

$period = new DatePeriod(
    new DateTime($from),
    new DateInterval('P1D'),
    (new DateTime($to))->modify('+1 day')
);

foreach ($period as $day) {

    // Skip Sundays
    if ($day->format('w') == 0) {
        continue;
    }

    $date = $day->format('Y-m-d');

    foreach ($employees as $employee) {

        if (isset($attendanceIndex[$date][$employee['id']])) {

            $record = $attendanceIndex[$date][$employee['id']];

            $status = $record['check_out']
                ? 'Checked Out'
                : ($date == date('Y-m-d') ? 'Present' : 'Missed Checkout');

            $attendance[] = [

                'id' => $employee['id'],
                'title' => $employee['title'],
                'full_name' => $employee['full_name'],
                'department' => $employee['department'],
                'photo_path' => $employee['photo_path'],

                'attendance_date' => $date,

                'check_in' => $record['check_in'],
                'check_out' => $record['check_out'],
                'total_hours' => $record['total_hours'],

                'status' => $status

            ];
        } else {

            $attendance[] = [

                'id' => $employee['id'],
                'title' => $employee['title'],
                'full_name' => $employee['full_name'],
                'department' => $employee['department'],
                'photo_path' => $employee['photo_path'],

                'attendance_date' => $date,

                'check_in' => null,
                'check_out' => null,
                'total_hours' => null,

                'status' => 'Absent'

            ];
        }
    }
}
?>

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Visitor Management</title>
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicon/apple-touch-icon.png">

    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon/favicon-32x32.png">

    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon/favicon-16x16.png">

    <link rel="icon" href="assets/favicon/favicon.ico">

    <link rel="manifest" href="assets/favicon/site.webmanifest">
    <style>
        #toast {

            position: fixed;
            top: 20px;
            right: 20px;

            background: var(--surface);

            border: 1px solid var(--border);

            padding: 1rem 1.25rem;

            border-radius: 10px;

            display: none;

            z-index: 2000;

        }

        .modal-overlay {

            position: fixed;
            inset: 0;

            background: rgba(0, 0, 0, .7);

            display: none;

            align-items: center;
            justify-content: center;

            z-index: 1000;

        }

        .modal-box {

            width: 400px;

            background: var(--surface);

            border: 1px solid var(--border);

            border-radius: 12px;

            padding: 1.5rem;

        }

        .modal-box h3 {

            margin-bottom: 1rem;

            color: var(--accent2);

        }

        .modal-box p {

            color: var(--muted);

            margin-bottom: 1.5rem;

        }

        .modal-actions {

            display: flex;

            gap: .75rem;

        }

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
            --font: 'Segoe UI', system-ui, sans-serif;
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
            border-bottom: 1px solid var(--border);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        header .logo {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        header .logo svg {
            color: var(--accent2);
        }

        header h1 {
            font-size: 1.2rem;
            font-weight: 700;
            letter-spacing: -.02em;
        }

        header h1 span {
            color: var(--accent2);
        }

        header a {
            background: var(--accent);
            color: #fff;
            padding: .5rem 1.2rem;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
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

        .date-filter {

            display: flex;
            align-items: end;
            gap: 12px;

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

            border: 1px solid var(--border);

            border-radius: 10px;

            padding: .65rem .85rem;

            font-size: .85rem;

            min-width: 170px;

            transition: .2s;

        }

        .date-group input:focus {

            outline: none;

            border-color: var(--accent);

            box-shadow:
                0 0 0 3px rgba(108, 99, 255, .15);

        }

        .report-toolbar {

            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 1rem;
            flex-wrap: wrap;

        }

        .export-buttons {

            display: flex;
            gap: .5rem;
            align-items: flex-end;

        }

        .date-filter {

            display: flex;
            align-items: flex-end;
            gap: 12px;
            flex-wrap: wrap;

        }

        @media(max-width:900px) {

            .date-filter {

                flex-direction: column;
                align-items: stretch;

            }

            .date-group input {

                width: 100%;

            }

        }

        .range-info {

            padding: 1rem 1.5rem;

            background: rgba(108, 99, 255, .08);

            border-bottom: 1px solid var(--border);

            color: var(--accent2);

            font-weight: 600;

        }

        h2 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text);
        }

        .checkout-btn {
            background: linear-gradient(135deg,
                    var(--accent),
                    var(--accent2));

            color: white;
            border: none;
            border-radius: 8px;

            padding: .45rem .85rem;

            font-size: .75rem;
            font-weight: 600;

            cursor: pointer;

            transition: transform .15s,
                opacity .15s;

            margin-right: .5rem;
        }

        .checkout-btn:hover {
            opacity: .9;
        }

        .checkout-btn:active {
            transform: scale(.97);
        }

        .table-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .table-card .card-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: var(--surface2);
            padding: .75rem 1rem;
            text-align: left;
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--muted);
            font-weight: 600;
        }

        tbody tr {
            border-top: 1px solid var(--border);
            transition: background .15s;
        }

        tbody tr:hover {
            background: var(--surface2);
        }

        tbody td {
            padding: .85rem 1rem;
            font-size: .875rem;
            vertical-align: middle;
        }

        .visitor-cell {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--border);
            flex-shrink: 0;
        }

        .avatar-placeholder {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--surface2);
            border: 2px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .85rem;
            font-weight: 700;
            color: var(--accent2);
            flex-shrink: 0;
        }

        .visitor-name {
            font-weight: 600;
        }

        .visitor-phone {
            font-size: .75rem;
            color: var(--muted);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .25rem .7rem;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 600;
        }

        .badge.absent {

            background:
                rgba(239, 68, 68, .15);

            color:
                var(--danger);

        }

        .badge.missed {

            background: rgba(245, 158, 11, 0.15);

            color:
                var(--warn);

        }

        .badge.in {
            background: rgba(34, 197, 94, .15);
            color: var(--success);
        }

        .badge.out {
            background: rgba(148, 163, 184, .1);
            color: var(--muted);
        }

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

            border: 1px solid var(--border);

            color: var(--text);

            padding: .75rem 3rem .75rem 1rem;

            border-radius: 10px;

            font-size: .9rem;

        }

        #searchInput:focus {

            outline: none;

            border-color: var(--accent);

        }

        #clearSearch {

            position: absolute;

            top: 50%;

            right: 12px;

            transform: translateY(-50%);

            width: 28px;

            height: 28px;

            border: none;

            border-radius: 50%;

            background: var(--surface2);

            color: var(--muted);

            cursor: pointer;

            display: none;

            transition: .2s;

        }

        #clearSearch:hover {

            background: var(--accent);

            color: white;

        }

        .search-bar input:focus {
            outline: none;
            border-color: var(--accent);
        }

        .badge-num {
            font-family: monospace;
            font-size: .8rem;
            color: var(--accent2);
        }

        @media (max-width: 900px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }

            .container {
                padding: 1rem;
            }
        }
    </style>
</head>
<header>
    <div class="logo">
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
        </svg>
        <h1>Ambitious<span>Group</span> — Attendance</h1>
    </div>
    <div style="
display:flex;
gap:.75rem;
">

        <?php if ($_SESSION['role'] == 'hr'): ?>
            <a href="employees.php">

                Employees

            </a>
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

<body>
    <div class="container">

        <div class="stats-grid">

            <div class="stat-card blue">

                <div class="label">

                    Total Employees

                </div>

                <div class="value">

                    <?= $totalEmployees ?>

                </div>

            </div>

            <div class="stat-card green">

                <div class="label">

                    Present Today

                </div>

                <div class="value">

                    <?= $presentEmployees ?>

                </div>

            </div>

            <div class="stat-card orange">

                <div class="label">

                    In Office

                </div>

                <div class="value">

                    <?= $inOffice ?>

                </div>

            </div>

            <div class="stat-card red">

                <div class="label">

                    Absent

                </div>

                <div class="value">

                    <?= $absentEmployees ?>

                </div>

            </div>

        </div>
        <div class="search-bar">

            <div class="search-container">

                <input type="text" id="searchInput" placeholder="Search employee...">

                <button id="clearSearch">

                    ✕

                </button>

            </div>

        </div>
        <div class="table-card">

            <div class="card-header report-toolbar">

                <h2>

                    Attendance History

                </h2>
                <div class="date-filter">

                    <div class="date-group">

                        <label>From Date</label>

                        <input type="date" id="fromDate" value="<?= $from ?>">

                    </div>

                    <div class="date-group">

                        <label>To Date</label>

                        <input type="date" id="toDate" value="<?= $to ?>">

                    </div>

                    <button class="checkout-btn" onclick="applyDateFilter()">
                        Apply
                    </button>
                    <div class="export-buttons">

                        <button class="checkout-btn" onclick="resetFilter()">
                            Reset
                        </button>

                        <?php if ($_SESSION['role'] == 'hr'): ?>

                            <button class="checkout-btn" onclick="exportPDF()">
                                PDF
                            </button>

                            <button class="checkout-btn" onclick="exportExcel()">
                                Excel
                            </button>

                            <button class="checkout-btn" onclick="window.print()">
                                Print
                            </button>

                        <?php endif; ?>

                    </div>

                </div>

            </div>
            <div class="range-info">
                Showing records from

                <?= date('d M Y', strtotime($from)) ?>

                to

                <?= date('d M Y', strtotime($to)) ?>

            </div>
            <table>

                <thead>

                    <tr>

                        <th>Employee</th>

                        <th>Department</th>

                        <th>Date</th>

                        <th>Check In</th>

                        <th>Check Out</th>

                        <th>Hours</th>

                        <th>Status</th>

                    </tr>

                </thead>

                <tbody><?php foreach ($attendance as $row): ?>

                        <tr>

                            <td>

                                <div class="visitor-cell">

                                    <?php if (!empty($row['photo_path'])): ?>

                                        <img src="<?= UPLOAD_URL . htmlspecialchars($row['photo_path']) ?>" class="avatar">

                                    <?php else: ?>

                                        <div class="avatar-placeholder">

                                            <?= strtoupper(
                                                substr(
                                                    $row['full_name'],
                                                    0,
                                                    1
                                                )
                                            ) ?>

                                        </div>

                                    <?php endif; ?>

                                    <div>

                                        <div class="visitor-name">

                                            <?= htmlspecialchars(
                                                $row['title'] . ' ' . $row['full_name']
                                            ) ?>

                                        </div>

                                    </div>

                                </div>

                            </td>

                            <td>

                                <?= htmlspecialchars(

                                    $row['department']

                                ) ?>

                            </td>

                            <td>
                                <span class="badge out">

                                    <?= $row['attendance_date']
                                        ? date('d-m-Y', strtotime($row['attendance_date']))
                                        : '-'
                                    ?>

                                </span>

                            </td>

                            <td>

                                <?= $row['check_in']
                                    ? date('h:i A', strtotime($row['check_in']))
                                    : '-'
                                ?>
                            </td>

                            <td>

                                <?=

                                $row['check_out']

                                    ?

                                    date(

                                        'h:i A',

                                        strtotime(
                                            $row['check_out']
                                        )

                                    )

                                    :

                                    '-'

                                ?>

                            </td>

                            <td>

                                <?=

                                $row['total_hours']

                                    ?

                                    $row['total_hours']

                                    :

                                    '-'

                                ?>



                            </td>

                            <td>

                                <?php

                                $statusClass = match ($row['status']) {

                                    'Present' => 'in',

                                    'Checked Out' => 'out',

                                    'Missed Checkout' => 'missed',

                                    default => 'absent'
                                };

                                ?>

                                <span class="badge <?= $statusClass ?>">
                                    <?= $row['status'] ?>
                                </span>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>


        <script>
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
                        this.value.toLowerCase();

                    clearBtn.style.display =
                        value ? 'block' : 'none';

                    document
                        .querySelectorAll(
                            '.table-card tbody tr'
                        )
                        .forEach(row => {

                            row.style.display =
                                row.innerText
                                .toLowerCase()
                                .includes(value) ?
                                '' :
                                'none';

                        });

                });

            clearBtn.onclick = () => {

                searchInput.value = '';

                searchInput.dispatchEvent(
                    new Event('input')
                );

            };

            function applyDateFilter() {

                const from =
                    document.getElementById(
                        'fromDate'
                    ).value;

                const to =
                    document.getElementById(
                        'toDate'
                    ).value;

                window.location =
                    `attendance.php?from=${from}&to=${to}`;

            }

            function exportExcel() {

                const from = document.getElementById('fromDate').value;
                const to = document.getElementById('toDate').value;
                const search = document.getElementById('searchInput').value;

                window.open(
                    `api/export_excel.php?from=${from}&to=${to}&search=${encodeURIComponent(search)}`
                );
            }

            function resetFilter() {
                window.location =
                    'attendance.php';

            }

            function exportPDF() {

                const from = document.getElementById('fromDate').value;
                const to = document.getElementById('toDate').value;
                const search = document.getElementById('searchInput').value;

                window.open(
                    `api/export_pdf.php?from=${from}&to=${to}&search=${encodeURIComponent(search)}`
                );

            }
        </script>

</body>

</html>