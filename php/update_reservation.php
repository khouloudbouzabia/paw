<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db_connection.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$status = $_POST['status'] ?? null;
$full_update = isset($_POST['customer_name']);

if ($full_update) {

    $customer_name = trim($_POST['customer_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $people = intval($_POST['people'] ?? 1);
    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');
    $status = trim($_POST['status'] ?? 'pending');
    $requests = trim($_POST['requests'] ?? '');

    if (
        empty($customer_name) ||
        empty($email) ||
        empty($phone) ||
        empty($date) ||
        empty($time)
    ) {
        echo json_encode(['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis']);
        exit;
    }

    $sql = "UPDATE reservations SET
        customer_name = ?, email = ?, phone = ?,
        number_of_people = ?, reservation_date = ?, reservation_time = ?,
        status = ?, special_requests = ?, updated_at = NOW()
        WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssissssi",
        $customer_name,
        $email,
        $phone,
        $people,
        $date,
        $time,
        $status,
        $requests,
        $id
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }

    $stmt->close();
}

// Mise à jour du statut uniquement (à partir du bouton de confirmation)
if (!$full_update) {

    if ($id <= 0 || empty($status)) {
        echo json_encode(['success' => false, 'message' => 'Données invalides']);
        exit;
    }

    $sql = "UPDATE reservations SET status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }

    $stmt->close();
}


$conn->close();
