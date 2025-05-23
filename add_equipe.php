<?php
session_start();

// Check if the user is logged in and is a manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header("Location: login.php"); // Redirect to login if not logged in or not a manager
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'gestion_absences_conges');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$message = "";
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_name = trim($_POST['team_name']);

    // Validate input
    if (empty($team_name)) {
        $message = "Team name cannot be empty.";
    } else {
        // Check if team name already exists
        $sql_check = "SELECT id FROM equipes WHERE nom = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("s", $team_name);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $message = "A team with this name already exists.";
        } else {
            // Insert the new team into the database
            $sql_insert = "INSERT INTO equipes (nom) VALUES (?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("s", $team_name);

            if ($stmt_insert->execute()) {
                $message = "Team added successfully.";
                $success = true;
            } else {
                $message = "Error adding team: " . $conn->error;
            }

            $stmt_insert->close();
        }

        $stmt_check->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Team</title>
    <style>
        body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; }
        .navbar { background-color: #333; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .navbar h1 { margin: 0; }
        .navbar a { color: white; text-decoration: none; margin-left: 15px; }
        .container { padding: 20px; margin: 20px auto; background: white; border-radius: 10px; max-width: 600px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); }
        .btn { background-color: #4facfe; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-top: 10px; }
        .btn:hover { background-color: #00c6fb; }
        .logout { background-color: #ff4c4c; }
        .logout:hover { background-color: #ff1a1a; }
        form { display: flex; flex-direction: column; gap: 10px; }
        input[type="text"] { padding: 10px; border: 1px solid #ddd; border-radius: 5px; width: 100%; box-sizing: border-box;}
        .message { margin-top: 20px; padding: 10px; border-radius: 5px; text-align: center; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>Add Team</h1>
        <a href="manager_dashboard.php" class="btn">Back to Dashboard</a>
    </div>

    <div class="container">
        <h2>Create a New Team</h2>
        <form method="POST" action="">
            <label for="team_name">Team Name</label>
            <input type="text" id="team_name" name="team_name" placeholder="Enter team name" required>
            <button type="submit" class="btn">Add Team</button>
        </form>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
