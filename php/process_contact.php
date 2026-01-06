<?php
require_once 'db_connection.php';
header('Content-Type: application/json; charset=utf-8');

// Paramètres de protection contre le spam (exemple : limite d'un message par IP toutes les 60 secondes)
$spam_time_limit = 60; // en secondes
$user_ip = $_SERVER['REMOTE_ADDR'];

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Méthode de requête non autorisée'
    ]);
    exit;
}

// Récupération et nettoyage des données
$name    = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
$email   = htmlspecialchars(trim($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8');
$subject = htmlspecialchars(trim($_POST['subject'] ?? ''), ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars(trim($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8');

// Vérification des champs
if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    echo json_encode([
        'success' => false,
        'message' => 'Tous les champs sont obligatoires'
    ]);
    exit;
}

// Vérification de l'email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Adresse e-mail invalide'
    ]);
    exit;
}

// Protection contre le spam : vérifier le dernier message de la même IP
$stmt_check = $conn->prepare("SELECT created_at FROM contact_messages WHERE ip_address = ? ORDER BY created_at DESC LIMIT 1");
$stmt_check->bind_param("s", $user_ip);
$stmt_check->execute();
$stmt_check->store_result();
$stmt_check->bind_result($last_time);

if ($stmt_check->num_rows > 0) {
    $stmt_check->fetch();
    $last_timestamp = strtotime($last_time);
    if ((time() - $last_timestamp) < $spam_time_limit) {
        echo json_encode([
            'success' => false,
            'message' => 'Veuillez attendre avant d\'envoyer un nouveau message'
        ]);
        exit;
    }
}
$stmt_check->close();

// Insertion des données dans la base de données
$sql = "INSERT INTO contact_messages (name, email, subject, message, ip_address)
        VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données'
    ]);
    exit;
}

$stmt->bind_param("sssss", $name, $email, $subject, $message, $user_ip);

if ($stmt->execute()) {

    // Envoi de l'email après l'enregistrement
    $to = "info@coffee-shop.com"; // Modifiez avec votre adresse
    $email_subject = "Nouveau message depuis le site : $subject";
    $email_body = "Nom : $name\nEmail : $email\nSujet : $subject\nMessage :\n$message\n\nEnvoyé le : " . date('Y-m-d H:i:s');
    $headers = "From: $email\r\nReply-To: $email\r\n";

    mail($to, $email_subject, $email_body, $headers);

    echo json_encode([
        'success' => true,
        'message' => 'Votre message a été envoyé avec succès, nous vous contacterons bientôt'
    ]);

} else {

    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de l\'enregistrement du message'
    ]);
}

$stmt->close();
$conn->close();
?>
