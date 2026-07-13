// Reveal the elements matched by `data-reveal-target` and hide the trigger itself
document.addEventListener('click', function (event) {
    const trigger = event.target.closest('[data-reveal-target]');
    if (!trigger) {
        return;
    }
    const hiddenClass = trigger.dataset.revealHiddenClass || 'd-none';
    document.querySelectorAll(trigger.dataset.revealTarget).forEach(function (el) {
        el.classList.remove(hiddenClass);
    });
    trigger.classList.add('d-none');
});
