// Carousel / slider otomatis (auto-slide + crossfade). Efek Ken Burns ditangani
// via CSS (lihat resources/css/app.css); JS hanya berpindah slide aktif.
// Markup: resources/views/partials/slider.blade.php.

function initSlider(root) {
    const slides = Array.from(root.querySelectorAll('[data-slide]'));
    if (slides.length === 0) return;

    const dots = Array.from(root.querySelectorAll('[data-dot]'));
    const interval = parseInt(root.dataset.interval, 10) || 5000;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    let current = slides.findIndex((s) => s.classList.contains('is-active'));
    if (current < 0) current = 0;
    let timer = null;

    function show(index) {
        current = (index + slides.length) % slides.length;

        slides.forEach((slide, i) => {
            const active = i === current;
            slide.classList.toggle('is-active', active);
            slide.toggleAttribute('aria-hidden', !active);
        });

        dots.forEach((dot, i) => dot.classList.toggle('is-active', i === current));
    }

    const next = () => show(current + 1);
    const prev = () => show(current - 1);

    function start() {
        if (timer || reduceMotion || slides.length < 2) return;
        timer = window.setInterval(next, interval);
    }

    function stop() {
        if (timer) {
            window.clearInterval(timer);
            timer = null;
        }
    }

    // Reset timer setelah interaksi manual agar tak langsung berpindah.
    function restart() {
        stop();
        start();
    }

    root.querySelector('[data-slider-next]')?.addEventListener('click', () => {
        next();
        restart();
    });
    root.querySelector('[data-slider-prev]')?.addEventListener('click', () => {
        prev();
        restart();
    });
    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            show(parseInt(dot.dataset.dot, 10) || 0);
            restart();
        });
    });

    // Jeda auto-slide saat hover atau saat fokus keyboard di dalam slider.
    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    root.addEventListener('focusin', stop);
    root.addEventListener('focusout', start);

    // Jeda saat tab tak terlihat, lanjut saat kembali.
    document.addEventListener('visibilitychange', () => {
        document.hidden ? stop() : start();
    });

    show(current);
    start();
}

// Count-up angka statistik: menghitung 0 → nilai saat elemen masuk viewport.
// Markup: resources/views/partials/stats.blade.php ([data-countup]).
function initCountUp() {
    const nums = Array.from(document.querySelectorAll('[data-countup]'));
    if (nums.length === 0) return;

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const run = (el) => {
        const target = parseInt(el.dataset.countup, 10) || 0;
        const suffix = el.dataset.suffix || '';

        if (reduceMotion) {
            el.textContent = target + suffix;
            return;
        }

        const duration = 1600;
        const startTime = performance.now();

        const tick = (now) => {
            const progress = Math.min((now - startTime) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3); // easeOutCubic
            el.textContent = Math.round(target * eased) + suffix;
            if (progress < 1) requestAnimationFrame(tick);
            else el.textContent = target + suffix;
        };

        requestAnimationFrame(tick);
    };

    // Fallback bila IntersectionObserver tak tersedia: langsung jalankan.
    if (!('IntersectionObserver' in window)) {
        nums.forEach(run);
        return;
    }

    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                run(entry.target);
                obs.unobserve(entry.target);
            }
        });
    }, { threshold: 0.4 });

    nums.forEach((el) => {
        el.textContent = '0' + (el.dataset.suffix || '');
        observer.observe(el);
    });
}

// Reveal-on-scroll: elemen [data-reveal] memudar & naik halus saat masuk viewport.
// [data-reveal-group] sama, tapi child-child-nya muncul bergantian (cascade) via CSS.
// State awal (tersembunyi) di-CSS gate lewat class .js pada <html>.
function initReveal() {
    const els = Array.from(document.querySelectorAll('[data-reveal], [data-reveal-group]'));
    if (els.length === 0) return;

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reduceMotion || !('IntersectionObserver' in window)) {
        els.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                obs.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });

    els.forEach((el) => observer.observe(el));
}

// Lightbox galeri (foto & video). Markup global di layouts/app.blade.php,
// kartu pemicu di partials/gallery-item.blade.php ([data-gallery-item]).
// Video YouTube diputar langsung di sini via iframe embed saat dibuka —
// tidak pernah membuka tab/redirect ke youtube.com.
function initGalleryLightbox() {
    const lightbox = document.querySelector('[data-gallery-lightbox]');
    if (!lightbox) return;

    const content = lightbox.querySelector('[data-gallery-content]');

    function open(item) {
        content.innerHTML = '';

        if (item.dataset.type === 'video' && item.dataset.embedUrl) {
            const iframe = document.createElement('iframe');
            iframe.src = item.dataset.embedUrl;
            iframe.className = 'aspect-video w-full rounded-lg';
            iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
            iframe.allowFullscreen = true;
            content.appendChild(iframe);
        } else {
            const img = document.createElement('img');
            img.src = item.dataset.fullSrc;
            img.alt = item.dataset.title || '';
            img.className = 'max-h-[85vh] w-full rounded-lg object-contain';
            content.appendChild(img);
        }

        lightbox.classList.remove('hidden');
        lightbox.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function close() {
        lightbox.classList.add('hidden');
        lightbox.classList.remove('flex');
        content.innerHTML = ''; // hentikan pemutaran video saat ditutup
        document.body.classList.remove('overflow-hidden');
    }

    document.querySelectorAll('[data-gallery-item]').forEach((btn) => {
        btn.addEventListener('click', () => open(btn));
    });

    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) close();
    });
    lightbox.querySelector('[data-gallery-close]')?.addEventListener('click', close);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !lightbox.classList.contains('hidden')) close();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-slider]').forEach(initSlider);
    initCountUp();
    initReveal();
    initGalleryLightbox();
});
