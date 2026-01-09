<?php
session_start();
require_once 'db_connection.php';
header('Content-Type: application/json; charset=utf-8');

// Vérification de la connexion et du type d'utilisateur
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérification que seul l'admin peut supprimer
/*if ($_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Permission refusée. Seul l\'administrateur peut supprimer des réservations']);
    exit;
}*/

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupération de l'ID de réservation
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de réservation invalide']);
    exit;
}

$reservation_id = intval($_POST['id']);

// Première étape : récupérer les données de la réservation pour journalisation (log)
/*$sql_select = "SELECT * FROM reservations WHERE id = ?";
$stmt_select = $conn->prepare($sql_select);

if ($stmt_select) {
    $stmt_select->bind_param("i", $reservation_id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    
    if ($result->num_rows > 0) {
        $reservation = $result->fetch_assoc();
        
        // Enregistrer la suppression dans la table des logs (si existante)
        $log_sql = "INSERT INTO logs (user_id, action, details, created_at) 
                    VALUES (?, 'delete_reservation', ?, NOW())";
        $log_stmt = $conn->prepare($log_sql);
        
        if ($log_stmt) {
            $details = "Suppression de la réservation #" . $reservation_id . 
                      " - Client: " . $reservation['customer_name'] . 
                      " - Date: " . $reservation['reservation_date'];
            
            $log_stmt->bind_param("is", $_SESSION['user_id'], $details);
            $log_stmt->execute();
            $log_stmt->close();
        }
    }
    
    $stmt_select->close();
}
*/

// Maintenant : suppression de la réservation
$sql_delete = "DELETE FROM reservations WHERE id = ?";
$stmt_delete = $conn->prepare($sql_delete);

if ($stmt_delete) {
    $stmt_delete->bind_param("i", $reservation_id);
    
    if ($stmt_delete->execute()) {
        if ($stmt_delete->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Réservation supprimée avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Aucune réservation trouvée avec cet ID']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression: ' . $stmt_delete->error]);
    }
    
    $stmt_delete->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur de préparation de la requête']);
}

$conn->close();
?>
