<?php
session_start();
require_once '../../include/conn.php';
require_once '../../include/auth/check_permesso.php';

$action = $_GET['action'] ?? '';

// Blocca chiunque non sia un tavolo autenticato
if (!verificaPermesso($conn, "tavolo/" . $action)) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Non autorizzato']));
}

// --- HELPER ---

// Risponde in JSON e termina
function json($data) {
    header('Content-Type: application/json');
    die(json_encode($data));
}

// Trova il carrello attivo (ordine in_attesa) del tavolo, oppure ne crea uno nuovo
function trovaOCreaCarrello($conn, $idTavolo) {
    $res = $conn->query("SELECT id_ordine FROM ordini WHERE id_utente = $idTavolo AND stato = 'in_attesa' LIMIT 1");
    if ($res->num_rows > 0) return $res->fetch_assoc()['id_ordine'];

    $conn->query("INSERT INTO ordini (id_utente, stato, data_ora) VALUES ($idTavolo, 'in_attesa', NOW())");
    return $conn->insert_id;
}

// Recupera l'orario di login del tavolo per limitare la visibilità degli ordini alla sessione corrente
function orarioLoginTavolo($conn, $idTavolo) {
    $stmt = $conn->prepare("SELECT sessione_inizio FROM utenti WHERE id_utente = ?");
    $stmt->bind_param("i", $idTavolo);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row['sessione_inizio'] ?? '1970-01-01 00:00:00';
}

// --- ROUTER ---

switch ($action) {

    // Aggiunge un piatto al carrello (o incrementa la quantità se già presente)
    case 'aggiungi_al_carrello': {
        $idTavolo = $_SESSION['id_tavolo'];
        $idPiatto = intval($_POST['id_alimento'] ?? 0);
        $qta      = intval($_POST['quantita'] ?? 1);

        if ($idPiatto <= 0) json(['success' => false, 'message' => 'Piatto non valido.']);

        $idOrdine = trovaOCreaCarrello($conn, $idTavolo);

        // Se il piatto è già nel carrello, incrementa la quantità; altrimenti inserisci una nuova riga
        $check = $conn->query("SELECT quantita FROM dettaglio_ordini WHERE id_ordine = $idOrdine AND id_alimento = $idPiatto");

        if ($check->num_rows > 0) {
            $conn->query("UPDATE dettaglio_ordini SET quantita = quantita + $qta WHERE id_ordine = $idOrdine AND id_alimento = $idPiatto");
        } else {
            $conn->query("INSERT INTO dettaglio_ordini (id_ordine, id_alimento, quantita) VALUES ($idOrdine, $idPiatto, $qta)");
        }

        json(['success' => true, 'message' => 'Aggiunto al carrello.']);
    }

    // Restituisce il contenuto attuale del carrello
    case 'get_carrello': {
        if (!isset($_SESSION['id_tavolo'])) json([]);

        $idTavolo = intval($_SESSION['id_tavolo']);

        $result = $conn->query("SELECT d.id_alimento, d.quantita, a.nome_piatto, a.prezzo
            FROM dettaglio_ordini d
            JOIN ordini o ON d.id_ordine = o.id_ordine
            JOIN alimenti a ON d.id_alimento = a.id_alimento
            WHERE o.id_utente = $idTavolo AND o.stato = 'in_attesa'");

        json($result ? $result->fetch_all(MYSQLI_ASSOC) : []);
    }

    // Finalizza il carrello e invia l'ordine in cucina (usa una transazione per sicurezza)
    case 'invia_ordine': {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['prodotti']))
            json(['success' => false, 'message' => 'Il carrello è vuoto.']);

        $idTavolo = $_SESSION['id_tavolo'];
        $conn->begin_transaction();

        try {
            // Crea l'ordine principale
            $stmt = $conn->prepare("INSERT INTO ordini (id_utente, stato, data_ora) VALUES (?, 'in_attesa', NOW())");
            $stmt->bind_param("i", $idTavolo);
            if (!$stmt->execute()) throw new Exception("Errore creazione ordine.");

            $idOrdine = $conn->insert_id;

            // Inserisce ogni piatto come dettaglio dell'ordine
            $det = $conn->prepare("INSERT INTO dettaglio_ordini (id_ordine, id_alimento, quantita, note) VALUES (?, ?, ?, ?)");
            foreach ($data['prodotti'] as $p) {
                if ($p['qta'] > 0) {
                    $note = $p['note'] ?? null;
                    $det->bind_param("iiis", $idOrdine, $p['id'], $p['qta'], $note);
                    if (!$det->execute()) throw new Exception("Errore inserimento piatto.");
                }
            }

            $conn->commit();
            json(['success' => true, 'message' => 'Ordine inviato in cucina.']);
        } catch (Exception $e) {
            $conn->rollback();
            json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // Mostra la cronologia ordini del tavolo (solo quelli della sessione corrente)
    case 'leggi_ordini_tavolo': {
        $idTavolo    = $_SESSION['id_tavolo'];
        $orarioLogin = orarioLoginTavolo($conn, $idTavolo);

        $stmt = $conn->prepare("SELECT o.id_ordine, o.stato, o.data_ora, d.quantita, a.nome_piatto, a.prezzo, d.note
            FROM ordini o
            JOIN dettaglio_ordini d ON o.id_ordine = d.id_ordine
            JOIN alimenti a ON d.id_alimento = a.id_alimento
            WHERE o.id_utente = ? AND o.data_ora >= ?
            ORDER BY o.data_ora DESC");

        $stmt->bind_param("is", $idTavolo, $orarioLogin);
        $stmt->execute();
        $result = $stmt->get_result();

        // Raggruppa le righe piatte in un array multi-dimensionale per ordine
        $ordini = [];
        while ($row = $result->fetch_assoc()) {
            $id = $row['id_ordine'];
            if (!isset($ordini[$id])) {
                $ordini[$id] = [
                    'id_ordine' => $id,
                    'stato'     => $row['stato'],
                    'ora'       => date('H:i', strtotime($row['data_ora'])),
                    'data'      => date('d/m/Y', strtotime($row['data_ora'])),
                    'piatti'    => [],
                    'totale'    => 0
                ];
            }
            $ordini[$id]['piatti'][] = [
                'nome'   => $row['nome_piatto'],
                'qta'    => $row['quantita'],
                'prezzo' => number_format($row['prezzo'], 2),
                'note'   => $row['note'] ?? ''
            ];
            $ordini[$id]['totale'] += $row['quantita'] * $row['prezzo'];
        }

        // Formatta i totali a 2 decimali
        foreach ($ordini as &$o) $o['totale'] = number_format($o['totale'], 2);

        json(array_values($ordini));
    }

    // Rimuove un'unità di un piatto dal carrello (o elimina la riga se qta == 1)
    case 'rimuovi_dal_carrello': {
        if (!isset($_SESSION['id_tavolo']))
            json(['success' => false, 'message' => 'Non autorizzato']);

        $idTavolo = intval($_SESSION['id_tavolo']);
        $idPiatto = intval($_POST['id_alimento'] ?? 0);

        if ($idPiatto <= 0) json(['success' => false, 'message' => 'Piatto non valido.']);

        $res = $conn->query("SELECT id_ordine FROM ordini WHERE id_utente = $idTavolo AND stato = 'in_attesa' LIMIT 1");

        if ($res->num_rows == 0) json(['success' => false, 'message' => 'Nessun carrello attivo.']);

        $idOrdine = $res->fetch_assoc()['id_ordine'];
        $qRes = $conn->query("SELECT quantita FROM dettaglio_ordini WHERE id_ordine = $idOrdine AND id_alimento = $idPiatto");

        if ($qRes->num_rows == 0) json(['success' => false, 'message' => 'Piatto non nel carrello.']);

        $qta = $qRes->fetch_assoc()['quantita'];

        // Se qta > 1 decrementa, altrimenti elimina la riga
        $sql = ($qta > 1)
            ? "UPDATE dettaglio_ordini SET quantita = quantita - 1 WHERE id_ordine = $idOrdine AND id_alimento = $idPiatto"
            : "DELETE FROM dettaglio_ordini WHERE id_ordine = $idOrdine AND id_alimento = $idPiatto";

        json($conn->query($sql) ? ['success' => true] : ['success' => false, 'message' => $conn->error]);
    }

    // Verifica se la sessione del tavolo è ancora valida (polling periodico dal JS)
    case 'verifica_sessione': {
        $idTavolo = intval($_SESSION['id_tavolo']);

        $stmt = $conn->prepare("SELECT stato FROM utenti WHERE id_utente = ?");
        $stmt->bind_param("i", $idTavolo);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        // Valida se il tavolo esiste ed è ancora in stato 'occupato' (il manager può forzare il logout cambiandolo a 'libero')
        json(['valida' => $row && $row['stato'] === 'occupato']);
    }

    default:
        die('Azione non valida.');
}
?>
