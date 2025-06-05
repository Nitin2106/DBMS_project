<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check if user is logged in and is a student
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    header("Location: login.php");
    exit();
}

$host = 'localhost';
$db   = 'students';
$user = 'root';
$pass = '';

try {
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $user_id = $_SESSION['user_id'] ?? 0;
    $student = [];
    $grades = [];
    $attendance = [];
    $mentor = [];
    $course_name = '';
    $mentor_notes = [];
    $academic_progress = [];
    $achievements = [];

    if ($user_id) {
        // Get email from register table
        $stmt = $conn->prepare("SELECT email FROM register WHERE user_id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $emailResult = $stmt->get_result();
        $emailData = $emailResult->fetch_assoc();
        $stmt->close();

        $email = $emailData['email'] ?? '';

        if (!$email) {
            throw new Exception("Email not found for user_id: " . $user_id);
        }

        // Get student details
        $stmt = $conn->prepare("SELECT * FROM student_details WHERE email = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        $stmt->close();

        if (!$student) {
            throw new Exception("Student details not found for email: " . $email);
        }

        if ($student && $student['course_id']) {
            $stmt = $conn->prepare("SELECT course_name FROM course_details WHERE course_id = ?");
            $stmt->bind_param("i", $student['course_id']);
            $stmt->execute();
            $res = $stmt->get_result();
            $course = $res->fetch_assoc();
            $course_name = $course['course_name'] ?? '';
            $stmt->close();
        }

        if ($student && $student['stud_id']) {
            $stmt = $conn->prepare("SELECT c.course_name, g.grade FROM grades g JOIN course_details c ON g.course_id = c.course_id WHERE g.stud_id = ?");
            $stmt->bind_param("i", $student['stud_id']);
            $stmt->execute();
            $gradesResult = $stmt->get_result();
            while ($row = $gradesResult->fetch_assoc()) {
                $grades[$row['course_name']] = $row['grade'];
            }
            $stmt->close();

            $stmt = $conn->prepare("SELECT m.mentor_name, m.mentor_department FROM student_mentors sm JOIN mentors m ON sm.mentor_id = m.mentor_id WHERE sm.stud_id = ?");
            $stmt->bind_param("i", $student['stud_id']);
            $stmt->execute();
            $mentorResult = $stmt->get_result();
            $mentor = $mentorResult->fetch_assoc();
            $stmt->close();

            $stmt = $conn->prepare("SELECT total_classes, attended_classes FROM attendance WHERE stud_id = ?");
            $stmt->bind_param("i", $student['stud_id']);
            $stmt->execute();
            $attResult = $stmt->get_result();
            $attendance = $attResult->fetch_assoc();
            $stmt->close();

            // Get mentor notes
            $stmt = $conn->prepare("
                SELECT 
                    mn.note_content,
                    mn.note_date,
                    m.mentor_name,
                    mn.note_type
                FROM mentor_notes mn
                JOIN mentors m ON mn.mentor_id = m.mentor_id
                WHERE mn.stud_id = ?
                ORDER BY mn.note_date DESC
            ");
            $stmt->bind_param("i", $student['stud_id']);
            $stmt->execute();
            $notesResult = $stmt->get_result();
            while ($note = $notesResult->fetch_assoc()) {
                $mentor_notes[] = $note;
            }
            $stmt->close();

            // Get academic progress
            $stmt = $conn->prepare("
                SELECT 
                    ap.semester,
                    ap.cgpa,
                    ap.academic_standing,
                    ap.remarks
                FROM academic_progress ap
                WHERE ap.stud_id = ?
                ORDER BY ap.semester DESC
            ");
            $stmt->bind_param("i", $student['stud_id']);
            $stmt->execute();
            $progressResult = $stmt->get_result();
            while ($progress = $progressResult->fetch_assoc()) {
                $academic_progress[] = $progress;
            }
            $stmt->close();

            // Get achievements and recognitions
            $stmt = $conn->prepare("
                SELECT 
                    achievement_title,
                    achievement_date,
                    description,
                    awarded_by
                FROM student_achievements
                WHERE stud_id = ?
                ORDER BY achievement_date DESC
            ");
            $stmt->bind_param("i", $student['stud_id']);
            $stmt->execute();
            $achievementsResult = $stmt->get_result();
            while ($achievement = $achievementsResult->fetch_assoc()) {
                $achievements[] = $achievement;
            }
            $stmt->close();
        }

        $total = 0;
        $count = 0;
        foreach ($grades as $grade) {
            $total += (int)$grade;
            $count++;
        }
    } else {
        throw new Exception("No user_id found in session");
    }
} catch (Exception $e) {
    // Log the error
    error_log("Error in student-dashboard.php: " . $e->getMessage());
    
    // Display a user-friendly error message
    die("An error occurred while loading your dashboard. Please try again later or contact support.<br>Error: " . htmlspecialchars($e->getMessage()));
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Get all subjects
$subjects_query = $conn->prepare("
    SELECT s.*, 
           sa.total_classes, 
           sa.attended_classes,
           sg.assignment_score,
           sg.midterm_score,
           sg.final_score,
           sg.total_score,
           sg.grade_letter
    FROM subjects s
    LEFT JOIN subject_attendance sa ON s.subject_id = sa.subject_id AND sa.stud_id = ?
    LEFT JOIN subject_grades sg ON s.subject_id = sg.subject_id AND sg.stud_id = ?
    WHERE s.course_id = ?
    ORDER BY s.subject_name
");
$subjects_query->bind_param("iii", $student['stud_id'], $student['stud_id'], $student['course_id']);
$subjects_query->execute();
$subjects_result = $subjects_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --background-dark: #1e1e2f;
            --text-light: #ffffff;
            --text-dark: #2c2c44;
        }

        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-dark);
            color: var(--text-light);
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: #151521;
            padding: 20px;
            color: white;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            background: var(--background-dark);
        }

        .section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            color: var(--text-light);
        }

        .profile-card {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-light);
        }

        th {
            background-color: rgba(52, 152, 219, 0.2);
            font-weight: 600;
            color: var(--primary-color);
        }

        .mentor-note {
            background: rgba(52, 152, 219, 0.1);
            border-left: 4px solid var(--primary-color);
            padding: 15px;
            margin: 10px 0;
            border-radius: 0 5px 5px 0;
        }

        .achievement-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }

        .progress-semester {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .status-indicator {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            margin-left: 10px;
        }

        .status-good {
            background: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
        }

        .status-warning {
            background: rgba(241, 196, 15, 0.2);
            color: #f1c40f;
        }

        .status-danger {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }

        .nav-links {
            list-style: none;
            padding: 0;
        }

        .nav-links a {
            color: var(--text-light);
            text-decoration: none;
            padding: 10px 15px;
            display: block;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .nav-links a:hover {
            background: rgba(52, 152, 219, 0.2);
        }

        .subject-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .subject-card {
            background: rgba(52, 152, 219, 0.1);
            border-radius: 10px;
            padding: 20px;
            transition: transform 0.3s ease;
        }

        .subject-card:hover {
            transform: translateY(-5px);
        }

        .subject-card h3 {
            color: var(--primary-color);
            margin-top: 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 10px;
        }

        .grade-section, .attendance-section {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .grade-details, .attendance-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }

        .stat-label {
            color: rgba(255, 255, 255, 0.7);
        }

        .stat-value {
            color: var(--text-light);
            font-weight: bold;
            text-align: right;
        }

        .grade-letter, .attendance-percentage {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
            color: var(--primary-color);
        }

        .attendance-percentage {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
            padding: 10px;
            border-radius: 8px;
        }

        .attendance-good {
            background: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
        }

        .attendance-warning {
            background: rgba(241, 196, 15, 0.2);
            color: #f1c40f;
        }

        .attendance-danger {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <nav class="sidebar">
            <div class="logo">Student Dashboard</div>
            <ul class="nav-links">
                <li><a href="#profile">Profile</a></li>
                <li><a href="#subjects">Subjects</a></li>
                <li><a href="#academic">Academic Progress</a></li>
                <li><a href="#mentor-notes">Mentor Notes</a></li>
                <li><a href="#achievements">Achievements</a></li>
                <li><a href="?logout=true">Logout</a></li>
            </ul>
        </nav>
        <main class="main-content">
            <div id="profile" class="section profile-card">
                <h2>Welcome, <?php echo htmlspecialchars($student['fname'] ?? '') . ' ' . htmlspecialchars($student['lname'] ?? ''); ?></h2>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                    <div>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email'] ?? ''); ?></p>
                        <p><strong>Course:</strong> <?php echo htmlspecialchars($course_name); ?></p>
                        <p><strong>Nationality:</strong> <?php echo htmlspecialchars($student['nationality'] ?? ''); ?></p>
                    </div>
                    <div>
                        <p><strong>Mobile:</strong> <?php echo htmlspecialchars($student['mobile'] ?? ''); ?></p>
                        <p><strong>State:</strong> <?php echo htmlspecialchars($student['state'] ?? ''); ?></p>
                        <p><strong>City:</strong> <?php echo htmlspecialchars($student['city'] ?? ''); ?></p>
                        <p><strong>Mentor:</strong> <?php echo htmlspecialchars($mentor['mentor_name'] ?? 'Not Assigned'); ?> 
                           (<?php echo htmlspecialchars($mentor['mentor_department'] ?? ''); ?>)</p>
                    </div>
                </div>
            </div>

            <div id="subjects" class="section">
                <h2>Your Subjects</h2>
                <div class="subject-grid">
                    <?php while ($subject = $subjects_result->fetch_assoc()): ?>
                        <div class="subject-card">
                            <h3><?php echo htmlspecialchars($subject['subject_name']); ?></h3>
                            
                            <!-- Grades Section -->
                            <div class="grade-section">
                                <h4>Grades</h4>
                                <?php if (isset($subject['grade_letter'])): ?>
                                    <div class="grade-letter"><?php echo htmlspecialchars($subject['grade_letter']); ?></div>
                                    <div class="grade-details">
                                        <span class="stat-label">Assignments (30%)</span>
                                        <span class="stat-value"><?php echo number_format($subject['assignment_score'], 2); ?></span>
                                        <span class="stat-label">Midterm (30%)</span>
                                        <span class="stat-value"><?php echo number_format($subject['midterm_score'], 2); ?></span>
                                        <span class="stat-label">Final (40%)</span>
                                        <span class="stat-value"><?php echo number_format($subject['final_score'], 2); ?></span>
                                        <span class="stat-label">Total Score</span>
                                        <span class="stat-value"><?php echo number_format($subject['total_score'], 2); ?></span>
                                    </div>
                                <?php else: ?>
                                    <p>No grades recorded yet</p>
                                <?php endif; ?>
                            </div>

                            <!-- Attendance Section -->
                            <div class="attendance-section">
                                <h4>Attendance</h4>
                                <?php if (isset($subject['total_classes']) && $subject['total_classes'] > 0): ?>
                                    <?php 
                                    $attendance_percentage = ($subject['attended_classes'] / $subject['total_classes']) * 100;
                                    $attendance_class = '';
                                    if ($attendance_percentage >= 80) {
                                        $attendance_class = 'attendance-good';
                                    } elseif ($attendance_percentage >= 70) {
                                        $attendance_class = 'attendance-warning';
                                    } else {
                                        $attendance_class = 'attendance-danger';
                                    }
                                    ?>
                                    <div class="attendance-percentage <?php echo $attendance_class; ?>">
                                        <?php echo number_format($attendance_percentage, 1); ?>%
                                    </div>
                                    <div class="attendance-details">
                                        <span class="stat-label">Total Classes</span>
                                        <span class="stat-value"><?php echo $subject['total_classes']; ?></span>
                                        <span class="stat-label">Attended</span>
                                        <span class="stat-value"><?php echo $subject['attended_classes']; ?></span>
                                    </div>
                                <?php else: ?>
                                    <p>No attendance recorded yet</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <div id="academic" class="section">
                <h2>Academic Progress</h2>
                <?php if (!empty($academic_progress)): ?>
                    <?php foreach ($academic_progress as $progress): ?>
                        <div class="progress-semester">
                            <h3>Semester <?php echo htmlspecialchars($progress['semester']); ?></h3>
                            <p><strong>CGPA:</strong> <?php echo htmlspecialchars($progress['cgpa']); ?></p>
                            <p><strong>Academic Standing:</strong> 
                                <?php echo htmlspecialchars($progress['academic_standing']); ?>
                                <?php
                                    $status_class = 'status-good';
                                    if ($progress['cgpa'] < 2.0) {
                                        $status_class = 'status-danger';
                                    } elseif ($progress['cgpa'] < 2.5) {
                                        $status_class = 'status-warning';
                                    }
                                ?>
                                <span class="status-indicator <?php echo $status_class; ?>">
                                    <?php echo $progress['cgpa'] >= 2.5 ? 'Good Standing' : ($progress['cgpa'] >= 2.0 ? 'Warning' : 'Academic Probation'); ?>
                                </span>
                            </p>
                            <p><strong>Remarks:</strong> <?php echo htmlspecialchars($progress['remarks']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No academic progress data available.</p>
                <?php endif; ?>
            </div>

            <div id="mentor-notes" class="section">
                <h2>Mentor Notes</h2>
                <?php if (!empty($mentor_notes)): ?>
                    <?php foreach ($mentor_notes as $note): ?>
                        <div class="mentor-note">
                            <p style="color: #666; font-size: 0.9em;">
                                <?php echo date('F j, Y', strtotime($note['note_date'])); ?> - 
                                <?php echo htmlspecialchars($note['mentor_name']); ?>
                            </p>
                            <p style="margin: 10px 0;">
                                <strong><?php echo htmlspecialchars($note['note_type']); ?>:</strong>
                                <?php echo htmlspecialchars($note['note_content']); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No mentor notes available.</p>
                <?php endif; ?>
            </div>

            <div id="achievements" class="section">
                <h2>Achievements & Recognition</h2>
                <?php if (!empty($achievements)): ?>
                    <?php foreach ($achievements as $achievement): ?>
                        <div class="achievement-card">
                            <h3><?php echo htmlspecialchars($achievement['achievement_title']); ?></h3>
                            <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($achievement['achievement_date'])); ?></p>
                            <p><strong>Awarded By:</strong> <?php echo htmlspecialchars($achievement['awarded_by']); ?></p>
                            <p><?php echo htmlspecialchars($achievement['description']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No achievements recorded yet.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
<?php
$subjects_query->close();
$conn->close();
?>
