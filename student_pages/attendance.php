<?php
include 'includes/db.php';
$user_id = $_SESSION['user_id'];

$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$where = "WHERE student_id='$user_id'";
if($filter == 'present')      $where .= " AND status='present'";
elseif($filter == 'absent')   $where .= " AND status='absent'";
if(!empty($search))           $where .= " AND DATE(date_added)='$search'";

$records = $conn->query("SELECT date_added, status FROM attendance $where ORDER BY date_added DESC");

$present = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE student_id='$user_id' AND status='present'")->fetch_assoc()['total'];
$absent  = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE student_id='$user_id' AND status='absent'")->fetch_assoc()['total'];
$total   = $present + $absent;
$rate    = $total > 0 ? ($present / $total) * 100 : 0;

if($rate >= 85)     { $risk = "LOW RISK";    $riskClass = "risk-low";    $msg = "Excellent attendance. Keep it up!"; }
elseif($rate >= 60) { $risk = "MEDIUM RISK"; $riskClass = "risk-medium"; $msg = "Attendance is okay but needs improvement."; }
else                { $risk = "HIGH RISK";   $riskClass = "risk-high";   $msg = "Warning: You are at risk of absenteeism!"; }
?>

<style>
/* ── FEATURE GRID ── */
.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.feature-card {
    background: #fff;
    padding: 20px;
    border-radius: 14px;
    text-align: center;
    cursor: pointer;
    text-decoration: none;
    color: #1e293b;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    transition: transform 0.2s, box-shadow 0.2s;
    border-top: 4px solid transparent;
}

.feature-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.10);
}

.feature-card .fc-icon { font-size: 26px; margin-bottom: 8px; }
.feature-card h4 { font-size: 14px; font-weight: 600; margin-bottom: 4px; }
.feature-card p  { font-size: 12px; color: #6b7280; }

.feature-card.pdf    { border-top-color: #ef4444; }
.feature-card.notify { border-top-color: #10b981; }
.feature-card.qr     { border-top-color: #6366f1; }
.feature-card.ai     { border-top-color: #f59e0b; }

/* ── AI BOX ── */
.ai-box {
    background: #fff;
    border-radius: 14px;
    padding: 20px 22px;
    margin-bottom: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    border-left: 4px solid #f59e0b;
    display: none;
}

.ai-box h3 { font-size: 15px; font-weight: 600; margin-bottom: 10px; }

.risk-low    { color: #16a34a; font-weight: 700; font-size: 15px; }
.risk-medium { color: #f59e0b; font-weight: 700; font-size: 15px; }
.risk-high   { color: #dc2626; font-weight: 700; font-size: 15px; }

/* ── FILTER BAR ── */
.filter-bar {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 18px;
    flex-wrap: wrap;
}

.filter-bar select,
.filter-bar input[type="date"] {
    padding: 9px 13px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 13px;
    font-family: 'Poppins', sans-serif;
    color: #374151;
    background: #fff;
    outline: none;
}

.filter-bar select:focus,
.filter-bar input:focus { border-color: #3b82f6; }

.filter-bar button {
    background: #22c55e;
    color: #fff;
    border: none;
    padding: 9px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-family: 'Poppins', sans-serif;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.filter-bar button:hover { background: #16a34a; }

/* ── TABLE ── */
.table-card {
    background: #fff;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.table-card h3 {
    font-size: 15px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 16px;
}

.data-table { width: 100%; border-collapse: collapse; }

.data-table th {
    background: #f8fafc;
    padding: 11px 14px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    text-align: left;
}

.data-table td {
    padding: 12px 14px;
    font-size: 14px;
    color: #374151;
    border-bottom: 1px solid #f1f5f9;
}

.data-table tr:last-child td { border-bottom: none; }

.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 600;
}

.badge.present { background: #dcfce7; color: #16a34a; }
.badge.absent  { background: #fee2e2; color: #dc2626; }

/* ── MODAL ── */
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 999;
    align-items: center;
    justify-content: center;
}

.modal.open { display: flex; }

.modal-content {
    background: #fff;
    padding: 28px;
    width: 340px;
    border-radius: 16px;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}

.modal-content h3 { margin-bottom: 16px; font-size: 16px; font-weight: 600; }

.modal-content button {
    margin-top: 16px;
    background: #ef4444;
    color: #fff;
    border: none;
    padding: 9px 22px;
    border-radius: 8px;
    font-size: 13px;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
}

.section-title {
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}
</style>

<div class="section-title">📅 My Attendance</div>

<!-- FEATURE CARDS -->
<div class="feature-grid">
    <a href="bash/export_pdf.php" target="_blank" class="feature-card pdf">
        <div class="fc-icon">📄</div>
        <h4>Export Report</h4>
        <p>Download attendance PDF</p>
    </a>

    <a href="send_notification.php?student_id=<?= $user_id ?>" class="feature-card notify">
        <div class="fc-icon">📩</div>
        <h4>Notify Parent</h4>
        <p>Send attendance alert</p>
    </a>

    <div class="feature-card qr" onclick="openQR()">
        <div class="fc-icon">📍</div>
        <h4>Scan QR</h4>
        <p>Mark your attendance</p>
    </div>

    <div class="feature-card ai" onclick="toggleAI()">
        <div class="fc-icon">🧠</div>
        <h4>AI Insight</h4>
        <p>Check performance</p>
    </div>
</div>

<!-- AI BOX -->
<div id="aiBox" class="ai-box">
    <h3>🧠 Attendance Insight</h3>
    <p class="<?= $riskClass ?>">Risk Level: <?= $risk ?></p>
    <p style="margin-top:6px;font-size:14px;color:#374151;"><?= $msg ?></p>
    <p style="margin-top:8px;font-size:13px;color:#6b7280;"><strong>Attendance Rate:</strong> <?= round($rate) ?>%</p>
</div>

<!-- FILTER -->
<form method="GET" class="filter-bar">
    <input type="hidden" name="page" value="attendance">
    <select name="filter">
        <option value="all"     <?= $filter=='all'    ?'selected':'' ?>>All</option>
        <option value="present" <?= $filter=='present'?'selected':'' ?>>Present</option>
        <option value="absent"  <?= $filter=='absent' ?'selected':'' ?>>Absent</option>
    </select>
    <input type="date" name="search" value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Filter</button>
</form>

<!-- TABLE -->
<div class="table-card">
    <h3>📋 Attendance History</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php if($records->num_rows > 0): ?>
            <?php while($r = $records->fetch_assoc()): ?>
            <tr>
                <td><?= date('M d, Y — h:i A', strtotime($r['date_added'])) ?></td>
                <td><span class="badge <?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="2" style="text-align:center;color:#9ca3af;padding:20px;">No records found</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- QR MODAL -->
<div id="qrModal" class="modal">
    <div class="modal-content">
        <h3>📍 Scan QR Code</h3>
        <div id="reader"></div>
        <button onclick="closeQR()">Close</button>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
let scanner;

function toggleAI(){
    const box = document.getElementById("aiBox");
    box.style.display = box.style.display === "none" || box.style.display === "" ? "block" : "none";
}

function openQR(){
    document.getElementById("qrModal").classList.add("open");
    if(!scanner){
        scanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 200 });
        scanner.render(success => {
            fetch("mark_attendance.php?id=" + success)
                .then(() => alert("✅ Attendance recorded"))
                .then(() => location.reload());
        });
    }
}

function closeQR(){
    document.getElementById("qrModal").classList.remove("open");
    if(scanner){ scanner.clear(); scanner = null; }
}
</script>