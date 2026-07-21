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

// Mobile navbar burger menu
// Delegated on `document` (rather than wired once on the elements captured at module load)
// so it keeps working after Turbo swaps in a new navbar without a full reload.
const closeNavbar = () => {
    const navbarCollapse = document.getElementById('navbar-collapse');
    const navbarToggle = document.getElementById('navbar-toggle');
    if (!navbarCollapse || !navbarToggle) return;
    navbarCollapse.classList.remove('navbar__collapse--open');
    navbarToggle.classList.remove('active');
    navbarToggle.setAttribute('aria-expanded', 'false');
};
document.addEventListener('click', (e) => {
    const navbarToggle = document.getElementById('navbar-toggle');
    const navbarCollapse = document.getElementById('navbar-collapse');
    if (!navbarToggle || !navbarCollapse) return;
    if (navbarToggle.contains(e.target)) {
        const isOpen = navbarCollapse.classList.toggle('navbar__collapse--open');
        navbarToggle.classList.toggle('active', isOpen);
        navbarToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        return;
    }
    if (!navbarCollapse.classList.contains('navbar__collapse--open')) return;
    if (!navbarCollapse.contains(e.target)) closeNavbar();
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeNavbar();
});
