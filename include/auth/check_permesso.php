<?php
// Questa funzione controlla se l'utente corrente ha i permessi per accedere a uno specifico endpoint o pagina
function verificaPermesso($conn, $endpoint) {

    // Se nella sessione attuale non è stato impostato alcun "ruolo" (es. utente non loggato), nega subito l'accesso
    if (!isset($_SESSION['ruolo'])) return false;

    // Salva il ruolo dell'utente loggato in una variabile locale per facilità d'uso (es. 'manager', 'cuoco', 'tavolo')
    $ruolo = $_SESSION['ruolo'];

    // Questa mappa definisce quali ruoli possono accedere a quali "macro-aree" (o contesti) del sistema applicativo
    $permessi = [
        // L'area 'manager' è accessibile ESCLUSIVAMENTE dagli utenti con ruolo 'manager'
        'manager' => ['manager'],
        // L'area 'cucina' è accessibile dai 'cuochi', ma anche dai 'manager' per scopi di supervisione
        'cucina' => ['cuoco', 'manager'],
        // L'area 'tavolo' è riservata unicamente ai dispositivi loggati come 'tavolo' per le ordinazioni clienti
        'tavolo' => ['tavolo']
    ];

    // L'endpoint passato in input solitamente ha una struttura tipo "contesto/azione" (es. "manager/aggiungi_piatto")
    // Usiamo explode per dividere l'endpoint in un array di parti separate dalla barra "/"
    $parti = explode('/', $endpoint);
    // Il "contesto" principale è la primissima parte dell'URL (es. "manager", "cucina", "tavolo", "dashboard")
    $contesto = $parti[0];

    // Gestione speciale se l'endpoint inizia con "dashboard":
    // "dashboard" non è un ruolo o area univoca, ma un prefisso generale per le viste HTML (es. "dashboard/tavolo").
    if ($contesto === 'dashboard') {
        // Se c'è una seconda parte (es. "tavolo" in "dashboard/tavolo"), usiamo quella come vero contesto da validare
        // Se non c'è una seconda parte (es. solo "dashboard/"), impostiamo una stringa vuota per forzare il fallimento del check
        $contesto = isset($parti[1]) ? $parti[1] : '';
    }

    // Controlliamo se la mappa dei permessi contiene una regola per questo specifico contesto (es. c'è la chiave "manager"?)
    if (isset($permessi[$contesto])) {
        // Verifica se il "ruolo" dell'utente esiste all'interno dell'array di ruoli autorizzati per quel contesto specifico
        // Restituisce true se l'utente è autorizzato, false altrimenti
        return in_array($ruolo, $permessi[$contesto]);
    }

    // Se il contesto non esiste nella nostra mappa dei permessi (es. url sconosciuta o base), neghiamo l'accesso di default (sicurezza)
    return false;
}
?>
