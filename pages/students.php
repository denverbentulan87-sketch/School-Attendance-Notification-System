<?php
include 'includes/db.php';

$search = $_GET['search'] ?? '';
$editData = null;

if(isset($_POST['add'])){
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = md5($_POST['password']);

    $stmt = $conn->prepare("INSERT INTO user (name,email,password,role) VALUES (?, ?, ?, 'student')");
    $stmt->bind_param("sss", $name, $email, $password);
    $stmt->execute();

    echo "<script>window.location.href='admin_dashboard.php?page=students';</script>";
    exit();
}

if(isset($_POST['update'])){
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];

    $stmt = $conn->prepare("UPDATE user SET name=?, email=? WHERE id=?");
    $stmt->bind_param("ssi", $name, $email, $id);
    $stmt->execute();

    echo "<script>window.location.href='admin_dashboard.php?page=students';</script>";
    exit();
}

if(isset($_GET['delete'])){
    $id = $_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM user WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo "<script>window.location.href='admin_dashboard.php?page=students';</script>";
    exit();
}

if(isset($_GET['edit'])){
    $id = $_GET['edit'];

    $stmt = $conn->prepare("SELECT * FROM user WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
}

if(!empty($search)){
    $stmt = $conn->prepare("SELECT * FROM user WHERE role='student' AND name LIKE ?");
    $like = "%$search%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $students = $stmt->get_result();
} else {
    $students = $conn->query("SELECT * FROM user WHERE role='student'");
}

$total_students = $students ? $students->num_rows : 0;
?>

<div class="table-container">

    <div class="table-header">
        <h2>Students Management</h2>

        <form method="GET" class="search-box">
            <input type="hidden" name="page" value="students">
            <input type="text" name="search" placeholder="Search student..." value="<?= htmlspecialchars($search) ?>">
        </form>
    </div>

    <!-- ADD / UPDATE FORM -->
    <form method="POST" style="margin-bottom:15px; display:flex; gap:10px; flex-wrap:wrap;">
        <input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">

        <input type="text" name="name" placeholder="Name"
               value="<?= $editData['name'] ?? '' ?>" required>

        <input type="email" name="email" placeholder="Email"
               value="<?= $editData['email'] ?? '' ?>" required>

        <?php if(empty($editData)): ?>
            <input type="password" name="password" placeholder="Password" required>
            <button class="btn-add" name="add">Add Student</button>
        <?php else: ?>
            <button class="btn-add" name="update">Update</button>
            <a class="btn" href="admin_dashboard.php?page=students">Cancel</a>
        <?php endif; ?>
    </form>

    <p><strong>Total Students:</strong> <?= $total_students ?></p>

    <table>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Attendance</th>
            <th>Action</th>
        </tr>

        <?php if($students && $students->num_rows > 0): ?>
            <?php while($row = $students->fetch_assoc()): ?>

            <?php
            // SAFE attendance query
            $stmt = $conn->prepare("
                SELECT 
                SUM(status='present') as present,
                SUM(status='absent') as absent
                FROM attendance WHERE student_id=?
            ");
            $stmt->bind_param("i", $row['id']);
            $stmt->execute();
            $att = $stmt->get_result()->fetch_assoc();

            $present = $att['present'] ?? 0;
            $absent = $att['absent'] ?? 0;
            ?>

            <tr>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>

                <td>
                    <span style="color:green;">✔ <?= $present ?></span> |
                    <span style="color:red;">✖ <?= $absent ?></span>
                </td>

                <td>
                    <a class="btn btn-edit"
                       href="admin_dashboard.php?page=students&edit=<?= $row['id'] ?>">Edit</a>

                    <a class="btn btn-delete"
                       href="admin_dashboard.php?page=students&delete=<?= $row['id'] ?>"
                       onclick="return confirm('Delete this student?')">Delete</a>
                </td>
            </tr>

            <?php endwhile; ?>
        <?php else: ?>
        <tr>
            <td colspan="4" style="text-align:center;">No students found</td>
        </tr>
        <?php endif; ?>

    </table>

</div>