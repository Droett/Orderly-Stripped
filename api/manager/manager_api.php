<?php
session_start();
require_once '../../include/conn.php';
require_once '../../include/auth/check_permesso.php';

$action = $_GET['action'] ?? '';
if (!verificaPermesso($conn, "manager/" . $action)) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Non autorizzato', 'message' => 'Non autorizzato']));
}

switch ($action) {
    case 'aggiungi_categoria': {
        // Aggiunge una nuova categoria al menu
        
        
        if ($_SERVER["REQUEST_METHOD"] !== "POST")
            die("Accesso negato.");
        
        $nome = trim($_POST['nome_categoria'] ?? '');
        $idMenu = intval($_POST['id_menu'] ?? 0);
        
        if (empty($nome))
            die("La categoria deve avere un nome!");
        
        $stmt = $conn->prepare("INSERT INTO categorie (nome_categoria, id_menu) VALUES (?, ?)");
        $stmt->bind_param("si", $nome, $idMenu);
        
        if ($stmt->execute()) {
            header("Location: ../../dashboards/manager.php?msg=cat_success");
        } else {
            echo "Errore: " . $stmt->error;
        }
        
        break;
    }

    case 'aggiungi_piatto': {
        // Aggiunge un nuovo piatto al menu con foto e allergeni
        
        
        if ($_SERVER["REQUEST_METHOD"] !== "POST")
            die("Accesso negato.");
        
        $nomePiatto = $_POST['nome_piatto'] ?? '';
        $descrizione = $_POST['descrizione'] ?? '';
        $prezzo = floatval($_POST['prezzo'] ?? 0);
        $idCategoria = intval($_POST['id_categoria'] ?? 0);
        $allergeni = empty($_POST['allergeni']) ? "" : implode(",", $_POST['allergeni']);
        
        $immagineBinaria = null;
        if (isset($_FILES["immagine"]) && $_FILES["immagine"]["error"] === 0) {
            $immagineBinaria = file_get_contents($_FILES["immagine"]["tmp_name"]);
        }
        
        $stmt = $conn->prepare("INSERT INTO alimenti (nome_piatto, descrizione, prezzo, id_categoria, immagine, lista_allergeni) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdiss", $nomePiatto, $descrizione, $prezzo, $idCategoria, $immagineBinaria, $allergeni);
        
        if ($stmt->execute()) {
            header("Location: ../../dashboards/manager.php?msg=success#menu");
        } else {
            echo "Errore: " . $stmt->error;
        }
        
        break;
    }

    case 'aggiungi_tavolo': {
        // Registra un nuovo tavolo nel sistema
        
                header('Content-Type: application/json');
        
        $nome = trim($_POST['nome_tavolo'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $posti = intval($_POST['posti'] ?? 4);
        
        if (empty($nome) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Nome e Password sono obbligatori.']);
            exit;
        }
        
        // Verifica che non esista già un tavolo con lo stesso nome
        $check = $conn->prepare("SELECT id_utente FROM utenti WHERE username = ? AND ruolo='tavolo'");
        $check->bind_param("s", $nome);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'Esiste già un tavolo con questo nome.']);
            exit;
        }
        
        $stmt = $conn->prepare("INSERT INTO utenti (username, password, ruolo, stato, posti, id_menu) VALUES (?, ?, 'tavolo', 'libero', ?, 1)");
        $stmt->bind_param("ssi", $nome, $password, $posti);
        
        echo json_encode($stmt->execute() ? ['success' => true, 'id' => $stmt->insert_id] : ['success' => false, 'error' => 'Errore durante il salvataggio.']);
        
        break;
    }

    case 'cambia_stato_tavolo': {
        // Cambia lo stato di un tavolo (libero/occupato/riservato)
        
                header('Content-Type: application/json');
        
        $id = intval($_POST['id_tavolo'] ?? 0);
        $stato = trim($_POST['stato'] ?? '');
        $statiValidi = ['libero', 'occupato', 'riservato'];
        
        if ($id <= 0 || !in_array($stato, $statiValidi)) {
            echo json_encode(['success' => false, 'error' => 'Dati non validi']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE utenti SET stato = ? WHERE id_utente = ? AND ruolo='tavolo'");
        $stmt->bind_param("si", $stato, $id);
        
        echo json_encode($stmt->execute() ? ['success' => true, 'nuovo_stato' => $stato] : ['success' => false, 'error' => 'Errore aggiornamento']);
        
        break;
    }

    case 'elimina_categoria': {
        // Elimina una categoria (fallisce se contiene ancora piatti per vincoli FK)
        
        
        if ($_SERVER["REQUEST_METHOD"] !== "POST")
            die("Accesso negato.");
        
        if (empty($_POST['id_categoria']))
            die("ID categoria mancante.");
        
        $id = intval($_POST['id_categoria']);
        
        $stmt = $conn->prepare("DELETE FROM categorie WHERE id_categoria = ?");
        $stmt->bind_param("i", $id);
        
        try {
            if ($stmt->execute()) {
                header("Location: ../../dashboards/manager.php?msg=cat_deleted");
            } else {
                throw new Exception($stmt->error);
            }
        } catch (Exception $e) {
            echo "<div style='font-family:sans-serif;padding:40px;text-align:center;color:#721c24;background:#f8d7da;'>";
            echo "<h2>Impossibile eliminare!</h2>";
            echo "<p>Ci sono ancora piatti collegati a questa categoria.</p>";
            echo "<br><a href='../../dashboards/manager.php' style='padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;'>Indietro</a>";
            echo "</div>";
        }
        
        break;
    }

    case 'elimina_piatto': {
        // Elimina un piatto dal menu
        
        
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
        // Elimina un tavolo e tutti i suoi ordini associati
        
                header('Content-Type: application/json');
        
        $id = intval($_POST['id_tavolo'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID tavolo non valido.']);
            exit;
        }
        
        // Elimina prima i dettagli ordini e gli ordini (vincoli FK)
        $conn->query("DELETE do FROM dettaglio_ordini do INNER JOIN ordini o ON do.id_ordine = o.id_ordine WHERE o.id_utente = $id");
        $conn->query("DELETE FROM ordini WHERE id_utente = $id");
        
        $stmt = $conn->prepare("DELETE FROM utenti WHERE id_utente = ? AND ruolo='tavolo'");
        $stmt->bind_param("i", $id);
        
        echo json_encode($stmt->execute() ? ['success' => true] : ['success' => false, 'error' => 'Errore eliminazione.']);
        
        break;
    }

    case 'get_tavoli': {
        // Restituisce la lista di tutti i tavoli con il conteggio ordini attivi
        
                header('Content-Type: application/json');
        
        $result = $conn->query("SELECT t.id_utente, t.username, t.password,
            COALESCE(t.stato, 'libero') as stato, COALESCE(t.posti, 4) as posti, t.id_menu,
            (SELECT COUNT(*) FROM ordini o WHERE o.id_utente = t.id_utente AND o.stato != 'pronto') as ordini_attivi
            FROM utenti t WHERE t.ruolo='tavolo' ORDER BY t.username ASC");
        
        echo json_encode($result ? $result->fetch_all(MYSQLI_ASSOC) : []);
        
        break;
    }

    case 'modifica_piatto': {
        // Aggiorna i dati di un piatto esistente (con eventuale nuova immagine)
        
        
        if ($_SERVER["REQUEST_METHOD"] !== "POST")
            die("Accesso negato.");
        
        $idPiatto = intval($_POST['id_alimento']);
        $nomePiatto = $_POST['nome_piatto'] ?? '';
        $prezzo = floatval($_POST['prezzo'] ?? 0);
        $descrizione = $_POST['descrizione'] ?? '';
        $idCategoria = intval($_POST['id_categoria'] ?? 0);
        $allergeni = empty($_POST['allergeni']) ? "" : implode(", ", $_POST['allergeni']);
        
        $aggiornaImmagine = isset($_FILES['immagine']) && $_FILES['immagine']['error'] === 0;
        
        if ($aggiornaImmagine) {
            $immagineBinaria = file_get_contents($_FILES["immagine"]["tmp_name"]);
            $stmt = $conn->prepare("UPDATE alimenti SET nome_piatto=?, prezzo=?, descrizione=?, id_categoria=?, lista_allergeni=?, immagine=? WHERE id_alimento=?");
            $stmt->bind_param("sdsissi", $nomePiatto, $prezzo, $descrizione, $idCategoria, $allergeni, $immagineBinaria, $idPiatto);
        } else {
            $stmt = $conn->prepare("UPDATE alimenti SET nome_piatto=?, prezzo=?, descrizione=?, id_categoria=?, lista_allergeni=? WHERE id_alimento=?");
            $stmt->bind_param("sdsisi", $nomePiatto, $prezzo, $descrizione, $idCategoria, $allergeni, $idPiatto);
        }
        
        if ($stmt->execute()) {
            header("Location: ../../dashboards/manager.php?msg=success#menu");
        } else {
            echo "Errore: " . $stmt->error;
        }
        
        break;
    }

    case 'modifica_tavolo': {
        // Modifica nome, password, posti e stato di un tavolo
        
                header('Content-Type: application/json');
        
        $id = intval($_POST['id_tavolo'] ?? 0);
        $nome = trim($_POST['nome_tavolo'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $posti = intval($_POST['posti'] ?? 4);
        $stato = trim($_POST['stato'] ?? 'libero');
        
        if ($id <= 0 || empty($nome) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Dati incompleti.']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE utenti SET username=?, password=?, posti=?, stato=? WHERE id_utente=? AND ruolo='tavolo'");
        $stmt->bind_param("ssisi", $nome, $password, $posti, $stato, $id);
        
        echo json_encode($stmt->execute() ? ['success' => true] : ['success' => false, 'error' => 'Errore: ' . $stmt->error]);
        
        break;
    }

    case 'termina_sessione': {
        // Termina la sessione di un tavolo (resetta stato e device_token)
        
                header('Content-Type: application/json');
        
        $id = intval($_POST['id_tavolo'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID tavolo non valido.']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE utenti SET sessione_inizio = NOW(), stato = 'libero', device_token = NULL WHERE id_utente = ? AND ruolo='tavolo'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
        
        break;
    }

    default:
        die('Azione non valida.');
}
