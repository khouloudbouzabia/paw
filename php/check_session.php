<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

$response = [
    'loggedIn' => isset($_SESSION['user_id']),
    'username' => $_SESSION['username'] ?? null,
    'role' => $_SESSION['role'] ?? null
];

echo json_encode($response);
?>