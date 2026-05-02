<?php
/**
 * my_qr.php — Student QR Display Page
 * Students open this on their phone at the school entrance.
 * Shows their QR code fullscreen, bright, easy to scan.
 */
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT fullname, qr_code FROM users WHERE id = ? AND role = 'student'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result || empty($result['qr_code'])) {
    die("QR code not found. Please contact your administrator.");
}

$fullname = htmlspecialchars($result['fullname']);

// Support both old (full URL) and new (local relative path) formats
$raw_qr = $result['qr_code'];
$qr_url = htmlspecialchars(
    str_starts_with($raw_qr, 'http')
        ? $raw_qr
        : '/School-Attendance-Notification-System/' . $raw_qr
);

$today    = date('l, F j, Y');
$time_now = date('h:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>My QR Code — <?= $fullname ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --green:  #16a34a;
            --green2: #22c55e;
            --dark:   #0f172a;
            --card:   #ffffff;
            --text:   #1e293b;
            --muted:  #64748b;
        }

        html, body {
            height: 100%;
            font-family: 'Sora', sans-serif;
            background: var(--dark);
            color: var(--card);
            overflow: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: radial-gradient(ellipse at 20% 20%, #064e3b44 0%, transparent 60%),
                        radial-gradient(ellipse at 80% 80%, #14532d33 0%, transparent 60%),
                        var(--dark);
            z-index: 0;
        }

        .page {
            position: relative;
            z-index: 1;
            height: 100dvh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 20px;
            gap: 20px;
        }

        .school-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--green2);
            opacity: 0.85;
        }

        .school-label::before,
        .school-label::after {
            content: '';
            width: 28px;
            height: 1px;
            background: var(--green2);
            opacity: 0.4;
        }

        .qr-card {
            background: var(--card);
            border-radius: 28px;
            padding: 28px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            box-shadow:
                0 0 0 1px rgba(34,197,94,0.15),
                0 0 60px rgba(34,197,94,0.12),
                0 32px 64px rgba(0,0,0,0.5);
            animation: cardIn 0.5s cubic-bezier(0.34,1.56,0.64,1) both;
            max-width: 320px;
            width: 100%;
        }

        @keyframes cardIn {
            from { opacity: 0; transform: scale(0.88) translateY(20px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }

        .qr-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qr-wrapper::before {
            content: '';
            position: absolute;
            inset: -10px;
            border-radius: 18px;
            border: 2px solid var(--green2);
            opacity: 0;
            animation: pulse 2.5s ease-in-out infinite;
        }

        @keyframes pulse {
            0%   { opacity: 0;    transform: scale(0.95); }
            50%  { opacity: 0.35; transform: scale(1.02); }
            100% { opacity: 0;    transform: scale(1.06); }
        }

        .qr-img {
            width: 220px;
            height: 220px;
            border-radius: 12px;
            display: block;
        }

        .qr-wrapper::after {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 16px;
            background:
                linear-gradient(var(--card), var(--card)) padding-box,
                linear-gradient(135deg, var(--green2), transparent 50%, transparent 50%, var(--green2)) border-box;
            border: 2px solid transparent;
        }

        .student-name {
            font-size: 18px;
            font-weight: 800;
            color: var(--text);
            text-align: center;
            line-height: 1.2;
        }

        .student-badge {
            background: #dcfce7;
            color: var(--green);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 4px 14px;
            border-radius: 99px;
        }

        .datetime {
            text-align: center;
        }

        .datetime .date {
            font-size: 13px;
            color: var(--muted);
            font-weight: 500;
        }

        .datetime .time {
            font-size: 32px;
            font-weight: 800;
            color: var(--text);
            letter-spacing: -1px;
            line-height: 1.1;
        }

        .instruction {
            font-size: 12px;
            color: rgba(255,255,255,0.45);
            text-align: center;
            letter-spacing: 0.3px;
            max-width: 260px;
            line-height: 1.5;
        }

        .back-btn {
            position: fixed;
            top: 16px;
            left: 16px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            color: rgba(255,255,255,0.7);
            font-family: 'Sora', sans-serif;
            font-size: 12px;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 99px;
            cursor: pointer;
            text-decoration: none;
            backdrop-filter: blur(8px);
            transition: background 0.2s, color 0.2s;
            z-index: 10;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.15);
            color: #fff;
        }

        .awake-hint {
            position: fixed;
            bottom: 16px;
            font-size: 11px;
            color: rgba(255,255,255,0.25);
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

<a href="student_dashboard.php" class="back-btn">← Back</a>

<div class="page">
    <div class="school-label">Attendance QR Code</div>

    <div class="qr-card">
        <div class="qr-wrapper">
            <img src="<?= $qr_url ?>" alt="QR Code" class="qr-img">
        </div>

        <div>
            <div class="student-name"><?= $fullname ?></div>
        </div>

        <div class="student-badge">Student</div>

        <div class="datetime">
            <div class="time" id="liveClock"><?= $time_now ?></div>
            <div class="date"><?= $today ?></div>
        </div>
    </div>

    <p class="instruction">
        Show this QR code to the scanner at the school entrance gate
    </p>
</div>

<span class="awake-hint">Keep screen brightness high for best scanning</span>

<script>
    function updateClock() {
        const now  = new Date();
        let h      = now.getHours();
        const m    = String(now.getMinutes()).padStart(2, '0');
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        document.getElementById('liveClock').textContent = `${h}:${m} ${ampm}`;
    }
    setInterval(updateClock, 1000);

    async function keepAwake() {
        try {
            if ('wakeLock' in navigator) {
                await navigator.wakeLock.request('screen');
            }
        } catch(e) { /* silently ignore if not supported */ }
    }
    keepAwake();
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') keepAwake();
    });
</script>
</body>
</html>