(function () {
  "use strict";

  function setOpen(item, open) {
    const panel = item.querySelector(":scope > .theme-item-mega-menu");
    const toggle = item.querySelector(":scope > .theme-item-mega-menu__mobile-toggle");
    item.classList.toggle("is-mega-menu-open", open);
    if (panel) panel.setAttribute("aria-hidden", open ? "false" : "true");
    if (toggle) toggle.setAttribute("aria-expanded", open ? "true" : "false");
  }

  function initialize(item) {
    if (item.dataset.itemMegaMenuReady === "true") return;
    item.dataset.itemMegaMenuReady = "true";

    const directLink = item.querySelector(":scope > .wp-block-navigation-item__content");
    if (!directLink) return;

    const toggle = document.createElement("button");
    toggle.className = "theme-item-mega-menu__mobile-toggle";
    toggle.type = "button";
    toggle.setAttribute("aria-expanded", "false");
    toggle.setAttribute("aria-label", "باز و بسته کردن مگامنو");
    toggle.innerHTML = '<span aria-hidden="true"></span>';
    directLink.insertAdjacentElement("afterend", toggle);

    toggle.addEventListener("click", function (event) {
      event.preventDefault();
      event.stopPropagation();
      const willOpen = !item.classList.contains("is-mega-menu-open");

      document.querySelectorAll(".has-custom-mega-menu.is-mega-menu-open").forEach(function (otherItem) {
        if (otherItem !== item) setOpen(otherItem, false);
      });
      setOpen(item, willOpen);
    });

    directLink.addEventListener("click", function (event) {
      if (!window.matchMedia("(max-width: 860px)").matches) return;
      const href = directLink.getAttribute("href");
      if (!href || href === "#") {
        event.preventDefault();
        toggle.click();
      }
    });

    item.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        setOpen(item, false);
        directLink.focus();
      }
    });
  }

  function initializeAll() {
    document.querySelectorAll(".site-header__nav .has-custom-mega-menu").forEach(initialize);
  }

  document.addEventListener("click", function (event) {
    document.querySelectorAll(".has-custom-mega-menu.is-mega-menu-open").forEach(function (item) {
      if (!item.contains(event.target)) setOpen(item, false);
    });
  });

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initializeAll);
  } else {
    initializeAll();
  }
})();
