(function () {
  "use strict";

  const mobileQuery = window.matchMedia("(max-width: 768px)");
  let panelIndex = 0;

  function setPanelState(title, panel, open) {
    title.classList.toggle("is-open", open);
    title.setAttribute("aria-expanded", open ? "true" : "false");
    panel.hidden = !open;
  }

  function prepareSection(title) {
    if (title.dataset.footerAccordionReady === "true") {
      return;
    }

    const panel = document.createElement("div");
    const panelId = "footer-accordion-panel-" + (++panelIndex);
    let sibling = title.nextElementSibling;

    panel.className = "footer-accordion__panel";
    panel.id = panelId;

    while (sibling && sibling.tagName !== "H4") {
      const nextSibling = sibling.nextElementSibling;
      panel.appendChild(sibling);
      sibling = nextSibling;
    }

    title.after(panel);
    title.dataset.footerAccordionReady = "true";
    title.classList.add("footer-accordion__title");
    title.setAttribute("aria-controls", panelId);

    function updateMode() {
      if (mobileQuery.matches) {
        title.setAttribute("role", "button");
        title.setAttribute("tabindex", "0");
        setPanelState(title, panel, false);
      } else {
        title.removeAttribute("role");
        title.removeAttribute("tabindex");
        title.removeAttribute("aria-expanded");
        title.classList.remove("is-open");
        panel.hidden = false;
      }
    }

    title.addEventListener("click", function () {
      if (!mobileQuery.matches) {
        return;
      }

      setPanelState(
        title,
        panel,
        title.getAttribute("aria-expanded") !== "true"
      );
    });

    title.addEventListener("keydown", function (event) {
      if (
        mobileQuery.matches &&
        (event.key === "Enter" || event.key === " ")
      ) {
        event.preventDefault();
        title.click();
      }
    });

    if (typeof mobileQuery.addEventListener === "function") {
      mobileQuery.addEventListener("change", updateMode);
    } else {
      mobileQuery.addListener(updateMode);
    }

    updateMode();
  }

  function initializeFooterAccordions() {
    document
      .querySelectorAll(
        ".site-footer .footer-contact > h4, .site-footer .footer-links > h4"
      )
      .forEach(prepareSection);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initializeFooterAccordions);
  } else {
    initializeFooterAccordions();
  }
})();
