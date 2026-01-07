<?php
session_start();

// Si l'utilisateur est déjà connecté, redirection vers le tableau de bord
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once '../php/db_connection.php';

$error = "";

// Vérifier si le formulaire est envoyé
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Récupérer les données du formulaire
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Préparer la requête SQL (sécurisée)
    $stmt = $conn->prepare("SELECT id, username, password, role, full_name FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Vérifier si l'utilisateur existe
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Vérifier le mot de passe
        if ($password === $user['password']) {

            // Créer la session utilisateur
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];

            // Redirection vers le tableau de bord
            header('Location: dashboard.php');
            exit;

        } else {
            $error = "Nom d'utilisateur ou mot de passe incorrect";
        }
    } else {
        $error = "Nom d'utilisateur ou mot de passe incorrect";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - Administration</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">

<div class="login-container">
    <div class="login-box">

        <div class="login-header">
            <h1><i class="fas fa-coffee"></i> Connexion</h1>
            <p>Panneau d'administration du café</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <?= $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="login-form">

            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user"></i> Nom d'utilisateur
                </label>
                <input type="text" name="username" id="username" required>
            </div>

            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Mot de passe
                </label>
                <input type="password" name="password" id="password" required>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Se connecter
            </button>

        </form>

        <div class="login-footer">
            <p>Mot de passe oublié ? <a href="#">Contactez l'administrateur</a></p>
            <p><a href="../index.html">Retour au site</a></p>
        </div>

    </div>
</div>

</body>
</html>
