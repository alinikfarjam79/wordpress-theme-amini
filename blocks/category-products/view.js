(function () {
  "use strict";
  function initSection(section) {
    if (section.dataset.categoryProductsReady === "true" || typeof window.Swiper !== "function") return;
    const slider = section.querySelector(".productSwiper");
    const box = slider && slider.querySelector(".swiper-wrapper");
    const tabs = Array.prototype.slice.call(section.querySelectorAll(".wc-tabs [data-cat]"));
    const wrap = section.querySelector(".product-slider-wrap");
    if (!slider || !box || !wrap || !tabs.length) return;
    section.dataset.categoryProductsReady = "true";
    const next = section.querySelector(".product-next");
    const prev = section.querySelector(".product-prev");
    const pagination = section.querySelector(".product-pagination");
    const status = section.querySelector(".category-products-status");
    const config = window.myThemeCategoryProducts || {};
    let requestController = null;
    let requestId = 0;
    const desktopGap = Number(wrap.dataset.desktopGap || 40);
    const tabletGap = Number(wrap.dataset.tabletGap || 32);
    const mobileGap = Number(wrap.dataset.mobileGap || 10);
    const swiper = new window.Swiper(slider, {
      slidesPerView: "auto", spaceBetween: desktopGap, observer: true, observeParents: true, watchOverflow: true,
      navigation: next && prev ? { prevEl: next, nextEl: prev } : undefined,
      pagination: pagination ? { el: pagination, clickable: true } : undefined,
      breakpoints: { 320: { spaceBetween: mobileGap }, 768: { spaceBetween: tabletGap }, 1024: { spaceBetween: desktopGap } }
    });
    function sync() {
      const locked = swiper.isLocked || swiper.slides.length <= 1;
      if (next) next.classList.toggle("is-hidden", locked || swiper.isBeginning);
      if (prev) prev.classList.toggle("is-hidden", locked || swiper.isEnd);
    }
    swiper.on("slideChange", sync); swiper.on("update", sync); sync();
    function activateTab(tab) {
      tabs.forEach(function (item) {
        const active = item === tab;
        item.classList.toggle("active", active);
        item.setAttribute("aria-selected", active ? "true" : "false");
        item.setAttribute("tabindex", active ? "0" : "-1");
      });
      slider.setAttribute("aria-labelledby", tab.id || "");
    }
    function selectTab(tab) {
      if (!tab || tab.classList.contains("active")) return;
      activateTab(tab);
      if (requestController) requestController.abort();
      requestController = typeof window.AbortController === "function" ? new window.AbortController() : null;
      const currentRequest = ++requestId;
      section.classList.add("is-loading");
      slider.setAttribute("aria-busy", "true");
      if (status) status.textContent = config.loading || "در حال دریافت محصولات…";
      const form = new FormData();
      form.append("action", "my_theme_category_products");
      form.append("nonce", config.nonce || "");
      form.append("cat", tab.dataset.cat);
      form.append("limit", wrap.dataset.productsCount || "12");
      const options = { method: "POST", body: form, credentials: "same-origin" };
      if (requestController) options.signal = requestController.signal;
      fetch(config.ajaxUrl || "/wp-admin/admin-ajax.php", options)
        .then(function (response) { if (!response.ok) throw new Error(); return response.json(); })
        .then(function (payload) {
          if (currentRequest !== requestId || !payload.success) throw new Error();
          box.innerHTML = payload.data.html;
          swiper.update();
          swiper.slideTo(0, 0);
          sync();
          if (status) status.textContent = "";
        })
        .catch(function (error) {
          if (error && error.name === "AbortError") return;
          if (currentRequest === requestId && status) status.textContent = config.error || "دریافت محصولات انجام نشد. دوباره تلاش کنید.";
        })
        .finally(function () {
          if (currentRequest !== requestId) return;
          section.classList.remove("is-loading");
          slider.removeAttribute("aria-busy");
        });
    }
    tabs.forEach(function (tab) {
      tab.addEventListener("click", function () {
        selectTab(tab);
      });
      tab.addEventListener("keydown", function (event) {
        if (!["ArrowLeft", "ArrowRight", "Home", "End"].includes(event.key)) return;
        event.preventDefault();
        let index = tabs.indexOf(tab);
        if (event.key === "Home") index = 0;
        else if (event.key === "End") index = tabs.length - 1;
        else index = (index + (event.key === "ArrowLeft" ? 1 : -1) + tabs.length) % tabs.length;
        tabs[index].focus();
        selectTab(tabs[index]);
      });
    });
  }
  function initAll(root) { (root || document).querySelectorAll(".wp-block-my-theme-category-products").forEach(initSection); }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", function () { initAll(document); }); else initAll(document);
})();
