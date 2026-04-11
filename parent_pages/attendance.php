<?php

include 'includes/db.php';

// ✅ Check login + role
if(!isset($_SESSION['email']) || $_SESSION['role'] !== 'parent'){
    echo "<p>Access denied.</p>";
    exit;
}

$parent_email = $_SESSION['email'];

// ✅ Get children safely
$stmt = $conn->prepare("SELECT id, name FROM user WHERE parent_email=?");
$stmt->bind_param("s", $parent_email);
$stmt->execute();
$children_result = $stmt->get_result();

$children = [];
while($row = $children_result->fetch_assoc()){
    $children[] = $row;
}

// ✅ Filters
$selected_child = $_GET['child'] ?? '';
$selected_date = $_GET['date'] ?? '';

// ✅ Build query safely
$where = [];
$params = [];
$types = "";

if($selected_child){
    $where[] = "a.student_id = ?";
    $params[] = $selected_child;
    $types .= "i";
}

if($selected_date){
    $where[] = "DATE(a.created_at) = ?";
    $params[] = $selected_date;
    $types .= "s";
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

// ✅ Main query
$sql = "
    SELECT a.*, u.name 
    FROM attendance a
    JOIN user u ON a.student_id = u.id
    $where_sql
    ORDER BY a.created_at DESC
";

$stmt2 = $conn->prepare($sql);

if($params){
    $stmt2->bind_param($types, ...$params);
}

$stmt2->execute();
$result = $stmt2->get_result();

// ✅ Stats
$present = 0;
$absent = 0;
$data_rows = [];

while($row = $result->fetch_assoc()){
    $data_rows[] = $row;
    if($row['status'] == 'present') $present++;
    else $absent++;
}

$total = count($data_rows);
$percentage = $total > 0 ? round(($present/$total)*100) : 0;
?>

<h2>📅 Attendance Dashboard</h2>

<!-- FILTERS -->
<form method="GET" class="filters">
    <select name="child">
        <option value="">All Children</option>
        <?php foreach($children as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($selected_child == $c['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <input type="date" name="date" value="<?= $selected_date ?>">

    <button type="submit">Filter</button>
</form>

<!-- SUMMARY CARDS -->
 <div style="background:#eee; border-radius:10px; overflow:hidden; margin-bottom:20px;">
    <div style="
        width: <?= $percentage ?>%;
        background:#16a34a;
        color:white;
        padding:8px;
        text-align:center;
    ">
        <?= $percentage ?>% Attendance Rate
    </div>
</div>
<div class="cards">
    <div class="card">
        <h4>Total Records</h4>
        <p><?= $total ?></p>
    </div>

    <div class="card green">
        <h4>Present</h4>
        <p><?= $present ?></p>
    </div>

    <div class="card red">
        <h4>Absent</h4>
        <p><?= $absent ?></p>
    </div>

    <div class="card blue">
        <h4>Attendance Rate</h4>
        <p><?= $percentage ?>%</p>
    </div>
</div>

<!-- TABLE -->
<div class="table-box">

<?php if(empty($data_rows)): ?>
    <p class="empty">📭 No attendance records found. Try selecting a different filter.</p>
<?php else: ?>

<table>
<tr>
    <th>Student</th>
    <th>Status</th>
    <th>Date</th>
    <th>Time</th>
</tr>

<?php foreach($data_rows as $row): ?>
<tr>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td class="<?= $row['status'] ?>">
        <?= ucfirst($row['status']) ?>
    </td>
    <td><?= date("Y-m-d", strtotime($row['created_at'])) ?></td>
    <td><?= date("h:i A", strtotime($row['created_at'])) ?></td>
</tr>
<?php endforeach; ?>

</table>

<?php endif; ?>

</div>

<style>

/* FILTER */
.filters{
    display:flex;
    gap:10px;
    margin-bottom:20px;
}

.filters select,
.filters input,
.filters button{
    padding:8px;
    border-radius:8px;
    border:1px solid #ddd;
}

.filters button{
    background:#2563eb;
    color:white;
    border:none;
}


/* CARDS */
./* CARDS - Enhanced Design */
.card{
    background: linear-gradient(135deg, #ffffff, #f9fafb);
    padding:20px;
    border-radius:16px;
    box-shadow:0 6px 15px rgba(0,0,0,0.08);
    text-align:center;
    transition:0.3s ease;
    position:relative;
    overflow:hidden;
}

.card:hover{
    transform:translateY(-5px) scale(1.02);
    box-shadow:0 10px 25px rgba(0,0,0,0.12);
}

/* Top color bar */
.card::before{
    content:"";
    position:absolute;
    top:0;
    left:0;
    width:100%;
    height:5px;
}

/* Different colors per card */
.card:nth-child(1)::before{ background:#6b7280; } /* Total */
.card.green::before{ background:#16a34a; }
.card.red::before{ background:#dc2626; }
.card.blue::before{ background:#2563eb; }

/* Titles */
.card h4{
    font-size:14px;
    color:#6b7280;
    margin-bottom:8px;
    letter-spacing:0.5px;
}

/* Numbers */
.card p{
    font-size:28px;
    font-weight:700;
    margin:0;
}

/* Colors */
.green p{ color:#16a34a; }
.red p{ color:#dc2626; }
.blue p{ color:#2563eb; }

.green p{ color:#16a34a; }
.red p{ color:#dc2626; }
.blue p{ color:#2563eb; }

/* TABLE */
.table-box{
    background:white;
    padding:20px;
    border-radius:12px;
}

table{
    width:100%;
    border-collapse:collapse;
}

table th{
    background:#f3f4f6;
    padding:10px;
}

table td{
    padding:10px;
    border-bottom:1px solid #eee;
}

.present{ color:#16a34a; font-weight:bold; }
.absent{ color:#dc2626; font-weight:bold; }

.empty{
    text-align:center;
    color:#777;
}

</style>