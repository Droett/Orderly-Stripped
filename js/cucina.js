document.addEventListener('DOMContentLoaded', function () {
    const count = parseInt(document.getElementById('count-new').textContent) || 0;
    const last  = parseInt(localStorage.getItem('cucina_lastCount') || '0');

    if (count > last && last > 0) {
        const audio = new Audio('../audio/notifica_cucina.mp3');
        audio.play().catch(() => {});
    }
    localStorage.setItem('cucina_lastCount', count);

    setInterval(() => location.reload(), 5000);
});
