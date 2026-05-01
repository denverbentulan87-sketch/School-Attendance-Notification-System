<?php
include 'includes/db.php';
include 'includes/mailer.php';

$search  = $_GET['search'] ?? '';
$editData = null;

/* ── GENERATE QR for existing student missing one ── */
if (isset($_GET['generate_qr'])) {
    $id = intval($_GET['generate_qr']);
    $s  = $conn->prepare("SELECT fullname, email FROM users WHERE id = ? AND role = 'student'");
    $s->bind_param("i", $id);
    $s->execute();
    $stu = $s->get_result()->fetch_assoc();
    if ($stu) {
        $qr_token = bin2hex(random_bytes(16));
        $scan_url = "http://localhost/School-Attendance-Notification-System/scan.php?token=" . $qr_token;
        $qr_code  = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($scan_url);
        $upd = $conn->prepare("UPDATE users SET qr_code = ?, qr_token = ? WHERE id = ?");
        $upd->bind_param("ssi", $qr_code, $qr_token, $id);
        $upd->execute();
        send_qr_email($stu['email'], $stu['fullname'], $qr_code);
        echo "<script>alert('QR Code generated and sent to " . addslashes($stu['email']) . "!'); window.location.href='admin_dashboard.php?page=students';</script>";
    } else {
        echo "<script>alert('Student not found.'); window.location.href='admin_dashboard.php?page=students';</script>";
    }
    exit();
}

/* ── ADD ── */
if (isset($_POST['add'])) {
    $fullname     = trim($_POST['name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $parent_email = trim($_POST['parent_email'] ?? '');
    $raw_password = $_POST['password'];
    $password     = password_hash($raw_password, PASSWORD_DEFAULT);

    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo "<script>alert('Email already exists.'); window.location.href='admin_dashboard.php?page=students';</script>";
        exit();
    }

    $qr_token = bin2hex(random_bytes(16));
    $scan_url = "http://localhost/School-Attendance-Notification-System/scan.php?token=" . $qr_token;
    $qr_code  = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($scan_url);

    $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role, parent_email, qr_code, qr_token) VALUES (?, ?, ?, 'student', ?, ?, ?)");
    $stmt->bind_param("ssssss", $fullname, $email, $password, $parent_email, $qr_code, $qr_token);

    if ($stmt->execute()) {
        send_qr_email($email, $fullname, $qr_code);
        echo "<script>alert('Student added! QR Code sent to their email.'); window.location.href='admin_dashboard.php?page=students';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close(); exit();
}

/* ── UPDATE ── */
if (isset($_POST['update'])) {
    $id           = intval($_POST['id'] ?? 0);
    $fullname     = $_POST['name']         ?? '';
    $email        = $_POST['email']        ?? '';
    $parent_email = $_POST['parent_email'] ?? '';
    $stmt = $conn->prepare("UPDATE users SET fullname=?, email=?, parent_email=? WHERE id=?");
    $stmt->bind_param("sssi", $fullname, $email, $parent_email, $id);
    $stmt->execute();
    echo "<script>window.location.href='admin_dashboard.php?page=students';</script>";
    exit();
}

/* ── DELETE ── */
if (isset($_GET['delete'])) {
    $id   = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo "<script>window.location.href='admin_dashboard.php?page=students';</script>";
    exit();
}

/* ── EDIT ── */
if (isset($_GET['edit'])) {
    $id   = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
}

/* ── SEARCH / LIST ── */
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE role='student' AND fullname LIKE ?");
    $like = "%$search%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $students = $stmt->get_result();
} else {
    $students = $conn->query("SELECT * FROM users WHERE role='student'");
}

$total_students = $students ? $students->num_rows : 0;

/* ── Avatar colour palette ── */
$avatar_colors = [
    ['bg'=>'#dbeafe','text'=>'#1d4ed8'],
    ['bg'=>'#dcfce7','text'=>'#15803d'],
    ['bg'=>'#fce7f3','text'=>'#be185d'],
    ['bg'=>'#fef3c7','text'=>'#92400e'],
    ['bg'=>'#ede9fe','text'=>'#6d28d9'],
    ['bg'=>'#ffedd5','text'=>'#c2410c'],
];

function getInitials(string $name): string {
    $parts = explode(' ', trim($name));
    $ini   = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) $ini .= strtoupper(substr(end($parts), 0, 1));
    return $ini;
}
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Sora:wght@600;700&display=swap');

.students-wrap { font-family: 'DM Sans', sans-serif; }

/* ── Top bar ── */
.stu-topbar {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 20px;
}
.stu-topbar-left h2 {
    font-family: 'Sora', sans-serif; font-size: 20px;
    font-weight: 700; color: #0f1923; margin: 0 0 2px;
}
.stu-topbar-left .stu-sub { font-size: 13px; color: #64748b; }
.stu-topbar-right { display: flex; align-items: center; gap: 10px; }

/* ── Search ── */
.search-wrapper { position: relative; }
.search-wrapper svg {
    position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
    width: 15px; height: 15px; stroke: #94a3b8; fill: none;
    stroke-width: 2; stroke-linecap: round; pointer-events: none;
}
.search-wrapper input {
    padding: 10px 14px 10px 36px; border: 1.5px solid #e2e8f0;
    border-radius: 10px; font-size: 13px; font-family: 'DM Sans', sans-serif;
    width: 220px; color: #0f1923; background: #fff; outline: none;
    transition: border 0.15s;
}
.search-wrapper input:focus { border-color: #16a34a; }
.search-wrapper input::placeholder { color: #b0bac8; }

/* ── Add button ── */
.btn-add-student {
    display: inline-flex; align-items: center; gap: 7px;
    background: #16a34a; color: #fff; border: none;
    padding: 10px 20px; border-radius: 10px;
    font-size: 13px; font-weight: 600; font-family: 'DM Sans', sans-serif;
    cursor: pointer; box-shadow: 0 3px 10px rgba(22,163,74,0.30);
    transition: background 0.15s, transform 0.1s;
}
.btn-add-student:hover { background: #15803d; transform: translateY(-1px); }

/* ── Table card ── */
.stu-card {
    background: #fff; border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06); overflow: hidden;
}

table.stu-table { width: 100%; border-collapse: collapse; }
table.stu-table thead tr { background: #f8fafc; border-bottom: 1px solid #e8eef4; }
table.stu-table th {
    padding: 13px 18px; font-size: 11px; font-weight: 700;
    letter-spacing: 0.7px; text-transform: uppercase;
    color: #64748b; text-align: left;
}
table.stu-table td {
    padding: 14px 18px; font-size: 13.5px; color: #374151;
    border-bottom: 1px solid #f1f5f9; vertical-align: middle;
}
table.stu-table tbody tr:hover { background: #fafcff; }
table.stu-table tbody tr:last-child td { border-bottom: none; }

/* ── Row number ── */
.row-num { font-size: 13px; font-weight: 600; color: #94a3b8; }

/* ── Avatar + name cell ── */
.student-cell { display: flex; align-items: center; gap: 12px; }
.s-avatar {
    width: 40px; height: 40px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 700; flex-shrink: 0;
}
.student-name  { font-size: 14px; font-weight: 600; color: #0f1923; line-height: 1.2; }
.student-email { font-size: 12px; color: #94a3b8; margin-top: 2px; }

/* ── Parent email cell ── */
.parent-cell { display: flex; align-items: center; gap: 7px; font-size: 13px; color: #374151; }
.no-parent { font-size: 12px; color: #f59e0b; font-style: italic; }

/* ── QR cell ── */
.qr-img {
    width: 56px; height: 56px; border-radius: 8px; display: block;
    border: 1px solid #e2e8f0; cursor: pointer;
    transition: transform 0.15s, box-shadow 0.15s;
}
.qr-img:hover { transform: scale(1.08); box-shadow: 0 4px 14px rgba(0,0,0,0.12); }
.no-qr-badge {
    display: inline-flex; align-items: center; gap: 5px;
    background: #fff7ed; color: #c2410c;
    border: 1px dashed #fdba74; border-radius: 7px;
    padding: 4px 10px; font-size: 11px; font-weight: 600;
}

/* ── Attendance badges ── */
.att-wrap { display: flex; align-items: center; gap: 6px; }
.att-badge {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 20px;
}
.att-present { background: #dcfce7; color: #15803d; }
.att-absent  { background: #fee2e2; color: #b91c1c; }

/* ── Action buttons ── */
.action-group { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; }
.action-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 7px 14px; border-radius: 8px; font-size: 12px; font-weight: 600;
    text-decoration: none; cursor: pointer; border: none;
    transition: opacity 0.15s, transform 0.1s; white-space: nowrap;
    font-family: 'DM Sans', sans-serif;
}
.action-btn:hover { opacity: 0.82; transform: translateY(-1px); }
.btn-edit-row    { background: #dbeafe; color: #1d4ed8; }
.btn-delete-row  { background: #fee2e2; color: #b91c1c; }
.btn-generate-qr { background: #fef3c7; color: #92400e; }
.btn-view-qr     { background: #ede9fe; color: #6d28d9; }

/* ── Empty state ── */
.empty-row td { text-align: center; padding: 40px !important; color: #9ca3af; font-size: 14px; }

/* ════════════ MODALS ════════════ */
.modal {
    display: none; position: fixed; z-index: 999;
    left: 0; top: 0; width: 100%; height: 100%;
    background: rgba(10,20,40,0.52);
    justify-content: center; align-items: center;
    backdrop-filter: blur(3px);
}
.modal-content {
    background: #fff; border-radius: 20px; width: 460px;
    box-shadow: 0 28px 80px rgba(0,0,0,0.18);
    animation: slideUp 0.22s cubic-bezier(.25,.8,.25,1); overflow: hidden;
}
@keyframes slideUp {
    from { transform: translateY(22px) scale(0.98); opacity: 0; }
    to   { transform: translateY(0) scale(1); opacity: 1; }
}
.modal-header {
    padding: 22px 26px 20px; border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; justify-content: space-between;
}
.modal-header-info { display: flex; align-items: center; gap: 14px; }
.modal-icon {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; flex-shrink: 0;
}
.modal-icon.green  { background: #dcfce7; }
.modal-icon.blue   { background: #dbeafe; }
.modal-icon.purple { background: #ede9fe; }
.modal-header-text h3 {
    font-family: 'Sora', sans-serif; font-size: 17px;
    font-weight: 700; color: #0f1923; margin: 0 0 3px;
}
.modal-header-text p { font-size: 12px; color: #94a3b8; margin: 0; }
.modal-close-btn {
    width: 32px; height: 32px; border-radius: 8px;
    background: #f1f5f9; border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: #64748b; font-size: 17px; flex-shrink: 0; text-decoration: none;
    transition: background 0.15s, color 0.15s;
}
.modal-close-btn:hover { background: #fee2e2; color: #dc2626; }
.modal-body { padding: 24px 26px 26px; }
.field-group { margin-bottom: 16px; }
.field-group label {
    display: block; font-size: 11.5px; font-weight: 700; color: #374151;
    margin-bottom: 7px; text-transform: uppercase; letter-spacing: 0.6px;
}
.field-wrap { position: relative; }
.field-wrap .f-icon {
    position: absolute; left: 12px; top: 50%;
    transform: translateY(-50%); font-size: 14px; pointer-events: none;
}
.field-wrap input {
    width: 100%; padding: 11px 14px 11px 38px;
    border-radius: 10px; border: 1.5px solid #e2e8f0;
    font-size: 14px; font-family: 'DM Sans', sans-serif;
    color: #0f1923; background: #f8fafc; outline: none;
    transition: border 0.15s, box-shadow 0.15s; box-sizing: border-box;
}
.field-wrap input:focus { border-color: #16a34a; background: #fff; box-shadow: 0 0 0 3px rgba(22,163,74,0.1); }
.field-wrap input::placeholder { color: #b0bac8; }
.modal-divider { height: 1px; background: #f1f5f9; margin: 6px 0 20px; }
.modal-actions { display: flex; gap: 10px; margin-top: 4px; }
.btn-confirm {
    flex: 1; padding: 12px 16px; border: none; border-radius: 10px;
    font-size: 14px; font-weight: 700; font-family: 'DM Sans', sans-serif;
    cursor: pointer; background: #16a34a; color: #fff;
    box-shadow: 0 3px 12px rgba(22,163,74,0.35);
    transition: background 0.15s, transform 0.1s;
    display: flex; align-items: center; justify-content: center; gap: 7px;
    text-decoration: none;
}
.btn-confirm:hover { background: #15803d; transform: translateY(-1px); }
.btn-confirm.blue { background: #2563eb; box-shadow: 0 3px 12px rgba(37,99,235,0.3); }
.btn-confirm.blue:hover { background: #1d4ed8; }
.btn-cancel {
    flex: 1; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 10px;
    font-size: 14px; font-weight: 600; font-family: 'DM Sans', sans-serif;
    cursor: pointer; background: #fff; color: #374151;
    text-align: center; text-decoration: none;
    display: flex; align-items: center; justify-content: center; gap: 7px;
    box-sizing: border-box; transition: background 0.15s, color 0.15s, transform 0.1s;
}
.btn-cancel:hover { background: #fef2f2; border-color: #fca5a5; color: #dc2626; transform: translateY(-1px); }
.field-hint { font-size: 11px; color: #94a3b8; margin-top: 5px; padding-left: 2px; }

/* ── QR Modal specific ── */
.qr-modal-img-wrap {
    display: flex; flex-direction: column; align-items: center; gap: 14px;
    padding: 4px 0 8px;
}
.qr-modal-img {
    width: 210px; height: 210px; border-radius: 12px;
    border: 1px solid #e2e8f0; display: block;
}
.qr-token-box {
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;
    padding: 8px 14px; font-family: monospace; font-size: 11px;
    color: #64748b; word-break: break-all; text-align: center; width: 100%;
}
.qr-active-badge {
    display: inline-flex; align-items: center; gap: 5px;
    background: #dcfce7; color: #15803d;
    font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 20px;
}
</style>

<!-- ══════════════ STUDENTS PAGE ══════════════ -->
<div class="students-wrap">

    <!-- Top bar -->
    <div class="stu-topbar">
        <div class="stu-topbar-left">
            <h2>Students Management</h2>
            <div class="stu-sub">Total: <?= $total_students ?> students enrolled</div>
        </div>
        <div class="stu-topbar-right">
            <form method="GET" style="margin:0;">
                <input type="hidden" name="page" value="students">
                <div class="search-wrapper">
                    <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="search" placeholder="Search students..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>
            </form>
            <button class="btn-add-student" onclick="openAddModal()">+ Add Student</button>
        </div>
    </div>

    <!-- Table card -->
    <div class="stu-card">
        <table class="stu-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Parent Email</th>
                    <th>QR Code</th>
                    <th>Attendance</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($total_students === 0): ?>
                <tr class="empty-row"><td colspan="6">No students found.</td></tr>
            <?php else: ?>
            <?php $rowNum = 0; while ($row = $students->fetch_assoc()): $rowNum++; ?>
            <?php
                $id       = $row['id'];
                $color    = $avatar_colors[($rowNum - 1) % count($avatar_colors)];
                $initials = getInitials($row['fullname']);

                $stmt = $conn->prepare("SELECT COALESCE(SUM(status='present'),0) as present, COALESCE(SUM(status='absent'),0) as absent FROM attendance WHERE student_id=?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $att = $stmt->get_result()->fetch_assoc();

                $has_qr = !empty($row['qr_code']) && !empty($row['qr_token']);

                // Safe JS values for onclick
                $js_name  = addslashes(htmlspecialchars($row['fullname']));
                $js_email = addslashes(htmlspecialchars($row['email']));
                $js_qr    = addslashes($row['qr_code'] ?? '');
                $js_token = addslashes($row['qr_token'] ?? '');
            ?>
            <tr>
                <!-- Row number -->
                <td><span class="row-num"><?= $rowNum ?></span></td>

                <!-- Student: avatar + name + email stacked -->
                <td>
                    <div class="student-cell">
                        <div class="s-avatar" style="background:<?= $color['bg'] ?>;color:<?= $color['text'] ?>;">
                            <?= $initials ?>
                        </div>
                        <div>
                            <div class="student-name"><?= htmlspecialchars($row['fullname']) ?></div>
                            <div class="student-email"><?= htmlspecialchars($row['email']) ?></div>
                        </div>
                    </div>
                </td>

                <!-- Parent email -->
                <td>
                    <?php if (!empty($row['parent_email'])): ?>
                        <div class="parent-cell">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                 stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <?= htmlspecialchars($row['parent_email']) ?>
                        </div>
                    <?php else: ?>
                        <span class="no-parent">&#x26A0; Not set</span>
                    <?php endif; ?>
                </td>

                <!-- QR Code — click thumbnail to open viewer -->
                <td>
                    <?php if ($has_qr): ?>
                        <img src="<?= htmlspecialchars($row['qr_code']) ?>"
                             class="qr-img" alt="QR"
                             title="Click to enlarge"
                             onclick="openQrModal('<?= $js_name ?>', '<?= $js_email ?>', '<?= $js_qr ?>', '<?= $js_token ?>')">
                    <?php else: ?>
                        <span class="no-qr-badge">&#x26A0; No QR</span>
                    <?php endif; ?>
                </td>

                <!-- Attendance -->
                <td>
                    <div class="att-wrap">
                        <span class="att-badge att-present">&#x2714; <?= $att['present'] ?></span>
                        <span class="att-badge att-absent">&#x2716; <?= $att['absent'] ?></span>
                    </div>
                </td>

                <!-- Actions -->
                <td>
                    <div class="action-group">
                        <a class="action-btn btn-edit-row"
                           href="admin_dashboard.php?page=students&edit=<?= $id ?>">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2.5"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                            Edit
                        </a>

                        <?php if ($has_qr): ?>
                        <button class="action-btn btn-view-qr"
                                onclick="openQrModal('<?= $js_name ?>', '<?= $js_email ?>', '<?= $js_qr ?>', '<?= $js_token ?>')">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2.5"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="5" height="5" rx="1"/>
                                <rect x="16" y="3" width="5" height="5" rx="1"/>
                                <rect x="3" y="16" width="5" height="5" rx="1"/>
                                <path d="M16 16h2v2h-2zM18 20v-2M20 18h-2"/>
                            </svg>
                            View QR
                        </button>
                        <?php else: ?>
                        <a class="action-btn btn-generate-qr"
                           href="admin_dashboard.php?page=students&generate_qr=<?= $id ?>"
                           onclick="return confirm('Generate and email a QR code to this student?')">
                           &#x1F4F2; Gen QR
                        </a>
                        <?php endif; ?>

                        <a class="action-btn btn-delete-row"
                           href="admin_dashboard.php?page=students&delete=<?= $id ?>"
                           onclick="return confirm('Delete this student?')">&#x1F5D1; Delete</a>
                    </div>
                </td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>

</div><!-- /.students-wrap -->


<!-- ══════════ ADD MODAL ══════════ -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-header-info">
                <div class="modal-icon green">&#x1F464;</div>
                <div class="modal-header-text">
                    <h3>Add New Student</h3>
                    <p>Fill in the details to enroll a student</p>
                </div>
            </div>
            <button class="modal-close-btn" onclick="closeAddModal()">&#x2715;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <div class="field-group">
                    <label>Full Name</label>
                    <div class="field-wrap">
                        <span class="f-icon">&#x1F464;</span>
                        <input type="text" name="name" placeholder="e.g. Juan dela Cruz" required>
                    </div>
                </div>
                <div class="field-group">
                    <label>Email Address</label>
                    <div class="field-wrap">
                        <span class="f-icon">&#x2709;&#xFE0F;</span>
                        <input type="email" name="email" placeholder="student@email.com" required>
                    </div>
                </div>
                <div class="field-group">
                    <label>Parent Gmail</label>
                    <div class="field-wrap">
                        <span class="f-icon">&#x1F4E7;</span>
                        <input type="email" name="parent_email" placeholder="parent@gmail.com" required>
                    </div>
                    <div class="field-hint">Parent will be notified if student misses attendance.</div>
                </div>
                <div class="field-group">
                    <label>Password</label>
                    <div class="field-wrap">
                        <span class="f-icon">&#x1F512;</span>
                        <input type="password" name="password" placeholder="Create a password" required>
                    </div>
                    <div class="field-hint">Student will use this password to log in.</div>
                </div>
                <div class="modal-divider"></div>
                <div class="modal-actions">
                    <button class="btn-confirm" name="add">&#x2795; Add Student</button>
                    <button type="button" class="btn-cancel" onclick="closeAddModal()">&#x2715; Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ══════════ EDIT MODAL ══════════ -->
<div id="editModal" class="modal" style="<?= $editData ? 'display:flex;' : '' ?>">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-header-info">
                <div class="modal-icon blue">&#x270F;&#xFE0F;</div>
                <div class="modal-header-text">
                    <h3>Edit Student</h3>
                    <p>Update the student's information</p>
                </div>
            </div>
            <a class="modal-close-btn" href="admin_dashboard.php?page=students">&#x2715;</a>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">
                <div class="field-group">
                    <label>Full Name</label>
                    <div class="field-wrap">
                        <span class="f-icon">&#x1F464;</span>
                        <input type="text" name="name"
                               value="<?= htmlspecialchars($editData['fullname'] ?? '') ?>"
                               placeholder="Full Name" required>
                    </div>
                </div>
                <div class="field-group">
                    <label>Email Address</label>
                    <div class="field-wrap">
                        <span class="f-icon">&#x2709;&#xFE0F;</span>
                        <input type="email" name="email"
                               value="<?= htmlspecialchars($editData['email'] ?? '') ?>"
                               placeholder="student@email.com" required>
                    </div>
                </div>
                <div class="field-group">
                    <label>Parent Gmail</label>
                    <div class="field-wrap">
                        <span class="f-icon">&#x1F4E7;</span>
                        <input type="email" name="parent_email"
                               value="<?= htmlspecialchars($editData['parent_email'] ?? '') ?>"
                               placeholder="parent@gmail.com">
                    </div>
                    <div class="field-hint">Parent will be notified if student misses attendance.</div>
                </div>
                <div class="modal-divider"></div>
                <div class="modal-actions">
                    <button class="btn-confirm blue" name="update">&#x2714; Update Student</button>
                    <a class="btn-cancel" href="admin_dashboard.php?page=students">&#x2715; Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ══════════ QR VIEW MODAL ══════════ -->
<div id="qrViewModal" class="modal">
    <div class="modal-content" style="width: 360px;">
        <div class="modal-header">
            <div class="modal-header-info">
                <div class="modal-icon purple">&#x1F4F1;</div>
                <div class="modal-header-text">
                    <h3>Student QR Code</h3>
                    <p id="qrModalStudentName">—</p>
                </div>
            </div>
            <button class="modal-close-btn" onclick="closeQrModal()">&#x2715;</button>
        </div>
        <div class="modal-body">
            <div class="qr-modal-img-wrap">
                <span class="qr-active-badge">&#x2714; Active &amp; Functional</span>
                <img id="qrModalImg" src="" alt="QR Code" class="qr-modal-img">
                <div class="qr-token-box" id="qrModalToken"></div>
            </div>
            <div class="modal-divider"></div>
            <div class="modal-actions">
                <a id="qrDownloadBtn" href="#" target="_blank"
                   class="btn-confirm" style="text-decoration:none;">
                    &#x2B07; Download QR
                </a>
                <button type="button" class="btn-cancel" onclick="closeQrModal()">&#x2715; Close</button>
            </div>
        </div>
    </div>
</div>


<script>
/* ── Add modal ── */
function openAddModal()  { document.getElementById('addModal').style.display  = 'flex'; }
function closeAddModal() { document.getElementById('addModal').style.display  = 'none'; }

/* ── QR view modal ── */
function openQrModal(name, email, qrUrl, token) {
    document.getElementById('qrModalStudentName').textContent = name + ' · ' + email;
    document.getElementById('qrModalImg').src                 = qrUrl;
    document.getElementById('qrModalToken').textContent       = 'Token: ' + token;
    document.getElementById('qrDownloadBtn').href             = qrUrl;
    document.getElementById('qrViewModal').style.display      = 'flex';
}
function closeQrModal() {
    document.getElementById('qrViewModal').style.display = 'none';
}

/* ── Backdrop click closes any modal ── */
window.onclick = function(e) {
    const modals = ['addModal', 'editModal', 'qrViewModal'];
    modals.forEach(id => {
        const el = document.getElementById(id);
        if (e.target === el) el.style.display = 'none';
    });
};
</script>