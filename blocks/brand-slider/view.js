(function () {
  "use strict";
  function init(block) {
    if (block.dataset.brandSliderReady === "true" || typeof window.Swiper !== "function") return;
    const wrap = block.querySelector(".brand-slider-wrap");
    const slider = block.querySelector(".brandSwiper");
    if (!wrap || !slider) return;
    block.dataset.brandSliderReady = "true";
    const next = wrap.querySelector(".brand-next");
    const prev = wrap.querySelector(".brand-prev");
    const itemCount = slider.querySelectorAll(".brand-item").length;
    if (itemCount < 2) {
      block.dataset.brandSliderReady = "true";
      return;
    }
    const desktopGap = Number(wrap.dataset.desktopGap || 68);
    const tabletGap = Number(wrap.dataset.tabletGap || 24);
    const mobileGap = Number(wrap.dataset.mobileGap || 16);
    const autoplay = wrap.dataset.autoplay === "true" && itemCount > 1 ? { delay: Number(wrap.dataset.delay || 3000), disableOnInteraction: false } : false;
    const canLoop = wrap.dataset.loop === "true" && itemCount > 3;
    const swiper = new window.Swiper(slider, {
      slidesPerView: "auto",
      spaceBetween: desktopGap,
      loop: canLoop,
      autoplay: autoplay,
      watchOverflow: true,
      centerInsufficientSlides: true,
      observer: true,
      observeParents: true,
      navigation: next && prev ? { nextEl: prev, prevEl: next } : undefined,
      breakpoints: {
        320: { slidesPerView: Math.min(3, itemCount), spaceBetween: mobileGap },
        768: { slidesPerView: Math.min(3, itemCount), spaceBetween: tabletGap },
        1024: { slidesPerView: "auto", spaceBetween: desktopGap }
      }
    });
    function sync() {
      const locked = swiper.isLocked || itemCount < 2;
      if (next) next.classList.toggle("is-hidden", locked || (!canLoop && swiper.isBeginning));
      if (prev) prev.classList.toggle("is-hidden", locked || (!canLoop && swiper.isEnd));
    }
    swiper.on("slideChange", sync); swiper.on("update", sync); swiper.on("resize", sync); sync();
  }
  function initAll(root) { (root || document).querySelectorAll(".wp-block-my-theme-brand-slider").forEach(init); }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", function () { initAll(document); }); else initAll(document);
})();
