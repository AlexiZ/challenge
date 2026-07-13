// Photo submission dropzone + gallery filter.
// Delegated on `document` (rather than wired once on DOMContentLoaded) so it keeps
// working after Turbo swaps in this page's content without a full reload.

function showDropzonePreview(dropzone, file) {
    const previewImg = dropzone.querySelector('.bp-preview-img');
    const nameEl = dropzone.querySelector('.bp-preview-name');
    const idleEl = dropzone.querySelector('.bp-dropzone-idle');
    const previewEl = dropzone.querySelector('.bp-dropzone-preview');
    const reader = new FileReader();
    reader.onload = function (e) {
        previewImg.src = e.target.result;
        nameEl.textContent = file.name;
        idleEl.style.display = 'none';
        previewEl.style.display = 'flex';
    };
    reader.readAsDataURL(file);
}

function resetDropzone(dropzone) {
    const fileInput = dropzone.querySelector('.bp-photo-input');
    fileInput.value = '';
    dropzone.querySelector('.bp-preview-img').src = '';
    dropzone.querySelector('.bp-preview-name').textContent = '';
    dropzone.querySelector('.bp-dropzone-idle').style.display = '';
    dropzone.querySelector('.bp-dropzone-preview').style.display = 'none';
}

document.addEventListener('click', function (event) {
    const resetBtn = event.target.closest('.bp-preview-reset');
    if (resetBtn) {
        event.stopPropagation();
        resetDropzone(resetBtn.closest('.bp-dropzone'));
        return;
    }

    const dropzone = event.target.closest('.bp-dropzone');
    if (dropzone) {
        dropzone.querySelector('.bp-photo-input').click();
    }
});

document.addEventListener('change', function (event) {
    const fileInput = event.target.closest('.bp-photo-input');
    if (fileInput && fileInput.files[0]) {
        showDropzonePreview(fileInput.closest('.bp-dropzone'), fileInput.files[0]);
    }
});

document.addEventListener('dragenter', function (event) {
    const dropzone = event.target.closest('.bp-dropzone');
    if (!dropzone) {
        return;
    }
    event.preventDefault();
    dropzone.dragCounter = (dropzone.dragCounter || 0) + 1;
    dropzone.classList.add('bp-dropzone--over');
});

document.addEventListener('dragleave', function (event) {
    const dropzone = event.target.closest('.bp-dropzone');
    if (!dropzone) {
        return;
    }
    dropzone.dragCounter = (dropzone.dragCounter || 1) - 1;
    if (dropzone.dragCounter <= 0) {
        dropzone.dragCounter = 0;
        dropzone.classList.remove('bp-dropzone--over');
    }
});

document.addEventListener('dragover', function (event) {
    if (event.target.closest('.bp-dropzone')) {
        event.preventDefault();
    }
});

document.addEventListener('drop', function (event) {
    const dropzone = event.target.closest('.bp-dropzone');
    if (!dropzone) {
        return;
    }
    event.preventDefault();
    dropzone.dragCounter = 0;
    dropzone.classList.remove('bp-dropzone--over');

    const files = event.dataTransfer.files;
    if (!files.length) {
        return;
    }
    const fileInput = dropzone.querySelector('.bp-photo-input');
    const dt = new DataTransfer();
    dt.items.add(files[0]);
    fileInput.files = dt.files;
    showDropzonePreview(dropzone, files[0]);
});

// Gallery filter tabs + progressive reveal
function applyGalleryVisibility(gallery) {
    const cards = Array.from(gallery.querySelectorAll('.bp-photo-card'));
    const seeMoreSection = gallery.querySelector('.bp-see-more');
    const seeMoreBtn = gallery.querySelector('.bp-see-more-btn');
    const currentFilter = gallery.dataset.currentFilter || 'all';
    const allVisible = gallery.dataset.allVisible === 'true';

    let count = 0;
    cards.forEach(function (card) {
        const matchesFilter = currentFilter === 'all' || card.dataset.challenge === currentFilter;
        const withinLimit = allVisible || count < 6;
        if (matchesFilter && withinLimit) {
            card.classList.remove('bp-hidden');
            count++;
        } else {
            card.classList.add('bp-hidden');
        }
    });

    const remaining = cards.filter(function (c) {
        return currentFilter === 'all' || c.dataset.challenge === currentFilter;
    }).length - count;

    if (seeMoreSection) {
        seeMoreSection.style.display = (!allVisible && remaining > 0) ? '' : 'none';
    }
    if (seeMoreBtn && remaining > 0) {
        seeMoreBtn.textContent = '↓ Voir les ' + remaining + ' autres photos';
    }
}

document.addEventListener('click', function (event) {
    const tab = event.target.closest('.bp-filter-tab');
    if (tab) {
        const gallery = tab.closest('.bp-gallery');
        gallery.querySelectorAll('.bp-filter-tab').forEach(function (t) {
            t.classList.remove('active');
        });
        tab.classList.add('active');
        gallery.dataset.currentFilter = tab.dataset.filter;
        gallery.dataset.allVisible = 'false';
        applyGalleryVisibility(gallery);
        return;
    }

    const seeMoreBtn = event.target.closest('.bp-see-more-btn');
    if (seeMoreBtn) {
        const gallery = seeMoreBtn.closest('.bp-gallery');
        gallery.dataset.allVisible = 'true';
        applyGalleryVisibility(gallery);
    }
});

function initGalleries() {
    document.querySelectorAll('.bp-gallery').forEach(applyGalleryVisibility);
}

document.addEventListener('DOMContentLoaded', initGalleries);
document.addEventListener('turbo:load', initGalleries);
