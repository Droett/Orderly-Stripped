<?php
// Motore di connessione al database relazionale MySQL backend core
$conn = mysqli_connect("localhost", "root", "", "ristorante_db"); // Instaura instradamento pipe API server con i parametri local host zero psw

// Scudo di bloccaggio in caso di password rotte o database file corrotto o assente (es XAMPP down)
if (!$conn)
    die("Errore di connessione: " . mysqli_connect_error()); // Uccide secco the engine ed emette print rosso di fail server
?>