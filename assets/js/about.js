(function () {
  "use strict";

  const sliderSelector = "[data-about-gallery-slider]";
  const brandsSliderSelector = ".about-brands-slider";

  function syncArrowVisibility(swiper) {
    const slider = swiper && swiper.el ? swiper.el : null;

    if (!slider) {
      return;
    }

    const frame = slider.closest(".about-gallery-frame") || slider;
    const leftArrow = frame.querySelector(".about-gallery-arrow--prev");
    const rightArrow = frame.querySelector(".about-gallery-arrow--next");
    const isLocked = Boolean(swiper.isLocked);

    if (leftArrow) {
      const isDisabled = isLocked || swiper.isEnd;
      leftArrow.hidden = isDisabled;
      leftArrow.disabled = isDisabled;
      leftArrow.setAttribute("aria-hidden", isDisabled ? "true" : "false");
    }

    if (rightArrow) {
      const isDisabled = isLocked || swiper.isBeginning;
      rightArrow.hidden = isDisabled;
      rightArrow.disabled = isDisabled;
      rightArrow.setAttribute("aria-hidden", isDisabled ? "true" : "false");
    }
  }

  function initializeAboutGallery(slider) {
    if (typeof window.Swiper !== "function") {
      return;
    }

    if (!slider || slider.dataset.aboutGalleryInitialized === "true") {
      return;
    }

    const frame = slider.closest(".about-gallery-frame") || slider;
    const leftArrow = frame.querySelector(".about-gallery-arrow--prev");
    const rightArrow = frame.querySelector(".about-gallery-arrow--next");

    if (!slider.querySelector(".swiper-wrapper")) {
      return;
    }

    let swiper;

    try {
      swiper = new window.Swiper(slider, {
        slidesPerView: "auto",
        slidesPerGroup: 1,
        spaceBetween: 30,
        watchOverflow: true,
        loop: false,
        rewind: false,
        centeredSlides: false,
        centerInsufficientSlides: false,
        observer: true,
        observeParents: true,
        navigation: {
          nextEl: leftArrow,
          prevEl: rightArrow,
        },
        on: {
          init: syncArrowVisibility,
          slideChange: syncArrowVisibility,
          transitionEnd: syncArrowVisibility,
          resize: syncArrowVisibility,
          breakpoint: syncArrowVisibility,
          lock: syncArrowVisibility,
          unlock: syncArrowVisibility,
        },
      });
    } catch (error) {
      return;
    }

    slider.dataset.aboutGalleryInitialized = "true";
    slider.aboutGalleryInstance = swiper;
    syncArrowVisibility(swiper);
  }

  function initializeAboutGalleries(root) {
    const scope = root && root.querySelectorAll ? root : document;

    if (scope.matches && scope.matches(sliderSelector)) {
      initializeAboutGallery(scope);
      return;
    }

    scope.querySelectorAll(sliderSelector).forEach(initializeAboutGallery);
  }

  function syncAboutBrandsState(swiper) {
    const slider = swiper && swiper.el ? swiper.el : null;

    if (!slider) {
      return;
    }

    const section = slider.closest(".about-brands-section") || document;
    const nextButton = section.querySelector(".about-brands-slider__next");

    if (nextButton) {
      const isHidden = swiper.activeIndex === 0;
      nextButton.classList.toggle("is-hidden", isHidden);
      nextButton.disabled = isHidden;
      nextButton.setAttribute("aria-hidden", isHidden ? "true" : "false");
    }
  }

  function initializeAboutBrandsSlider(slider) {
    if (typeof window.Swiper !== "function") {
      return;
    }

    if (!slider || slider.dataset.aboutBrandsInitialized === "true") {
      return;
    }

    const section = slider.closest(".about-brands-section");

    if (!section || !slider.querySelector(".swiper-wrapper")) {
      return;
    }

    const previousButton = section.querySelector(".about-brands-slider__prev");
    const nextButton = section.querySelector(".about-brands-slider__next");

    let swiper;

    try {
      swiper = new window.Swiper(slider, {
        slidesPerView: "auto",
        spaceBetween: 68,
        loop: false,
        watchOverflow: true,
        navigation: {
          nextEl: previousButton,
          prevEl: nextButton,
        },
        breakpoints: {
          320: { slidesPerView: 3, spaceBetween: 16 },
          768: { slidesPerView: 3, spaceBetween: 24 },
          1024: { slidesPerView: "auto", spaceBetween: 68 },
        },
      });
    } catch (error) {
      return;
    }

    slider.dataset.aboutBrandsInitialized = "true";
    slider.aboutBrandsInstance = swiper;

    const sync = function () {
      syncAboutBrandsState(swiper);
    };

    swiper.on("slideChange", sync);
    swiper.on("transitionEnd", sync);
    swiper.on("update", sync);
    swiper.on("resize", sync);
    sync();
  }

  function initializeAboutBrandsSliders(root) {
    const scope = root && root.querySelectorAll ? root : document;

    if (scope.matches && scope.matches(brandsSliderSelector)) {
      initializeAboutBrandsSlider(scope);
      return;
    }

    scope.querySelectorAll(brandsSliderSelector).forEach(initializeAboutBrandsSlider);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      initializeAboutGalleries(document);
      initializeAboutBrandsSliders(document);
    });
  } else {
    initializeAboutGalleries(document);
    initializeAboutBrandsSliders(document);
  }
})();
