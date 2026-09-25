import './bootstrap';

const menuToggle = document.querySelector('.menu-toggle');
const mobileNav = document.querySelector('#mobile-nav');

if (menuToggle && mobileNav) {
    menuToggle.addEventListener('click', () => {
        const expanded = menuToggle.getAttribute('aria-expanded') === 'true';
        menuToggle.setAttribute('aria-expanded', String(!expanded));
        menuToggle.setAttribute('aria-label', expanded ? 'Open menu' : 'Close menu');
        mobileNav.hidden = expanded;
    });
}
