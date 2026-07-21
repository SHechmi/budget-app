CREATE DATABASE IF NOT EXISTS budget_app
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
 
USE budget_app;
 
-- ==============================================================
--  1. UTILISATEURS
--  (Personne 1)
-- ==============================================================
CREATE TABLE utilisateurs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nom             VARCHAR(50)  NOT NULL,
    prenom          VARCHAR(50)  NOT NULL,
    email           VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe    VARCHAR(255) NOT NULL,          -- hashé bcrypt
    role            ENUM('admin', 'user') DEFAULT 'user',
    date_inscription DATETIME    DEFAULT CURRENT_TIMESTAMP,
    actif           TINYINT(1)  DEFAULT 0           -- 0 = en attente validation admin
);
 
-- ==============================================================
--  2. CATEGORIES
--  (Personne 2)
-- ==============================================================
CREATE TABLE categories (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nom             VARCHAR(50)  NOT NULL,
    couleur         VARCHAR(7)   DEFAULT '#000000', -- code hex ex: #FF5733
    type            ENUM('revenu', 'depense') NOT NULL,
    id_utilisateur  INT DEFAULT NULL,               -- NULL = catégorie par défaut système
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE SET NULL
);
 
-- ==============================================================
--  3. BUDGETS
--  (Personne 2)
-- ==============================================================
CREATE TABLE budgets (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nom             VARCHAR(100) NOT NULL,
    type            ENUM('individuel', 'partage') DEFAULT 'individuel',
    periode         ENUM('mensuel', 'hebdomadaire', 'personnalise') DEFAULT 'mensuel',
    date_debut      DATE         NOT NULL,
    date_fin        DATE         NOT NULL,
    plafond_global  DECIMAL(10,2) DEFAULT 0.00,
    id_createur     INT          NOT NULL,
    date_creation   DATETIME     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_createur) REFERENCES utilisateurs(id) ON DELETE CASCADE
);
 
-- ==============================================================
--  4. BUDGET_CATEGORIES (plafond par catégorie dans un budget)
--  (Personne 2)
-- ==============================================================
CREATE TABLE budget_categories (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    id_budget       INT          NOT NULL,
    id_categorie    INT          NOT NULL,
    plafond         DECIMAL(10,2) DEFAULT 0.00,
    FOREIGN KEY (id_budget)    REFERENCES budgets(id)    ON DELETE CASCADE,
    FOREIGN KEY (id_categorie) REFERENCES categories(id) ON DELETE CASCADE
);
 
-- ==============================================================
--  5. BUDGETS_MEMBRES (membres d'un budget partagé)
--  (Personne 2)
-- ==============================================================
CREATE TABLE budgets_membres (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    id_budget       INT          NOT NULL,
    id_utilisateur  INT          NOT NULL,
    role            ENUM('createur', 'membre') DEFAULT 'membre',
    date_ajout      DATETIME     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_budget)       REFERENCES budgets(id)      ON DELETE CASCADE,
    FOREIGN KEY (id_utilisateur)  REFERENCES utilisateurs(id) ON DELETE CASCADE
);
 
-- ==============================================================
--  6. TRANSACTIONS
--  (Personne 2)
-- ==============================================================
CREATE TABLE transactions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    montant         DECIMAL(10,2) NOT NULL,
    type            ENUM('revenu', 'depense') NOT NULL,
    description     VARCHAR(255) DEFAULT NULL,
    date            DATE         NOT NULL,
    id_utilisateur  INT          NOT NULL,
    id_categorie    INT          DEFAULT NULL,
    id_budget       INT          DEFAULT NULL,
    date_creation   DATETIME     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (id_categorie)   REFERENCES categories(id)   ON DELETE SET NULL,
    FOREIGN KEY (id_budget)      REFERENCES budgets(id)      ON DELETE SET NULL
);
 
-- ==============================================================
--  7. COMMENTAIRES (sur les transactions — budget partagé)
--  (Personne 2)
-- ==============================================================
CREATE TABLE commentaires (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    id_transaction  INT          NOT NULL,
    id_utilisateur  INT          NOT NULL,
    contenu         TEXT         NOT NULL,
    date_commentaire DATETIME    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_transaction)  REFERENCES transactions(id)  ON DELETE CASCADE,
    FOREIGN KEY (id_utilisateur)  REFERENCES utilisateurs(id)  ON DELETE CASCADE
);
 
-- ==============================================================
--  8. ALERTES
--  (Personne 1) — après budgets
-- ==============================================================
CREATE TABLE alertes (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    id_budget           INT          NOT NULL,
    id_utilisateur      INT          NOT NULL,
    type                ENUM('seuil', 'depassement') NOT NULL,
    message             VARCHAR(255) DEFAULT NULL,
    seuil_pourcentage   INT          DEFAULT 80,      -- ex: alerte à 80%
    date_alerte         DATETIME     DEFAULT CURRENT_TIMESTAMP,
    lu                  TINYINT(1)  DEFAULT 0,        -- 0 = non lu
    FOREIGN KEY (id_budget)       REFERENCES budgets(id)      ON DELETE CASCADE,
    FOREIGN KEY (id_utilisateur)  REFERENCES utilisateurs(id) ON DELETE CASCADE
);