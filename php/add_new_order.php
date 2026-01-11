<?php
session_start();
require_once '../php/db_connection.php';

// Vérifier si les données ont été envoyées
if (isset($_POST['customer_name'], $_POST['product_id'], $_POST['quantity'])) {
    $customer_name = $_POST['customer_name'];
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];

    // Ajouter la commande à la base de données
    $stmt = $conn->prepare("INSERT INTO orders (customer_name, product_id, quantity, order_status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
    $stmt->bind_param("sii", $customer_name, $product_id, $quantity);
    
    if ($stmt->execute()) {
        // Commande ajoutée avec succès
        echo json_encode(['success' => true]);
    } else {
        // Échec de l'ajout de la commande
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout de la commande']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
}

$conn->close();
?>
