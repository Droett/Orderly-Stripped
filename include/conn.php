<?php
// Inizializza la connessione al database MySQL utilizzando le credenziali server locali
// - Host: "localhost" (il database si trova sullo stesso server locale)
// - Utente: "root" (l'utente amministratore di default in ambienti di sviluppo locali es. XAMPP)
// - Password: "" (nessuna password impostata, tipico in sviluppo)
// - Nome Database: "ristorante_db" (il database specifico del progetto)
$conn = mysqli_connect("localhost", "root", "", "ristorante_db"); 

// Verifica se la connessione è fallita
if (!$conn)
    // Se fallisce, interrompe immediatamente l'esecuzione del codice (die) e mostra un messaggio d'errore stampando l'errore effettivo di MySQL
    die("Errore di connessione: " . mysqli_connect_error()); 
?>
