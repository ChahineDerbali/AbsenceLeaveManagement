<?php
header('Content-Type: application/json');

$pdo = new PDO('mysql:host=localhost;dbname=gestion_absences_conges', 'root', '');

// Get the selected team (equipe_id) from the request
$equipe_id = isset($_GET['equipe_id']) ? $_GET['equipe_id'] : null;

// Base query to retrieve absences
$query = "
    SELECT 
        conges.id, 
        CONCAT('Congé demandé : ', users.name) AS title, 
        conges.date_debut AS start, 
        conges.date_fin AS end, 
        conges.status, 
        equipes.nom AS equipe
    FROM conges
    JOIN users ON conges.user_id = users.id
    JOIN equipe_membres ON users.id = equipe_membres.user_id
    JOIN equipes ON equipe_membres.equipe_id = equipes.id
";

// Filter by team if an `equipe_id` is provided
if ($equipe_id) {
    $query .= " WHERE equipe_membres.equipe_id = :equipe_id";
}

$stmt = $pdo->prepare($query);

if ($equipe_id) {
    $stmt->bindParam(':equipe_id', $equipe_id, PDO::PARAM_INT);
}

$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($events);
?>
