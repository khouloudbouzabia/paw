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
                'En attente': 'pending',   // قيد الانتظار
                'Confirmée': 'confirmed',  // مؤكد
                'Annulée': 'cancelled'     // ملغي
            };
            $(this).toggle(statusMap[rowStatus] === status);
        });
    }
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
