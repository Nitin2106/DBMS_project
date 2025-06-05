<?php
header('Content-Type: application/json');

// DB connection
$conn = new mysqli("localhost", "username", "password", "database_name");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "Database connection failed"]);
    exit;
}

$action = $_POST['action'] ?? '';
$studentId = intval($_POST['studentId'] ?? 0);

if ($action === 'attendance') {
    $totalClasses = intval($_POST['totalClasses']);
    $attendedClasses = intval($_POST['attendedClasses']);

    $stmt = $conn->prepare("UPDATE attendance SET totalClasses = ?, attendedClasses = ? WHERE studentId = ?");
    $stmt->bind_param("iii", $totalClasses, $attendedClasses, $studentId);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(["success" => false, "error" => "Invalid action"]);
}

$conn->close();
?>
