(function () {
  "use strict";

  const mobileQuery = window.matchMedia("(max-width: 499px)");
  const compactArchiveQuery = window.matchMedia("(max-width: 1023px)");
  const sheetMap = {
    all: [
      ".product-category-filter-card__section",
      ".product-category-color-filter",
      ".product-brand-filter",
      ".product-price-filter",
      ".product-binary-filters",
    ],
    sort: ".product-category-toolbar__options",
    category: ".product-category-filter-card__section",
    color: ".product-category-color-filter",
    brand: ".product-brand-filter",
    price: ".product-price-filter",
  };

  let activeSheet = null;
  let activeButton = null;
  let movedFilter = null;
  let movedFilters = [];
  let placeholder = null;
  let placeholders = [];
  let previousPanelState = null;
  let previousPanelStates = new Map();
  let closeFallbackTimer = null;
  let closeTransitionHandler = null;
  let scrollY = 0;
  let bodyOriginalStyles = null;
  let htmlOriginalStyles = null;
  let expandableTextIdCounter = 0;

  function isEditorPreview(root) {
    return (
      !!root.closest(".editor-styles-wrapper") ||
      document.body.classList.contains("block-editor-page")
    );
  }

  function getFocusable(container) {
    return Array.from(
      container.querySelectorAll(
        'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
      )
    ).filter(function (element) {
      return !element.hasAttribute("hidden") && element.offsetParent !== null;
    });
  }

  function normalizePersianText(value) {
    return String(value || "")
      .normalize("NFKC")
      .replace(/[\u064b-\u065f\u0670\u06d6-\u06ed]/g, "")
      .replace(/\u064a/g, "\u06cc")
      .replace(/\u0643/g, "\u06a9")
      .replace(/\u0649/g, "\u06cc")
      .replace(/\u06c0/g, "\u0647")
      .replace(/\u0629/g, "\u0647")
      .replace(/\u200c/g, " ")
      .replace(/\s+/g, " ")
      .trim()
      .toLocaleLowerCase("fa-IR");
  }

  function getModalRoot(root) {
    return (
      root.productFilterModalRoot ||
      root.querySelector("[data-product-filter-sheet-root]") ||
      document.querySelector("[data-product-filter-sheet-root]")
    );
  }

  function portalModalRoot(root) {
    const modalRoot = getModalRoot(root);

    if (!modalRoot) {
      return null;
    }

    root.productFilterModalRoot = modalRoot;
    modalRoot.productFilterArchiveRoot = root;
    modalRoot.setAttribute("dir", document.documentElement.dir || "rtl");

    if (modalRoot.parentElement !== document.body) {
      document.body.appendChild(modalRoot);
    }

    return modalRoot;
  }

  function getOverlay(root) {
    const modalRoot = getModalRoot(root);

    return modalRoot
      ? modalRoot.querySelector("[data-product-filter-sheet-overlay]")
      : null;
  }

  function getSheet(root, key) {
    const modalRoot = getModalRoot(root);

    return modalRoot
      ? modalRoot.querySelector('[data-filter-sheet-panel="' + key + '"]')
      : null;
  }

  function getFilterSheetContent(sheet) {
    if (!sheet) {
      return null;
    }

    return (
      sheet.querySelector("[data-filter-sheet-content]") ||
      sheet.querySelector("[data-filter-sheet-body]")
    );
  }

  function focusWithoutScroll(element) {
    if (!element) {
      return;
    }

    try {
      element.focus({ preventScroll: true });
    } catch (error) {
      element.focus();
      window.scrollTo(0, scrollY);
    }
  }

  function getArchiveI18n(key, fallback) {
    return (
      window.myThemeProductArchive &&
      window.myThemeProductArchive.i18n &&
      window.myThemeProductArchive.i18n[key]
    ) || fallback;
  }

  function getExpandableToggleIcon() {
    return (
      '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">' +
      '<path d="M7.82065 7.2659C7.3927 7.66175 7.3927 8.33825 7.82065 8.7341L11.2064 11.8659C11.6343 12.2617 11.6343 12.9383 11.2064 13.3341L11.1655 13.3719C10.7822 13.7264 10.1907 13.7264 9.80744 13.3719L4.79362 8.7341C4.36568 8.33825 4.36568 7.66175 4.79362 7.2659L9.80744 2.62811C10.1907 2.27358 10.7822 2.27358 11.1655 2.62811L11.2064 2.6659C11.6343 3.06175 11.6343 3.73825 11.2064 4.1341L7.82065 7.2659Z" fill="currentColor"/>' +
      "</svg>"
    );
  }

  function ensureExpandableToggle(component, content, index) {
    let toggle = component.querySelector("[data-archive-expandable-toggle]");

    if (!content.id) {
      expandableTextIdCounter += 1;
      content.id = "archive-expandable-text-content-" + index + "-" + expandableTextIdCounter;
    }

    if (toggle) {
      return toggle;
    }

    toggle = document.createElement("button");
    toggle.type = "button";
    toggle.className = "archive-expandable-text__toggle";
    toggle.setAttribute("aria-controls", content.id);
    toggle.setAttribute("aria-expanded", "false");
    toggle.setAttribute("data-archive-expandable-toggle", "");
    toggle.hidden = true;
    toggle.innerHTML =
      '<span class="archive-expandable-text__toggle-label"></span>' +
      '<span class="archive-expandable-text__toggle-icon" aria-hidden="true">' +
      getExpandableToggleIcon() +
      "</span>";

    component.appendChild(toggle);

    return toggle;
  }

  function updateExpandableToggle(toggle, expanded) {
    const label = toggle.querySelector(".archive-expandable-text__toggle-label");

    if (label) {
      label.textContent = expanded
        ? getArchiveI18n("readLess", "Less")
        : getArchiveI18n("readMore", "More");
    }

    toggle.setAttribute("aria-expanded", expanded ? "true" : "false");
  }

  function measureExpandableText(component, index) {
    const content = component.querySelector("[data-archive-expandable-text-content]");

    if (!content) {
      return;
    }

    const toggle = ensureExpandableToggle(component, content, index);
    const expanded = component.classList.contains("is-expanded");

    if (!mobileQuery.matches) {
      component.classList.remove("is-clampable", "is-expanded");
      toggle.hidden = true;
      updateExpandableToggle(toggle, false);
      return;
    }

    component.classList.remove("is-clampable");

    const styles = window.getComputedStyle(content);
    let lineHeight = parseFloat(styles.lineHeight);

    if (!lineHeight || Number.isNaN(lineHeight)) {
      const fontSize = parseFloat(styles.fontSize) || 16;
      lineHeight = fontSize * 1.5;
    }

    const maxCollapsedHeight = lineHeight * 6;
    const shouldClamp = content.scrollHeight > maxCollapsedHeight + 1;

    component.classList.toggle("is-clampable", shouldClamp);
    component.classList.toggle("is-expanded", shouldClamp && expanded);
    toggle.hidden = !shouldClamp;
    updateExpandableToggle(toggle, shouldClamp && expanded);
  }

  function refreshExpandableTexts(root) {
    root.querySelectorAll("[data-archive-expandable-text]").forEach(function (component, index) {
      measureExpandableText(component, index + 1);
    });
  }

  function initExpandableArchiveTexts(root) {
    if (root.dataset.archiveExpandableTextInit === "true") {
      refreshExpandableTexts(root);
      return;
    }

    root.dataset.archiveExpandableTextInit = "true";
    refreshExpandableTexts(root);

    if (document.fonts && document.fonts.ready) {
      document.fonts.ready.then(function () {
        refreshExpandableTexts(root);
      });
    }

    let resizeTimer = null;

    window.addEventListener("resize", function () {
      window.clearTimeout(resizeTimer);
      resizeTimer = window.setTimeout(function () {
        refreshExpandableTexts(root);
      }, 150);
    });
  }

  function lockScroll() {
    if (document.body.dataset.productArchiveScrollLock === "true") {
      return;
    }

    scrollY = window.scrollY || window.pageYOffset || document.documentElement.scrollTop || 0;
    bodyOriginalStyles = {
      overflow: document.body.style.overflow,
      overscrollBehavior: document.body.style.overscrollBehavior,
      paddingInlineEnd: document.body.style.paddingInlineEnd,
      scrollbarCompensation: document.body.style.getPropertyValue(
        "--scrollbar-compensation"
      ),
      touchAction: document.body.style.touchAction,
    };
    htmlOriginalStyles = {
      overflow: document.documentElement.style.overflow,
      overscrollBehavior: document.documentElement.style.overscrollBehavior,
      touchAction: document.documentElement.style.touchAction,
    };

    document.body.dataset.productArchiveScrollLock = "true";
    document.documentElement.classList.add("is-product-filter-sheet-open");
    document.body.classList.add("is-product-filter-sheet-open");
    document.documentElement.style.overflow = "hidden";
    document.documentElement.style.overscrollBehavior = "none";
    document.documentElement.style.touchAction = "none";
    document.body.style.overflow = "hidden";
    document.body.style.overscrollBehavior = "none";
    document.body.style.touchAction = "none";

    window.scrollTo({
      top: scrollY,
      left: 0,
      behavior: "auto",
    });
  }

  function unlockScroll() {
    if (document.body.dataset.productArchiveScrollLock !== "true") {
      return;
    }

    const restoredScrollY = scrollY;

    document.documentElement.classList.remove("is-product-filter-sheet-open");
    document.body.classList.remove("is-product-filter-sheet-open");

    if (bodyOriginalStyles) {
      document.body.style.overflow = bodyOriginalStyles.overflow;
      document.body.style.overscrollBehavior = bodyOriginalStyles.overscrollBehavior;
      document.body.style.paddingInlineEnd = bodyOriginalStyles.paddingInlineEnd;
      document.body.style.touchAction = bodyOriginalStyles.touchAction;

      if (bodyOriginalStyles.scrollbarCompensation) {
        document.body.style.setProperty(
          "--scrollbar-compensation",
          bodyOriginalStyles.scrollbarCompensation
        );
      } else {
        document.body.style.removeProperty("--scrollbar-compensation");
      }
    } else {
      document.body.style.overflow = "";
      document.body.style.overscrollBehavior = "";
      document.body.style.paddingInlineEnd = "";
      document.body.style.touchAction = "";
      document.body.style.removeProperty("--scrollbar-compensation");
    }

    if (htmlOriginalStyles) {
      document.documentElement.style.overflow = htmlOriginalStyles.overflow;
      document.documentElement.style.overscrollBehavior =
        htmlOriginalStyles.overscrollBehavior;
      document.documentElement.style.touchAction = htmlOriginalStyles.touchAction;
    } else {
      document.documentElement.style.overflow = "";
      document.documentElement.style.overscrollBehavior = "";
      document.documentElement.style.touchAction = "";
    }

    delete document.body.dataset.productArchiveScrollLock;
    window.scrollTo({
      top: restoredScrollY,
      left: 0,
      behavior: "auto",
    });

    bodyOriginalStyles = null;
    htmlOriginalStyles = null;
  }

  function filterSheetOptions(input) {
    const sheet = input.closest(".product-filter-bottom-sheet");

    if (!sheet) {
      return;
    }

    const query = normalizePersianText(input.value);
    const options = Array.from(sheet.querySelectorAll("[data-filter-option]"));
    const emptyState = sheet.querySelector("[data-filter-search-empty]");
    let visibleCount = 0;

    options.forEach(function (option) {
      const searchableText = normalizePersianText(
        option.dataset.searchText || option.textContent || ""
      );
      const shouldShow = query === "" || searchableText.includes(query);

      option.hidden = !shouldShow;
      option.setAttribute("aria-hidden", shouldShow ? "false" : "true");

      if (shouldShow) {
        visibleCount += 1;
      }
    });

    if (emptyState) {
      emptyState.hidden = !(query !== "" && visibleCount === 0);
    }
  }

  function resetSheetSearch(sheet) {
    if (!sheet) {
      return;
    }

    sheet.querySelectorAll("[data-filter-option-search]").forEach(function (input) {
      input.value = "";
      filterSheetOptions(input);
    });

    sheet.querySelectorAll("[data-filter-option]").forEach(function (option) {
      option.hidden = false;
      option.setAttribute("aria-hidden", "false");
    });

    sheet.querySelectorAll("[data-filter-search-empty]").forEach(function (emptyState) {
      emptyState.hidden = true;
    });
  }

  function syncMobileActiveFilters(root, sheet) {
    if (!root || !sheet) {
      return;
    }

    const mobileContainer = sheet.querySelector("[data-mobile-active-filters]");

    if (!mobileContainer) {
      return;
    }

    const desktopContainer = root.querySelector("[data-active-filters]");

    if (!desktopContainer || desktopContainer.textContent.trim() === "") {
      mobileContainer.innerHTML = "";
      mobileContainer.hidden = true;
      return;
    }

    mobileContainer.innerHTML = desktopContainer.innerHTML;
    mobileContainer.hidden = false;
  }

  function restoreFilter() {
    movedFilters.forEach(function (filter, index) {
      const currentPlaceholder = placeholders[index];
      const state = previousPanelStates.get(filter);

      if (!filter || !currentPlaceholder || !currentPlaceholder.parentNode) {
        return;
      }

      if (state) {
        const panel = filter.querySelector(
          "[data-filter-accordion-panel], [data-category-filter-panel]"
        );
        const toggle = filter.querySelector(
          "[data-filter-accordion-toggle], [data-category-filter-toggle]"
        );

        if (panel) {
          panel.hidden = state.hidden;
        }

        if (toggle) {
          toggle.setAttribute("aria-expanded", state.expanded);
        }
      }

      currentPlaceholder.parentNode.insertBefore(filter, currentPlaceholder);
      currentPlaceholder.remove();
    });

    movedFilter = null;
    movedFilters = [];
    placeholder = null;
    placeholders = [];
    previousPanelState = null;
    previousPanelStates = new Map();
  }

  function prepareFilter(filter, shouldExpand) {
    const panel = filter.querySelector(
      "[data-filter-accordion-panel], [data-category-filter-panel]"
    );
    const toggle = filter.querySelector(
      "[data-filter-accordion-toggle], [data-category-filter-toggle]"
    );

    const state = {
      hidden: panel ? panel.hidden : false,
      expanded: toggle ? toggle.getAttribute("aria-expanded") || "false" : "false",
    };

    previousPanelState = state;
    previousPanelStates.set(filter, state);

    if (panel) {
      panel.hidden = !shouldExpand;
    }

    if (toggle) {
      toggle.setAttribute("aria-expanded", shouldExpand ? "true" : "false");
    }
  }

  function setAllBadgesClosed(root) {
    root.querySelectorAll("[data-filter-sheet]").forEach(function (button) {
      button.setAttribute("aria-expanded", "false");
    });
  }

  function finalizeBottomSheetClose(sheet, overlay, settings) {
    window.clearTimeout(closeFallbackTimer);
    closeFallbackTimer = null;

    if (closeTransitionHandler) {
      sheet.removeEventListener("transitionend", closeTransitionHandler);
      closeTransitionHandler = null;
    }

    resetSheetSearch(sheet);
    restoreFilter();

    sheet.hidden = true;
    sheet.classList.remove("is-mounted", "is-opening", "is-open", "is-closing");
    sheet.dataset.bottomSheetState = "closed";

    if (overlay && !settings.keepOverlay) {
      overlay.hidden = true;
    }

    if (settings.root && !settings.keepOverlay) {
      setAllBadgesClosed(settings.root);
    }

    if (!settings.keepLocked) {
      unlockScroll();
    }

    if (!settings.skipFocus && activeButton) {
      focusWithoutScroll(activeButton);
      window.scrollTo({
        top: scrollY,
        left: 0,
        behavior: "auto",
      });
    }

    activeSheet = null;
    activeButton = null;

    if (typeof settings.onAfterClose === "function") {
      settings.onAfterClose();
    }
  }

  function closeBottomSheet(options) {
    const settings = options || {};
    const sheet = activeSheet;
    const overlay = settings.root
      ? getOverlay(settings.root)
      : document.querySelector("[data-product-filter-sheet-overlay]");
    let finalized = false;

    if (!sheet || sheet.dataset.bottomSheetState === "closing") {
      return;
    }

    sheet.dataset.bottomSheetState = "closing";
    sheet.classList.remove("is-opening", "is-open");
    sheet.classList.add("is-closing");

    if (overlay && !settings.keepOverlay) {
      overlay.classList.remove("is-open");
    }

    closeTransitionHandler = function (event) {
      if (event.target !== sheet || event.propertyName !== "transform") {
        return;
      }

      if (finalized) {
        return;
      }

      finalized = true;
      finalizeBottomSheetClose(sheet, overlay, settings);
    };

    sheet.addEventListener("transitionend", closeTransitionHandler);
    closeFallbackTimer = window.setTimeout(function () {
      if (finalized) {
        return;
      }

      finalized = true;
      finalizeBottomSheetClose(sheet, overlay, settings);
    }, 420);
  }

  function getClearFiltersUrl() {
    const url = new URL(window.location.href);
    const explicitFilterKeys = [
      "filter_category",
      "filter_color",
      "filter_brand",
      "min_price",
      "max_price",
      "on_sale",
      "in_stock",
      "paged",
      "product-page",
    ];

    Array.from(url.searchParams.keys()).forEach(function (key) {
      if (
        explicitFilterKeys.includes(key) ||
        key.indexOf("filter_") === 0 ||
        key.indexOf("query_type_") === 0
      ) {
        url.searchParams.delete(key);
      }
    });

    url.hash = "";

    return url.toString();
  }

  function handleFilterSheetAction(root, action) {
    if (!action) {
      return false;
    }

    const actionType = action.dataset.filterAction;

    if (actionType === "apply") {
      closeBottomSheet({ root });
      return true;
    }

    if (actionType === "clear") {
      if (isEditorPreview(root)) {
        closeBottomSheet({ root });
        return true;
      }

      const nextUrl = getClearFiltersUrl();

      if (nextUrl === window.location.href) {
        closeBottomSheet({ root });
        return true;
      }

      window.location.assign(nextUrl);
      return true;
    }

    return false;
  }

  function openBottomSheet(root, key, button) {
    if (!compactArchiveQuery.matches) {
      return;
    }

    const modalRoot = getModalRoot(root);
    const sheet = getSheet(root, key);
    const body = sheet ? sheet.querySelector("[data-filter-sheet-body]") : null;
    const content = getFilterSheetContent(sheet);
    const overlay = getOverlay(root);
    const selectors = Array.isArray(sheetMap[key]) ? sheetMap[key] : [sheetMap[key]];
    const sources = selectors
      .filter(Boolean)
      .map(function (selector) {
        return root.querySelector(selector);
      })
      .filter(Boolean);

    if (!modalRoot || !sheet || !body || !content || !overlay || sources.length === 0) {
      return;
    }

    if (activeSheet) {
      if (activeSheet === sheet && sheet.classList.contains("is-open")) {
        return;
      }

      closeBottomSheet({
        skipFocus: true,
        keepLocked: true,
        keepOverlay: true,
        root,
        onAfterClose: function () {
          openBottomSheet(root, key, button);
        },
      });
      return;
    }

    lockScroll();

    sources.forEach(function (source, index) {
      const currentPlaceholder = document.createComment(
        "product-filter-placeholder-" + key
      );
      const shouldExpand = key !== "all" || index === 0;

      source.parentNode.insertBefore(currentPlaceholder, source);
      prepareFilter(source, shouldExpand);
      content.appendChild(source);
      placeholders.push(currentPlaceholder);
      movedFilters.push(source);
    });

    movedFilter = movedFilters[0] || null;
    placeholder = placeholders[0] || null;

    activeSheet = sheet;
    activeButton = button;

    resetSheetSearch(sheet);
    syncMobileActiveFilters(root, sheet);

    overlay.hidden = false;
    sheet.hidden = false;
    sheet.classList.add("is-mounted", "is-opening");
    sheet.dataset.bottomSheetState = "opening";

    root.querySelectorAll("[data-filter-sheet]").forEach(function (badge) {
      badge.setAttribute("aria-expanded", badge === button ? "true" : "false");
    });

    window.requestAnimationFrame(function () {
      window.requestAnimationFrame(function () {
        sheet.classList.remove("is-opening", "is-closing");
        sheet.classList.add("is-open");
        sheet.dataset.bottomSheetState = "open";
        overlay.classList.add("is-open");

        const focusTarget =
          sheet.querySelector("[data-filter-sheet-close]") ||
          sheet.querySelector(".product-filter-bottom-sheet__title") ||
          sheet;
        focusWithoutScroll(focusTarget);
      });
    });
  }

  function syncSearchForm(root) {
    const form = root.querySelector("[data-product-archive-search]");
    const input = root.querySelector("[data-product-archive-search-input]");

    if (!form || !input || form.dataset.productArchiveSearchInit === "true") {
      return;
    }

    form.dataset.productArchiveSearchInit = "true";
    const currentSearchParams = new URL(window.location.href).searchParams;
    input.value =
      currentSearchParams.get("search") ||
      currentSearchParams.get("product_search") ||
      "";

    form.addEventListener("submit", function () {
      const url = new URL(window.location.href);
      const isEmptySearch = input.value.trim() === "";

      Array.from(form.querySelectorAll("[data-dynamic-search-param]")).forEach(
        function (element) {
          element.remove();
        }
      );

      url.searchParams.delete("paged");
      url.searchParams.delete("product-page");
      url.searchParams.delete("product_search");

      url.searchParams.forEach(function (value, key) {
        const hidden = document.createElement("input");
        hidden.type = "hidden";
        hidden.name = key;
        hidden.value = value;
        hidden.dataset.dynamicSearchParam = "true";
        form.appendChild(hidden);
      });

      if (isEmptySearch) {
        input.disabled = true;
        window.setTimeout(function () {
          input.disabled = false;
        }, 0);
      }
    });
  }

  function syncActiveBadges(root) {
    const params = new URL(window.location.href).searchParams;
    const activeMap = {
      sort: params.has("orderby"),
      category: params.has("filter_category"),
      color: params.has("filter_color"),
      brand: params.has("filter_brand"),
      price: params.has("min_price") || params.has("max_price"),
      binary: params.has("on_sale") || params.has("in_stock"),
    };
    activeMap.all = ["category", "color", "brand", "price", "binary"].some(
      function (key) {
        return activeMap[key];
      }
    );

    root.querySelectorAll("[data-filter-sheet]").forEach(function (button) {
      const isActive = !!activeMap[button.dataset.filterSheet];
      button.classList.toggle("is-active", isActive);
    });
  }

  function bindSheetSearch(root, modalRoot) {
    if (root.dataset.productFilterSheetSearchInit === "true") {
      return;
    }

    root.dataset.productFilterSheetSearchInit = "true";

    const bindTarget = modalRoot || root;

    bindTarget.addEventListener("input", function (event) {
      const input = event.target.closest("[data-filter-option-search]");

      if (!input || !bindTarget.contains(input)) {
        return;
      }

      filterSheetOptions(input);
    });

    bindTarget.addEventListener("keydown", function (event) {
      const input = event.target.closest("[data-filter-option-search]");

      if (!input || !bindTarget.contains(input) || event.key !== "Enter") {
        return;
      }

      event.preventDefault();
    });
  }

  function initArchive(root) {
    if (!root || root.dataset.productArchiveMobileInit === "true") {
      return;
    }

    root.dataset.productArchiveMobileInit = "true";
    const modalRoot = portalModalRoot(root);

    syncSearchForm(root);
    syncActiveBadges(root);
    bindSheetSearch(root, modalRoot);
    initExpandableArchiveTexts(root);

    root.addEventListener("click", function (event) {
      const expandableToggle = event.target.closest("[data-archive-expandable-toggle]");
      const badge = event.target.closest("[data-filter-sheet]");
      const close = event.target.closest("[data-filter-sheet-close]");
      const overlay = event.target.closest("[data-product-filter-sheet-overlay]");

      if (expandableToggle && root.contains(expandableToggle)) {
        const component = expandableToggle.closest("[data-archive-expandable-text]");
        const expanded = component && !component.classList.contains("is-expanded");

        if (!component) {
          return;
        }

        event.preventDefault();
        component.classList.toggle("is-expanded", expanded);
        updateExpandableToggle(expandableToggle, expanded);
        focusWithoutScroll(expandableToggle);
        return;
      }

      if (badge && root.contains(badge)) {
        event.preventDefault();
        openBottomSheet(root, badge.dataset.filterSheet, badge);
        return;
      }

      if (close || overlay) {
        event.preventDefault();
        closeBottomSheet({ root });
      }
    });

    if (modalRoot) {
      modalRoot.addEventListener("click", function (event) {
        const close = event.target.closest("[data-filter-sheet-close]");
        const overlay = event.target.closest("[data-product-filter-sheet-overlay]");
        const action = event.target.closest("[data-filter-action]");

        if (action && modalRoot.contains(action)) {
          event.preventDefault();
          handleFilterSheetAction(root, action);
          return;
        }

        if (!close && !overlay) {
          return;
        }

        event.preventDefault();
        closeBottomSheet({ root });
      });
    }

    document.addEventListener("keydown", function (event) {
      if (!activeSheet) {
        return;
      }

      if ("Escape" === event.key) {
        event.preventDefault();
        closeBottomSheet({ root });
        return;
      }

      if ("Tab" !== event.key) {
        return;
      }

      const focusable = getFocusable(activeSheet);

      if (focusable.length === 0) {
        event.preventDefault();
        focusWithoutScroll(activeSheet);
        return;
      }

      const first = focusable[0];
      const last = focusable[focusable.length - 1];

      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });

    mobileQuery.addEventListener("change", function () {
      refreshExpandableTexts(root);
    });

    compactArchiveQuery.addEventListener("change", function (event) {
      if (!event.matches && activeSheet) {
        closeBottomSheet({ skipFocus: true, root });
      }
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".product-category-archive").forEach(initArchive);
  });
})();
