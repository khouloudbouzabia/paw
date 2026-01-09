<?php
session_start();
require_once '../php/db_connection.php';

// Vérification de la connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Vérification du type d'utilisateur s'il est défini
$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'admin';

// Récupération des filtres avec validation des entrées
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';

// Validation des entrées
$allowed_statuses = ['all', 'pending', 'confirmed', 'cancelled'];
if (!in_array($status_filter, $allowed_statuses)) {
    $status_filter = 'all';
}

if ($date_filter && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_filter)) {
    $date_filter = '';
}

// Construction de la requête avec Prepared Statements
$sql = "SELECT * FROM reservations WHERE 1=1";
$params = [];
$types = "";

if ($status_filter !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($date_filter) {
    $sql .= " AND reservation_date = ?";
    $params[] = $date_filter;
    $types .= "s";
}

if ($search_filter) {
    $sql .= " AND (customer_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_term = "%{$search_filter}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

$sql .= " ORDER BY created_at DESC";

// Exécution de la requête
$stmt = $conn->prepare($sql);

if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $total_rows = $result->num_rows;
} else {
    // En cas d'erreur lors de la préparation
    $result = $conn->query("SELECT * FROM reservations ORDER BY created_at DESC");
    $total_rows = $result->num_rows;
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des réservations - Café Al Raha</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" 
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" 
          integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" 
          crossorigin="anonymous" 
          referrerpolicy="no-referrer" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="admin-page">
    <!-- Barre de navigation -->
    <nav class="admin-navbar">
        <div class="container">
            <h1><i class="fas fa-coffee"></i> Gestion des réservations - Café Al Raha</h1>
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
        <!-- Menu latéral -->
        <aside class="admin-sidebar">
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
                <li><a href="reservations.php" class="active"><i class="fas fa-calendar-check"></i> Gestion des réservations</a></li>
                <li><a href="menu.php"><i class="fas fa-utensils"></i> Gestion du menu</a></li>
                <li><a href="clients.php"><i class="fas fa-users"></i> Clients</a></li>
                <?php if ($user_type == 'admin'): ?>
                    <li><a href="staff.php"><i class="fas fa-user-tie"></i> Personnel</a></li>
                <?php endif; ?>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Rapports</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Paramètres</a></li>
            </ul>
        </aside>
        
        <!-- Contenu principal -->
        <main class="admin-content">
            <h2>Gestion des réservations</h2>
            
            <!-- Filtres -->
            <div class="filters-container">
                <form method="GET" class="filters-form">
                    <div class="filter-row">
                        <div class="form-group">
                            <label>Filtrer par statut :</label>
                            <select name="status">
                                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>Tous les statuts</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>En attente</option>
                                <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmé</option>
                                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Annulé</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Filtrer par date :</label>
                            <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Recherche :</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search_filter); ?>" placeholder="Nom, email ou téléphone">
                        </div>
                        
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-filter"></i> Appliquer
                        </button>
                        
                        <a href="reservation.php" class="btn-reset">Réinitialiser</a>
                    </div>
                </form>
            </div>
            
            <!-- Tableau -->
            <div class="table-container">
                <div class="table-header">
                    <h3>Liste des réservations</h3>
                    <div class="table-actions">
                        <button class="btn-action btn-export">
                            <i class="fas fa-download"></i> Exporter vers Excel
                        </button>
                        <span class="table-count">
                            Total : <?php echo $total_rows; ?>
                        </span>
                    </div>
                </div>
                
                <?php if ($total_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Client</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Date</th>
                            <th>Heure</th>
                            <th>Personnes</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($reservation = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo str_pad($reservation['id'], 5, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($reservation['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['email']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['phone']); ?></td>
                            <td><?php echo $reservation['reservation_date']; ?></td>
                            <td><?php echo date('H:i', strtotime($reservation['reservation_time'])); ?></td>
                            <td><?php echo $reservation['number_of_people']; ?></td>
                            <td>
                                <span class="status status-<?php echo $reservation['status']; ?>">
                                    <?php
                                    $status_text = [
                                        'pending' => 'En attente',
                                        'confirmed' => 'Confirmé',
                                        'cancelled' => 'Annulé'
                                    ];
                                    echo $status_text[$reservation['status']] ?? $reservation['status'];
                                    ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <button class="btn-action btn-view" data-id="<?php echo $reservation['id']; ?>">
                                    <i class="fas fa-eye"></i> Voir
                                </button>
                                
                                <button class="btn-action btn-edit" data-id="<?php echo $reservation['id']; ?>">
                                    <i class="fas fa-edit"></i> Modifier
                                </button>
                                
                                <?php if($reservation['status'] !== 'confirmed'): ?>
                                <button class="btn-action btn-confirm" data-id="<?php echo $reservation['id']; ?>">
                                    <i class="fas fa-check"></i> Confirmer
                                </button>
                                <?php endif; ?>
                                
                                <button class="btn-action btn-delete" data-id="<?php echo $reservation['id']; ?>">
                                    <i class="fas fa-trash"></i> Supprimer
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                    <p>Aucune réservation trouvée</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Modal pour voir les détails -->
    <div id="viewModal" class="modal" style="display: none;">
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
    
    <!-- Formulaire de modification de réservation -->
    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Modifier la réservation</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editReservationForm">
                    <input type="hidden" id="editId" name="id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nom du client *</label>
                            <input type="text" id="editCustomerName" name="customer_name" required>
                        </div>
                        <div class="form-group">
                            <label>E-mail *</label>
                            <input type="email" id="editEmail" name="email" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Numéro de téléphone *</label>
                            <input type="tel" id="editPhone" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label>Nombre de personnes *</label>
                            <input type="number" id="editPeople" name="people" min="1" max="20" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date *</label>
                            <input type="date" id="editDate" name="date" required>
                        </div>
                        <div class="form-group">
                            <label>Heure *</label>
                            <select id="editTime" name="time" required>
                                <option value="">-- Choisissez l'heure --</option>
                                <?php
                                for ($hour = 8; $hour <= 20; $hour++) {
                                    $time = sprintf('%02d:00', $hour);
                                    echo "<option value=\"$time\">$time</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Statut</label>
                        <select id="editStatus" name="status">
                            <option value="pending">En attente</option>
                            <option value="confirmed">Confirmé</option>
                            <option value="cancelled">Annulé</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Demandes spéciales</label>
                        <textarea id="editRequests" name="requests" rows="3"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                        <button type="button" class="btn-cancel">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Afficher les détails de la réservation
        $(document).on('click', '.btn-view', function() {
            const reservationId = $(this).data('id');
            
            $.ajax({
                url: '../php/get_reservations_details.php',
                type: 'GET',
                data: { id: reservationId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const reservation = response.reservation;
                        
                        const details = `
<div class="reservation-details">
    <p><strong>Numéro:</strong> #${reservation.id}</p>
    <p><strong>Nom:</strong> ${reservation.customer_name}</p>
    <p><strong>Email:</strong> ${reservation.email}</p>
    <p><strong>Téléphone:</strong> ${reservation.phone}</p>
    <p><strong>Date:</strong> ${reservation.reservation_date}</p>
    <p><strong>Heure:</strong> ${reservation.reservation_time}</p>
    <p><strong>Personnes:</strong> ${reservation.number_of_people}</p>
    <p><strong>Statut:</strong> 
        <span class="status status-${reservation.status}">
            ${
                reservation.status === 'pending' ? 'En attente' :
                reservation.status === 'confirmed' ? 'Confirmé' :
                'Annulé'
            }
        </span>
    </p>
    ${
        reservation.special_requests
        ? `<p><strong>Demandes spéciales:</strong><br>${reservation.special_requests}</p>`
        : ''
    }
    <p><strong>Créé le:</strong> ${reservation.created_at}</p>
</div>
`;

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

        // Modifier la réservation
        $(document).on('click', '.btn-edit', function() {
            const reservationId = $(this).data('id');
            
            $.ajax({
                url: '../php/get_reservations_details.php',
                type: 'GET',
                data: { id: reservationId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const reservation = response.reservation;
                        
                        $('#editId').val(reservation.id);
                        $('#editCustomerName').val(reservation.customer_name);
                        $('#editEmail').val(reservation.email);
                        $('#editPhone').val(reservation.phone);
                        $('#editDate').val(reservation.reservation_date);
                        $('#editTime').val(reservation.reservation_time.substring(0, 5));
                        $('#editPeople').val(reservation.number_of_people);
                        $('#editStatus').val(reservation.status);
                        $('#editRequests').val(reservation.special_requests || '');
                        
                        $('#editModal').show();
                    } else {
                        alert('Erreur: ' + response.message);
                    }
                },
                error: function() {
                    alert('Erreur de connexion au serveur');
                }
            });
        });

        // Confirmer la réservation
        $(document).on('click', '.btn-confirm', function() {
            const reservationId = $(this).data('id');
            
            if (confirm('Êtes-vous sûr de confirmer cette réservation ?')) {
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

        // Supprimer la réservation
        $(document).on('click', '.btn-delete', function() {
            const reservationId = $(this).data('id');
            
            if (confirm('Êtes-vous sûr de supprimer cette réservation ? Cette action est irréversible.')) {
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

        // Enregistrer les modifications
        $('#editReservationForm').submit(function(e) {
            e.preventDefault();
            const formData = $(this).serialize();
            
            $.ajax({
                url: '../php/update_reservation.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Réservation mise à jour avec succès');
                        location.reload();
                    } else {
                        alert('Erreur: ' + response.message);
                    }
                },
                error: function() {
                    alert('Erreur de connexion au serveur');
                }
            });
        });

        // Fermer les modals
        $('.close-modal, .btn-cancel').click(function() {
            $('.modal').hide();
        });
    });
    </script>
</body>
</html>
