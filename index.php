<?php
session_start();
require_once 'function.php';
$datas = recuperation_produits_images();
?>

<?php require_once 'headear.php'; ?>

<!-- ══════════════════════════════════════════
     HERO SECTION
     ══════════════════════════════════════════ -->
<section class="hero" aria-label="Bannière principale">
    <div class="hero__bg-overlay"></div>

    <div class="hero__content">
        <p class="hero__eyebrow">Directement sourcé d'Afrique</p>
        <h2 class="hero__title">
            L'Afrique<br>
            <em>parfume</em><br>
            vos instants
        </h2>
        <p class="hero__desc">
            Des épices rares, des mélanges authentiques,<br>
            cueillis à la main et livrés chez vous.
        </p>
        <div class="hero__ctas">
            <a href="#produits" class="hero__cta hero__cta--primary">
                Découvrir nos épices
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
            <a href="#historique" class="hero__cta hero__cta--ghost">Notre histoire</a>
        </div>

        <!-- Stats -->
        <div class="hero__stats">
            <div class="hero__stat">
                <span class="hero__stat-num">50+</span>
                <span class="hero__stat-label">Épices & aromates</span>
            </div>
            <div class="hero__stat-sep"></div>
            <div class="hero__stat">
                <span class="hero__stat-num">100%</span>
                <span class="hero__stat-label">Naturels & purs</span>
            </div>
            <div class="hero__stat-sep"></div>
            <div class="hero__stat">
                <span class="hero__stat-num">24h</span>
                <span class="hero__stat-label">Expédition rapide</span>
            </div>
        </div>
    </div>

    <!-- Scroll indicator -->
    <div class="hero__scroll" aria-hidden="true">
        <div class="hero__scroll-dot"></div>
    </div>
</section>



<!-- ══════════════════════════════════════════
     SECTION PRODUITS
     ══════════════════════════════════════════ -->
<div class="section-header" id="produits">
    <div class="section-header__eyebrow">Notre sélection</div>
    <h2 class="section-header__title">Les Épices du Moment</h2>
    <p class="section-header__sub">Chaque produit est soigneusement sélectionné pour vous offrir le meilleur de l'Afrique.</p>
</div>

<!-- Filtres -->
<div class="filters-bar">
    <button class="filter-btn filter-btn--active" data-filter="all">Tous</button>
    <button class="filter-btn" data-filter="epices">Épices</button>
    <button class="filter-btn" data-filter="melanges">Mélanges</button>
    <button class="filter-btn" data-filter="huiles">Huiles</button>
    <button class="filter-btn" data-filter="bio">Bio</button>
</div>

<section class="produits" id="produit">

    <?php foreach($datas as $data): ?>

        <article class="produit" data-category="epices">

            <div class="produit__img-wrap">
                <img
                    src="<?= htmlspecialchars($data->url_image ?? 'images/placeholder.png'); ?>"
                    alt="<?= htmlspecialchars($data->nom); ?>"
                    loading="lazy"
                >
                <!-- Badge -->
                <?php if((int)$data->stock <= 5 && (int)$data->stock > 0): ?>
                    <span class="produit__badge produit__badge--low">Dernières pièces</span>
                <?php elseif((int)$data->stock === 0): ?>
                    <span class="produit__badge produit__badge--rupture">Épuisé</span>
                <?php else: ?>
                    <span class="produit__badge produit__badge--new">Disponible</span>
                <?php endif; ?>

                <!-- Wishlist -->
                <button class="produit__wishlist" aria-label="Ajouter aux favoris" onclick="this.classList.toggle('actif')">♡</button>

                <!-- Overlay au hover -->
                <div class="produit__overlay">
                    <button class="produit__btn-quick">Aperçu rapide</button>
                </div>
            </div>

            <div class="produit__contenu">
                <p class="produit__categorie">Épice · WakAroma</p>

                <h2 class="produit__titre">
                    <?= htmlspecialchars($data->nom); ?>
                </h2>

                <div class="produit__etoiles" aria-label="Note : 4.5 sur 5">
                    ★★★★<span style="opacity:.35">★</span>
                    <span class="produit__avis">(24)</span>
                </div>

                <p class="produit__description">
                    <?= htmlspecialchars($data->description); ?>
                </p>
            </div>

            <div class="produit__footer">
                <div class="produit__prix-row">
                    <span class="produit__prix"><?= number_format($data->prix, 2); ?> €</span>
                </div>

                <div class="produit__stock <?= (int)$data->stock > 0 ? 'produit__stock--ok' : '' ?>">
                    <?php if((int)$data->stock > 0): ?>
                        <svg width="10" height="10" viewBox="0 0 10 10"><circle cx="5" cy="5" r="4" fill="#2d7a44"/></svg>
                        En stock (<?= (int)$data->stock ?>)
                    <?php else: ?>
                        <svg width="10" height="10" viewBox="0 0 10 10"><circle cx="5" cy="5" r="4" fill="#e74c3c"/></svg>
                        Rupture de stock
                    <?php endif; ?>
                </div>

                <div class="produit__actions">
                    <button class="produit__btn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        Découvrir
                    </button>
                </div>

                <?php if((int)$data->stock === 0): ?>
                    <span class="rupture-label">⚠ Indisponible</span>
                <?php endif; ?>

                <button
                    class="panier__btn<?= (int)$data->stock === 0 ? ' panier__btn--rupture' : '' ?>"
                    data-id="<?= (int)$data->id_produit ?>"
                    onclick="ajouterAuPanier(this)"
                    <?= (int)$data->stock === 0 ? 'disabled' : '' ?>
                >
                    <?php if((int)$data->stock === 0): ?>
                        ✕ Indisponible
                    <?php else: ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                        Ajouter au panier
                    <?php endif; ?>
                </button>
            </div>

        </article>

    <?php endforeach; ?>

</section>

<!-- ══════════════════════════════════════════
     SECTION UNIVERS / EDITO
     ══════════════════════════════════════════ -->
<section class="edito-section" id="historique">
    <div class="edito-grid">
        <div class="edito-card edito-card--large" style="background: linear-gradient(135deg, var(--color-green) 0%, #0d3320 100%);">
            <div class="edito-card__content">
                <p class="edito-card__eyebrow">Notre histoire</p>
                <h3 class="edito-card__title">L'Afrique au cœur de chaque épice</h3>
                <p class="edito-card__text">Fondée avec passion, WakAroma sélectionne les meilleures épices directement auprès des producteurs africains pour vous offrir une expérience gustative unique.</p>
                <a href="#" class="edito-card__link">En savoir plus →</a>
            </div>
            <div class="edito-card__deco" aria-hidden="true">🌍</div>
        </div>
        <div class="edito-card" style="background: linear-gradient(135deg, #c8943a 0%, #e8a832 100%);">
            <div class="edito-card__content">
                <p class="edito-card__eyebrow">Savoir-faire</p>
                <h3 class="edito-card__title">Mélanges artisanaux</h3>
                <p class="edito-card__text">Chaque mélange est préparé à la main selon des recettes ancestrales.</p>
                <a href="#" class="edito-card__link">Découvrir →</a>
            </div>
            <div class="edito-card__deco" aria-hidden="true">🫙</div>
        </div>
        <div class="edito-card" style="background: linear-gradient(135deg, #d4a574 0%, #e8c49a 100%);">
            <div class="edito-card__content">
                <p class="edito-card__eyebrow">Nos salons</p>
                <h3 class="edito-card__title">Venez nous rencontrer</h3>
                <p class="edito-card__text">Découvrez nos épices en boutique et laissez-vous guider par nos experts.</p>
                <a href="#salon" class="edito-card__link" style="color: var(--color-green);">Trouver un salon →</a>
            </div>
            <div class="edito-card__deco" aria-hidden="true">🌿</div>
        </div>
    </div>
</section>

<!-- Newsletter -->
<section class="newsletter-section">
    <div class="newsletter-inner">
        <div class="newsletter-text">
            <h3 class="newsletter-title">Restez dans la saveur</h3>
            <p class="newsletter-sub">Recevez nos nouveautés, recettes et offres exclusives directement dans votre boîte mail.</p>
        </div>
        <form class="newsletter-form" onsubmit="return false;">
            <input type="email" placeholder="votre@email.com" class="newsletter-input" required>
            <button type="submit" class="newsletter-btn">S'abonner ✦</button>
        </form>
    </div>
</section>

<!-- Toast de notification -->
<div id="toast-index" class="toast-notif" aria-live="polite"></div>

<!-- Footer -->
<?php require_once 'footer.php'; ?>

<!-- ══════════════════════════════════════════
     CHATBOT WAKAROMA
     ══════════════════════════════════════════ -->
<button id="chat-toggle" aria-label="Ouvrir le chat Wakaroma" style="
  position:fixed; bottom:28px; right:28px; width:62px; height:62px;
  border-radius:50%;
  background:linear-gradient(135deg,#1f4f2e 0%,#2d7a44 100%);
  border:none; cursor:pointer;
  box-shadow:0 8px 28px rgba(31,79,46,0.38);
  display:flex; align-items:center; justify-content:center;
  transition:transform .25s ease,box-shadow .25s ease; z-index:9999;">
  <span id="chat-badge" style="position:absolute;top:-3px;right:-3px;width:14px;height:14px;background:#c77f2c;border-radius:50%;border:2px solid #f9f9f9;animation:wk-pulse 2s infinite;"></span>
  <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
</button>

<div id="chat-window" role="dialog" aria-label="Chatbot Wakaroma" style="position:fixed;bottom:104px;right:28px;width:380px;max-height:620px;background:#fff;border-radius:20px;box-shadow:0 24px 60px rgba(31,79,46,0.18);border:1px solid rgba(199,127,44,0.2);display:flex;flex-direction:column;overflow:hidden;z-index:9998;opacity:0;transform:translateY(20px) scale(0.96);pointer-events:none;transition:opacity .3s ease,transform .3s ease;font-family:'DM Sans','Segoe UI',sans-serif;">
  <div style="background:linear-gradient(135deg,#1f4f2e 0%,#2d7a44 100%);padding:1.1rem 1.4rem;display:flex;align-items:center;gap:.9rem;flex-shrink:0;">
    <div style="width:42px;height:42px;background:rgba(255,255,255,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:2px solid rgba(199,127,44,.5);">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9" stroke-width="3"/><line x1="15" y1="9" x2="15.01" y2="9" stroke-width="3"/></svg>
    </div>
    <div style="flex:1;">
      <div style="font-family:'Cormorant Garamond',serif;font-size:1.15rem;font-weight:700;color:#fff;">Wakaroma Assistant</div>
      <div style="font-size:.75rem;color:rgba(255,255,255,.7);display:flex;align-items:center;gap:.35rem;margin-top:.1rem;"><span style="width:7px;height:7px;background:#5de38a;border-radius:50%;display:inline-block;"></span>En ligne · répond en quelques secondes</div>
    </div>
    <button id="chat-close" aria-label="Fermer" style="background:rgba(255,255,255,.12);border:none;cursor:pointer;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.8);transition:background .2s;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
  <div id="wk-rgpd-banner" style="background:#fff8e6;border-bottom:1px solid #fde8c0;padding:1rem 1.25rem;flex-shrink:0;">
    <p style="font-size:.78rem;color:#6b7870;line-height:1.5;margin-bottom:.75rem;"><strong style="color:#2d352e;">Avant de commencer 🍃</strong><br>Ce chatbot traite vos messages pour vous répondre. Aucune donnée n'est partagée avec des tiers. En cliquant sur <strong>« Accepter »</strong> vous consentez à l'utilisation de vos messages.</p>
    <div style="display:flex;gap:.5rem;">
      <button id="wk-btn-accept" style="flex:1;padding:.55rem .75rem;border-radius:999px;font-size:.78rem;font-weight:600;cursor:pointer;border:1.5px solid #1f4f2e;background:#1f4f2e;color:#fff;font-family:inherit;">✓ Accepter</button>
      <button id="wk-btn-refuse" style="flex:1;padding:.55rem .75rem;border-radius:999px;font-size:.78rem;font-weight:600;cursor:pointer;border:1.5px solid #ddd;background:transparent;color:#6b7870;font-family:inherit;">Refuser</button>
    </div>
  </div>
  <div id="wk-messages" style="flex:1;overflow-y:auto;padding:1.2rem 1.1rem;display:flex;flex-direction:column;gap:.8rem;scroll-behavior:smooth;"></div>
  <div id="wk-suggestions" style="display:none;flex-wrap:wrap;gap:.4rem;padding:0 1.1rem .8rem;"></div>
  <div id="wk-rgpd-refused" style="display:none;flex:1;align-items:center;justify-content:center;flex-direction:column;gap:.75rem;padding:2rem;text-align:center;">
    <p style="font-size:.88rem;color:#6b7870;">Vous avez refusé le traitement de vos données. Le chatbot ne peut pas fonctionner sans votre consentement.</p>
    <button id="wk-btn-retry" style="padding:.6rem 1.4rem;background:#1f4f2e;color:#fff;border:none;border-radius:999px;font-size:.82rem;font-weight:600;font-family:inherit;cursor:pointer;">Modifier mon choix</button>
  </div>
  <div id="wk-input-zone" style="display:none;border-top:1px solid #f0f0f0;padding:.85rem 1rem;gap:.6rem;align-items:center;flex-shrink:0;">
    <input type="text" id="wk-input" placeholder="Écrivez votre message…" maxlength="500" disabled style="flex:1;border:1.5px solid #e5e5e5;border-radius:999px;padding:.65rem 1.1rem;font-size:.87rem;font-family:inherit;color:#2d352e;outline:none;background:#fafafa;width:100%;">
    <button id="wk-send" disabled aria-label="Envoyer" style="width:42px;height:42px;border-radius:50%;flex-shrink:0;background:linear-gradient(135deg,#1f4f2e 0%,#2d7a44 100%);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(31,79,46,.3);">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
    </button>
  </div>
  <div id="wk-footer" style="display:none;text-align:center;font-size:.68rem;color:#bbb;padding:.4rem 1rem .7rem;">🔒 Données protégées · <a href="mailto:contact@wakaroma.fr" style="color:#c77f2c;text-decoration:none;">Politique de confidentialité</a></div>
</div>

<style>
@keyframes wk-pulse{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.3);opacity:.7}}
@keyframes wk-msgIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
@keyframes wk-typing{0%,80%,100%{transform:scale(1);opacity:.5}40%{transform:scale(1.3);opacity:1}}
#chat-toggle:hover{transform:scale(1.08) translateY(-2px);box-shadow:0 14px 38px rgba(31,79,46,.46) !important;}
#wk-messages::-webkit-scrollbar{width:4px}
#wk-messages::-webkit-scrollbar-thumb{background:#ddd;border-radius:4px}
#wk-input-zone{display:none;}
#wk-input-zone.wk-visible{display:flex;}
#chat-window.wk-open{opacity:1 !important;transform:translateY(0) scale(1) !important;pointer-events:all !important;}
@media(max-width:480px){#chat-window{width:calc(100vw - 20px) !important;right:10px !important;bottom:94px !important;max-height:70vh !important;}#chat-toggle{right:16px !important;bottom:16px !important;}}
</style>

<script>
(function(){
  var SYSTEM=`Tu es l'assistant virtuel du site Wakaroma, une épicerie fine spécialisée dans les produits aromatiques, épices, huiles essentielles et produits naturels de qualité.\nTon rôle : accueillir les visiteurs, répondre aux questions sur les produits (épices, huiles, aromates, thés…), aider à la navigation (panier, commandes, livraison, compte), donner des conseils culinaires ou bien-être, informer sur les politiques de livraison et retours.\nInfos clés : livraison offerte dès 40€ (France métropolitaine), délai 3-5 jours ouvrés, retours sous 14 jours (produits non ouverts), contact : contact@wakaroma.fr.\nStyle : chaleureux, professionnel, concis. Émojis avec modération 🌿. Toujours répondre en français.`;
  var SUGGESTIONS_INIT=['🌿 Meilleures épices','🚚 Délais de livraison','↩️ Politique de retour','🔒 Mes droits RGPD'];
  var history=[];var rgpdOk=localStorage.getItem('wakaroma_rgpd')==='accepted';
  var toggle=document.getElementById('chat-toggle'),win=document.getElementById('chat-window'),closeBtn=document.getElementById('chat-close'),banner=document.getElementById('wk-rgpd-banner'),refused=document.getElementById('wk-rgpd-refused'),inputZone=document.getElementById('wk-input-zone'),messages=document.getElementById('wk-messages'),sugg=document.getElementById('wk-suggestions'),input=document.getElementById('wk-input'),sendBtn=document.getElementById('wk-send'),footer=document.getElementById('wk-footer'),badge=document.getElementById('chat-badge');
  toggle.addEventListener('click',function(){win.classList.toggle('wk-open');badge.style.display='none';if(win.classList.contains('wk-open')&&rgpdOk&&messages.children.length===0)initChat();});
  closeBtn.addEventListener('click',function(){win.classList.remove('wk-open');});
  if(rgpdOk){showUI();}
  document.getElementById('wk-btn-accept').addEventListener('click',function(){localStorage.setItem('wakaroma_rgpd','accepted');rgpdOk=true;banner.style.display='none';showUI();initChat();});
  document.getElementById('wk-btn-refuse').addEventListener('click',function(){banner.style.display='none';refused.style.display='flex';});
  document.getElementById('wk-btn-retry').addEventListener('click',function(){refused.style.display='none';banner.style.display='block';});
  function showUI(){banner.style.display='none';refused.style.display='none';inputZone.classList.add('wk-visible');footer.style.display='block';input.disabled=false;sendBtn.disabled=false;}
  function initChat(){addMsg('bot','🌿 Bonjour et bienvenue sur **Wakaroma** ! Je suis votre assistant. Comment puis-je vous aider aujourd\'hui ?');showSugg(SUGGESTIONS_INIT);}
  function showSugg(items){sugg.innerHTML='';if(!items.length){sugg.style.display='none';return;}items.forEach(function(txt){var b=document.createElement('button');b.textContent=txt;b.style.cssText='padding:.45rem .85rem;background:#fff8e6;border:1px solid #fde8c0;border-radius:999px;font-size:.78rem;font-weight:500;color:#c77f2c;cursor:pointer;font-family:inherit;';b.addEventListener('click',function(){sugg.style.display='none';send(txt);});sugg.appendChild(b);});sugg.style.display='flex';}
  function addMsg(role,text){var wrap=document.createElement('div');wrap.style.cssText='display:flex;gap:.5rem;align-items:flex-end;animation:wk-msgIn .25s ease;'+(role==='user'?'align-self:flex-end;flex-direction:row-reverse;':'align-self:flex-start;');if(role==='bot'){var av=document.createElement('div');av.style.cssText='width:28px;height:28px;background:#e8f5ec;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;';av.innerHTML='<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1f4f2e" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9" stroke-width="3"/><line x1="15" y1="9" x2="15.01" y2="9" stroke-width="3"/></svg>';wrap.appendChild(av);}var bubble=document.createElement('div');bubble.style.cssText='max-width:78%;padding:.65rem .95rem;font-size:.87rem;line-height:1.55;'+(role==='bot'?'background:#f4f7f5;color:#2d352e;border-radius:4px 16px 16px 16px;':'background:linear-gradient(135deg,#1f4f2e,#2d7a44);color:#fff;border-radius:16px 4px 16px 16px;');bubble.innerHTML=text.replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>').replace(/\n/g,'<br>');wrap.appendChild(bubble);messages.appendChild(wrap);messages.scrollTop=messages.scrollHeight;}
  function showTyping(){var wrap=document.createElement('div');wrap.id='wk-typing';wrap.style.cssText='display:flex;gap:.5rem;align-items:flex-end;align-self:flex-start;';wrap.innerHTML='<div style="width:28px;height:28px;background:#e8f5ec;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1f4f2e" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg></div><div style="display:flex;gap:4px;align-items:center;padding:.65rem .95rem;background:#f4f7f5;border-radius:4px 16px 16px 16px;"><span style="width:7px;height:7px;background:#bbb;border-radius:50%;animation:wk-typing 1.2s infinite;"></span><span style="width:7px;height:7px;background:#bbb;border-radius:50%;animation:wk-typing 1.2s .2s infinite;"></span><span style="width:7px;height:7px;background:#bbb;border-radius:50%;animation:wk-typing 1.2s .4s infinite;"></span></div>';messages.appendChild(wrap);messages.scrollTop=messages.scrollHeight;}
  function hideTyping(){var el=document.getElementById('wk-typing');if(el)el.remove();}
  async function send(text){var msg=text||input.value.trim();if(!msg)return;input.value='';sendBtn.disabled=true;sugg.style.display='none';addMsg('user',msg);history.push({role:'user',content:msg});showTyping();try{var res=await fetch('https://api.anthropic.com/v1/messages',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({model:'claude-sonnet-4-20250514',max_tokens:1000,system:SYSTEM,messages:history})});var data=await res.json();hideTyping();var reply=(data.content&&data.content[0]&&data.content[0].text)?data.content[0].text:"Désolé, je n'ai pas pu vous répondre. Contactez-nous à contact@wakaroma.fr 🌿";addMsg('bot',reply);history.push({role:'assistant',content:reply});if(history.length===2)showSugg(['🛒 Voir le panier','📦 Suivre ma commande','🌿 Nos produits bio','📧 Nous contacter']);}catch(e){hideTyping();addMsg('bot','⚠️ Une erreur est survenue. Contactez-nous à **contact@wakaroma.fr**.');}sendBtn.disabled=false;input.focus();}
  sendBtn.addEventListener('click',function(){send();});
  input.addEventListener('keydown',function(e){if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();send();}});
})();
</script>

<style>
/* Badge panier */
.nav-cart-wrapper {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
#cart-badge-count {
  position: absolute;
  top: -8px;
  right: -8px;
  min-width: 18px;
  height: 18px;
  padding: 0 4px;
  background: #c77f2c;
  color: #fff;
  font-size: 0.68rem;
  font-weight: 700;
  border-radius: 999px;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  border: 2px solid #fff;
  pointer-events: none;
  transform: scale(0);
  transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
  z-index: 10;
}
#cart-badge-count.visible {
  transform: scale(1);
}
#cart-badge-count.bump {
  animation: badge-bump 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes badge-bump {
  0%   { transform: scale(1); }
  50%  { transform: scale(1.5); }
  100% { transform: scale(1); }
}
</style>

<script>
// ── Badge panier ──────────────────────────────────────────────
function wrapCartIcon() {
  // Cherche le lien vers panier.php dans le header
  const cartLink = document.querySelector('a[href*="panier"]');
  if (!cartLink) return;
  // Évite de wrapper deux fois
  if (cartLink.querySelector('#cart-badge-count')) return;

  cartLink.style.position = 'relative';
  cartLink.style.display  = 'inline-flex';
  cartLink.style.alignItems = 'center';

  const badge = document.createElement('span');
  badge.id = 'cart-badge-count';
  badge.textContent = '0';
  cartLink.appendChild(badge);
}

function updateCartBadge(nb, animate = false) {
  const badge = document.getElementById('cart-badge-count');
  if (!badge) return;
  const n = parseInt(nb) || 0;
  badge.textContent = n > 99 ? '99+' : n;
  if (n > 0) {
    badge.classList.add('visible');
  } else {
    badge.classList.remove('visible');
  }
  if (animate && n > 0) {
    badge.classList.remove('bump');
    void badge.offsetWidth; // force reflow
    badge.classList.add('bump');
    setTimeout(() => badge.classList.remove('bump'), 300);
  }
}

async function fetchCartCount() {
  <?php if (!empty($_SESSION['auth'])): ?>
  try {
    const body = new URLSearchParams({ action: 'get_count' });
    const res  = await fetch('panier.php', { method: 'POST', body });
    const json = await res.json();
    if (json.success) updateCartBadge(json.nb_articles);
  } catch(e) {}
  <?php endif; ?>
}

document.addEventListener('DOMContentLoaded', () => {
  wrapCartIcon();
  fetchCartCount();
});
</script>

<script>
async function ajouterAuPanier(btn) {
    <?php if (empty($_SESSION['auth'])): ?>
        window.location.href = 'login.php';
        return;
    <?php endif; ?>
    const idProduit = btn.dataset.id;
    btn.disabled = true;
    const texteOriginal = btn.innerHTML;
    btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Ajout…';
    try {
        const body = new URLSearchParams({ action: 'ajouter', id_produit: idProduit });
        const res  = await fetch('panier.php', { method: 'POST', body });
        const json = await res.json();
        if (json.success) {
            btn.innerHTML = '✓ Ajouté !';
            btn.classList.add('panier__btn--added');
            afficherToast('Article ajouté au panier 🛒');
            if (json.nb_articles !== undefined) updateCartBadge(json.nb_articles, true);
            setTimeout(() => {
                btn.innerHTML = texteOriginal;
                btn.classList.remove('panier__btn--added');
                btn.disabled = false;
            }, 1800);
        } else {
            afficherToast(json.message || "Erreur lors de l'ajout", 'error');
            btn.innerHTML = texteOriginal;
            btn.disabled = false;
        }
    } catch (e) {
        afficherToast('Erreur de connexion', 'error');
        btn.innerHTML = texteOriginal;
        btn.disabled = false;
    }
}
function afficherToast(msg, type = 'success') {
    const el = document.getElementById('toast-index');
    el.textContent = msg;
    el.className = 'toast-notif toast-notif--' + type + ' toast-notif--visible';
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove('toast-notif--visible'), 3000);
}
</script>