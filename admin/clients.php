<?php
session_start();
require_once '../php/db_connection.php';

// Vérifier la connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_SESSION['success_message'])) {
    echo "<div id='successMessage' class='success-message'>" . $_SESSION['success_message'] . "</div>";
    unset($_SESSION['success_message']); // Supprimer le message après affichage
}

// Vérification du type d'utilisateur
$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'admin';

// Attirer des clients grâce à la liquidation
$search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Construire la requête
$sql = "
    SELECT 
        c.id,
        c.full_name,
        c.email,
        c.phone,
        c.total_visits,
        c.loyalty_points,
        c.created_at,

        COUNT(r.id) AS total_reservations,
        COALESCE(SUM(r.number_of_people), 0) AS total_people,
        MAX(r.reservation_date) AS last_reservation

    FROM customers c
    LEFT JOIN reservations r 
        ON c.id = r.customer_id

    WHERE 1=1
";

// Ajouter des termes de recherche
$params = [];
$types = "";

if ($search_filter) {
    $sql .= " AND (c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
    $search_term = "%{$search_filter}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

if ($date_from) {
    $sql .= " AND c.created_at >= ?";
    $params[] = $date_from . " 00:00:00";
    $types .= "s";
}

if ($date_to) {
    $sql .= " AND c.created_at <= ?";
    $params[] = $date_to . " 23:59:59";
    $types .= "s";
}

$sql .= " GROUP BY c.id ORDER BY c.created_at DESC";

// Exécuter la requête
$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $total_clients = $result->num_rows;
} else {
    $result = $conn->query($sql);
    $total_clients = $result->num_rows;
}
?>


<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des clients - Café Al Raha</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .client-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .client-stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .client-stat-card i {
            font-size: 40px;
            color: #3498db;
            margin-bottom: 15px;
        }
        
        .client-stat-card h3 {
            font-size: 32px;
            margin: 10px 0;
            color: #2c3e50;
        }
        
        .client-stat-card p {
            color: #7f8c8d;
            margin: 0;
        }
        
        .client-tags {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        
        .client-tag {
            background: #ecf0f1;
            color: #2c3e50;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
        }
        
        .client-tag.regular {
            background: #2ecc71;
            color: white;
        }
        
        .client-tag.vip {
            background: #e74c3c;
            color: white;
        }
    </style>
</head>
<body class="admin-page">
    <nav class="admin-navbar">
        <div class="container">
            <h1><i class="fas fa-users"></i> Gestion des clients - Café Al Raha</h1>
            <div class="user-info">
                <span>Bonjour, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
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
                <li><a href="clients.php" class="active"><i class="fas fa-users"></i> Clients</a></li>
                <li><a href="staff.php"><i class="fas fa-user-tie"></i> Personnel</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Rapports</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Paramètres</a></li>
            </ul>
        </aside>
        
        <main class="admin-content">
            <h2>Gestion des clients</h2>
            
            <?php
            // Compte statistiques clients
$stats_sql = "
    SELECT 
        COUNT(c.id) AS total,
        COUNT(DISTINCT r.customer_id) AS with_reservations,
        SUM(
            CASE 
                WHEN c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                THEN 1 ELSE 0 
            END
        ) AS new_last_month
    FROM customers c
    LEFT JOIN reservations r ON c.id = r.customer_id
";

$stats_result = $conn->query($stats_sql);

if (!$stats_result) {
    die("Stats SQL Error: " . $conn->error);
}

$stats = $stats_result->fetch_assoc();

            $stats_result = $conn->query($stats_sql);
            $stats = $stats_result->fetch_assoc();
            ?>
            
            <div class="client-stats">
                <div class="client-stat-card">
                    <i class="fas fa-user-friends"></i>
                    <h3><?php echo $stats['total'] ?? 0; ?></h3>
                    <p>Total des clients</p>
                </div>
                
                <div class="client-stat-card">
                    <i class="fas fa-calendar-check"></i>
                    <h3><?php echo $stats['with_reservations'] ?? 0; ?></h3>
                    <p>Clients avec réservations</p>
                </div>
                
                <div class="client-stat-card">
                    <i class="fas fa-user-plus"></i>
                    <h3><?php echo $stats['new_last_month'] ?? 0; ?></h3>
                    <p>Nouveaux (30 jours)</p>
                </div>
                
                <div class="client-stat-card">
                    <i class="fas fa-star"></i>
                    <h3 id="vip-count">0</h3>
                    <p>Clients VIP</p>
                </div>
            </div>
            
            <!-- Filtres -->
            <div class="filters-container">
                <form method="GET" class="filters-form">
                    <div class="filter-row">
                        <div class="form-group" style="flex: 2;">
                            <input type="text" name="search" 
                                   value="<?php echo htmlspecialchars($search_filter); ?>"
                                   placeholder="Rechercher par nom, email ou téléphone">
                        </div>
                        
                        <div class="form-group">
                            <label>Date début</label>
                            <input type="date" name="date_from" value="<?php echo $date_from; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Date fin</label>
                            <input type="date" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                        
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-filter"></i> Appliquer
                        </button>
                        
                        <a href="clients.php" class="btn-reset">Réinitialiser</a>
                    </div>
                </form>
            </div>
            
            <!-- Tableau des clients -->
            <div class="table-container">
                <div class="table-header">
                    <h3>Liste des clients (<?php echo $total_clients; ?>)</h3>
                    <div class="table-actions">
                        <button class="btn-action btn-export">
                            <i class="fas fa-download"></i> Exporter
                        </button>
                        <button class="btn-action" id="btn-add-client">
    <i class="fas fa-plus"></i> Ajouter client
</button>

                    </div>
                </div>
                
                <?php if ($total_clients > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Réservations</th>
                            <th>Dernière visite</th>
                            <th>Inscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($client = $result->fetch_assoc()): 
                            // تحديد نوع العميل بناءً على عدد الحجوزات
                            $client_type = $client['total_reservations'] >= 10 ? 'vip' : 
                                          ($client['total_reservations'] >= 3 ? 'regular' : 'new');
                        ?>
                        <tr data-id="<?php echo $client['id']; ?>">
                            <td>#<?php echo str_pad($client['id'], 5, '0', STR_PAD_LEFT); ?></td>
                            <td>
                                <?php echo htmlspecialchars($client['full_name']); ?>
                                <div class="client-tags">
                                    <?php if ($client_type == 'vip'): ?>
                                        <span class="client-tag vip">VIP</span>
                                    <?php elseif ($client_type == 'regular'): ?>
                                        <span class="client-tag regular">Régulier</span>
                                    <?php else: ?>
                                        <span class="client-tag">Nouveau</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($client['email']); ?></td>
                            <td><?php echo htmlspecialchars($client['phone']); ?></td>
                            <td>
                                <span class="badge"><?php echo $client['total_reservations'] ?? 0; ?></span>
                                <small>(<?php echo $client['total_people'] ?? 0; ?> pers.)</small>
                            </td>
                            <td>
                                <?php if ($client['last_reservation']): ?>
                                    <?php echo date('d/m/Y', strtotime($client['last_reservation'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">Jamais</span>
                                <?php endif; ?>
                                </td>
                            <td><?php echo date('d/m/Y', strtotime($client['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-action btn-view-client" 
                                            data-id="<?php echo $client['id']; ?>">
                                        <i class="fas fa-eye"></i> Voir
                                    </button>
                                    
                                    <button class="btn-action btn-edit-client" 
                                            data-id="<?php echo $client['id']; ?>">
                                        <i class="fas fa-edit"></i>Modifier
                                    </button>
                                    
                                    <button class="btn-action btn-message" 
                                            data-email="<?php echo htmlspecialchars($client['email']); ?>">
                                        <i class="fas fa-envelope"></i>Send email
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-user-slash" style="font-size: 48px;"></i>
                    <p>Aucun client trouvé</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Modals -->
    <div id="viewClientModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3>Détails du client</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body" id="clientDetails">
                <!-- Les détails seront chargés ici -->
            </div>
        </div>
    </div>
    
    <form id="addClientForm">
    <div class="form-row">
        <div class="form-group">
            <label>Nom et prénom *</label>
            <input type="text" name="name" required>
        </div>
        <div class="form-group">
            <label>E-mail *</label>
            <input type="email" name="email" required>
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label>Téléphone *</label>
            <input type="tel" name="phone" required>
        </div>
        <div class="form-group">
            <label>date de naissance</label>
            <input type="date" name="birth_date">
        </div>
    </div>
    
    <div class="form-group">
        <label>l'adresse</label>
        <textarea name="address" rows="3"></textarea>
    </div>
    
    <div class="form-group">
        <label>commentaires</label>
        <textarea name="notes" rows="3" placeholder="Par exemple, des allergies ou des préférences..."></textarea>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn-save">
            <i class="fas fa-save"></i>sauvegarder
        </button>
        <button type="button" class="btn-cancel">إannuler</button>
    </div>
</form>

<!-- Modal for Adding Client -->
<div id="addClientModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Ajouter un nouveau client</h3>
            <span class="close-modal">&times;</span>
        </div>
        <form id="addClientForm">
            <div class="form-row">
                <div class="form-group">
                    <label>nom et prénom *</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>E-mail *</label>
                    <input type="email" name="email" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Téléphone *</label>
                    <input type="tel" name="phone" required>
                </div>
                <div class="form-group">
                    <label>date de naissance</label>
                    <input type="date" name="birth_date">
                </div>
            </div>

            <div class="form-group">
                <label>l'adresse</label>
                <textarea name="address" rows="3"></textarea>
            </div>

            <div class="form-group">
                <label>commentaires</label>
                <textarea name="notes" rows="3" placeholder="Par exemple, des allergies ou des préférences..."></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i>sauvegarder
                </button>
                <button type="button" class="btn-cancel">annulation</button>
            </div>
        </form>
    </div>
</div>

<script>
    window.onload = function() {
        // Vérifier s'il y a un message de succès
        var successMessage = document.getElementById('successMessage');
        
        if (successMessage) {
            // Masquer le message après 3 secondes
            setTimeout(function() {
                successMessage.classList.add('hidden');
            }, 3000);
        }
    };
</script>

    <script>
    $(document).ready(function() {
    // Le module se ferme lorsque l'on clique sur le bouton fermer ou annuler.
    $(document).on('click', '.close-modal, .btn-cancel', function() {
        $('#viewClientModal').hide();// Cacher le module
    });

    // Le mod se ferme lorsqu'on clique en dehors du mod
    $(document).on('click', '#viewClientModal', function(event) {
        if ($(event.target).is('#viewClientModal')) {
            $('#viewClientModal').hide(); // Cacher le module
        }
    });
    // Afficher le module Ajouter un client en cliquant sur le bouton « Ajouter un client »
$('#btn-add-client').click(function() {
    $('#addClientModal').show(); // Afficher la fenêtre Ajouter un client
});

// Le module se ferme lorsque l'on clique sur le bouton fermer ou annuler.
$(document).on('click', '.close-modal, .btn-cancel', function() {
    $('#addClientModal').hide();
});

});

    // Calcul du nombre de clients VIP
    function calculateVIPCount() {
        let vipCount = 0;
        $('.client-tag.vip').each(function() {
            vipCount++;
        });
        $('#vip-count').text(vipCount);
    }
    calculateVIPCount();
    
    // Afficher les détails du client en cliquant sur le bouton « voir »
    $(document).on('click', '.btn-view-client', function() {
        const clientId = $(this).data('id');
        
        $.ajax({
            url: '../php/get_client_details.php',
            type: 'GET',
            data: { id: clientId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const client = response.client;
                    const reservations = response.reservations;
                    
                    let details = `
                    <div class="client-details">
                        <div class="client-header">
                            <h3>${client.full_name}</h3>
                            <div class="client-meta">
                                <p><strong>ID:</strong> #${client.id}</p>
                                <p><strong>Email:</strong> ${client.email}</p>
                                <p><strong>Téléphone:</strong> ${client.phone}</p>
                                ${client.address ? `<p><strong>Adresse:</strong> ${client.address}</p>` : ''}
                                ${client.birth_date ? `<p><strong>Date de naissance:</strong> ${client.birth_date}</p>` : ''}
                                <p><strong>Inscrit le:</strong> ${client.created_at}</p>
                            </div>
                        </div>

                        <div class="client-stats">
                            <div class="stat-item">
                                <h4>Statistiques</h4>
                                <p>Total réservations: <strong>${client.total_reservations || 0}</strong></p>
                                <p>Total personnes: <strong>${client.total_people || 0}</strong></p>
                                <p>Dernière visite: <strong>${client.last_reservation || 'Jamais'}</strong></p>
                            </div>
                        </div>
                    `;
                    
                    if (reservations && reservations.length > 0) {
                        details += `
                            <div class="client-reservations">
                                <h4>Dernières réservations</h4>
                                <table class="mini-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Heure</th>
                                            <th>Personnes</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        
                        reservations.forEach(reservation => {
                            details += `
                                <tr>
                                    <td>${reservation.reservation_date}</td>
                                    <td>${reservation.reservation_time}</td>
                                    <td>${reservation.number_of_people}</td>
                                    <td><span class="status status-${reservation.status}">${reservation.status}</span></td>
                                </tr>
                            `;
                        });

                        details += `
                                    </tbody>
                                </table>
                            </div>
                        `;
                    }

                    details += `
                        <div class="client-actions" style="margin-top: 20px;">
                            <button class="btn-action" onclick="window.location.href='reservation.php?client_id=${client.id}'">
                                <i class="fas fa-calendar-plus"></i> Nouvelle réservation
                            </button>
                            <button class="btn-action" onclick="sendMessage('${client.email}')">
                                <i class="fas fa-envelope"></i> Envoyer email
                            </button>
                        </div>
                    </div>`;

                    $('#clientDetails').html(details);
                    $('#viewClientModal').show();
                } else {
                    alert('Erreur: ' + response.message);
                }
            },
            error: function() {
                alert('Erreur de connexion au serveur');
            }
        });
    });

   // Ajouter un événement pour le clic sur le bouton Modifier
$(document).on('click', '.btn-edit-client', function() {
    const clientId = $(this).data('id'); //Obtenir l'identifiant client
    window.location.href = '../edit_client.php?id=' + clientId; // Page de redirection pour modification par le client
});

    // Envoyer un e-mail lorsque vous cliquez sur le bouton « Envoyer un e-mail »
    $(document).on('click', '.btn-message', function() {
        const email = $(this).data('email');
        const subject = prompt('Objet du message:');
        if (subject) {
            const message = prompt('Message:');
            if (message) {
                $.ajax({
                    url: '../php/send_email.php',
                    type: 'POST',
                    data: {
                        to: email,
                        subject: subject,
                        message: message
                    },
                    success: function(response) {
                        alert('Message envoyé avec succès');
                    },
                    error: function() {
                        alert('Erreur lors de l\'envoi du message');
                    }
                });
            }
        }
    });

    // Ferme le module
$(document).on('click', '.btn-edit-client', function() {
    const clientId = $(this).data('id');
    window.location.href = 'edit_client.php?id=' + clientId;
});

  // Ajouter un nouveau client
$('#addClientForm').submit(function(e) {
    e.preventDefault(); // Empêcher la soumission du formulaire par défaut

    const formData = $(this).serialize();// Récupère les données du formulaire

    $.ajax({
        url: '../php/add_client.php', // Chemin d'accès au fichier d'ajout client
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Le client a été ajouté avec succès.');
                $('#addClientModal').hide();//Masquer la fenêtre Ajouter un client
                location.reload(); //Actualisez la page pour afficher le nouveau client.
            } else {
                alert('Une erreur s\est produite :' + response.message);
            }
        },
        error: function() {
            alert('Erreur de connexion au serveur');
        }
    });
});


    </script>
</body>
</html>
<?php 
if (isset($stmt)) $stmt->close();
$conn->close();
?>