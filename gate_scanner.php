<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header("Location: index.php");
    exit();
}

$operator = htmlspecialchars($_SESSION['fullname'] ?? 'Staff');
$today    = date('l, F j, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gate Scanner — School Attendance</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:      #0a0f1e;
            --surface: #111827;
            --border:  #1e293b;
            --green:   #22c55e;
            --green-d: #16a34a;
            --red:     #ef4444;
            --amber:   #f59e0b;
            --blue:    #3b82f6;
            --text:    #f1f5f9;
            --muted:   #64748b;
        }

        html, body {
            height: 100%;
            font-family: 'Sora', sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .layout {
            display: grid;
            grid-template-columns: 1fr 380px;
            grid-template-rows: 64px 1fr;
            height: 100vh;
        }

        .topbar {
            grid-column: 1 / -1;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--green);
            animation: blink 1.5s ease-in-out infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.3; }
        }

        .topbar h1 { font-size: 16px; font-weight: 700; letter-spacing: -0.3px; }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
            font-size: 13px;
            color: var(--muted);
        }

        .topbar-right strong { color: var(--text); }

        .scanner-panel {
            background: var(--bg);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 32px;
            gap: 20px;
            position: relative;
        }

        .scanner-label {
            font-size: 11px;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 600;
        }

        /* Tab switcher */
        .tab-row {
            display: flex;
            gap: 8px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 4px;
        }

        .tab-btn {
            padding: 8px 20px;
            border-radius: 8px;
            border: none;
            background: transparent;
            color: var(--muted);
            font-family: 'Sora', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .tab-btn.active {
            background: var(--green-d);
            color: #fff;
        }

        #reader {
            width: 100%;
            max-width: 480px;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 0 0 1px var(--border), 0 0 40px rgba(34,197,94,0.08);
            min-height: 300px;
            background: #000;
        }

        #reader video { border-radius: 20px; width: 100% !important; }
        #reader img   { display: none !important; }

        /* Hide the default Html5Qrcode UI elements we don't need */
        #reader > div > select,
        #reader > div > button,
        #reader__dashboard,
        #reader__dashboard_section,
        #reader__filescan_input { display: none !important; }

        /* Scan frame: corners only — no darkening shadow that hurts QR decode */
        .scan-frame {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 10;
            border-radius: 20px;
        }

        /* Four corner brackets via a pseudo + box-shadow trick — no fill */
        .scan-frame::before,
        .scan-frame::after {
            content: '';
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 200px; height: 200px;
        }

        /* Green corner brackets */
        .scan-frame::before {
            border: 3px solid transparent;
            border-radius: 10px;
            background:
                linear-gradient(var(--bg), var(--bg)) padding-box,
                linear-gradient(var(--green), var(--green)) border-box;
            /* Only show corners using clip tricks */
            -webkit-mask:
                linear-gradient(#fff 0 0) content-box,
                linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            border: 3px solid var(--green);
            clip-path: polygon(
                0 16px, 0 0, 16px 0,
                calc(100% - 16px) 0, 100% 0, 100% 16px,
                100% calc(100% - 16px), 100% 100%, calc(100% - 16px) 100%,
                16px 100%, 0 100%, 0 calc(100% - 16px)
            );
        }

        /* Scan line animation */
        .scan-frame::after {
            width: 190px; height: 2px;
            background: linear-gradient(90deg, transparent, var(--green), transparent);
            border-radius: 99px;
            animation: scanline 2s ease-in-out infinite;
        }

        @keyframes scanline {
            0%   { transform: translate(-50%, -50%) translateY(-90px); opacity: 0; }
            10%  { opacity: 1; }
            90%  { opacity: 1; }
            100% { transform: translate(-50%, -50%) translateY(90px); opacity: 0; }
        }

        /* Upload panel */
        .upload-panel {
            width: 100%;
            max-width: 480px;
            display: none;
            flex-direction: column;
            align-items: center;
            gap: 16px;
        }

        .upload-panel.active { display: flex; }

        .upload-drop {
            width: 100%;
            border: 2px dashed var(--border);
            border-radius: 20px;
            padding: 48px 24px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
            position: relative;
        }

        .upload-drop:hover {
            border-color: var(--green);
            background: rgba(34,197,94,0.04);
        }

        .upload-drop input[type=file] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .upload-icon { font-size: 40px; margin-bottom: 12px; }

        .upload-drop p {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .upload-drop strong { color: var(--text); }

        #uploadPreview {
            max-width: 260px;
            max-height: 260px;
            border-radius: 12px;
            border: 2px solid var(--border);
            display: none;
        }

        .scanner-hint {
            font-size: 13px;
            color: var(--muted);
            text-align: center;
            max-width: 360px;
            line-height: 1.6;
        }

        .side-panel {
            background: var(--surface);
            border-left: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 24px;
            gap: 20px;
            overflow-y: auto;
        }

        .stats-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .stat-box {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
        }

        .stat-box .val { font-size: 28px; font-weight: 800; line-height: 1; }
        .stat-box .lbl { font-size: 11px; color: var(--muted); margin-top: 4px; font-weight: 500; }
        .val.green { color: var(--green); }
        .val.red   { color: var(--red); }

        .result-card {
            border-radius: 16px;
            padding: 20px;
            display: none;
            flex-direction: column;
            gap: 10px;
            animation: slideIn 0.3s cubic-bezier(0.34,1.56,0.64,1) both;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .result-card.success { background: #052e16; border: 1px solid #166534; display: flex; }
        .result-card.error   { background: #1c0505; border: 1px solid #7f1d1d; display: flex; }
        .result-card.warn    { background: #1c1505; border: 1px solid #78350f; display: flex; }

        .result-icon { font-size: 32px; }
        .result-name { font-size: 17px; font-weight: 700; }
        .result-sub  { font-size: 12px; color: var(--muted); }
        .result-time { font-size: 13px; font-weight: 600; margin-top: 4px; }

        .log-header {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
            padding-bottom: 10px;
        }

        .log-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
            overflow-y: auto;
        }

        .log-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 10px;
            background: var(--bg);
            border: 1px solid var(--border);
            animation: fadeIn 0.25s ease both;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateX(8px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        .log-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .log-dot.present { background: var(--green); }
        .log-dot.error   { background: var(--red); }

        .log-name  { font-size: 13px; font-weight: 600; flex: 1; }
        .log-badge {
            font-size: 10px; font-weight: 700;
            padding: 3px 10px; border-radius: 99px; letter-spacing: 0.5px;
        }

        .log-badge.present { background: #052e16; color: var(--green); }
        .log-badge.error   { background: #1c0505; color: var(--red); }
        .log-badge.dup     { background: #1c1505; color: var(--amber); }
        .log-time  { font-size: 11px; color: var(--muted); }

        .empty-log {
            text-align: center;
            color: var(--muted);
            font-size: 13px;
            padding: 24px 0;
        }

        @media (max-width: 768px) {
            .layout {
                grid-template-columns: 1fr;
                grid-template-rows: 56px auto auto;
            }
            .side-panel { border-left: none; border-top: 1px solid var(--border); }
        }
    </style>
</head>
<body>

<div class="layout">

    <!-- TOP BAR -->
    <div class="topbar">
        <div class="topbar-left">
            <div class="logo-dot"></div>
            <h1>Gate Scanner — EduTrack</h1>
        </div>
        <div class="topbar-right">
            <span>📅 <?= $today ?></span>
            <span>Operator: <strong><?= $operator ?></strong></span>
            <a href="admin_dashboard.php" style="color:var(--muted);font-size:12px;text-decoration:none;">← Dashboard</a>
        </div>
    </div>

    <!-- SCANNER PANEL -->
    <div class="scanner-panel">
        <span class="scanner-label">Point camera at student QR code</span>

        <!-- Tab switcher: Camera vs Upload -->
        <div class="tab-row">
            <button class="tab-btn active" onclick="switchTab('camera')">📷 Camera Scan</button>
            <button class="tab-btn"        onclick="switchTab('upload')">📁 Upload QR Image</button>
        </div>

        <!-- Camera mode -->
        <div id="cameraMode" style="position:relative; width:100%; max-width:480px;">
            <div id="reader"></div>
            <div class="scan-frame"></div>
        </div>

        <!-- Upload mode -->
        <div id="uploadMode" class="upload-panel">
            <div class="upload-drop">
                <input type="file" id="qrFileInput" accept="image/*" onchange="handleFileUpload(event)">
                <div class="upload-icon">📎</div>
                <p><strong>Click to upload QR image</strong><br>
                or drag and drop the PNG from the student's email attachment</p>
            </div>
            <img id="uploadPreview" alt="QR Preview">
            <p id="uploadStatus" style="font-size:13px;color:var(--muted);"></p>
        </div>

        <p class="scanner-hint" id="scannerHint">
            Students show their saved QR image from their email at the camera.
            Or switch to <strong>Upload QR Image</strong> to scan from a file.
        </p>
    </div>

    <!-- SIDE PANEL -->
    <div class="side-panel">

        <div class="stats-row">
            <div class="stat-box">
                <div class="val green" id="countPresent">0</div>
                <div class="lbl">Present Today</div>
            </div>
            <div class="stat-box">
                <div class="val red" id="countDup">0</div>
                <div class="lbl">Already Scanned</div>
            </div>
        </div>

        <div id="resultCard" class="result-card">
            <div class="result-icon" id="resultIcon"></div>
            <div>
                <div class="result-name" id="resultName"></div>
                <div class="result-sub"  id="resultSub"></div>
                <div class="result-time" id="resultTime"></div>
            </div>
        </div>

        <div class="log-header">Today's Scan Log</div>
        <div class="log-list" id="logList">
            <div class="empty-log">No scans yet today</div>
        </div>

    </div>
</div>

<script>
let scanCooldown = false;
let presentCount = 0;
let dupCount     = 0;
let html5QrCode  = null;
let cameraStarted = false;

// ── Tab switching ─────────────────────────────────────────────────────────
function switchTab(mode) {
    const btns = document.querySelectorAll('.tab-btn');
    btns.forEach(b => b.classList.remove('active'));

    if (mode === 'camera') {
        btns[0].classList.add('active');
        document.getElementById('cameraMode').style.display = 'block';
        document.getElementById('uploadMode').classList.remove('active');
        setHint('Point camera at QR code. Or switch to <strong>Upload QR Image</strong> to scan from a file.');
        startCamera();
    } else {
        btns[1].classList.add('active');
        document.getElementById('cameraMode').style.display = 'none';
        document.getElementById('uploadMode').classList.add('active');
        setHint('Upload the QR PNG from the student\'s email attachment to mark attendance.');
        stopCamera();
    }
}

function setHint(html) {
    document.getElementById('scannerHint').innerHTML = html;
}

// ── Camera: start ─────────────────────────────────────────────────────────
function startCamera() {
    if (cameraStarted) return;

    html5QrCode = new Html5Qrcode("reader", { verbose: false });

    const config = {
        fps: 10,
        // No qrbox restriction — scan the full video frame for reliability
        supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA]
    };

    // Try environment (back) camera first, fall back to any camera
    html5QrCode.start(
        { facingMode: "environment" },
        config,
        onQrDecoded,
        () => {} // per-frame error — silent
    )
    .then(() => {
        cameraStarted = true;
        setHint('📷 Camera active — hold QR code steady in view.');
    })
    .catch(() => {
        // Back camera failed — try any available camera
        html5QrCode.start(
            { facingMode: "user" },
            config,
            onQrDecoded,
            () => {}
        )
        .then(() => {
            cameraStarted = true;
            setHint('📷 Camera active — hold QR code steady in view.');
        })
        .catch(err => {
            setHint('❌ Camera access denied or unavailable. Allow camera permission and reload.');
            console.error('Camera error:', err);
            html5QrCode = null;
        });
    });
}

// ── Camera: stop ──────────────────────────────────────────────────────────
function stopCamera() {
    if (!html5QrCode || !cameraStarted) return;
    html5QrCode.stop()
        .then(() => { html5QrCode.clear(); })
        .catch(() => {})
        .finally(() => {
            html5QrCode   = null;
            cameraStarted = false;
        });
}

// ── QR decoded callback ───────────────────────────────────────────────────
function onQrDecoded(decodedText) {
    if (scanCooldown) return;
    scanCooldown = true;
    playBeep();
    processToken(decodedText);
    setTimeout(() => { scanCooldown = false; }, 2500);
}

// Auto-start camera on page load
startCamera();

// ── Handle file upload scan ───────────────────────────────────────────────
function handleFileUpload(event) {
    const file = event.target.files[0];
    if (!file) return;

    const statusEl  = document.getElementById('uploadStatus');
    const previewEl = document.getElementById('uploadPreview');

    // Show preview
    const fr = new FileReader();
    fr.onload = e => {
        previewEl.src          = e.target.result;
        previewEl.style.display = 'block';
    };
    fr.readAsDataURL(file);

    statusEl.textContent = '⏳ Reading QR code…';

    Html5Qrcode.scanFile(file, true)
        .then(decodedText => {
            statusEl.textContent = '✅ QR decoded — processing…';
            processToken(decodedText);
            event.target.value = '';
        })
        .catch(err => {
            statusEl.textContent = '❌ Could not read QR from image. Use a clear, unedited PNG.';
            console.error('Upload QR error:', err);
            event.target.value = '';
        });
}

// ── Token extraction + attendance call ───────────────────────────────────
function processToken(raw) {
    let token = raw.trim();
    try {
        const url    = new URL(raw);
        const parsed = url.searchParams.get('token');
        if (parsed) token = parsed;
    } catch (_) {}

    fetch("mark_attendance.php?token=" + encodeURIComponent(token))
        .then(r => r.json())
        .then(data => showResult(data))
        .catch(() => showResult({ status: 'error', message: 'Server error. Please try again.' }));
}

// ── Result card ───────────────────────────────────────────────────────────
function showResult(data) {
    const card = document.getElementById('resultCard');
    const icon = document.getElementById('resultIcon');
    const name = document.getElementById('resultName');
    const sub  = document.getElementById('resultSub');
    const time = document.getElementById('resultTime');

    card.className = 'result-card';
    card.style.opacity = '1';

    const now = new Date().toLocaleTimeString('en-US', {
        hour: '2-digit', minute: '2-digit', second: '2-digit'
    });

    if (data.status === 'success') {
        const attStatus = (data.att_status || 'present').toLowerCase();

        // ── Color and icon follow the actual attendance status ──
        if (attStatus === 'present') {
            card.classList.add('success');   // green
            icon.textContent = '✅';
        } else if (attStatus === 'late') {
            card.classList.add('warn');      // amber
            icon.textContent = '🕐';
        } else {
            // absent — scanned outside the valid window
            card.classList.add('error');     // red
            icon.textContent = '❌';
        }

        name.textContent = data.student_name || 'Student';
        sub.textContent  = 'Marked as ' + attStatus.toUpperCase();
        time.textContent = '🕐 ' + now;
        presentCount++;
        document.getElementById('countPresent').textContent = presentCount;
        addLog(data.student_name, attStatus, now);

    } else if (data.status === 'duplicate') {
        card.classList.add('warn');
        icon.textContent = '⚠️';
        name.textContent = data.student_name || 'Student';
        sub.textContent  = 'Already scanned today at ' + (data.scanned_at || '');
        time.textContent = '🕐 ' + now;
        dupCount++;
        document.getElementById('countDup').textContent = dupCount;
        addLog(data.student_name, 'dup', now);

    } else {
        card.classList.add('error');
        icon.textContent = '❌';
        name.textContent = 'Scan Failed';
        sub.textContent  = data.message || 'Invalid or unrecognized QR code.';
        time.textContent = '🕐 ' + now;
        addLog('Unknown QR', 'error', now);
    }

    document.getElementById('uploadStatus').textContent = '';
    setTimeout(() => { card.style.opacity = '0.4'; }, 4000);
    setTimeout(() => { card.style.opacity = '1';   }, 6000);
}

// ── Scan log ──────────────────────────────────────────────────────────────
function addLog(studentName, type, time) {
    const list  = document.getElementById('logList');
    const empty = list.querySelector('.empty-log');
    if (empty) empty.remove();

    // Map status to label and dot color
    const labels   = { present: 'Present', late: 'Late', absent: 'Absent', error: 'Error', dup: 'Duplicate' };
    const dotClass = (type === 'present') ? 'present'
                   : (type === 'late')    ? 'late'
                   : (type === 'absent')  ? 'absent-dot'
                   : 'error';

    const item = document.createElement('div');
    item.className = 'log-item';
    item.innerHTML = `
        <div class="log-dot ${dotClass}"></div>
        <span class="log-name">${studentName}</span>
        <span class="log-badge ${type}">${labels[type] ?? type}</span>
        <span class="log-time">${time}</span>
    `;
    list.insertBefore(item, list.firstChild);

    const items = list.querySelectorAll('.log-item');
    if (items.length > 50) items[items.length - 1].remove();
    
}
.log-dot.late        { background: var(--amber); }
.log-dot.absent-dot  { background: var(--red); }

.log-badge.late   { background: #1c1505; color: var(--amber); }
.log-badge.absent { background: #1c0505; color: var(--red);
 }
// ── Beep ──────────────────────────────────────────────────────────────────
function playBeep() {
    try {
        const ctx  = new (window.AudioContext || window.webkitAudioContext)();
        const osc  = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain); gain.connect(ctx.destination);
        osc.frequency.value = 880; osc.type = 'sine';
        gain.gain.setValueAtTime(0.3, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.25);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.25);
    } catch(e) {}
}
</script>

</body>
</html>