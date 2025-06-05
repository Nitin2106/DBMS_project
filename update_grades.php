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
        !isset($data['assignment_score']) || !isset($data['midterm_score']) || 
        !isset($data['final_score'])) {
        throw new Exception("Missing required fields");
    }

    // Validate score ranges
    if ($data['assignment_score'] < 0 || $data['assignment_score'] > 100 ||
        $data['midterm_score'] < 0 || $data['midterm_score'] > 100 ||
        $data['final_score'] < 0 || $data['final_score'] > 100) {
        throw new Exception("Scores must be between 0 and 100");
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

    // Calculate total score and grade letter
    $total_score = ($data['assignment_score'] * 0.3) + ($data['midterm_score'] * 0.3) + ($data['final_score'] * 0.4);
    
    // Determine grade letter
    $grade_letter = 'F';
    if ($total_score >= 90) $grade_letter = 'A';
    else if ($total_score >= 80) $grade_letter = 'B';
    else if ($total_score >= 70) $grade_letter = 'C';
    else if ($total_score >= 60) $grade_letter = 'D';

    // Update or insert grade record
    $upsert_query = "INSERT INTO subject_grades 
                     (stud_id, subject_id, assignment_score, midterm_score, final_score, total_score, grade_letter) 
                     VALUES (?, ?, ?, ?, ?, ?, ?) 
                     ON DUPLICATE KEY UPDATE 
                     assignment_score = VALUES(assignment_score),
                     midterm_score = VALUES(midterm_score),
                     final_score = VALUES(final_score),
                     total_score = VALUES(total_score),
                     grade_letter = VALUES(grade_letter)";

    $upsert_stmt = $conn->prepare($upsert_query);
    $upsert_stmt->bind_param("iidddds", 
        $data['stud_id'], 
        $data['subject_id'],
        $data['assignment_score'],
        $data['midterm_score'],
        $data['final_score'],
        $total_score,
        $grade_letter
    );

    if ($upsert_stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Grades updated successfully',
            'data' => [
                'total_score' => round($total_score, 2),
                'grade_letter' => $grade_letter
            ]
        ]);
    } else {
        throw new Exception("Error updating grades: " . $upsert_stmt->error);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($upsert_stmt)) $upsert_stmt->close();
    if (isset($conn)) $conn->close();
}
?> 