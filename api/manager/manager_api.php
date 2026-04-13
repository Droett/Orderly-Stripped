<?php
// Avvio sessione per l'accesso a variabili protette come l'autenticazione del manager impostata al login
session_start();

// Importiamo il file conn.php per ottenere l'oggetto mysqli connesso a db ($conn)
require_once '../../include/conn.php';

// Importiamo la logica dei ruoli
require_once '../../include/auth/check_permesso.php';

// Acquisizione sicura parametro action dall'URL: ?action=xyz
$action = $_GET['action'] ?? '';

// Il manager_api blocca chiunque non abbia esplicitamente il ruolo 'manager' tramite check_permesso 
if (!verificaPermesso($conn, "manager/" . $action)) {
    // Risposta di sicurezza: 403 Forbidden
    http_response_code(403);
    // Print Json Error e interrompe l'interpretazione 
    die(json_encode(['success' => false, 'error' => 'Non autorizzato', 'message' => 'Non autorizzato']));
}

// Router centrale per le funzioni Manageriali
switch ($action) {

    // Azione 1: Inserimento di una nuova macro Categoria (es. Pizze, Bevande)
    case 'aggiungi_categoria': {

        // Protezione addizionale: blocca chiavante se cerca di accedere l'URL via GET (digitandolo) anziché fare un form POST
        if ($_SERVER["REQUEST_METHOD"] !== "POST")
            die("Accesso negato.");

        // Pulitura degli spazi vuoti ad inizio e fine della stringa testo categoria
        $nome = trim($_POST['nome_categoria'] ?? '');

        // L'Id del menù a cui andrà ad agganciarsi questa categoria (in questo progetto è "1" fisso da input hidden, ma predisposto DB) 
        $idMenu = intval($_POST['id_menu'] ?? 0);

        // Controllo validità: il nome non può essere vuoto
        if (empty($nome))
            die("La categoria deve avere un nome!");

        // Prepariamo in modo pulito l'INSERT
        $stmt = $conn->prepare("INSERT INTO categorie (nome_categoria, id_menu) VALUES (?, ?)");

        // Passiamo i parametri sicuri. La 's' sta per string ($nome), e la 'i' sta per integer ($idMenu) 
        $stmt->bind_param("si", $nome, $idMenu);

        // Esegeuzione della query preparata 
        if ($stmt->execute()) {
            // Dopo il salvataggio ri-dirige (redirect) il browser manager verso la sua pagina visualizzando una notifica flash (msg=cat_success)
            header("Location: ../../dashboards/manager.php?msg=cat_success");
        } else {
            // Se c'è stato un errore specifico MySQL
            echo "Errore: " . $stmt->error;
        }

        break;
    }

    // Azione 2: Creare un intero piatto nuovo ed assegnarlo al listino menù
    case 'aggiungi_piatto': {

        // Restrizione dei metodi HTTP non conformi (Solo POST da HTML Form Action)
        if ($_SERVER["REQUEST_METHOD"] !== "POST")
            die("Accesso negato.");

        // Estrazione dati testuali dalle input utente nel form Piatto 
        $nomePiatto = $_POST['nome_piatto'] ?? '';
        $descrizione = $_POST['descrizione'] ?? '';

        // Cast da stringa input a numero di virgola mobile(float) per salvataggio prezzo monetario
        $prezzo = floatval($_POST['prezzo'] ?? 0);

        // Recupero categoria assegnata a cui agganciare la Foreing Key
        $idCategoria = intval($_POST['id_categoria'] ?? 0);

        // Gli allergeni dal form HTML arrivano come un array [] di checkbox selezionate. 
        // Usiamo PHP implode() per schiacciarle in un'unica riga di testo salvabile in MySQL separate da virgola (Es. "Lattosio,Glutine,Pesce")
        $allergeni = empty($_POST['allergeni']) ? "" : implode(",", $_POST['allergeni']);

        // Prepariamo la scatola dove mettere i byte dell'immagine
        $immagineBinaria = null;

        // Se l'utente ha provato a caricare un file immagine, e l'upload è andato a buon fine senza errori ($_FILES_error == 0)
        if (isset($_FILES["immagine"]) && $_FILES["immagine"]["error"] === 0) {
            // Prendi il file temporaneo uploadato salvato nelle folder temp del server Apache, scaricane i raw file bytes (codifica BLOB) e caricalo in RAM
            $immagineBinaria = file_get_contents($_FILES["immagine"]["tmp_name"]);
        }

        // Query generica insert a 6 incognite
        $stmt = $conn->prepare("INSERT INTO alimenti (nome_piatto, descrizione, prezzo, id_categoria, immagine, lista_allergeni) VALUES (?, ?, ?, ?, ?, ?)");

        // Typebinding (String, String, DoubleFloat, Integer, String(BlobBytes), String(AllergeniList))
        $stmt->bind_param("ssdiss", $nomePiatto, $descrizione, $prezzo, $idCategoria, $immagineBinaria, $allergeni);

        // Se l'operazione su Database è andata bene...
        if ($stmt->execute()) {
            // Torna subito al tab #menu della dashboard del manager, passando parametro custom di successo
            header("Location: ../../dashboards/manager.php?msg=success#menu");
        } else {
            // Mostra l'errore nudo in pagina.
            echo "Errore: " . $stmt->error;
        }

        break;
    }

    // Azione 3: Setup di un nuovo Tavolo cliente usando Fetch Javascript (non fa reload page)
    case 'aggiungi_tavolo': {

        // Imposta l'output ad Api JSON Response perché questa chiamata Javascript vuole ricevere indietro ID o JSON format
        header('Content-Type: application/json');

        // Prendo puliti username (numero di tavolo di solito es "Tavolo12") e PIN (Password)
        $nome = trim($_POST['nome_tavolo'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Numeri di posti massimi previsti da sedere per l'admin per quel tavolo
        $posti = intval($_POST['posti'] ?? 4);

        // Data Check 
        if (empty($nome) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Nome e Password sono obbligatori.']);
            exit; 
        }

        // Controllo Univocità: Controlla che nessun altro tavolo preesistente si chiami con lo stesso $nome per via dell'indice UNIQUE su db
        $check = $conn->prepare("SELECT id_utente FROM utenti WHERE username = ? AND ruolo='tavolo'");
        $check->bind_param("s", $nome);
        $check->execute();

        // Se conta file db > 0 allora vuol dire che è ridondante, andrebbe in collisione sul login!
        if ($check->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'Esiste già un tavolo con questo nome.']);
            exit;
        }

        // Tutto in regola, possiamo creare l'account
        $stmt = $conn->prepare("INSERT INTO utenti (username, password, ruolo, stato, posti, id_menu) VALUES (?, ?, 'tavolo', 'libero', ?, 1)");
        $stmt->bind_param("ssi", $nome, $password, $posti);

        // Se query success: json_encode dizionario di response positivo includendo insert_id per i javascript frontend renderer
        echo json_encode($stmt->execute() ? ['success' => true, 'id' => $stmt->insert_id] : ['success' => false, 'error' => 'Errore durante il salvataggio.']);

        break;
    }

    // Azione 4: Manager che clicca un badge per gestire lo stato locale visivo di un tavolo a fine turno 
    case 'cambia_stato_tavolo': {

        header('Content-Type: application/json');

        // Cast id sicuro, stringa check su stato
        $id = intval($_POST['id_tavolo'] ?? 0);
        $stato = trim($_POST['stato'] ?? '');

        // Previene iniezioni per evitare di inserire stringhe 'A_CASO' ma solo enumeri controllati
        $statiValidi = ['libero', 'occupato', 'riservato'];

        if ($id <= 0 || !in_array($stato, $statiValidi)) {
            echo json_encode(['success' => false, 'error' => 'Dati non validi']);
            exit;
        }

        // Aggiorna specificatamente per ruolo="tavolo" onde evitare malfunzionamenti dove un manager disattiva il suo stesso account
        $stmt = $conn->prepare("UPDATE utenti SET stato = ? WHERE id_utente = ? AND ruolo='tavolo'");
        $stmt->bind_param("si", $stato, $id);

        // Stampa risposta
        echo json_encode($stmt->execute() ? ['success' => true, 'nuovo_stato' => $stato] : ['success' => false, 'error' => 'Errore aggiornamento']);

        break;
    }

    // Azione 5: Rimozione categorica e dei raggruppamenti del Menu
    case 'elimina_categoria': {

        // Solite Sicurezze Cross-Scripting GET block
        if ($_SERVER["REQUEST_METHOD"] !== "POST")
            die("Accesso negato.");

        if (empty($_POST['id_categoria']))
            die("ID categoria mancante.");

        $id = intval($_POST['id_categoria']);

        $stmt = $conn->prepare("DELETE FROM categorie WHERE id_categoria = ?");
        $stmt->bind_param("i", $id);

        try {
            // Esecuzione Cancellazione dal Database SQL Formale
            if ($stmt->execute()) {
                // Naviga l'utente ad intero reload visualizzandogli un messaggio
                header("Location: ../../dashboards/manager.php?msg=cat_deleted");
            } else {
                throw new Exception($stmt->error); 
            }
        } catch (Exception $e) {
            // Questa eccezione avviene spesso sulle Constraint SQL! Se per sbaglio Manager vuole cancellare Categoria 
            // "Primi" che però ha ancora dentro la "Carbonara", il DBMS fallisce di proposito la Foreign Key Action impedendo di renderli 
            // piatti Orfani. Si stampano gli headers html basic con link di ritorno per avvisare di questo Blocco Tecnico Riferito.
            echo "<div style='font-family:sans-serif;padding:40px;text-align:center;color:#721c24;background:#f8d7da;'>";
            echo "<h2>Impossibile eliminare!</h2>";
            echo "<p>Ci sono ancora piatti collegati a questa categoria.</p>";
            echo "<br><a href='../../dashboards/manager.php' style='padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;'>Indietro</a>";
            echo "</div>";
        }

        break;
    }

    // Azione 6: Rimozione Singolo Piatto  
    case 'elimina_piatto': {

        if ($_SERVER["REQUEST_METHOD"] !== "POST")
            die("Accesso negato.");

        if (empty($_POST['id_alimento']))
            die("ID piatto mancante.");

        $id = intval($_POST['id_alimento']);

        $stmt = $conn->prepare("DELETE FROM alimenti WHERE id_alimento = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            // Cancellato? Bene torna in Dashboard tab Default e refresh msg success
            header("Location: ../../dashboards/manager.php?msg=deleted");
        } else {
            echo "Errore: " . $stmt->error;
        }

        break;
    }

    // Azione 7: Rimozione definitiva utente Tipo Tavolo dal sistema Ristorante
    case 'elimina_tavolo': {

        header('Content-Type: application/json');

        $id = intval($_POST['id_tavolo'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID tavolo non valido.']);
            exit;
        }

        // 1. Qui c'è un blocco multi-delete manuale in caso si decida di NON aver fatto ON DELETE CASCADE sulle keys del DB 
        // Viene fatta l'inner join pulendo forzatamente tutti i "dettaglio_ordini" riferiti al master order di quel tavolo prima del master object
        $conn->query("DELETE do FROM dettaglio_ordini do INNER JOIN ordini o ON do.id_ordine = o.id_ordine WHERE o.id_utente = $id");

        // 2. Viene poi eliminata per sicurezza la testata delle vecchie e odierne comande oramai svuotate dai detailed_order rows
        $conn->query("DELETE FROM ordini WHERE id_utente = $id");

        // 3. E finalizziamo eliminando finalmente colui che ha generato quegli ordini, la riga base di utenza.  
        $stmt = $conn->prepare("DELETE FROM utenti WHERE id_utente = ? AND ruolo='tavolo'");
        $stmt->bind_param("i", $id);

        echo json_encode($stmt->execute() ? ['success' => true] : ['success' => false, 'error' => 'Errore eliminazione.']);

        break;
    }

    // Azione 8: Polling di lettura e prelievo dati per ridisegnare la Griglia layout tavoli aggiornata
    case 'get_tavoli': {

        header('Content-Type: application/json');

        // Subquery complessa integrata nel Select:
        // COALESCE(...) si assicura che se nel DB il campo Null esiste, verrà convertito in un literal come da predefinito.
        // La SELECT annidata Conta quante Ordinazioni 'Non Pronte' sono attive per indicarlo graficamente. 
        $result = $conn->query("SELECT t.id_utente, t.username, t.password,
            COALESCE(t.stato, 'libero') as stato, COALESCE(t.posti, 4) as posti, t.id_menu,
            (SELECT COUNT(*) FROM ordini o WHERE o.id_utente = t.id_utente AND o.stato != 'pronto') as ordini_attivi
            FROM utenti t WHERE t.ruolo='tavolo' ORDER BY t.username ASC");

        echo json_encode($result ? $result->fetch_all(MYSQLI_ASSOC) : []);

        break;
    }

    // Azione 9: Overwrite o "Editazione" intero piatto in caso manager sbagliasse a digitare gli estremi
    case 'modifica_piatto': {

        if ($_SERVER["REQUEST_METHOD"] !== "POST")
            die("Accesso negato.");

        // Stesse elaborazioni e tipizzazione prese sul Case "aggiungi" 
        $idPiatto = intval($_POST['id_alimento']);
        $nomePiatto = $_POST['nome_piatto'] ?? '';
        $prezzo = floatval($_POST['prezzo'] ?? 0);
        $descrizione = $_POST['descrizione'] ?? '';
        $idCategoria = intval($_POST['id_categoria'] ?? 0);

        // Riallineamento allergeni
        $allergeni = empty($_POST['allergeni']) ? "" : implode(", ", $_POST['allergeni']);

        // Check Condizionale: il Manager ha caricato Nuova Immagine con questo Edit Request Form ?? 
        $aggiornaImmagine = isset($_FILES['immagine']) && $_FILES['immagine']['error'] === 0;

        if ($aggiornaImmagine) {
            // SI! Voleva cambiare foto. Leggiamo nuovo tmp_name file BLOB
            $immagineBinaria = file_get_contents($_FILES["immagine"]["tmp_name"]);

            // L'UPDATE includerà `immagine=?` 
            $stmt = $conn->prepare("UPDATE alimenti SET nome_piatto=?, prezzo=?, descrizione=?, id_categoria=?, lista_allergeni=?, immagine=? WHERE id_alimento=?");
            $stmt->bind_param("sdsissi", $nomePiatto, $prezzo, $descrizione, $idCategoria, $allergeni, $immagineBinaria, $idPiatto);
        } else {

            // NO! Il file input era vuoto, il manager voleva mantenere la vecchia foto. Fare UPDATE su tutto fuorché quel field Blob Image 
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

    // Azione 10: Rinomina parametrica del Tavolo tramite modale setting "tavolo" edit window
    case 'modifica_tavolo': {

        header('Content-Type: application/json');

        $id = intval($_POST['id_tavolo'] ?? 0);
        $nome = trim($_POST['nome_tavolo'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $posti = intval($_POST['posti'] ?? 4);
        $stato = trim($_POST['stato'] ?? 'libero');

        // Check validità
        if ($id <= 0 || empty($nome) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Dati incompleti.']);
            exit;
        }

        // Sovrascrivi
        $stmt = $conn->prepare("UPDATE utenti SET username=?, password=?, posti=?, stato=? WHERE id_utente=? AND ruolo='tavolo'");
        $stmt->bind_param("ssisi", $nome, $password, $posti, $stato, $id);

        echo json_encode($stmt->execute() ? ['success' => true] : ['success' => false, 'error' => 'Errore: ' . $stmt->error]);

        break;
    }

    // Azione 11: Scadenza e disimpegno programmatico manageriale per forzare checkout o logout
    case 'termina_sessione': {

        header('Content-Type: application/json');

        $id = intval($_POST['id_tavolo'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID tavolo non valido.']);
            exit;
        }

        // Il "Reset": Pone sessione di inizio ora ad Adesso, cambia status su Libero, CANCELLA il Security Device_Token per far disconnettere o refreshare forzatamente l'iPad di Sala
        $stmt = $conn->prepare("UPDATE utenti SET sessione_inizio = NOW(), stato = 'libero', device_token = NULL WHERE id_utente = ? AND ruolo='tavolo'");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        echo json_encode(['success' => true]);

        break;
    }

    default:
        die('Azione non valida.'); 
}
?>
