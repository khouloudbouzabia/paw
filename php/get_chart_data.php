<?php
session_start();
require_once 'db_connection.php';

// Vérification de la connexion
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$response = [
    'success' => true,
    'reservations' => [
        'labels' => [],
        'datasets' => ['total' => []]
    ],
    'products' => [
        'labels' => [],
        'data' => []
    ]
];

// Données des 7 derniers jours
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_name = date('D', strtotime($date));
    
    // Conversion des noms des jours en français
    $days_fr = [
        'Mon' => 'Lun',
        'Tue' => 'Mar',
        'Wed' => 'Mer',
        'Thu' => 'Jeu',
        'Fri' => 'Ven',
        'Sat' => 'Sam',
        'Sun' => 'Dim'
    ];
    
    $day_label = $days_fr[$day_name] ?? $day_name;
    
    // Requête pour le nombre de réservations ce jour
    $sql = "SELECT COUNT(*) as total FROM reservations WHERE DATE(reservation_date) = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $response['reservations']['labels'][] = $day_label;
        $response['reservations']['datasets']['total'][] = $row['total'] ?? 0;
        
        $stmt->close();
    }
}

// Données des produits par catégorie
$sql = "SELECT category, COUNT(*) as count FROM products GROUP BY category";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $response['products']['labels'][] = $row['category'];
        $response['products']['data'][] = $row['count'];
    }
}

// Si aucune donnée pour les produits, ajouter des données fictives
if (empty($response['products']['labels'])) {
    $response['products']['labels'] = ['Café', 'Thé', 'Pâtisserie', 'Sandwich'];
    $response['products']['data'] = [15, 10, 20, 8];
}

echo json_encode($response);
$conn->close();
?>
