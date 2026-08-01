<?php

session_start();
if (
    !isset($_SESSION['admin_logged_in'])
) {

    header("Location: login.php");

    exit();
}

if ($_SESSION['role'] != 'hr') {

    header("Location: admin.php");

    exit();
}
require_once 'includes/config.php';

$pdo = getDB();

$id = $_GET['id'];

$stmt = $pdo->prepare("
SELECT *
FROM employees
WHERE id=?
");

$stmt->execute([$id]);

$employee = $stmt->fetch();

if (!$employee) {

    die('ERROR 404 - Employee not found');
}

/* Default calendar to current month/year unless overridden */

$month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('n');
$year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');

if ($month < 1 || $month > 12) {
    $month = (int) date('n');
}

/* Pull this month's attendance for the initial render */

$attStmt = $pdo->prepare("
SELECT attendance_date, check_in, check_out, total_hours, status
FROM employee_attendance
WHERE employee_id = ?
AND YEAR(attendance_date) = ?
AND MONTH(attendance_date) = ?
");

$attStmt->execute([$id, $year, $month]);

$attendanceRows = $attStmt->fetchAll();

$attendanceMap = [];

foreach ($attendanceRows as $row) {

    $attendanceMap[$row['attendance_date']] = [
        'status' => $row['status'],
        'check_in' => $row['check_in'],
        'check_out' => $row['check_out'],
        'total_hours' => $row['total_hours'],
    ];
}
// Count present days
$presentDays = count($attendanceRows);

// Calculate working days (excluding Sundays)
$workingDays = 0;

$daysInMonth = cal_days_in_month(
    CAL_GREGORIAN,
    $month,
    $year
);

for ($d = 1; $d <= $daysInMonth; $d++) {

    $date = sprintf(
        '%04d-%02d-%02d',
        $year,
        $month,
        $d
    );

    if (date('w', strtotime($date)) != 0) {
        $workingDays++;
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
        .employee-profile {
            display: grid;
            grid-template-columns: 320px minmax(0, 1fr);
            gap: 2rem;
            align-items: start;
        }

        .left-column {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .right-column {
            display: flex;
            flex-direction: column;
        }

        .profile-card {

            background: var(--surface);

            border: 1px solid var(--border);

            border-radius: 14px;

            padding: 1.5rem;

        }

        .section-card {

            background: var(--surface);

            border: 1px solid var(--border);

            border-radius: 14px;

            padding: 1.75rem;

            margin-bottom: 1.5rem;

        }

        .section-title {

            font-size: 1rem;

            font-weight: 700;

            margin-bottom: 1.25rem;

            color: var(--accent2);

        }

        .profile-photo {

            width: 180px;

            height: 180px;

            border-radius: 18px;

            object-fit: cover;

            border: 3px solid var(--border);

            display: block;

            margin: auto;

        }

        .document-image {

            width: 100%;

            max-width: 550px;

            display: block;

            border-radius: 12px;

            border: 2px solid var(--border);

        }

        .document-preview {

            margin-top: 20px;

        }

        .employee-photo {

            text-align: center;

        }

        .employee-photo h3 {

            margin-top: 1rem;

        }

        .employee-name {

            margin-top: 20px;

            font-size: 1.4rem;

            font-weight: 700;

        }

        .employee-designation {

            color: var(--accent2);

            margin-top: 8px;

            font-size: .95rem;

        }

        .employee-department {

            display: inline-block;

            margin-top: 8px;

            padding: 6px 14px;

            border-radius: 20px;

            background: var(--surface2);

            color: var(--muted);

            font-size: .8rem;

        }

        .status-badge {

            margin: 20px 0;

        }

        .action-btn {

            background: var(--surface2);

            color: var(--text);

            border: 1px solid var(--border);

            padding: 12px;

            border-radius: 10px;

            cursor: pointer;

            font-weight: 600;

            transition: .25s;

            text-decoration: none;

            display: inline-block;

            text-align: center;

        }

        .action-btn:hover {

            background: var(--accent);

            color: white;

        }

        .quick-info {

            margin-top: 25px;

        }

        .info-row {

            display: flex;

            justify-content: space-between;

            padding: 12px 0;

            border-bottom: 1px solid var(--border);

        }

        .info-label {

            color: var(--muted);

            font-size: .9rem;

        }

        .form-grid {

            display: grid;

            grid-template-columns: repeat(2, 1fr);

            gap: 1.25rem;

        }

        .form-group {

            display: flex;

            flex-direction: column;

        }

        .form-group label {

            margin-bottom: .45rem;

            font-size: .85rem;

            font-weight: 600;

            color: var(--muted);

        }

        .form-group input,
        .form-group select {

            width: 100%;

            background: var(--surface2);

            color: var(--text);

            border: 1px solid var(--border);

            border-radius: 10px;

            padding: .8rem 1rem;

            font-size: .95rem;

            transition: .2s;

        }

        /* Disabled inputs keep the identical look to the edit page,
           just visually inert instead of greyed out */

        .form-group input:disabled,
        .form-group select:disabled {

            opacity: 1;

            color: var(--text);

            cursor: default;

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

        h2 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text);
        }

        .checkout-btn {

            display: block;

            width: 220px;

            margin: 2rem auto 0;

            padding: 14px 24px;

            background: linear-gradient(135deg,
                    var(--accent),
                    var(--accent2));

            color: #fff;

            border: none;

            border-radius: 10px;

            font-size: .95rem;

            font-weight: 700;

            cursor: pointer;

            transition: .25s;

        }

        .checkout-btn:hover {

            transform: translateY(-2px);

        }

        .checkout-btn:active {

            transform: scale(.98);

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

        .badge.in {
            background: rgba(34, 197, 94, .15);
            color: var(--success);
        }

        .badge.out {
            background: rgba(148, 163, 184, .1);
            color: var(--muted);
        }

        /* Top action bar */

        .top-actions {

            display: flex;

            justify-content: flex-end;

            gap: .75rem;

            margin-bottom: 1.5rem;

        }

        .top-actions .action-btn {

            width: auto;

            padding: .7rem 1.4rem;

        }

        .top-actions .edit-link {

            background: var(--accent);

            color: #fff;

            border: none;

        }

        .top-actions .edit-link:hover {

            background: var(--accent2);

        }

        /* Calendar */

        .calendar-header {

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 1.25rem;

        }

        .calendar-nav {

            display: flex;

            align-items: center;

            gap: 1rem;

        }

        .calendar-nav button {

            background: var(--surface2);

            color: var(--text);

            border: 1px solid var(--border);

            border-radius: 8px;

            width: 34px;

            height: 34px;

            cursor: pointer;

            font-size: 1rem;

            transition: .2s;

        }

        .calendar-nav button:hover {

            background: var(--accent);

            color: #fff;

        }

        .calendar-month-label {

            font-weight: 700;

            font-size: .9rem;
            min-width: auto;

            text-align: center;

        }

        .calendar-legend {

            display: flex;

            gap: 1.25rem;

            font-size: .8rem;

            color: var(--muted);

        }

        .legend-dot {

            display: inline-block;

            width: 10px;

            height: 10px;

            border-radius: 50%;

            margin-right: 6px;

        }

        .legend-dot.present {
            background: var(--success);
        }

        .legend-dot.absent {
            background: var(--danger);
        }

        .legend-dot.none {
            background: var(--muted);
            opacity: .4;
        }

        .attendance-summary {

            margin-top: 14px;

            font-size: .9rem;

            font-weight: 600;

            color: var(--text);

        }

        .attendance-summary strong {

            color: var(--success);

        }

        .calendar-grid {

            display: grid;

            grid-template-columns: repeat(7, 1fr);

            gap: 5px;
        }

        .calendar-weekday {

            text-align: center;

            font-size: .75rem;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: .05em;

            color: var(--muted);

            padding-bottom: 6px;

        }

        .calendar-day {

            aspect-ratio: 1;

            border-radius: 10px;

            border: 1px solid var(--border);

            background: var(--surface2);

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: center;

            font-size: .85rem;

            font-weight: 600;

            cursor: default;

            position: relative;

            transition: .15s;

        }

        .calendar-day.empty {

            background: transparent;

            border: none;

        }

        .calendar-day.present {

            background: rgba(34, 197, 94, .18);

            border-color: rgba(34, 197, 94, .4);

            color: var(--success);

            cursor: pointer;

        }

        .calendar-day.absent {

            background: rgba(239, 68, 68, .18);

            border-color: rgba(239, 68, 68, .4);

            color: var(--danger);

            cursor: pointer;

        }

        .calendar-day.present:hover,
        .calendar-day.absent:hover {

            transform: translateY(-2px);

            filter: brightness(1.15);

        }

        .calendar-day.today {

            outline: 2px solid var(--accent2);

            outline-offset: 2px;

        }

        .calendar-day .day-num {

            font-size: .85rem;

        }

        .calendar-day .day-dot {

            width: 6px;

            height: 6px;

            border-radius: 50%;

            margin-top: 4px;

            background: currentColor;

        }

        /* Day detail modal */

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

            width: 380px;

            background: var(--surface);

            border: 1px solid var(--border);

            border-radius: 12px;

            padding: 1.5rem;

        }

        .modal-box h3 {

            margin-bottom: 1rem;

            color: var(--accent2);

        }

        .modal-actions {

            display: flex;

            gap: .75rem;

            margin-top: 1.25rem;

        }

        @media (max-width: 900px) {
            .employee-profile {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {

            .container {
                padding: 1rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .calendar-legend {
                flex-wrap: wrap;
                gap: .5rem 1rem;
            }
        }
    </style>
</head>

<body>

    <header>

        <div class="logo">

            <h1>

                Ambitious<span>Group</span> — Employees

            </h1>

        </div>
        <a href="employees.php" class="action-btn">

            ← Back to Employees

        </a>
    </header>

    <div class="container">

        <div class="top-actions">

            <a href="api/export_employee_details.php?id=<?= (int) $employee['id'] ?>" class="action-btn">

                ⬇ Export to Excel

            </a>

            <a href="edit_employee.php?id=<?= (int) $employee['id'] ?>" class="action-btn edit-link">

                ✎ Edit Employee

            </a>

        </div>

        <div class="employee-profile">
            <div class="left-column">
                <!-- LEFT SIDE -->

                <div class="profile-card">

                    <div class="employee-photo">

                        <img src="<?= !empty($employee['photo_path'])
                                        ? UPLOAD_URL . $employee['photo_path']
                                        : 'assets/images/default-avatar.png'; ?>" class="profile-photo" id="photoPreview">

                        <h2 class="employee-name">
                            <?= htmlspecialchars($employee['full_name']) ?>
                        </h2>

                        <p class="employee-designation">
                            <?= htmlspecialchars($employee['designation'] ?? 'Not Assigned') ?>
                        </p>

                        <span class="employee-department">
                            <?= htmlspecialchars($employee['department'] ?? 'Not Assigned') ?>
                        </span>

                        <div class="status-badge">

                            <span class="badge in">

                                ● Active

                            </span>

                        </div>

                    </div>

                    <hr>

                    <div class="quick-info">

                        <div class="info-row">

                            <span class="info-label">

                                Joined

                            </span>

                            <span>

                                <?= !empty($employee['joining_date']) ? date("d M Y", strtotime($employee['joining_date'])) : "-" ?>

                            </span>

                        </div>

                        <div class="info-row">

                            <span class="info-label">

                                Phone

                            </span>

                            <span>

                                <?= htmlspecialchars($employee['phone'] ?? '-') ?>

                            </span>

                        </div>

                        <div class="info-row">

                            <span class="info-label">

                                Email

                            </span>

                            <span>

                                <?= htmlspecialchars($employee['email'] ?? '-') ?>

                            </span>

                        </div>

                    </div>

                </div>
                <div class="section-card attendance-card">


                    <h3 class="section-title">
                        Attendance
                    </h3>

                    <div class="calendar-header">

                        <div class="calendar-nav">

                            <button type="button" id="prevMonthBtn" aria-label="Previous month">‹</button>

                            <span class="calendar-month-label" id="monthLabel"></span>

                            <button type="button" id="nextMonthBtn" aria-label="Next month">›</button>

                        </div>

                        <div class="calendar-legend">

                            <span><span class="legend-dot present"></span>Present</span>

                            <span><span class="legend-dot none"></span>No record</span>

                        </div>

                    </div>

                    <div class="calendar-grid" id="calendarGrid">

                        <!-- populated by JS -->

                    </div>
                    <div class="attendance-summary">

                        Attendance:

                        <strong id="presentDaysCount">
                            <?= $presentDays ?>
                        </strong>

                        /

                        <strong id="workingDaysCount">
                            <?= $workingDays ?>
                        </strong>

                        working days

                    </div>

                </div>
            </div>
            <!-- RIGHT SIDE -->

            <div>

                <div class="section-card">

                    <h3 class="section-title">
                        Personal Information
                    </h3>

                    <div class="form-grid">

                        <div class="form-group">

                            <label>Title</label>

                            <select disabled>

                                <option selected><?= htmlspecialchars($employee['title'] ?? '-') ?></option>

                            </select>

                        </div>

                        <div class="form-group">

                            <label>Full Name</label>

                            <input type="text" disabled value="<?= htmlspecialchars($employee['full_name'] ?? '') ?>">

                        </div>

                        <div class="form-group">

                            <label>Legal Name</label>

                            <input type="text" disabled value="<?= htmlspecialchars($employee['legal_name'] ?? '') ?>">

                        </div>

                        <div class="form-group">

                            <label>Gender</label>

                            <select disabled>

                                <option selected><?= htmlspecialchars($employee['gender'] ?? '-') ?></option>

                            </select>

                        </div>

                        <div class="form-group">

                            <label>Date of Birth</label>

                            <input type="text" disabled
                                value="<?= !empty($employee['dob']) ? date("d M Y", strtotime($employee['dob'])) : '-' ?>">

                        </div>

                        <div class="form-group">

                            <label>Nationality</label>

                            <input type="text" disabled value="<?= htmlspecialchars($employee['nationality'] ?? '') ?>">

                        </div>

                    </div>

                </div>

                <div class="section-card">

                    <h3 class="section-title">

                        Contact Information

                    </h3>

                    <div class="form-grid">

                        <div class="form-group">

                            <label>Mobile Number</label>

                            <input type="text" disabled value="<?= htmlspecialchars($employee['phone'] ?? '') ?>">

                        </div>

                        <div class="form-group">

                            <label>Email Address</label>

                            <input type="text" disabled value="<?= htmlspecialchars($employee['email'] ?? '') ?>">

                        </div>

                    </div>

                </div>

                <div class="section-card">

                    <h3 class="section-title">

                        Employment Details

                    </h3>

                    <div class="form-grid">

                        <div class="form-group">

                            <label>Joining Date</label>

                            <input type="text" disabled
                                value="<?= !empty($employee['joining_date']) ? date("d M Y", strtotime($employee['joining_date'])) : '-' ?>">

                        </div>

                        <div class="form-group">

                            <label>Department</label>

                            <input type="text" disabled value="<?= htmlspecialchars($employee['department'] ?? '') ?>">

                        </div>

                        <div class="form-group">

                            <label>Designation</label>

                            <input type="text" disabled value="<?= htmlspecialchars($employee['designation'] ?? '') ?>">

                        </div>

                        <div class="form-group">

                            <label>Reporting Manager</label>

                            <select disabled>

                                <option>
                                    Coming Soon
                                </option>

                            </select>

                        </div>

                    </div>

                </div>

                <div class="section-card">

                    <h3 class="section-title">
                        Identity Documents
                    </h3>

                    <div class="form-grid">

                        <div class="form-group">

                            <label>Document Type</label>

                            <select disabled>

                                <option selected><?= htmlspecialchars($employee['document_type'] ?? '-') ?></option>

                            </select>

                        </div>

                        <div class="form-group">

                            <label>Document Number</label>

                            <input type="text" disabled
                                value="<?= htmlspecialchars($employee['document_number'] ?? '') ?>">

                        </div>

                        <div class="form-group">

                            <label>Issue Date</label>

                            <input type="text" disabled
                                value="<?= !empty($employee['document_issue_date']) ? date("d M Y", strtotime($employee['document_issue_date'])) : '-' ?>">

                        </div>

                        <div class="form-group">

                            <label>Expiry Date</label>

                            <input type="text" disabled
                                value="<?= !empty($employee['document_expiry_date']) ? date("d M Y", strtotime($employee['document_expiry_date'])) : '-' ?>">

                        </div>

                    </div>

                    <hr style="margin:30px 0;border-color:var(--border);">

                    <h3 class="section-title">

                        Current Document

                    </h3>

                    <div class="document-preview">

                        <?php if (!empty($employee['document_path'])): ?>

                            <?php

                            $extension = strtolower(pathinfo($employee['document_path'], PATHINFO_EXTENSION));

                            ?>

                            <?php if ($extension == "pdf"): ?>

                                <iframe src="<?= UPLOAD_URL . $employee['document_path'] ?>" width="100%" height="450"
                                    style="border:1px solid var(--border);border-radius:10px;">

                                </iframe>

                            <?php else: ?>

                                <img src="<?= UPLOAD_URL . $employee['document_path'] ?>" class="document-image">

                            <?php endif; ?>

                            <br><br>

                            <a href="<?= UPLOAD_URL . $employee['document_path'] ?>" target="_blank"
                                class="checkout-btn" style="display:inline-block;text-align:center;width:180px;">

                                👁 View Full

                            </a>

                            <a href="<?= UPLOAD_URL . $employee['document_path'] ?>" download class="checkout-btn"
                                style="display:inline-block;text-align:center;width:180px;margin-left:10px;">

                                ⬇ Download

                            </a>

                        <?php else: ?>

                            <p style="color:var(--muted);">

                                No document uploaded.

                            </p>

                        <?php endif; ?>

                    </div>

                </div>



            </div>

        </div>

    </div>

    <div class="modal-overlay" id="dayModal">

        <div class="modal-box">

            <h3 id="dayModalTitle">Attendance</h3>

            <div id="dayModalBody">

                <div class="info-row">
                    <span class="info-label">Status</span>
                    <span id="modalStatus">-</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Check In</span>
                    <span id="modalCheckIn">-</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Check Out</span>
                    <span id="modalCheckOut">-</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Total Hours</span>
                    <span id="modalHours">-</span>
                </div>

            </div>

            <div class="modal-actions">

                <button type="button" class="checkout-btn" style="margin:0;width:100%;" onclick="closeDayModal()">

                    Close

                </button>

            </div>

        </div>

    </div>

    <script>
        const EMPLOYEE_ID = <?= (int) $employee['id'] ?>;

        let currentMonth = <?= (int) $month ?>;
        let currentYear = <?= (int) $year ?>;
        let attendanceData = <?= json_encode($attendanceMap) ?>;

        const MONTH_NAMES = [
            "January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"
        ];

        function pad(n) {
            return n < 10 ? "0" + n : "" + n;
        }

        function renderCalendar() {

            document.getElementById("monthLabel").textContent =
                MONTH_NAMES[currentMonth - 1] + " " + currentYear;

            const grid = document.getElementById("calendarGrid");
            grid.innerHTML = "";

            ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"].forEach(d => {
                const el = document.createElement("div");
                el.className = "calendar-weekday";
                el.textContent = d;
                grid.appendChild(el);
            });

            const firstDay = new Date(currentYear, currentMonth - 1, 1).getDay();
            const daysInMonth = new Date(currentYear, currentMonth, 0).getDate();

            const today = new Date();
            const isCurrentMonth = today.getFullYear() === currentYear && (today.getMonth() + 1) === currentMonth;

            for (let i = 0; i < firstDay; i++) {
                const empty = document.createElement("div");
                empty.className = "calendar-day empty";
                grid.appendChild(empty);
            }

            for (let day = 1; day <= daysInMonth; day++) {

                const dateStr = currentYear + "-" + pad(currentMonth) + "-" + pad(day);
                const record = attendanceData[dateStr];

                const cell = document.createElement("div");
                cell.className = "calendar-day";

                if (record) {
                    cell.classList.add(record.status === "present" ? "present" : "absent");
                    cell.addEventListener("click", () => openDayModal(dateStr, record));
                }

                if (isCurrentMonth && day === today.getDate()) {
                    cell.classList.add("today");
                }

                const num = document.createElement("span");
                num.className = "day-num";
                num.textContent = day;
                cell.appendChild(num);

                if (record) {
                    const dot = document.createElement("span");
                    dot.className = "day-dot";
                    cell.appendChild(dot);
                }

                grid.appendChild(cell);
            }
        }

        function openDayModal(dateStr, record) {

            document.getElementById("dayModalTitle").textContent =
                "Attendance — " + dateStr;

            document.getElementById("modalStatus").textContent =
                record.status === "present" ? "Present" : "Absent";

            document.getElementById("modalCheckIn").textContent =
                record.check_in ? record.check_in : "-";

            document.getElementById("modalCheckOut").textContent =
                record.check_out ? record.check_out : "-";

            document.getElementById("modalHours").textContent =
                record.total_hours ? record.total_hours : "-";

            document.getElementById("dayModal").style.display = "flex";
        }

        function closeDayModal() {
            document.getElementById("dayModal").style.display = "none";
        }

        async function loadMonth(month, year) {

            try {

                const res = await fetch(
                    "api/get_attendance.php?employee_id=" + EMPLOYEE_ID +
                    "&month=" + month + "&year=" + year
                );

                if (!res.ok) throw new Error("Failed to load attendance");

                const data = await res.json();

                attendanceData = data.attendance;

                document.getElementById("presentDaysCount").textContent =
                    data.presentDays;

                document.getElementById("workingDaysCount").textContent =
                    data.workingDays;

                currentMonth = month;
                currentYear = year;

                renderCalendar();

            } catch (err) {

                console.error(err);
                alert("Could not load attendance for that month.");

            }
        }

        document.getElementById("prevMonthBtn").addEventListener("click", () => {

            let m = currentMonth - 1;
            let y = currentYear;

            if (m < 1) {
                m = 12;
                y -= 1;
            }

            loadMonth(m, y);
        });

        document.getElementById("nextMonthBtn").addEventListener("click", () => {

            let m = currentMonth + 1;
            let y = currentYear;

            if (m > 12) {
                m = 1;
                y += 1;
            }

            loadMonth(m, y);
        });

        document.getElementById("dayModal").addEventListener("click", (e) => {
            if (e.target.id === "dayModal") closeDayModal();
        });

        renderCalendar();
    </script>

</body>

</html>