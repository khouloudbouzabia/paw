<?php

session_start();

// Détruire toutes les données de la session
$_SESSION = array();

// S'il existe des cookies de session, supprimez-les
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Détruire la session
session_destroy();

// Redirection vers la page de connexion
header('Location: login.php');
exit;
?>
