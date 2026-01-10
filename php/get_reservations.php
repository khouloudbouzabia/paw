<?php
// php/get_reservations.php
require_once 'db_connection.php';

header('Content-Type: application/json; charset=utf-8');

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

// Paramètres de filtrage
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$date = isset($_GET['date']) ? $_GET['date'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Construction de la requête SQL
$sql = "SELECT * FROM reservations WHERE 1=1";

if ($status !== 'all') {
    $sql .= " AND status = '$status'";
}

if ($date) {
    $sql .= " AND reservation_date = '$date'";
}

if ($search) {
    $search = mysqli_real_escape_string($conn, $search);
    $sql .= " AND (customer_name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%')";
}

$sql .= " ORDER BY created_at DESC";

$result = $conn->query($sql);

$reservations = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Formatage des données pour l'affichage
        $row['formatted_date'] = date('Y-m-d', strtotime($row['reservation_date']));
        $row['formatted_time'] = date('h:i A', strtotime($row['reservation_time']));
        $row['created_at_formatted'] = date('Y-m-d h:i A', strtotime($row['created_at']));
        
        // Texte du statut en arabe
        $status_text = [
            'pending' => 'En attente',
            'confirmed' => 'Confirmé',
            'cancelled' => 'Annulé'
        ];
        $row['status_text'] = $status_text[$row['status']];
        
        $reservations[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'reservations' => $reservations,
    'total' => count($reservations)
]);

$conn->close();
?>
