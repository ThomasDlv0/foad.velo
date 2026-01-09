-- Base de données RESAVELO
-- Système de location de vélos

CREATE DATABASE IF NOT EXISTS resavelo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE resavelo;

-- Table des vélos
CREATE TABLE IF NOT EXISTS velos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    description TEXT,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table des réservations
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    velo_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    customer_name VARCHAR(100),
    customer_email VARCHAR(100),
    customer_phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (velo_id) REFERENCES velos(id) ON DELETE CASCADE,
    INDEX idx_velo_id (velo_id),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Insertion de données de test pour les vélos
INSERT INTO velos (name, price, quantity, description, image_url) VALUES
('VTC Confort', 15.00, 5, 'Vélo tout chemin idéal pour la ville et les balades tranquilles. Selle confortable et position droite.', 'vtc_confort.jpg'),
('VTT Sport', 25.00, 3, 'VTT robuste pour les terrains difficiles. Suspension avant et pneus crantés.', 'vtt_sport.jpg'),
('Vélo Électrique', 40.00, 4, 'Vélo à assistance électrique, autonomie de 60km. Parfait pour les longues distances.', 'velo_electrique.jpg'),
('Vélo de Course', 30.00, 2, 'Vélo route léger et rapide. Pour les cyclistes sportifs.', 'velo_course.jpg'),
('Vélo Hollandais', 12.00, 6, 'Vélo urbain classique avec panier avant. Confort et élégance.', 'velo_hollandais.jpg'),
('VTC Électrique', 45.00, 3, 'VTC avec assistance électrique. Le meilleur des deux mondes.', 'vtc_electrique.jpg'),
('Vélo Pliant', 18.00, 4, 'Vélo compact et pliable. Facile à transporter et à ranger.', 'velo_pliant.jpg'),
('Tandem', 35.00, 2, 'Vélo pour deux personnes. Partagez vos balades en duo.', 'tandem.jpg');

-- Insertion de données de test pour les réservations
INSERT INTO reservations (velo_id, start_date, end_date, total_price, status, customer_name, customer_email, customer_phone) VALUES
(1, '2026-01-05', '2026-01-07', 30.00, 'completed', 'Jean Dupont', 'jean.dupont@email.com', '0612345678'),
(2, '2026-01-08', '2026-01-10', 50.00, 'confirmed', 'Marie Martin', 'marie.martin@email.com', '0623456789'),
(3, '2026-01-10', '2026-01-12', 80.00, 'pending', 'Pierre Durand', 'pierre.durand@email.com', '0634567890'),
(1, '2026-01-15', '2026-01-20', 75.00, 'pending', 'Sophie Bernard', 'sophie.bernard@email.com', '0645678901'),
(4, '2026-01-06', '2026-01-08', 60.00, 'cancelled', 'Luc Petit', 'luc.petit@email.com', '0656789012'),
(5, '2026-01-09', '2026-01-11', 24.00, 'confirmed', 'Julie Robert', 'julie.robert@email.com', '0667890123');

