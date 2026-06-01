
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
--ALTER TABLE users ADD souvenir_token VARCHAR(225) NOT NULL;

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
-- ALTER TABLE produits ADD COLUMN seuil_alerte INT NOT NULL DEFAULT 10;
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







-- ══════════════════════════════════════════════════════════════
-- TABLE decouvrir_produit
-- Reliée à la table produits via id_produit (FK)
-- Contient : description longue + image dédiée page découvrir
-- ══════════════════════════════════════════════════════════════

-- CREATE TABLE decouvrir_produit (
--     id_decouvrir     INT AUTO_INCREMENT PRIMARY KEY,
--     id_produit       INT NOT NULL UNIQUE,           -- 1 fiche par produit
--     description_long TEXT NOT NULL,                 -- texte long éditorial
--     image_url        VARCHAR(255) NOT NULL,          -- photo différente de images.url_image

--     created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

--     CONSTRAINT fk_decouvrir_produit
--         FOREIGN KEY (id_produit)
--         REFERENCES produits(id_produit)
--         ON DELETE CASCADE
--         ON UPDATE CASCADE
-- );


-- ══════════════════════════════════════════════════════════════
-- INSERTION DES FICHES DÉCOUVRIR
-- (id_produit correspond aux INSERT dans tables.sql)
--   1  → AYEEYO
--   2  → DIGAAG
--   3  → HAWAASH
--   4  → HILIB
--   5  → LA BASE
--   6  → SUMAC
--   7  → WAKA
--   8  → SHAAH
--   9  → NABAD
--  10  → BOUNKA
-- ══════════════════════════════════════════════════════════════

INSERT INTO decouvrir_produit (id_produit, description_long, image_url) VALUES

-- ─── 1 · AYEEYO ───────────────────────────────────────────────
(
    1,
    'Un hommage aux recettes transmises de génération en génération, AYEYO incarne la sagesse culinaire des grand-mères africaines. Ce mélange piquant et parfumé est le gardien des saveurs authentiques qui rehaussent chaque plat avec caractère.\n\nVéritable allié de votre cuisine quotidienne, AYEYO sublime vos marinades de poulet et viandes pour des shawarmas irrésistibles, tout en apportant cette touche d\'assaisonnement parfaite directement à table. Sa chaleur maîtrisée réveille les papilles sans les brusquer, offrant cet équilibre délicat entre piquant et profondeur aromatique.\n\nPosez AYEYO sur votre table : il deviendra vite le complice incontournable de vos repas, celui qui transforme l\'ordinaire en extraordinaire, comme le faisaient nos grand-mères avec amour et savoir-faire.',
    'images/decouvrir/ayeyo_detail.png'
),

-- ─── 2 · DIGAAG ───────────────────────────────────────────────
(
    2,
    'Laissez-vous transporter par les arômes envoûtants de DIGAAG, le compagnon idéal de tous vos plats de poulet. Ce mélange harmonieux d\'épices soigneusement sélectionnées a été créé pour révéler toute la tendreté et la saveur de vos volailles.\n\nQue vous prépariez un poulet en sauce onctueuse, un rôti du dimanche ou des brochettes grillées, DIGAAG apporte cette dimension parfumée qui fait toute la différence. Son bouquet aromatique enveloppe délicatement la chair du poulet, créant une symphonie de saveurs qui éveille les sens et réchauffe les cœurs.\n\nDIGAAG, c\'est l\'essence même de la cuisine généreuse, celle qui rassemble autour de la table et crée des souvenirs gourmands inoubliables.',
    'images/decouvrir/digaag_detail.png'
),

-- ─── 3 · HAWAASH ──────────────────────────────────────────────
(
    3,
    'Découvrez HAWAASH, le secret des plats réconfortants qui réchauffent le cœur et l\'âme. Ce mélange d\'épices aux notes délicates est né pour magnifier vos riz parfumés et vos sauces onctueuses aux légumes.\n\nImaginez un riz moelleux embaumé par les arômes subtils de HAWAASH, ou une sauce aux petits pois qui prend une dimension nouvelle, une sauce aux haricots verts qui éveille les papilles avec douceur. HAWAASH est cette touche magique qui transforme les légumes du quotidien en véritables célébrations gustatives.\n\nPolyvalent et généreux, HAWAASH s\'invite dans votre cuisine pour créer ces plats familiaux qui rassemblent, ces recettes transmises avec amour, ces saveurs qui font voyager sans quitter sa table.',
    'images/decouvrir/hawaash_detail.png'
),

-- ─── 4 · HILIB ────────────────────────────────────────────────
(
    4,
    'HILIB est le gardien des traditions culinaires où la viande est reine. Ce mélange robuste et généreux a été pensé pour magnifier toutes vos préparations carnées, de la plus simple à la plus élaborée.\n\nQu\'il s\'agisse d\'une sauce bolognaise mijotée avec amour, d\'un ragoût de bœuf fondant qui embaume toute la maison, ou de toute autre recette où la viande est à l\'honneur, HILIB apporte cette profondeur de saveurs incomparable. Ses épices soigneusement dosées caressent la viande, l\'enrobent et révèlent toute sa richesse.\n\nHILIB, c\'est l\'essence même des plats réconfortants qui se partagent en famille, ces recettes qui mijotent doucement et créent des souvenirs impérissables autour d\'une table généreuse.',
    'images/decouvrir/hilib_detail.png'
),

-- ─── 5 · LA BASE ──────────────────────────────────────────────
(
    5,
    'Comme son nom l\'indique, LA BASE est LE mélange indispensable, celui qui ne quitte jamais votre cuisine. C\'est la fondation de toutes vos créations culinaires, l\'essentiel qui transforme l\'ordinaire en extraordinaire.\n\nPolyvalent et équilibré, LA BASE s\'adapte à tous vos plats du quotidien avec une élégance naturelle. Un grain de riz à parfumer, une soupe à rehausser, des légumes à sublimer, une viande à assaisonner… LA BASE répond présent, toujours juste, jamais de trop.\n\nC\'est le compagnon fidèle des cuisiniers avisés, celui qui vous permet d\'improviser avec assurance, de créer sans limites. LA BASE, c\'est votre secret pour une cuisine savoureuse au quotidien, celle qui fait dire "mais qu\'est-ce que tu as mis dedans ?" à chaque bouchée.',
    'images/decouvrir/base_detail.png'
),

-- ─── 6 · SUMAC ────────────────────────────────────────────────
(
    6,
    'Laissez-vous séduire par SUMAC, ce mélange précieux qui tire son nom de l\'une des épices les plus emblématiques du Moyen-Orient. Le sumac, baie rouge pourpre au goût acidulé et fruité, est utilisé depuis des millénaires dans les cuisines levantine, turque et persane.\n\nNotre SUMAC marie harmonieusement cette épice ancestrale à d\'autres aromates soigneusement sélectionnés, créant un mélange aux notes citronnées et légèrement acidulées qui réveillent les papilles. Véritable trésor gustatif, il sublime les grillades, apporte fraîcheur aux salades, magnifie les mezze, et transforme vos poissons et légumes rôtis en véritables festins.\n\nSUMAC, c\'est l\'invitation au voyage, la promesse d\'une cuisine ensoleillée où chaque plat devient une célébration des saveurs méditerranéennes et orientales. Un essentiel pour ceux qui aiment explorer et créer.',
    'images/decouvrir/sumac_detail.png'
),

-- ─── 7 · WAKA ─────────────────────────────────────────────────
(
    7,
    'WAKA, c\'est l\'âme même de WakAroma concentrée dans un flacon. Ce mélange signature incarne notre philosophie : réveiller les saveurs, célébrer l\'authenticité, et transformer chaque instant culinaire en moment de bonheur partagé.\n\nPolyvalent et audacieux, WAKA est le compagnon idéal de vos grillades fumantes qui embaument l\'air d\'été, de vos sauces pour poissons délicats, et même de vos pizzas maison auxquelles il apporte ce petit quelque chose d\'inattendu. Sa palette aromatique équilibrée s\'adapte avec élégance à une multitude de préparations, prouvant qu\'un seul mélange peut être mille possibilités.\n\nWAKA, c\'est notre signature gustative, celle qui fait dire à vos convives : "Mais d\'où vient ce goût extraordinaire ?" La réponse ? De l\'Afrique qui parfume vos instants.',
    'images/decouvrir/waka_detail.png'
),

-- ─── 8 · SHAAH ────────────────────────────────────────────────
(
    8,
    'Voici le joyau de la collection WakAroma, la pépite rare qui transforme l\'ordinaire en extraordinaire. SHAAH, c\'est notre truffe, notre caviar, le trésor précieux que nous avons créé pour les palais exigeants et les âmes créatives.\n\nCe mélange d\'exception sublime vos thés et cafés d\'une dimension aromatique incomparable, comme une caresse parfumée qui réchauffe le cœur. Mais SHAAH ne s\'arrête pas là : il révèle des notes insoupçonnées dans vos pâtisseries, apporte sophistication à vos gâteaux, et crée des boissons épicées envoûtantes qui émerveillent à chaque gorgée.\n\nRare et précieux, SHAAH incarne l\'excellence et l\'art de l\'assemblage. C\'est le mélange des moments d\'exception, celui que l\'on réserve aux instants privilégiés, aux invités de marque, ou simplement à soi-même quand on veut se faire plaisir. SHAAH, c\'est l\'excellence africaine sublimée.',
    'images/decouvrir/shaah_detail.png'
),

-- ─── 9 · NABAD ────────────────────────────────────────────────
(
    9,
    'Nabad signifie "paix" et "bonjour" en somali. Ce thé noir épicé incarne l\'essence même de l\'hospitalité est-africaine, cette tradition sacrée où chaque invité est accueilli avec générosité et chaleur humaine.\n\nNABAD marie la profondeur du thé noir aux épices chaleureuses qui réchauffent le cœur et apaisent l\'âme. Chaque tasse est une invitation au partage, un moment suspendu où l\'on prend le temps de se retrouver, d\'échanger, de créer des liens. C\'est le thé des retrouvailles, des conversations qui s\'éternisent, des rires partagés autour d\'une table accueillante.\n\nOffrir NABAD, c\'est offrir la paix, c\'est dire "bienvenue", c\'est perpétuer cette belle tradition africaine où l\'hospitalité n\'est pas un geste mais une philosophie de vie.',
    'images/decouvrir/nabad_detail.png'
),

-- ─── 10 · BOUNKA ──────────────────────────────────────────────
(
    10,
    'BOUNKA réinvente le rituel du café en y insufflant l\'âme épicée de l\'Afrique de l\'Est. Ce mélange sophistiqué transforme votre tasse matinale en une expérience sensorielle inoubliable, où chaque gorgée devient une célébration.\n\nAjoutez une pincée de BOUNKA à votre café et laissez-vous transporter par cette harmonie parfumée qui caresse le palais et éveille l\'esprit. Les épices soigneusement sélectionnées dialoguent avec l\'amertume du café, créant un équilibre délicat entre chaleur, profondeur et douceur aromatique.\n\nBOUNKA, c\'est bien plus qu\'un café : c\'est un voyage, un moment de contemplation, l\'éveil des sens au rythme des traditions ancestrales. Pour ceux qui refusent l\'ordinaire et choisissent de commencer chaque journée avec élégance et caractère.',
    'images/decouvrir/bounka_detail.png'
);