<?php
/**
 * Fonctions CRUD pour la gestion des vélos
 * RESAVELO - Système de location de vélos
 */

/**
 * Récupère tous les vélos
 *
 * @param PDO $pdo Instance PDO
 * @param array $filters Filtres optionnels (disponible, prix_max, prix_min)
 * @return array Liste des vélos
 */
function getAllVelos($pdo, $filters = []) {
    $sql = "SELECT * FROM velos WHERE 1=1";
    $params = [];

    // Filtre par disponibilité
    if (isset($filters['disponible']) && $filters['disponible']) {
        $sql .= " AND quantity > 0";
    }

    // Filtre par prix minimum
    if (isset($filters['prix_min']) && is_numeric($filters['prix_min'])) {
        $sql .= " AND price >= :prix_min";
        $params[':prix_min'] = $filters['prix_min'];
    }

    // Filtre par prix maximum
    if (isset($filters['prix_max']) && is_numeric($filters['prix_max'])) {
        $sql .= " AND price <= :prix_max";
        $params[':prix_max'] = $filters['prix_max'];
    }

    // Tri
    $sql .= " ORDER BY created_at DESC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur getAllVelos: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère un vélo par son ID
 *
 * @param PDO $pdo Instance PDO
 * @param int $id ID du vélo
 * @return array|false Données du vélo ou false si non trouvé
 */
function getVeloById($pdo, $id) {
    $sql = "SELECT * FROM velos WHERE id = :id";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erreur getVeloById: " . $e->getMessage());
        return false;
    }
}

/**
 * Ajoute un nouveau vélo
 *
 * Insere un nouveau velo dans la base de donnees avec toutes ses informations
 *
 * @param PDO $pdo Instance PDO
 * @param array $data Données du vélo (name, price, quantity, description, image_url)
 * @return int|false ID du vélo créé ou false en cas d'erreur
 */
function addVelo($pdo, $data) {
    // Preparation de la requete d'insertion
    $sql = "INSERT INTO velos (name, price, quantity, description, image_url) 
            VALUES (:name, :price, :quantity, :description, :image_url)";

    try {
        // Execution de la requete avec les donnees fournies
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':price' => $data['price'],
            ':quantity' => $data['quantity'],
            ':description' => $data['description'] ?? '', // Valeur par defaut si vide
            ':image_url' => $data['image_url'] ?? ''
        ]);
        // Retourne l'ID du velo nouvellement cree
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Erreur addVelo: " . $e->getMessage());
        return false;
    }
}

/**
 * Met à jour un vélo existant
 *
 * Modifie toutes les informations d'un velo identifie par son ID
 *
 * @param PDO $pdo Instance PDO
 * @param int $id ID du vélo
 * @param array $data Données à mettre à jour
 * @return bool Succès de l'opération
 */
function updateVelo($pdo, $id, $data) {
    // Preparation de la requete de mise a jour
    $sql = "UPDATE velos 
            SET name = :name, 
                price = :price, 
                quantity = :quantity, 
                description = :description, 
                image_url = :image_url 
            WHERE id = :id";

    try {
        // Execution de la mise a jour
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':price' => $data['price'],
            ':quantity' => $data['quantity'],
            ':description' => $data['description'] ?? '',
            ':image_url' => $data['image_url'] ?? ''
        ]);
    } catch (PDOException $e) {
        error_log("Erreur updateVelo: " . $e->getMessage());
        return false;
    }
}

/**
 * Supprime un vélo
 *
 * @param PDO $pdo Instance PDO
 * @param int $id ID du vélo
 * @return bool Succès de l'opération
 */
function deleteVelo($pdo, $id) {
    $sql = "DELETE FROM velos WHERE id = :id";

    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    } catch (PDOException $e) {
        error_log("Erreur deleteVelo: " . $e->getMessage());
        return false;
    }
}

/**
 * Gère l'upload d'une image de vélo
 *
 * @param array $file Fichier $_FILES
 * @return string|false Nom du fichier uploadé ou false
 */
function uploadVeloImage($file) {
    $uploadDir = __DIR__ . '/../assets/imgs/velos/';

    // Créer le dossier s'il n'existe pas
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Vérifier le type de fichier
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }

    // Vérifier la taille (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return false;
    }

    // Générer un nom unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'velo_' . uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;

    // Déplacer le fichier
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }

    return false;
}

