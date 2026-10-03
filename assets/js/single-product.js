(function () {
  "use strict";

  if (window.myThemeSingleProductActionsInitialized) {
    return;
  }

  window.myThemeSingleProductActionsInitialized = true;

  const attributesButtonSelector = ".single-product-attributes__button";
  const wishlistButtonSelector = ".single-product-wishlist-button";
  const nativeShareButtonSelector = ".single-product-share-link--native";
  const copyLinkButtonSelector = ".single-product-share-link--copy";
  const bulkOrderButtonSelector = ".single-product-purchase-card__action--bulk";
  const shippingDetailsButtonSelector =
    ".single-product-purchase-card__shipping-details";
  const productTabsSelector = "[data-product-tabs]";
  const productTabSelector = ".product-details-tab";
  const productTabPanelSelector = ".product-details-tab-panel";
  const productTabListSelector = ".product-details-tabs__list";
  const mobileProductTabsQuery = window.matchMedia("(max-width: 1100px)");
  const productDetailsSectionSelectors = {
    description: '[data-product-details-section="description"]',
    specifications: '[data-product-details-section="specifications"]',
    reviews: '[data-product-details-section="reviews"]',
    brand: '[data-product-details-section="brand"]',
  };
  const productDetailsAnchorSelectors = {
    description: '[data-product-details-anchor="description"]',
    specifications: '[data-product-details-anchor="specifications"]',
    reviews: '[data-product-details-anchor="reviews"]',
    brand: '[data-product-details-anchor="brand"]',
  };
  const mobileSpecificationsToggleSelector =
    "[data-mobile-specifications-toggle]";
  const mobileSpecificationsListSelector =
    "[data-mobile-specifications-list]";
  const reviewVoteButtonSelector = "[data-review-vote]";
  const reviewFormSelector = "#review_form form.product-review-form, #review_form form";
  const reviewRatingStarSelector = "[data-review-rating-star]";
  const reviewRatingSelectSelector = ".product-review-rating-select";
  const expandableContentSelector =
    "[data-expandable-description], [data-expandable-brand]";

  function updateExpandableContent(root) {
    if (!root) {
      return;
    }

    const content = root.querySelector(
      ".product-description-expandable__content, .product-brand-expandable__content"
    );
    const toggle = root.querySelector(
      ".product-description-expandable__toggle, .product-brand-expandable__toggle"
    );

    if (!content || !toggle) {
      return;
    }

    if (root.matches("[data-expandable-brand]") && !isMobileProductTabs()) {
      toggle.hidden = true;
      return;
    }

    if (content.classList.contains("is-expanded")) {
      toggle.hidden = true;
      return;
    }

    if (content.offsetParent === null) {
      return;
    }

    toggle.hidden = !(content.scrollHeight > content.clientHeight + 1);
  }

  function updateExpandableContents(scope) {
    const root = scope || document;

    root
      .querySelectorAll(expandableContentSelector)
      .forEach(updateExpandableContent);
  }

  function updateExpandableDescriptions(scope) {
    updateExpandableContents(scope);
  }

  function getOriginalAttribute(element, attributeName) {
    if (!element) {
      return "";
    }

    const dataKey =
      "original" +
      attributeName.charAt(0).toUpperCase() +
      attributeName.slice(1);

    if (!Object.prototype.hasOwnProperty.call(element.dataset, dataKey)) {
      element.dataset[dataKey] = element.getAttribute(attributeName) || "";
    }

    return element.dataset[dataKey];
  }

  function restoreOriginalAttribute(element, attributeName) {
    const originalValue = getOriginalAttribute(element, attributeName);

    if ("" === originalValue) {
      element.removeAttribute(attributeName);
      return;
    }

    element.setAttribute(attributeName, originalValue);
  }

  function isMobileProductTabs() {
    return mobileProductTabsQuery.matches;
  }

  function syncProductTabsPresentation(root) {
    if (!root) {
      return;
    }

    const isMobile = isMobileProductTabs();
    const tabList = root.querySelector(productTabListSelector);
    const tabs = Array.from(root.querySelectorAll(productTabSelector));
    const panels = Array.from(root.querySelectorAll(productTabPanelSelector));

    if (tabList) {
      getOriginalAttribute(tabList, "role");

      if (isMobile) {
        tabList.removeAttribute("role");
      } else {
        restoreOriginalAttribute(tabList, "role");
      }
    }

    tabs.forEach(function (tab) {
      getOriginalAttribute(tab, "role");

      if (isMobile) {
        tab.removeAttribute("role");
        tab.removeAttribute("aria-selected");
        tab.tabIndex = 0;
        return;
      }

      restoreOriginalAttribute(tab, "role");
    });

    panels.forEach(function (panel) {
      getOriginalAttribute(panel, "role");

      if (isMobile) {
        panel.hidden = false;
        panel.removeAttribute("role");
        return;
      }

      restoreOriginalAttribute(panel, "role");
    });

    if (!isMobile) {
      const activeTab =
        root.querySelector(productTabSelector + ".is-active") || tabs[0];

      activateProductTab(root, activeTab, false);
    }
  }

  function syncAllProductTabsPresentation() {
    document.querySelectorAll(productTabsSelector).forEach(function (root) {
      syncProductTabsPresentation(root);
    });
  }

  function initializeExpandableContents() {
    document
      .querySelectorAll(expandableContentSelector)
      .forEach(function (root) {
        if (root.dataset.expandableInitialized === "true") {
          updateExpandableContent(root);
          return;
        }

        root.dataset.expandableInitialized = "true";

        const content = root.querySelector(
          ".product-description-expandable__content, .product-brand-expandable__content"
        );
        const toggle = root.querySelector(
          ".product-description-expandable__toggle, .product-brand-expandable__toggle"
        );

        if (!content || !toggle) {
          return;
        }

        toggle.addEventListener("click", function () {
          content.classList.remove("is-collapsed");
          content.classList.add("is-expanded");
          toggle.setAttribute("aria-expanded", "true");
          toggle.hidden = true;
        });

        updateExpandableContent(root);
      });
  }

  function initializeExpandableDescriptions() {
    initializeExpandableContents();
  }

  function activateProductTab(root, tab, shouldFocus) {
    if (!root || !tab) {
      return;
    }

    if (isMobileProductTabs()) {
      syncProductTabsPresentation(root);
      return;
    }

    const targetId = tab.getAttribute("aria-controls");

    if (!targetId) {
      return;
    }

    const tabs = Array.from(root.querySelectorAll(productTabSelector));
    const panels = Array.from(root.querySelectorAll(productTabPanelSelector));

    tabs.forEach(function (currentTab) {
      const isActive = currentTab === tab;

      currentTab.classList.toggle("is-active", isActive);
      currentTab.setAttribute("aria-selected", isActive ? "true" : "false");
      currentTab.tabIndex = isActive ? 0 : -1;
    });

    panels.forEach(function (panel) {
      const isActive = panel.id === targetId;

      panel.classList.toggle("is-active", isActive);
      panel.hidden = !isActive;

      if (isActive) {
        updateExpandableDescriptions(panel);
      }
    });

    if (shouldFocus) {
      tab.focus({ preventScroll: true });
    }
  }

  function activateProductTabForPanel(panel) {
    if (!panel) {
      return;
    }

    const root = panel.closest(productTabsSelector);

    if (!root || !panel.id) {
      return;
    }

    const tab = root.querySelector(
      productTabSelector + '[aria-controls="' + panel.id + '"]'
    );

    activateProductTab(root, tab, false);
  }

  function getProductDetailsRoot() {
    return document.querySelector(productTabsSelector);
  }

  function getSingleProductRoot(control) {
    if (!control) {
      return null;
    }

    return (
      control.closest("[data-single-product-root]") ||
      control.closest(".single-product-page")
    );
  }

  function getProductDetailsKey(control) {
    return control ? control.dataset.productDetailsTarget || "" : "";
  }

  function getProductDetailsSection(root, key) {
    const selector = productDetailsSectionSelectors[key];

    if (!selector || !root) {
      return null;
    }

    const target = root.querySelector(selector);

    if (!target || target.offsetParent === null) {
      return null;
    }

    return target;
  }

  function getProductDetailsAnchor(productRoot, key) {
    const selector = productDetailsAnchorSelectors[key];

    if (!selector || !productRoot) {
      return null;
    }

    const anchors = productRoot.querySelectorAll(selector);

    if (anchors.length !== 1) {
      return null;
    }

    const anchor = anchors[0];

    if (!anchor || anchor.offsetParent === null) {
      return null;
    }

    return anchor;
  }

  function getScrollTarget(root, selector) {
    if (!selector) {
      return null;
    }

    const target =
      (root && root.querySelector(selector)) || document.querySelector(selector);

    if (!target || target.offsetParent === null) {
      return null;
    }

    return target;
  }

  function getCssPixelValue(element, propertyName) {
    if (!element || !propertyName) {
      return 0;
    }

    const value = window
      .getComputedStyle(element)
      .getPropertyValue(propertyName)
      .trim();

    if (!value) {
      return 0;
    }

    const parsed = Number.parseFloat(value);

    return Number.isFinite(parsed) ? parsed : 0;
  }

  function getMobileProductDetailsScrollOffset(root, target) {
    const documentElement = document.documentElement;
    const offsetSource = target || root || documentElement;
    const headerOffset =
      getCssPixelValue(offsetSource, "--site-header-offset") ||
      getCssPixelValue(documentElement, "--site-header-offset");
    const tabsOffset =
      getCssPixelValue(offsetSource, "--product-tabs-sticky-height") ||
      getCssPixelValue(root, "--product-tabs-sticky-height");

    return headerOffset + tabsOffset + 16;
  }

  function getVisibleFixedHeight(element) {
    if (!element) {
      return 0;
    }

    const styles = window.getComputedStyle(element);

    if (styles.display === "none" || styles.visibility === "hidden") {
      return 0;
    }

    if (styles.position !== "fixed" && styles.position !== "sticky") {
      return 0;
    }

    return element.getBoundingClientRect().height || 0;
  }

  function getProductDetailsScrollOffset(productRoot) {
    const adminBar = document.getElementById("wpadminbar");
    const mobileHeader =
      document.querySelector("[data-mobile-site-header]") ||
      document.querySelector(".site-header");
    const tabsList = productRoot
      ? productRoot.querySelector("[data-product-details-tabs-list]")
      : null;

    return (
      getVisibleFixedHeight(adminBar) +
      getVisibleFixedHeight(mobileHeader) +
      getVisibleFixedHeight(tabsList) +
      16
    );
  }

  function clearProgrammaticScrollWhenStable(productRoot) {
    if (!productRoot) {
      return;
    }

    let lastPosition = window.scrollY;
    let stableFrames = 0;

    const check = function () {
      const currentPosition = window.scrollY;

      if (Math.abs(currentPosition - lastPosition) <= 1) {
        stableFrames += 1;
      } else {
        stableFrames = 0;
        lastPosition = currentPosition;
      }

      if (stableFrames >= 4) {
        delete productRoot.dataset.programmaticScroll;
        return;
      }

      window.requestAnimationFrame(check);
    };

    window.requestAnimationFrame(check);
  }

  function scrollToProductDetailsAnchor(productRoot, anchor) {
    if (!productRoot || !anchor) {
      return null;
    }

    const offset = getProductDetailsScrollOffset(productRoot);
    const absoluteTop = anchor.getBoundingClientRect().top + window.scrollY;
    const destination = Math.max(0, absoluteTop - offset);

    productRoot.dataset.programmaticScroll = "true";

    window.scrollTo({
      top: destination,
      behavior: "smooth",
    });

    clearProgrammaticScrollWhenStable(productRoot);

    return anchor;
  }

  function scrollToProductDetailsSection(root, selector) {
    const target = getScrollTarget(root, selector);

    if (!target) {
      return null;
    }

    target.scrollIntoView({
      behavior: "smooth",
      block: "start",
    });

    return target;
  }

  function setActiveProductTab(root, tab) {
    if (!root || !tab) {
      return;
    }

    root.querySelectorAll(productTabSelector).forEach(function (item) {
      const isActive = item === tab;

      item.classList.toggle("is-active", isActive);

      if (isMobileProductTabs()) {
        return;
      }

      item.setAttribute("aria-selected", isActive ? "true" : "false");
      item.tabIndex = isActive ? 0 : -1;
    });
  }

  function setActiveMobileProductTab(productRoot, targetKey) {
    const tabsRoot = productRoot ? productRoot.querySelector(productTabsSelector) : null;

    if (!tabsRoot || !targetKey) {
      return;
    }

    tabsRoot.querySelectorAll(productTabSelector).forEach(function (control) {
      const isActive = getProductDetailsKey(control) === targetKey;

      control.classList.toggle("is-active", isActive);
      control.setAttribute("aria-selected", isActive ? "true" : "false");
      control.tabIndex = 0;
    });
  }

  function getProductTabByKey(root, key) {
    if (!root || !key) {
      return null;
    }

    return root.querySelector(
      productTabSelector + '[data-product-details-target="' + key + '"]'
    );
  }

  function activateMobileProductTab(root, tab) {
    if (!root || !tab) {
      return;
    }

    const key = getProductDetailsKey(tab);
    const productRoot = getSingleProductRoot(root);
    const anchor = getProductDetailsAnchor(productRoot, key);

    if (!anchor) {
      return;
    }

    setActiveMobileProductTab(productRoot, key);
    syncProductTabsPresentation(root);

    window.requestAnimationFrame(function () {
      updateExpandableDescriptions(anchor.parentElement);
      scrollToProductDetailsAnchor(productRoot, anchor);
    });
  }

  function getVisibleProductDetailsSectionKey(productRoot) {
    if (!productRoot) {
      return "";
    }

    const viewportTop = getProductDetailsScrollOffset(productRoot);
    const viewportBottom = window.innerHeight || document.documentElement.clientHeight;
    let activeKey = "";
    let activeVisibleHeight = 0;
    let nearestPassedKey = "";
    let nearestPassedDistance = Infinity;

    Object.keys(productDetailsSectionSelectors).forEach(function (key) {
      const section = productRoot.querySelector(productDetailsSectionSelectors[key]);

      if (!section || section.offsetParent === null) {
        return;
      }

      const rect = section.getBoundingClientRect();
      const distanceFromOffset = Math.abs(rect.top - viewportTop);
      const visibleHeight =
        Math.max(
          0,
          Math.min(rect.bottom, viewportBottom) - Math.max(rect.top, viewportTop)
        );

      if (rect.top <= viewportTop + 1 && rect.bottom > viewportTop) {
        if (distanceFromOffset < nearestPassedDistance) {
          nearestPassedKey = key;
          nearestPassedDistance = distanceFromOffset;
        }
      } else if (
        !nearestPassedKey &&
        rect.top > viewportTop &&
        distanceFromOffset < nearestPassedDistance
      ) {
        nearestPassedKey = key;
        nearestPassedDistance = distanceFromOffset;
      }

      if (visibleHeight > activeVisibleHeight) {
        activeVisibleHeight = visibleHeight;
        activeKey = key;
      }
    });

    return nearestPassedKey || activeKey;
  }

  function initializeMobileProductDetailsScrollSpy() {
    if (typeof window.IntersectionObserver !== "function") {
      return;
    }

    document.querySelectorAll(productTabsSelector).forEach(function (tabsRoot) {
      const productRoot = getSingleProductRoot(tabsRoot);

      if (
        !productRoot ||
        productRoot.dataset.mobileScrollSpyInit === "true"
      ) {
        return;
      }

      const sections = Array.from(
        productRoot.querySelectorAll("[data-product-details-section]")
      );

      if (sections.length === 0) {
        return;
      }

      const observer = new IntersectionObserver(
        function () {
          if (!isMobileProductTabs()) {
            return;
          }

          if (productRoot.dataset.programmaticScroll === "true") {
            return;
          }

          const activeKey = getVisibleProductDetailsSectionKey(productRoot);

          if (!activeKey) {
            return;
          }

          setActiveMobileProductTab(productRoot, activeKey);
        },
        {
          root: null,
          threshold: [0.2, 0.4, 0.6],
          rootMargin: "-20% 0px -55% 0px",
        }
      );

      sections.forEach(function (section) {
        observer.observe(section);
      });

      productRoot.dataset.mobileScrollSpyInit = "true";
      productRoot.productDetailsScrollSpyObserver = observer;
    });
  }

  function handleProductTabsKeydown(event, root) {
    const currentTab = event.target.closest(productTabSelector);

    if (!currentTab || !root.contains(currentTab)) {
      return;
    }

    const tabs = Array.from(root.querySelectorAll(productTabSelector));
    const currentIndex = tabs.indexOf(currentTab);

    if (-1 === currentIndex) {
      return;
    }

    let nextIndex = currentIndex;

    switch (event.key) {
      case "ArrowRight":
        nextIndex = currentIndex - 1;
        break;
      case "ArrowLeft":
        nextIndex = currentIndex + 1;
        break;
      case "Home":
        nextIndex = 0;
        break;
      case "End":
        nextIndex = tabs.length - 1;
        break;
      case "Enter":
      case " ":
      case "Spacebar":
        event.preventDefault();
        activateProductTab(root, currentTab, false);
        return;
      default:
        return;
    }

    event.preventDefault();

    if (nextIndex < 0) {
      nextIndex = tabs.length - 1;
    }

    if (nextIndex >= tabs.length) {
      nextIndex = 0;
    }

    activateProductTab(root, tabs[nextIndex], true);
  }

  function initializeProductTabs() {
    document.querySelectorAll(productTabsSelector).forEach(function (root) {
      if (root.dataset.tabsInitialized === "true") {
        syncProductTabsPresentation(root);
        return;
      }

      root.dataset.tabsInitialized = "true";
      syncProductTabsPresentation(root);

      root.addEventListener("click", function (event) {
        const tab = event.target.closest(productTabSelector);

        if (!tab || !root.contains(tab)) {
          return;
        }

        if (isMobileProductTabs()) {
          event.preventDefault();
          event.stopPropagation();
          event.stopImmediatePropagation();
          activateMobileProductTab(root, tab);
          return;
        }

        activateProductTab(root, tab, false);
      }, true);

      root.addEventListener("keydown", function (event) {
        handleProductTabsKeydown(event, root);
      });
    });
  }

  function refreshSingleProductInteractions() {
    initializeProductTabs();
    initializeMobileProductDetailsScrollSpy();
    syncAllProductTabsPresentation();
    initializeReviewForms();
    initializeExpandableDescriptions();
    updateExpandableDescriptions(document);
  }

  function copyToClipboard(text) {
    if (!text) {
      return;
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).catch(function () {});
      return;
    }

    const textarea = document.createElement("textarea");
    textarea.value = text;
    textarea.setAttribute("readonly", "");
    textarea.style.position = "fixed";
    textarea.style.opacity = "0";
    document.body.appendChild(textarea);
    textarea.select();

    try {
      document.execCommand("copy");
    } catch (error) {}

    document.body.removeChild(textarea);
  }

  function updateReviewVoteButtons(card, state, likesText, dislikesText) {
    if (!card) {
      return;
    }

    const likeButton = card.querySelector('[data-review-vote="like"]');
    const dislikeButton = card.querySelector('[data-review-vote="dislike"]');
    const likeCount = card.querySelector('[data-review-vote-count="like"]');
    const dislikeCount = card.querySelector(
      '[data-review-vote-count="dislike"]'
    );

    if (likeButton) {
      likeButton.classList.toggle("is-selected", "like" === state);
      likeButton.setAttribute("aria-pressed", "like" === state ? "true" : "false");
    }

    if (dislikeButton) {
      dislikeButton.classList.toggle("is-selected", "dislike" === state);
      dislikeButton.setAttribute(
        "aria-pressed",
        "dislike" === state ? "true" : "false"
      );
    }

    if (likeCount && typeof likesText === "string") {
      likeCount.textContent = likesText;
    }

    if (dislikeCount && typeof dislikesText === "string") {
      dislikeCount.textContent = dislikesText;
    }
  }

  function submitReviewVote(button) {
    if (!button || !window.myThemeSingleProduct) {
      return;
    }

    if (window.myThemeSingleProduct.isEditor) {
      return;
    }

    const commentId = button.dataset.commentId || "";
    const vote = button.dataset.reviewVote || "";

    if (!commentId || !vote) {
      return;
    }

    const formData = new FormData();
    formData.append("action", "my_theme_review_vote");
    formData.append("nonce", window.myThemeSingleProduct.nonce || "");
    formData.append("commentId", commentId);
    formData.append("vote", vote);

    button.disabled = true;

    fetch(window.myThemeSingleProduct.ajaxUrl || "", {
      method: "POST",
      credentials: "same-origin",
      body: formData,
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (response) {
        if (!response || !response.success || !response.data) {
          return;
        }

        updateReviewVoteButtons(
          button.closest(".product-review-card"),
          response.data.state,
          response.data.likesText,
          response.data.dislikesText
        );
      })
      .catch(function () {})
      .finally(function () {
        button.disabled = false;
      });
  }

  function updateReviewRatingStars(form, previewValue) {
    if (!form) {
      return;
    }

    const select = form.querySelector(reviewRatingSelectSelector);
    const selectedValue = select ? parseInt(select.value || "0", 10) : 0;
    const activeValue =
      typeof previewValue === "number" ? previewValue : selectedValue;

    form.querySelectorAll(reviewRatingStarSelector).forEach(function (star) {
      const value = parseInt(star.dataset.reviewRatingStar || "0", 10);
      const isActive = value <= activeValue;
      const isSelected = value <= selectedValue && selectedValue > 0;

      star.classList.toggle("is-active", isActive);
      star.classList.toggle("is-selected", isSelected);
      star.setAttribute("aria-pressed", value === selectedValue ? "true" : "false");
    });
  }

  function setReviewRating(form, value) {
    const select = form ? form.querySelector(reviewRatingSelectSelector) : null;

    if (!select) {
      return;
    }

    select.value = String(value);
    select.dispatchEvent(new Event("change", { bubbles: true }));
    updateReviewRatingStars(form);
  }

  function removeNativeWooCommerceStars(root) {
    if (!root) {
      return;
    }

    root
      .querySelectorAll(
        ".comment-form-rating > p.stars," +
          ".comment-form-rating .stars," +
          ":scope > p.stars," +
          "p.stars," +
          ".woocommerce-product-rating," +
          ".star-rating"
      )
      .forEach(function (element) {
        if (!element.classList.contains("product-review-rating-stars")) {
          element.remove();
        }
      });
  }

  function observeNativeWooCommerceStars(root) {
    if (
      !window.MutationObserver ||
      !root ||
      "true" === root.dataset.reviewNativeStarsObserver
    ) {
      return;
    }

    root.dataset.reviewNativeStarsObserver = "true";

    const observer = new MutationObserver(function () {
      removeNativeWooCommerceStars(root);

      if (!root.querySelector(".comment-form-rating p.stars, p.stars")) {
        observer.disconnect();
        root.dataset.reviewNativeStarsObserver = "done";
      }
    });

    observer.observe(root, {
      childList: true,
      subtree: true,
    });

    window.setTimeout(function () {
      removeNativeWooCommerceStars(root);
      observer.disconnect();
      root.dataset.reviewNativeStarsObserver = "done";
    }, 1500);
  }

  function initializeReviewForms() {
    document.querySelectorAll(reviewFormSelector).forEach(function (form) {
      form.classList.add("product-review-form");
      form.dataset.productReviewForm = "true";
      removeNativeWooCommerceStars(form);
      observeNativeWooCommerceStars(form);

      if (form.dataset.reviewFormInitialized === "true") {
        removeNativeWooCommerceStars(form);
        updateReviewRatingStars(form);
        return;
      }

      form.dataset.reviewFormInitialized = "true";

      form.querySelectorAll(reviewRatingStarSelector).forEach(function (star) {
        star.addEventListener("mouseenter", function () {
          updateReviewRatingStars(
            form,
            parseInt(star.dataset.reviewRatingStar || "0", 10)
          );
        });

        star.addEventListener("focus", function () {
          updateReviewRatingStars(
            form,
            parseInt(star.dataset.reviewRatingStar || "0", 10)
          );
        });

        star.addEventListener("click", function () {
          setReviewRating(
            form,
            parseInt(star.dataset.reviewRatingStar || "0", 10)
          );
        });

        star.addEventListener("keydown", function (event) {
          const currentValue = parseInt(star.dataset.reviewRatingStar || "0", 10);
          let nextValue = currentValue;

          if ("Enter" === event.key || " " === event.key || "Spacebar" === event.key) {
            event.preventDefault();
            setReviewRating(form, currentValue);
            return;
          }

          if ("ArrowLeft" === event.key || "ArrowUp" === event.key) {
            nextValue = Math.min(5, currentValue + 1);
          } else if ("ArrowRight" === event.key || "ArrowDown" === event.key) {
            nextValue = Math.max(1, currentValue - 1);
          } else {
            return;
          }

          event.preventDefault();
          setReviewRating(form, nextValue);

          const nextStar = form.querySelector(
            '[data-review-rating-star="' + nextValue + '"]'
          );

          if (nextStar) {
            nextStar.focus({ preventScroll: true });
          }
        });
      });

      const starGroup = form.querySelector(".product-review-rating-stars");

      if (starGroup) {
        starGroup.addEventListener("mouseleave", function () {
          updateReviewRatingStars(form);
        });
      }

      form.addEventListener("submit", function (event) {
        const select = form.querySelector(reviewRatingSelectSelector);
        const submitButton = form.querySelector('[type="submit"]');

        if (window.myThemeSingleProduct && window.myThemeSingleProduct.isEditor) {
          event.preventDefault();
          return;
        }

        if (select && !select.value) {
          event.preventDefault();
          select.setCustomValidity("لطفاً امتیاز خود را انتخاب کنید.");
          select.reportValidity();
          return;
        }

        if (select) {
          select.setCustomValidity("");
        }

        if (submitButton) {
          submitButton.disabled = true;
        }
      });

      updateReviewRatingStars(form);
    });
  }

  function handleClick(event) {
    const reviewVoteButton = event.target.closest(reviewVoteButtonSelector);

    if (reviewVoteButton) {
      submitReviewVote(reviewVoteButton);
      return;
    }

    const mobileSpecificationsToggle = event.target.closest(
      mobileSpecificationsToggleSelector
    );

    if (mobileSpecificationsToggle) {
      const panel = mobileSpecificationsToggle.closest(
        productTabPanelSelector
      );
      const list = panel
        ? panel.querySelector(mobileSpecificationsListSelector)
        : document.querySelector(mobileSpecificationsListSelector);

      if (!list) {
        return;
      }

      list.classList.add("is-expanded");
      mobileSpecificationsToggle.setAttribute("aria-expanded", "true");
      mobileSpecificationsToggle.hidden = true;
      return;
    }

    const attributesButton = event.target.closest(attributesButtonSelector);

    if (attributesButton) {
      const targetKey = getProductDetailsKey(attributesButton);

      if (!targetKey) {
        return;
      }

      if (isMobileProductTabs()) {
        const productRoot = getSingleProductRoot(attributesButton);
        const anchor = getProductDetailsAnchor(productRoot, targetKey);

        event.preventDefault();
        event.stopPropagation();

        if (!anchor) {
          return;
        }

        setActiveMobileProductTab(productRoot, targetKey);
        updateExpandableDescriptions(anchor.parentElement);
        scrollToProductDetailsAnchor(productRoot, anchor);
        return;
      }

      const tabsRoot = getProductDetailsRoot();
      const specificationsTab = getProductTabByKey(tabsRoot, targetKey);

      if (tabsRoot) {
        if (specificationsTab) {
          activateProductTab(tabsRoot, specificationsTab, false);
        }
      }

      if (!scrollToProductDetailsSection(tabsRoot, productDetailsSectionSelectors[targetKey])) {
        return;
      }

      return;
    }

    const wishlistButton = event.target.closest(wishlistButtonSelector);

    if (wishlistButton) {
      wishlistButton.dispatchEvent(
        new CustomEvent("myThemeProductWishlistClick", {
          bubbles: true,
          detail: {
            productId: wishlistButton.dataset.productId || "",
          },
        })
      );
      return;
    }

    const bulkOrderButton = event.target.closest(bulkOrderButtonSelector);

    if (bulkOrderButton) {
      bulkOrderButton.dispatchEvent(
        new CustomEvent("myThemeProductBulkOrderClick", {
          bubbles: true,
          detail: {
            productId: bulkOrderButton.dataset.productId || "",
            productTitle: bulkOrderButton.dataset.productTitle || "",
          },
        })
      );
      return;
    }

    const shippingDetailsButton = event.target.closest(
      shippingDetailsButtonSelector
    );

    if (shippingDetailsButton) {
      shippingDetailsButton.dispatchEvent(
        new CustomEvent("myThemeProductShippingDetailsClick", {
          bubbles: true,
          detail: {
            productId: shippingDetailsButton.dataset.productId || "",
            productTitle: shippingDetailsButton.dataset.productTitle || "",
          },
        })
      );
      return;
    }

    const nativeShareButton = event.target.closest(nativeShareButtonSelector);

    if (nativeShareButton) {
      const shareData = {
        title: nativeShareButton.dataset.shareTitle || document.title,
        url: nativeShareButton.dataset.shareUrl || window.location.href,
      };

      if (navigator.share) {
        navigator.share(shareData).catch(function () {});
        return;
      }

      copyToClipboard(shareData.url);
      return;
    }

    const copyButton = event.target.closest(copyLinkButtonSelector);

    if (copyButton) {
      copyToClipboard(copyButton.dataset.shareUrl || window.location.href);
    }
  }

  document.addEventListener("click", handleClick);

  if ("loading" === document.readyState) {
    document.addEventListener("DOMContentLoaded", refreshSingleProductInteractions);
  } else {
    refreshSingleProductInteractions();
  }

  window.addEventListener("resize", function () {
    syncAllProductTabsPresentation();
    updateExpandableDescriptions(document);
  });

  if (mobileProductTabsQuery.addEventListener) {
    mobileProductTabsQuery.addEventListener("change", function () {
      syncAllProductTabsPresentation();
      updateExpandableDescriptions(document);
    });
  } else if (mobileProductTabsQuery.addListener) {
    mobileProductTabsQuery.addListener(function () {
      syncAllProductTabsPresentation();
      updateExpandableDescriptions(document);
    });
  }

  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(function () {
      updateExpandableDescriptions(document);
    });
  }

  if (window.MutationObserver && document.body) {
    const observer = new MutationObserver(function () {
      refreshSingleProductInteractions();
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true,
    });
  }
})();
