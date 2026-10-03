(function () {
  "use strict";

  const cardSelector = "[data-product-category-filter-card]";

  function normalize(value) {
    return String(value || "")
      .trim()
      .toLocaleLowerCase("fa-IR")
      .replace(/\u064a/g, "\u06cc")
      .replace(/\u0643/g, "\u06a9")
      .replace(/\u0649/g, "\u06cc")
      .replace(/\u06c0/g, "\u0647")
      .replace(/\u0629/g, "\u0647");
  }

  function updateCategoryResults(root, input) {
    const results = root.querySelector("[data-category-filter-results]");

    if (!results) {
      return;
    }

    const items = Array.from(
      results.querySelectorAll("[data-category-filter-item]")
    );
    const emptyState = results.querySelector("[data-category-filter-empty]");
    const query = normalize(input ? input.value : "");
    let visibleCount = 0;

    items.forEach(function (item) {
      const categoryName = normalize(
        item.dataset.categoryName ||
          item.dataset.categoryFilterName ||
          item.textContent ||
          ""
      );
      const shouldShow = query.length === 0 || categoryName.includes(query);

      item.hidden = !shouldShow;
      item.setAttribute("aria-hidden", shouldShow ? "false" : "true");

      if (shouldShow) {
        visibleCount += 1;
      }
    });

    if (emptyState) {
      emptyState.hidden = !(query.length > 0 && visibleCount === 0);
    }
  }

  function isEditorPreview(root) {
    return (
      !!root.closest(".editor-styles-wrapper") ||
      document.body.classList.contains("block-editor-page")
    );
  }

  function updateBrandResults(root, input) {
    const results = root.querySelector("[data-brand-filter-results]");

    if (!results) {
      return;
    }

    const items = Array.from(results.querySelectorAll("[data-brand-filter-item]"));
    const emptyState = results.querySelector("[data-brand-filter-empty]");
    const query = normalize(input ? input.value : "");
    let visibleCount = 0;

    items.forEach(function (item) {
      const brandName = normalize(item.dataset.brandName || item.textContent || "");
      const shouldShow = query.length === 0 || brandName.includes(query);

      item.hidden = !shouldShow;
      item.setAttribute("aria-hidden", shouldShow ? "false" : "true");

      if (shouldShow) {
        visibleCount += 1;
      }
    });

    if (emptyState) {
      emptyState.hidden = !(query.length > 0 && visibleCount === 0);
    }
  }

  function normalizePriceNumber(value) {
    return String(value || "")
      .replace(/[\u06f0-\u06f9]/g, function (digit) {
        return String("\u06f0\u06f1\u06f2\u06f3\u06f4\u06f5\u06f6\u06f7\u06f8\u06f9".indexOf(digit));
      })
      .replace(/[\u0660-\u0669]/g, function (digit) {
        return String("\u0660\u0661\u0662\u0663\u0664\u0665\u0666\u0667\u0668\u0669".indexOf(digit));
      })
      .replace(/[,\u066c\s]/g, "")
      .replace(/[^\d]/g, "");
  }

  function parsePriceValue(value) {
    const normalized = normalizePriceNumber(value);

    if (!normalized) {
      return null;
    }

    const parsed = Number(normalized);

    return Number.isFinite(parsed) ? parsed : null;
  }

  function formatPriceValue(value) {
    return String(Math.round(Number(value) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }

  function clamp(value, min, max) {
    return Math.max(min, Math.min(max, value));
  }

  function updateCurrentFilterUrl(options) {
    const settings = options || {};
    const set = settings.set || {};
    const remove = settings.remove || [];
    const url = new URL(window.location.href);

    remove.forEach(function (key) {
      url.searchParams.delete(key);
    });

    Object.keys(set).forEach(function (key) {
      const value = set[key];

      if (value === null || value === undefined || value === "") {
        url.searchParams.delete(key);
        return;
      }

      url.searchParams.set(key, String(value));
    });

    url.searchParams.delete("paged");
    url.searchParams.delete("product-page");
    url.hash = "";

    return url.toString();
  }

  function initProductPriceFilter(root) {
    if (!root || root.dataset.priceFilterInitialized === "true") {
      return;
    }

    const filters = Array.from(root.querySelectorAll("[data-price-filter-root]"));

    if (filters.length === 0) {
      return;
    }

    root.dataset.priceFilterInitialized = "true";

    filters.forEach(function (filter) {
      const minInput = filter.querySelector("[data-price-min-input]");
      const maxInput = filter.querySelector("[data-price-max-input]");
      const minRange = filter.querySelector("[data-price-min-range]");
      const maxRange = filter.querySelector("[data-price-max-range]");
      const activeTrack = filter.querySelector("[data-price-active-track]");
      const absoluteMin = Number(filter.dataset.priceMin || 0);
      const absoluteMax = Number(filter.dataset.priceMax || 0);

      if (
        !minInput ||
        !maxInput ||
        !minRange ||
        !maxRange ||
        !activeTrack ||
        !Number.isFinite(absoluteMin) ||
        !Number.isFinite(absoluteMax) ||
        absoluteMin > absoluteMax
      ) {
        return;
      }

      function getValues() {
        let minValue = clamp(Number(minRange.value), absoluteMin, absoluteMax);
        let maxValue = clamp(Number(maxRange.value), absoluteMin, absoluteMax);

        if (minValue > maxValue) {
          if (document.activeElement === minRange || document.activeElement === minInput) {
            minValue = maxValue;
          } else {
            maxValue = minValue;
          }
        }

        return {
          min: minValue,
          max: maxValue,
        };
      }

      function syncUI(values, formatInputs) {
        const minValue = clamp(values.min, absoluteMin, absoluteMax);
        const maxValue = clamp(values.max, absoluteMin, absoluteMax);
        const safeMin = Math.min(minValue, maxValue);
        const safeMax = Math.max(minValue, maxValue);
        const span = Math.max(1, absoluteMax - absoluteMin);
        const minPercent = ((safeMin - absoluteMin) / span) * 100;
        const maxPercent = ((safeMax - absoluteMin) / span) * 100;

        minRange.value = String(safeMin);
        maxRange.value = String(safeMax);
        minRange.setAttribute("aria-valuenow", String(safeMin));
        maxRange.setAttribute("aria-valuenow", String(safeMax));
        filter.style.setProperty("--price-min-position", minPercent + "%");
        filter.style.setProperty("--price-max-position", maxPercent + "%");
        activeTrack.style.setProperty("--price-min-position", minPercent + "%");
        activeTrack.style.setProperty("--price-max-position", maxPercent + "%");

        if (formatInputs) {
          minInput.value = formatPriceValue(safeMin);
          maxInput.value = formatPriceValue(safeMax);
        }

        if (safeMax - safeMin < span * 0.04) {
          minRange.style.zIndex = "5";
          maxRange.style.zIndex = "6";
        } else {
          minRange.style.zIndex = "3";
          maxRange.style.zIndex = "4";
        }
      }

      function applyPriceFilter() {
        const values = getValues();

        syncUI(values, true);

        if (isEditorPreview(root)) {
          return;
        }

        const set = {};
        const remove = [];

        if (values.min > absoluteMin) {
          set.min_price = String(Math.round(values.min));
        } else {
          remove.push("min_price");
        }

        if (values.max < absoluteMax) {
          set.max_price = String(Math.round(values.max));
        } else {
          remove.push("max_price");
        }

        window.location.assign(
          updateCurrentFilterUrl({
            set: set,
            remove: remove,
          })
        );
      }

      function syncFromRange() {
        syncUI(getValues(), true);
      }

      function syncFromInputs(formatInputs) {
        const parsedMin = parsePriceValue(minInput.value);
        const parsedMax = parsePriceValue(maxInput.value);
        const current = getValues();
        const nextMin = parsedMin === null ? current.min : parsedMin;
        const nextMax = parsedMax === null ? current.max : parsedMax;

        syncUI(
          {
            min: Math.min(nextMin, nextMax),
            max: Math.max(nextMin, nextMax),
          },
          formatInputs
        );
      }

      minRange.addEventListener("input", syncFromRange);
      maxRange.addEventListener("input", syncFromRange);
      minRange.addEventListener("change", applyPriceFilter);
      maxRange.addEventListener("change", applyPriceFilter);

      minInput.addEventListener("input", function () {
        if (parsePriceValue(minInput.value) !== null) {
          syncFromInputs(false);
        }
      });
      maxInput.addEventListener("input", function () {
        if (parsePriceValue(maxInput.value) !== null) {
          syncFromInputs(false);
        }
      });
      minInput.addEventListener("blur", function () {
        syncFromInputs(true);
      });
      maxInput.addEventListener("blur", function () {
        syncFromInputs(true);
      });
      minInput.addEventListener("change", applyPriceFilter);
      maxInput.addEventListener("change", applyPriceFilter);

      syncUI(getValues(), true);
    });
  }

  function initBrandSearch(root) {
    if (!root || root.dataset.brandSearchInitialized === "true") {
      return;
    }

    const input = root.querySelector("[data-brand-filter-search]");
    const results = root.querySelector("[data-brand-filter-results]");

    if (!input || !results) {
      return;
    }

    root.dataset.brandSearchInitialized = "true";
    updateBrandResults(root, input);

    input.addEventListener("input", function () {
      updateBrandResults(root, input);
    });
  }

  function initBrandCheckboxes(root) {
    if (!root || root.dataset.brandCheckboxesInitialized === "true") {
      return;
    }

    const inputs = Array.from(root.querySelectorAll(".product-brand-filter__input"));

    if (inputs.length === 0) {
      return;
    }

    root.dataset.brandCheckboxesInitialized = "true";

    inputs.forEach(function (input) {
      input.addEventListener("change", function () {
        const selectedBrands = inputs
          .filter(function (brandInput) {
            return brandInput.checked;
          })
          .map(function (brandInput) {
            return brandInput.value;
          })
          .filter(Boolean);

        if (isEditorPreview(root)) {
          return;
        }

        window.location.assign(
          updateCurrentFilterUrl({
            set: {
              filter_brand: selectedBrands.join(","),
            },
          })
        );
      });
    });
  }

  function initBinaryProductFilters(root) {
    if (!root || root.dataset.binaryFiltersInitialized === "true") {
      return;
    }

    const inputs = Array.from(root.querySelectorAll("[data-product-binary-filter]"));

    if (inputs.length === 0) {
      return;
    }

    root.dataset.binaryFiltersInitialized = "true";

    inputs.forEach(function (input) {
      input.addEventListener("change", function () {
        const parameter = input.dataset.filterParameter || "";

        if (!parameter || isEditorPreview(root)) {
          return;
        }

        window.location.assign(
          updateCurrentFilterUrl({
            set: {
              [parameter]: input.checked ? "1" : "",
            },
          })
        );
      });
    });
  }

  function initAccordions(root) {
    if (!root || root.dataset.accordionsInitialized === "true") {
      return;
    }

    const toggles = Array.from(
      root.querySelectorAll(
        "[data-category-filter-toggle], [data-filter-accordion-toggle]"
      )
    );

    root.dataset.accordionsInitialized = "true";

    function setAccordionState(toggle, panel, isOpen) {
      const accordion = toggle.closest("[data-filter-accordion]");

      toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
      panel.hidden = !isOpen;

      if (accordion) {
        accordion.classList.toggle("is-open", isOpen);
      }
    }

    toggles.forEach(function (toggle) {
      const controls = toggle.getAttribute("aria-controls");
      const panel = controls ? root.querySelector("#" + controls) : null;

      if (!panel) {
        return;
      }

      toggle.addEventListener("click", function () {
        const isExpanded = toggle.getAttribute("aria-expanded") === "true";

        setAccordionState(toggle, panel, !isExpanded);
      });

      setAccordionState(toggle, panel, toggle.getAttribute("aria-expanded") === "true");
    });
  }

  function initCategorySearch(root) {
    if (!root || root.dataset.categorySearchInitialized === "true") {
      return;
    }

    const input = root.querySelector("[data-category-filter-search]");
    const results = root.querySelector("[data-category-filter-results]");

    if (!input || !results) {
      return;
    }

    root.dataset.categorySearchInitialized = "true";
    updateCategoryResults(root, input);

    input.addEventListener("input", function () {
      updateCategoryResults(root, input);
    });
  }

  function initCategoryFilterCard(root) {
    initAccordions(root);
    initCategorySearch(root);
    initBrandSearch(root);
    initBrandCheckboxes(root);
    initProductPriceFilter(root);
    initBinaryProductFilters(root);
  }

  function boot() {
    document.querySelectorAll(cardSelector).forEach(initCategoryFilterCard);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot, { once: true });
  } else {
    boot();
  }
})();
