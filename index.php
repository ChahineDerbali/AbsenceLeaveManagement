<?php
session_start();

// Database connection
$conn = new mysqli('localhost', 'root', '', 'gestion_absences_conges');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize error message
$error = '';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validate input
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Prepare SQL query to fetch user by email
        $sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // If user is found, verify the password
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role']; // Store the user role

                // Redirect based on role (Manager or Employee)
                if ($user['role'] === 'manager') {
                    header("Location: manager_dashboard.php");
                } else {
                    header("Location: employee_dashboard.php");
                }
                exit();
            } else {
                $error = "Wrong password";
            }
        } else {
            $error = "No user found with that email.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            overflow: hidden;
        }

        .container {
            background: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        h1 {
            margin-bottom: 20px;
            font-size: 1.8rem;
            color: #333;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            box-sizing: border-box;
        }

        button {
            margin: 5px 0 ;
            background: #4facfe;
            border: none;
            padding: 12px 20px;
            font-size: 1rem;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background: #00c6fb;
        }

        .error {
            color: red;
            font-size: 0.9rem;
        }

        .animated-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            animation: bgAnimation 6s infinite linear;
        }

        @keyframes bgAnimation {
            0% {
                background-position: 0 0;
            }

            100% {
                background-position: 200% 200%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Login</h1>
        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="email" name="email" placeholder="Enter your email" required>
            <input type="password" name="password" placeholder="Enter your password" required>
            <button type="submit">Login</button>
        </form>
        <form action="create_account.php">
            <button type="submit">Create an Account</button>
        </form>
    </div>
    <div class="animated-bg"></div>
    <script>
        // Optional JavaScript for interaction (e.g., input field focus animation)
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', () => {
                input.style.borderColor = '#00c6fb';
            });
            input.addEventListener('blur', () => {
                input.style.borderColor = '#ddd';
            });
        });
    </script>
</body>

</html>