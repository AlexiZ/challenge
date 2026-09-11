// Click-to-sort table headers: `<table>` with `<th data-sort data-type="number|text">`.
// Delegated on `document` so it keeps working after Turbo swaps in the table without a full reload.
document.addEventListener('click', function (event) {
    const th = event.target.closest('th[data-sort]');
    if (!th) {
        return;
    }

    const table = th.closest('table');
    const tbody = table.querySelector('tbody');
    if (!tbody) {
        return;
    }

    const headerRow = th.parentNode;
    const cellIndex = Array.from(headerRow.children).indexOf(th);
    const direction = th.dataset.sortDir === 'desc' ? 'asc' : 'desc';

    Array.from(headerRow.children).forEach((h) => delete h.dataset.sortDir);
    th.dataset.sortDir = direction;

    const type = th.dataset.type || 'text';
    const rows = Array.from(tbody.querySelectorAll('tr'));

    rows.sort((rowA, rowB) => {
        const cellA = rowA.children[cellIndex];
        const cellB = rowB.children[cellIndex];
        const valueA = cellA?.dataset.value ?? cellA?.textContent.trim() ?? '';
        const valueB = cellB?.dataset.value ?? cellB?.textContent.trim() ?? '';

        const comparison = type === 'number'
            ? parseFloat(valueA) - parseFloat(valueB)
            : valueA.localeCompare(valueB, 'fr', { sensitivity: 'base' });

        return direction === 'asc' ? comparison : -comparison;
    });

    rows.forEach((row) => tbody.appendChild(row));
});
