<?php
session_start();
require_once '../php/db_connection.php';

// Vérifier si l'utilisateur est connecté et dispose des autorisations nécessaires
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    echo json_encode(['success' => false, 'message' => 'Accès interdit.']);
    exit;
}

if (isset($_POST['reservation_id'])) {
    $reservation_id = $_POST['reservation_id'];
    $staff_id = $_SESSION['user_id']; // Identifiant de l'employé effectuant la procédure d'« enregistrement »

   // Vérifier si la réservation existe dans la base de données
    $check_sql = "SELECT * FROM reservations WHERE id = ? AND status = 'confirmed'";
    $stmt = $conn->prepare($check_sql);

    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Erreur de préparation de la requête SQL.']);
        exit;
    }

    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();
    $stmt->close();

    //Si la réservation est introuvable ou si la réservation n'est pas « confirmée »
    if (!$reservation) {
        echo json_encode(['success' => false, 'message' => 'Aucune réservation trouvée ou l\'état est incorrect.']);
        exit;
    }

    // Mettre à jour le statut de la réservation à « terminée » ou « enregistrée »
    $update_sql = "UPDATE reservations 
                   SET status = 'completed', 
                       confirmed_by = ?, 
                       created_at = NOW() 
                   WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);

    if ($update_stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Erreur de préparation de la requête SQL.']);
        exit;
    }

    $update_stmt->bind_param("ii", $staff_id, $reservation_id);
    $update_stmt->execute();

    if ($update_stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Check-in enregistré avec succès.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement du check-in.']);
    }

    $update_stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Aucun ID de réservation fourni.']);
}

$conn->close();
?>
