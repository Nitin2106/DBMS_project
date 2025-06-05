<?php
header('Content-Type: application/json');
require_once '../config.php';

session_start();
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['stud_id']) || !isset($data['note_type']) || !isset($data['note_content']) || !isset($_SESSION['mentor_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO mentor_notes (stud_id, mentor_id, note_type, note_content) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", 
        $data['stud_id'],
        $_SESSION['mentor_id'],
        $data['note_type'],
        $data['note_content']
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