<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Ambitious Group Driver</title>

    <link
        rel="apple-touch-icon"
        sizes="180x180"
        href="assets/favicon/apple-touch-icon.png">

    <link
        rel="icon"
        type="image/png"
        sizes="32x32"
        href="assets/favicon/favicon-32x32.png">

    <link
        rel="icon"
        type="image/png"
        sizes="16x16"
        href="assets/favicon/favicon-16x16.png">

    <link
        rel="icon"
        href="assets/favicon/favicon.ico">

    <link
        rel="manifest"
        href="assets/favicon/site.webmanifest">

    <meta
        name="theme-color"
        content="#6c63ff">

    <meta
        name="apple-mobile-web-app-capable"
        content="yes">

    <meta
        name="apple-mobile-web-app-status-bar-style"
        content="black-translucent">

    <meta
        name="apple-mobile-web-app-title"
        content="Driver">

    <style>
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

            background:
                linear-gradient(135deg,
                    var(--accent),
                    var(--accent2));

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

        /* MAIN */

        main {
            display: grid;

            grid-template-columns:
                minmax(0, 1fr) minmax(0, 1fr);

            min-height: calc(100dvh - 64px);
        }

        /* LEFT CAMERA */

        .left-panel {
            background: var(--surface);

            border-right: 1px solid var(--border);

            display: flex;
            flex-direction: column;

            align-items: center;
            justify-content: center;

            padding: 2rem;
        }

        .camera-section {
            width: 100%;
            max-width: 520px;
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

            aspect-ratio: 4 / 3;

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

            background:
                linear-gradient(to bottom,
                    transparent 60%,
                    rgba(0, 0, 0, .5));

            pointer-events: none;
        }

        /* CORNER GUIDES */

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

        /* CAMERA CONTROLS */

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

        .btn:disabled {
            opacity: .4;

            cursor: not-allowed;
        }

        /* DRIVER SIDE */

        .driver-info {
            background: var(--bg);

            display: flex;

            flex-direction: column;

            justify-content: center;

            padding: 2.5rem;

        }

        .driver-info h2 {
            font-size: 2rem;

            margin-bottom: .5rem;
        }

        .driver-info h2 span {
            color: var(--accent2);
        }

        .driver-info p {
            color: var(--muted);

            line-height: 1.6;

            max-width: 420px;
        }

        .scan-status {
            margin-top: 2rem;

            padding: 1rem;

            background: var(--surface);

            border: 1px solid var(--border);

            border-radius: var(--radius);

            color: var(--muted);

            max-width: 420px;
        }

        footer {
            text-align: center;

            padding: .75rem;

            font-size: .75rem;

            color: var(--muted);

            border-top: 1px solid var(--border);

            background: var(--surface);
        }

        /* MOBILE */

        @media (max-width: 768px) {

            header {
                padding: 1rem 1.25rem;
            }

            .logo h1 {
                font-size: 1rem;
            }

            .logo-icon {
                width: 34px;
                height: 34px;
            }

            main {
                grid-template-columns: 1fr;

                min-height: auto;
            }

            .left-panel {
                padding: 1.25rem;

                border-right: none;

                border-bottom: 1px solid var(--border);
            }

            .camera-section {
                max-width: 100%;
            }

            .driver-info {
                padding: 1.5rem;
            }

            .driver-info h2 {
                font-size: 1.5rem;
            }

            footer {
                display: none;
            }

        }
    </style>

</head>

<body>

    <header>

        <div class="logo">

            <div class="logo-icon">

                <svg
                    width="20"
                    height="20"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                    viewBox="0 0 24 24">

                    <path
                        d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />

                    <circle
                        cx="9"
                        cy="7"
                        r="4" />

                    <path
                        d="M23 21v-2a4 4 0 0 0-3-3.87" />

                    <path
                        d="M16 3.13a4 4 0 0 1 0 7.75" />

                </svg>

            </div>

            <h1>
                Ambitious Group
            </h1>

        </div>

        <div class="header-right">

            <div
                class="clock"
                id="clock"></div>

        </div>

    </header>


    <main>

        <!-- CAMERA -->

        <div class="left-panel">

            <div class="camera-section">

                <div class="camera-label">
                    Driver Face Scan
                </div>

                <div class="camera-box">

                    <video
                        id="video"
                        autoplay
                        playsinline
                        muted></video>

                    <img
                        id="capturedPhoto"
                        alt="Captured">

                    <canvas id="canvas"></canvas>

                    <div class="camera-overlay"></div>

                    <div class="corner tl"></div>
                    <div class="corner tr"></div>
                    <div class="corner bl"></div>
                    <div class="corner br"></div>

                </div>

                <div class="cam-controls">

                    <button
                        class="btn btn-capture"
                        id="captureBtn"
                        onclick="capturePhoto()">
                        Scan Face
                    </button>

                    <button
                        class="btn btn-retake"
                        id="retakeBtn"
                        onclick="retakePhoto()"
                        style="display:none">
                        Retake
                    </button>

                </div>

            </div>

        </div>


        <!-- DRIVER INFORMATION -->

        <div class="driver-info">

            <h2>
                Driver <span>Portal</span>
            </h2>

            <p>
                Scan your face to continue.
            </p>

            <div
                class="scan-status"
                id="scanStatus">
                Camera ready. Press <strong>Scan Face</strong> to continue.
            </div>

        </div>

    </main>


    <footer>
        Ambitious Group Driver Portal
    </footer>


    <script>
        /*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

        let capturedPhotoData = null;

        let currentFaceDescriptor = null;

        let faceModelsLoaded = false;

        let processingFace = false;


        /*
        |--------------------------------------------------------------------------
        | Clock
        |--------------------------------------------------------------------------
        */

        function updateClock() {

            const now = new Date();

            document.getElementById('clock').textContent =
                now.toLocaleTimeString(
                    'en-US', {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: true
                    }
                );

        }

        setInterval(
            updateClock,
            1000
        );

        updateClock();


        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        function setScanStatus(message) {

            document.getElementById(
                'scanStatus'
            ).textContent = message;

        }


        /*
        |--------------------------------------------------------------------------
        | Face Models
        |--------------------------------------------------------------------------
        */

        async function loadFaceModels() {

            await faceapi.nets.tinyFaceDetector.loadFromUri(
                './models'
            );

            await faceapi.nets.faceLandmark68Net.loadFromUri(
                './models'
            );

            await faceapi.nets.faceRecognitionNet.loadFromUri(
                './models'
            );

            faceModelsLoaded = true;

            console.log(
                'Face models loaded'
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Camera
        |--------------------------------------------------------------------------
        */

        const video =
            document.getElementById('video');

        const canvas =
            document.getElementById('canvas');

        const photo =
            document.getElementById('capturedPhoto');


        async function waitForFaceAPI() {

            while (
                typeof faceapi === 'undefined'
            ) {

                await new Promise(
                    resolve =>
                    setTimeout(
                        resolve,
                        100
                    )
                );

            }

        }


        (async function() {

            await waitForFaceAPI();

            navigator.mediaDevices
                .getUserMedia({

                    video: {
                        facingMode: 'user'
                    }

                })

                .catch(() => {

                    return navigator.mediaDevices
                        .getUserMedia({
                            video: true
                        });

                })

                .then(async stream => {

                    video.srcObject = stream;

                    await loadFaceModels();

                })

                .catch(() => {

                    document
                        .querySelector('.camera-box')
                        .innerHTML =
                        '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#64748b;font-size:.875rem;padding:1rem;text-align:center">Camera unavailable.</div>';

                });

        })();


        /*
        |--------------------------------------------------------------------------
        | Capture Photo
        |--------------------------------------------------------------------------
        */

        async function capturePhoto() {

            if (processingFace) {
                return;
            }

            if (!faceModelsLoaded) {

                setScanStatus(
                    'Face recognition is still loading...'
                );

                return;

            }

            canvas.width =
                video.videoWidth || 640;

            canvas.height =
                video.videoHeight || 480;


            const ctx =
                canvas.getContext('2d');


            /*
             * Keep the same mirrored capture
             * used by the existing visitor system.
             */

            ctx.translate(
                canvas.width,
                0
            );

            ctx.scale(
                -1,
                1
            );

            ctx.drawImage(
                video,
                0,
                0
            );

            ctx.setTransform(
                1,
                0,
                0,
                1,
                0,
                0
            );


            capturedPhotoData =
                canvas.toDataURL(
                    'image/jpeg',
                    .6
                );


            photo.src =
                capturedPhotoData;

            photo.style.display =
                'block';


            document.getElementById(
                    'captureBtn'
                ).style.display =
                'none';

            document.getElementById(
                    'retakeBtn'
                ).style.display =
                '';


            await identifyFace();

        }


        /*
        |--------------------------------------------------------------------------
        | Retake
        |--------------------------------------------------------------------------
        */

        function retakePhoto() {

            capturedPhotoData = null;

            currentFaceDescriptor = null;

            photo.style.display =
                'none';

            document.getElementById(
                    'captureBtn'
                ).style.display =
                '';

            document.getElementById(
                    'retakeBtn'
                ).style.display =
                'none';

            document.getElementById(
                    'captureBtn'
                ).disabled =
                false;

            processingFace = false;

            setScanStatus(
                'Camera ready. Press Scan Face to continue.'
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Identify Driver Face
        |--------------------------------------------------------------------------
        */

        async function identifyFace() {

            if (processingFace) {
                return;
            }

            processingFace = true;


            const btn =
                document.getElementById(
                    'captureBtn'
                );

            btn.disabled = true;

            btn.textContent =
                'Verifying...';


            setScanStatus(
                'Verifying driver...'
            );


            try {

                const detection =
                    await faceapi
                    .detectSingleFace(
                        canvas,
                        new faceapi.TinyFaceDetectorOptions({

                            /*
                             * Same detection settings
                             * used by your current system.
                             */

                            inputSize: 160,

                            scoreThreshold: 0.3

                        })
                    )
                    .withFaceLandmarks()
                    .withFaceDescriptor();


                console.log(
                    'Driver detection:',
                    detection
                );


                if (!detection) {

                    setScanStatus(
                        'No face detected. Please try again.'
                    );

                    btn.disabled =
                        false;

                    btn.textContent =
                        'Scan Face';

                    processingFace =
                        false;

                    return;

                }


                currentFaceDescriptor =
                    Array.from(
                        detection.descriptor
                    );


                /*
                 * Send the descriptor to the dedicated
                 * driver lookup endpoint.
                 */

                const response =
                    await fetch(
                        'api/driver_lookup.php', {

                            method: 'POST',

                            headers: {
                                'Content-Type': 'application/json'
                            },

                            body: JSON.stringify({

                                descriptor: currentFaceDescriptor

                            })

                        }
                    );


                const data =
                    await response.json();


                console.log(
                    'Driver lookup:',
                    data
                );


                if (
                    data.success &&
                    data.driver_id
                ) {

                    setScanStatus(
                        'Driver verified. Continuing...'
                    );


                    /*
                     * driver_lookup.php is responsible
                     * for creating $_SESSION['driver_id'].
                     */

                    window.location.href =
                        data.redirect ||
                        'trip_start.php';

                    return;

                }


                setScanStatus(
                    data.message ||
                    'Driver not recognized.'
                );


                btn.disabled =
                    false;

                btn.textContent =
                    'Scan Face';

                processingFace =
                    false;

            } catch (error) {

                console.error(
                    'Driver lookup error:',
                    error
                );


                setScanStatus(
                    'Unable to verify driver. Please try again.'
                );


                btn.disabled =
                    false;

                btn.textContent =
                    'Scan Face';

                processingFace =
                    false;

            }

        }
    </script>

</body>

</html>