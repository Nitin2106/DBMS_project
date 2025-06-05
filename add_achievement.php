<?php
header('Content-Type: application/json');
require_once '../config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['stud_id']) || !isset($data['achievement_title']) || !isset($data['achievement_date']) || 
    !isset($data['description']) || !isset($data['awarded_by'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO student_achievements (stud_id, achievement_title, achievement_date, description, awarded_by) 
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", 
        $data['stud_id'],
        $data['achievement_title'],
        $data['achievement_date'],
        $data['description'],
        $data['awarded_by']
    );

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?> 