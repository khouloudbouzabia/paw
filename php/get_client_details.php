<?php
session_start();
require_once 'db_connection.php';

// Vérifier la connexion
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// S'assurer que la sortie est uniquement au format JSON
header('Content-Type: application/json; charset=utf-8');

try {
    // Vérification de l'identifiant
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('ID client invalide');
    }

    $client_id = intval($_GET['id']);

    // Récupérer les détails du client
    $sql = "SELECT 
                c.id,
                c.full_name AS name,
                c.email,
                c.phone,
                c.total_visits,
                c.loyalty_points,
                c.created_at,
                COUNT(r.id) as total_reservations,
                SUM(r.number_of_people) as total_people,
                MAX(r.reservation_date) as last_reservation,
                MIN(r.reservation_date) as first_reservation
            FROM customers c
            LEFT JOIN reservations r ON c.id = r.customer_id
            WHERE c.id = ?
            GROUP BY c.id";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare client query: ' . $conn->error);
    }
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Client non trouvé');
    }

    $client = $result->fetch_assoc();

    // Récupérer les 5 dernières réservations du client
    $reservations_sql = "SELECT * FROM reservations 
                         WHERE customer_id = ? 
                         ORDER BY reservation_date DESC 
                         LIMIT 5";

    $res_stmt = $conn->prepare($reservations_sql);
    if (!$res_stmt) {
        throw new Exception('Failed to prepare reservations query: ' . $conn->error);
    }
    $res_stmt->bind_param("i", $client_id);
    $res_stmt->execute();
    $res_result = $res_stmt->get_result();

    $reservations = [];
    while ($row = $res_result->fetch_assoc()) {
        // Décodez le nom à l'aide de utf8_decode
        $row['customer_name'] = utf8_decode($row['customer_name']);
        $reservations[] = $row;
    }

    // Récupérer le total des dépenses
    $spending_sql = "SELECT COALESCE(SUM(o.total_amount), 0) as total_spent
                     FROM orders o
                     WHERE o.customer_id = ? 
                     AND o.order_status = 'completed'";

    $sp_stmt = $conn->prepare($spending_sql);
    if (!$sp_stmt) {
        throw new Exception('Failed to prepare spending query: ' . $conn->error);
    }
    $sp_stmt->bind_param("i", $client_id);
    $sp_stmt->execute();
    $sp_result = $sp_stmt->get_result();
    $spending = $sp_result->fetch_assoc();

    // Calculer la valeur moyenne
    $avg_value = ($client['total_reservations'] > 0 && $spending['total_spent'] > 0) 
                 ? $spending['total_spent'] / $client['total_reservations'] 
                 : 0;

    echo json_encode([
        'success' => true,
        'client' => [
            'id' => $client['id'],
            // Décodez le nom à l'aide de utf8_decode
            'name' => utf8_decode($client['name']),
            'email' => $client['email'],
            'phone' => $client['phone'],
            'total_visits' => $client['total_visits'] ?? 0,
            'loyalty_points' => $client['loyalty_points'] ?? 0,
            'created_at' => date('d/m/Y H:i', strtotime($client['created_at'])),
            'total_reservations' => $client['total_reservations'] ?? 0,
            'total_people' => $client['total_people'] ?? 0,
            'last_reservation' => $client['last_reservation'] ? date('d/m/Y', strtotime($client['last_reservation'])) : 'Jamais',
            'first_reservation' => $client['first_reservation'] ? date('d/m/Y', strtotime($client['first_reservation'])) : 'Jamais',
            'total_spent' => number_format($spending['total_spent'], 2),
            'avg_value' => number_format($avg_value, 2),
            'customer_type' => $client['total_reservations'] >= 10 ? 'VIP' : 
                              ($client['total_reservations'] >= 3 ? 'Régulier' : 'Nouveau')
        ],
        'reservations' => $reservations
    ]);

    $stmt->close();
    $res_stmt->close();
    $sp_stmt->close();

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
