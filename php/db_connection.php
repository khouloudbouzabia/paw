<?php
// Gestion de la configuration, session et connexion à la base de données

// PARAMÈTRES DU SITE
$site_name  = "Café Al-Raha";       // Nom du site
$timezone   = "Africa/Algiers";     // Fuseau horaire
$debug_mode = true;                 // Mode debug activé/désactivé

// Définir le fuseau horaire
date_default_timezone_set($timezone);


// DÉBUT DE LA SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// GESTION DES ERREURS
if ($debug_mode) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}


$host   = 'localhost';           // Hôte
$user   = 'root';                // Utilisateur
$pass   = '';                    // Mot de passe
$dbname = 'coffee_shop';      // Nom de la base

$conn = new mysqli($host, $user, $pass, $dbname, 3307);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Erreur de connexion à la base : " . $conn->connect_error);
}

// Définir le charset UTF-8 pour supporter toutes les langues
$conn->set_charset("utf8");


// FONCTIONS SIMPLES

// Nettoyer les données saisies par l'utilisateur
function sanitize($data) {
    return htmlspecialchars(trim($data));
}

// Vérifier si un utilisateur est connecté
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Rediriger vers une autre page
function redirect($url) {
    header("Location: $url");
    exit;
}


// FONCTIONS DE BASE POUR LA BASE DE DONNÉES

// Exécuter une requête SQL simple
function db_query($sql) {
    global $conn;
    return $conn->query($sql);
}

// Récupérer un résultat en tableau associatif
function db_fetch($result) {
    return $result->fetch_assoc();
}

// Échapper les caractères spéciaux pour SQL
function db_escape($string) {
    global $conn;
    return $conn->real_escape_string($string);
}

// Obtenir le dernier ID inséré
function db_last_id() {
    global $conn;
    return $conn->insert_id;
}
?>
