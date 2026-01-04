<?php
require_once 'db_connection.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $time = mysqli_real_escape_string($conn, $_POST['time']);
    $people = (int)$_POST['people'];
    $requests = mysqli_real_escape_string($conn, $_POST['requests'] ?? '');

    // Vérifier les champs obligatoires
    if (empty($name) || empty($email) || empty($phone) || empty($date) || empty($time) || empty($people)) {
        echo json_encode([
            'success' => false,
            'message' => 'Tous les champs obligatoires doivent être remplis.'
        ]);
        exit;
    }

    // Vérification de l'e-mail
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Adresse e-mail invalide.'
        ]);
        exit;
    }

    // Vérification de la date de réservation
    $currentDate = date('Y-m-d');
    if ($date < $currentDate) {
        echo json_encode([
            'success' => false,
            'message' => 'Veuillez choisir une date dans le futur.'
        ]);
        exit;
    }

    // Insertion des données dans la base de données
    $sql = "INSERT INTO reservations (
                name, email, phone, reservation_date, reservation_time,
                number_people, special_requests, status
            ) VALUES (
                '$name', '$email', '$phone', '$date', '$time',
                $people, '$requests', 'pending'
            )";

    if ($conn->query($sql) === TRUE) {
        // Message de confirmation (e-mail)
        $subject = "Confirmation de réservation - Café Al Raha";
        $message = "Cher/Chère $name,\n\n";
        $message .= "Votre demande de réservation au Café Al Raha a bien été reçue.\n";
        $message .= "Détails de la réservation :\n";
        $message .= "Date : $date\n";
        $message .= "Heure : $time\n";
        $message .= "Nombre de personnes : $people\n";
        $message .= "Votre réservation sera confirmée dans les 24 heures.\n\n";
        $message .= "Merci d'avoir choisi le Café Al Raha !";
        
        // mail($email, $subject, $message);

        echo json_encode([
            'success' => true,
            'message' => 'Votre demande de réservation a été enregistrée avec succès. Nous vous contacterons prochainement pour confirmation.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Une erreur est survenue lors de l’enregistrement de la réservation : ' . $conn->error
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Méthode de requête non autorisée.'
    ]);
}

$conn->close();
?>
