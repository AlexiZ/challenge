// Toggle a `.password-field` input between type="password" and type="text" via its eye button.
document.addEventListener('click', function (event) {
    const btn = event.target.closest('[data-password-toggle]');
    if (!btn) {
        return;
    }
    const input = btn.closest('.password-field').querySelector('input');
    const icon = btn.querySelector('i');
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    icon.classList.toggle('fa-eye', !isHidden);
    icon.classList.toggle('fa-eye-slash', isHidden);
    btn.setAttribute('aria-label', isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
});
