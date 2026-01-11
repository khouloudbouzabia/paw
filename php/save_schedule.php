<?php
session_start();
require_once '../php/db_connection.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    if (!isset($_POST['schedule']) || empty($_POST['schedule'])) {
        throw new Exception('Données de planning manquantes');
    }
    
    $schedule_data = json_decode($_POST['schedule'], true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Format de données invalide');
    }
    
    // Début de la transaction
    $conn->begin_transaction();
    
    // Suppression du planning ancien de cette semaine
    $delete_sql = "DELETE FROM staff_schedules WHERE week_start = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    
    // Calcul du début de la semaine actuelle
    $week_start = date('Y-m-d', strtotime('monday this week'));
    $delete_stmt->bind_param("s", $week_start);
    if (!$delete_stmt->execute()) {
        throw new Exception('Erreur lors de la suppression de l\'ancien planning');
    }
    $delete_stmt->close();
    
    // Insertion du nouveau planning
    $insert_sql = "INSERT INTO staff_schedules 
                   (staff_id, day_of_week, shift_type, shift_start, shift_end, week_start) 
                   VALUES (?, ?, ?, ?, ?, ?)";
    
    $insert_stmt = $conn->prepare($insert_sql);
    if (!$insert_stmt) {
        error_log("Erreur lors de la préparation de la requête SQL: " . $conn->error);
        throw new Exception('Erreur lors de la préparation de la requête SQL');
    }

    $days_map = [
        'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
        'thursday' => 4, 'friday' => 5, 'saturday' => 6, 'sunday' => 7
    ];
    
    $shifts_times = [
        'morning' => ['08:00:00', '16:00:00'],
        'evening' => ['16:00:00', '00:00:00'],
        'night' => ['00:00:00', '08:00:00']
    ];
    
    // Vérification des valeurs envoyées et insertion des données
    foreach ($schedule_data as $day => $shifts) {
        foreach ($shifts as $shift_type => $staff_id) {
            if (!empty($staff_id)) {
                // Vérifier si le staff_id existe dans la table des utilisateurs
                $check_user_sql = "SELECT id FROM users WHERE id = ?";
                $check_user_stmt = $conn->prepare($check_user_sql);
                $check_user_stmt->bind_param("i", $staff_id);
                $check_user_stmt->execute();
                $check_user_stmt->store_result();
                if ($check_user_stmt->num_rows === 0) {
                    throw new Exception("L'utilisateur avec l'ID $staff_id n'existe pas");
                }
                $check_user_stmt->close();
                
                $shift_times = $shifts_times[$shift_type];
                
                $insert_stmt->bind_param(
                    "iissss",
                    $staff_id,
                    $days_map[$day],
                    $shift_type,
                    $shift_times[0],
                    $shift_times[1],
                    $week_start
                );
                
                if (!$insert_stmt->execute()) {
                    error_log("Erreur d'exécution de la requête d'insertion: " . $insert_stmt->error);
                    throw new Exception('Erreur lors de l\'insertion du planning');
                }
            }
        }
    }
    
    // Envoi des notifications aux employés
    $notification_sql = "INSERT INTO notifications 
                        (user_id, title, message, type, created_at) 
                        VALUES (?, ?, ?, ?, NOW())";
    
    $notif_stmt = $conn->prepare($notification_sql);
    $title = "Mise à jour du planning";
    $message = "Votre planning pour la semaine a été mis à jour. Veuillez consulter votre profil.";
    
    // Récupération de tous les employés dans le planning
    foreach ($schedule_data as $day => $shifts) {
        foreach ($shifts as $shift_type => $staff_id) {
            if (!empty($staff_id)) {
                $notif_stmt->bind_param("isss", $staff_id, $title, $message, 'schedule');
                if (!$notif_stmt->execute()) {
                    error_log("Erreur d'exécution de la requête d'insertion d'une notification: " . $notif_stmt->error);
                }
            }
        }
    }
    
    // Enregistrement de l'activité
    $activity_sql = "INSERT INTO activities (user_id, action, details, created_at) 
                     VALUES (?, 'update_schedule', ?, NOW())";
    $activity_stmt = $conn->prepare($activity_sql);
    $details = "Mise à jour du planning du personnel pour la semaine du " . $week_start;
    $activity_stmt->bind_param("is", $_SESSION['user_id'], $details);
    if (!$activity_stmt->execute()) {
        error_log("Erreur d'exécution de la requête d'enregistrement d'une activité: " . $activity_stmt->error);
    }
    
    // Confirmation de la transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Planning enregistré avec succès',
        'week_start' => $week_start
    ]);
    
    $insert_stmt->close();
    $notif_stmt->close();
    $activity_stmt->close();
    
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
