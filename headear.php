<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>www.wakAroma.com</title>
</head>
<body>
    <header class="header">
        <!-- Logo et titre -->
        <div class="header__brgitand">
            
            <div class="header__title">
                <a href="index.php" style="text-decoration:none;">
                    <h1>WakAroma</h1>
                    <p class="header__subtitle">Épices d'Afrique</p>
                </a>
            </div>
        </div>

       <!-- Barre de recherche -->
<div class="header__search">
    <form action="index.php" method="GET" class="search-form">
        <input 
            type="search" 
            name="q"
            placeholder="Rechercher un produit" 
            class="search-form__input"
            value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
        >
        <button type="submit" class="search-form__button">Rechercher</button>
    </form>
</div>
    
        <!-- Actions utilisateur -->
            <nav class="header__actions">

                <a href="compte.php" class="header__link header__link--login">
                    <img src="icones/moncompte.png" alt="Mon compte">
                    <span>Mon compte</span>
                </a>

                <a href="panier.php" class="header__link header__link--cart">
                    <img src="icones/calebass.png" alt="Panier">
                    <span>Panier</span>
                </a>

            </nav>
    </header>