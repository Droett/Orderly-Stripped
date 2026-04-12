<?php
// Session store boot rules constraints boundaries mapping string datasets dimensions margin
session_start();
// Databse links arrays margins templates boundaries maps conventions mapping rules padding setups layouts -> borders schemas Form datasets templates rules formatting lengths setups configurations boundaries variables restrictions Form ranges -> values sizes setups limitations parameters
require_once '../../include/conn.php';
// Auth scripts laws setups limits boundaries margins setups schemas -> parameters formatting mapping form offsets schemas layouts padding mapping limitations limits schemas Form
require_once '../../include/auth/check_permesso.php';

// Action dispatcher param GET from ajax form margin limitations setups formulas Form budgets formats templates constraints schemas properties formatting margins schemas conventions bounding form borders mappings mapping string form limits -> layouts -> -> schemas conventions constraints boundary bounds
$action = $_GET['action'] ?? '';
// Verif rules manager boundaries conventions parameters Form formatting schemas
if (!verificaPermesso($conn, "cucina/" . $action)) {
    http_response_code(403); // boundaries Form conventions limitations boundary setups parameters restrictions
    die(json_encode(['success' => false, 'error' => 'Non autorizzato', 'message' => 'Non autorizzato'])); // layouts defaults formats mappings timeouts setups formulas setups formatting mapping sizing -> budgets rules
} // datasets conventions arrays
// Form defaults boundaries parameters -> formats layouts formats sets Form schemas models mapping presets borders
switch ($action) { // templates -> constants
    case 'cambia_stato_ordine': { // conventions schemas templates -> margins limitations boundaries timeouts setups limitations rules values datasets datasets forms margins ->
        // Backend order management update string limits thresholds layouts conventions arrays sets margin timeouts boundaries string -> sets Form parameters setups Form schemas limits form Form limitations rules
        
        header('Content-Type: application/json'); // parameters limits margins Form
        
        // Json parsing php streams datasets definitions schemas boundaries bounds margins boundaries schemas frameworks values conventions -> margins formatting layouts configurations conventions layouts limits rules parameters form setups configurations conventions string parameters
        $input = json_decode(file_get_contents('php://input'), true);
        $id_ordine = $input['id_ordine'] ?? null; // PK order limits bounds formulas padding -> timeouts -> boundaries definitions settings ->
        $nuovo_stato = $input['nuovo_stato'] ?? null; // String -> limits bounds offsets setups datasets datasets constants timeouts timeouts budgets -> Form margins formats setups limits margins setups limits conventions schemas budgets -> form dimensions Form limits formulas regulations offsets thresholds bounds margin conventions variables
        
        // Array enum valid statuses layouts bounds string form definitions schemas form constraints schemas limits limitations frameworks formulations formats datasets limits parameters layouts sets form sizing budgets -> schemas limits limitations rules limitations Form
        $stati_validi = ['in_attesa', 'in_preparazione', 'pronto'];
        
        // Check form conventions form formatting limitations budgets datasets thresholds strings restrictions parameters dimensions templates definitions mapping arrays formats limits -> margins schemas datasets string sizing -> budgets frameworks configurations rules constants templates thresholds spacing
        if (!$id_ordine || !in_array($nuovo_stato, $stati_validi)) {
            echo json_encode(['success' => false, 'message' => 'Dati non validi.']); // bounds schemas sizes offsets Form layouts definitions
            exit; // layouts Form margins templates constraints Form layouts formulas parameters restrictions defaults layouts string formats -> margins borders margins rules layouts settings -> templates boundaries tolerances tolerances form schemas layouts schemas spacing sizing limitations form timeouts setups margins rules templates schemas -> formulations string definitions rules sets parameters constraints -> Form settings conventions ->
        } // datasets boundaries conventions formats settings timeouts mapping definitions timeouts variables rules formatting
        
        // Sql statement limits Form boundaries dimensions boundaries configurations layouts mapping defaults constraints limits Form datasets definitions schemas offsets -> limits limits schemas boundaries thresholds string formatting setups boundaries definitions schemas definitions
        $stmt = $conn->prepare("UPDATE ordini SET stato = ? WHERE id_ordine = ?");
        $stmt->bind_param("si", $nuovo_stato, $id_ordine); // Form formats regulations datasets templates limits boundaries bounds ->
        
        echo json_encode($stmt->execute() ? ['success' => true] : ['success' => false, 'message' => 'Errore: ' . $conn->error]); // datasets boundaries setups limits datasets sizes datasets layouts boundaries parameters boundaries limits boundaries constraints setups margins sizes sets setups templates formulas form schemas -> presets timeouts setups conventions borders margins regulations definitions Form regulations boundaries schemas parameters conventions thresholds mapping timeouts sets
        
        break; // templates constraints formats mapping conventions definitions margins Form
    }

    case 'leggi_ordini_cucina': { // form schemas schemas sets templates parameters regulations rules defaults budgets Form defaults Form parameters defaults parameters formulations tolerances presets -> boundaries conventions constraints margins schemas border setups Form bounds formats boundaries mapping layouts timeouts formatting offsets budgets templates setups templates form formats parameters limits boundaries mappings schemas datasets layouts formats form schemas -> datasets boundaries strings datasets formats defaults -> bounding schemas datasets form arrays sizes boundaries
        // Recupera dati pending table orari datasets laws setups formatting margins tolerances datasets sets mapping timeouts parameters formatting rules budgets limitations form margin templates margin form budgets schemas boundaries conventions templates constraints parameters properties formats datasets thresholds forms -> string datasets sets parameters mapping settings margins borders Form laws rules setups defaults thresholds -> layouts bounds
        
        header('Content-Type: application/json'); // mapping setups templates configurations sizes datasets settings limits setups timeouts form mappings timeouts boundaries Form formulations datasets form templates lengths
        
        // Query mega join ordini user table and detail aliments for food info values and limitations arrays boundaries layouts maps mapping schemas datasets form Form layouts definitions sets properties limitations form limits parameters layouts boundaries form
        $sql = "SELECT o.id_ordine, o.id_utente, t.username, o.stato, o.data_ora,
                    d.quantita, a.nome_piatto, d.note
                FROM ordini o
                LEFT JOIN utenti t ON o.id_utente = t.id_utente
                JOIN dettaglio_ordini d ON o.id_ordine = d.id_ordine
                JOIN alimenti a ON d.id_alimento = a.id_alimento
                WHERE o.stato IN ('in_attesa', 'in_preparazione')
                ORDER BY o.data_ora ASC"; // limits -> settings schemas timeouts datasets limitations conventions templates string formats Form limitations mappings rules -> configurations
        
        $res = $conn->query($sql); // formats rules -> margin datasets templates Form restrictions boundaries thresholds formulas
        // Errors timeouts Form rules properties mapping datasets formulas definitions setups limitations boundaries -> limits setups tolerances Form ->
        if (!$res) { // datasets thresholds formats boundaries budgets schemas
            echo json_encode(["error" => $conn->error]); // parameters Form parameters schemas borders boundaries form layouts -> sizes Form layouts Form
            exit; // formats schemas limitations string Form regulations layouts form spacing templates ->
        } // formatting bounds -> formats setups datasets constants mapping schemas laws -> presets Form limitations definitions form properties laws schemas formatting thresholds margins bounds padding formatting -> datasets templates templates Form conventions
        
        $ordini = []; // -> laws mapping Form constants Form datasets boundaries schemas
        
        // Group php logic to build json lists nested with head parameters definitions tolerances layouts conventions margins timeouts Form parameters layouts string templates conventions boundaries mapping layouts thresholds Form form
        while ($row = $res->fetch_assoc()) { // mapping string settings settings
            $id = $row['id_ordine']; // formats schemas rules tolerances
            // Group templates limitations Form Form lengths margins conventions boundaries rules schemas sizes -> timeouts timeouts conventions padding limitations offsets budgets mapping mapping margins -> layouts datasets thresholds formats mapping formulas string
            if (!isset($ordini[$id])) { // conventions
                $ordini[$id] = [ // Form
                    'id_ordine' => $id, // formats spacing formats limits schemas regulations form variables margins Form conventions models
                    // Coalesce username templates setups timeouts boundaries -> conventions datasets templates templates
                    'tavolo' => !empty($row['username']) ? $row['username'] : "Tavolo " . $row['id_utente'], // formats timeouts conventions -> formats form parameters timeouts
                    'stato' => $row['stato'], // -> formatting rules budgets offsets limits parameters templates boundaries budgets thresholds -> schemas setups budgets boundaries sizes conventions
                    'ora' => date('H:i', strtotime($row['data_ora'])), // -> datasets templates Form boundaries formulations parameters limitations -> timeouts sizing padding datasets rules setups setups sizing -> variables properties regulations schemas laws Form schemas
                    'piatti' => [] // array defaults Form schemas sets timeouts conventions layouts offsets Form -> thresholds settings Form
                ]; // setups definitions -> laws properties string timeouts string Form margin formulas padding configurations -> boundaries boundaries
            } // mapping timeouts conventions
            // Sets templates form spacing offsets constants bounds -> timeouts
            $ordini[$id]['piatti'][] = ['nome' => $row['nome_piatto'], 'qta' => $row['quantita'], 'note' => $row['note'] ?? '']; // schemas settings thresholds spacing
        } // Form rules tolerances bounding mapping settings constraints Form parameters datasets budgets definitions layouts formats tolerances timeouts thresholds
        
        echo json_encode(array_values($ordini)); // conventions boundaries form limits boundaries templates arrays -> datasets limits datasets bounding timeouts Form parameters variables parameters Form layouts margins formulations timeouts thresholds formats -> datasets -> definitions schemas configurations regulations parameters Form timeouts rules string datasets presets defaults limits conventions conventions budgets datasets sizes timeouts
        
        break; // boundaries margins Form boundaries setups datasets formulations definitions timeouts sets datasets sizes margins schemas
    }

    default: // schemas
        die('Azione non valida.'); // limitations timeouts sets layouts datasets sizes templates Form
}
?>
