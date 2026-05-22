<?php
// inclusion du fichier function
require_once 'function.php';


//recuperation produits + images
$datas = recuperation_produits_images();

?>


<!-- header -->
<?php require_once 'headear.php'; ?>

<section class="produits">

    <?php foreach($datas as $data): ?>

        <article class="produit">

            <img 
                src="<?= htmlspecialchars($data->url_image); ?>" 
                alt="<?= htmlspecialchars($data->nom); ?>"
            >

            <div class="produit__contenu">

                <h2 class="produit__titre">
                    <?= htmlspecialchars($data->nom); ?>
                </h2>

                <p class="produit__description">
                    <?= htmlspecialchars($data->description); ?>
                </p>

                <div class="produit__footer">

                    <span class="produit__prix">
                        <?= number_format($data->prix, 2); ?> €
                    </span>

                    <button class="produit__btn">
                        Decouvrire
                    </button>
                    <button class="panier__btn">
                        Ajouter au paniers 🛒
                    </button>
                </div>

            </div>

        </article>

    <?php endforeach; ?>

</section>

<!-- footer -->
<?php require_once 'footer.php'; ?>