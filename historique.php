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
    <div class="wak-scene__inner wak-scene__inner--split">
      <div class="wak-tl__text">
        <span class="wak-label" data-splitting>Notre cheminement</span>
        <span class="wak-tl__badge">Origines sacrées</span>
        <h2 class="wak-title wak-title--tl" data-splitting>La transmission ancestrale</h2>
        <p class="wak-body" data-splitting>
          Dans les marchés d'exception de Djibouti et de la Somalie, la famille sélectionne et prépare les épices selon des rituels ancestraux. Chaque arôme raconte une mémoire, chaque mélange est une œuvre d'art.
        </p>
      </div>
      <div class="wak-tl__visual" aria-hidden="true">
        <svg class="wak-svg-art wak-svg-spin" viewBox="0 0 220 220" xmlns="http://www.w3.org/2000/svg">
          <circle cx="110" cy="110" r="100" fill="none" stroke="rgba(232,200,154,0.15)" stroke-width="1"/>
          <circle cx="110" cy="72"  r="72"  fill="none" stroke="rgba(232,200,154,0.22)" stroke-width="0.8" transform="rotate(0 110 110)"/>
          <circle cx="110" cy="110" r="44"  fill="none" stroke="rgba(232,200,154,0.35)" stroke-width="1"/>
          <g stroke="rgba(232,200,154,0.50)" stroke-width="0.9" fill="none">
            <path d="M110,110 Q132,68 110,36 Q88,68 110,110"/>
            <path d="M110,110 Q152,88 184,110 Q152,132 110,110"/>
            <path d="M110,110 Q132,152 110,184 Q88,152 110,110"/>
            <path d="M110,110 Q68,132 36,110 Q68,88 110,110"/>
            <path d="M110,110 Q148,70 150,36 Q124,70 110,110" opacity=".4"/>
            <path d="M110,110 Q150,150 184,150 Q150,124 110,110" opacity=".4"/>
            <path d="M110,110 Q70,150 70,184 Q96,150 110,110" opacity=".4"/>
            <path d="M110,110 Q70,70 36,70 Q70,96 110,110" opacity=".4"/>
          </g>
          <circle cx="110" cy="110" r="5" fill="rgba(232,200,154,0.8)"/>
          <circle cx="110" cy="110" r="14" fill="none" stroke="rgba(232,200,154,0.4)" stroke-width="0.8"/>
          <g stroke="rgba(232,200,154,0.45)" stroke-width="1.5" stroke-linecap="round">
            <line x1="110" y1="9" x2="110" y2="22"/>
            <line x1="110" y1="198" x2="110" y2="211"/>
            <line x1="9" y1="110" x2="22" y2="110"/>
            <line x1="198" y1="110" x2="211" y2="110"/>
            <line x1="38" y1="38" x2="47" y2="47"/>
            <line x1="182" y1="38" x2="173" y2="47"/>
            <line x1="38" y1="182" x2="47" y2="173"/>
            <line x1="182" y1="182" x2="173" y2="173"/>
          </g>
        </svg>
      </div>
    </div>
    <div class="wak-scene__num" aria-hidden="true">02</div>
  </section>

  <!-- TL 1 — France -->
  <section class="wak-scene wak-scene--tl" id="wak-tl-1" data-scene="3">
    <div class="wak-scene__inner wak-scene__inner--split wak-scene__inner--reverse">
      <div class="wak-tl__text">
        <span class="wak-tl__badge">La nostalgie du continent</span>
        <h2 class="wak-title wak-title--tl" data-splitting>L'arrivée en France</h2>
        <p class="wak-body" data-splitting>
          Installée en France, la fondatrice ressent l'absence d'une certaine élégance culinaire. Impossible de retrouver la cardamome fumée ou le xawaash authentique dans le commerce. Pourtant, derrière cette nostalgie se cachent vingt ans de travail silencieux — sélectionner, doser, affiner chaque mélange jusqu'à la perfection. Naît alors l'idée d'une passerelle d'exception.
        </p>
      </div>
      <div class="wak-tl__visual" aria-hidden="true">
        <svg class="wak-svg-art" viewBox="0 0 260 160" xmlns="http://www.w3.org/2000/svg">
          <!-- Silhouette continent Afrique — esquisse calligraphique -->
          <path d="M60,20 Q70,18 78,24 Q88,22 94,30 Q100,38 98,50 Q104,58 102,70 Q106,80 100,92 Q96,104 88,112 Q80,122 72,118 Q64,114 58,106 Q52,96 50,84 Q46,72 48,60 Q50,46 52,34 Z"
                fill="none" stroke="rgba(232,200,154,0.55)" stroke-width="1.2" stroke-linejoin="round"/>
          <!-- Corne de l'Afrique -->
          <path d="M94,30 Q102,26 110,32 Q116,42 108,50 Q102,54 98,50"
                fill="none" stroke="rgba(232,200,154,0.45)" stroke-width="1" stroke-linejoin="round"/>
          <!-- Ligne de route / mer -->
          <line x1="118" y1="80" x2="142" y2="80" stroke="rgba(232,200,154,0.6)" stroke-width="1" stroke-dasharray="4 3"/>
          <!-- Points de voyage -->
          <circle cx="114" cy="80" r="3" fill="rgba(232,200,154,0.7)"/>
          <circle cx="146" cy="80" r="3" fill="rgba(232,200,154,0.7)"/>
          <!-- Silhouette de France — esquisse épurée -->
          <path d="M150,40 Q162,36 172,42 Q182,48 186,60 Q190,74 184,86 Q178,100 166,108 Q154,114 146,106 Q140,98 140,86 Q138,72 140,60 Q142,50 150,40 Z"
                fill="none" stroke="rgba(232,200,154,0.55)" stroke-width="1.2" stroke-linejoin="round"/>
          <!-- Légendes en script -->
          <text x="44" y="138" font-family="Cormorant Garamond,Georgia,serif" font-style="italic"
                font-size="10" fill="rgba(232,200,154,0.55)" text-anchor="middle">Djibouti</text>
          <text x="166" y="126" font-family="Cormorant Garamond,Georgia,serif" font-style="italic"
                font-size="10" fill="rgba(232,200,154,0.55)" text-anchor="middle">France</text>
          <!-- Étoile de navigation -->
          <path d="M130,76 L132,71 L134,76 L139,76 L135,79 L137,84 L132,81 L127,84 L129,79 L125,76 Z"
                fill="rgba(232,200,154,0.35)" stroke="rgba(232,200,154,0.5)" stroke-width="0.5"/>
        </svg>
      </div>
    </div>
    <div class="wak-scene__num" aria-hidden="true">03</div>
  </section>

  <!-- TL 2 — Naissance -->
  <section class="wak-scene wak-scene--tl" id="wak-tl-2" data-scene="4">
    <div class="wak-scene__inner wak-scene__inner--split">
      <div class="wak-tl__text">
        <span class="wak-tl__badge">La naissance</span>
        <h2 class="wak-title wak-title--tl" data-splitting>WakAroma voit le jour</h2>
        <p class="wak-body" data-splitting>
          Les premières préparations sont élaborées à la main, dans l'écrin de la cuisine familiale. Un mariage subtil entre des épices d'exception directement importées et des trésors dénichés en France — car WakAroma est née de ces deux mondes. Le bouche-à-oreille d'une clientèle exigeante révèle ces trésors uniques.
        </p>
      </div>
      <div class="wak-tl__visual" aria-hidden="true">
        <svg class="wak-svg-art" viewBox="0 0 200 240" xmlns="http://www.w3.org/2000/svg">
          <!-- Mortier -->
          <path d="M55,90 Q55,68 100,68 Q145,68 145,90 L138,170 Q138,188 100,188 Q62,188 62,170 Z"
                fill="rgba(232,200,154,0.07)" stroke="rgba(232,200,154,0.45)" stroke-width="1.2"/>
          <!-- Motif géométrique sur le mortier -->
          <path d="M68,100 L132,100" stroke="rgba(232,200,154,0.25)" stroke-width="0.8"/>
          <path d="M66,115 L134,115" stroke="rgba(232,200,154,0.18)" stroke-width="0.8"/>
          <path d="M72,130 L128,130" stroke="rgba(232,200,154,0.12)" stroke-width="0.8"/>
          <!-- Remplissage épices -->
          <path d="M62,170 Q62,148 100,148 Q138,148 138,170 Q138,188 100,188 Q62,188 62,170 Z"
                fill="rgba(178,125,64,0.22)"/>
          <!-- Pilon -->
          <rect x="93" y="24" width="14" height="70" rx="7"
                fill="rgba(232,200,154,0.12)" stroke="rgba(232,200,154,0.5)" stroke-width="1.2"/>
          <ellipse cx="100" cy="24" rx="10" ry="6"
                   fill="rgba(232,200,154,0.25)" stroke="rgba(232,200,154,0.5)" stroke-width="1"/>
          <!-- Fumée / arôme — lignes ondulées -->
          <path d="M85,18 Q82,10 85,4" fill="none" stroke="rgba(232,200,154,0.3)" stroke-width="1" stroke-linecap="round" class="wak-steam-line"/>
          <path d="M100,14 Q97,6 100,0" fill="none" stroke="rgba(232,200,154,0.4)" stroke-width="1" stroke-linecap="round" class="wak-steam-line"/>
          <path d="M115,18 Q118,10 115,4" fill="none" stroke="rgba(232,200,154,0.3)" stroke-width="1" stroke-linecap="round" class="wak-steam-line"/>
          <!-- Ornement bas -->
          <path d="M75,195 Q100,202 125,195" fill="none" stroke="rgba(232,200,154,0.3)" stroke-width="0.8"/>
        </svg>
      </div>
    </div>
    <div class="wak-scene__num" aria-hidden="true">04</div>
  </section>

  <!-- TL 3 — Expansion -->
  <section class="wak-scene wak-scene--tl" id="wak-tl-3" data-scene="5">
    <div class="wak-scene__inner wak-scene__inner--split wak-scene__inner--reverse">
      <div class="wak-tl__text">
        <span class="wak-tl__badge">L'expansion</span>
        <h2 class="wak-title wak-title--tl" data-splitting>Au-delà des frontières</h2>
        <p class="wak-body" data-splitting>
          WakAroma a vocation à dépasser les cercles communautaires. Des chefs étoilés, des épiceries fines et des amateurs éclairés pourraient un jour s'approprier ces mélanges rares. La boutique en ligne, elle, est prête à accueillir une clientèle internationale.
        </p>
      </div>
      <div class="wak-tl__visual" aria-hidden="true">
        <svg class="wak-svg-art wak-svg-breathe" viewBox="0 0 220 220" xmlns="http://www.w3.org/2000/svg">
          <!-- Rosace géométrique islamique / corne de l'Afrique -->
          <g transform="translate(110,110)">
            <!-- Hexagone central -->
            <polygon points="0,-38 33,-19 33,19 0,38 -33,19 -33,-19"
                     fill="rgba(232,200,154,0.06)" stroke="rgba(232,200,154,0.45)" stroke-width="1"/>
            <!-- 6 losanges rayonnants -->
            <g stroke="rgba(232,200,154,0.35)" stroke-width="0.8" fill="none">
              <path d="M0,-38 L20,-66 L0,-94 L-20,-66 Z"/>
              <path d="M33,-19 L66,-19 L80,-46 L47,-46 Z" transform="rotate(60)"/>
              <path d="M33,19 L66,19 L80,46 L47,46 Z" transform="rotate(120)"/>
              <path d="M0,38 L20,66 L0,94 L-20,66 Z" transform="rotate(180)"/>
              <path d="M-33,19 L-66,19 L-80,46 L-47,46 Z" transform="rotate(240)"/>
              <path d="M-33,-19 L-66,-19 L-80,-46 L-47,-46 Z" transform="rotate(300)"/>
            </g>
            <!-- Cercle extérieur ponctué -->
            <circle r="100" fill="none" stroke="rgba(232,200,154,0.2)" stroke-width="0.8" stroke-dasharray="3 6"/>
            <!-- Points aux sommets -->
            <circle cy="-100" r="3" fill="rgba(232,200,154,0.5)"/>
            <circle cy="-100" r="3" fill="rgba(232,200,154,0.5)" transform="rotate(60)"/>
            <circle cy="-100" r="3" fill="rgba(232,200,154,0.5)" transform="rotate(120)"/>
            <circle cy="-100" r="3" fill="rgba(232,200,154,0.5)" transform="rotate(180)"/>
            <circle cy="-100" r="3" fill="rgba(232,200,154,0.5)" transform="rotate(240)"/>
            <circle cy="-100" r="3" fill="rgba(232,200,154,0.5)" transform="rotate(300)"/>
            <!-- Centre -->
            <circle r="5" fill="rgba(232,200,154,0.75)"/>
          </g>
        </svg>
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
      <div class="wak-fin-ornament" aria-hidden="true">
        <svg viewBox="0 0 280 60" xmlns="http://www.w3.org/2000/svg" class="wak-svg-divider-full">
          <line x1="0" y1="30" x2="90" y2="30" stroke="rgba(232,200,154,0.3)" stroke-width="0.8"/>
          <path d="M95,30 Q110,10 125,30 Q140,50 155,30 Q170,10 185,30"
                fill="none" stroke="rgba(232,200,154,0.6)" stroke-width="1.2" stroke-linecap="round"/>
          <line x1="190" y1="30" x2="280" y2="30" stroke="rgba(232,200,154,0.3)" stroke-width="0.8"/>
          <circle cx="140" cy="30" r="3" fill="rgba(232,200,154,0.6)"/>
        </svg>
      </div>
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
          <div class="wak-valeur-card__icon">
            <svg viewBox="0 0 48 48" class="wak-icon-svg" xmlns="http://www.w3.org/2000/svg">
              <path d="M16,12 Q24,8 32,12 L36,20 Q38,28 36,36 Q32,44 24,44 Q16,44 12,36 Q10,28 12,20 Z"
                    fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
              <path d="M14,18 Q24,14 34,18" fill="none" stroke="currentColor" stroke-width="1.2"/>
              <path d="M12,26 Q24,22 36,26" fill="none" stroke="currentColor" stroke-width="0.8" opacity=".6"/>
              <path d="M20,6 Q24,4 28,6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </div>
          <h3 class="wak-valeur-card__title" data-splitting>Authenticité précieuse</h3>
          <p class="wak-valeur-card__text">Chaque épice est sélectionnée directement à la source, sans intermédiaire, pour préserver toute la noblesse aromatique originelle.</p>
        </div>

        <div class="wak-valeur-card">
          <div class="wak-valeur-card__icon">
            <svg viewBox="0 0 48 48" class="wak-icon-svg" xmlns="http://www.w3.org/2000/svg">
              <!-- Mains jointes / poignée de main stylisée -->
              <path d="M10,28 Q10,20 18,18 L24,16 L30,18 Q38,20 38,28 Q38,38 24,42 Q10,38 10,28 Z"
                    fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
              <path d="M18,18 Q24,12 30,18" fill="none" stroke="currentColor" stroke-width="1.2"/>
              <line x1="24" y1="16" x2="24" y2="10" stroke="currentColor" stroke-width="1" stroke-linecap="round"/>
              <line x1="20" y1="12" x2="20" y2="7"  stroke="currentColor" stroke-width="0.8" stroke-linecap="round" opacity=".6"/>
              <line x1="28" y1="12" x2="28" y2="7"  stroke="currentColor" stroke-width="0.8" stroke-linecap="round" opacity=".6"/>
            </svg>
          </div>
          <h3 class="wak-valeur-card__title" data-splitting>Commerce équitable</h3>
          <p class="wak-valeur-card__text">WakAroma soutient avec respect les productrices et producteurs africains, assurant une juste rémunération et des conditions dignes.</p>
        </div>

        <div class="wak-valeur-card">
          <div class="wak-valeur-card__icon">
            <svg viewBox="0 0 48 48" class="wak-icon-svg" xmlns="http://www.w3.org/2000/svg">
              <!-- Main tenant une graine / artisanat -->
              <path d="M14,32 Q12,26 16,22 Q20,18 24,20 Q28,18 32,22 Q36,26 34,32 Q32,38 24,40 Q16,38 14,32 Z"
                    fill="none" stroke="currentColor" stroke-width="1.5"/>
              <path d="M24,20 L24,10" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
              <path d="M24,10 Q28,6 32,8 Q28,12 24,10" fill="none" stroke="currentColor" stroke-width="1" stroke-linejoin="round"/>
              <path d="M24,10 Q20,6 16,8 Q20,12 24,10" fill="none" stroke="currentColor" stroke-width="1" stroke-linejoin="round"/>
              <line x1="18" y1="30" x2="30" y2="30" stroke="currentColor" stroke-width="0.8" opacity=".5"/>
            </svg>
          </div>
          <h3 class="wak-valeur-card__title" data-splitting>Artisanat d'exception</h3>
          <p class="wak-valeur-card__text">Chaque pot est préparé et conditionné avec un soin méticuleux, perpétuant l'exigence d'une tradition familiale.</p>
        </div>

        <div class="wak-valeur-card">
          <div class="wak-valeur-card__icon">
            <svg viewBox="0 0 48 48" class="wak-icon-svg" xmlns="http://www.w3.org/2000/svg">
              <!-- Deux arches / pont culturel -->
              <path d="M8,36 Q8,20 20,16 Q24,14 28,16 Q40,20 40,36"
                    fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
              <path d="M14,36 Q14,26 24,24 Q34,26 34,36"
                    fill="none" stroke="currentColor" stroke-width="1" opacity=".6" stroke-linecap="round"/>
              <line x1="6" y1="36" x2="42" y2="36" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
              <circle cx="24" cy="16" r="3" fill="none" stroke="currentColor" stroke-width="1.2"/>
            </svg>
          </div>
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
      <div class="wak-ornament wak-ornament--large" aria-hidden="true">
        <svg viewBox="0 0 120 40" class="wak-svg-cta-ornament" xmlns="http://www.w3.org/2000/svg">
          <line x1="0" y1="20" x2="42" y2="20" stroke="rgba(232,200,154,0.4)" stroke-width="0.8"/>
          <path d="M46,20 Q52,8 60,12 Q68,8 74,20 Q68,32 60,28 Q52,32 46,20 Z"
                fill="none" stroke="rgba(232,200,154,0.65)" stroke-width="1.2"/>
          <line x1="78" y1="20" x2="120" y2="20" stroke="rgba(232,200,154,0.4)" stroke-width="0.8"/>
          <circle cx="60" cy="20" r="2.5" fill="rgba(232,200,154,0.65)"/>
        </svg>
      </div>
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
.wak-scene__inner--split {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: clamp(2rem,5vw,6rem);
  align-items: center;
  max-width: 1100px;
}
.wak-scene__inner--reverse { direction: rtl; }
.wak-scene__inner--reverse > * { direction: ltr; }
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
.wak-title--tl   { font-size: clamp(2rem,4vw,3.2rem); font-weight: 600; }
.wak-title--large { font-size: clamp(3rem,7vw,5.5rem); font-weight: 900; }
.wak-title--cta  { font-size: clamp(2.8rem,6vw,5rem); font-weight: 600; }

.wak-body {
  font-family: var(--wak-body-serif);
  font-size: clamp(1rem,1.5vw,1.2rem);
  line-height: 1.9;
  color: var(--wak-white-dim);
  font-weight: 400;
  letter-spacing: 0.01em;
  margin-bottom: 1.4rem;
}
.wak-body--centered { text-align: center; max-width: 700px; margin-left:auto; margin-right:auto; }
.wak-body--wide { max-width: 820px; }

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
.wak-tl__text { display: flex; flex-direction: column; gap: 0.4rem; }
.wak-tl__badge {
  display: inline-flex; align-self: flex-start;
  font-family: var(--wak-sans); font-size: 0.72rem;
  letter-spacing: 0.22em; text-transform: uppercase; font-weight: 700;
  color: var(--wak-gold);
  background: rgba(232,200,154,0.14);
  border: 1px solid rgba(232,200,154,0.3);
  padding: 0.3rem 1rem; border-radius: 20px; margin-bottom: 0.8rem;
}

/* ── ARTIFACTS VISUELS ── */
.wak-tl__visual {
  display: flex; align-items: center; justify-content: center;
  opacity: 0; transform: scale(0.85) translateX(30px);
}
.wak-scene__inner--reverse .wak-tl__visual { transform: scale(0.85) translateX(-30px); }

/* ── SVG ART — motifs africains / arabesque Corne de l'Afrique ── */
.wak-svg-art {
  width: 220px; height: 220px;
  opacity: 0.88;
  filter: drop-shadow(0 0 18px rgba(232,200,154,0.12));
}

/* Rotation lente pour le motif Adinkra */
.wak-svg-spin {
  animation: wakSvgSpin 40s linear infinite;
}
@keyframes wakSvgSpin { to { transform: rotate(360deg); } }

/* Respiration douce pour la rosace */
.wak-svg-breathe {
  animation: wakSvgBreathe 6s ease-in-out infinite;
}
@keyframes wakSvgBreathe {
  0%,100%{ transform: scale(1); opacity: .88; }
  50%    { transform: scale(1.04); opacity: 1; }
}

/* Lignes de vapeur animées sur le mortier */
.wak-steam-line {
  animation: wakSteamRise 3s ease-in-out infinite;
  transform-origin: bottom center;
}
.wak-steam-line:nth-child(1) { animation-delay: 0s; }
.wak-steam-line:nth-child(2) { animation-delay: .6s; }
.wak-steam-line:nth-child(3) { animation-delay: 1.2s; }
@keyframes wakSteamRise {
  0%  { opacity: 0; transform: translateY(0) scaleX(1); }
  40% { opacity: .7; }
  100%{ opacity: 0; transform: translateY(-14px) scaleX(1.5); }
}

/* Ornement fin de section Aujourd'hui */
.wak-fin-ornament {
  margin-top: 3rem; opacity: 0;
}
.wak-svg-divider-full { width: 280px; height: 60px; display: block; margin: 0 auto; }

/* Icônes SVG des valeurs */
.wak-valeur-card__icon {
  width: 52px; height: 52px;
  margin: 0 auto 1.4rem;
  color: var(--wak-gold);
  opacity: 0.85;
}
.wak-icon-svg {
  width: 100%; height: 100%;
  display: block;
}

/* Ornement SVG CTA */
.wak-svg-cta-ornament { width: 120px; height: 40px; display: block; margin: 0 auto; }

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
  .wak-scene__inner--split { grid-template-columns: 1fr; }
  .wak-scene__inner--reverse { direction: ltr; }
  .wak-tl__visual { display: none; }
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
    gsap.to(id + ' .wak-tl__visual', { opacity:1, scale:1, x:0, duration:1.2, ease:'power3.out', scrollTrigger:{ trigger:id, start:'top 58%' }});
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