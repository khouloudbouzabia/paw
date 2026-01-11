<?php
require_once '../php/db_connection.php';

// Récupérer les produits depuis la base de données
$sql = "SELECT * FROM products";
$result = $conn->query($sql);
?>

<form id="newOrderForm">
    <div class="form-group">
        <label for="customer_name">Nom du client:</label>
        <input type="text" id="customer_name" name="customer_name" required>
    </div>

    <div class="form-group">
        <label for="product_id">Produit:</label>
        <select id="product_id" name="product_id" required>
            <option value="">Choisissez un produit</option>
            <?php
            while ($product = $result->fetch_assoc()) {
                echo "<option value='" . $product['id'] . "'>" . $product['name'] . "</option>";
            }
            ?>
        </select>
    </div>

    <div class="form-group">
        <label for="quantity">Quantité:</label>
        <input type="number" id="quantity" name="quantity" min="1" required>
    </div>

    <div class="form-group">
        <button type="submit" class="btn-submit">Ajouter la commande</button>
    </div>
</form>

<script>
    // Lors de la soumission du formulaire
    $('#newOrderForm').on('submit', function(e) {
        e.preventDefault(); // Empêcher l'envoi traditionnel du formulaire
        
        // Rassembler les données
        var formData = $(this).serialize();
        
        $.ajax({
            url: '../php/add_new_order.php', // Fichier PHP qui ajoutera la commande
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('Commande ajoutée avec succès');
                    $('#newOrderModal').hide(); // Fermer la fenêtre modale après l'ajout
                    location.reload(); // Rafraîchir la page pour afficher la nouvelle commande
                } else {
                    alert('Erreur: ' + response.message);
                }
            },
            error: function() {
                alert('Erreur de connexion au serveur');
            }
        });
    });
</script>
