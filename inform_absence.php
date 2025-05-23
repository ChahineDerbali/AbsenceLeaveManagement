<?php
session_start();

// Redirect to login if the user is not logged in or is not an employee
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'employee') {
    header("Location: index.php");
    exit();
}

include('db_connection.php');

// Fetch user quota details
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT total_quota, used_quota, remaining_quota
    FROM quotas
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$quota = $result->fetch_assoc();

if (!$quota) {
    die("Quota information not available.");
}

// Handle form submission for emergency absence request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $status = 'approuve'; // Emergency absence status

    // Validate input
    if (empty($start_date) || empty($end_date) || empty($description)) {
        $message = "All fields are required.";
        $message_type = "error";
    } else {
        $start_timestamp = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        $days_diff = ($end_timestamp - $start_timestamp) / (60 * 60 * 24) + 1;

        if ($days_diff <= 0) {
            $message = "End date must be after the start date.";
            $message_type = "error";
        } elseif ($days_diff > $quota['remaining_quota']) {
            $message = "Insufficient leave quota for this period. This is an emergency, so the request will not be validated.";
            $message_type = "error";
        } else {
            // Insert emergency absence request into the database
            $stmt = $conn->prepare("
                INSERT INTO conges (user_id, date_debut, date_fin, motif, status)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("issss", $user_id, $start_date, $end_date, $description, $status);

            if ($stmt->execute()) {
                // Update quota (only if there are sufficient days)
                if ($days_diff <= $quota['remaining_quota']) {
                    $stmt = $conn->prepare("
                        UPDATE quotas
                        SET used_quota = used_quota + ?, remaining_quota = remaining_quota - ?
                        WHERE user_id = ?
                    ");
                    $stmt->bind_param("iii", $days_diff, $days_diff, $user_id);
                    $stmt->execute();
                }

                $message = "Emergency leave request submitted successfully.";
                $message_type = "success";
            } else {
                $message = "Failed to submit emergency leave request.";
                $message_type = "error";
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
    <title>Request Emergency Leave</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #555;
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            margin: 5px 0 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        textarea {
            resize: none;
            height: 80px;
        }
        button {
            width: 100%;
            background-color: #007BFF;
            color: white;
            border: none;
            padding: 10px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .info {
            background-color: #e7f3fe;
            color: #31708f;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #bce8f1;
            border-radius: 5px;
        }
        .error, .success {
            text-align: center;
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
    </style>

</head>
<body>
<div class="container">
    <h1>Emergency Leave Request</h1>

    <div class="info">
        <p><strong>Total Quota:</strong> <?= $quota['total_quota'] ?> days</p>
        <p><strong>Used Quota:</strong> <?= $quota['used_quota'] ?> days</p>
        <p><strong>Remaining Quota:</strong> <?= $quota['remaining_quota'] ?> days</p>
    </div>

    <?php if (isset($message)): ?>
        <div class="<?= $message_type ?>">
            <p><?= $message ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="start_date">Start Date</label>
            <input type="date" id="start_date" name="start_date" required>
        </div>
        <div class="form-group">
            <label for="end_date">End Date</label>
            <input type="date" id="end_date" name="end_date" required>
        </div>
        <div class="form-group">
            <label for="description">Reason for Emergency Leave</label>
            <textarea id="description" name="description" placeholder="Provide a reason for your leave..." required></textarea>
        </div>
        <button type="submit">Submit Emergency Request</button>
    </form>
    <div style="text-align: center; margin-top: 20px;">
    <a href="employee_dashboard.php" style="text-decoration: none; font-size: 16px; color: #007bff;">
        <button style="background-color: #28a745; border: none; color: white; padding: 10px 20px; font-size: 16px; cursor: pointer; border-radius: 5px;">
            Back to Dashboard
        </button>
    </a>
</div>
</div>
</body>
</html>
