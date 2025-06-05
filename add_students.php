<?php
// Enable strict error reporting for development
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set response type to JSON
header('Content-Type: application/json');

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data === null) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid JSON data received',
        'debug' => [
            'json_error' => json_last_error_msg(),
            'raw_input' => $json
        ]
    ]);
    exit();
}

// Database configuration
$host     = 'localhost';
$db       = 'students';
$user     = 'root';
$pass     = '';

// Connect to the database
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit();
}

// Required fields for insertion
$required = [
    'fname', 'lname', 'gender', 'email',
    'course_id', 'course_name', 'birthdate',
    'mobile', 'nationality', 'state', 'city'
];

// Validate required fields
foreach ($required as $field) {
    if (!isset($data[$field]) || trim($data[$field]) === '') {
        echo json_encode([
            'status' => 'error',
            'message' => "Missing required field: $field",
            'debug' => ['field' => $field, 'value' => $data[$field] ?? null]
        ]);
        exit();
    }
}

// Validate email format
$email = trim($data['email']);
if (empty($email) || $email === '0' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid email format or empty email',
        'debug' => ['email' => $email]
    ]);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // First, check if email already exists
    $stmt = $conn->prepare("SELECT stud_id FROM student_details WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception("Email already exists");
    }
    $stmt->close();

    // Insert into student_details
    $stmt = $conn->prepare("INSERT INTO student_details (fname, lname, gender, email, course_id, course_name, birthdate, mobile, nationality, state, city) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssissssss", 
        $data['fname'],
        $data['lname'],
        $data['gender'],
        $email,
        $data['course_id'],
        $data['course_name'],
        $data['birthdate'],
        $data['mobile'],
        $data['nationality'],
        $data['state'],
        $data['city']
    );

    if (!$stmt->execute()) {
        throw new Exception("Error inserting student details");
    }
    $stud_id = $conn->insert_id;
    $stmt->close();

    // Get current mentor's ID from session
    session_start();
    if (isset($_SESSION['mentor_id'])) {
        // Insert into student_mentors table
        $stmt = $conn->prepare("INSERT INTO student_mentors (mentor_id, stud_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $_SESSION['mentor_id'], $stud_id);
        if (!$stmt->execute()) {
            throw new Exception("Error assigning mentor");
        }
        $stmt->close();
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode(['status' => 'success', 'message' => 'Student added successfully']);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>
