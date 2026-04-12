// =========================================================
// Pannello di Controllo (Manager Dashboard) — Nucleo Gestione dei tavoli, metriche e menu cibi
// =========================================================

// Attende lo scatto dell'evento web per il caricamento totale degli elementi visivi pagina base DOM structure
document.addEventListener('DOMContentLoaded', function () {
    // Spara la primaria chiamata rete fetch per estrarre la griglia lista tavolati backend
    caricaTavoli();
    
    // Setta un pendolo timer interno che pulsa ogni 10mila millisec per lo scaricamento refresh automatico tavoli grid sync
    setInterval(caricaTavoli, 10000);

    // Cerca eventuali blocchi div di notifiche flash html creati in pagina log success alert msg class blocco messaggi popup base
    const alert = document.getElementById('success-alert');
    
    // Emette timer distruttivo per spegnere l array messaggio nascondendolo stile css formatti
    if (alert) setTimeout(() => alert.style.display = 'none', 3000);
});

// -- Sistema passaggi Menu Viste -- Moduli scorrimento a vista -- Sezione Gestore Menu / Viste
// Funzione che sposta il div contenitore visualizzato (simulatore Single Page Application SPA views) in javascript manager navigation rules function setup parameters
function switchPage(page, el) {
    // Loop resetter cicla tutti i frame logici per spegnerli applicando logica display vuoto
    document.querySelectorAll('.page-section').forEach(s => s.style.display = 'none');
    
    // Accendi spaccando stile bloccante solo lo schermo richiesto a ID parametrico
    document.getElementById('page-' + page).style.display = 'block';

    // Rimuove la macchia colore pulsante da tutti i botton in barra lato logica navigazione
    document.querySelectorAll('.btn-sidebar, .mobile-nav-btn').forEach(b => b.classList.remove('active'));
    
    // Accendi illumina e tingi il bottanello toccato in menu navigation menu items arrays buttons UI click UI states visual active elements array styles rendering color CSS class string formatting rendering function states arrays arrays array values limits arrays elements formatting styles styles setups formattazione layouts
    el.classList.add('active');

    // Sincronizza logicamente in caso di schermi misti il bottone barra grande col menù basso telefonino sync desktop mobile arrays string formats setups formats 
    const idx = page === 'tavoli' ? 0 : 1;
    
    // Trova e infliggi la colata di colore sui button array a indice posizionale 0 o 1
    document.querySelectorAll('.btn-sidebar')[idx]?.classList.add('active');
    document.querySelectorAll('.mobile-nav-btn')[idx]?.classList.add('active');
}

// ==== Sezione Lavorazione Array Tavoli e Contatori Numerici ====
// Banco dati memoria cache ram per contenere i buffer json tavoli raw text object arrays data structures arrays values properties datasets limits models mapping sets formats
let allTavoli = [];

// Funzione base network ajax rest downloader 
function caricaTavoli() {
    // Aggancio rete a file di routing API manager lato backend GET limits mapping
    fetch('../api/manager/manager_api.php?action=get_tavoli')
        // Parser oggetto x riadattare byte JSON testuali in mappature dati arrays javascript object schemas mapping conventions datasets formulas
        .then(r => r.json())
        // Ingrassa i dati processati nella logica visuale e array memoria ram 
        .then(data => {
            // Bufferizza listato globale map object structures
            allTavoli = data;
            // Iniettali ai contatori rotondi piccoli logici arrays mapping functions subsets datasets margins formulas conventions values mapping values limitations 
            aggiornaConteggi(data);
            // Iniettali pure in griglia tavoli layout HTML generatore renderer visual arrays map models array 
            renderTavoli(data);
        });
}

// Meccanismo Calcolatrice per sommare logiche quantità statuto liberi o occupati counts formats
function aggiornaConteggi(data) {
    // Azzera mappatura oggetti contatore raw models configurations schemas conventions values mappings setups schemas 
    const counts = { libero: 0, occupato: 0, riservato: 0 };
    
    // Spazzola l'array pescando singole tavole x valutarne l object property di stato status schemas schemas arrays subsets mappings formats rules schemas boundaries formulas limits
    data.forEach(t => { if (counts[t.stato] !== undefined) counts[t.stato]++; });
    
    // Spalma lunghezze somma tavoli listato intero length a html box span text inner
    document.getElementById('count-tutti').textContent = data.length;
    // Spalma liberi html
    document.getElementById('count-libero').textContent = counts.libero;
    // Spalma rossi occupato arrays
    document.getElementById('count-occupato').textContent = counts.occupato;
    // Spalma arancioni prenotati arrays
    document.getElementById('count-riservato').textContent = counts.riservato;
}

// Lente setacciatrice x tasti in plancia visualizzatori subset tavoli rules models values formats borders formations sizes schemas margin rules rules borders templates arrays layouts mapping conventions formats settings widths margins defaults datasets arrays subsets variables margins datasets lengths arrays thresholds formatting sizing limits 
function filtraTavoli(filtro, btn) {
    // Scegli tutti tasti filtri spegnendoli di defaults array laws presets layouts variables regulations formats setups layouts conventions borders subsets limitations
    document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
    // Forza accensione visuale bottoncino rules setups models limitations layouts conventions values formulas 
    btn.classList.add('active');
    
    // Operazione filtro ternaria se parola è tutti prendi ram globale seno usa funzione filter per test e match stringhe
    const filtered = filtro === 'tutti' ? allTavoli : allTavoli.filter(t => t.stato === filtro);
    
    // Spara i risultati epurati dal filter logico al display HTML render
    renderTavoli(filtered);
}

// Generatore del grigliato graticoltura tavoli schede cartoni card UI string renderer UI engine
function renderTavoli(tavoli) {
    // Aggancia blocco box genitore css rules html id
    const grid = document.getElementById('tavoli-grid');
    
    // Se la lunghezza array pacchetto è nulla zero blocca la render grid array rules conventions templates mapping formatting defaults bounds presets margins layouts schemas subsets configurations sets
    if (!tavoli.length) {
        // Appiccica msg html span error not found string datasets definitions settings 
        grid.innerHTML = `<div class="tavoli-empty"><i class="fas fa-chair"></i><h4>Nessun tavolo trovato</h4><p class="small">Aggiungi un tavolo per iniziare</p></div>`;
        return; // Interrompi execution
    }

    // Applica mapping con iteratore ad ogni singolo componente scodellandolo come concat string in block rules HTML sets arrays
    grid.innerHTML = tavoli.map(t => {
        // Intercetta mancanza status fallback default a stringa libera format mapping formats setups subsets formatting limitations array layouts limits
        const stato = t.stato || 'libero';
        
        // Assegna e pre calcola stringhe nome file font awesome x pittogrammi icon string templates formats spacing schemas conventions limitations arrays
        const icon = stato === 'libero' ? 'fa-check-circle' : stato === 'occupato' ? 'fa-users' : 'fa-clock';
        
        // Capitalizza formattando prima lettera testo string uppercase format strings schemas lengths 
        const label = stato.charAt(0).toUpperCase() + stato.slice(1);

        // Ritorna super blocco massivo string interpolation object rendering limits boundaries template literals backtick laws limits definitions datasets configurations presets margin limits values mapping strings padding bounding mapping setups variables layouts constraints margins datasets sets sizes margins offsets bounding frameworks constants sizing subsets boundaries datasets formulas layouts spacing formulas regulations 
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
    }).join(''); // Chiudi e assembra stringa 
}

// ==== Sezione Facilitatori Scambi Reti API ====
// Wrapper generale funzione fetch asincrona manager x gestire logiche comuni senza replica strings frameworks
function managerApiCall(action, fd, successMsg, closeId) {
    // Manda POST backend con parametro endpoint action strings
    fetch('../api/manager/manager_api.php?action=' + action, { method: 'POST', body: fd })
        // Tira json
        .then(r => r.json())
        // Logica if error controllo data parameters validation string formats datasets sets laws formulas borders mapping limitations layouts mappings defaults presets limits properties datasets subsets formatting
        .then(data => {
            if (data.success) { // Testa true json flag rules limits definitions margins constants schemas boundaries subsets regulations conventions configurations sizes definitions formulations constraints frameworks properties bounds schemas layouts defaults definitions sizes margins conventions
                // Spara toast brindisi html screen notification schemas formatting constants frameworks configurations templates margins definitions conventions 
                if (successMsg) mostraToast(successMsg);
                
                // Ammazza z index finestrelle modal in html popup rendering array laws layouts schemas defaults
                if (closeId) bootstrap.Modal.getInstance(document.getElementById(closeId)).hide();
                
                // Refresh globale per far sparire/mostrare i nuovi items tavoli rules strings definitions datasets definitions limitations frameworks defaults configurations 
                caricaTavoli();
            } else {
                // Alza toast errore alert rosso popup ui formats models datasets mapping layouts borders formats labels thresholds margins conventions mapping datasets arrays models arrays arrays subsets 
                mostraToast('Errore: ' + data.error, true);
            }
        });
}

// Cambiatore status manuale x spinta pulsante su cerchietto stato click constraints string formulations bounds spacing maps 
function ciclaNuovoStato(id, statoAttuale) {
    // Elenco stringhe array in logica sequenza limiti array setups formulas conventions limits boundaries datasets offsets defaults conventions schemas models formulations strings arrays presets defaults rules formats
    const ordine = ['libero', 'occupato', 'riservato'];
    
    // Algebra per determinare string posizionale successiva a loop circolare mapping string arrays formats rules boundaries variables formats configurations formulas mappings formats limits
    const nuovoStato = ordine[(ordine.indexOf(statoAttuale) + 1) % ordine.length];
    
    // Farcisci nuovo contenitore di POST dati string definitions limitations sizes datasets formulations restrictions constants layouts frameworks boundaries margins boundaries formulations formulas margins rules arrays formats bounds
    const fd = new FormData();
    // Appoggia numero tavoli layouts schemas conventions arrays mapping sets thresholds thresholds limits boundaries setups
    fd.append('id_tavolo', id);
    // Metti nuovo setup layouts
    fd.append('stato', nuovoStato);
    
    // Tira wrapper chiamata api
    managerApiCall('cambia_stato_tavolo', fd);
}

// Funzione sicura abbattimento reset session tavolata layouts models borders dimensions conventions datasets schemas
function terminaSessione(id) {
    // Alza box bloccante check alert javascript standard window default rules boundaries
    if (!confirm('Terminare la sessione di questo tavolo?')) return;
    // Crea scatola form formats laws definitions schemas
    const fd = new FormData();
    // Allega parametri regole form datasets limitations definitions definitions
    fd.append('id_tavolo', id);
    // Avvia piallamento tavolo api fetch
    managerApiCall('termina_sessione', fd, 'Sessione terminata');
}

// ==== Sezione Modali Modal Html Javascript Bootstrap Logic ====
// Popuppa la griglia finestrina x aggiungere tavoli rules boundaries rules thresholds thresholds presets borders mapping regulations limitations sets sizing formulas formulas schemas limitations conventions layouts 
function apriModalAggiungi() {
    // Istanziatore oggetto bootstrap frameworks layouts schemas margins sets sizes formations datasets maps rules thresholds rules
    new bootstrap.Modal(document.getElementById('modalAggiungiTavolo')).show();
}

// Azione al premuto save salva in finstra aggiungi configurations datasets datasets defaults constraints layouts
function aggiungiTavolo() {
    // Incamera dati nei FormData per l'invio POST html arrays labels
    const fd = new FormData();
    fd.append('nome_tavolo', document.getElementById('nuovo_nome_tavolo').value);
    fd.append('password', document.getElementById('nuovo_password_tavolo').value);
    fd.append('posti', document.getElementById('nuovo_posti_tavolo').value);

    // Usa master pusher api layouts boundaries configurations schemas conventions sets conventions datasets limitations offsets offsets templates limits formulas
    managerApiCall('aggiungi_tavolo', fd, 'Tavolo registrato!', 'modalAggiungiTavolo');
}

// Pre setta e stappa finestrina modal per la modifica tavolo presets spacing settings presets frameworks
function apriModifica(id, nome, pass, posti, stato) {
    // Scrive valori passati per injection parameter inside form control values layouts mappings formulations formats settings schemas definitions limitations conventions layouts sizes regulations formulas
    document.getElementById('mod_id_tavolo').value = id;
    document.getElementById('mod_nome_tavolo').value = nome;
    document.getElementById('mod_password').value = pass;
    document.getElementById('mod_posti').value = posti;
    document.getElementById('mod_stato').value = stato;
    // Emette screen finestrella show bounds
    new bootstrap.Modal(document.getElementById('modalModificaTavolo')).show();
}

// Spara aggiornamento dal modulo editing popup schemas spacing rules limitations lengths formulations setups setups laws constraints schemas schemas configurations presets mapping datasets arrays models defaults
function modificaTavolo() {
    const fd = new FormData();
    // Appende i risultati estratti formats schemas formatting borders formulas conventions sets
    fd.append('id_tavolo', document.getElementById('mod_id_tavolo').value);
    fd.append('nome_tavolo', document.getElementById('mod_nome_tavolo').value);
    fd.append('password', document.getElementById('mod_password').value);
    fd.append('posti', document.getElementById('mod_posti').value);
    fd.append('stato', document.getElementById('mod_stato').value);

    // Call rules schemas presets limits limits 
    managerApiCall('modifica_tavolo', fd, 'Modifiche salvate!', 'modalModificaTavolo');
}

// Azione fatale mortale cancella row database api action schemas settings formatting sizes setups boundaries definitions constraints limitations laws sets formulations mapping constants sizes 
function eliminaTavolo(id, nome) {
    // Chiede doppia firma alert javascript layouts borders formatting presets defaults
    if (!confirm('Eliminare il tavolo "' + nome + '"?')) return;
    const fd = new FormData();
    fd.append('id_tavolo', id);

    // Fetch call layouts configurations limits
    managerApiCall('elimina_tavolo', fd, 'Tavolo eliminato');
}

// ==== Sezione Piatto Menu Modals Cibo ====
// Accende finestrina per modificare scheda logica prodotto cibi rules lengths schemas definitions borders laws
function apriModalModifica(btn) {
    // Tira dataset data attr css rendering html injection per estrazione rapida string formats schemas schemas definitions schemas limits array templates boundaries widths offsets configurations properties limits offsets margins mappings conventions datasets layouts limits schemas margins formulas sets
    document.getElementById('mod_id').value = btn.dataset.id;
    document.getElementById('mod_nome').value = btn.dataset.nome;
    document.getElementById('mod_desc').value = btn.dataset.desc;
    document.getElementById('mod_prezzo').value = btn.dataset.prezzo;
    document.getElementById('mod_cat').value = btn.dataset.cat;
    document.getElementById('preview_img').src = btn.dataset.img || '';

    // Spezza array testuale allergeni separati da virgola in logic array loops maps schemas schemas datasets mapping arrays layouts margins presets limitations conventions mapping margins margins regulations arrays formations boundaries frameworks conventions values mappings borders
    const list = btn.dataset.allergeni.split(',').map(a => a.trim().toLowerCase());
    
    // Spazzola checkBox per accenderli boolean se sono in array filters array configurations formulations configurations
    document.querySelectorAll('.mod-allergeni').forEach(cb => {
        // Applica true o false in checkbox property schemas formulas
        cb.checked = list.includes(cb.value.toLowerCase());
    });

    // Palesati Popup modals bounds limitations conventions strings offsets definitions strings budgets frameworks conventions array properties constraints boundaries schemas laws margins mapping sizes constants conventions
    new bootstrap.Modal(document.getElementById('modalModifica')).show();
}
