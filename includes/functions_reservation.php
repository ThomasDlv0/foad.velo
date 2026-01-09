<?php
/**
 * Fonctions pour la gestion des réservations
 * RESAVELO - Système de location de vélos
 */

/**
 * Crée une nouvelle réservation
 *
 * @param PDO $pdo Instance PDO
 * @param int $velo_id ID du vélo
 * @param string $start_date Date de début (format: Y-m-d)
 * @param string $end_date Date de fin (format: Y-m-d)
 * @param array $customer_info Informations client (name, email, phone)
 * @return int|false ID de la réservation créée ou false
 */
function createReservation($pdo, $velo_id, $start_date, $end_date, $customer_info = []) {
    // Vérifier la disponibilité
    if (!checkAvailability($pdo, $velo_id, $start_date, $end_date)) {
        return false;
    }

    // Récupérer le prix du vélo
    $velo = getVeloById($pdo, $velo_id);
    if (!$velo) {
        return false;
    }

    // Calculer le prix total
    require_once __DIR__ . '/functions_calculation.php';
    $total_price = calculatePrice($velo['price'], $start_date, $end_date);

    $sql = "INSERT INTO reservations (velo_id, start_date, end_date, total_price, customer_name, customer_email, customer_phone) 
            VALUES (:velo_id, :start_date, :end_date, :total_price, :customer_name, :customer_email, :customer_phone)";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':velo_id' => $velo_id,
            ':start_date' => $start_date,
            ':end_date' => $end_date,
            ':total_price' => $total_price,
            ':customer_name' => $customer_info['name'] ?? '',
            ':customer_email' => $customer_info['email'] ?? '',
            ':customer_phone' => $customer_info['phone'] ?? ''
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Erreur createReservation: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère toutes les réservations avec les informations du vélo
 *
 * @param PDO $pdo Instance PDO
 * @param array $filters Filtres optionnels (status, velo_id, date)
 * @return array Liste des réservations
 */
function getAllReservations($pdo, $filters = []) {
    $sql = "SELECT r.*, v.name as velo_name, v.image_url as velo_image 
            FROM reservations r 
            JOIN velos v ON r.velo_id = v.id 
            WHERE 1=1";
    $params = [];

    // Filtre par statut
    if (isset($filters['status'])) {
        $sql .= " AND r.status = :status";
        $params[':status'] = $filters['status'];
    }

    // Filtre par vélo
    if (isset($filters['velo_id'])) {
        $sql .= " AND r.velo_id = :velo_id";
        $params[':velo_id'] = $filters['velo_id'];
    }

    // Filtre par date (réservations actives)
    if (isset($filters['active']) && $filters['active']) {
        $sql .= " AND r.start_date <= CURDATE() AND r.end_date >= CURDATE() AND r.status = 'confirmed'";
    }

    $sql .= " ORDER BY r.created_at DESC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur getAllReservations: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère une réservation par son ID
 *
 * @param PDO $pdo Instance PDO
 * @param int $id ID de la réservation
 * @return array|false Données de la réservation ou false
 */
function getReservationById($pdo, $id) {
    $sql = "SELECT r.*, v.name as velo_name, v.price as velo_price, v.image_url as velo_image 
            FROM reservations r 
            JOIN velos v ON r.velo_id = v.id 
            WHERE r.id = :id";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erreur getReservationById: " . $e->getMessage());
        return false;
    }
}

/**
 * Met à jour le statut d'une réservation
 *
 * @param PDO $pdo Instance PDO
 * @param int $id ID de la réservation
 * @param string $status Nouveau statut (pending, confirmed, cancelled, completed)
 * @return bool Succès de l'opération
 */
function updateReservationStatus($pdo, $id, $status) {
    $validStatuses = ['pending', 'confirmed', 'cancelled', 'completed'];
    if (!in_array($status, $validStatuses)) {
        return false;
    }

    $sql = "UPDATE reservations SET status = :status WHERE id = :id";

    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':status' => $status
        ]);
    } catch (PDOException $e) {
        error_log("Erreur updateReservationStatus: " . $e->getMessage());
        return false;
    }
}

/**
 * Annule une réservation
 *
 * @param PDO $pdo Instance PDO
 * @param int $id ID de la réservation
 * @return bool Succès de l'opération
 */
function cancelReservation($pdo, $id) {
    return updateReservationStatus($pdo, $id, 'cancelled');
}

/**
 * Vérifie la disponibilité d'un vélo pour une période donnée
 *
 * Algorithme:
 * 1. Verifie que le velo existe et a du stock
 * 2. Compte combien de reservations actives chevauchent la periode demandee
 * 3. Compare avec la quantite disponible
 *
 * @param PDO $pdo Instance PDO
 * @param int $velo_id ID du vélo
 * @param string $start_date Date de début (format: Y-m-d)
 * @param string $end_date Date de fin (format: Y-m-d)
 * @return bool True si disponible, false sinon
 */
function checkAvailability($pdo, $velo_id, $start_date, $end_date) {
    // Etape 1: Verifier que le velo existe et qu'il a du stock
    $velo = getVeloById($pdo, $velo_id);
    if (!$velo || $velo['quantity'] <= 0) {
        return false; // Velo inexistant ou aucun en stock
    }

    // Etape 2: Compter les reservations qui chevauchent la periode demandee
    // Une reservation chevauche si elle commence avant la fin demandee
    // ET se termine apres le debut demande
    $sql = "SELECT COUNT(*) as count 
            FROM reservations 
            WHERE velo_id = :velo_id 
            AND status IN ('pending', 'confirmed')
            AND (
                (start_date <= :end_date AND end_date >= :start_date)
            )";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':velo_id' => $velo_id,
            ':start_date' => $start_date,
            ':end_date' => $end_date
        ]);
        $result = $stmt->fetch();

        // Etape 3: Le velo est disponible si le nombre de reservations
        // est inferieur a la quantite en stock
        return $result['count'] < $velo['quantity'];
    } catch (PDOException $e) {
        error_log("Erreur checkAvailability: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les statistiques des réservations
 *
 * @param PDO $pdo Instance PDO
 * @return array Statistiques
 */
function getReservationStats($pdo) {
    $stats = [
        'total' => 0,
        'pending' => 0,
        'confirmed' => 0,
        'cancelled' => 0,
        'completed' => 0,
        'revenue_total' => 0,
        'revenue_month' => 0
    ];

    try {
        // Compter par statut
        $sql = "SELECT status, COUNT(*) as count, SUM(total_price) as revenue 
                FROM reservations 
                GROUP BY status";
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll();

        foreach ($results as $row) {
            $stats[$row['status']] = $row['count'];
            $stats['total'] += $row['count'];
            if ($row['status'] !== 'cancelled') {
                $stats['revenue_total'] += $row['revenue'];
            }
        }

        // Revenu du mois en cours
        $sql = "SELECT SUM(total_price) as revenue 
                FROM reservations 
                WHERE YEAR(created_at) = YEAR(CURDATE()) 
                AND MONTH(created_at) = MONTH(CURDATE())
                AND status != 'cancelled'";
        $stmt = $pdo->query($sql);
        $result = $stmt->fetch();
        $stats['revenue_month'] = $result['revenue'] ?? 0;

    } catch (PDOException $e) {
        error_log("Erreur getReservationStats: " . $e->getMessage());
    }

    return $stats;
}

/**
 * Récupère les vélos les plus loués
 *
 * @param PDO $pdo Instance PDO
 * @param int $limit Nombre de résultats
 * @return array Liste des vélos avec leur nombre de locations
 */
function getMostRentedVelos($pdo, $limit = 5) {
    $sql = "SELECT v.*, COUNT(r.id) as rental_count 
            FROM velos v 
            LEFT JOIN reservations r ON v.id = r.velo_id AND r.status != 'cancelled'
            GROUP BY v.id 
            ORDER BY rental_count DESC 
            LIMIT :limit";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur getMostRentedVelos: " . $e->getMessage());
        return [];
    }
}

