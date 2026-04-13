<?php
// Avvia o riprende la sessione utente per permettere la lettura delle variabili di sessione (es. l'ID del tavolo)
session_start();

// Include la connessione al database
require_once '../../include/conn.php';

// Include il modulo di controllo autorizzazioni
require_once '../../include/auth/check_permesso.php';

// Recupera il parametro d'azione dalla query string (es. ?action=aggiungi_al_carrello)
$action = $_GET['action'] ?? '';

// Verifica se l'utente corrente ha i fondamenti per chiamare un'api del "tavolo"
if (!verificaPermesso($conn, "tavolo/" . $action)) {
    // Rifiuta la richiesta HTTP assegnando il codice 403 Forbidden
    http_response_code(403);
    // Termina lo script ed espone un risultato JSON di sicurezza che spiega l'errore
    die(json_encode(['success' => false, 'error' => 'Non autorizzato', 'message' => 'Non autorizzato']));
}

// Analizza l'azione richiesta ed esegue il blocco corrispondente
switch ($action) {
    // Caso: Un cliente preme "Aggiungi" per mettere un piatto nel carrello temporaneo
    case 'aggiungi_al_carrello': {

        // Comunica al frontend che il dato ritornato sarà puro JSON
        header('Content-Type: application/json'); 

        // Recupera dalla sessione loggata l'ID del tavolo corrente
        $idTavolo = $_SESSION['id_tavolo'];
        // Estrae l'ID del piatto mandato tramite post ajax
        $idPiatto = intval($_POST['id_alimento'] ?? 0); 
        // Estrae la porzione desiderata di quel piatto
        $qta = intval($_POST['quantita'] ?? 1); 

        // Misura di sicurezza base per bloccare ID inesistenti o malformati
        if ($idPiatto <= 0) {
            echo json_encode(['success' => false, 'message' => 'Piatto non valido.']);
            exit; 
        }

        // Cerca se questo tavolo ha attualmente un ordine rimasto in bozza ('in_attesa' agisce da carrello virtuale in questa fase)
        $res = $conn->query("SELECT id_ordine FROM ordini WHERE id_utente = $idTavolo AND stato = 'in_attesa' LIMIT 1");

        // Controlla se è stato trovato almeno un record
        if ($res->num_rows > 0) {
            // Usa quell'ordine "bozza" esistente per continuare ad accumulare i piatti
            $idOrdine = $res->fetch_assoc()['id_ordine'];
        } else {
            // Altrimenti, ne crea uno completamente nuovo, impostandolo temporaneamente come 'in_attesa' a data/ora attuale
            $conn->query("INSERT INTO ordini (id_utente, stato, data_ora) VALUES ($idTavolo, 'in_attesa', NOW())");
            // E recupera subito l'identificativo AUTO_INCREMENT appena generato da poter usare dopo
            $idOrdine = $conn->insert_id;
        }

        // Adesso che abbiamo il "contenitore" (l'ordine) controlla se quel piatto specifico è stato GIÀ aggiunto
        $check = $conn->query("SELECT quantita FROM dettaglio_ordini WHERE id_ordine = $idOrdine AND id_alimento = $idPiatto");

        if ($check->num_rows > 0) {
            // Se esiste, limitiamoci ad INCREMENTARE il counter quantitativo, per evitare duplicati sgradevoli nella comanda (es: Carbonara x2 invece di due righe separate)
            $conn->query("UPDATE dettaglio_ordini SET quantita = quantita + $qta WHERE id_ordine = $idOrdine AND id_alimento = $idPiatto");
        } else {
            // Se è la prima volta che il cliente chiede questo piatto nell'ordine in corso, creiamo la riga
            $conn->query("INSERT INTO dettaglio_ordini (id_ordine, id_alimento, quantita) VALUES ($idOrdine, $idPiatto, $qta)");
        }

        // Operazione conclusa, notifichiamo il successo al browser per aggiornare i badge del carrello
        echo json_encode(['success' => true, 'message' => 'Aggiunto al carrello.']);

        break; 
    }

    // Caso: Il JS sta ricaricando il conto del menu laterale e vuole il contenuto corrente
    case 'get_carrello': {

        // Forziamo testata JSON
        header('Content-Type: application/json'); 

        // Se per qualche motivo siamo senza l'ID del tavolo sessione
        if (!isset($_SESSION['id_tavolo'])) {
            // Restituiamo un carrello vuoto per evitare javascript errors sul frontend
            echo json_encode([]); 
            exit; 
        }

        $idTavolo = intval($_SESSION['id_tavolo']); 

        // Mettiamo in join "ordini", "dettaglio_ordini" e "alimenti" per avere in un colpo solo il piatto, il nome testuale e il layout prezzi
        $result = $conn->query("SELECT d.id_alimento, d.quantita, a.nome_piatto, a.prezzo
            FROM dettaglio_ordini d
            JOIN ordini o ON d.id_ordine = o.id_ordine
            JOIN alimenti a ON d.id_alimento = a.id_alimento
            WHERE o.id_utente = $idTavolo AND o.stato = 'in_attesa'"); // "in_attesa" fa le veci del carrello non finalizzato

        // Se ci sono risultati mappa tutte le stringhe SQL associative, altrimenti fallback con lista zero-items
        echo json_encode($result ? $result->fetch_all(MYSQLI_ASSOC) : []);

        break; 
    }

    // Caso: Il tavolo ha verificato l'ordine e preme INVIA verso la cucina
    case 'invia_ordine': {

        header('Content-Type: application/json');

        // Stavolta i dati arrivano formattati esplicitamente dentro il "body" Javascript Fetch API (raw json string)
        $data = json_decode(file_get_contents('php://input'), true);

        // Disabilitiamo ordini a vuoto bloccandoli
        if (empty($data['prodotti'])) {
            echo json_encode(['success' => false, 'message' => 'Il carrello è vuoto.']);
            exit;
        }

        $idTavolo = $_SESSION['id_tavolo']; 

        // Iniziamo una TRANSACTION SQL.
        // Se un'operazione tra gli n-inserimenti fallisce, tutto viene annullato e la comanda non viene creata "a metà"
        $conn->begin_transaction();

        try {

            // Prepariamo l'inserimento formale sicuro dell'ordine generale
            $stmt = $conn->prepare("INSERT INTO ordini (id_utente, stato, data_ora) VALUES (?, 'in_attesa', NOW())");
            $stmt->bind_param("i", $idTavolo); 
            // Lanciamo e in caso di fallimento solleviamo volontariamente una Exception (andando nel Catch)
            if (!$stmt->execute()) 
                throw new Exception("Errore creazione ordine."); 

            // Preleviamo l'identity/id reale
            $idOrdine = $conn->insert_id; 

            // Prepariamo uno statement per i dettagli riutilizzandolo iterativamente nel loop
            $det = $conn->prepare("INSERT INTO dettaglio_ordini (id_ordine, id_alimento, quantita, note) VALUES (?, ?, ?, ?)");

            // Mappiamo i nodi provenienti dal Javascript (il carrello effettivo riletto e validato del cliente)
            foreach ($data['prodotti'] as $p) {

                // Filtro difensivo contro inserimenti negativi
                if ($p['qta'] > 0) {
                    $note = $p['note'] ?? null; 

                    // Associamo ID Ordine, l'id alimento iterativo, la qtà, e le note tipo "Ben cotto" o Null
                    $det->bind_param("iiis", $idOrdine, $p['id'], $p['qta'], $note);
                    if (!$det->execute()) 
                        throw new Exception("Errore inserimento piatto."); 
                } 
            }

            // Tutto è andato bene: concretizziamo tutte le query temporanee pendenti nel DB fisico definitivamente
            $conn->commit();

            // Sblocco confermato, notifica di sistema per sbloccare la modale success-loading e ripulire il localstorage
            echo json_encode(['success' => true, 'message' => 'Ordine inviato in cucina.']);
        } catch (Exception $e) { 

            // Errore generico! Annulliamo l'intera sequazione SQL pendente, riportando il DB allo stato pre-chiamata
            $conn->rollback();

            // Messaggio che potremmo renderizzare al cliente
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } 

        break; 
    }

    // Caso: Il cliente apre la modale "Rivedi Ordini Precedenti" per fare scopa con quello che ha già consumato
    case 'leggi_ordini_tavolo': {

        header('Content-Type: application/json'); 

        $idTavolo = $_SESSION['id_tavolo']; 

        // Sicurezza cross-client: non dobbiamo mai mostrare a questo cliente la cronologia pasti dei clienti passati che si erano seduti prima allo stesso tavolo!
        // Dobbiamo estrarre `sessione_inizio` che abbiamo resettato quando LUI ha effettuato il login PIN
        $stmtSess = $conn->prepare("SELECT sessione_inizio FROM utenti WHERE id_utente = ?"); 
        $stmtSess->bind_param("i", $idTavolo); 
        $stmtSess->execute();
        $resSess = $stmtSess->get_result()->fetch_assoc(); 
        $orarioLogin = $resSess['sessione_inizio'] ?? '1970-01-01 00:00:00'; 

        // Ora estrae la cronologia UNICAMENTE a partire dall'istante di check-in (scartando tutti quegli ordini che risalgono a prima di quel ">= datetime")
        $stmt = $conn->prepare("SELECT o.id_ordine, o.stato, o.data_ora, d.quantita, a.nome_piatto, a.prezzo, d.note
            FROM ordini o
            JOIN dettaglio_ordini d ON o.id_ordine = d.id_ordine
            JOIN alimenti a ON d.id_alimento = a.id_alimento
            WHERE o.id_utente = ? AND o.data_ora >= ?
            ORDER BY o.data_ora DESC"); // Dal più recente al più datato (comanda per comanda)

        $stmt->bind_param("is", $idTavolo, $orarioLogin); 
        $stmt->execute();
        $result = $stmt->get_result(); 

        // Raggruppamento multi-dimensionale analogo a quello in cucina_api.php
        $ordini = []; 

        while ($row = $result->fetch_assoc()) {
            $id = $row['id_ordine']; 
            if (!isset($ordini[$id])) { 
                $ordini[$id] = [ 
                    'id_ordine' => $id, 
                    'stato' => $row['stato'], // Per colorare il badge in "pronto" o "in lavorazione"
                    'ora' => date('H:i', strtotime($row['data_ora'])), 
                    'data' => date('d/m/Y', strtotime($row['data_ora'])), 
                    'piatti' => [], 
                    'totale' => 0  // Per sommare il costo della singola comanda parziale
                ]; 
            } 
            // Inserisce i dati testuali al fondo della coda array per ogni iterazione
            $ordini[$id]['piatti'][] = [ 
                'nome' => $row['nome_piatto'], 
                'qta' => $row['quantita'], 
                'prezzo' => number_format($row['prezzo'], 2), 
                'note' => $row['note'] ?? '' 
            ]; 

            // Aumenta l'integrità del sub-totale
            $ordini[$id]['totale'] += $row['quantita'] * $row['prezzo'];
        } 

        // Riformatta il cast double float per non fare impazzire i parse js europei del frontend (decimal trailing limit)
        foreach ($ordini as &$o)
            $o['totale'] = number_format($o['totale'], 2); 

        // Trans-form output e stampa JSON string per la fetch
        echo json_encode(array_values($ordini)); 

        break; 
    }

    // Caso: Tasto (-) premuto nel carrello per eliminare (o decrementare) quantità piatto
    case 'rimuovi_dal_carrello': {

        header('Content-Type: application/json'); 

        // Base checks
        if (!isset($_SESSION['id_tavolo'])) { 
            echo json_encode(['success' => false, 'message' => 'Non autorizzato']); 
            exit; 
        } 

        $idTavolo = intval($_SESSION['id_tavolo']); 
        $idPiatto = intval($_POST['id_alimento'] ?? 0); 

        if ($idPiatto <= 0) { 
            echo json_encode(['success' => false, 'message' => 'Piatto non valido.']); 
            exit; 
        } 

        // Individua quel famigerato "carrello corrente attivo"
        $res = $conn->query("SELECT id_ordine FROM ordini WHERE id_utente = $idTavolo AND stato = 'in_attesa' LIMIT 1"); 

        if ($res->num_rows > 0) { 
            $idOrdine = $res->fetch_assoc()['id_ordine']; 

            // Cerca fisicamente la pietanza richiesta e guarda a quanto è il contatore QTA in DB
            $qRes = $conn->query("SELECT quantita FROM dettaglio_ordini WHERE id_ordine = $idOrdine AND id_alimento = $idPiatto"); 

            if ($qRes->num_rows > 0) { 
                $qta = $qRes->fetch_assoc()['quantita']; 

                // L'operatore ternario se la QTA > 1 farà UPDATE (sottrazione), altrimenti se la quantità è 1, eliminare la RIGA fisicamente (DELETE)
                $sql = ($qta > 1)
                    ? "UPDATE dettaglio_ordini SET quantita = quantita - 1 WHERE id_ordine = $idOrdine AND id_alimento = $idPiatto"
                    : "DELETE FROM dettaglio_ordini WHERE id_ordine = $idOrdine AND id_alimento = $idPiatto";

                // Ritorniamo il flag status della query booleana
                echo json_encode($conn->query($sql) ? ['success' => true] : ['success' => false, 'message' => $conn->error]); 
            } else { 
                // Il piatto in teoria non esiste nel carrello (es: l'utente spinge e click-spam)
                echo json_encode(['success' => false, 'message' => 'Piatto non nel carrello.']); 
            } 
        } else { 
            echo json_encode(['success' => false, 'message' => 'Nessun carrello attivo.']); 
        } 

        break; 
    }

    // Caso: Un polling JS ciclico invia ping per verificare se la sessione non sia stata killata remotamente/scaduta
    case 'verifica_sessione': {

        header('Content-Type: application/json'); 

        $idTavolo = intval($_SESSION['id_tavolo']); 
        // Va alla caccia del token di sicurezza iniettato alla fase preliminare di login
        $tokenCookie = $_COOKIE['device_token_' . $idTavolo] ?? ''; 

        // Estrazione dello stato logico corrente di backend
        $stmt = $conn->prepare("SELECT stato, device_token FROM utenti WHERE id_utente = ?"); 
        $stmt->bind_param("i", $idTavolo); 
        $stmt->execute(); 
        $row = $stmt->get_result()->fetch_assoc(); 

        // Criteri operativi validità della sessione: 
        // 1. Tavolo Esiste! 2. Tavolo deve presentarsi in stato "occupato". 3. Token db != empty. 4. Token Db combacia con Token in transito cookie (mitigate session hijacking e session clash).
        $valida = $row && $row['stato'] === 'occupato' && !empty($row['device_token']) && $row['device_token'] === $tokenCookie;

        // true / false
        echo json_encode(['valida' => $valida]); 

        break; 
    }

    default: 
        die('Azione non valida.'); 
}
?>
