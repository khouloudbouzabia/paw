<?php
session_start();
require_once '../php/db_connection.php';

// معالجة حذف موظف
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $staff_id = (int)$_GET['delete'];
    
    // لا يمكن حذف نفسه
    if ($staff_id != $_SESSION['user_id']) {
        $sql = "DELETE FROM users WHERE id = ? AND role = 'staff'";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $staff_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    header('Location: staff.php');
    exit;
}

// معالجة إضافة/تعديل موظف
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $username = trim($_POST['username']);
    $role = 'staff'; // كل الموظفين من هذا النوع
    
    if ($id > 0) {
        // تحديث
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE users SET full_name=?, email=?, phone=?, username=?, password=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("sssssi", $full_name, $email, $phone, $username, $password, $id);
            }
        } else {
            $sql = "UPDATE users SET full_name=?, email=?, phone=?, username=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ssssi", $full_name, $email, $phone, $username, $id);
            }
        }
    } else {
        // إضافة جديد
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (full_name, email, phone, username, password, role) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssssss", $full_name, $email, $phone, $username, $password, $role);
        }
    }
    
    if ($stmt && $stmt->execute()) {
        $success_message = $id > 0 ? "Staff updated successfully" : "Staff added successfully";
        header('Location: staff.php?success=' . urlencode($success_message));
        exit;
    }
    
    if ($stmt) $stmt->close();
}

// جلب الموظف للتعديل
$edit_staff = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $staff_id = (int)$_GET['edit'];
    $sql = "SELECT * FROM users WHERE id = ? AND role = 'staff'";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $staff_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $edit_staff = $result->fetch_assoc();
        }
        $stmt->close();
    }
}

// جلب جميع الموظفين
$sql = "SELECT * FROM users WHERE role = 'staff' ORDER BY created_at DESC";
$staff_result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion du personnel - Café Al Raha</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .staff-roster {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .staff-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s;
        }
        
        .staff-card:hover {
            transform: translateY(-5px);
        }
        
        .staff-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .staff-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: white;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: #667eea;
        }
        
        .staff-body {
            padding: 20px;
        }
        
        .staff-info {
            margin-bottom: 15px;
        }
        
        .staff-info p {
            margin: 5px 0;
            color: #555;
        }
        
        .staff-info i {
            width: 20px;
            color: #3498db;
            margin-right: 10px;
        }
        
        .staff-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .shift-schedule {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .shift-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .shift-table th {
            background: #f8f9fa;
            padding: 10px;
            text-align: left;
        }
        
        .shift-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .shift-badge {
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .shift-morning {
            background: #ffeaa7;
            color: #e17055;
        }
        
        .shift-evening {
            background: #a29bfe;
            color: white;
        }
        
        .shift-night {
            background: #2d3436;
            color: white;
        }
    </style>
</head>
<body class="admin-page">
    <nav class="admin-navbar">
        <div class="container">
            <h1><i class="fas fa-user-tie"></i> Gestion du personnel - Café Al Raha</h1>
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
                <li><a href="staff.php" class="active"><i class="fas fa-user-tie"></i> Personnel</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Rapports</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Paramètres</a></li>
            </ul>
            </aside>
        
        <main class="admin-content">
            <h2>Gestion du personnel</h2>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Formulaire Ajouter/Modifier -->
            <div class="admin-section" style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
                <h3 style="margin-top: 0;"><?php echo $edit_staff ? 'Modifier le membre du personnel' : 'Ajouter un nouveau membre'; ?></h3>
                
                <form method="POST" action="" class="admin-form">
                    <?php if ($edit_staff): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_staff['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nom complet *</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($edit_staff['full_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($edit_staff['email'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Téléphone *</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($edit_staff['phone'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Nom d'utilisateur *</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($edit_staff['username'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php echo $edit_staff ? 'Nouveau mot de passe (laisser vide pour ne pas changer)' : 'Mot de passe *'; ?></label>
                            <input type="password" name="password" <?php echo $edit_staff ? '' : 'required'; ?>>
                        </div>
                        <div class="form-group">
                            <label>Confirmer le mot de passe</label>
                            <input type="password" name="password_confirmation">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i> <?php echo $edit_staff ? 'Mettre à jour' : 'Ajouter'; ?>
                        </button>
                        
                        <?php if ($edit_staff): ?>
                            <a href="staff.php" class="btn-cancel">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Planning des shifts -->
            <div class="shift-schedule">
                <h3>Planning des shifts (Cette semaine)</h3>
                <table class="shift-table">
                    <thead>
                        <tr>
                            <th>Jour</th>
                            <th>Shift matin</th>
                            <th>Shift soir</th>
                            <th>Shift nuit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
                        $shifts = [
                            'matin' => ['08:00', '16:00'],
                            'soir' => ['16:00', '00:00'],
                            'nuit' => ['00:00', '08:00']
                        ];
                        
                        foreach ($days as $day):
                        ?>
                        <tr>
                            <td><strong><?php echo $day; ?></strong></td>
                            <td>
                                <span class="shift-badge shift-morning">08:00 - 16:00</span>
                                <select class="staff-select" data-day="<?php echo strtolower($day); ?>" data-shift="morning">
                                    <option value="">Sélectionner</option>
                                    <?php
                                    mysqli_data_seek($staff_result, 0);
                                    while($staff = $staff_result->fetch_assoc()):
                                    ?>
                                    <option value="<?php echo $staff['id']; ?>">
                                        <?php echo htmlspecialchars($staff['full_name']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </td>
                            <td>
                                <span class="shift-badge shift-evening">16:00 - 00:00</span>
                                <select class="staff-select" data-day="<?php echo strtolower($day); ?>" data-shift="evening">
                                    <option value="">Sélectionner</option>
                                    <?php
                                    mysqli_data_seek($staff_result, 0);
                                    while($staff = $staff_result->fetch_assoc()):
                                    ?>
                                    <option value="<?php echo $staff['id']; ?>">
                                        <?php echo htmlspecialchars($staff['full_name']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </td>
                            <td>
                                <span class="shift-badge shift-night">00:00 - 08:00</span>
                                <select class="staff-select" data-day="<?php echo strtolower($day); ?>" data-shift="night">
                                    <option value="">Sélectionner</option>
                                    <?php
                                    mysqli_data_seek($staff_result, 0);
                                    while($staff = $staff_result->fetch_assoc()):
                                    ?>
                                    <option value="<?php echo $staff['id']; ?>">
                                        <?php echo htmlspecialchars($staff['full_name']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div style="margin-top: 20px; text-align: right;">
                    <button id="saveSchedule" class="btn-action">
                        <i class="fas fa-save"></i> Enregistrer le planning
                    </button>
                </div>
            </div>
            
            <!-- Liste du personnel -->
            <div class="admin-section" style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <h3 style="margin-top: 0;">Liste du personnel (<?php echo $staff_result->num_rows; ?>)</h3>
                
                <div class="staff-roster">
                    <?php 
                    mysqli_data_seek($staff_result, 0);
                    while($staff = $staff_result->fetch_assoc()):
                    ?>
                    <div class="staff-card">
                        <div class="staff-header">
                            <div class="staff-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <h4 style="margin: 0; color: white;"><?php echo htmlspecialchars($staff['full_name']); ?></h4>
                            <p style="margin: 5px 0 0 0; opacity: 0.9;"><?php echo $staff['role'] == 'admin' ? 'Administrateur' : 'Staff'; ?></p>
                        </div>
                        
                        <div class="staff-body">
                            <div class="staff-info">
                                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($staff['email']); ?></p>
                                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($staff['phone']); ?></p>
                                <p><i class="fas fa-user-circle"></i> @<?php echo htmlspecialchars($staff['username']); ?></p>
                                <p><i class="fas fa-calendar-plus"></i> Membre depuis <?php echo date('d/m/Y', strtotime($staff['created_at'])); ?></p>
                            </div>
                            
                            <div class="staff-actions">
                                <a href="staff.php?edit=<?php echo $staff['id']; ?>" class="btn-action btn-edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <?php if ($staff['id'] != $_SESSION['user_id']): ?>
                                <button class="btn-action btn-delete" 
                                        data-id="<?php echo $staff['id']; ?>" 
                                        data-name="<?php echo htmlspecialchars($staff['full_name']); ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                                
                                <button class="btn-action btn-view-staff" data-id="<?php echo $staff['id']; ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    
                    <?php if ($staff_result->num_rows == 0): ?>
                        <div class="no-data" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                            <i class="fas fa-user-slash" style="font-size: 48px; color: #ccc;"></i>
                            <p>Aucun membre du personnel</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Modal de suppression -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Confirmer la suppression</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer "<span id="staffName"></span>" ?</p>
                <p>Cette action est irréversible.</p>
            </div>
            <div class="modal-footer">
                <button id="confirmDelete" class="btn-danger">Oui, supprimer</button>
                <button class="btn-cancel">Annuler</button>
            </div>
        </div>
    </div>
    
    <!-- Modal de détails -->
    <div id="staffDetailsModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>Détails du membre</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body" id="staffDetailsContent">
                <!-- Les détails seront chargés ici -->
            </div>
        </div>
    </div>

    <script>
$(document).ready(function() {
    // Enregistrer le planning
    $('#saveSchedule').click(function() {
        const schedule = {}; // تعريف متغير الجدول

        // جمع البيانات من القوائم المنسدلة
        $('.staff-select').each(function() {
            const day = $(this).data('day');  // اليوم
            const shift = $(this).data('shift');  // نوع الوردية
            const staffId = $(this).val();  // ID الموظف

            if (!schedule[day]) schedule[day] = {};  // إذا لم يكن هناك يوم، أضفه
            schedule[day][shift] = staffId;  // إضافة بيانات الموظف للوردية
        });

        // إرسال البيانات باستخدام AJAX
        $.ajax({
            url: '../php/save_schedule.php',
            type: 'POST',
            data: { schedule: JSON.stringify(schedule) },  // إرسال المتغير الصحيح
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Planning enregistré avec succès');
                } else {
                    alert('Erreur: ' + response.message);
                }
            },
            error: function() {
                alert('Erreur de connexion au serveur');
            }
        });
    });

    // Suppression
    $('.btn-delete').click(function() {
        const staffId = $(this).data('id');
        const staffName = $(this).data('name');

        $('#staffName').text(staffName);
        $('#confirmDelete').data('id', staffId);
        $('#deleteModal').show();
    });

    $('#confirmDelete').click(function() {
        const staffId = $(this).data('id');
        window.location.href = 'staff.php?delete=' + staffId;
    });

    // Voir les détails
    $('.btn-view-staff').click(function() {
        const staffId = $(this).data('id');

        $.ajax({
            url: '../php/get_staff_details.php',
            type: 'GET',
            data: { id: staffId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const staff = response.staff;
                    const performance = response.performance;

                    let content = `
                        <div class="staff-details">
                            <div class="staff-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                                <div style="display: flex; align-items: center; gap: 20px;">
                                    <div style="width: 80px; height: 80px; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; font-size: 32px; color: #667eea;">
                                    <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <h3 style="margin: 0; color: white;">${staff.full_name}</h3>
                                        <p style="margin: 5px 0 0 0; opacity: 0.9;">${staff.user_type == 'admin' ? 'Administrateur' : 'Staff'}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="staff-info-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                                <div class="info-card" style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                                    <h4 style="margin-top: 0;"><i class="fas fa-info-circle"></i> Informations</h4>
                                    <p><strong>Email:</strong> ${staff.email}</p>
                                    <p><strong>Téléphone:</strong> ${staff.phone}</p>
                                    <p><strong>Nom d'utilisateur:</strong> @${staff.username}</p>
                                    <p><strong>Membre depuis:</strong> ${staff.created_at}</p>
                                </div>

                                <div class="performance-card" style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                                    <h4 style="margin-top: 0;"><i class="fas fa-chart-line"></i> Performance</h4>
                                    <p><strong>Réservations traitées:</strong> ${performance.total_reservations || 0}</p>
                                    <p><strong>Moyenne satisfaction:</strong> ${performance.avg_rating || 'N/A'}</p>
                                    <p><strong>Dernière connexion:</strong> ${staff.last_login || 'Jamais'}</p>
                                    <p><strong>Statut:</strong> <span class="status ${staff.is_active ? 'status-available' : 'status-unavailable'}">${staff.is_active ? 'Actif' : 'Inactif'}</span></p>
                                </div>
                            </div>

                            <div class="staff-actions" style="display: flex; gap: 10px; justify-content: center;">
                                <button class="btn-action" onclick="window.location.href='staff.php?edit=${staff.id}'">
                                    <i class="fas fa-edit"></i> Modifier
                                </button>
                                <button class="btn-action" onclick="sendStaffMessage('${staff.email}')">
                                    <i class="fas fa-envelope"></i> Contacter
                                </button>
                            </div>
                        </div>
                    `;

                    $('#staffDetailsContent').html(content);
                    $('#staffDetailsModal').show();
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

    $(window).click(function(event) {
        if ($(event.target).hasClass('modal')) {
            $('.modal').hide();
        }
    });

    // Validation du formulaire
    $('form').submit(function() {
        const password = $('input[name="password"]').val();
        const confirm = $('input[name="password_confirmation"]').val();

        if (password && password !== confirm) {
            alert('Les mots de passe ne correspondent pas');
            return false;
        }

        return true;
    });
});

// دالة إرسال الرسالة
function sendStaffMessage(email) {
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
}
</script>

</body>
</html>
<?php 
if (isset($staff_result)) $staff_result->free();
$conn->close();
?>