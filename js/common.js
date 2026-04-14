// ============================================================
// common.js — Utilità condivise
// ============================================================
// Questo file viene caricato su OGNI pagina (tavolo, cucina, manager).
// Contiene piccole funzioni di supporto usate ovunque.
// ============================================================


// Quando la pagina finisce di caricarsi, controlla se l'utente aveva
// attivato la modalità scura. In caso affermativo, sostituisce l'icona
// della luna con quella del sole per riflettere lo stato attuale.
document.addEventListener('DOMContentLoaded', function () {

    if (localStorage.getItem('theme') === 'dark') {
        // Possono esserci più icone tema nella pagina (barra laterale + barra mobile),
        // quindi le scorriamo tutte con un loop
        document.querySelectorAll('[id="theme-icon"]').forEach(icon => {
            icon.classList.replace('fa-moon', 'fa-sun');
        });
    }
});


/**
 * toggleTheme()
 * Alterna la pagina tra modalità scura e modalità chiara.
 * La preferenza viene salvata in localStorage così persiste
 * anche dopo un aggiornamento della pagina o la navigazione verso un'altra pagina.
 */
function toggleTheme() {
    // Legge il tema attualmente salvato (o 'light' come valore predefinito)
    const current = localStorage.getItem('theme') || 'light';
    const next    = current === 'dark' ? 'light' : 'dark';

    // Salva la nuova scelta per sopravvivere ai ricaricamenti della pagina
    localStorage.setItem('theme', next);

    // Ricarica la pagina — il CSS applicherà automaticamente il tema salvato
    location.reload();
}


/**
 * mostraToast(msg, isError)
 * Mostra una piccola notifica a comparsa in fondo allo schermo.
 *
 * msg     — il testo da mostrare (es. "Ordine salvato!")
 * isError — se true, il toast diventa rosso (errore); altrimenti verde (successo)
 *
 * Il componente Toast di Bootstrap gestisce l'animazione e si nasconde
 * automaticamente dopo 3 secondi.
 */
function mostraToast(msg, isError = false) {
    // Pagine diverse usano ID diversi per il toast, quindi controlliamo entrambi
    const el = document.getElementById('managerToast') || document.getElementById('liveToast');

    // Se non esiste nessun elemento toast in questa pagina, non fare nulla
    if (!el) return;

    // Imposta il colore di sfondo: rosso per gli errori, verde per i successi
    el.className = `toast align-items-center text-white border-0 shadow-lg ${isError ? 'bg-danger' : 'bg-success'}`;

    // Aggiorna il testo del messaggio all'interno del toast
    const msgEl = el.querySelector('.toast-body span');
    if (msgEl) msgEl.textContent = msg;

    // Mostra il toast e nascondilo automaticamente dopo 3000ms (3 secondi)
    new bootstrap.Toast(el, { delay: 3000 }).show();
}
