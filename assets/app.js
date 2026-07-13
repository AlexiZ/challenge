import './stimulus_bootstrap.js';
import 'bootstrap';
import './styles/app.css';
import './js/confirm-submit.js';
import './js/row-link.js';
import './js/reveal-more.js';
import './js/radio-toggle.js';
import './js/color-swatch.js';
import './js/avatar-preview.js';
import './js/bonus-photo.js';
import './js/lightbox.js';

// On mobile, the user-menu must stay permanently expanded (not a click-gated dropdown)
const mobileUserMenuQuery = window.matchMedia('(max-width: 767px)');
const syncMobileUserMenu = () => {
    if (!mobileUserMenuQuery.matches) return;
    document.querySelectorAll('.navbar__actions .user-menu').forEach((d) => d.setAttribute('open', ''));
};
syncMobileUserMenu();
mobileUserMenuQuery.addEventListener('change', syncMobileUserMenu);

// Close user-menu dropdown when clicking outside (desktop only — always open on mobile)
document.addEventListener('click', (e) => {
    document.querySelectorAll('details.user-menu[open]').forEach((d) => {
        if (mobileUserMenuQuery.matches) return;
        if (!d.contains(e.target)) d.removeAttribute('open');
    });
});

// Mobile navbar burger menu
const navbarToggle = document.getElementById('navbar-toggle');
const navbarCollapse = document.getElementById('navbar-collapse');
if (navbarToggle && navbarCollapse) {
    const closeNavbar = () => {
        navbarCollapse.classList.remove('navbar__collapse--open');
        navbarToggle.classList.remove('active');
        navbarToggle.setAttribute('aria-expanded', 'false');
    };
    navbarToggle.addEventListener('click', () => {
        const isOpen = navbarCollapse.classList.toggle('navbar__collapse--open');
        navbarToggle.classList.toggle('active', isOpen);
        navbarToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
    document.addEventListener('click', (e) => {
        if (!navbarCollapse.classList.contains('navbar__collapse--open')) return;
        if (!navbarCollapse.contains(e.target) && !navbarToggle.contains(e.target)) closeNavbar();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeNavbar();
    });
}
