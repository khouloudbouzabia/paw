$(document).ready(function() {
    // Fixez la date minimale pour demain
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    const minDate = tomorrow.toISOString().split('T')[0];
    $('#date').attr('min', minDate);

    // Traiter le formulaire de réservation
    $('#quickReservationForm').submit(function(e) {
        e.preventDefault();
        
        // Collecter les données du modèle
        const formData = {
            name: $('#name').val(),
            email: $('#email').val(),
            phone: $('#phone').val(),
            date: $('#date').val(),
            time: $('#time').val(),
            people: $('#people').val(),
            requests: $('#requests').val()
        };

        // Envoyer des données via AJAX
        $.ajax({
            url: 'php/process_reservation.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                const messageDiv = $('#reservationMessage');
                messageDiv.removeClass('success error').addClass(response.success ? 'success' : 'error');
                messageDiv.text(response.message);
                
                if (response.success) {
                    // Réinitialise le formulaire
                    $('#quickReservationForm')[0].reset();
                    //Masquer le message après 5 secondes
                    setTimeout(() => {
                        messageDiv.fadeOut();
                    }, 5000);
                }
            },
            error: function() {
                $('#reservationMessage').removeClass('success').addClass('error')
                    .text('Une erreur s\est produite lors de la connexion au serveur. Veuillez réessayer.');
            }
        });
    });
});