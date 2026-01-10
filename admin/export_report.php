<?php
session_start();
require_once '../php/db_connection.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

// جلب عوامل التصفية
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$report_type = isset($_GET['type']) ? $_GET['type'] : 'daily';
$export_type = isset($_GET['export']) ? $_GET['export'] : 'excel';

// بناء الاستعلام حسب نوع التقرير
switch ($report_type) {
    case 'reservations':
        $sql = "SELECT 
                    r.id,
                    r.customer_name,
                    r.email,
                    r.phone,
                    r.reservation_date,
                    r.reservation_time,
                    r.number_of_people,
                    r.status,
                    r.special_requests,
                    r.created_at,
                    u.full_name as assigned_staff,
                    COALESCE(SUM(o.total_amount), 0) as total_spent
                FROM reservations r
                LEFT JOIN users u ON r.assigned_to = u.id
                LEFT JOIN orders o ON r.id = o.reservation_id
                WHERE r.reservation_date BETWEEN ? AND ?
                GROUP BY r.id
                ORDER BY r.reservation_date DESC, r.reservation_time DESC";
        $filename = "reservations_" . date('Y-m-d') . ".xls";
        $headers = [
            'N° Réservation', 'Client', 'Email', 'Téléphone', 'Date', 
            'Heure', 'Personnes', 'Statut', 'Demandes spéciales', 
            'Date création', 'Staff assigné', 'Montant dépensé'
        ];
        break;
        
    case 'clients':
        $sql = "SELECT 
                    c.id,
                    c.name,
                    c.email,
                    c.phone,
                    c.address,
                    c.birth_date,
                    c.created_at,
                    COUNT(r.id) as total_reservations,
                    SUM(r.number_of_people) as total_people,
                    COALESCE(SUM(o.total_amount), 0) as total_spent,
                    MAX(r.reservation_date) as last_visit
                FROM customers c
                LEFT JOIN reservations r ON c.id = r.customer_id
                LEFT JOIN orders o ON r.id = o.reservation_id
                WHERE c.created_at BETWEEN ? AND ?
                GROUP BY c.id
                ORDER BY c.created_at DESC";
        $filename = "clients_" . date('Y-m-d') . ".xls";
        $headers = [
            'ID', 'Nom', 'Email', 'Téléphone', 'Adresse', 
            'Date naissance', 'Date inscription', 'Réservations',
            'Personnes totales', 'Montant total', 'Dernière visite'
        ];
        break;
        
    case 'products':
        $sql = "SELECT 
            p.id,
            p.name,
            p.description,
            p.unit_price, 
            p.category,
            p.available,
            p.created_at,
            COUNT(oi.id) as sales_count,
            SUM(oi.quantity) as total_quantity,
            SUM(oi.unit_price * oi.quantity) as total_revenue 
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id
        WHERE o.order_date BETWEEN ? AND ? OR o.id IS NULL
        GROUP BY p.id
        ORDER BY total_revenue DESC";

        $filename = "products_" . date('Y-m-d') . ".xls";
        $headers = [
            'ID', 'Nom', 'Description', 'Prix', 'Catégorie', 
            'Disponible', 'Date ajout', 'Ventes', 'Quantité', 'Revenu total'
        ];
        break;
        
    default:
        $sql = "SELECT
            r.id,
            r.customer_name,
            r.reservation_date,
            r.reservation_time,
            r.number_of_people,
            r.status,
            p.name as product_name,
            p.category,
            oi.quantity,
            oi.unit_price, 
            (oi.quantity * oi.unit_price) as total 
        FROM reservations r
        LEFT JOIN orders o ON r.id = o.reservation_id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE r.reservation_date BETWEEN ? AND ?
        ORDER BY r.reservation_date DESC";

        $filename = "rapport_complet_" . date('Y-m-d') . ".xls";
        $headers = [
            'Réservation', 'Client', 'Date', 'Heure', 'Personnes', 
            'Statut', 'Produit', 'Catégorie', 'Quantité', 'Prix unitaire', 'Total'
        ];
        break;
}

// تنفيذ الاستعلام
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    // إذا فشل التحضير، قم بطباعة رسالة الخطأ
    die('Error in SQL query: ' . $conn->error);
}

$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// تعيين رؤوس التصدير
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// بداية ملف Excel
echo "<table border='1'>";
echo "<tr>";

// رؤوس الأعمدة
foreach ($headers as $header) {
    echo "<th style='background-color: #4CAF50; color: white; padding: 10px;'>" . $header . "</th>";
}
echo "</tr>";

// البيانات
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    
    foreach ($row as $key => $value) {
        // تنسيق خاص لأنواع معينة من البيانات
        if (strpos($key, 'date') !== false || strpos($key, '_at') !== false) {
            $value = !empty($value) ? date('d/m/Y', strtotime($value)) : '';
        } elseif (strpos($key, 'time') !== false) {
            $value = !empty($value) ? date('H:i', strtotime($value)) : '';
        } elseif (strpos($key, 'price') !== false || strpos($key, 'amount') !== false || strpos($key, 'revenue') !== false || strpos($key, 'spent') !== false) {
            $value = !empty($value) ? number_format($value, 2) . ' DA' : '0.00 DA';
        } elseif ($key == 'available' || $key == 'is_active') {
            $value = $value ? 'Oui' : 'Non';
        } elseif ($key == 'status') {
            $status_map = [
                'pending' => 'En attente',
                'confirmed' => 'Confirmée',
                'cancelled' => 'Annulée',
                'checked_in' => 'Arrivée',
                'completed' => 'Terminée'
            ];
            $value = $status_map[$value] ?? $value;
        }
        
        echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
    }
    
    echo "</tr>";
}

echo "</table>";

// إضافة ملخص في النهاية
echo "<br><br>";
echo "<table border='1' style='background-color: #f2f2f2;'>";
echo "<tr><th colspan='3' style='padding: 10px;'>Résumé du rapport</th></tr>";
echo "<tr><td>Période</td><td colspan='2'>" . date('d/m/Y', strtotime($start_date)) . " - " . date('d/m/Y', strtotime($end_date)) . "</td></tr>";
echo "<tr><td>Date de génération</td><td colspan='2'>" . date('d/m/Y H:i') . "</td></tr>";
echo "<tr><td>Généré par</td><td colspan='2'>" . $_SESSION['full_name'] . "</td></tr>";
echo "</table>";

$stmt->close();
$conn->close();
?>