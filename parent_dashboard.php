<?php
session_start();
include 'includes/db.php';

// Only parents allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: index.php");
    exit();
}

$parent_id    = $_SESSION['user_id'];
$parent_name  = $_SESSION['name'];
$page         = $_GET['page'] ?? 'dashboard';

/* ── Fetch parent's own email ── */
$pq = $conn->prepare("SELECT email FROM users WHERE id = ?");
$pq->bind_param("i", $parent_id);
$pq->execute();
$parent = $pq->get_result()->fetch_assoc();
$parent_email = $parent['email'];

/* ── Fetch linked child (student whose parent_email = this parent's email) ── */
$cq = $conn->prepare("
    SELECT id, fullname, email, qr_code, qr_token 
    FROM users 
    WHERE role = 'student' AND parent_email = ?
");
$cq->bind_param("s", $parent_email);
$cq->execute();
$children = $cq->get_result()->fetch_all(MYSQLI_ASSOC);

/* ── Avatar helper ── */
function getInitials(string $n): string {
    $p = explode(' ', trim($n));
    $i = strtoupper(substr($p[0], 0, 1));
    if (count($p) > 1) $i .= strtoupper(substr(end($p), 0, 1));
    return $i;
}
$ava_colors = ['#3b82f6','#16a34a','#be185d','#9333ea','#ea580c','#0891b2'];
$avatar_bg  = $ava_colors[$parent_id % count($ava_colors)];
$initials   = getInitials($parent_name);

/* ── Selected child (multi-child support) ── */
$selected_child_id = $_GET['child_id'] ?? ($children[0]['id'] ?? null);
$selected_child    = null;
foreach ($children as $c) {
    if ($c['id'] == $selected_child_id) { $selected_child = $c; break; }
}

/* ── Attendance summary for selected child ── */
$att_records = [];
$present_count = 0;
$absent_count  = 0;

if ($selected_child) {
    $cid = $selected_child['id'];

    $aq = $conn->prepare("
        SELECT a.*
        FROM attendance a
        WHERE a.student_id = ?
        ORDER BY a.created_at DESC
    ");
    $aq->bind_param("i", $cid);
    $aq->execute();
    $att_records = $aq->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($att_records as $r) {
        if ($r['status'] === 'present') $present_count++;
        else $absent_count++;
    }
}

$total       = $present_count + $absent_count;
$attend_rate = $total > 0 ? round(($present_count / $total) * 100) : 0;

/* ── Recent notifications for parent ── */
$notifs = [];
if ($selected_child) {
    $cid = $selected_child['id'];
    $nq  = $conn->prepare("
        SELECT * FROM notifications 
        WHERE student_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $nq->bind_param("i", $cid);
    $nq->execute();
    $notifs = $nq->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Parent Dashboard – EduTrack</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&family=DM+Sans:wght@400;500;600&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Poppins',sans-serif;background:#f4f7fb;display:flex;min-height:100vh;}

/* ════ SIDEBAR ════ */
.sidebar{width:250px;min-height:100vh;background:#1a2332;color:#fff;display:flex;flex-direction:column;justify-content:space-between;position:fixed;top:0;left:0;bottom:0;}
.sidebar-top{display:flex;flex-direction:column;}
.sidebar-brand{display:flex;align-items:center;gap:12px;padding:22px 20px 18px;border-bottom:1px solid rgba(255,255,255,0.07);}
.brand-icon{width:40px;height:40px;background:#f59e0b;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;}
.brand-text h2{font-size:16px;font-weight:600;color:#fff;}
.brand-text span{font-size:10px;color:#94a3b8;letter-spacing:1.5px;text-transform:uppercase;}
.sidebar-section-label{font-size:10px;font-weight:600;color:#64748b;letter-spacing:1.5px;text-transform:uppercase;padding:18px 20px 6px;}
.sidebar nav a{display:flex;align-items:center;gap:10px;padding:10px 20px;color:#94a3b8;text-decoration:none;font-size:14px;transition:all 0.2s;margin:2px 10px;border-radius:8px;}
.sidebar nav a:hover{background:rgba(255,255,255,0.07);color:#e2e8f0;}
.sidebar nav a.active{background:#f59e0b;color:#fff;font-weight:500;}
.sidebar nav a .nav-icon{width:18px;text-align:center;font-size:15px;}
.sidebar-user{display:flex;align-items:center;gap:10px;padding:16px 20px;border-top:1px solid rgba(255,255,255,0.07);}
.sidebar-avatar{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;color:#fff;flex-shrink:0;}
.user-info .uname{font-size:13px;font-weight:500;color:#e2e8f0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.user-info .urole{font-size:11px;color:#64748b;}
.logout-btn{color:#94a3b8;text-decoration:none;font-size:18px;padding:4px;border-radius:6px;transition:color 0.2s;}
.logout-btn:hover{color:#f87171;}

/* ════ MAIN ════ */
.main{flex:1;margin-left:250px;padding:28px 30px;min-height:100vh;}
.top-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:28px;}
.welcome-text{font-size:26px;font-weight:600;color:#1e293b;}
.welcome-sub{font-size:13px;color:#64748b;margin-top:3px;}
.role-badge{background:#f59e0b;color:white;padding:7px 16px;border-radius:20px;font-size:13px;font-weight:500;}

/* ════ CHILD SELECTOR ════ */
.child-selector{display:flex;gap:10px;margin-bottom:24px;flex-wrap:wrap;}
.child-tab{display:flex;align-items:center;gap:8px;padding:9px 18px;border-radius:12px;border:2px solid #e2e8f0;background:#fff;cursor:pointer;text-decoration:none;color:#64748b;font-size:13px;font-weight:500;transition:all 0.18s;}
.child-tab:hover{border-color:#f59e0b;color:#92400e;}
.child-tab.active{background:#f59e0b;border-color:#f59e0b;color:#fff;}
.child-tab-avatar{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;}

/* ════ STAT CARDS ════ */
.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:24px;}
.stat-card{background:#fff;border-radius:16px;padding:20px 22px;box-shadow:0 2px 12px rgba(0,0,0,0.06);display:flex;align-items:center;gap:16px;}
.stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;}
.si-green{background:#dcfce7;} .si-red{background:#fee2e2;} .si-blue{background:#dbeafe;} .si-amber{background:#fef3c7;}
.stat-body{}
.stat-label{font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;}
.stat-value{font-family:'Sora',sans-serif;font-size:26px;font-weight:700;color:#0f1923;line-height:1;}
.stat-sub{font-size:11px;color:#94a3b8;margin-top:3px;}

/* ════ ATTENDANCE RATE BAR ════ */
.rate-bar-wrap{background:#fff;border-radius:16px;padding:22px 24px;box-shadow:0 2px 12px rgba(0,0,0,0.06);margin-bottom:24px;}
.rate-bar-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;}
.rate-bar-title{font-family:'Sora',sans-serif;font-size:15px;font-weight:700;color:#0f1923;}
.rate-pct{font-family:'Sora',sans-serif;font-size:22px;font-weight:700;}
.rate-pct.good{color:#16a34a;} .rate-pct.warn{color:#ea580c;} .rate-pct.bad{color:#dc2626;}
.bar-track{height:10px;background:#f1f5f9;border-radius:99px;overflow:hidden;}
.bar-fill{height:100%;border-radius:99px;transition:width 0.6s ease;}
.bar-fill.good{background:linear-gradient(90deg,#22c55e,#16a34a);}
.bar-fill.warn{background:linear-gradient(90deg,#fb923c,#ea580c);}
.bar-fill.bad{background:linear-gradient(90deg,#f87171,#dc2626);}
.bar-legend{display:flex;gap:16px;margin-top:10px;}
.bar-legend-item{display:flex;align-items:center;gap:6px;font-size:12px;color:#64748b;}
.bar-dot{width:8px;height:8px;border-radius:50%;}

/* ════ ATTENDANCE TABLE ════ */
.table-card{background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,0.06);overflow:hidden;margin-bottom:24px;}
.table-card-header{padding:18px 22px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;}
.table-card-title{font-family:'Sora',sans-serif;font-size:15px;font-weight:700;color:#0f1923;display:flex;align-items:center;gap:8px;}
.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
thead th{padding:11px 16px;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;text-align:left;background:#f8fafc;border-bottom:1px solid #f1f5f9;}
tbody tr{border-bottom:1px solid #f8fafc;transition:background 0.12s;}
tbody tr:hover{background:#fafbff;}
tbody td{padding:12px 16px;font-size:13px;color:#374151;}
.status-pill{display:inline-flex;align-items:center;gap:5px;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;}
.sp-present{background:#dcfce7;color:#15803d;} .sp-absent{background:#fee2e2;color:#b91c1c;}
.sp-late{background:#fef3c7;color:#92400e;}
.empty-state{text-align:center;padding:50px 20px;color:#94a3b8;}
.empty-icon{font-size:40px;margin-bottom:10px;}

/* ════ NO CHILD ════ */
.no-child-box{background:#fff;border-radius:20px;padding:60px 30px;text-align:center;box-shadow:0 2px 12px rgba(0,0,0,0.06);}
.no-child-icon{font-size:52px;margin-bottom:14px;}
.no-child-title{font-family:'Sora',sans-serif;font-size:18px;font-weight:700;color:#f59e0b;margin-bottom:8px;}
.no-child-desc{font-size:13px;color:#94a3b8;line-height:1.7;}

/* ════ CHILD PROFILE CARD ════ */
.child-profile-card{background:#fff;border-radius:16px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,0.06);margin-bottom:24px;display:flex;align-items:center;gap:20px;}
.child-big-avatar{width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;color:#fff;flex-shrink:0;}
.child-profile-info h3{font-family:'Sora',sans-serif;font-size:17px;font-weight:700;color:#0f1923;}
.child-profile-info p{font-size:12.5px;color:#64748b;margin-top:3px;}
.child-email-tag{display:inline-flex;align-items:center;gap:5px;background:#f1f5f9;color:#475569;font-size:11px;padding:3px 10px;border-radius:20px;margin-top:6px;}

/* ════ NOTIFICATIONS ════ */
.notif-list{display:flex;flex-direction:column;gap:10px;}
.notif-item{background:#fff;border-radius:12px;padding:14px 18px;box-shadow:0 1px 8px rgba(0,0,0,0.05);border-left:4px solid #e2e8f0;display:flex;gap:14px;align-items:flex-start;}
.notif-item.present{border-left-color:#22c55e;}
.notif-item.absent{border-left-color:#f87171;}
.notif-icon{font-size:20px;flex-shrink:0;margin-top:2px;}
.notif-body{}
.notif-msg{font-size:13px;color:#1e293b;font-weight:500;margin-bottom:4px;}
.notif-time{font-size:11px;color:#94a3b8;}
</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-top">
        <div class="sidebar-brand">
            <div class="brand-icon">&#x1F46A;</div>
            <div class="brand-text"><h2>EduTrack</h2><span>Parent Portal</span></div>
        </div>

        <div class="sidebar-section-label">Main Menu</div>
        <nav>
            <a href="?page=dashboard" class="<?= $page==='dashboard'?'active':'' ?>">
                <span class="nav-icon">&#x1F4CA;</span> Dashboard
            </a>
            <a href="?page=attendance" class="<?= $page==='attendance'?'active':'' ?>">
                <span class="nav-icon">&#x1F4C5;</span> Attendance History
            </a>
            <a href="?page=notifications" class="<?= $page==='notifications'?'active':'' ?>">
                <span class="nav-icon">&#x1F514;</span> Notifications
            </a>
        </nav>

        <div class="sidebar-section-label">Account</div>
        <nav>
            <a href="?page=profile" class="<?= $page==='profile'?'active':'' ?>">
                <span class="nav-icon">&#x1F464;</span> My Profile
            </a>
        </nav>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-avatar" style="background:<?= $avatar_bg ?>;"><?= $initials ?></div>
        <div class="user-info">
            <div class="uname"><?= htmlspecialchars($parent_name) ?></div>
            <div class="urole">Parent / Guardian</div>
        </div>
        <a href="logout.php" class="logout-btn" title="Logout">&#x23FB;</a>
    </div>
</div>

<!-- MAIN -->
<div class="main">

    <div class="top-header">
        <div>
            <div class="welcome-text">
                <?php
                $page_titles = [
                    'dashboard'     => 'Parent Dashboard &#x1F46A;',
                    'attendance'    => 'Attendance History &#x1F4C5;',
                    'notifications' => 'Notifications &#x1F514;',
                    'profile'       => 'My Profile &#x1F464;',
                ];
                echo $page_titles[$page] ?? 'Parent Dashboard &#x1F46A;';
                ?>
            </div>
            <div class="welcome-sub">
                <?php if ($selected_child): ?>
                    Viewing records for <strong><?= htmlspecialchars($selected_child['fullname']) ?></strong>
                <?php else: ?>
                    No linked student found for your account.
                <?php endif; ?>
            </div>
        </div>
        <div class="role-badge">&#x1F46A; Parent</div>
    </div>

    <?php if (empty($children)): ?>
    <!-- NO LINKED CHILD -->
    <div class="no-child-box">
        <div class="no-child-icon">&#x1F50D;</div>
        <div class="no-child-title">No Student Linked</div>
        <div class="no-child-desc">
            Your email (<strong><?= htmlspecialchars($parent_email) ?></strong>) is not set as a parent email 
            for any student yet.<br><br>
            Please ask the school administrator to link your email to your child's account,<br>
            or ask your child to register with your email as their parent email.
        </div>
    </div>

    <?php else: ?>

    <!-- CHILD TABS (multi-child support) -->
    <?php if (count($children) > 1): ?>
    <div class="child-selector">
        <?php foreach ($children as $c):
            $c_colors = ['#3b82f6','#16a34a','#be185d','#9333ea','#ea580c','#0891b2'];
            $c_bg     = $c_colors[$c['id'] % count($c_colors)];
            $c_init   = getInitials($c['fullname']);
            $is_active = ($c['id'] == $selected_child_id);
        ?>
        <a href="?page=<?= $page ?>&child_id=<?= $c['id'] ?>"
           class="child-tab <?= $is_active?'active':'' ?>">
            <div class="child-tab-avatar" style="background:<?= $c_bg ?>"><?= $c_init ?></div>
            <?= htmlspecialchars($c['fullname']) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($page === 'dashboard' || $page === 'attendance'): ?>

        <!-- CHILD INFO STRIP -->
        <?php if ($selected_child):
            $c_colors = ['#3b82f6','#16a34a','#be185d','#9333ea','#ea580c','#0891b2'];
            $c_bg     = $c_colors[$selected_child['id'] % count($c_colors)];
            $c_init   = getInitials($selected_child['fullname']);
        ?>
        <div class="child-profile-card">
            <div class="child-big-avatar" style="background:<?= $c_bg ?>"><?= $c_init ?></div>
            <div class="child-profile-info">
                <h3><?= htmlspecialchars($selected_child['fullname']) ?></h3>
                <p>Student Account</p>
                <span class="child-email-tag">&#x2709; <?= htmlspecialchars($selected_child['email']) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- STAT CARDS -->
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-icon si-blue">&#x1F4DA;</div>
                <div class="stat-body">
                    <div class="stat-label">Total Classes</div>
                    <div class="stat-value"><?= $total ?></div>
                    <div class="stat-sub">All recorded</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon si-green">&#x2705;</div>
                <div class="stat-body">
                    <div class="stat-label">Present</div>
                    <div class="stat-value"><?= $present_count ?></div>
                    <div class="stat-sub">Days attended</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon si-red">&#x274C;</div>
                <div class="stat-body">
                    <div class="stat-label">Absent</div>
                    <div class="stat-value"><?= $absent_count ?></div>
                    <div class="stat-sub">Days missed</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon si-amber">&#x1F4C8;</div>
                <div class="stat-body">
                    <div class="stat-label">Rate</div>
                    <div class="stat-value"><?= $attend_rate ?>%</div>
                    <div class="stat-sub">Attendance rate</div>
                </div>
            </div>
        </div>

        <!-- RATE BAR -->
        <?php
            $bar_class = $attend_rate >= 80 ? 'good' : ($attend_rate >= 60 ? 'warn' : 'bad');
            $rate_label = $attend_rate >= 80 ? '&#x1F7E2; Good Standing' : ($attend_rate >= 60 ? '&#x1F7E1; Needs Improvement' : '&#x1F534; Critical — Please Contact School');
        ?>
        <div class="rate-bar-wrap">
            <div class="rate-bar-header">
                <div class="rate-bar-title">&#x1F4CA; Overall Attendance Rate — <?= $rate_label ?></div>
                <div class="rate-pct <?= $bar_class ?>"><?= $attend_rate ?>%</div>
            </div>
            <div class="bar-track">
                <div class="bar-fill <?= $bar_class ?>" style="width:<?= $attend_rate ?>%"></div>
            </div>
            <div class="bar-legend">
                <span class="bar-legend-item"><span class="bar-dot" style="background:#22c55e"></span><?= $present_count ?> Present</span>
                <span class="bar-legend-item"><span class="bar-dot" style="background:#f87171"></span><?= $absent_count ?> Absent</span>
                <span class="bar-legend-item"><span class="bar-dot" style="background:#e2e8f0"></span><?= $total ?> Total</span>
            </div>
        </div>

        <!-- ATTENDANCE TABLE -->
        <div class="table-card">
            <div class="table-card-header">
                <div class="table-card-title">
                    &#x1F4CB; Attendance Records
                </div>
                <span style="font-size:12px;color:#94a3b8;"><?= count($att_records) ?> record<?= count($att_records)!=1?'s':'' ?></span>
            </div>
            <div class="table-wrap">
                <?php if (empty($att_records)): ?>
                <div class="empty-state">
                    <div class="empty-icon">&#x1F4C5;</div>
                    <div>No attendance records found yet.</div>
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date &amp; Time</th>
                            <th>Status</th>
                            <th>Subject / Class</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($att_records as $i => $r): ?>
                        <tr>
                            <td style="color:#94a3b8;font-size:12px;"><?= $i + 1 ?></td>
                            <td>
                                <div style="font-weight:500;"><?= date('M j, Y', strtotime($r['created_at'])) ?></div>
                                <div style="font-size:11px;color:#94a3b8;"><?= date('g:i A', strtotime($r['created_at'])) ?></div>
                            </td>
                            <td>
                                <?php
                                $s = strtolower($r['status']);
                                $cls = match($s) {
                                    'present' => 'sp-present',
                                    'absent'  => 'sp-absent',
                                    'late'    => 'sp-late',
                                    default   => ''
                                };
                                $icon = match($s) {
                                    'present' => '&#x2705;',
                                    'absent'  => '&#x274C;',
                                    'late'    => '&#x23F0;',
                                    default   => '&#x2B55;'
                                };
                                ?>
                                <span class="status-pill <?= $cls ?>"><?= $icon ?> <?= ucfirst($r['status']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($r['subject'] ?? '—') ?></td>
                            <td style="font-size:12px;color:#94a3b8;font-style:italic;"><?= htmlspecialchars($r['notes'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($page === 'notifications'): ?>

        <!-- NOTIFICATIONS -->
        <?php if (empty($notifs)): ?>
        <div class="no-child-box">
            <div class="no-child-icon">&#x1F514;</div>
            <div class="no-child-title">No Notifications</div>
            <div class="no-child-desc">There are no notifications for your child yet.</div>
        </div>
        <?php else: ?>
        <div class="notif-list">
            <?php foreach ($notifs as $n):
                $cls  = strtolower($n['type'] ?? '');
                $icon = $cls === 'present' ? '&#x2705;' : ($cls === 'absent' ? '&#x1F6A8;' : '&#x1F514;');
            ?>
            <div class="notif-item <?= $cls ?>">
                <div class="notif-icon"><?= $icon ?></div>
                <div class="notif-body">
                    <div class="notif-msg"><?= htmlspecialchars($n['message']) ?></div>
                    <div class="notif-time">&#x1F550; <?= date('M j, Y g:i A', strtotime($n['created_at'])) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    <?php elseif ($page === 'profile'): ?>

        <!-- PARENT PROFILE -->
        <div style="max-width:480px;">
            <div class="child-profile-card" style="flex-direction:column;text-align:center;padding:30px;">
                <div class="child-big-avatar" style="background:<?= $avatar_bg ?>;width:72px;height:72px;font-size:24px;margin:0 auto 14px;"><?= $initials ?></div>
                <div class="child-profile-info">
                    <h3><?= htmlspecialchars($parent_name) ?></h3>
                    <p style="color:#f59e0b;font-weight:600;">Parent / Guardian</p>
                    <span class="child-email-tag" style="margin-top:10px;">&#x2709; <?= htmlspecialchars($parent_email) ?></span>
                </div>
            </div>

            <div class="table-card" style="padding:22px;">
                <div class="table-card-header" style="padding:0 0 16px 0;border-bottom:1px solid #f1f5f9;margin-bottom:16px;">
                    <div class="table-card-title">&#x1F9D2; Linked Children</div>
                </div>
                <?php foreach ($children as $c):
                    $cb = $ava_colors[$c['id'] % count($ava_colors)];
                    $ci = getInitials($c['fullname']);
                ?>
                <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f8fafc;">
                    <div class="child-tab-avatar" style="background:<?= $cb ?>;width:36px;height:36px;border-radius:50%;font-size:12px;"><?= $ci ?></div>
                    <div>
                        <div style="font-size:13px;font-weight:600;color:#0f1923;"><?= htmlspecialchars($c['fullname']) ?></div>
                        <div style="font-size:11px;color:#94a3b8;"><?= htmlspecialchars($c['email']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    <?php endif; ?>

    <?php endif; // end has children ?>

</div>
</body>
</html>