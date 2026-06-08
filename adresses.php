<?php
session_start();

if (empty($_SESSION['auth'])) {
    header("Location: login.php");
    exit();
}

require_once 'pdo.php';
require_once 'function.php';

$id_user = $_SESSION['auth']['id_user'];
$toast   = null;
$error   = null;

// ── SUPPRESSION ──────────────────────────────────────────────
if (isset($_GET['supprimer'])) {
    $idAddr = (int) $_GET['supprimer'];
    $pdo->prepare("DELETE FROM adresses WHERE id_adresse = :id AND id_user = :uid")
        ->execute([':id' => $idAddr, ':uid' => $id_user]);
    $_SESSION['toast'] = "Adresse supprimée.";
    header("Location: adresses.php");
    exit();
}

// ── DÉFINIR PAR DÉFAUT ───────────────────────────────────────
if (isset($_GET['defaut'])) {
    $idAddr = (int) $_GET['defaut'];
    $pdo->prepare("UPDATE adresses SET is_defaut = 0 WHERE id_user = :uid")
        ->execute([':uid' => $id_user]);
    $pdo->prepare("UPDATE adresses SET is_defaut = 1 WHERE id_adresse = :id AND id_user = :uid")
        ->execute([':id' => $idAddr, ':uid' => $id_user]);
    $_SESSION['toast'] = "Adresse définie par défaut.";
    header("Location: adresses.php");
    exit();
}

// ── AJOUT / MODIFICATION ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_adresse = (int) ($_POST['id_adresse'] ?? 0);
    $type       = in_array($_POST['type'] ?? '', ['livraison', 'domicile', 'siege_social'])
                    ? $_POST['type'] : 'livraison';
    $prenom     = trim($_POST['prenom']     ?? '');
    $nom        = trim($_POST['nom']        ?? '');
    $adresse    = trim($_POST['adresse']    ?? '');
    $complement = trim($_POST['complement'] ?? '');
    $code_postal= trim($_POST['code_postal']?? '');
    $ville      = trim($_POST['ville']      ?? '');
    $pays       = trim($_POST['pays']       ?? 'France');
    $telephone  = trim($_POST['telephone']  ?? '');
    $is_defaut  = isset($_POST['is_defaut']) ? 1 : 0;

    if (empty($prenom) || empty($nom) || empty($adresse) || empty($code_postal) || empty($ville)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {
        if ($is_defaut) {
            $pdo->prepare("UPDATE adresses SET is_defaut = 0 WHERE id_user = :uid")
                ->execute([':uid' => $id_user]);
        }

        if ($id_adresse > 0) {
            // Modification
            $pdo->prepare("
                UPDATE adresses SET
                    type = :type, prenom = :prenom, nom = :nom,
                    adresse = :adresse, complement = :complement,
                    code_postal = :cp, ville = :ville, pays = :pays,
                    telephone = :tel, is_defaut = :defaut
                WHERE id_adresse = :id AND id_user = :uid
            ")->execute([
                ':type'      => $type,    ':prenom'  => $prenom,
                ':nom'       => $nom,     ':adresse' => $adresse,
                ':complement'=> $complement, ':cp'   => $code_postal,
                ':ville'     => $ville,   ':pays'    => $pays,
                ':tel'       => $telephone, ':defaut' => $is_defaut,
                ':id'        => $id_adresse, ':uid'  => $id_user,
            ]);
            $_SESSION['toast'] = "Adresse modifiée avec succès.";
        } else {
            // Ajout
            $pdo->prepare("
                INSERT INTO adresses
                    (id_user, type, prenom, nom, adresse, complement, code_postal, ville, pays, telephone, is_defaut)
                VALUES
                    (:uid, :type, :prenom, :nom, :adresse, :complement, :cp, :ville, :pays, :tel, :defaut)
            ")->execute([
                ':uid'       => $id_user, ':type'    => $type,
                ':prenom'    => $prenom,  ':nom'     => $nom,
                ':adresse'   => $adresse, ':complement' => $complement,
                ':cp'        => $code_postal, ':ville' => $ville,
                ':pays'      => $pays,    ':tel'     => $telephone,
                ':defaut'    => $is_defaut,
            ]);
            $_SESSION['toast'] = "Adresse ajoutée avec succès.";
        }
        header("Location: adresses.php");
        exit();
    }
}

// ── CHARGEMENT DES ADRESSES ──────────────────────────────────
$adresses = $pdo->prepare("SELECT * FROM adresses WHERE id_user = :uid ORDER BY is_defaut DESC, id_adresse ASC");
$adresses->execute([':uid' => $id_user]);
$adresses = $adresses->fetchAll(PDO::FETCH_OBJ);

// ── EDITION (pré-remplissage formulaire) ─────────────────────
$edit = null;
if (isset($_GET['editer'])) {
    $req = $pdo->prepare("SELECT * FROM adresses WHERE id_adresse = :id AND id_user = :uid LIMIT 1");
    $req->execute([':id' => (int)$_GET['editer'], ':uid' => $id_user]);
    $edit = $req->fetch(PDO::FETCH_OBJ);
}

// Toast session
if (!empty($_SESSION['toast'])) {
    $toast = $_SESSION['toast'];
    unset($_SESSION['toast']);
}

// Labels types
$typeLabels = [
    'livraison'   => ['label' => 'Livraison',    'icon' => '🚚', 'color' => '#1f4f2e'],
    'domicile'    => ['label' => 'Domicile',     'icon' => '🏠', 'color' => '#2563eb'],
    'siege_social'=> ['label' => 'Siège social', 'icon' => '🏢', 'color' => '#7c3aed'],
];
?>
<?php require_once 'headear.php'; ?>

<style>
/* ── Variables locales ─────────────────────── */
:root { --boutique-sidebar-w: 270px; }

/* ── Layout principal ──────────────────────── */
.boutique-layout {
  display: grid;
  grid-template-columns: var(--boutique-sidebar-w) 1fr;
  min-height: calc(100vh - 120px);
  background: var(--color-bg);
}

/* ══════════════════════════════════════════
   SIDEBAR (même que compte.php)
   ══════════════════════════════════════════ */
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
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 1.5rem 1rem 2rem;
  border-bottom: 1px solid rgba(255,255,255,0.12);
  margin-bottom: 1rem;
}
.sidebar-avatar__ring {
  width: 72px; height: 72px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--color-gold), var(--color-primary));
  display: flex; align-items: center; justify-content: center;
  font-family: var(--font-display);
  font-size: 2rem; font-weight: 700; color: #fff;
  box-shadow: 0 0 0 4px rgba(200,148,58,0.35);
}
.sidebar-avatar__name {
  font-family: var(--font-display);
  font-size: 1.25rem; font-weight: 700;
  color: #fff; text-align: center;
}
.sidebar-avatar__badge {
  font-size: 0.65rem; letter-spacing: 0.2em;
  text-transform: uppercase; font-weight: 600;
  color: var(--color-gold);
  background: rgba(200,148,58,0.18);
  padding: 0.25rem 0.75rem;
  border-radius: 999px;
  border: 1px solid rgba(200,148,58,0.35);
}
.sidebar-nav__label {
  font-size: 0.62rem; letter-spacing: 0.2em;
  text-transform: uppercase;
  color: rgba(255,255,255,0.35);
  font-weight: 600;
  padding: 0.6rem 0.75rem 0.3rem;
  margin-top: 0.5rem;
}
.sidebar-nav__item {
  display: flex; align-items: center; gap: 0.85rem;
  padding: 0.8rem 1rem; border-radius: 12px;
  text-decoration: none;
  color: rgba(255,255,255,0.7);
  font-size: 0.88rem; font-weight: 500;
  cursor: pointer;
  transition: all var(--transition);
  border: none; background: transparent;
  width: 100%; text-align: left;
}
.sidebar-nav__item:hover, .sidebar-nav__item--active {
  color: #fff; background: rgba(255,255,255,0.1);
}
.sidebar-nav__item--active {
  background: rgba(200,148,58,0.25) !important;
  color: var(--color-gold) !important;
  font-weight: 600;
}
.sidebar-nav__icon {
  width: 36px; height: 36px;
  border-radius: 9px;
  background: rgba(255,255,255,0.08);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; font-size: 1rem;
}
.sidebar-nav__item--active .sidebar-nav__icon {
  background: rgba(200,148,58,0.3);
}
.sidebar-nav__pill {
  margin-left: auto; font-size: 0.62rem;
  background: var(--color-gold); color: var(--color-green);
  font-weight: 700; padding: 0.15rem 0.55rem;
  border-radius: 999px;
}
.sidebar-bottom {
  margin-top: auto; padding-top: 1.5rem;
  border-top: 1px solid rgba(255,255,255,0.1);
}

/* ══════════════════════════════════════════
   MAIN
   ══════════════════════════════════════════ */
.boutique-main {
  padding: 2.5rem 3rem;
  display: flex; flex-direction: column; gap: 2.5rem;
}
.boutique-topbar {
  display: flex; align-items: flex-start;
  justify-content: space-between; gap: 1rem; flex-wrap: wrap;
}
.boutique-topbar__greeting {
  font-family: var(--font-display);
  font-size: 2.4rem; font-weight: 700;
  color: var(--color-green);
  letter-spacing: -0.03em; line-height: 1.1;
}
.boutique-topbar__greeting em {
  font-style: italic; color: var(--color-gold);
}
.boutique-topbar__sub {
  font-size: 0.9rem; color: var(--color-muted); margin-top: 0.4rem;
}
.section-title {
  display: flex; align-items: center;
  justify-content: space-between; gap: 1rem; margin-bottom: 1.2rem;
}
.section-title__h {
  font-family: var(--font-display);
  font-size: 1.45rem; font-weight: 700;
  color: var(--color-green); letter-spacing: -0.02em;
}
.section-title__eyebrow {
  font-size: 0.65rem; letter-spacing: 0.18em;
  text-transform: uppercase; color: var(--color-gold);
}
.btn-primary {
  display: inline-flex; align-items: center; gap: 0.5rem;
  padding: 0.65rem 1.35rem;
  background: var(--color-green); color: #fff;
  border: none; border-radius: var(--radius-btn);
  font-size: 0.82rem; font-weight: 600;
  cursor: pointer; text-decoration: none;
  transition: all var(--transition);
}
.btn-primary:hover {
  background: var(--color-green-mid);
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(31,79,46,0.25);
}
.btn-outline {
  display: inline-flex; align-items: center; gap: 0.5rem;
  padding: 0.6rem 1.1rem;
  border: 1.5px solid var(--color-border);
  border-radius: var(--radius-btn);
  background: var(--color-bg-card);
  color: var(--color-text);
  font-size: 0.82rem; font-weight: 600;
  cursor: pointer; text-decoration: none;
  transition: all var(--transition);
}
.btn-outline:hover {
  border-color: var(--color-primary); color: var(--color-green);
}

/* ══════════════════════════════════════════
   CARTES ADRESSES
   ══════════════════════════════════════════ */
.addr-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.25rem;
}

.addr-card {
  background: var(--color-bg-card);
  border: 1.5px solid var(--color-border);
  border-radius: 18px;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  position: relative;
  transition: box-shadow 0.2s, border-color 0.2s, transform 0.2s;
}
.addr-card:hover {
  box-shadow: 0 8px 28px rgba(31,79,46,0.1);
  transform: translateY(-2px);
  border-color: rgba(31,79,46,0.25);
}
.addr-card--defaut {
  border-color: var(--color-gold);
  box-shadow: 0 0 0 2px rgba(200,148,58,0.2);
}

.addr-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}

.addr-type-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.3rem 0.85rem;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}
.addr-type-badge--livraison    { background: rgba(31,79,46,0.1);   color: #1f4f2e; }
.addr-type-badge--domicile     { background: rgba(37,99,235,0.1);  color: #2563eb; }
.addr-type-badge--siege_social { background: rgba(124,58,237,0.1); color: #7c3aed; }

.addr-defaut-badge {
  font-size: 0.65rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  font-weight: 700;
  color: var(--color-gold);
  background: rgba(200,148,58,0.12);
  border: 1px solid rgba(200,148,58,0.3);
  padding: 0.2rem 0.6rem;
  border-radius: 999px;
}

.addr-card__name {
  font-weight: 700;
  font-size: 1rem;
  color: var(--color-text);
}
.addr-card__lines {
  font-size: 0.88rem;
  color: var(--color-muted);
  line-height: 1.7;
}
.addr-card__tel {
  font-size: 0.82rem;
  color: var(--color-muted);
  margin-top: 0.25rem;
}

.addr-card__actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  padding-top: 0.75rem;
  border-top: 1px solid var(--color-border);
}
.addr-action-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.45rem 0.85rem;
  border-radius: 8px;
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  border: 1.5px solid transparent;
  transition: all 0.18s;
}
.addr-action-btn--edit {
  border-color: var(--color-border);
  background: var(--color-bg-card);
  color: var(--color-text);
}
.addr-action-btn--edit:hover {
  border-color: var(--color-green);
  color: var(--color-green);
}
.addr-action-btn--defaut {
  border-color: rgba(200,148,58,0.4);
  background: rgba(200,148,58,0.07);
  color: var(--color-gold);
}
.addr-action-btn--defaut:hover {
  background: rgba(200,148,58,0.15);
}
.addr-action-btn--delete {
  border-color: rgba(192,57,43,0.25);
  background: rgba(192,57,43,0.05);
  color: #c0392b;
  margin-left: auto;
}
.addr-action-btn--delete:hover {
  background: rgba(192,57,43,0.12);
  border-color: rgba(192,57,43,0.5);
}

/* Vide */
.addr-empty {
  text-align: center;
  padding: 4rem 2rem;
  color: var(--color-muted);
}
.addr-empty__icon { font-size: 3.5rem; margin-bottom: 1rem; }
.addr-empty__title {
  font-family: var(--font-display);
  font-size: 1.4rem; color: var(--color-green);
  margin-bottom: 0.5rem;
}

/* ══════════════════════════════════════════
   FORMULAIRE
   ══════════════════════════════════════════ */
.addr-form-wrap {
  background: var(--color-bg-card);
  border: 1.5px solid var(--color-border);
  border-radius: 20px;
  padding: 2rem 2.5rem;
}
.addr-form-title {
  font-family: var(--font-display);
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--color-green);
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

/* Type selector */
.type-selector {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin-bottom: 1.5rem;
}
.type-option {
  display: none;
}
.type-option + label {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.4rem;
  padding: 0.85rem 1.4rem;
  border: 2px solid var(--color-border);
  border-radius: 14px;
  cursor: pointer;
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--color-muted);
  background: var(--color-bg);
  transition: all 0.18s;
  min-width: 100px;
  text-align: center;
}
.type-option + label .type-icon {
  font-size: 1.6rem;
}
.type-option:checked + label {
  border-color: var(--color-green);
  background: rgba(31,79,46,0.07);
  color: var(--color-green);
  box-shadow: 0 0 0 3px rgba(31,79,46,0.12);
}
.type-option[value="domicile"]:checked + label {
  border-color: #2563eb;
  background: rgba(37,99,235,0.07);
  color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
}
.type-option[value="siege_social"]:checked + label {
  border-color: #7c3aed;
  background: rgba(124,58,237,0.07);
  color: #7c3aed;
  box-shadow: 0 0 0 3px rgba(124,58,237,0.1);
}

.addr-form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem 1.5rem;
}
.addr-form-grid .full { grid-column: 1 / -1; }

.form-group label {
  display: block;
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--color-muted);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin-bottom: 0.4rem;
}
.form-group label .required { color: #e05c5c; }
.form-group input,
.form-group select {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1.5px solid var(--color-border);
  border-radius: 10px;
  background: var(--color-bg);
  color: var(--color-text);
  font-size: 0.9rem;
  transition: border-color 0.18s, box-shadow 0.18s;
  box-sizing: border-box;
}
.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: var(--color-green);
  box-shadow: 0 0 0 3px rgba(31,79,46,0.1);
}

.defaut-check {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.85rem 1rem;
  border: 1.5px solid var(--color-border);
  border-radius: 12px;
  cursor: pointer;
  transition: border-color 0.18s, background 0.18s;
  grid-column: 1 / -1;
}
.defaut-check:hover {
  border-color: var(--color-gold);
  background: rgba(200,148,58,0.04);
}
.defaut-check input[type="checkbox"] {
  width: 18px; height: 18px;
  accent-color: var(--color-gold);
  cursor: pointer;
  flex-shrink: 0;
  border: none;
  border-radius: 0;
  padding: 0;
  box-shadow: none;
}
.defaut-check span {
  font-size: 0.88rem;
  font-weight: 500;
  color: var(--color-text);
}

.addr-form-actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 1.5rem;
  flex-wrap: wrap;
}

/* Alert */
.alert--error {
  background: rgba(192,57,43,0.08);
  border: 1px solid rgba(192,57,43,0.3);
  color: #c0392b;
  padding: 0.85rem 1.2rem;
  border-radius: 10px;
  font-size: 0.88rem;
  font-weight: 500;
  margin-bottom: 1.5rem;
}

/* Toast */
.toast-addr {
  position: fixed;
  bottom: 2rem; right: 2rem;
  z-index: 9999;
  background: var(--color-green);
  color: #fff;
  padding: 0.85rem 1.4rem;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 600;
  box-shadow: 0 8px 24px rgba(0,0,0,0.18);
  display: flex; align-items: center; gap: 0.6rem;
  transform: translateY(100px);
  opacity: 0;
  transition: all 0.35s cubic-bezier(0.22,1,0.36,1);
}
.toast-addr.show {
  transform: translateY(0);
  opacity: 1;
}

/* Animations */
.fade-up {
  opacity: 0;
  transform: translateY(18px);
  animation: fadeUp 0.5s cubic-bezier(0.22,1,0.36,1) forwards;
}
@keyframes fadeUp { to { opacity:1; transform:none; } }
.fade-up:nth-child(1) { animation-delay:0.05s; }
.fade-up:nth-child(2) { animation-delay:0.10s; }
.fade-up:nth-child(3) { animation-delay:0.15s; }

@media (max-width: 900px) {
  .boutique-layout { grid-template-columns: 1fr; }
  .boutique-sidebar { height: auto; position: relative; }
  .boutique-main { padding: 1.5rem; }
  .addr-form-grid { grid-template-columns: 1fr; }
  .addr-form-grid .full { grid-column: 1; }
}
</style>

<div class="boutique-layout">

  <!-- ══════════ SIDEBAR ══════════ -->
  <aside class="boutique-sidebar" aria-label="Navigation espace client">
    <div class="sidebar-avatar">
      <div class="sidebar-avatar__ring">
        <?= strtoupper(mb_substr($_SESSION['auth']['prenom'], 0, 1)) ?>
      </div>
      <div class="sidebar-avatar__name"><?= htmlspecialchars($_SESSION['auth']['prenom']) ?></div>
      <span class="sidebar-avatar__badge">✦ Membre Gold</span>
    </div>

    <span class="sidebar-nav__label">Mon espace</span>
    <a href="compte.php"    class="sidebar-nav__item"><span class="sidebar-nav__icon">🏠</span>Tableau de bord</a>
    <a href="commandes.php" class="sidebar-nav__item"><span class="sidebar-nav__icon">📦</span>Mes commandes<span class="sidebar-nav__pill">3</span></a>
    <a href="favoris.php"   class="sidebar-nav__item"><span class="sidebar-nav__icon">❤️</span>Mes favoris</a>

    <span class="sidebar-nav__label">Mon compte</span>
    <a href="profil.php"    class="sidebar-nav__item"><span class="sidebar-nav__icon">👤</span>Mon profil</a>
    <a href="adresses.php"  class="sidebar-nav__item sidebar-nav__item--active"><span class="sidebar-nav__icon">📍</span>Mes adresses</a>

    <div class="sidebar-bottom">
      <a href="logout.php" class="sidebar-nav__item" style="color:rgba(255,120,100,0.85);"
         onmouseover="this.style.background='rgba(231,76,60,0.15)';this.style.color='#ff6b5b'"
         onmouseout="this.style.background='transparent';this.style.color='rgba(255,120,100,0.85)'">
        <span class="sidebar-nav__icon" style="background:rgba(231,76,60,0.15);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
            <polyline points="16 17 21 12 16 7"/>
            <line x1="21" y1="12" x2="9" y2="12"/>
          </svg>
        </span>
        Se déconnecter
      </a>
    </div>
  </aside>

  <!-- ══════════ MAIN ══════════ -->
  <main class="boutique-main">

    <!-- Topbar -->
    <div class="boutique-topbar fade-up">
      <div>
        <h1 class="boutique-topbar__greeting">
          Mes adresses 📍
        </h1>
        <p class="boutique-topbar__sub">
          Gérez vos adresses de livraison, domicile et siège social.
        </p>
      </div>
      <div>
        <button class="btn-primary" onclick="ouvrirFormulaire()">
          + Ajouter une adresse
        </button>
      </div>
    </div>

    <!-- Erreur -->
    <?php if ($error): ?>
      <div class="alert--error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ── Formulaire ajout / édition ── -->
    <div class="addr-form-wrap fade-up" id="addr-form-wrap" style="<?= ($edit || $error) ? '' : 'display:none' ?>">
      <div class="addr-form-title">
        <?= $edit ? '✏️ Modifier l\'adresse' : '➕ Nouvelle adresse' ?>
      </div>

      <form method="POST" action="adresses.php">
        <?php if ($edit): ?>
          <input type="hidden" name="id_adresse" value="<?= $edit->id_adresse ?>">
        <?php endif; ?>

        <!-- Type -->
        <div class="type-selector">
          <?php foreach ($typeLabels as $val => $info): ?>
            <input type="radio" name="type" id="type_<?= $val ?>" value="<?= $val ?>" class="type-option"
              <?= (($edit && $edit->type === $val) || (!$edit && $val === 'livraison')) ? 'checked' : '' ?>>
            <label for="type_<?= $val ?>">
              <span class="type-icon"><?= $info['icon'] ?></span>
              <?= $info['label'] ?>
            </label>
          <?php endforeach; ?>
        </div>

        <div class="addr-form-grid">
          <div class="form-group">
            <label>Prénom <span class="required">*</span></label>
            <input type="text" name="prenom" placeholder="Jean" value="<?= htmlspecialchars($edit->prenom ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Nom <span class="required">*</span></label>
            <input type="text" name="nom" placeholder="Dupont" value="<?= htmlspecialchars($edit->nom ?? '') ?>" required>
          </div>
          <div class="form-group full">
            <label>Adresse <span class="required">*</span></label>
            <input type="text" name="adresse" placeholder="12 rue des Épices" value="<?= htmlspecialchars($edit->adresse ?? '') ?>" required>
          </div>
          <div class="form-group full">
            <label>Complément d'adresse</label>
            <input type="text" name="complement" placeholder="Bât. B, appartement 4..." value="<?= htmlspecialchars($edit->complement ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Code postal <span class="required">*</span></label>
            <input type="text" name="code_postal" placeholder="75001" value="<?= htmlspecialchars($edit->code_postal ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Ville <span class="required">*</span></label>
            <input type="text" name="ville" placeholder="Paris" value="<?= htmlspecialchars($edit->ville ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Pays</label>
            <input type="text" name="pays" placeholder="France" value="<?= htmlspecialchars($edit->pays ?? 'France') ?>">
          </div>
          <div class="form-group">
            <label>Téléphone</label>
            <input type="tel" name="telephone" placeholder="+33 6 12 34 56 78" value="<?= htmlspecialchars($edit->telephone ?? '') ?>">
          </div>

          <label class="defaut-check">
            <input type="checkbox" name="is_defaut" <?= ($edit && $edit->is_defaut) ? 'checked' : '' ?>>
            <span>⭐ Définir comme adresse par défaut</span>
          </label>
        </div>

        <div class="addr-form-actions">
          <button type="submit" class="btn-primary">
            <?= $edit ? '💾 Enregistrer les modifications' : '✅ Ajouter l\'adresse' ?>
          </button>
          <a href="adresses.php" class="btn-outline">Annuler</a>
        </div>
      </form>
    </div>

    <!-- ── Liste des adresses ── -->
    <div class="fade-up">
      <div class="section-title">
        <div>
          <div class="section-title__eyebrow">Carnet d'adresses</div>
          <h2 class="section-title__h">
            <?= count($adresses) ?> adresse<?= count($adresses) > 1 ? 's' : '' ?> enregistrée<?= count($adresses) > 1 ? 's' : '' ?>
          </h2>
        </div>
      </div>

      <?php if (empty($adresses)): ?>
        <div class="addr-empty">
          <div class="addr-empty__icon">📭</div>
          <div class="addr-empty__title">Aucune adresse enregistrée</div>
          <p>Ajoutez votre première adresse pour faciliter vos commandes.</p>
          <button class="btn-primary" style="margin-top:1.5rem;" onclick="ouvrirFormulaire()">+ Ajouter une adresse</button>
        </div>
      <?php else: ?>
        <div class="addr-grid">
          <?php foreach ($adresses as $addr): ?>
            <div class="addr-card <?= $addr->is_defaut ? 'addr-card--defaut' : '' ?>">
              <div class="addr-card__header">
                <span class="addr-type-badge addr-type-badge--<?= $addr->type ?>">
                  <?= $typeLabels[$addr->type]['icon'] ?> <?= $typeLabels[$addr->type]['label'] ?>
                </span>
                <?php if ($addr->is_defaut): ?>
                  <span class="addr-defaut-badge">⭐ Par défaut</span>
                <?php endif; ?>
              </div>

              <div>
                <div class="addr-card__name"><?= htmlspecialchars($addr->prenom . ' ' . $addr->nom) ?></div>
                <div class="addr-card__lines">
                  <?= htmlspecialchars($addr->adresse) ?><br>
                  <?php if (!empty($addr->complement)): ?>
                    <?= htmlspecialchars($addr->complement) ?><br>
                  <?php endif; ?>
                  <?= htmlspecialchars($addr->code_postal . ' ' . $addr->ville) ?><br>
                  <?= htmlspecialchars($addr->pays) ?>
                </div>
                <?php if (!empty($addr->telephone)): ?>
                  <div class="addr-card__tel">📞 <?= htmlspecialchars($addr->telephone) ?></div>
                <?php endif; ?>
              </div>

              <div class="addr-card__actions">
                <a href="adresses.php?editer=<?= $addr->id_adresse ?>" class="addr-action-btn addr-action-btn--edit">✏️ Modifier</a>
                <?php if (!$addr->is_defaut): ?>
                  <a href="adresses.php?defaut=<?= $addr->id_adresse ?>" class="addr-action-btn addr-action-btn--defaut">⭐ Par défaut</a>
                <?php endif; ?>
                <a href="adresses.php?supprimer=<?= $addr->id_adresse ?>"
                   class="addr-action-btn addr-action-btn--delete"
                   onclick="return confirm('Supprimer cette adresse ?')">🗑️ Supprimer</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </main>
</div>

<!-- Toast -->
<div class="toast-addr" id="toastAddr">✅ <span id="toastMsg"></span></div>

<script>
<?php if ($toast): ?>
window.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($toast) ?>));
<?php endif; ?>

function showToast(msg) {
  const el  = document.getElementById('toastAddr');
  const txt = document.getElementById('toastMsg');
  txt.textContent = msg;
  el.classList.add('show');
  setTimeout(() => el.classList.remove('show'), 3000);
}

function ouvrirFormulaire() {
  const f = document.getElementById('addr-form-wrap');
  f.style.display = 'block';
  f.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>

<?php require_once 'footer.php'; ?>
