<?php

session_start();
if(
!isset($_SESSION['admin_logged_in'])
){

    header("Location: login.php");

    exit();

}

if($_SESSION['role']!='hr'){

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

if(!$employee){

    die('Employee not found');

}
?>

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Visitor Management</title>
    <link rel="apple-touch-icon"
sizes="180x180"
href="assets/favicon/apple-touch-icon.png">

<link rel="icon"
type="image/png"
sizes="32x32"
href="assets/favicon/favicon-32x32.png">

<link rel="icon"
type="image/png"
sizes="16x16"
href="assets/favicon/favicon-16x16.png">

<link rel="icon"
href="assets/favicon/favicon.ico">

<link rel="manifest"
href="assets/favicon/site.webmanifest">
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

            <h1>

                Ambitious<span>Group</span> — Employees

            </h1>

        </div>

        <a href="attendance.php">

            Attendance History

        </a>

    </header>

    <div class="container">
       <form action="api/update_employee.php" method="POST">

<input
type="hidden"
name="id"
value="<?= $employee['id'] ?>">
<select name="title">

<option value="Mr"
<?= $employee['title']=='Mr'?'selected':'' ?>>

Mr

</option>

<option value="Mrs"
<?= $employee['title']=='Mrs'?'selected':'' ?>>

Mrs

</option>

<option value="Ms"
<?= $employee['title']=='Ms'?'selected':'' ?>>

Ms

</option>

</select>
<input
type="text"
name="full_name"
value="<?= htmlspecialchars($employee['full_name']) ?>"
required>
<input
type="text"
name="phone"
value="<?= htmlspecialchars($employee['phone']) ?>">
<input
type="text"
name="department"
value="<?= htmlspecialchars($employee['department']) ?>">
<input
type="text"
name="designation"
value="<?= htmlspecialchars($employee['designation']) ?>">
<input
type="email"
name="email"
value="<?= htmlspecialchars($employee['email']) ?>">

<br><br>

<button
type="submit"
class="checkout-btn">

Update Employee

</button>

</form>

</div>

</body>

</html>