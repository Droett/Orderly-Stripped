<?php
// Avvia o riprende la sessione PHP per identificare l'utente connesso
session_start();
// Includi lo script di connessione al database
require_once '../../include/conn.php';
// Includi il gestore dei permessi e ruoli (RBAC) basato su DB
require_once '../../include/auth/check_permesso.php';

// Estrai l'azione richiesta dall'URL (parametro GET '?action=...'), default stringa vuota
$action = $_GET['action'] ?? '';
// Verifica se l'utente attuale ha il permesso per eseguire questa specifica azione manageriale
if (!verificaPermesso($conn, "manager/" . $action)) {
    // Se non ha i permessi, imposta lo status code HTTP 403 Forbidden
    http_response_code(403);
    // Interrompi l'esecuzione restituendo un JSON d'errore
    die(json_encode(['success' => false, 'error' => 'Non autorizzato', 'message' => 'Non autorizzato']));
}

// Router principale basato sull'azione richiesta
switch ($action) {
    case 'aggiungi_categoria': {
        // Aggiunge una nuova categoria al menu
        
        // Blocca l'accesso se il metodo non è POST
        if ($_SERVER["REQUEST_METHOD"] !== "POST")
            die("Accesso negato.");
        
        // Pulisce e cattura il nome della categoria inviato
        $nome = trim($_POST['nome_categoria'] ?? '');
        // Cattura l'ID del menu (convertito a intero di sicurezza)
        $idMenu = intval($_POST['id_menu'] ?? 0);
        
        // Blocca se il nome è vuoto
        if (empty($nome))
            die("La categoria deve avere un nome!");
        
        // Prepara la query di inserimento categoria anti SQL-injection
        $stmt = $conn->prepare("INSERT INTO categorie (nome_categoria, id_menu) VALUES (?, ?)");
        // Binda parametri: nome (string), id_menu (integer)
        $stmt->bind_param("si", $nome, $idMenu);
        
        // Esegue la query
        if ($stmt->execute()) {
            // Se successo redirige con messaggio di conferma
            header("Location: ../../dashboards/manager.php?msg=cat_success");
        } else {
            // Se fallisce stampa in output l'errore nudo del server (utile per debug)
            echo "Errore: " . $stmt->error;
        }
        
        // Interrompi questo case block
        break;
    }

    case 'aggiungi_piatto': {
        // Aggiunge un nuovo piatto al menu (supporta upload foto e array allergeni)
        
        // Blocca se metodo diverso da POST
        if ($_SERVER["REQUEST_METHOD"] !== "POST")
            die("Accesso negato.");
        
        // Estrazione e sanitizzazione basilare
        $nomePiatto = $_POST['nome_piatto'] ?? '';
        $descrizione = $_POST['descrizione'] ?? '';
        // Converte prezzo in float
        $prezzo = floatval($_POST['prezzo'] ?? 0);
        // Converte id categoria in intero
        $idCategoria = intval($_POST['id_categoria'] ?? 0);
        // Collassa l'array di allergeni checkato nel frontend in una stringa csv separata dalla virgola
        $allergeni = empty($_POST['allergeni']) ? "" : implode(",", $_POST['allergeni']);
        
        // Inizializza variabile binaria per il blob immagine a nullo
        $immagineBinaria = null;
        // Se un'immagine è stata inviata e senza errori di upload server
        if (isset($_FILES["immagine"]) && $_FILES["immagine"]["error"] === 0) {
            // Leggi l'intero file caricato ram locale e fai cast a bytes nudi
            $immagineBinaria = file_get_contents($_FILES["immagine"]["tmp_name"]);
        }
        
        // Prepara query insert del piatto
        $stmt = $conn->prepare("INSERT INTO alimenti (nome_piatto, descrizione, prezzo, id_categoria, immagine, lista_allergeni) VALUES (?, ?, ?, ?, ?, ?)");
        // 'ssdiss': due stringhe(s,s) un double(d) intero(i) e due ultime stringhe compreso il blob come s(string)
        $stmt->bind_param("ssdiss", $nomePiatto, $descrizione, $prezzo, $idCategoria, $immagineBinaria, $allergeni);
        
        // Runna query
        if ($stmt->execute()) {
            // Redirige
            header("Location: ../../dashboards/manager.php?msg=success#menu");
        } else {
            // Errore failure db dump
            echo "Errore: " . $stmt->error;
        }
        
        break;
    }

    case 'aggiungi_tavolo': {
        // Registra un nuovo utente di ruolo tavolo nel sistema (REST API JSON style)
        
        // Setta l'intestazione esplicita JSON per il frontend JS (fetch/ajax)
        header('Content-Type: application/json');
        
        // Estrai nome tavolo
        $nome = trim($_POST['nome_tavolo'] ?? '');
        // Estrai password
        $password = trim($_POST['password'] ?? '');
        // Estrai sedute a tavola (def 4 int)
        $posti = intval($_POST['posti'] ?? 4);
        
        // Requisiti obbligatori
        if (empty($nome) || empty($password)) {
            // Responso fail JSON
            echo json_encode(['success' => false, 'error' => 'Nome e Password sono obbligatori.']);
            exit; // Taglio secchi script
        }
        
        // Verifica pre-insert per bloccare omonimie clonate di tavoli
        $check = $conn->prepare("SELECT id_utente FROM utenti WHERE username = ? AND ruolo='tavolo'");
        $check->bind_param("s", $nome);
        $check->execute();
        // Se si trova 1 record matcha il nome
        if ($check->get_result()->num_rows > 0) {
            // Fail perche già esite questo identico tavolo root account login
            echo json_encode(['success' => false, 'error' => 'Esiste già un tavolo con questo nome.']);
            exit;
        }
        
        // Inserimento con stato bloccato ('tavolo') e id form 1 ('menu base') e default a 'libero'
        $stmt = $conn->prepare("INSERT INTO utenti (username, password, ruolo, stato, posti, id_menu) VALUES (?, ?, 'tavolo', 'libero', ?, 1)");
        $stmt->bind_param("ssi", $nome, $password, $posti);
        
        // Ritorna espressione ternaria esito True con id generato o False con messagio fail server sql
        echo json_encode($stmt->execute() ? ['success' => true, 'id' => $stmt->insert_id] : ['success' => false, 'error' => 'Errore durante il salvataggio.']);
        
        break;
    }

    case 'cambia_stato_tavolo': {
        // Aggiorna bandierina visuale stato del tavolo dal lato root manager interface (Libero Occupato Riservato)
        
        header('Content-Type: application/json');
        
        $id = intval($_POST['id_tavolo'] ?? 0);
        $stato = trim($_POST['stato'] ?? '');
        // Definiamo lista di enum consentiti onde di evitare manomissione parametri da inspector
        $statiValidi = ['libero', 'occupato', 'riservato'];
        
        // Se id falso o lo stato forzato da input non esiste nel range ammesso allora crash
        if ($id <= 0 || !in_array($stato, $statiValidi)) {
            echo json_encode(['success' => false, 'error' => 'Dati non validi']);
            exit;
        }
        
        // Modifica stato SOLO se l'utente intercettato e' rigorosamente di classe tavoli security level
        $stmt = $conn->prepare("UPDATE utenti SET stato = ? WHERE id_utente = ? AND ruolo='tavolo'");
        $stmt->bind_param("si", $stato, $id);
        
        echo json_encode($stmt->execute() ? ['success' => true, 'nuovo_stato' => $stato] : ['success' => false, 'error' => 'Errore aggiornamento']);
        
        break;
    }

    case 'elimina_categoria': {
        // Elimina hard reset della categoria (triggera MySQL Constraint fails se in us on un child Piatto cascade block)
        
        if ($_SERVER["REQUEST_METHOD"] !== "POST")
            die("Accesso negato.");
        
        if (empty($_POST['id_categoria']))
            die("ID categoria mancante.");
        
        $id = intval($_POST['id_categoria']);
        
        $stmt = $conn->prepare("DELETE FROM categorie WHERE id_categoria = ?");
        $stmt->bind_param("i", $id);
        
        // Try per fare trapping eccezione da mysql constraint chiave esterna su piatti se esistenti
        try {
            if ($stmt->execute()) {
                header("Location: ../../dashboards/manager.php?msg=cat_deleted");
            } else {
                throw new Exception($stmt->error); // Forza error se db fallisce per logica interna
            }
        } catch (Exception $e) {
            // Messaggione bruttale di error view custom su PHP puro crudo echo per mostrare la protezione relazioni db child record esistente
            echo "<div style='font-family:sans-serif;padding:40px;text-align:center;color:#721c24;background:#f8d7da;'>";
            echo "<h2>Impossibile eliminare!</h2>";
            echo "<p>Ci sono ancora piatti collegati a questa categoria.</p>";
            echo "<br><a href='../../dashboards/manager.php' style='padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;'>Indietro</a>";
            echo "</div>";
        }
        
        break;
    }

    case 'elimina_piatto': {
        // Elimina un alimento (distruzione record riga table mysql)
        
        if ($_SERVER["REQUEST_METHOD"] !== "POST")
            die("Accesso negato.");
        
        if (empty($_POST['id_alimento']))
            die("ID piatto mancante.");
        
        $id = intval($_POST['id_alimento']);
        
        $stmt = $conn->prepare("DELETE FROM alimenti WHERE id_alimento = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            header("Location: ../../dashboards/manager.php?msg=deleted");
        } else {
            echo "Errore: " . $stmt->error;
        }
        
        break;
    }

    case 'elimina_tavolo': {
        // Metodo violento manager reset per epurare anche record derivanti orfani
        
        header('Content-Type: application/json');
        
        $id = intval($_POST['id_tavolo'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID tavolo non valido.']);
            exit;
        }
        
        // Pialla prima le righine child foreign key (Elimina le ordinazioni di dettaglio DO) joinando su head table e verificando a tavolo appesa constraint cascade
        $conn->query("DELETE do FROM dettaglio_ordini do INNER JOIN ordini o ON do.id_ordine = o.id_ordine WHERE o.id_utente = $id");
        // Spazza ordini teste generali header di transito per tavolo intercettati null record list cascade block evasion limits
        $conn->query("DELETE FROM ordini WHERE id_utente = $id");
        
        // Solo ora si puo droppare root the big boss user tavola account without the foreign constraints key limits blocking boundaries server side
        $stmt = $conn->prepare("DELETE FROM utenti WHERE id_utente = ? AND ruolo='tavolo'");
        $stmt->bind_param("i", $id);
        
        echo json_encode($stmt->execute() ? ['success' => true] : ['success' => false, 'error' => 'Errore eliminazione.']);
        
        break;
    }

    case 'get_tavoli': {
        // API getta la roba status refresh loop JS lato tavoli grid
        
        header('Content-Type: application/json');
        
        // Sub query massiccia calcolatrice in corsa numero items carrello da o.stato diversi da pronto e coalescenza dati x valori opz nulli null pointer def param
        $result = $conn->query("SELECT t.id_utente, t.username, t.password,
            COALESCE(t.stato, 'libero') as stato, COALESCE(t.posti, 4) as posti, t.id_menu,
            (SELECT COUNT(*) FROM ordini o WHERE o.id_utente = t.id_utente AND o.stato != 'pronto') as ordini_attivi
            FROM utenti t WHERE t.ruolo='tavolo' ORDER BY t.username ASC");
        
        // Return fetch massivo db res o array vuoto se 0 records
        echo json_encode($result ? $result->fetch_all(MYSQLI_ASSOC) : []);
        
        break;
    }

    case 'modifica_piatto': {
        // Script editing piatti table
        
        if ($_SERVER["REQUEST_METHOD"] !== "POST")
            die("Accesso negato.");
        
        $idPiatto = intval($_POST['id_alimento']);
        $nomePiatto = $_POST['nome_piatto'] ?? '';
        $prezzo = floatval($_POST['prezzo'] ?? 0);
        $descrizione = $_POST['descrizione'] ?? '';
        $idCategoria = intval($_POST['id_categoria'] ?? 0);
        // Ricompila var string array string form
        $allergeni = empty($_POST['allergeni']) ? "" : implode(", ", $_POST['allergeni']);
        
        // Seletect per definire flag di modifica bolla immagine
        $aggiornaImmagine = isset($_FILES['immagine']) && $_FILES['immagine']['error'] === 0;
        
        // Se update full incl fotografia
        if ($aggiornaImmagine) {
            $immagineBinaria = file_get_contents($_FILES["immagine"]["tmp_name"]);
            // Inietta parametro extra immagine e i file bytes binding param (s i i s s d s) params values list updates rules limits mapping mapping margins
            $stmt = $conn->prepare("UPDATE alimenti SET nome_piatto=?, prezzo=?, descrizione=?, id_categoria=?, lista_allergeni=?, immagine=? WHERE id_alimento=?");
            $stmt->bind_param("sdsissi", $nomePiatto, $prezzo, $descrizione, $idCategoria, $allergeni, $immagineBinaria, $idPiatto);
        } else {
            // Update light scarta immg non sovrascriverla o la svuoti con the param sql injection values
            $stmt = $conn->prepare("UPDATE alimenti SET nome_piatto=?, prezzo=?, descrizione=?, id_categoria=?, lista_allergeni=? WHERE id_alimento=?");
            $stmt->bind_param("sdsisi", $nomePiatto, $prezzo, $descrizione, $idCategoria, $allergeni, $idPiatto);
        }
        
        if ($stmt->execute()) {
            header("Location: ../../dashboards/manager.php?msg=success#menu"); // hash routing back to the menu lists
        } else {
            echo "Errore: " . $stmt->error;
        }
        
        break;
    }

    case 'modifica_tavolo': {
        // Edit tavolo data properties and configs
        
        header('Content-Type: application/json');
        
        $id = intval($_POST['id_tavolo'] ?? 0);
        $nome = trim($_POST['nome_tavolo'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $posti = intval($_POST['posti'] ?? 4);
        $stato = trim($_POST['stato'] ?? 'libero');
        
        // Validation check constraint
        if ($id <= 0 || empty($nome) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Dati incompleti.']);
            exit;
        }
        
        // Set update
        $stmt = $conn->prepare("UPDATE utenti SET username=?, password=?, posti=?, stato=? WHERE id_utente=? AND ruolo='tavolo'");
        $stmt->bind_param("ssisi", $nome, $password, $posti, $stato, $id);
        
        echo json_encode($stmt->execute() ? ['success' => true] : ['success' => false, 'error' => 'Errore: ' . $stmt->error]);
        
        break;
    }

    case 'termina_sessione': {
        // Azzera la login live active di un tavolo resettandogli i token bypass e sbattendo utente alla index view limits form maps
        
        header('Content-Type: application/json');
        
        $id = intval($_POST['id_tavolo'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID tavolo non valido.']);
            exit;
        }
        
        // Reset device token = nullo cosi client con vecchio cookie match fail session db refresh values parameters 
        $stmt = $conn->prepare("UPDATE utenti SET sessione_inizio = NOW(), stato = 'libero', device_token = NULL WHERE id_utente = ? AND ruolo='tavolo'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
        
        break;
    }

    // Default error switch caso invalid action string name endpoint non esiste
    default:
        die('Azione non valida.'); // Fall down break -> exit
}
?>
