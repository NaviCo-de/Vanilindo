import './bootstrap';

document.querySelectorAll('[data-blog-carousel]').forEach((carousel) => {
    const track = carousel.querySelector('[data-blog-track]');
    const slides = [...carousel.querySelectorAll('[data-blog-slide]')];
    const previous = carousel.querySelector('[data-blog-previous]');
    const next = carousel.querySelector('[data-blog-next]');
    const status = carousel.querySelector('[data-blog-status]');
    if (!track || !slides.length) return;

    let index = 0;
    let destination = 0;
    const slideLeft = (slide) => slide.offsetLeft - slides[0].offsetLeft;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const updateControls = () => {
        index = slides.reduce((nearest, slide, candidate) =>
            Math.abs(slideLeft(slide) - track.scrollLeft) < Math.abs(slideLeft(slides[nearest]) - track.scrollLeft) ? candidate : nearest, 0);
        previous.disabled = index === 0;
        next.disabled = index === slides.length - 1;
        status.textContent = `${index + 1} of ${slides.length}`;
    };
    const goTo = (target) => {
        destination = Math.max(0, Math.min(target, slides.length - 1));
        track.scrollTo({ left: slideLeft(slides[destination]), behavior: reducedMotion.matches ? 'instant' : 'smooth' });
    };

    previous.addEventListener('click', () => goTo(Math.min(index, destination) - 1));
    next.addEventListener('click', () => goTo(Math.max(index, destination) + 1));
    track.addEventListener('scroll', updateControls, { passive: true });
    track.addEventListener('scrollend', () => { destination = index; });
    track.addEventListener('keydown', (event) => {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        const target = event.key === 'Home' ? 0 : event.key === 'End' ? slides.length - 1
            : event.key === 'ArrowLeft' ? Math.min(index, destination) - 1 : Math.max(index, destination) + 1;
        goTo(target);
    });
    new ResizeObserver(() => {
        track.scrollTo({ left: slideLeft(slides[index]), behavior: 'instant' });
        destination = index;
        updateControls();
    }).observe(track);
    updateControls();
});

const menuToggle = document.querySelector('.menu-toggle');
const mobileNav = document.querySelector('#mobile-nav');
const headerInner = document.querySelector('.header-inner');

const closeMenu = () => {
    if (!menuToggle || !mobileNav) return;
    menuToggle.setAttribute('aria-expanded', 'false');
    menuToggle.setAttribute('aria-label', 'Open menu');
    mobileNav.hidden = true;
};

if (menuToggle && mobileNav) {
    menuToggle.addEventListener('click', () => {
        const expanded = menuToggle.getAttribute('aria-expanded') === 'true';
        menuToggle.setAttribute('aria-expanded', String(!expanded));
        menuToggle.setAttribute('aria-label', expanded ? 'Open menu' : 'Close menu');
        mobileNav.hidden = expanded;
    });

    mobileNav.addEventListener('click', (event) => {
        if (event.target.closest('a')) closeMenu();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !mobileNav.hidden) {
            closeMenu();
            menuToggle.focus();
        }
    });

    window.matchMedia('(min-width: 1001px)').addEventListener('change', closeMenu);
}

if (headerInner) {
    const updateHeaderHeight = () => {
        document.documentElement.style.setProperty('--header-height', `${headerInner.getBoundingClientRect().height}px`);
    };
    updateHeaderHeight();
    new ResizeObserver(updateHeaderHeight).observe(headerInner);
}

const sections = [...document.querySelectorAll('[data-scroll-section]')];
const navigationLinks = [...document.querySelectorAll('[data-nav-section]')];

if (document.querySelector('#home') && sections.length) {
    let scheduled = false;
    const updateActiveSection = () => {
        const headerHeight = headerInner?.getBoundingClientRect().height || 0;
        let activeId = 'home';

        for (const section of sections) {
            if (section.getBoundingClientRect().top <= headerHeight + 8) activeId = section.id;
        }
        if (window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 2) activeId = 'contact';

        navigationLinks.forEach((link) => {
            if (link.dataset.navSection === activeId) link.setAttribute('aria-current', 'location');
            else link.removeAttribute('aria-current');
        });
        scheduled = false;
    };
    const scheduleUpdate = () => {
        if (!scheduled) {
            scheduled = true;
            requestAnimationFrame(updateActiveSection);
        }
    };

    window.addEventListener('scroll', scheduleUpdate, { passive: true });
    window.addEventListener('resize', scheduleUpdate);
    window.addEventListener('hashchange', scheduleUpdate);
    document.fonts.ready.then(scheduleUpdate);
    scheduleUpdate();

    // Font swaps can change stacked section heights after the browser's initial fragment jump.
    const initialHash = window.location.hash;
    if (initialHash) {
        window.addEventListener('load', () => {
            document.fonts.ready.then(() => requestAnimationFrame(() => {
                if (window.location.hash !== initialHash) return;
                const target = sections.find((section) => `#${section.id}` === initialHash);
                target?.scrollIntoView({ behavior: 'instant', block: 'start' });
                scheduleUpdate();
            }));
        }, { once: true });
    }
}
