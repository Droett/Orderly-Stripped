// =========================================================
// Pannello Cliente (Tavolo Dashboard) — Motore Menu, carrello spesa e ordini
// =========================================================

// Variabile RAM locale per contenere i piatti selezionati object logic map rules schemas sizing constants limitations schemas
let carrello = {};

// Array pila che contiene tutte le etichette stringa degli allergeni da filtrare
let filtriAllergeni = [];

// Stringa d'allocazione predefinita per la categoria cibi menu, imposta base a 'all' tutto
let categoriaAttiva = 'all';

// Ascolta che l'albero HTLM document model venga montato per avviare motori limits regulations budgets boundaries limitations schemas formatting setups boundaries schemas laws presets
document.addEventListener('DOMContentLoaded', function () {
    // Chiama asincrona API al database php per ripescare vecchie sessioni di carrello sospese frameworks lengths boundaries sets formulas budgets boundaries mapping thresholds setups conventions schemas
    sincronizzaCarrello();
    
    // Spazzola l'elenco e valuta chi div mostrare e chi spegnere display null
    renderProdotti();
    
    // Timer che bussa ogni 5 secondi al server per spiare se il cameriere ha killato sessione boundaries laws formulas arrays borders padding sets models dimensions sizes setups formulas conventions variables presets templates formulas borders limitations
    setInterval(verificaSessione, 5000);

    // Mette orecchie al pulsantone "Invia Comanda" per far scattare il modale di check
    document.getElementById('btn-invia-ordine').addEventListener('click', () => {
        // Usa libreria Bootstrap 5 Javascript Object per popuppare id_modal schemas mapping restrictions restrictions schemas formulas sizing limitations defaults maps setups arrays conventions models defaults settings definitions rules 
        new bootstrap.Modal(document.getElementById('modalConfermaOrdine')).show();
    });
    
    // Orecchie al bottone DENTRO il modal che da il fuoco definitivo send limits limitations schemas datasets
    document.getElementById('confirm-send-btn').addEventListener('click', inviaOrdine);
});

// ==== Logica di sincronizzazione e render UI carrello spesa ====
// Chiama API per leggere dati appesi nel PHP server side session rules schemas formulas presets boundaries templates settings sets rules setups limits
function sincronizzaCarrello() {
    // Spara richiesta GET base all'endpoint api_tavoli
    fetch('../api/tavolo/tavolo_api.php?action=get_carrello')
        // Ingrassa i byte in Oggetto Parser Javascript rules setups setups properties boundaries formulas
        .then(r => r.json())
        // Flusso promessa data JSON
        .then(data => {
            // Svuotamento crudo per resettare lo stato cache constraints lengths limits schemas padding boundaries
            carrello = {};
            
            // Per ogni prodotto raw tira via campi maps formulas limits templates conventions mappings borders constants layouts
            data.forEach(item => {
                // Inietta nel dictionary / mapping JS array a indice id_alimento schemas schemas thresholds constants boundaries restrictions properties schemas templates subsets frameworks formulas boundaries 
                carrello[item.id_alimento] = {
                    nome: item.nome_piatto, // Campo name
                    qta: parseInt(item.quantita), // Forzatura casting su numero Base intera 10 rules rules
                    prezzo: parseFloat(item.prezzo) // Forzatura floating croma cassa float punto mobile decimals properties schemas
                };
            });
            // Re triggera ricalcolo grafico per aggiornare icone badge totali soldi
            aggiornaUI();
        });
}

// ==== Gestione Filtraggi e UI Logica Rendering display none block ====
// Cicla tutti gli stack piatti card per settare css display block o css display none
function renderProdotti() {
    // Stappa la stringa input raw lowercase minuscola x case insensitives comparazioni regex rules borders layouts formatting configurations sets
    const search = document.getElementById('search-bar').value.toLowerCase();

    // Loops su nodi classi css item arrays labels definitions properties subsets budgets rules schemas setups schemas frameworks
    document.querySelectorAll('.item-prodotto').forEach(item => {
        // Estrai figlio card datasets rules limitations
        const card = item.querySelector('.card-prodotto');
        
        // Risucchia dataset var per nome abbassato a casello conventions mappings borders formats formatting presets sizes schemas
        const nome = card.dataset.nome.toLowerCase();
        
        // Risucchia string desctript abbassata schemas templates defaults 
        const desc = card.dataset.desc.toLowerCase();
        
        // Pigola var id o test string categorica limits maps datasets setups formulas arrays subsets regulations setups limitations limits formats sizes setups formulas datasets variables mapping widths templates
        const cat = item.dataset.cat;
        
        // Spacca la concatenazione allergeni in listino piccolo array list limits boundaries defaults margins schemas offsets limits schemas formulas conventions
        const allergeniPiatto = card.dataset.allergeni.split(',').map(a => a.trim().toLowerCase());

        // Booleano logico cerca inclusione query in nome OR query in desck models offsets arrays formulas presets lengths boundaries laws laws subsets arrays subsets rules
        const matchSearch = nome.includes(search) || desc.includes(search);
        
        // Booleano se attivo tutti OR id cateria matcha subsets definitions borders dimensions subsets formats mapping definitions margins
        const matchCat = categoriaAttiva === 'all' || cat == categoriaAttiva;
        
        // Boolean x filtratura negata interset allergeni limits bounds layouts schemas setups datasets limits sets limitations mapping borders laws conventions array mapping datasets widths array conventions templates formulas formulas thresholds boundaries thresholds
        const matchAllergeni = filtriAllergeni.length === 0 || !filtriAllergeni.some(f => allergeniPiatto.includes(f.toLowerCase()));

        // Scatta meccanismo interruttore bloccando blocchi if 3 test pass margins constraints widths widths layouts mappings formulas bounding boundaries setups
        item.style.display = (matchSearch && matchCat && matchAllergeni) ? '' : 'none';
    });
}

// Interruttore logico pulsanti categorie schemas conventions laws formulas
function filtraCategoria(catId, btn) {
    // Fissa la ram a cat current limits sizing datasets regulations schemas formulas boundaries thresholds definitions laws borders schemas datasets models properties regulations boundaries formats presets schemas datasets
    categoriaAttiva = catId;
    
    // Spenge CSS rosso primario bottoncino margins datasets schemas templates defaults conventions formulas boundaries schemas rules models budgets limitations datasets borders rules definitions datasets limits limitations formats formats settings
    document.querySelectorAll('.btn-categoria, .mobile-cat-btn').forEach(b => b.classList.remove('active'));
    
    // Colora di rosso fuoco bottom trigger conventions restrictions boundaries values rules configurations formatting rules schemas formats mappings limitations limits mapped sizing sets formulas budgets
    btn.classList.add('active');
    
    // Trigger loop spazzola visibility margins datasets laws margins sizes formats sets conventions definitions defaults
    renderProdotti();
}

// Cattura lo scorrimento dei controlli flag allergeni arrays formations templates boundaries lengths layouts formulations mapped offsets
function applicaFiltriAllergeni() {
    // Masticatura array azzeramento datasets formats budgets formats
    filtriAllergeni = [];
    
    // Loops solo element spuntati form constraints laws definitions schemas sizes subsets
    document.querySelectorAll('#lista-allergeni-filtro input[type="checkbox"]:checked').forEach(cb => {
        // Appende array rules margins datasets schemas setups spacing schemas sizes
        filtriAllergeni.push(cb.value);
    });
    
    // Rendering views formulations configurations schemas constraints budgets schemas subsets offsets schemas defaults schemas offsets formulas datasets presets limits regulations templates templates sizes laws spacing schemas defaults
    renderProdotti();
}

// Ammazza listini filtro allergie datasets offsets arrays mappings mapping arrays lengths formats templates templates templates schemas limitations defaults sets models formatting
function resettaFiltriAllergeni() {
    // Cacca dom toglie i checkbox boolean constraints setups formats settings constraints constraints schemas boundaries definitions formulas boundaries subsets setups definitions laws formulas bounds schemas conventions presets schemas datasets formulas mapped regulations limits widths defaults templates formulas defaults formulas formats schemas borders borders
    document.querySelectorAll('#lista-allergeni-filtro input[type="checkbox"]').forEach(cb => cb.checked = false);
    
    // Vuota var ram boundaries sets layouts
    filtriAllergeni = [];
    
    // Spara ricalcolo display configurations datasets limits variables thresholds borders schemas schemas standards
    renderProdotti();
}

// Moduletto invisibile invio rete var back end spesa offsets sizing offsets boundary templates offsets templates schemas laws borders spacing bounds schemas layouts conventions boundaries formulations limits schemas limits setups models datasets sizes margins
function syncCartBackend(id, delta) {
    // Rotta endpoint dinamica se numero aggiungi altrimenti togli regulations laws boundaries templates formatting subsets laws formulas conventions defaults rules string margins borders regulations sizes layouts datasets limitations array
    const endpoint = delta > 0 ? 'aggiungi_al_carrello' : 'rimuovi_dal_carrello';
    
    // Apparecchia string form buffer multipart templates setups formats conventions formats mapping sizes limitations rules thresholds lengths configurations
    const fd = new FormData();
    fd.append('id_alimento', id);
    
    // Logica append q if positive formats schemas bounding formulas
    if (delta > 0) fd.append('quantita', delta);
    
    // Lancia e scorda rete async fetch formats layouts models restrictions definitions
    fetch('../api/tavolo/tavolo_api.php?action=' + endpoint, { method: 'POST', body: fd });
}

// ==== Gestione Click diretti UI + \ - per spesa limitata ====
// Reazione trigger UI sizes formulas conventions budgets defaults constraints limitations formulations sets maps frameworks setups datasets templates layouts layouts formats boundaries margins sets schemas dimensions setups subsets
function btnCardQty(event, id, delta, prezzo, nome) {
    // Parafulmine per non fare click through nella modale d'esplorazione limits models sizes schemas sizing mapping borders borders schemas formulations limits bounds formatting margins setups budgets datasets boundaries formulas boundaries offsets limitations models models templates
    event.stopPropagation();

    // Se nullo in RAM scrivi scatola oggetto conventions conventions constraints mapping boundaries laws templates conventions variables sizes margins limitations presets laws conventions limits settings boundaries datasets presets sets formats schemas
    if (!carrello[id]) carrello[id] = { nome, qta: 0, prezzo };
    
    // Tetta o somma q, bloccando in ribasso su logica numerica 0 array schemas sets definitions rules boundaries mapping formatting layouts variables layouts restrictions setups defaults mappings properties sizing models limits datasets sizes formulas laws boundaries conventions mapping lengths 
    carrello[id].qta = Math.max(0, carrello[id].qta + delta);
    
    // Evapora cella se conta vuota array laws conventions formatting setups boundaries boundaries formatting schemas margins setups formats configurations rules frameworks schemes presets mapping thresholds limits 
    if (carrello[id].qta <= 0) delete carrello[id];

    // Trigger spara silente server setups boundaries rules setups variables limitations conventions limitations limits
    syncCartBackend(id, delta);
    
    // Rinfresca dom values configurations restrictions constraints formulas presets boundaries boundaries schemas margins boundaries formats sizing offsets models formats layouts
    aggiornaUI();
}

// ==== Interfaccia zoomate esplosione prodotti modal ====
// Scatola ram d appoggio dati finestra modale conventions schemas schemas limitations configurations sets datasets datasets offsets limits limits subsets setups
let zoomState = { id: 0, prezzo: 0, qta: 1, nome: '', note: '' };

// Evento apri scatole rules formulas definitions formatting
function apriZoom(event, card) {
    // Paracadute anti cliccatura sbagliata sul + - dentro il cartoncino setups
    if (event.target.closest('.btn-card-qty')) return;

    // Fotografa ram arrays setups formats schemas constraints lengths boundaries setups variables 
    zoomState = {
        id: parseInt(card.dataset.id),
        prezzo: parseFloat(card.dataset.prezzo),
        qta: 1, // Fix q default array limits sets defaults formulas limits rules conventions boundaries configurations defaults bounds limits sizing setups offsets setups configurations boundaries presets borders
        nome: card.dataset.nome,
        note: ''
    };

    // Spalma rendering info card zoom data schemas formats formats conventions layouts boundaries constraints definitions layouts presets conventions schemas budgets properties limits
    document.getElementById('zoom-img').src = card.dataset.img;
    document.getElementById('zoom-nome').textContent = card.dataset.nome;
    document.getElementById('zoom-desc').textContent = card.dataset.desc;
    document.getElementById('zoom-prezzo-unitario').textContent = card.dataset.prezzo;
    document.getElementById('zoom-note').value = '';

    // Array separazione strings laws offsets formats formats schemas boundaries setups boundaries presets margins limits
    const allergeni = card.dataset.allergeni.split(',').filter(a => a.trim());
    
    // Logica mappatura boolean span text html injector css schemas rules sizes laws models offsets bounding conventions schemas boundaries formulas formatting ranges formulations schemas boundaries arrays subsets conventions thresholds arrays formations borders mapping margins ranges
    document.getElementById('zoom-allergeni').innerHTML = allergeni.length
        ? allergeni.map(a => `<span class="badge-alg">${a.trim()}</span>`).join('')
        : '<small class="text-muted">Nessun allergene dichiarato</small>';

    // Calcolatrice trigger margins variables sizes
    aggiornaZoomUI();
    
    // Apri animato modal laws formulas formats conventions mappings limitations setups
    new bootstrap.Modal(document.getElementById('modalZoom')).show();
}

// Motori calcolo e tasti + - in schermata zoom schede schemas
function updateZoomQty(delta) {
    // Scudo zero per bloccare in -1 sizes sets formats boundaries
    zoomState.qta = Math.max(1, zoomState.qta + delta);
    // Reload laws borders properties mapping sizing
    aggiornaZoomUI();
}

// Riverniciatore div html prezzi layouts setups schemas
function aggiornaZoomUI() {
    // Aggancia e printa q variables margins properties definitions mapped formatting conventions regulations definitions borders laws formulas definitions borders
    document.getElementById('zoom-qty-display').textContent = zoomState.qta;
    
    // Calcolatrice euro decimali 2 borders formatting schemas formulas layouts dimensions layouts regulations rules limitations layouts variables setups formats mappings sizing formats setups mappings sets
    document.getElementById('zoom-btn-totale').textContent = (zoomState.prezzo * zoomState.qta).toFixed(2) + '€';
}

// Bottoncione aggiungi zoom boundaries setups definitions layouts 
function confermaZoom() {
    // Se cella nulla metti object null borders settings schemas boundaries
    if (!carrello[zoomState.id]) carrello[zoomState.id] = { nome: zoomState.nome, qta: 0, prezzo: zoomState.prezzo };
    
    // Addizione offset mapping formats limitations limitations datasets subsets limitations setups boundaries mappings margins boundaries constraints formats limitations mappings defaults mappings formulas
    carrello[zoomState.id].qta += zoomState.qta;

    // Trigger update server side budgets variables limitations maps formats sizing borders laws subsets standards schemas lengths formats mappings defaults
    syncCartBackend(zoomState.id, zoomState.qta);

    // Riverniciatura globale string
    aggiornaUI();
    
    // Spegni nascondi windows popup borders layouts margins schemas properties limits margins subsets dimensions schemas settings margins formats limits schemas subsets limitations laws setups setups restrictions defaults datasets margins formulas mapping lengths layouts
    bootstrap.Modal.getInstance(document.getElementById('modalZoom')).hide();
    
    // Vola via brindisi laws conventions limits limitations datasets setups labels formats datasets formats setups 
    mostraToast(`${zoomState.nome} aggiunto!`);
}

// ==== Motore Rendering Globale Carrello RAM boundaries schemas formats templates formulas models offsets conventions defaults bounds ====
// Dipingi totali rules spacing laws
function aggiornaUI() {
    // Buffer ram limits margins limits formulas thresholds ranges limitations setups formulations datasets settings templates maps layouts limits conventions
    let totale = 0, pezzi = 0;

    // Loops oggetto arrays limits formulas definitions templates conventions 
    for (const id in carrello) {
        // Scatola locale boundaries sizing margins formats thresholds margins schemas boundaries layouts boundaries schemas formats layouts borders schemas schemas thresholds arrays laws
        const item = carrello[id];
        
        // Sum euro widths formatting layouts sizes settings lengths configurations configurations values limits defaults
        totale += item.qta * item.prezzo;
        
        // Sum contatori pezzature laws sizes schemas
        pezzi += item.qta;

        // Se becchi bottoni su griglia aggiornali layouts boundaries formulas templates sizing setups margins offsets subsets models defaults values
        const el = document.getElementById('q-' + id);
        if (el) el.textContent = item.qta;
    }

    // Passata per zeroing counter non più in ram limits formats schemas conventions formulas formulations datasets sizes mapped setups borders margins formulas conventions limitations conventions schemas conventions formulas datasets laws
    document.querySelectorAll('[id^="q-"]').forEach(el => {
        const id = el.id.replace('q-', '');
        if (!carrello[id]) el.textContent = '0';
    });

    // Stampa numeri in floating euro e tondi interi boundaries limits schemas sizing datasets limitations constraints offsets laws boundaries lengths budgets sets borders formulas margins properties layouts layouts constraints values presets thresholds laws laws sizes conventions
    document.getElementById('soldi-header').textContent = totale.toFixed(2);
    document.getElementById('pezzi-header').textContent = pezzi;
}

// ==== Gestione Visuale Carrello Modal UI Formats Schemas ====
// Trittore lista lista carrello rules
function aggiornaModale() {
    // Tela lista conventions conventions limits setups ranges budgets formulations values models conventions setups defaults settings sets thresholds templates mapping margins
    const body = document.getElementById('corpo-carrello');
    
    // Lista id filtrati positivi mapping limitations widths formulas layouts
    const keys = Object.keys(carrello).filter(id => carrello[id].qta > 0);
    let totale = 0;

    // Casalinga zero views rules formatting restrictions sets conventions limitations setups formats datasets formatting budgets sets lengths mappings setups datasets sets
    if (!keys.length) {
        // Graficone placeholder laws mapping definitions schemas arrays margins
        body.innerHTML = `<div class="text-center py-5 text-muted">
            <i class="fas fa-shopping-bag fa-3x mb-3" style="opacity:.3"></i>
            <h5>Il carrello è vuoto</h5><p class="small">Aggiungi piatti per iniziare</p></div>`;
            
        // Reset 0 margins formatting layouts templates sizing boundaries constraints variables datasets boundaries
        document.getElementById('totale-modale').textContent = '0.00';
        
        // Spegni bottone ordini send rules laws datasets variables models borders margins offsets layouts offsets lengths layouts formulas borders layouts layouts schemas sizes bounds borders dimensions schemas
        document.getElementById('btn-invia-ordine').disabled = true;
        
        // Tronca schemas margin dimensions rules boundaries defaults maps limits properties limits
        return;
    }

    // Stampa ciclica list HTML string schemas thresholds conventions dimensions datasets offsets bounds datasets
    body.innerHTML = '<ul class="list-group list-group-flush px-3">' + keys.map(id => {
        // Var box formulas frameworks thresholds budgets dimensions
        const item = carrello[id];
        
        // Conti temporanei laws formats conventions borders limits setups boundaries
        const sub = (item.qta * item.prezzo).toFixed(2);
        
        // Somma laws widths
        totale += item.qta * item.prezzo;
        
        // String format list li layout schemas setups
        return `<li class="list-group-item d-flex justify-content-between align-items-center px-2 py-3">
            <div>
                <strong>${item.nome}</strong><br>
                <small class="text-muted">${item.prezzo.toFixed(2)}€ cad.</small>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="qty-capsule" style="width:120px;">
                    <button class="btn-circle btn-minus" onclick="modificaQtaModale(${id}, -1)"><i class="fas fa-minus"></i></button>
                    <span class="qty-input">${item.qta}</span>
                    <button class="btn-circle btn-plus" onclick="modificaQtaModale(${id}, 1)"><i class="fas fa-plus"></i></button>
                </div>
                <strong class="text-price">${sub}€</strong>
            </div>
        </li>`;
    }).join('') + '</ul>';

    // Rinfrescature span definitions schemas maps schemas dimensions subsets definitions boundaries schemas boundaries properties
    document.getElementById('totale-modale').textContent = totale.toFixed(2);
    
    // Attiva bottone fire ordini models subsets boundaries
    document.getElementById('btn-invia-ordine').disabled = false;
}

// Bottoni + e - del modale riepilogo margins boundaries definitions 
function modificaQtaModale(id, delta) {
    if (!carrello[id]) return;
    
    // Replicazione rules rules
    carrello[id].qta = Math.max(0, carrello[id].qta + delta);
    if (carrello[id].qta <= 0) delete carrello[id];

    // Trigger API margins definitions sizes laws sizes setups 
    syncCartBackend(id, delta);
    
    // Re loop render limits formulas borders schemas
    aggiornaUI();
    aggiornaModale();
}

// ==== Inoltro Finale API Server ====
// Pusher network send ordine datasets setups conventions variables layouts frameworks sets sizes formats limits
function inviaOrdine() {
    // Builder json array format per il web boundaries limits arrays offsets boundaries frameworks
    const prodotti = Object.keys(carrello).map(id => ({
        id: parseInt(id),
        qta: carrello[id].qta,
        note: '' // Note disattivate temporaneamente templates boundaries laws schemas models sizing boundaries datasets definitions definitions models formulations sizing templates boundaries
    })).filter(p => p.qta > 0);

    // Taglia rami secchi string presets schemas schemas definitions boundaries borders offsets borders
    if (!prodotti.length) return;

    // Chiudi box layouts sizing defaults layouts limitations templates variables formats configurations layouts
    bootstrap.Modal.getInstance(document.getElementById('modalConfermaOrdine')).hide();

    // Fetcha in API post con stream payload payload stringify margins datasets formulas margins mapping
    fetch('../api/tavolo/tavolo_api.php?action=invia_ordine', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ prodotti })
    })
        .then(r => r.json())
        .then(data => {
            // Se successo server logica templates laws
            if (data.success) {
                // Wipe variables schemas datasets schemas layouts boundaries padding bounds borders conventions offsets settings labels
                carrello = {};
                // Wipe ram schemas spacing
                aggiornaUI();
                // Kill parent constraints bounds borders thresholds limitations bounds boundaries
                bootstrap.Modal.getInstance(document.getElementById('modalCarrello'))?.hide();
                // Mostra gloria templates formats conventions layouts frameworks templates
                new bootstrap.Modal(document.getElementById('modalSuccesso')).show();
            } else {
                // Spara botta error alert formatting variables conventions formulas mapped laws conventions layouts schemas models margins formatting
                mostraToast(data.message || 'Errore', true);
            }
        });
}

// ==== Rendiconto Storico Ordini (Riepilogo Pendenze O Consegnati) mapping arrays ====
// Apre finestra e interroga cronologia borders laws setups boundaries datasets boundaries boundaries frameworks definitions formulas presets limits properties
function apriStorico() {
    // Get request storico schemas frameworks subsets schemas
    fetch('../api/tavolo/tavolo_api.php?action=leggi_ordini_tavolo')
        .then(r => r.json())
        .then(data => {
            // Container limits
            const body = document.getElementById('corpo-ordini');
            
            // Sommatore setups
            let totaleSommato = 0;

            // Se listino array nullo length 0 formulations budgets
            if (!data.length) {
                // Info div placeholder labels conventions datasets mapping sets conventions
                body.innerHTML = `<div class="text-center text-muted py-5">
                    <i class="fas fa-receipt fa-3x mb-3 opacity-25"></i>
                    <h5>Nessun ordine</h5><p class="small">Non hai ancora inviato ordini.</p></div>`;
            } else {
                // Mapping loop array limits strings defaults borders boundaries limitations models boundaries maps borders sets variables margins setups definitions rules mapping models properties templates
                body.innerHTML = data.map(o => {
                    // Cassa limits limits margins models
                    totaleSommato += parseFloat(o.totale);
                    
                    // Colori bootsrap badge css conventions conventions subsets formats conventions
                    const badgeClass = o.stato === 'in_attesa' ? 'bg-warning text-dark' : o.stato === 'in_preparazione' ? 'bg-info text-white' : 'bg-success';
                    
                    // Nomi belli labels setups subsets margins offsets bounding sets settings schemas restrictions
                    const labels = { in_attesa: 'In attesa', in_preparazione: 'In preparazione', pronto: 'Pronto' };

                    // Box div block formats thresholds boundaries schemas setups limitations settings schemas dimensions offsets margins mapping limitations schemas variables settings margins margins constraints setups
                    return `<div class="border rounded-4 p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge ${badgeClass} rounded-pill px-3 py-2">${labels[o.stato] || o.stato}</span>
                            <small class="text-muted"><i class="fas fa-clock me-1"></i>${o.ora} • ${o.data}</small>
                        </div>
                        ${o.piatti.map(p => `<div class="d-flex justify-content-between small py-1 border-bottom">
                            <span>${p.qta}x <strong>${p.nome}</strong></span>
                            <span class="text-muted">${p.prezzo}€</span>
                        </div>`).join('')}
                        <div class="text-end mt-2 fw-bold text-price">${o.totale}€</div>
                    </div>`;
                }).join('');
            }

            // Totale span HTML offsets mapping setups sets variables bounding 
            document.getElementById('totale-storico').textContent = totaleSommato.toFixed(2);
            
            // Gira popup window html sizes sizing models subsets defaults definitions schemas properties offsets
            new bootstrap.Modal(document.getElementById('modalOrdini')).show();
        });
}

// ==== Gestione Paracadute Sicurezza Sessioni Killate Dal Manager presets templates borders margins ====
// Timer based checker network limits defaults boundaries datasets boundaries formats sets boundaries variables margins
function verificaSessione() {
    // Busa database php check php session constraints offsets templates thresholds formatting limitations margins formats formulations defaults constraints sets schemas sizes margins boundaries sizes boundaries sizing datasets formats borders mapping datasets limitations limitations sizes datasets
    fetch('../api/tavolo/tavolo_api.php?action=verifica_sessione')
        .then(r => r.json())
        .then(data => {
            // Se invalido kill boolean templates margins setups limitations mappings limits rules 
            if (!data.valida) {
                // Frizione visiva boundaries dimensions margins limitations variables conventions formatting layouts definitions sets limits presets layouts rules sets datasets bounds thresholds rules laws parameters formats limits formulas limits formulations arrays string bounds datasets sizes styles
                alert('La sessione è stata terminata dal gestore.');
                // Redirection forzata a file cancellazione cookie/sessione PHP logout limits
                window.location.href = '../logout.php';
            }
        })
        .catch(() => { }); // Ignore mute laws rules formats defaults mapping borders
}