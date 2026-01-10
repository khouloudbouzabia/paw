<?php
session_start();
require_once '../php/db_connection.php';

// Vérifier la connexion
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Veuillez vous connecter d\'abord']);
    exit;
}

// Vérification des données envoyées par POST
if (isset($_POST['name'], $_POST['email'], $_POST['phone'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $birth_date = isset($_POST['birth_date']) ? trim($_POST['birth_date']) : null;
    $address = isset($_POST['address']) ? trim($_POST['address']) : null;
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

    // Vérifier la validité de l'email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email invalide']);
        exit;
    }

    // Vérifier que le numéro de téléphone contient uniquement des chiffres
    if (!preg_match("/^[0-9]{10,15}$/", $phone)) {
        echo json_encode(['success' => false, 'message' => 'Numéro de téléphone invalide']);
        exit;
    }

    // Requête pour insérer les données dans la base de données
    $sql = "INSERT INTO customers (full_name, email, phone, birth_date, address, notes, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    // Préparer la requête
    if ($stmt = $conn->prepare($sql)) {
        // Définir les types des paramètres (s = chaîne de caractères, s = chaîne de caractères, s = chaîne de caractères, s = chaîne de caractères, s = chaîne de caractères, s = chaîne de caractères)
        if ($stmt->bind_param("ssssss", $name, $email, $phone, $birth_date, $address, $notes)) {
            // Exécuter la requête
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Client ajouté avec succès']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'exécution de la requête : ' . $stmt->error]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la liaison des paramètres : ' . $stmt->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur dans la préparation de la requête : ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Données incomplètes']);
}

// Fermer la connexion à la base de données
$conn->close();
?>
