<?php
include 'includes/db.php';
$user_id = $_SESSION['user_id'];

/* FILTERS */
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$where = "WHERE student_id='$user_id'";

if($filter == 'present'){
    $where .= " AND status='present'";
}
elseif($filter == 'absent'){
    $where .= " AND status='absent'";
}

if(!empty($search)){
    $where .= " AND DATE(date_added)='$search'";
}

/* RECORDS */
$records = $conn->query("
    SELECT date_added, status 
    FROM attendance 
    $where
    ORDER BY date_added DESC
");

/* AI */
$present = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE student_id='$user_id' AND status='present'")->fetch_assoc()['total'];
$absent = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE student_id='$user_id' AND status='absent'")->fetch_assoc()['total'];

$total = $present + $absent;
$rate = $total > 0 ? ($present / $total) * 100 : 0;

if($rate >= 85){
    $risk = "LOW RISK";
    $class = "ai-low";
    $msg = "Excellent attendance. Keep it up!";
}
elseif($rate >= 60){
    $risk = "MEDIUM RISK";
    $class = "ai-medium";
    $msg = "Attendance is okay but needs improvement.";
}
else{
    $risk = "HIGH RISK";
    $class = "ai-high";
    $msg = "Warning: You are at risk of absenteeism!";
}
?>

<h2>📅 My Attendance</h2>

<!-- FEATURE CARDS -->
<div class="feature-grid">

    <a href="bash/export_pdf.php" target="_blank" class="feature-card pdf">
        📄
        <h4>Export Report</h4>
        <p>Download attendance PDF</p>
    </a>

    <a href="send_notification.php?student_id=<?= $user_id ?>" class="feature-card notify">
        📩
        <h4>Notify Parent</h4>
        <p>Send attendance alert</p>
    </a>

    <div class="feature-card qr" onclick="openQR()">
        📍
        <h4>Scan QR</h4>
        <p>Mark your attendance</p>
    </div>

    <div class="feature-card ai" onclick="toggleAI()">
        🧠
        <h4>AI Insight</h4>
        <p>Check performance</p>
    </div>

</div>

<!-- AI BOX -->
<div id="aiBox" class="ai-box" style="display:none;">
    <h3>🧠 Attendance Insight</h3>
    <p class="<?= $class ?>">Risk Level: <?= $risk ?></p>
    <p><?= $msg ?></p>
    <p><strong>Attendance Rate:</strong> <?= round($rate) ?>%</p>
</div>

<!-- FILTER -->
<form method="GET" class="filters">
    <select name="filter">
        <option value="all">All</option>
        <option value="present" <?= $filter=='present'?'selected':'' ?>>Present</option>
        <option value="absent" <?= $filter=='absent'?'selected':'' ?>>Absent</option>
    </select>

    <input type="date" name="search" value="<?= $search ?>">
    <button type="submit">Filter</button>
</form>

<!-- TABLE -->
<div class="table-box">
    <h3>📋 Attendance History</h3>
    <table>
        <tr>
            <th>Date</th>
            <th>Status</th>
        </tr>

        <?php if($records->num_rows > 0): ?>
            <?php while($r=$records->fetch_assoc()): ?>
            <tr>
                <td><?= date('M d, Y - h:i A', strtotime($r['date_added'])) ?></td>
                <td>
                    <span class="badge <?= $r['status'] ?>">
                        <?= ucfirst($r['status']) ?>
                    </span>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="2" style="text-align:center;">No records found</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<!-- QR MODAL -->
<div id="qrModal" class="modal">
  <div class="modal-content">
    <h3>Scan QR Code</h3>
    <div id="reader"></div>
    <button onclick="closeQR()">Close</button>
  </div>
</div>

<style>
body{
    background:#f4f7fb;
    font-family:'Segoe UI', sans-serif;
}

/* FEATURE GRID */
.feature-grid{
    display:grid;
    grid-template-columns: repeat(auto-fit, minmax(180px,1fr));
    gap:15px;
    margin-bottom:20px;
}

/* FEATURE CARD */
.feature-card{
    background:white;
    padding:20px;
    border-radius:12px;
    text-align:center;
    cursor:pointer;
    text-decoration:none;
    color:#333;
    box-shadow:0 5px 20px rgba(0,0,0,0.05);
    transition:all 0.25s ease;
}

.feature-card:hover{
    transform:translateY(-6px) scale(1.02);
    box-shadow:0 10px 25px rgba(0,0,0,0.12);
}

/* COLORS */
.feature-card.pdf{border-top:4px solid #ef4444;}
.feature-card.notify{border-top:4px solid #10b981;}
.feature-card.qr{border-top:4px solid #6366f1;}
.feature-card.ai{border-top:4px solid #f59e0b;}

.feature-card h4{
    margin:10px 0 5px;
}

/* AI */
.ai-box{
    background:white;
    padding:15px;
    border-radius:10px;
    margin-bottom:15px;
    box-shadow:0 5px 20px rgba(0,0,0,0.05);
}
.ai-low{color:#16a34a;font-weight:600;}
.ai-medium{color:#f59e0b;font-weight:600;}
.ai-high{color:#dc2626;font-weight:600;}

/* FILTER */
.filters{
    display:flex;
    gap:10px;
    margin-bottom:15px;
}
.filters input, .filters select{
    padding:8px;
    border-radius:6px;
    border:1px solid #ccc;
}
.filters button{
    background:#16a34a;
    color:white;
    border:none;
    padding:8px 12px;
    border-radius:6px;
}

/* TABLE */
.table-box{
    background:white;
    padding:20px;
    border-radius:10px;
    box-shadow:0 5px 20px rgba(0,0,0,0.05);
}

table{
    width:100%;
    border-collapse:collapse;
}
th{
    background:#f3f4f6;
    padding:12px;
}
td{
    padding:12px;
    border-bottom:1px solid #eee;
}

.badge{
    padding:6px 12px;
    border-radius:20px;
    font-size:13px;
}
.present{background:#dcfce7;color:#16a34a;}
.absent{background:#fee2e2;color:#dc2626;}

/* MODAL */
.modal{
 display:none;
 position:fixed;
 top:0; left:0;
 width:100%; height:100%;
 background:rgba(0,0,0,0.6);
}
.modal-content{
 background:white;
 padding:20px;
 width:320px;
 margin:100px auto;
 border-radius:10px;
 text-align:center;
}
</style>

<script src="https://unpkg.com/html5-qrcode"></script>

<script>
let scanner;

// AI toggle
function toggleAI(){
    let box = document.getElementById("aiBox");
    box.style.display = (box.style.display === "none") ? "block" : "none";
}

// QR OPEN
function openQR(){
 document.getElementById("qrModal").style.display="block";

 if(!scanner){
    scanner = new Html5QrcodeScanner("reader",{fps:10,qrbox:200});
    scanner.render(success=>{
        fetch("mark_attendance.php?id="+success)
        .then(()=>alert("✅ Attendance recorded"))
        .then(()=>location.reload());
    });
 }
}

// QR CLOSE
function closeQR(){
 document.getElementById("qrModal").style.display="none";
 if(scanner){
    scanner.clear();
    scanner = null;
 }
}
</script>