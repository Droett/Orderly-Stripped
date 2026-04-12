<?php
// Connessione al database MySQL
$conn = mysqli_connect("localhost", "root", "", "ristorante_db");

if (!$conn)
    die("Errore di connessione: " . mysqli_connect_error());
?>