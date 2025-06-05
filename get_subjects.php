<?php
header('Content-Type: application/json');
session_start();

require_once '../config.php';

if (!isset($_SESSION['mentor_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authorized']);
    exit;
}

try {
    $stud_id = isset($_GET['stud_id']) ? (int)$_GET['stud_id'] : 0;
    
    if (!$stud_id) {
        throw new Exception("Invalid student ID");
    }

    // First check if student is assigned to this mentor
    $check_query = "SELECT sd.course_id 
                   FROM student_mentors sm 
                   JOIN student_details sd ON sm.stud_id = sd.stud_id
                   WHERE sm.stud_id = ? AND sm.mentor_id = ?";
    
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $stud_id, $_SESSION['mentor_id']);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $student = $result->fetch_assoc();

    if (!$student) {
        throw new Exception("Student not found or not assigned to this mentor");
    }

    // Get subjects for the student's course
    $subjects_query = "SELECT subject_id, subject_name 
                      FROM subjects 
                      WHERE course_id = ? 
                      ORDER BY subject_name";
    
    $subjects_stmt = $conn->prepare($subjects_query);
    $subjects_stmt->bind_param("i", $student['course_id']);
    $subjects_stmt->execute();
    $subjects_result = $subjects_stmt->get_result();
    
    $subjects = [];
    while ($subject = $subjects_result->fetch_assoc()) {
        $subjects[] = $subject;
    }

    echo json_encode([
        'status' => 'success',
        'subjects' => $subjects
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($subjects_stmt)) $subjects_stmt->close();
    if (isset($conn)) $conn->close();
}
?> 