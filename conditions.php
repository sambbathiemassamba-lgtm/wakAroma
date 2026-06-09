<?php
session_start();
?>
<?php require_once 'header_login.php'; ?>

<style>

/* ═══════════════════════════════════════════
   CGV — WakAroma · Conditions Générales de Vente
   ═══════════════════════════════════════════ */

@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=DM+Sans:wght@300;400;500&display=swap');

:root {
    --gold:        #c8943a;
    --gold-light:  #e8b860;
    --gold-pale:   #f5ead8;
    --dark:        #1a1410;
    --text:        #3a3028;
    --muted:       #7a6f65;
    --cream:       #faf7f2;
    --white:       #ffffff;
    --border:      #ede5d8;
    --radius:      12px;
    --shadow:      0 4px 24px rgba(30,18,8,.08);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* ── HERO ── */
.cgv-hero {
    background: linear-gradient(135deg, #1a1410 0%, #2c1f10 50%, #3d2a14 100%);
    padding: 72px 24px 56px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.cgv-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 60% 80% at 20% 50%, rgba(200,148,58,.12) 0%, transparent 70%),
        radial-gradient(ellipse 40% 60% at 80% 30%, rgba(232,184,96,.08) 0%, transparent 70%);
    pointer-events: none;
}

.cgv-hero__eyebrow {
    font-family: 'DM Sans', sans-serif;
    font-size: .75rem;
    font-weight: 500;
    letter-spacing: .2em;
    text-transform: uppercase;
    color: var(--gold-light);
    margin-bottom: 16px;
}

.cgv-hero__title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(2.2rem, 5vw, 3.8rem);
    font-weight: 600;
    color: var(--white);
    line-height: 1.1;
    margin-bottom: 16px;
}

.cgv-hero__title em {
    font-style: italic;
    color: var(--gold-light);
}

.cgv-hero__sub {
    font-family: 'DM Sans', sans-serif;
    font-size: .95rem;
    color: rgba(255,255,255,.55);
    max-width: 480px;
    margin: 0 auto 32px;
    line-height: 1.7;
}

.cgv-hero__meta {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(200,148,58,.15);
    border: 1px solid rgba(200,148,58,.3);
    border-radius: 999px;
    padding: 8px 20px;
    font-family: 'DM Sans', sans-serif;
    font-size: .8rem;
    color: var(--gold-light);
}

/* ── SOMMAIRE ── */
.cgv-toc {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 28px 32px;
    margin: 40px 0;
    box-shadow: var(--shadow);
}

.cgv-toc__title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.15rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.cgv-toc__title::before {
    content: '';
    display: block;
    width: 4px;
    height: 18px;
    background: var(--gold);
    border-radius: 2px;
    flex-shrink: 0;
}

.cgv-toc__list {
    list-style: none;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 6px 20px;
}

.cgv-toc__list a {
    font-family: 'DM Sans', sans-serif;
    font-size: .875rem;
    color: var(--muted);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 0;
    transition: color .2s;
}

.cgv-toc__list a::before {
    content: '→';
    color: var(--gold);
    font-size: .8rem;
    flex-shrink: 0;
}

.cgv-toc__list a:hover { color: var(--gold); }

/* ── LAYOUT ── */
.cgv-wrapper {
    max-width: 820px;
    margin: 0 auto;
    padding: 0 24px 80px;
}

/* ── ARTICLE (section CGV) ── */
.cgv-section {
    margin-bottom: 40px;
    padding-bottom: 40px;
    border-bottom: 1px solid var(--border);
    scroll-margin-top: 80px;
}

.cgv-section:last-child {
    border-bottom: none;
}

.cgv-section__num {
    font-family: 'DM Sans', sans-serif;
    font-size: .7rem;
    font-weight: 500;
    letter-spacing: .2em;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 8px;
}

.cgv-section__title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.55rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 20px;
    line-height: 1.2;
}

.cgv-section p {
    font-family: 'DM Sans', sans-serif;
    font-size: .925rem;
    color: var(--text);
    line-height: 1.8;
    margin-bottom: 14px;
}

.cgv-section p:last-child { margin-bottom: 0; }

.cgv-section strong {
    color: var(--dark);
    font-weight: 500;
}

/* ── CALLOUT (alerte importante) ── */
.cgv-callout {
    background: linear-gradient(135deg, #fff8ee, var(--gold-pale));
    border-left: 4px solid var(--gold);
    border-radius: 0 var(--radius) var(--radius) 0;
    padding: 18px 22px;
    margin: 20px 0;
    font-family: 'DM Sans', sans-serif;
    font-size: .9rem;
    color: var(--text);
    line-height: 1.7;
}

.cgv-callout--warning {
    background: linear-gradient(135deg, #fff5f5, #ffe8e8);
    border-left-color: #c94040;
}

.cgv-callout strong {
    display: block;
    margin-bottom: 6px;
    font-size: .8rem;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--gold);
}

.cgv-callout--warning strong {
    color: #c94040;
}

/* ── LISTE ── */
.cgv-list {
    list-style: none;
    margin: 14px 0;
}

.cgv-list li {
    font-family: 'DM Sans', sans-serif;
    font-size: .9rem;
    color: var(--text);
    line-height: 1.7;
    padding: 7px 0 7px 20px;
    position: relative;
    border-bottom: 1px solid var(--border);
}

.cgv-list li:last-child { border-bottom: none; }

.cgv-list li::before {
    content: '▸';
    position: absolute;
    left: 0;
    color: var(--gold);
    font-size: .7rem;
    top: 10px;
}

/* ── TABLEAU ── */
.cgv-table-wrap {
    overflow-x: auto;
    margin: 18px 0;
    border-radius: var(--radius);
    border: 1px solid var(--border);
}

.cgv-table {
    width: 100%;
    border-collapse: collapse;
    font-family: 'DM Sans', sans-serif;
    font-size: .875rem;
}

.cgv-table th {
    background: var(--dark);
    color: var(--gold-light);
    font-weight: 500;
    text-align: left;
    padding: 12px 18px;
    font-size: .8rem;
    letter-spacing: .05em;
    text-transform: uppercase;
}

.cgv-table td {
    padding: 12px 18px;
    border-bottom: 1px solid var(--border);
    color: var(--text);
    vertical-align: top;
    line-height: 1.6;
}

.cgv-table tr:last-child td { border-bottom: none; }
.cgv-table tr:nth-child(even) td { background: var(--cream); }

/* ── CONTACT CARD ── */
.cgv-contact {
    background: var(--dark);
    border-radius: var(--radius);
    padding: 32px;
    color: var(--white);
    margin-top: 40px;
    text-align: center;
}

.cgv-contact__title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.4rem;
    font-weight: 600;
    color: var(--gold-light);
    margin-bottom: 12px;
}

.cgv-contact p {
    font-family: 'DM Sans', sans-serif;
    font-size: .9rem;
    color: rgba(255,255,255,.65);
    margin-bottom: 20px;
    line-height: 1.7;
}

.cgv-contact a {
    color: var(--gold-light);
    text-decoration: none;
    border-bottom: 1px solid rgba(200,148,58,.4);
    transition: border-color .2s;
}

.cgv-contact a:hover { border-color: var(--gold-light); }

/* ── BACK TO TOP ── */
.cgv-backtop {
    display: flex;
    justify-content: center;
    margin-top: 48px;
}

.cgv-backtop a {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: 'DM Sans', sans-serif;
    font-size: .85rem;
    color: var(--muted);
    text-decoration: none;
    border: 1px solid var(--border);
    border-radius: 999px;
    padding: 10px 22px;
    transition: all .2s;
}

.cgv-backtop a:hover {
    color: var(--gold);
    border-color: var(--gold);
    background: var(--gold-pale);
}

/* ── RESPONSIVE ── */
@media (max-width: 600px) {
    .cgv-toc__list { grid-template-columns: 1fr; }
    .cgv-contact { padding: 24px 20px; }
    .cgv-table th, .cgv-table td { padding: 10px 12px; }
}

</style>

<!-- ══ HERO ══ -->
<div class="cgv-hero" id="top">
    <p class="cgv-hero__eyebrow">Documents légaux · WakAroma</p>
    <h1 class="cgv-hero__title">Conditions Générales<br><em>de Vente</em></h1>
    <p class="cgv-hero__sub">Veuillez lire attentivement ces conditions avant toute commande sur notre boutique.</p>
    <span class="cgv-hero__meta">
        📅 Dernière mise à jour : <?= date('d/m/Y') ?> &nbsp;·&nbsp; Version 1.0
    </span>
</div>

<!-- ══ CONTENU ══ -->
<div class="cgv-wrapper">

    <!-- SOMMAIRE -->
    <nav class="cgv-toc" aria-label="Sommaire">
        <p class="cgv-toc__title">Sommaire</p>
        <ol class="cgv-toc__list">
            <li><a href="#art1">1. Identification du vendeur</a></li>
            <li><a href="#art2">2. Champ d'application</a></li>
            <li><a href="#art3">3. Produits & disponibilité</a></li>
            <li><a href="#art4">4. Prix</a></li>
            <li><a href="#art5">5. Commande</a></li>
            <li><a href="#art6">6. Paiement</a></li>
            <li><a href="#art7">7. Livraison</a></li>
            <li><a href="#art8">8. Absence de droit de rétractation</a></li>
            <li><a href="#art9">9. Garanties légales</a></li>
            <li><a href="#art10">10. Responsabilité</a></li>
            <li><a href="#art11">11. Données personnelles (RGPD)</a></li>
            <li><a href="#art12">12. Cookies</a></li>
            <li><a href="#art13">13. Propriété intellectuelle</a></li>
            <li><a href="#art14">14. Médiation & litiges</a></li>
            <li><a href="#art15">15. Droit applicable</a></li>
        </ol>
    </nav>

    <!-- ART 1 -->
    <section class="cgv-section" id="art1">
        <p class="cgv-section__num">Article 1</p>
        <h2 class="cgv-section__title">Identification du vendeur</h2>
        <p>Le site <strong>WakAroma</strong> (ci-après « le Vendeur ») est exploité par :</p>
        <ul class="cgv-list">
            <li><strong>Raison sociale :</strong> WakAroma</li>
            <li><strong>Forme juridique :</strong> [Forme juridique — ex. SAS, SASU, auto-entrepreneur…]</li>
            <li><strong>Siège social :</strong> [Adresse complète]</li>
            <li><strong>SIRET :</strong> [Numéro SIRET]</li>
            <li><strong>TVA intracommunautaire :</strong> [Numéro TVA — si applicable]</li>
            <li><strong>Email :</strong> <a href="mailto:contact@wakaroma.fr">contact@wakaroma.fr</a></li>
            <li><strong>Téléphone :</strong> [Numéro de téléphone]</li>
            <li><strong>Hébergeur :</strong> [Nom et adresse de l'hébergeur du site]</li>
        </ul>
        <p>Le directeur de publication est [Nom du responsable]. Toute communication écrite adressée au Vendeur peut être effectuée par email ou courrier postal aux coordonnées ci-dessus.</p>
    </section>

    <!-- ART 2 -->
    <section class="cgv-section" id="art2">
        <p class="cgv-section__num">Article 2</p>
        <h2 class="cgv-section__title">Champ d'application</h2>
        <p>Les présentes Conditions Générales de Vente (ci-après « CGV ») régissent exclusivement les relations contractuelles entre le Vendeur et toute personne physique non professionnelle (ci-après « le Client ») effectuant un achat sur le site <strong>wakaroma.fr</strong>.</p>
        <p>Toute commande passée sur le site implique l'acceptation pleine, entière et sans réserve des présentes CGV par le Client. Ces CGV prévalent sur tout autre document du Client, notamment ses éventuelles conditions générales d'achat.</p>
        <p>Le Vendeur se réserve le droit de modifier les présentes CGV à tout moment. Les CGV applicables sont celles en vigueur à la date de la commande.</p>
        <p>Ces CGV s'appliquent aux ventes de produits alimentaires (épices, aromates, huiles, mélanges) proposés à la vente sur le site, destinées aux clients résidant principalement en France métropolitaine. Pour toute livraison hors France métropolitaine, le Client est invité à contacter le Vendeur préalablement à toute commande.</p>
    </section>

    <!-- ART 3 -->
    <section class="cgv-section" id="art3">
        <p class="cgv-section__num">Article 3</p>
        <h2 class="cgv-section__title">Produits & disponibilité</h2>
        <p>Les produits proposés sont des denrées alimentaires : épices, aromates, huiles et mélanges d'origine africaine. Chaque fiche produit présente une description, les caractéristiques essentielles, le prix TTC et la disponibilité en stock.</p>
        <p>Le Vendeur s'efforce de maintenir les informations produits à jour. En cas d'erreur manifeste ou d'indisponibilité constatée après la commande, le Client en sera informé dans les meilleurs délais et pourra opter pour un remboursement intégral.</p>
        <div class="cgv-callout">
            <strong>⚠ Allergènes</strong>
            Les produits WakAroma sont des denrées alimentaires. Les informations relatives aux allergènes figurent sur les fiches produits et les emballages. Il appartient au Client de vérifier la composition des produits avant tout achat, notamment en cas d'allergie ou d'intolérance alimentaire connue.
        </div>
        <p>Les photographies et visuels des produits sont donnés à titre indicatif et ne sont pas contractuels. De légères variations de couleur ou de présentation peuvent exister.</p>
        <p>Les produits alimentaires sont vendus dans le respect des normes sanitaires en vigueur (règlements CE n° 178/2002, 852/2004 et 853/2004, et réglementation française applicable). Les dates de péremption (DLUO ou DLC) sont indiquées sur les emballages.</p>
    </section>

    <!-- ART 4 -->
    <section class="cgv-section" id="art4">
        <p class="cgv-section__num">Article 4</p>
        <h2 class="cgv-section__title">Prix</h2>
        <p>Les prix affichés sur le site sont indiqués en <strong>euros (€) toutes taxes comprises (TTC)</strong>, conformément à l'article L. 112-1 du Code de la consommation.</p>
        <p>Les frais de livraison ne sont pas inclus dans le prix des produits et sont calculés lors de la validation de la commande, en fonction du poids total et de l'adresse de livraison. Ils sont indiqués clairement avant la confirmation définitive de la commande.</p>
        <p>Le Vendeur se réserve le droit de modifier ses prix à tout moment. Toutefois, les produits sont facturés sur la base des tarifs en vigueur au moment de la validation de la commande.</p>
        <p>En cas de promotion, les prix promotionnels sont applicables uniquement pendant la durée indiquée sur le site.</p>
    </section>

    <!-- ART 5 -->
    <section class="cgv-section" id="art5">
        <p class="cgv-section__num">Article 5</p>
        <h2 class="cgv-section__title">Commande</h2>
        <p>Pour passer commande, le Client doit :</p>
        <ul class="cgv-list">
            <li>Créer un compte ou se connecter à son espace personnel sur wakaroma.fr ;</li>
            <li>Sélectionner les produits souhaités et les ajouter au panier ;</li>
            <li>Vérifier le contenu du panier et les informations de livraison ;</li>
            <li>Valider la commande et procéder au paiement sécurisé ;</li>
            <li>Recevoir un email de confirmation de commande.</li>
        </ul>
        <p>La commande est ferme et définitive à compter de la <strong>confirmation du paiement</strong>. Le Vendeur adresse alors un email de confirmation récapitulant les produits commandés, le montant total, et les modalités de livraison.</p>
        <p>Le Vendeur se réserve le droit d'annuler ou de refuser toute commande en cas de litige antérieur avec le Client, de suspicion de fraude, d'erreur manifeste sur le prix, ou d'indisponibilité du produit. Dans ces cas, le Client sera intégralement remboursé.</p>
        <p>Conformément à l'article 1127-1 du Code civil, le Client peut corriger toute erreur de saisie avant la validation définitive de sa commande.</p>
    </section>

    <!-- ART 6 -->
    <section class="cgv-section" id="art6">
        <p class="cgv-section__num">Article 6</p>
        <h2 class="cgv-section__title">Paiement</h2>
        <p>Le paiement s'effectue en ligne, au moment de la commande, par les moyens sécurisés proposés sur le site (carte bancaire, etc.). Le paiement est exigible immédiatement à la commande.</p>
        <p>Le site utilise un protocole de paiement sécurisé. Les données bancaires du Client sont chiffrées et ne sont jamais stockées par le Vendeur.</p>
        <p>En cas de refus de paiement par la banque du Client, la commande sera automatiquement annulée. Le Client est seul responsable des frais bancaires éventuels liés au paiement.</p>
        <p>Le Vendeur se réserve le droit de suspendre tout traitement de commande en cas de tentative de fraude avérée ou de paiement litigieux.</p>
    </section>

    <!-- ART 7 -->
    <section class="cgv-section" id="art7">
        <p class="cgv-section__num">Article 7</p>
        <h2 class="cgv-section__title">Livraison</h2>
        <p>Les commandes sont expédiées en France métropolitaine, dans les délais indiqués sur le site. Le délai de livraison commence à courir à compter de la confirmation du paiement.</p>
        <div class="cgv-table-wrap">
            <table class="cgv-table">
                <thead>
                    <tr>
                        <th>Mode de livraison</th>
                        <th>Délai estimé</th>
                        <th>Frais</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Colissimo suivi</td>
                        <td>2 à 4 jours ouvrés</td>
                        <td>Calculés au panier</td>
                    </tr>
                    <tr>
                        <td>Livraison express</td>
                        <td>24 à 48 h ouvrées</td>
                        <td>Calculés au panier</td>
                    </tr>
                    <tr>
                        <td>Point relais</td>
                        <td>3 à 5 jours ouvrés</td>
                        <td>Calculés au panier</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p>Le Vendeur décline toute responsabilité en cas de retard imputable au transporteur ou à des événements de force majeure (grève, intempéries, etc.).</p>
        <p>À réception du colis, le Client doit vérifier l'état des produits. En cas de colis endommagé ou manquant, le Client doit formuler des réserves auprès du transporteur et contacter le Vendeur dans les <strong>48 heures</strong> suivant la livraison.</p>
        <p>Si le colis est retourné au Vendeur en raison d'une adresse erronée fournie par le Client ou d'un défaut de retrait, des frais de réexpédition pourront être facturés.</p>
    </section>

    <!-- ART 8 — POINT CENTRAL : PAS DE RETOUR -->
    <section class="cgv-section" id="art8">
        <p class="cgv-section__num">Article 8</p>
        <h2 class="cgv-section__title">Absence de droit de rétractation — Produits alimentaires</h2>

        <div class="cgv-callout cgv-callout--warning">
            <strong>🚫 Aucun retour, échange ou remboursement</strong>
            Conformément à l'article L. 221-28 du Code de la consommation, les produits WakAroma étant des denrées périssables et/ou alimentaires descellables, le droit de rétractation ne s'applique pas. Toute commande validée et payée est définitive.
        </div>

        <p>En vertu de l'<strong>article L. 221-28, 3° et 4° du Code de la consommation</strong>, le droit de rétractation de 14 jours prévu par l'article L. 221-18 du même Code <strong>ne s'applique pas</strong> aux contrats portant sur :</p>
        <ul class="cgv-list">
            <li>La fourniture de biens susceptibles de se détériorer ou de se périmer rapidement ;</li>
            <li>La fourniture de biens qui ont été descellés par le consommateur après la livraison et qui ne peuvent être renvoyés pour des raisons d'hygiène ou de protection de la santé.</li>
        </ul>
        <p>Les épices, aromates, huiles et mélanges commercialisés par WakAroma sont des denrées alimentaires qui entrent expressément dans ces deux catégories. En conséquence :</p>
        <ul class="cgv-list">
            <li><strong>Aucun retour</strong> de produit ne sera accepté après livraison ;</li>
            <li><strong>Aucun échange</strong> ne peut être effectué ;</li>
            <li><strong>Aucun remboursement</strong> ne sera accordé au titre du droit de rétractation.</li>
        </ul>
        <p>Le Client est invité à lire attentivement les fiches produits (description, composition, allergènes, grammage, DLUO) avant de passer commande, et à contacter le service client pour toute question : <a href="mailto:contact@wakaroma.fr">contact@wakaroma.fr</a>.</p>

        <div class="cgv-callout">
            <strong>Exception : produit défectueux ou erreur d'expédition</strong>
            La présente clause ne prive pas le Client de ses droits au titre des garanties légales (voir article 9). En cas de produit manifestement défectueux, avarié, ou d'erreur d'expédition (mauvais produit livré), le Client doit contacter le Vendeur dans les <strong>48 heures suivant la réception</strong> avec photographies à l'appui. Une solution adaptée (remboursement ou réexpédition) sera proposée au cas par cas.
        </div>
    </section>

    <!-- ART 9 -->
    <section class="cgv-section" id="art9">
        <p class="cgv-section__num">Article 9</p>
        <h2 class="cgv-section__title">Garanties légales</h2>
        <p>Indépendamment de toute stipulation contractuelle, le Vendeur est tenu aux garanties légales suivantes, conformément aux articles L. 217-1 et suivants du Code de la consommation et aux articles 1641 et suivants du Code civil :</p>
        <ul class="cgv-list">
            <li><strong>Garantie légale de conformité</strong> (art. L. 217-4 à L. 217-16 C. conso.) : le Vendeur livre un bien conforme au contrat. En cas de défaut de conformité, le Client dispose de 2 ans à compter de la délivrance pour agir ;</li>
            <li><strong>Garantie légale contre les vices cachés</strong> (art. 1641 C. civ.) : le Vendeur répond des vices cachés de la chose vendue. Le Client dispose de 2 ans à compter de la découverte du vice pour agir.</li>
        </ul>
        <p>Pour mettre en œuvre une garantie légale, le Client adresse sa demande par email à <a href="mailto:contact@wakaroma.fr">contact@wakaroma.fr</a>, en joignant les preuves d'achat et photographies des produits concernés.</p>
    </section>

    <!-- ART 10 -->
    <section class="cgv-section" id="art10">
        <p class="cgv-section__num">Article 10</p>
        <h2 class="cgv-section__title">Responsabilité</h2>
        <p>Le Vendeur ne saurait être tenu responsable des dommages résultant d'une utilisation non conforme des produits, d'une conservation inadaptée par le Client, ou d'une allergie alimentaire que le Client n'aurait pas signalée avant l'achat.</p>
        <p>Le Vendeur ne peut être tenu responsable en cas de force majeure telle que définie par la jurisprudence française (catastrophe naturelle, grève générale, pandémie, etc.) rendant impossible l'exécution de ses obligations.</p>
        <p>La responsabilité totale du Vendeur envers le Client ne peut excéder le montant de la commande concernée.</p>
    </section>

    <!-- ART 11 -->
    <section class="cgv-section" id="art11">
        <p class="cgv-section__num">Article 11</p>
        <h2 class="cgv-section__title">Données personnelles (RGPD)</h2>
        <p>Le Vendeur collecte et traite les données personnelles du Client dans le respect du <strong>Règlement (UE) 2016/679</strong> (RGPD) et de la loi n° 78-17 du 6 janvier 1978 modifiée (Loi Informatique et Libertés).</p>
        <p><strong>Responsable du traitement :</strong> WakAroma — [adresse] — <a href="mailto:contact@wakaroma.fr">contact@wakaroma.fr</a></p>
        <p><strong>Données collectées :</strong> nom, prénom, adresse email, numéro de téléphone, adresse postale, données de connexion et d'achat.</p>
        <p><strong>Finalités :</strong> gestion des commandes et du compte client, envoi d'informations commerciales (avec consentement), amélioration du service, obligations légales et comptables.</p>
        <p><strong>Base légale :</strong> exécution du contrat (commande), obligation légale (facturation), consentement (newsletter), intérêt légitime (sécurité du site).</p>
        <p><strong>Durée de conservation :</strong> données de compte : 3 ans après la dernière activité ; données de facturation : 10 ans (obligation comptable).</p>
        <p><strong>Destinataires :</strong> les données peuvent être transmises aux prestataires nécessaires à l'exécution de la commande (transporteurs, processeur de paiement), dans le strict respect du RGPD. Elles ne sont jamais vendues à des tiers.</p>
        <p>Conformément aux articles 15 à 22 du RGPD, le Client dispose des droits suivants :</p>
        <ul class="cgv-list">
            <li>Droit d'accès à ses données ;</li>
            <li>Droit de rectification ;</li>
            <li>Droit à l'effacement (« droit à l'oubli »), sous réserve des obligations légales ;</li>
            <li>Droit à la limitation du traitement ;</li>
            <li>Droit à la portabilité des données ;</li>
            <li>Droit d'opposition au traitement ;</li>
            <li>Droit de retirer son consentement à tout moment (pour les traitements fondés sur le consentement) ;</li>
            <li>Droit d'introduire une réclamation auprès de la <strong>CNIL</strong> (www.cnil.fr).</li>
        </ul>
        <p>Pour exercer ces droits, le Client adresse sa demande par email à <a href="mailto:contact@wakaroma.fr">contact@wakaroma.fr</a> en joignant une copie d'une pièce d'identité. Le Vendeur s'engage à répondre dans un délai d'un mois.</p>
    </section>

    <!-- ART 12 -->
    <section class="cgv-section" id="art12">
        <p class="cgv-section__num">Article 12</p>
        <h2 class="cgv-section__title">Cookies</h2>
        <p>Le site wakaroma.fr utilise des cookies nécessaires au bon fonctionnement technique du site (session, panier, authentification). Ces cookies ne nécessitent pas le consentement du Client, conformément à la recommandation de la CNIL.</p>
        <p>Des cookies de mesure d'audience ou de personnalisation peuvent être déposés avec le consentement exprès du Client. Celui-ci peut à tout moment gérer ses préférences via le panneau de gestion des cookies disponible sur le site, ou depuis les paramètres de son navigateur.</p>
        <p>Pour plus d'informations, consultez notre <a href="confidentialite.php">Politique de confidentialité</a>.</p>
    </section>

    <!-- ART 13 -->
    <section class="cgv-section" id="art13">
        <p class="cgv-section__num">Article 13</p>
        <h2 class="cgv-section__title">Propriété intellectuelle</h2>
        <p>L'ensemble des éléments constituant le site wakaroma.fr (textes, photographies, logos, visuels, structure, code source) est la propriété exclusive de WakAroma ou de ses partenaires, et est protégé par le droit d'auteur et le droit des marques.</p>
        <p>Toute reproduction, représentation, modification, publication, adaptation ou exploitation, même partielle, de ces éléments, sans autorisation écrite préalable du Vendeur, est strictement interdite et constituerait une contrefaçon sanctionnée par les articles L. 335-2 et suivants du Code de la propriété intellectuelle.</p>
    </section>

    <!-- ART 14 -->
    <section class="cgv-section" id="art14">
        <p class="cgv-section__num">Article 14</p>
        <h2 class="cgv-section__title">Médiation & règlement des litiges</h2>
        <p>En cas de litige lié à une commande, le Client est invité à contacter en premier lieu le service client WakAroma à l'adresse <a href="mailto:contact@wakaroma.fr">contact@wakaroma.fr</a> afin de rechercher une solution amiable.</p>
        <p>En l'absence de réponse satisfaisante dans un délai de 60 jours, le Client consommateur peut recourir gratuitement à un médiateur de la consommation conformément aux articles L. 611-1 et suivants du Code de la consommation.</p>
        <p>Le Client peut également utiliser la plateforme de règlement en ligne des litiges (RLL) mise à disposition par la Commission européenne :</p>
        <p><a href="https://ec.europa.eu/consumers/odr" target="_blank" rel="noopener">https://ec.europa.eu/consumers/odr</a></p>
        <p><strong>Médiateur désigné :</strong> [Nom et coordonnées du médiateur référencé par WakAroma — à compléter]</p>
    </section>

    <!-- ART 15 -->
    <section class="cgv-section" id="art15">
        <p class="cgv-section__num">Article 15</p>
        <h2 class="cgv-section__title">Droit applicable & juridiction compétente</h2>
        <p>Les présentes CGV sont soumises au droit français.</p>
        <p>En cas de litige persistant après tentative de médiation, les tribunaux français seront seuls compétents. Le Client consommateur peut saisir la juridiction de son choix conformément à l'article R. 631-3 du Code de la consommation.</p>
        <p>Si une ou plusieurs stipulations des présentes CGV sont tenues pour non valides ou déclarées nulles, les autres stipulations garderont toute leur force et leur portée.</p>
    </section>

    <!-- CONTACT -->
    <div class="cgv-contact">
        <p class="cgv-contact__title">Une question sur votre commande ?</p>
        <p>Notre équipe répond dans les meilleurs délais du lundi au vendredi.<br>
        Précisez votre numéro de commande dans votre message.</p>
        <a href="mailto:contact@wakaroma.fr">contact@wakaroma.fr</a>
    </div>

    <!-- BACK TO TOP -->
    <div class="cgv-backtop">
        <a href="#top">↑ Retour en haut de page</a>
    </div>

</div>

<!-- FOOTER -->
<?php require_once "footer.php"; ?>
