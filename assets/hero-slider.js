document.addEventListener("DOMContentLoaded", function () {


    new Swiper(".heroSwiper", {
        loop: true,
        autoplay: {
            delay: 3000,
        },
        pagination: {
            el: ".swiper-pagination",
            clickable: true,
        },
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        }
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

    let productSwiper = new Swiper(".productSwiper", {

        spaceBetween: 16,

        navigation: {
            nextEl: ".product-next",
            prevEl: ".product-prev",
        },

        pagination: {
            el: ".product-pagination",
            clickable: true,
        },

        breakpoints: {

            320: {
                slidesPerView: 2.15,
                spaceBetween: 10
            },

            480: {
                slidesPerView: 2,
                spaceBetween: 12
            },

            768: {
                slidesPerView: 3,
                spaceBetween: 16
            },

            1024: {
                slidesPerView: 4,
                spaceBetween: 16
            },

            1280: {
                slidesPerView: 6,
                spaceBetween: 20
            }
        },

        observer: true,
        observeParents: true
    });

    const box = document.getElementById("productBox");
    const buttons = document.querySelectorAll("#wcTabs button");

    function loadProducts(cat = "all") {

        box.innerHTML = `<div class="wc-loader">در حال دریافت ...</div>`;

        const formData = new FormData();
        formData.append("action", "wc_filter_products");
        formData.append("cat", cat);

        fetch(wc_ajax.url, {
            method: "POST",
            body: formData
        })
            .then(res => res.text())
            .then(html => {

                box.innerHTML = html;

                productSwiper.update();
                productSwiper.slideTo(0);

            });
    }

    // 💥 LOAD PRODUCTS ON PAGE LOAD (THIS FIXES YOUR ISSUE)
    loadProducts("all");

    buttons.forEach(btn => {

        btn.addEventListener("click", function () {

            buttons.forEach(b => b.classList.remove("active"));
            this.classList.add("active");

            loadProducts(this.dataset.cat);

        });

    });

});


document.addEventListener('DOMContentLoaded', function () {

    const brandEl = document.querySelector(".brandSwiper");

    if (!brandEl) return;

    new Swiper(".brandSwiper", {
        slidesPerView: 5,
        spaceBetween: 20,
        loop: false,
        watchOverflow: true,
        navigation: {
            nextEl: ".brand-next",
            prevEl: ".brand-prev",
        },

        breakpoints: {
            320: { slidesPerView: 2.35, spaceBetween: 14 },
            768: { slidesPerView: 4, spaceBetween: 18 },
            1024: { slidesPerView: 6, spaceBetween: 28 }
        }
    });

});

document.addEventListener("DOMContentLoaded", function () {
    const testimonialEl = document.querySelector(".testimonialSwiper");
    if (!testimonialEl) return;

    new Swiper(".testimonialSwiper", {
        slidesPerView: 1,
        spaceBetween: 20,
        loop: true,
        autoHeight: false,

        navigation: {
            nextEl: ".testimonial-next",
            prevEl: ".testimonial-prev",
        },


    });
});

document.addEventListener("DOMContentLoaded", function () {

    new Swiper(".greenProductSwiper", {

        slidesPerView: 3,
        spaceBetween: 15,

        navigation: {
            nextEl: ".green-next",
            prevEl: ".green-prev",
        },

        breakpoints: {
            320: { slidesPerView: 2, spaceBetween: 14 },
            640: { slidesPerView: 2, spaceBetween: 16 },
            861: { slidesPerView: 3, spaceBetween: 18 },
            1100: { slidesPerView: 4, spaceBetween: 15 }
        }
    });

});
