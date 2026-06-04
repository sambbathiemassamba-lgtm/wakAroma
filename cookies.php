<?php
// ══════════════════════════════════════════
//  BANNIÈRE COOKIES — WakAroma
//  Inclure avec : <?php require_once 'cookies.php'; ?>



<!-- ══════════════════════════════════════════
     BANNIÈRE CONSENTEMENT COOKIES
     ══════════════════════════════════════════ -->
<div id="wk-cookie-banner" class="wk-cookie" role="dialog" aria-modal="true" aria-label="Consentement aux cookies" style="display:none;">

    <div class="wk-cookie__inner">

        <!-- Icône + Titre -->
        <div class="wk-cookie__header">
            <span class="wk-cookie__icon">🍪</span>
            <div>
                <p class="wk-cookie__title">Vos préférences de cookies</p>
                <p class="wk-cookie__subtitle">WakAroma respecte votre vie privée</p>
            </div>
        </div>

        <!-- Texte -->
        <p class="wk-cookie__text">
            Nous utilisons des cookies pour améliorer votre expérience, mémoriser votre panier et analyser notre audience.
            Vous pouvez accepter tous les cookies ou choisir vos préférences.
        </p>

        <!-- Détail des types de cookies -->
        <div class="wk-cookie__details" id="wk-cookie-details" style="display:none;">
            <label class="wk-cookie__check">
                <input type="checkbox" checked disabled>
                <span class="wk-cookie__check-label">
                    <strong>Essentiels</strong>
                    <small>Panier, session, sécurité — toujours actifs</small>
                </span>
            </label>
            <label class="wk-cookie__check">
                <input type="checkbox" id="wk-cookie-analytics" checked>
                <span class="wk-cookie__check-label">
                    <strong>Analytiques</strong>
                    <small>Mesure d'audience anonyme (Google Analytics)</small>
                </span>
            </label>
            <label class="wk-cookie__check">
                <input type="checkbox" id="wk-cookie-marketing">
                <span class="wk-cookie__check-label">
                    <strong>Marketing</strong>
                    <small>Publicités personnalisées selon vos goûts</small>
                </span>
            </label>
        </div>

        <!-- Bouton "Personnaliser" -->
        <button class="wk-cookie__toggle" id="wk-cookie-toggle" type="button">
            ⚙️ Personnaliser mes choix
        </button>

        <!-- Actions -->
        <div class="wk-cookie__actions">
            <button class="wk-cookie__btn wk-cookie__btn--refuse" id="wk-cookie-refuse" type="button">
                Refuser tout
            </button>
            <button class="wk-cookie__btn wk-cookie__btn--custom" id="wk-cookie-save" type="button" style="display:none;">
                Enregistrer mes choix
            </button>
            <button class="wk-cookie__btn wk-cookie__btn--accept" id="wk-cookie-accept" type="button">
                Tout accepter
            </button>
        </div>

        <!-- Lien politique -->
        <p class="wk-cookie__legal">
            En savoir plus sur notre <a href="politique-confidentialite.php">politique de confidentialité</a>.
        </p>

    </div>

</div>

<!-- Overlay sombre derrière la bannière -->
<div id="wk-cookie-overlay" class="wk-cookie-overlay" style="display:none;"></div>

<script>
(function () {
    const STORAGE_KEY = 'wk_cookies_consent';
    const banner      = document.getElementById('wk-cookie-banner');
    const overlay     = document.getElementById('wk-cookie-overlay');
    const btnAccept   = document.getElementById('wk-cookie-accept');
    const btnRefuse   = document.getElementById('wk-cookie-refuse');
    const btnSave     = document.getElementById('wk-cookie-save');
    const btnToggle   = document.getElementById('wk-cookie-toggle');
    const details     = document.getElementById('wk-cookie-details');
    const chkAnalytics  = document.getElementById('wk-cookie-analytics');
    const chkMarketing  = document.getElementById('wk-cookie-marketing');

    // ── Afficher si pas encore de choix ──
    function init() {
        if (!localStorage.getItem(STORAGE_KEY)) {
            show();
        }
    }

    function show() {
        banner.style.display  = 'flex';
        overlay.style.display = 'block';
        // Animation entrée
        requestAnimationFrame(() => {
            banner.classList.add('wk-cookie--visible');
            overlay.classList.add('wk-cookie-overlay--visible');
        });
    }

    function hide() {
        banner.classList.remove('wk-cookie--visible');
        overlay.classList.remove('wk-cookie-overlay--visible');
        setTimeout(() => {
            banner.style.display  = 'none';
            overlay.style.display = 'none';
        }, 400);
    }

    function save(prefs) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            ...prefs,
            date: new Date().toISOString()
        }));
        hide();
    }

    // ── Tout accepter ──
    btnAccept.addEventListener('click', () => {
        save({ essential: true, analytics: true, marketing: true, choice: 'all' });
    });

    // ── Refuser tout ──
    btnRefuse.addEventListener('click', () => {
        save({ essential: true, analytics: false, marketing: false, choice: 'none' });
    });

    // ── Enregistrer choix personnalisé ──
    btnSave.addEventListener('click', () => {
        save({
            essential:  true,
            analytics:  chkAnalytics.checked,
            marketing:  chkMarketing.checked,
            choice:     'custom'
        });
    });

    // ── Afficher / masquer le panneau détaillé ──
    btnToggle.addEventListener('click', () => {
        const open = details.style.display === 'block';
        details.style.display  = open ? 'none' : 'block';
        btnSave.style.display   = open ? 'none' : 'inline-flex';
        btnToggle.textContent   = open ? '⚙️ Personnaliser mes choix' : '✕ Fermer la personnalisation';
    });

    // Fermer sur clic overlay
    overlay.addEventListener('click', () => {
        save({ essential: true, analytics: false, marketing: false, choice: 'dismissed' });
    });

    document.addEventListener('DOMContentLoaded', init);
})();
</script>
