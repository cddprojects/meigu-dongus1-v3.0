/**
 * The Daily Watchlist — Global Header & Footer
 * Include on every page. Place:
 *   <div id="g-nav"></div>
 *   <div id="g-footer"></div>
 * then load this script near the end of <body>.
 */
(function () {
  'use strict';

  var WA_HREF = "/f3/api/go-whatsapp.php";

  var path = (location.pathname || '/').replace(/\/+$/, '') || '/';
  var isHome = path === '/' || /(?:^|\/)index\.html$/.test(path);
  function href(hash) {
    // Prefer clean root URLs when live: /#section
    if (!hash || hash === '#') return isHome ? '/' : '/';
    return isHome ? hash : ('/' + hash);
  }
  function pageHref(slug) {
    // Relative .html works in local preview. Live hosts 301 these to clean URLs.
    return slug + '.html';
  }
  var isThankYou = /(?:^|\/)thank-you(?:\.html)?$/.test(path);

  var WA_ICON = '<svg class="wa-icon-sm" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.117 1.524 5.845L0 24l6.347-1.524A11.937 11.937 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.907 0-3.694-.505-5.23-1.384l-.374-.222-3.878.931.931-3.791-.245-.389A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>';

  var SHARED_CSS = '\
    :root {\
      --text: #172033;\
      --text-2: #5D6678;\
      --border: #E2E8F0;\
      --blue: #2563EB;\
      --blue-dark: #1E3A5F;\
      --blue-light: #EFF6FF;\
      --wa: #25D366;\
      --wa-dark: #128C7E;\
      --radius-sm: 6px;\
      --radius: 12px;\
    }\
    body.g-has-nav { padding-top: 64px; }\
    nav.nav {\
      position: fixed; top: 0; left: 0; right: 0; z-index: 1000;\
      background: rgba(255,255,255,0.95); backdrop-filter: blur(16px) saturate(180%);\
      -webkit-backdrop-filter: blur(16px) saturate(180%);\
      border-bottom: 1px solid var(--border); height: 64px;\
    }\
    .nav-inner {\
      max-width: 1200px; margin: 0 auto; padding: 0 5%; height: 64px;\
      display: flex; align-items: center; justify-content: space-between;\
    }\
    .nav-brand {\
      display: flex; align-items: center; gap: 10px; text-decoration: none;\
      font-family: "Lora", Georgia, serif; font-size: 1.05rem; font-weight: 700; color: var(--text);\
    }\
    .nav-links { display: flex; align-items: center; gap: 24px; }\
    .nav-links a {\
      font-size: 1rem; font-weight: 500; color: var(--text-2); text-decoration: none;\
      transition: color 0.15s; padding: 4px 0;\
    }\
    .nav-links a:hover { color: var(--blue); }\
    .nav-links a:focus-visible { outline: 2px solid var(--blue); outline-offset: 3px; border-radius: 3px; }\
    .nav-right { display: flex; align-items: center; gap: 12px; }\
    .nav .btn {\
      display: inline-flex; align-items: center; gap: 8px;\
      padding: 10px 18px; border-radius: var(--radius); font-size: 1rem; font-weight: 600;\
      text-decoration: none; cursor: pointer; border: none; transition: all 0.18s ease;\
      white-space: nowrap; font-family: inherit;\
    }\
    .nav .btn-wa {\
      background: var(--wa); color: #fff; box-shadow: 0 4px 14px rgba(37,211,102,0.3); padding:12px 16px;\
    }\
    .nav .btn-wa:hover { background: var(--wa-dark); transform: translateY(-1px); }\
    .nav .wa-icon-sm { width: 15px; height: 15px; flex-shrink: 0; }\
    .nav-hamburger {\
      display: none; background: none; border: 1.5px solid var(--border); border-radius: var(--radius-sm);\
      padding: 8px; cursor: pointer; color: var(--text); line-height: 0;\
      transition: border-color 0.15s;\
    }\
    .nav-hamburger:hover { border-color: var(--blue); color: var(--blue); }\
    .nav-hamburger svg { width: 18px; height: 18px; }\
    .nav-hamburger[aria-expanded="true"] .icon-open { display: none; }\
    .nav-hamburger[aria-expanded="false"] .icon-close { display: none; }\
    .nav-mobile {\
      display: none; position: fixed; inset: 64px 0 0; background: rgba(255,255,255,0.98);\
      backdrop-filter: blur(16px); z-index: 999; padding: 28px 5%;\
      flex-direction: column; gap: 6px; border-top: 1px solid var(--border);\
      overflow-y: auto;\
    }\
    .nav-mobile.open { display: flex; }\
    .nav-mobile a {\
      font-size: 1rem; font-weight: 600; color: var(--text); text-decoration: none;\
      padding: 12px 0; border-bottom: 1px solid var(--border);\
      transition: color 0.15s;\
    }\
    .nav-mobile a:hover { color: var(--blue); }\
    @media (max-width: 860px) {\
      .nav-links { display: none; }\
      .nav-hamburger { display: flex; align-items: center; justify-content: center; }\
    }\
    footer.site-footer { background: var(--blue-dark); padding: 56px 0 36px; }\
    .footer-grid { display: grid; grid-template-columns: 1.5fr 1fr 1fr; gap: 36px; max-width: 1200px; margin: 0 auto 44px; padding: 0 5%; }\
    .footer-brand { font-family: "Lora", Georgia, serif; font-size: 1rem; font-weight: 700; color: #fff; margin-bottom: 8px; display:flex; align-items: center; gap: 12px;}\
    .footer-brand-desc { font-size: 0.875rem; color: rgba(255,255,255,0.68); line-height: 1.7; max-width: 240px; }\
    .footer-col h5 { font-size: 0.75rem; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: rgba(255,255,255,0.62); margin-bottom: 14px; }\
    .footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 9px; margin: 0; padding: 0; }\
    .footer-col a { font-size: 0.875rem; color: rgba(255,255,255,0.72); text-decoration: none; transition: color 0.15s; }\
    .footer-col a:hover { color: #fff; }\
    .footer-divider { max-width: 1200px; margin: 0 auto 24px; padding: 0 5%; border: none; border-top: 1px solid rgba(255,255,255,0.14); }\
    .footer-bottom { max-width: 1200px; margin: 0 auto; padding: 0 5%; font-size: 0.875rem; color: rgba(255,255,255,0.55); line-height: 1.75; }\
    .footer-bottom a { color: rgba(255,255,255,0.7); text-decoration: none; }\
    .footer-bottom a:hover { color: #fff; }\
    @media (max-width: 860px) { .footer-grid { grid-template-columns: 1fr 1fr; } }\
    @media (max-width: 540px)  { .footer-grid { grid-template-columns: 1fr; } }\
  ';

  function navHtml() {
    return ''
      + '<nav class="nav" role="navigation" aria-label="Main navigation">'
      +   '<div class="nav-inner">'
      +     '<a href="' + href('#') + '" class="nav-brand" aria-label="The Daily Watchlist home">'
      +       '<img src="favicon.png" alt="" height="32" width="32" loading="eager" />'
      +       '<span style="font-family:\'Lora\',serif;font-weight:700;font-size:1rem;color:var(--text);">'
      +          'The Daily Watchlist'
      +       '</span>'
      +     '</a>'
      +     '<div class="nav-links" role="list">'
      +       '<a href="' + href('#watchlist') + '" role="listitem">Today\'s Brief</a>'
      +       '<a href="' + href('#how-it-works') + '" role="listitem">How It Works</a>'
      +       '<a href="' + href('#scoreboard') + '" role="listitem">Research Log</a>'
      +       '<a href="' + href('#faq') + '" role="listitem">FAQ</a>'
      +     '</div>'
      +     '<div class="nav-right">'
      +       '<a class="btn btn-wa btn-sm" href="' + WA_HREF + '" href="/f3/api/go-whatsapp.php" target="_blank" rel="noopener noreferrer">'
      +         WA_ICON + ' Get the Brief'
      +       '</a>'
      +       '<button class="nav-hamburger" id="mobileMenuBtn" type="button"'
      +         ' aria-expanded="false" aria-controls="mobileMenu" aria-label="Open navigation menu">'
      +         '<svg class="icon-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>'
      +         '<svg class="icon-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'
      +       '</button>'
      +     '</div>'
      +   '</div>'
      + '</nav>'
      + '<div class="nav-mobile" id="mobileMenu" role="dialog" aria-label="Navigation menu" aria-modal="false">'
      +   '<a href="' + href('#watchlist') + '">Today\'s Brief</a>'
      +   '<a href="' + href('#how-it-works') + '">How It Works</a>'
      +   '<a href="' + href('#scoreboard') + '">Research Log</a>'
      +   '<a href="' + href('#faq') + '">FAQ</a>'
      +   '<a href="' + href('#disclosure') + '">Full Disclosure</a>'
      + '</div>';
  }

  function footerHtml() {
    return ''
      + '<footer class="site-footer" role="contentinfo">'
      +   '<div class="footer-grid">'
      +     '<div>'
      +       '<div class="footer-brand"><img src="logo-light.png" alt="The Daily Watchlist" width="32" height="32" />The Daily Watchlist</div>'
      +       '<p class="footer-brand-desc">Daily pre-market research on US stocks. Published at 9:00 AM ET every trading day, before the 9:30 AM ET open.</p>'
      +     '</div>'
      +     '<nav class="footer-col" aria-label="Research">'
      +       '<h5>Research</h5>'
      +       '<ul>'
      +         '<li><a href="' + href('#watchlist') + '">Today\'s Brief</a></li>'
      +         '<li><a href="' + href('#scoreboard') + '">Research Log</a></li>'
      +         '<li><a href="' + href('#how-it-works') + '">Methodology</a></li>'
      +         '<li><a href="' + href('#faq') + '">FAQ</a></li>'
      +         '<li><a href="' + href('#disclosure') + '">Full Disclosure</a></li>'
      +       '</ul>'
      +     '</nav>'
      +     '<nav class="footer-col" aria-label="Legal">'
      +       '<h5>Legal</h5>'
      +       '<ul>'
      +         '<li><a href="' + pageHref('privacy-policy') + '">Privacy Policy</a></li>'
      +         '<li><a href="' + pageHref('terms-of-use') + '">Terms of Use</a></li>'
      +       '</ul>'
      +     '</nav>'
      +   '</div>'
      +   '<hr class="footer-divider" />'
      +   '<p class="footer-bottom">'
      +     '&copy; 2026 The Daily Watchlist, LLC · Delaware LLC · 1209 Orange Street, Wilmington, DE 19801 · '
      +     '<a href="mailto:hello@thedailywatchlist.com">hello@thedailywatchlist.com</a><br>'
      +     'Published for general educational and informational purposes only. Not investment advice. Not a registered investment adviser or broker-dealer. '
      +     'Investing involves risk, including possible loss of principal. Past performance does not guarantee future results. '
      +     'To unsubscribe, leave the WhatsApp group at any time.'
      +   '</p>'
      + '</footer>';
  }

  function injectStyles() {
    if (document.getElementById('g-shared-styles')) return;
    var style = document.createElement('style');
    style.id = 'g-shared-styles';
    style.textContent = SHARED_CSS;
    document.head.appendChild(style);
  }

  function closeMobile() {
    var btn = document.getElementById('mobileMenuBtn');
    var menu = document.getElementById('mobileMenu');
    if (!btn || !menu) return;
    btn.setAttribute('aria-expanded', 'false');
    menu.classList.remove('open');
    document.body.style.overflow = '';
  }

  window.closeMobile = closeMobile;

  function wireMobileMenu() {
    var btn = document.getElementById('mobileMenuBtn');
    var menu = document.getElementById('mobileMenu');
    if (!btn || !menu) return;

    btn.addEventListener('click', function () {
      var isOpen = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', String(!isOpen));
      menu.classList.toggle('open', !isOpen);
      document.body.style.overflow = isOpen ? '' : 'hidden';
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeMobile();
    });

    document.addEventListener('click', function (e) {
      if (menu.classList.contains('open') && !menu.contains(e.target) && !btn.contains(e.target)) {
        closeMobile();
      }
    });

    menu.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', closeMobile);
    });
  }

  function injectNav() {
    var placeholder = document.getElementById('g-nav');
    if (!placeholder) return;
    var wrap = document.createElement('div');
    wrap.innerHTML = navHtml();
    var nodes = Array.prototype.slice.call(wrap.childNodes);
    nodes.forEach(function (node) {
      placeholder.parentNode.insertBefore(node, placeholder);
    });
    placeholder.remove();
    document.body.classList.add('g-has-nav');
    wireMobileMenu();
  }

  function injectFooter() {
    var placeholder = document.getElementById('g-footer');
    if (!placeholder) return;
    var wrap = document.createElement('div');
    wrap.innerHTML = footerHtml();
    placeholder.replaceWith(wrap.firstElementChild);
  }

  function boot() {
    injectStyles();
    injectNav();
    injectFooter();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
}());
