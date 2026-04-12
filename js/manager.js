// Manager Dashboard — Gestione tavoli e menu

document.addEventListener('DOMContentLoaded', function () {
    caricaTavoli();
    setInterval(caricaTavoli, 10000);

    // Auto-nasconde l'avviso di successo
    const alert = document.getElementById('success-alert');
    if (alert) setTimeout(() => alert.style.display = 'none', 3000);
});

// --- Navigazione tra sezioni ---
function switchPage(page, el) {
    document.querySelectorAll('.page-section').forEach(s => s.style.display = 'none');
    document.getElementById('page-' + page).style.display = 'block';

    document.querySelectorAll('.btn-sidebar, .mobile-nav-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');

    // Sincronizza desktop e mobile
    const idx = page === 'tavoli' ? 0 : 1;
    document.querySelectorAll('.btn-sidebar')[idx]?.classList.add('active');
    document.querySelectorAll('.mobile-nav-btn')[idx]?.classList.add('active');
}

// --- Gestione Tavoli ---
let allTavoli = [];

function caricaTavoli() {
    fetch('../api/manager/manager_api.php?action=get_tavoli')
        .then(r => r.json())
        .then(data => {
            allTavoli = data;
            aggiornaConteggi(data);
            renderTavoli(data);
        });
}

function aggiornaConteggi(data) {
    const counts = { libero: 0, occupato: 0, riservato: 0 };
    data.forEach(t => { if (counts[t.stato] !== undefined) counts[t.stato]++; });
    document.getElementById('count-tutti').textContent = data.length;
    document.getElementById('count-libero').textContent = counts.libero;
    document.getElementById('count-occupato').textContent = counts.occupato;
    document.getElementById('count-riservato').textContent = counts.riservato;
}

function filtraTavoli(filtro, btn) {
    document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const filtered = filtro === 'tutti' ? allTavoli : allTavoli.filter(t => t.stato === filtro);
    renderTavoli(filtered);
}

function renderTavoli(tavoli) {
    const grid = document.getElementById('tavoli-grid');
    if (!tavoli.length) {
        grid.innerHTML = `<div class="tavoli-empty"><i class="fas fa-chair"></i><h4>Nessun tavolo trovato</h4><p class="small">Aggiungi un tavolo per iniziare</p></div>`;
        return;
    }

    grid.innerHTML = tavoli.map(t => {
        const stato = t.stato || 'libero';
        const icon = stato === 'libero' ? 'fa-check-circle' : stato === 'occupato' ? 'fa-users' : 'fa-clock';
        const label = stato.charAt(0).toUpperCase() + stato.slice(1);

        return `<div class="tavolo-card" data-stato="${stato}">
            <div class="tavolo-card-header">
                <div class="tavolo-icon ${stato}"><i class="fas ${icon}"></i></div>
                <div class="tavolo-name">${t.username}</div>
                <div class="tavolo-seats"><i class="fas fa-chair"></i> ${t.posti} posti</div>
            </div>
            <div class="tavolo-card-footer">
                <div class="tavolo-status-badge badge-${stato}" onclick="ciclaNuovoStato(${t.id_utente}, '${stato}')">
                    <span class="status-dot dot-${stato}"></span> ${label}
                </div>
                <div class="tavolo-actions">
                    ${stato === 'occupato' ? `<button class="btn-act" title="Resetta" onclick="terminaSessione(${t.id_utente})"><i class="fas fa-redo-alt"></i></button>` : ''}
                    <button class="btn-act" title="Modifica" onclick="apriModifica(${t.id_utente},'${t.username}','${t.password}',${t.posti},'${stato}')"><i class="fas fa-pen"></i></button>
                    <button class="btn-act btn-delete-t" title="Elimina" onclick="eliminaTavolo(${t.id_utente}, '${t.username}')"><i class="fas fa-trash"></i></button>
                </div>
            </div>
        </div>`;
    }).join('');
}

// --- Helper API ---
function managerApiCall(action, fd, successMsg, closeId) {
    fetch('../api/manager/manager_api.php?action=' + action, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (successMsg) mostraToast(successMsg);
                if (closeId) bootstrap.Modal.getInstance(document.getElementById(closeId)).hide();
                caricaTavoli();
            } else {
                mostraToast('Errore: ' + data.error, true);
            }
        });
}

function ciclaNuovoStato(id, statoAttuale) {
    const ordine = ['libero', 'occupato', 'riservato'];
    const nuovoStato = ordine[(ordine.indexOf(statoAttuale) + 1) % ordine.length];
    const fd = new FormData();
    fd.append('id_tavolo', id);
    fd.append('stato', nuovoStato);
    managerApiCall('cambia_stato_tavolo', fd);
}

function terminaSessione(id) {
    if (!confirm('Terminare la sessione di questo tavolo?')) return;
    const fd = new FormData();
    fd.append('id_tavolo', id);
    managerApiCall('termina_sessione', fd, 'Sessione terminata');
}

// --- Modali Tavoli ---
function apriModalAggiungi() {
    new bootstrap.Modal(document.getElementById('modalAggiungiTavolo')).show();
}

function aggiungiTavolo() {
    const fd = new FormData();
    fd.append('nome_tavolo', document.getElementById('nuovo_nome_tavolo').value);
    fd.append('password', document.getElementById('nuovo_password_tavolo').value);
    fd.append('posti', document.getElementById('nuovo_posti_tavolo').value);

    managerApiCall('aggiungi_tavolo', fd, 'Tavolo registrato!', 'modalAggiungiTavolo');
}

function apriModifica(id, nome, pass, posti, stato) {
    document.getElementById('mod_id_tavolo').value = id;
    document.getElementById('mod_nome_tavolo').value = nome;
    document.getElementById('mod_password').value = pass;
    document.getElementById('mod_posti').value = posti;
    document.getElementById('mod_stato').value = stato;
    new bootstrap.Modal(document.getElementById('modalModificaTavolo')).show();
}

function modificaTavolo() {
    const fd = new FormData();
    fd.append('id_tavolo', document.getElementById('mod_id_tavolo').value);
    fd.append('nome_tavolo', document.getElementById('mod_nome_tavolo').value);
    fd.append('password', document.getElementById('mod_password').value);
    fd.append('posti', document.getElementById('mod_posti').value);
    fd.append('stato', document.getElementById('mod_stato').value);

    managerApiCall('modifica_tavolo', fd, 'Modifiche salvate!', 'modalModificaTavolo');
}

function eliminaTavolo(id, nome) {
    if (!confirm('Eliminare il tavolo "' + nome + '"?')) return;
    const fd = new FormData();
    fd.append('id_tavolo', id);

    managerApiCall('elimina_tavolo', fd, 'Tavolo eliminato');
}

// --- Modifica Piatto ---
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
