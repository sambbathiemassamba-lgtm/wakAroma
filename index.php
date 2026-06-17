<?php
session_start();
require_once 'function.php';

// ── Redirection automatique entreprises ─────────────────────────────────────
if (!empty($_SESSION['auth']['is_entreprise'])) {
    header('Location: index_entreprise.php');
    exit;
}
// ── Recherche (barre du header : index.php?q=...) ────────────────────────────
// Filtre sur le nom du produit, la description OU la catégorie (Café, Épices, Thé…)
$recherche = trim($_GET['q'] ?? '');

$datas_raw = recuperation_produits_images($recherche !== '' ? $recherche : null);

// Dédoublonnage : on garde un seul enregistrement par produit
// (la première image rencontrée est conservée)
$datas = [];
foreach ($datas_raw as $row) {
    if (!isset($datas[$row->id_produit])) {
        $datas[$row->id_produit] = $row;
    }
}
?>

<?php require_once 'headear.php'; ?>

<!-- ══════════════════════════════════════════
     HERO SECTION
     ══════════════════════════════════════════ -->

<style>
/* Lien sur l'image produit */
.produit__img-link {
    display: block;
    position: relative;
    width: 100%;
    height: 100%;
    text-decoration: none;
    color: inherit;
}
.produit__img-link img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.produit__unite {
    font-size: 0.78rem;
    color: #b0a898;
    background: rgba(200,148,58,0.10);
    border: 1px solid rgba(200,148,58,0.25);
    border-radius: 20px;
    padding: 2px 9px;
    font-weight: 600;
    letter-spacing: 0.02em;
    white-space: nowrap;
    align-self: center;
}
</style>

<section class="hero" aria-label="Bannière principale">
    <div class="hero__bg-overlay"></div>

    <div class="hero__content">
        <p class="hero__eyebrow">Directement sourcé d'Afrique</p>
        <h2 class="hero__title">
            L'Afrique<br>
            <em>parfume</em><br>
            vos instants
        </h2>
        <p class="hero__desc">
            Des épices rares, des mélanges authentiques,<br>
            cueillis à la main et livrés chez vous.
        </p>
        <div class="hero__ctas">
            <a href="#produits" class="hero__cta hero__cta--primary">
                Découvrir nos épices
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
            <a href="historique.php" class="hero__cta hero__cta--ghost">Notre histoire</a>
        </div>

        <!-- Stats -->
        <div class="hero__stats">
            <div class="hero__stat">
                <span class="hero__stat-num">50+</span>
                <span class="hero__stat-label">Épices & aromates</span>
            </div>
            <div class="hero__stat-sep"></div>
            <div class="hero__stat">
                <span class="hero__stat-num">100%</span>
                <span class="hero__stat-label">Naturels & purs</span>
            </div>
            <div class="hero__stat-sep"></div>
            <div class="hero__stat">
                <span class="hero__stat-num">24h</span>
                <span class="hero__stat-label">Expédition rapide</span>
            </div>
        </div>
    </div>

    <!-- Scroll indicator -->
    <div class="hero__scroll" aria-hidden="true">
        <div class="hero__scroll-dot"></div>
    </div>
</section>



<!-- ══════════════════════════════════════════
     SECTION PRODUITS
     ══════════════════════════════════════════ -->
<div class="section-header" id="produits">
    <?php if ($recherche !== ''): ?>
        <div class="section-header__eyebrow">Résultats de recherche</div>
        <h2 class="section-header__title">« <?= htmlspecialchars($recherche) ?> »</h2>
        <p class="section-header__sub">
            <?= count($datas) ?> produit<?= count($datas) > 1 ? 's' : '' ?> trouvé<?= count($datas) > 1 ? 's' : '' ?>
            — <a href="index.php#produits" style="color:#c8943a;font-weight:600;">Voir tous les produits</a>
        </p>
    <?php else: ?>
        <div class="section-header__eyebrow">Notre sélection</div>
        <h2 class="section-header__title">Les Épices du Moment</h2>
        <p class="section-header__sub">Chaque produit est soigneusement sélectionné pour vous offrir le meilleur de l'Afrique.</p>
    <?php endif; ?>
</div>

<!-- Filtres dynamiques depuis la table catégories -->
<?php
// Fonction pour créer un slug depuis le nom de catégorie
function slugify($str) {
    $str = mb_strtolower(trim($str), 'UTF-8');
    $str = str_replace(['é','è','ê','ë'], 'e', $str);
    $str = str_replace(['à','â','ä'], 'a', $str);
    $str = str_replace(['ô','ö'], 'o', $str);
    $str = str_replace(['î','ï'], 'i', $str);
    $str = str_replace(['ù','û','ü'], 'u', $str);
    $str = str_replace(['ç'], 'c', $str);
    $str = preg_replace('/[^a-z0-9]+/', '-', $str);
    return trim($str, '-');
}

// Récupérer uniquement les catégories qui ont au moins un produit affiché
$categories_filtre = [];
foreach ($datas as $d) {
    $cat = $d->nom_categorie ?? '';
    if ($cat !== '' && !in_array($cat, $categories_filtre, true)) {
        $categories_filtre[] = $cat;
    }
}
sort($categories_filtre);
?>
<div class="filters-bar">
    <button class="filter-btn filter-btn--active" data-filter="all">Tous</button>
    <?php foreach($categories_filtre as $cat_nom): ?>
    <button class="filter-btn" data-filter="<?= htmlspecialchars(slugify($cat_nom)) ?>"><?= htmlspecialchars($cat_nom) ?></button>
    <?php endforeach; ?>
</div>

<section class="produits" id="produit">

    <?php if ($recherche !== '' && empty($datas)): ?>
        <div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:#6b6b6b;">
            <div style="font-size:2.5rem;margin-bottom:12px;">🔍</div>
            <p style="font-size:1.05rem;font-weight:600;color:#1f4f2e;margin-bottom:6px;">
                Aucun produit trouvé pour « <?= htmlspecialchars($recherche) ?> »
            </p>
            <p style="font-size:.9rem;">Essayez avec un autre mot, ou une catégorie comme « café », « épices » ou « thé ».</p>
        </div>
    <?php endif; ?>

    <?php foreach($datas as $data): ?>

        <article class="produit" data-category="<?= htmlspecialchars(slugify($data->nom_categorie ?? '')) ?>">

            <div class="produit__img-wrap">
                <a href="decouvrir.php?id=<?= (int)$data->id_produit ?>" class="produit__img-link" aria-label="Voir <?= htmlspecialchars($data->nom) ?>">
                    <img
                        src="<?= htmlspecialchars($data->url_image ?? 'images/placeholder.png'); ?>"
                        alt="<?= htmlspecialchars($data->nom); ?>"
                        loading="lazy"
                    >
                    <!-- Badge -->
                    <?php if((int)$data->stock <= 5 && (int)$data->stock > 0): ?>
                        <span class="produit__badge produit__badge--low">Dernières pièces</span>
                    <?php elseif((int)$data->stock === 0): ?>
                        <span class="produit__badge produit__badge--rupture">Épuisé</span>
                    <?php else: ?>
                        <span class="produit__badge produit__badge--new">Disponible</span>
                    <?php endif; ?>
                    <!-- Overlay au hover -->
                    <div class="produit__overlay">
                        <span class="produit__btn-quick">Voir le produit</span>
                    </div>
                </a>

                <!-- Wishlist (hors du lien pour éviter le conflit de clic) -->
                <button
                    class="produit__wishlist"
                    aria-label="Ajouter aux favoris"
                    data-id="<?= (int)$data->id_produit ?>"
                    data-nom="<?= htmlspecialchars($data->nom) ?>"
                    data-prix="<?= htmlspecialchars($data->prix) ?>"
                    data-img="<?= htmlspecialchars($data->url_image ?? '') ?>"
                    onclick="toggleFavoriIndex(this)"
                >♡</button>
            </div>

            <div class="produit__contenu">
                <p class="produit__categorie"><?= htmlspecialchars($data->nom_categorie ?? 'Épice') ?> · WakAroma</p>

                <h2 class="produit__titre">
                    <?= htmlspecialchars($data->nom); ?>
                </h2>

                <p class="produit__description">
                    <?= htmlspecialchars($data->description); ?>
                </p>
            </div>

            <div class="produit__footer">
                <div class="produit__prix-row">
                    <span class="produit__prix"><?= number_format($data->prix, 2); ?> €</span>
                    <?php if (!empty($data->unite)): ?>
                        <span class="produit__unite"><?= htmlspecialchars($data->unite) ?></span>
                    <?php endif; ?>
                </div>

                <div class="produit__stock <?= (int)$data->stock > 0 ? 'produit__stock--ok' : '' ?>">
                    <?php if((int)$data->stock > 0): ?>
                        <svg width="10" height="10" viewBox="0 0 10 10"><circle cx="5" cy="5" r="4" fill="#2d7a44"/></svg>
                        En stock (<?= (int)$data->stock ?>)
                    <?php else: ?>
                        <svg width="10" height="10" viewBox="0 0 10 10"><circle cx="5" cy="5" r="4" fill="#e74c3c"/></svg>
                        Rupture de stock
                    <?php endif; ?>
                </div>

                <div class="produit__actions">
                    <button class="produit__btn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        <a href="decouvrir.php?id=<?= (int)$data->id_produit ?>" class="produit__btn">
                            Découvrir
                        </a>
                    </button>
                </div>

                <?php if((int)$data->stock === 0): ?>
                    <span class="rupture-label">⚠ Indisponible</span>
                <?php endif; ?>

                <button
                    class="panier__btn<?= (int)$data->stock === 0 ? ' panier__btn--rupture' : '' ?>"
                    data-id="<?= (int)$data->id_produit ?>"
                    onclick="ajouterAuPanier(this)"
                    <?= (int)$data->stock === 0 ? 'disabled' : '' ?>
                >
                    <?php if((int)$data->stock === 0): ?>
                        ✕ Indisponible
                    <?php else: ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                        Ajouter au panier
                    <?php endif; ?>
                </button>
            </div>

        </article>

    <?php endforeach; ?>

</section>

<?php if ($recherche !== ''): ?>
<!-- Après une recherche, on amène directement le visiteur sur les résultats -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('produits');
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
});
</script>
<?php endif; ?>

<!-- ══════════════════════════════════════════
     SECTION UNIVERS / EDITO
     ══════════════════════════════════════════ -->
<section class="edito-section" id="historique">
    <div class="edito-grid">

        <div class="edito-card edito-card--large">
            <div class="edito-card__icon-badge">
                <img src="icones/logo_afrique.png" alt="" onerror="this.style.display='none'">
            </div>
            <div class="edito-card__content">
                <p class="edito-card__eyebrow">Notre histoire</p>
                <h3 class="edito-card__title">L'Afrique au cœur de chaque épice</h3>
                <p class="edito-card__text">Fondée avec passion, WakAroma sélectionne les meilleures épices directement auprès des producteurs africains pour vous offrir une expérience gustative unique.</p>
                <a href="historique.php" class="edito-card__link">En savoir plus →</a>
            </div>
        </div>

        <div class="edito-card edito-card--gold">
            <div class="edito-card__icon-badge">
                <img src="icones/epices.png" alt="" onerror="this.style.display='none'">
            </div>
            <div class="edito-card__content">
                <p class="edito-card__eyebrow">Savoir-faire</p>
                <h3 class="edito-card__title">Mélanges artisanaux</h3>
                <p class="edito-card__text">Chaque mélange est préparé à la main selon des recettes ancestrales.</p>
                <a href="#" class="edito-card__link">Découvrir →</a>
            </div>
        </div>

        <div class="edito-card edito-card--beige">
            <div class="edito-card__icon-badge">
                <img src="icones/cardamone.png" alt="" onerror="this.style.display='none'">
            </div>
            <div class="edito-card__content">
                <p class="edito-card__eyebrow">Nos salons</p>
                <h3 class="edito-card__title">Venez nous rencontrer</h3>
                <p class="edito-card__text">Découvrez nos épices en boutique et laissez-vous guider par nos experts.</p>
                <a href="salon.php" class="edito-card__link edito-card__link--green">Trouver un salon →</a>
            </div>
        </div>

    </div>
</section>

<!-- Newsletter -->
<?php require_once 'newsletter.php'?>


<!-- Toast de notification -->
<div id="toast-index" class="toast-notif" aria-live="polite"></div>

<!-- ══ Bannière Cookies ══ -->
<?php require_once 'cookies.php'; ?>

<!-- Footer -->
<?php require_once 'footer.php'; ?>


<script>
// ── Badge panier ──────────────────────────────────────────────
function wrapCartIcon() {
  // Cherche le lien vers panier.php dans le header
  const cartLink = document.querySelector('a[href*="panier"]');
  if (!cartLink) return;
  // Évite de wrapper deux fois
  if (cartLink.querySelector('#cart-badge-count')) return;

  cartLink.style.position = 'relative';
  cartLink.style.display  = 'inline-flex';
  cartLink.style.alignItems = 'center';

  const badge = document.createElement('span');
  badge.id = 'cart-badge-count';
  badge.textContent = '0';
  cartLink.appendChild(badge);
}

function updateCartBadge(nb, animate = false) {
  const badge = document.getElementById('cart-badge-count');
  if (!badge) return;
  const n = parseInt(nb) || 0;
  badge.textContent = n > 99 ? '99+' : n;
  if (n > 0) {
    badge.classList.add('visible');
  } else {
    badge.classList.remove('visible');
  }
  if (animate && n > 0) {
    badge.classList.remove('bump');
    void badge.offsetWidth; // force reflow
    badge.classList.add('bump');
    setTimeout(() => badge.classList.remove('bump'), 300);
  }
}

async function fetchCartCount() {
  <?php if (!empty($_SESSION['auth'])): ?>
  try {
    const body = new URLSearchParams({ action: 'get_count' });
    const res  = await fetch('panier.php', { method: 'POST', body });
    const json = await res.json();
    if (json.success) updateCartBadge(json.nb_articles);
  } catch(e) {}
  <?php endif; ?>
}

document.addEventListener('DOMContentLoaded', () => {
  wrapCartIcon();
  fetchCartCount();
});
</script>

<script>
async function ajouterAuPanier(btn) {
    <?php if (empty($_SESSION['auth'])): ?>
        header('Location: login.php');
        exit();
    <?php endif; ?>
    const idProduit = btn.dataset.id;
    btn.disabled = true;
    const texteOriginal = btn.innerHTML;
    btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Ajout…';
    try {
        const body = new URLSearchParams({ action: 'ajouter', id_produit: idProduit });
        const res  = await fetch('panier.php', { method: 'POST', body });
        const json = await res.json();
        if (json.success) {
            btn.innerHTML = '✓ Ajouté !';
            btn.classList.add('panier__btn--added');
            afficherToast('Article ajouté au panier 🛒');
            if (json.nb_articles !== undefined) updateCartBadge(json.nb_articles, true);
            setTimeout(() => {
                btn.innerHTML = texteOriginal;
                btn.classList.remove('panier__btn--added');
                btn.disabled = false;
            }, 1800);
        } else {
            afficherToast(json.message || "Erreur lors de l'ajout", 'error');
            btn.innerHTML = texteOriginal;
            btn.disabled = false;
        }
    } catch (e) {
        afficherToast('Erreur de connexion', 'error');
        btn.innerHTML = texteOriginal;
        btn.disabled = false;
    }
}
function afficherToast(msg, type = 'success') {
    const el = document.getElementById('toast-index');
    el.textContent = msg;
    el.className = 'toast-notif toast-notif--' + type + ' toast-notif--visible';
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove('toast-notif--visible'), 3000);
}

</script>
<script>
// ══════════════════════════════════════════
//  SYSTÈME FAVORIS — WakAroma (synchronisé BDD)
// ══════════════════════════════════════════

async function toggleFavoriIndex(btn) {
    // Si non connecté → rediriger vers login
    <?php if (empty($_SESSION['auth'])): ?>
    window.location.href = 'login.php';
    return;
    <?php endif; ?>

    const id = btn.dataset.id;

    // Animation pop
    btn.classList.add('pop');
    btn.addEventListener('animationend', () => btn.classList.remove('pop'), { once: true });

    try {
        const res  = await fetch('favoris.php', {
            method: 'POST',
            body: new URLSearchParams({ action: 'toggle', id_produit: id })
        });
        const json = await res.json();

        if (json.success) {
            if (json.actif) {
                btn.classList.add('actif');
                btn.innerHTML = '♥';
                btn.setAttribute('aria-label', 'Retirer des favoris');
                afficherToast('❤️ Ajouté aux favoris !');
            } else {
                btn.classList.remove('actif');
                btn.innerHTML = '♡';
                btn.setAttribute('aria-label', 'Ajouter aux favoris');
                afficherToast('Retiré des favoris');
            }
        } else {
            afficherToast(json.message || 'Erreur', 'error');
        }
    } catch(e) {
        afficherToast('Erreur de connexion', 'error');
    }
}

// Au chargement : récupérer les IDs favoris depuis la BDD pour colorier les boutons
document.addEventListener('DOMContentLoaded', async () => {
    <?php if (!empty($_SESSION['auth'])): ?>
    try {
        const res  = await fetch('favoris.php', {
            method: 'POST',
            body: new URLSearchParams({ action: 'get_ids' })
        });
        const json = await res.json();
        if (json.success && json.ids.length > 0) {
            document.querySelectorAll('.produit__wishlist').forEach(btn => {
                if (json.ids.includes(parseInt(btn.dataset.id))) {
                    btn.classList.add('actif');
                    btn.innerHTML = '♥';
                    btn.setAttribute('aria-label', 'Retirer des favoris');
                }
            });
        }
    } catch(e) { /* silencieux */ }
    <?php endif; ?>
});
</script>

<script>
// ══════════════════════════════════════════
//  FILTRAGE PAR CATÉGORIE — WakAroma
// ══════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const produits   = document.querySelectorAll('.produits .produit');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Activer le bouton cliqué
            filterBtns.forEach(b => b.classList.remove('filter-btn--active'));
            btn.classList.add('filter-btn--active');

            const filtre = btn.dataset.filter;

            produits.forEach(article => {
                if (filtre === 'all' || article.dataset.category === filtre) {
                    article.style.display = '';
                } else {
                    article.style.display = 'none';
                }
            });
        });
    });
});
</script>
