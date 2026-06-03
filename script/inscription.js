/**
 * =========================================================
 * PAGE IINSCRIPTION
 * ==============================================================
 */

const countries = [
  ["Afghanistan","AF","+93"],["Afrique du Sud","ZA","+27"],["Albanie","AL","+355"],
  ["Algérie","DZ","+213"],["Allemagne","DE","+49"],["Andorre","AD","+376"],
  ["Angola","AO","+244"],["Arabie Saoudite","SA","+966"],["Argentine","AR","+54"],
  ["Arménie","AM","+374"],["Australie","AU","+61"],["Autriche","AT","+43"],
  ["Azerbaïdjan","AZ","+994"],["Bahreïn","BH","+973"],["Bangladesh","BD","+880"],
  ["Belgique","BE","+32"],["Bénin","BJ","+229"],["Biélorussie","BY","+375"],
  ["Bolivie","BO","+591"],["Brésil","BR","+55"],["Bulgarie","BG","+359"],
  ["Burkina Faso","BF","+226"],["Burundi","BI","+257"],["Cambodge","KH","+855"],
  ["Cameroun","CM","+237"],["Canada","CA","+1"],["Chili","CL","+56"],
  ["Chine","CN","+86"],["Chypre","CY","+357"],["Colombie","CO","+57"],
  ["Congo","CG","+242"],["Corée du Sud","KR","+82"],["Costa Rica","CR","+506"],
  ["Côte d'Ivoire","CI","+225"],["Croatie","HR","+385"],["Cuba","CU","+53"],
  ["Danemark","DK","+45"],["Djibouti","DJ","+253"],["Égypte","EG","+20"],
  ["Émirats Arabes Unis","AE","+971"],["Équateur","EC","+593"],["Espagne","ES","+34"],
  ["Estonie","EE","+372"],["États-Unis","US","+1"],["Éthiopie","ET","+251"],
  ["Finlande","FI","+358"],["France","FR","+33"],["Gabon","GA","+241"],
  ["Géorgie","GE","+995"],["Ghana","GH","+233"],["Grèce","GR","+30"],
  ["Guatemala","GT","+502"],["Guinée","GN","+224"],["Haïti","HT","+509"],
  ["Honduras","HN","+504"],["Hongrie","HU","+36"],["Inde","IN","+91"],
  ["Indonésie","ID","+62"],["Irak","IQ","+964"],["Iran","IR","+98"],
  ["Irlande","IE","+353"],["Islande","IS","+354"],["Israël","IL","+972"],
  ["Italie","IT","+39"],["Jamaïque","JM","+1876"],["Japon","JP","+81"],
  ["Jordanie","JO","+962"],["Kazakhstan","KZ","+7"],["Kenya","KE","+254"],
  ["Koweït","KW","+965"],["Laos","LA","+856"],["Lettonie","LV","+371"],
  ["Liban","LB","+961"],["Libye","LY","+218"],["Lituanie","LT","+370"],
  ["Luxembourg","LU","+352"],["Madagascar","MG","+261"],["Malaisie","MY","+60"],
  ["Malawi","MW","+265"],["Mali","ML","+223"],["Malte","MT","+356"],
  ["Maroc","MA","+212"],["Maurice","MU","+230"],["Mauritanie","MR","+222"],
  ["Mexique","MX","+52"],["Moldavie","MD","+373"],["Monaco","MC","+377"],
  ["Mongolie","MN","+976"],["Monténégro","ME","+382"],["Mozambique","MZ","+258"],
  ["Namibie","NA","+264"],["Népal","NP","+977"],["Nicaragua","NI","+505"],
  ["Niger","NE","+227"],["Nigeria","NG","+234"],["Norvège","NO","+47"],
  ["Nouvelle-Zélande","NZ","+64"],["Oman","OM","+968"],["Ouganda","UG","+256"],
  ["Ouzbékistan","UZ","+998"],["Pakistan","PK","+92"],["Panama","PA","+507"],
  ["Paraguay","PY","+595"],["Pays-Bas","NL","+31"],["Pérou","PE","+51"],
  ["Philippines","PH","+63"],["Pologne","PL","+48"],["Portugal","PT","+351"],
  ["Qatar","QA","+974"],["République dominicaine","DO","+1809"],
  ["République tchèque","CZ","+420"],["Roumanie","RO","+40"],
  ["Royaume-Uni","GB","+44"],["Russie","RU","+7"],["Rwanda","RW","+250"],
  ["Sénégal","SN","+221"],["Serbie","RS","+381"],["Singapour","SG","+65"],
  ["Slovaquie","SK","+421"],["Slovénie","SI","+386"],["Somalie","SO","+252"],
  ["Soudan","SD","+249"],["Sri Lanka","LK","+94"],["Suède","SE","+46"],
  ["Suisse","CH","+41"],["Syrie","SY","+963"],["Tadjikistan","TJ","+992"],
  ["Tanzanie","TZ","+255"],["Tchad","TD","+235"],["Thaïlande","TH","+66"],
  ["Togo","TG","+228"],["Tunisie","TN","+216"],["Turquie","TR","+90"],
  ["Ukraine","UA","+380"],["Uruguay","UY","+598"],["Venezuela","VE","+58"],
  ["Vietnam","VN","+84"],["Yémen","YE","+967"],["Zambie","ZM","+260"],
  ["Zimbabwe","ZW","+263"]
];

const flag = code => `<img src="https://flagcdn.com/24x18/${code.toLowerCase()}.png" style="width:24px;height:18px;border-radius:2px;object-fit:cover;" />`;

const trigger = document.getElementById('trigger');
const panel   = document.getElementById('panel');
const search  = document.getElementById('search');
const list    = document.getElementById('list');
const flagDisplay = document.getElementById('flag-display');
const dialDisplay = document.getElementById('dial-display');

let selected = countries.find(c => c[1] === 'FR');

function render(query) {
  const q = query.toLowerCase();
  const filtered = countries.filter(([name, code, dial]) =>
    name.toLowerCase().includes(q) || dial.includes(q)
  );
  if (!filtered.length) {
    list.innerHTML = '<div class="dd-empty">Aucun résultat</div>';
    return;
  }
  list.innerHTML = filtered.map(([name, code, dial]) =>
    `<div class="dd-item${selected[1] === code ? ' selected' : ''}" data-code="${code}">
      <span>${flag(code)}</span>
      <span>${name}</span>
      <span class="dd-dial">${dial}</span>
    </div>`
  ).join('');
  list.querySelectorAll('.dd-item').forEach(item => {
    item.addEventListener('click', () => {
      selected = countries.find(c => c[1] === item.dataset.code);
      flagDisplay.innerHTML = flag(selected[1]);
      dialDisplay.textContent = selected[2];
      // Mettre a jour le champ hidden pour que PHP recoit le bon indicatif
      const hiddenIndicatif = document.querySelector('input[name="indicatif"]');
      if (hiddenIndicatif) hiddenIndicatif.value = selected[2];
      closePanel();
      render(search.value);
    });
  });
}

function openPanel() {
  panel.classList.add('open');
  trigger.classList.add('open');
  search.value = '';
  render('');
  setTimeout(() => {
    search.focus();
    const sel = list.querySelector('.selected');
    if (sel) sel.scrollIntoView({ block: 'nearest' });
  }, 50);
}

function closePanel() {
  panel.classList.remove('open');
  trigger.classList.remove('open');
}

trigger.addEventListener('click', () =>
  panel.classList.contains('open') ? closePanel() : openPanel()
);
search.addEventListener('input', () => render(search.value));
document.addEventListener('click', e => {
  if (!document.querySelector('.dd-wrap').contains(e.target)) closePanel();
});
