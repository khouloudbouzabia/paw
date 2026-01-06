$(document).ready(function() {

    // Gestion du formulaire de contact
    $('#contactForm').submit(function(e) {
        // Empêcher l’envoi par défaut du formulaire
        e.preventDefault();
        
        // Récupération des données du formulaire
        const formData = {
            name: $('#contactName').val(),       // Nom de l’expéditeur
            email: $('#contactEmail').val(),     // Adresse e-mail
            subject: $('#contactSubject').val(), // Sujet du message
            message: $('#contactMessage').val()  // Contenu du message
        };

        // Vérification de la validité du formulaire
        if (!name || !email || !subject || !message) {
            $('#contactMessageResponse')
                .removeClass('success')
                .addClass('error')
                .show()
                .text('Veuillez remplir tous les champs.');
            return;
        }

        // Envoi des données au serveur via AJAX
        $.ajax({
            url: 'php/process_contact.php', // Fichier de traitement côté serveur
            type: 'POST',                   // Type de requête
            data: formData,                 // Données envoyées
            dataType: 'json',               // Type de données reçues
            success: function(response) {
                const messageDiv = $('#contactMessageResponse');

                // Supprimer les anciennes classes et ajouter la classe appropriée
                messageDiv
                    .removeClass('success error')
                    .addClass(response.success ? 'success' : 'error')
                    .show()

                // Afficher le message de succès ou d’erreur
                      .text(response.message);
                
                // En cas de succès
                if (response.success) {
                    $('#contactForm')[0].reset();// Réinitialiser le formulaire

                    // Masquer le message après 6 secondes
                    setTimeout(() => {
                        messageDiv.fadeOut();
                    }, 6000);
                }
            },
            error: function() {
                // En cas d’erreur de connexion avec le serveur
                $('#contactMessageResponse')
                    .removeClass('success')
                    .addClass('error')
                    .show()
                    .text('Une erreur de communication avec le serveur est survenue. Veuillez réessayer.');
            }
        });
    });
});
