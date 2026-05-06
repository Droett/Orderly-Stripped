document.addEventListener('DOMContentLoaded', function () {
    // Auto-refresh table grid every 10s, but only when the tavoli section is visible
    setInterval(function () {
        if (document.getElementById('page-tavoli').style.display !== 'none') {
            location.reload();
        }
    }, 10000);

    const alert = document.getElementById('success-alert');
    if (alert) setTimeout(() => alert.style.display = 'none', 3000);
});

function switchPage(page, el) {
    document.querySelectorAll('.page-section').forEach(s => s.style.display = 'none');
    document.getElementById('page-' + page).style.display = 'block';
    document.querySelectorAll('.btn-sidebar, .mobile-nav-btn').forEach(b => b.classList.remove('active'));
    const idx = page === 'tavoli' ? 0 : 1;
    document.querySelectorAll('.btn-sidebar')[idx]?.classList.add('active');
    document.querySelectorAll('.mobile-nav-btn')[idx]?.classList.add('active');
}

function apriModalAggiungi() {
    new bootstrap.Modal(document.getElementById('modalAggiungiTavolo')).show();
}

function apriModifica(id, nome, pass, posti, stato) {
    document.getElementById('mod_id_tavolo').value = id;
    document.getElementById('mod_nome_tavolo').value = nome;
    document.getElementById('mod_password').value = pass;
    document.getElementById('mod_posti').value = posti;
    document.getElementById('mod_stato').value = stato;
    new bootstrap.Modal(document.getElementById('modalModificaTavolo')).show();
}

function apriModalModifica(btn) {
    document.getElementById('mod_id').value = btn.dataset.id;
    document.getElementById('mod_nome').value = btn.dataset.nome;
    document.getElementById('mod_desc').value = btn.dataset.desc;
    document.getElementById('mod_prezzo').value = btn.dataset.prezzo;
    document.getElementById('mod_cat').value = btn.dataset.cat;
    document.getElementById('preview_img').src = btn.dataset.img || '';
    const list = btn.dataset.allergeni.split(',').map(a => a.trim().toLowerCase());
    document.querySelectorAll('.mod-allergeni').forEach(cb => {
        cb.checked = list.includes(cb.value.toLowerCase());
    });
    new bootstrap.Modal(document.getElementById('modalModifica')).show();
}
