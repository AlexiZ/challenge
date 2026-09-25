// Toggle the row's checkbox when clicking anywhere on a `data-row-toggle` element,
// except on form controls and labels (they already handle the click natively).
document.addEventListener('click', function (event) {
    const row = event.target.closest('[data-row-toggle]');
    if (!row || event.target.closest('input, label, select, textarea, button, a')) {
        return;
    }
    const checkbox = row.querySelector('input[type="checkbox"]');
    if (checkbox && !checkbox.disabled) {
        checkbox.click();
    }
});
