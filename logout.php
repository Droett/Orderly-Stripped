<?php
session_start();

// Se è un tavolo, libera il posto e cancella il device_token
if (isset($_SESSION['ruolo']) && $_SESSION['ruolo'] === 'tavolo' && isset($_SESSION['id_tavolo'])) {
    include "include/conn.php";
    $idTavolo = intval($_SESSION['id_tavolo']);

    $conn->query("UPDATE utenti SET stato='libero', device_token=NULL WHERE id_utente=" . $idTavolo);
    setcookie('device_token_' . $idTavolo, '', time() - 3600, '/');
}

session_unset();
session_destroy();

header("Location: index.php");
exit;
?>