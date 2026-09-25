// Rebuild the `.js-registration-teams` checkbox list from the `data-teams` JSON of the selected city option.
// Shows a placeholder text when no city is chosen or the city has no team.
function refreshTeams(container) {
    const citySelect = document.querySelector(container.dataset.citySelect);
    const cityOption = citySelect.selectedOptions[0];
    const teams = cityOption && cityOption.dataset.teams ? JSON.parse(cityOption.dataset.teams) : null;
    const checked = new Set(Array.from(container.querySelectorAll('input:checked'), (input) => input.value));

    container.replaceChildren();
    if (!teams || teams.length === 0) {
        const placeholder = document.createElement('p');
        placeholder.className = 'text-muted text-sm mb-0';
        placeholder.textContent = teams ? container.dataset.placeholderNoTeam : container.dataset.placeholderNoCity;
        container.append(placeholder);
        return;
    }
    teams.sort((a, b) => a.name.localeCompare(b.name)).forEach((team) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'form-check';
        const input = document.createElement('input');
        input.type = 'checkbox';
        input.className = 'form-check-input';
        input.id = `${container.id}_${team.id}`;
        input.name = container.dataset.name;
        input.value = String(team.id);
        input.checked = checked.has(input.value);
        const label = document.createElement('label');
        label.className = 'form-check-label';
        label.htmlFor = input.id;
        label.textContent = team.name;
        wrapper.append(input, label);
        container.append(wrapper);
    });
}

document.addEventListener('change', function (event) {
    document.querySelectorAll('.js-registration-teams').forEach(function (container) {
        if (event.target.matches(container.dataset.citySelect)) {
            refreshTeams(container);
        }
    });
});

document.addEventListener('turbo:load', function () {
    document.querySelectorAll('.js-registration-teams').forEach(refreshTeams);
});
