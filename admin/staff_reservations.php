<?php
session_start();
require_once '../php/db_connection.php';

$staff_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// التصفية
$filter_date = isset($_GET['date']) ? $_GET['date'] : $today;
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// بناء الاستعلام
$sql = "SELECT * FROM reservations WHERE DATE(reservation_date) = ?";
$params = [$filter_date];
$types = "s";

if ($filter_status !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if ($search_term) {
    $sql .= " AND (customer_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_like = "%$search_term%";
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $types .= "sss";
}

$sql .= " ORDER BY reservation_time ASC";

// تنفيذ الاستعلام
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$reservations = $stmt->get_result();
$total_reservations = $reservations->num_rows;
$stmt->close();
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des réservations - Staff</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="staff-page">
    <nav class="admin-navbar">
        <div class="container">
            <h1><i class="fas fa-calendar-check"></i> Gestion des réservations</h1>
            <div class="user-info">
                <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <span class="staff-badge">Staff</span>
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </div>
    </nav>
    
    <div class="admin-container">
        <aside class="admin-sidebar">
            <ul>
                <li><a href="staff_dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
                <li><a href="staff_reservations.php" class="active"><i class="fas fa-calendar-check"></i> Réservations</a></li>
                <li><a href="orders.php"><i class="fas fa-concierge-bell"></i> Commandes</a></li>
                <li><a href="shift.php"><i class="fas fa-clipboard-list"></i> Mon shift</a></li>
                <li><a href="profile.php"><i class="fas fa-user-circle"></i> Mon profil</a></li>
            </ul>
        </aside>
        
        <main class="admin-content">
            <h2>Gestion des réservations</h2>
            
            <!-- Filtres -->
            <div class="filters-container">
                <form method="GET" class="filters-form">
                    <div class="filter-row">
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="date" value="<?php echo $filter_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Statut</label>
                            <select name="status">
                                <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>Tous</option>
                                <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>En attente</option>
                                <option value="confirmed" <?php echo $filter_status == 'confirmed' ? 'selected' : ''; ?>>Confirmée</option>
                                <option value="checked_in" <?php echo $filter_status == 'checked_in' ? 'selected' : ''; ?>>Arrivée</option>
                                <option value="cancelled" <?php echo $filter_status == 'cancelled' ? 'selected' : ''; ?>>Annulée</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Recherche</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Nom, email, téléphone">
                        </div>
                        
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-filter"></i> Filtrer
                        </button>
                        
                        <a href="reservations.php" class="btn-reset">
                            <i class="fas fa-redo"></i> Réinitialiser
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Résumé des statuts -->
            <div class="status-summary" style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
                <?php
                $status_counts = [
                    'all' => $total_reservations,
                    'pending' => 0,
                    'confirmed' => 0,
                    'checked_in' => 0,
                    'cancelled' => 0
                ];
                
                mysqli_data_seek($reservations, 0);
                while($row = $reservations->fetch_assoc()) {
                    $status_counts[$row['status']]++;
                }
                mysqli_data_seek($reservations, 0);
                ?>
                
                <div class="status-badge" style="background: #95a5a6;">
                    <span>Total: <?php echo $status_counts['all']; ?></span>
                </div>
                <div class="status-badge" style="background: #f39c12;">
                    <span>En attente: <?php echo $status_counts['pending']; ?></span>
                </div>
                <div class="status-badge" style="background: #3498db;">
                    <span>Confirmées: <?php echo $status_counts['confirmed']; ?></span>
                </div>
                <div class="status-badge" style="background: #2ecc71;">
                    <span>Arrivées: <?php echo $status_counts['checked_in']; ?></span>
                </div>
                <div class="status-badge" style="background: #e74c3c;">
                    <span>Annulées: <?php echo $status_counts['cancelled']; ?></span>
                </div>
            </div>
            
            <!-- Tableau des réservations -->
            <div class="table-container">
                <div class="table-header">
                    <h3>Liste des réservations (<?php echo $total_reservations; ?>)</h3>
                    <div class="table-actions">
                        <button class="btn-action" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Actualiser
                        </button>
                        <button class="btn-action" onclick="printTable()">
                            <i class="fas fa-print"></i> Imprimer
                        </button>
                    </div>
                </div>
                
                <?php if ($total_reservations > 0): ?>
                <table class="data-table" id="reservationsTable">
                    <thead>
                        <tr>
                            <th>Heure</th>
                            <th>Client</th>
                            <th>Contact</th>
                            <th>Personnes</th>
                            <th>Table</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($reservation = $reservations->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo date('H:i', strtotime($reservation['reservation_time'])); ?></strong>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($reservation['customer_name']); ?></strong>
                                <?php if ($reservation['special_requests']): ?>
                                <div class="special-request" style="font-size: 12px; color: #7f8c8d; margin-top: 3px;">
                                    <i class="fas fa-sticky-note"></i> 
                                    <?php echo substr(htmlspecialchars($reservation['special_requests']), 0, 30); ?>...
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($reservation['email']); ?></div>
                                <div><?php echo htmlspecialchars($reservation['phone']); ?></div>
                            </td>
                            <td><?php echo $reservation['number_of_people']; ?></td>
                            <td>
                                <?php if ($reservation['table_number']): ?>
                                    <span class="table-badge">Table <?php echo $reservation['table_number']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">À assigner</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status status-<?php echo $reservation['status']; ?>">
                                    <?php 
                                    $status_text = [
                                        'pending' => 'En attente',
                                        'confirmed' => 'Confirmée',
                                        'checked_in' => 'Arrivée',
                                        'cancelled' => 'Annulée'
                                    ];
                                    echo $status_text[$reservation['status']] ?? $reservation['status'];
                                    ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($reservation['status'] == 'confirmed'): ?>
                                    <button class="btn-action btn-checkin" 
                                            data-id="<?php echo $reservation['id']; ?>"
                                            title="Check-in client">
                                        <i class="fas fa-user-check"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <button class="btn-action btn-view" 
                                            data-id="<?php echo $reservation['id']; ?>"
                                            title="Voir détails">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-action btn-add-order" 
                                            data-id="<?php echo $reservation['id']; ?>"
                                            title="Nouvelle commande">
                                        <i class="fas fa-concierge-bell"></i>
                                    </button>
                                    
                                    <?php if ($reservation['status'] == 'confirmed'): ?>
                                    <button class="btn-action btn-cancel" 
                                            data-id="<?php echo $reservation['id']; ?>"
                                            title="Annuler réservation">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-calendar-times" style="font-size: 48px;"></i>
                    <p>Aucune réservation trouvée</p>
                    <a href="staff_reservations.php?date=<?php echo date('Y-m-d'); ?>" class="btn-action">
                        <i class="fas fa-calendar-day"></i> Voir aujourd'hui
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Modal pour voir les détails -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Détails de la réservation</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body" id="viewModalBody">
                <!-- Les détails seront chargés ici -->
            </div>
        </div>
    </div>
    
    <!-- Modal pour créer une commande -->
    <div id="orderModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3>Nouvelle commande</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div id="orderFormContainer">
                    <!-- Formulaire sera chargé ici -->
                </div>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
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
        
        // Voir les détails
        $(document).on('click', '.btn-view', function() {
            const reservationId = $(this).data('id');
            
            $.ajax({
                url: '../php/get_reservation_details.php',
                type: 'GET',
                data: { id: reservationId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const reservation = response.reservation;
                        
                        let details = 
                            <div class="reservation-details">
                                <div class="detail-row">
                                    <strong>ID:</strong> #${reservation.id}
                                </div>
                                <div class="detail-row">
                                    <strong>Client:</strong> ${reservation.customer_name}
                                </div>
                                <div class="detail-row">
                                    <strong>Email:</strong> ${reservation.email}
                                </div>
                                <div class="detail-row">
                                    <strong>Téléphone:</strong> ${reservation.phone}
                                </div>
                                <div class="detail-row">
                                    <strong>Date:</strong> ${reservation.reservation_date}
                                </div>
                                <div class="detail-row">
                                    <strong>Heure:</strong> ${reservation.reservation_time}
                                </div>
                                <div class="detail-row">
                                    <strong>Personnes:</strong> ${reservation.number_of_people}
                                </div>
                                <div class="detail-row">
                                    <strong>Statut:</strong> 
                                    <span class="status status-${reservation.status}">
                                        ${reservation.status === 'pending' ? 'En attente' : 
                                         reservation.status === 'confirmed' ? 'Confirmée' : 
                                         reservation.status === 'checked_in' ? 'Arrivée' : 'Annulée'}
                                    </span>
                                </div>
                        ;
                        
                        if (reservation.table_number) {
                            details += 
                                <div class="detail-row">
                                    <strong>Table:</strong> ${reservation.table_number}
                                </div>
                            ;
                        }
                        
                        if (reservation.special_requests) {
                            details += 
                                <div class="detail-row">
                                    <strong>Demandes spéciales:</strong>
                                    <p style="margin-top: 5px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                        ${reservation.special_requests}
                                    </p>
                                </div>
                            ;
                        }
                        
                        if (reservation.checkin_time) {
                            details += 
                                <div class="detail-row">
                                    <strong>Check-in:</strong> ${reservation.checkin_time}
                                </div>
                            ;
                        }
                        
                        details += </div>;
                        
                        $('#viewModalBody').html(details);
                        $('#viewModal').show();
                    } else {
                        alert('Erreur: ' + response.message);
                    }
                },
                error: function() {
                    alert('Erreur de connexion au serveur');
                }
            });
        });
        
        // Annuler réservation
        $(document).on('click', '.btn-cancel', function() {
            const reservationId = $(this).data('id');
            
            if (confirm('Êtes-vous sûr d\'annuler cette réservation ? Un email sera envoyé au client.')) {
                $.ajax({
                    url: '../php/cancel_reservation.php',
                    type: 'POST',
                    data: { reservation_id: reservationId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Réservation annulée avec succès');
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
        
        // Créer une commande
        $(document).on('click', '.btn-add-order', function() {
            const reservationId = $(this).data('id');
            
            $.ajax({
                url: '../php/get_order_form.php',
                type: 'GET',
                data: { reservation_id: reservationId },
                dataType: 'html',
                success: function(response) {
                    $('#orderFormContainer').html(response);
                    $('#orderModal').show();
                },
                error: function() {
                    alert('Erreur de chargement du formulaire');
                }
            });
        });
        
        // Fermer les modals
        $('.close-modal').click(function() {
            $('.modal').hide();
        });
        
        $(window).click(function(event) {
            if ($(event.target).hasClass('modal')) {
                $('.modal').hide();
            }
        });
        
        // Imprimer le tableau
        function printTable() {
            const printWindow = window.open('', '_blank');
            const tableHtml = document.getElementById('reservationsTable').outerHTML;
            
            printWindow.document.write(
                <html>
                <head>
                    <title>Réservations - ${new Date().toLocaleDateString('fr-FR')}</title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        .status { padding: 3px 8px; border-radius: 10px; font-size: 12px; }
                        .status-confirmed { background: #3498db; color: white; }
                        .status-checked_in { background: #2ecc71; color: white; }
                    </style>
                </head>
                <body>
                    <h2>Réservations - Café Al Raha</h2>
                    <p>Date: ${new Date().toLocaleDateString('fr-FR')}</p>
                    <p>Généré par: ${'<?php echo $_SESSION["full_name"]; ?>'}</p>
                    ${tableHtml}
                </body>
                </html>
            );
            
            printWindow.document.close();
            printWindow.print();
        }
    });
    </script>
</body>
</html>
<?php $conn->close(); ?>