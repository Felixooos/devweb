-- Script de création des tables pour le budget manager

CREATE TABLE IF NOT EXISTS E_utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    budget_mensuel DECIMAL(10, 2) NOT NULL DEFAULT 0,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS E_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    icone VARCHAR(10) NOT NULL
);

CREATE TABLE IF NOT EXISTS E_sous_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    budget_max DECIMAL(10, 2) NOT NULL DEFAULT 0,
    FOREIGN KEY (categorie_id) REFERENCES E_categories(id)
);

CREATE TABLE IF NOT EXISTS E_depenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    sous_categorie_id INT NOT NULL,
    montant DECIMAL(10, 2) NOT NULL,
    date_depense DATE NOT NULL,
    date_saisie DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES E_utilisateurs(id),
    FOREIGN KEY (sous_categorie_id) REFERENCES E_sous_categories(id)
);

-- Données d'exemple pour les catégories
INSERT INTO E_categories (nom, icone) VALUES
('Alimentation', '🍔'),
('Transport', '🚗'),
('Logement', '🏠'),
('Loisirs', '🎮'),
('Santé', '💊');

-- Données d'exemple pour les sous-catégories
INSERT INTO E_sous_categories (categorie_id, nom, budget_max) VALUES
(1, 'Courses', 300.00),
(1, 'Restaurant', 100.00),
(2, 'Essence', 150.00),
(2, 'Transport en commun', 75.00),
(3, 'Loyer', 800.00),
(3, 'Électricité', 80.00),
(3, 'Internet', 30.00),
(4, 'Sorties', 100.00),
(4, 'Abonnements', 50.00),
(5, 'Pharmacie', 30.00),
(5, 'Médecin', 50.00);
