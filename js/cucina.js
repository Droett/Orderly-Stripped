// ============================================================
// cucina.js — Logica della Dashboard Cucina
// ============================================================
// Questo file gestisce la bacheca degli ordini in tempo reale per la cucina.
//
// Cosa fa:
//   - Recupera gli ordini attivi dall'API ogni 5 secondi
//   - Visualizza le card degli ordini in due colonne (nuovi / in preparazione)
//   - Riproduce un suono quando arriva un nuovo ordine
//   - Permette ai cuochi di cambiare lo stato di un ordine con un clic
// ============================================================


// Tiene traccia di quanti ordini "in_attesa" (nuovi) esistevano l'ultima volta.
// Usato per rilevare quando arriva un nuovo ordine così da riprodurre un suono.
let lastOrderCount = 0;

// Pre-carica il suono di notifica così è pronto a essere riprodotto immediatamente.
// Gli oggetti Audio funzionano come un lettore musicale — lo carichiamo una volta e
// chiamiamo .play() quando necessario.
const audio = new Audio('../audio/notifica_cucina.mp3');


// Quando la pagina finisce di caricarsi:
//   1. Recupera e mostra subito tutti gli ordini attivi
//   2. Ripeti ogni 5 secondi per mantenere la bacheca aggiornata (aggiornamento automatico)
document.addEventListener('DOMContentLoaded', function () {
    caricaOrdini();
    setInterval(caricaOrdini, 5000); // 5000 ms = 5 secondi
});


/**
 * caricaOrdini()
 * Recupera tutti gli ordini attivi dall'API e aggiorna la bacheca.
 * Viene chiamata al caricamento e poi ogni 5 secondi automaticamente.
 */
function caricaOrdini() {

    // Chiama l'API — fetch() invia una richiesta HTTP e restituisce una Promise
    fetch('../api/cucina/cucina_api.php?action=leggi_ordini_cucina')

        // Passo 1: Analizza la risposta testuale dell'API come dati JSON
        .then(r => r.json())

        // Passo 2: Usa i dati analizzati per aggiornare la pagina
        .then(data => {

            // Divide tutti gli ordini in due gruppi in base al loro stato
            const inAttesa = data.filter(o => o.stato === 'in_attesa');        // Colonna "In arrivo"
            const inPrep   = data.filter(o => o.stato === 'in_preparazione'); // Colonna "In preparazione"

            // Aggiorna i contatori degli ordini nell'intestazione di ogni colonna
            document.getElementById('count-new').textContent  = inAttesa.length;
            document.getElementById('count-prep').textContent = inPrep.length;

            // Ri-visualizza entrambe le colonne con le card degli ordini più recenti
            // renderCard() costruisce l'HTML per ogni card dell'ordine (vedi sotto)
            document.getElementById('col-new').innerHTML  = inAttesa.map(o => renderCard(o, 'new')).join('');
            document.getElementById('col-prep').innerHTML = inPrep.map(o => renderCard(o, 'prep')).join('');

            // Riproduce un suono di notifica se ci sono PIÙ ordini nuovi rispetto all'ultima volta.
            // Saltiamo il suono al primo caricamento (lastOrderCount === 0).
            if (inAttesa.length > lastOrderCount && lastOrderCount > 0) {
                audio.play().catch(() => {}); // .catch() silenzia gli errori se il browser blocca l'autoplay
            }

            // Ricorda questo conteggio per il prossimo confronto
            lastOrderCount = inAttesa.length;
        });
}


/**
 * renderCard(o, tipo)
 * Costruisce e restituisce la stringa HTML per una singola card ordine.
 *
 * o    — l'oggetto ordine: { id_ordine, tavolo, stato, ora, piatti: [...] }
 * tipo — 'new' (in attesa) oppure 'prep' (in preparazione)
 *
 * Ogni card mostra:
 *   - Da quale tavolo proviene l'ordine
 *   - A che ora è stato effettuato
 *   - La lista dei piatti (con eventuali note)
 *   - Un pulsante d'azione per avanzare l'ordine alla fase successiva
 */
function renderCard(o, tipo) {

    // Determina il testo del pulsante e il prossimo stato in base alla colonna della card
    const btnLabel  = tipo === 'new' ? 'Inizia Preparazione' : 'Segna come Pronto ✓';
    const nextState = tipo === 'new' ? 'in_preparazione'     : 'pronto';
    const btnClass  = tipo === 'new' ? 'btn-start'           : 'btn-green-custom';

    // Costruisce l'HTML per la lista dei piatti all'interno della card
    // Ogni piatto ha la propria riga, con una riga opzionale per le note
    const piatti = o.piatti.map(p =>
        `<div class="dish-row">
            <div class="qty-capsule">${p.qta}x</div>
            <div>
                <strong>${p.nome}</strong>
                ${p.note ? `<br><small class="text-muted"><i class="fas fa-sticky-note me-1"></i>${p.note}</small>` : ''}
            </div>
        </div>`
    ).join('');

    // Restituisce l'HTML completo della card
    // Cliccando il pulsante d'azione si chiama cambiaStato() con l'ID dell'ordine e il prossimo stato
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


/**
 * cambiaStato(id, stato)
 * Invia una richiesta API per aggiornare lo stato di un ordine.
 * Dopo un aggiornamento riuscito, ricarica immediatamente la bacheca.
 *
 * id    — l'ID dell'ordine da aggiornare
 * stato — il nuovo stato da impostare ('in_preparazione' oppure 'pronto')
 */
function cambiaStato(id, stato) {

    fetch('../api/cucina/cucina_api.php?action=cambia_stato_ordine', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }, // Informa il server che stiamo inviando JSON
        body: JSON.stringify({ id_ordine: id, nuovo_stato: stato }) // Converte l'oggetto JS in stringa JSON
    })
        .then(r => r.json())
        .then(data => {
            // Se l'aggiornamento ha avuto successo, ricarica subito la bacheca
            // così la card si sposta nella colonna giusta (o scompare se 'pronto')
            if (data.success) caricaOrdini();
        });
}
