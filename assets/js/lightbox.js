// Full-size photo viewer: click any `.js-lightbox` image to open, click anywhere
// except the enlarged image (backdrop or close button) or press Escape to close.
document.addEventListener('click', function (event) {
    const trigger = event.target.closest('.js-lightbox');
    if (trigger) {
        const overlay = document.querySelector('.lightbox');
        const img = overlay.querySelector('.lightbox__img');
        img.src = trigger.src;
        img.alt = trigger.alt || '';
        overlay.classList.add('is-open');
        return;
    }

    const overlay = event.target.closest('.lightbox');
    if (overlay && !event.target.closest('.lightbox__img')) {
        overlay.classList.remove('is-open');
    }
});

document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') {
        return;
    }
    const overlay = document.querySelector('.lightbox.is-open');
    if (overlay) {
        overlay.classList.remove('is-open');
    }
});
