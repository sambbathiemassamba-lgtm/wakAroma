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
            <img src="logo/logo.jpeg" alt="Logo WakAroma" class="header__logo">
            <div class="header__title">
                <h1>WakAroma</h1>
                <p class="header__subtitle">Épices d'Afrique</p>
            </div>
        </div>

        <!-- Barre de recherche -->
        <div class="header__search">
            <form action="" method="POST" class="search-form">
                <input type="search" placeholder="Rechercher un produit" class="search-form__input">
                <button type="submit" class="search-form__button">Rechercher</button>
            </form>
        </div>
    
        <!-- Actions utilisateur -->
        <nav class="header__actions">
            <a href="login.php" class="header__link header__link--login">
                <img src="icones/login.png" alt="Connexion">
                <span>Connexion</span>
            </a>
            <a href="#pannier" class="header__link header__link--cart">
                <img src="icones/panier.png" alt="Panier">
                <span>Panier</span>
            </a>
        </nav>
    </header>

    <?php require_once 'footer.php' ?>