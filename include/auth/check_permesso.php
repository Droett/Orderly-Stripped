<?php
// Blocco blindatura server — Verifica i permessi di accesso a un endpoint API scansionando la tabella mysql 'permessi_endpoint'

// Metodo nucleo per incrociare ticket utente con pass della stanza API GET o POST
function verificaPermesso($conn, $endpoint) {
    // Check superficiale se il client ha un flag cookie sessione loggata attiva in ram, altrimenti negazione istantanea
    if (!isset($_SESSION['ruolo'])) return false;

    // Cattura la tessera pass stringa del grado untente, eg. manager o cuciniere
    $ruolo = $_SESSION['ruolo'];
    
    // Compila la query blindata parametrizzata MySQL pre compilata anti Injection attacks cerca "1" se c'è match ruoli
    $stmt = $conn->prepare("SELECT 1 FROM permessi_endpoint WHERE endpoint = ? AND ruolo = ? LIMIT 1");
    
    // Inietta pulite le due stringhe (s s) al posto dei ? nella stringa SQL
    $stmt->bind_param("ss", $endpoint, $ruolo);
    
    // Invoca processore database execution code
    $stmt->execute();
    
    // Restituisci cassetto risposte buffer table MySQL structure properties
    $result = $stmt->get_result();

    // Ritorna espressione Bool, True se trovata 1 riga combaciante DB altrimenti False
    return $result->num_rows > 0;
}
?>
