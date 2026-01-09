<?php
/**
 * Formulaire d'ajout/modification de vélo
 * RESAVELO - Système de location de vélos
 */

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/functions_velos.php';

$error = '';
$success = '';
$velo = null;
$isEdit = false;

// Mode édition
if (isset($_GET['id'])) {
    $isEdit = true;
    $velo = getVeloById($pdo, $_GET['id']);
    if (!$velo) {
        header('Location: velos.php');
        exit;
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
    $price = floatval(isset($_POST['price']) ? $_POST['price'] : 0);
    $quantity = intval(isset($_POST['quantity']) ? $_POST['quantity'] : 0);
    $description = trim(isset($_POST['description']) ? $_POST['description'] : '');
    $current_image = $_POST['current_image'] ?? '';

    // Validation
    if (empty($name)) {
        $error = 'Le nom du vélo est obligatoire.';
    } elseif ($price <= 0) {
        $error = 'Le prix doit être supérieur à 0.';
    } elseif ($quantity < 0) {
        $error = 'La quantité ne peut pas être négative.';
    } else {
        // Gestion de l'image
        $image_url = $current_image;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploaded = uploadVeloImage($_FILES['image']);
            if ($uploaded) {
                $image_url = $uploaded;
                // Supprimer l'ancienne image si elle existe
                if (!empty($current_image) && file_exists(__DIR__ . '/../assets/imgs/velos/' . $current_image)) {
                    unlink(__DIR__ . '/../assets/imgs/velos/' . $current_image);
                }
            } else {
                $error = 'Erreur lors de l\'upload de l\'image. Vérifiez le format et la taille (max 5MB).';
            }
        }

        if (empty($error)) {
            $data = [
                'name' => $name,
                'price' => $price,
                'quantity' => $quantity,
                'description' => $description,
                'image_url' => $image_url
            ];

            if ($isEdit) {
                // Mise à jour
                if (updateVelo($pdo, $_GET['id'], $data)) {
                    $success = 'Vélo modifié avec succès.';
                    $velo = getVeloById($pdo, $_GET['id']);
                } else {
                    $error = 'Erreur lors de la modification du vélo.';
                }
            } else {
                // Ajout
                $id = addVelo($pdo, $data);
                if ($id) {
                    $success = 'Vélo ajouté avec succès.';
                    // Rediriger vers la page d'édition
                    header('Location: velo_form.php?id=' . $id . '&success=1');
                    exit;
                } else {
                    $error = 'Erreur lors de l\'ajout du vélo.';
                }
            }
        }
    }
}

// Message de succès après redirection
if (isset($_GET['success'])) {
    $success = 'Vélo ajouté avec succès.';
}

$pageTitle = $isEdit ? "Modifier un vélo" : "Ajouter un vélo";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - RESAVELO Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <header class="header admin-header">
        <div class="container">
            <div class="header-content">
                <h1 class="logo"><i class="fas fa-bicycle"></i> RESAVELO Admin</h1>
                <nav class="nav">
                    <a href="index.php" class="nav-link">Tableau de bord</a>
                    <a href="velos.php" class="nav-link active">Vélos</a>
                    <a href="reservations.php" class="nav-link">Réservations</a>
                    <a href="../public/index.php" class="nav-link">← Site public</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main admin-main">
        <div class="container">
            <div class="page-header">
                <div>
                    <a href="velos.php" class="back-link">← Retour à la liste</a>
                    <h2><?= $pageTitle ?></h2>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" action="" enctype="multipart/form-data" class="admin-form">
                    <input type="hidden" name="current_image" value="<?= $velo['image_url'] ?? '' ?>">

                    <div class="form-group">
                        <label for="name">Nom du vélo *</label>
                        <input type="text" id="name" name="name"
                               value="<?= htmlspecialchars($velo['name'] ?? $_POST['name'] ?? '') ?>"
                               required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Prix par jour (€) *</label>
                            <input type="number" id="price" name="price"
                                   value="<?= $velo['price'] ?? $_POST['price'] ?? '' ?>"
                                   min="0" step="0.01" required>
                        </div>

                        <div class="form-group">
                            <label for="quantity">Quantité disponible *</label>
                            <input type="number" id="quantity" name="quantity"
                                   value="<?= $velo['quantity'] ?? $_POST['quantity'] ?? '1' ?>"
                                   min="0" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"><?= htmlspecialchars($velo['description'] ?? $_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="image">Image du vélo</label>
                        <?php if (!empty($velo['image_url'])): ?>
                            <div class="current-image">
                                <p>Image actuelle :</p>
                                <img src="../assets/imgs/velos/<?= htmlspecialchars($velo['image_url']) ?>"
                                     alt="Image actuelle"
                                     style="max-width: 200px; border-radius: 8px; margin: 10px 0;">
                            </div>
                        <?php endif; ?>
                        <input type="file" id="image" name="image" accept="image/*">
                        <small>Formats acceptés : JPG, PNG, GIF, WEBP (max 5MB)</small>
                    </div>

                    <div class="form-actions">
                        <a href="velos.php" class="btn btn-secondary">Annuler</a>
                        <button type="submit" class="btn btn-primary">
                            <?= $isEdit ? 'Mettre à jour' : 'Ajouter' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2026 RESAVELO - Administration</p>
        </div>
    </footer>

    <script>
        // Prévisualisation de l'image
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const currentImageDiv = document.querySelector('.current-image');
                    if (currentImageDiv) {
                        const img = currentImageDiv.querySelector('img');
                        img.src = e.target.result;
                        currentImageDiv.querySelector('p').textContent = 'Nouvelle image :';
                    } else {
                        const newDiv = document.createElement('div');
                        newDiv.className = 'current-image';
                        newDiv.innerHTML = '<p>Nouvelle image :</p><img src="' + e.target.result + '" style="max-width: 200px; border-radius: 8px; margin: 10px 0;">';
                        document.getElementById('image').parentNode.insertBefore(newDiv, document.getElementById('image'));
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>

