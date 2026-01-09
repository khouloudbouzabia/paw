<?php
session_start();
require_once '../php/db_connection.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// التحقق من نوع المستخدم (فقط الإداري يمكنه إدارة القائمة)
$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'admin';
if ($user_type !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// معالجة حذف منتج
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    
    // استخدام Prepared Statement للحذف
    $delete_sql = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $product_id);
        
        if ($stmt->execute()) {
            $success_message = "Produit supprimé avec succès";
        } else {
            $error_message = "Erreur lors de la suppression du produit : " . $stmt->error;
        }
        $stmt->close();
    }
    
    header('Location: menu.php?message=' . urlencode($success_message ?? $error_message));
    exit;
}

// معالجة إضافة/تعديل منتج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $category = trim($_POST['category']);
    $available = isset($_POST['available']) ? 1 : 0;
    
    // معالجة رفع الصورة
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $upload_dir = '../uploads/products/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['image']['name']);
            $target_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_path = 'uploads/products/' . $file_name;
            }
        }
    }
    
    if ($id > 0) {
        // تحديث المنتج
        if ($image_path) {
            $sql = "UPDATE products SET name=?, description=?, price=?, category=?, available=?, image=?, updated_at=NOW() WHERE id=?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ssdsisi", $name, $description, $price, $category, $available, $image_path, $id);
            }
        } else {
            $sql = "UPDATE products SET name=?, description=?, price=?, category=?, available=?, updated_at=NOW() WHERE id=?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ssdsii", $name, $description, $price, $category, $available, $id);
            }
        }
    } else {
        // إضافة منتج جديد
        $sql = "INSERT INTO products (name, description, price, category, available, image) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssdsis", $name, $description, $price, $category, $available, $image_path);
        }
    }
    
    if ($stmt && $stmt->execute()) {
        $success_message = $id > 0 ? "Produit mis à jour avec succès" : "Produit ajouté avec succès";
    } elseif ($stmt) {
        $error_message = "Erreur : " . $stmt->error;
    } else {
        $error_message = "Erreur de préparation de la requête";
    }
    
    if (isset($stmt)) {
        $stmt->close();
    }
    
    header('Location: menu.php?message=' . urlencode($success_message ?? $error_message));
    exit;
}

// جلب منتج للتعديل
$edit_product = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $product_id = (int)$_GET['edit'];
    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $edit_product = $result->fetch_assoc();
        }
        $stmt->close();
    }
}

// جلب جميع المنتجات
$sql = "SELECT * FROM products ORDER BY category, name";
$products_result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion du menu - Café Al Raha</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="admin-page">
    <!-- Barre de navigation -->
    <nav class="admin-navbar">
        <div class="container">
            <h1><i class="fas fa-utensils"></i> Gestion du menu - Café Al Raha</h1>
            <div class="user-info">
                <span>Bonjour, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </div>
    </nav>

    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
                <li><a href="reservation.php"><i class="fas fa-calendar-check"></i> Gestion des réservations</a></li>
                <li><a href="menu.php" class="active"><i class="fas fa-utensils"></i> Gestion du menu</a></li>
                <li><a href="clients.php"><i class="fas fa-users"></i> Gestion des clients</a></li>
                <li><a href="staff.php"><i class="fas fa-user-tie"></i> Personnel</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Rapports</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Paramètres</a></li>
            </ul>
        </aside>

        <!-- Contenu principal -->
        <main class="admin-content">
            <?php if (isset($_GET['message'])): ?>
                <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($_GET['message']); ?>
                </div>
            <?php endif; ?>

            <div class="admin-section" style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
                <h2 style="margin-top: 0; color: #2c3e50;"><?php echo $edit_product ? 'Modifier le produit' : 'Ajouter un nouveau produit'; ?></h2>
                
                <form method="POST" action="" class="admin-form" enctype="multipart/form-data">
                    <?php if ($edit_product): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_product['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
                        <div class="form-group">
                            <label>Nom du produit *</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($edit_product['name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Prix (DA) *</label>
                            <input type="number" name="price" step="0.01" min="0" value="<?php echo $edit_product['price'] ?? ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
                        <div class="form-group">
                            <label>Catégorie *</label>
                            <select name="category" required>
                                <option value="">Sélectionnez la catégorie</option>
                                <option value="coffee" <?php echo ($edit_product['category'] ?? '') == 'coffee' ? 'selected' : ''; ?>>Café</option>
                                <option value="tea" <?php echo ($edit_product['category'] ?? '') == 'tea' ? 'selected' : ''; ?>>Thé</option>
                                <option value="pastry" <?php echo ($edit_product['category'] ?? '') == 'pastry' ? 'selected' : ''; ?>>Pâtisseries</option>
                                <option value="sandwich" <?php echo ($edit_product['category'] ?? '') == 'sandwich' ? 'selected' : ''; ?>>Sandwichs</option>
                                <option value="dessert" <?php echo ($edit_product['category'] ?? '') == 'dessert' ? 'selected' : ''; ?>>Desserts</option>
                                <option value="boisson" <?php echo ($edit_product['category'] ?? '') == 'boisson' ? 'selected' : ''; ?>>Boissons</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Disponibilité</label>
                            <label class="checkbox-label" style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
                                <input type="checkbox" name="available" value="1" <?php echo ($edit_product['available'] ?? 1) ? 'checked' : ''; ?>>
                                <span>Disponible</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>Description</label>
                        <textarea name="description" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>Image du produit</label>
                        <input type="file" name="image" accept="image/*" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <?php if ($edit_product && !empty($edit_product['image'])): ?>
                            <div class="current-image" style="margin-top: 10px;">
                                <p>Image actuelle :</p>
                                <img src="../<?php echo htmlspecialchars($edit_product['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($edit_product['name']); ?>" 
                                     style="max-width: 200px; border-radius: 5px; margin-top: 10px;">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-actions" style="display: flex; gap: 15px; margin-top: 20px;">
                        <button type="submit" class="btn-save" style="background: #27ae60; color: white; padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fas fa-save"></i> <?php echo $edit_product ? 'Enregistrer les modifications' : 'Ajouter le produit'; ?>
                        </button>
                        
                        <?php if ($edit_product): ?>
                            <a href="menu.php" class="btn-cancel" style="background: #95a5a6; color: white; padding: 12px 25px; border-radius: 4px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                                <i class="fas fa-times"></i> Annuler
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="admin-section" style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <h2 style="margin-top: 0; color: #2c3e50;">Liste des produits</h2>
                
                <!-- Filtres -->
                <div class="filters-container">
                    <form method="GET" action="" id="filterForm">
                        <div class="filter-row">
                            <div class="form-group" style="flex: 2;">
                                <input type="text" id="searchProducts" placeholder="Rechercher un produit par nom ou description..." 
                                       style="width: 100%;">
                            </div>
                            <div class="form-group">
                                <select id="categoryFilter">
                                    <option value="all">Toutes les catégories</option>
                                    <option value="coffee">Café</option>
                                    <option value="tea">Thé</option>
                                    <option value="pastry">Pâtisseries</option>
                                    <option value="sandwich">Sandwichs</option>
                                    <option value="dessert">Desserts</option>
                                    <option value="boisson">Boissons</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <select id="availabilityFilter">
                                    <option value="all">Tous les statuts</option>
                                    <option value="available">Disponible</option>
                                    <option value="unavailable">Indisponible</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nom du produit</th>
                                <th>Catégorie</th>
                                <th>Prix (DA)</th>
                                <th>Disponibilité</th>
                                <th>Date d'ajout</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($products_result, 0);
                            $counter = 1; 
                            ?>
                            
                            <?php if ($products_result->num_rows > 0): ?>
                                <?php while($product = $products_result->fetch_assoc()): ?>
                                <tr data-id="<?php echo $product['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                    data-description="<?php echo htmlspecialchars($product['description'] ?? ''); ?>"
                                    data-price="<?php echo number_format($product['price'], 2); ?>"
                                    data-category="<?php echo $product['category']; ?>"
                                    data-available="<?php echo $product['available']; ?>"
                                    data-image="<?php echo htmlspecialchars($product['image'] ?? ''); ?>"
                                    data-created="<?php echo date('d/m/Y', strtotime($product['created_at'])); ?>"
                                    data-updated="<?php echo !empty($product['updated_at']) ? date('d/m/Y', strtotime($product['updated_at'])) : 'Jamais'; ?>">
                                    <td><?php echo $counter++; ?></td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td>
                                        <?php 
                                        $category_names = [
                                            'coffee' => 'Café',
                                            'tea' => 'Thé',
                                            'pastry' => 'Pâtisseries',
                                            'sandwich' => 'Sandwichs',
                                            'dessert' => 'Desserts',
                                            'boisson' => 'Boissons'
                                        ];
                                        echo $category_names[$product['category']] ?? $product['category'];
                                        ?>
                                    </td>
                                    <td><?php echo number_format($product['price'], 2); ?></td>
                                    <td>
                                        <span class="status <?php echo $product['available'] ? 'status-available' : 'status-unavailable'; ?>">
                                            <?php echo $product['available'] ? 'Disponible' : 'Indisponible'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($product['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <!-- زر Voir الجديد -->
                                            <button class="btn-action btn-view" 
                                                    data-id="<?php echo $product['id']; ?>">
                                                <i class="fas fa-eye"></i> Voir
                                            </button>
                                            
                                            <a href="menu.php?edit=<?php echo $product['id']; ?>" class="btn-action btn-edit">
                                                <i class="fas fa-edit"></i> Modifier
                                            </a>
                                            
                                            <button class="btn-action btn-delete" 
                                                    data-id="<?php echo $product['id']; ?>" 
                                                    data-name="<?php echo htmlspecialchars($product['name']); ?>">
                                                <i class="fas fa-trash"></i> Supprimer
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="no-data">
                                        <i class="fas fa-inbox"></i>
                                        <p>Aucun produit disponible</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal pour voir les détails du produit -->
    <div id="viewProductModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="margin: 0;">Détails du produit</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div class="product-details-container">
                    <div class="product-image-section">
                        <div class="product-image-wrapper">
                            <img id="viewProductImage" src="" alt="Image du produit">
                            <div class="image-placeholder" id="imagePlaceholder" style="display: none; text-align: center; color: #7f8c8d;">
                                <i class="fas fa-image" style="font-size: 48px; margin-bottom: 10px;"></i>
                                <p>Aucune image disponible</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="product-info-section">
                        <div class="product-header">
                            <h2 id="viewProductName"></h2>
                            <span class="product-category-badge" id="viewProductCategoryBadge"></span>
                            <span class="product-status-badge" id="viewProductStatusBadge"></span>
                        </div>
                        
                        <div class="product-meta">
                            <div class="meta-item">
                                <i class="fas fa-tag"></i>
                                <span>Prix: <strong id="viewProductPrice"></strong> DA</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-calendar-plus"></i>
                                <span>Ajouté le: <strong id="viewProductCreated"></strong></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-calendar-edit"></i>
                                <span>Dernière modification: <strong id="viewProductUpdated"></strong></span>
                            </div>
                        </div>
                        
                        <div class="product-description">
                            <h4><i class="fas fa-align-left"></i> Description</h4>
                            <p id="viewProductDescription" style="line-height: 1.6; color: #555; margin: 0;"></p>
                            <div id="noDescription" style="display: none; color: #999; font-style: italic; margin-top: 10px;">
                                Aucune description disponible pour ce produit.
                            </div>
                        </div>
                        
                        <div class="product-actions" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; display: flex; gap: 15px;">
                            <button class="btn-action btn-edit-from-view" style="background: #f39c12; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                                <i class="fas fa-edit"></i> Modifier ce produit
                                </button>
                            <button class="btn-action btn-close-view" style="background: #95a5a6; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                                <i class="fas fa-times"></i> Fermer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3 style="margin: 0;">Confirmer la suppression</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer le produit "<span id="productName"></span>" ?</p>
                <p>Cette action est irréversible.</p>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 20px;">
                <button id="confirmDelete" class="btn-danger" style="background: #e74c3c; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
                    Oui, supprimer
                </button>
                <button class="btn-cancel" style="background: #95a5a6; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
                    Annuler
                </button>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        
        $(document).on('click', '.btn-view', function() {
            const row = $(this).closest('tr');
            const productId = row.data('id');
            const productName = row.data('name');
            const productDescription = row.data('description');
            const productPrice = row.data('price');
            const productCategory = row.data('category');
            const productAvailable = row.data('available');
            const productImage = row.data('image');
            const productCreated = row.data('created');
            const productUpdated = row.data('updated');
            
            //Cartes interchangeables
            const categoryMap = {
                'coffee': 'Café',
                'tea': 'Thé',
                'pastry': 'Pâtisseries',
                'sandwich': 'Sandwichs',
                'dessert': 'Desserts',
                'boisson': 'Boissons'
            };
            
            const statusMap = {
                '1': { text: 'Disponible', class: 'status-available-badge' },
                '0': { text: 'Indisponible', class: 'status-unavailable-badge' }
            };
            
            // Remplissage des informations dans la fenêtre modale
            $('#viewProductName').text(productName);
            $('#viewProductPrice').text(productPrice);
            $('#viewProductCategoryBadge').text(categoryMap[productCategory] || productCategory);
            $('#viewProductCreated').text(productCreated);
            $('#viewProductUpdated').text(productUpdated);
            
            //Traitement des descriptions
            if (productDescription && productDescription.trim() !== '') {
                $('#viewProductDescription').text(productDescription).show();
                $('#noDescription').hide();
            } else {
                $('#viewProductDescription').hide();
                $('#noDescription').show();
            }
            
            //Traitement des dossiers
            const statusInfo = statusMap[productAvailable];
            $('#viewProductStatusBadge')
                .text(statusInfo.text)
                .removeClass('status-available-badge status-unavailable-badge')
                .addClass(statusInfo.class);
            
            // Traitement d'images
            if (productImage && productImage.trim() !== '') {
                const imagePath = '../' + productImage;
                $('#viewProductImage')
                    .attr('src', imagePath)
                    .attr('alt', productName)
                    .on('error', function() {
                        $(this).hide();
                        $('#imagePlaceholder').show();
                    })
                    .show();
                $('#imagePlaceholder').hide();
            } else {
                $('#viewProductImage').hide();
                $('#imagePlaceholder').show();
            }
            
            // Configurer le bouton d'édition
            $('.btn-edit-from-view').off('click').on('click', function() {
                window.location.href = 'menu.php?edit=' + productId;
            });
            
            // Présentation modale
            $('#viewProductModal').show();
        });
        
        // ====== Liquidation de produits ======
        $('#searchProducts').on('keyup', function() {
            const value = $(this).val().toLowerCase();
            $('.data-table tbody tr').filter(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(value) > -1);
            });
        });
        
        $('#categoryFilter').on('change', filterTable);
        $('#availabilityFilter').on('change', filterTable);
        
        function filterTable() {
            const category = $('#categoryFilter').val();
            const availability = $('#availabilityFilter').val();
            
            $('.data-table tbody tr').each(function() {
                const rowCategory = $(this).data('category');
                const rowAvailable = $(this).data('available').toString();
                
                let categoryMatch = true;
                let availabilityMatch = true;
                
                if (category !== 'all' && rowCategory !== category) {
                    categoryMatch = false;
                }
                
                if (availability !== 'all') {
                    const expectedStatus = availability === 'available' ? '1' : '0';
                    if (rowAvailable !== expectedStatus) {
                        availabilityMatch = false;
                    }
                }
                
                $(this).toggle(categoryMatch && availabilityMatch);
            });
        }
        
        // ====== Gestion des fenêtres modales ======
        $('.close-modal, .btn-cancel, .btn-close-view').click(function() {
            $('.modal').hide();
        });
        
        $(window).click(function(event) {
            if ($(event.target).hasClass('modal')) {
                $('.modal').hide();
            }
        });
        
        // ====== Supprimer le produit ======
        $('.btn-delete').click(function() {
            const productId = $(this).data('id');
            const productName = $(this).data('name');
            
            $('#productName').text(productName);
            $('#confirmDelete').data('id', productId);
            $('#deleteModal').show();
        });
        
        $('#confirmDelete').click(function() {
            const productId = $(this).data('id');
            window.location.href = 'menu.php?delete=' + productId;
        });
    });
    </script>
</body>
</html>
<?php 
if (isset($products_result)) {
    $products_result->free();
}
if (isset($conn)) {
    $conn->close();
}
?>
