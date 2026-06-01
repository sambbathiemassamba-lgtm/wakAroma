<?php
session_start();
if(empty($_SESSION['auth'])) { header("Location: index.php"); exit(); }
require_once 'headear.php';
?>

<style>
/* ── Layout réutilisé ── */
:root { --boutique-sidebar-w: 270px; }

.boutique-layout {
  display: grid;
  grid-template-columns: var(--boutique-sidebar-w) 1fr;
  min-height: calc(100vh - 120px);
  background: var(--color-bg);
}

/* Sidebar (identique boutique) */
.boutique-sidebar {
  background: var(--color-green);
  padding: 2.5rem 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  position: sticky;
  top: 0;
  height: 100vh;
  overflow-y: auto;
}
.sidebar-avatar {
  display:flex; flex-direction:column; align-items:center;
  gap:.75rem; padding:1.5rem 1rem 2rem;
  border-bottom:1px solid rgba(255,255,255,.12); margin-bottom:1rem;
}
.sidebar-avatar__ring {
  width:72px; height:72px; border-radius:50%;
  background:linear-gradient(135deg,var(--color-gold),var(--color-primary));
  display:flex; align-items:center; justify-content:center;
  font-family:var(--font-display); font-size:2rem; font-weight:700; color:#fff;
  box-shadow:0 0 0 4px rgba(200,148,58,.35); letter-spacing:-.03em;
}
.sidebar-avatar__name { font-family:var(--font-display); font-size:1.25rem; font-weight:700; color:#fff; text-align:center; }
.sidebar-avatar__badge { font-size:.65rem; letter-spacing:.2em; text-transform:uppercase; font-weight:600; color:var(--color-gold); background:rgba(200,148,58,.18); padding:.25rem .75rem; border-radius:999px; border:1px solid rgba(200,148,58,.35); }
.sidebar-nav__label { font-size:.62rem; letter-spacing:.2em; text-transform:uppercase; color:rgba(255,255,255,.35); font-weight:600; padding:.6rem .75rem .3rem; margin-top:.5rem; }
.sidebar-nav__item {
  display:flex; align-items:center; gap:.85rem;
  padding:.8rem 1rem; border-radius:12px;
  text-decoration:none; color:rgba(255,255,255,.7);
  font-size:.88rem; font-weight:500; cursor:pointer;
  transition:all var(--transition); border:none; background:transparent; width:100%; text-align:left;
}
.sidebar-nav__item:hover, .sidebar-nav__item--active { color:#fff; background:rgba(255,255,255,.1); }
.sidebar-nav__item--active { background:rgba(200,148,58,.25)!important; color:var(--color-gold)!important; font-weight:600; }
.sidebar-nav__icon { width:36px; height:36px; border-radius:9px; background:rgba(255,255,255,.08); display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:1rem; }
.sidebar-nav__item--active .sidebar-nav__icon { background:rgba(200,148,58,.3); }
.sidebar-nav__pill { margin-left:auto; font-size:.62rem; background:var(--color-gold); color:var(--color-green); font-weight:700; padding:.15rem .55rem; border-radius:999px; }
.sidebar-bottom { margin-top:auto; padding-top:1.5rem; border-top:1px solid rgba(255,255,255,.1); }

/* ── Main ─────────────────── */
.favoris-main {
  padding: 2.5rem 3rem;
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* ── Topbar ─────────────────── */
.fav-topbar {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
}
.fav-topbar__eyebrow { font-size:.65rem; letter-spacing:.2em; text-transform:uppercase; color:var(--color-gold); font-weight:700; margin-bottom:.3rem; }
.fav-topbar__title { font-family:var(--font-display); font-size:2.2rem; font-weight:700; color:var(--color-green); letter-spacing:-.03em; }
.fav-topbar__count { font-size:.88rem; color:var(--color-muted); margin-top:.3rem; }

.btn-outline {
  display:inline-flex; align-items:center; gap:.5rem;
  padding:.65rem 1.2rem; border:1.5px solid var(--color-border);
  border-radius:var(--radius-btn); background:var(--color-bg-card);
  color:var(--color-text); font-size:.82rem; font-weight:600;
  cursor:pointer; text-decoration:none; transition:all var(--transition);
}
.btn-outline:hover { border-color:var(--color-primary); color:var(--color-green); box-shadow:0 4px 14px rgba(212,165,116,.2); transform:translateY(-1px); }

/* ── Grille favoris ─────────── */
.fav-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 1.3rem;
}

.fav-item {
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-card);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: all var(--transition);
  animation: fadeIn 0.35s cubic-bezier(0.22,1,0.36,1) both;
}

@keyframes fadeIn { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:none; } }

.fav-item:hover { box-shadow:var(--shadow-card-hover); transform:translateY(-3px); border-color:rgba(212,165,116,.4); }

/* Image */
.fav-item__img-wrap {
  position:relative; aspect-ratio:4/3; overflow:hidden; background:var(--color-cream);
}
.fav-item__img { width:100%; height:100%; object-fit:cover; transition:transform .45s cubic-bezier(.22,1,.36,1); }
.fav-item:hover .fav-item__img { transform:scale(1.06); }

/* Bouton retirer */
.fav-item__remove {
  position:absolute; top:10px; right:10px;
  width:34px; height:34px; border-radius:50%;
  background:rgba(255,255,255,.92);
  border:none; cursor:pointer;
  display:flex; align-items:center; justify-content:center;
  box-shadow:0 2px 8px rgba(0,0,0,.12);
  transition:all var(--transition);
  color:#e74c3c; font-size:1.05rem;
  z-index:2;
}
.fav-item__remove:hover { background:#fdecea; transform:scale(1.12); }

/* Corps */
.fav-item__body { padding:.9rem 1.1rem .5rem; flex:1; display:flex; flex-direction:column; gap:.25rem; }
.fav-item__cat  { font-size:.63rem; text-transform:uppercase; letter-spacing:.12em; color:var(--color-gold); font-weight:700; }
.fav-item__name { font-family:var(--font-display); font-size:1.05rem; font-weight:700; color:var(--color-green); letter-spacing:-.01em; line-height:1.2; }
.fav-item__stars { font-size:.8rem; color:var(--color-gold); }

/* Footer */
.fav-item__footer {
  display:flex; align-items:center; justify-content:space-between;
  padding:.7rem 1.1rem .9rem;
  border-top:1px solid var(--color-border);
  margin-top:.4rem;
}
.fav-item__price { font-family:var(--font-display); font-size:1.2rem; font-weight:700; color:var(--color-green); letter-spacing:-.02em; }
.fav-item__add {
  display:flex; align-items:center; gap:.4rem;
  padding:.5rem .95rem;
  background:var(--color-green); color:#fff;
  border:none; border-radius:var(--radius-btn);
  font-size:.76rem; font-weight:600; cursor:pointer;
  transition:all var(--transition); font-family:var(--font-body);
}
.fav-item__add:hover { background:var(--color-green-mid); transform:translateY(-1px); box-shadow:0 4px 12px rgba(31,79,46,.25); }

/* ── État vide ─────────────── */
.fav-empty {
  display: none;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1.2rem;
  padding: 5rem 2rem;
  text-align: center;
  background: var(--color-bg-card);
  border: 1.5px dashed var(--color-border);
  border-radius: var(--radius-card);
}
.fav-empty.visible { display: flex; }
.fav-empty__icon { font-size: 4rem; opacity: .35; }
.fav-empty__title { font-family:var(--font-display); font-size:1.6rem; font-weight:700; color:var(--color-green); letter-spacing:-.02em; }
.fav-empty__sub { font-size:.88rem; color:var(--color-muted); max-width:340px; line-height:1.6; }
.btn-primary {
  display:inline-flex; align-items:center; gap:.5rem;
  padding:.75rem 1.6rem;
  background:var(--color-green); color:#fff;
  border:none; border-radius:var(--radius-btn);
  font-size:.88rem; font-weight:600; cursor:pointer;
  text-decoration:none; transition:all var(--transition);
}
.btn-primary:hover { background:var(--color-green-mid); transform:translateY(-1px); box-shadow:0 6px 20px rgba(31,79,46,.25); }

/* ── Toast ─────────────────── */
#toast-fav {
  position:fixed; bottom:2rem; right:2rem; z-index:9999;
  background:var(--color-green); color:#fff;
  padding:.85rem 1.4rem; border-radius:12px;
  font-size:.85rem; font-weight:600; box-shadow:0 8px 24px rgba(0,0,0,.18);
  transform:translateY(120px); opacity:0;
  transition:all .3s cubic-bezier(.22,1,.36,1);
  pointer-events:none;
}

/* ── Barre tri ─────────────── */
.fav-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: .75rem;
}
.fav-bar__left { display:flex; gap:.5rem; align-items:center; }
.fav-bar select {
  padding:.45rem .9rem;
  border:1.5px solid var(--color-border);
  border-radius:var(--radius-btn);
  background:var(--color-bg-card);
  color:var(--color-text);
  font-size:.8rem; font-weight:500;
  cursor:pointer;
  font-family:var(--font-body);
  outline:none;
}
.clear-all-btn {
  background:none; border:none; cursor:pointer;
  font-size:.78rem; font-weight:600;
  color:#c0392b; text-decoration:underline;
  text-underline-offset:3px; transition:color var(--transition);
}
.clear-all-btn:hover { color:#e74c3c; }

/* ── Responsive ─────────────── */
@media (max-width: 860px) {
  .boutique-layout { grid-template-columns:1fr; }
  .boutique-sidebar { position:static; height:auto; flex-direction:row; flex-wrap:wrap; padding:1rem; gap:.5rem; }
  .sidebar-avatar { display:none; }
  .sidebar-nav__label { display:none; }
  .sidebar-nav__item { flex-direction:column; gap:.25rem; font-size:.7rem; padding:.6rem .7rem; text-align:center; }
  .sidebar-nav__pill { display:none; }
  .favoris-main { padding:1.5rem 1rem; }
}
@media (max-width:540px) {
  .fav-grid { grid-template-columns:1fr; }
}
</style>

<div class="boutique-layout">

  <!-- Sidebar -->
  <aside class="boutique-sidebar">
    <div class="sidebar-avatar">
      <div class="sidebar-avatar__ring"><?= strtoupper(mb_substr($_SESSION['auth']['prenom'],0,1)) ?></div>
      <div class="sidebar-avatar__name"><?= htmlspecialchars($_SESSION['auth']['prenom']) ?></div>
      <span class="sidebar-avatar__badge">✦ Membre Gold</span>
    </div>

    <span class="sidebar-nav__label">Mon espace</span>
    <a href="compte.php" class="sidebar-nav__item"><span class="sidebar-nav__icon">🏠</span>Tableau de bord</a>
    <a href="commandes.php" class="sidebar-nav__item"><span class="sidebar-nav__icon">📦</span>Mes commandes<span class="sidebar-nav__pill">3</span></a>
    <a href="favoris.php"  class="sidebar-nav__item sidebar-nav__item--active"><span class="sidebar-nav__icon">❤️</span>Mes favoris</a>
    <a href="avis.php"     class="sidebar-nav__item"><span class="sidebar-nav__icon">⭐</span>Mes avis</a>

    <span class="sidebar-nav__label">Mon compte</span>
    <a href="profil.php"      class="sidebar-nav__item"><span class="sidebar-nav__icon">👤</span>Mon profil</a>
    <a href="adresses.php"    class="sidebar-nav__item"><span class="sidebar-nav__icon">📍</span>Mes adresses</a>
    <a href="parametres.php"  class="sidebar-nav__item"><span class="sidebar-nav__icon">⚙️</span>Paramètres</a>

    <div class="sidebar-bottom">
      <a href="deconnexion.php" class="sidebar-nav__item"><span class="sidebar-nav__icon">🚪</span>Se déconnecter</a>
    </div>
  </aside>

  <!-- Main -->
  <main class="favoris-main">

    <!-- Topbar -->
    <div class="fav-topbar">
      <div>
        <div class="fav-topbar__eyebrow">Ma liste de souhaits</div>
        <h1 class="fav-topbar__title">Mes Favoris</h1>
        <p class="fav-topbar__count" id="fav-count-text">Chargement…</p>
      </div>
      <a href="index.php" class="btn-outline">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Retour à la boutique
      </a>
    </div>

    <!-- Barre de tri -->
    <div class="fav-bar" id="fav-bar" style="display:none">
      <div class="fav-bar__left">
        <select id="fav-sort" onchange="trierFavoris(this.value)">
          <option value="date">Ajoutés récemment</option>
          <option value="az">Nom A → Z</option>
          <option value="prix-asc">Prix croissant</option>
          <option value="prix-desc">Prix décroissant</option>
        </select>
      </div>
      <button class="clear-all-btn" onclick="viderTousFavoris()">🗑 Tout supprimer</button>
    </div>

    <!-- Grille -->
    <div class="fav-grid" id="fav-grid"></div>

    <!-- État vide -->
    <div class="fav-empty" id="fav-empty">
      <div class="fav-empty__icon">❤️</div>
      <div class="fav-empty__title">Votre liste est vide</div>
      <p class="fav-empty__sub">Parcourez notre catalogue et ajoutez vos épices préférées en cliquant sur le cœur ❤</p>
      <a href="index.php" class="btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        Découvrir les produits
      </a>
    </div>

  </main>
</div>

<!-- Toast -->
<div id="toast-fav" aria-live="polite"></div>

<script>
const FAV_KEY = 'waka_favoris';
function getFavoris()   { try { return JSON.parse(localStorage.getItem(FAV_KEY)) || {}; } catch { return {}; } }
function saveFavoris(o) { localStorage.setItem(FAV_KEY, JSON.stringify(o)); }

function showToast(msg, type='success') {
  const el = document.getElementById('toast-fav');
  el.textContent = msg;
  el.style.background = type==='error' ? '#c0392b' : 'var(--color-green)';
  el.style.transform='translateY(0)'; el.style.opacity='1';
  clearTimeout(el._t);
  el._t = setTimeout(()=>{ el.style.transform='translateY(120px)'; el.style.opacity='0'; }, 2600);
}

function retirerFavori(id) {
  const favs = getFavoris();
  delete favs[id];
  saveFavoris(favs);
  showToast('❌ Retiré des favoris');
  renderFavoris();
}

function viderTousFavoris() {
  if (!confirm('Supprimer tous vos favoris ?')) return;
  saveFavoris({});
  showToast('🗑 Liste vidée');
  renderFavoris();
}

function trierFavoris(mode) {
  renderFavoris(mode);
}

async function ajouterAuPanier(btn, id) {
  btn.disabled = true;
  const orig = btn.innerHTML;
  btn.textContent = '…';
  try {
    const body = new URLSearchParams({ action:'ajouter', id_produit: id });
    const res  = await fetch('panier.php', { method:'POST', body });
    const json = await res.json();
    if (json.success) {
      btn.innerHTML = '✓ Ajouté';
      showToast('🛒 Ajouté au panier !');
      setTimeout(()=>{ btn.innerHTML=orig; btn.disabled=false; }, 1600);
    } else {
      showToast(json.message||'Erreur','error');
      btn.innerHTML=orig; btn.disabled=false;
    }
  } catch {
    showToast('Erreur de connexion','error');
    btn.innerHTML=orig; btn.disabled=false;
  }
}

function renderFavoris(sort='date') {
  const favs  = getFavoris();
  const items = Object.values(favs);
  const grid  = document.getElementById('fav-grid');
  const empty = document.getElementById('fav-empty');
  const bar   = document.getElementById('fav-bar');
  const count = document.getElementById('fav-count-text');

  if (items.length === 0) {
    grid.innerHTML  = '';
    empty.classList.add('visible');
    bar.style.display  = 'none';
    count.textContent  = 'Aucun produit dans vos favoris';
    return;
  }

  empty.classList.remove('visible');
  bar.style.display = 'flex';
  count.textContent = `${items.length} produit${items.length>1?'s':''} sauvegardé${items.length>1?'s':''}`;

  // Tri
  if (sort === 'az')         items.sort((a,b) => a.nom.localeCompare(b.nom));
  if (sort === 'prix-asc')   items.sort((a,b) => parseFloat(a.prix) - parseFloat(b.prix));
  if (sort === 'prix-desc')  items.sort((a,b) => parseFloat(b.prix) - parseFloat(a.prix));

  grid.innerHTML = items.map((p, i) => `
    <article class="fav-item" style="animation-delay:${i*0.06}s">
      <div class="fav-item__img-wrap">
        ${p.img
          ? `<img class="fav-item__img" src="${escHtml(p.img)}" alt="${escHtml(p.nom)}" loading="lazy">`
          : `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:3rem;background:var(--color-green-light)">🌿</div>`
        }
        <button class="fav-item__remove" onclick="retirerFavori('${p.id}')" title="Retirer des favoris">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        </button>
      </div>
      <div class="fav-item__body">
        <p class="fav-item__cat">Épice · WakAroma</p>
        <h3 class="fav-item__name">${escHtml(p.nom)}</h3>
        <div class="fav-item__stars">★★★★<span style="opacity:.3">★</span></div>
      </div>
      <div class="fav-item__footer">
        <span class="fav-item__price">${escHtml(p.prix)} €</span>
        <button class="fav-item__add" onclick="ajouterAuPanier(this,'${p.id}')">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          Au panier
        </button>
      </div>
    </article>
  `).join('');
}

function escHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.addEventListener('DOMContentLoaded', () => renderFavoris());
</script>

<?php require_once 'footer.php'; ?>
