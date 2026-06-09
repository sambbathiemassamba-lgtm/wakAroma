<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'wakaroma');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8", DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec("CREATE TABLE IF NOT EXISTS salons (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                nom         VARCHAR(255) NOT NULL,
                lieu        VARCHAR(255) NOT NULL,
                ville       VARCHAR(150) NOT NULL,
                adresse     VARCHAR(255) DEFAULT '',
                date_debut  DATE NOT NULL,
                date_fin    DATE NOT NULL,
                heure_debut VARCHAR(10) DEFAULT '10:00',
                heure_fin   VARCHAR(10) DEFAULT '18:00',
                description TEXT DEFAULT '',
                stand       VARCHAR(255) DEFAULT '',
                actif       TINYINT(1) NOT NULL DEFAULT 1,
                created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
        } catch (PDOException $e) { die('Connexion impossible'); }
    }
    return $pdo;
}

$pdo = getDB();

// Récupérer les salons à venir (actifs, date_fin >= aujourd'hui), triés par date
$stmt = $pdo->query("SELECT * FROM salons WHERE actif = 1 AND date_fin >= CURDATE() ORDER BY date_debut ASC");
$salons = $stmt->fetchAll(PDO::FETCH_OBJ);

// Récupérer le prochain salon (le premier de la liste)
$prochain = !empty($salons) ? $salons[0] : null;

// Jours restants avant le prochain salon
$joursRestants = null;
if ($prochain) {
    $diff = (new DateTime($prochain->date_debut))->diff(new DateTime());
    $joursRestants = max(0, (int)$diff->format('%R%a') * -1);
}

// Mois en français
function moisFr($n) {
    return ['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'][(int)$n];
}
function formatDate($d) {
    $dt = new DateTime($d);
    return $dt->format('j') . ' ' . moisFr($dt->format('n')) . ' ' . $dt->format('Y');
}
function isSameDay($d1, $d2) { return $d1 === $d2; }

include 'headear.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nos Salons — WakAroma</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root {
  --gold:   #c8943a;
  --green:  #1f4f2e;
  --green2: #2d7a44;
  --cream:  #fdf8f2;
  --text:   #2d2a26;
  --muted:  #7a7268;
  --border: #e8e0d6;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

/* ── Wrapper fond commun hero + planning ── */
.page-bg {
  position: relative;
  background: linear-gradient(160deg, #0d1f12 0%, #1a3520 50%, #0f2a18 100%);
  overflow: hidden;
}
.page-bg .hero-particles {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 0;
}

/* ── HERO ── */
.salon-hero {
  position: relative;
  min-height: 92vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
}

.particle {
  position: absolute;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(200,148,58,0.6), transparent);
  animation: drift linear infinite;
}
@keyframes drift {
  0%   { transform: translateY(100vh) scale(0); opacity: 0; }
  10%  { opacity: 1; }
  90%  { opacity: .5; }
  100% { transform: translateY(-20vh) scale(1.5); opacity: 0; }
}

.hero-content {
  position: relative;
  z-index: 2;
  text-align: center;
  padding: 3rem 2rem;
  max-width: 820px;
}

.hero-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-size: .72rem;
  letter-spacing: .2em;
  text-transform: uppercase;
  color: var(--gold);
  font-weight: 700;
  margin-bottom: 1.8rem;
  padding: 7px 18px;
  border: 1px solid rgba(200,148,58,.3);
  border-radius: 999px;
  background: rgba(200,148,58,.06);
}

.hero-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: clamp(2.8rem, 7vw, 5.5rem);
  font-weight: 700;
  color: #fff;
  line-height: 1.05;
  margin-bottom: 1.5rem;
}
.hero-title em {
  font-style: italic;
  color: var(--gold);
}

.hero-desc {
  font-size: 1.1rem;
  color: rgba(255,255,255,.7);
  max-width: 560px;
  margin: 0 auto 2.5rem;
  line-height: 1.7;
}

/* Compte à rebours */
.countdown-wrap {
  display: flex;
  justify-content: center;
  gap: 1rem;
  margin-bottom: 2.5rem;
  flex-wrap: wrap;
}
.countdown-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(200,148,58,.25);
  border-radius: 14px;
  padding: 18px 22px;
  min-width: 80px;
  backdrop-filter: blur(8px);
}
.countdown-num {
  font-family: 'Cormorant Garamond', serif;
  font-size: 2.4rem;
  font-weight: 700;
  color: var(--gold);
  line-height: 1;
}
.countdown-label {
  font-size: .62rem;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: rgba(255,255,255,.5);
  margin-top: 6px;
  font-weight: 600;
}

.hero-cta {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 16px 36px;
  background: linear-gradient(135deg, var(--gold), #e8b860);
  color: #1a0f00;
  font-weight: 700;
  font-size: .95rem;
  border-radius: 999px;
  text-decoration: none;
  letter-spacing: .04em;
  transition: transform .2s, box-shadow .2s;
  box-shadow: 0 8px 32px rgba(200,148,58,.35);
}
.hero-cta:hover {
  transform: translateY(-3px);
  box-shadow: 0 14px 40px rgba(200,148,58,.45);
}

/* Salon mis en avant */
.hero-event-card {
  position: absolute;
  bottom: 3rem;
  right: 4rem;
  background: rgba(255,255,255,.07);
  border: 1px solid rgba(200,148,58,.3);
  border-radius: 18px;
  padding: 20px 24px;
  backdrop-filter: blur(14px);
  text-align: left;
  max-width: 280px;
  animation: floatCard 4s ease-in-out infinite;
}
@keyframes floatCard {
  0%, 100% { transform: translateY(0); }
  50%       { transform: translateY(-10px); }
}
.event-card-tag {
  font-size: .65rem;
  letter-spacing: .14em;
  text-transform: uppercase;
  color: var(--gold);
  font-weight: 700;
  margin-bottom: 8px;
}
.event-card-name {
  font-family: 'Cormorant Garamond', serif;
  font-size: 1.2rem;
  font-weight: 700;
  color: #fff;
  margin-bottom: 6px;
}
.event-card-info {
  font-size: .8rem;
  color: rgba(255,255,255,.6);
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.event-card-badge {
  display: inline-block;
  margin-top: 10px;
  padding: 4px 12px;
  background: rgba(200,148,58,.2);
  border: 1px solid rgba(200,148,58,.4);
  border-radius: 999px;
  font-size: .68rem;
  color: var(--gold);
  font-weight: 600;
}

/* ── PLANNING ── */
.section-planning {
  background: transparent;
  padding: 6rem 2rem;
  position: relative;
  z-index: 2;
}
.section-planning .section-title { color: #fff; }
.section-planning .section-sub   { color: rgba(255,255,255,.55); }
.section-planning .section-center { text-align: center; }

.planning-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  max-width: 860px;
  margin: 0 auto;
}

.salon-card {
  background: rgba(255,255,255,.05);
  border: 1px solid rgba(200,148,58,.2);
  border-radius: 20px;
  padding: 2rem 2.4rem;
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 1.8rem;
  align-items: center;
  backdrop-filter: blur(8px);
  transition: background .25s, border-color .25s, transform .25s;
  text-decoration: none;
}
.salon-card:hover {
  background: rgba(255,255,255,.09);
  border-color: rgba(200,148,58,.5);
  transform: translateX(6px);
}
.salon-card.next-salon {
  background: rgba(200,148,58,.1);
  border-color: rgba(200,148,58,.5);
  border-left: 4px solid var(--gold);
}

.salon-date-block {
  display: flex;
  flex-direction: column;
  align-items: center;
  background: rgba(200,148,58,.12);
  border: 1px solid rgba(200,148,58,.25);
  border-radius: 14px;
  padding: 14px 18px;
  min-width: 72px;
  text-align: center;
}
.salon-date-day   { font-family: 'Cormorant Garamond', serif; font-size: 2rem; font-weight: 700; color: var(--gold); line-height: 1; }
.salon-date-month { font-size: .65rem; letter-spacing: .1em; text-transform: uppercase; color: rgba(255,255,255,.55); font-weight: 600; margin-top: 3px; }
.salon-date-year  { font-size: .65rem; color: rgba(255,255,255,.35); margin-top: 1px; }

.salon-info { display: flex; flex-direction: column; gap: 5px; }
.salon-badge-next {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: .62rem;
  letter-spacing: .14em;
  text-transform: uppercase;
  color: var(--gold);
  font-weight: 700;
  padding: 3px 10px;
  border: 1px solid rgba(200,148,58,.4);
  border-radius: 999px;
  background: rgba(200,148,58,.1);
  width: fit-content;
  margin-bottom: 2px;
}
.salon-nom   { font-family: 'Cormorant Garamond', serif; font-size: 1.45rem; font-weight: 700; color: #fff; }
.salon-lieu  { font-size: .85rem; color: rgba(255,255,255,.55); display: flex; align-items: center; gap: 6px; }
.salon-horaires { font-size: .8rem; color: rgba(255,255,255,.4); display: flex; align-items: center; gap: 6px; }
.salon-stand { display: inline-flex; align-items: center; gap: 5px; margin-top: 4px; font-size: .75rem; padding: 3px 10px; background: rgba(31,79,46,.4); border: 1px solid rgba(45,122,68,.4); border-radius: 999px; color: #6fd98a; width: fit-content; }
.salon-desc-text { font-size: .82rem; color: rgba(255,255,255,.4); margin-top: 4px; font-style: italic; }

.salon-arrow {
  color: rgba(200,148,58,.4);
  font-size: 1.4rem;
  transition: color .2s, transform .2s;
}
.salon-card:hover .salon-arrow { color: var(--gold); transform: translateX(4px); }

.no-salon {
  text-align: center;
  padding: 4rem 2rem;
  color: rgba(255,255,255,.4);
  font-style: italic;
  font-size: 1.05rem;
}

/* ── CTA FINAL ── */
.section-cta {
  background: linear-gradient(135deg, var(--green) 0%, #0d2014 100%);
  padding: 5rem 2rem;
  text-align: center;
}
.section-cta .section-title { color: #fff; }
.section-cta .section-sub   { color: rgba(255,255,255,.6); }
.cta-btns { display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap; margin-top: 2rem; }
.btn-primary {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 14px 32px;
  background: linear-gradient(135deg, var(--gold), #e8b860);
  color: #1a0f00; font-weight: 700; font-size: .92rem;
  border-radius: 999px; text-decoration: none;
  transition: transform .2s, box-shadow .2s;
  box-shadow: 0 6px 24px rgba(200,148,58,.3);
}
.btn-primary:hover { transform: translateY(-3px); box-shadow: 0 12px 36px rgba(200,148,58,.4); }
.btn-secondary {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 14px 32px;
  background: transparent;
  color: rgba(255,255,255,.8); font-weight: 600; font-size: .92rem;
  border: 1.5px solid rgba(255,255,255,.2);
  border-radius: 999px; text-decoration: none;
  transition: border-color .2s, color .2s;
}
.btn-secondary:hover { border-color: rgba(255,255,255,.5); color: #fff; }

/* ── RESPONSIVE ── */
@media (max-width: 768px) {
  .hero-event-card { display: none; }
  .xp-visual { display: none; }
  .salon-card { grid-template-columns: auto 1fr; }
  .salon-arrow { display: none; }
  .countdown-box { padding: 14px 16px; min-width: 68px; }
  .countdown-num { font-size: 1.9rem; }
}
</style>

<!-- ══ BG COMMUN ══ -->
<div class="page-bg">
<div class="hero-particles" id="heroParticles"></div>

<!-- ══ HERO ══ -->
<section class="salon-hero">

  <div class="hero-content">
    <div class="hero-eyebrow">
      ✨ WakAroma en salon
    </div>

    <h1 class="hero-title">
      Venez vivre<br><em>l'Afrique en épices</em>
    </h1>

    <p class="hero-desc">
      Rencontrez-nous, découvrez nos mélanges uniques, sentez, goûtez et repartez avec des saveurs qui racontent une histoire.
    </p>

    <?php if ($prochain && $joursRestants !== null): ?>
    <div class="countdown-wrap" id="countdown">
      <div class="countdown-box">
        <span class="countdown-num" id="cd-j">—</span>
        <span class="countdown-label">Jours</span>
      </div>
      <div class="countdown-box">
        <span class="countdown-num" id="cd-h">—</span>
        <span class="countdown-label">Heures</span>
      </div>
      <div class="countdown-box">
        <span class="countdown-num" id="cd-m">—</span>
        <span class="countdown-label">Minutes</span>
      </div>
      <div class="countdown-box">
        <span class="countdown-num" id="cd-s">—</span>
        <span class="countdown-label">Secondes</span>
      </div>
    </div>
    <?php endif; ?>

    <a href="#planning" class="hero-cta">
      🗓 Voir le planning complet
    </a>
  </div>

  <?php if ($prochain): ?>
  <div class="hero-event-card">
    <div class="event-card-tag">🗓 Prochain salon</div>
    <div class="event-card-name"><?= htmlspecialchars($prochain->nom) ?></div>
    <div class="event-card-info">
      <span>📍 <?= htmlspecialchars($prochain->ville) ?></span>
      <span>📅 <?= formatDate($prochain->date_debut) ?></span>
      <?php if ($prochain->heure_debut): ?><span>🕙 <?= htmlspecialchars($prochain->heure_debut) ?> – <?= htmlspecialchars($prochain->heure_fin) ?></span><?php endif; ?>
    </div>
    <div class="event-card-badge">
      <?= $joursRestants === 0 ? "Aujourd'hui !" : "Dans $joursRestants jour" . ($joursRestants > 1 ? 's' : '') ?>
    </div>
  </div>
  <?php endif; ?>
</section>

<!-- ══ PLANNING ══ -->
<section class="section-planning" id="planning">
  <div class="section-center">
    <p class="section-eyebrow" style="color:rgba(200,148,58,.7)">Agenda</p>
    <h2 class="section-title">Notre <em style="color:var(--gold)">planning</em> des salons</h2>
    <p class="section-sub">Retrouvez-nous dans toute la France. Chaque salon est une nouvelle aventure.</p>

    <div class="planning-list">
      <?php if (empty($salons)): ?>
        <div class="no-salon">
          Aucun salon programmé pour le moment.<br>Revenez bientôt ou suivez-nous sur les réseaux !
        </div>
      <?php else: ?>
        <?php foreach ($salons as $i => $s): ?>
        <?php
          $dt = new DateTime($s->date_debut);
          $dtFin = new DateTime($s->date_fin);
          $meme = isSameDay($s->date_debut, $s->date_fin);
        ?>
        <div class="salon-card <?= $i === 0 ? 'next-salon' : '' ?>">

          <div class="salon-date-block">
            <span class="salon-date-day"><?= $dt->format('j') ?></span>
            <span class="salon-date-month"><?= strtoupper(substr(moisFr($dt->format('n')), 0, 3)) ?></span>
            <span class="salon-date-year"><?= $dt->format('Y') ?></span>
          </div>

          <div class="salon-info">
            <?php if ($i === 0): ?>
            <span class="salon-badge-next">⚡ Prochain salon</span>
            <?php endif; ?>
            <span class="salon-nom"><?= htmlspecialchars($s->nom) ?></span>
            <span class="salon-lieu">📍 <?= htmlspecialchars($s->lieu) ?>, <?= htmlspecialchars($s->ville) ?></span>
            <?php if (!$meme): ?>
            <span class="salon-horaires">📅 Du <?= formatDate($s->date_debut) ?> au <?= formatDate($s->date_fin) ?></span>
            <?php endif; ?>
            <?php if ($s->heure_debut): ?>
            <span class="salon-horaires">🕙 <?= htmlspecialchars($s->heure_debut) ?> – <?= htmlspecialchars($s->heure_fin) ?></span>
            <?php endif; ?>
            <?php if ($s->stand): ?>
            <span class="salon-stand">🏷 Stand : <?= htmlspecialchars($s->stand) ?></span>
            <?php endif; ?>
            <?php if ($s->description): ?>
            <span class="salon-desc-text"><?= htmlspecialchars($s->description) ?></span>
            <?php endif; ?>
          </div>

          <span class="salon-arrow">›</span>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>
</div><!-- /.page-bg -->

<!-- ══ CTA FINAL ══ -->
<section class="section-cta">
  <div class="section-center">
    <h2 class="section-title">Prêt à nous rejoindre ?</h2>
    <p class="section-sub">Retrouvez-nous au prochain salon et laissez-vous emporter par les saveurs d'Afrique.</p>
    <div class="cta-btns">
      <a href="#planning" class="btn-primary">🗓 Voir les dates</a>
      <a href="index.php" class="btn-secondary">🌿 Découvrir nos produits</a>
    </div>
  </div>
</section>

<script>
// ── Particules flottantes du hero ──
(function() {
  const wrap = document.getElementById('heroParticles');
  for (let i = 0; i < 18; i++) {
    const p = document.createElement('div');
    p.className = 'particle';
    const size = Math.random() * 80 + 20;
    p.style.cssText = `
      width:${size}px; height:${size}px;
      left:${Math.random()*100}%;
      animation-duration:${Math.random()*12+8}s;
      animation-delay:${Math.random()*10}s;
      opacity:${Math.random()*.4+.05};
    `;
    wrap.appendChild(p);
  }
})();

// ── Compte à rebours ──
<?php if ($prochain): ?>
(function() {
  const target = new Date("<?= $prochain->date_debut ?>T<?= $prochain->heure_debut ?>:00");
  function update() {
    const diff = target - new Date();
    if (diff <= 0) {
      document.getElementById('countdown')?.remove();
      return;
    }
    const j = Math.floor(diff / 86400000);
    const h = Math.floor((diff % 86400000) / 3600000);
    const m = Math.floor((diff % 3600000)  / 60000);
    const s = Math.floor((diff % 60000)    / 1000);
    document.getElementById('cd-j').textContent = String(j).padStart(2,'0');
    document.getElementById('cd-h').textContent = String(h).padStart(2,'0');
    document.getElementById('cd-m').textContent = String(m).padStart(2,'0');
    document.getElementById('cd-s').textContent = String(s).padStart(2,'0');
  }
  update();
  setInterval(update, 1000);
})();
<?php endif; ?>

// ── Scroll doux pour les ancres ──
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const target = document.querySelector(a.getAttribute('href'));
    if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); }
  });
});
</script>