// Preview the chosen file on any `.edit-avatar-input` file input inside its `.edit-avatar-row`
document.addEventListener('change', function (event) {
    const input = event.target.closest('.edit-avatar-input');
    if (!input) {
        return;
    }
    const file = input.files[0];
    if (!file) {
        return;
    }

    const preview = input.closest('.edit-avatar-row').querySelector('.edit-avatar-preview');
    const reader = new FileReader();
    reader.onload = function (e) {
        if (preview.tagName === 'IMG') {
            preview.src = e.target.result;
        } else {
            const img = document.createElement('img');
            img.className = 'edit-avatar-preview';
            img.alt = 'Photo de profil';
            img.src = e.target.result;
            preview.replaceWith(img);
        }
    };
    reader.readAsDataURL(file);
});
