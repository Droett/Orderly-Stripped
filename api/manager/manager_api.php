<?php
session_start();
require_once '../../include/conn.php';
require_once '../../include/auth/check_permesso.php';

$action = $_GET['action'] ?? '';

// Blocca chiunque non sia manager
if (!verificaPermesso($conn, "manager/" . $action)) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Non autorizzato']));
}

// --- FUNZIONI HELPER per ridurre la ripetizione di codice ---

// Blocca la richiesta se non è POST (usato nei form HTML)
function requirePost() {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") die("Accesso negato.");
}

// Risponde in JSON e termina lo script
function json($data) {
    header('Content-Type: application/json');
    die(json_encode($data));
}

// Redirect alla dashboard manager con un messaggio flash
function redirect($msg, $hash = '') {
    header("Location: ../../dashboards/manager.php?msg=$msg" . ($hash ? "#$hash" : ''));
    exit;
}

// Legge l'immagine caricata come bytes binari, ritorna null se nessun file è stato caricato
function leggiImmagine() {
    if (isset($_FILES["immagine"]) && $_FILES["immagine"]["error"] === 0)
        return file_get_contents($_FILES["immagine"]["tmp_name"]);
    return null;
}

// Unisce le checkbox allergeni in una stringa separata da virgole
function leggiAllergeni($sep = ',') {
    return empty($_POST['allergeni']) ? "" : implode($sep, $_POST['allergeni']);
}

// --- ROUTER ---

switch ($action) {

    // Aggiunge una nuova categoria al menu (es. Antipasti, Dolci)
    case 'aggiungi_categoria': {
        requirePost();
        $nome = trim($_POST['nome_categoria'] ?? '');
        $idMenu = intval($_POST['id_menu'] ?? 0);

        if (empty($nome)) die("La categoria deve avere un nome!");

        $stmt = $conn->prepare("INSERT INTO categorie (nome_categoria, id_menu) VALUES (?, ?)");
        $stmt->bind_param("si", $nome, $idMenu);
        $stmt->execute() ? redirect('cat_success') : die("Errore: " . $stmt->error);
        break;
    }

    // Aggiunge un nuovo piatto al database
    case 'aggiungi_piatto': {
        requirePost();
        $nome       = $_POST['nome_piatto'] ?? '';
        $desc       = $_POST['descrizione'] ?? '';
        $prezzo     = floatval($_POST['prezzo'] ?? 0);
        $idCat      = intval($_POST['id_categoria'] ?? 0);
        $allergeni  = leggiAllergeni();
        $img        = leggiImmagine();

        $stmt = $conn->prepare("INSERT INTO alimenti (nome_piatto, descrizione, prezzo, id_categoria, immagine, lista_allergeni) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdiss", $nome, $desc, $prezzo, $idCat, $img, $allergeni);
        $stmt->execute() ? redirect('success', 'menu') : die("Errore: " . $stmt->error);
        break;
    }

    // Modifica un piatto esistente (con o senza nuova immagine)
    case 'modifica_piatto': {
        requirePost();
        $id         = intval($_POST['id_alimento']);
        $nome       = $_POST['nome_piatto'] ?? '';
        $prezzo     = floatval($_POST['prezzo'] ?? 0);
        $desc       = $_POST['descrizione'] ?? '';
        $idCat      = intval($_POST['id_categoria'] ?? 0);
        $allergeni  = leggiAllergeni(', ');
        $img        = leggiImmagine();

        // Se c'è una nuova immagine, aggiorna anche il campo BLOB; altrimenti lo salta
        if ($img) {
            $stmt = $conn->prepare("UPDATE alimenti SET nome_piatto=?, prezzo=?, descrizione=?, id_categoria=?, lista_allergeni=?, immagine=? WHERE id_alimento=?");
            $stmt->bind_param("sdsissi", $nome, $prezzo, $desc, $idCat, $allergeni, $img, $id);
        } else {
            $stmt = $conn->prepare("UPDATE alimenti SET nome_piatto=?, prezzo=?, descrizione=?, id_categoria=?, lista_allergeni=? WHERE id_alimento=?");
            $stmt->bind_param("sdsisi", $nome, $prezzo, $desc, $idCat, $allergeni, $id);
        }
        $stmt->execute() ? redirect('success', 'menu') : die("Errore: " . $stmt->error);
        break;
    }

    // Elimina un piatto dal menu
    case 'elimina_piatto': {
        requirePost();
        if (empty($_POST['id_alimento'])) die("ID piatto mancante.");

        $id = intval($_POST['id_alimento']);
        $stmt = $conn->prepare("DELETE FROM alimenti WHERE id_alimento = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute() ? redirect('deleted') : die("Errore: " . $stmt->error);
        break;
    }

    // Elimina una categoria. Se contiene ancora piatti, il DB blocca l'operazione (Foreign Key)
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
            // La Foreign Key impedisce l'eliminazione se ci sono piatti agganciati
            echo "<div style='font-family:sans-serif;padding:40px;text-align:center;color:#721c24;background:#f8d7da;'>";
            echo "<h2>Impossibile eliminare!</h2>";
            echo "<p>Ci sono ancora piatti collegati a questa categoria.</p>";
            echo "<br><a href='../../dashboards/manager.php' style='padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;'>Indietro</a>";
            echo "</div>";
        }
        break;
    }

    // Aggiunge un nuovo tavolo al sistema
    case 'aggiungi_tavolo': {
        $nome     = trim($_POST['nome_tavolo'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $posti    = intval($_POST['posti'] ?? 4);

        if (empty($nome) || empty($password))
            json(['success' => false, 'error' => 'Nome e Password sono obbligatori.']);

        // Controlla che il nome non sia già usato
        $check = $conn->prepare("SELECT id_utente FROM utenti WHERE username = ? AND ruolo='tavolo'");
        $check->bind_param("s", $nome);
        $check->execute();
        $res = $check->get_result();
        if ($res->num_rows > 0) {
            json(['success' => false, 'error' => 'Esiste già un tavolo con questo nome.']);
        }

        $stmt = $conn->prepare("INSERT INTO utenti (username, password, ruolo, stato, posti, id_menu) VALUES (?, ?, 'tavolo', 'libero', ?, 1)");
        $stmt->bind_param("ssi", $nome, $password, $posti);
        json($stmt->execute() ? ['success' => true, 'id' => $stmt->insert_id] : ['success' => false, 'error' => 'Errore salvataggio.']);
    }

    // Modifica nome, password, posti o stato di un tavolo
    case 'modifica_tavolo': {
        $id       = intval($_POST['id_tavolo'] ?? 0);
        $nome     = trim($_POST['nome_tavolo'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $posti    = intval($_POST['posti'] ?? 4);
        $stato    = trim($_POST['stato'] ?? 'libero');

        if ($id <= 0 || empty($nome) || empty($password))
            json(['success' => false, 'error' => 'Dati incompleti.']);

        $stmt = $conn->prepare("UPDATE utenti SET username=?, password=?, posti=?, stato=? WHERE id_utente=? AND ruolo='tavolo'");
        $stmt->bind_param("ssisi", $nome, $password, $posti, $stato, $id);
        json($stmt->execute() ? ['success' => true] : ['success' => false, 'error' => 'Errore: ' . $stmt->error]);
    }

    // Cambia lo stato visivo di un tavolo (libero/occupato/riservato)
    case 'cambia_stato_tavolo': {
        $id    = intval($_POST['id_tavolo'] ?? 0);
        $stato = trim($_POST['stato'] ?? '');

        if ($id <= 0 || !in_array($stato, ['libero', 'occupato', 'riservato']))
            json(['success' => false, 'error' => 'Dati non validi']);

        $stmt = $conn->prepare("UPDATE utenti SET stato = ? WHERE id_utente = ? AND ruolo='tavolo'");
        $stmt->bind_param("si", $stato, $id);
        json($stmt->execute() ? ['success' => true, 'nuovo_stato' => $stato] : ['success' => false, 'error' => 'Errore aggiornamento']);
    }

    // Elimina un tavolo e tutti i suoi ordini/dettagli collegati
    case 'elimina_tavolo': {
        $id = intval($_POST['id_tavolo'] ?? 0);
        if ($id <= 0) json(['success' => false, 'error' => 'ID tavolo non valido.']);

        // Pulizia a cascata manuale: dettagli → ordini → utente
        $conn->query("DELETE do FROM dettaglio_ordini do INNER JOIN ordini o ON do.id_ordine = o.id_ordine WHERE o.id_utente = $id");
        $conn->query("DELETE FROM ordini WHERE id_utente = $id");

        $stmt = $conn->prepare("DELETE FROM utenti WHERE id_utente = ? AND ruolo='tavolo'");
        $stmt->bind_param("i", $id);
        json($stmt->execute() ? ['success' => true] : ['success' => false, 'error' => 'Errore eliminazione.']);
    }

    // Restituisce la lista di tutti i tavoli con il conteggio ordini attivi
    case 'get_tavoli': {
        $result = $conn->query("SELECT t.id_utente, t.username, t.password,
            COALESCE(t.stato, 'libero') as stato, COALESCE(t.posti, 4) as posti, t.id_menu,
            (SELECT COUNT(*) FROM ordini o WHERE o.id_utente = t.id_utente AND o.stato != 'pronto') as ordini_attivi
            FROM utenti t WHERE t.ruolo='tavolo' ORDER BY t.username ASC");
        json($result ? $result->fetch_all(MYSQLI_ASSOC) : []);
    }

    // Forza il logout/reset di un tavolo dal pannello manager
    case 'termina_sessione': {
        $id = intval($_POST['id_tavolo'] ?? 0);
        if ($id <= 0) json(['success' => false, 'error' => 'ID tavolo non valido.']);

        $stmt = $conn->prepare("UPDATE utenti SET sessione_inizio = NOW(), stato = 'libero', device_token = NULL WHERE id_utente = ? AND ruolo='tavolo'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        json(['success' => true]);
    }

    default:
        die('Azione non valida.');
}
?>
