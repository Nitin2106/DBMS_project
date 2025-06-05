<?php
header('Content-Type: application/json');
session_start();

require_once '../config.php';

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($_SESSION['mentor_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authorized']);
    exit;
}

try {
    // Validate required fields
    if (!isset($data['stud_id']) || !isset($data['subject_id']) || 
        !isset($data['total_classes']) || !isset($data['attended_classes'])) {
        throw new Exception("Missing required fields");
    }

    // Validate attendance numbers
    if ($data['attended_classes'] > $data['total_classes']) {
        throw new Exception("Attended classes cannot be more than total classes");
    }

    // Check if student exists and is assigned to this mentor
    $check_query = "SELECT sm.stud_id 
                    FROM student_mentors sm 
                    WHERE sm.stud_id = ? AND sm.mentor_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $data['stud_id'], $_SESSION['mentor_id']);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Student not found or not assigned to this mentor");
    }

    // Update or insert attendance record
    $upsert_query = "INSERT INTO subject_attendance 
                     (stud_id, subject_id, total_classes, attended_classes) 
                     VALUES (?, ?, ?, ?) 
                     ON DUPLICATE KEY UPDATE 
                     total_classes = VALUES(total_classes), 
                     attended_classes = VALUES(attended_classes)";

    $upsert_stmt = $conn->prepare($upsert_query);
    $upsert_stmt->bind_param("iiii", 
        $data['stud_id'], 
        $data['subject_id'], 
        $data['total_classes'], 
        $data['attended_classes']
    );

    if ($upsert_stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Attendance updated successfully']);
    } else {
        throw new Exception("Error updating attendance: " . $upsert_stmt->error);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($upsert_stmt)) $upsert_stmt->close();
    if (isset($conn)) $conn->close();
}
?> 