(function () {
  "use strict";

  const gallerySelector = ".my-theme-product-gallery";
  const mobileGalleryQuery = window.matchMedia("(max-width: 1100px)");

  function setOptionalAttribute(element, name, value) {
    if (value) {
      element.setAttribute(name, value);
      return;
    }

    element.removeAttribute(name);
  }

  function initializeGallery(gallery) {
    if (gallery.dataset.galleryInitialized === "true") {
      return;
    }

    const mainLink = gallery.querySelector(
      ".my-theme-product-gallery__main-link"
    );
    const mainImage = gallery.querySelector(
      ".my-theme-product-gallery__main-image"
    );
    const controls = gallery.querySelector(
      ".my-theme-product-gallery__controls"
    );
    const viewport = gallery.querySelector(
      ".my-theme-product-gallery__viewport"
    );
    const track = gallery.querySelector(
      ".my-theme-product-gallery__track"
    );
    const thumbnails = Array.from(
      gallery.querySelectorAll(".my-theme-product-gallery__thumbnail")
    );
    const previousButton = gallery.querySelector(
      ".my-theme-product-gallery__nav--previous"
    );
    const nextButton = gallery.querySelector(
      ".my-theme-product-gallery__nav--next"
    );

    if (
      !mainLink ||
      !mainImage ||
      !controls ||
      !viewport ||
      !track ||
      !previousButton ||
      !nextButton ||
      thumbnails.length === 0
    ) {
      return;
    }

    gallery.dataset.galleryInitialized = "true";

    const slideStep = 72 + 14;
    const visibleSlides = Math.min(3, thumbnails.length);
    const maxIndex = Math.max(0, thumbnails.length - visibleSlides);
    const isRtl = window.getComputedStyle(gallery).direction === "rtl";
    let currentIndex = 0;
    let isPointerDragging = false;
    let pointerStartX = 0;
    let lastPointerX = 0;
    let suppressNextClick = false;

    function isMobileGallery() {
      return mobileGalleryQuery.matches;
    }

    function renderSliderState() {
      if (isMobileGallery()) {
        track.style.removeProperty("transform");
        controls.dataset.sliderState = "mobile";
        controls.style.setProperty("--visible-thumbnails", thumbnails.length);
        previousButton.hidden = true;
        nextButton.hidden = true;
        return;
      }

      const directionMultiplier = isRtl ? 1 : -1;
      const trackOffset = currentIndex * slideStep * directionMultiplier;

      track.style.transform = `translateX(${trackOffset}px)`;
      controls.dataset.sliderState = currentIndex === 0 ? "initial" : "shifted";
      controls.style.setProperty("--visible-thumbnails", visibleSlides);
      previousButton.hidden = currentIndex === 0;
      nextButton.hidden = currentIndex === maxIndex;
    }

    function bringThumbnailIntoView(thumbnail, thumbnailIndex) {
      if (isMobileGallery()) {
        thumbnail.scrollIntoView({
          behavior: "smooth",
          block: "nearest",
          inline: "nearest",
        });
        return;
      }

      if (thumbnailIndex < currentIndex) {
        currentIndex = thumbnailIndex;
      } else if (thumbnailIndex >= currentIndex + visibleSlides) {
        currentIndex = thumbnailIndex - visibleSlides + 1;
      }

      currentIndex = Math.max(0, Math.min(currentIndex, maxIndex));
    }

    function selectThumbnail(thumbnail, thumbnailIndex) {
      thumbnails.forEach(function (candidate) {
        const isActive = candidate === thumbnail;
        candidate.setAttribute("aria-pressed", isActive ? "true" : "false");
      });

      mainImage.src = thumbnail.dataset.mainSrc;
      mainImage.alt = thumbnail.dataset.imageAlt || "";

      setOptionalAttribute(
        mainImage,
        "srcset",
        thumbnail.dataset.mainSrcset
      );
      setOptionalAttribute(mainImage, "sizes", thumbnail.dataset.mainSizes);
      setOptionalAttribute(mainImage, "width", thumbnail.dataset.mainWidth);
      setOptionalAttribute(mainImage, "height", thumbnail.dataset.mainHeight);
      setOptionalAttribute(mainImage, "data-src", thumbnail.dataset.fullSrc);
      setOptionalAttribute(
        mainImage,
        "data-large_image",
        thumbnail.dataset.fullSrc
      );

      mainLink.href = thumbnail.dataset.fullSrc || thumbnail.dataset.mainSrc;

      bringThumbnailIntoView(thumbnail, thumbnailIndex);
      renderSliderState();
    }

    thumbnails.forEach(function (thumbnail, thumbnailIndex) {
      thumbnail.addEventListener("click", function () {
        if (suppressNextClick) {
          suppressNextClick = false;
          return;
        }

        selectThumbnail(thumbnail, thumbnailIndex);
      });
    });

    viewport.addEventListener("pointerdown", function (event) {
      if (!isMobileGallery() || viewport.scrollWidth <= viewport.clientWidth) {
        return;
      }

      isPointerDragging = true;
      pointerStartX = event.clientX;
      lastPointerX = event.clientX;
      viewport.dataset.galleryDragging = "true";

      if (viewport.setPointerCapture) {
        viewport.setPointerCapture(event.pointerId);
      }
    });

    viewport.addEventListener("pointermove", function (event) {
      if (!isPointerDragging || !isMobileGallery()) {
        return;
      }

      const deltaX = event.clientX - lastPointerX;

      if (Math.abs(event.clientX - pointerStartX) > 4) {
        suppressNextClick = true;
      }

      if (0 !== deltaX) {
        const previousScrollLeft = viewport.scrollLeft;

        viewport.scrollLeft = previousScrollLeft - deltaX;

        if (viewport.scrollLeft === previousScrollLeft) {
          viewport.scrollLeft = previousScrollLeft + deltaX;
        }
      }

      lastPointerX = event.clientX;
      event.preventDefault();
    });

    function stopPointerDrag(event) {
      if (!isPointerDragging) {
        return;
      }

      isPointerDragging = false;
      delete viewport.dataset.galleryDragging;

      if (event && viewport.releasePointerCapture) {
        viewport.releasePointerCapture(event.pointerId);
      }
    }

    viewport.addEventListener("pointerup", stopPointerDrag);
    viewport.addEventListener("pointercancel", stopPointerDrag);
    viewport.addEventListener("pointerleave", stopPointerDrag);

    nextButton.addEventListener("click", function () {
      currentIndex = Math.min(currentIndex + 1, maxIndex);
      renderSliderState();
    });

    previousButton.addEventListener("click", function () {
      currentIndex = Math.max(currentIndex - 1, 0);
      renderSliderState();
    });

    if (mobileGalleryQuery.addEventListener) {
      mobileGalleryQuery.addEventListener("change", renderSliderState);
    } else if (mobileGalleryQuery.addListener) {
      mobileGalleryQuery.addListener(renderSliderState);
    }

    renderSliderState();
  }

  function initializeGalleries(root) {
    if (root.matches && root.matches(gallerySelector)) {
      initializeGallery(root);
    }

    if (!root.querySelectorAll) {
      return;
    }

    root.querySelectorAll(gallerySelector).forEach(initializeGallery);
  }

  function boot() {
    initializeGalleries(document);

    const observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        mutation.addedNodes.forEach(function (node) {
          if (node.nodeType === Node.ELEMENT_NODE) {
            initializeGalleries(node);
          }
        });
      });
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true,
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot, { once: true });
  } else {
    boot();
  }
})();
