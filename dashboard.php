<?php
$host = 'localhost';      // Change to your database host
$db   = 'students';     // Your database name
$user = 'root';           // Your DB username
$pass = '';               // Your DB password (empty if using XAMPP default)
$charset = 'utf8mb4';

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
session_start();
require_once 'config.php'; // Your DB connection file

// Redirect if not logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Fetch student data
$email = $_SESSION['email'] ?? '';
$student = [];

if ($email) {
    $stmt = $conn->prepare("SELECT first_name, last_name, email FROM students WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>.hidden { display: none; }</style>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const links = document.querySelectorAll('.nav-links a');
            const sections = document.querySelectorAll('.section');
            
            links.forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = this.getAttribute('href').substring(1);
                    sections.forEach(s => s.classList.add('hidden'));
                    document.getElementById(target).classList.remove('hidden');
                });
            });
        });
    </script>
</head>
<body>
<div class="dashboard-container">
    <nav class="sidebar">
        <div class="logo">Student Portal</div>
        <ul class="nav-links">
            <li><a href="#profile">Profile</a></li>
            <li><a href="#courses">Courses</a></li>
            <li><a href="#grades">Grades</a></li>
            <li><a href="#attendance">Attendance</a></li>
            <li><a href="#mentors">Mentors</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
    <main class="main-content">
        <div id="profile" class="section">
            <h2>Student Profile</h2>
            <p><strong>First Name:</strong> <?php echo htmlspecialchars($student['first_name'] ?? ''); ?></p>
            <p><strong>Last Name:</strong> <?php echo htmlspecialchars($student['last_name'] ?? ''); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email'] ?? ''); ?></p>
        </div>
        <div id="courses" class="section hidden">
            <h2>Courses</h2>
            <p>Your enrolled courses will appear here.</p>
        </div>
        <div id="grades" class="section hidden">
            <h2>Grades</h2>
            <p>Your grades will appear here.</p>
        </div>
        <div id="attendance" class="section hidden">
            <h2>Attendance</h2>
            <p>Your attendance records will appear here.</p>
        </div>
        <div id="mentors" class="section hidden">
            <h2>Mentors</h2>
            <p>Your mentors will appear here.</p>
        </div>
    </main>
</div>
</body>
</html>