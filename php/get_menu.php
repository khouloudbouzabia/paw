<?php
require_once 'db_connection.php'; // connexion à la base

header('Content-Type: application/json');

$result = $conn->query("SELECT id, name, description, price, category, image, available FROM products ORDER BY id ASC");

if ($result) {
    $menuItems = [];
    while ($row = $result->fetch_assoc()) {
        $row['available'] = (bool)$row['available'];
        $menuItems[] = $row;
    }
    echo json_encode([
        'success' => true,
        'data' => $menuItems
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur SQL : ' . $conn->error
    ]);
}
?>
