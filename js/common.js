document.addEventListener('DOMContentLoaded', function () {

    if (localStorage.getItem('theme') === 'dark') {

        document.querySelectorAll('[id="theme-icon"]').forEach(icon => {

            icon.classList.replace('fa-moon', 'fa-sun');
        });
    }
});

function mostraToast(msg, isError = false) {

    const el = document.getElementById('managerToast') || document.getElementById('liveToast');

    if (!el) return;

    el.className = `toast align-items-center text-white border-0 shadow-lg ${isError ? 'bg-danger' : 'bg-success'}`;

    const msgEl = el.querySelector('.toast-body span');

    if (msgEl) msgEl.textContent = msg;

    new bootstrap.Toast(el, { delay: 3000 }).show();
}
