<?php
// Importa piatti nel menu da un file CSV caricato dal manager
session_start();
include "../include/conn.php";
require_once "../include/auth/check_permesso.php";
require_once "../include/functions.php";

// Solo il manager può importare il menu
if (!verificaPermesso($conn, 'dashboard/manager')) {
    header("Location: ../index.php");
    exit;
}

// Accettiamo solo richieste POST (il form di upload invia il file)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['csv_file'])) {
    redirect_con_messaggio("manager.php?section=menu", "Nessun file selezionato.", "errore");
}

$file = $_FILES['csv_file'];

// Verifica che il file sia stato caricato senza errori
if ($file['error'] !== UPLOAD_ERR_OK) {
    redirect_con_messaggio("manager.php?section=menu", "Errore durante il caricamento del file.", "errore");
}

// Verifica che sia un file .csv
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($ext !== 'csv') {
    redirect_con_messaggio("manager.php?section=menu", "Il file deve essere in formato .csv", "errore");
}

// Apriamo il file CSV in lettura
$handle = fopen($file['tmp_name'], 'r');
if (!$handle) {
    redirect_con_messaggio("manager.php?section=menu", "Impossibile leggere il file.", "errore");
}

// Rimuoviamo il BOM UTF-8 se presente (3 byte iniziali)
$bom = fread($handle, 3);
if ($bom !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
    rewind($handle); // Se non c'è BOM, torniamo all'inizio
}

// Leggiamo la prima riga (intestazione) e la ignoriamo
$header = fgetcsv($handle, 0, ';');

// Precarichiamo tutte le categorie esistenti per trovare l'id dalla nome
$categorie = [];
$catResult = $conn->query("SELECT id_categoria, nome_categoria FROM categorie");
while ($cat = $catResult->fetch_assoc()) {
    $categorie[strtolower(trim($cat['nome_categoria']))] = $cat['id_categoria'];
}

// Contatori per il feedback
$importati = 0;
$saltati = 0;

// Leggiamo il CSV riga per riga
// Formato atteso: Nome Piatto; Categoria; Prezzo (€); Descrizione; Allergeni
while (($row = fgetcsv($handle, 0, ';')) !== false) {
    // Saltiamo righe vuote o incomplete
    if (count($row) < 3 || empty(trim($row[0]))) {
        $saltati++;
        continue;
    }

    $nome      = trim($row[0]);
    $categoria = trim($row[1] ?? '');
    $prezzo    = floatval(str_replace(',', '.', $row[2] ?? '0'));
    $desc      = trim($row[3] ?? '');
    $allergeni = trim($row[4] ?? '');

    // Cerchiamo la categoria per nome; se non esiste, la creiamo automaticamente
    $catKey = strtolower($categoria);
    if (!empty($categoria) && !isset($categorie[$catKey])) {
        $newId = db_insert($conn, "INSERT INTO categorie (nome_categoria, id_menu) VALUES (?, 1)", [$categoria], "s");
        $categorie[$catKey] = $newId;
    }
    $idCategoria = $categorie[$catKey] ?? 0;

    // Inseriamo il piatto nel database (senza immagine)
    db_insert($conn,
        "INSERT INTO alimenti (nome_piatto, descrizione, prezzo, id_categoria, immagine, lista_allergeni) VALUES (?, ?, ?, ?, NULL, ?)",
        [$nome, $desc, $prezzo, $idCategoria, $allergeni],
        "ssdis"
    );
    $importati++;
}

fclose($handle);

// Messaggio di feedback con il conteggio
$msg = "$importati piatti importati con successo!";
if ($saltati > 0) $msg .= " ($saltati righe saltate)";
redirect_con_messaggio("manager.php?section=menu", $msg, "successo");
?>
