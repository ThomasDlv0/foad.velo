<?php
/**
 * Page de consultation des réservations client
 * RESAVELO - Système de location de vélos
 */

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/functions_reservation.php';
require_once __DIR__ . '/../includes/functions_calculation.php';

$reservations = [];
$search_email = '';
$message = '';

// Recherche par email
if (isset($_GET['email']) && !empty($_GET['email'])) {
    $search_email = trim($_GET['email']);

    if (filter_var($search_email, FILTER_VALIDATE_EMAIL)) {
        // Récupérer les réservations par email
        $sql = "SELECT r.*, v.name as velo_name, v.image_url as velo_image, v.price as velo_price 
                FROM reservations r 
                JOIN velos v ON r.velo_id = v.id 
                WHERE r.customer_email = :email 
                ORDER BY r.created_at DESC";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $search_email]);
            $reservations = $stmt->fetchAll();

            if (empty($reservations)) {
                $message = "Aucune réservation trouvée pour cet email.";
            }
        } catch (PDOException $e) {
            $message = "Erreur lors de la recherche.";
            error_log("Erreur recherche réservations: " . $e->getMessage());
        }
    } else {
        $message = "Adresse email invalide.";
    }
}

$pageTitle = "Mes réservations";
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
                    <a href="index.php" class="nav-link">Catalogue</a>
                    <a href="mes_reservations.php" class="nav-link active">Mes réservations</a>
                    <a href="../admin/index.php" class="nav-link nav-link-admin">Administration</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <div class="page-header">
                <h2><?= $pageTitle ?></h2>
            </div>

            <section class="search-section">
                <h3>Rechercher mes réservations</h3>
                <form method="GET" action="mes_reservations.php" class="search-form">
                    <div class="form-group">
                        <label for="email">Entrez votre adresse email</label>
                        <input type="email" id="email" name="email"
                               value="<?= htmlspecialchars($search_email) ?>"
                               placeholder="votre.email@example.com" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </form>
            </section>

            <?php if ($message): ?>
                <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if (!empty($reservations)): ?>
                <section class="reservations-list">
                    <h3>Vos réservations</h3>
                    <?php foreach ($reservations as $reservation): ?>
                        <article class="reservation-card">
                            <div class="reservation-image">
                                <i class="fas fa-bicycle"></i>
                            </div>

                            <div class="reservation-content">
                                <div class="reservation-header">
                                    <h4><?= htmlspecialchars($reservation['velo_name'] ?? 'N/A') ?></h4>
                                    <span class="reservation-number">#<?= $reservation['id'] ?></span>
                                </div>

                                <div class="reservation-info">
                                    <div class="info-item">
                                        <strong><i class="far fa-calendar"></i> Dates :</strong>
                                        Du <?= formatDate($reservation['start_date']) ?>
                                        au <?= formatDate($reservation['end_date']) ?>
                                        (<?= calculateDays($reservation['start_date'], $reservation['end_date']) ?> jour(s))
                                    </div>
                                    <div class="info-item">
                                        <strong><i class="fas fa-euro-sign"></i> Prix total :</strong>
                                        <?= formatPrice($reservation['total_price']) ?>
                                    </div>
                                    <div class="info-item">
                                        <strong><i class="fas fa-user"></i> Client :</strong>
                                        <?= htmlspecialchars($reservation['customer_name'] ?? 'N/A') ?>
                                    </div>
                                    <div class="info-item">
                                        <strong><i class="fas fa-envelope"></i> Email :</strong>
                                        <?= htmlspecialchars($reservation['customer_email'] ?? 'N/A') ?>
                                    </div>
                                    <div class="info-item">
                                        <strong><i class="fas fa-phone"></i> Téléphone :</strong>
                                        <?= htmlspecialchars($reservation['customer_phone'] ?? 'N/A') ?>
                                    </div>
                                    <div class="info-item">
                                        <strong><i class="far fa-clock"></i> Réservé le :</strong>
                                        <?= formatDate(date('Y-m-d', strtotime($reservation['created_at']))) ?>
                                    </div>
                                </div>

                                <div class="reservation-status">
                                    <?php
                                    $statusClass = '';
                                    $statusText = '';
                                    switch ($reservation['status']) {
                                        case 'pending':
                                            $statusClass = 'status-pending';
                                            $statusText = '<i class="far fa-clock"></i> En attente';
                                            break;
                                        case 'confirmed':
                                            $statusClass = 'status-confirmed';
                                            $statusText = '<i class="fas fa-check-circle"></i> Confirmée';
                                            break;
                                        case 'cancelled':
                                            $statusClass = 'status-cancelled';
                                            $statusText = '<i class="fas fa-times-circle"></i> Annulée';
                                            break;
                                        case 'completed':
                                            $statusClass = 'status-completed';
                                            $statusText = '<i class="fas fa-check"></i> Terminée';
                                            break;
                                    }
                                    ?>
                                    <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <?php if (empty($search_email)): ?>
                <section class="help-section">
                    <h3>Comment ça marche ?</h3>
                    <ol>
                        <li>Entrez l'adresse email que vous avez utilisée lors de votre réservation</li>
                        <li>Consultez toutes vos réservations passées et en cours</li>
                        <li>Vérifiez le statut de vos réservations</li>
                    </ol>
                </section>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2026 RESAVELO - Location de vélos de ville</p>
        </div>
    </footer>
</body>
</html>

