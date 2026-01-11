<?php
session_start();
require_once '../php/db_connection.php';

$staff_id = $_SESSION['user_id'];
$today = date('Y-m-d');

//Filtre
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'active';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Construire la requête de base
$sql = "SELECT o.*, r.customer_name, r.table_number 
        FROM orders o
        JOIN reservations r ON o.reservation_id = r.id
        WHERE DATE(o.created_at) = ?";

// Filtrer par statut
if ($filter_status === 'active') {
    $sql .= " AND o.order_status IN ('pending', 'preparing', 'ready')";
} elseif ($filter_status !== 'all') {
    $sql .= " AND o.order_status = ?";
}

// Configuration des paramètres
$params = [$today];
$types = "s";

if ($filter_status !== 'active' && $filter_status !== 'all') {
    $params[] = $filter_status;
    $types .= "s";
}

if ($search_term) {
    $sql .= " AND (r.customer_name LIKE ? OR o.id LIKE ?)";
    $search_like = "%$search_term%";
    $params[] = $search_like;
    $params[] = "%" . str_pad($search_term, 4, '0', STR_PAD_LEFT) . "%";
    $types .= "ss";
}

// Trie les résultats
$sql .= " ORDER BY 
          CASE o.order_status 
            WHEN 'pending' THEN 1
            WHEN 'preparing' THEN 2
            WHEN 'ready' THEN 3
            WHEN 'served' THEN 4
            WHEN 'cancelled' THEN 5
            ELSE 6
          END,
          o.created_at DESC";

// Exécuter la requête
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error preparing query: " . $conn->error . " | SQL: " . $sql);
}

if ($params) {
    // Paramètres de liaison
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$orders = $stmt->get_result();
$total_orders = $orders->num_rows;
$stmt->close();

// Demander des statistiques
$stats_sql = "SELECT 
    COUNT(CASE WHEN order_status = 'pending' THEN 1 END) as pending,
    COUNT(CASE WHEN order_status = 'preparing' THEN 1 END) as preparing,
    COUNT(CASE WHEN order_status = 'ready' THEN 1 END) as ready,
    COUNT(CASE WHEN order_status = 'served' THEN 1 END) as served,
    COUNT(CASE WHEN order_status = 'cancelled' THEN 1 END) as cancelled
    FROM orders WHERE DATE(created_at) = ?";

// Configuration d'une requête de statistiques de commande
$stats_stmt = $conn->prepare($stats_sql);
if ($stats_stmt === false) {
    die("Error preparing stats query: " . $conn->error . " | SQL: " . $stats_sql);
}

$stats_stmt->bind_param("s", $today);
$stats_stmt->execute();
$order_stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des commandes - Staff</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="staff-page">
    <nav class="admin-navbar">
        <div class="container">
            <h1><i class="fas fa-concierge-bell"></i> Gestion des commandes</h1>
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
                <li><a href="staff_reservations.php"><i class="fas fa-calendar-check"></i> Réservations</a></li>
                <li><a href="orders.php" class="active"><i class="fas fa-concierge-bell"></i> Commandes</a></li>
                <li><a href="shift.php"><i class="fas fa-clipboard-list"></i> Mon shift</a></li>
                <li><a href="profile.php"><i class="fas fa-user-circle"></i> Mon profil</a></li>
            </ul>
        </aside>
        
        <main class="admin-content">
            <h2>Gestion des commandes</h2>
            
            <!-- Statistiques rapides -->
            <div class="stats-cards">
                <div class="stat-card" style="border-left: 4px solid #f39c12;">
                    <i class="fas fa-clock" style="color: #f39c12;"></i>
                    <h3><?php echo $order_stats['pending'] ?? 0; ?></h3>
                    <p>En attente</p>
                </div>
                <div class="stat-card" style="border-left: 4px solid #3498db;">
                    <i class="fas fa-utensils" style="color: #3498db;"></i>
                    <h3><?php echo $order_stats['preparing'] ?? 0; ?></h3>
                    <p>En préparation</p>
                </div>
                <div class="stat-card" style="border-left: 4px solid #2ecc71;">
                    <i class="fas fa-check-circle" style="color: #2ecc71;"></i>
                    <h3><?php echo $order_stats['ready'] ?? 0; ?></h3>
                    <p>Prêtes</p>
                </div>
                <div class="stat-card" style="border-left: 4px solid #9b59b6;">
                    <i class="fas fa-concierge-bell" style="color: #9b59b6;"></i>
                    <h3><?php echo $order_stats['served'] ?? 0; ?></h3>
                    <p>Servies</p>
                </div>
            </div>
            
            <!-- Filtres -->
            <div class="filters-container">
                <form method="GET" class="filters-form">
                    <div class="filter-row">
                        <div class="form-group">
                            <label>Statut</label>
                            <select name="status">
                                <option value="active" <?php echo $filter_status == 'active' ? 'selected' : ''; ?>>Actives</option>
                                <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>Toutes</option>
                                <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>En attente</option>
                                <option value="preparing" <?php echo $filter_status == 'preparing' ? 'selected' : ''; ?>>En préparation</option>
                                <option value="ready" <?php echo $filter_status == 'ready' ? 'selected' : ''; ?>>Prêtes</option>
                                <option value="served" <?php echo $filter_status == 'served' ? 'selected' : ''; ?>>Servies</option>
                                <option value="cancelled" <?php echo $filter_status == 'cancelled' ? 'selected' : ''; ?>>Annulées</option>
                                </select>
                        </div>
                        
                        <div class="form-group" style="flex: 2;">
                            <label>Recherche</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search_term); ?>" 
                                   placeholder="N° commande ou nom client">
                        </div>
                        
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-filter"></i> Filtrer
                        </button>
                        
                        <a href="orders.php" class="btn-reset">
                            <i class="fas fa-redo"></i> Réinitialiser
                        </a>
                        
<button type="button" class="btn-action" onclick="showNewOrderModal()">
    <i class="fas fa-plus"></i> Nouvelle commande
</button>
                    </div>
                </form>
            </div>
            
            <!-- Tableau des commandes -->
            <div class="table-container">
                <div class="table-header">
                    <h3>Commandes du jour (<?php echo $total_orders; ?>)</h3>
                    <div class="table-actions">
                        <button class="btn-action" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Actualiser
                        </button>
                        <button class="btn-action" onclick="printKitchenOrders()">
                            <i class="fas fa-print"></i> Cuisine
                        </button>
                    </div>
                </div>
                
                <?php if ($total_orders > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Client</th>
                            <th>Contenu</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Temps</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($order = $orders->fetch_assoc()): 
                            // Calcul du temps écoulé
                            $created_time = strtotime($order['created_at']);
                            $current_time = time();
                            $elapsed_minutes = floor(($current_time - $created_time) / 60);
                            
                            // Déterminer la priorité
                            $priority = ($elapsed_minutes > 30) ? 'high' : 
                                       (($elapsed_minutes > 15) ? 'medium' : 'low');
                            
                            // Récupérer les éléments de la requête
                            $items_sql = "SELECT oi.*, p.name as product_name 
                                         FROM order_items oi
                                         JOIN products p ON oi.product_id = p.id
                                         WHERE oi.order_id = ?";
                            $items_stmt = $conn->prepare($items_sql);
                            $items_stmt->bind_param("i", $order['id']);
                            $items_stmt->execute();
                            $order_items = $items_stmt->get_result();
                            $items_count = $order_items->num_rows;
                        ?>
                        <tr class="order-row status-<?php echo $order['status']; ?>">
                            <td>
                                <span class="order-priority priority-<?php echo $priority; ?>"></span>
                                <strong>#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></strong>
                            </td>
                            <td>
                                <div><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></div>
                                <?php if ($order['table_number']): ?>
                                <div style="font-size: 12px; color: #7f8c8d;">
                                    Table <?php echo $order['table_number']; ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="order-item-count"><?php echo $items_count; ?> articles</span>
                                <div class="order-items">
                                    <?php 
                                    $items_display = [];
                                    while($item = $order_items->fetch_assoc()) {
                                        $items_display[] = $item['quantity'] . 'x ' . $item['product_name'];
                                    }
                                    echo implode(', ', array_slice($items_display, 0, 2));
                                    if (count($items_display) > 2) echo '...';
                                    ?>
                                </div>
                                <?php if ($order['notes']): ?>
                                <div class="kitchen-notes">
                                    <i class="fas fa-sticky-note"></i> <?php echo substr($order['notes'], 0, 30); ?>...
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo number_format($order['total_amount'], 2); ?> DA</strong>
                            </td>
                            <td>
                                <span class="status status-<?php echo $order['order_status']; ?>">
                                    <?php 
                                    $status_text = [
                                        'pending' => 'En attente',
                                        'preparing' => 'En préparation',
                                        'ready' => 'Prête',
                                        'served' => 'Servie',
                                        'cancelled' => 'Annulée'
                                    ];
                                    echo $status_text[$order['order_status']] ?? $order['order_status'];
                                    ?>
                                </span>
                            </td>
                            <td>
                                <div class="timer">
                                    <?php 
                                    $hours = floor($elapsed_minutes / 60);
                                    $minutes = $elapsed_minutes % 60;
                                    echo sprintf('%02d:%02d', $hours, $minutes);
                                    ?>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-action btn-view-order" 
                                            data-id="<?php echo $order['id']; ?>"
                                            title="Voir détails">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <?php if ($order['order_status'] == 'pending'): ?>
                                    <button class="btn-action btn-start-order"
                                    data-id="<?php echo $order['id']; ?>"
                                            title="Commencer préparation">
                                        <i class="fas fa-play"></i>
                                    </button>
                                    <?php elseif ($order['order_status'] == 'preparing'): ?>
                                    <button class="btn-action btn-ready-order" 
                                            data-id="<?php echo $order['id']; ?>"
                                            title="Prête à servir">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <?php elseif ($order['order_status'] == 'ready'): ?>
                                    <button class="btn-action btn-serve-order" 
                                            data-id="<?php echo $order['id']; ?>"
                                            title="Marquer comme servie">
                                        <i class="fas fa-concierge-bell"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array($order['order_status'], ['pending', 'preparing'])): ?>
                                    <button class="btn-action btn-cancel-order" 
                                            data-id="<?php echo $order['id']; ?>"
                                            title="Annuler commande">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php 
                        $items_stmt->close();
                        endwhile; 
                        ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-concierge-bell" style="font-size: 48px;"></i>
                    <p>Aucune commande trouvée</p>
                    <!-- HTML -->
<button type="button" class="btn-action" onclick="showNewOrderModal()">
    <i class="fas fa-plus"></i> Nouvelle commande
</button>

<script>
function showNewOrderModal() {
    $.ajax({
        url: '../php/new_order_form.php',
        type: 'GET',
        dataType: 'html',
        success: function(response) {
            $('#newOrderBody').html(response);
            $('#newOrderModal').show();
        },
        error: function() {
            alert('Erreur de chargement du formulaire');
        }
    });
}
</script>

                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Modals -->
    <div id="orderDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Détails de la commande</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body" id="orderDetailsBody"></div>
        </div>
    </div>
    
    <div id="newOrderModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3>Nouvelle commande</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body" id="newOrderBody">
                <!-- Formulaire sera chargé ici -->
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
    // Vérifiez que tous les boutons fonctionnent correctement grâce aux événements délégués
    $(document).on('click', '.btn-view-order', function() {
        const orderId = $(this).data('id');
        
        $.ajax({
            url: '../php/get_order_details.php',
            type: 'GET',
            data: { id: orderId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const order = response.order;
                    const items = response.items;
                    
                    let itemsHtml = '<h4>Articles:</h4><table class="mini-table">';
                    itemsHtml += '<tr><th>Produit</th><th>Qté</th><th>Prix</th><th>Total</th></tr>';
                    
                    items.forEach(item => {
                        itemsHtml += 
                            `<tr>
                                <td>${item.product_name}</td>
                                <td>${item.quantity}</td>
                                <td>${item.price} DA</td>
                                <td>${item.quantity * item.price} DA</td>
                            </tr>`;
                    });
                    
                    itemsHtml += '</table>';
                    
                    const details = 
                        `<div class="order-details">
                            <div class="detail-row">
                                <strong>Commande:</strong> #${order.id}
                            </div>
                            <div class="detail-row">
                                <strong>Client:</strong> ${order.customer_name}
                            </div>
                            ${order.table_number ? 
                            `<div class="detail-row">
                                <strong>Table:</strong> ${order.table_number}
                            </div>` : ''}
                            <div class="detail-row">
                                <strong>Statut:</strong> 
                                <span class="status status-${order.order_status}">
                                    ${order.order_status === 'pending' ? 'En attente' : 
                                     order.order_status === 'preparing' ? 'En préparation' : 
                                     order.order_status === 'ready' ? 'Prête' : 
                                     order.order_status === 'served' ? 'Servie' : 'Annulée'}
                                </span>
                            </div>
                            <div class="detail-row">
                                <strong>Montant total:</strong> ${order.total_amount} DA
                            </div>
                            <div class="detail-row">
                                <strong>Créée à:</strong> ${order.created_at}
                            </div>
                            ${order.notes ? 
                            `<div class="detail-row">
                                <strong>Notes:</strong>
                                <p style="padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                    ${order.notes}
                                </p>
                            </div>` : ''}
                            ${itemsHtml}
                        </div>`;
                    
                    $('#orderDetailsBody').html(details);
                    $('#orderDetailsModal').show();
                } else {
                    alert('Erreur: ' + response.message);
                }
            },
            error: function() {
                alert('Erreur de connexion au serveur');
            }
        });
    });

    // Update order status when buttons are clicked
    $(document).on('click', '.btn-start-order', function() {
        updateOrderStatus($(this).data('id'), 'preparing');
    });

    $(document).on('click', '.btn-ready-order', function() {
        updateOrderStatus($(this).data('id'), 'ready');
    });

    $(document).on('click', '.btn-serve-order', function() {
        updateOrderStatus($(this).data('id'), 'served');
    });

    $(document).on('click', '.btn-cancel-order', function() {
        const orderId = $(this).data('id');
        
        if (confirm('Êtes-vous sûr d\'annuler cette commande ?')) {
            const reason = prompt('Raison de l\'annulation :');
            if (reason !== null) {
                updateOrderStatus(orderId, 'cancelled', reason);
            }
        }
    });

    function updateOrderStatus(orderId, status, reason = '') {
        $.ajax({
            url: '../php/update_order_status.php',
            type: 'POST',
            data: {
                order_id: orderId,
                status: status,
                reason: reason
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Statut mis à jour avec succès');
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

    // Nouvelle commande
    function showNewOrderModal() {
    $.ajax({
        url: '../php/new_order_form.php',
        type: 'GET',
        dataType: 'html',
        success: function(response) {
            $('#newOrderBody').html(response);
            $('#newOrderModal').show();
        },
        error: function() {
            alert('Erreur de chargement du formulaire');
        }
    });
}

    // Fermer les modals
    $('.close-modal').click(function() {
        $('.modal').hide();
    });

    $(window).click(function(event) {
        if ($(event.target).hasClass('modal')) {
            $('.modal').hide();
        }
    });

    // Imprimer les commandes de la cuisine
    function printKitchenOrders() {
        window.open('print_kitchen_orders.php', '_blank');
    }

    // Auto-refresh toutes les 30 secondes
    setTimeout(function() {
        location.reload();
    }, 30000);
});

    </script>
</body>
</html>
<?php $conn->close(); ?>