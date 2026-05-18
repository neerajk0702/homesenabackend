/* =========================
   DOCUMENT READY
========================= */

$(document).ready(function () {
    /* =========================
       NAVBAR SCROLL + BACK TO TOP
    ========================= */

    var kawa = $(".top-bar");
    var back = $("#back-to-top");

    function scrollFunction() {
        if ($(window).scrollTop() > 700) {
            kawa.addClass("fixed");
            back.addClass("show-top");
        } else {
            kawa.removeClass("fixed");
            back.removeClass("show-top");
        }
    }

    $(window).on("scroll", scrollFunction);

    /* =========================
       SMOOTH SCROLL
    ========================= */

    $('a[href*="#"]:not([href="#"])').click(function () {
        if (
            location.pathname.replace(/^\//, "") ===
                this.pathname.replace(/^\//, "") &&
            location.hostname === this.hostname
        ) {
            var target = $(this.hash);
            target = target.length
                ? target
                : $("[name=" + this.hash.slice(1) + "]");

            if (target.length) {
                $("html, body").animate(
                    {
                        scrollTop: target.offset().top,
                    },
                    800,
                );

                return false;
            }
        }
    });
});

/* =========================
   FAQ TOGGLE
========================= */

document.querySelectorAll(".faq-question").forEach(function (btn) {
    btn.addEventListener("click", function () {
        let item = this.parentElement;

        document.querySelectorAll(".faq-item").forEach(function (faq) {
            if (faq !== item) {
                faq.classList.remove("active");
            }
        });

        item.classList.toggle("active");
    });
});

/* =========================
   ARC FOCUS CAROUSEL
========================= */
(function () {
    var carousel = document.getElementById("arcCarousel");
    if (!carousel) return;

    var viewport = carousel.querySelector(".arc-carousel__viewport");
    var items = carousel.querySelectorAll(".arc-carousel__item");
    var titleEl = document.getElementById("arcActiveTitle");
    var subtitleEl = document.getElementById("arcActiveSubtitle");
    var prevBtn = document.getElementById("arcPrev");
    var nextBtn = document.getElementById("arcNext");
    var dotsContainer = document.getElementById("arcDots");
    var totalItems = items.length;
    var activeIndex = 0;
    var isDragging = false;
    var dragStartX = 0;
    var dragDelta = 0;
    var dragFractional = 0;

    /* service info per card */
    var serviceData = [
        { title: "Bathroom Cleaning", subtitle: "Sparkling clean bathrooms with deep sanitization" },
        { title: "Laundry", subtitle: "Fresh, folded laundry delivered with care" },
        { title: "Utensils", subtitle: "Spotless dishes and utensil cleaning service" },
        { title: "Outdoor Cleaning", subtitle: "Pristine outdoor spaces, patios and driveways" },
        { title: "Kitchen Cleaning", subtitle: "Hygienic kitchen deep-clean from top to bottom" },
        { title: "Window Cleaning", subtitle: "Crystal-clear windows inside and out" },
        { title: "Sweeping", subtitle: "Thorough floor sweeping for every room" },
        { title: "Fan Cleaning", subtitle: "Dust-free fans for fresh, clean air" },
        { title: "Kitchen Prep", subtitle: "Meal prep assistance and kitchen organization" }
    ];

    /* ---- SPRING PHYSICS ---- */
    function SpringValue(stiffness, damping) {
        this.stiffness = stiffness || 280;
        this.damping = damping || 26;
        this.position = 0;
        this.velocity = 0;
        this.target = 0;
    }

    SpringValue.prototype.setTarget = function (t) {
        this.target = t;
    };

    SpringValue.prototype.update = function (dt) {
        var force = (this.target - this.position) * this.stiffness;
        var damp = -this.velocity * this.damping;
        this.velocity += (force + damp) * dt;
        this.position += this.velocity * dt;
    };

    SpringValue.prototype.isAtRest = function () {
        return Math.abs(this.target - this.position) < 0.001 &&
               Math.abs(this.velocity) < 0.01;
    };

    /* create springs for each item: x, y, scale, opacity, rotation */
    var springs = [];
    for (var i = 0; i < totalItems; i++) {
        springs.push({
            x: new SpringValue(250, 24),
            y: new SpringValue(250, 24),
            scale: new SpringValue(300, 28),
            opacity: new SpringValue(200, 22),
            rotate: new SpringValue(280, 26)
        });
    }

    /* ---- LAYOUT CONFIG ---- */
    function getConfig() {
        var w = window.innerWidth;
        if (w <= 480) return { spacing: 160, arcHeight: 45, scaleStep: 0.12, opacityStep: 0.20, rotateStep: 12 };
        if (w <= 768) return { spacing: 200, arcHeight: 55, scaleStep: 0.10, opacityStep: 0.18, rotateStep: 14 };
        if (w <= 992) return { spacing: 250, arcHeight: 65, scaleStep: 0.09, opacityStep: 0.16, rotateStep: 15 };
        if (w <= 1200) return { spacing: 290, arcHeight: 75, scaleStep: 0.08, opacityStep: 0.14, rotateStep: 16 };
        return { spacing: 320, arcHeight: 85, scaleStep: 0.07, opacityStep: 0.12, rotateStep: 18 };
    }

    /* ---- CALCULATE TARGETS (with wrapping for infinite loop) ---- */
    function wrapOffset(offset, total) {
        /* find shortest-path offset: e.g. for 9 items, offset 8 becomes -1 */
        while (offset > total / 2) offset -= total;
        while (offset < -total / 2) offset += total;
        return offset;
    }

    function updateTargets(fractionalIndex) {
        var cfg = getConfig();
        for (var i = 0; i < totalItems; i++) {
            var offset = wrapOffset(i - fractionalIndex, totalItems);
            var absOff = Math.abs(offset);
            var x = offset * cfg.spacing;
            var y = Math.pow(absOff, 1.4) * cfg.arcHeight; /* gentle curve, not steep quadratic */
            var s = Math.max(0.55, 1 - absOff * cfg.scaleStep);
            var o = Math.max(0.25, 1 - absOff * cfg.opacityStep);
            var r = offset * cfg.rotateStep; /* tilt: left items rotate negative, right positive */

            springs[i].x.setTarget(x);
            springs[i].y.setTarget(y);
            springs[i].scale.setTarget(s);
            springs[i].opacity.setTarget(o);
            springs[i].rotate.setTarget(r);
        }
    }

    /* ---- RENDER ---- */
    function render() {
        var itemWidth = items[0].offsetWidth;
        var halfW = itemWidth / 2;
        for (var i = 0; i < totalItems; i++) {
            var sp = springs[i];
            var tx = sp.x.position - halfW;
            var ty = sp.y.position;
            var sc = sp.scale.position;
            var op = sp.opacity.position;
            var rot = sp.rotate.position;
            var wrappedOff = Math.abs(wrapOffset(i - activeIndex, totalItems));
            var zIdx = Math.round((1 - wrappedOff / totalItems) * 100);

            items[i].style.transform = "translate3d(" + tx + "px," + ty + "px,0) scale(" + sc + ") rotate(" + rot + "deg)";
            items[i].style.opacity = Math.max(0, Math.min(1, op));
            items[i].style.zIndex = zIdx;

            /* active class */
            if (i === activeIndex) {
                items[i].classList.add("is-active");
            } else {
                items[i].classList.remove("is-active");
            }
        }
    }

    /* ---- ANIMATION LOOP ---- */
    var animating = false;
    var dt = 1 / 60;

    function tick() {
        var allRest = true;
        for (var i = 0; i < totalItems; i++) {
            var sp = springs[i];
            sp.x.update(dt);
            sp.y.update(dt);
            sp.scale.update(dt);
            sp.opacity.update(dt);
            sp.rotate.update(dt);
            if (!sp.x.isAtRest() || !sp.y.isAtRest() ||
                !sp.scale.isAtRest() || !sp.opacity.isAtRest() ||
                !sp.rotate.isAtRest()) {
                allRest = false;
            }
        }
        render();

        if (allRest) {
            animating = false;
        } else {
            requestAnimationFrame(tick);
        }
    }

    function startAnimation() {
        if (!animating) {
            animating = true;
            requestAnimationFrame(tick);
        }
    }

    /* ---- GO TO INDEX (infinite wrapping) ---- */
    function goTo(idx) {
        activeIndex = ((idx % totalItems) + totalItems) % totalItems;
        updateTargets(activeIndex);
        updateInfo();
        updateDots();
        startAnimation();
        resetAutoPlay();
    }

    /* ---- AUTO-PLAY ---- */
    var autoPlayInterval = null;
    var autoPlayDelay = 3000; /* ms between auto-advances */
    var isHovering = false;

    function startAutoPlay() {
        stopAutoPlay();
        autoPlayInterval = setInterval(function () {
            if (!isDragging && !isHovering) {
                goTo(activeIndex + 1);
            }
        }, autoPlayDelay);
    }

    function stopAutoPlay() {
        if (autoPlayInterval) {
            clearInterval(autoPlayInterval);
            autoPlayInterval = null;
        }
    }

    function resetAutoPlay() {
        startAutoPlay();
    }

    /* pause on hover */
    carousel.addEventListener("mouseenter", function () { isHovering = true; });
    carousel.addEventListener("mouseleave", function () { isHovering = false; });

    /* ---- UPDATE INFO TEXT ---- */
    function updateInfo() {
        if (titleEl && serviceData[activeIndex]) {
            titleEl.style.opacity = 0;
            subtitleEl.style.opacity = 0;
            setTimeout(function () {
                titleEl.textContent = serviceData[activeIndex].title;
                subtitleEl.textContent = serviceData[activeIndex].subtitle;
                titleEl.style.opacity = 1;
                subtitleEl.style.opacity = 1;
            }, 200);
        }
    }

    /* ---- DOTS ---- */
    function createDots() {
        if (!dotsContainer) return;
        dotsContainer.innerHTML = "";
        for (var i = 0; i < totalItems; i++) {
            var dot = document.createElement("button");
            dot.className = "arc-carousel__dot";
            dot.setAttribute("aria-label", "Go to slide " + (i + 1));
            dot.setAttribute("data-idx", i);
            if (i === activeIndex) dot.classList.add("is-active");
            dotsContainer.appendChild(dot);
        }

        dotsContainer.addEventListener("click", function (e) {
            var btn = e.target.closest(".arc-carousel__dot");
            if (btn) goTo(parseInt(btn.getAttribute("data-idx"), 10));
        });
    }

    function updateDots() {
        if (!dotsContainer) return;
        var dots = dotsContainer.querySelectorAll(".arc-carousel__dot");
        for (var i = 0; i < dots.length; i++) {
            if (i === activeIndex) {
                dots[i].classList.add("is-active");
            } else {
                dots[i].classList.remove("is-active");
            }
        }
    }

    /* ---- CLICK TO FOCUS ---- */
    for (var c = 0; c < items.length; c++) {
        (function (idx) {
            items[idx].addEventListener("click", function (e) {
                if (Math.abs(dragDelta) > 5) return;  /* ignore if was dragging */
                goTo(idx);
            });
        })(c);
    }

    /* ---- ARROW BUTTONS ---- */
    if (prevBtn) prevBtn.addEventListener("click", function () { goTo(activeIndex - 1); });
    if (nextBtn) nextBtn.addEventListener("click", function () { goTo(activeIndex + 1); });

    /* ---- KEYBOARD ---- */
    document.addEventListener("keydown", function (e) {
        /* only respond if carousel is somewhat visible */
        var rect = carousel.getBoundingClientRect();
        if (rect.top > window.innerHeight || rect.bottom < 0) return;
        if (e.key === "ArrowLeft") { e.preventDefault(); goTo(activeIndex - 1); }
        if (e.key === "ArrowRight") { e.preventDefault(); goTo(activeIndex + 1); }
    });

    /* ---- SCROLL WHEEL ---- */
    var wheelCooldown = false;
    viewport.addEventListener("wheel", function (e) {
        e.preventDefault();
        if (wheelCooldown) return;
        wheelCooldown = true;

        if (e.deltaY > 0 || e.deltaX > 0) {
            goTo(activeIndex + 1);
        } else {
            goTo(activeIndex - 1);
        }

        setTimeout(function () { wheelCooldown = false; }, 400);
    }, { passive: false });

    /* ---- DRAG / POINTER ---- */

    viewport.addEventListener("pointerdown", function (e) {
        if (e.pointerType === "mouse" && e.button !== 0) return;
        isDragging = true;
        dragStartX = e.clientX;
        dragDelta = 0;
        dragFractional = activeIndex;
        viewport.classList.add("is-dragging");
        viewport.setPointerCapture(e.pointerId);
    });

    viewport.addEventListener("pointermove", function (e) {
        if (!isDragging) return;
        dragDelta = e.clientX - dragStartX;
        var cfg = getConfig();
        var fractional = activeIndex - (dragDelta / cfg.spacing);
        dragFractional = fractional;

        updateTargets(fractional);
        startAnimation();
    });

    viewport.addEventListener("pointerup", function (e) {
        if (!isDragging) return;
        isDragging = false;
        viewport.classList.remove("is-dragging");

        /* snap to nearest (with wrapping) */
        var cfg = getConfig();
        var threshold = cfg.spacing * 0.25;
        var newIdx = activeIndex;

        if (dragDelta < -threshold) {
            newIdx = activeIndex + 1;
        } else if (dragDelta > threshold) {
            newIdx = activeIndex - 1;
        }

        /* fast swipe detection */
        if (Math.abs(dragDelta) > 10) {
            var snapTarget = Math.round(dragFractional);
            newIdx = snapTarget;
        }

        goTo(newIdx);
        dragDelta = 0;
    });

    viewport.addEventListener("pointercancel", function () {
        isDragging = false;
        viewport.classList.remove("is-dragging");
        goTo(activeIndex);
    });

    /* ---- TOUCH: prevent default to avoid page scroll while dragging ---- */
    viewport.addEventListener("touchmove", function (e) {
        if (isDragging) e.preventDefault();
    }, { passive: false });

    /* ---- INIT ---- */
    function init() {
        createDots();
        /* set initial positions instantly (no animation) */
        updateTargets(activeIndex);
        for (var i = 0; i < totalItems; i++) {
            var sp = springs[i];
            sp.x.position = sp.x.target;
            sp.y.position = sp.y.target;
            sp.scale.position = sp.scale.target;
            sp.opacity.position = sp.opacity.target;
            sp.rotate.position = sp.rotate.target;
        }
        render();
        startAutoPlay();
    }

    /* responsive recalc */
    var resizeTimer;
    window.addEventListener("resize", function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            goTo(activeIndex);
        }, 150);
    });

    init();
})();


document.addEventListener("DOMContentLoaded", function () {

    const menuBtn = document.getElementById("menuBtn");
    const mobileMenu = document.getElementById("mobileMenu");
    const closeMenu = document.getElementById("closeMenu");

    if (menuBtn && mobileMenu) {
        menuBtn.addEventListener("click", function () {
            console.log("Menu clicked");
            mobileMenu.style.left = "0";
        });
    }

     closeMenu.addEventListener("click", function () {
        mobileMenu.style.left = "-100%";
    });

    const menuLinks = mobileMenu.querySelectorAll("a");

    menuLinks.forEach((link) => {
        link.addEventListener("click", () => {
            mobileMenu.style.left = "-100%";
        });
    });

});