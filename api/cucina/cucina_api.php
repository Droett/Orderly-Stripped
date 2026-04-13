<?php
// Inizia o riprende la sessione PHP per accedere a variabili come $_SESSION['ruolo'] (necessario per l'autenticazione)
session_start();

// Include il file di connessione al database ($conn)
require_once '../../include/conn.php';

// Include il file che contiene la funzione verificaPermesso()
require_once '../../include/auth/check_permesso.php';

// Recupera l'azione richiesta passata via GET (es: ?action=leggi_ordini_cucina)
// Se non è presente, assegna una stringa vuota di default
$action = $_GET['action'] ?? '';

// Passa la connessione e la stringa "cucina/azione" alla funzione di verifica dei permessi
// Se l'utente loggato non ha i permessi (es. non è cuoco o manager), il blocco if viene eseguito
if (!verificaPermesso($conn, "cucina/" . $action)) {
    // Imposta lo status code HTTP a 403 (Forbidden)
    http_response_code(403); 
    // Termina l'esecuzione dello script ritornando un JSON con messaggio di errore di autorizzazione fallita
    die(json_encode(['success' => false, 'error' => 'Non autorizzato', 'message' => 'Non autorizzato'])); 
} 

// Esegue il codice corrispondente in base al valore della variabile $action
switch ($action) { 

    // Caso 1: L'utente (cuoco) sta tentando di cambiare lo stato di un ordine (es. da 'in_attesa' a 'in_preparazione')
    case 'cambia_stato_ordine': { 

        // Specifica che la risposta che stiamo per mandare indietro sarà in formato JSON
        header('Content-Type: application/json'); 

        // Legge il corpo della richiesta HTTP in formato raw (spesso usato quando si inviano dati via Fetch API o Axios) e lo decodifica in array associativo
        $input = json_decode(file_get_contents('php://input'), true);
        // Estrae l'ID dell'ordine dal payload JSON (se mancante, imposta null)
        $id_ordine = $input['id_ordine'] ?? null; 
        // Estrae il nuovo stato richiesto dal payload JSON
        $nuovo_stato = $input['nuovo_stato'] ?? null; 

        // Definisce un array (una "lista bianca") contenente tutti gli stati di ordine considerati sicuri o accettabili dalla logica di business
        $stati_validi = ['in_attesa', 'in_preparazione', 'pronto'];

        // Se manca l'ID dell'ordine, oppure il nuovo stato non fa parte degli stati validi consentiti:
        if (!$id_ordine || !in_array($nuovo_stato, $stati_validi)) {
            // Ritorna un JSON indicando l'errore per dati non validi
            echo json_encode(['success' => false, 'message' => 'Dati non validi.']); 
            // Ferma l'esecuzione (sicurezza) per prevenire iniezioni di stati malevoli nel DB
            exit; 
        } 

        // Prepara l'istruzione SQL per aggiornare lo stato di quello specifico ordine nella tabella ordini (evita SQL Injection)
        $stmt = $conn->prepare("UPDATE ordini SET stato = ? WHERE id_ordine = ?");
        // Assegna in modo sicuro ("bind") il nuovo stato (Stringa -> "s") e l'id dell'ordine (Integer -> "i") nei punti interrogativi
        $stmt->bind_param("si", $nuovo_stato, $id_ordine); 

        // Esegue la query bindata. Se va a buon fine, ritorna un JSON {success: true}. Se fallisce, ritorna {success: false, message: ...} con l'errore del server SQL
        echo json_encode($stmt->execute() ? ['success' => true] : ['success' => false, 'message' => 'Errore: ' . $conn->error]); 

        // Esce dal blocco switch per non eseguire altro codice accidentale
        break; 
    }

    // Caso 2: Ottiene la lista degli ordini attivi da mostrare nel cruscotto della cucina (la dashboard in tempo reale)
    case 'leggi_ordini_cucina': { 

        // Definisce l'intestazione HTTP della risposta informando che manderemo dati in formato JSON
        header('Content-Type: application/json'); 

        // Si costruisce la query SQL complessa per aggregare tutte le informazioni sparse nelle varie tabelle
        // - o.id_ordine, o.id_utente, o.stato, o.data_ora: Info basiche dell'ordine principale
        // - t.username: Il nome del tavolo dal quale proviene l'ordine (uniamo la tabella utenti come "t")
        // - d.quantita, d.note: I dettagli che compongono l'ordine
        // - a.nome_piatto: Il nome effettivo dell'alimento basato sul suo ID (uniamo la tabella alimenti come "a")
        $sql = "SELECT o.id_ordine, o.id_utente, t.username, o.stato, o.data_ora,
                    d.quantita, a.nome_piatto, d.note
                FROM ordini o
                LEFT JOIN utenti t ON o.id_utente = t.id_utente -- LEFT JOIN garantisce che l'ordine venga ritornato anche se per caso il tavolo-utente venisse eliminato logicamente
                JOIN dettaglio_ordini d ON o.id_ordine = d.id_ordine
                JOIN alimenti a ON d.id_alimento = a.id_alimento
                WHERE o.stato IN ('in_attesa', 'in_preparazione') -- Carichiamo SOLO gli ordini lavorabili. Gli ordini 'pronto' non servono più in cucina.
                ORDER BY o.data_ora ASC"; // Ordiniamo per data/ora crescente (FIFO: il primo a entrare deve essere il primo mostrato sulla lista)

        // Esegue la query appena scritta contro il DataBase
        $res = $conn->query($sql); 

        // Se per qualche motivo c'è stato un problema col DB (es: errori di sintassi nella query)
        if (!$res) { 
            // Mostra l'errore generato dal motore MySQL in una stringa JSON rintracciabile nel frontend
            echo json_encode(["error" => $conn->error]); 
            // Terminazione di emergenza dello script per evitare danni maggiori o rendering corrotto
            exit; 
        } 

        // Crea un array vuoto associativo dove verranno organizzati e "raggruppati" tutti gli ordini per ID
        $ordini = []; 

        // Avvia il ciclo While per leggere uno a uno tutti i risultati piatti/singole righe prodotti dalla query JOINata sql
        while ($row = $res->fetch_assoc()) { 
            // Memorizza l'identificavo univoco dell'ordine corrente ricavato dal database
            $id = $row['id_ordine']; 

            // Se questa è la PRIMA riga processata dal while contenente questo "$id" ordine (es. il primo piatto di un ordine misto)
            if (!isset($ordini[$id])) { 
                // Allora inizializza la "testata" principale di quell'ordine nell'indice $id dell'array, preparandole uno spazio "piatti" libero
                $ordini[$id] = [ 
                    'id_ordine' => $id, // ID utile al frontend
                    // Se username è pieno metti quello, atrimenti usa un nome "fallback" con "Tavolo {ID}"
                    'tavolo' => !empty($row['username']) ? $row['username'] : "Tavolo " . $row['id_utente'], 
                    'stato' => $row['stato'], // Stato attuale dell'ordine per colorare i badge in cucina
                    'ora' => date('H:i', strtotime($row['data_ora'])), // Formattazione pulita dell'ora, nascondendo la data
                    'piatti' => [] // Crea l'array (lista) inizialmente vuoto dedicato ad accogliere tutti i piatti appartenenti a QUESTA precisa ordinazione
                ]; 
            } 

            // Usa l'operatore ( push array "[]" ) per inserire i dati della pietanza corrente letta dalla JOIN
            // come un nuovo elemento all'interno del figlio "piatti" dell'elemento ordine genitore
            $ordini[$id]['piatti'][] = ['nome' => $row['nome_piatto'], 'qta' => $row['quantita'], 'note' => $row['note'] ?? '']; 
        } 

        // Siccome lavoriamo con Javascript che si aspetta array standard (zero-indexed indicizzati 0, 1, 2) e non Dizionari Associativi (10, 52, 93) basato sugli $id_ordine
        // Abbiamo bisogno di array_values() che spiana e converte i nostri ID in una sequenza numerica standard da [ { }, { } ]. Trasformazione stampata via `json_encode`
        echo json_encode(array_values($ordini)); 

        // Fermiamo qui l'elaborazione del caso
        break; 
    }

    // Se un bot/utente invia un'azione HTTP_GET sconosciuta o malevola non prevista dal nostro switch di route
    default: 
        // Stampiamo una stringa di errore secco e facciamo arrestare ogni esecuzione php successiva per policy stringenti di sicurezza Backend
        die('Azione non valida.'); 
}
?>
