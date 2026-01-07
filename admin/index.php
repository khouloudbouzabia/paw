<?php
// d'accueil de l'administration
session_start();
require_once '../php/db_connection.php';

// Vérification de la connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Si l'utilisateur est connecté, rediriger vers le tableau de bord
header('Location: dashboard.php');
exit;
?>
