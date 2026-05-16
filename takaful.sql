-- ============================================
-- BASE DE DONNÉES TAKAFUL
-- Importer via phpMyAdmin
-- ============================================

CREATE DATABASE IF NOT EXISTS takaful CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE takaful;

-- Table des membres (utilisateurs)
CREATE TABLE membre (
    id VARCHAR(36) PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    wilaya VARCHAR(100),
    role ENUM('membre','membre_association','president_siege','president_association','admin') DEFAULT 'membre',
    statut ENUM('actif','inactif','suspendu') DEFAULT 'actif',
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table des administrateurs
CREATE TABLE administrateur (
    id VARCHAR(36) PRIMARY KEY,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    statut ENUM('actif','inactif') DEFAULT 'actif'
);

-- Table des associations
CREATE TABLE association (
    id VARCHAR(36) PRIMARY KEY,
    nom VARCHAR(200) NOT NULL,
    description TEXT,
    president_id VARCHAR(36),
    statut ENUM('en_attente','active','suspendue','rejetee') DEFAULT 'en_attente',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (president_id) REFERENCES membre(id) ON DELETE SET NULL
);

-- Table des sièges
CREATE TABLE siege (
    id VARCHAR(36) PRIMARY KEY,
    nom VARCHAR(200) NOT NULL,
    wilaya VARCHAR(100) NOT NULL,
    adresse TEXT,
    association_id VARCHAR(36) NOT NULL,
    president_siege_id VARCHAR(36),
    statut ENUM('actif','inactif') DEFAULT 'actif',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (association_id) REFERENCES association(id) ON DELETE CASCADE,
    FOREIGN KEY (president_siege_id) REFERENCES membre(id) ON DELETE SET NULL
);

-- Table des membres d'association
CREATE TABLE membre_association (
    id VARCHAR(36) PRIMARY KEY,
    membre_id VARCHAR(36) NOT NULL,
    association_id VARCHAR(36) NOT NULL,
    date_adhesion DATE DEFAULT (CURRENT_DATE),
    statut ENUM('actif','inactif') DEFAULT 'actif',
    FOREIGN KEY (membre_id) REFERENCES membre(id) ON DELETE CASCADE,
    FOREIGN KEY (association_id) REFERENCES association(id) ON DELETE CASCADE
);

-- Table des dons
CREATE TABLE don (
    id VARCHAR(36) PRIMARY KEY,
    donateur_id VARCHAR(36) NOT NULL,
    siege_id VARCHAR(36),
    type ENUM('financier','materiel') NOT NULL,
    categorie VARCHAR(100),
    montant DECIMAL(10,2) DEFAULT 0,
    description TEXT,
    statut ENUM('en_attente','confirme','collecte','annule') DEFAULT 'en_attente',
    date_don DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donateur_id) REFERENCES membre(id) ON DELETE CASCADE,
    FOREIGN KEY (siege_id) REFERENCES siege(id) ON DELETE SET NULL
);

-- Table des demandes d'aide
CREATE TABLE demande_aide (
    id VARCHAR(36) PRIMARY KEY,
    demandeur_id VARCHAR(36) NOT NULL,
    siege_id VARCHAR(36),
    sujet VARCHAR(255) NOT NULL,
    description TEXT,
    type_aide ENUM('financiere','medicale','alimentaire','autre') DEFAULT 'autre',
    statut ENUM('soumise','en_cours','acceptee','refusee','resolue') DEFAULT 'soumise',
    date_demande DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (demandeur_id) REFERENCES membre(id) ON DELETE CASCADE,
    FOREIGN KEY (siege_id) REFERENCES siege(id) ON DELETE SET NULL
);

-- Table des missions
CREATE TABLE mission (
    id VARCHAR(36) PRIMARY KEY,
    siege_id VARCHAR(36) NOT NULL,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('distribution','transport','sensibilisation','autre') DEFAULT 'autre',
    date_mission DATETIME,
    statut ENUM('planifiee','en_cours','terminee','annulee') DEFAULT 'planifiee',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (siege_id) REFERENCES siege(id) ON DELETE CASCADE
);

-- Table des assignations (membre_association à mission/demande/don)
CREATE TABLE assignation (
    id VARCHAR(36) PRIMARY KEY,
    membre_association_id VARCHAR(36),
    mission_id VARCHAR(36),
    demande_id VARCHAR(36),
    don_id VARCHAR(36),
    date_assignation DATE DEFAULT (CURRENT_DATE),
    statut ENUM('assignee','en_cours','terminee','annulee') DEFAULT 'assignee',
    note TEXT,
    FOREIGN KEY (membre_association_id) REFERENCES membre_association(id) ON DELETE SET NULL,
    FOREIGN KEY (mission_id) REFERENCES mission(id) ON DELETE SET NULL,
    FOREIGN KEY (demande_id) REFERENCES demande_aide(id) ON DELETE SET NULL,
    FOREIGN KEY (don_id) REFERENCES don(id) ON DELETE SET NULL
);

-- Table des candidatures de siège (pour devenir président de siège)
CREATE TABLE candidature_siege (
    id VARCHAR(36) PRIMARY KEY,
    membre_id VARCHAR(36) NOT NULL,
    siege_id VARCHAR(36) NOT NULL,
    message TEXT,
    statut ENUM('en_attente','acceptee','refusee') DEFAULT 'en_attente',
    date_candidature DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (membre_id) REFERENCES membre(id) ON DELETE CASCADE,
    FOREIGN KEY (siege_id) REFERENCES siege(id) ON DELETE CASCADE
);

-- Table des annonces de maintenance (admin)
CREATE TABLE annonce (
    id VARCHAR(36) PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    contenu TEXT NOT NULL,
    admin_id VARCHAR(36),
    date_debut DATETIME,
    date_fin DATETIME,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES administrateur(id) ON DELETE SET NULL
);

-- ============================================
-- DONNÉES INITIALES
-- ============================================

-- Admin par défaut (mot de passe: admin123)
INSERT INTO administrateur (id, email, mot_de_passe, statut) VALUES
(UUID(), 'admin@takaful.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'actif');

-- Quelques membres de test (mot de passe: password)
INSERT INTO membre (id, nom, prenom, email, mot_de_passe, telephone, wilaya, role, statut) VALUES
('m1', 'Benali', 'Ahmed', 'ahmed@test.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0555000001', 'Alger', 'president_association', 'actif'),
('m2', 'Mansouri', 'Fatima', 'fatima@test.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0555000002', 'Oran', 'president_siege', 'actif'),
('m3', 'Khelil', 'Karim', 'karim@test.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0555000003', 'Constantine', 'membre_association', 'actif'),
('m4', 'Ouali', 'Sara', 'sara@test.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0555000004', 'Alger', 'membre', 'actif');

-- Association de test
INSERT INTO association (id, nom, description, president_id, statut) VALUES
('a1', 'Nour El Amal', 'Association humanitaire pour les familles démunies', 'm1', 'active');

-- Siège de test
INSERT INTO siege (id, nom, wilaya, adresse, association_id, president_siege_id, statut) VALUES
('s1', 'Siège Central Alger', 'Alger', '12 Rue Didouche Mourad, Alger', 'a1', 'm2', 'actif');

-- Membre d'association
INSERT INTO membre_association (id, membre_id, association_id, date_adhesion, statut) VALUES
(UUID(), 'm3', 'a1', CURRENT_DATE, 'actif');
