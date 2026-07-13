// Give the option wrapping a checked radio an "active" class within a `[data-radio-group]`.
// The active class name can be overridden per group with `data-active-class`.
document.addEventListener('change', function (event) {
    if (event.target.type !== 'radio') {
        return;
    }
    const option = event.target.closest('.js-radio-toggle-option');
    if (!option) {
        return;
    }
    const group = option.closest('[data-radio-group]');
    const activeClass = group.dataset.activeClass || 'active';
    group.querySelectorAll('.js-radio-toggle-option').forEach(function (opt) {
        opt.classList.remove(activeClass);
    });
    option.classList.add(activeClass);
});
