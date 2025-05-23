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

// Fetch all users (managers and employees) along with their team names
$sql = "
    SELECT users.id, users.name, users.email, users.role, equipes.nom AS team_name 
    FROM users
    LEFT JOIN equipe_membres ON users.id = equipe_membres.user_id
    LEFT JOIN equipes ON equipe_membres.equipe_id = equipes.id
";

$result = $conn->query($sql);

// Fetch all leave requests grouped by team and sorted by start date
$request_sql = "
    SELECT r.id, r.user_id, r.date_debut, r.date_fin, r.motif, r.status, q.used_quota AS used_quota , u.name AS user_name, e.nom AS team_name
    FROM conges r 
    JOIN users u ON r.user_id = u.id
    JOIN quotas q ON u.id = q.user_id
    JOIN equipe_membres em ON u.id = em.user_id
    JOIN equipes e ON em.equipe_id = e.id
    ORDER BY e.nom, r.date_debut
";
$request_result = $conn->query($request_sql);

// Fetch all teams and count members in each team
$teams_sql = "SELECT e.id AS equipe_id, e.nom AS equipe_name, COUNT(em.user_id) AS member_count
              FROM equipes e
              LEFT JOIN equipe_membres em ON e.id = em.equipe_id
              GROUP BY e.id, e.nom";
$teams_result = $conn->query($teams_sql);

function validé($id)
{
    $servername = "localhost";
    $username = "root"; // Nom d'utilisateur MySQL
    $password = ""; // Mot de passe MySQL
    $dbname = "gestion_absences_conges";
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connexion échouée: " . $conn->connect_error);
    }

    $sql = "UPDATE conges SET status = 'approuve' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<script>alert('validé avec succès.');</script>";
    } else {
        echo "<script>alert('Erreur lors de la mise à jour : " . $stmt->error . "');</script>";
    }

    $stmt->close();
    $conn->close();
}

if (isset($_POST['validé'])) {
    $id = $_POST['validé_id'];
    validé($id);
}

function rejeter($id)
{
    $servername = "localhost";
    $username = "root"; // Nom d'utilisateur MySQL
    $password = ""; // Mot de passe MySQL
    $dbname = "gestion_absences_conges";
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connexion échouée: " . $conn->connect_error);
    }

    $sql = "UPDATE conges SET status = 'rejete' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<script>alert('rejetection avec succès.');</script>";
    } else {
        echo "<script>alert('Erreur lors de la mise à jour : " . $stmt->error . "');</script>";
    }

    $stmt->close();
    $conn->close();
}

if (isset($_POST['rejeter'])) {
    $id = $_POST['rejeter_id'];
    rejeter($id);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            background-color: #f4f4f4;
        }

        .navbar {
            background-color: #333;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar h1 {
            margin: 0;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
        }

        .container {
            padding: 20px;
            margin: 10px;
            background-color: white;
        }
        .container2 {
            padding: 20px;
            margin: 10px 0;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .section {
            margin: 10px 0;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .section:last-child {
            margin-bottom: 0;
        }

        h2,
        h3 {
            color: #333;
        }

        .btn {
            background-color: #4facfe;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 10px 0;
        }

        .btn:hover {
            background-color: #00c6fb;
        }

        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
            
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 10px;
            text-align: center;
        }

        .logout {
            background-color: #ff4c4c;
        }

        .logout:hover {
            background-color: #ff1a1a;
        }
    </style>
</head>

<body>

    <div class="navbar">
        <h1>Manager Dashboard</h1>
        <div>
            <a href="logout.php" class="btn logout">Logout</a>
        </div>
    </div>

    <div class="container">
        <h2>Welcome, <?php echo $_SESSION['username']; ?>!</h2>

        <!-- Button to add an Equipe -->
        <a href="add_equipe.php"><button class="btn">Add Equipe</button></a>

        <!-- Button to add a Manager or Employee -->
        <a href="add_manager.php"><button class="btn">Add Manager</button></a>
        <a href="add_employee.php"><button class="btn">Add Employee</button></a>

        <div class="container2">
            <h3>Calendar</h3>
            <?php include('calendar.html'); ?>
        </div>

        <div class="section">
            <h3>Teams and Member Count</h3>
            <table>
                <thead>
                    <tr>
                        <th>Team ID</th>
                        <th>Team Name</th>
                        <th>Number of Members</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Display all teams and member counts
                    if ($teams_result->num_rows > 0) {
                        while ($row = $teams_result->fetch_assoc()) {
                            echo "<tr>
                                <td>" . $row['equipe_id'] . "</td>
                                <td>" . $row['equipe_name'] . "</td>
                                <td>" . $row['member_count'] . "</td>
                              </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3'>No teams found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h3>Leave Requests by Team</h3>

            <?php
            $leaveRequestsByTeam = [];
            if ($request_result->num_rows > 0) {
                while ($row = $request_result->fetch_assoc()) {
                    $leaveRequestsByTeam[$row['team_name']][] = $row;
                }

                foreach ($leaveRequestsByTeam as $teamName => $requests) {
                    echo "<h4>Team: " . $teamName . "</h4>";
                    echo "<table>
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Employee Name</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Motif</th>
                                <th>used_quota</th>
                                <th>Status</th>
                                <th>Actions</th>

                            </tr>
                        </thead>
                        <tbody>";

                    foreach ($requests as $row) {
                        echo "<tr>
                            <td>" . $row['id'] . "</td>
                            <td>" . $row['user_name'] . "</td>
                            <td>" . $row['date_debut'] . "</td>
                            <td>" . $row['date_fin'] . "</td>
                            <td>" . $row['motif'] . "</td>
                            <td>" . $row['used_quota'] . "</td>
                            <td>" . ucfirst($row['status']) . "</td>
                            <td>";

                        if ($row['status'] === 'en_attente') {
                            echo "<form method='POST' style='display:inline;'>
                                <input type='hidden' name='validé_id' value='{$row['id']}'>
                                <button type='submit' name='validé' class='block_user'>Approve</button>
                            </form>
                            <form method='POST' style='display:inline;'>
                                <input type='hidden' name='rejeter_id' value='{$row['id']}'>
                                <button type='submit' name='rejeter' class='delete_user'>Reject</button>
                            </form>";
                        } else {
                            echo "No actions available";
                        }

                        echo "</td></tr>";
                    }

                    echo "</tbody>
                    </table>";
                }
            } else {
                echo "<p>No leave requests found</p>";
            }
            ?>

        </div>

</body>

</html>

<?php
$conn->close();
?>
