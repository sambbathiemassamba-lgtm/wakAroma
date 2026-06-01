<?php
session_start();
require_once 'function.php';
$datas = recuperation_produits_images();
?>

<?php require_once 'headear.php'; ?>

<!-- ══════════════════════════════════════════
     HERO SECTION
     ══════════════════════════════════════════ -->

<!-- ══ CSS Système de notation ══ -->
<style>
/* ── Widget de notation ── */
.produit__rating {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin: 2px 0 4px;
}

/* Étoiles cliquables */
.stars-input {
    display: flex;
    gap: 2px;
    align-items: center;
}

.star-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 18px;
    color: #ddd;
    padding: 2px;
    line-height: 1;
    transition: color 0.15s, transform 0.12s;
    /* Zone de touch confortable */
    min-width: 28px;
    min-height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
}

.star-btn:hover,
.star-btn.hover {
    color: #f5c518;
    transform: scale(1.2);
}

.star-btn.active {
    color: #c8943a;
    transform: scale(1.1);
}

/* État "déjà voté" */
.stars-input.voted .star-btn {
    cursor: default;
}
.stars-input.voted .star-btn:hover {
    transform: none;
}

/* Barre de résultat */
.stars-result {
    display: flex;
    align-items: center;
    gap: 7px;
}

.stars-bar-wrap {
    flex: 1;
    height: 5px;
    background: #e8e0d6;
    border-radius: 99px;
    overflow: hidden;
    max-width: 90px;
}

.stars-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #c8943a, #e8b860);
    border-radius: 99px;
    transition: width 0.5s cubic-bezier(0.22,1,0.36,1);
}

.stars-pct {
    font-size: 0.78rem;
    font-weight: 700;
    color: #c8943a;
    min-width: 36px;
}

.stars-count {
    font-size: 0.72rem;
    color: #b0a898;
    white-space: nowrap;
}

/* Animation au vote */
@keyframes starPop {
    0%   { transform: scale(1); }
    40%  { transform: scale(1.4); }
    70%  { transform: scale(0.9); }
    100% { transform: scale(1.1); }
}
.star-btn.pop {
    animation: starPop 0.35s ease forwards;
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
            <a href="#historique" class="hero__cta hero__cta--ghost">Notre histoire</a>
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
    <div class="section-header__eyebrow">Notre sélection</div>
    <h2 class="section-header__title">Les Épices du Moment</h2>
    <p class="section-header__sub">Chaque produit est soigneusement sélectionné pour vous offrir le meilleur de l'Afrique.</p>
</div>

<!-- Filtres -->
<div class="filters-bar">
    <button class="filter-btn filter-btn--active" data-filter="all">Tous</button>
    <button class="filter-btn" data-filter="epices">Épices</button>
    <button class="filter-btn" data-filter="melanges">Mélanges</button>
    <button class="filter-btn" data-filter="huiles">Huiles</button>
    <button class="filter-btn" data-filter="bio">Bio</button>
</div>

<section class="produits" id="produit">

    <?php foreach($datas as $data): ?>

        <article class="produit" data-category="epices">

            <div class="produit__img-wrap">
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

                <!-- Wishlist -->
                <button class="produit__wishlist" aria-label="Ajouter aux favoris" onclick="this.classList.toggle('actif')">♡</button>

                <!-- Overlay au hover -->
                <div class="produit__overlay">
                    <button class="produit__btn-quick">Aperçu rapide</button>
                </div>
            </div>

            <div class="produit__contenu">
                <p class="produit__categorie">Épice · WakAroma</p>

                <h2 class="produit__titre">
                    <?= htmlspecialchars($data->nom); ?>
                </h2>

                <div class="produit__rating" data-id="<?= (int)$data->id_produit ?>">
                    <!-- Étoiles interactives -->
                    <div class="stars-input" role="radiogroup" aria-label="Donner une note">
                        <?php for($i=1;$i<=5;$i++): ?>
                        <button
                            type="button"
                            class="star-btn"
                            data-val="<?= $i ?>"
                            aria-label="<?= $i ?> étoile<?= $i>1?'s':'' ?>"
                            title="<?= $i ?> étoile<?= $i>1?'s':'' ?>"
                        >★</button>
                        <?php endfor; ?>
                    </div>
                    <!-- Résultat : barre % + compteur -->
                    <div class="stars-result">
                        <div class="stars-bar-wrap">
                            <div class="stars-bar-fill" style="width:0%"></div>
                        </div>
                        <span class="stars-pct">—</span>
                        <span class="stars-count"></span>
                    </div>
                </div>

                <p class="produit__description">
                    <?= htmlspecialchars($data->description); ?>
                </p>
            </div>

            <div class="produit__footer">
                <div class="produit__prix-row">
                    <span class="produit__prix"><?= number_format($data->prix, 2); ?> €</span>
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

<!-- ══════════════════════════════════════════
     SECTION UNIVERS / EDITO
     ══════════════════════════════════════════ -->
<section class="edito-section" id="historique">
    <div class="edito-grid">

        <div class="edito-card edito-card--large">

            <div class="edito-card__content">
                <p class="edito-card__eyebrow">Notre histoire</p>

                <h3 class="edito-card__title"> L'Afrique au cœur de chaque épice </h3>

                <p class="edito-card__text">
                    Fondée avec passion, WakAroma sélectionne les meilleures épices directement auprès des producteurs africains pour vous offrir une expérience gustative unique.
                </p>

                <a href="#" class="edito-card__link"> En savoir plus → </a>
            </div>

            <div class="edito-card__deco" aria-hidden="true"> 🌍 </div>

        </div>

        <div class="edito-card edito-card--gold">

            <div class="edito-card__content">

                <p class="edito-card__eyebrow"> Savoir-faire </p>

                <h3 class="edito-card__title"> Mélanges artisanaux </h3>

                <p class="edito-card__text"> Chaque mélange est préparé à la main selon des recettes ancestrales. </p>

                <a href="#" class="edito-card__link"> Découvrir → </a>

            </div>

            <div class="edito-card__deco" aria-hidden="true"> 🫙 </div>

        </div>

        <div class="edito-card edito-card--beige">

            <div class="edito-card__content">

                <p class="edito-card__eyebrow">
                    Nos salons
                </p>

                <h3 class="edito-card__title">
                    Venez nous rencontrer
                </h3>

                <p class="edito-card__text">
                    Découvrez nos épices en boutique et laissez-vous guider par nos experts.
                </p>

                <a href="#salon" class="edito-card__link edito-card__link--green">
                    Trouver un salon →
                </a>

            </div>

            <div class="edito-card__deco" aria-hidden="true"> 🌿 </div>
        </div>
    </div>
</section>

<!-- Newsletter -->
<?php require_once 'newsletter.php'?>


<!-- Toast de notification -->
<div id="toast-index" class="toast-notif" aria-live="polite"></div>

<!-- Footer -->
<?php require_once 'footer.php'; ?>

<!-- ══════════════════════════════════════════
     CHATBOT WAKAROMA
     ══════════════════════════════════════════ -->
<?php require_once 'chatbox.php'?>


<script src="script/index.js"></script>

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
//  SYSTÈME DE NOTATION — WakAroma
// ══════════════════════════════════════════

// Charge les stats de tous les produits au démarrage
async function chargerToutesLesNotes() {
    try {
        const res  = await fetch('avis.php?action=get_all_stats');
        const json = await res.json();
        if (!json.success) return;

        document.querySelectorAll('.produit__rating').forEach(widget => {
            const id     = parseInt(widget.dataset.id);
            const stats  = json.stats[id]   || { moyenne: 0, nb_votes: 0, pct: 0 };
            const maNote = json.mes_notes[id] || 0;
            mettreAJourWidget(widget, stats, maNote);
        });
    } catch(e) {
        console.warn('Impossible de charger les notes :', e);
    }
}

// Met à jour visuellement un widget
function mettreAJourWidget(widget, stats, maNote) {
    const starsInput = widget.querySelector('.stars-input');
    const barFill    = widget.querySelector('.stars-bar-fill');
    const pctEl      = widget.querySelector('.stars-pct');
    const countEl    = widget.querySelector('.stars-count');

    // Barre et pourcentage
    barFill.style.width = stats.pct + '%';
    pctEl.textContent   = stats.nb_votes > 0 ? stats.pct + '%' : '—';
    countEl.textContent = stats.nb_votes > 0
        ? `(${stats.nb_votes} avis)`
        : '(0 avis)';

    // Colorer les étoiles selon la moyenne affichée
    const moyenne = stats.moyenne;
    widget.querySelectorAll('.star-btn').forEach(btn => {
        const v = parseInt(btn.dataset.val);
        btn.classList.toggle('active', v <= Math.round(moyenne));
    });

    // Si le visiteur a déjà voté, marquer son vote
    if (maNote > 0) {
        marquerMonVote(starsInput, maNote);
    }
}

// Colore les étoiles correspondant au vote du visiteur
function marquerMonVote(starsInput, note) {
    starsInput.classList.add('voted');
    starsInput.querySelectorAll('.star-btn').forEach(btn => {
        const v = parseInt(btn.dataset.val);
        btn.classList.toggle('active', v <= note);
        btn.setAttribute('aria-pressed', v <= note ? 'true' : 'false');
    });
}

// ── Initialisation des widgets au chargement ──
document.addEventListener('DOMContentLoaded', () => {
    // Survol pour preview
    document.querySelectorAll('.produit__rating').forEach(widget => {
        const starsInput = widget.querySelector('.stars-input');
        const btns       = starsInput.querySelectorAll('.star-btn');

        btns.forEach(btn => {
            const val = parseInt(btn.dataset.val);

            // Survol : allumer jusqu'à cette étoile
            btn.addEventListener('mouseenter', () => {
                if (starsInput.classList.contains('voted')) return;
                btns.forEach(b => b.classList.toggle('hover', parseInt(b.dataset.val) <= val));
            });

            // Fin de survol : éteindre
            btn.addEventListener('mouseleave', () => {
                btns.forEach(b => b.classList.remove('hover'));
            });

            // Clic : voter
            btn.addEventListener('click', () => {
                if (starsInput.classList.contains('voted')) return;
                voter(widget, val);
            });
        });
    });

    chargerToutesLesNotes();
});

// Envoie le vote au serveur
async function voter(widget, note) {
    const idProduit = parseInt(widget.dataset.id);
    const starsInput = widget.querySelector('.stars-input');
    const btns       = starsInput.querySelectorAll('.star-btn');

    // Feedback immédiat (optimistic UI)
    btns.forEach(b => {
        const v = parseInt(b.dataset.val);
        if (v <= note) { b.classList.add('active', 'pop'); }
        else           { b.classList.remove('active'); }
    });

    try {
        const body = new URLSearchParams({ action: 'voter', id_produit: idProduit, note });
        const res  = await fetch('avis.php', { method: 'POST', body });
        const json = await res.json();

        if (json.success) {
            mettreAJourWidget(widget, json.stats, json.ma_note);
            marquerMonVote(starsInput, json.ma_note);
            afficherToast(`Merci pour votre ${note}★ !`);
        } else {
            afficherToast('Erreur lors du vote', 'error');
        }
    } catch(e) {
        afficherToast('Erreur de connexion', 'error');
    }
}
</script>
</script>