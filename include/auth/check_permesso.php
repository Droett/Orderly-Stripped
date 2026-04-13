<?php
// Blocco blindatura server — Verifica i permessi di accesso a un endpoint API 

// Metodo nucleo per incrociare ticket utente con pass della stanza API GET o POST
function verificaPermesso($conn, $endpoint) {
    // Check superficiale se il client ha un flag cookie sessione loggata attiva in ram, altrimenti negazione istantanea
    if (!isset($_SESSION['ruolo'])) return false;

    // Cattura la tessera pass stringa del grado untente, eg. manager o cuciniere
    $ruolo = $_SESSION['ruolo'];
    
    // Mappa dei ruoli ammessi per macro-aree
    $permessi = [
        'manager' => ['manager'],
        'cucina' => ['cuoco', 'manager'],
        'tavolo' => ['tavolo']
    ];

    // Estrae il prefisso dell'endpoint (es. "manager", "cucina", "tavolo", "dashboard")
    $parti = explode('/', $endpoint);
    $contesto = $parti[0];

    // Se stiamo accedendo a una dashboard, il contesto rilevante è il nome della dashboard
    if ($contesto === 'dashboard') {
        $contesto = isset($parti[1]) ? $parti[1] : '';
    }

    // Verifica se il ruolo ha accesso al contesto richiesto
    if (isset($permessi[$contesto])) {
        return in_array($ruolo, $permessi[$contesto]);
    }

    return false;
}
?>
