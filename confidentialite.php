<?php
session_start();
?>
<?php require_once 'header_login.php'; ?>

<style>

/* ══════════════════════════════════════════
   POLITIQUE DE CONFIDENTIALITÉ — WakAroma
   ══════════════════════════════════════════ */

/* ── Variables DA ── */
:root {
    --gold:        #c8943a;
    --gold-light:  #e8b860;
    --gold-pale:   #faf7f2;
    --gold-border: #ecdfc8;
    --dark:        #1a1410;
    --text:        #3a3028;
    --muted:       #7a6e62;
    --white:       #ffffff;
}

/* ── Layout ── */
.rgpd-page {
    max-width: 860px;
    margin: 0 auto;
    padding: 60px 24px 80px;
    color: var(--text);
    font-family: inherit;
    line-height: 1.75;
}

/* ── En-tête de page ── */
.rgpd-hero {
    text-align: center;
    margin-bottom: 56px;
    padding-bottom: 40px;
    border-bottom: 2px solid var(--gold-border);
    position: relative;
}

.rgpd-hero::after {
    content: '';
    display: block;
    width: 60px;
    height: 3px;
    background: linear-gradient(90deg, var(--gold), var(--gold-light));
    border-radius: 99px;
    margin: 24px auto 0;
}

.rgpd-hero__eyebrow {
    display: inline-block;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--gold);
    background: var(--gold-pale);
    border: 1px solid var(--gold-border);
    padding: 5px 14px;
    border-radius: 99px;
    margin-bottom: 18px;
}

.rgpd-hero__title {
    font-size: clamp(1.8rem, 4vw, 2.6rem);
    font-weight: 800;
    color: var(--dark);
    margin: 0 0 12px;
    letter-spacing: -0.02em;
}

.rgpd-hero__date {
    font-size: 0.88rem;
    color: var(--muted);
    font-style: italic;
}

/* ── Table des matières ── */
.rgpd-toc {
    background: var(--gold-pale);
    border: 1px solid var(--gold-border);
    border-radius: 14px;
    padding: 28px 32px;
    margin-bottom: 52px;
}

.rgpd-toc__title {
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--gold);
    margin: 0 0 16px;
}

.rgpd-toc__list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 8px 24px;
}

.rgpd-toc__list li a {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    color: var(--text);
    text-decoration: none;
    padding: 4px 0;
    transition: color 0.2s;
}

.rgpd-toc__list li a:hover {
    color: var(--gold);
}

.rgpd-toc__list li a::before {
    content: '';
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--gold);
    flex-shrink: 0;
}

/* ── Sections ── */
.rgpd-section {
    margin-bottom: 52px;
    scroll-margin-top: 80px;
}

.rgpd-section__header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 20px;
}

.rgpd-section__num {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    color: var(--white);
    font-size: 0.82rem;
    font-weight: 800;
    flex-shrink: 0;
}

.rgpd-section__title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--dark);
    margin: 0;
}

.rgpd-section p,
.rgpd-section li {
    font-size: 0.95rem;
    color: var(--text);
    margin-bottom: 10px;
}

.rgpd-section ul,
.rgpd-section ol {
    padding-left: 20px;
    margin-bottom: 14px;
}

.rgpd-section ul li {
    list-style: none;
    padding-left: 18px;
    position: relative;
    margin-bottom: 8px;
}

.rgpd-section ul li::before {
    content: '›';
    position: absolute;
    left: 0;
    color: var(--gold);
    font-weight: 700;
}

/* ── Encadrés info ── */
.rgpd-box {
    background: var(--gold-pale);
    border-left: 4px solid var(--gold);
    border-radius: 0 12px 12px 0;
    padding: 18px 22px;
    margin: 18px 0;
    font-size: 0.9rem;
}

.rgpd-box strong {
    color: var(--gold);
    display: block;
    margin-bottom: 6px;
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

/* ── Tableau des durées de conservation ── */
.rgpd-table-wrap {
    overflow-x: auto;
    margin: 18px 0;
    border-radius: 12px;
    border: 1px solid var(--gold-border);
}

.rgpd-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
}

.rgpd-table thead {
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    color: var(--white);
}

.rgpd-table thead th {
    padding: 12px 16px;
    text-align: left;
    font-weight: 700;
    font-size: 0.8rem;
    letter-spacing: 0.05em;
}

.rgpd-table tbody tr {
    border-bottom: 1px solid var(--gold-border);
}

.rgpd-table tbody tr:last-child {
    border-bottom: none;
}

.rgpd-table tbody tr:nth-child(even) {
    background: var(--gold-pale);
}

.rgpd-table td {
    padding: 11px 16px;
    color: var(--text);
    vertical-align: top;
}

.rgpd-table td:first-child {
    font-weight: 600;
    color: var(--dark);
}

/* ── Droits ── */
.rgpd-rights-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 14px;
    margin: 18px 0;
}

.rgpd-right-card {
    background: var(--white);
    border: 1px solid var(--gold-border);
    border-radius: 12px;
    padding: 18px 16px;
    transition: box-shadow 0.2s, border-color 0.2s;
}

.rgpd-right-card:hover {
    border-color: var(--gold);
    box-shadow: 0 4px 20px rgba(200, 148, 58, 0.12);
}

.rgpd-right-card__icon {
    font-size: 1.5rem;
    margin-bottom: 10px;
    display: block;
}

.rgpd-right-card__name {
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 6px;
}

.rgpd-right-card__desc {
    font-size: 0.82rem;
    color: var(--muted);
    line-height: 1.55;
    margin: 0;
}

/* ── Contact bloc ── */
.rgpd-contact {
    background: linear-gradient(135deg, #2a1f12, #3d2a0e);
    border-radius: 16px;
    padding: 36px 40px;
    text-align: center;
    margin-top: 56px;
}

.rgpd-contact__title {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--white);
    margin: 0 0 10px;
}

.rgpd-contact__sub {
    font-size: 0.9rem;
    color: rgba(255,255,255,0.65);
    margin: 0 0 22px;
}

.rgpd-contact__btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    color: var(--white);
    font-weight: 700;
    font-size: 0.9rem;
    padding: 12px 28px;
    border-radius: 99px;
    text-decoration: none;
    transition: opacity 0.2s, transform 0.2s;
}

.rgpd-contact__btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

/* ── Séparateur ── */
.rgpd-divider {
    border: none;
    border-top: 1px solid var(--gold-border);
    margin: 40px 0;
}

/* ── Responsive ── */
@media (max-width: 600px) {
    .rgpd-page { padding: 36px 16px 60px; }
    .rgpd-toc { padding: 20px 18px; }
    .rgpd-contact { padding: 28px 20px; }
    .rgpd-rights-grid { grid-template-columns: 1fr 1fr; }
}

</style>

<div class="rgpd-page">

    <!-- ── En-tête ── -->
    <div class="rgpd-hero">
        <div class="rgpd-hero__eyebrow">Transparence &amp; Confiance</div>
        <h1 class="rgpd-hero__title">Politique de Confidentialité</h1>
        <p class="rgpd-hero__date">Dernière mise à jour : <?= date('d/m/Y') ?> — Conforme au RGPD (Règlement UE 2016/679)</p>
    </div>

    <!-- ── Table des matières ── -->
    <nav class="rgpd-toc" aria-label="Table des matières">
        <p class="rgpd-toc__title">Sommaire</p>
        <ul class="rgpd-toc__list">
            <li><a href="#s1">Responsable du traitement</a></li>
            <li><a href="#s2">Données collectées</a></li>
            <li><a href="#s3">Finalités &amp; bases légales</a></li>
            <li><a href="#s4">Durées de conservation</a></li>
            <li><a href="#s5">Destinataires des données</a></li>
            <li><a href="#s6">Transferts hors UE</a></li>
            <li><a href="#s7">Vos droits</a></li>
            <li><a href="#s8">Cookies</a></li>
            <li><a href="#s9">Sécurité</a></li>
            <li><a href="#s10">Mineurs</a></li>
            <li><a href="#s11">Modifications</a></li>
            <li><a href="#s12">Contact &amp; réclamations</a></li>
        </ul>
    </nav>


    <!-- ══════════════════════════
         1. RESPONSABLE DU TRAITEMENT
    ══════════════════════════ -->
    <section class="rgpd-section" id="s1">
        <div class="rgpd-section__header">
            <span class="rgpd-section__num">1</span>
            <h2 class="rgpd-section__title">Responsable du traitement</h2>
        </div>

        <p>Le responsable du traitement de vos données personnelles est :</p>

        <div class="rgpd-box">
            <strong>Identité</strong>
            <p style="margin:0"><strong>WakAroma</strong><br>
            [Forme juridique — ex. SAS, SARL, auto-entrepreneur]<br>
            [Adresse du siège social]<br>
            [Code postal] [Ville], France<br>
            SIRET : [Numéro SIRET]<br>
            E-mail : <a href="mailto:contact@wakaroma.fr" style="color:var(--gold)">contact@wakaroma.fr</a>
            </p>
        </div>

        <p>Pour toute question relative à la protection de vos données, vous pouvez nous contacter à l'adresse indiquée ci-dessus ou via le formulaire de contact disponible sur le site.</p>
    </section>

    <hr class="rgpd-divider">


    <!-- ══════════════════════════
         2. DONNÉES COLLECTÉES
    ══════════════════════════ -->
    <section class="rgpd-section" id="s2">
        <div class="rgpd-section__header">
            <span class="rgpd-section__num">2</span>
            <h2 class="rgpd-section__title">Données personnelles collectées</h2>
        </div>

        <p>Nous collectons uniquement les données strictement nécessaires à la fourniture de nos services. Selon votre interaction avec WakAroma, les catégories suivantes peuvent être concernées :</p>

        <p><strong>Lors de la création d'un compte :</strong></p>
        <ul>
            <li>Nom et prénom</li>
            <li>Adresse e-mail</li>
            <li>Numéro de téléphone (indicatif + numéro)</li>
            <li>Mot de passe (stocké sous forme hachée avec bcrypt — jamais en clair)</li>
        </ul>

        <p><strong>Lors d'une commande :</strong></p>
        <ul>
            <li>Adresse de livraison et de facturation</li>
            <li>Informations de paiement (traitées par notre prestataire de paiement sécurisé — non conservées par WakAroma)</li>
            <li>Historique des commandes</li>
        </ul>

        <p><strong>Lors de la navigation :</strong></p>
        <ul>
            <li>Adresse IP (anonymisée après 13 mois)</li>
            <li>Données de navigation et de session (cookies — voir section 8)</li>
            <li>Avis et notes déposés sur les produits</li>
        </ul>

        <p><strong>Via la newsletter (si souscription) :</strong></p>
        <ul>
            <li>Adresse e-mail</li>
            <li>Date d'inscription et préférences de communication</li>
        </ul>

        <div class="rgpd-box">
            <strong>Principe de minimisation</strong>
            Conformément à l'article 5(1)(c) du RGPD, nous ne collectons que les données adéquates, pertinentes et limitées à ce qui est nécessaire au regard des finalités pour lesquelles elles sont traitées.
        </div>
    </section>

    <hr class="rgpd-divider">


    <!-- ══════════════════════════
         3. FINALITÉS & BASES LÉGALES
    ══════════════════════════ -->
    <section class="rgpd-section" id="s3">
        <div class="rgpd-section__header">
            <span class="rgpd-section__num">3</span>
            <h2 class="rgpd-section__title">Finalités du traitement et bases légales</h2>
        </div>

        <p>Chaque traitement de vos données repose sur une base légale définie par le RGPD (art. 6) :</p>

        <div class="rgpd-table-wrap">
            <table class="rgpd-table">
                <thead>
                    <tr>
                        <th>Finalité</th>
                        <th>Base légale (art. 6 RGPD)</th>
                        <th>Détail</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Création &amp; gestion du compte client</td>
                        <td>Exécution du contrat (6.1.b)</td>
                        <td>Nécessaire à l'ouverture et la gestion de votre espace personnel</td>
                    </tr>
                    <tr>
                        <td>Traitement et suivi des commandes</td>
                        <td>Exécution du contrat (6.1.b)</td>
                        <td>Préparation, expédition, livraison et facturation</td>
                    </tr>
                    <tr>
                        <td>Service après-vente &amp; support</td>
                        <td>Exécution du contrat (6.1.b)</td>
                        <td>Traitement des demandes, retours et remboursements</td>
                    </tr>
                    <tr>
                        <td>Obligations légales &amp; comptables</td>
                        <td>Obligation légale (6.1.c)</td>
                        <td>Conservation des pièces comptables (10 ans, art. L.123-22 C.com.)</td>
                    </tr>
                    <tr>
                        <td>Lutte contre la fraude</td>
                        <td>Intérêt légitime (6.1.f)</td>
                        <td>Détection et prévention des activités frauduleuses</td>
                    </tr>
                    <tr>
                        <td>Newsletter &amp; communications marketing</td>
                        <td>Consentement (6.1.a)</td>
                        <td>Uniquement si vous y avez explicitement consenti — révocable à tout moment</td>
                    </tr>
                    <tr>
                        <td>Amélioration du site &amp; statistiques</td>
                        <td>Intérêt légitime (6.1.f)</td>
                        <td>Analyse anonymisée de la navigation pour améliorer l'expérience</td>
                    </tr>
                    <tr>
                        <td>Avis &amp; notations produits</td>
                        <td>Consentement (6.1.a)</td>
                        <td>Publication de votre avis sur le site avec votre accord</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <hr class="rgpd-divider">


    <!-- ══════════════════════════
         4. DURÉES DE CONSERVATION
    ══════════════════════════ -->
    <section class="rgpd-section" id="s4">
        <div class="rgpd-section__header">
            <span class="rgpd-section__num">4</span>
            <h2 class="rgpd-section__title">Durées de conservation</h2>
        </div>

        <p>Vos données ne sont conservées que le temps strictement nécessaire aux finalités pour lesquelles elles ont été collectées, conformément à l'article 5(1)(e) du RGPD :</p>

        <div class="rgpd-table-wrap">
            <table class="rgpd-table">
                <thead>
                    <tr>
                        <th>Catégorie de données</th>
                        <th>Durée de conservation</th>
                        <th>Fondement</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Données du compte client actif</td>
                        <td>Durée de la relation commerciale + 3 ans après inactivité</td>
                        <td>Prescription civile (art. 2224 C.civ.)</td>
                    </tr>
                    <tr>
                        <td>Données de commande</td>
                        <td>10 ans</td>
                        <td>Obligation comptable (art. L.123-22 C.com.)</td>
                    </tr>
                    <tr>
                        <td>Données de paiement</td>
                        <td>13 mois (empreinte CB) / Non stockées par WakAroma</td>
                        <td>Recommandation CNIL</td>
                    </tr>
                    <tr>
                        <td>Adresse IP (logs serveur)</td>
                        <td>12 mois</td>
                        <td>Recommandation CNIL &amp; loi LCEN</td>
                    </tr>
                    <tr>
                        <td>Consentement newsletter</td>
                        <td>3 ans à compter du dernier contact</td>
                        <td>Recommandation CNIL</td>
                    </tr>
                    <tr>
                        <td>Cookies de mesure d'audience</td>
                        <td>13 mois maximum</td>
                        <td>Délibération CNIL 2020-091</td>
                    </tr>
                    <tr>
                        <td>Données de prospection (non-client)</td>
                        <td>3 ans à compter de la collecte</td>
                        <td>Recommandation CNIL</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p>À l'issue de ces délais, vos données sont supprimées de façon sécurisée ou anonymisées de manière irréversible.</p>
    </section>

    <hr class="rgpd-divider">


    <!-- ══════════════════════════
         5. DESTINATAIRES
    ══════════════════════════ -->
    <section class="rgpd-section" id="s5">
        <div class="rgpd-section__header">
            <span class="rgpd-section__num">5</span>
            <h2 class="rgpd-section__title">Destinataires des données</h2>
        </div>

        <p>Vos données personnelles ne sont <strong>jamais vendues</strong> à des tiers. Elles peuvent être transmises aux seuls destinataires suivants, dans le strict cadre des finalités décrites :</p>

        <ul>
            <li><strong>Notre personnel habilité</strong> (service client, logistique, comptabilité) — accès limité au strict nécessaire</li>
            <li><strong>Prestataire de paiement sécurisé</strong> (ex. Stripe, PayPlug) — traitement des transactions dans le respect des normes PCI-DSS</li>
            <li><strong>Prestataires logistiques &amp; transporteurs</strong> — pour l'expédition et la livraison de vos commandes</li>
            <li><strong>Hébergeur du site</strong> — stockage des données dans un datacenter situé en Union européenne</li>
            <li><strong>Service d'envoi d'e-mails transactionnels</strong> (PHPMailer / SMTP) — pour les confirmations de commande et les codes de validation</li>
            <li><strong>Autorités compétentes</strong> — sur requête judiciaire ou légale obligatoire uniquement</li>
        </ul>

        <p>Chaque prestataire est lié à WakAroma par un contrat de traitement de données (DPA) garantissant un niveau de protection équivalent au nôtre, conformément à l'article 28 du RGPD.</p>
    </section>

    <hr class="rgpd-divider">


    <!-- ══════════════════════════
         6. TRANSFERTS HORS UE
    ══════════════════════════ -->
    <section class="rgpd-section" id="s6">
        <div class="rgpd-section__header">
            <span class="rgpd-section__num">6</span>
            <h2 class="rgpd-section__title">Transferts hors de l'Union européenne</h2>
        </div>

        <p>WakAroma s'efforce de traiter vos données au sein de l'Union européenne. Dans le cas où un prestataire serait établi hors de l'UE, nous nous assurons que le transfert est encadré par l'une des garanties appropriées prévues par le RGPD (Chapitre V) :</p>

        <ul>
            <li><strong>Décision d'adéquation</strong> de la Commission européenne (ex. pays reconnu comme offrant un niveau de protection adéquat)</li>
            <li><strong>Clauses contractuelles types (CCT)</strong> adoptées par la Commission européenne</li>
            <li><strong>Règles d'entreprise contraignantes (BCR)</strong></li>
        </ul>

        <p>Pour toute information sur les garanties en place, vous pouvez nous contacter à <a href="mailto:contact@wakaroma.fr" style="color:var(--gold)">contact@wakaroma.fr</a>.</p>
    </section>

    <hr class="rgpd-divider">


    <!-- ══════════════════════════
         7. VOS DROITS
    ══════════════════════════ -->
    <section class="rgpd-section" id="s7">
        <div class="rgpd-section__header">
            <span class="rgpd-section__num">7</span>
            <h2 class="rgpd-section__title">Vos droits sur vos données</h2>
        </div>

        <p>Conformément au RGPD (art. 15 à 22) et à la loi Informatique et Libertés modifiée, vous disposez des droits suivants :</p>

        <div class="rgpd-rights-grid">

            <div class="rgpd-right-card">
                <span class="rgpd-right-card__icon">👁️</span>
                <p class="rgpd-right-card__name">Droit d'accès (art. 15)</p>
                <p class="rgpd-right-card__desc">Obtenir une copie de toutes vos données personnelles que nous traitons.</p>
            </div>

            <div class="rgpd-right-card">
                <span class="rgpd-right-card__icon">✏️</span>
                <p class="rgpd-right-card__name">Droit de rectification (art. 16)</p>
                <p class="rgpd-right-card__desc">Corriger des données inexactes ou incomplètes vous concernant.</p>
            </div>

            <div class="rgpd-right-card">
                <span class="rgpd-right-card__icon">🗑️</span>
                <p class="rgpd-right-card__name">Droit à l'effacement (art. 17)</p>
                <p class="rgpd-right-card__desc">Demander la suppression de vos données (« droit à l'oubli »), sous réserve des obligations légales.</p>
            </div>

            <div class="rgpd-right-card">
                <span class="rgpd-right-card__icon">🔒</span>
                <p class="rgpd-right-card__name">Droit à la limitation (art. 18)</p>
                <p class="rgpd-right-card__desc">Suspendre temporairement le traitement de vos données dans certains cas.</p>
            </div>

            <div class="rgpd-right-card">
                <span class="rgpd-right-card__icon">📦</span>
                <p class="rgpd-right-card__name">Droit à la portabilité (art. 20)</p>
                <p class="rgpd-right-card__desc">Récupérer vos données dans un format structuré et lisible par machine.</p>
            </div>

            <div class="rgpd-right-card">
                <span class="rgpd-right-card__icon">🚫</span>
                <p class="rgpd-right-card__name">Droit d'opposition (art. 21)</p>
                <p class="rgpd-right-card__desc">Vous opposer à tout traitement fondé sur notre intérêt légitime, notamment la prospection commerciale.</p>
            </div>

            <div class="rgpd-right-card">
                <span class="rgpd-right-card__icon">🤖</span>
                <p class="rgpd-right-card__name">Décision automatisée (art. 22)</p>
                <p class="rgpd-right-card__desc">Ne pas faire l'objet d'une décision basée exclusivement sur un traitement automatisé.</p>
            </div>

            <div class="rgpd-right-card">
                <span class="rgpd-right-card__icon">📋</span>
                <p class="rgpd-right-card__name">Directives post-mortem</p>
                <p class="rgpd-right-card__desc">Définir le sort de vos données après votre décès (loi Informatique et Libertés, art. 85).</p>
            </div>

        </div>

        <div class="rgpd-box">
            <strong>Comment exercer vos droits ?</strong>
            <p style="margin:0">
                Envoyez votre demande par e-mail à <a href="mailto:contact@wakaroma.fr" style="color:var(--gold)"><strong>contact@wakaroma.fr</strong></a> en précisant l'objet de votre demande et votre identité (nom, prénom, adresse e-mail du compte). Nous répondrons dans un délai d'<strong>un mois</strong> à compter de la réception de votre demande. En cas de demande complexe ou multiple, ce délai peut être prolongé de deux mois supplémentaires, avec information préalable.
            </p>
        </div>

        <p>Si vous estimez que vos droits ne sont pas respectés, vous avez le droit d'introduire une réclamation auprès de la <strong>CNIL</strong> (Commission Nationale de l'Informatique et des Libertés) — <a href="https://www.cnil.fr" target="_blank" rel="noopener noreferrer" style="color:var(--gold)">www.cnil.fr</a> — ou de toute autre autorité de contrôle compétente.</p>
    </section>

    <hr class="rgpd-divider">


    <!-- ══════════════════════════
         8. COOKIES
    ══════════════════════════ -->
    <section class="rgpd-section" id="s8">
        <div class="rgpd-section__header">
            <span class="rgpd-section__num">8</span>
            <h2 class="rgpd-section__title">Politique en matière de cookies</h2>
        </div>

        <p>Conformément à la délibération CNIL n° 2020-091 du 17 septembre 2020 et à la directive ePrivacy, nous vous informons de l'utilisation de cookies sur notre site.</p>

        <p><strong>Qu'est-ce qu'un cookie ?</strong><br>
        Un cookie est un petit fichier texte déposé sur votre terminal (ordinateur, tablette, smartphone) lors de votre visite. Il permet de mémoriser des informations relatives à votre navigation.</p>

        <p><strong>Cookies utilisés par WakAroma :</strong></p>

        <div class="rgpd-table-wrap">
            <table class="rgpd-table">
                <thead>
                    <tr>
                        <th>Type de cookie</th>
                        <th>Finalité</th>
                        <th>Durée</th>
                        <th>Consentement</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Session PHP (PHPSESSID)</td>
                        <td>Maintien de votre session de connexion (panier, authentification)</td>
                        <td>Session (supprimé à la fermeture)</td>
                        <td>Non requis (strictement nécessaire)</td>
                    </tr>
                    <tr>
                        <td>Préférences utilisateur</td>
                        <td>Mémorisation de vos préférences (langue, indicatif téléphonique)</td>
                        <td>12 mois</td>
                        <td>Non requis (fonctionnel)</td>
                    </tr>
                    <tr>
                        <td>Mesure d'audience (statistiques)</td>
                        <td>Analyse anonymisée du trafic pour améliorer le site</td>
                        <td>13 mois max.</td>
                        <td><strong>Requis</strong></td>
                    </tr>
                    <tr>
                        <td>Cookies marketing / publicité</td>
                        <td>Non utilisés par WakAroma</td>
                        <td>—</td>
                        <td>—</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p><strong>Gestion de vos cookies :</strong><br>
        Vous pouvez à tout moment retirer votre consentement aux cookies non essentiels via le panneau de gestion accessible en bas de page, ou en configurant votre navigateur :</p>

        <ul>
            <li><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener noreferrer" style="color:var(--gold)">Google Chrome</a></li>
            <li><a href="https://support.mozilla.org/fr/kb/protection-renforcee-contre-le-pistage-firefox" target="_blank" rel="noopener noreferrer" style="color:var(--gold)">Mozilla Firefox</a></li>
            <li><a href="https://support.apple.com/fr-fr/guide/safari/sfri11471/mac" target="_blank" rel="noopener noreferrer" style="color:var(--gold)">Apple Safari</a></li>
            <li><a href="https://support.microsoft.com/fr-fr/microsoft-edge/supprimer-les-cookies-dans-microsoft-edge" target="_blank" rel="noopener noreferrer" style="color:var(--gold)">Microsoft Edge</a></li>
        </ul>

        <div class="rgpd-box">
            <strong>Attention</strong>
            La désactivation des cookies strictement nécessaires (session) peut altérer le bon fonctionnement du site, notamment votre panier et votre espace client.
        </div>
    </section>

    <hr class="rgpd-divider">


    <!-- ══════════════════════════
         9. SÉCURITÉ
    ══════════════════════════ -->
    <section class="rgpd-section" id="s9">
        <div class="rgpd-section__header">
            <span class="rgpd-section__num">9</span>
            <h2 class="rgpd-section__title">Sécurité des données</h2>
        </div>

        <p>WakAroma met en œuvre des mesures techniques et organisationnelles appropriées pour protéger vos données contre toute perte, destruction, altération, accès ou divulgation non autorisés, conformément à l'article 32 du RGPD :</p>

        <ul>
            <li><strong>Chiffrement des mots de passe</strong> avec l'algorithme bcrypt (facteur de coût adapté)</li>
            <li><strong>Transmission chiffrée</strong> des données via le protocole HTTPS / TLS</li>
            <li><strong>Accès restreint</strong> aux données personnelles — seul le personnel habilité y a accès, selon le principe du moindre privilège</li>
            <li><strong>Hébergement sécurisé</strong> chez un prestataire certifié, situé en Union européenne</li>
            <li><strong>Mises à jour régulières</strong> des systèmes et des logiciels pour corriger les vulnérabilités</li>
            <li><strong>Journalisation des accès</strong> pour détecter toute activité suspecte</li>
        </ul>

        <p>En cas de violation de données susceptible d'engendrer un risque pour vos droits et libertés, nous nous engageons à notifier la CNIL dans les <strong>72 heures</strong> conformément à l'article 33 du RGPD, et à vous en informer dans les meilleurs délais si la violation présente un risque élevé (art. 34 RGPD).</p>
    </section>

    <hr class="rgpd-divider">


    <!-- ══════════════════════════
         10. MINEURS
    ══════════════════════════ -->
    <section class="rgpd-section" id="s10">
        <div class="rgpd-section__header">
            <span class="rgpd-section__num">10</span>
            <h2 class="rgpd-section__title">Protection des mineurs</h2>
        </div>

        <p>Le site WakAroma est destiné à un public adulte. Conformément à l'article 8 du RGPD transposé en droit français par la loi n° 2018-493 du 20 juin 2018, nous ne collectons pas sciemment les données personnelles de mineurs de moins de <strong>15 ans</strong> sans le consentement préalable d'un titulaire de l'autorité parentale.</p>

        <p>Si vous êtes parent ou tuteur légal et que vous pensez que votre enfant nous a fourni des données personnelles sans votre consentement, veuillez nous contacter immédiatement à <a href="mailto:contact@wakaroma.fr" style="color:var(--gold)">contact@wakaroma.fr</a> afin que nous procédions à leur suppression.</p>
    </section>

    <hr class="rgpd-divider">


    <!-- ══════════════════════════
         11. MODIFICATIONS
    ══════════════════════════ -->
    <section class="rgpd-section" id="s11">
        <div class="rgpd-section__header">
            <span class="rgpd-section__num">11</span>
            <h2 class="rgpd-section__title">Modifications de la présente politique</h2>
        </div>

        <p>WakAroma se réserve le droit de modifier la présente politique de confidentialité à tout moment, notamment pour se conformer à toute évolution réglementaire, jurisprudentielle ou technique.</p>

        <p>En cas de modification substantielle, vous serez informé par e-mail (si vous disposez d'un compte) et/ou par une notification visible sur le site, au moins <strong>30 jours avant</strong> l'entrée en vigueur des nouvelles dispositions.</p>

        <p>La date de dernière mise à jour est toujours indiquée en haut de cette page. Nous vous encourageons à consulter régulièrement cette page.</p>
    </section>

    <hr class="rgpd-divider">


    <!-- ══════════════════════════
         12. CONTACT & RÉCLAMATIONS
    ══════════════════════════ -->
    <section class="rgpd-section" id="s12">
        <div class="rgpd-section__header">
            <span class="rgpd-section__num">12</span>
            <h2 class="rgpd-section__title">Contact et réclamations</h2>
        </div>

        <p>Pour toute question, demande d'exercice de vos droits ou réclamation relative à la protection de vos données personnelles, vous pouvez nous contacter :</p>

        <ul>
            <li><strong>Par e-mail :</strong> <a href="mailto:contact@wakaroma.fr" style="color:var(--gold)">contact@wakaroma.fr</a></li>
            <li><strong>Par courrier :</strong> WakAroma — [Adresse postale], [Code postal] [Ville], France</li>
        </ul>

        <p>Si votre demande ne reçoit pas de réponse satisfaisante dans le délai imparti, vous pouvez introduire une réclamation auprès de l'autorité de contrôle compétente en France :</p>

        <div class="rgpd-box">
            <strong>CNIL — Commission Nationale de l'Informatique et des Libertés</strong>
            <p style="margin:0">
                3 Place de Fontenoy — TSA 80715<br>
                75334 Paris Cedex 07<br>
                Tél. : 01 53 73 22 22<br>
                <a href="https://www.cnil.fr/fr/plaintes" target="_blank" rel="noopener noreferrer" style="color:var(--gold)">www.cnil.fr/fr/plaintes</a>
            </p>
        </div>
    </section>


    <!-- ── Bloc contact CTA ── -->
    <div class="rgpd-contact">
        <p class="rgpd-contact__title">Une question sur vos données ?</p>
        <p class="rgpd-contact__sub">Notre équipe vous répond dans un délai d'un mois, comme l'exige le RGPD.</p>
        <a href="mailto:contact@wakaroma.fr" class="rgpd-contact__btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            Nous contacter
        </a>
    </div>

</div>

<!-- FOOTER -->
<?php require_once "footer.php"; ?>
