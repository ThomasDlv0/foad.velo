# RESAVELO 🚴

Application web de location de vélos de ville développée en PHP natif avec architecture modulaire.

Test test test

## 📋 Description

RESAVELO est un système complet de gestion de location de vélos permettant aux clients de consulter un catalogue, effectuer des réservations, et aux administrateurs de gérer l'ensemble du parc de vélos et des réservations.

## ✨ Fonctionnalités

### Partie Publique (Frontend)
- 📚 **Catalogue de vélos** avec images et descriptions
- 🔍 **Filtres avancés** : disponibilité, prix min/max
- 📅 **Système de réservation** avec sélection de dates
- 💰 **Calcul automatique des prix** avec réductions pour longues durées
  - 10% de réduction à partir de 3 jours
  - 15% de réduction à partir de 7 jours
- 📧 **Consultation des réservations** par email
- ✅ **Vérification de disponibilité** en temps réel

### Partie Administration (Backend)
- ➕ **CRUD complet des vélos** (Créer, Lire, Modifier, Supprimer)
- 🖼️ **Upload d'images** pour les vélos
- 📊 **Tableau de bord** avec statistiques détaillées
  - Nombre total de réservations
  - Réservations par statut (en attente, confirmées, terminées, annulées)
  - Chiffre d'affaires total et mensuel
  - Vélos les plus loués
- 🔄 **Gestion des réservations** : validation, refus, annulation
- 🔍 **Filtres et tri** des réservations par statut
- 📋 **Vue d'ensemble** des réservations en cours, passées, annulées

## 🛠️ Technologies Utilisées

- **Backend** : PHP 7.4+ (natif, sans framework)
- **Base de données** : MySQL avec PDO
- **Frontend** : HTML5, CSS3 personnalisé (sans framework CSS)
- **JavaScript** : Vanilla JS pour le tri et filtrage dynamique
- **Serveur** : MAMP (Apache + MySQL)

## 📁 Structure du Projet

```
/foad_velo
├── /config
│   └── db_connect.php              # Connexion PDO à la base de données
├── /includes
│   ├── functions_velos.php         # Fonctions CRUD vélos
│   ├── functions_reservation.php   # Fonctions gestion réservations
│   └── functions_calculation.php   # Calculs prix et disponibilités
├── /admin
│   ├── index.php                   # Tableau de bord administrateur
│   ├── velos.php                   # Liste des vélos
│   ├── velo_form.php              # Ajout/Modification vélo
│   └── reservations.php            # Gestion des réservations
├── /public
│   ├── index.php                   # Catalogue public
│   ├── reservation_form.php        # Formulaire de réservation
│   └── mes_reservations.php        # Consultation réservations client
├── /assets
│   ├── /css
│   │   ├── style.css              # Styles principaux
│   │   └── admin.css              # Styles administration
│   ├── /js
│   │   └── reservations.js        # Tri et filtrage dynamique
│   └── /imgs
│       ├── default-bike.svg       # Image par défaut
│       └── /velos                 # Images uploadées des vélos
├── /data
│   └── database.sql               # Script de création de la BDD
└── README.md
```

## 🚀 Installation

### Prérequis
- MAMP (ou XAMPP/WAMP)
- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Navigateur web moderne

### Étapes d'installation

1. **Cloner le projet**
   ```bash
   cd /Applications/MAMP/htdocs/
   git clone [votre-repo] foad_velo
   ```

2. **Créer la base de données**
   - Ouvrir phpMyAdmin (http://localhost:8888/phpMyAdmin)
   - Créer une nouvelle base de données nommée `resavelo`
   - Importer le fichier `data/database.sql`
   
   Ou via la ligne de commande :
   ```bash
   mysql -u root -p < data/database.sql
   ```

3. **Configurer la connexion à la base de données**
   
   Éditer le fichier `config/db_connect.php` si nécessaire :
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'resavelo');
   define('DB_USER', 'root');
   define('DB_PASS', 'root'); // Mot de passe par défaut MAMP
   ```

4. **Créer le dossier pour les images**
   ```bash
   mkdir -p assets/imgs/velos
   chmod 755 assets/imgs/velos
   ```

5. **Démarrer MAMP**
   - Lancer MAMP
   - Démarrer les serveurs Apache et MySQL
   - Vérifier que le port est bien 8888 (ou adapter les URLs)

6. **Accéder à l'application**
   - Site public : http://localhost:8888/foad_velo/public/
   - Administration : http://localhost:8888/foad_velo/admin/

## 📊 Modèle de Base de Données

### Table `velos`
| Champ | Type | Description |
|-------|------|-------------|
| id | INT | Clé primaire auto-incrémentée |
| name | VARCHAR(100) | Nom du vélo |
| price | DECIMAL(10,2) | Prix journalier |
| quantity | INT | Quantité disponible |
| description | TEXT | Description du vélo |
| image_url | VARCHAR(255) | Nom du fichier image |
| created_at | TIMESTAMP | Date de création |

### Table `reservations`
| Champ | Type | Description |
|-------|------|-------------|
| id | INT | Clé primaire auto-incrémentée |
| velo_id | INT | Référence au vélo (clé étrangère) |
| start_date | DATE | Date de début de location |
| end_date | DATE | Date de fin de location |
| total_price | DECIMAL(10,2) | Prix total calculé |
| status | ENUM | Statut : pending, confirmed, cancelled, completed |
| customer_name | VARCHAR(100) | Nom du client |
| customer_email | VARCHAR(100) | Email du client |
| customer_phone | VARCHAR(20) | Téléphone du client |
| created_at | TIMESTAMP | Date de réservation |

## 🔧 Fonctions Principales

### Gestion des Vélos
- `getAllVelos($pdo, $filters)` - Récupère tous les vélos avec filtres optionnels
- `getVeloById($pdo, $id)` - Récupère un vélo par son ID
- `addVelo($pdo, $data)` - Ajoute un nouveau vélo
- `updateVelo($pdo, $id, $data)` - Met à jour un vélo
- `deleteVelo($pdo, $id)` - Supprime un vélo
- `uploadVeloImage($file)` - Gère l'upload d'images

### Gestion des Réservations
- `createReservation($pdo, $velo_id, $start_date, $end_date, $customer_info)` - Crée une réservation
- `getAllReservations($pdo, $filters)` - Récupère toutes les réservations
- `updateReservationStatus($pdo, $id, $status)` - Change le statut d'une réservation
- `cancelReservation($pdo, $id)` - Annule une réservation
- `checkAvailability($pdo, $velo_id, $start_date, $end_date)` - Vérifie la disponibilité
- `getReservationStats($pdo)` - Récupère les statistiques
- `getMostRentedVelos($pdo, $limit)` - Vélos les plus loués

### Calculs
- `calculatePrice($price_per_day, $start_date, $end_date)` - Calcule le prix total avec réductions
- `calculateDays($start_date, $end_date)` - Calcule le nombre de jours
- `validateReservationDates($start_date, $end_date)` - Valide les dates de réservation
- `formatPrice($amount)` - Formate un montant en euros
- `formatDate($date)` - Formate une date au format français

## 🎨 Design

Le design est entièrement personnalisé sans utilisation de frameworks CSS (Bootstrap, Tailwind, etc.).

**Caractéristiques :**
- Design responsive (mobile-first)
- Palette de couleurs moderne et professionnelle
- Cards avec effets hover
- Animations CSS subtiles
- Interface intuitive et accessible

## 🔒 Sécurité

- Requêtes préparées PDO (protection contre les injections SQL)
- Validation des données côté serveur
- Échappement HTML avec `htmlspecialchars()`
- Validation des types de fichiers pour les uploads
- Limite de taille pour les images (5MB)
- Gestion des erreurs avec logs

## 📱 Responsive Design

L'application est entièrement responsive et s'adapte à tous les écrans :
- Mobile (< 768px)
- Tablette (768px - 1024px)
- Desktop (> 1024px)

## 🧪 Données de Test

La base de données est pré-remplie avec :
- 8 vélos différents (VTC, VTT, vélos électriques, etc.)
- 6 réservations d'exemple avec différents statuts
- Prix variés de 12€ à 45€ par jour

## 🚧 Améliorations Possibles

- [ ] Système d'authentification pour les clients
- [ ] Envoi d'emails de confirmation
- [ ] Paiement en ligne
- [ ] Système de notation des vélos
- [ ] Calendrier de disponibilité visuel
- [ ] Export PDF des réservations
- [ ] API REST pour intégration mobile
- [ ] Gestion multilingue

## 📝 Licence

Projet développé dans le cadre d'une formation FOAD PHP.

## 👨‍💻 Auteur

Développé en janvier 2026

## 📞 Support

Pour toute question ou problème :
1. Vérifier que MAMP est démarré
2. Vérifier la configuration de la base de données
3. Consulter les logs d'erreurs PHP
4. Vérifier les permissions du dossier `assets/imgs/velos`

---

**Note** : Ce projet est une application de démonstration à but éducatif. Pour une utilisation en production, des améliorations de sécurité et de performance seraient nécessaires.

