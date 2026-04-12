<?php
// Blocco distruttore distruzioni sessioni PHP e sloggate dal server system

session_start(); // Apri pacchetto scatola sessione lato server buffer memory limits

// Controlla se a lanciare l esci kill server è un banale tavolo arrays rules 
// Se è un tavolo, libera il posto in root db sql object schema e cancella il device_token limit
if (isset($_SESSION['ruolo']) && $_SESSION['ruolo'] === 'tavolo' && isset($_SESSION['id_tavolo'])) {
    include "include/conn.php"; // Tiralo dal link params connection
    // Sanitize string to INT rules conventions limits schemas boundaries
    $idTavolo = intval($_SESSION['id_tavolo']);

    // Cancella cookie e sbatti libero in sql query parameters schemas formatting margins
    $conn->query("UPDATE utenti SET stato='libero', device_token=NULL WHERE id_utente=" . $idTavolo);
    
    // Cookie time death minus 3600 per sparare il cookie cookie nel passato di ore uccidendone il ttl rules margins
    setcookie('device_token_' . $idTavolo, '', time() - 3600, '/');
}

// Azzoppa variables globals session parameters schemas
session_unset();
// Distruzione cruda kill process session RAM limits layouts rules formats schemas parameters definitions spacing
session_destroy();

// Piegati a index rediretta e ciao limitations layouts formatting datasets schemas constraints margins datasets schemas margins presets
header("Location: index.php");
// End margins parameters datasets setups margins datasets offsets offsets schemas mapping variables
exit;
?>