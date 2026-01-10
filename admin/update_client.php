<?php
session_start();
require_once '../php/db_connection.php';

// Vérification de la connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Vérification des données du formulaire
if (isset($_POST['id'], $_POST['full_name'], $_POST['email'], $_POST['phone'])) {
    $client_id = $_POST['id'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $total_visits = isset($_POST['total_visits']) ? $_POST['total_visits'] : 0;
    $loyalty_points = isset($_POST['loyalty_points']) ? $_POST['loyalty_points'] : 0;

    // Mise à jour des données du client
    $sql = "UPDATE customers SET full_name = ?, email = ?, phone = ?, total_visits = ?, loyalty_points = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssii", $full_name, $email, $phone, $total_visits, $loyalty_points, $client_id);

    if ($stmt->execute()) {
        header('Location: clients.php'); // Redirection vers la liste des clients
         $_SESSION['success_message'] = "Les modifications ont été enregistrées avec succès.";
        exit;
    } else {
        echo "Une erreur est survenue lors de la mise à jour des données du client : " . $stmt->error;
    }
} else {
    echo "Les données sont incomplètes.";
}

$stmt->close();
$conn->close();
?>
