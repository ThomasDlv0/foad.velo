<?php
/**
 * Page d'accueil - Catalogue des vélos
 * RESAVELO - Système de location de vélos
 */

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/functions_velos.php';
require_once __DIR__ . '/../includes/functions_calculation.php';

// Récupérer les filtres
$filters = [];
if (isset($_GET['disponible']) && $_GET['disponible'] == '1') {
    $filters['disponible'] = true;
}
if (isset($_GET['prix_min']) && !empty($_GET['prix_min'])) {
    $filters['prix_min'] = floatval($_GET['prix_min']);
}
if (isset($_GET['prix_max']) && !empty($_GET['prix_max'])) {
    $filters['prix_max'] = floatval($_GET['prix_max']);
}

// Récupérer tous les vélos
$velos = getAllVelos($pdo, $filters);

$pageTitle = "Catalogue des vélos";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - RESAVELO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <h1 class="logo"><i class="fas fa-bicycle"></i> RESAVELO</h1>
                <nav class="nav">
                    <a href="index.php" class="nav-link active">Catalogue</a>
                    <a href="mes_reservations.php" class="nav-link">Mes réservations</a>
                    <a href="../admin/index.php" class="nav-link nav-link-admin">Administration</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <section class="hero">
                <h2>Louez votre vélo idéal</h2>
                <p>Découvrez notre gamme de vélos de qualité pour vos déplacements urbains et vos balades</p>
            </section>

            <section class="filters">
                <h3>Filtres</h3>
                <form method="GET" action="index.php" class="filter-form">
                    <div class="filter-group">
                        <label>
                            <input type="checkbox" name="disponible" value="1" <?= isset($_GET['disponible']) ? 'checked' : '' ?>>
                            Disponible uniquement
                        </label>
                    </div>
                    <div class="filter-group">
                        <label for="prix_min">Prix min (€/jour)</label>
                        <input type="number" id="prix_min" name="prix_min" min="0" step="1"
                               value="<?= $_GET['prix_min'] ?? '' ?>" placeholder="0">
                    </div>
                    <div class="filter-group">
                        <label for="prix_max">Prix max (€/jour)</label>
                        <input type="number" id="prix_max" name="prix_max" min="0" step="1"
                               value="<?= $_GET['prix_max'] ?? '' ?>" placeholder="100">
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">Filtrer</button>
                        <a href="index.php" class="btn btn-secondary">Réinitialiser</a>
                    </div>
                </form>
            </section>

            <section class="velos-grid">
                <?php if (empty($velos)): ?>
                    <div class="no-results">
                        <p>Aucun vélo ne correspond à vos critères.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($velos as $velo): ?>
                        <article class="velo-card">
                            <div class="velo-image">
                                <i class="fas fa-bicycle"></i>

                                <?php if ($velo['quantity'] > 0): ?>
                                    <span class="badge badge-success">Disponible (<?= $velo['quantity'] ?>)</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Indisponible</span>
                                <?php endif; ?>
                            </div>

                            <div class="velo-content">
                                <h3 class="velo-name"><?= htmlspecialchars($velo['name']) ?></h3>
                                <p class="velo-description"><?= htmlspecialchars($velo['description']) ?></p>
                                <div class="velo-footer">
                                    <span class="velo-price"><?= formatPrice($velo['price']) ?> / jour</span>
                                    <?php if ($velo['quantity'] > 0): ?>
                                        <a href="reservation_form.php?velo_id=<?= $velo['id'] ?>" class="btn btn-primary">Réserver</a>
                                    <?php else: ?>
                                        <button class="btn btn-disabled" disabled>Indisponible</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2026 RESAVELO - Location de vélos de ville</p>
        </div>
    </footer>
</body>
</html>

