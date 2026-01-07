<?php
// Activer l'affichage des erreurs pour le développement
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connexion à la base de données
require_once 'db_connection.php';

// Type de réponse : JSON
header('Content-Type: application/json; charset=utf-8');

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Méthode de requête non autorisée'
    ]);
    exit;
}

// Nettoyage des données reçues depuis le formulaire
$name    = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
$email   = htmlspecialchars(trim($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8');
$subject = htmlspecialchars(trim($_POST['subject'] ?? ''), ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars(trim($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8');

// Vérification que tous les champs sont remplis
if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    echo json_encode([
        'success' => false,
        'message' => 'Tous les champs sont obligatoires'
    ]);
    exit;
}

// Vérification de la validité de l'email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Adresse e-mail invalide'
    ]);
    exit;
}

// Insertion des données dans la base de données
$sql = "INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données'
    ]);
    exit;
}

$stmt->bind_param("ssss", $name, $email, $subject, $message);

if ($stmt->execute()) {
    //envoyer le JSON de succès
    $response = [
        'success' => true,
        'message' => 'Votre message a été envoyé avec succès, nous vous contacterons bientôt'
    ];
    echo json_encode($response);

    // Puis tenter d'envoyer l'email, sans affecter le JSON
    try {
        $to = "info@coffee-shop.com";
        $email_subject = "Nouveau message depuis le site : $subject";
        $email_body = "Nom : $name\nEmail : $email\nSujet : $subject\nMessage :\n$message\n\nEnvoyé le : " . date('Y-m-d H:i:s');
        $headers = "From: $email\r\nReply-To: $email\r\n";

        @mail($to, $email_subject, $email_body, $headers); // @ pour éviter les warnings PHP
    } catch (Exception $e) {
        // Si une erreur survient avec mail(), elle n'affecte pas le formulaire
        error_log("Erreur mail: " . $e->getMessage());
    }

} else {
    // En cas d'échec de l'insertion dans la base de données
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de l\'enregistrement du message'
    ]);
}

// Fermeture de la requête et de la connexion
$stmt->close();
$conn->close();
exit;
?>
