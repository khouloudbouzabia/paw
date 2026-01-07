<?php
session_start();
require_once '../php/db_connection.php';

// Vérifier la connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Récupération des statistiques
function countTable($conn, $sql) {
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        return $row['total'];
    } else {
        return 0;
    }
}

$total_reservations   = countTable($conn, "SELECT COUNT(*) as total FROM reservations");
$pending_reservations = countTable($conn, "SELECT COUNT(*) as total FROM reservations WHERE status = 'pending'");
$today_reservations   = countTable($conn, "SELECT COUNT(*) as total FROM reservations WHERE reservation_date = CURDATE()");
$total_products       = countTable($conn, "SELECT COUNT(*) as total FROM products");

$recent_reservations = $conn->query("SELECT * FROM reservations ORDER BY created_at DESC LIMIT 10");
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Café Al Raha</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="employee-page">

<nav class="admin-navbar">
    <div class="container">
        <h1><i class="fas fa-coffee"></i> Tableau de bord – Café Al Raha</h1>
        <div class="user-info">
            <span>Bienvenue, <?php echo $_SESSION['full_name']; ?></span>
            <a href="logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </div>
</nav>

<div class="admin-container">
    <aside class="admin-sidebar">
        <ul>
            <li><a href="staff_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
            <li><a href="reservation_staff.php"><i class="fas fa-calendar-check"></i> Gestion des réservations</a></li>
        </ul>
    </aside>

    <main class="admin-content">
        <h2>Tableau de bord</h2>

        <div class="stats-cards">
            <div class="stat-card">
                <i class="fas fa-calendar"></i>
                <h3><?php echo $total_reservations; ?></h3>
                <p>Total des réservations</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-clock"></i>
                <h3><?php echo $pending_reservations; ?></h3>
                <p>Réservations en attente</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-calendar-day"></i>
                <h3><?php echo $today_reservations; ?></h3>
                <p>Réservations du jour</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-coffee"></i>
                <h3><?php echo $total_products; ?></h3>
                <p>Articles du menu</p>
            </div>
        </div>

        <div class="charts-container">
            <div class="chart-box">
                <h3>Réservations de la dernière semaine</h3>
                <canvas id="reservationsChart"></canvas>
            </div>
            <div class="chart-box">
                <h3>Répartition des produits par catégorie</h3>
                <canvas id="productsChart"></canvas>
            </div>
        </div>

        <div class="table-container">
            <h3>Dernières réservations</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>N° Réservation</th>
                        <th>Nom du client</th>
                        <th>Date</th>
                        <th>Heure</th>
                        <th>Nombre de personnes</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($reservation = $recent_reservations->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo str_pad($reservation['id'], 5, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo htmlspecialchars($reservation['customer_name']); ?></td>
                        <td><?php echo $reservation['reservation_date']; ?></td>
                        <td><?php echo date('H:i', strtotime($reservation['reservation_time'])); ?></td>
                        <td><?php echo $reservation['number_of_people']; ?></td>
                        <td>
                            <span class="status status-<?php echo $reservation['status']; ?>">
                                <?php
                                $status_text = [
                                    'pending'   => 'En attente',
                                    'confirmed' => 'Confirmée',
                                    'cancelled' => 'Annulée'
                                ];
                                echo $status_text[$reservation['status']];
                                ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script src="../js/admin.js"></script>
</body>
</html>
