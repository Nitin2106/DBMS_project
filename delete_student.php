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

    $stud_id = (int)$data['stud_id'];
    
    // Start transaction
    $conn->begin_transaction();

    try {
        // Delete from subject_grades first
        $stmt = $conn->prepare("DELETE FROM subject_grades WHERE stud_id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare subject_grades delete statement');
        }
        $stmt->bind_param("i", $stud_id);
        $stmt->execute();
        $stmt->close();

        // Delete from subject_attendance
        $stmt = $conn->prepare("DELETE FROM subject_attendance WHERE stud_id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare subject_attendance delete statement');
        }
        $stmt->bind_param("i", $stud_id);
        $stmt->execute();
        $stmt->close();

        // Delete from student_mentors
        $stmt = $conn->prepare("DELETE FROM student_mentors WHERE stud_id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare student_mentors delete statement');
        }
        $stmt->bind_param("i", $stud_id);
        $stmt->execute();
        $stmt->close();

        // Delete from academic_progress
        $stmt = $conn->prepare("DELETE FROM academic_progress WHERE stud_id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare academic_progress delete statement');
        }
        $stmt->bind_param("i", $stud_id);
        $stmt->execute();
        $stmt->close();

        // Delete from student_achievements
        $stmt = $conn->prepare("DELETE FROM student_achievements WHERE stud_id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare student_achievements delete statement');
        }
        $stmt->bind_param("i", $stud_id);
        $stmt->execute();
        $stmt->close();

        // Finally delete from student_details
        $stmt = $conn->prepare("DELETE FROM student_details WHERE stud_id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare student_details delete statement');
        }
        $stmt->bind_param("i", $stud_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            // Commit transaction
            $conn->commit();
            echo json_encode([
                'status' => 'success',
                'message' => 'Student and all related records deleted successfully'
            ]);
        } else {
            throw new Exception('Student not found');
        }
        $stmt->close();

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw new Exception('Delete operation failed: ' . $e->getMessage());
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 