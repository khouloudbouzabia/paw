<?php
session_start();
require_once 'db_connection.php';

// التحقق من تسجيل الدخول ونوع المستخدم (staff فقط)
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// التحقق من البيانات
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

$reservation_id = intval($_POST['id']);

// التحقق من أن الحجز لليوم الحالي
$today = date('Y-m-d');
$sql = "SELECT * FROM reservations WHERE id = ? AND reservation_date = ? AND status = 'confirmed'";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("is", $reservation_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Réservation non trouvée ou non confirmée pour aujourd\'hui']);
        $stmt->close();
        exit;
    }
    
    $stmt->close();
}

// تحديث حالة الحجز إلى "checked_in"
$sql_update = "UPDATE reservations SET checkin_time = NOW(), checkin_staff = ?, status = 'checked_in' WHERE id = ?";
$stmt_update = $conn->prepare($sql_update);

if ($stmt_update) {
    $staff_id = $_SESSION['user_id'];
    $stmt_update->bind_param("ii", $staff_id, $reservation_id);
    
    if ($stmt_update->execute()) {
        echo json_encode(['success' => true, 'message' => 'Check-in enregistré avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement: ' . $stmt_update->error]);
    }
    
    $stmt_update->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur de préparation de la requête']);
}

$conn->close();
?>