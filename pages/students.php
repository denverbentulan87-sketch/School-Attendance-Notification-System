<?php
include 'includes/db.php';

$search = $_GET['search'] ?? '';
$editData = null;

/* ================= ADD STUDENT ================= */
if(isset($_POST['add'])){
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO `user` (name, email, password, role) VALUES (?, ?, ?, 'student')");
    $stmt->bind_param("sss", $name, $email, $password);

    if ($stmt->execute()) {
        echo "<script>alert('Student added successfully'); window.location.href='admin_dashboard.php?page=students';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    exit();
}

/* ================= UPDATE ================= */
if(isset($_POST['update'])){
    $id = intval($_POST['id'] ?? 0);
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';

    $stmt = $conn->prepare("UPDATE `user` SET name=?, email=? WHERE id=?");
    $stmt->bind_param("ssi", $name, $email, $id);
    $stmt->execute();

    echo "<script>window.location.href='admin_dashboard.php?page=students';</script>";
    exit();
}

/* ================= DELETE ================= */
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);

    $stmt = $conn->prepare("DELETE FROM `user` WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo "<script>window.location.href='admin_dashboard.php?page=students';</script>";
    exit();
}

/* ================= EDIT ================= */
if(isset($_GET['edit'])){
    $id = intval($_GET['edit']);

    $stmt = $conn->prepare("SELECT * FROM `user` WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
}

/* ================= SEARCH ================= */
if(!empty($search)){
    $stmt = $conn->prepare("SELECT * FROM `user` WHERE role='student' AND name LIKE ?");
    $like = "%$search%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $students = $stmt->get_result();
} else {
    $students = $conn->query("SELECT * FROM `user` WHERE role='student'");
}

$total_students = $students ? $students->num_rows : 0;
?>

<!-- ================= DESIGN ================= -->
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f6f9;
}

.table-container {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

/* HEADER */
.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

/* SEARCH */
.search-box input {
    padding: 10px 15px;
    border-radius: 8px;
    border: 1px solid #ccc;
    outline: none;
    width: 220px;
}

.search-box input:focus {
    border-color: #4CAF50;
    box-shadow: 0 0 5px rgba(76,175,80,0.4);
}

/* FORM */
.student-form {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    background: #f9f9f9;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.student-form input {
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
    flex: 1;
    min-width: 180px;
}

.student-form input:focus {
    border-color: #4CAF50;
}

/* BUTTONS */
.btn-add {
    background: #4CAF50;
    color: white;
    border: none;
    padding: 10px 18px;
    border-radius: 8px;
    cursor: pointer;
}

.btn-add:hover {
    background: #45a049;
}

.btn {
    padding: 10px 15px;
    border-radius: 8px;
    text-decoration: none;
    color: white;
}

.btn-edit {
    background: #2196F3;
}

.btn-delete {
    background: #f44336;
}

/* TABLE */
table {
    width: 100%;
    border-collapse: collapse;
}

table th {
    background: #4CAF50;
    color: white;
    padding: 12px;
}

table td {
    padding: 10px;
    border-bottom: 1px solid #eee;
}

table tr:hover {
    background: #f5f5f5;
}
</style>

<!-- ================= UI ================= -->
<div class="table-container">

    <div class="table-header">
        <h2>Students Management</h2>

        <form method="GET" class="search-box">
            <input type="hidden" name="page" value="students">
            <input type="text" name="search"
                   placeholder="🔍 Search student..."
                   value="<?= htmlspecialchars($search) ?>">
        </form>
    </div>

    <!-- FORM -->
    <form method="POST" class="student-form">
        <input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">

        <input type="text" name="name" placeholder="👤 Student Name"
               value="<?= htmlspecialchars($editData['name'] ?? '') ?>" required>

        <input type="email" name="email" placeholder="📧 Email Address"
               value="<?= htmlspecialchars($editData['email'] ?? '') ?>" required>

        <?php if(empty($editData)): ?>
            <input type="password" name="password" placeholder="🔒 Password" required>
            <button class="btn-add" name="add">➕ Add Student</button>
        <?php else: ?>
            <button class="btn-add" name="update"> Update</button>
            <a class="btn btn-delete" href="admin_dashboard.php?page=students">Cancel</a>
        <?php endif; ?>
    </form>

    <p><strong>Total Students:</strong> <?= $total_students ?></p>

    <table>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>QR Code</th>
            <th>Attendance</th>
            <th>Action</th>
        </tr>

        <?php if($students && $students->num_rows > 0): ?>
            <?php while($row = $students->fetch_assoc()): ?>

            <?php
            $id = $row['id'];
            $name = $row['name'];
            $email = $row['email'];

            $stmt = $conn->prepare("
                SELECT 
                COALESCE(SUM(status='present'),0) as present,
                COALESCE(SUM(status='absent'),0) as absent
                FROM attendance WHERE student_id=?
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $att = $stmt->get_result()->fetch_assoc();

            $present = $att['present'];
            $absent = $att['absent'];
            ?>

            <tr>
                <td><?= htmlspecialchars($name) ?></td>
                <td><?= htmlspecialchars($email) ?></td>

                <td>
                    <img src="generate_qr.php?id=<?= $id ?>" width="70">
                </td>

                <td>
                    <span style="color:green;">✔ <?= $present ?></span> |
                    <span style="color:red;">✖ <?= $absent ?></span>
                </td>

                <td>
                    <a class="btn btn-edit"
                       href="admin_dashboard.php?page=students&edit=<?= $id ?>">Edit</a>

                    <a class="btn btn-delete"
                       href="admin_dashboard.php?page=students&delete=<?= $id ?>"
                       onclick="return confirm('Delete this student?')">Delete</a>
                </td>
            </tr>

            <?php endwhile; ?>
        <?php else: ?>
        <tr>
            <td colspan="5" style="text-align:center;">No students found</td>
        </tr>
        <?php endif; ?>

    </table>

</div>