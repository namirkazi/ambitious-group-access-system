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
?>

<head>
    <script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Visitor Management</title>
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicon/apple-touch-icon.png">

    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon/favicon-32x32.png">

    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon/favicon-16x16.png">

    <link rel="icon" href="assets/favicon/favicon.ico">

    <link rel="manifest" href="assets/favicon/site.webmanifest">
    <?php if (isset($_GET['updated'])): ?>

        <div id="toast">

            Employee updated successfully.

        </div>

    <?php endif; ?>
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

        .employee-profile {

            display: grid;

            grid-template-columns: 320px 1fr;

            gap: 2rem;

            align-items: start;

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

        .photo-buttons {

            display: flex;

            flex-direction: column;

            gap: 12px;

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

        .form-group input:focus,
        .form-group select:focus {

            outline: none;

            border-color: var(--accent);

            box-shadow: 0 0 0 3px rgba(108, 99, 255, .2);

        }

        .processing-overlay {

            position: absolute;

            inset: 0;

            background: rgba(0, 0, 0, .65);

            backdrop-filter: blur(4px);

            display: flex;

            flex-direction: column;

            justify-content: center;

            align-items: center;

            z-index: 999;

            pointer-events: all;

        }

        .loader {

            width: 60px;

            height: 60px;

            border: 6px solid rgba(255, 255, 255, .2);

            border-top: 6px solid #6c63ff;

            border-radius: 50%;

            animation: spin 1s linear infinite;

        }

        .processing-overlay p {

            margin-top: 18px;

            color: white;

            font-size: 16px;

            font-weight: 600;

        }

        @keyframes spin {

            from {

                transform: rotate(0deg);

            }

            to {

                transform: rotate(360deg);

            }

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

        .camera-box {

            width: 100%;

            aspect-ratio: 4/3;

            background: #000;

            border-radius: 12px;

            overflow: hidden;

            position: relative;

        }

        #video {

            width: 100%;

            height: 100%;

            object-fit: cover;

            transform: scaleX(-1);

        }

        #capturedPhoto {

            position: absolute;

            inset: 0;

        }

        #canvas {

            display: none;

        }

        .cam-controls {

            display: flex;

            gap: 10px;

            margin-top: 15px;

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

            @media(max-width:768px) {

                .form-grid {

                    grid-template-columns: 1fr;

                }

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

        <div class="form-card">

            <h2>Edit Employee</h2>

            <form action="api/update_employee.php" method="POST" enctype="multipart/form-data"
                onsubmit="return validateForm()">

                <input type="hidden" name="id" value="<?= $employee['id'] ?? ''?>">
                <input type="hidden" name="photo_data" id="photoData">
                <input type="hidden" name="face_descriptor" id="faceDescriptor">
                <div class="employee-profile">

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

                            <div class="photo-buttons">

                                <button type="button" class="action-btn" onclick="openCamera()">

                                    Take Photo

                                </button>

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

                    <!-- RIGHT SIDE -->

                    <div>

                        <div class="section-card">

                            <h3 class="section-title">
                                Personal Information
                            </h3>

                            <div class="form-grid">

                                <div class="form-group">

                                    <label>Title</label>

                                    <select name="title">

                                        <option value="Mr" <?= $employee['title'] == 'Mr' ? 'selected' : '' ?>>Mr</option>

                                        <option value="Mrs" <?= $employee['title'] == 'Mrs' ? 'selected' : '' ?>>Mrs
                                        </option>

                                        <option value="Ms" <?= $employee['title'] == 'Ms' ? 'selected' : '' ?>>Ms</option>

                                    </select>

                                </div>

                                <div class="form-group">

                                    <label>Full Name</label>

                                    <input type="text" name="full_name"
                                        value="<?= htmlspecialchars($employee['full_name'] ?? '') ?>">

                                </div>

                                <div class="form-group">

                                    <label>Legal Name</label>

                                    <input type="text" name="legal_name"
                                        value="<?= htmlspecialchars($employee['legal_name'] ?? '') ?>">

                                </div>

                                <div class="form-group">

                                    <label>Gender</label>

                                    <select name="gender">

                                        <option value="Male" <?= $employee['gender'] == 'Male' ? 'selected' : '' ?>>
                                            Male
                                        </option>

                                        <option value="Female" <?= $employee['gender'] == 'Female' ? 'selected' : '' ?>>
                                            Female
                                        </option>

                                        <option value="Other" <?= $employee['gender'] == 'Other' ? 'selected' : '' ?>>
                                            Other
                                        </option>

                                    </select>

                                </div>

                                <div class="form-group">

                                    <label>Date of Birth</label>

                                    <input type="date" name="dob" value="<?= $employee['dob'] ?? ''?>">

                                </div>

                                <div class="form-group">

                                    <label>Nationality</label>

                                    <input type="text" name="nationality"
                                        value="<?= htmlspecialchars($employee['nationality'] ?? '') ?>">

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

                                    <input type="tel" id="phone" name="phone" maxlength="16"
                                        value="<?= htmlspecialchars($employee['phone'] ?? '') ?>"
                                        oninput="formatPhone(this)">

                                </div>

                                <div class="form-group">

                                    <label>Email Address</label>

                                    <input type="email" name="email"
                                        value="<?= htmlspecialchars($employee['email'] ?? '') ?>">

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

                                    <input type="date" name="joining_date"
                                        value="<?= $employee['joining_date'] ?? '' ?>">

                                </div>

                                <div class="form-group">

                                    <label>Department</label>

                                    <input type="text" name="department"
                                        value="<?= htmlspecialchars($employee['department'] ?? '') ?>">

                                </div>

                                <div class="form-group">

                                    <label>Designation</label>

                                    <input type="text" name="designation"
                                        value="<?= htmlspecialchars($employee['designation'] ?? '') ?>">

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

                                    <select id="documentType" name="document_type">

                                        <option value="Emirates ID" <?= $employee['document_type'] == 'Emirates ID' ? 'selected' : '' ?>>
                                            Emirates ID
                                        </option>

                                        <option value="Passport" <?= $employee['document_type'] == 'Passport' ? 'selected' : '' ?>>
                                            Passport
                                        </option>

                                    </select>

                                </div>

                                <div class="form-group">

                                    <label id="documentNumberLabel">

                                        Document Number

                                    </label>

                                    <input type="text" id="documentNumber" name="document_number"
                                        value="<?= htmlspecialchars($employee['document_number'] ?? '') ?>">

                                </div>

                                <div class="form-group">

                                    <label>Issue Date</label>

                                    <input type="date" name="document_issue_date"
                                        value="<?= $employee['document_issue_date'] ?? '-' ?>">

                                </div>

                                <div class="form-group">

                                    <label>Expiry Date</label>

                                    <input type="date" name="document_expiry_date"
                                        value="<?= $employee['document_expiry_date'] ?? '-' ?>">

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

                                <br><br>

                                <div class="form-group">

                                    <label>

                                        Replace Document

                                    </label>

                                    <input type="file" name="employee_document" accept=".pdf,.jpg,.jpeg,.png">

                                </div>

                            </div>

                        </div>

                        <div style="text-align:right;margin-top:30px;">

                            <button type="submit" class="checkout-btn">

                                Update Employee

                            </button>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>
    <div class="modal-overlay" id="cameraModal">

        <div class="modal-box">

            <h3>Capture Employee Photo</h3>

            <div class="camera-box">

                <video id="video" autoplay playsinline>
                </video>

                <img id="capturedPhoto" style="display:none;width:100%;height:100%;object-fit:cover;">
                <div id="processingOverlay" class="processing-overlay" style="display:none;">

                    <div class="loader"></div>

                    <p>Processing Face...</p>

                </div>
                <canvas id="canvas"></canvas>

            </div>

            <div class="cam-controls">

                <button type="button" class="checkout-btn" id="captureBtn" onclick="capturePhoto()">

                    Capture

                </button>

                <button type="button" class="checkout-btn" id="retakeBtn" onclick="retakePhoto()" style="display:none">

                    Retake

                </button>

                <button type="button" id="closeCameraBtn" class="checkout-btn" onclick="closeCamera()">

                    Close

                </button>

            </div>

        </div>

    </div>
    <script>
        let capturedPhotoData = null;

        let currentFaceDescriptor = null;

        let faceModelsLoaded = false;

        const video = document.getElementById("video");

        const canvas = document.getElementById("canvas");

        const photo = document.getElementById("capturedPhoto");

        async function capturePhoto() {

            const captureBtn = document.getElementById("captureBtn");
            const retakeBtn = document.getElementById("retakeBtn");

            captureBtn.disabled = true;
            retakeBtn.disabled = true;

            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;

            const ctx = canvas.getContext("2d");

            ctx.translate(canvas.width, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(video, 0, 0);
            ctx.setTransform(1, 0, 0, 1, 0, 0);

            capturedPhotoData = canvas.toDataURL("image/jpeg", 0.85);

            /* Freeze frame */

            photo.src = capturedPhotoData;
            photo.style.display = "block";

            video.pause();
            video.style.visibility = "hidden";

            /* Grey out UI */


            document.getElementById("processingOverlay").style.display = "flex";

            document.querySelector(".modal-box").style.pointerEvents = "none";

            /* Allow browser to paint spinner */

            await new Promise(resolve => setTimeout(resolve, 50));

            /* Face detection */
            document.getElementById("closeCameraBtn").disabled = true;
            const detection = await faceapi
                .detectSingleFace(
                    canvas,
                    new faceapi.TinyFaceDetectorOptions({
                        inputSize: 320,
                        scoreThreshold: 0.2
                    })
                )
                .withFaceLandmarks()
                .withFaceDescriptor();

            if (!detection) {

                document.getElementById("processingOverlay").style.display = "none";

                document.querySelector(".modal-box").style.pointerEvents = "";

                alert("No face detected. Please try again.");

                retakePhoto();

                return;
            }

            currentFaceDescriptor = Array.from(detection.descriptor);

            document.getElementById("photoPreview").src = capturedPhotoData;

            document.getElementById("photoData").value = capturedPhotoData;

            document.getElementById("faceDescriptor").value =
                JSON.stringify(currentFaceDescriptor);

            closeCamera();
        }

        async function loadFaceModels() {

            await faceapi.nets.tinyFaceDetector.loadFromUri('./models');

            await faceapi.nets.faceLandmark68Net.loadFromUri('./models');

            await faceapi.nets.faceRecognitionNet.loadFromUri('./models');

            faceModelsLoaded = true;

            console.log('Face models loaded');
        }

        async function waitForFaceAPI() {

            while (typeof faceapi === 'undefined') {

                await new Promise(resolve =>
                    setTimeout(resolve, 100)
                );

            }

        }
        function retakePhoto() {

            capturedPhotoData = null;
            currentFaceDescriptor = null;

            document.getElementById("photoData").value = "";
            document.getElementById("faceDescriptor").value = "";

            photo.style.display = "none";

            video.style.visibility = "visible";

            document.getElementById("processingOverlay").style.display = "none";

            document.querySelector(".modal-box").style.pointerEvents = "";

            document.getElementById("captureBtn").disabled = false;
            document.getElementById("retakeBtn").disabled = false;
            document.getElementById("closeCameraBtn").disabled = false;

            /* Resume live camera */

            video.play();

        }
        function showToast(message) {

            const toast = document.getElementById("toast");

            if (!toast) return;

            toast.textContent = message;

            toast.style.display = "block";

            setTimeout(() => {

                toast.style.display = "none";

            }, 3000);

        }

        async function openCamera() {
            video.style.display = "block";

            photo.style.display = "none";

            document.getElementById("processingOverlay").style.display = "none";

            document.getElementById("captureBtn").disabled = false;

            document.getElementById("retakeBtn").disabled = false;

            document.getElementById("closeCameraBtn").disabled = false;

            document.getElementById("cameraModal").style.display = "flex";

            photo.style.display = "none";

            document.getElementById("captureBtn").style.display = "";

            document.getElementById("retakeBtn").style.display = "none";

            if (!faceModelsLoaded) {

                await waitForFaceAPI();

                await loadFaceModels();

            }

            const stream = await navigator.mediaDevices.getUserMedia({

                video: { facingMode: "user" }

            });

            video.srcObject = stream;

        }

        function closeCamera() {

            document.getElementById("processingOverlay").style.display = "none";

            document.querySelector(".modal-box").style.pointerEvents = "";

            document.getElementById("cameraModal").style.display = "none";

            photo.style.display = "none";

            video.style.visibility = "visible";

            video.play();

            if (video.srcObject) {

                video.srcObject.getTracks().forEach(track => track.stop());

                video.srcObject = null;
            }
        }

        function formatPhone(input) {

            let digits = input.value.replace(/\D/g, '');

            if (digits.startsWith('971'))
                digits = digits.substring(3);

            if (digits.startsWith('0'))
                digits = digits.substring(1);

            digits = digits.substring(0, 9);

            let formatted = "+971 ";

            if (digits.length >= 2) {

                formatted += digits.substring(0, 2);

                if (digits.length > 2) {

                    formatted += " " + digits.substring(2);

                }

            }
            else {

                formatted += digits;

            }

            input.value = formatted;

        }

        window.addEventListener("DOMContentLoaded", () => {

            const phone = document.getElementById("phone");

            if (phone.value.trim() === "") {

                phone.value = "+971 ";

            }

            phone.addEventListener("keydown", function (e) {

                if (

                    this.selectionStart <= 5 &&

                    (e.key === "Backspace" || e.key === "Delete")

                ) {

                    e.preventDefault();

                }

            });

        });
        function validateForm() {

            const phone = document.getElementById("phone");

            const regex = /^\+971 5[0-6] \d{7}$/;

            if (!regex.test(phone.value)) {

                alert("Please enter a valid UAE mobile number.");

                phone.focus();

                return false;

            }

            return true;

        }
        window.addEventListener("load", () => {

            const params = new URLSearchParams(window.location.search);

            if (params.has("updated")) {

                showToast("Employee updated successfully.");

                history.replaceState(
                    {},
                    "",
                    "edit_employee.php?id=<?= $employee['id'] ?>"
                );

            }

        });
    </script>
</body>

</html>