<?php
header('Content-Type: application/json');

$pdo = new PDO('mysql:host=localhost;dbname=gestion_absences_conges', 'root', '');

// Check if the request is for teams or absences
if (isset($_GET['fetch']) && $_GET['fetch'] === 'teams') {
    // Fetch teams
    $teamQuery = "SELECT id, nom FROM equipes";
    $teamStmt = $pdo->query($teamQuery);
    $teams = $teamStmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($teams);
    exit;
}

// Fetch absences
$equipe_id = isset($_GET['equipe_id']) ? $_GET['equipe_id'] : null;

// Base query to retrieve absences
$query = "
    SELECT 
        conges.id, 
        CONCAT(conges.motif, ' : ', users.name) AS title,  -- Replace 'Congé demandé' with motif
        conges.date_debut AS start, 
        conges.date_fin AS end, 
        conges.status, 
        equipes.nom AS equipe
    FROM conges
    JOIN users ON conges.user_id = users.id
    JOIN equipe_membres ON users.id = equipe_membres.user_id
    JOIN equipes ON equipe_membres.equipe_id = equipes.id
    WHERE conges.status IN ('approuve', 'en_attente')
";

if ($equipe_id) {
    $query .= " AND equipe_membres.equipe_id = :equipe_id";
}

$stmt = $pdo->prepare($query);

if ($equipe_id) {
    $stmt->bindParam(':equipe_id', $equipe_id, PDO::PARAM_INT);
}

$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($events);
?>
