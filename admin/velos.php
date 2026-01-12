<?php

/**
 * Gestion des vélos - Liste
 * RESAVELO - Système de location de vélos
 */

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/functions_velos.php';
require_once __DIR__ . '/../includes/functions_calculation.php';

$message = '';
$messageType = '';

// Gestion de la suppression
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if (deleteVelo($pdo, $id)) {
        $message = 'Vélo supprimé avec succès.';
        $messageType = 'success';
    } else {
        $message = 'Erreur lors de la suppression du vélo.';
        $messageType = 'error';
    }
}

// Récupérer tous les vélos
$velos = getAllVelos($pdo);

$pageTitle = "Gestion des vélos";
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - RESAVELO Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    <header class="header admin-header">
        <div class="container">
            <div class="header-content">
                <h1 class="logo">🚴 RESAVELO Admin</h1>
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
                    <h2><?= $pageTitle ?></h2>
                    <p class="subtitle">Gérez votre flotte de vélos</p>
                </div>
                <a href="velo_form.php" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter un vélo</a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if (empty($velos)): ?>
                <div class="no-data-card">
                    <p>Aucun vélo dans la base de données.</p>
                    <a href="velo_form.php" class="btn btn-primary">Ajouter le premier vélo</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Nom</th>
                                <th>Description</th>
                                <th>Prix/jour</th>
                                <th>Quantité</th>
                                <th>Disponibilité</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($velos as $velo): ?>
                                <tr>
                                    <td><?= $velo['id'] ?></td>
                                    <td>
                                        <?php
                                        $imagePath = '../assets/imgs/velos/' . htmlspecialchars($velo['image_url']);
                                        if (!file_exists($imagePath)): ?>
                                            <i class="fas fa-bicycle table-icon"></i>
                                        <?php else: ?>
                                            <img src="<?= $imagePath ?>"
                                                alt="Image actuelle"
                                                style="max-width: 200px; border-radius: 8px; margin: 10px 0;">
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= htmlspecialchars($velo['name']) ?></strong></td>
                                    <td>
                                        <small><?= htmlspecialchars(substr($velo['description'], 0, 60)) ?>
                                            <?= strlen($velo['description']) > 60 ? '...' : '' ?></small>
                                    </td>
                                    <td><?= formatPrice($velo['price']) ?></td>
                                    <td class="text-center">
                                        <span class="badge badge-info"><?= $velo['quantity'] ?></span>
                                    </td>
                                    <td>
                                        <?php if ($velo['quantity'] > 0): ?>
                                            <span class="badge badge-success">Disponible</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Indisponible</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions-cell">
                                        <a href="velo_form.php?id=<?= $velo['id'] ?>"
                                            class="btn btn-sm btn-secondary" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="velos.php?action=delete&id=<?= $velo['id'] ?>"
                                            class="btn btn-sm btn-danger"
                                            title="Supprimer"
                                            onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce vélo ?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="table-summary">
                    <p>Total : <strong><?= count($velos) ?></strong> vélo(s)</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2026 RESAVELO - Administration</p>
        </div>
    </footer>
</body>

</html>