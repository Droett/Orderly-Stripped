// =========================================================
// Pannello (Kitchen Dashboard) — Lavagna Kanban ordini con richieste di refresh cicliche
// =========================================================

// Accumulatore ordini precedente per sapere quando suonare gli allarmi di nuovi ticket ordine
let lastOrderCount = 0;

// Alloca spazio in memoria e carica il file di notifica sonora suoni
const audio = new Audio('../audio/notifica_cucina.mp3');

// Aggancio evento innesco funzioni allo scattare dell'avvenuto carico browser DOM
document.addEventListener('DOMContentLoaded', function () {
    // Chiama la prima iniezione asincrona visuale per caricare i blocchi ordine
    caricaOrdini();
    // Reitera ciclicamente ad intervalli di 5000 millisec (5 s) la stessa per fare refresh visivo live
    setInterval(caricaOrdini, 5000);
});

// Funzione master che preleva i dati JSON crudi JSON dal file API Backend
function caricaOrdini() {
    // Connetti via metodo verb GET le API di endpoint specifici x i piatti order
    fetch('../api/cucina/cucina_api.php?action=leggi_ordini_cucina')
        // Risolvi asincrono convertendo responsi raw JSON web buffer array string raw format in javascript object map
        .then(r => r.json())
        // Connetti pipe passaggi logici coi dati processabili object struct javascript 
        .then(data => {
            // Separa filtro list data status solo ticket sospesi attesa 'in_attesa'
            const inAttesa = data.filter(o => o.stato === 'in_attesa');
            
            // Separa filtro list data status logici ordini attivi cucinando 'in_preparazione'
            const inPrep = data.filter(o => o.stato === 'in_preparazione');

            // Setta counter bolla numerica visuale html count per i ticket nuovi
            document.getElementById('count-new').textContent = inAttesa.length;
            
            // Setta counter bolla numerica html per la roba ora sui fornelli
            document.getElementById('count-prep').textContent = inPrep.length;

            // Fila HTML dei taselli nuovi mappata con function di rendering array concat
            document.getElementById('col-new').innerHTML = inAttesa.map(o => renderCard(o, 'new')).join('');
            
            // Fila html de tasselli sui fornelli mappata array
            document.getElementById('col-prep').innerHTML = inPrep.map(o => renderCard(o, 'prep')).join('');

            // Se numero nuovi è maggiore del prima, e ce ne sono maggiori di zero scatta suono
            if (inAttesa.length > lastOrderCount && lastOrderCount > 0) {
                // Play forzato con aggancio null error block x policy browser anti noise auto
                audio.play().catch(() => { });
            }
            
            // Aggiorna lo snap di buffer contatore prima fase
            lastOrderCount = inAttesa.length;
        });
}

// Layout template costruttore html a tassello ticket singolare 
function renderCard(o, tipo) {
    // Logica if per generare etichetta sul pulsante "inizia" o "segna pronto"
    const btnLabel = tipo === 'new' ? 'Inizia Preparazione' : 'Segna come Pronto ✓';
    
    // Regola gli switch logici db prossimo stato click (new->prep , prep->pronto) 
    const nextState = tipo === 'new' ? 'in_preparazione' : 'pronto';
    
    // Alterna visuale button design colori
    const btnClass = tipo === 'new' ? 'btn-start' : 'btn-green-custom';

    // Disegna internamente la fila piatti html in ciclo di join su string formattazioni loop list array piatti
    const piatti = o.piatti.map(p =>
        `<div class="dish-row"><div class="qty-capsule">${p.qta}x</div>
         <div><strong>${p.nome}</strong>${p.note ? `<br><small class="text-muted"><i class="fas fa-sticky-note me-1"></i>${p.note}</small>` : ''}</div></div>`
    ).join('');

    // Ritorno il blob formattato card html string limit text con tutto combinato da piantare in DOM innerHTML list string string formato blocco limit layout blocchi
    return `<div class="order-card">
        <div class="card-top">
            <div class="table-badge">${o.tavolo}</div>
            <div class="time-badge"><i class="fas fa-clock"></i> ${o.ora}</div>
        </div>
        ${piatti}
        <button class="btn-action ${btnClass}" onclick="cambiaStato(${o.id_ordine}, '${nextState}')">
            <i class="fas ${tipo === 'new' ? 'fa-fire' : 'fa-check'}"></i> ${btnLabel}
        </button>
    </div>`;
}

// Iniezione comandi click POST api x variare il flusso database del DB ordini backend mysql api control panel status
function cambiaStato(id, stato) {
    // Push json post x fare scattare updates back office system ordini stato backend update record table limits 
    fetch('../api/cucina/cucina_api.php?action=cambia_stato_ordine', {
        method: 'POST', // Invia formati server string buffer limit boundaries limit network call boundaries
        headers: { 'Content-Type': 'application/json' }, // Intesta JSON raw byte data streams formats form parameters headers network rules
        body: JSON.stringify({ id_ordine: id, nuovo_stato: stato }) // Fai casting json text buffer
    })
        .then(r => r.json()) // Leggilo cast json responso bool object parser
        .then(data => {
            // Refresh tutto a successo database update completato e ok visuale ticket reset reload fetch 
            if (data.success) caricaOrdini();
        });
}
