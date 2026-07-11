<?php
require_once 'includes/config.php';
$pdo = getDB();

$hosts = $pdo->query(
"
SELECT
    id,
    name,
    department
FROM hosts
WHERE active = 1
ORDER BY name ASC
"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ambitious Group Visitor Check-In </title>


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
      overflow: hidden;
      grid-template-rows: auto 1fr auto;
    }

    .btn-clear {

      display: none;

      width: 100%;

      margin-top: .75rem;

      padding: .9rem;

      border: none;

      border-radius: 8px;

      background: #374151;

      color: #fff;

      font-size: .95rem;

      font-weight: 600;

      cursor: pointer;

      transition: .25s;

    }

    .btn-clear:hover {

      background: #4b5563;

    }

    .btn-clear:active {

      transform: scale(.98);

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
      grid-template-columns: 430px 1fr;
      gap: 0;
      min-height: calc(100dvh - 64px);
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

    /* Returning visitor card */
    #returningCard {
      display: none;
      background: var(--surface2);
      border: 1px solid var(--accent);
      border-radius: var(--radius);
      padding: 1.25rem;
      width: 100%;
      max-width: 380px;
      animation: slideIn .3s ease;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }

      to {
        opacity: 1;
        transform: none;
      }
    }

    .returning-header {
      display: flex;
      align-items: center;
      gap: .75rem;
      margin-bottom: .75rem;
    }

    .returning-avatar {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--accent);
    }

    .returning-avatar-placeholder {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      background: var(--accent);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.3rem;
      font-weight: 800;
      color: #fff;
      flex-shrink: 0;
    }

    .returning-name {
      font-weight: 700;
      font-size: 1.05rem;
    }

    .returning-meta {
      font-size: .8rem;
      color: var(--muted);
    }

    .returning-tag {
      display: inline-flex;
      align-items: center;
      gap: .3rem;
      background: rgba(108, 99, 255, .2);
      color: var(--accent2);
      padding: .25rem .65rem;
      border-radius: 999px;
      font-size: .75rem;
      font-weight: 700;
      margin-bottom: .75rem;
    }

    .returning-stats {
      display: flex;
      gap: 1rem;
      padding: .75rem;
      background: var(--surface3);
      border-radius: 8px;
    }

    .rstat {
      text-align: center;
      flex: 1;
    }

    .rstat .rv {
      font-size: 1.3rem;
      font-weight: 800;
      color: var(--accent2);
    }

    .rstat .rl {
      font-size: .7rem;
      color: var(--muted);
    }

    /* RIGHT PANEL — Form */
    .right-panel {
      overflow-y: hidden;
      padding: 2rem;
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

    /* Phone lookup */
    .phone-lookup {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.25rem;
    }

    .phone-lookup label {
      display: block;
      font-size: .75rem;
      text-transform: uppercase;
      letter-spacing: .07em;
      color: var(--muted);
      margin-bottom: .5rem;
      font-weight: 600;
    }

    .phone-input-row {
      display: flex;
      gap: .5rem;
    }

    .phone-input-row input {
      flex: 1;
      background: var(--surface2);
      border: 1.5px solid var(--border);
      color: var(--text);
      padding: .75rem 1rem;
      border-radius: 8px;
      font-size: 1rem;
      font-family: var(--font);
      transition: border-color .2s;
    }

    .phone-input-row input:focus {
      outline: none;
      border-color: var(--accent);
    }

    .btn-lookup {
      background: var(--accent);
      color: #fff;
      border: none;
      padding: .75rem 1.25rem;
      border-radius: 8px;
      font-family: var(--font);
      font-size: .875rem;
      font-weight: 600;
      cursor: pointer;
    }

    .lookup-hint {
      font-size: .78rem;
      color: var(--muted);
      margin-top: .5rem;
    }

    .lookup-hint.found {
      color: var(--success);
    }

    .lookup-hint.not-found {
      color: var(--warn);
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
      grid-template-columns: 1fr 1fr;
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

    .form-group textarea {
      resize: vertical;
      min-height: 80px;
    }

    .form-group input:disabled,
    .form-group select:disabled {
      opacity: .7;
      cursor: not-allowed;
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

    .badge-display {
      background: var(--surface2);
      border: 1px dashed var(--border);
      border-radius: var(--radius);
      padding: 1.5rem;
      margin: 1.5rem 0;
    }

    .badge-display .badge-label {
      font-size: .75rem;
      color: var(--muted);
      margin-bottom: .5rem;
    }

    .badge-display .badge-num {
      font-size: 2rem;
      font-weight: 900;
      letter-spacing: .1em;
      font-family: monospace;
      color: var(--accent2);
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

      width: 420px;

      background: var(--surface);

      border: 1px solid var(--border);

      border-radius: var(--radius);

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
  </style>
</head>

<body>

  <header>
    <div class="logo">
      <div class="logo-icon">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
          <circle cx="9" cy="7" r="4" />
          <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
          <path d="M16 3.13a4 4 0 0 1 0 7.75" />
        </svg>
      </div>
      <h1>Ambitious Group</h1>
    </div>
    <div class="header-right">
      <div class="clock" id="clock"></div>
    </div>
  </header>

  <main>
    <!-- LEFT: Camera -->
    <div class="left-panel">
      <div class="camera-section">
        <div class="camera-label"> Visitor Photo Capture</div>
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
          <button class="btn btn-retake" id="retakeBtn" onclick="retakePhoto()" style="display:none">Retake</button>
        </div>
      </div>

      <!-- Returning visitor card -->
      <div id="returningCard">
        <div class="returning-tag">Returning Visitor</div>
        <div class="returning-header">
          <div id="returningAvatar" class="returning-avatar-placeholder"></div>
          <div>
            <div class="returning-name" id="returningName"></div>
            <div class="returning-meta" id="returningMeta"></div>
          </div>
        </div>
        <div class="returning-stats">
          <div class="rstat">
            <div class="rv" id="returningVisits">—</div>
            <div class="rl">Total Visits</div>
          </div>
          <div class="rstat">
            <div class="rv" id="returningLastVisit" style="font-size:.9rem">—</div>
            <div class="rl">Last Visit</div>
          </div>
        </div>
      </div>
    </div>

    <!-- RIGHT: Form -->
    <div class="right-panel">
      <div id="mainForm">
        <h2>Check-In <span>Registration</span></h2>

        <!-- Step 1: Phone lookup -->
        <div class="phone-lookup" style="display:none">
          <label>Phone Number <span style="color:var(--danger)">*</span></label>
          <div class="phone-input-row">
            <input type="tel" id="phoneInput" placeholder="+971 XX XXX XXXX" oninput="onPhoneInput()" maxlength="20">
            <button class="btn-lookup" onclick="lookupVisitor()">Look Up</button>
          </div>
          <div class="lookup-hint" id="lookupHint">Enter phone number to check if visitor has visited before</div>
        </div>

        <!-- Visitor info (hidden for returning visitors) -->
        <div class="form-section" id="visitorInfoSection">
          <h3>👤 Visitor Information</h3>
          <div class="form-row">
            <div class="form-group full">
              <label>Full Name <span class="req">*</span></label>
              <input type="text" id="fullName" placeholder="Enter full name">
            </div>
            
            <div class="form-group">
              <label>Email Address</label>
              <input type="email" id="email" placeholder="visitor@email.com">
            </div>
            <div class="form-group">
              <label>Phone <span class="req">*</span></label>
              <input type="tel" id="phoneDisplay" placeholder="+971 50 1234567" maxlength="16"
                oninput="formatPhone(this)">
            </div>
          </div>
        </div>

        <!-- Visit details -->
        <div class="form-section">
          <h3>Visit Details</h3>
          <div class="form-row">
            <div class="form-group">
              <label>Meeting With <span class="req">*</span></label>
              <select id="hostSelect" onchange="onHostChange()">
                <option value="">— Select a person —</option>
                <?php foreach ($hosts as $h): ?>
                  <option value="<?= htmlspecialchars($h['name']) ?>"
                    data-dept="<?= htmlspecialchars($h['department']) ?>">
                    <?= htmlspecialchars($h['name']) ?> (<?= htmlspecialchars($h['department']) ?>)
                  </option>
                <?php endforeach; ?>
                <option value="__other__">Other (type below)</option>
              </select>
            </div>
            <div class="form-group">
              <label>Department</label>
              <input type="text" id="hostDept" placeholder="Auto-filled or enter manually">
            </div>
            <div class="form-group full" id="otherHostGroup" style="display:none">
              <label>Host Name (Other) <span class="req">*</span></label>
              <input type="text" id="otherHost" placeholder="Enter the person's name">
            </div>
            <div class="form-group full">
              <label>Purpose of Visit <span class="req">*</span></label>
              <textarea id="purpose" placeholder="Briefly describe the purpose of this visit…"></textarea>
            </div>
          </div>
        </div>

        <button class="btn btn-submit" onclick="submitForm()" id="submitBtn">
          Register Visit
        </button>
        <button id="clearBtn" class="btn btn-clear" onclick="clearForm()" style="display:none">
          Clear
        </button>
      </div>
      <div id="employeeResult" style="display:none"></div>
      <!-- Success screen -->
      <div id="successScreen">
        <div class="success-icon">
          <svg width="36" height="36" fill="none" stroke="var(--success)" stroke-width="2.5" viewBox="0 0 24 24">
            <polyline points="20 6 9 17 4 12" />
          </svg>
        </div>
        <h2 id="successTitle" style="font-size:1.6rem;margin-bottom:.5rem">

          Welcome!

        </h2>
        <p style="color:var(--muted);margin-bottom:1rem" id="successName"></p>
        <div class="badge-display" id="badgeDisplay">

          <div class="badge-label" id="badgeLabel">

            Your Visitor Card

          </div>

          <div class="badge-num" id="successBadge">

          </div>

        </div>
        <p id="successMessage" style="font-size:.875rem;color:var(--muted);margin-bottom:1rem">
          Please collect your card from the reception desk. <br>
          Present it when exiting the premises.
        </p>

        <button class="btn btn-new" onclick="resetForm()">← New Visitor Check-In</button>
      </div>
    </div>
  </main>

  <footer>Ambitious Group — Visitor Management System &nbsp;|&nbsp; <?= date('d F Y') ?></footer>

  <canvas id="canvas" style="display:none"></canvas>

  <script>
    // State
    let capturedPhotoData = null;
    let currentVisitorId = null;
    let isReturning = false;
    let lookupTimeout = null;
    let currentFaceDescriptor = null;
    let faceModelsLoaded = false;
    let processingFace = false;
    let pauseScanning = false;
    let employeeModalOpen = false;
    let checkoutTimeout = null;
    let waitingForBlink = false;
    let blinkVerified = false;
    let eyesClosed = false;
    let inactivityTimer;
    // Phone input formatting
    function getEAR(eye) {

      const A = Math.hypot(
        eye[1].x - eye[5].x,
        eye[1].y - eye[5].y
      );

      const B = Math.hypot(
        eye[2].x - eye[4].x,
        eye[2].y - eye[4].y
      );

      const C = Math.hypot(
        eye[0].x - eye[3].x,
        eye[0].y - eye[3].y
      );

      return (A + B) / (2 * C);
    }
    function isFaceStraight(landmarks) {

      const nose = landmarks.getNose();

      const jaw = landmarks.getJawOutline();

      const leftJaw = jaw[0];
      const rightJaw = jaw[16];

      const noseTip = nose[3];

      const faceCenter =
        (leftJaw.x + rightJaw.x) / 2;

      const faceWidth =
        rightJaw.x - leftJaw.x;

      const offset =
        Math.abs(noseTip.x - faceCenter);

      // Reject if nose moves more than 12% of face width
      if (offset > faceWidth * 0.12) {

        return false;

      }

      return true;

    }
    function clearForm() {

      retakePhoto();

      resetReturnState();

      document.getElementById('fullName').value = '';
      document.getElementById('email').value = '';
      document.getElementById('phoneDisplay').value = '';
      document.getElementById('purpose').value = '';
      document.getElementById('hostDept').value = '';
      document.getElementById('otherHost').value = '';

      document.getElementById('hostSelect').selectedIndex = 0;

      document.getElementById('clearBtn').style.display = 'none';

      resetInactivityTimer();

    }
    function toggleClearButton() {

      const fields = [

        'fullName',
        'email',
        'phoneDisplay',
        'purpose',
        'otherHost'

      ];

      const show = fields.some(id => {

        return document
          .getElementById(id)
          .value
          .trim() !== '';

      });

      document.getElementById('clearBtn').style.display =
        show ? 'block' : 'none';

    }
    function resetInactivityTimer() {

      clearTimeout(inactivityTimer);

      inactivityTimer = setTimeout(() => {

        if (employeeModalOpen)
          return;

        showToast("Returning to home screen...");

        setTimeout(() => {

          resetForm();

        }, 800);

      }, 15000);

    }

    function formatPhone(input) {

      // Remove everything except digits
      let digits = input.value.replace(/\D/g, '');

      // Remove country code if pasted
      if (digits.startsWith('971')) {
        digits = digits.substring(3);
      }

      // Remove leading zero if present
      if (digits.startsWith('0')) {
        digits = digits.substring(1);
      }

      // Limit to 9 digits (50 + 1234567)
      digits = digits.substring(0, 9);

      // Build formatted number
      let formatted = '+971 ';

      if (digits.length >= 2) {

        formatted += digits.substring(0, 2);

        if (digits.length > 2) {

          formatted += ' ' + digits.substring(2);

        }

      }
      else {

        formatted += digits;

      }

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
        hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
      });
    }
    setInterval(updateClock, 1000); updateClock();

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

            if (!faceModelsLoaded) return;
            if (processingFace) return;
            if (pauseScanning) return;

            const detection =
              await faceapi
                .detectSingleFace(
                  video,
                  new faceapi.TinyFaceDetectorOptions({
                    inputSize: 160,
                    scoreThreshold: 0.3
                  })
                )
                .withFaceLandmarks();

            if (!detection) {

              waitingForBlink = false;
              blinkVerified = false;
              eyesClosed = false;

              return;
            }

            // Reject side faces BEFORE asking for a blink
            if (!isFaceStraight(detection.landmarks)) {

              waitingForBlink = false;
              blinkVerified = false;
              eyesClosed = false;

              if (!window.faceWarningShown) {

                showToast("Please face the camera", "Please Face the camera");

                window.faceWarningShown = true;

                setTimeout(() => {

                  window.faceWarningShown = false;

                }, 2000);

              }

              return;
            }

            // Only now ask for a blink
            if (!waitingForBlink) {

              waitingForBlink = true;

              showToast("Please blink once",
                "Please look straight at the camera and blink once.");

            }

            const leftEAR =
              getEAR(
                detection.landmarks.getLeftEye()
              );

            const rightEAR =
              getEAR(
                detection.landmarks.getRightEye()
              );

            const ear =
              (leftEAR + rightEAR) / 2;
            //console.log("EAR:", ear);
            // eyes closed
            if (ear < 0.24) {

              eyesClosed = true;

            }

            // blink completed
            if (eyesClosed && ear > 0.28) {

              blinkVerified = true;

              waitingForBlink = false;

              eyesClosed = false;

              showToast("Liveness verified",
                "Liveness verified. Please wait while we recognize you.");

              pauseScanning = true;
              await capturePhoto();

            }

          }, 200);
        })
        .catch(() => {
          document.querySelector('.camera-box').innerHTML =
            '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#64748b;font-size:.875rem;padding:1rem;text-align:center">Camera unavailable.<br>Photo will not be captured.</div>';
        });
    })();
    async function capturePhoto() {
      console.time("Capture Photo");
      canvas.width = video.videoWidth || 640;
      canvas.height = video.videoHeight || 480;

      const ctx = canvas.getContext('2d');

      ctx.translate(canvas.width, 0);
      ctx.scale(-1, 1);
      ctx.drawImage(video, 0, 0);
      ctx.setTransform(1, 0, 0, 1, 0, 0);

      capturedPhotoData = canvas.toDataURL('image/jpeg', .6);

      photo.src = capturedPhotoData;
      photo.style.display = 'block';
      pauseScanning = true;
      document.getElementById('captureBtn').style.display = 'none';
      document.getElementById('retakeBtn').style.display = '';
      console.timeEnd("Capture Photo");
      await identifyFace();
    }

    function retakePhoto() {

      capturedPhotoData = null;
      currentFaceDescriptor = null;
      photo.style.display = 'none';

      document.getElementById('captureBtn').style.display = '';
      document.getElementById('retakeBtn').style.display = 'none';

      // Clear all form fields
      document.getElementById('fullName').value = '';
      document.getElementById('email').value = '';
      document.getElementById('phoneDisplay').value = '';
      document.getElementById('purpose').value = '';
      document.getElementById('hostDept').value = '';
      document.getElementById('otherHost').value = '';
      document.getElementById('hostSelect').selectedIndex = 0;
      document.getElementById('otherHostGroup').style.display = 'none';
      document.getElementById('clearBtn').style.display = 'none';
      // Remove returning visitor card
      resetReturnState();

      // Resume scanning
      pauseScanning = false;
      processingFace = false;
      waitingForBlink = false;

      eyesClosed = false;

    }
    async function identifyFace() {
      console.time("Identify Face");
      if (processingFace)
        return;

      processingFace = true;

      console.log(
        "Canvas:",
        canvas.width,
        canvas.height
      );

      console.log(
        "Photo src exists:",
        !!photo.src
      );

      const detection =
        await faceapi
          .detectSingleFace(
            canvas,
            new faceapi.TinyFaceDetectorOptions({
              inputSize: 160,
              scoreThreshold: 0.3
            })
          )
          .withFaceLandmarks()
          .withFaceDescriptor();

      console.log(
        "Detection:",
        detection
      );

      if (!detection) {

        processingFace = false;

        showToast(
          'No face detected', "No Face Detected"
        );

        setTimeout(() => {

          retakePhoto();

        }, 1000);

        return;

      }

      currentFaceDescriptor =
        Array.from(
          detection.descriptor
        );
      console.timeEnd("Identify Face");
      fetch(
        'api/person_lookup.php',
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            descriptor: currentFaceDescriptor
          })
        }
      )
        .then(r => r.json())
        .then(data => {

          console.log(data);

          // =====================
          // EMPLOYEE DETECTED
          // =====================

          if (data.success && data.type === 'employee') {

            processingFace = false;
            pauseScanning = true;
            employeeModalOpen = false;

            fetch(
              'api/employee_attendance.php',
              {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json'
                },
                body: JSON.stringify({

                  employee_id:
                    data.employee_id

                })
              }
            )
              .then(r => r.json())
              .then(a => {

                if (a.action === 'checkin') {

                  showEmployeeAttendanceCard(

                    'Check In Successful',

                    data.name,

                    'Time : ' +

                    new Date().toLocaleTimeString(
                      [],
                      {
                        hour: '2-digit',
                        minute: '2-digit'
                      }
                    )

                  );

                }

                else if (a.action === 'checkout_prompt') {

                  if (employeeModalOpen)
                    return;

                  employeeModalOpen = true;

                  showModal(

                    'Employee Checkout',

                    data.name +

                    '\n\nChecked in at '

                    +

                    a.check_in +

                    '\n\nAre you leaving for the day?',

                    () => {

                      confirmEmployeeCheckout(
                        data.employee_id
                      );

                    }

                  );

                }

                else if (a.action === 'done') {

                  processingFace = false;

                  pauseScanning = false;

                  showToast(
                    'Attendance already completed today'
                  );

                  setTimeout(() => {

                    resetForm();

                  }, 3000);

                }

              });
            return;


          }

          // =====================
          // RETURNING VISITOR
          // =====================

          if (data.success && data.type === 'visitor') {

            if (data.checked_in) {

              showModal(

                "Welcome Back",

                "You are currently checked in.\n\nWould you like to check out?",

                () => {

                  checkoutByFace(
                    data.log_id
                  );

                }

              );

            }
            else {

              showToast(
                "👋 Welcome back!", "Welcome Back"
              );

              loadVisitorByFace(
                data.visitor_id
              );

            }

            processingFace = false;

            return;

          }

          // =====================
          // NEW VISITOR
          // =====================

          showToast(
            "New visitor detected",
            "Welcome. Please fill in your details."
          );
          toggleClearButton();

          document.getElementById(
            'lookupHint'
          ).textContent =
            'Please fill in your details below';

          document.getElementById(
            'lookupHint'
          ).className =
            'lookup-hint not-found';

          processingFace = false;

        })
        .catch(err => {

          console.error(err);

          processingFace = false;

          pauseScanning = false;

          showToast(
            'Recognition failed', "Recognition Failed"
          );

          setTimeout(() => {

            retakePhoto();

          }, 1000);

        });



      console.log(
        Array.from(
          detection.descriptor
        )
      );
    }

    function showEmployeeAttendanceCard(
      title,
      name,
      line1,
      line2 = ''
    ) {

      document.getElementById(
        'mainForm'
      ).style.display = 'none';

      document.getElementById(
        'successScreen'
      ).style.display = 'block';

      document.getElementById(
        'successTitle'
      ).innerText = title;

      document.getElementById(
        'successName'
      ).innerText = name;

      document.getElementById(
        'badgeLabel'
      ).innerText = 'Attendance';

      document.getElementById(
        'successMessage'
      ).style.display = 'none';

      document.querySelector(
        '.btn-new'
      ).style.display = 'none';

      document.getElementById(
        'successBadge'
      ).innerHTML = `

        <div style="
            font-size:1rem;
            color:var(--text);
            letter-spacing:0;
        ">

            ${line1}

            ${line2
          ?
          `<div style="
                    margin-top:1rem;
                    color:var(--muted);
                ">
                    ${line2}
                </div>`
          :
          ''
        }

        </div>

    `;

      setTimeout(() => {

        resetForm();

        processingFace = false;

        pauseScanning = false;

        employeeModalOpen = false;
        waitingForBlink = false;
        blinkVerified = false;
        eyesClosed = false;
        processingFace = false;
        pauseScanning = false;

      }, 5000);

    }
    function checkoutByFace(logId) {

      fetch(
        'api/checkout.php',
        {
          method: 'POST',
          headers: {
            'Content-Type':
              'application/x-www-form-urlencoded'
          },
          body:
            'log_id=' + logId
        }
      )
        .then(r => r.json())
        .then(data => {

          if (data.success) {

            showToast(
              "Checked out successfully.\n\nPlease return your Visitor Card " +
              data.card_number, "Please Return Your visitor. Thank You for visiting."
            );
            setTimeout(() => {

              resetForm();

            }, 3000);

          }
          else {

            showToast(
              data.message
            );

          }

        });

    }
    function confirmEmployeeCheckout(employeeId) {

      fetch(
        'api/employee_checkout.php',
        {

          method: 'POST',

          headers: {
            'Content-Type': 'application/json'
          },

          body: JSON.stringify({

            employee_id: employeeId

          })

        }

      )

        .then(r => r.json())

        .then(data => {

          showEmployeeAttendanceCard(

            'Check Out Successful',

            data.name,

            'Check Out : ' + data.check_out,

            'Hours Worked : ' + data.hours

          );



        });

    }
    function loadVisitorByFace(visitorId) {

      fetch(`api/get_visitor.php?id=${visitorId}`)
        .then(r => r.json())
        .then(data => {

          if (!data.success) return;

          showReturningVisitor(data);

          document.getElementById('phoneInput').value =
            data.phone || '';

          document.getElementById('phoneDisplay').value =
            data.phone || '';

          document.getElementById('lookupHint').textContent =
            'Visitor recognized automatically';

          document.getElementById('lookupHint').className =
            'lookup-hint found';

        });
    }
    // ── Phone lookup ───────────────────────────────────
    function onPhoneInput() {
      const phone = document.getElementById('phoneDisplay').value.trim();
      document.getElementById('phoneDisplay').value = phone;
      clearTimeout(lookupTimeout);
      if (phone.length >= 7) {
        lookupTimeout = setTimeout(lookupVisitor, 600);
      } else {
        resetReturnState();
      }
    }

    function lookupVisitor() {
      const phone = document.getElementById('phoneDisplay').value.trim();
      if (!phone) return;

      document.getElementById('lookupHint').textContent = 'Looking up…';
      document.getElementById('lookupHint').className = 'lookup-hint';

      fetch(`api/lookup_visitor.php?phone=${encodeURIComponent(phone)}`)
        .then(r => r.json())
        .then(data => {
          if (data.found) {
            showReturningVisitor(data);
          } else {
            resetReturnState();
            document.getElementById('lookupHint').textContent = '✦ New visitor — please fill in details below';
            document.getElementById('lookupHint').className = 'lookup-hint not-found';
          }
        });
    }

    function showReturningVisitor(data) {
      currentVisitorId = data.id;
      isReturning = true;
      pauseScanning = true;

      // Fill & lock personal fields
      document.getElementById('fullName').value = data.full_name;
      document.getElementById('email').value = data.email || '';
      document.getElementById('phoneDisplay').value = data.phone;
      document.getElementById('fullName').disabled = true;
      document.getElementById('email').disabled = true;

      // Hint
      document.getElementById('lookupHint').textContent = `Returning visitor found — details pre-filled`;
      document.getElementById('lookupHint').className = 'lookup-hint found';

      // Show returning card
      const card = document.getElementById('returningCard');
      card.style.display = 'block';

      // Avatar
      const avatarEl = document.getElementById('returningAvatar');
      if (data.photo_path) {
        avatarEl.outerHTML = `<img id="returningAvatar" class="returning-avatar" src="${data.photo_path}" alt="">`;
      } else {
        avatarEl.textContent = data.full_name.charAt(0).toUpperCase();
      }

      document.getElementById('returningName').textContent = data.full_name;
      document.getElementById('returningMeta').textContent = data.phone + (data.email ? ' · ' + data.email : '');
      document.getElementById('returningVisits').textContent = data.total_visits;
      document.getElementById('returningLastVisit').textContent = data.last_visit;

      document.getElementById('submitBtn').textContent = ' Log New Visit';
    }

    function resetReturnState() {
      currentVisitorId = null;
      isReturning = false;
      document.getElementById('returningCard').style.display = 'none';
      document.getElementById('fullName').disabled = false;
      document.getElementById('email').disabled = false;
      document.getElementById('fullName').value = '';
      document.getElementById('email').value = '';
      document.getElementById('submitBtn').textContent = ' Register Visit';
      document.getElementById('lookupHint').textContent = 'Enter phone number to check if visitor has visited before';
      document.getElementById('lookupHint').className = 'lookup-hint';
    }

    // ── Host select ────────────────────────────────────
    function onHostChange() {
      const sel = document.getElementById('hostSelect');
      const dept = sel.selectedOptions[0]?.dataset.dept || '';
      const other = sel.value === '__other__';

      document.getElementById('hostDept').value = other ? '' : dept;
      document.getElementById('otherHostGroup').style.display = other ? '' : 'none';
      document.getElementById('otherHost').required = other;
    }

    // ── Submit ─────────────────────────────────────────
    function submitForm() {
      const phone = document.getElementById('phoneDisplay').value.trim();
      const name = document.getElementById('fullName').value.trim();
      const hostSel = document.getElementById('hostSelect').value;
      const hostName = hostSel === '__other__'
        ? document.getElementById('otherHost').value.trim()
        : hostSel;
      const purpose = document.getElementById('purpose').value.trim();



      const phoneRegex = /^\+971 5[0-6] \d{7}$/;

      if (!phoneRegex.test(phone)) {

        showToast(
          'Please enter a valid UAE mobile number', "Enter valid Number"
        );
        return;
      }
      if (!isReturning && !name) { showToast('Please enter the visitor\'s full name'); return; }
      if (!hostName) { showToast('Please select or enter who they are meeting'); return; }
      if (!purpose) { showToast('Please describe the purpose of the visit'); return; }
      if (!currentFaceDescriptor || currentFaceDescriptor.length !== 128) {
        showToast("Please scan your face before registering.","Please scan your face.");
        return;
      }
      if (!capturedPhotoData) {
        showToast("Please capture your photo.","Please capture your photo.");
        return;
      }
      const btn = document.getElementById('submitBtn');
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner"></span> Registering…';

      const fd = new FormData();
      fd.append('visitor_id', currentVisitorId || 0);
      fd.append('full_name', name);
      fd.append(
        'phone',
        phone
      );
      fd.append('email', document.getElementById('email').value.trim());
      fd.append('host_name', hostName);
      fd.append('host_department', document.getElementById('hostDept').value.trim());
      fd.append('purpose', purpose);
      if (capturedPhotoData) fd.append('photo_data', capturedPhotoData);
      if (currentFaceDescriptor) {

        fd.append(
          'face_descriptor',
          JSON.stringify(currentFaceDescriptor)
        );

      }


      fetch('api/register_visit.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
          btn.disabled = false;
          btn.textContent = ' Register Visit';
          document.getElementById(
            'successTitle'
          ).innerText = 'Welcome!';

          document.getElementById(
            'badgeLabel'
          ).innerText = 'Your Visitor Card';

          document.getElementById(
            'successMessage'
          ).style.display = 'block';

          document.querySelector(
            '.btn-new'
          ).style.display = '';

          if (data.success) {
            document.getElementById(
              'successMessage'
            ).style.display = 'block';

            document.querySelector(
              '.btn-new'
            ).style.display = '';

            document.querySelector(
              '#successScreen h2'
            ).innerText = 'Welcome!';
            document.getElementById(
              'successTitle'
            ).innerText = 'Welcome!';

            document.getElementById(
              'badgeLabel'
            ).innerText = 'Your Visitor Card';

            document.getElementById(
              'successMessage'
            ).style.display = 'block';

            document.querySelector(
              '.btn-new'
            ).style.display = '';

            document.getElementById(
              'badgeDisplay'
            ).style.display = 'block';
            document.getElementById('mainForm').style.display = 'none';
            const sc = document.getElementById('successScreen');
            sc.style.display = 'block';
            document.getElementById('successName').textContent =
              `Welcome, ${name || document.getElementById('fullName').value}! Your visit has been logged.`;
            document.getElementById('successBadge').textContent = data.card_number;
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
          btn.textContent = ' Register Visit';
          showToast('Network error — please try again');
        });
    }
    // ── Cancel Checkout ─────────────────────────────────
    function cancelCheckout() {
      clearTimeout(checkoutTimeout);
      closeModal();

      processingFace = false;

      pauseScanning = false;

      setTimeout(() => {

        retakePhoto();

      }, 1000);

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
      ['phoneInput', 'fullName', 'email', 'purpose', 'otherHost', 'hostDept'].forEach(id => {
        const el = document.getElementById(id);
        if (el) { el.value = ''; el.disabled = false; }
      });
      document.getElementById('hostSelect').selectedIndex = 0;
      document.getElementById('phoneDisplay').value = '';
      document.getElementById('otherHostGroup').style.display = 'none';
      document.getElementById('clearBtn').style.display = 'none';
      retakePhoto();
      resetReturnState();
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

      el.addEventListener('input', () => {

        toggleClearButton();

        resetInactivityTimer();

      });

      el.addEventListener('change', () => {

        toggleClearButton();

        resetInactivityTimer();

      });

    });
    function showModal(
      title,
      message,
      onConfirm
    ) {

      document.getElementById(
        'modalTitle'
      ).innerText = title;

      document.getElementById(
        'modalMessage'
      ).innerText = message;

      document.getElementById(
        'modalOverlay'
      ).style.display = 'flex';

      clearTimeout(checkoutTimeout);

      checkoutTimeout = setTimeout(() => {

        closeModal();

        processingFace = false;

        pauseScanning = false;

        employeeModalOpen = false;

        retakePhoto();

        showToast('Session timed out');

      }, 15000);
      document.getElementById(
        'modalConfirmBtn'
      ).onclick = () => {

        clearTimeout(checkoutTimeout);

        closeModal();

        onConfirm();

      };

    }



    function closeModal() {

      clearTimeout(checkoutTimeout);

      document.getElementById(
        'modalOverlay'
      ).style.display = 'none';

      employeeModalOpen = false;

    }

    function showToast(message, voice = null) {

      const t = document.getElementById('toast');

      t.innerText = message;

      t.style.display = 'block';

      if (voice) {
        speak(voice);
      }

      setTimeout(() => {

        t.style.display = 'none';

      }, 3000);

    }
    function speak(text) {

      if (!('speechSynthesis' in window))
        return;

      speechSynthesis.cancel();

      const msg = new SpeechSynthesisUtterance(text);

      msg.rate = 1;

      msg.pitch = 1;

      msg.volume = 1;

      // Optional: choose an English voice
      const voices = speechSynthesis.getVoices();

      const voice = voices.find(v =>
        v.lang.startsWith("en")
      );

      if (voice)
        msg.voice = voice;

      speechSynthesis.speak(msg);

    }

  </script>
  <div id="modalOverlay" class="modal-overlay">

    <div class="modal-box">

      <h3 id="modalTitle"></h3>

      <p id="modalMessage"></p>

      <div class="modal-actions">
        <button class="btn btn-retake" onclick="cancelCheckout()">

          Cancel

        </button>


        <button class="btn btn-submit" id="modalConfirmBtn">

          Confirm

        </button>

      </div>

    </div>

  </div>

  <div id="toast"></div>
  <script>
    [
      'click',
      'touchstart',
      'keydown'
    ].forEach(event => {

      document.addEventListener(event, () => {

        resetInactivityTimer();

      });

    });
    resetInactivityTimer();
    if ('serviceWorker' in navigator) {

      navigator.serviceWorker.register(
        'service-worker.js'
      );

    }

  </script>
</body>

</html>