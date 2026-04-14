// ============================================================
// manager.js — Logica della Dashboard Manager
// ============================================================
// Questo file gestisce il pannello amministrativo del manager.
//
// Funzionalità principali:
//   - Carica e mostra la griglia dei tavoli (si aggiorna automaticamente ogni 10 secondi)
//   - Filtra i tavoli per stato (libero / occupato / riservato)
//   - Aggiunge, modifica, elimina tavoli
//   - Cicla lo stato di un tavolo cliccando il badge di stato
//   - Forza la fine della sessione di un tavolo
//   - Apre la modale di modifica del piatto con i dati già precompilati
// ============================================================


// Quando la pagina finisce di caricarsi:
//   1. Recupera e mostra tutti i tavoli
//   2. Aggiorna automaticamente la griglia ogni 10 secondi
//   3. Nasconde l'avviso di successo (se presente) dopo 3 secondi
document.addEventListener('DOMContentLoaded', function () {

    caricaTavoli();
    setInterval(caricaTavoli, 10000); // 10000 ms = 10 secondi

    // L'avviso "menu aggiornato" si nasconde automaticamente dopo 3 secondi
    const alert = document.getElementById('success-alert');
    if (alert) setTimeout(() => alert.style.display = 'none', 3000);
});


// ============================================================
// NAVIGAZIONE TRA SEZIONI
// ============================================================

/**
 * switchPage(page, el)
 * Alterna tra la sezione "Tavoli" e quella "Menu".
 * Mostra la sezione selezionata ed evidenzia il pulsante di navigazione giusto.
 *
 * page — 'tavoli' oppure 'menu'
 * el   — il pulsante di navigazione che è stato cliccato
 */
function switchPage(page, el) {

    // Nasconde tutte le sezioni
    document.querySelectorAll('.page-section').forEach(s => s.style.display = 'none');

    // Mostra la sezione selezionata
    document.getElementById('page-' + page).style.display = 'block';

    // Rimuove "attivo" da tutti i pulsanti di navigazione (sia sidebar che barra mobile)
    document.querySelectorAll('.btn-sidebar, .mobile-nav-btn').forEach(b => b.classList.remove('active'));

    // Evidenzia il pulsante corretto sia nella sidebar che nella barra mobile
    const idx = page === 'tavoli' ? 0 : 1;
    document.querySelectorAll('.btn-sidebar')[idx]?.classList.add('active');
    document.querySelectorAll('.mobile-nav-btn')[idx]?.classList.add('active');
}


// ============================================================
// GRIGLIA TAVOLI — caricamento, visualizzazione, filtro
// ============================================================

// Memorizza la lista completa dei tavoli recuperati dal server.
// Conservata globalmente così le funzioni filtro possono ri-visualizzare senza un'altra chiamata API.
let allTavoli = [];


/**
 * caricaTavoli()
 * Recupera tutti i tavoli dall'API e aggiorna la griglia.
 * Viene chiamata al caricamento e ogni 10 secondi.
 */
function caricaTavoli() {

    fetch('../api/manager/manager_api.php?action=get_tavoli')
        .then(r => r.json())
        .then(data => {
            allTavoli = data;          // Salva per uso nei filtri
            aggiornaConteggi(data);    // Aggiorna i badge contatore nelle schede filtro
            renderTavoli(data);        // Ricostruisce le card della griglia
        });
}


/**
 * aggiornaConteggi(data)
 * Aggiorna i badge numerici sulle schede filtro
 * (es. "Liberi 3", "Occupati 2").
 *
 * data — la lista completa degli oggetti tavolo
 */
function aggiornaConteggi(data) {

    // Conta quanti tavoli hanno ogni stato
    const counts = { libero: 0, occupato: 0, riservato: 0 };
    data.forEach(t => {
        if (counts[t.stato] !== undefined) counts[t.stato]++;
    });

    // Aggiorna il testo del badge accanto a ogni scheda filtro
    document.getElementById('count-tutti').textContent     = data.length;
    document.getElementById('count-libero').textContent    = counts.libero;
    document.getElementById('count-occupato').textContent  = counts.occupato;
    document.getElementById('count-riservato').textContent = counts.riservato;
}


/**
 * filtraTavoli(filtro, btn)
 * Filtra la griglia dei tavoli per stato.
 *
 * filtro — 'tutti', 'libero', 'occupato', oppure 'riservato'
 * btn    — il pulsante scheda filtro cliccato
 */
function filtraTavoli(filtro, btn) {

    // Evidenzia la scheda cliccata e rimuove l'evidenziazione dalle altre
    document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    // Mostra tutti i tavoli o solo quelli con lo stato corrispondente
    const filtered = filtro === 'tutti' ? allTavoli : allTavoli.filter(t => t.stato === filtro);

    renderTavoli(filtered);
}


/**
 * renderTavoli(tavoli)
 * Costruisce e inserisce l'HTML delle card dei tavoli nella griglia.
 *
 * tavoli — l'array degli oggetti tavolo da visualizzare
 */
function renderTavoli(tavoli) {

    const grid = document.getElementById('tavoli-grid');

    // Mostra uno stato vuoto se nessun tavolo corrisponde al filtro
    if (!tavoli.length) {
        grid.innerHTML = `<div class="tavoli-empty">
            <i class="fas fa-chair"></i>
            <h4>Nessun tavolo trovato</h4>
            <p class="small">Aggiungi un tavolo per iniziare</p>
        </div>`;
        return;
    }

    // Costruisce una card per ogni tavolo
    grid.innerHTML = tavoli.map(t => {

        const stato = t.stato || 'libero';

        // Sceglie l'icona giusta per lo stato attuale
        const icon  = stato === 'libero'   ? 'fa-check-circle'
                    : stato === 'occupato' ? 'fa-users'
                    : 'fa-clock';

        // Mette in maiuscolo la prima lettera per la visualizzazione (es. 'libero' → 'Libero')
        const label = stato.charAt(0).toUpperCase() + stato.slice(1);

        return `<div class="tavolo-card" data-stato="${stato}">
            <div class="tavolo-card-header">
                <div class="tavolo-icon ${stato}"><i class="fas ${icon}"></i></div>
                <div class="tavolo-name">${t.username}</div>
                <div class="tavolo-seats"><i class="fas fa-chair"></i> ${t.posti} posti</div>
            </div>
            <div class="tavolo-card-footer">

                <!-- Badge di stato — cliccarlo cicla al prossimo stato -->
                <div class="tavolo-status-badge badge-${stato}" onclick="ciclaNuovoStato(${t.id_utente}, '${stato}')">
                    <span class="status-dot dot-${stato}"></span> ${label}
                </div>

                <div class="tavolo-actions">
                    <!-- Pulsante "Resetta sessione" — mostrato solo per i tavoli occupati -->
                    ${stato === 'occupato' ? `<button class="btn-act" title="Resetta" onclick="terminaSessione(${t.id_utente})"><i class="fas fa-redo-alt"></i></button>` : ''}

                    <!-- Pulsante Modifica -->
                    <button class="btn-act" title="Modifica" onclick="apriModifica(${t.id_utente},'${t.username}','${t.password}',${t.posti},'${stato}')">
                        <i class="fas fa-pen"></i>
                    </button>

                    <!-- Pulsante Elimina -->
                    <button class="btn-act btn-delete-t" title="Elimina" onclick="eliminaTavolo(${t.id_utente}, '${t.username}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>`;
    }).join('');
}


// ============================================================
// HELPER API
// ============================================================

/**
 * managerApiCall(action, fd, successMsg, closeId)
 * Un helper riutilizzabile per inviare una richiesta POST all'API manager.
 * In caso di successo: mostra un toast, chiude una modale (se indicata) e aggiorna la griglia.
 * In caso di errore: mostra un toast di errore.
 *
 * action     — il nome dell'azione API (es. 'aggiungi_tavolo')
 * fd         — un oggetto FormData contenente i campi POST
 * successMsg — messaggio toast da mostrare in caso di successo (opzionale)
 * closeId    — ID di una modale Bootstrap da chiudere in caso di successo (opzionale)
 */
function managerApiCall(action, fd, successMsg, closeId) {

    fetch('../api/manager/manager_api.php?action=' + action, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (successMsg) mostraToast(successMsg);
                // Chiude la modale se è stato fornito un ID
                if (closeId) bootstrap.Modal.getInstance(document.getElementById(closeId)).hide();
                // Ricarica la griglia dei tavoli per mostrare i dati più recenti
                caricaTavoli();
            } else {
                mostraToast('Errore: ' + data.error, true); // true = toast rosso di errore
            }
        });
}


// ============================================================
// AZIONI SUI TAVOLI
// ============================================================

/**
 * ciclaNuovoStato(id, statoAttuale)
 * Cicla lo stato di un tavolo al successivo nella sequenza:
 *   libero → occupato → riservato → libero → ...
 *
 * Viene attivato cliccando il badge di stato sulla card del tavolo.
 */
function ciclaNuovoStato(id, statoAttuale) {

    const ordine     = ['libero', 'occupato', 'riservato'];
    // Trova la posizione dello stato attuale e passa al successivo (con ritorno all'inizio)
    const nuovoStato = ordine[(ordine.indexOf(statoAttuale) + 1) % ordine.length];

    const fd = new FormData();
    fd.append('id_tavolo', id);
    fd.append('stato', nuovoStato);

    managerApiCall('cambia_stato_tavolo', fd);
}


/**
 * terminaSessione(id)
 * Forza il logout di un tavolo (azzera la sua sessione).
 * Mostra una finestra di conferma prima di procedere.
 */
function terminaSessione(id) {

    if (!confirm('Terminare la sessione di questo tavolo?')) return;

    const fd = new FormData();
    fd.append('id_tavolo', id);

    managerApiCall('termina_sessione', fd, 'Sessione terminata');
}


/**
 * apriModalAggiungi()
 * Apre la modale "Aggiungi Nuovo Tavolo".
 */
function apriModalAggiungi() {
    new bootstrap.Modal(document.getElementById('modalAggiungiTavolo')).show();
}


/**
 * aggiungiTavolo()
 * Legge i campi del modulo "Aggiungi Tavolo" e li invia all'API.
 * Viene chiamata quando l'utente invia la modale "Nuovo Tavolo".
 */
function aggiungiTavolo() {

    const fd = new FormData();
    fd.append('nome_tavolo', document.getElementById('nuovo_nome_tavolo').value);
    fd.append('password',    document.getElementById('nuovo_password_tavolo').value);
    fd.append('posti',       document.getElementById('nuovo_posti_tavolo').value);

    // 'modalAggiungiTavolo' verrà chiusa automaticamente in caso di successo
    managerApiCall('aggiungi_tavolo', fd, 'Tavolo registrato!', 'modalAggiungiTavolo');
}


/**
 * apriModifica(id, nome, pass, posti, stato)
 * Apre la modale "Modifica Tavolo" con i dati attuali del tavolo selezionato
 * già precompilati nei campi del modulo.
 */
function apriModifica(id, nome, pass, posti, stato) {

    // Precompila il modulo con i valori attuali
    document.getElementById('mod_id_tavolo').value  = id;
    document.getElementById('mod_nome_tavolo').value = nome;
    document.getElementById('mod_password').value   = pass;
    document.getElementById('mod_posti').value      = posti;
    document.getElementById('mod_stato').value      = stato;

    new bootstrap.Modal(document.getElementById('modalModificaTavolo')).show();
}


/**
 * modificaTavolo()
 * Legge il modulo "Modifica Tavolo" e invia i dati aggiornati all'API.
 * Viene chiamata quando l'utente invia la modale di modifica.
 */
function modificaTavolo() {

    const fd = new FormData();
    fd.append('id_tavolo',   document.getElementById('mod_id_tavolo').value);
    fd.append('nome_tavolo', document.getElementById('mod_nome_tavolo').value);
    fd.append('password',    document.getElementById('mod_password').value);
    fd.append('posti',       document.getElementById('mod_posti').value);
    fd.append('stato',       document.getElementById('mod_stato').value);

    managerApiCall('modifica_tavolo', fd, 'Modifiche salvate!', 'modalModificaTavolo');
}


/**
 * eliminaTavolo(id, nome)
 * Chiede conferma e poi elimina un tavolo (e tutti i suoi ordini).
 */
function eliminaTavolo(id, nome) {

    if (!confirm('Eliminare il tavolo "' + nome + '"?')) return;

    const fd = new FormData();
    fd.append('id_tavolo', id);

    managerApiCall('elimina_tavolo', fd, 'Tavolo eliminato');
}


// ============================================================
// MODALE MODIFICA PIATTO
// ============================================================

/**
 * apriModalModifica(btn)
 * Apre la modale "Modifica Piatto" con i dati del piatto selezionato già precompilati.
 * I dati del piatto sono memorizzati negli attributi data-* del pulsante di modifica.
 *
 * btn — il pulsante "Modifica" che è stato cliccato (contiene data-id, data-nome, ecc.)
 */
function apriModalModifica(btn) {

    // Precompila tutti i campi del modulo dagli attributi data del pulsante
    document.getElementById('mod_id').value     = btn.dataset.id;
    document.getElementById('mod_nome').value   = btn.dataset.nome;
    document.getElementById('mod_desc').value   = btn.dataset.desc;
    document.getElementById('mod_prezzo').value = btn.dataset.prezzo;
    document.getElementById('mod_cat').value    = btn.dataset.cat;
    document.getElementById('preview_img').src  = btn.dataset.img || '';

    // Seleziona le checkbox allergeni che corrispondono agli allergeni di questo piatto
    const list = btn.dataset.allergeni.split(',').map(a => a.trim().toLowerCase());
    document.querySelectorAll('.mod-allergeni').forEach(cb => {
        cb.checked = list.includes(cb.value.toLowerCase());
    });

    new bootstrap.Modal(document.getElementById('modalModifica')).show();
}
