<?php
/**
 * Tableau de bord administrateur
 * RESAVELO - Système de location de vélos
 */

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/functions_velos.php';
require_once __DIR__ . '/../includes/functions_reservation.php';
require_once __DIR__ . '/../includes/functions_calculation.php';

// Récupérer les statistiques
$stats = getReservationStats($pdo);
$mostRented = getMostRentedVelos($pdo, 5);
$recentReservations = getAllReservations($pdo);
$recentReservations = array_slice($recentReservations, 0, 5);

// Récupérer le nombre total de vélos
$totalVelos = count(getAllVelos($pdo));
$availableVelos = count(getAllVelos($pdo, ['disponible' => true]));

$pageTitle = "Tableau de bord";
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
                    <a href="index.php" class="nav-link active">Tableau de bord</a>
                    <a href="velos.php" class="nav-link">Vélos</a>
                    <a href="reservations.php" class="nav-link">Réservations</a>
                    <a href="../public/index.php" class="nav-link">← Site public</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main admin-main">
        <div class="container">
            <div class="page-header">
                <h2><?= $pageTitle ?></h2>
                <p class="subtitle">Vue d'ensemble de votre activité de location</p>
            </div>

            <!-- Statistiques principales -->
            <section class="stats-grid">
                <div class="stat-card stat-primary">
                    <div class="stat-content">
                        <h3>Total Réservations</h3>
                        <p class="stat-number"><?= $stats['total'] ?></p>
                    </div>
                </div>

                <div class="stat-card stat-warning">
                    <div class="stat-icon"><i class="far fa-clock"></i></div>
                    <div class="stat-content">
                        <h3>En attente</h3>
                        <p class="stat-number"><?= $stats['pending'] ?></p>
                    </div>
                </div>

                <div class="stat-card stat-success">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-content">
                        <h3>Confirmées</h3>
                        <p class="stat-number"><?= $stats['confirmed'] ?></p>
                    </div>
                </div>

                <div class="stat-card stat-info">
                    <div class="stat-icon"><i class="fas fa-euro-sign"></i></div>
                    <div class="stat-content">
                        <h3>Chiffre d'affaires total</h3>
                        <p class="stat-number"><?= formatPrice($stats['revenue_total']) ?></p>
                    </div>
                </div>

                <div class="stat-card stat-info">
                    <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-content">
                        <h3>CA du mois</h3>
                        <p class="stat-number"><?= formatPrice($stats['revenue_month']) ?></p>
                    </div>
                </div>

                <div class="stat-card stat-secondary">
                    <div class="stat-icon"><i class="fas fa-bicycle"></i></div>
                    <div class="stat-content">
                        <h3>Vélos disponibles</h3>
                        <p class="stat-number"><?= $availableVelos ?> / <?= $totalVelos ?></p>
                    </div>
                </div>
            </section>

            <div class="dashboard-row">
                <!-- Vélos les plus loués -->
                <section class="dashboard-section">
                    <h3><i class="fas fa-trophy"></i> Vélos les plus loués</h3>
                    <?php if (empty($mostRented)): ?>
                        <p class="no-data">Aucune donnée disponible</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Vélo</th>
                                    <th>Prix/jour</th>
                                    <th>Stock</th>
                                    <th>Locations</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mostRented as $velo): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($velo['name']) ?></strong>
                                        </td>
                                        <td><?= formatPrice($velo['price']) ?></td>
                                        <td>
                                            <?php if ($velo['quantity'] > 0): ?>
                                                <span class="badge badge-success"><?= $velo['quantity'] ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-info"><?= $velo['rental_count'] ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </section>

                <!-- Réservations récentes -->
                <section class="dashboard-section">
                    <h3>📋 Réservations récentes</h3>
                    <?php if (empty($recentReservations)): ?>
                        <p class="no-data">Aucune réservation</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Vélo</th>
                                    <th>Client</th>
                                    <th>Dates</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentReservations as $reservation): ?>
                                    <tr>
                                        <td>#<?= $reservation['id'] ?></td>
                                        <td><?= htmlspecialchars($reservation['velo_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($reservation['customer_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <small>
                                                <?= formatDate($reservation['start_date']) ?><br>
                                                au <?= formatDate($reservation['end_date']) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = '';
                                            $statusText = '';
                                            switch ($reservation['status']) {
                                                case 'pending':
                                                    $statusClass = 'badge-warning';
                                                    $statusText = '<i class="far fa-clock"></i> En attente';
                                                    break;
                                                case 'confirmed':
                                                    $statusClass = 'badge-success';
                                                    $statusText = '<i class="fas fa-check-circle"></i> Confirmée';
                                                    break;
                                                case 'cancelled':
                                                    $statusClass = 'badge-danger';
                                                    $statusText = '<i class="fas fa-times-circle"></i> Annulée';
                                                    break;
                                                case 'completed':
                                                    $statusClass = 'badge-info';
                                                    $statusText = '<i class="fas fa-check"></i> Terminée';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="section-footer">
                            <a href="reservations.php" class="btn btn-secondary">Voir toutes les réservations</a>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <!-- Actions rapides -->
            <section class="quick-actions">
                <h3>Actions rapides</h3>
                <div class="actions-grid">
                    <!-- Action 1: Creer un nouveau velo -->
                    <a href="velo_form.php" class="action-card">
                        <span class="action-icon"><i class="fas fa-plus-circle"></i></span>
                        <span class="action-text">Ajouter un vélo</span>
                    </a>
                    <!-- Action 2: Acceder a la liste complete des velos -->
                    <a href="velos.php" class="action-card">
                        <span class="action-icon"><i class="fas fa-bicycle"></i></span>
                        <span class="action-text">Gérer les vélos</span>
                    </a>
                    <!-- Action 3: Voir les reservations necessitant une validation -->
                    <a href="reservations.php?status=pending" class="action-card">
                        <span class="action-icon"><i class="far fa-clock"></i></span>
                        <span class="action-text">Réservations en attente</span>
                    </a>
                    <!-- Action 4: Voir toutes les reservations -->
                    <a href="reservations.php" class="action-card">
                        <span class="action-icon"><i class="fas fa-list-alt"></i></span>
                        <span class="action-text">Toutes les réservations</span>
                    </a>
                </div>
            </section>
        </div>
    </main>

    <!-- Pied de page -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2026 RESAVELO - Administration</p>
        </div>
    </footer>
</body>
</html>

