<?php
session_start();
require_once '../php/db_connection.php';

// Vérification de la connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Vérification du type d’utilisateur s’il existe
$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'admin';

// Fonctions de statistiques
function countTable($conn, $sql, $params = []) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Erreur de préparation: " . $conn->error);
        return 0;
    }
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $row = $result->fetch_assoc()) {
        return $row['total'];
    }
    
    return 0;
}

// Statistiques
$total_reservations = countTable($conn, "SELECT COUNT(*) as total FROM reservations");
$pending_reservations = countTable($conn, "SELECT COUNT(*) as total FROM reservations WHERE status = ?", ['pending']);
$today_reservations = countTable($conn, "SELECT COUNT(*) as total FROM reservations WHERE reservation_date = CURDATE()");
$total_products = countTable($conn, "SELECT COUNT(*) as total FROM products");

// Dernières réservations
$sql = "SELECT * FROM reservations ORDER BY created_at DESC LIMIT 10";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->execute();
    $recent_reservations = $stmt->get_result();
} else {
    $recent_reservations = $conn->query($sql);
}

// Statistiques supplémentaires pour le graphique
$weekly_stats = [];
$days = 6;
for ($i = $days; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $sql = "SELECT COUNT(*) as count FROM reservations WHERE DATE(reservation_date) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $weekly_stats[] = [
        'date' => date('d/m', strtotime($date)),
        'count' => $row['count'] ?? 0
    ];
}

// Statistiques des produits par catégorie
$categories_stats = [];
$sql = "SELECT category, COUNT(*) as count FROM products GROUP BY category";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $categories_stats[] = [
        'category' => $row['category'],
        'count' => $row['count']
    ];
}
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
<body class="admin-page">
    <nav class="admin-navbar">
        <div class="container">
            <h1><i class="fas fa-coffee"></i> Tableau de bord - Café Al Raha</h1>
            <div class="user-info">
                <span>Bienvenue, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <?php if ($user_type == 'staff'): ?>
                <span class="user-badge"><i class="fas fa-user-tie"></i> Staff</span>
                <?php endif; ?>
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </div>
    </nav>
    
    <div class="admin-container">
        <aside class="admin-sidebar">
            <ul>
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
                <li><a href="reservation.php"><i class="fas fa-calendar-check"></i> Gestion des réservations</a></li>
                <li><a href="menu.php"><i class="fas fa-utensils"></i> Gestion du menu</a></li>
                <li><a href="clients.php"><i class="fas fa-users"></i> Gestion des clients</a></li>
                <?php if ($user_type == 'admin'): ?>
                <li><a href="staff.php"><i class="fas fa-user-tie"></i> Gestion du personnel</a></li>
                <?php endif; ?>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Rapports</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Paramètres</a></li>
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_reservations && $recent_reservations->num_rows > 0): ?>
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
                                            'pending' => 'En attente',
                                            'confirmed' => 'Confirmée',
                                            'cancelled' => 'Annulée'
                                        ];
                                        echo $status_text[$reservation['status']] ?? $reservation['status'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-action btn-confirm" data-id="<?php echo $reservation['id']; ?>">Confirmer</button>
                                    <button class="btn-action btn-delete" data-id="<?php echo $reservation['id']; ?>">Supprimer</button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 20px;">
                                    <i class="fas fa-inbox" style="font-size: 48px; color: #ccc;"></i>
                                    <p>Aucune réservation trouvée</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
    $(document).ready(function() {
        // Graphique des réservations de la semaine
        const weeklyData = {
            labels: <?php echo json_encode(array_column($weekly_stats, 'date')); ?>,
            datasets: [{
                label: 'Nombre de réservations',
                data: <?php echo json_encode(array_column($weekly_stats, 'count')); ?>,
                backgroundColor: '#3498db',
                borderColor: '#2980b9',
                borderWidth: 2
            }]
        };
        
        const reservationsChart = new Chart(
            document.getElementById('reservationsChart'),
            {
                type: 'bar',
                data: weeklyData,
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            }
        );
        
        // Graphique des catégories de produits
        const categoriesData = {
            labels: <?php echo json_encode(array_column($categories_stats, 'category')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($categories_stats, 'count')); ?>,
                backgroundColor: [
                    '#e74c3c', '#27ae60', '#3498db', 
                    '#f39c12', '#9b59b6', '#1abc9c'
                ]
            }]
        };
        
        const productsChart = new Chart(
            document.getElementById('productsChart'),
            {
                type: 'pie',
                data: categoriesData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            }
        );
        
        // Actions sur les boutons
        $('.btn-confirm').click(function() {
            const reservationId = $(this).data('id');
            if (confirm('Êtes-vous sûr de vouloir confirmer cette réservation ?')) {
                $.ajax({
                    url: '../php/update_reservation.php',
                    type: 'POST',
                    data: {
                        id: reservationId,
                        status: 'confirmed'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Réservation confirmée avec succès');
                            location.reload();
                        } else {
                            alert('Erreur: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Erreur de connexion au serveur');
                    }
                });
            }
        });
        
        $('.btn-delete').click(function() {
            const reservationId = $(this).data('id');
            if (confirm('Êtes-vous sûr de vouloir supprimer cette réservation ? Cette action est irréversible.')) {
                $.ajax({
                    url: '../php/delete_reservation.php',
                    type: 'POST',
                    data: { id: reservationId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Réservation supprimée avec succès');
                            location.reload();
                        } else {
                            alert('Erreur: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Erreur de connexion au serveur');
                    }
                });
            }
        });
    });
    </script>
    
    <script src="../js/admin.js"></script>
</body>
</html>