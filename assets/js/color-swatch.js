// Live hex preview for `.adm-color-swatch` color inputs, with `.adm-color-reset-btn` reset buttons.
// Delegated on `document` (rather than wired once on DOMContentLoaded) so it keeps
// working after Turbo swaps in this page's content without a full reload.
function syncColorPreview(input) {
    input.nextElementSibling.textContent = input.value.toUpperCase();
}

function initColorPreviews() {
    document.querySelectorAll('.adm-color-swatch').forEach(syncColorPreview);
}

document.addEventListener('DOMContentLoaded', initColorPreviews);
document.addEventListener('turbo:load', initColorPreviews);

document.addEventListener('input', function (event) {
    const input = event.target.closest('.adm-color-swatch');
    if (input) {
        syncColorPreview(input);
    }
});

document.addEventListener('click', function (event) {
    const button = event.target.closest('.adm-color-reset-btn');
    if (!button) {
        return;
    }
    const input = button.closest('.adm-color-field-row').querySelector('.adm-color-swatch');
    input.value = button.dataset.resetValue;
    input.dispatchEvent(new Event('input'));
});
