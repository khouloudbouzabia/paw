<?php
session_start();
require_once '../php/db_connection.php';

// Vérification de la connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Vérification de l'ID du client dans l'URL
if (!isset($_GET['id'])) {
    header('Location: clients.php');
    exit;
}

// Récupération des données du client en fonction de l'ID
$client_id = $_GET['id'];
$sql = "SELECT * FROM customers WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: clients.php');
    exit;
}

$client = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier les informations du client</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="admin-navbar">
        <div class="container">
            <h1>Modifier les informations du client</h1>
            <div class="user-info">
                <span>Bonjour, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Se déconnecter
                </a>
            </div>
        </div>
    </nav>

    <div class="admin-container">
        <aside class="admin-sidebar">
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
                <li><a href="reservations.php"><i class="fas fa-calendar-check"></i> Réservations</a></li>
                <li><a href="menu.php"><i class="fas fa-utensils"></i> Menu</a></li>
                <li><a href="clients.php"><i class="fas fa-users"></i> Clients</a></li>
                <li><a href="staff.php"><i class="fas fa-user-tie"></i> Personnel</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Rapports</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Paramètres</a></li>
            </ul>
        </aside>
        
        <main class="admin-content">
            <h2>Modifier les informations du client</h2>
            
            <!-- Formulaire de modification des informations du client -->
            <form action="update_client.php" method="POST">
                <input type="hidden" name="id" value="<?php echo $client['id']; ?>">

                <div class="form-group">
                    <label>Nom complet *</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($client['full_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Adresse email *</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($client['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Numéro de téléphone *</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($client['phone']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Nombre de visites</label>
                    <input type="number" name="total_visits" value="<?php echo $client['total_visits']; ?>">
                </div>

                <div class="form-group">
                    <label>Points de fidélité</label>
                    <input type="number" name="loyalty_points" value="<?php echo $client['loyalty_points']; ?>">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Sauvegarder les modifications
                    </button>
                    <a href="clients.php" class="btn-cancel">Annuler</a>
                </div>
            </form>
        </main>
    </div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
