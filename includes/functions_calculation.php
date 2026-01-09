<?php
/**
 * Fonctions de calcul pour les prix et disponibilités
 * RESAVELO - Système de location de vélos
 */

/**
 * Calcule le prix total d'une location
 *
 * @param float $price_per_day Prix journalier
 * @param string $start_date Date de début (format: Y-m-d)
 * @param string $end_date Date de fin (format: Y-m-d)
 * @return float Prix total
 */
function calculatePrice($price_per_day, $start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);

    // Calculer le nombre de jours
    $interval = $start->diff($end);
    $days = $interval->days + 1; // +1 pour inclure le jour de fin

    // Prix de base
    $total = $price_per_day * $days;

    // Réduction pour les longues durées
    if ($days >= 7) {
        $total *= 0.85; // 15% de réduction pour 7 jours ou plus
    } elseif ($days >= 3) {
        $total *= 0.90; // 10% de réduction pour 3 à 6 jours
    }

    return round($total, 2);
}

/**
 * Calcule le nombre de jours entre deux dates
 *
 * @param string $start_date Date de début
 * @param string $end_date Date de fin
 * @return int Nombre de jours
 */
function calculateDays($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = $start->diff($end);
    return $interval->days + 1;
}

/**
 * Vérifie si les dates sont valides pour une réservation
 *
 * Verifie 3 regles metier :
 * 1. La date de debut doit etre aujourd'hui ou dans le futur
 * 2. La date de fin doit etre apres la date de debut
 * 3. La duree ne doit pas depasser 30 jours
 *
 * @param string $start_date Date de début
 * @param string $end_date Date de fin
 * @return array ['valid' => bool, 'error' => string]
 */
function validateReservationDates($start_date, $end_date) {
    // Initialisation du resultat
    $result = ['valid' => true, 'error' => ''];

    try {
        // Conversion des dates
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $today = new DateTime('today');

        // Regle 1: La date de debut doit etre aujourd'hui ou future
        if ($start < $today) {
            $result['valid'] = false;
            $result['error'] = 'La date de début doit être aujourd\'hui ou dans le futur.';
            return $result;
        }

        // Regle 2: La date de fin doit etre apres la date de debut
        if ($end < $start) {
            $result['valid'] = false;
            $result['error'] = 'La date de fin doit être après la date de début.';
            return $result;
        }

        // Regle 3: Duree maximale de 30 jours
        $interval = $start->diff($end);
        if ($interval->days > 30) {
            $result['valid'] = false;
            $result['error'] = 'La durée maximale de location est de 30 jours.';
            return $result;
        }

    } catch (Exception $e) {
        // En cas d'erreur de format de date
        $result['valid'] = false;
        $result['error'] = 'Format de date invalide.';
    }

    return $result;
}

/**
 * Formate un montant en euros
 *
 * @param float $amount Montant
 * @return string Montant formaté
 */
function formatPrice($amount) {
    return number_format($amount, 2, ',', ' ') . ' €';
}

/**
 * Formate une date au format français
 *
 * @param string $date Date au format Y-m-d
 * @return string Date formatée
 */
function formatDate($date) {
    $dt = new DateTime($date);
    setlocale(LC_TIME, 'fr_FR.UTF-8', 'fra');
    return $dt->format('d/m/Y');
}

/**
 * Calcule le taux d'occupation d'un vélo pour une période
 *
 * @param PDO $pdo Instance PDO
 * @param int $velo_id ID du vélo
 * @param string $start_date Date de début
 * @param string $end_date Date de fin
 * @return float Taux d'occupation en pourcentage
 */
function calculateOccupancyRate($pdo, $velo_id, $start_date, $end_date) {
    require_once __DIR__ . '/functions_velos.php';

    $velo = getVeloById($pdo, $velo_id);
    if (!$velo) {
        return 0;
    }

    $totalDays = calculateDays($start_date, $end_date);
    $totalCapacity = $totalDays * $velo['quantity'];

    // Compter les jours réservés
    $sql = "SELECT start_date, end_date 
            FROM reservations 
            WHERE velo_id = :velo_id 
            AND status IN ('confirmed', 'completed')
            AND (
                (start_date BETWEEN :start_date AND :end_date)
                OR (end_date BETWEEN :start_date AND :end_date)
                OR (start_date <= :start_date AND end_date >= :end_date)
            )";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':velo_id' => $velo_id,
            ':start_date' => $start_date,
            ':end_date' => $end_date
        ]);
        $reservations = $stmt->fetchAll();

        $totalReservedDays = 0;
        foreach ($reservations as $reservation) {
            $totalReservedDays += calculateDays($reservation['start_date'], $reservation['end_date']);
        }

        return $totalCapacity > 0 ? round(($totalReservedDays / $totalCapacity) * 100, 2) : 0;
    } catch (PDOException $e) {
        error_log("Erreur calculateOccupancyRate: " . $e->getMessage());
        return 0;
    }
}

