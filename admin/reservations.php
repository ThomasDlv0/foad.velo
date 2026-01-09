<?php
/**
 * Gestion des réservations
 * RESAVELO - Système de location de vélos
 */

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/functions_reservation.php';
require_once __DIR__ . '/../includes/functions_calculation.php';

$message = '';
$messageType = '';

// Gestion des actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    switch ($action) {
        case 'confirm':
            if (updateReservationStatus($pdo, $id, 'confirmed')) {
                $message = 'Réservation confirmée avec succès.';
                $messageType = 'success';
            } else {
                $message = 'Erreur lors de la confirmation.';
                $messageType = 'error';
            }
            break;
        case 'cancel':
            if (cancelReservation($pdo, $id)) {
                $message = 'Réservation annulée avec succès.';
                $messageType = 'success';
            } else {
                $message = 'Erreur lors de l\'annulation.';
                $messageType = 'error';
            }
            break;
        case 'complete':
            if (updateReservationStatus($pdo, $id, 'completed')) {
                $message = 'Réservation marquée comme terminée.';
                $messageType = 'success';
            } else {
                $message = 'Erreur lors de la mise à jour.';
                $messageType = 'error';
            }
            break;
    }
}

// Filtres
$filters = [];
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}

// Récupérer toutes les réservations
$reservations = getAllReservations($pdo, $filters);

$pageTitle = "Gestion des réservations";
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
                    <a href="velos.php" class="nav-link">Vélos</a>
                    <a href="reservations.php" class="nav-link active">Réservations</a>
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
                    <p class="subtitle">Gérez les réservations de vos clients</p>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <!-- Filtres -->
            <div class="filters-bar">
                <a href="reservations.php" class="filter-btn <?= !isset($_GET['status']) ? 'active' : '' ?>">
                    Toutes
                </a>
                <a href="reservations.php?status=pending" class="filter-btn <?= ($_GET['status'] ?? '') === 'pending' ? 'active' : '' ?>">
                    En attente
                </a>
                <a href="reservations.php?status=confirmed" class="filter-btn <?= ($_GET['status'] ?? '') === 'confirmed' ? 'active' : '' ?>">
                    Confirmées
                </a>
                <a href="reservations.php?status=completed" class="filter-btn <?= ($_GET['status'] ?? '') === 'completed' ? 'active' : '' ?>">
                    Terminées
                </a>
                <a href="reservations.php?status=cancelled" class="filter-btn <?= ($_GET['status'] ?? '') === 'cancelled' ? 'active' : '' ?>">
                    Annulées
                </a>
            </div>

            <?php if (empty($reservations)): ?>
                <div class="no-data-card">
                    <p>Aucune réservation trouvée.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table reservations-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Vélo</th>
                                <th>Client</th>
                                <th>Contact</th>
                                <th>Dates</th>
                                <th>Prix</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $reservation): ?>
                                <tr>
                                    <td><strong>#<?= $reservation['id'] ?></strong></td>
                                    <td>
                                        <div class="velo-info">
                                            <i class="fas fa-bicycle"></i>
                                            <span><?= htmlspecialchars($reservation['velo_name'] ?? 'N/A') ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($reservation['customer_name'] ?? 'N/A') ?></td>
                                    <td>
                                        <small>
                                            <i class="fas fa-envelope"></i> <?= htmlspecialchars($reservation['customer_email'] ?? 'N/A') ?><br>
                                            <i class="fas fa-phone"></i> <?= htmlspecialchars($reservation['customer_phone'] ?? 'N/A') ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small>
                                            <strong>Du:</strong> <?= formatDate($reservation['start_date']) ?><br>
                                            <strong>Au:</strong> <?= formatDate($reservation['end_date']) ?><br>
                                            <span class="badge badge-secondary">
                                                <?= calculateDays($reservation['start_date'], $reservation['end_date']) ?> jour(s)
                                            </span>
                                        </small>
                                    </td>
                                    <td><strong><?= formatPrice($reservation['total_price']) ?></strong></td>
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
                                    <td class="actions-cell">
                                        <?php if ($reservation['status'] === 'pending'): ?>
                                            <a href="reservations.php?action=confirm&id=<?= $reservation['id'] ?>"
                                               class="btn btn-sm btn-success"
                                               title="Confirmer"
                                               onclick="return confirm('Confirmer cette réservation ?')">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="reservations.php?action=cancel&id=<?= $reservation['id'] ?>"
                                               class="btn btn-sm btn-danger"
                                               title="Refuser"
                                               onclick="return confirm('Refuser cette réservation ?')">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php elseif ($reservation['status'] === 'confirmed'): ?>
                                            <a href="reservations.php?action=complete&id=<?= $reservation['id'] ?>"
                                               class="btn btn-sm btn-info"
                                               title="Marquer comme terminée"
                                               onclick="return confirm('Marquer comme terminée ?')">
                                                <i class="fas fa-check-double"></i>
                                            </a>
                                            <a href="reservations.php?action=cancel&id=<?= $reservation['id'] ?>"
                                               class="btn btn-sm btn-danger"
                                               title="Annuler"
                                               onclick="return confirm('Annuler cette réservation ?')">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="table-summary">
                    <p>Total : <strong><?= count($reservations) ?></strong> réservation(s)</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2026 RESAVELO - Administration</p>
        </div>
    </footer>

    <script src="../assets/js/reservations.js"></script>
</body>
</html>

