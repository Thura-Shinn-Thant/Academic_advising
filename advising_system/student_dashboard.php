<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "advising_system");

// Ensure user is logged in as a student
if ($_SESSION['user_type'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch student profile
$profile = $mysqli->query("SELECT * FROM students WHERE user_id='$user_id'")->fetch_assoc();

// Define the available courses
$courses = [
    'NeuroScience',
    'Computing',
    'Software Development',
    'Computer Science',
    'Digital Marketing',
    'Physics',
    'Quantum Computing'
];

// Handle profile setup/update and course selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = $mysqli->real_escape_string($_POST['full_name']);
    $email = $mysqli->real_escape_string($_POST['email']);
    $phone = $mysqli->real_escape_string($_POST['phone']);
    $bio = $mysqli->real_escape_string($_POST['bio']);
    
    // Update or insert profile
    if ($profile) {
        $mysqli->query("UPDATE students SET full_name='$full_name', email='$email', phone='$phone', bio='$bio' WHERE user_id='$user_id'");
    } else {
        $mysqli->query("INSERT INTO students (user_id, full_name, email, phone, bio) VALUES ('$user_id', '$full_name', '$email', '$phone', '$bio')");
    }

    // Handle course selections
    if (isset($_POST['courses'])) {
        // First delete existing course selections
        $mysqli->query("DELETE FROM student_courses WHERE student_id='$user_id'");

        // Insert new course selections
        foreach ($_POST['courses'] as $course_name) {
            // Check if course already exists in the database, if not, add it
            $result = $mysqli->query("SELECT id FROM courses WHERE course_name='$course_name'");
            $course = $result->fetch_assoc();
            if (!$course) {
                $mysqli->query("INSERT INTO courses (course_name) VALUES ('$course_name')");
                $course_id = $mysqli->insert_id; // Get the new course ID
            } else {
                $course_id = $course['id'];
            }
            $mysqli->query("INSERT INTO student_courses (student_id, course_id) VALUES ('$user_id', '$course_id')");
        }
    }

    // Handle marks entry
    if (isset($_POST['marks'])) {
        foreach ($_POST['marks'] as $course_name => $marks) {
            // Get course ID by course name
            $result = $mysqli->query("SELECT id FROM courses WHERE course_name='$course_name'");
            $course = $result->fetch_assoc();
            $course_id = $course['id'];

            // Update or insert marks
            $marks = $mysqli->real_escape_string($marks);
            $mysqli->query("UPDATE student_courses SET marks='$marks' WHERE student_id='$user_id' AND course_id='$course_id'");
        }
    }

    // Redirect to prevent form resubmission
    header("Location: student_dashboard.php");
    exit();
}

// Fetch student courses
$student_courses = $mysqli->query("SELECT sc.*, c.course_name FROM student_courses sc JOIN courses c ON sc.course_id = c.id WHERE sc.student_id='$user_id'");

// Fetch comments
$comments = $mysqli->query("SELECT * FROM comments WHERE student_id='$user_id'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; padding: 20px; background-color: #f5f5f5; }
        h1 { color: #333; }
        form { margin-bottom: 20px; }
        input, select, textarea { display: block; width: 100%; margin-bottom: 10px; padding: 10px; }
        button { padding: 10px 20px; background-color: #007BFF; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .card { background: white; margin-bottom: 20px; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
    </style>
</head>
<body>
    <h1>Welcome, <?php echo $profile ? $profile['full_name'] : "Student"; ?>!</h1>

    <!-- Profile Section -->
    <div class="card">
        <h2>Profile</h2>
        <form method="POST">
            <input type="text" name="full_name" placeholder="Full Name" value="<?php echo $profile['full_name'] ?? ''; ?>" required>
            <input type="email" name="email" placeholder="Email" value="<?php echo $profile['email'] ?? ''; ?>" required>
            <input type="text" name="phone" placeholder="Phone" value="<?php echo $profile['phone'] ?? ''; ?>" required>
           
    </div>

    <!-- Course Selection Section -->
    <div class="card">
        <h2>Select Your Courses</h2>
        <form method="POST">
            <h3>Choose Courses:</h3>
            <select name="courses[]" multiple required style="height: 150px;">
                <?php foreach ($courses as $course_name): ?>
                    <option value="<?php echo $course_name; ?>"
                        <?php 
                            // Check if the student is already enrolled in this course
                            $student_courses->data_seek(0); // Reset the result pointer
                            while ($student_course = $student_courses->fetch_assoc()) {
                                if ($student_course['course_name'] == $course_name) {
                                    echo "selected";
                                    break;
                                }
                            }
                        ?>
                    > <?php echo $course_name; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Marks Entry Section -->
            <h3>Enter Marks for Your Courses:</h3>
            <?php 
                $student_courses->data_seek(0); // Reset the result pointer
                while ($course = $student_courses->fetch_assoc()): ?>
                <label><?php echo $course['course_name']; ?>:</label>
                <input type="number" name="marks[<?php echo $course['course_name']; ?>]" value="<?php echo $course['marks'] ?? ''; ?>" placeholder="Enter Marks">
            <?php endwhile; ?>

        </form>
    </div>

    <!-- Save Button (One Save for all) -->
    <form method="POST">
        <button type="submit" name="update_profile">Save All</button>
    </form>

    <!-- Your Courses Section -->
    <div class="card">
        <h3>Your Courses:</h3>
        <ul>
            <?php while ($course = $student_courses->fetch_assoc()): ?>
                <li><?php echo $course['course_name']; ?> - Marks: <?php echo $course['marks'] ?? 'Not Entered'; ?></li>
            <?php endwhile; ?>
        </ul>
    </div>

    <!-- Comments Section -->
    <div class="card">
        <h2>Advisor Comments</h2>
        <ul>
            <?php while ($comment = $comments->fetch_assoc()): ?>
                <li><?php echo $comment['comment']; ?></li>
            <?php endwhile; ?>
        </ul>
    </div>
</body>
</html>
