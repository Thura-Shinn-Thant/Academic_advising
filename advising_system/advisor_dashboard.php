<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "advising_system");

// Ensure user is logged in as advisor or consultant
if (!in_array($_SESSION['user_type'], ['advisor', 'education_consultant'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch appointments for this advisor/consultant
$appointments = $mysqli->query("
    SELECT a.*, s.full_name, s.email 
    FROM appointments a 
    JOIN students s ON a.student_id = s.id 
    WHERE (a.advisor_id='$user_id' OR a.consultant_id='$user_id') 
    AND a.status='pending'
");

// Fetch students with marks
$students = $mysqli->query("
    SELECT sc.*, s.full_name, s.email, c.course_name 
    FROM student_courses sc 
    JOIN students s ON sc.student_id = s.id 
    JOIN courses c ON sc.course_id = c.id
");

// Handle appointment status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_appointment'])) {
    $appointment_id = $mysqli->real_escape_string($_POST['appointment_id']);
    $status = $mysqli->real_escape_string($_POST['status']);
    $mysqli->query("UPDATE appointments SET status='$status' WHERE id='$appointment_id'");
    header("Location: advisor_dashboard.php");
    exit();
}

// Handle adding comments
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $student_id = $mysqli->real_escape_string($_POST['student_id']);
    $comment = $mysqli->real_escape_string($_POST['comment']);
    $mysqli->query("INSERT INTO comments (student_id, advisor_id, comment) VALUES ('$student_id', '$user_id', '$comment')");
    header("Location: advisor_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advisor/Consultant Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; padding: 20px; background-color: #f5f5f5; }
        h1 { color: #333; }
        .card { background: white; margin-bottom: 20px; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
        button { padding: 10px 20px; background-color: #007BFF; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: left; }
    </style>
</head>
<body>
    <h1>Welcome, <?php echo ucfirst($_SESSION['user_type']); ?>!</h1>

    <!-- Pending Appointments Section -->
    <div class="card">
        <h2>Pending Appointments</h2>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Email</th>
                    <th>Appointment Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($appointment = $appointments->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $appointment['full_name']; ?></td>
                        <td><?php echo $appointment['email']; ?></td>
                        <td><?php echo $appointment['appointment_date']; ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                <button type="submit" name="update_appointment" value="accepted">Accept</button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                <button type="submit" name="update_appointment" value="rejected">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Student Marks Section -->
    <div class="card">
        <h2>Student Marks</h2>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Email</th>
                    <th>Course</th>
                    <th>Marks</th>
                    <th>Comment</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($student = $students->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $student['full_name']; ?></td>
                        <td><?php echo $student['email']; ?></td>
                        <td><?php echo $student['course_name']; ?></td>
                        <td><?php echo $student['marks'] ?? 'N/A'; ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                <textarea name="comment" placeholder="Add a comment" required></textarea>
                                <button type="submit" name="add_comment">Submit</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
