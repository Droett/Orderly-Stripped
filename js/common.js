// Funzionalità JS condivise tra tutte le pagine applicative
// =========================================================

// Sincronizza visivamente l'icona del tema al caricamento della pagina del browser
document.addEventListener('DOMContentLoaded', function () {
    // Controlla nel contenitore locale del browser se esiste la chiave 'theme' pari a 'dark' scuro
    if (localStorage.getItem('theme') === 'dark') {
        // Cerca tutta la lista di elementi icone tema nella pagina corrente
        document.querySelectorAll('[id="theme-icon"]').forEach(icon => {
            // Sostituisci l'icona lunetta con l'icona del sole chiaro per invertirne stato visivo
            icon.classList.replace('fa-moon', 'fa-sun');
        });
    }
});

// Sistema di Toast per mostrare Notifiche e Errori universale base
function mostraToast(msg, isError = false) {
    // Cerca l'ID fisico del toast appropriato e predisposto per la visualizzazione nella pagina corrente
    const el = document.getElementById('managerToast') || document.getElementById('liveToast');
    
    // Se non trovi il contenitore fisico esci subito vuoto preventivo di null reference error
    if (!el) return;

    // Riscrivi classi css forzando lo span o l'allievo d'errore a css rossi pericolo isError var
    el.className = `toast align-items-center text-white border-0 shadow-lg ${isError ? 'bg-danger' : 'bg-success'}`;
    
    // Trova l'etichetta testo contenuta interna al brindisi/toast html span
    const msgEl = el.querySelector('.toast-body span');
    
    // Assegna e scrivi var stringa messaggio come testo puro interno del container
    if (msgEl) msgEl.textContent = msg;

    // Forza invocazione API bootstrap UI per lanciare la comparsa toast da schermo di 3 mila milli sec
    new bootstrap.Toast(el, { delay: 3000 }).show();
}
