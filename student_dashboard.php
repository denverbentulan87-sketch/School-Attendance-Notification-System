<?php
session_start();
include 'includes/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student'){
    header("Location: index.php");
    exit();
}

$name       = $_SESSION['name'];
$role       = $_SESSION['role'];
$page       = $_GET['page'] ?? 'dashboard';
$student_id = $_SESSION['user_id'];

/* ── Fetch student's own data ── */
$sq = $conn->prepare("SELECT fullname, email, parent_email, qr_code, qr_token FROM users WHERE id = ?");
$sq->bind_param("i", $student_id);
$sq->execute();
$me = $sq->get_result()->fetch_assoc();

$aq = $conn->prepare("SELECT COALESCE(SUM(status='present'),0) AS present, COALESCE(SUM(status='absent'),0) AS absent FROM attendance WHERE student_id = ?");
$aq->bind_param("i", $student_id);
$aq->execute();
$my_att = $aq->get_result()->fetch_assoc();

$has_qr = !empty($me['qr_code']) && !empty($me['qr_token']);

/* ── Avatar initials + colour ── */
function getInitials(string $n): string {
    $p = explode(' ', trim($n));
    $i = strtoupper(substr($p[0],0,1));
    if (count($p)>1) $i .= strtoupper(substr(end($p),0,1));
    return $i;
}
$initials   = getInitials($name);
$ava_colors = ['#3b82f6','#16a34a','#be185d','#9333ea','#ea580c','#0891b2'];
$avatar_bg  = $ava_colors[$student_id % count($ava_colors)];
?>
<!DOCTYPE html>
<html>
<head>
<title>Student Dashboard – EduTrack</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&family=DM+Sans:wght@400;500;600&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Poppins',sans-serif;background:#f4f7fb;display:flex;min-height:100vh;}

/* ════ SIDEBAR ════ */
.sidebar{width:250px;min-height:100vh;background:#1a2332;color:#fff;display:flex;flex-direction:column;justify-content:space-between;position:fixed;top:0;left:0;bottom:0;}
.sidebar-top{display:flex;flex-direction:column;}
.sidebar-brand{display:flex;align-items:center;gap:12px;padding:22px 20px 18px;border-bottom:1px solid rgba(255,255,255,0.07);}
.brand-icon{width:40px;height:40px;background:#22c55e;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;}
.brand-text h2{font-size:16px;font-weight:600;color:#fff;letter-spacing:0.3px;}
.brand-text span{font-size:10px;color:#94a3b8;letter-spacing:1.5px;text-transform:uppercase;}
.sidebar-section-label{font-size:10px;font-weight:600;color:#64748b;letter-spacing:1.5px;text-transform:uppercase;padding:18px 20px 6px;}
.sidebar nav a{display:flex;align-items:center;gap:10px;padding:10px 20px;color:#94a3b8;text-decoration:none;font-size:14px;transition:all 0.2s;margin:2px 10px;border-radius:8px;}
.sidebar nav a:hover{background:rgba(255,255,255,0.07);color:#e2e8f0;}
.sidebar nav a.active{background:#22c55e;color:#fff;font-weight:500;}
.sidebar nav a .nav-icon{width:18px;text-align:center;font-size:15px;}
.sidebar-user{display:flex;align-items:center;gap:10px;padding:16px 20px;border-top:1px solid rgba(255,255,255,0.07);}
.sidebar-avatar{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;color:#fff;flex-shrink:0;}
.user-info{flex:1;overflow:hidden;}
.user-info .uname{font-size:13px;font-weight:500;color:#e2e8f0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.user-info .urole{font-size:11px;color:#64748b;}
.logout-btn{color:#94a3b8;text-decoration:none;font-size:18px;padding:4px;border-radius:6px;transition:color 0.2s;}
.logout-btn:hover{color:#f87171;}

/* ════ MAIN ════ */
.main{flex:1;margin-left:250px;padding:28px 30px;min-height:100vh;}
.top-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:28px;}
.welcome-text{font-size:26px;font-weight:600;color:#1e293b;}
.welcome-sub{font-size:13px;color:#64748b;margin-top:3px;}
.role-badge{background:#3b82f6;color:white;padding:7px 16px;border-radius:20px;font-size:13px;font-weight:500;}

/* ════ PROFILE PAGE ════ */
.profile-wrap{display:grid;grid-template-columns:290px 1fr;gap:22px;align-items:start;}

/* Profile card */
.profile-card{background:#fff;border-radius:20px;box-shadow:0 2px 16px rgba(0,0,0,0.07);overflow:hidden;}
.profile-card-top{padding:30px 22px 22px;text-align:center;background:linear-gradient(160deg,#1a2332 0%,#243447 100%);}
.profile-big-avatar{width:76px;height:76px;border-radius:50%;margin:0 auto 12px;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:700;color:#fff;border:3px solid rgba(255,255,255,0.18);}
.profile-fullname{font-family:'Sora',sans-serif;font-size:16px;font-weight:700;color:#fff;margin-bottom:6px;}
.profile-role-pill{display:inline-flex;align-items:center;gap:5px;background:rgba(34,197,94,0.2);color:#4ade80;font-size:11px;font-weight:600;padding:3px 12px;border-radius:20px;border:1px solid rgba(34,197,94,0.3);}
.cred-list{padding:16px 20px;}
.cred-item{display:flex;align-items:flex-start;gap:12px;padding:11px 0;border-bottom:1px solid #f1f5f9;}
.cred-item:last-child{border-bottom:none;}
.cred-icon{width:32px;height:32px;border-radius:8px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:14px;}
.ci-blue{background:#dbeafe;} .ci-green{background:#dcfce7;} .ci-purple{background:#ede9fe;} .ci-amber{background:#fef3c7;}
.cred-details{flex:1;min-width:0;}
.cred-label{font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.6px;margin-bottom:3px;}
.cred-value{font-size:13px;font-weight:500;color:#0f1923;word-break:break-all;line-height:1.4;}
.cred-masked{font-size:13px;color:#94a3b8;letter-spacing:3px;}

/* QR panel */
.qr-panel{background:#fff;border-radius:20px;box-shadow:0 2px 16px rgba(0,0,0,0.07);overflow:hidden;}
.qr-panel-header{padding:18px 22px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;}
.qr-panel-title{font-family:'Sora',sans-serif;font-size:15px;font-weight:700;color:#0f1923;display:flex;align-items:center;gap:8px;}
.qr-active-pill{display:inline-flex;align-items:center;gap:5px;background:#dcfce7;color:#15803d;font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;}
.qr-no-pill{display:inline-flex;align-items:center;gap:5px;background:#fff7ed;color:#c2410c;font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;border:1px dashed #fdba74;}
.qr-panel-body{padding:26px 22px;}
.qr-display-grid{display:grid;grid-template-columns:auto 1fr;gap:24px;align-items:start;}
.qr-big-img{width:150px;height:150px;border-radius:14px;border:2px solid #e2e8f0;display:block;cursor:pointer;transition:transform 0.15s,box-shadow 0.15s;}
.qr-big-img:hover{transform:scale(1.04);box-shadow:0 8px 24px rgba(0,0,0,0.13);}
.qr-click-hint{font-size:10px;color:#94a3b8;text-align:center;margin-top:5px;}
.qr-info-title{font-family:'Sora',sans-serif;font-size:15px;font-weight:700;color:#0f1923;margin-bottom:6px;}
.qr-info-desc{font-size:12.5px;color:#64748b;line-height:1.65;margin-bottom:14px;}
.qr-token-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:9px 12px;font-family:monospace;font-size:10.5px;color:#64748b;word-break:break-all;margin-bottom:16px;}
.qr-token-box strong{color:#374151;font-weight:600;}
.qr-action-row{display:flex;gap:10px;flex-wrap:wrap;}
.btn-enlarge{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:9px;font-size:12px;font-weight:600;background:#6d28d9;color:#fff;border:none;cursor:pointer;font-family:'DM Sans',sans-serif;transition:background 0.15s,transform 0.1s;text-decoration:none;}
.btn-enlarge:hover{background:#5b21b6;transform:translateY(-1px);}
.btn-download{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:9px;font-size:12px;font-weight:600;background:#16a34a;color:#fff;border:none;cursor:pointer;font-family:'DM Sans',sans-serif;transition:background 0.15s,transform 0.1s;text-decoration:none;}
.btn-download:hover{background:#15803d;transform:translateY(-1px);}
.no-qr-state{text-align:center;padding:40px 20px;}
.no-qr-icon{font-size:48px;margin-bottom:12px;}
.no-qr-title{font-family:'Sora',sans-serif;font-size:16px;font-weight:700;color:#f59e0b;margin-bottom:6px;}
.no-qr-desc{font-size:13px;color:#94a3b8;line-height:1.6;}

/* ════ QR MODAL ════ */
.stu-modal{display:none;position:fixed;z-index:9999;inset:0;background:rgba(10,20,40,0.58);justify-content:center;align-items:center;backdrop-filter:blur(4px);}
.stu-modal-box{background:#fff;border-radius:22px;width:340px;box-shadow:0 30px 80px rgba(0,0,0,0.22);animation:modalUp 0.22s cubic-bezier(.25,.8,.25,1);overflow:hidden;}
@keyframes modalUp{from{transform:translateY(20px) scale(0.97);opacity:0;}to{transform:translateY(0) scale(1);opacity:1;}}
.stu-modal-header{padding:20px 22px 18px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;background:linear-gradient(135deg,#1a2332 0%,#243447 100%);}
.stu-modal-title{font-family:'Sora',sans-serif;font-size:16px;font-weight:700;color:#fff;}
.stu-modal-sub{font-size:11px;color:#94a3b8;margin-top:3px;}
.stu-modal-close{width:30px;height:30px;border-radius:8px;background:rgba(255,255,255,0.1);border:none;cursor:pointer;font-size:16px;color:#fff;display:flex;align-items:center;justify-content:center;transition:background 0.15s;}
.stu-modal-close:hover{background:rgba(239,68,68,0.3);}
.stu-modal-body{padding:24px;display:flex;flex-direction:column;align-items:center;gap:14px;}
.stu-modal-qr{width:210px;height:210px;border-radius:14px;border:2px solid #e2e8f0;display:block;}
.stu-modal-hint{font-size:12px;color:#64748b;text-align:center;line-height:1.6;}
.stu-modal-actions{display:flex;gap:10px;width:100%;}
.btn-dl-qr{flex:1;padding:11px 16px;background:#16a34a;color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;text-align:center;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:6px;transition:background 0.15s;}
.btn-dl-qr:hover{background:#15803d;}
.btn-close-qr{flex:1;padding:11px 16px;background:#fff;color:#374151;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;transition:background 0.15s,color 0.15s;}
.btn-close-qr:hover{background:#fef2f2;color:#dc2626;border-color:#fca5a5;}
</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-top">
        <div class="sidebar-brand">
            <div class="brand-icon">&#x1F393;</div>
            <div class="brand-text"><h2>EduTrack</h2><span>Student Portal</span></div>
        </div>

        <div class="sidebar-section-label">Main Menu</div>
        <nav>
            <a href="?page=dashboard" class="<?= $page=='dashboard'?'active':'' ?>">
                <span class="nav-icon">&#x1F4CA;</span> Dashboard
            </a>
            <a href="?page=attendance" class="<?= $page=='attendance'?'active':'' ?>">
                <span class="nav-icon">&#x1F4C5;</span> My Attendance
            </a>
            <a href="?page=notifications" class="<?= $page=='notifications'?'active':'' ?>">
                <span class="nav-icon">&#x1F514;</span> Notifications
            </a>
        </nav>

        <div class="sidebar-section-label">Account</div>
        <nav>
            <a href="?page=profile" class="<?= $page=='profile'?'active':'' ?>">
                <span class="nav-icon">&#x1F464;</span> My Profile
            </a>
        </nav>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-avatar" style="background:<?= $avatar_bg ?>;"><?= $initials ?></div>
        <div class="user-info">
            <div class="uname"><?= htmlspecialchars($name) ?></div>
            <div class="urole">Student</div>
        </div>
        <a href="logout.php" class="logout-btn" title="Logout">&#x23FB;</a>
    </div>
</div>

<!-- MAIN -->
<div class="main">

    <div class="top-header">
        <div>
            <div class="welcome-text">
                <?= $page==='profile' ? 'My Profile &#x1F464;' : 'Welcome, '.htmlspecialchars($name).' &#x1F44B;' ?>
            </div>
            <div class="welcome-sub">
                <?= $page==='profile' ? 'Your account credentials and QR attendance code.' : 'View your attendance records and notifications.' ?>
            </div>
        </div>
        <div class="role-badge"><?= ucfirst($role) ?></div>
    </div>

    <!-- ════ MY PROFILE ════ -->
    <?php if ($page === 'profile'): ?>
    <div class="profile-wrap">

        <!-- LEFT: credentials card -->
        <div class="profile-card">
            <div class="profile-card-top">
                <div class="profile-big-avatar" style="background:<?= $avatar_bg ?>;"><?= $initials ?></div>
                <div class="profile-fullname"><?= htmlspecialchars($me['fullname']) ?></div>
                <div style="margin-top:6px;"><span class="profile-role-pill">&#x2714; Student</span></div>
            </div>
            <div class="cred-list">

                <div class="cred-item">
                    <div class="cred-icon ci-blue">&#x1F464;</div>
                    <div class="cred-details">
                        <div class="cred-label">Full Name</div>
                        <div class="cred-value"><?= htmlspecialchars($me['fullname']) ?></div>
                    </div>
                </div>

                <div class="cred-item">
                    <div class="cred-icon ci-green">&#x2709;&#xFE0F;</div>
                    <div class="cred-details">
                        <div class="cred-label">Student Email (Login)</div>
                        <div class="cred-value"><?= htmlspecialchars($me['email']) ?></div>
                    </div>
                </div>

                <div class="cred-item">
                    <div class="cred-icon ci-amber">&#x1F4E7;</div>
                    <div class="cred-details">
                        <div class="cred-label">Parent Email</div>
                        <?php if (!empty($me['parent_email'])): ?>
                            <div class="cred-value"><?= htmlspecialchars($me['parent_email']) ?></div>
                        <?php else: ?>
                            <div class="cred-value" style="color:#f59e0b;font-style:italic;">Not set</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="cred-item">
                    <div class="cred-icon ci-purple">&#x1F512;</div>
                    <div class="cred-details">
                        <div class="cred-label">Password</div>
                        <div class="cred-masked">&#x25CF;&#x25CF;&#x25CF;&#x25CF;&#x25CF;&#x25CF;&#x25CF;&#x25CF;</div>
                    </div>
                </div>

                <?php
                    $total = $my_att['present'] + $my_att['absent'];
                    $rate  = $total > 0 ? round(($my_att['present'] / $total) * 100) : 0;
                ?>
                <div class="cred-item">
                    <div class="cred-icon ci-green">&#x1F4CA;</div>
                    <div class="cred-details">
                        <div class="cred-label">Attendance</div>
                        <div class="cred-value">
                            <span style="color:#15803d;font-weight:600;"><?= $my_att['present'] ?> present</span>
                            &nbsp;/&nbsp;
                            <span style="color:#b91c1c;font-weight:600;"><?= $my_att['absent'] ?> absent</span>
                            &nbsp;&mdash;&nbsp;<strong><?= $rate ?>%</strong>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- RIGHT: QR panel -->
        <div class="qr-panel">
            <div class="qr-panel-header">
                <div class="qr-panel-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6d28d9" stroke-width="2.5" stroke-linecap="round"><rect x="3" y="3" width="5" height="5" rx="1"/><rect x="16" y="3" width="5" height="5" rx="1"/><rect x="3" y="16" width="5" height="5" rx="1"/><path d="M16 16h2v2h-2zM18 20v-2M20 18h-2"/></svg>
                    My QR Attendance Code
                </div>
                <?php if ($has_qr): ?>
                    <span class="qr-active-pill">&#x2714; Active</span>
                <?php else: ?>
                    <span class="qr-no-pill">&#x26A0; Not Assigned</span>
                <?php endif; ?>
            </div>

            <div class="qr-panel-body">
                <?php if ($has_qr): ?>
                <div class="qr-display-grid">
                    <div>
                        <img src="<?= htmlspecialchars($me['qr_code']) ?>"
                             class="qr-big-img" alt="My QR Code"
                             title="Click to enlarge"
                             onclick="openStuQrModal()">
                        <div class="qr-click-hint">click to enlarge</div>
                    </div>
                    <div>
                        <div class="qr-info-title">Your Attendance QR</div>
                        <div class="qr-info-desc">
                            Show this QR code to your teacher when attending class.
                            Each scan logs your attendance automatically.
                            Do not share this code with others.
                        </div>
                        <div class="qr-token-box">
                            <strong>Token:</strong> <?= htmlspecialchars($me['qr_token']) ?>
                        </div>
                        <div class="qr-action-row">
                            <button class="btn-enlarge" onclick="openStuQrModal()">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                                Enlarge
                            </button>
                            <a href="<?= htmlspecialchars($me['qr_code']) ?>" target="_blank" class="btn-download">
                                &#x2B07; Download
                            </a>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="no-qr-state">
                    <div class="no-qr-icon">&#x1F4F2;</div>
                    <div class="no-qr-title">No QR Code Assigned</div>
                    <div class="no-qr-desc">Your QR code hasn't been generated yet.<br>Please contact your administrator to request one.</div>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <?php elseif ($page == 'dashboard'): ?>
        <?php include 'student_pages/dashboard.php'; ?>
    <?php elseif($page == 'attendance'): ?>
        <?php include 'student_pages/attendance.php'; ?>
    <?php elseif($page == 'notifications'): ?>
        <?php include 'student_pages/notifications.php'; ?>
    <?php elseif($page == 'notif_count'): ?>
        <?php include 'student_pages/notif_count.php'; ?>
    <?php endif; ?>

</div>

<!-- QR ENLARGE MODAL -->
<?php if ($has_qr): ?>
<div id="stuQrModal" class="stu-modal" onclick="if(event.target===this)closeStuQrModal()">
    <div class="stu-modal-box">
        <div class="stu-modal-header">
            <div>
                <div class="stu-modal-title">My QR Code</div>
                <div class="stu-modal-sub"><?= htmlspecialchars($me['fullname']) ?> &mdash; <?= htmlspecialchars($me['email']) ?></div>
            </div>
            <button class="stu-modal-close" onclick="closeStuQrModal()">&#x2715;</button>
        </div>
        <div class="stu-modal-body">
            <img src="<?= htmlspecialchars($me['qr_code']) ?>" class="stu-modal-qr" alt="My QR Code">
            <div class="stu-modal-hint">
                Present this QR to your teacher to mark your attendance.<br>
                Keep it safe &mdash; do not share it with anyone.
            </div>
            <div class="stu-modal-actions">
                <a href="<?= htmlspecialchars($me['qr_code']) ?>" target="_blank" class="btn-dl-qr">&#x2B07; Download</a>
                <button class="btn-close-qr" onclick="closeStuQrModal()">&#x2715; Close</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function openStuQrModal()  { document.getElementById('stuQrModal').style.display = 'flex'; }
function closeStuQrModal() { document.getElementById('stuQrModal').style.display = 'none'; }
</script>
</body>
</html>