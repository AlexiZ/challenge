// Ask for confirmation before submitting any form with a `data-confirm` message
document.addEventListener('submit', function (event) {
    const message = event.target.dataset.confirm;
    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});
