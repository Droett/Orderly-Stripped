<?php
// ============================================================
// manager_api.php — API del Manager
// ============================================================
// Questo file gestisce tutte le azioni che il MANAGER può eseguire:
//   - Aggiungere / modificare / eliminare categorie del menu
//   - Aggiungere / modificare / eliminare piatti del menu (piatti)
//   - Aggiungere / modificare / eliminare tavoli (tavoli)
//   - Cambiare lo stato di un tavolo (libero, occupato, riservato)
//   - Terminare la sessione di un tavolo (log out forzato)
//   - Ottenere una lista di tutti i tavoli
//
// Come funziona:
//   La pagina è chiamata con un URL del tipo:
//   manager_api.php?action=get_tavoli
//   Il parametro "action" decide quale codice verrà eseguito di seguito.
// ============================================================

// Avvia la sessione PHP così da verificare chi ha effettuato l'accesso
session_start();

// Carica la connessione al database — fornisce la variabile $conn
require_once '../../include/conn.php';

// Carica il controllo dei permessi — blocca i non manager
require_once '../../include/auth/check_permesso.php';

// Legge il valore di "action" dall'URL (es. ?action=aggiungi_piatto)
$action = $_GET['action'] ?? '';

// --- CONTROLLO SICUREZZA ---
// Se l'utente connesso non è un manager, ritorna 403 Forbidden e si ferma.
if (!verificaPermesso($conn, "manager/" . $action)) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Non autorizzato']));
}


// ============================================================
// FUNZIONI DI SUPPORTO (HELPER)
// ============================================================

/**
 * Ferma lo script se la richiesta non è una richiesta POST.
 * L'invio dei form utilizza POST. Le visite dirette tramite URL utilizzano GET.
 * Lo usiamo per impedire agli utenti di eseguire azioni dei form
 * visitando semplicemente un URL nel loro browser.
 */
function requirePost() {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") die("Accesso negato.");
}

/**
 * Invia una risposta JSON e ferma lo script.
 * Usato ovunque ci sia bisogno di far tornare dati al frontend JavaScript.
 */
function json($data) {
    header('Content-Type: application/json');
    die(json_encode($data));
}

/**
 * Reindirizza il browser indietro alla dashboard del manager.
 * $msg  — una breve parola chiave mostrata nell'URL (es. "success")
 * $hash — ancora opzionale per scorrere a una specifica sezione (es. "#menu")
 */
function redirect($msg, $hash = '') {
    header("Location: ../../dashboards/manager.php?msg=$msg" . ($hash ? "#$hash" : ''));
    exit;
}

/**
 * Legge un file d'immagine caricato e ne ritorna il suo contenuto binario grezzo (raw).
 * Ritorna null se nessun file è stato caricato o se il caricamento fallisce.
 * I dati binari sono salvati direttamente nel database come BLOB.
 */
function leggiImmagine() {
    if (isset($_FILES["immagine"]) && $_FILES["immagine"]["error"] === 0)
        return file_get_contents($_FILES["immagine"]["tmp_name"]);
    return null;
}

/**
 * Legge le opzioni degli allergeni (checkbox) dal form.
 * Ritorna un'unica stringa separata da virgola (es. "Glutine, Latte, Uova").
 * $sep — il carattere separatore fra gli allergeni (default: ',')
 */
function leggiAllergeni($sep = ',') {
    return empty($_POST['allergeni']) ? "" : implode($sep, $_POST['allergeni']);
}


// ============================================================
// ROUTER — esegue il blocco corretto in base al valore "action"
// ============================================================
switch ($action) {

    // --------------------------------------------------------
    // ACTION: aggiungi_categoria
    // Crea una nuova categoria del menu (es. "Antipasti", "Dessert").
    // --------------------------------------------------------
    case 'aggiungi_categoria': {
        requirePost(); // Consente solo richieste POST

        $nome  = trim($_POST['nome_categoria'] ?? ''); // Nome categoria dal form
        $idMenu = intval($_POST['id_menu'] ?? 0);       // A quale menu appartiene

        // Una categoria deve avere un nome
        if (empty($nome)) die("La categoria deve avere un nome!");

        // Inserisce la nuova categoria nel database
        $stmt = $conn->prepare("INSERT INTO categorie (nome_categoria, id_menu) VALUES (?, ?)");
        $stmt->bind_param("si", $nome, $idMenu);

        // Reindirizza alla dashboard con un messaggio di successo o errore
        $stmt->execute() ? redirect('cat_success') : die("Errore: " . $stmt->error);
        break;
    }

    // --------------------------------------------------------
    // ACTION: aggiungi_piatto
    // Aggiunge un piatto nuovo nel menu.
    // --------------------------------------------------------
    case 'aggiungi_piatto': {
        requirePost();

        $nome      = $_POST['nome_piatto'] ?? '';         // Nome piatto
        $desc      = $_POST['descrizione'] ?? '';          // Descrizione / ingredienti
        $prezzo    = floatval($_POST['prezzo'] ?? 0);      // Prezzo (come numero decimale)
        $idCat     = intval($_POST['id_categoria'] ?? 0);  // A quale categoria appartiene
        $allergeni = leggiAllergeni();                      // Allergeni (checkbox)
        $img       = leggiImmagine();                       // Foto caricata (dati binari)

        // Inserisce il piatto nel database
        $stmt = $conn->prepare("INSERT INTO alimenti (nome_piatto, descrizione, prezzo, id_categoria, immagine, lista_allergeni) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdiss", $nome, $desc, $prezzo, $idCat, $img, $allergeni);

        $stmt->execute() ? redirect('success', 'menu') : die("Errore: " . $stmt->error);
        break;
    }

    // --------------------------------------------------------
    // ACTION: modifica_piatto
    // Aggiorna i dettagli di un piatto esistente.
    // Se viene caricata una nuova foto, aggiorna anche l'immagine.
    // --------------------------------------------------------
    case 'modifica_piatto': {
        requirePost();

        $id        = intval($_POST['id_alimento']);        // Il piatto da aggiornare
        $nome      = $_POST['nome_piatto'] ?? '';
        $prezzo    = floatval($_POST['prezzo'] ?? 0);
        $desc      = $_POST['descrizione'] ?? '';
        $idCat     = intval($_POST['id_categoria'] ?? 0);
        $allergeni = leggiAllergeni(', ');
        $img       = leggiImmagine();                       // Può essere null se non c'è una nuova foto

        if ($img) {
            // Una nuova foto è stata caricata — aggiorna tutto inclusa l'immagine
            $stmt = $conn->prepare("UPDATE alimenti SET nome_piatto=?, prezzo=?, descrizione=?, id_categoria=?, lista_allergeni=?, immagine=? WHERE id_alimento=?");
            $stmt->bind_param("sdsissi", $nome, $prezzo, $desc, $idCat, $allergeni, $img, $id);
        } else {
            // Nessuna nuova foto — aggiorna tutto eccetto la colonna immagine
            $stmt = $conn->prepare("UPDATE alimenti SET nome_piatto=?, prezzo=?, descrizione=?, id_categoria=?, lista_allergeni=? WHERE id_alimento=?");
            $stmt->bind_param("sdsisi", $nome, $prezzo, $desc, $idCat, $allergeni, $id);
        }

        $stmt->execute() ? redirect('success', 'menu') : die("Errore: " . $stmt->error);
        break;
    }

    // --------------------------------------------------------
    // ACTION: elimina_piatto
    // Elimina permanentemente un piatto dal menu.
    // --------------------------------------------------------
    case 'elimina_piatto': {
        requirePost();

        if (empty($_POST['id_alimento'])) die("ID piatto mancante.");

        $id = intval($_POST['id_alimento']);

        $stmt = $conn->prepare("DELETE FROM alimenti WHERE id_alimento = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute() ? redirect('deleted') : die("Errore: " . $stmt->error);
        break;
    }

    // --------------------------------------------------------
    // ACTION: elimina_categoria
    // Elimina una categoria dal menu.
    // Se ci sono ancora piatti collegati alla categoria,
    // il vincolo di "Foreign Key" del database bloccherà l'eliminazione.
    // --------------------------------------------------------
    case 'elimina_categoria': {
        requirePost();

        if (empty($_POST['id_categoria'])) die("ID categoria mancante.");

        $id = intval($_POST['id_categoria']);

        $stmt = $conn->prepare("DELETE FROM categorie WHERE id_categoria = ?");
        $stmt->bind_param("i", $id);

        try {
            if (!$stmt->execute()) throw new Exception($stmt->error);
            redirect('cat_deleted');
        } catch (Exception $e) {
            // Il database ha bloccato l'eliminazione perché esistono ancora piatti in questa categoria.
            // Mostra un messaggio di errore amichevole al manager invece di una schermata bianca.
            echo "<div style='font-family:sans-serif;padding:40px;text-align:center;color:#721c24;background:#f8d7da;'>";
            echo "<h2>Impossibile eliminare!</h2>";
            echo "<p>Ci sono ancora piatti collegati a questa categoria.</p>";
            echo "<br><a href='../../dashboards/manager.php' style='padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;'>Indietro</a>";
            echo "</div>";
        }
        break;
    }

    // --------------------------------------------------------
    // ACTION: aggiungi_tavolo
    // Crea un nuovo utente tavolo nel sistema.
    // Ogni tavolo ha un username, una password e un numero di posti.
    // --------------------------------------------------------
    case 'aggiungi_tavolo': {
        $nome     = trim($_POST['nome_tavolo'] ?? '');  // Nome del tavolo (es. "Tavolo 1")
        $password = trim($_POST['password'] ?? '');      // Password di login del tavolo
        $posti    = intval($_POST['posti'] ?? 4);        // Numero di posti

        // Sia il nome che la password sono richiesti
        if (empty($nome) || empty($password))
            json(['success' => false, 'error' => 'Nome e Password sono obbligatori.']);

        // Controlla se esiste già un tavolo con questo nome
        $check = $conn->prepare("SELECT id_utente FROM utenti WHERE username = ? AND ruolo='tavolo'");
        $check->bind_param("s", $nome);
        $check->execute();
        if ($check->get_result()->num_rows > 0)
            json(['success' => false, 'error' => 'Esiste già un tavolo con questo nome.']);

        // Inserisci il nuovo tavolo come utente con il ruolo "tavolo" e stato "libero"
        $stmt = $conn->prepare("INSERT INTO utenti (username, password, ruolo, stato, posti, id_menu) VALUES (?, ?, 'tavolo', 'libero', ?, 1)");
        $stmt->bind_param("ssi", $nome, $password, $posti);

        json($stmt->execute() ? ['success' => true, 'id' => $stmt->insert_id] : ['success' => false, 'error' => 'Errore salvataggio.']);
    }

    // --------------------------------------------------------
    // ACTION: modifica_tavolo
    // Aggiorna il nome, la password, il numero di posti o lo stato di un tavolo.
    // --------------------------------------------------------
    case 'modifica_tavolo': {
        $id       = intval($_POST['id_tavolo'] ?? 0);
        $nome     = trim($_POST['nome_tavolo'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $posti    = intval($_POST['posti'] ?? 4);
        $stato    = trim($_POST['stato'] ?? 'libero'); // 'libero', 'occupato', o 'riservato'

        // Controlla la validità dei campi richiesti
        if ($id <= 0 || empty($nome) || empty($password))
            json(['success' => false, 'error' => 'Dati incompleti.']);

        $stmt = $conn->prepare("UPDATE utenti SET username=?, password=?, posti=?, stato=? WHERE id_utente=? AND ruolo='tavolo'");
        $stmt->bind_param("ssisi", $nome, $password, $posti, $stato, $id);

        json($stmt->execute() ? ['success' => true] : ['success' => false, 'error' => 'Errore: ' . $stmt->error]);
    }

    // --------------------------------------------------------
    // ACTION: cambia_stato_tavolo
    // Cambia velocemente solo lo stato di un tavolo
    // (libero / occupato / riservato) senza modificare gli altri campi.
    // --------------------------------------------------------
    case 'cambia_stato_tavolo': {
        $id    = intval($_POST['id_tavolo'] ?? 0);
        $stato = trim($_POST['stato'] ?? '');

        // Permette solo valori di stato validi (approccio whitelist per sicurezza)
        if ($id <= 0 || !in_array($stato, ['libero', 'occupato', 'riservato']))
            json(['success' => false, 'error' => 'Dati non validi']);

        $stmt = $conn->prepare("UPDATE utenti SET stato = ? WHERE id_utente = ? AND ruolo='tavolo'");
        $stmt->bind_param("si", $stato, $id);

        json($stmt->execute() ? ['success' => true, 'nuovo_stato' => $stato] : ['success' => false, 'error' => 'Errore aggiornamento']);
    }

    // --------------------------------------------------------
    // ACTION: elimina_tavolo
    // Elimina un tavolo e tutti gli ordini relativi.
    // Eliminiamo manualmente prima i record "figli" perché il DB
    // usa i vincoli "Foreign Key" (una riga non può essere eliminata
    // se altre righe in altre tabelle fanno riferimento ad essa).
    // --------------------------------------------------------
    case 'elimina_tavolo': {
        $id = intval($_POST['id_tavolo'] ?? 0);
        if ($id <= 0) json(['success' => false, 'error' => 'ID tavolo non valido.']);

        // Step 1: Elimina i dettagli (elementi o piatti) di quegli ordini collegati a questo tavolo
        $conn->query("DELETE do FROM dettaglio_ordini do INNER JOIN ordini o ON do.id_ordine = o.id_ordine WHERE o.id_utente = $id");

        // Step 2: Elimina gli ordini stessi
        $conn->query("DELETE FROM ordini WHERE id_utente = $id");

        // Step 3: Infine elimina il record del tavolo (utente)
        $stmt = $conn->prepare("DELETE FROM utenti WHERE id_utente = ? AND ruolo='tavolo'");
        $stmt->bind_param("i", $id);

        json($stmt->execute() ? ['success' => true] : ['success' => false, 'error' => 'Errore eliminazione.']);
    }

    // --------------------------------------------------------
    // ACTION: get_tavoli
    // Ritorna una lista di TUTTI i tavoli, compreso il loro stato e
    // quanti ordini attivi hanno in quel momento.
    // Usato nella dashboard manager per mostrare la griglia dei tavoli.
    // --------------------------------------------------------
    case 'get_tavoli': {
        $result = $conn->query("
            SELECT
                t.id_utente,
                t.username,
                t.password,
                COALESCE(t.stato, 'libero') AS stato,   -- Imposta a 'libero' se null
                COALESCE(t.posti, 4) AS posti,           -- Imposta a 4 posti se null
                t.id_menu,
                -- Conta gli ordini attivi (non ancora 'pronto') per questo tavolo
                (SELECT COUNT(*) FROM ordini o WHERE o.id_utente = t.id_utente AND o.stato != 'pronto') AS ordini_attivi
            FROM utenti t
            WHERE t.ruolo = 'tavolo'
            ORDER BY t.username ASC
        ");

        json($result ? $result->fetch_all(MYSQLI_ASSOC) : []);
    }

    // --------------------------------------------------------
    // ACTION: termina_sessione
    // Forza il logout di un tavolo azzerando i suoi dati di sessione.
    // Il manager lo usa per "pulire" un tavolo dopo che i clienti se ne vanno.
    // --------------------------------------------------------
    case 'termina_sessione': {
        $id = intval($_POST['id_tavolo'] ?? 0);
        if ($id <= 0) json(['success' => false, 'error' => 'ID tavolo non valido.']);

        // Reimposta l'inizio sessione, metti lo stato 'libero' e cancella il token dispositivo
        $stmt = $conn->prepare("UPDATE utenti SET sessione_inizio = NOW(), stato = 'libero', device_token = NULL WHERE id_utente = ? AND ruolo='tavolo'");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        json(['success' => true]);
    }

    // --------------------------------------------------------
    // DEFAULT: Azione sconosciuta
    // --------------------------------------------------------
    default:
        die('Azione non valida.');
}
?>
