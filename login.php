<?php
session_start();

// Handle logout
if (isset($_GET['logout'])) {
    // Destroy all session data
    session_destroy();
    // Redirect to login page
    header("Location: login.php");
    exit();
}

$host = 'localhost';
$db   = 'students';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$loginError = "";
$currentLoginType = $_POST['login_type'] ?? 'student';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($currentLoginType === 'mentor') {
        // Mentor login using plain text password
        $stmt = $conn->prepare("SELECT mentor_id, mentor_name, mentor_password FROM mentors WHERE mentor_email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($mentor_id, $mentor_name, $storedPassword);
            $stmt->fetch();

            if ($password === $storedPassword) {
                $_SESSION['user_type'] = 'mentor';
                $_SESSION['mentor_id'] = $mentor_id;
                $_SESSION['mentor_name'] = $mentor_name;
                $_SESSION['mentor_email'] = $email;
                header("Location: mentor-dashboard.php");
                exit();
            } else {
                $loginError = "Incorrect password";
            }
        } else {
            $loginError = "Mentor email not found";
        }

        $stmt->close();
    } else {
        // Student and Admin login (both use hashed password)
        $stmt = $conn->prepare("SELECT user_id, password, user_type FROM register WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($user_id, $hashedPassword, $user_type);
            $stmt->fetch();

            if (password_verify($password, $hashedPassword)) {
                $_SESSION['user_type'] = $user_type;
                $_SESSION['user_id'] = $user_id;
                
                // Redirect based on user type
                if ($user_type === 'admin') {
                    header("Location: mentor-assignment.php");
                } else {
                    header("Location: student-dashboard.php");
                }
                exit();
            } else {
                $loginError = "Incorrect password";
            }
        } else {
            $loginError = "Email not registered";
        }

        $stmt->close();
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Management System - Login</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            background-color: #1a1a2e;
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #ffffff;
        }

        .login-type-selector {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .type-btn {
            padding: 10px 20px;
            border: 1px solid #2d2d42;
            background: #16213e;
            cursor: pointer;
            border-radius: 4px;
            font-size: 15px;
            font-weight: 500;
            color: #a0a0a0;
            transition: all 0.3s ease;
        }

        .type-btn:hover {
            background: #1f2b4d;
            border-color: #0d6efd;
            color: #ffffff;
        }

        .type-btn.active {
            background: #0d6efd;
            color: white;
            border-color: #0a58ca;
            font-weight: 600;
        }

        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background: #1f2937;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        .login-container h2 {
            color: #ffffff;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 25px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #e5e7eb;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #374151;
            background-color: #111827;
            border-radius: 6px;
            font-size: 15px;
            color: #ffffff;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus {
            border-color: #0d6efd;
            outline: none;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.2);
            background-color: #1f2937;
        }

        .form-group input::placeholder {
            color: #6b7280;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background: #0d6efd;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .login-btn:hover {
            background: #0a58ca;
        }

        .error-message {
            color: #fca5a5;
            background-color: rgba(220, 38, 38, 0.2);
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
            border: 1px solid rgba(220, 38, 38, 0.3);
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #9ca3af;
            font-size: 14px;
        }

        .register-link a {
            color: #0d6efd;
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover {
            color: #0a58ca;
            text-decoration: underline;
        }
    </style>
    <script>
        function setLoginType(type) {
            document.getElementById('login_type').value = type;
            document.getElementById('registerLink').style.display = type === 'student' ? 'block' : 'none';
            const buttons = document.querySelectorAll('.type-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            document.getElementById(type + 'Btn').classList.add('active');
        }
    </script>
</head>
<body onload="setLoginType('<?php echo $currentLoginType; ?>')">
    <div class="container">
        <div class="login-container">
            <h2>Welcome Back</h2>
            <div class="login-type-selector">
                <button type="button" id="studentBtn" class="type-btn" onclick="setLoginType('student')">Student</button>
                <button type="button" id="mentorBtn" class="type-btn" onclick="setLoginType('mentor')">Mentor</button>
            </div>
            <form method="POST" action="login.php">
                <input type="hidden" name="login_type" id="login_type" value="student">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                <?php if ($loginError): ?>
                    <div class="error-message"><?php echo htmlspecialchars($loginError); ?></div>
                <?php endif; ?>
                <button type="submit" class="login-btn">Sign In</button>
            </form>
            <p class="register-link" id="registerLink">New student? <a href="register.php">Create an account</a></p>
        </div>
    </div>
</body>
</html>