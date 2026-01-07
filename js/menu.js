$(document).ready(function() {

    // Fonction pour récupérer les produits depuis PHP via AJAX
    function fetchMenuItems(callback) {
        $.ajax({
            url: 'php/get_menu.php', // Page PHP qui retourne les produits au format JSON
            method: 'GET', // Méthode HTTP GET
            dataType: 'json', // Type de données attendu
            success: function(response) {
                console.log(response);
                if (response.success) {
                    callback(response.data); // Si succès, renvoyer les données au callback
                } else {
                    console.error('Erreur lors de la récupération du menu :', response.message);
                    callback([]); // En cas d'erreur, renvoyer un tableau vide
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX :', error);
                callback([]); // En cas d'erreur AJAX, renvoyer un tableau vide
            }
        });
    }

    // Fonction pour afficher les produits dans le DOM
    function displayMenuItems(items) {
        const $menuContainer = $('.menu-items'); // Sélecteur du conteneur des produits
        $menuContainer.empty(); // Vider le conteneur avant d'afficher les nouveaux éléments
        
        const template = $('#menuItemTemplate').html(); // Récupérer le modèle HTML

        items.forEach(item => {
            let $item = $(template); // Créer un élément à partir du modèle

            // Remplir les informations du produit
            $item.attr('data-category', item.category); // Attribuer la catégorie
            $item.find('.item-name').text(item.name); // Nom du produit
            $item.find('.item-description').text(item.description); // Description
            $item.find('.item-price').text(parseFloat(item.price).toFixed(2) + ' €'); // Prix formaté
            $item.find('img').attr('src', item.image).attr('alt', item.name); // Image et alt

            // Ajouter un badge si le produit est indisponible
            if (!item.available) {
                $item.find('.item-badge').text('Indisponible').addClass('unavailable');
            }

            // Ajouter l'élément au conteneur
            $menuContainer.append($item);
        });
    }

    // Fonction pour filtrer les produits par catégorie
    $('.category-btn').click(function() {
        const category = $(this).data('category'); // Récupérer la catégorie sélectionnée
        
        // Mettre à jour le bouton actif
        $('.category-btn').removeClass('active');
        $(this).addClass('active');

        // Filtrer les produits selon la catégorie
        let filteredItems = menuData;
        if (category !== 'all') {
            filteredItems = menuData.filter(item => item.category === category);
        }
        displayMenuItems(filteredItems);


        // Afficher les produits filtrés
        displayMenuItems(filteredItems);
    });

    // Charger les produits depuis la base de données via AJAX au chargement de la page
    let menuData = []; // Tableau global pour stocker les produits
    fetchMenuItems(function(data) {
        menuData = data; // Stocker les produits récupérés
        displayMenuItems(menuData); // Afficher les produits
    });
});
