<?php include 'headear.php'; ?>

<!-- ═══════════════════════════════════════════
     LIBS — GSAP · Three.js · Splitting
════════════════════════════════════════════ -->
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,900;1,400;1,600&family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300;1,400&family=Inter:wght@300;400;500&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/splitting@1.0.6/dist/splitting.css" />

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/splitting@1.0.6/dist/splitting.min.js"></script>

<main class="wak-main">

  <!-- ░░ THREE.JS CANVAS — toile vivante ░░ -->
  <canvas id="wak-canvas"></canvas>

  <!-- ░░ LIGNE DE PROGRESSION ░░ -->
  <div class="wak-progress" id="wakProgress"></div>

  <!-- ░░ CURSEUR ░░ -->
  <div class="wak-cursor" id="wakCursor">
    <div class="wak-cursor__dot"></div>
    <div class="wak-cursor__ring"></div>
  </div>

  <!-- ░░ NAV CHAPITRES ░░ -->
  <nav class="wak-chapnav" aria-label="Navigation historique">
    <button class="wak-chapnav__dot active" data-target="wak-hero"    title="Héritage"></button>
    <button class="wak-chapnav__dot"         data-target="wak-intro"   title="Racines"></button>
    <button class="wak-chapnav__dot"         data-target="wak-tl-0"    title="Origines"></button>
    <button class="wak-chapnav__dot"         data-target="wak-tl-1"    title="France"></button>
    <button class="wak-chapnav__dot"         data-target="wak-tl-2"    title="Naissance"></button>
    <button class="wak-chapnav__dot"         data-target="wak-tl-3"    title="Expansion"></button>
    <button class="wak-chapnav__dot"         data-target="wak-tl-4"    title="Aujourd'hui"></button>
    <button class="wak-chapnav__dot"         data-target="wak-valeurs" title="Valeurs"></button>
  </nav>

  <!-- ══════════════════════════════════════
       SCÈNE 0 — HERO
  ══════════════════════════════════════ -->
  <section class="wak-scene wak-scene--hero" id="wak-hero" data-scene="0">
    <div class="wak-scene__inner">
      <span class="wak-label" data-splitting>Héritage & Élégance</span>
      <h1 class="wak-title wak-title--hero" data-splitting>
        L'Appel du goût,<br><em>L'Écho du continent</em>
      </h1>
      <p class="wak-sub" data-splitting>
        De la Corne de l'Afrique à votre table, une aventure d'exception portée par la passion des saveurs les plus précieuses.
      </p>
      <div class="wak-hero__line"></div>
    </div>
    <div class="wak-scroll-cue">
      <span>Découvrir l'héritage</span>
      <div class="wak-scroll-cue__arrow"></div>
    </div>
    <div class="wak-scene__num" aria-hidden="true">00</div>
  </section>

  <!-- ══════════════════════════════════════
       SCÈNE 1 — INTRO / RACINES
  ══════════════════════════════════════ -->
  <section class="wak-scene wak-scene--intro" id="wak-intro" data-scene="1">
    <div class="wak-scene__inner wak-scene__inner--card">
      <div class="wak-ornament" aria-hidden="true">✦</div>
      <h2 class="wak-title wak-title--mid" data-splitting>
        Un héritage précieux<br>depuis des générations
      </h2>
      <div class="wak-divider"></div>
      <p class="wak-body wak-body--centered" data-splitting>
        WakAroma puise ses racines dans un héritage familial d'exception — celui de femmes et d'hommes qui, depuis des siècles,
        cultivaient et élevaient les épices les plus fines de la Corne de l'Afrique. Cardamome royale, encens sacré, curcuma sauvage, fenugreek doré…
        ces trésors botaniques se transmettaient comme des secrets précieux, de génération en génération.
      </p>
      <p class="wak-body wak-body--centered" data-splitting>
        Aujourd'hui, WakAroma porte cette tradition d'excellence jusqu'en France, en sélectionnant chaque produit à la source
        avec un soin méticuleux, pour préserver l'intégrité et la puissance des arômes originels.
      </p>
    </div>
    <div class="wak-scene__num" aria-hidden="true">01</div>
  </section>

  <!-- ══════════════════════════════════════
       SCÈNES 2–6 — TIMELINE (une par item)
  ══════════════════════════════════════ -->

  <!-- TL 0 — Origines -->
  <section class="wak-scene wak-scene--tl" id="wak-tl-0" data-scene="2">
    <div class="wak-scene__inner wak-scene__inner--centered">
      <div class="wak-tl__text">
        <span class="wak-label" data-splitting>Notre cheminement</span>
        <span class="wak-tl__badge">Origines sacrées</span>
        <h2 class="wak-title wak-title--tl" data-splitting>La transmission ancestrale</h2>
        <p class="wak-body wak-body--centered wak-body--wide" data-splitting>
          Dans les marchés d'exception de Djibouti et de la Somalie, la famille sélectionne et prépare les épices selon des rituels ancestraux. Chaque arôme raconte une mémoire, chaque mélange est une œuvre d'art.
        </p>
      </div>
    </div>
    <div class="wak-scene__num" aria-hidden="true">02</div>
  </section>

  <!-- TL 1 — France -->
  <section class="wak-scene wak-scene--tl" id="wak-tl-1" data-scene="3">
    <div class="wak-scene__inner wak-scene__inner--centered">
      <div class="wak-tl__text">
        <span class="wak-tl__badge">La nostalgie du continent</span>
        <h2 class="wak-title wak-title--tl" data-splitting>L'arrivée en France</h2>
        <p class="wak-body wak-body--centered wak-body--wide" data-splitting>
          Installée en France, la fondatrice ressent l'absence d'une certaine élégance culinaire. Impossible de retrouver la cardamome fumée ou le xawaash authentique dans le commerce. Pourtant, derrière cette nostalgie se cachent vingt ans de travail silencieux — sélectionner, doser, affiner chaque mélange jusqu'à la perfection. Naît alors l'idée d'une passerelle d'exception.
        </p>
      </div>
    </div>
    <div class="wak-scene__num" aria-hidden="true">03</div>
  </section>

  <!-- TL 2 — Naissance -->
  <section class="wak-scene wak-scene--tl" id="wak-tl-2" data-scene="4">
    <div class="wak-scene__inner wak-scene__inner--centered">
      <div class="wak-tl__text">
        <span class="wak-tl__badge">La naissance</span>
        <h2 class="wak-title wak-title--tl" data-splitting>WakAroma voit le jour</h2>
        <p class="wak-body wak-body--centered wak-body--wide" data-splitting>
          Les premières préparations sont élaborées à la main, dans l'écrin de la cuisine familiale. Un mariage subtil entre des épices d'exception directement importées et des trésors dénichés en France — car WakAroma est née de ces deux mondes. Le bouche-à-oreille d'une clientèle exigeante révèle ces trésors uniques.
        </p>
      </div>
    </div>
    <div class="wak-scene__num" aria-hidden="true">04</div>
  </section>

  <!-- TL 3 — Expansion -->
  <section class="wak-scene wak-scene--tl" id="wak-tl-3" data-scene="5">
    <div class="wak-scene__inner wak-scene__inner--centered">
      <div class="wak-tl__text">
        <span class="wak-tl__badge">L'expansion</span>
        <h2 class="wak-title wak-title--tl" data-splitting>Au-delà des frontières</h2>
        <p class="wak-body wak-body--centered wak-body--wide" data-splitting>
          WakAroma a vocation à dépasser les cercles communautaires. Des chefs étoilés, des épiceries fines et des amateurs éclairés pourraient un jour s'approprier ces mélanges rares. La boutique en ligne, elle, est prête à accueillir une clientèle internationale.
        </p>
      </div>
    </div>
    <div class="wak-scene__num" aria-hidden="true">05</div>
  </section>

  <!-- TL 4 — Aujourd'hui -->
  <section class="wak-scene wak-scene--tl" id="wak-tl-4" data-scene="6">
    <div class="wak-scene__inner wak-scene__inner--centered">
      <span class="wak-tl__badge">Aujourd'hui</span>
      <h2 class="wak-title wak-title--large" data-splitting>WakAroma,<br><em>un pont<br>d'excellence</em></h2>
      <p class="wak-body wak-body--centered wak-body--wide" data-splitting>
        Une collection d'épices rares et de mélanges soigneusement élaborés, pensés pour révéler la profondeur et la complexité des cuisines du monde. Chaque produit est une invitation au voyage — il porte en lui la mémoire d'un terroir, la précision d'un geste, et la vision d'une femme qui a fait de la transmission culinaire sa raison d'être.
      </p>
      <div class="wak-fin-ornament" aria-hidden="true"></div>
    </div>
    <div class="wak-scene__num" aria-hidden="true">06</div>
  </section>

  <!-- ══════════════════════════════════════
       SCÈNE 7 — VALEURS
  ══════════════════════════════════════ -->
  <section class="wak-scene wak-scene--valeurs" id="wak-valeurs" data-scene="7">
    <div class="wak-scene__inner">
      <span class="wak-label">Notre éthique</span>
      <h2 class="wak-title wak-title--mid" data-splitting>Une vision d'excellence</h2>
      <div class="wak-divider"></div>
      <div class="wak-valeurs__grid">

        <div class="wak-valeur-card">
          <h3 class="wak-valeur-card__title" data-splitting>Authenticité précieuse</h3>
          <p class="wak-valeur-card__text">Chaque épice est sélectionnée directement à la source, sans intermédiaire, pour préserver toute la noblesse aromatique originelle.</p>
        </div>

        <div class="wak-valeur-card">
          <h3 class="wak-valeur-card__title" data-splitting>Commerce équitable</h3>
          <p class="wak-valeur-card__text">WakAroma soutient avec respect les productrices et producteurs africains, assurant une juste rémunération et des conditions dignes.</p>
        </div>

        <div class="wak-valeur-card">
          <h3 class="wak-valeur-card__title" data-splitting>Artisanat d'exception</h3>
          <p class="wak-valeur-card__text">Chaque pot est préparé et conditionné avec un soin méticuleux, perpétuant l'exigence d'une tradition familiale.</p>
        </div>

        <div class="wak-valeur-card">
          <h3 class="wak-valeur-card__title" data-splitting>Pont culturel</h3>
          <p class="wak-valeur-card__text">Partager les saveurs d'Afrique, c'est offrir un voyage d'exception. WakAroma est une invitation à l'évasion dans chaque assiette.</p>
        </div>

      </div>
    </div>
    <div class="wak-scene__num" aria-hidden="true">07</div>
  </section>

  <!-- ══════════════════════════════════════
       SCÈNE 8 — CTA FINAL
  ══════════════════════════════════════ -->
  <section class="wak-scene wak-scene--cta" id="wak-cta" data-scene="8">
    <div class="wak-scene__bg-text" aria-hidden="true">WAK</div>
    <div class="wak-scene__inner wak-scene__inner--cta">
      <div class="wak-ornament wak-ornament--large" aria-hidden="true">✦</div>
      <h2 class="wak-title wak-title--cta" data-splitting>Laissez-vous<br><em>séduire</em></h2>
      <p class="wak-body wak-body--centered" data-splitting>
        Découvrez notre collection d'épices rares, d'encens précieux et de cosmétiques naturels — un voyage d'exception de la Corne de l'Afrique à votre table.
      </p>
      <a href="index.php" class="wak-cta-btn">Explorer la collection</a>
    </div>
  </section>

</main>

<?php include 'footer.php'; ?>

<!-- ═══════════════════════════════════════════
     STYLES
════════════════════════════════════════════ -->
<style>
/* ── RESET SCOPED ── */
.wak-main *, .wak-main *::before, .wak-main *::after { box-sizing: border-box; }

/* ── VARIABLES WAKAROMA ── */
:root {
  --wak-gold:       #e8c89a;
  --wak-gold-deep:  #b27d40;
  --wak-gold-dim:   rgba(232,200,154,0.45);
  --wak-gold-faint: rgba(232,200,154,0.15);
  --wak-green:      #1a3d2f;
  --wak-green-deep: #0d261c;
  --wak-white:      #faf6f0;
  --wak-white-dim:  rgba(250,246,240,0.65);
  --wak-white-faint:rgba(250,246,240,0.22);
  --wak-serif:      'Playfair Display', 'Cormorant Garamond', Georgia, serif;
  --wak-body-serif: 'Cormorant Garamond', Georgia, serif;
  --wak-sans:       'Inter', system-ui, sans-serif;
  --wak-ease:       cubic-bezier(0.16,1,0.3,1);
  --wak-border:     1px solid rgba(232,200,154,0.28);
}

/* ── CANVAS THREE.JS ── */
#wak-canvas {
  position: fixed; inset: 0;
  width: 100%; height: 100%;
  z-index: 0; pointer-events: none;
}

/* ── PROGRESS ── */
.wak-progress {
  position: fixed; top: 0; left: 0;
  width: 0%; height: 2px;
  background: linear-gradient(90deg, var(--wak-gold-deep), var(--wak-gold), var(--wak-gold-deep));
  z-index: 1000;
}

/* ── CURSEUR ── */
body { cursor: none; }
.wak-cursor {
  position: fixed;
  pointer-events: none;
  z-index: 9999;
  top: 0; left: 0;
  will-change: transform;
}
.wak-cursor__dot {
  position: absolute; top: 50%; left: 50%;
  width: 7px; height: 7px;
  background: var(--wak-gold);
  border-radius: 50%;
  transform: translate(-50%,-50%);
  transition: width .2s var(--wak-ease), height .2s var(--wak-ease);
}
.wak-cursor__ring {
  width: 36px; height: 36px;
  border: 1px solid var(--wak-gold-dim);
  border-radius: 50%;
  transition: width .3s var(--wak-ease), height .3s var(--wak-ease), border-color .3s;
}
.wak-cursor.is-hover .wak-cursor__ring {
  width: 64px; height: 64px;
  border-color: var(--wak-gold);
}
.wak-cursor.is-hover .wak-cursor__dot {
  width: 4px; height: 4px; background: var(--wak-gold);
}
@media (pointer:coarse) { body{cursor:auto;} .wak-cursor{display:none;} }

/* ── NAV CHAPITRES ── */
.wak-chapnav {
  position: fixed; right: 1.8rem; top: 50%;
  transform: translateY(-50%);
  display: flex; flex-direction: column; gap: 10px;
  z-index: 100;
}
.wak-chapnav__dot {
  width: 5px; height: 5px;
  border-radius: 50%;
  background: var(--wak-white-faint);
  border: 1px solid var(--wak-white-faint);
  cursor: pointer; padding: 0;
  transition: all .4s var(--wak-ease);
}
.wak-chapnav__dot.active {
  background: var(--wak-gold);
  border-color: var(--wak-gold);
  transform: scaleY(2.8); border-radius: 2px;
}
@media (max-width:600px){ .wak-chapnav{right:.6rem;} }

/* ── MAIN WRAPPER ── */
.wak-main {
  position: relative;
  background: transparent;
  overflow-x: hidden;
}

/* ── SCENES ── */
.wak-scene {
  position: relative; z-index: 1;
  min-height: 100vh;
  display: flex; align-items: center; justify-content: center;
  padding: 10vh clamp(1.5rem, 6vw, 5rem);
  overflow: hidden;
}
.wak-scene__inner {
  position: relative; z-index: 1;
  max-width: 900px; width: 100%; margin: 0 auto;
}
.wak-scene__inner--card {
  background: rgba(255,255,255,0.06);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: var(--wak-border);
  border-radius: 40px;
  padding: clamp(2.5rem,5vw,5rem) clamp(2rem,5vw,5.5rem);
  box-shadow: 0 24px 60px rgba(0,0,0,0.2);
  text-align: center;
}
.wak-scene__inner--centered { text-align: center; }
.wak-scene__inner--cta {
  background: rgba(255,255,255,0.06);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: var(--wak-border);
  border-radius: 48px;
  padding: clamp(3rem,6vw,5rem) clamp(2rem,5vw,4.5rem);
  text-align: center; max-width: 720px;
}

/* ── BIG GHOST BACKGROUND TEXT ── */
.wak-scene__num {
  position: absolute; bottom: -0.05em; right: 3vw;
  font-family: var(--wak-serif);
  font-size: clamp(9rem,18vw,22rem);
  font-weight: 900; line-height: 1;
  color: rgba(232,200,154,0.04);
  pointer-events: none; user-select: none;
}
.wak-scene--cta .wak-scene__bg-text {
  position: absolute; left:50%; top:50%;
  transform: translate(-50%,-50%);
  font-family: var(--wak-serif);
  font-size: clamp(22vw,30vw,380px);
  font-weight: 900;
  color: rgba(232,200,154,0.025);
  pointer-events: none; white-space: nowrap; user-select: none;
}

/* ── TYPOGRAPHY ── */
.wak-label {
  display: inline-block;
  font-family: var(--wak-sans);
  font-size: 0.7rem;
  letter-spacing: 0.38em;
  text-transform: uppercase;
  font-weight: 500;
  color: var(--wak-gold);
  background: var(--wak-gold-faint);
  border: 1px solid rgba(232,200,154,0.35);
  padding: 0.45rem 1.3rem;
  border-radius: 40px;
  margin-bottom: 1.8rem;
  opacity: 0; transform: translateX(-16px);
}

.wak-title {
  font-family: var(--wak-serif);
  color: var(--wak-white);
  letter-spacing: -0.02em;
  margin-bottom: 1.5rem;
  line-height: 1.05;
}
.wak-title em { font-style: italic; color: var(--wak-gold); font-weight: 400; }
.wak-title--hero { font-size: clamp(3rem,7vw,6rem); font-weight: 600; }
.wak-title--mid  { font-size: clamp(2.2rem,4.5vw,3.8rem); font-weight: 700; }
.wak-title--tl   { font-size: clamp(2.3rem,4.6vw,3.8rem); font-weight: 600; }
.wak-title--large { font-size: clamp(3rem,7vw,5.5rem); font-weight: 900; }
.wak-title--cta  { font-size: clamp(2.8rem,6vw,5rem); font-weight: 600; }

.wak-body {
  font-family: var(--wak-body-serif);
  font-size: clamp(1.15rem,1.9vw,1.45rem);
  line-height: 1.85;
  color: var(--wak-white-dim);
  font-weight: 400;
  letter-spacing: 0.01em;
  margin-bottom: 1.4rem;
}
.wak-body--centered { text-align: center; max-width: 760px; margin-left:auto; margin-right:auto; }
.wak-body--wide { max-width: 880px; }

.wak-ornament {
  font-size: 2rem; color: var(--wak-gold);
  opacity: 0.6; margin-bottom: 1.2rem; letter-spacing: 0.2em;
  display: block;
}
.wak-ornament--large { font-size: 2.8rem; margin-bottom: 1.5rem; }

.wak-divider {
  width: 55px; height: 1px;
  background: linear-gradient(90deg, transparent, var(--wak-gold), transparent);
  margin: 1rem auto 2.2rem; opacity: 0.55;
}

/* ── SPLITTING.JS chars ── */
.wak-title .word, .wak-body .word, .wak-label .word { display:inline-block; overflow:hidden; vertical-align:bottom; }
.wak-title .char, .wak-body .char, .wak-label .char {
  display: inline-block;
  transform: translateY(115%);
  opacity: 0;
  will-change: transform, opacity;
}

/* ── HERO ── */
.wak-scene--hero { min-height: 100vh; }
.wak-hero__line {
  width: 60px; height: 1px;
  background: linear-gradient(90deg, transparent, var(--wak-gold), transparent);
  margin: 2rem auto 0; opacity: 0.6;
  animation: wakLinePulse 2.5s ease-in-out infinite;
}
@keyframes wakLinePulse {
  0%,100%{width:60px;opacity:.5} 50%{width:100px;opacity:.9}
}
.wak-scroll-cue {
  position: absolute; bottom: 2.5rem; left: 50%;
  transform: translateX(-50%);
  display: flex; align-items: center; gap: 1rem;
  font-family: var(--wak-sans); font-size: 0.65rem;
  letter-spacing: 0.28em; text-transform: uppercase;
  color: var(--wak-white-faint);
}
.wak-scroll-cue__arrow {
  width: 16px; height: 16px;
  border-right: 1px solid var(--wak-gold);
  border-bottom: 1px solid var(--wak-gold);
  transform: rotate(45deg);
  animation: wak-bounce 1.8s ease-in-out infinite;
}
@keyframes wak-bounce {
  0%,100%{transform:rotate(45deg) translateY(0);opacity:.6}
  50%{transform:rotate(45deg) translateY(5px);opacity:1}
}

/* ── SUB HERO ── */
.wak-sub {
  font-family: var(--wak-sans); font-size: clamp(0.9rem,1.3vw,1.05rem);
  font-weight: 300; line-height: 1.8;
  color: var(--wak-white-dim); max-width: 520px;
  margin-bottom: 1.5rem; letter-spacing: 0.02em;
}
.wak-sub .char { transform: translateY(115%); opacity: 0; }

/* ── TIMELINE SCENES ── */
.wak-tl__text { display: flex; flex-direction: column; align-items: center; gap: 0.4rem; }
.wak-tl__badge {
  display: inline-flex; align-self: center;
  font-family: var(--wak-sans); font-size: 0.72rem;
  letter-spacing: 0.22em; text-transform: uppercase; font-weight: 700;
  color: var(--wak-gold);
  background: rgba(232,200,154,0.14);
  border: 1px solid rgba(232,200,154,0.3);
  padding: 0.3rem 1rem; border-radius: 20px; margin-bottom: 0.8rem;
}

/* Ornement fin de section Aujourd'hui */
.wak-fin-ornament {
  margin-top: 3rem; opacity: 0;
}
.wak-fin-ornament::before {
  content: '✦ ✦ ✦';
  display: block;
  text-align: center;
  color: var(--wak-gold);
  letter-spacing: 1em;
  font-size: 0.9rem;
}

/* ── VALEURS ── */
.wak-scene--valeurs { padding-bottom: 8vh; }
.wak-valeurs__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
  margin-top: 2rem;
}
.wak-valeur-card {
  background: rgba(255,255,255,0.06);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: var(--wak-border);
  border-radius: 24px;
  padding: 2.5rem 2rem;
  text-align: center;
  opacity: 0; transform: translateY(28px) rotate(-1deg);
  transition: box-shadow .3s, border-color .3s, background .3s;
}
.wak-valeur-card:hover {
  transform: translateY(-7px) rotate(0deg) !important;
  border-color: rgba(232,200,154,0.5);
  background: rgba(255,255,255,0.1);
  box-shadow: 0 24px 48px rgba(0,0,0,0.22);
}
.wak-valeur-card__icon { font-size: 2.8rem; margin-bottom: 1.2rem; opacity: 0.85; }
.wak-valeur-card__title {
  font-family: var(--wak-serif); font-size: 1.45rem; font-weight: 700;
  color: var(--wak-white); margin-bottom: 0.9rem;
}
.wak-valeur-card__title .char { transform: translateY(115%); opacity: 0; }
.wak-valeur-card__text {
  font-family: var(--wak-body-serif); font-size: 1.05rem;
  color: var(--wak-white-dim); line-height: 1.85;
}

/* ── CTA ── */
.wak-scene--cta { min-height: 80vh; }
.wak-cta-btn {
  display: inline-block;
  padding: 1rem 2.8rem;
  background: transparent;
  border: 1.5px solid var(--wak-gold);
  color: var(--wak-gold);
  border-radius: 50px;
  font-family: var(--wak-sans);
  font-size: 0.82rem; font-weight: 600;
  letter-spacing: 0.12em; text-transform: uppercase;
  text-decoration: none;
  transition: all .3s var(--wak-ease);
  position: relative; z-index: 2;
  margin-top: 1.5rem;
}
.wak-cta-btn:hover {
  background: var(--wak-gold);
  color: var(--wak-green-deep);
  transform: translateY(-3px);
  box-shadow: 0 14px 32px rgba(232,200,154,0.22);
}

/* ── RESPONSIVE ── */
@media (max-width: 768px) {
  .wak-scene__num { font-size: 20vw; }
  .wak-chapnav { right: 0.5rem; }
  .wak-scene__inner--card { padding: 2.5rem 1.5rem; border-radius: 24px; }
  .wak-scene__inner--cta  { padding: 2.5rem 1.5rem; }
}
</style>

<!-- ═══════════════════════════════════════════
     SCRIPT — Three.js · GSAP ScrollTrigger · Splitting
     (Lenis supprimé — conflit avec le scroll natif du site)
════════════════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function () {

  /* ── 1. GSAP register ── */
  gsap.registerPlugin(ScrollTrigger);

  /* ── 2. Splitting — chars doivent exister avant les triggers ── */
  Splitting({ target: '[data-splitting]', by: 'chars' });

  /* ════════════════════════════════════════
     PALETTE PAR SCÈNE
  ════════════════════════════════════════ */
  const WAK_SCENES = [
    { bg1:[0.04,0.10,0.07], bg2:[0.07,0.16,0.10], acc:[0.91,0.78,0.60], ns:2.0, nsp:0.00013 },
    { bg1:[0.05,0.12,0.08], bg2:[0.08,0.20,0.12], acc:[0.85,0.70,0.45], ns:1.7, nsp:0.00018 },
    { bg1:[0.04,0.09,0.06], bg2:[0.06,0.15,0.09], acc:[0.70,0.88,0.62], ns:2.4, nsp:0.00022 },
    { bg1:[0.04,0.07,0.08], bg2:[0.05,0.12,0.14], acc:[0.60,0.78,0.70], ns:2.8, nsp:0.00028 },
    { bg1:[0.06,0.09,0.05], bg2:[0.10,0.16,0.08], acc:[0.91,0.78,0.60], ns:2.0, nsp:0.00015 },
    { bg1:[0.03,0.08,0.06], bg2:[0.05,0.14,0.09], acc:[0.72,0.90,0.64], ns:2.6, nsp:0.00032 },
    { bg1:[0.04,0.10,0.07], bg2:[0.06,0.17,0.11], acc:[0.91,0.78,0.60], ns:1.9, nsp:0.00012 },
    { bg1:[0.04,0.09,0.06], bg2:[0.06,0.15,0.09], acc:[0.85,0.70,0.45], ns:2.2, nsp:0.00016 },
    { bg1:[0.03,0.08,0.05], bg2:[0.05,0.12,0.07], acc:[0.91,0.78,0.60], ns:1.8, nsp:0.00010 },
  ];

  /* ════════════════════════════════════════
     THREE.JS — TOILE PEINTE VIVANTE
     pointer-events:none sur le canvas —
     ne bloque pas du tout le scroll natif
  ════════════════════════════════════════ */
  const cvs = document.getElementById('wak-canvas');
  const renderer = new THREE.WebGLRenderer({ canvas: cvs, antialias: false, powerPreference: 'low-power' });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.2));
  renderer.setSize(window.innerWidth, window.innerHeight);

  const threeScene = new THREE.Scene();
  const cam = new THREE.OrthographicCamera(-1, 1, 1, -1, 0, 1);

  const vert = `varying vec2 vUv; void main(){ vUv=uv; gl_Position=vec4(position,1.0); }`;
  const frag = `
  precision mediump float;
  varying vec2 vUv;
  uniform float uTime; uniform vec2 uMouse,uRes;
  uniform vec3 uBg1,uBg2,uAcc;
  uniform float uNS,uNSP;
  vec3 mod289(vec3 x){return x-floor(x*(1./289.))*289.;}
  vec2 mod289(vec2 x){return x-floor(x*(1./289.))*289.;}
  vec3 permute(vec3 x){return mod289(((x*34.)+1.)*x);}
  float sn(vec2 v){
    const vec4 C=vec4(0.211325,0.366025,-0.577350,0.024390);
    vec2 i=floor(v+dot(v,C.yy)),x0=v-i+dot(i,C.xx);
    vec2 i1=(x0.x>x0.y)?vec2(1.,0.):vec2(0.,1.);
    vec4 x12=x0.xyxy+C.xxzz; x12.xy-=i1; i=mod289(i);
    vec3 p=permute(permute(i.y+vec3(0.,i1.y,1.))+i.x+vec3(0.,i1.x,1.));
    vec3 m=max(0.5-vec3(dot(x0,x0),dot(x12.xy,x12.xy),dot(x12.zw,x12.zw)),0.);
    m=m*m; m=m*m;
    vec3 x=2.*fract(p*C.www)-1.,h=abs(x)-.5,ox=floor(x+.5),a0=x-ox;
    m*=1.79284-.85373*(a0*a0+h*h);
    vec3 g; g.x=a0.x*x0.x+h.x*x0.y; g.yz=a0.yz*x12.xz+h.yz*x12.yw;
    return 130.*dot(m,g);
  }
  void main(){
    vec2 uv=vUv; float t=uTime*uNSP;
    vec2 asp=vec2(uRes.x/uRes.y,1.);
    float md=length(uv*asp-uMouse*asp);
    float mw=smoothstep(.55,.0,md)*.08;
    vec2 p=uv*uNS+vec2(t*.45,t*.3)+vec2(mw*sin(uTime*.0008),mw*cos(uTime*.0006));
    float n1=sn(p)*.5+.5, n2=sn(p*2.4+vec2(4.1,1.8))*.5+.5, n3=sn(p*5.5+vec2(2.2,4.3))*.5+.5;
    float n=n1*.55+n2*.3+n3*.15;
    vec2 vig=uv*2.-1.;
    float vg=clamp(1.-dot(vig*vec2(1.,1.35),vig*vec2(1.,1.35))*.5,0.,1.);
    vec3 col=mix(mix(uBg1,uBg2,uv.y+n*.18),uAcc,smoothstep(.4,.75,n2)*smoothstep(.65,.3,n1)*.22);
    col+=uAcc*smoothstep(.42,.0,md)*.07;
    col*=vg;
    col+=(fract(sin(dot(uv+t,vec2(127.1,311.7)))*43758.5)-.5)*.015;
    gl_FragColor=vec4(clamp(col,0.,1.),1.);
  }`;

  const uni = {
    uTime:  { value: 0 },
    uMouse: { value: new THREE.Vector2(.5,.5) },
    uRes:   { value: new THREE.Vector2(window.innerWidth, window.innerHeight) },
    uBg1:   { value: new THREE.Vector3(...WAK_SCENES[0].bg1) },
    uBg2:   { value: new THREE.Vector3(...WAK_SCENES[0].bg2) },
    uAcc:   { value: new THREE.Vector3(...WAK_SCENES[0].acc) },
    uNS:    { value: WAK_SCENES[0].ns },
    uNSP:   { value: WAK_SCENES[0].nsp },
  };
  threeScene.add(new THREE.Mesh(new THREE.PlaneGeometry(2,2), new THREE.ShaderMaterial({ vertexShader:vert, fragmentShader:frag, uniforms:uni })));

  window.addEventListener('resize', () => {
    renderer.setSize(window.innerWidth, window.innerHeight);
    uni.uRes.value.set(window.innerWidth, window.innerHeight);
  });

  /* Mouse — mise à jour directe sans lerp dans le RAF */
  document.addEventListener('mousemove', e => {
    uni.uMouse.value.set(e.clientX / window.innerWidth, 1 - e.clientY / window.innerHeight);
  }, { passive: true });

  /* RAF Three.js uniquement — ne touche pas au scroll */
  (function rl(ts) {
    uni.uTime.value = ts * 0.001;
    renderer.render(threeScene, cam);
    requestAnimationFrame(rl);
  })(0);

  /* Tween palette au changement de scène */
  function tweenScene(i) {
    const s = WAK_SCENES[Math.min(i, WAK_SCENES.length - 1)];
    gsap.to(uni.uBg1.value, { x:s.bg1[0], y:s.bg1[1], z:s.bg1[2], duration:2, ease:'power2.inOut' });
    gsap.to(uni.uBg2.value, { x:s.bg2[0], y:s.bg2[1], z:s.bg2[2], duration:2, ease:'power2.inOut' });
    gsap.to(uni.uAcc.value, { x:s.acc[0], y:s.acc[1], z:s.acc[2], duration:2, ease:'power2.inOut' });
    gsap.to(uni, { 'uNS.value':s.ns, 'uNSP.value':s.nsp, duration:2.4, ease:'power1.inOut' });
  }

  /* ════════════════════════════════════════
     BARRE DE PROGRESSION — scroll natif
  ════════════════════════════════════════ */
  const progressEl = document.getElementById('wakProgress');
  window.addEventListener('scroll', () => {
    const max = document.documentElement.scrollHeight - window.innerHeight;
    progressEl.style.width = (max > 0 ? window.scrollY / max * 100 : 0) + '%';
  }, { passive: true });

  /* ════════════════════════════════════════
     NAV DOTS — scroll natif
  ════════════════════════════════════════ */
  const dots = document.querySelectorAll('.wak-chapnav__dot');
  let currentScene = 0;

  function setScene(i) {
    if (i === currentScene) return;
    currentScene = i;
    dots.forEach((d, j) => d.classList.toggle('active', j === i));
    tweenScene(i);
  }

  dots.forEach(dot => {
    dot.addEventListener('click', () => {
      const el = document.getElementById(dot.dataset.target);
      if (!el) return;
      /* scroll natif fluide — behavior:smooth suffit */
      el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });

  /* ════════════════════════════════════════
     SCROLLTRIGGER — scroll natif (pas de scroller:)
  ════════════════════════════════════════ */

  /* Détection scène active */
  document.querySelectorAll('.wak-scene[data-scene]').forEach(sec => {
    const idx = parseInt(sec.dataset.scene);
    ScrollTrigger.create({
      trigger: sec,
      start: 'top 55%',
      end:   'bottom 45%',
      onEnter:     () => setScene(idx),
      onEnterBack: () => setScene(idx),
    });
  });

  /* Parallax ghost numbers */
  document.querySelectorAll('.wak-scene__num').forEach(n => {
    gsap.fromTo(n, { y: '10%', opacity: 0 }, {
      y: '-10%', opacity: 0.04, ease: 'none',
      scrollTrigger: { trigger: n.closest('.wak-scene'), start: 'top bottom', end: 'bottom top', scrub: 1.5 }
    });
  });

  /* ── Animations par section ── */
  animLabel('#wak-hero .wak-label');
  animChars('#wak-hero .wak-title', 0.1, 0.030);
  animChars('#wak-hero .wak-sub',   0.4, 0.020);
  gsap.from('#wak-hero .wak-hero__line',  { scaleX:0, duration:1.2, delay:0.9, ease:'power3.inOut', scrollTrigger:{ trigger:'#wak-hero', start:'top 75%' }});
  gsap.from('#wak-hero .wak-scroll-cue',  { opacity:0, y:12, duration:1, delay:1.3, scrollTrigger:{ trigger:'#wak-hero', start:'top 75%' }});

  animLabel('#wak-intro .wak-ornament');
  animChars('#wak-intro .wak-title', 0.1, 0.026);
  animChars('#wak-intro .wak-body:nth-of-type(1)', 0.25, 0.014);
  animChars('#wak-intro .wak-body:nth-of-type(2)', 0.45, 0.014);
  gsap.from('#wak-intro .wak-divider', { scaleX:0, duration:1.2, ease:'power3.inOut', scrollTrigger:{ trigger:'#wak-intro', start:'top 60%' }});

  ['#wak-tl-0','#wak-tl-1','#wak-tl-2','#wak-tl-3'].forEach(id => {
    animChars(id + ' .wak-title', 0.08, 0.028);
    animChars(id + ' .wak-body',  0.22, 0.014);
  });
  animLabel('#wak-tl-0 .wak-label');

  animChars('#wak-tl-4 .wak-title', 0.08, 0.034);
  animChars('#wak-tl-4 .wak-body',  0.35, 0.014);
  gsap.to('#wak-tl-4 .wak-fin-ornament', { opacity:1, duration:1.4, delay:0.7, scrollTrigger:{ trigger:'#wak-tl-4', start:'top 58%' }});

  animChars('#wak-valeurs .wak-title', 0.08, 0.026);
  gsap.to('#wak-valeurs .wak-valeur-card', {
    opacity:1, y:0, rotation:0,
    stagger:{ each:0.12 }, duration:0.9, ease:'back.out(1.3)',
    scrollTrigger:{ trigger:'#wak-valeurs .wak-valeurs__grid', start:'top 70%' }
  });
  document.querySelectorAll('.wak-valeur-card__title').forEach(t => {
    gsap.to(t.querySelectorAll('.char'), {
      y:'0%', opacity:1, stagger:.025, duration:.75, ease:'power3.out',
      scrollTrigger:{ trigger: t.closest('.wak-valeur-card'), start:'top 80%' }
    });
  });

  gsap.from('#wak-cta .wak-ornament', { opacity:0, scale:.5, duration:1.1, ease:'back.out(2)', scrollTrigger:{ trigger:'#wak-cta', start:'top 62%' }});
  animChars('#wak-cta .wak-title', 0.15, 0.032);
  animChars('#wak-cta .wak-body',  0.45, 0.016);
  gsap.from('#wak-cta .wak-cta-btn', { opacity:0, y:18, duration:0.9, delay:0.8, scrollTrigger:{ trigger:'#wak-cta', start:'top 62%' }});

  /* ── HELPERS ── */
  function animLabel(sel) {
    const el = document.querySelector(sel); if (!el) return;
    gsap.to(el, { opacity:1, x:0, duration:0.9, ease:'power3.out',
      scrollTrigger:{ trigger: el.closest('.wak-scene'), start:'top 65%' }
    });
  }
  function animChars(sel, delay = 0, stagger = .024) {
    const el = document.querySelector(sel); if (!el) return;
    gsap.to(el.querySelectorAll('.char'), {
      y:'0%', opacity:1, duration:.85, ease:'power3.out', stagger, delay,
      scrollTrigger:{ trigger: el.closest('.wak-scene'), start:'top 63%' }
    });
  }

  /* ════════════════════════════════════════
     CURSEUR CUSTOM — CSS transform uniquement,
     zéro impact sur le scroll
  ════════════════════════════════════════ */
  const cur = document.getElementById('wakCursor');
  if (cur && window.matchMedia('(pointer:fine)').matches) {
    document.addEventListener('mousemove', e => {
      cur.style.transform = `translate(calc(${e.clientX}px - 50%), calc(${e.clientY}px - 50%))`;
    }, { passive: true });
    document.querySelectorAll('a, button, .wak-valeur-card').forEach(el => {
      el.addEventListener('mouseenter', () => cur.classList.add('is-hover'));
      el.addEventListener('mouseleave', () => cur.classList.remove('is-hover'));
    });
  } else if (cur) {
    cur.style.display = 'none';
  }

  /* ── Refresh ScrollTrigger une fois tout défini ── */
  ScrollTrigger.refresh();

}); /* fin DOMContentLoaded */
</script>
