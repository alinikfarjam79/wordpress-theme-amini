(function () {
  "use strict";

  const carouselSelector = "[data-product-carousel]";
  const legacyCarouselSelector = ".greenProductSwiper";

  function syncCarouselState(swiperEl, swiper, controls) {
    const greenSection = swiperEl.closest(".green-section");
    const relatedCarousel = swiperEl.closest(
      ".related-products-section__carousel"
    );

    if (greenSection) {
      greenSection.classList.toggle("is-slider-expanded", swiper.activeIndex > 0);
    }

    if (relatedCarousel) {
      const currentSlidesPerView = swiper.params
        ? swiper.params.slidesPerView
        : swiperEl.dataset.slidesPerView;
      const visibleCount =
        typeof currentSlidesPerView === "number"
          ? currentSlidesPerView
          : Number(swiperEl.dataset.slidesPerView) || 0;
      const realSlideCount = Array.from(swiper.slides).filter(function (slide) {
        return slide.dataset.carouselSpacer !== "true";
      }).length;
      const lockNavigation =
        Boolean(visibleCount) && realSlideCount <= visibleCount;

      if (controls.prevButton) {
        const disabled = lockNavigation || swiper.isBeginning;
        controls.prevButton.disabled = disabled;
        controls.prevButton.classList.toggle("is-disabled", disabled);
      }

      if (controls.nextButton) {
        const disabled = lockNavigation || swiper.isEnd;
        controls.nextButton.disabled = disabled;
        controls.nextButton.classList.toggle("is-disabled", disabled);
      }
    }
  }

  function initializeProductCarousel(root) {
    if (typeof window.Swiper !== "function") {
      return;
    }

    const isRootCarousel = root.matches(carouselSelector);
    const swiperEl = isRootCarousel
      ? root.querySelector(".greenProductSwiper")
      : root;
    const wrapper = swiperEl ? swiperEl.querySelector(".swiper-wrapper") : null;
    const slides = wrapper ? wrapper.querySelectorAll(".swiper-slide") : [];

    if (!swiperEl || !wrapper || slides.length === 0) {
      return;
    }

    if (
      (isRootCarousel && root.dataset.carouselInitialized === "true") ||
      swiperEl.dataset.greenSwiperInitialized === "true"
    ) {
      return;
    }

    const controlsRoot = isRootCarousel
      ? root
      : swiperEl.closest(".green-wrapper, .related-products-section__carousel") ||
        document;
    const controls = {
      prevButton: controlsRoot.querySelector(".green-prev"),
      nextButton: controlsRoot.querySelector(".green-next"),
    };
    const isRelatedProductsCarousel =
      isRootCarousel ||
      swiperEl.matches("[data-related-products-carousel]");
    const slidesPerView = swiperEl.dataset.slidesPerView
      ? Number(swiperEl.dataset.slidesPerView)
      : "auto";
    const spaceBetween = swiperEl.dataset.spaceBetween
      ? Number(swiperEl.dataset.spaceBetween)
      : 15;
    const baseSlidesPerView = isRelatedProductsCarousel ? "auto" : slidesPerView;
    const baseSpaceBetween = isRelatedProductsCarousel ? 8 : spaceBetween;
    const baseSlidesOffset = isRelatedProductsCarousel ? 0 : 12;
    const breakpoints = isRelatedProductsCarousel
      ? {
          0: {
            slidesPerView: "auto",
            slidesPerGroup: 1,
            spaceBetween: 8,
            slidesOffsetBefore: 0,
            slidesOffsetAfter: 0,
          },
          500: {
            slidesPerView: 2,
            slidesPerGroup: 1,
            spaceBetween: spaceBetween,
            slidesOffsetBefore: 0,
            slidesOffsetAfter: 0,
          },
          768: {
            slidesPerView: 3,
            slidesPerGroup: 1,
            spaceBetween: spaceBetween,
            slidesOffsetBefore: 0,
            slidesOffsetAfter: 0,
          },
          1101: {
            slidesPerView: slidesPerView,
            slidesPerGroup: 1,
            spaceBetween: spaceBetween,
            slidesOffsetBefore: 0,
            slidesOffsetAfter: 0,
          },
        }
      : {
          320: { spaceBetween: spaceBetween },
          640: { spaceBetween: spaceBetween },
          951: { spaceBetween: spaceBetween },
          1100: { spaceBetween: spaceBetween },
        };

    let swiper;

    try {
      swiper = new window.Swiper(swiperEl, {
        slidesPerView: baseSlidesPerView,
        slidesPerGroup: 1,
        spaceBetween: baseSpaceBetween,
        slidesOffsetBefore: baseSlidesOffset,
        slidesOffsetAfter: baseSlidesOffset,
        allowTouchMove: true,
        simulateTouch: true,
        cssMode: false,
        centeredSlides: false,
        centeredSlidesBounds: false,
        slidesPerGroupAuto: false,
        longSwipes: true,
        shortSwipes: true,
        longSwipesRatio: 0.2,
        longSwipesMs: 300,
        threshold: 5,
        followFinger: true,
        touchRatio: 1,
        touchAngle: 45,
        watchOverflow: true,
        freeMode: false,
        resistance: false,
        resistanceRatio: 0,
        loop: false,
        rewind: false,
        loopAddBlankSlides: false,
        centerInsufficientSlides: false,
        observer: true,
        observeParents: true,
        navigation: {
          nextEl: controls.nextButton,
          prevEl: controls.prevButton,
        },
        breakpoints: breakpoints,
      });
    } catch (error) {
      return;
    }

    if (isRootCarousel) {
      root.dataset.carouselInitialized = "true";
    }

    swiperEl.dataset.greenSwiperInitialized = "true";
    root.productCarouselInstance = swiper;

    const sync = function () {
      syncCarouselState(swiperEl, swiper, controls);
    };

    const updateLayout = function () {
      const currentIndex = swiper.activeIndex || 0;
      swiper.update();

      swiper.slideTo(Math.min(currentIndex, swiper.slides.length - 1), 0, false);
      sync();
    };

    swiper.on("slideChange", function () {
      sync();
    });
    swiper.on("transitionEnd", function () {
      sync();
    });
    swiper.on("touchEnd", function () {
      sync();
    });
    swiper.on("breakpoint", function () {
      swiper.slideTo(
        Math.min(swiper.activeIndex || 0, swiper.slides.length - 1),
        0,
        false
      );
      sync();
    });
    swiper.on("resize", function () {
      updateLayout();
    });
    updateLayout();
  }

  function initializeProductCarousels(root) {
    const scope = root && root.querySelectorAll ? root : document;

    if (scope.matches && scope.matches(carouselSelector)) {
      initializeProductCarousel(scope);
      return;
    }

    scope.querySelectorAll(carouselSelector).forEach(initializeProductCarousel);

    if (scope.matches && scope.matches(legacyCarouselSelector)) {
      initializeProductCarousel(scope);
    }

    scope
      .querySelectorAll(
        legacyCarouselSelector + ":not([data-related-products-carousel])"
      )
      .forEach(initializeProductCarousel);
  }

  window.myThemeInitializeProductCarousels = initializeProductCarousels;

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      initializeProductCarousels(document);
    });
  } else {
    initializeProductCarousels(document);
  }

  if (window.MutationObserver && document.body) {
    const observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        mutation.addedNodes.forEach(function (node) {
          if (node.nodeType === 1) {
            initializeProductCarousels(node);
          }
        });
      });
    });

    observer.observe(document.body, { childList: true, subtree: true });
  }
})();
