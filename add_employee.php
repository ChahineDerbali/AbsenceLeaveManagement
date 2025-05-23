<?php
session_start();

// Check if the user is logged in and is a manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header("Location: login.php");
    exit();
}

include('db_connection.php');

// Initialize error message and success message
$error = '';
$success = '';

// Fetch teams for the dropdown
$teams = [];
$result = $conn->query("SELECT id, nom FROM equipes");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $teams[] = $row;
    }
}

// Process form submission when the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $total_quota = (int)trim($_POST['total_quota']);
    $team_id = (int)$_POST['team_id']; // Get the selected team ID
    $role = 'employee'; // All users added through this form are employees by default

    // Validate the input
    if (empty($name) || empty($email) || empty($password) || $total_quota <= 0 || $team_id <= 0) {
        $error = "Please fill in all fields and ensure valid values are provided.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Email already exists. Please choose another email.";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Start transaction
            $conn->begin_transaction();

            try {
                // Insert the new employee into the users table
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

                if ($stmt->execute()) {
                    // Get the ID of the newly created user
                    $user_id = $stmt->insert_id;

                    // Insert the leave quota for the employee
                    $stmt = $conn->prepare("INSERT INTO quotas (user_id, total_quota) VALUES (?, ?)");
                    $stmt->bind_param("ii", $user_id, $total_quota);

                    if ($stmt->execute()) {
                        // Assign the employee to the selected team
                        $stmt = $conn->prepare("INSERT INTO equipe_membres (equipe_id, user_id) VALUES (?, ?)");
                        $stmt->bind_param("ii", $team_id, $user_id);

                        if ($stmt->execute()) {
                            $conn->commit();
                            $success = "Employee added successfully and assigned to the team!";
                        } else {
                            throw new Exception("Error assigning employee to the team.");
                        }
                    } else {
                        throw new Exception("Error adding leave quota.");
                    }
                } else {
                    throw new Exception("Error adding employee.");
                }
            } catch (Exception $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Employee</title>
    <style>
        /* Add some basic styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .navbar {
            background-color: #333;
            padding: 10px;
            text-align: center;
        }
        .navbar a {
            color: #fff;
            padding: 14px 20px;
            text-decoration: none;
        }
        .navbar a:hover {
            background-color: #ddd;
        }
        .container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
        }
        input[type="text"], input[type="email"], input[type="password"], input[type="number"], select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #4caf50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .message {
            text-align: center;
            margin-top: 10px;
        }
        .error {
            color: red;
        }
        .success {
            color: green;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="manager_dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="container">
        <h2>Add Employee</h2>
        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="text" name="name" placeholder="Enter employee's name" required>
            <input type="email" name="email" placeholder="Enter employee's email" required>
            <input type="password" name="password" placeholder="Enter employee's password" required>
            <input type="number" name="total_quota" placeholder="Enter total leave quota" min="1" required>
            <select name="team_id" required>
                <option value="">Select a Team</option>
                <?php foreach ($teams as $team): ?>
                    <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['nom']); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Add Employee</button>
        </form>
    </div>
</body>
</html>
