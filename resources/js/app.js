// Livewire 4 provides Alpine for the interactive application components.

let purchaseAudioContext;
let purchaseToastTimer;

document.addEventListener('click', (event) => {
    if (!event.target.closest('[data-purchase-submit]')) {
        return;
    }

    const AudioContextClass = window.AudioContext || window.webkitAudioContext;

    if (!AudioContextClass) {
        return;
    }

    purchaseAudioContext ??= new AudioContextClass();
    purchaseAudioContext.resume().catch(() => {});
}, true);

window.addEventListener('purchase-added', (event) => {
    const toast = document.getElementById('purchase-success-toast');
    const message = document.getElementById('purchase-success-message');

    if (toast && message) {
        message.textContent = event.detail.message || 'Barang berhasil ditambahkan ke transaksi.';
        toast.classList.add('is-visible');
        toast.setAttribute('aria-hidden', 'false');
        window.clearTimeout(purchaseToastTimer);
        purchaseToastTimer = window.setTimeout(() => {
            toast.classList.remove('is-visible');
            toast.setAttribute('aria-hidden', 'true');
        }, 4200);
    }

    playPurchaseChime();
});

document.addEventListener('click', (event) => {
    if (!event.target.closest('[data-dismiss-purchase-toast]')) {
        return;
    }

    const toast = document.getElementById('purchase-success-toast');

    if (toast) {
        toast.classList.remove('is-visible');
        toast.setAttribute('aria-hidden', 'true');
    }
    window.clearTimeout(purchaseToastTimer);
});

function playPurchaseChime() {
    if (!purchaseAudioContext || purchaseAudioContext.state !== 'running') {
        return;
    }

    const startTime = purchaseAudioContext.currentTime + 0.02;

    [880, 1318.5].forEach((frequency, index) => {
        const oscillator = purchaseAudioContext.createOscillator();
        const gain = purchaseAudioContext.createGain();
        const noteStart = startTime + index * 0.12;

        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(frequency, noteStart);
        gain.gain.setValueAtTime(0.0001, noteStart);
        gain.gain.exponentialRampToValueAtTime(0.16, noteStart + 0.025);
        gain.gain.exponentialRampToValueAtTime(0.0001, noteStart + 0.28);
        oscillator.connect(gain);
        gain.connect(purchaseAudioContext.destination);
        oscillator.start(noteStart);
        oscillator.stop(noteStart + 0.3);
    });
}
