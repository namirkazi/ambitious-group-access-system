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

$employees = $pdo->query("
SELECT *
FROM employees
WHERE active=1
ORDER BY id ASC
")->fetchAll();

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

        .header-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
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

<body>

    <header>
        <div class="logo">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
                <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
            </svg>
            <h1>Ambitious<span>Group</span> — Employees</h1>
        </div>
        <div style="
display:flex;
gap:.75rem;
">

            <a href="attendance.php">

                Attendance History

            </a>
            <a href="logout.php">

                ⏻ Logout

            </a>

        </div>
    </header>

    <div class="container">
        <?php

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

        $absentEmployees =
            $totalEmployees
            -
            $presentEmployees;

        ?>
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

            <div class="stat-card red">

                <div class="label">

                    Absent

                </div>

                <div class="value">

                    <?= $absentEmployees ?>

                </div>

            </div>

        </div>
        <div class="table-card">

            <div class="card-header">

                <h2>

                    Employees

                </h2>

                <div class="header-actions">
                    <a class="checkout-btn" href="employee_add.php">
                        + Add Employee
                    </a>

                    <a class="checkout-btn" href="api/export_employees.php">
                        Export Employees
                    </a>
                </div>
            </div>

            <table>

                <thead>

                    <tr>

                        <th>SR</th>

                        <th>Name</th>

                        <th>Department</th>

                        <th>Designation</th>

                        <th>Actions</th>

                    </tr>

                </thead>

                <tbody>
                    <?php $sr = 1; ?>
                    <?php foreach ($employees as $emp): ?>

                        <tr>

                            <td>

                                <?= $sr++ ?>

                            </td>

                            <td>

                                <div class="visitor-cell">

                                    <?php if (!empty($emp['photo_path'])): ?>

                                        <img src="<?= UPLOAD_URL . htmlspecialchars($emp['photo_path']) ?>" class="avatar">

                                    <?php else: ?>

                                        <div class="avatar-placeholder">

                                            <?= strtoupper(substr($emp['full_name'], 0, 1)) ?>

                                        </div>

                                    <?php endif; ?>

                                    <div>

                                        <div class="visitor-name">

                                            <?= htmlspecialchars(
                                                $emp['title'] . ' ' . $emp['full_name']
                                            ) ?>

                                        </div>

                                        <div class="visitor-phone">

                                            <?= htmlspecialchars($emp['phone']) ?>

                                        </div>

                                    </div>

                                </div>

                            </td>

                            <td>

                                <?= htmlspecialchars($emp['department']) ?>

                            </td>

                            <td>

                                <?= htmlspecialchars($emp['designation']) ?>

                            </td>

                            <td>
                                <button class="checkout-btn"
                                    onclick="window.location='view_employee.php?id=<?= $emp['id'] ?>'">

                                    View

                                </button>
                                <button class="checkout-btn"
                                    onclick="window.location='edit_employee.php?id=<?= $emp['id'] ?>'">

                                    Edit

                                </button>

                                <button class="checkout-btn" style="background:var(--danger)" onclick="confirmDelete(
<?= $emp['id'] ?>
)">

                                    Delete

                                </button>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>
            </table>
            <script>
                function confirmDelete(id) {

                    if (

                        confirm(
                            'Are you sure you want to delete this employee?\n\nThis employee will be hidden but attendance records will be preserved.'
                        )

                    ) {

                        window.location =
                            'api/delete_employee.php?id=' +
                            id;

                    }

                }
            </script>
        </div>