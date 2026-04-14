<?php
// ============================================================
// tavolo_api.php — API del Tavolo
// ============================================================
// Questo file gestisce tutte le azioni che può compiere un utente TAVOLO:
//   - Aggiungere/rimuovere piatti dal carrello
//   - Visualizzare il carrello
//   - Inviare l'ordine in cucina
//   - Visualizzare lo storico ordini
//   - Verificare che la sessione sia ancora attiva
//
// Come funziona:
//   La pagina viene richiamata con un URL del tipo:
//   tavolo_api.php?action=get_carrello
//   Il parametro "action" decide quale blocco di codice eseguire.
// ============================================================

// Avvia la sessione PHP così possiamo leggere chi ha fatto il login (es. $_SESSION['id_tavolo'])
session_start();

// Carica il file per la connessione al database — fornisce la variabile $conn
require_once '../../include/conn.php';

// Carica la funzione di check permessi — blocca utenti non autorizzati
require_once '../../include/auth/check_permesso.php';

// Legge l'"action" dalla query string dell'URL (es. ?action=get_carrello)
// Se non c'è nessuna action assegnata, va in default in stringa vuota
$action = $_GET['action'] ?? '';

// --- CONTROLLO SICUREZZA ---
// Assicura che solo un utente di tipo TAVOLO che abbia fatto il login possa accedere.
// Se il controllo fallisce, manda indietro l'errore 403 "Forbidden" e si ferma.
if (!verificaPermesso($conn, "tavolo/" . $action)) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Non autorizzato']));
}


// ============================================================
// FUNZIONI DI SUPPORTO (HELPER)
// Piccole funzioni riutilizzabili per evitare codice ripetitivo.
// ============================================================

/**
 * Manda una risposta JSON al browser e ferma lo script.
 * Usiamo questa funzione invece di "echo" perché così viene impostato il corretto
 * header Content-Type, in modo che il browser capisca che si tratta di dati JSON.
 */
function json($data) {
    header('Content-Type: application/json');
    die(json_encode($data));
}

/**
 * Trova il carrello attivo (un ordine in stato 'in_attesa') per un certo tavolo.
 * Se non c'è ancora, ne crea uno e ne ritorna l'ID.
 *
 * Immaginalo come: "C'è un carrello aperto per questo tavolo? Se non c'è, aprilo."
 */
function trovaOCreaCarrello($conn, $idTavolo) {
    // Cerca l'ordine attuale 'in attesa' per questo tavolo
    $res = $conn->query("SELECT id_ordine FROM ordini WHERE id_utente = $idTavolo AND stato = 'in_attesa' LIMIT 1");

    // Se ne troviamo uno, ritorna direttamente l'ID
    if ($res->num_rows > 0) return $res->fetch_assoc()['id_ordine'];

    // Altrimenti, crea un ordine nuovo di zecca e ritorna il suo ID
    $conn->query("INSERT INTO ordini (id_utente, stato, data_ora) VALUES ($idTavolo, 'in_attesa', NOW())");
    return $conn->insert_id;
}

/**
 * Ritorna l'orario in cui il tavolo ha fatto l'accesso (l'inizio della loro sessione).
 * Viene utilizzata per mostrare GLI ordini effettuati DURANTE la sessione corrente,
 * non quelli di eventuali sessioni precedenti effettuate sullo stesso tavolo.
 */
function orarioLoginTavolo($conn, $idTavolo) {
    $stmt = $conn->prepare("SELECT sessione_inizio FROM utenti WHERE id_utente = ?");
    $stmt->bind_param("i", $idTavolo);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    // Ritorna l'orario di login, oppure una data passata come fallback
    return $row['sessione_inizio'] ?? '1970-01-01 00:00:00';
}


// ============================================================
// ROUTER — decide che fare in base al parametro "action"
// ============================================================
switch ($action) {

    // --------------------------------------------------------
    // ACTION: aggiungi_al_carrello
    // Aggiunge un piatto nel carrello, o ne incrementa la quantità se è
    // già presente.
    // --------------------------------------------------------
    case 'aggiungi_al_carrello': {
        $idTavolo = $_SESSION['id_tavolo'];           // Chi sta ordinando
        $idPiatto = intval($_POST['id_alimento'] ?? 0); // Quale piatto (dal form)
        $qta      = intval($_POST['quantita'] ?? 1);    // Quanto (valore default: 1)

        // Validazione: l'ID piatto dev'essere un numero positivo
        if ($idPiatto <= 0) json(['success' => false, 'message' => 'Piatto non valido.']);

        // Ottiene (o crea) il carrello attivo per questo tavolo
        $idOrdine = trovaOCreaCarrello($conn, $idTavolo);

        // Controlla se questo piatto si trova già nel carrello
        $check = $conn->query("SELECT quantita FROM dettaglio_ordini WHERE id_ordine = $idOrdine AND id_alimento = $idPiatto");

        if ($check->num_rows > 0) {
            // Piatto già nel carrello → aumenta semplicemente la quantità
            $conn->query("UPDATE dettaglio_ordini SET quantita = quantita + $qta WHERE id_ordine = $idOrdine AND id_alimento = $idPiatto");
        } else {
            // Piatto non ancora nel carrello → aggiungi nuova riga
            $conn->query("INSERT INTO dettaglio_ordini (id_ordine, id_alimento, quantita) VALUES ($idOrdine, $idPiatto, $qta)");
        }

        json(['success' => true, 'message' => 'Aggiunto al carrello.']);
    }

    // --------------------------------------------------------
    // ACTION: get_carrello
    // Restituisce i contenuti attuali del carrello del tavolo.
    // Il JavaScript nella pagina lo chiama per mostrare il carrello.
    // --------------------------------------------------------
    case 'get_carrello': {
        // Se per qualche motivo non c'è una sessione, restituisci un array vuoto
        if (!isset($_SESSION['id_tavolo'])) json([]);

        $idTavolo = intval($_SESSION['id_tavolo']);

        // Prendi tutti gli elementi nel carrello unendo le tabelle:
        //   dettaglio_ordini (righe carrello) → ordini (l'ordine) → alimenti (info piatto)
        $result = $conn->query("SELECT d.id_alimento, d.quantita, a.nome_piatto, a.prezzo
            FROM dettaglio_ordini d
            JOIN ordini o ON d.id_ordine = o.id_ordine
            JOIN alimenti a ON d.id_alimento = a.id_alimento
            WHERE o.id_utente = $idTavolo AND o.stato = 'in_attesa'");

        // Restituisci il risultato come array JSON (o array vuoto se nulla viene trovato)
        json($result ? $result->fetch_all(MYSQLI_ASSOC) : []);
    }

    // --------------------------------------------------------
    // ACTION: invia_ordine
    // Finalizza e invia l'ordine in cucina.
    // Usa una "transaction" per assicurarsi che vengano salvati
    // o TUTTI i piatti o NESSUNO — no ordini parziali.
    // --------------------------------------------------------
    case 'invia_ordine': {
        // Leggi il corpo JSON inviato dal browser (tramite fetch/AJAX)
        $data = json_decode(file_get_contents('php://input'), true);

        // Assicurati che l'ordine in realtà contenga dei prodotti
        if (empty($data['prodotti']))
            json(['success' => false, 'message' => 'Il carrello è vuoto.']);

        $idTavolo = $_SESSION['id_tavolo'];

        // Inizia una transazione per il DB — se qualcosa fallisce, si può fare un roll back completo
        $conn->begin_transaction();

        try {
            // Step 1: Crea il record principale dell'ordine
            $stmt = $conn->prepare("INSERT INTO ordini (id_utente, stato, data_ora) VALUES (?, 'in_attesa', NOW())");
            $stmt->bind_param("i", $idTavolo);
            if (!$stmt->execute()) throw new Exception("Errore creazione ordine.");

            $idOrdine = $conn->insert_id; // Ottieni l'ID per l'ordine appena creato

            // Step 2: Cicla per ogni piatto e salvalo come riga dettaglio in array
            $det = $conn->prepare("INSERT INTO dettaglio_ordini (id_ordine, id_alimento, quantita, note) VALUES (?, ?, ?, ?)");
            foreach ($data['prodotti'] as $p) {
                if ($p['qta'] > 0) {
                    $note = $p['note'] ?? null; // Note opzionali (es. "niente cipolla")
                    $det->bind_param("iiis", $idOrdine, $p['id'], $p['qta'], $note);
                    if (!$det->execute()) throw new Exception("Errore inserimento piatto.");
                }
            }

            // Se tutto è andato bene, conferma (salva) la transazione
            $conn->commit();
            json(['success' => true, 'message' => 'Ordine inviato in cucina.']);

        } catch (Exception $e) {
            // Qualcosa è andato storto — annulla i cambi fatti in modo da tener pulito il DB
            $conn->rollback();
            json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // --------------------------------------------------------
    // ACTION: leggi_ordini_tavolo
    // Ritorna le statistiche d'ordine per l'attuale sessione del tavolo
    // Mostra solo gli ordini inviati dopo che il tavolo ha effettuato il login.
    // --------------------------------------------------------
    case 'leggi_ordini_tavolo': {
        $idTavolo    = $_SESSION['id_tavolo'];
        $orarioLogin = orarioLoginTavolo($conn, $idTavolo); // Mostra solo ordini di quest'ultima sessione

        // Prendi tutti gli ordini ed i rispettivi piatti in un'unica query SQL tramite i JOINs
        $stmt = $conn->prepare("SELECT o.id_ordine, o.stato, o.data_ora, d.quantita, a.nome_piatto, a.prezzo, d.note
            FROM ordini o
            JOIN dettaglio_ordini d ON o.id_ordine = d.id_ordine
            JOIN alimenti a ON d.id_alimento = a.id_alimento
            WHERE o.id_utente = ? AND o.data_ora >= ?
            ORDER BY o.data_ora DESC");

        $stmt->bind_param("is", $idTavolo, $orarioLogin);
        $stmt->execute();
        $result = $stmt->get_result();

        // La query SQL restituisce righe "piatte" (una riga per piatto).
        // Occorre raggrupparle in base all'ID dell'ordine, usando una struttura nidificata del tipo:
        //   Ordine #5 → [Pasta, Pizza]
        //   Ordine #6 → [Insalata]
        $ordini = [];
        while ($row = $result->fetch_assoc()) {
            $id = $row['id_ordine'];

            // È la prima volta che si incontra questo ID dell'ordine? Imposta l'intestazione dell'ordine
            if (!isset($ordini[$id])) {
                $ordini[$id] = [
                    'id_ordine' => $id,
                    'stato'     => $row['stato'],
                    'ora'       => date('H:i', strtotime($row['data_ora'])),   // es. "14:30"
                    'data'      => date('d/m/Y', strtotime($row['data_ora'])), // es. "13/04/2026"
                    'piatti'    => [],
                    'totale'    => 0
                ];
            }

            // Aggiungi questo piatto alla lista dei piatti dell'ordine
            $ordini[$id]['piatti'][] = [
                'nome'   => $row['nome_piatto'],
                'qta'    => $row['quantita'],
                'prezzo' => number_format($row['prezzo'], 2),
                'note'   => $row['note'] ?? ''
            ];

            // Aggiungi al totale cumulativo di questo ordine
            $ordini[$id]['totale'] += $row['quantita'] * $row['prezzo'];
        }

        // Formatta tutti i totali con 2 posizioni decimali (es. 12.50)
        foreach ($ordini as &$o) $o['totale'] = number_format($o['totale'], 2);

        // array_values() resetta le chiavi a 0, 1, 2... (necessario per un array JSON valido)
        json(array_values($ordini));
    }

    // --------------------------------------------------------
    // ACTION: rimuovi_dal_carrello
    // Rimuove un'unità di un piatto dal carrello.
    // Se la quantità arriva a 0, il piatto è eliminato dal carrello.
    // --------------------------------------------------------
    case 'rimuovi_dal_carrello': {
        if (!isset($_SESSION['id_tavolo']))
            json(['success' => false, 'message' => 'Non autorizzato']);

        $idTavolo = intval($_SESSION['id_tavolo']);
        $idPiatto = intval($_POST['id_alimento'] ?? 0);

        if ($idPiatto <= 0) json(['success' => false, 'message' => 'Piatto non valido.']);

        // Trova il carrello attivo per questo tavolo
        $res = $conn->query("SELECT id_ordine FROM ordini WHERE id_utente = $idTavolo AND stato = 'in_attesa' LIMIT 1");

        if ($res->num_rows == 0) json(['success' => false, 'message' => 'Nessun carrello attivo.']);

        $idOrdine = $res->fetch_assoc()['id_ordine'];

        // Trova l'attuale quantità di questo piatto all'interno del carrello
        $qRes = $conn->query("SELECT quantita FROM dettaglio_ordini WHERE id_ordine = $idOrdine AND id_alimento = $idPiatto");

        if ($qRes->num_rows == 0) json(['success' => false, 'message' => 'Piatto non nel carrello.']);

        $qta = $qRes->fetch_assoc()['quantita'];

        // Se quantità > 1: diminuisci di 1. Se quantità == 1: elimina direttamente la riga.
        $sql = ($qta > 1)
            ? "UPDATE dettaglio_ordini SET quantita = quantita - 1 WHERE id_ordine = $idOrdine AND id_alimento = $idPiatto"
            : "DELETE FROM dettaglio_ordini WHERE id_ordine = $idOrdine AND id_alimento = $idPiatto";

        json($conn->query($sql) ? ['success' => true] : ['success' => false, 'message' => $conn->error]);
    }

    // --------------------------------------------------------
    // ACTION: verifica_sessione
    // Controlla se la sessione del tavolo è ancora valida.
    // Il JavaScript interroga questo endpoint ogni pochi secondi.
    // Se il manager riavvia il tavolo (reset), lo status cambia in
    // 'libero' e questo endpoint tornerà false — disconnettendo il tavolo.
    // --------------------------------------------------------
    case 'verifica_sessione': {
        $idTavolo = intval($_SESSION['id_tavolo']);

        $stmt = $conn->prepare("SELECT stato FROM utenti WHERE id_utente = ?");
        $stmt->bind_param("i", $idTavolo);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        // La sessione è valida solo se il tavolo esiste E il suo status è in 'occupato'
        json(['valida' => $row && $row['stato'] === 'occupato']);
    }

    // --------------------------------------------------------
    // DEFAULT: Azione sconosciuta
    // Se qualcuno chiama questo script con un'azione non riconosciuta, fermalo.
    // --------------------------------------------------------
    default:
        die('Azione non valida.');
}
?>
