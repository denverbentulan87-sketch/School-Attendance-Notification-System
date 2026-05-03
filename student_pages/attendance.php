<?php
include 'includes/db.php';
$student_id = $_SESSION['user_id'];

$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$where = "WHERE student_id='$student_id'";
if($filter == 'present')      $where .= " AND status='present'";
elseif($filter == 'absent')   $where .= " AND status='absent'";
elseif($filter == 'late')     $where .= " AND status='late'";
if(!empty($search))           $where .= " AND DATE(date_added)='$search'";

$records = $conn->query("SELECT date_added, status FROM attendance $where ORDER BY date_added DESC");

$present = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE student_id='$student_id' AND status='present'")->fetch_assoc()['total'];
$absent  = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE student_id='$student_id' AND status='absent'")->fetch_assoc()['total'];
$late    = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE student_id='$student_id' AND status='late'")->fetch_assoc()['total'];
$total   = $present + $absent + $late;
$rate    = $total > 0 ? (($present + $late) / $total) * 100 : 0;
$strict_rate = $total > 0 ? ($present / $total) * 100 : 0;

if($strict_rate >= 85)     { $risk = "LOW RISK";    $riskClass = "risk-low";    $msg = "Excellent attendance! You rarely miss class."; }
elseif($strict_rate >= 60) { $risk = "MEDIUM RISK"; $riskClass = "risk-medium"; $msg = "Attendance is acceptable but try to reduce absences."; }
else                       { $risk = "HIGH RISK";   $riskClass = "risk-high";   $msg = "Warning: Your absence rate is critically high!"; }
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

.feature-card.pdf      { border-top-color: #ef4444; }
.feature-card.summary  { border-top-color: #3b82f6; }
.feature-card.calendar { border-top-color: #8b5cf6; }
.feature-card.ai       { border-top-color: #f59e0b; }

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

/* ── SUMMARY BOX ── */
.summary-box {
    background: #fff;
    border-radius: 14px;
    padding: 20px 22px;
    margin-bottom: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    border-left: 4px solid #3b82f6;
    display: none;
}

.summary-box h3 { font-size: 15px; font-weight: 600; margin-bottom: 14px; }

.summary-filter-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.sum-tab {
    padding: 6px 16px;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 600;
    border: 2px solid #e5e7eb;
    cursor: pointer;
    transition: all 0.18s;
    background: #f1f5f9;
    color: #64748b;
}

.sum-tab:hover { opacity: 0.8; }
.sum-tab.active-all     { background: #3b82f6; color: #fff; border-color: #3b82f6; }
.sum-tab.active-present { background: #dcfce7; color: #16a34a; border-color: #16a34a; }
.sum-tab.active-late    { background: #fef3c7; color: #d97706; border-color: #d97706; }
.sum-tab.active-absent  { background: #fee2e2; color: #dc2626; border-color: #dc2626; }

.summary-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 12px;
}

.stat-pill {
    background: #f8fafc;
    border-radius: 10px;
    padding: 14px;
    text-align: center;
    transition: opacity 0.2s;
}

.stat-pill.hidden { display: none; }

.stat-pill .stat-num  { font-size: 26px; font-weight: 700; }
.stat-pill .stat-label { font-size: 12px; color: #6b7280; margin-top: 2px; }
.stat-pill.s-present .stat-num { color: #16a34a; }
.stat-pill.s-absent  .stat-num { color: #dc2626; }
.stat-pill.s-late    .stat-num { color: #f59e0b; }
.stat-pill.s-rate    .stat-num { color: #3b82f6; }

/* ── CALENDAR BOX ── */
.calendar-box {
    background: #fff;
    border-radius: 14px;
    padding: 20px 22px;
    margin-bottom: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    border-left: 4px solid #8b5cf6;
    display: none;
}

.calendar-box h3 { font-size: 15px; font-weight: 600; margin-bottom: 14px; }

.cal-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.cal-nav span { font-size: 14px; font-weight: 600; color: #1e293b; }

.cal-nav button {
    background: #f1f5f9;
    border: none;
    border-radius: 8px;
    padding: 6px 12px;
    cursor: pointer;
    font-size: 14px;
    color: #374151;
}

.cal-nav button:hover { background: #e2e8f0; }

.cal-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
    text-align: center;
}

.cal-grid .day-label {
    font-size: 11px;
    font-weight: 600;
    color: #94a3b8;
    padding: 4px 0;
    text-transform: uppercase;
}

.cal-day {
    border-radius: 8px;
    padding: 8px 4px;
    font-size: 12px;
    color: #374151;
    min-height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cal-day.present  { background: #dcfce7; color: #16a34a; font-weight: 600; }
.cal-day.absent   { background: #fee2e2; color: #dc2626; font-weight: 600; }
.cal-day.late     { background: #fef3c7; color: #d97706; font-weight: 600; }
.cal-day.today    { outline: 2px solid #8b5cf6; }
.cal-day.empty    { background: transparent; }

.cal-legend {
    display: flex;
    gap: 14px;
    margin-top: 12px;
    flex-wrap: wrap;
}

.cal-legend span {
    font-size: 12px;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 5px;
}

.dot {
    width: 10px;
    height: 10px;
    border-radius: 3px;
    display: inline-block;
}

.dot.present  { background: #dcfce7; border: 1px solid #16a34a; }
.dot.absent   { background: #fee2e2; border: 1px solid #dc2626; }
.dot.late     { background: #fef3c7; border: 1px solid #d97706; }

/* ── RISK LABELS ── */
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
.badge.late    { background: #fef3c7; color: #d97706; }

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

    <div class="feature-card summary" onclick="toggleSummary()">
        <div class="fc-icon">📊</div>
        <h4>Attendance Summary</h4>
        <p>View stats & overview</p>
    </div>

    <div class="feature-card calendar" onclick="toggleCalendar()">
        <div class="fc-icon">🗓️</div>
        <h4>Monthly View</h4>
        <p>Calendar attendance view</p>
    </div>

    <div class="feature-card ai" onclick="toggleAI()">
        <div class="fc-icon">🧠</div>
        <h4>AI Insight</h4>
        <p>Check performance</p>
    </div>
</div>

<!-- SUMMARY BOX -->
<div id="summaryBox" class="summary-box">
    <h3>📊 Attendance Summary</h3>
    <div class="summary-filter-tabs">
        <button class="sum-tab active-all" onclick="filterSummary('all', this)">All</button>
        <button class="sum-tab" onclick="filterSummary('present', this)">✅ Present</button>
        <button class="sum-tab" onclick="filterSummary('late', this)">⏰ Late</button>
        <button class="sum-tab" onclick="filterSummary('absent', this)">❌ Absent</button>
    </div>
    <div class="summary-stats">
        <div class="stat-pill s-present" id="pill-present">
            <div class="stat-num"><?= $present ?></div>
            <div class="stat-label">Present</div>
        </div>
        <div class="stat-pill s-absent" id="pill-absent">
            <div class="stat-num"><?= $absent ?></div>
            <div class="stat-label">Absent</div>
        </div>
        <div class="stat-pill s-late" id="pill-late">
            <div class="stat-num"><?= $late ?></div>
            <div class="stat-label">Late</div>
        </div>
        <div class="stat-pill s-rate" id="pill-rate">
            <div class="stat-num"><?= round($rate) ?>%</div>
            <div class="stat-label">Attendance Rate</div>
        </div>
    </div>
</div>

<!-- CALENDAR BOX -->
<div id="calendarBox" class="calendar-box">
    <h3>🗓️ Monthly Attendance View</h3>
    <div class="cal-nav">
        <button onclick="changeMonth(-1)">&#8249;</button>
        <span id="calTitle"></span>
        <button onclick="changeMonth(1)">&#8250;</button>
    </div>
    <div id="calGrid" class="cal-grid"></div>
    <div class="cal-legend">
        <span><span class="dot present"></span> Present</span>
        <span><span class="dot absent"></span> Absent</span>
        <span><span class="dot late"></span> Late</span>
    </div>
</div>

<!-- AI BOX -->
<div id="aiBox" class="ai-box">
    <h3>🧠 Attendance Insight</h3>
    <p class="<?= $riskClass ?>">Risk Level: <?= $risk ?></p>
    <p style="margin-top:6px;font-size:14px;color:#374151;"><?= $msg ?></p>
    <div style="margin-top:12px;display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px;">
        <div style="background:#f8fafc;border-radius:10px;padding:12px;text-align:center;">
            <div style="font-size:20px;font-weight:700;color:#16a34a;"><?= $present ?></div>
            <div style="font-size:11px;color:#6b7280;">Present Days</div>
        </div>
        <div style="background:#f8fafc;border-radius:10px;padding:12px;text-align:center;">
            <div style="font-size:20px;font-weight:700;color:#f59e0b;"><?= $late ?></div>
            <div style="font-size:11px;color:#6b7280;">Late Days</div>
        </div>
        <div style="background:#f8fafc;border-radius:10px;padding:12px;text-align:center;">
            <div style="font-size:20px;font-weight:700;color:#dc2626;"><?= $absent ?></div>
            <div style="font-size:11px;color:#6b7280;">Absent Days</div>
        </div>
        <div style="background:#f8fafc;border-radius:10px;padding:12px;text-align:center;">
            <div style="font-size:20px;font-weight:700;color:#3b82f6;"><?= round($strict_rate) ?>%</div>
            <div style="font-size:11px;color:#6b7280;">Present Rate</div>
        </div>
    </div>
    <p style="margin-top:10px;font-size:12px;color:#94a3b8;">
        📌 Risk is based on present-only rate (<?= round($strict_rate) ?>%). Overall attendance including late days: <?= round($rate) ?>%.
    </p>
</div>

<!-- FILTER -->
<form method="GET" class="filter-bar">
    <input type="hidden" name="page" value="attendance">
    <select name="filter">
        <option value="all"     <?= $filter=='all'    ?'selected':'' ?>>All Status</option>
        <option value="present" <?= $filter=='present'?'selected':'' ?>>✅ Present</option>
        <option value="late"    <?= $filter=='late'   ?'selected':'' ?>>⏰ Late</option>
        <option value="absent"  <?= $filter=='absent' ?'selected':'' ?>>❌ Absent</option>
    </select>
    <input type="date" name="search" value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Filter</button>
    <?php if($filter !== 'all' || !empty($search)): ?>
        <a href="?page=attendance" style="font-size:13px;color:#6b7280;text-decoration:none;padding:9px 4px;">✕ Reset</a>
    <?php endif; ?>
</form>

<!-- TABLE -->
<div class="table-card">
    <h3 style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
        <span>📋 Attendance History
            <?php if($filter !== 'all'): ?>
                <span style="font-size:12px;font-weight:500;padding:3px 10px;border-radius:99px;margin-left:6px;
                    <?= $filter==='present' ? 'background:#dcfce7;color:#16a34a;' : ($filter==='absent' ? 'background:#fee2e2;color:#dc2626;' : 'background:#fef3c7;color:#d97706;') ?>">
                    <?= ucfirst($filter) ?>
                </span>
            <?php endif; ?>
        </span>
        <span style="font-size:12px;font-weight:500;color:#94a3b8;"><?= $records->num_rows ?> record(s)</span>
    </h3>
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

<script>
// ── AI toggle ──
function toggleAI(){
    const box = document.getElementById("aiBox");
    box.style.display = (box.style.display === "block") ? "none" : "block";
}

// ── Summary filter ──
function filterSummary(status, btn){
    // Update active tab styling
    document.querySelectorAll('.sum-tab').forEach(t => t.className = 'sum-tab');
    btn.classList.add('active-' + status);

    const pills = {
        present: document.getElementById('pill-present'),
        absent:  document.getElementById('pill-absent'),
        late:    document.getElementById('pill-late'),
        rate:    document.getElementById('pill-rate'),
    };

    if(status === 'all'){
        Object.values(pills).forEach(p => p.classList.remove('hidden'));
    } else {
        Object.entries(pills).forEach(([key, el]) => {
            // rate pill always shows alongside whichever status is selected
            if(key === 'rate' || key === status) el.classList.remove('hidden');
            else el.classList.add('hidden');
        });
    }
}

// ── Summary toggle ──
function toggleSummary(){
    const box = document.getElementById("summaryBox");
    box.style.display = (box.style.display === "block") ? "none" : "block";
}

// ── Calendar logic ──
// Attendance data passed from PHP
const attendanceData = {
    <?php
    $allRecords = $conn->query("SELECT date_added, status FROM attendance WHERE student_id='$student_id'");
    while($r = $allRecords->fetch_assoc()){
        $date = date('Y-m-d', strtotime($r['date_added']));
        echo "'$date': '{$r['status']}',\n";
    }
    ?>
};

let currentMonth = new Date().getMonth();
let currentYear  = new Date().getFullYear();

function renderCalendar(){
    const monthNames = ["January","February","March","April","May","June","July","August","September","October","November","December"];
    document.getElementById("calTitle").textContent = monthNames[currentMonth] + " " + currentYear;

    const grid = document.getElementById("calGrid");
    grid.innerHTML = "";

    const days = ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
    days.forEach(d => {
        const label = document.createElement("div");
        label.className = "day-label";
        label.textContent = d;
        grid.appendChild(label);
    });

    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    const today = new Date();

    for(let i = 0; i < firstDay; i++){
        const empty = document.createElement("div");
        empty.className = "cal-day empty";
        grid.appendChild(empty);
    }

    for(let d = 1; d <= daysInMonth; d++){
        const cell = document.createElement("div");
        const dateStr = currentYear + "-" + String(currentMonth+1).padStart(2,"0") + "-" + String(d).padStart(2,"0");
        const status = attendanceData[dateStr] || "";
        let cls = "cal-day";
        if(status) cls += " " + status;
        if(d === today.getDate() && currentMonth === today.getMonth() && currentYear === today.getFullYear()) cls += " today";
        cell.className = cls;
        cell.textContent = d;
        grid.appendChild(cell);
    }
}

function changeMonth(dir){
    currentMonth += dir;
    if(currentMonth > 11){ currentMonth = 0; currentYear++; }
    if(currentMonth < 0) { currentMonth = 11; currentYear--; }
    renderCalendar();
}

function toggleCalendar(){
    const box = document.getElementById("calendarBox");
    if(box.style.display === "block"){
        box.style.display = "none";
    } else {
        box.style.display = "block";
        renderCalendar();
    }
}
</script>