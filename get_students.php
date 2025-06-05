<?php
header('Content-Type: application/json');
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$db   = 'students';
$user = 'root';
$pass = '';

try {
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Debug session information
    error_log("Session contents: " . print_r($_SESSION, true));

    // Get mentor's ID from session
    $mentor_id = $_SESSION['mentor_id'] ?? null;
    if (!$mentor_id) {
        throw new Exception("Mentor not logged in");
    }

    error_log("Mentor ID: " . $mentor_id);

    // First, let's check if there are any records in student_details
    $check_query = "SELECT COUNT(*) as total FROM student_details";
    $check_result = $conn->query($check_query);
    $total_students = $check_result->fetch_assoc()['total'];
    error_log("Total students in student_details: " . $total_students);

    // Check student_mentors table
    $check_mentors_query = "SELECT COUNT(*) as total FROM student_mentors WHERE mentor_id = ?";
    $check_stmt = $conn->prepare($check_mentors_query);
    $check_stmt->bind_param("i", $mentor_id);
    $check_stmt->execute();
    $mentor_students = $check_stmt->get_result()->fetch_assoc()['total'];
    error_log("Total students assigned to mentor {$mentor_id}: " . $mentor_students);

    // Get all students assigned to this mentor
    $query = "SELECT sd.* 
              FROM student_details sd 
              JOIN student_mentors sm ON sd.stud_id = sm.stud_id 
              WHERE sm.mentor_id = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $mentor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    error_log("Number of students fetched: " . count($students));
    error_log("Query result: " . print_r($students, true));

    echo json_encode([
        'status' => 'success',
        'data' => $students,
        'debug' => [
            'total_students' => $total_students,
            'mentor_students' => $mentor_students,
            'mentor_id' => $mentor_id
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in get_students.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>
