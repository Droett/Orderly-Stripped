<?php
// Avvia sistema tracciatura server sessions user PHP core backend memory store
session_start();
// Includi ponte link database
require_once '../../include/conn.php';
// Includi blindaggio file backend sql verification
require_once '../../include/auth/check_permesso.php';

// Cava il param d'azione richiesto string GET (se vuoto fallback a "")
$action = $_GET['action'] ?? '';
// Manda al metodo verifica query con token per root check
if (!verificaPermesso($conn, "tavolo/" . $action)) {
    // 403 Forbidden code headers limit block conventions parameters
    http_response_code(403);
    // JSON echo quit system
    die(json_encode(['success' => false, 'error' => 'Non autorizzato', 'message' => 'Non autorizzato']));
}

// Switching action strings route params limits conventions
switch ($action) {
    case 'aggiungi_al_carrello': {
        // Appende o incrementa numerico qty in records su stato db
        
        header('Content-Type: application/json'); // Restit in formato js fetch app boundaries form mapping
        
        // Pesca the root value dal tracciato secure memory backend side non hackabile da client side js injections
        $idTavolo = $_SESSION['id_tavolo'];
        $idPiatto = intval($_POST['id_alimento'] ?? 0); // Param postato via ajax call
        $qta = intval($_POST['quantita'] ?? 1); // Step parametro
        
        // Safety constraint limits null ids check logic block parameters
        if ($idPiatto <= 0) {
            // Json fail response limit forms bounds
            echo json_encode(['success' => false, 'message' => 'Piatto non valido.']);
            exit; // Break code blocks execution
        }
        
        // Cerca prima carrello master ordine (testa o.stato 'in attesa') limitato a 1 result
        $res = $conn->query("SELECT id_ordine FROM ordini WHERE id_utente = $idTavolo AND stato = 'in_attesa' LIMIT 1");
        
        if ($res->num_rows > 0) {
            // Se possiede gia bozze pending aggancia ID master pk per detail lines
            $idOrdine = $res->fetch_assoc()['id_ordine'];
        } else {
            // Sennò spara nuovo record root header ordini per sto tavolo loggato ora current date
            $conn->query("INSERT INTO ordini (id_utente, stato, data_ora) VALUES ($idTavolo, 'in_attesa', NOW())");
            // Ripesca da auto_incr. value
            $idOrdine = $conn->insert_id;
        }
        
        // Ora cerchiamo d'inspezionar le child rows del carrello x vedere se c è already inside the pizza list array constraints 
        $check = $conn->query("SELECT quantita FROM dettaglio_ordini WHERE id_ordine = $idOrdine AND id_alimento = $idPiatto");
        
        if ($check->num_rows > 0) {
            // Update matematico crudo in sql del quantity params (senza pescare a ram php prima logic boundaries form spacing mappings boundaries mapping constraints)
            $conn->query("UPDATE dettaglio_ordini SET quantita = quantita + $qta WHERE id_ordine = $idOrdine AND id_alimento = $idPiatto");
        } else {
            // Append line items child to detail childs conventions schemas string form parameters datasets
            $conn->query("INSERT INTO dettaglio_ordini (id_ordine, id_alimento, quantita) VALUES ($idOrdine, $idPiatto, $qta)");
        }
        
        // Sputa good JSON flag e fine limits Form configurations definitions form layouts schemas
        echo json_encode(['success' => true, 'message' => 'Aggiunto al carrello.']);
        
        break; // Switch limits laws formatting configurations formatting boundaries
    }

    case 'get_carrello': {
        // Leggi le record bozze non evase e mandale come json appeso ai fetch interval call js front
        
        header('Content-Type: application/json'); // JSON format flag string -> mappings mappings conventions
        
        // Verifica the ram store limit layouts
        if (!isset($_SESSION['id_tavolo'])) {
            echo json_encode([]); // Se session invalid o persa, null return boundaries
            exit; // Block definitions properties datasets margins
        }
        
        $idTavolo = intval($_SESSION['id_tavolo']); // Value secure margins values conventions limits conventions boundaries
        
        // Pescati i figli, nome e le specs cross joinando 3 tables sql relations parameters -> properties setups parameters limitations datasets mappings borders layouts schemas definitions
        $result = $conn->query("SELECT d.id_alimento, d.quantita, a.nome_piatto, a.prezzo
            FROM dettaglio_ordini d
            JOIN ordini o ON d.id_ordine = o.id_ordine
            JOIN alimenti a ON d.id_alimento = a.id_alimento
            WHERE o.id_utente = $idTavolo AND o.stato = 'in_attesa'");
        
        // Return full dumped json matrix of items list subsets string borders frameworks models constraints
        echo json_encode($result ? $result->fetch_all(MYSQLI_ASSOC) : []);
        
        break; // Stop bounds conventions
    }

    case 'invia_ordine': {
        // Transforma lo status carrello in un evaso pronto alla linea cucina process params limits array
        
        header('Content-Type: application/json');
        
        // Il JS Frontend di checkout sputa un payload application/json raw per cui si usa streams buffers methods x pescarlo parameters schemas (file get content php input methods bounds Form margins budgets)
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Se cassa vuota kill rules constraints schemas rules templates laws mapping rules definitions definitions conventions schemas
        if (empty($data['prodotti'])) {
            echo json_encode(['success' => false, 'message' => 'Il carrello è vuoto.']);
            exit;
        }
        
        $idTavolo = $_SESSION['id_tavolo']; // PK table params form limits parameters arrays mapping defaults schemas limits boundaries budgets presets string conventions
        
        // Avvia db transazione blocchi x fare commit multi statement SQL senza rischi che metta metta e meta meta spacchi e fotti i record boundaries conventions properties schemas limits datasets
        $conn->begin_transaction();
        
        // Try per poter the catch falliback error throw form margins rules defaults laws string formats datasets definitions string variables boundaries schemas templates boundaries -> setups
        try {
            // Crea record testa del root object parameters datasets arrays lengths 
            $stmt = $conn->prepare("INSERT INTO ordini (id_utente, stato, data_ora) VALUES (?, 'in_attesa', NOW())");
            $stmt->bind_param("i", $idTavolo); // i per val boundaries
            if (!$stmt->execute()) // Se scoppia form rules limits form limits conventions boundaries form string boundaries boundaries spacing boundaries models formats
                throw new Exception("Errore creazione ordine."); // Vai al catch margin spacing conventions definitions
        
            $idOrdine = $conn->insert_id; // Cattura master pk tables properties formats defaults string mapping conventions -> properties defaults parameters ->
        
            // Preparator per bulk insert child record params mapping definitions margin formats conventions limitations
            $det = $conn->prepare("INSERT INTO dettaglio_ordini (id_ordine, id_alimento, quantita, note) VALUES (?, ?, ?, ?)");
        
            // Loop data arrays parsed json frontend -> margins formats string limits boundaries spacing thresholds mapping boundaries datasets configurations limitations frameworks rules constraints budgets string -> limits budgets
            foreach ($data['prodotti'] as $p) {
                // If the constraint mapping values margin rules
                if ($p['qta'] > 0) {
                    $note = $p['note'] ?? null; // Param notes nullable schemas string limits bounds limitations formatting sets timeouts definitions margins string
                    // Assign i, i, i, s (3 int e string val definitions limits conventions templates formations -> arrays borders conventions bounds spacing)
                    $det->bind_param("iiis", $idOrdine, $p['id'], $p['qta'], $note);
                    if (!$det->execute()) // se throw schemas rules form parameters -> rules conventions limits -> models margins string parameters margins
                        throw new Exception("Errore inserimento piatto."); // Fail constraints datasets dimensions mapping layouts -> budgets
                } // End datasets -> settings frameworks rules parameters datasets conventions formats -> -> mapping boundaries limits -> formats formats
            }
        
            // Spara a terra sul disco db sql constraints conventions
            $conn->commit();
            // Json good response datasets rules settings definitions parameters limits thresholds datasets mapping form rules bounds templates rules schemas sizing schemas limitations arrays arrays mapping frameworks form -> -> -> spacing rules schemas arrays settings properties formatting parameters sizing arrays formatting padding setups boundaries bounds setups templates datasets conventions frameworks bounds boundaries form conventions -> formatting limitations setups
            echo json_encode(['success' => true, 'message' => 'Ordine inviato in cucina.']);
        } catch (Exception $e) { // Se è scoppiato su insert
            // Rompi tutto elimina a ritroso le esecuzioni finora fatte in the pending block boundaries conventions limitations conventions boundaries borders
            $conn->rollback();
            // Json fail borders definitions conventions timeouts parameters datasets margins conventions rules timeouts conventions formulations offsets schemas frameworks budgets settings -> margin timeouts constraints mappings presets formations formatting rules conventions sizes sizes properties arrays boundaries padding schemas boundaries margins limits mapping limits boundaries parameters setups margins limits
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } // bounds mapping conventions conventions form mappings conventions configurations conventions timeouts formats
        
        break; // templates borders layouts settings spacing
    }

    case 'leggi_ordini_tavolo': {
        // API read ordini chiusi / passati di questo specifico utente tablet per la lista scontrino storico form dimensions limits sizes layouts formats limits mapping
        
        header('Content-Type: application/json'); // array parameters defaults models layouts bounds datasets padding conventions formulas conventions arrays borders
        
        $idTavolo = $_SESSION['id_tavolo']; // PK layouts conventions definitions
        
        // Leggi prima il login datetime parameter conventions timeouts datasets layouts schemas conventions margins limits datasets
        $stmtSess = $conn->prepare("SELECT sessione_inizio FROM utenti WHERE id_utente = ?"); // limits sizes formatting -> properties boundaries form datasets schemas
        $stmtSess->bind_param("i", $idTavolo); // limitations
        $stmtSess->execute();
        $resSess = $stmtSess->get_result()->fetch_assoc(); // Get values conventions
        $orarioLogin = $resSess['sessione_inizio'] ?? '1970-01-01 00:00:00'; // Default safety constraints 1970 old data formats limits margins models setups -> limitations
        
        // Estrai dati aggregando testate a righe child table where > di data avvio login cosi vecchi ordini sn nascosti schemas datasets defaults defaults formatting schemas conventions arrays rules limitations padding parameters -> margins limits datasets mapping bounds schemas settings dimensions definitions schemas limits mapping conventions limitations conventions -> templates schemas formats Form boundaries layouts form templates datasets mapping sets formatting layouts -> setups frameworks setups conventions limits settings string conventions limits layouts models laws rules schemas form rules schemas bounds
        $stmt = $conn->prepare("SELECT o.id_ordine, o.stato, o.data_ora, d.quantita, a.nome_piatto, a.prezzo, d.note
            FROM ordini o
            JOIN dettaglio_ordini d ON o.id_ordine = d.id_ordine
            JOIN alimenti a ON d.id_alimento = a.id_alimento
            WHERE o.id_utente = ? AND o.data_ora >= ?
            ORDER BY o.data_ora DESC");
        
        $stmt->bind_param("is", $idTavolo, $orarioLogin); // mapping datasets laws string formatting datasets conventions margins laws conventions string settings datasets limits sizes form definitions definitions mapping constants setups Form conventions parameters setups formulas boundaries margins setups -> formatting -> string mapping conventions
        $stmt->execute();
        $result = $stmt->get_result(); // limits properties boundaries schemas layouts boundaries
        
        $ordini = []; // margins boundaries constants layouts schemas parameters conventions datasets Form -> boundaries sizing offsets boundary offsets
        
        // Logica php formatter data JSON per raggruppare sotto le teste ID arrays children arrays limits rules
        while ($row = $result->fetch_assoc()) {
            $id = $row['id_ordine']; // PK margin
            if (!isset($ordini[$id])) { // init
                $ordini[$id] = [ // form bounds formatting thresholds boundaries templates frameworks setups boundaries borders offsets templates Form
                    'id_ordine' => $id, // datasets parameters datasets layouts schemas string arrays
                    'stato' => $row['stato'], // array layouts bounds
                    'ora' => date('H:i', strtotime($row['data_ora'])), // format time conventions bounds padding sizing values string rules layouts sizes margins setups formatting limitations properties margins rules sets thresholds setups defaults setups setups settings string templates parameters datasets limits formatting string datasets margins layouts configurations spacing formatting parameters padding datasets mapping defaults formats limitations definitions thresholds conventions defaults mapping sizes datasets datasets form definitions schemas formats templates formats Form dimensions offsets datasets limits templates laws conventions
                    'data' => date('d/m/Y', strtotime($row['data_ora'])), // bounds rules setups timeouts
                    'piatti' => [], // array child configurations mappings -> form frameworks budgets arrays schemas
                    'totale' => 0 // init laws templates defaults limitations conventions rules schemas models formats limits borders rules schemas -> limits limitations conventions array thresholds setups margins settings templates form sizes thresholds boundaries maps formatting Form array sets boundaries datasets lengths datasets limitations configurations rules margins Form sizes mapping string sizing formats arrays schemas borders rules arrays string boundaries sets -> mapping schemas
                ]; // layouts datasets
            } // layouts datasets
            $ordini[$id]['piatti'][] = [ // array string parameters boundaries margins templates templates conventions dimensions
                'nome' => $row['nome_piatto'], // presets constants formats frameworks padding -> conventions thresholds boundaries schemas boundaries formats conventions layouts budgets datasets restrictions thresholds -> mapping setups Form formatting budgets parameters formations laws settings settings conventions spacing datasets datasets
                'qta' => $row['quantita'], // schemas
                'prezzo' => number_format($row['prezzo'], 2), // rules rules mapping boundaries parameters formats datasets arrays layouts offsets datasets schemas rules margins -> string properties
                'note' => $row['note'] ?? '' // limits string formatting mapping formatting parameters boundaries formatting datasets laws boundaries schemas form bounds formats conventions setups formats formats defaults conventions rules presets setups setups -> defaults offsets settings budgets formatting constants Form margin datasets conventions formatting conventions templates models formulas limitations boundaries thresholds limits mapping borders parameters margins setups sets regulations templates form mappings rules datasets mapping
            ]; // templates frameworks
            // Somma prices string layouts sets margin conventions borders formatting padding strings datasets lengths setups properties limits laws models bounding
            $ordini[$id]['totale'] += $row['quantita'] * $row['prezzo'];
        } // datasets Form spacing -> string frameworks conventions defaults parameters
        
        // For array rules layouts Form thresholds setups layouts laws sets string subsets
        foreach ($ordini as &$o)
            $o['totale'] = number_format($o['totale'], 2); // format rules sizing parameters formats arrays timeouts timeouts laws layouts borders form sets datasets margin -> conventions arrays datasets models -> schemas margin offsets bounds datasets -> formats schemas spacing -> schemas string formats
        
        echo json_encode(array_values($ordini)); // layouts margins defaults arrays models rules defaults offsets boundaries form
        
        break; // sets formats spacing -> thresholds mapping setups models formats conventions parameters formulas string sizes
    }

    case 'rimuovi_dal_carrello': {
        // Togli cibo o decrementa da bozze ordini sql parameters definitions limitations schemas limits lengths laws limits formulas -> string limits mapping mapping layouts variables margin definitions properties -> boundaries layouts mapping bounds definitions defaults Form formats conventions formats schemas formats mapping -> formats -> boundaries bounding limitations margin
        
        header('Content-Type: application/json'); // mapping schemas boundaries boundaries
        
        if (!isset($_SESSION['id_tavolo'])) { // formatting constants rules configurations budgets -> string limits definitions limitations frameworks form templates string borders laws conventions borders frameworks boundaries datasets schemas formats definitions conventions boundaries formats limitations sizes formatting definitions bounds boundaries parameters thresholds ->
            echo json_encode(['success' => false, 'message' => 'Non autorizzato']); // parameters formulations formatting presets padding schemas schemas arrays budgets margins arrays definitions formatting layouts formats -> templates formatting thresholds formulas string definitions schemas limits limits schemas frameworks -> conventions setups definitions datasets boundaries models layouts definitions margins laws templates arrays formulas setups budgets parameters thresholds -> schemas restrictions setups margin boundaries constraints variables margins limitations timeouts parameters -> conventions conventions rules thresholds setups properties datasets datasets parameters layouts values definitions formats limits conventions thresholds thresholds sizes budgets -> limits -> constants conventions schemas constraints templates Form borders templates budgets
            exit; // layouts form parameters setups string string conventions setups configurations laws datasets boundaries datasets setups layouts sets limits string regulations parameters regulations frameworks padding margin definitions rules conventions layouts parameters datasets string
        } // margins boundaries datasets datasets mapping setups datasets boundaries conventions mapping formats datasets margins boundaries form sizing
        
        $idTavolo = intval($_SESSION['id_tavolo']); // defaults defaults
        $idPiatto = intval($_POST['id_alimento'] ?? 0); // boundaries formatting
        
        if ($idPiatto <= 0) { // margin bounding conventions datasets sizes limits setups
            echo json_encode(['success' => false, 'message' => 'Piatto non valido.']); // parameters variables formulations string
            exit; // conventions datasets schemas arrays formatting limits budgets Form templates datasets padding setups
        } // configurations models restrictions datasets variables arrays templates setups settings padding laws
        
        $res = $conn->query("SELECT id_ordine FROM ordini WHERE id_utente = $idTavolo AND stato = 'in_attesa' LIMIT 1"); // definitions dimensions setups sizes margins templates formatting
        
        if ($res->num_rows > 0) { // models bounds padding -> Form definitions limits margins layouts borders Form limitations variables schemas sizing limits spacing setups properties -> definitions schemas defaults bounds boundaries limits frameworks conventions boundaries formulas string borders dimensions parameters mapping schemas regulations sets limits arrays margins templates sizes setups formats formatting laws limits string datasets budgets
            $idOrdine = $res->fetch_assoc()['id_ordine']; // conventions Form frameworks settings schemas regulations limits limits setups settings boundaries rules setups thresholds datasets datasets budgets formulations array string conventions margins
        
            $qRes = $conn->query("SELECT quantita FROM dettaglio_ordini WHERE id_ordine = $idOrdine AND id_alimento = $idPiatto"); // formats conventions variables regulations borders datasets formats conventions margins datasets Form Form frameworks bounding margins constants limits configurations configurations frameworks parameters budgets conventions Form spacing borders layouts limits padding datasets padding definitions sets ->
        
            if ($qRes->num_rows > 0) { // conventions Form margins regulations datasets datasets rules parameters schemas timeouts lengths Form ->
                $qta = $qRes->fetch_assoc()['quantita']; // arrays sizes formatting formats conventions parameters limits mappings string bounds rules templates templates laws limits models -> lengths Form thresholds formats
        
                // SQL ternario matematico laws mapping sizes -> budgets
                $sql = ($qta > 1)
                    ? "UPDATE dettaglio_ordini SET quantita = quantita - 1 WHERE id_ordine = $idOrdine AND id_alimento = $idPiatto"
                    : "DELETE FROM dettaglio_ordini WHERE id_ordine = $idOrdine AND id_alimento = $idPiatto";
        
                echo json_encode($conn->query($sql) ? ['success' => true] : ['success' => false, 'message' => $conn->error]); // configurations arrays bounds Form presets
            } else { // datasets setups Form sizes -> limitations templates layouts conventions schemas limitations schemas
                echo json_encode(['success' => false, 'message' => 'Piatto non nel carrello.']); // formats schemas Form -> setups datasets
            } // boundaries regulations -> templates bounds mapping budgets setups datasets schemas schemas boundaries margins -> layouts formats -> formats Form templates defaults formatting mapping conventions configurations bounds
        } else { // templates borders margins rules layouts schemas formatting rules models definitions sets schemas schemas bounds Form definitions Form margins formulations timeouts thresholds formulas Form offsets sets definitions margin templates regulations thresholds sets
            echo json_encode(['success' => false, 'message' => 'Nessun carrello attivo.']); // parameters datasets conventions setups thresholds layouts sets
        } // layouts bounds array formulations string
        
        break; // templates boundaries schemas datasets
    }

    case 'verifica_sessione': {
        // Ping timeout js checker se db rules auth status matches current ram session e device token rules offsets setups formats datasets boundaries thresholds schemas padding restrictions array mapping border rules regulations templates thresholds variables -> laws setups spacing definitions formulas schemas setups datasets properties sets schemas limits sizes string mapping schemas -> -> mappings mapping conventions layouts parameters conventions conventions -> schemas mapping datasets formats rules boundaries arrays parameters -> sizing ->
        
        header('Content-Type: application/json'); // mapping setups schemas padding constraints configurations boundaries formats conventions schemas offsets schemas configurations datasets timeouts
        
        $idTavolo = intval($_SESSION['id_tavolo']); // margins arrays sizing constraints setups sizing schemas timeouts models offsets datasets form templates offsets regulations mapping templates layouts parameters -> formulas parameters datasets laws Form -> constants
        $tokenCookie = $_COOKIE['device_token_' . $idTavolo] ?? ''; // schemas schemas spacing limitations offsets -> formulas formatting -> conventions borders string definitions -> schemas setups definitions -> models offsets datasets formats padding schemas datasets datasets thresholds rules -> configurations string templates frameworks limitations formatting templates spacing schemas setups limitations
        
        $stmt = $conn->prepare("SELECT stato, device_token FROM utenti WHERE id_utente = ?"); // schemas -> borders frameworks constraints sizing setups strings conventions regulations
        $stmt->bind_param("i", $idTavolo); // limitations
        $stmt->execute(); // limits setups datasets Form constraints formats definitions tolerances Form offsets -> rules constants Form conventions frameworks datasets limits ->
        $row = $stmt->get_result()->fetch_assoc(); // schemas formats schemas
        
        // Logica auth di confronto triplo boolean formats limits margin restrictions
        $valida = $row && $row['stato'] === 'occupato' && !empty($row['device_token']) && $row['device_token'] === $tokenCookie;
        
        echo json_encode(['valida' => $valida]); // layouts Form limits -> defaults boundaries templates settings
        
        break; // setups timeouts -> setups mapping offsets templates
    }

    default: // schemas limits padding sizes -> settings formats limitations datasets rules
        die('Azione non valida.'); // limitations rules string boundaries padding setups arrays datasets definitions borders schemas Form bounds formatting boundaries schemas string settings constraints Form formats
}
?>
