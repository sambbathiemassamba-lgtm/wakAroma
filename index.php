<?php
session_start();
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
                src="<?= htmlspecialchars($data->url_image ?? 'images/placeholder.png'); ?>" 
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
                        Découvrir
                    </button>

                </div>

                <?php if ((int)$data->stock === 0): ?>
                    <span class="rupture-label">⚠ Rupture de stock</span>
                <?php endif; ?>

                <button 
                    class="panier__btn<?= (int)$data->stock === 0 ? ' panier__btn--rupture' : '' ?>" 
                    data-id="<?= (int)$data->id_produit ?>"
                    onclick="ajouterAuPanier(this)"
                    <?= (int)$data->stock === 0 ? 'disabled' : '' ?>
                >
                    <?= (int)$data->stock === 0 ? '✕ Indisponible' : 'Ajouter au panier 🛒' ?>
                </button>

            </div>

        </article>

    <?php endforeach; ?>

</section>

<!-- Toast de notification -->
<div id="toast-index" class="toast-notif" aria-live="polite"></div>

<!-- footer -->
<?php require_once 'footer.php'; ?>

<script>
async function ajouterAuPanier(btn) {
    <?php if (empty($_SESSION['auth'])): ?>
        // Non connecté : rediriger vers login
        window.location.href = 'login.php';
        return;
    <?php endif; ?>

    const idProduit = btn.dataset.id;

    // Feedback visuel immédiat
    btn.disabled = true;
    const texteOriginal = btn.textContent;
    btn.textContent = 'Ajout en cours…';

    try {
        const body = new URLSearchParams({ action: 'ajouter', id_produit: idProduit });
        const res  = await fetch('panier.php', { method: 'POST', body });
        const json = await res.json();

        if (json.success) {
            btn.textContent = '✓ Ajouté !';
            btn.classList.add('panier__btn--added');
            afficherToast('Article ajouté au panier 🛒');
            setTimeout(() => {
                btn.textContent = texteOriginal;
                btn.classList.remove('panier__btn--added');
                btn.disabled = false;
            }, 1800);
        } else {
            afficherToast(json.message || 'Erreur lors de l\'ajout', 'error');
            btn.textContent = texteOriginal;
            btn.disabled = false;
        }
    } catch (e) {
        afficherToast('Erreur de connexion', 'error');
        btn.textContent = texteOriginal;
        btn.disabled = false;
    }
}

function afficherToast(msg, type = 'success') {
    const el = document.getElementById('toast-index');
    el.textContent = msg;
    el.className   = 'toast-notif toast-notif--' + type + ' toast-notif--visible';
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove('toast-notif--visible'), 3000);
}
</script>