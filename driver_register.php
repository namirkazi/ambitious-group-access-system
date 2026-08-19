<?php

session_start();

if (
    !isset($_SESSION['admin_logged_in'])
) {

    header("Location: login.php");

    exit();
}

// NOTE: employee_add.php restricts this page to role === 'hr'.
// Adjust the role check below to whichever role should manage drivers
// (e.g. 'admin' or 'fleet') in your system.
if ($_SESSION['role'] != 'hr') {

    header("Location: admin.php");

    exit();
}

require_once 'includes/config.php';
$pdo = getDB();

// Populate the "Assigned Vehicle" dropdown from a `vehicles` table if one
// exists. The `drivers` table shown in phpMyAdmin does not currently have
// a vehicle_id column — see the note in api/save_driver.php about adding
// one (or a driver_vehicle mapping table) if you want this to persist.

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Registration</title>
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicon/apple-touch-icon.png">

    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon/favicon-32x32.png">

    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon/favicon-16x16.png">

    <link rel="icon" href="assets/favicon/favicon.ico">

    <link rel="manifest" href="assets/favicon/site.webmanifest">
    <meta name="theme-color" content="#6c63ff">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="VisitorHub">
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

        header a {
            background: var(--accent);
            color: #fff;
            padding: .5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: .875rem;
            font-weight: 600;
        }

        :root {
            --bg: #0f1117;
            --surface: #1a1d27;
            --surface2: #222538;
            --surface3: #2a2d42;
            --accent: #6c63ff;
            --accent2: #a78bfa;
            --success: #22c55e;
            --warn: #f59e0b;
            --danger: #ef4444;
            --text: #e2e8f0;
            --muted: #94a3b8;
            --border: #2d3148;
            --radius: 12px;
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
            display: grid;
            grid-template-rows: auto 1fr auto;
        }

        /* HEADER */
        header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 1.1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .logo-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-icon svg {
            color: #fff;
        }

        .logo h1 {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -.03em;
        }

        .logo h1 span {
            color: var(--accent2);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .clock {
            font-size: .875rem;
            color: var(--muted);
            font-variant-numeric: tabular-nums;
        }

        .admin-btn {
            background: var(--surface2);
            color: var(--text);
            padding: .5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: .8rem;
            font-weight: 600;
            border: 1px solid var(--border);
            transition: border-color .2s;
        }

        .admin-btn:hover {
            border-color: var(--accent2);
            color: var(--accent2);
        }

        /* MAIN LAYOUT */
        main {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            height: calc(100vh - 64px);
        }

        /* LEFT PANEL — Camera + ID */
        .left-panel {
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            gap: 1.5rem;
        }

        .camera-section {
            width: 100%;
            max-width: 380px;
        }

        .camera-label {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            margin-bottom: .75rem;
        }

        .camera-box {
            position: relative;
            width: 100%;
            aspect-ratio: 4/3;
            background: #000;
            border-radius: var(--radius);
            overflow: hidden;
            border: 2px solid var(--border);
        }

        #video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transform: scaleX(-1);
        }

        #canvas {
            display: none;
        }

        #capturedPhoto {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }

        .camera-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, transparent 60%, rgba(0, 0, 0, .5));
            pointer-events: none;
        }

        /* Corner guides */
        .corner {
            position: absolute;
            width: 20px;
            height: 20px;
            border-color: var(--accent2);
            border-style: solid;
            border-width: 0;
        }

        .corner.tl {
            top: 12px;
            left: 12px;
            border-top-width: 2px;
            border-left-width: 2px;
            border-radius: 3px 0 0 0;
        }

        .corner.tr {
            top: 12px;
            right: 12px;
            border-top-width: 2px;
            border-right-width: 2px;
            border-radius: 0 3px 0 0;
        }

        .corner.bl {
            bottom: 12px;
            left: 12px;
            border-bottom-width: 2px;
            border-left-width: 2px;
            border-radius: 0 0 0 3px;
        }

        .corner.br {
            bottom: 12px;
            right: 12px;
            border-bottom-width: 2px;
            border-right-width: 2px;
            border-radius: 0 0 3px 0;
        }

        .cam-controls {
            display: flex;
            gap: .75rem;
            margin-top: .75rem;
        }

        .btn {
            flex: 1;
            padding: .7rem;
            border: none;
            border-radius: 8px;
            font-family: var(--font);
            font-size: .875rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity .2s, transform .1s;
        }

        .btn:active {
            transform: scale(.98);
        }

        .btn-capture {
            background: var(--accent);
            color: #fff;
        }

        .btn-retake {
            background: var(--surface2);
            color: var(--muted);
            border: 1px solid var(--border);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: #fff;
            width: 100%;
            padding: .9rem;
            font-size: 1rem;
        }

        .btn:disabled {
            opacity: .4;
            cursor: not-allowed;
        }

        /* RIGHT PANEL — Form */
        .right-panel {
            overflow-y: auto;
            padding: 0.7rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .right-panel h2 {
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: -.03em;
        }

        .right-panel h2 span {
            color: var(--accent2);
        }

        /* Form */
        .form-section {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.25rem;
        }

        .form-section h3 {
            font-size: .85rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 1rem;
            padding-bottom: .5rem;
            border-bottom: 1px solid var(--border);
        }

        .form-row {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));

            gap: .75rem;

        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: .4rem;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-size: .75rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .form-group label .req {
            color: var(--danger);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            background: var(--surface2);
            border: 1.5px solid var(--border);
            color: var(--text);
            padding: .7rem .9rem;
            border-radius: 8px;
            font-size: .9rem;
            font-family: var(--font);
            transition: border-color .2s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent);
        }

        .form-group select option {
            background: var(--surface2);
        }

        .form-group input:disabled,
        .form-group select:disabled {
            opacity: .7;
            cursor: not-allowed;
        }

        /* Status pill toggle */
        .status-toggle {
            display: flex;
            gap: .5rem;
        }

        .status-toggle label {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            padding: .7rem .9rem;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            background: var(--surface2);
            font-size: .85rem;
            font-weight: 600;
            color: var(--muted);
            cursor: pointer;
            text-transform: none;
            letter-spacing: 0;
            transition: border-color .2s, color .2s, background .2s;
        }

        .status-toggle input {
            display: none;
        }

        .status-toggle input:checked+span {
            color: inherit;
        }

        .status-toggle label:has(input:checked) {
            border-color: var(--accent);
            color: var(--text);
        }

        .status-toggle label.active-option:has(input:checked) {
            background: rgba(34, 197, 94, .12);
            border-color: var(--success);
            color: var(--success);
        }

        .status-toggle label.inactive-option:has(input:checked) {
            background: rgba(239, 68, 68, .12);
            border-color: var(--danger);
            color: var(--danger);
        }

        /* Success screen */
        #successScreen {
            display: none;
            text-align: center;
            padding: 3rem 2rem;
            animation: slideIn .4s ease;
        }

        .success-icon {
            width: 72px;
            height: 72px;
            background: rgba(34, 197, 94, .15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            border: 2px solid var(--success);
        }

        .btn-new {
            background: var(--surface2);
            color: var(--text);
            border: 1px solid var(--border);
            padding: .8rem 2rem;
            border-radius: 8px;
            font-family: var(--font);
            font-size: .9rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: .5rem;
        }

        /* Spinner */
        .spinner {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, .3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .6s linear infinite;
            vertical-align: middle;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        footer {
            text-align: center;
            padding: .75rem;
            font-size: .75rem;
            color: var(--muted);
            border-top: 1px solid var(--border);
            background: var(--surface);
        }

        @media (max-width: 768px) {
            main {
                grid-template-columns: 1fr;
                height: auto;
            }

            .left-panel {
                padding: 1.5rem;
                border-right: none;
                border-bottom: 1px solid var(--border);
            }

            .right-panel {
                padding: 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <header>
        <div class="logo">
            <div class="logo-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path d="M5 17h14M6 17V9a2 2 0 0 1 2-2h1l2-3h2l2 3h1a2 2 0 0 1 2 2v8" />
                    <circle cx="7.5" cy="17" r="1.8" />
                    <circle cx="16.5" cy="17" r="1.8" />
                </svg>
            </div>
            <h1>Ambitious Group</h1>
        </div>
        <div class="header-right">
            <a href="driver_trips.php">

                ← Back

            </a>
            <div class="clock" id="clock"></div>
        </div>
    </header>

    <main>
        <!-- LEFT: Camera -->
        <div class="left-panel">
            <div class="camera-section">
                <div class="camera-label"> Driver Photo Capture</div>
                <div class="camera-box">
                    <video id="video" autoplay playsinline></video>
                    <img id="capturedPhoto" alt="Captured">
                    <canvas id="canvas"></canvas>
                    <div class="camera-overlay"></div>
                    <div class="corner tl"></div>
                    <div class="corner tr"></div>
                    <div class="corner bl"></div>
                    <div class="corner br"></div>
                </div>
                <div class="cam-controls">
                    <button class="btn btn-capture" id="captureBtn" onclick="capturePhoto()"> Scan Face</button>
                    <button class="btn btn-retake" id="retakeBtn" onclick="retakePhoto()"
                        style="display:none">Retake</button>
                </div>
            </div>
        </div>

        <!-- RIGHT: Form -->
        <div class="right-panel">
            <div id="mainForm">
                <h2>Driver <span>Registration</span></h2>

                <div class="form-section">

                    <h3>Driver Information</h3>

                    <div class="form-row">

                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" id="fullName" maxlength="100" pattern="[A-Za-z ]+"
                                placeholder="Ahmed Al Rashid">
                        </div>

                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" id="phoneDisplay" oninput="formatPhone(this)"
                                placeholder="+971 5X XXXXXXX" maxlength="16">
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" id="email" maxlength="100" placeholder="ahmed@ambitiousgroup.com">
                        </div>

                        <div class="form-group">
                            <label>Emirates ID</label>
                            <input type="text" id="emiratesId" oninput="formatEmiratesId(this)"
                                placeholder="784-XXXX-XXXXXXX-X" maxlength="18">
                        </div>

                        <div class="form-group">
                            <label>Driving License Number</label>
                            <input type="text" id="licenseNumber" maxlength="30" placeholder="e.g. 1234567">
                        </div>

                        <div class="form-group">
                            <label>Driving License Expiry</label>
                            <input type="date" id="licenseExpiry">
                        </div>
                    </div>

                </div>
                <br>
                <button class="btn btn-submit" onclick="submitForm()" id="submitBtn">
                    Register Driver
                </button>
            </div>
            <!-- Success screen -->
            <div id="successScreen">
                <div class="success-icon">
                    <svg width="36" height="36" fill="none" stroke="var(--success)" stroke-width="2.5"
                        viewBox="0 0 24 24">
                        <polyline points="20 6 9 17 4 12" />
                    </svg>
                </div>
                <h2 style="font-size:1.6rem;margin-bottom:.5rem">Driver Added!</h2>
                <p style="color:var(--muted);margin-bottom:1rem" id="successName"></p>

                <p style="font-size:.875rem;color:var(--muted);margin-bottom:1rem">
                    Driver registration completed successfully.
                </p>
                <button class="btn btn-new" onclick="resetForm()">← Register Another Driver</button>
            </div>
        </div>
    </main>

    <footer>Ambitious Group — Fleet Management System &nbsp;|&nbsp; <?= date('d F Y') ?></footer>

    <script>
        // State
        let capturedPhotoData = null;
        let currentFaceDescriptor = null;
        let faceModelsLoaded = false;
        let processingFace = false;
        let pauseScanning = false;

        // Phone input formatting
        function formatPhone(input) {

            let digits = input.value.replace(/\D/g, '');

            if (digits.startsWith('971')) {
                digits = digits.substring(3);
            }

            if (digits.startsWith('0')) {
                digits = digits.substring(1);
            }

            digits = digits.substring(0, 9);

            let formatted = '+971 ';

            if (digits.length >= 2) {

                formatted += digits.substring(0, 2);

                if (digits.length > 2) {

                    formatted += ' ' + digits.substring(2);

                }

            } else {

                formatted += digits;

            }

            input.value = formatted;
        }

        // Emirates ID formatting: 784-YYYY-XXXXXXX-X
        function formatEmiratesId(input) {

            let digits = input.value.replace(/\D/g, '').substring(0, 15);

            let formatted = '';

            if (digits.length > 0) formatted += digits.substring(0, 3);
            if (digits.length > 3) formatted += '-' + digits.substring(3, 7);
            if (digits.length > 7) formatted += '-' + digits.substring(7, 14);
            if (digits.length > 14) formatted += '-' + digits.substring(14, 15);

            input.value = formatted;
        }

        async function loadFaceModels() {

            await faceapi.nets.tinyFaceDetector.loadFromUri('./models');

            await faceapi.nets.faceLandmark68Net.loadFromUri('./models');

            await faceapi.nets.faceRecognitionNet.loadFromUri('./models');

            faceModelsLoaded = true;

            console.log('Face models loaded');
        }

        // ── Clock ──────────────────────────────────────────
        function updateClock() {
            const now = new Date();
            document.getElementById('clock').textContent = now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });
        }
        setInterval(updateClock, 1000);
        updateClock();

        // ── Camera ─────────────────────────────────────────
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const photo = document.getElementById('capturedPhoto');

        async function waitForFaceAPI() {

            while (typeof faceapi === 'undefined') {

                await new Promise(resolve =>
                    setTimeout(resolve, 100)
                );

            }

        }

        (async () => {

            await waitForFaceAPI();

            navigator.mediaDevices.getUserMedia({

                    video: {

                        facingMode: "user"

                    }

                })
                .catch(() => {

                    return navigator.mediaDevices.getUserMedia({

                        video: true

                    });

                })
                .then(async stream => {
                    video.srcObject = stream;

                    await loadFaceModels();
                    faceModelsLoaded = true;
                    setInterval(async () => {

                        if (!faceModelsLoaded)
                            return;

                        if (processingFace)
                            return;

                        if (photo.style.display === 'block')
                            return;

                        if (pauseScanning)
                            return;

                        await capturePhoto();

                    }, 3000);
                })
                .catch(() => {
                    document.querySelector('.camera-box').innerHTML =
                        '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#64748b;font-size:.875rem;padding:1rem;text-align:center">Camera unavailable.<br>Photo will not be captured.</div>';
                });
        })();

        async function capturePhoto() {

            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;

            const ctx = canvas.getContext('2d');

            ctx.translate(canvas.width, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(video, 0, 0);
            ctx.setTransform(1, 0, 0, 1, 0, 0);
            const detection =
                await faceapi
                .detectSingleFace(
                    canvas,
                    new faceapi.TinyFaceDetectorOptions({
                        inputSize: 512,
                        scoreThreshold: 0.2
                    })
                );

            if (!detection) {

                showToast(
                    'No face detected'
                );

                setTimeout(() => {

                    retakePhoto();

                }, 1000);

                return;

            }

            capturedPhotoData = canvas.toDataURL('image/jpeg', .85);

            photo.src = capturedPhotoData;
            photo.style.display = 'block';
            pauseScanning = true;
            document.getElementById('captureBtn').style.display = 'none';
            document.getElementById('retakeBtn').style.display = '';

            await extractFaceDescriptor();
        }

        function retakePhoto() {

            capturedPhotoData = null;
            currentFaceDescriptor = null;

            photo.style.display = 'none';

            document.getElementById('captureBtn').style.display = '';
            document.getElementById('retakeBtn').style.display = 'none';

            // Resume scanning
            pauseScanning = false;
            processingFace = false;

        }

        async function extractFaceDescriptor() {

            const img = document.getElementById(
                'capturedPhoto'
            );

            const detection =
                await faceapi
                .detectSingleFace(
                    img,
                    new faceapi.TinyFaceDetectorOptions({

                        inputSize: 512,

                        scoreThreshold: 0.2

                    })
                )
                .withFaceLandmarks()
                .withFaceDescriptor();

            if (!detection) {

                showToast(
                    'Face not detected'
                );

                setTimeout(
                    retakePhoto,
                    1000
                );

                return;

            }

            currentFaceDescriptor =
                Array.from(
                    detection.descriptor
                );

            console.log(
                currentFaceDescriptor.length
            );

        }

        // ── Submit ─────────────────────────────────────────
        const nameRegex = /^[A-Za-z ]{3,100}$/;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const phoneRegex = /^\+971 \d{2} \d{7}$/;
        const emiratesIdRegex = /^\d{3}-\d{4}-\d{7}-\d{1}$/;

        function submitForm() {

            const name = document.getElementById('fullName').value.trim();

            if (!nameRegex.test(name)) {

                showToast(
                    'Name should contain only letters (min 3 characters)'
                );

                return;

            }

            const phone = document.getElementById('phoneDisplay').value.trim();

            if (!phoneRegex.test(phone)) {

                showToast(
                    'Please enter a valid UAE mobile number'
                );

                return;

            }

            const email = document.getElementById('email').value.trim();

            if (!emailRegex.test(email)) {

                showToast(
                    'Invalid email address'
                );

                return;

            }

            const emiratesId = document.getElementById('emiratesId').value.trim();

            if (!emiratesIdRegex.test(emiratesId)) {

                showToast(
                    'Enter a valid Emirates ID (784-XXXX-XXXXXXX-X)'
                );

                return;

            }

            const licenseNumber = document.getElementById('licenseNumber').value.trim();

            if (licenseNumber.length < 3) {

                showToast(
                    'Enter driving license number'
                );

                return;

            }

            const licenseExpiry = document.getElementById('licenseExpiry').value;

            if (!licenseExpiry) {

                showToast(
                    'Select driving license expiry date'
                );

                return;

            }

            if (new Date(licenseExpiry) < new Date(new Date().toDateString())) {

                showToast(
                    'License expiry date cannot be in the past'
                );

                return;

            }

            if (!currentFaceDescriptor) {

                showToast(
                    'Please scan driver face'
                );

                return;

            }

            if (!capturedPhotoData) {

                showToast(
                    'Please capture driver photo'
                );

                return;

            }
            const fd = new FormData();
            fd.append('full_name', name);
            fd.append('phone', phone);
            fd.append('email', email);
            fd.append('emirates_id', emiratesId);
            fd.append('license_number', licenseNumber);
            fd.append('license_expiry', licenseExpiry);
            fd.append('photo_data', capturedPhotoData);
            fd.append('face_descriptor', JSON.stringify(currentFaceDescriptor));

            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Registering…';

            fetch('api/save_driver.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    btn.textContent = 'Register Driver';

                    if (data.success) {
                        document.getElementById('mainForm').style.display = 'none';
                        const sc = document.getElementById('successScreen');
                        sc.style.display = 'block';
                        document.getElementById('successName').textContent =
                            `${name} has been registered as a driver.`;

                        setTimeout(() => {

                            resetForm();

                            processingFace = false;

                        }, 10000);
                    } else {
                        showToast('Error: ' + (data.message || 'Something went wrong'));
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.textContent = 'Register Driver';
                    showToast('Network error — please try again');
                });
        }

        // ── Reset ──────────────────────────────────────────
        function resetForm() {
            document.getElementById('mainForm').style.display = '';
            document.getElementById('successScreen').style.display = 'none';
            photo.style.display = 'none';

            document.getElementById('captureBtn').style.display = '';

            document.getElementById('retakeBtn').style.display = 'none';

            capturedPhotoData = null;

            currentFaceDescriptor = null;

            // Clear all fields
            [
                'fullName',
                'phoneDisplay',
                'email',
                'emiratesId',
                'licenseNumber',
                'licenseExpiry'
            ].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.value = '';
                    el.disabled = false;
                }
            });

            retakePhoto();

            capturedPhotoData = null;
            pauseScanning = false;
            processingFace = false;
        }

        document.querySelectorAll(
            'input, textarea, select'
        ).forEach(el => {

            el.addEventListener('focus', () => {

                pauseScanning = true;

            });

        });

        function showToast(message) {

            const t = document.getElementById('toast');

            t.innerText = message;

            t.style.display = 'block';

            setTimeout(() => {

                t.style.display = 'none';

            }, 3000);

        }
    </script>

    <div id="toast"></div>
    <script>
        if ('serviceWorker' in navigator) {

            navigator.serviceWorker.register(
                'service-worker.js'
            );

        }
    </script>
</body>

</html>