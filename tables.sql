
-- =========================
-- TABLE USERS
-- =========================
CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50),
    prenom VARCHAR(50),
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =========================
-- TABLE ADRESSES
-- =========================
CREATE TABLE adresses (
    id_adresse INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    ville VARCHAR(50) NOT NULL,
    code_postal VARCHAR(20),
    adresse_complete VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_adresses_users
        FOREIGN KEY (id_user)
        REFERENCES users(id_user)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- =========================
-- TABLE CATEGORIES
-- =========================
CREATE TABLE categories (
    id_categorie INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL
);

-- =========================
-- TABLE PRODUITS
-- =========================
CREATE TABLE produits (
    id_produit INT AUTO_INCREMENT PRIMARY KEY,
    id_categorie INT NOT NULL,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    reference VARCHAR(50) UNIQUE,
    prix DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_produits_categories
        FOREIGN KEY (id_categorie)
        REFERENCES categories(id_categorie)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);

-- =========================
-- TABLE CARACTERISTIQUES
-- =========================
CREATE TABLE caracteristiques (
    id_caracteristique INT AUTO_INCREMENT PRIMARY KEY,
    id_produit INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    valeur VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_caracteristiques_produits
        FOREIGN KEY (id_produit)
        REFERENCES produits(id_produit)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- =========================
-- TABLE IMAGES
-- =========================
CREATE TABLE images (
    id_image INT AUTO_INCREMENT PRIMARY KEY,
    id_produit INT NOT NULL,
    url_image VARCHAR(255) NOT NULL,

    CONSTRAINT fk_images_produits
        FOREIGN KEY (id_produit)
        REFERENCES produits(id_produit)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- =========================
-- TABLE PANIERS
-- =========================
CREATE TABLE paniers (
    id_panier INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_panier_user UNIQUE (id_user),

    CONSTRAINT fk_paniers_users
        FOREIGN KEY (id_user)
        REFERENCES users(id_user)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- =========================
-- TABLE LIGNES PANIER
-- =========================
CREATE TABLE lignes_panier (
    id_ligne_panier INT AUTO_INCREMENT PRIMARY KEY,
    id_panier INT NOT NULL,
    id_produit INT NOT NULL,
    quantite INT NOT NULL DEFAULT 1,
    prix_capture DECIMAL(10,2) NOT NULL,

    CONSTRAINT fk_lignes_panier_panier
        FOREIGN KEY (id_panier)
        REFERENCES paniers(id_panier)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_lignes_panier_produits
        FOREIGN KEY (id_produit)
        REFERENCES produits(id_produit)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- =========================
-- TABLE COMMANDES
-- =========================
CREATE TABLE commandes (
    id_commande INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    numero_commande VARCHAR(50) NOT NULL UNIQUE,
    statut VARCHAR(50) DEFAULT 'en_attente',
    total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_commandes_users
        FOREIGN KEY (id_user)
        REFERENCES users(id_user)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);

-- =========================
-- TABLE LIGNES COMMANDES
-- =========================
CREATE TABLE lignes_commandes (
    id_ligne_commande INT AUTO_INCREMENT PRIMARY KEY,
    id_commande INT NOT NULL,
    id_produit INT NULL,

    nom_produit VARCHAR(150) NOT NULL,
    reference_produit VARCHAR(50),
    quantite INT NOT NULL DEFAULT 1,
    prix DECIMAL(10,2) NOT NULL,

    CONSTRAINT fk_lignes_commandes_commande
        FOREIGN KEY (id_commande)
        REFERENCES commandes(id_commande)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_lignes_commandes_produits
        FOREIGN KEY (id_produit)
        REFERENCES produits(id_produit)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);