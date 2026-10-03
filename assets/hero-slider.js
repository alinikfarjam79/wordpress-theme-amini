document.addEventListener("DOMContentLoaded", function () {

    document.querySelectorAll('.site-header__search[role="search"]').forEach(function (form) {
        const input = form.querySelector('input[name="search"]');

        if (!input || form.dataset.combinedSearchInit === "true") {
            return;
        }

        form.dataset.combinedSearchInit = "true";

        form.addEventListener("submit", function (event) {
            const value = input.value.trim();

            if (value === "") {
                event.preventDefault();
                input.value = "";
                input.focus();
                return;
            }

            input.value = value;
        });
    });

    document.querySelectorAll(".heroSwiper").forEach(function (slider) {
        if (slider.swiper) return;
        const pagination = slider.querySelector(".swiper-pagination");
        const next = slider.querySelector(".swiper-button-next");
        const prev = slider.querySelector(".swiper-button-prev");
        const autoplay = slider.dataset.autoplay !== "false";
        new Swiper(slider, {
            loop: slider.dataset.loop !== "false",
            autoplay: autoplay ? { delay: parseInt(slider.dataset.delay || "3000", 10) } : false,
            pagination: pagination ? { el: pagination, clickable: true } : undefined,
            navigation: next && prev ? { nextEl: next, prevEl: prev } : undefined
        });
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const header = document.querySelector(".site-header");
    const nav = document.querySelector(".site-header__nav .wp-block-navigation");

    if (!header || !nav) return;

    const openButton = nav.querySelector(".wp-block-navigation__responsive-container-open");
    const closeButton = nav.querySelector(".wp-block-navigation__responsive-container-close");
    const container = nav.querySelector(".wp-block-navigation__responsive-container");

    if (!openButton || !container) return;

    const setMenuBounds = function () {
        const headerBottom = Math.round(header.getBoundingClientRect().bottom);
        const bottomBar = document.querySelector(".site-bottom-bar, .mobile-bottom-bar, .bottom-bar");
        const bottomOffset = bottomBar ? Math.max(0, window.innerHeight - bottomBar.getBoundingClientRect().top) : 0;

        document.documentElement.style.setProperty("--site-header-offset", `${headerBottom}px`);
        document.documentElement.style.setProperty("--site-bottom-bar-offset", `${Math.round(bottomOffset)}px`);
    };

    const syncMenuState = function () {
        const isOpen = container.classList.contains("is-menu-open");

        document.body.classList.toggle("mobile-menu-is-open", isOpen);
        openButton.setAttribute("aria-label", isOpen ? "Close menu" : "Open menu");
        openButton.setAttribute("aria-expanded", isOpen ? "true" : "false");
        setMenuBounds();
    };

    openButton.addEventListener("click", function (event) {
        if (!document.body.classList.contains("mobile-menu-is-open")) return;

        event.preventDefault();
        event.stopImmediatePropagation();

        if (closeButton) {
            closeButton.click();
        } else {
            container.classList.remove("is-menu-open");
            syncMenuState();
        }
    }, true);

    window.addEventListener("resize", setMenuBounds);
    window.addEventListener("orientationchange", setMenuBounds);

    const observer = new MutationObserver(syncMenuState);
    observer.observe(container, {
        attributes: true,
        attributeFilter: ["class"]
    });

    setMenuBounds();
    syncMenuState();
});



document.addEventListener("DOMContentLoaded", function () {
    const articleSliders = Array.from(
        document.querySelectorAll(".latestArticlesSwiper")
    );
    if (!articleSliders.length) return;

    const mobileQuery = window.matchMedia("(max-width: 700px)");

    const toggleArticlesSwiper = function () {
        articleSliders.forEach(function (slider) {
            const currentSwiper = slider.myThemeArticlesSwiper || null;

            if (mobileQuery.matches && !currentSwiper) {
                const pagination = slider.querySelector(
                    ".latest-articles-pagination"
                );
                const options = {
                    initialSlide: 0,
                    slidesPerView: 1,
                    centeredSlides: false,
                    spaceBetween: 0,
                    slidesOffsetBefore: 0,
                    slidesOffsetAfter: 0,
                    observer: true,
                    observeParents: true,
                    resizeObserver: true,
                    rewind: false,
                    watchOverflow: true,
                };

                if (pagination) {
                    options.pagination = {
                        el: pagination,
                        clickable: true,
                    };
                }

                slider.myThemeArticlesSwiper = new Swiper(slider, options);
                slider.myThemeArticlesSwiper.update();
            }

            if (!mobileQuery.matches && currentSwiper) {
                currentSwiper.destroy(true, true);
                slider.myThemeArticlesSwiper = null;
            }
        });
    };

    toggleArticlesSwiper();

    if (mobileQuery.addEventListener) {
        mobileQuery.addEventListener("change", toggleArticlesSwiper);
    } else {
        mobileQuery.addListener(toggleArticlesSwiper);
    }
});
