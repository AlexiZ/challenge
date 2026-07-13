// Navigate to `data-href` when clicking anywhere on a row/element carrying it
document.addEventListener('click', function (event) {
    const target = event.target.closest('[data-href]');
    if (target) {
        window.location.href = target.dataset.href;
    }
});
