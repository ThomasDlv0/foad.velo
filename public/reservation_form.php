<?php
/**
 * Formulaire de réservation
 * RESAVELO - Système de location de vélos
 */

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/functions_velos.php';
require_once __DIR__ . '/../includes/functions_reservation.php';
require_once __DIR__ . '/../includes/functions_calculation.php';

$error = '';
$success = '';
$velo = null;
$estimated_price = 0;

// Récupérer le vélo sélectionné
if (isset($_GET['velo_id'])) {
    $velo = getVeloById($pdo, $_GET['velo_id']);
    if (!$velo) {
        header('Location: index.php');
        exit;
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $velo_id = $_POST['velo_id'] ?? 0;
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');

    // Validation
    if (empty($customer_name) || empty($customer_email) || empty($customer_phone)) {
        $error = 'Tous les champs sont obligatoires.';
    } elseif (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } else {
        // Valider les dates
        $dateValidation = validateReservationDates($start_date, $end_date);
        if (!$dateValidation['valid']) {
            $error = $dateValidation['error'];
        } else {
            // Vérifier la disponibilité
            if (!checkAvailability($pdo, $velo_id, $start_date, $end_date)) {
                $error = 'Ce vélo n\'est pas disponible pour les dates sélectionnées.';
            } else {
                // Créer la réservation
                $customer_info = [
                    'name' => $customer_name,
                    'email' => $customer_email,
                    'phone' => $customer_phone
                ];

                $reservation_id = createReservation($pdo, $velo_id, $start_date, $end_date, $customer_info);

                if ($reservation_id) {
                    $success = 'Votre réservation a été enregistrée avec succès ! Numéro de réservation : #' . $reservation_id;
                    // Réinitialiser le formulaire
                    $_POST = [];
                } else {
                    $error = 'Erreur lors de la création de la réservation. Veuillez réessayer.';
                }
            }
        }
    }

    // Recharger le vélo après traitement
    if ($velo_id) {
        $velo = getVeloById($pdo, $velo_id);
    }
}

// Calculer le prix estimé si les dates sont fournies
if ($velo && !empty($_POST['start_date']) && !empty($_POST['end_date'])) {
    $estimated_price = calculatePrice($velo['price'], $_POST['start_date'], $_POST['end_date']);
    $estimated_days = calculateDays($_POST['start_date'], $_POST['end_date']);
}

$pageTitle = $velo ? "Réserver " . $velo['name'] : "Réservation";
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
                    <a href="mes_reservations.php" class="nav-link">Mes réservations</a>
                    <a href="../admin/index.php" class="nav-link nav-link-admin">Administration</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <div class="page-header">
                <a href="index.php" class="back-link">← Retour au catalogue</a>
                <h2><?= $pageTitle ?></h2>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                    <br><br>
                    <a href="index.php" class="btn btn-primary">Retour au catalogue</a>
                    <a href="mes_reservations.php" class="btn btn-secondary">Voir mes réservations</a>
                </div>
            <?php endif; ?>

            <?php if ($velo && !$success): ?>
                <div class="reservation-container">
                    <div class="velo-summary">
                        <h3>Vélo sélectionné</h3>
                        <div class="velo-summary-icon">
                            <i class="fas fa-bicycle"></i>
                        </div>
                        <h4><?= htmlspecialchars($velo['name']) ?></h4>
                        <p><?= htmlspecialchars($velo['description']) ?></p>
                        <div class="price-info">
                            <strong><?= formatPrice($velo['price']) ?> / jour</strong>
                        </div>
                        <?php if ($estimated_price > 0): ?>
                            <div class="estimated-price">
                                <p>Durée : <?= $estimated_days ?> jour(s)</p>
                                <p class="total">Total estimé : <strong><?= formatPrice($estimated_price) ?></strong></p>
                                <?php if ($estimated_days >= 3): ?>
                                    <p class="discount-info"><i class="fas fa-tag"></i> Réduction appliquée !</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="reservation-form-container">
                        <h3>Informations de réservation</h3>
                        <form method="POST" action="reservation_form.php" class="reservation-form" id="reservationForm">
                            <input type="hidden" name="velo_id" value="<?= $velo['id'] ?>">

                            <div class="form-group">
                                <label for="start_date">Date de début *</label>
                                <input type="date" id="start_date" name="start_date"
                                       value="<?= $_POST['start_date'] ?? '' ?>"
                                       min="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="end_date">Date de fin *</label>
                                <input type="date" id="end_date" name="end_date"
                                       value="<?= $_POST['end_date'] ?? '' ?>"
                                       min="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="customer_name">Nom complet *</label>
                                <input type="text" id="customer_name" name="customer_name"
                                       value="<?= htmlspecialchars($_POST['customer_name'] ?? '') ?>"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="customer_email">Email *</label>
                                <input type="email" id="customer_email" name="customer_email"
                                       value="<?= htmlspecialchars($_POST['customer_email'] ?? '') ?>"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="customer_phone">Téléphone *</label>
                                <input type="tel" id="customer_phone" name="customer_phone"
                                       value="<?= htmlspecialchars($_POST['customer_phone'] ?? '') ?>"
                                       pattern="[0-9]{10}"
                                       placeholder="0612345678" required>
                                <small>Format : 10 chiffres sans espaces</small>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary" onclick="calculateEstimate()">
                                    Calculer le prix
                                </button>
                                <button type="submit" class="btn btn-primary">Confirmer la réservation</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php elseif (!$velo): ?>
                <div class="alert alert-error">
                    Vélo non trouvé. <a href="index.php">Retour au catalogue</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2026 RESAVELO - Location de vélos de ville</p>
        </div>
    </footer>

    <script>
        // Calculer le prix estimé
        function calculateEstimate() {
            const form = document.getElementById('reservationForm');
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;

            if (startDate && endDate) {
                // Soumettre le formulaire avec les dates pour recalculer
                form.submit();
            } else {
                alert('Veuillez sélectionner les dates de début et de fin.');
            }
        }

        // Mettre à jour la date de fin minimum quand la date de début change
        document.getElementById('start_date').addEventListener('change', function() {
            document.getElementById('end_date').min = this.value;
        });
    </script>
</body>
</html>

