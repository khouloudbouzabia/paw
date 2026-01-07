$(document).ready(function() {
    // Confirmer la réservation
    $(document).on('click', '.btn-confirm', function() {
        const reservationId = $(this).data('id');
        if (confirm('Êtes-vous sûr de vouloir confirmer cette réservation ?')) {
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
                        alert('Une erreur est survenue : ' + response.message);
                    }
                }
            });
        }
    });

    // Supprimer la réservation
    $(document).on('click', '.btn-delete', function() {
        const reservationId = $(this).data('id');
        if (confirm('Êtes-vous sûr de vouloir supprimer cette réservation ? Cette action est irréversible.')) {
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
                        alert('Une erreur est survenue : ' + response.message);
                    }
                }
            });
        }
    });

    // Recherche dans les tableaux
    $('#searchInput').on('keyup', function() {
        const value = $(this).val().toLowerCase();
        $('.data-table tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Filtrer les réservations selon le statut
    $('#statusFilter').on('change', function() {
        const status = $(this).val();
        if (status === 'all') {
            $('.data-table tbody tr').show();
        } else {
            $('.data-table tbody tr').each(function() {
                const rowStatus = $(this).find('.status').text().trim();
                const statusMap = {
                    'قيد الانتظار': 'pending',   // En attente
                    'مؤكد': 'confirmed',          // Confirmée
                    'ملغي': 'cancelled'           // Annulée
                };
                $(this).toggle(statusMap[rowStatus] === status);
            });
        }
    });
});

// ====== Graphiques ======
$(document).ready(function () {

    $.getJSON('../php/get_chart_data.php', function (response) {

        if (!response.success) {
            console.error('Pas de données disponibles pour le graphique');
            return;
        }

        // Graphique des réservations
        const reservationsCanvas = document.getElementById('reservationsChart');
        if (reservationsCanvas) {
            new Chart(reservationsCanvas, {
                type: 'bar', // Meilleur pour une seule journée
                data: {
                    labels: response.reservations.labels,
                    datasets: [{
                        label: 'Nombre de réservations',
                        data: response.reservations.datasets.total,
                        backgroundColor: '#3498db'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: true }
                    }
                }
            });
        }

        // Graphique des produits
        const productsCanvas = document.getElementById('productsChart');
        if (productsCanvas) {
            new Chart(productsCanvas, {
                type: 'pie',
                data: {
                    labels: response.products.labels,
                    datasets: [{
                        data: response.products.data,
                        backgroundColor: [
                            '#c0392b',
                            '#27ae60',
                            '#2980b9',
                            '#f39c12',
                            '#8e44ad'
                        ]
                    }]
                },
                options: {
                    responsive: true
                }
            });
        }

    });

});
