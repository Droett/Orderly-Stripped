<?php
// Esporta il menu completo in formato CSV (scaricabile dal browser)
session_start();
include "../include/conn.php";
require_once "../include/auth/check_permesso.php";

// Solo il manager può esportare il menu
if (!verificaPermesso($conn, 'dashboard/manager')) {
    header("Location: ../index.php");
    exit;
}

// Impostiamo gli header HTTP per forzare il download di un file .csv
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="menu_orderly_' . date('Y-m-d') . '.csv"');

// Apriamo lo "stream di output" di PHP come se fosse un file
$output = fopen('php://output', 'w');

// BOM UTF-8: serve per far riconoscere i caratteri accentati a Excel
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Riga di intestazione del CSV
fputcsv($output, ['Nome Piatto', 'Categoria', 'Prezzo (€)', 'Descrizione', 'Allergeni'], ';');

// Query: recupera tutti i piatti con il nome della categoria associata
$sql = "SELECT a.nome_piatto, c.nome_categoria, a.prezzo, a.descrizione, a.lista_allergeni
        FROM alimenti a
        LEFT JOIN categorie c ON a.id_categoria = c.id_categoria
        ORDER BY c.nome_categoria, a.nome_piatto";
$result = $conn->query($sql);

// Scriviamo ogni piatto come riga nel CSV
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['nome_piatto'],
        $row['nome_categoria'] ?? 'Senza categoria',
        number_format($row['prezzo'], 2, ',', ''),
        $row['descrizione'],
        $row['lista_allergeni']
    ], ';');
}

fclose($output);
exit;
?>
