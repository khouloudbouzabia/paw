<?php
session_start();
require_once 'db_connection.php';

// Vérification de la connexion
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérification de l'existence de l'ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de réservation invalide']);
    exit;
}

$reservation_id = intval($_GET['id']);

// Utilisation de Prepared Statement
$sql = "SELECT * FROM reservations WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $reservation = $result->fetch_assoc();
        
        // Conversion des dates au format lisible
        $reservation['reservation_time'] = date('H:i', strtotime($reservation['reservation_time']));
        
        echo json_encode([
            'success' => true,
            'reservation' => $reservation
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Réservation non trouvée']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur de préparation de la requête']);
}

$conn->close();
?>
