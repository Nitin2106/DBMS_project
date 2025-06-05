<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$host = 'localhost';
$db   = 'students';
$user = 'root';
$pass = '';
$message = '';

try {
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['assign_mentor'])) {
            $stud_id = $_POST['student'];
            $mentor_id = $_POST['mentor'];
            
            // Check if student already has a mentor
            $check_stmt = $conn->prepare("SELECT mentor_id FROM student_mentors WHERE stud_id = ?");
            $check_stmt->bind_param("i", $stud_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing assignment
                $update_stmt = $conn->prepare("UPDATE student_mentors SET mentor_id = ? WHERE stud_id = ?");
                $update_stmt->bind_param("ii", $mentor_id, $stud_id);
                $update_stmt->execute();
                $message = "Mentor assignment updated successfully!";
            } else {
                // Create new assignment
                $insert_stmt = $conn->prepare("INSERT INTO student_mentors (stud_id, mentor_id) VALUES (?, ?)");
                $insert_stmt->bind_param("ii", $stud_id, $mentor_id);
                $insert_stmt->execute();
                $message = "New mentor assigned successfully!";
            }
        }
    }

    // Get all students
    $students_query = "SELECT s.stud_id, s.fname, s.lname, s.email, c.course_name, 
                             sm.mentor_id as current_mentor_id
                      FROM student_details s 
                      LEFT JOIN course_details c ON s.course_id = c.course_id
                      LEFT JOIN student_mentors sm ON s.stud_id = sm.stud_id
                      ORDER BY s.fname, s.lname";
    $students_result = $conn->query($students_query);

    // Get all mentors
    $mentors_query = "SELECT mentor_id, mentor_name, mentor_department FROM mentors ORDER BY mentor_name";
    $mentors_result = $conn->query($mentors_query);
    $mentors = $mentors_result->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    $message = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mentor Assignment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        select {
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 200px;
        }

        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        .navigation {
            margin-bottom: 20px;
        }

        .navigation .logout-btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }

        .navigation .logout-btn:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="navigation">
            <a href="login.php?logout=true" class="logout-btn">Logout</a>
        </div>

        <h1>Mentor Assignment</h1>

        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Email</th>
                    <th>Course</th>
                    <th>Current Mentor</th>
                    <th>Assign Mentor</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($student = $students_result->fetch_assoc()): ?>
                    <form method="POST">
                        <tr>
                            <td><?php echo htmlspecialchars($student['fname'] . ' ' . $student['lname']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo htmlspecialchars($student['course_name']); ?></td>
                            <td>
                                <?php
                                $current_mentor = '';
                                foreach ($mentors as $mentor) {
                                    if ($mentor['mentor_id'] == $student['current_mentor_id']) {
                                        $current_mentor = $mentor['mentor_name'] . ' (' . $mentor['mentor_department'] . ')';
                                        break;
                                    }
                                }
                                echo htmlspecialchars($current_mentor ?: 'Not Assigned');
                                ?>
                            </td>
                            <td>
                                <input type="hidden" name="student" value="<?php echo $student['stud_id']; ?>">
                                <select name="mentor" required>
                                    <option value="">Select Mentor</option>
                                    <?php foreach ($mentors as $mentor): ?>
                                        <option value="<?php echo $mentor['mentor_id']; ?>"
                                                <?php echo ($mentor['mentor_id'] == $student['current_mentor_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($mentor['mentor_name'] . ' (' . $mentor['mentor_department'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <button type="submit" name="assign_mentor">Update</button>
                            </td>
                        </tr>
                    </form>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
<?php
$conn->close();
?> 