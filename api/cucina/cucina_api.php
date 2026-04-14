<?php
// ============================================================
// cucina_api.php — Server API per la Cucina
// ============================================================
// Questo file gestisce le due funzionalità necessarie allo schermo della CUCINA:
//   1. Leggere tutti gli ordini attivi da mostrare sullo schermo
//   2. Aggiornare lo stato di un ordine (es. "in preparazione" → "pronto")
//
// Come funziona:
//   Viene chiamato con un URL come: cucina_api.php?action=leggi_ordini_cucina
//   Il valore di "action" decide quale blocco di codice viene eseguito.
// ============================================================

// Avvia la sessione PHP per poter verificare chi ha effettuato l'accesso
session_start();

// Carica il file di connessione al database — fornisce la variabile $conn
require_once '../../include/conn.php';

// Carica il controllo dei permessi — blocca chi non è del personale di cucina o manager
require_once '../../include/auth/check_permesso.php';

// Legge "action" dalla query string dell'URL
// Esempio: ?action=leggi_ordini_cucina → $action = 'leggi_ordini_cucina'
$action = $_GET['action'] ?? '';

// --- CONTROLLO SICUREZZA ---
// Se l'utente connesso non ha i permessi, restituisce 403 Forbidden e si ferma.
if (!verificaPermesso($conn, "cucina/" . $action)) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Non autorizzato', 'message' => 'Non autorizzato']));
}


// ============================================================
// ROUTER — esegue il blocco corretto in base al valore di "action"
// ============================================================
switch ($action) {

    // --------------------------------------------------------
    // ACTION: cambia_stato_ordine
    // Aggiorna lo stato di un ordine specifico.
    //
    // La cucina può spostare un ordine attraverso questi stati:
    //   in_attesa → in_preparazione → pronto
    //
    // Il browser invia i dati JSON nel corpo della richiesta (tramite fetch).
    // --------------------------------------------------------
    case 'cambia_stato_ordine': {
        // Dichiara al browser che stiamo restituendo JSON
        header('Content-Type: application/json');

        // Legge il corpo JSON inviato dal browser e lo decodifica in un array PHP
        // (Ecco come il fetch() JavaScript moderno invia i dati)
        $input = json_decode(file_get_contents('php://input'), true);

        $id_ordine   = $input['id_ordine'] ?? null;    // Quale ordine aggiornare
        $nuovo_stato = $input['nuovo_stato'] ?? null;  // Il nuovo stato da impostare

        // Solo questi tre stati sono permessi — tutto il resto viene rifiutato
        $stati_validi = ['in_attesa', 'in_preparazione', 'pronto'];

        // Validazione: abbiamo bisogno di un ID ordine e un nuovo stato validi
        if (!$id_ordine || !in_array($nuovo_stato, $stati_validi)) {
            echo json_encode(['success' => false, 'message' => 'Dati non validi.']);
            exit;
        }

        // Aggiorna lo stato dell'ordine nel database
        // Usiamo un "prepared statement" per inserire in modo sicuro i valori
        // (questo previene attacchi di SQL Injection)
        $stmt = $conn->prepare("UPDATE ordini SET stato = ? WHERE id_ordine = ?");
        $stmt->bind_param("si", $nuovo_stato, $id_ordine);  // "s" = stringa, "i" = intero

        // Esegue la query e restituisce successo o fallimento
        echo json_encode($stmt->execute()
            ? ['success' => true]
            : ['success' => false, 'message' => 'Errore: ' . $conn->error]
        );

        break;
    }

    // --------------------------------------------------------
    // ACTION: leggi_ordini_cucina
    // Restituisce tutti gli ordini su cui la cucina deve ancora lavorare.
    // Mostra solo gli ordini 'in_attesa' e 'in_preparazione' —
    // gli ordini completati ('pronto') sono esclusi.
    //
    // Il risultato è una struttura nidificata raggruppata per ordine:
    //   Ordine #5 (Tavolo A) → [Pasta x2, Pizza x1]
    //   Ordine #6 (Tavolo B) → [Insalata x1]
    // --------------------------------------------------------
    case 'leggi_ordini_cucina': {
        header('Content-Type: application/json');

        // Questa query SQL unisce tre tabelle per prendere le INFO necessarie:
        //   ordini           → l'ordine principale (ID, stato, orario)
        //   utenti (come "t")→ il tavolo che ha effettuato l'ordine (username)
        //   dettaglio_ordini → i piatti presi per l'ordine
        //   alimenti         → il nome del piatto
        //
        // Usiamo LEFT JOIN per "utenti" in modo che l'ordine appaia anche se
        // il tavolo è stato accidentalmente eliminato dal database.
        $sql = "
            SELECT
                o.id_ordine,
                o.id_utente,
                t.username,
                o.stato,
                o.data_ora,
                d.quantita,
                a.nome_piatto,
                d.note
            FROM ordini o
            LEFT JOIN utenti t ON o.id_utente = t.id_utente
            JOIN dettaglio_ordini d ON o.id_ordine = d.id_ordine
            JOIN alimenti a ON d.id_alimento = a.id_alimento
            WHERE o.stato IN ('in_attesa', 'in_preparazione')
            ORDER BY o.data_ora ASC
        ";
        // ORDER BY data_ora ASC = prima gli ordini più vecchi (FIFO — first in, first out)

        $res = $conn->query($sql);

        // Se la query fallisce (es. errore di sintassi), restituisce l'errore del DB
        if (!$res) {
            echo json_encode(["error" => $conn->error]);
            exit;
        }

        // La query restituisce righe piatte — una riga per piatto per ogni ordine.
        // Dobbiamo raggrupparle in una struttura nidificata basata sull'ID ordine.
        //
        // Esempio: il DB ci fornisce:
        //   Riga 1: ordine 5, "Pasta", qtà 2
        //   Riga 2: ordine 5, "Pizza", qtà 1
        //   Riga 3: ordine 6, "Insalata", qtà 1
        //
        // Noi vogliamo ottenere:
        //   [ { id: 5, piatti: [{Pasta,2},{Pizza,1}] }, { id: 6, piatti:[{Insalata,1}] } ]
        $ordini = [];

        while ($row = $res->fetch_assoc()) {
            $id = $row['id_ordine'];

            // È la prima volta che troviamo questo ID ordine? Inizializza l'elemento.
            if (!isset($ordini[$id])) {
                $ordini[$id] = [
                    'id_ordine' => $id,
                    // Usa l'username del tavolo, o torna a "Tavolo {ID}" se assente
                    'tavolo'    => !empty($row['username']) ? $row['username'] : "Tavolo " . $row['id_utente'],
                    'stato'     => $row['stato'],
                    'ora'       => date('H:i', strtotime($row['data_ora'])), // es. "14:30"
                    'piatti'    => [] // Inizia con una lista di piatti vuota
                ];
            }

            // Aggiunge questo piatto alla lista dei piatti dell'ordine
            $ordini[$id]['piatti'][] = [
                'nome' => $row['nome_piatto'],
                'qta'  => $row['quantita'],
                'note' => $row['note'] ?? '' // Nota opzionale (es. "senza sale")
            ];
        }

        // array_values() converte le chiavi associative (gli ID ordine) in
        // un array numerico semplice [0, 1, 2, ...] — necessario per ottenere un JSON array valido.
        echo json_encode(array_values($ordini));

        break;
    }

    // --------------------------------------------------------
    // DEFAULT: Azione sconosciuta
    // Se qualcuno chiama questo file con un'azione non prevista, si ferma.
    // --------------------------------------------------------
    default:
        die('Azione non valida.');
}
?>
