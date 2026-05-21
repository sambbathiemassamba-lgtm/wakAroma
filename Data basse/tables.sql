
-- -- =========================
-- -- TABLE USERS
-- -- =========================
-- CREATE TABLE users (
--     id_user INT AUTO_INCREMENT PRIMARY KEY,
--     nom VARCHAR(50),
--     prenom VARCHAR(50),
--     email VARCHAR(150) NOT NULL UNIQUE,
--     password_hash VARCHAR(255) NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
-- );
--ALTER TABLE users ADD numero VARCHAR(20) NOT NULL;
--ALTER TABLE users ADD confirmation_token VARCHAR(225) NOT NULL;
-- -- =========================
-- -- TABLE ADRESSES
-- -- =========================
-- CREATE TABLE adresses (
--     id_adresse INT AUTO_INCREMENT PRIMARY KEY,
--     id_user INT NOT NULL,
--     ville VARCHAR(50) NOT NULL,
--     code_postal VARCHAR(20),
--     adresse_complete VARCHAR(255),
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

--     CONSTRAINT fk_adresses_users
--         FOREIGN KEY (id_user)
--         REFERENCES users(id_user)
--         ON DELETE CASCADE
--         ON UPDATE CASCADE
-- );

-- -- =========================
-- -- TABLE CATEGORIES
-- -- =========================
-- CREATE TABLE categories (
--     id_categorie INT AUTO_INCREMENT PRIMARY KEY,
--     nom VARCHAR(100) NOT NULL
-- );

-- -- =========================
-- -- TABLE PRODUITS
-- -- =========================
-- CREATE TABLE produits (
--     id_produit INT AUTO_INCREMENT PRIMARY KEY,
--     id_categorie INT NOT NULL,
--     nom VARCHAR(150) NOT NULL,
--     description TEXT,
--     reference VARCHAR(50) UNIQUE,
--     prix DECIMAL(10,2) NOT NULL DEFAULT 0,
--     stock INT NOT NULL DEFAULT 0,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

--     CONSTRAINT fk_produits_categories
--         FOREIGN KEY (id_categorie)
--         REFERENCES categories(id_categorie)
--         ON DELETE RESTRICT
--         ON UPDATE CASCADE
-- );

-- -- =========================
-- -- TABLE CARACTERISTIQUES
-- -- =========================
-- CREATE TABLE caracteristiques (
--     id_caracteristique INT AUTO_INCREMENT PRIMARY KEY,
--     id_produit INT NOT NULL,
--     nom VARCHAR(100) NOT NULL,
--     valeur VARCHAR(255),
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

--     CONSTRAINT fk_caracteristiques_produits
--         FOREIGN KEY (id_produit)
--         REFERENCES produits(id_produit)
--         ON DELETE CASCADE
--         ON UPDATE CASCADE
-- );

-- -- =========================
-- -- TABLE IMAGES
-- -- =========================
-- CREATE TABLE images (
--     id_image INT AUTO_INCREMENT PRIMARY KEY,
--     id_produit INT NOT NULL,
--     url_image VARCHAR(255) NOT NULL,

--     CONSTRAINT fk_images_produits
--         FOREIGN KEY (id_produit)
--         REFERENCES produits(id_produit)
--         ON DELETE CASCADE
--         ON UPDATE CASCADE
-- );

-- -- =========================
-- -- TABLE PANIERS
-- -- =========================
-- CREATE TABLE paniers (
--     id_panier INT AUTO_INCREMENT PRIMARY KEY,
--     id_user INT NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

--     CONSTRAINT uq_panier_user UNIQUE (id_user),

--     CONSTRAINT fk_paniers_users
--         FOREIGN KEY (id_user)
--         REFERENCES users(id_user)
--         ON DELETE CASCADE
--         ON UPDATE CASCADE
-- );

-- -- =========================
-- -- TABLE LIGNES PANIER
-- -- =========================
-- CREATE TABLE lignes_panier (
--     id_ligne_panier INT AUTO_INCREMENT PRIMARY KEY,
--     id_panier INT NOT NULL,
--     id_produit INT NOT NULL,
--     quantite INT NOT NULL DEFAULT 1,
--     prix_capture DECIMAL(10,2) NOT NULL,

--     CONSTRAINT fk_lignes_panier_panier
--         FOREIGN KEY (id_panier)
--         REFERENCES paniers(id_panier)
--         ON DELETE CASCADE
--         ON UPDATE CASCADE,

--     CONSTRAINT fk_lignes_panier_produits
--         FOREIGN KEY (id_produit)
--         REFERENCES produits(id_produit)
--         ON DELETE CASCADE
--         ON UPDATE CASCADE
-- );

-- -- =========================
-- -- TABLE COMMANDES
-- -- =========================
-- CREATE TABLE commandes (
--     id_commande INT AUTO_INCREMENT PRIMARY KEY,
--     id_user INT NOT NULL,
--     numero_commande VARCHAR(50) NOT NULL UNIQUE,
--     statut VARCHAR(50) DEFAULT 'en_attente',
--     total DECIMAL(10,2) NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

--     CONSTRAINT fk_commandes_users
--         FOREIGN KEY (id_user)
--         REFERENCES users(id_user)
--         ON DELETE RESTRICT
--         ON UPDATE CASCADE
-- );

-- -- =========================
-- -- TABLE LIGNES COMMANDES
-- -- =========================
-- CREATE TABLE lignes_commandes (
--     id_ligne_commande INT AUTO_INCREMENT PRIMARY KEY,
--     id_commande INT NOT NULL,
--     id_produit INT NULL,

--     nom_produit VARCHAR(150) NOT NULL,
--     reference_produit VARCHAR(50),
--     quantite INT NOT NULL DEFAULT 1,
--     prix DECIMAL(10,2) NOT NULL,

--     CONSTRAINT fk_lignes_commandes_commande
--         FOREIGN KEY (id_commande)
--         REFERENCES commandes(id_commande)
--         ON DELETE CASCADE
--         ON UPDATE CASCADE,

--     CONSTRAINT fk_lignes_commandes_produits
--         FOREIGN KEY (id_produit)
--         REFERENCES produits(id_produit)
--         ON DELETE SET NULL
--         ON UPDATE CASCADE
-- );

--====================================================================================================================================================




-- ==============================================================
-- INSERTION DES CATEGORIES
-- ==============================================================

INSERT INTO categories (nom)
VALUES
    ('Epices'),
    ('The'),
    ('Cafe');



-- ==============================================================
-- INSERTION DES PRODUITS
-- ==============================================================

INSERT INTO produits (
    id_categorie,
    nom,
    description,
    reference,
    prix,
    stock
)
VALUES

-- ==============================================================
-- EPICES  (id_categorie = 1)
-- ==============================================================

(
    1,
    'AYEEYO',
    'Mélange piquant aux saveurs généreuses, AYEYO rend hommage aux recettes transmises de génération en génération. Parfait pour assaisonner à table ou préparer des marinades savoureuses pour poulets, viandes et shawarmas.',
    'EPICE-001',
    12.00,
    200
),

(
    1,
    'DIGAAG',
    'Mélange aromatique spécialement conçu pour sublimer vos plats de poulet. DIGAAG apporte profondeur et parfum à toutes vos préparations.',
    'EPICE-002',
    12.00,
    200
),

(
    1,
    'HAWAASH',
    'Mélange d’épices aux notes délicates, parfait pour sublimer vos riz parfumés et vos sauces aux légumes.',
    'EPICE-003',
    12.00,
    150
),

(
    1,
    'HILIB',
    'Mélange robuste spécialement élaboré pour sublimer toutes vos préparations à base de viande.',
    'EPICE-004',
    12.00,
    200
),

(
    1,
    'LA BASE',
    'Le mélange essentiel et polyvalent qui sublime tous vos plats du quotidien.',
    'EPICE-005',
    12.00,
    344
),

(
    1,
    'SUMAC',
    'Mélange inspiré du sumac, baie acidulée et fruitée emblématique du Moyen-Orient.',
    'EPICE-006',
    12.00,
    212
),

(
    1,
    'WAKA',
    'Le mélange signature de WakAroma, polyvalent et audacieux.',
    'EPICE-007',
    12.00,
    43
),

-- ==============================================================
-- THE (id_categorie = 2)
-- ==============================================================

(
    2,
    'SHAAH',
    'Le joyau de notre collection, un mélange d’exception qui sublime thés, cafés et pâtisseries.',
    'THE-001',
    14.00,
    534
),

(
    2,
    'NABAD',
    'Thé noir épicé qui incarne l’hospitalité est-africaine.',
    'THE-002',
    15.00,
    234
),

-- ==============================================================
-- CAFES (id_categorie = 3)
-- ==============================================================

(
    3,
    'BOUNKA',
    'Mélange sophistiqué qui réinvente le rituel du café avec des épices africaines.',
    'CAFE-001',
    15.00,
    432
);




-- ==============================================================
-- INSERTION DES CARACTERISTIQUES
-- ==============================================================

INSERT INTO caracteristiques (
    id_produit,
    nom,
    valeur
)
VALUES
    (1, 'Poids', '80g'),
    (2, 'Poids', '130g'),
    (3, 'Poids', '130g'),
    (4, 'Poids', '80g'),
    (5, 'Poids', '80g'),
    (6, 'Poids', '80g'),
    (7, 'Poids', '80g'),
    (8, 'Poids', '130g'),
    (9, 'Poids', '130g'),
    (10, 'Poids', '130g');


-- ==============================================================
-- INSERTION DES IMAGES
-- ==============================================================

INSERT INTO images (
    id_produit,
    url_image
)
VALUES
    (1, 'images/ayeyo.png'),
    (2, 'images/digaag.png'),
    (3, 'images/hawaash.png'),
    (4, 'images/hilib.png'),
    (5, 'images/base.png'),
    (6, 'images/sumac.png'),
    (7, 'images/waka.png'),
    (8, 'images/shaah.png'),
    (9, 'images/nabad.png'),
    (10, 'images/bounka.png');
