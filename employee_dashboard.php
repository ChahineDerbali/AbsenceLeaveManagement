<?php
session_start();

// Check if the user is logged in and is an employee
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'employee') {
    header("Location: index.php");
    exit();
}

include('db_connection.php');

// Get the logged-in user's details along with their quota info
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT u.name, u.email, u.role, q.total_quota, q.used_quota, q.remaining_quota
    FROM users u
    LEFT JOIN quotas q ON u.id = q.user_id
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    // If no user is found (shouldn't happen)
    header("Location: logout.php");
    exit();
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_request_id'])) {
    $delete_request_id = $_POST['delete_request_id'];

    // Check if the request exists and belongs to the user
    $stmt = $conn->prepare("
        SELECT id, date_debut, date_fin, status 
        FROM conges 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $delete_request_id, $user_id);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();

    if ($request && $request['status'] == 'en_attente') {
        // Calculate the number of days in the request
        $days = (new DateTime($request['date_fin']))->diff(new DateTime($request['date_debut']))->days + 1;

        // Delete the request and update the quota
        $conn->begin_transaction();
        try {
            $delete_stmt = $conn->prepare("DELETE FROM conges WHERE id = ?");
            $delete_stmt->bind_param("i", $delete_request_id);
            $delete_stmt->execute();

            $update_quota_stmt = $conn->prepare("UPDATE quotas SET used_quota = used_quota - ? WHERE user_id = ?");
            $update_quota_stmt->bind_param("ii", $days, $user_id);
            $update_quota_stmt->execute();

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            die("Error deleting request: " . $e->getMessage());
        }
    }
}

// Fetch all requests for the user
$stmt = $conn->prepare("
    SELECT id, date_debut, date_fin, motif, status, created_at 
    FROM conges 
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$requests = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard</title>
    <style>
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
            text-decoration: none;
            margin: 0 15px;
        }
        .navbar a:hover {
            background-color: #ddd;
            color: black;
        }
        .quota-ball {
            display: inline-block;
            width: 50px;
            height: 50px;
            background-color: #00c6fb;
            border-radius: 50%;
            color: white;
            font-size: 1.5rem;
            line-height: 50px;
            margin-right: 20px;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .user-info {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
            
        }
        .user-info p {
            margin: 10px 0;
        }
        .action-button {
            display: block;
            width: 100%;
            padding: 15px;
            background-color: #4facfe;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.2rem;
            margin: 15px 0;
            text-decoration: none;
            text-align: center;
            box-sizing: border-box;

        }
        .action-button:hover {
            background-color: #00c6fb;
        }
        .logout-btn {
            margin-top: 20px;
            display: inline-block;
            background-color: #e74c3c;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
        }
        .logout-btn:hover {
            background-color: #c0392b;
        }
        .requests-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .requests-table th, .requests-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .requests-table th {
            background-color: #f4f4f4;
        }
        .delete-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        .delete-btn:hover {
            background-color: #c0392b;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="quota-ball"><?php echo $user['remaining_quota']; ?></div>
        <a href="index.php" class="logout-btn">Logout</a>
    </div>

    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>

        <div class="user-info">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Role:</strong> <?php echo ucfirst($user['role']); ?></p>
            <p><strong>Total Quotas:</strong> <?php echo $user['total_quota']; ?> days</p>
            <p><strong>Used Quotas:</strong> <?php echo $user['used_quota']; ?> days</p>
            <p><strong>Remaining Quotas:</strong> <?php echo $user['remaining_quota']; ?> days</p>
        </div>

        <a href="request_leave.php" class="action-button">Request a Leave</a>
        <a href="inform_absence.php" class="action-button">Inform About an Absence</a>

        <h3>Your Leave Requests</h3>
        <table class="requests-table">
            <tr>
                <th>Date Start</th>
                <th>Date End</th>
                <th>Motif</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            <?php while ($request = $requests->fetch_assoc()) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($request['date_debut']); ?></td>
                    <td><?php echo htmlspecialchars($request['date_fin']); ?></td>
                    <td><?php echo htmlspecialchars($request['motif']); ?></td>
                    <td><?php echo ucfirst($request['status']); ?></td>
                    <td>
                        <?php if ($request['status'] == 'en_attente') : ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="delete_request_id" value="<?php echo $request['id']; ?>">
                                <button type="submit" class="delete-btn">Delete</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>
