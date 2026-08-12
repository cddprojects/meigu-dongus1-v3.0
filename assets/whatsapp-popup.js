(function () {
  "use strict";

  var root = document.querySelector("[data-hz-whatsapp]");
  if (!root) {
    var host = document.createElement("div");
    host.innerHTML = [
      '<div class="hz-whatsapp" data-hz-whatsapp>',
      '<button class="hz-whatsapp__trigger" type="button" aria-label="Chat with us on WhatsApp" aria-haspopup="dialog" data-hz-whatsapp-open>',
      '<span class="hz-whatsapp__trigger-icon" aria-hidden="true"><svg viewBox="0 0 32 32" focusable="false"><path d="M27.3 4.7A15.78 15.78 0 0 0 16.05 0C7.32 0 .22 7.1.22 15.83c0 2.79.73 5.51 2.11 7.91L.09 31.9l8.35-2.19a15.8 15.8 0 0 0 7.6 1.94h.01c8.73 0 15.83-7.1 15.83-15.83 0-4.23-1.63-8.2-4.58-11.12Zm-11.25 24.3h-.01a13.1 13.1 0 0 1-6.68-1.83l-.48-.28-4.95 1.3 1.32-4.83-.31-.5a13.11 13.11 0 0 1-2.01-6.98c0-7.24 5.89-13.13 13.13-13.13 3.5 0 6.8 1.37 9.28 3.85a13.04 13.04 0 0 1 3.84 9.28c-.01 7.23-5.89 13.12-13.13 13.12Zm7.2-9.84c-.4-.2-2.34-1.15-2.7-1.28-.36-.13-.63-.2-.89.2-.26.4-1.02 1.28-1.25 1.54-.23.26-.46.3-.86.1-.4-.2-1.67-.61-3.18-1.96a11.9 11.9 0 0 1-2.2-2.74c-.23-.4-.02-.61.17-.81.18-.18.4-.46.59-.69.2-.23.26-.4.4-.66.13-.26.07-.5-.03-.69-.1-.2-.89-2.14-1.22-2.93-.32-.77-.65-.67-.89-.68h-.76c-.26 0-.69.1-1.05.5-.36.4-1.38 1.35-1.38 3.29s1.41 3.81 1.61 4.07c.2.26 2.78 4.24 6.73 5.95.94.41 1.67.65 2.25.83.95.3 1.8.26 2.48.16.76-.11 2.34-.96 2.67-1.88.33-.92.33-1.71.23-1.88-.1-.16-.36-.26-.76-.46Z"/></svg></span>',
      '<span class="hz-whatsapp__trigger-label">WhatsApp us</span></button>',
      '<div class="hz-whatsapp__backdrop" aria-hidden="true" data-hz-whatsapp-backdrop>',
      '<section class="hz-whatsapp__dialog" role="dialog" aria-modal="true" aria-labelledby="hz-whatsapp-title" data-hz-whatsapp-dialog>',
      '<button class="hz-whatsapp__close" type="button" aria-label="Close WhatsApp chat" data-hz-whatsapp-close><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6 6 18"/></svg></button>',
      '<header class="hz-whatsapp__header"><span class="hz-whatsapp__header-icon" aria-hidden="true"><svg viewBox="0 0 32 32" focusable="false"><path d="M27.3 4.7A15.78 15.78 0 0 0 16.05 0C7.32 0 .22 7.1.22 15.83c0 2.79.73 5.51 2.11 7.91L.09 31.9l8.35-2.19a15.8 15.8 0 0 0 7.6 1.94h.01c8.73 0 15.83-7.1 15.83-15.83 0-4.23-1.63-8.2-4.58-11.12Zm-11.25 24.3h-.01a13.1 13.1 0 0 1-6.68-1.83l-.48-.28-4.95 1.3 1.32-4.83-.31-.5a13.11 13.11 0 0 1-2.01-6.98c0-7.24 5.89-13.13 13.13-13.13 3.5 0 6.8 1.37 9.28 3.85a13.04 13.04 0 0 1 3.84 9.28c-.01 7.23-5.89 13.12-13.13 13.12Zm7.2-9.84c-.4-.2-2.34-1.15-2.7-1.28-.36-.13-.63-.2-.89.2-.26.4-1.02 1.28-1.25 1.54-.23.26-.46.3-.86.1-.4-.2-1.67-.61-3.18-1.96a11.9 11.9 0 0 1-2.2-2.74c-.23-.4-.02-.61.17-.81.18-.18.4-.46.59-.69.2-.23.26-.4.4-.66.13-.26.07-.5-.03-.69-.1-.2-.89-2.14-1.22-2.93-.32-.77-.65-.67-.89-.68h-.76c-.26 0-.69.1-1.05.5-.36.4-1.38 1.35-1.38 3.29s1.41 3.81 1.61 4.07c.2.26 2.78 4.24 6.73 5.95.94.41 1.67.65 2.25.83.95.3 1.8.26 2.48.16.76-.11 2.34-.96 2.67-1.88.33-.92.33-1.71.23-1.88-.1-.16-.36-.26-.76-.46Z"/></svg></span>',
      '<div><h2 class="hz-whatsapp__title" id="hz-whatsapp-title">Get Your Free Daily Briefing</h2><p class="hz-whatsapp__intro">Delivered to WhatsApp · Before 9 AM ET · Every Trading Day</p></div></header>',
      '<div class="hz-whatsapp__form"><div data-cddform="meigu-dong-us10" data-origin="www.thedailywatchlist.com"></div></div>',
      '</section></div></div>'
    ].join("");
    root = host.firstElementChild;
    document.body.appendChild(root);
  }

  var trigger = root.querySelector("[data-hz-whatsapp-open]");
  var backdrop = root.querySelector("[data-hz-whatsapp-backdrop]");
  var dialog = root.querySelector("[data-hz-whatsapp-dialog]");
  var closeButton = root.querySelector("[data-hz-whatsapp-close]");
  var embed = root.querySelector("[data-cddform]");
  var returnFocusTarget = trigger;

  if (!trigger || !backdrop || !dialog || !closeButton) return;

  var focusableSelector = [
    "a[href]",
    "button:not([disabled])",
    "input:not([disabled])",
    "select:not([disabled])",
    "textarea:not([disabled])",
    "iframe",
    "[tabindex]:not([tabindex='-1'])"
  ].join(",");

  function ensureFormScript() {
    if (!embed || embed.querySelector("iframe")) return;
    if (document.querySelector("script[src*='chatfromforms.com/form-embed.js']")) return;
    var script = document.createElement("script");
    script.src = "https://www.chatfromforms.com/form-embed.js?v=0.1";
    script.defer = true;
    document.body.appendChild(script);
  }

  function openModal(opener) {
    if (opener && typeof opener.focus === "function") returnFocusTarget = opener;
    ensureFormScript();
    backdrop.setAttribute("aria-hidden", "false");
    document.body.classList.add("hz-whatsapp-modal-open");
    window.setTimeout(function () { closeButton.focus(); }, 0);
  }

  function closeModal() {
    if (backdrop.getAttribute("aria-hidden") === "true") return;
    backdrop.setAttribute("aria-hidden", "true");
    document.body.classList.remove("hz-whatsapp-modal-open");
    if (returnFocusTarget && document.contains(returnFocusTarget)) returnFocusTarget.focus();
    else trigger.focus();
  }

  function keepFocusInside(event) {
    if (event.key !== "Tab") return;
    var focusable = Array.prototype.slice.call(dialog.querySelectorAll(focusableSelector));
    if (!focusable.length) {
      event.preventDefault();
      closeButton.focus();
      return;
    }
    var first = focusable[0];
    var last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  }

  trigger.addEventListener("click", function () { openModal(trigger); });
  document.addEventListener("click", function (event) {
    var opener = event.target.closest && event.target.closest("[data-hz-whatsapp-cta]");
    if (opener) openModal(opener);
  });
  closeButton.addEventListener("click", closeModal);
  dialog.addEventListener("click", function (event) { event.stopPropagation(); });
  backdrop.addEventListener("click", closeModal);

  document.addEventListener("keydown", function (event) {
    if (backdrop.getAttribute("aria-hidden") !== "false") return;
    if (event.key === "Escape") {
      event.preventDefault();
      closeModal();
      return;
    }
    keepFocusInside(event);
  });

  document.querySelectorAll("[data-hz-whatsapp-cta]").forEach(function (button) {
    button.setAttribute("aria-haspopup", "dialog");
  });
  ensureFormScript();
}());