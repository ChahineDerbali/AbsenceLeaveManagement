<?php
session_start();

// Ensure the user is logged in and has the manager role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'gestion_absences_conges');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the required parameters are present
if (isset($_GET['id']) && isset($_GET['action'])) {
    $request_id = intval($_GET['id']);
    $action = $_GET['action'];

    // Validate the action
    if ($action === 'approuve' || $action === 'rejete') { // Fixed the typo here
        // Map action to the correct status
        $status = ($action === 'approuve') ? 'approuve' : 'rejete';
    
        // Update the leave request status
        $stmt = $conn->prepare("UPDATE conges SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $request_id);
    
        if ($stmt->execute()) {
            $_SESSION['message'] = "Leave request has been $status successfully.";
        } else {
            $_SESSION['message'] = "Failed to update the leave request.";
        }
    
        $stmt->close();
    } else {
        $_SESSION['message'] = "Invalid action.";
    }
} else {
    $_SESSION['message'] = "Invalid request parameters.";
}

// Redirect back to the dashboard
header("Location: manager_dashboard.php");
$conn->close();
exit();
?>
