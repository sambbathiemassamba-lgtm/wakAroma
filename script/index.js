
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


