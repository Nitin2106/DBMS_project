<?php
$host = 'localhost';      // Database host
$db   = 'students';       // Database name
$user = 'root';           // DB username
$pass = '';               // DB password (empty for XAMPP)
$charset = 'utf8mb4';

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$successMessage = '';
$errorMessage = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get required fields
    $fname = trim($_POST['fname'] ?? '');
    $lname = trim($_POST['lname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $cpassword = $_POST['cpassword'] ?? '';

    // Validate required fields
    if (empty($fname) || empty($lname) || empty($email) || empty($password) || empty($cpassword)) {
        $errorMessage = "Please fill in all required fields.";
    }
    // Validate password match
    else if ($password !== $cpassword) {
        $errorMessage = "Passwords do not match.";
    }
    // Validate email format
    else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Invalid email format.";
    }
    else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT user_id FROM register WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errorMessage = "Email is already registered.";
        } else {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Set default values for required fields
            $mobile = '0000000000';  // Default phone number
            $gender = 'Other';       // Default gender from ENUM
            $dob = date('Y-m-d');    // Current date as default

            // Insert into 'register' table
            $insertStmt = $conn->prepare("
                INSERT INTO register (
                    fname, lname, email, password, cpassword,
                    mobile, gender, dob, user_type
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'student')
            ");
            
            $insertStmt->bind_param(
                "ssssssss",
                $fname, $lname, $email, $hashedPassword, $hashedPassword,
                $mobile, $gender, $dob
            );

            if ($insertStmt->execute()) {
                $successMessage = "Registration successful! <a href='login.php'>Login here</a>";
            } else {
                $errorMessage = "Error during registration: " . $insertStmt->error;
            }

            $insertStmt->close();
        }
        $stmt->close();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Registration</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <div class="login-container">
        <h2>Student Registration</h2>

        <?php if ($errorMessage): ?>
            <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <?php if ($successMessage): ?>
            <div class="success-message"><?php echo $successMessage; ?></div>
        <?php else: ?>
            <form method="POST" action="register.php" id="registerForm">
                <div class="form-group">
                    <input type="text" name="fname" placeholder="First Name" required>
                </div>
                <div class="form-group">
                    <input type="text" name="lname" placeholder="Last Name" required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <div class="form-group">
                    <input type="password" name="cpassword" placeholder="Confirm Password" required>
                </div>
                <button type="submit" class="login-btn">Register</button>
            </form>
            <p class="register-link">Already have an account? <a href="login.php">Login here</a></p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>