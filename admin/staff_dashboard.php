<?php
session_start();
require_once '../php/db_connection.php';

// Vérifier l'identifiant et le type d'utilisateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../login.php');
    exit;
}

$staff_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Statistiques du jour
$stats_sql = "SELECT 
    (SELECT COUNT(*) FROM reservations 
     WHERE DATE(reservation_date) = ? 
     AND status IN ('confirmed', 'pending')) as total_reservations,
    
    (SELECT COUNT(*) FROM reservations 
     WHERE DATE(reservation_date) = ? 
     AND status = 'checked_in') as checked_in,
    
    (SELECT COUNT(*) FROM orders 
     WHERE DATE(created_at) = ? 
     AND order_status IN ('pending', 'preparing')) as active_orders,
    
    (SELECT COUNT(*) FROM reservations 
     WHERE DATE(reservation_date) = ? 
     AND status = 'confirmed'
     AND DATE(reservation_time) <= DATE_ADD(NOW(), INTERVAL 2 HOUR)) as upcoming_reservations";

$stmt = $conn->prepare($stats_sql);
if ($stmt === false) {
    die('Error preparing SQL query: ' . $conn->error);
}
$stmt->bind_param("ssss", $today, $today, $today, $today);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Changement actuel
$shift_sql = "SELECT * FROM staff_shifts 
              WHERE staff_id = ? 
              AND DATE(shift_date) = ? 
              ORDER BY start_time DESC LIMIT 1";
$shift_stmt = $conn->prepare($shift_sql);
if ($shift_stmt === false) {
    die('Error preparing SQL query: ' . $conn->error);
}
$shift_stmt->bind_param("is", $staff_id, $today);
$shift_stmt->execute();
$current_shift = $shift_stmt->get_result()->fetch_assoc();
$shift_stmt->close();

// الحجوزات القادمة
$upcoming_sql = "SELECT * FROM reservations 
                 WHERE DATE(reservation_date) = ? 
                 AND status = 'confirmed'
                 AND TIME(reservation_time) BETWEEN TIME(NOW()) AND TIME(DATE_ADD(NOW(), INTERVAL 3 HOUR))
                 ORDER BY reservation_time ASC 
                 LIMIT 5";
$upcoming_stmt = $conn->prepare($upcoming_sql);
if ($upcoming_stmt === false) {
    die('Error preparing SQL query: ' . $conn->error);
}
$upcoming_stmt->bind_param("s", $today);
$upcoming_stmt->execute();
$upcoming_reservations = $upcoming_stmt->get_result();
$upcoming_stmt->close();

// الطلبات النشطة
$orders_sql = "SELECT o.*, r.customer_name, r.table_number 
               FROM orders o
               LEFT JOIN reservations r ON o.reservation_id = r.id
               WHERE o.order_status IN ('pending', 'preparing')
               AND (o.staff_id = ? OR o.staff_id IS NULL)
               ORDER BY o.created_at DESC 
               LIMIT 5";
$orders_stmt = $conn->prepare($orders_sql);
if ($orders_stmt === false) {
    die('Error preparing SQL query: ' . $conn->error);
}
$orders_stmt->bind_param("i", $staff_id);
$orders_stmt->execute();
$active_orders = $orders_stmt->get_result();
$orders_stmt->close();
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Staff - Café Al Raha</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>
<body class="staff-page">
    <nav class="admin-navbar">
        <div class="container">
            <h1><i class="fas fa-user-tie"></i> Tableau de bord Staff</h1>
            <div class="user-info">
                <span>Bonjour, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <span class="staff-badge">Staff</span>
                <?php if ($current_shift): ?>
                <span class="shift-indicator" id="shiftIndicator">
                    <i class="fas fa-clock"></i>
                    <?php echo date('H:i', strtotime($current_shift['start_time'])) . ' - ' . 
                               date('H:i', strtotime($current_shift['end_time'])); ?>
                </span>
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
                <li><a href="staff_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
                <li><a href="staff_reservations.php"><i class="fas fa-calendar-check"></i> Réservations</a></li>
                <li><a href="orders.php"><i class="fas fa-concierge-bell"></i> Commandes</a></li>
                <li><a href="shift.php"><i class="fas fa-clipboard-list"></i> Mon shift</a></li>
                <li><a href="profile.php"><i class="fas fa-user-circle"></i> Mon profil</a></li>
            </ul>
        </aside>
        
        <main class="admin-content">
           <!-- Heure et date -->
            <div class="clock-display">
                <div class="current-date" id="currentDate"><?php echo date('l d F Y'); ?></div>
                <div class="current-time" id="currentTime"><?php echo date('H:i:s'); ?></div>
                <div style="opacity: 0.7; font-size: 14px;">Café Al Raha - Interface Staff</div>
            </div>
            
            <!-- Statistiques rapides -->
            <div class="stats-cards">
                <div class="stat-card">
                    <i class="fas fa-calendar-check"></i>
                    <h3><?php echo $stats['total_reservations'] ?? 0; ?></h3>
                    <p>Réservations aujourd'hui</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-check"></i>
                    <h3><?php echo $stats['checked_in'] ?? 0; ?></h3>
                    <p>Clients arrivés</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-concierge-bell"></i>
                    <h3><?php echo $stats['active_orders'] ?? 0; ?></h3>
                    <p>Commandes actives</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock"></i>
                    <h3><?php echo $stats['upcoming_reservations'] ?? 0; ?></h3>
                    <p>Réservations prochaines</p>
                </div>
            </div>
            
           <!-- Actions rapides -->
            <div class="quick-actions">
                <a href="staff_reservations.php?action=checkin" class="quick-action">
                    <i class="fas fa-user-check"></i>
                    <span>Check-in client</span>
                </a>
                <a href="orders.php?action=new" class="quick-action">
                    <i class="fas fa-plus-circle"></i>
                    <span>Nouvelle commande</span>
                </a>
                <a href="staff_reservations.php" class="quick-action">
                    <i class="fas fa-calendar-day"></i>
                    <span>Voir réservations</span>
                </a>
                <a href="orders.php" class="quick-action">
                    <i class="fas fa-list"></i>
                    <span>Commandes en cours</span>
                </a>
                <a href="shift.php" class="quick-action">
                    <i class="fas fa-clipboard-check"></i>
                    <span>Rapport shift</span>
                </a>
                <a href="profile.php" class="quick-action">
                    <i class="fas fa-user-cog"></i>
                    <span>Mon profil</span>
                </a>
            </div>
            
            <!-- Titres en vedette -->
            <div class="today-highlights">
                <!--Réservations à venir -->
                <div class="highlight-card">
                    <h3><i class="fas fa-clock"></i> Prochaines réservations (3h)</h3>
                    <?php if ($upcoming_reservations->num_rows > 0): ?>
                        <?php while($reservation = $upcoming_reservations->fetch_assoc()): ?>
                        <div class="reservation-item">
                            <div>
                                <strong><?php echo htmlspecialchars($reservation['customer_name']); ?></strong>
                                <div style="font-size: 12px; color: #7f8c8d;">
                                    <?php echo $reservation['number_of_people']; ?> personnes
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <span class="time-badge">
                                    <?php echo date('H:i', strtotime($reservation['reservation_time'])); ?>
                                </span>
                                <div style="margin-top: 5px;">
                                    <button class="btn-action btn-sm btn-checkin" 
                                            data-id="<?php echo $reservation['id']; ?>">
                                        <i class="fas fa-user-check"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 20px; color: #7f8c8d;">
                            <i class="fas fa-calendar-times" style="font-size: 48px;"></i>
                            <p>Aucune réservation à venir</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Commandes actives -->
                <div class="highlight-card">
                    <h3><i class="fas fa-concierge-bell"></i> Commandes en cours</h3>
                    <?php if ($active_orders->num_rows > 0): ?>
                        <?php while($order = $active_orders->fetch_assoc()): ?>
                        <div class="order-item">
                            <div>
                                <strong>Commande #<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></strong>
                                <div style="font-size: 12px; color: #7f8c8d;">
                                    <?php echo htmlspecialchars($order['customer_name']); ?>
                                    <?php if ($order['table_number']): ?>
                                        - Table <?php echo $order['table_number']; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                    <?php 
                                    $status_map = [
                                        'pending' => 'En attente',
                                        'preparing' => 'En préparation',
                                        'ready' => 'Prête'
                                    ];
                                    echo $status_map[$order['order_status']] ?? $order['order_status'];
                                    ?>
                                </span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 20px; color: #7f8c8d;">
                            <i class="fas fa-concierge-bell" style="font-size: 48px;"></i>
                            <p>Aucune commande en cours</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
    // Mise à jour de l'heure en direct
    function updateClock() {
        const now = new Date();
        const dateStr = now.toLocaleDateString('fr-FR', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric'
            });
        
        const timeStr = now.toLocaleTimeString('fr-FR', {
            hour12: false,
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        
        $('#currentDate').text(dateStr);
        $('#currentTime').text(timeStr);
    }
    
    // Mise à jour toutes les secondes
    setInterval(updateClock, 1000);
    updateClock();
    
    // Check-in client
    $(document).on('click', '.btn-checkin', function() {
        const reservationId = $(this).data('id');
        
        if (confirm('Confirmer l\'arrivée du client ?')) {
            $.ajax({
                url: '../php/checkin_reservation.php',
                type: 'POST',
                data: { reservation_id: reservationId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Check-in enregistré avec succès');
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
    
    // Auto-refresh every 60 seconds
    setTimeout(function() {
        location.reload();
    }, 60000);
    </script>
</body>
</html>
<?php $conn->close(); ?>