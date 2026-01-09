<?php
/**
 * Connexion à la base de données avec PDO
 * RESAVELO - Système de location de vélos
 *
 * Ce fichier etablit la connexion a la base de donnees MySQL
 * en utilisant PDO (PHP Data Objects) pour plus de securite
 */

// ===== Configuration de la base de donnees =====
// Ces constantes contiennent les parametres de connexion
define('DB_HOST', 'localhost');           // Serveur MySQL (localhost pour MAMP)
define('DB_NAME', 'resavelo');            // Nom de la base de donnees
define('DB_USER', 'root');                // Utilisateur MySQL
define('DB_PASS', 'root');                // Mot de passe (par defaut 'root' pour MAMP)
define('DB_CHARSET', 'utf8mb4');          // Encodage des caracteres (support des emojis)

/**
 * Crée et retourne une connexion PDO à la base de données
 *
 * Utilise le pattern Singleton pour ne creer qu'une seule instance
 * de connexion pendant toute la duree de vie du script
 *
 * @return PDO Instance PDO configurée
 * @throws PDOException En cas d'erreur de connexion
 */
function getDbConnection() {
    // Variable statique : conserve sa valeur entre les appels de fonction
    static $pdo = null;

    // Si la connexion n'existe pas encore, on la cree
    if ($pdo === null) {
        // Etape 1: Construction de la chaine DSN (Data Source Name)
        // Pour MAMP sur macOS, on utilise un socket Unix pour une connexion plus rapide
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET . ";unix_socket=/Applications/MAMP/tmp/mysql/mysql.sock";

        // Etape 2: Configuration des options PDO pour plus de securite
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Lance des exceptions en cas d'erreur
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Retourne les resultats en tableau associatif
            PDO::ATTR_EMULATE_PREPARES   => false,                   // Utilise les requetes preparees natives
        ];

        try {
            // Etape 3: Creation de l'instance PDO
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // En cas d'erreur, afficher un message d'aide
            // En production, il faudrait logger l'erreur au lieu de l'afficher
            die("Erreur de connexion à la base de données : " . $e->getMessage() .
                "<br><br>Vérifiez que :<br>- MAMP est démarré<br>- La base de données 'resavelo' existe<br>- Les identifiants sont corrects dans config/db_connect.php");
        }
    }

    // Retourne l'instance PDO (nouvelle ou existante)
    return $pdo;
}

// ===== Creation de la connexion globale =====
// Cette variable $pdo sera utilisee dans tous les scripts qui incluent ce fichier
try {
    $pdo = getDbConnection();
} catch (PDOException $e) {
    die("Impossible de se connecter à la base de données.");
}

