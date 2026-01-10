<?php
session_start();
require_once '../php/db_connection.php';

// Vérifier la connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Vérifier le type d'utilisateur
$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'admin';

// Dates de début et de fin du rapport
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$report_type = isset($_GET['type']) ? $_GET['type'] : 'daily';

// Fonction pour récupérer les statistiques
function getStatistics($conn, $start_date, $end_date) {
    $stats = [];

    // Interroger le chiffre d'affaires total à partir de la table order_items
    $sql = "SELECT COALESCE(SUM(oi.total_price), 0) as revenue
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN reservations r ON o.reservation_id = r.id
            WHERE r.reservation_date BETWEEN ? AND ? 
            AND o.payment_status = 'paid'";  // Confirmer que le paiement a été effectué avec succès

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Error in preparing the query: ' . $conn->error); //Afficher un message d'erreur en cas d'échec de la préparation
    }
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['revenue'] = $result->fetch_assoc()['revenue'] ?? 0;
    $stmt->close();

    // Demande de renseignements sur le nombre de nouveaux clients
    $sql = "SELECT COUNT(*) as new_customers 
            FROM customers 
            WHERE created_at BETWEEN ? AND ?";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Error in preparing the query: ' . $conn->error); // Afficher un message d'erreur en cas d'échec de la préparation
    }
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['customers'] = $result->fetch_assoc();
    $stmt->close();
    
   // Demande de renseignements sur les produits les plus vendus
    $sql = "SELECT p.name, p.category, COUNT(*) as sales_count
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at BETWEEN ? AND ? -- Remplacez la date de commande par created_at
        GROUP BY p.id
        ORDER BY sales_count DESC
        LIMIT 10";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Error in preparing the query: ' . $conn->error);// Afficher un message d'erreur en cas d'échec de la préparation
    }
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['top_products'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['top_products'][] = $row;
    }
    $stmt->close();
    
    // Demande de renseignements aux heures de pointe
    $sql = "SELECT HOUR(r.reservation_date) as hour, COUNT(*) as reservation_count
        FROM reservations r
        JOIN orders o ON r.id = o.reservation_id -- Assurez-vous du lien entre la réservation et la demande
        WHERE r.reservation_date BETWEEN ? AND ? 
        GROUP BY HOUR(r.reservation_date)
        ORDER BY reservation_count DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Error in preparing the query: ' . $conn->error); // Afficher un message d'erreur en cas d'échec de la préparation
    }
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['peak_hours'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['peak_hours'][] = $row;
    }
    $stmt->close();
    
    return $stats;
}

// Fonction pour récupérer les données du graphe
function getChartData($conn, $start_date, $end_date, $type = 'daily') {
    $data = [];
    
    if ($type == 'daily') {
        $sql = "SELECT DATE(reservation_date) as date, 
                       COUNT(*) as reservations, 
                       SUM(number_of_people) as people
                FROM reservations 
                WHERE reservation_date BETWEEN ? AND ? 
                GROUP BY DATE(reservation_date)
                ORDER BY date";
    } else if ($type == 'weekly') {
        $sql = "SELECT WEEK(reservation_date) as week,
                       COUNT(*) as reservations,
                       SUM(number_of_people) as people
                FROM reservations 
                WHERE reservation_date BETWEEN ? AND ? 
                GROUP BY WEEK(reservation_date)
                ORDER BY week";
    } else {
        $sql = "SELECT MONTH(reservation_date) as month,
                       COUNT(*) as reservations,
                       SUM(number_of_people) as people
                FROM reservations 
                WHERE reservation_date BETWEEN ? AND ? 
                GROUP BY MONTH(reservation_date)
                ORDER BY month";
    }
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Error in preparing the query: ' . $conn->error); // Afficher un message d'erreur en cas d'échec de la préparation
    }
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $stmt->close();
    return $data;
}

//Obtenir des statistiques
$statistics = getStatistics($conn, $start_date, $end_date);
$chart_data = getChartData($conn, $start_date, $end_date, $report_type);
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports et statistiques - Café Al Raha</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        .report-header {
            background: linear-gradient(135deg, #2c3e50 0%, #4a6491 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .date-range-picker {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
        }
        
        .stat-card.revenue::before { background: #27ae60; }
        .stat-card.reservations::before { background: #3498db; }
        .stat-card.customers::before { background: #9b59b6; }
        .stat-card.people::before { background: #e74c3c; }
        
        .stat-card h3 {
            font-size: 24px;
            margin: 0 0 10px 0;
            color: #2c3e50;
        }
        
        .stat-card p {
            color: #7f8c8d;
            margin: 0;
        }
        
        .stat-card .stat-number {
            font-size: 36px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stat-card.revenue .stat-number { color: #27ae60; }
        .stat-card.reservations .stat-number { color: #3498db; }
        .stat-card.customers .stat-number { color: #9b59b6; }
        .stat-card.people .stat-number { color: #e74c3c; }
        
        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .chart-container h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        .top-products {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .product-rank {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .product-rank:last-child {
            border-bottom: none;
        }
        
        .rank-number {
            width: 30px;
            height: 30px;
            background: #3498db;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .rank-number.top1 { background: #f1c40f; }
        .rank-number.top2 { background: #95a5a6; }
        .rank-number.top3 { background: #d35400; }
        
        .report-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="admin-page">
    <nav class="admin-navbar">
        <div class="container">
            <h1><i class="fas fa-chart-bar"></i> Rapports et statistiques - Café Al Raha</h1>
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
                <li><a href="reservations.php"><i class="fas fa-calendar-check"></i>Gestion des réservations</a></li>
                <li><a href="menu.php"><i class="fas fa-utensils"></i>Gestion de menu</a></li>
                <li><a href="clients.php"><i class="fas fa-users"></i>Gestion des clients</a></li>
                <li><a href="staff.php"><i class="fas fa-user-tie"></i> Personnel</a></li>
                <li><a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> Rapports</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Paramètres</a></li>
            </ul>
        </aside>
        
        <main class="admin-content">
            <div class="report-header">
                <h2 style="margin: 0; color: white;">Rapports et statistiques</h2>
                <p style="margin: 10px 0 0 0; opacity: 0.9;">Analyse des performances et indicateurs clés</p>
                
                <div class="date-range-picker">
                    <form method="GET" action="" id="reportForm">
                        <div class="filter-row" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                            <div class="form-group">
                                <label style="color: white;">Période</label>
                                <select name="type" onchange="updateReportType()" style="background: white;">
                                    <option value="daily" <?php echo $report_type == 'daily' ? 'selected' : ''; ?>>Journalier</option>
                                    <option value="weekly" <?php echo $report_type == 'weekly' ? 'selected' : ''; ?>>Hebdomadaire</option>
                                    <option value="monthly" <?php echo $report_type == 'monthly' ? 'selected' : ''; ?>>Mensuel</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label style="color: white;">Date début</label>
                                <input type="date" name="start_date" value="<?php echo $start_date; ?>" style="background: white;">
                            </div>
                            
                            <div class="form-group">
                                <label style="color: white;">Date fin</label>
                                <input type="date" name="end_date" value="<?php echo $end_date; ?>" style="background: white;">
                            </div>
                            
                            <button type="submit" class="btn-filter" style="background: white; color: #2c3e50;">
                                <i class="fas fa-filter"></i> Générer
                            </button>
                            
                            <div class="report-actions">
                                <button type="button" onclick="exportToPDF()" class="btn-action" style="background: #e74c3c;">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                                <button type="button" onclick="exportToExcel()" class="btn-action" style="background: #27ae60;">
                                    <i class="fas fa-file-excel"></i> Excel
                                </button>
                                <button type="button" onclick="printReport()" class="btn-action" style="background: #3498db;">
                                    <i class="fas fa-print"></i> Imprimer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Fiches statistiques -->
            <div class="stats-grid">
                <div class="stat-card revenue">
                    <h3><i class="fas fa-money-bill-wave"></i> Revenu total</h3>
                    <div class="stat-number"><?php echo number_format($statistics['revenue'], 2); ?> DA</div>
                    <p>Période: <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
                </div>
                
                <div class="stat-card reservations">
                    <h3><i class="fas fa-calendar-check"></i> Réservations</h3>
                    <div class="stat-number"><?php echo $statistics['reservations']['total'] ?? 0; ?></div>
                    <p>
                        Confirmées: <?php echo $statistics['reservations']['confirmed'] ?? 0; ?> | 
                        Annulées: <?php echo $statistics['reservations']['cancelled'] ?? 0; ?>
                    </p>
                </div>
                
                <div class="stat-card customers">
                    <h3><i class="fas fa-user-plus"></i> Nouveaux clients</h3>
                    <div class="stat-number"><?php echo $statistics['customers']['new_customers'] ?? 0; ?></div>
                    <p>Clients inscrits durant la période</p>
                </div>
                
                <div class="stat-card people">
                    <h3><i class="fas fa-users"></i> Personnes servies</h3>
                    <div class="stat-number"><?php echo $statistics['reservations']['total_people'] ?? 0; ?></div>
                    <p>Total des personnes dans toutes les réservations</p>
                </div>
            </div>
            
            <!-- Graphiques -->
            <div class="chart-container">
                <h3>Évolution des réservations</h3>
                <canvas id="reservationsChart" height="100"></canvas>
            </div>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                <div class="chart-container">
                    <h3>Heures de pointe</h3>
                    <canvas id="peakHoursChart" height="200"></canvas>
                </div>
                
                <div class="top-products">
                    <h3>Produits les plus populaires</h3>
                    <div id="topProductsList">
                        <?php if (!empty($statistics['top_products'])): ?>
                            <?php foreach ($statistics['top_products'] as $index => $product): ?>
                            <div class="product-rank">
                                <div class="rank-number <?php echo $index < 3 ? 'top' . ($index + 1) : ''; ?>">
                                    <?php echo $index + 1; ?>
                                </div>
                                <div style="flex: 1;">
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                    <div style="font-size: 12px; color: #7f8c8d;">
                                        <?php echo ucfirst($product['category']); ?>
                                    </div>
                                </div>
                                <div style="font-weight: bold; color: #3498db;">
                                    <?php echo $product['sales_count']; ?> ventes
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: #7f8c8d; padding: 20px;">
                                <i class="fas fa-chart-pie"></i><br>
                                Aucune donnée disponible
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    // Mettre à jour le type de rapport
    function updateReportType() {
        const type = $('select[name="type"]').val();
        const today = new Date();
        
        if (type === 'monthly') {
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            
            $('input[name="start_date"]').val(firstDay.toISOString().split('T')[0]);
            $('input[name="end_date"]').val(lastDay.toISOString().split('T')[0]);
        } else if (type === 'weekly') {
            const firstDay = new Date(today.setDate(today.getDate() - today.getDay()));
            const lastDay = new Date(today.setDate(today.getDate() - today.getDay() + 6));
            
            $('input[name="start_date"]').val(firstDay.toISOString().split('T')[0]);
            $('input[name="end_date"]').val(lastDay.toISOString().split('T')[0]);
        }
    }
    
    // Graphiques
    $(document).ready(function() {
        //Tableau des réservations
        const reservationsData = {
            labels: <?php echo json_encode(array_column($chart_data, $report_type == 'daily' ? 'date' : 'week')); ?>,
            datasets: [{
                label: 'Nombre de réservations',
                data: <?php echo json_encode(array_column($chart_data, 'reservations')); ?>,
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                fill: true,
                tension: 0.4
            }]
        };
        
        const reservationsChart = new Chart(
            document.getElementById('reservationsChart'),
            {
                type: 'line',
                data: reservationsData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                        },
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
        
        // Graphique des heures de pointe
        const peakHoursData = {
            labels: <?php echo json_encode(array_map(function($h) { 
                return $h['hour'] . ':00'; 
            }, $statistics['peak_hours'])); ?>,
            datasets: [{
                label: 'Réservations',
                data: <?php echo json_encode(array_column($statistics['peak_hours'], 'reservation_count')); ?>,
                backgroundColor: [
                    '#e74c3c', '#3498db', '#2ecc71', '#f1c40f',
                    '#9b59b6', '#1abc9c', '#d35400', '#34495e',
                    '#7f8c8d', '#27ae60'
                ]
            }]
        };
        
        const peakHoursChart = new Chart(
            document.getElementById('peakHoursChart'),
            {
                type: 'bar',
                data: peakHoursData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            }
        );
    });
    
    // Exporter au format PDF
    function exportToPDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        // l'adresse
        doc.setFontSize(20);
        doc.text('Rapport Café Al Raha', 105, 20, { align: 'center' });
        
        doc.setFontSize(12);
        doc.text('Période: ' + '<?php echo date("d/m/Y", strtotime($start_date)); ?>' + 
                 ' - ' + '<?php echo date("d/m/Y", strtotime($end_date)); ?>', 105, 30, { align: 'center' });
        
        //Statistiques
        let y = 50;
        doc.setFontSize(14);
        doc.text('Statistiques principales:', 20, y);
        y += 10;
        
        doc.setFontSize(12);
        doc.text('Revenu total: ' + '<?php echo number_format($statistics["revenue"], 2); ?>' + ' DA', 30, y);
        y += 8;
        doc.text('Réservations: ' + '<?php echo $statistics["reservations"]["total"] ?? 0; ?>', 30, y);
        y += 8;
        doc.text('Nouveaux clients: ' + '<?php echo $statistics["customers"]["new_customers"] ?? 0; ?>', 30, y);
        y += 8;
        doc.text('Personnes servies: ' + '<?php echo $statistics["reservations"]["total_people"] ?? 0; ?>', 30, y);
        
        // Enregistrez le fichier
        doc.save('rapport_cafe_al_raha_<?php echo date("Y-m-d"); ?>.pdf');
    }
    
    // Exporter vers Excel
    function exportToExcel() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', 'excel');
        window.location.href = 'export_report.php?' + params.toString();
    }
    
    // Imprimer le rapport
    function printReport() {
        const printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Rapport Café Al Raha</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .header { text-align: center; margin-bottom: 30px; }
                    .stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px; }
                    .stat-item { padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
                    .stat-value { font-size: 24px; font-weight: bold; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f5f5f5; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>Rapport Café Al Raha</h1>
                    <p>Période: <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
                    <p>Généré le: <?php echo date('d/m/Y H:i'); ?></p>
                </div>
                
                <div class="stats">
                    <div class="stat-item">
                        <h3>Revenu total</h3>
                        <div class="stat-value"><?php echo number_format($statistics['revenue'], 2); ?> DA</div>
                    </div>
                    <div class="stat-item">
                        <h3>Réservations</h3>
                        <div class="stat-value"><?php echo $statistics['reservations']['total'] ?? 0; ?></div>
                        <p>Confirmées: <?php echo $statistics['reservations']['confirmed'] ?? 0; ?></p>
                        <p>Annulées: <?php echo $statistics['reservations']['cancelled'] ?? 0; ?></p>
                    </div>
                    <div class="stat-item">
                        <h3>Nouveaux clients</h3>
                        <div class="stat-value"><?php echo $statistics['customers']['new_customers'] ?? 0; ?></div>
                    </div>
                    <div class="stat-item">
                        <h3>Personnes servies</h3>
                        <div class="stat-value"><?php echo $statistics['reservations']['total_people'] ?? 0; ?></div>
                    </div>
                </div>
                
                <?php if (!empty($statistics['top_products'])): ?>
                <h3>Produits les plus populaires</h3>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Produit</th>
                            <th>Catégorie</th>
                            <th>Ventes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($statistics['top_products'] as $index => $product): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo ucfirst($product['category']); ?></td>
                            <td><?php echo $product['sales_count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </body>
            </html>
        `;
        
        const printWindow = window.open('', '_blank');
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    }
    </script>
</body>
</html>
<?php 
$conn->close();
?>