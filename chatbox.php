
<button id="chat-toggle" aria-label="Ouvrir le chat Wakaroma
    <span id="chat-badge"></span>

    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
    </svg>

</button>

<div id="chat-window" role="dialog" aria-label="Chatbot Wakaroma">

    <!-- HEADER -->
    <div class="chat-header">

        <div class="chat-avatar">

            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <path d="M8 14s1.5 2 4 2 4-2 4-2"/>
                <line x1="9" y1="9" x2="9.01" y2="9" stroke-width="3"/>
                <line x1="15" y1="9" x2="15.01" y2="9" stroke-width="3"/>
            </svg>

        </div>

        <div class="chat-header__content">

            <div class="chat-title">
                Wakaroma Assistant
            </div>

            <div class="chat-status">

                <span class="chat-status-dot"></span>

                En ligne · répond en quelques secondes

            </div>

        </div>

        <button id="chat-close" aria-label="Fermer" class="chat-close">

            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>

        </button>

    </div>

    <!-- RGPD -->
    <div id="wk-rgpd-banner" class="chat-rgpd">

        <p class="chat-rgpd__text">

            <strong>Avant de commencer 🍃</strong><br>

            Ce chatbot traite vos messages pour vous répondre.
            Aucune donnée n'est partagée avec des tiers.

            En cliquant sur <strong>« Accepter »</strong>
            vous consentez à l'utilisation de vos messages.

        </p>

        <div class="chat-rgpd__actions">

            <button id="wk-btn-accept" class="chat-btn chat-btn--primary">
                ✓ Accepter
            </button>

            <button id="wk-btn-refuse" class="chat-btn chat-btn--secondary">
                Refuser
            </button>

        </div>

    </div>

    <!-- MESSAGES -->
    <div id="wk-messages"></div>

    <!-- SUGGESTIONS -->
    <div id="wk-suggestions"></div>

    <!-- REFUS -->
    <div id="wk-rgpd-refused">

        <p>
            Vous avez refusé le traitement de vos données.
            Le chatbot ne peut pas fonctionner sans votre consentement.
        </p>

        <button id="wk-btn-retry">
            Modifier mon choix
        </button>

    </div>

    <!-- INPUT -->
    <div id="wk-input-zone">

        <input
            type="text"
            id="wk-input"
            placeholder="Écrivez votre message…"
            maxlength="500"
            disabled
        >

        <button id="wk-send" disabled aria-label="Envoyer">

            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="22" y1="2" x2="11" y2="13"/>
                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>

        </button>

    </div>

    <!-- FOOTER -->
    <div id="wk-footer">

        🔒 Données protégées ·

        <a href="mailto:contact@wakaroma.fr">
            Politique de confidentialité
        </a>

    </div>

</div>