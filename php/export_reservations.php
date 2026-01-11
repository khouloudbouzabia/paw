<?php
session_start();
require_once 'db_connection.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// جلب عوامل التصفية
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';

// بناء الاستعلام
$sql = "SELECT * FROM reservations WHERE 1=1";
$params = [];
$types = "";

if ($status_filter !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($date_filter) {
    $sql .= " AND reservation_date = ?";
    $params[] = $date_filter;
    $types .= "s";
}

if ($search_filter) {
    $sql .= " AND (customer_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_term = "%{$search_filter}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

$sql .= " ORDER BY reservation_date DESC, reservation_time DESC";

// تنفيذ الاستعلام
$stmt = $conn->prepare($sql);

if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// تعيين رؤوس التصدير
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="reservations_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// بداية ملف Excel
echo "<table border='1'>";
echo "<tr>";
echo "<th>N° Réservation</th>";
echo "<th>Nom du client</th>";
echo "<th>Email</th>";
echo "<th>Téléphone</th>";
echo "<th>Date</th>";
echo "<th>Heure</th>";
echo "<th>Nombre de personnes</th>";
echo "<th>Statut</th>";
echo "<th>Demandes spéciales</th>";
echo "<th>Date de création</th>";
echo "</tr>";

while ($row = $result->fetch_assoc()) {
    // تحويل الحالة إلى نص
    $status_text = [
        'pending' => 'En attente',
        'confirmed' => 'Confirmée',
        'cancelled' => 'Annulée'
    ];
    
    $status = $status_text[$row['status']] ?? $row['status'];
    
    echo "<tr>";
    echo "<td>#" . str_pad($row['id'], 5, '0', STR_PAD_LEFT) . "</td>";
    echo "<td>" . htmlspecialchars($row['customer_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
    echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
    echo "<td>" . $row['reservation_date'] . "</td>";
    echo "<td>" . date('H:i', strtotime($row['reservation_time'])) . "</td>";
    echo "<td>" . $row['number_of_people'] . "</td>";
    echo "<td>" . $status . "</td>";
    echo "<td>" . htmlspecialchars($row['special_requests'] ?? '') . "</td>";
    echo "<td>" . $row['created_at'] . "</td>";
    echo "</tr>";
}

echo "</table>";

if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>