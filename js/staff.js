$(document).ready(function() {
    // Enregistrement de l'arrivée du client
    $(document).on('click', '.btn-checkin', function() {
        const reservationId = $(this).data('id');
        
        if (confirm('Confirmer l\'arrivée du client ?')) {
            $.ajax({
                url: '../php/staff_checkin.php',
                type: 'POST',
                data: { id: reservationId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Arrivée du client enregistrée avec succès');
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
    
    // Mise à jour des statistiques du jour
    function updateTodayStats() {
        const today = new Date().toISOString().split('T')[0];
        
        $.ajax({
            url: '../php/get_today_stats.php',
            type: 'GET',
            data: { date: today },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#total-today').text(response.total);
                    $('#confirmed-today').text(response.confirmed);
                    $('#pending-today').text(response.pending);
                }
            }
        });
    }
    
    // Mise à jour des statistiques toutes les 30 secondes
    updateTodayStats();
    setInterval(updateTodayStats, 30000);
    
    // Mise à jour du tableau toutes les minutes
    setInterval(function() {
        $.ajax({
            url: '../php/get_today_reservations.php',
            type: 'GET',
            dataType: 'html',
            success: function(response) {
                $('.data-table tbody').html(response);
            }
        });
    }, 60000);
});
