import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['source', 'icon'];

    async copy() {
        await navigator.clipboard.writeText(this.sourceTarget.textContent.trim());
        this.iconTarget.className = 'fas fa-check';
        setTimeout(() => { this.iconTarget.className = 'fas fa-copy'; }, 1500);
    }
}
