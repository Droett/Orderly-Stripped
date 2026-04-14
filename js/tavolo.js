// ============================================================
// tavolo.js — Logica della Dashboard Tavolo (Cliente)
// ============================================================
// Questo file gestisce la pagina del menu rivolta al cliente.
//
// Funzionalità principali:
//   - Sfogliare e filtrare il menu per categoria, ricerca o allergeni
//   - Aggiungere/rimuovere piatti dal carrello (sincronizzato col server)
//   - Aprire un popup di dettaglio del piatto (vista zoom)
//   - Inviare l'ordine alla cucina
//   - Visualizzare lo storico degli ordini della sessione corrente
//   - Controllare automaticamente ogni 5 secondi se la sessione è ancora attiva
// ============================================================


// --- STATO GLOBALE ---
// Queste variabili fungono da "memoria" della pagina.
// Sono accessibili da tutte le funzioni in questo file.

// Il carrello: un oggetto in cui la chiave è l'ID del piatto e il valore è { nome, qta, prezzo }
// Esempio: { "3": { nome: "Pizza", qta: 2, prezzo: 8.50 } }
let carrello = {};

// Lista dei nomi degli allergeni che l'utente vuole escludere (es. ["Glutine", "Latte"])
let filtriAllergeni = [];

// L'ID della categoria attualmente selezionata (o 'all' per mostrare tutto)
let categoriaAttiva = 'all';


// ============================================================
// INIZIALIZZAZIONE PAGINA — si esegue quando la pagina finisce di caricarsi
// ============================================================
document.addEventListener('DOMContentLoaded', function () {

    // Carica il carrello dal server (nel caso l'utente avesse elementi prima di un aggiornamento)
    sincronizzaCarrello();

    // Applica i filtri correnti per mostrare/nascondere le card dei piatti
    renderProdotti();

    // Ogni 5 secondi, controlla se il manager ha terminato la sessione di questo tavolo
    setInterval(verificaSessione, 5000);

    // Quando si clicca il pulsante "Invia Ordine" nella modale del carrello,
    // apri una finestra di conferma prima di inviare effettivamente
    document.getElementById('btn-invia-ordine').addEventListener('click', () => {
        new bootstrap.Modal(document.getElementById('modalConfermaOrdine')).show();
    });

    // Quando l'utente conferma nella finestra di dialogo, invia l'ordine
    document.getElementById('confirm-send-btn').addEventListener('click', inviaOrdine);
});


// ============================================================
// CARRELLO — funzioni che gestiscono lo stato del carrello
// ============================================================

/**
 * sincronizzaCarrello()
 * Recupera il contenuto del carrello dal server e ricostruisce
 * l'oggetto locale `carrello` in modo che corrisponda.
 *
 * Viene chiamata al caricamento della pagina per ripristinare
 * lo stato del carrello dopo un aggiornamento.
 */
function sincronizzaCarrello() {

    fetch('../api/tavolo/tavolo_api.php?action=get_carrello')
        .then(r => r.json())
        .then(data => {

            // Azzera il carrello locale prima di ricostruirlo
            carrello = {};

            // Ricostruisce il carrello dai dati del server
            data.forEach(item => {
                carrello[item.id_alimento] = {
                    nome:   item.nome_piatto,
                    qta:    parseInt(item.quantita),
                    prezzo: parseFloat(item.prezzo)
                };
            });

            // Aggiorna i numeri di quantità mostrati su ogni card del piatto
            aggiornaUI();
        });
}


/**
 * syncCartBackend(id, delta)
 * Comunica al server una modifica al carrello (aggiunge o rimuove un'unità di un piatto).
 *
 * id    — l'ID del piatto
 * delta — +1 per aggiungere, -1 per rimuovere
 *
 * Mantiene la copia del carrello sul server sincronizzata con la pagina.
 * Viene chiamata ogni volta che l'utente clicca + o − su un piatto.
 */
function syncCartBackend(id, delta) {

    // Sceglie la giusta azione API in base all'aggiunta o rimozione
    const endpoint = delta > 0 ? 'aggiungi_al_carrello' : 'rimuovi_dal_carrello';

    // FormData è come un invio di modulo — la usiamo per inviare dati POST
    const fd = new FormData();
    fd.append('id_alimento', id);
    if (delta > 0) fd.append('quantita', delta); // Necessario solo in fase di aggiunta

    fetch('../api/tavolo/tavolo_api.php?action=' + endpoint, { method: 'POST', body: fd });
}


/**
 * btnCardQty(event, id, delta, prezzo, nome)
 * Chiamata quando l'utente clicca il pulsante + o − sulla card di un piatto.
 *
 * event  — l'evento click (blocchiamo la propagazione verso il gestore zoom della card)
 * id     — ID del piatto
 * delta  — +1 (aggiungi) oppure -1 (rimuovi)
 * prezzo — prezzo del piatto
 * nome   — nome del piatto
 */
function btnCardQty(event, id, delta, prezzo, nome) {

    // Impedisce al clic di aprire anche il popup zoom della card
    event.stopPropagation();

    // Se questo piatto non è ancora nel carrello, inizializzalo con quantità 0
    if (!carrello[id]) carrello[id] = { nome, qta: 0, prezzo };

    // Aggiorna la quantità, ma non lasciarla scendere sotto 0
    carrello[id].qta = Math.max(0, carrello[id].qta + delta);

    // Se la quantità raggiunge 0, rimuove il piatto dal carrello
    if (carrello[id].qta <= 0) delete carrello[id];

    // Comunica la modifica al server
    syncCartBackend(id, delta);

    // Aggiorna i numeri di quantità mostrati nella pagina
    aggiornaUI();
}


/**
 * modificaQtaModale(id, delta)
 * Come btnCardQty ma chiamata dall'interno della modale del carrello
 * (i pulsanti + e − accanto a ogni elemento nella finestra popup del carrello).
 */
function modificaQtaModale(id, delta) {
    if (!carrello[id]) return;

    carrello[id].qta = Math.max(0, carrello[id].qta + delta);
    if (carrello[id].qta <= 0) delete carrello[id];

    syncCartBackend(id, delta);

    // Aggiorna sia l'interfaccia principale che la vista della modale carrello
    aggiornaUI();
    aggiornaModale();
}


/**
 * aggiornaUI()
 * Aggiorna tutti i numeri di quantità e i totali visibili nella pagina principale.
 * Viene chiamata dopo ogni modifica al carrello.
 */
function aggiornaUI() {

    let totale = 0; // Prezzo totale di tutto ciò che è nel carrello
    let pezzi  = 0; // Numero totale di singoli articoli nel carrello

    // Scorre ogni elemento nel carrello e aggiorna la sua quantità visualizzata
    for (const id in carrello) {
        const item = carrello[id];
        totale += item.qta * item.prezzo;
        pezzi  += item.qta;

        // Trova l'elemento di visualizzazione quantità sulla card del piatto (formato ID: "q-{id_piatto}")
        const el = document.getElementById('q-' + id);
        if (el) el.textContent = item.qta;
    }

    // Reimposta a "0" le card dei piatti che NON sono nel carrello
    document.querySelectorAll('[id^="q-"]').forEach(el => {
        const id = el.id.replace('q-', '');
        if (!carrello[id]) el.textContent = '0';
    });

    // Aggiorna il conto stimato nella barra superiore e il conteggio degli articoli
    document.getElementById('soldi-header').textContent = totale.toFixed(2);
    document.getElementById('pezzi-header').textContent = pezzi;
}


/**
 * aggiornaModale()
 * Ricostruisce il contenuto HTML della modale popup del carrello.
 * Chiamata quando l'utente apre il carrello o apporta una modifica al suo interno.
 */
function aggiornaModale() {

    const body = document.getElementById('corpo-carrello');

    // Prende solo gli ID dei piatti con quantità > 0
    const keys = Object.keys(carrello).filter(id => carrello[id].qta > 0);
    let totale = 0;

    // Se il carrello è vuoto, mostra un messaggio di stato vuoto
    if (!keys.length) {
        body.innerHTML = `<div class="text-center py-5 text-muted">
            <i class="fas fa-shopping-bag fa-3x mb-3" style="opacity:.3"></i>
            <h5>Il carrello è vuoto</h5>
            <p class="small">Aggiungi piatti per iniziare</p>
        </div>`;

        document.getElementById('totale-modale').textContent = '0.00';
        document.getElementById('btn-invia-ordine').disabled = true;
        return;
    }

    // Costruisce un elemento di lista per ogni voce del carrello, con controlli +/-
    body.innerHTML = '<ul class="list-group list-group-flush px-3">' +
        keys.map(id => {
            const item = carrello[id];
            const sub  = (item.qta * item.prezzo).toFixed(2); // Subtotale per questo piatto
            totale += item.qta * item.prezzo;

            return `<li class="list-group-item d-flex justify-content-between align-items-center px-2 py-3">
                <div>
                    <strong>${item.nome}</strong><br>
                    <small class="text-muted">${item.prezzo.toFixed(2)}€ cad.</small>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="qty-capsule" style="width:120px;">
                        <button class="btn-circle btn-minus" onclick="modificaQtaModale(${id}, -1)"><i class="fas fa-minus"></i></button>
                        <span class="qty-input">${item.qta}</span>
                        <button class="btn-circle btn-plus"  onclick="modificaQtaModale(${id}, 1)"><i class="fas fa-plus"></i></button>
                    </div>
                    <strong class="text-price">${sub}€</strong>
                </div>
            </li>`;
        }).join('') +
    '</ul>';

    document.getElementById('totale-modale').textContent = totale.toFixed(2);
    document.getElementById('btn-invia-ordine').disabled = false;
}


/**
 * inviaOrdine()
 * Invia il contenuto del carrello alla cucina come ordine confermato.
 * Viene chiamata dopo che l'utente clicca "Conferma" nella finestra di dialogo.
 */
function inviaOrdine() {

    // Converte l'oggetto carrello in un array di oggetti { id, qta, note }
    const prodotti = Object.keys(carrello)
        .map(id => ({
            id:   parseInt(id),
            qta:  carrello[id].qta,
            note: '' // Le note possono essere estese in futuro
        }))
        .filter(p => p.qta > 0); // Include solo gli articoli con quantità > 0

    // Niente da inviare — esce
    if (!prodotti.length) return;

    // Chiude la finestra di conferma
    bootstrap.Modal.getInstance(document.getElementById('modalConfermaOrdine')).hide();

    // Invia l'ordine all'API come JSON
    fetch('../api/tavolo/tavolo_api.php?action=invia_ordine', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ prodotti })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Svuota il carrello locale
                carrello = {};
                aggiornaUI();

                // Chiude la modale del carrello (se è aperta)
                bootstrap.Modal.getInstance(document.getElementById('modalCarrello'))?.hide();

                // Mostra la modale di successo
                new bootstrap.Modal(document.getElementById('modalSuccesso')).show();
            } else {
                // Mostra un toast di errore se qualcosa è andato storto
                mostraToast(data.message || 'Errore', true);
            }
        });
}


// ============================================================
// VISTA ZOOM — popup di dettaglio del piatto
// ============================================================

// Memorizza lo stato del popup zoom: quale piatto è mostrato e quanti aggiungerne
let zoomState = { id: 0, prezzo: 0, qta: 1, nome: '', note: '' };


/**
 * apriZoom(event, card)
 * Apre il popup di dettaglio del piatto quando l'utente clicca su una card.
 * Viene ignorato se l'utente ha cliccato sui pulsanti di quantità +/-.
 *
 * event — l'evento click
 * card  — l'elemento card del piatto cliccato (contiene attributi data-*)
 */
function apriZoom(event, card) {

    // Se il clic era su un pulsante di quantità, non aprire il popup zoom
    if (event.target.closest('.btn-card-qty')) return;

    // Memorizza le informazioni del piatto selezionato nello stato zoom
    zoomState = {
        id:    parseInt(card.dataset.id),
        prezzo: parseFloat(card.dataset.prezzo),
        qta:   1, // Inizia con quantità 1 nella vista zoom
        nome:  card.dataset.nome,
        note:  ''
    };

    // Popola tutti i campi nella modale zoom
    document.getElementById('zoom-img').src               = card.dataset.img;
    document.getElementById('zoom-nome').textContent      = card.dataset.nome;
    document.getElementById('zoom-desc').textContent      = card.dataset.desc;
    document.getElementById('zoom-prezzo-unitario').textContent = card.dataset.prezzo;
    document.getElementById('zoom-note').value            = '';

    // Costruisce i badge degli allergeni (o mostra "nessun allergene" se non ce ne sono)
    const allergeni = card.dataset.allergeni.split(',').filter(a => a.trim());
    document.getElementById('zoom-allergeni').innerHTML = allergeni.length
        ? allergeni.map(a => `<span class="badge-alg">${a.trim()}</span>`).join('')
        : '<small class="text-muted">Nessun allergene dichiarato</small>';

    // Aggiorna il display della quantità e il prezzo totale nella modale
    aggiornaZoomUI();

    // Mostra la modale
    new bootstrap.Modal(document.getElementById('modalZoom')).show();
}


/**
 * updateZoomQty(delta)
 * Modifica la quantità nel popup zoom (+1 oppure -1).
 * La quantità non può scendere sotto 1.
 */
function updateZoomQty(delta) {
    zoomState.qta = Math.max(1, zoomState.qta + delta);
    aggiornaZoomUI();
}


/**
 * aggiornaZoomUI()
 * Aggiorna il display della quantità e il prezzo totale mostrati nel popup zoom.
 */
function aggiornaZoomUI() {
    document.getElementById('zoom-qty-display').textContent = zoomState.qta;
    // Mostra il prezzo totale per la quantità selezionata (es. 2 × 8,50 = "17,00€")
    document.getElementById('zoom-btn-totale').textContent = (zoomState.prezzo * zoomState.qta).toFixed(2) + '€';
}


/**
 * confermaZoom()
 * Aggiunge il piatto selezionato (con la quantità scelta) al carrello
 * quando l'utente conferma dal popup zoom.
 */
function confermaZoom() {

    // Se questo piatto non è ancora nel carrello, aggiungilo con quantità 0
    if (!carrello[zoomState.id]) {
        carrello[zoomState.id] = { nome: zoomState.nome, qta: 0, prezzo: zoomState.prezzo };
    }

    // Aggiunge la quantità scelta al carrello
    carrello[zoomState.id].qta += zoomState.qta;

    // Sincronizza la modifica con il server
    syncCartBackend(zoomState.id, zoomState.qta);

    // Aggiorna l'interfaccia
    aggiornaUI();

    // Chiude il popup zoom
    bootstrap.Modal.getInstance(document.getElementById('modalZoom')).hide();

    // Mostra una breve notifica toast "aggiunto!"
    mostraToast(`${zoomState.nome} aggiunto!`);
}


// ============================================================
// FILTRAGGIO — barra di ricerca, categorie, allergeni
// ============================================================

/**
 * renderProdotti()
 * Mostra o nasconde ogni card del piatto in base ai filtri attivi:
 *   - Testo di ricerca (deve corrispondere al nome o alla descrizione del piatto)
 *   - Categoria attiva (deve corrispondere alla categoria del piatto)
 *   - Filtri allergeni (il piatto NON deve contenere allergeni bloccati)
 *
 * Viene chiamata ogni volta che cambia un filtro.
 */
function renderProdotti() {

    // Legge il testo di ricerca corrente (in minuscolo per un confronto senza distinzione maiuscole)
    const search = document.getElementById('search-bar').value.toLowerCase();

    // Scorre ogni card del piatto nella pagina
    document.querySelectorAll('.item-prodotto').forEach(item => {

        const card = item.querySelector('.card-prodotto');

        const nome  = card.dataset.nome.toLowerCase();
        const desc  = card.dataset.desc.toLowerCase();
        const cat   = item.dataset.cat;

        // Divide la lista allergeni del piatto in un array per un controllo facile
        const allergeniPiatto = card.dataset.allergeni.split(',').map(a => a.trim().toLowerCase());

        // Verifica ogni condizione di filtro
        const matchSearch    = nome.includes(search) || desc.includes(search);
        const matchCat       = categoriaAttiva === 'all' || cat == categoriaAttiva;
        // Passa se nessun filtro allergeni è attivo, OPPURE se il piatto non contiene allergeni bloccati
        const matchAllergeni = filtriAllergeni.length === 0 || !filtriAllergeni.some(f => allergeniPiatto.includes(f.toLowerCase()));

        // Mostra la card solo se TUTTE e tre le condizioni sono soddisfatte
        item.style.display = (matchSearch && matchCat && matchAllergeni) ? '' : 'none';
    });
}


/**
 * filtraCategoria(catId, btn)
 * Chiamata quando l'utente clicca un pulsante categoria nella barra laterale o in quella mobile.
 * Attiva quel filtro categoria e ri-visualizza le card dei piatti.
 *
 * catId — l'ID della categoria (o 'all' per tutto)
 * btn   — il pulsante cliccato (usato per aggiornare il evidenziazione "attivo")
 */
function filtraCategoria(catId, btn) {

    categoriaAttiva = catId;

    // Rimuove l'evidenziazione "attivo" da tutti i pulsanti categoria
    document.querySelectorAll('.btn-categoria, .mobile-cat-btn').forEach(b => b.classList.remove('active'));

    // Aggiunge l'evidenziazione "attivo" al pulsante cliccato
    btn.classList.add('active');

    renderProdotti();
}


/**
 * applicaFiltriAllergeni()
 * Legge le checkbox degli allergeni selezionate nella modale filtri
 * e le applica per nascondere i piatti che contengono quegli allergeni.
 */
function applicaFiltriAllergeni() {

    filtriAllergeni = [];

    // Raccoglie tutte le checkbox allergeni selezionate
    document.querySelectorAll('#lista-allergeni-filtro input[type="checkbox"]:checked').forEach(cb => {
        filtriAllergeni.push(cb.value);
    });

    renderProdotti();
}


/**
 * resettaFiltriAllergeni()
 * Cancella tutti i filtri allergeni e deseleziona tutte le checkbox.
 */
function resettaFiltriAllergeni() {

    // Deseleziona ogni checkbox allergeno
    document.querySelectorAll('#lista-allergeni-filtro input[type="checkbox"]').forEach(cb => cb.checked = false);

    filtriAllergeni = [];

    renderProdotti();
}


// ============================================================
// STORICO ORDINI
// ============================================================

/**
 * apriStorico()
 * Recupera e mostra lo storico degli ordini del tavolo per la sessione corrente.
 * Mostra tutti gli ordini effettuati da quando il tavolo ha effettuato l'accesso.
 */
function apriStorico() {

    fetch('../api/tavolo/tavolo_api.php?action=leggi_ordini_tavolo')
        .then(r => r.json())
        .then(data => {

            const body = document.getElementById('corpo-ordini');
            let totaleSommato = 0; // Totale generale su tutti gli ordini

            if (!data.length) {
                // Nessun ordine ancora — mostra uno stato vuoto
                body.innerHTML = `<div class="text-center text-muted py-5">
                    <i class="fas fa-receipt fa-3x mb-3 opacity-25"></i>
                    <h5>Nessun ordine</h5>
                    <p class="small">Non hai ancora inviato ordini.</p>
                </div>`;
            } else {
                // Costruisce una card per ogni ordine nello storico
                body.innerHTML = data.map(o => {
                    totaleSommato += parseFloat(o.totale);

                    // Sceglie il colore del badge in base allo stato dell'ordine
                    const badgeClass = o.stato === 'in_attesa'       ? 'bg-warning text-dark'
                                     : o.stato === 'in_preparazione' ? 'bg-info text-white'
                                     : 'bg-success';

                    // Etichette di stato leggibili dall'utente
                    const labels = {
                        in_attesa:       'In attesa',
                        in_preparazione: 'In preparazione',
                        pronto:          'Pronto'
                    };

                    return `<div class="border rounded-4 p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge ${badgeClass} rounded-pill px-3 py-2">${labels[o.stato] || o.stato}</span>
                            <small class="text-muted"><i class="fas fa-clock me-1"></i>${o.ora} • ${o.data}</small>
                        </div>
                        ${o.piatti.map(p => `
                            <div class="d-flex justify-content-between small py-1 border-bottom">
                                <span>${p.qta}x <strong>${p.nome}</strong></span>
                                <span class="text-muted">${p.prezzo}€</span>
                            </div>`).join('')}
                        <div class="text-end mt-2 fw-bold text-price">${o.totale}€</div>
                    </div>`;
                }).join('');
            }

            // Aggiorna il totale generale mostrato in fondo alla modale storico
            document.getElementById('totale-storico').textContent = totaleSommato.toFixed(2);

            // Apre la modale storico
            new bootstrap.Modal(document.getElementById('modalOrdini')).show();
        });
}


// ============================================================
// CONTROLLO SESSIONE
// ============================================================

/**
 * verificaSessione()
 * Controlla con il server se la sessione di questo tavolo è ancora attiva.
 * Viene chiamata ogni 5 secondi (impostata nel blocco DOMContentLoaded sopra).
 *
 * Se il manager ha forzato il reset del tavolo, il server restituirà
 * { valida: false } e reindirizzeremo l'utente alla pagina di logout.
 */
function verificaSessione() {

    fetch('../api/tavolo/tavolo_api.php?action=verifica_sessione')
        .then(r => r.json())
        .then(data => {
            if (!data.valida) {
                alert('La sessione è stata terminata dal gestore.');
                window.location.href = '../logout.php';
            }
        })
        .catch(() => {}); // Ignora silenziosamente gli errori di rete (es. brevi interruzioni Wi-Fi)
}
