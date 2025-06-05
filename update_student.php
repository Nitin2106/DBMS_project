<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set response type to JSON
header('Content-Type: application/json');

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['stud_id']) || !is_numeric($data['stud_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid student ID'
    ]);
    exit();
}

// Required fields for update
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
            'message' => "Missing required field: $field"
        ]);
        exit();
    }
}

// Validate email format
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid email format'
    ]);
    exit();
}

// Database configuration
$host = 'localhost';
$db   = 'students';
$user = 'root';
$pass = '';

try {
    // Create connection
    $conn = new mysqli($host, $user, $pass, $db);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Check if email exists for other students
    $stmt = $conn->prepare("SELECT stud_id FROM student_details WHERE email = ? AND stud_id != ?");
    $stmt->bind_param("si", $data['email'], $data['stud_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'This email is already registered with another student'
        ]);
        exit();
    }
    $stmt->close();

    // Prepare update statement
    $stmt = $conn->prepare("UPDATE student_details SET 
        fname = ?, lname = ?, gender = ?, email = ?,
        course_id = ?, course_name = ?, birthdate = ?,
        mobile = ?, nationality = ?, state = ?, city = ?
        WHERE stud_id = ?");

    if (!$stmt) {
        throw new Exception('Failed to prepare update statement: ' . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param("ssssississsi",
        $data['fname'],
        $data['lname'],
        $data['gender'],
        $data['email'],
        $data['course_id'],
        $data['course_name'],
        $data['birthdate'],
        $data['mobile'],
        $data['nationality'],
        $data['state'],
        $data['city'],
        $data['stud_id']
    );

    if (!$stmt->execute()) {
        throw new Exception('Failed to update student: ' . $stmt->error);
    }

    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Student updated successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No changes made or student not found'
        ]);
    }

} catch (Exception $e) {
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