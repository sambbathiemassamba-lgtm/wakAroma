<?php
session_start();
require_once 'pdo.php';
require_once 'function.php';

// ──────────────────────────────────────────────────────────────
// ACTIONS AJAX (appelées par fetch() en JS)
// ──────────────────────────────────────────────────────────────

// Helper : compte le total d'articles dans le panier (somme des quantités)
function compterArticlesPanier($pdo, $id_user) {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(lp.quantite), 0) AS nb
        FROM paniers pan
        INNER JOIN lignes_panier lp ON lp.id_panier = pan.id_panier
        WHERE pan.id_user = :id
    ");
    $stmt->execute([':id' => $id_user]);
    return (int) $stmt->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // L'utilisateur doit être connecté
    if (empty($_SESSION['auth'])) {
        echo json_encode(['success' => false, 'message' => 'Non connecté', 'nb_articles' => 0]);
        exit();
    }

    $id_user = (int) $_SESSION['auth']['id_user'];
    $action  = $_POST['action'];

    // ── Compter les articles (appelé au chargement de page) ──
    if ($action === 'get_count') {
        echo json_encode(['success' => true, 'nb_articles' => compterArticlesPanier($pdo, $id_user)]);
        exit();
    }

    // ── Récupérer ou créer le panier de l'utilisateur ──
    $panier = $pdo->prepare("SELECT id_panier FROM paniers WHERE id_user = :id");
    $panier->execute([':id' => $id_user]);
    $row = $panier->fetch(PDO::FETCH_OBJ);

    if (!$row) {
        $pdo->prepare("INSERT INTO paniers (id_user) VALUES (:id)")->execute([':id' => $id_user]);
        $id_panier = (int) $pdo->lastInsertId();
    } else {
        $id_panier = (int) $row->id_panier;
    }

    // ── Ajouter un produit ──
    if ($action === 'ajouter') {
        $id_produit = (int) $_POST['id_produit'];

        // Récupérer le prix actuel
        $prod = $pdo->prepare("SELECT prix, stock FROM produits WHERE id_produit = :id");
        $prod->execute([':id' => $id_produit]);
        $produit = $prod->fetch(PDO::FETCH_OBJ);

        if (!$produit || $produit->stock <= 0) {
            echo json_encode(['success' => false, 'message' => 'Produit indisponible']);
            exit();
        }

        // Vérifier si déjà dans le panier
        $exist = $pdo->prepare("SELECT id_ligne_panier, quantite FROM lignes_panier WHERE id_panier = :p AND id_produit = :prod");
        $exist->execute([':p' => $id_panier, ':prod' => $id_produit]);
        $ligne = $exist->fetch(PDO::FETCH_OBJ);

        if ($ligne) {
            $newQty = $ligne->quantite + 1;
            if ($newQty > $produit->stock) {
                echo json_encode(['success' => false, 'message' => 'Stock insuffisant']);
                exit();
            }
            $pdo->prepare("UPDATE lignes_panier SET quantite = :q WHERE id_ligne_panier = :id")
                ->execute([':q' => $newQty, ':id' => $ligne->id_ligne_panier]);
        } else {
            $pdo->prepare("INSERT INTO lignes_panier (id_panier, id_produit, quantite, prix_capture) VALUES (:p, :prod, 1, :prix)")
                ->execute([':p' => $id_panier, ':prod' => $id_produit, ':prix' => $produit->prix]);
        }

        echo json_encode(['success' => true, 'message' => 'Produit ajouté au panier', 'nb_articles' => compterArticlesPanier($pdo, $id_user)]);
        exit();
    }

    // ── Modifier la quantité ──
    if ($action === 'modifier_quantite') {
        $id_ligne   = (int) $_POST['id_ligne'];
        $quantite   = (int) $_POST['quantite'];

        if ($quantite <= 0) {
            $pdo->prepare("DELETE FROM lignes_panier WHERE id_ligne_panier = :id AND id_panier = :p")
                ->execute([':id' => $id_ligne, ':p' => $id_panier]);
        } else {
            // Vérifier le stock
            $stockCheck = $pdo->prepare("
                SELECT p.stock FROM produits p
                INNER JOIN lignes_panier lp ON lp.id_produit = p.id_produit
                WHERE lp.id_ligne_panier = :id
            ");
            $stockCheck->execute([':id' => $id_ligne]);
            $s = $stockCheck->fetch(PDO::FETCH_OBJ);

            if ($s && $quantite > $s->stock) {
                echo json_encode(['success' => false, 'message' => 'Stock insuffisant (max ' . $s->stock . ')']);
                exit();
            }

            $pdo->prepare("UPDATE lignes_panier SET quantite = :q WHERE id_ligne_panier = :id AND id_panier = :p")
                ->execute([':q' => $quantite, ':id' => $id_ligne, ':p' => $id_panier]);
        }

        echo json_encode(['success' => true, 'nb_articles' => compterArticlesPanier($pdo, $id_user)]);
        exit();
    }

    // ── Supprimer une ligne ──
    if ($action === 'supprimer') {
        $id_ligne = (int) $_POST['id_ligne'];
        $pdo->prepare("DELETE FROM lignes_panier WHERE id_ligne_panier = :id AND id_panier = :p")
            ->execute([':id' => $id_ligne, ':p' => $id_panier]);
        echo json_encode(['success' => true, 'nb_articles' => compterArticlesPanier($pdo, $id_user)]);
        exit();
    }

    // ── Vider le panier ──
    if ($action === 'vider') {
        $pdo->prepare("DELETE FROM lignes_panier WHERE id_panier = :p")
            ->execute([':p' => $id_panier]);
        echo json_encode(['success' => true, 'nb_articles' => 0]);
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Action inconnue']);
    exit();
}

// ──────────────────────────────────────────────────────────────
// AFFICHAGE PAGE
// ──────────────────────────────────────────────────────────────

// Rediriger si non connecté
if (empty($_SESSION['auth'])) {
    header("Location: login.php");
    exit();
}

$id_user = (int) $_SESSION['auth']['id_user'];

// Récupérer les lignes du panier
$req = $pdo->prepare("
    SELECT
        lp.id_ligne_panier,
        lp.quantite,
        lp.prix_capture,
        p.id_produit,
        p.nom,
        p.description,
        p.stock,
        i.url_image
    FROM paniers pan
    INNER JOIN lignes_panier lp ON lp.id_panier = pan.id_panier
    INNER JOIN produits p       ON p.id_produit  = lp.id_produit
    LEFT  JOIN images i         ON i.id_produit  = p.id_produit
    WHERE pan.id_user = :id
    ORDER BY lp.id_ligne_panier ASC
");
$req->execute([':id' => $id_user]);
$lignes = $req->fetchAll(PDO::FETCH_OBJ);

// Calcul total
$total = 0;
foreach ($lignes as $l) {
    $total += $l->prix_capture * $l->quantite;
}
?>
<?php require_once 'headear.php'; ?>

<main class="panier-page">

    <!-- ── EN-TÊTE PANIER ── -->
    <div class="panier-header">
        <a href="index.php" class="panier-retour">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
            Continuer mes achats
        </a>
        <h1 class="panier-titre">
            Mon Panier
            <?php if (!empty($lignes)): ?>
                <span class="panier-badge"><?= count($lignes) ?></span>
            <?php endif; ?>
        </h1>
    </div>

    <?php if (empty($lignes)): ?>
    <!-- ── PANIER VIDE ── -->
    <div class="panier-vide">
        <div class="panier-vide__icone">🛒</div>
        <h2>Votre panier est vide</h2>
        <p>Découvrez nos épices et thés d'exception</p>
        <a href="index.php" class="btn-continuer">Explorer la boutique</a>
    </div>

    <?php else: ?>
    <!-- ── CONTENU PANIER ── -->
    <div class="panier-contenu">

        <!-- Colonne gauche : liste des articles -->
        <section class="panier-articles">

            <div class="panier-articles__entete">
                <span>Produit</span>
                <span>Prix unitaire</span>
                <span>Quantité</span>
                <span>Sous-total</span>
                <span></span>
            </div>

            <ul class="panier-liste" id="panier-liste">
            <?php foreach ($lignes as $l): ?>
                <li class="panier-ligne" id="ligne-<?= $l->id_ligne_panier ?>">

                    <!-- Image -->
                    <div class="ligne-img">
                        <img src="<?= htmlspecialchars($l->url_image ?? 'images/placeholder.png') ?>"
                             alt="<?= htmlspecialchars($l->nom) ?>">
                    </div>

                    <!-- Infos produit -->
                    <div class="ligne-info">
                        <h3 class="ligne-nom"><?= htmlspecialchars($l->nom) ?></h3>
                        <p class="ligne-desc"><?= htmlspecialchars(mb_substr($l->description, 0, 60)) ?>…</p>
                        <?php if ($l->stock <= 5): ?>
                            <span class="ligne-alerte">⚠ Plus que <?= $l->stock ?> en stock</span>
                        <?php endif; ?>
                    </div>

                    <!-- Prix unitaire -->
                    <div class="ligne-prix-unit">
                        <?= number_format($l->prix_capture, 2) ?> €
                    </div>

                    <!-- Contrôle quantité -->
                    <div class="ligne-qte">
                        <button class="qte-btn qte-moins"
                                onclick="changerQuantite(<?= $l->id_ligne_panier ?>, parseInt(document.getElementById('qte-<?= $l->id_ligne_panier ?>').textContent) - 1)"
                                >−</button>
                        <span class="qte-valeur" id="qte-<?= $l->id_ligne_panier ?>" data-stock="<?= $l->stock ?>"><?= $l->quantite ?></span>
                        <button class="qte-btn qte-plus" id="plus-<?= $l->id_ligne_panier ?>"
                                onclick="changerQuantite(<?= $l->id_ligne_panier ?>, parseInt(document.getElementById('qte-<?= $l->id_ligne_panier ?>').textContent) + 1)"
                                <?= $l->quantite >= $l->stock ? 'disabled' : '' ?>>+</button>
                    </div>

                    <!-- Sous-total -->
                    <div class="ligne-sous-total" id="sous-total-<?= $l->id_ligne_panier ?>">
                        <?= number_format($l->prix_capture * $l->quantite, 2) ?> €
                    </div>

                    <!-- Supprimer -->
                    <button class="ligne-suppr"
                            onclick="supprimerLigne(<?= $l->id_ligne_panier ?>)"
                            aria-label="Supprimer">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                    </button>

                </li>
            <?php endforeach; ?>
            </ul>

            <!-- Vider le panier -->
            <div class="panier-actions-bas">
                <button class="btn-vider" onclick="viderPanier()">🗑 Vider le panier</button>
            </div>

        </section>

        <!-- Colonne droite : récapitulatif -->
        <aside class="panier-recap">
            <h2 class="recap-titre">Récapitulatif</h2>

            <div class="recap-ligne">
                <span>Sous-total</span>
                <span id="recap-sous-total"><?= number_format($total, 2) ?> €</span>
            </div>
            <div class="recap-ligne">
                <span>Livraison</span>
                <span class="recap-livraison">
                    <?= $total >= 50 ? '<span class="gratuit">Gratuite 🎉</span>' : 'À calculer' ?>
                </span>
            </div>
            <?php if ($total < 50): ?>
            <p class="recap-info-livraison">
                Encore <strong><?= number_format(50 - $total, 2) ?> €</strong> pour la livraison offerte
            </p>
            <?php endif; ?>

            <div class="recap-separateur"></div>

            <div class="recap-total">
                <span>Total</span>
                <span id="recap-total"><?= number_format($total, 2) ?> €</span>
            </div>

            <a href="paiement.php" class="btn-paiement">
                Procéder au paiement
            </a>

            <div class="recap-securite">
                <span>🔒 Paiement 100% sécurisé</span>
                <span>↩ Retours gratuits sous 30 jours</span>
            </div>
        </aside>

    </div>
    <?php endif; ?>

</main>

<!-- Toast de notification -->
<div id="toast" class="toast-notif" aria-live="polite"></div>

<?php require_once 'footer.php'; ?>

<script>
// ── Badge panier (sync avec l'icône dans le header) ───────────
function wrapCartIcon() {
  const cartLink = document.querySelector('a[href*="panier"]');
  if (!cartLink || cartLink.querySelector('#cart-badge-count')) return;
  cartLink.style.position = 'relative';
  cartLink.style.display  = 'inline-flex';
  cartLink.style.alignItems = 'center';
  const badge = document.createElement('span');
  badge.id = 'cart-badge-count';
  badge.textContent = '0';
  badge.style.cssText = 'position:absolute;top:-8px;right:-8px;min-width:18px;height:18px;padding:0 4px;background:#c77f2c;color:#fff;font-size:.68rem;font-weight:700;border-radius:999px;display:flex;align-items:center;justify-content:center;line-height:1;border:2px solid #fff;pointer-events:none;z-index:10;transform:scale(0);transition:transform .2s cubic-bezier(.34,1.56,.64,1);';
  cartLink.appendChild(badge);
}
function updateCartBadge(nb) {
  const badge = document.getElementById('cart-badge-count');
  if (!badge) return;
  const n = parseInt(nb) || 0;
  badge.textContent = n > 99 ? '99+' : n;
  badge.style.transform = n > 0 ? 'scale(1)' : 'scale(0)';
}
document.addEventListener('DOMContentLoaded', () => {
  wrapCartIcon();
  // Initialiser le badge avec le nombre de lignes déjà dans le panier
  const nbLignes = <?= count($lignes) ?>;
  // Calculer la vraie somme des quantités
  const totalQte = <?= array_sum(array_column((array)$lignes, 'quantite')) ?? 0 ?>;
  updateCartBadge(totalQte);
});

// ──────────────────────────────────────────────────────────────
// UTILITAIRES
// ──────────────────────────────────────────────────────────────
async function postAction(data) {
    const body = new URLSearchParams(data);
    const res  = await fetch('panier.php', { method: 'POST', body });
    return res.json();
}

function toast(msg, type = 'success') {
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.className   = 'toast-notif toast-notif--' + type + ' toast-notif--visible';
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove('toast-notif--visible'), 3000);
}

function recalculerTotaux() {
    let total = 0;
    document.querySelectorAll('.panier-ligne').forEach(ligne => {
        const id    = ligne.id.replace('ligne-', '');
        const qte   = parseInt(document.getElementById('qte-' + id)?.textContent || '0');
        const stEl  = document.getElementById('sous-total-' + id);
        if (!stEl) return;
        // Extraire le prix unitaire affiché
        const prixEl = ligne.querySelector('.ligne-prix-unit');
        const prix   = parseFloat(prixEl?.textContent?.replace(',', '.')) || 0;
        const st     = prix * qte;
        stEl.textContent = st.toFixed(2).replace('.', ',') + ' €';
        total += st;
    });
    const fmt = v => v.toFixed(2).replace('.', ',') + ' €';
    const stEl = document.getElementById('recap-sous-total');
    const tEl  = document.getElementById('recap-total');
    if (stEl) stEl.textContent = fmt(total);
    if (tEl)  tEl.textContent  = fmt(total);
}

// ──────────────────────────────────────────────────────────────
// CHANGER QUANTITE
// ──────────────────────────────────────────────────────────────
async function changerQuantite(idLigne, nouvelleQte) {
    try {
        const json = await postAction({ action: 'modifier_quantite', id_ligne: idLigne, quantite: nouvelleQte });

        if (!json.success) {
            toast(json.message || 'Erreur', 'error');
            return;
        }
        if (json.nb_articles !== undefined) updateCartBadge(json.nb_articles);

        if (nouvelleQte <= 0) {
            // Supprimer la ligne du DOM
            const el = document.getElementById('ligne-' + idLigne);
            if (el) {
                el.style.opacity  = '0';
                el.style.transform = 'translateX(-30px)';
                setTimeout(() => {
                    el.remove();
                    verifierPanierVide();
                    recalculerTotaux();
                }, 300);
            }
        } else {
            const qteEl = document.getElementById('qte-' + idLigne);
            if (qteEl) {
                qteEl.textContent = nouvelleQte;
                const stock = parseInt(qteEl.dataset.stock) || 0;
                // Mettre à jour l'état disabled des boutons +/−
                const btnPlus  = document.getElementById('plus-' + idLigne);
                const btnMoins = qteEl.previousElementSibling;
                if (btnPlus)  btnPlus.disabled  = (nouvelleQte >= stock);
                if (btnMoins) btnMoins.disabled = (nouvelleQte <= 1);
            }
            recalculerTotaux();
        }
    } catch(e) {
        toast('Erreur de connexion', 'error');
    }
}

// ──────────────────────────────────────────────────────────────
// SUPPRIMER UNE LIGNE
// ──────────────────────────────────────────────────────────────
async function supprimerLigne(idLigne) {
    try {
        const json = await postAction({ action: 'supprimer', id_ligne: idLigne });
        if (!json.success) { toast('Erreur', 'error'); return; }
        if (json.nb_articles !== undefined) updateCartBadge(json.nb_articles);
        if (el) {
            el.style.opacity   = '0';
            el.style.transform = 'translateX(-30px)';
            setTimeout(() => {
                el.remove();
                verifierPanierVide();
                recalculerTotaux();
            }, 300);
        }
        toast('Article retiré du panier');
    } catch(e) {
        toast('Erreur de connexion', 'error');
    }
}

// ──────────────────────────────────────────────────────────────
// VIDER LE PANIER
// ──────────────────────────────────────────────────────────────
async function viderPanier() {
    if (!confirm('Vider l\'intégralité du panier ?')) return;
    try {
        const json = await postAction({ action: 'vider' });
        if (!json.success) { toast('Erreur', 'error'); return; }
        if (json.nb_articles !== undefined) updateCartBadge(json.nb_articles);
        document.querySelectorAll('.panier-ligne').forEach(el => el.remove());
        verifierPanierVide();
        recalculerTotaux();
        toast('Panier vidé');
    } catch(e) {
        toast('Erreur de connexion', 'error');
    }
}

// ──────────────────────────────────────────────────────────────
// AFFICHER PANIER VIDE SI PLUS D'ARTICLES
// ──────────────────────────────────────────────────────────────
function verifierPanierVide() {
    const restants = document.querySelectorAll('.panier-ligne');
    if (restants.length === 0) {
        document.querySelector('.panier-contenu')?.remove();
        const main = document.querySelector('.panier-page');
        const vide = document.createElement('div');
        vide.className = 'panier-vide';
        vide.innerHTML = `
            <div class="panier-vide__icone">🛒</div>
            <h2>Votre panier est vide</h2>
            <p>Découvrez nos épices et thés d'exception</p>
            <a href="index.php" class="btn-continuer">Explorer la boutique</a>
        `;
        main.appendChild(vide);

        const badge = document.querySelector('.panier-badge');
        if (badge) badge.remove();
    } else {
        const badge = document.querySelector('.panier-badge');
        if (badge) badge.textContent = restants.length;
    }
}
</script>