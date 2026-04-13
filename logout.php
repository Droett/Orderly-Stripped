<?php
// Avvia o riprende la sessione
session_start(); 

// Se l'utente che sta facendo il logout è di tipo 'tavolo' ed è validamente loggato
if (isset($_SESSION['ruolo']) && $_SESSION['ruolo'] === 'tavolo' && isset($_SESSION['id_tavolo'])) {
    
    // Connetti al DB per eseguire lo "sganciamento" fisico del tavolo dal gestionale
    include "include/conn.php"; 

    $idTavolo = intval($_SESSION['id_tavolo']);

    // Rimette il tavolo a stato 'libero' in modo che in dashboard manager appaia nuovamente verde 
    // e setta il device token a nullo
    $conn->query("UPDATE utenti SET stato='libero', device_token=NULL WHERE id_utente=" . $idTavolo);

    // "Mangia" o cancella il cookie del device token portando la sua data di scadenza nel passato (-3600 secondi)
    setcookie('device_token_' . $idTavolo, '', time() - 3600, '/');
}

// Libera/svuota tutte le variabili registrate in sessione (es. array $_SESSION sarà vuoto)
session_unset();

// Distrugge definitivamente il file o riferimento di sessione lato server
session_destroy();

// Redirect l'utente di nuovo alla pagina di Login principale
header("Location: index.php");

// Ferma l'esecuzione di qualsiasi codice che potrebbe seguire
exit;
?>
