<?php
require_once 'db_connection.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

// Données des réservations durant la dernière semaine
$sql = "SELECT 
            DATE(reservation_date) as date,
            COUNT(*) as count,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM reservations 
        WHERE reservation_date >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
        GROUP BY DATE(reservation_date)
        ORDER BY date";

$result = $conn->query($sql);

$chart_data = [
    'labels' => [],
    'datasets' => [
        'total' => [],
        'confirmed' => [],
        'pending' => [],
        'cancelled' => []
    ]
];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $chart_data['labels'][] = date('d/m', strtotime($row['date']));
        $chart_data['datasets']['total'][] = (int)$row['count'];
        $chart_data['datasets']['confirmed'][] = (int)$row['confirmed'];
        $chart_data['datasets']['pending'][] = (int)$row['pending'];
        $chart_data['datasets']['cancelled'][] = (int)$row['cancelled'];
    }
}

// Données des produits les plus vendus
$sql_products = "SELECT 
                    category,
                    COUNT(*) as sales
                 FROM products 
                 GROUP BY category
                 ORDER BY sales DESC";

$result_products = $conn->query($sql_products);

$products_data = [
    'labels' => [],
    'data' => []
];

if ($result_products->num_rows > 0) {
    while ($row = $result_products->fetch_assoc()) {
        $category_names = [
            'coffee' => 'Café',
            'tea' => 'Thé',
            'pastry' => 'Pâtisseries',
            'sandwich' => 'Sandwichs',
            'dessert' => 'Desserts'
        ];
        $products_data['labels'][] = $category_names[$row['category']];
        $products_data['data'][] = (int)$row['sales'];
    }
}

echo json_encode([
    'success' => true,
    'reservations' => $chart_data,
    'products' => $products_data
]);

$conn->close();
?>
