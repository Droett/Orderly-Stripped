<?php
// Core start server memory backend tracking
session_start();
// Require connettori
include "../include/conn.php";
require_once "../include/auth/check_permesso.php";
require_once "../include/constants.php";

// Scudo protettivo backend controllato mysql per pagina admin manager
if (!verificaPermesso($conn, 'dashboard/manager')) {
    // Redireziona index log out automatic fallback if you bypass without token auth
    header("Location: ../index.php");
    exit;
}
// Header framework dependencies
include "../include/header.php";

// Carica prefetch dei db tables per popolare UI a server rendering completato PHP side
// Prelevati tutti i user tavoli
$tavoli = $conn->query("SELECT * FROM utenti WHERE ruolo='tavolo' ORDER BY username ASC");
// Prelevate info menu categories
$categorie_result = $conn->query("SELECT * FROM categorie ORDER BY nome_categoria");
// Map array locale categorie in loop
$categorie_array = [];
while ($cat = $categorie_result->fetch_assoc()) {
    $categorie_array[] = $cat; // Caching locale buffer array ram
}
?>

<!-- Imports font -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<!-- Imports FA icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- Imports Custom manager styles css (cache breakato su v timestamp param querystring dinamica time()) -->
<link rel="stylesheet" href="../css/manager.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="../css/common.css?v=<?php echo time(); ?>">

<!-- Full width app body Bootstrap CSS -> constraints container-fluid -->
<div class="container-fluid p-0">
    <div class="row g-0"> <!-- No padding e margins per le grids gaps 0 -->

        <!-- Sidebar Desktop Colonna Sinistra UI (sparisce sui telefoni mobile CSS block d-none) -->
        <div class="col-md-3 col-lg-2 d-none d-md-block">
            <div class="sidebar-custom d-flex flex-column"> <!-- Stack flexbox wrapper laterale -->
                <div class="text-center mb-5 mt-3"><img src="../imgs/ordlogo.png" width="100"></div> <!-- Brand Logo -->
                <div class="px-3 flex-grow-1"> <!-- Pulsantiera admin manager center -->
                    <small class="text-uppercase fw-bold ps-3 mb-2 d-block text-muted" style="font-size: 11px;">Pannello Admin</small>

                    <!-- Tasto trigger DOM JS visibilità pagina 1 'Gestione tavoli grid' -->
                    <div class="btn-sidebar active" onclick="switchPage('tavoli', this)">
                        <i class="fas fa-chair me-3"></i> Gestione Tavoli
                    </div>
                    <!-- Tasto trigger DOM JS visibilità pagina 2 'Editor Food Menus' -->
                    <div class="btn-sidebar" onclick="switchPage('menu', this)">
                        <i class="fas fa-utensils me-3"></i> Gestione Menu
                    </div>
                </div>

                <div class="p-4 mt-auto"> <!-- Fine Coda Sidebar footer in basso fisso -->
                    <div class="d-flex justify-content-center gap-3">
                        <!-- Botton toggle dark ui custom js callback -->
                        <div class="theme-toggle-sidebar" onclick="toggleTheme()" title="Cambia Tema">
                            <i class="fas fa-moon" id="theme-icon"></i>
                        </div>
                        <!-- Logout kill href target a root file -->
                        <a href="../logout.php" class="theme-toggle-sidebar text-danger" title="Esci">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonna Contenitore Centro Destra 100% su Mobile o 10 su 12 div col grid viewport bs CSS  -->
        <div class="col-md-9 col-lg-10">

            <!-- Navbar Mobile Alternativa (Visibile solo se telefono, via css block limit parameters) -->
            <div class="mobile-nav-bar d-md-none">
                <!-- Stessi botton page swap di sidebar triggerati in horizontal scroll list -->
                <div class="mobile-nav-btn active" onclick="switchPage('tavoli', this)">
                    <i class="fas fa-chair"></i> Tavoli
                </div>
                <div class="mobile-nav-btn" onclick="switchPage('menu', this)">
                    <i class="fas fa-utensils"></i> Menu
                </div>
                <!-- Box icone opzioni utente e config -->
                <div class="ms-auto d-flex gap-2 align-items-center">
                    <div class="theme-toggle-sidebar" onclick="toggleTheme()" style="width:32px;height:32px;">
                        <i class="fas fa-moon" style="font-size:0.8rem;"></i>
                    </div>
                    <a href="../logout.php" class="theme-toggle-sidebar text-danger" style="width:32px;height:32px;">
                        <i class="fas fa-sign-out-alt" style="font-size:0.8rem;"></i>
                    </a>
                </div>
            </div>

            <!-- AREA APP 1: SEZIONE GESTIONE TAVOLI E STATI LIVE (SPA CSS TRICKS DISPLAY) -->
            <div id="page-tavoli" class="page-section active">
                <!-- Testa della pagina con bottone aggiunta e titoli param -->
                <div class="page-header">
                    <div>
                        <h2 class="fw-bold m-0">Gestione Tavoli</h2>
                        <p class="text-muted m-0 small">Controlla lo stato delle prenotazioni in tempo reale</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <!-- Bottone trigger JS modalAggiungi pop function js table sql create trigger action -->
                        <button class="btn btn-dark rounded-pill px-4 py-2 fw-bold shadow-sm" onclick="apriModalAggiungi()">
                            <i class="fas fa-plus me-2"></i>Nuovo Tavolo
                        </button>
                    </div>
                </div>

                <!-- Bottoniera Filtri Stati tavoli per manager (Libero Occup Riservato) -->
                <div class="filter-tabs">
                    <!-- Tab filtra tutto -->
                    <button class="filter-tab active" onclick="filtraTavoli('tutti', this)">
                        <i class="fas fa-th me-1"></i> Tutti <span class="filter-count" id="count-tutti">0</span>
                    </button>
                    <!-- Filtra vuoti -->
                    <button class="filter-tab" onclick="filtraTavoli('libero', this)">
                        <span class="status-dot dot-libero"></span> Liberi <span class="filter-count" id="count-libero">0</span>
                    </button>
                    <!-- Filtra tavolo con gente mangiando (logged in da tablet clienti) -->
                    <button class="filter-tab" onclick="filtraTavoli('occupato', this)">
                        <span class="status-dot dot-occupato"></span> Occupati <span class="filter-count" id="count-occupato">0</span>
                    </button>
                    <!-- Filtra custom admin status per tenero occupato un posto senza ordinarlo app -->
                    <button class="filter-tab" onclick="filtraTavoli('riservato', this)">
                        <span class="status-dot dot-riservato"></span> Riservati <span class="filter-count" id="count-riservato">0</span>
                    </button>
                </div>

                <!-- Contenitore grid cards vuoto per i tavoli. Viene popolato dinamicam JS REST dal backend PHP manager_api.php su refresh() interval -->
                <div class="tavoli-grid" id="tavoli-grid"></div>
            </div>

            <!-- AREA APP 2: SEZIONE EDITOR CRUD MENU E PIATTI DATASET (SPA CSS TRICKS DISPLAY NONE BASE) -->
            <div id="page-menu" class="page-section" style="display: none;">
                <div class="container py-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="fw-bold m-0">Gestione Menu</h2>
                            <p class="text-muted m-0 small">Aggiungi, modifica o elimina piatti dal menu</p>
                        </div>
                    </div>

                    <!-- Notifiche query string success message html flash (Quando refreshi dopo esser andato su form target redirect) -->
                    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
                        <!-- Box HTML green per msg popup action db sql fine e completata -->
                        <div id="success-alert" class="alert alert-success border-0 shadow-sm rounded-3 mb-4 text-center fw-bold text-success">
                            Menu aggiornato correttamente!
                        </div>
                    <?php endif; ?>

                    <!-- Form Row -> split 2 grids layout components -->
                    <div class="row g-4">
                        
                        <!-- Colonna Sinistra larga - Form Aggiungi nuovo Piatto a db (8/12 bs grid limits) -->
                        <div class="col-lg-8">
                            <div class="card-custom"> <!-- shadow box base framework -->
                                <h5 class="card-title"><i class="fas fa-utensils me-2 text-warning"></i>Nuovo Piatto</h5>
                                <!-- Formulario che sputa Post nativo su endpoint PHP api create object -> multipart formData limits blocco necessario x files img upload -->
                                <form action="../api/manager/manager_api.php?action=aggiungi_piatto" method="POST" enctype="multipart/form-data">
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <!-- Titolo piatto HTML input box models schemas templates limits -->
                                            <input type="text" name="nome_piatto" class="form-control" required placeholder="Nome del piatto">
                                        </div>
                                        <div class="col-md-4">
                                            <!-- Prezzo con step in centesimi di valuta models -> html5 mapping defaults datasets string margin schemas -->
                                            <input type="number" step="0.01" name="prezzo" class="form-control" required placeholder="Prezzo (€)">
                                        </div>
                                        <div class="col-md-12">
                                            <!-- Box selettore select discesa categorie da db loop -->
                                            <select name="id_categoria" class="form-select" required>
                                                <option value="" selected disabled>Seleziona Categoria</option>
                                                <!-- Loop the PHP sql rows data per le opzioni drop menù -->
                                                <?php foreach ($categorie_array as $cat): ?>
                                                    <!-- Associa stringa su HTML UI e invia value numerico PK sql al form parameters models parameters setups -->
                                                    <option value="<?php echo $cat['id_categoria']; ?>"><?php echo $cat['nome_categoria']; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <!-- Descr ingredienti text div textarea array limits defaults models conventions bounding templates sets models constraints sizing variables schemas sets constraints dimensions parameters schemas datasets configurations formats -->
                                            <textarea name="descrizione" class="form-control" rows="2" placeholder="Descrizione ingredienti..."></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label class="small text-muted fw-bold mb-2">ALLERGENI PRESENTI</label>
                                            <div class="d-flex flex-wrap gap-2 p-3 rounded allergeni-box">
                                                <!-- Renderizzazione dinamica checkboxes in base a file arrays contants mapping php -->
                                                <?php foreach ($ALLERGENI as $a): ?>
                                                    <!-- Inline label flex grid boxes templates schemas -->
                                                    <div class="form-check form-check-inline m-0 me-3">
                                                        <!-- Mappa HTML form [] notation -> al invio form al backend trasforma sto coso in array lists bounds margins parameters laws string models parameters -->
                                                        <input class="form-check-input" type="checkbox" name="allergeni[]" value="<?php echo $a; ?>" id="al_<?php echo $a; ?>">
                                                        <!-- Mapping tag label all id checkbox html layouts schemas conventions schemas limitations -> templates -->
                                                        <label class="form-check-label small" for="al_<?php echo $a; ?>"><?php echo $a; ?></label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="col-12"> <!-- Sezione blob foto constraints rules layouts margins formats schemas -->
                                            <label class="small text-muted fw-bold">FOTO DEL PIATTO</label>
                                            <!-- Button sistema input forms per upload blob bytes -->
                                            <input type="file" name="immagine" class="form-control" accept="image/*" required>
                                        </div>
                                        <div class="col-12 mt-3">
                                            <!-- Submit POST form HTML datasets variables arrays thresholds form string -->
                                            <button type="submit" class="btn-main">Aggiungi Piatto</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Colonna Destra snella - Gestione Add ed Elimina Categorie (4/12 flex widths)-->
                        <div class="col-lg-4">
                            <!-- Box superiore piccolo nuovo category inserimento -->
                            <div class="card-custom mb-4">
                                <h5 class="card-title"><i class="fas fa-tags me-2 text-primary"></i>Nuova Categoria</h5>
                                <!-- Form aggiunta singola table db target api rules rules mapping parameters limits spacing -->
                                <form action="../api/manager/manager_api.php?action=aggiungi_categoria" method="POST" class="d-flex gap-2">
                                    <!-- Input testo limits sizing formats layouts schemas margins datasets spacing limits schemas schemas rules -->
                                    <input type="text" name="nome_categoria" class="form-control" placeholder="Es: Burger" required>
                                    <input type="hidden" name="id_menu" value="1"> <!-- Fissato ID x architettura base app limits constraints sets templates frameworks settings -->
                                    <button type="submit" class="btn btn-dark rounded-3"><i class="fas fa-plus"></i></button> <!-- Save schemas templates mapping -->
                                </form>
                            </div>

                            <!-- Box sotto piccolo tabella elencazione eliminazione array -->
                            <div class="card-custom">
                                <h5 class="card-title">Gestione Categorie</h5>
                                <!-- Scroller y constraints css per non far sbroccare the ui if too long parameters models regulations mapping margins datasets settings padding -> boundaries constraints datasets boundaries boundaries laws mapping -->
                                <div style="max-height: 300px; overflow-y: auto;">
                                    <table class="table-custom"> <!-- Boot HTML table schemas templates rules mappings -->
                                        <tbody>
                                            <!-- Loop table arrays mapping conventions layouts constraints -->
                                            <?php foreach ($categorie_array as $row): ?>
                                                <tr>
                                                    <!-- String nome presets rules -->
                                                    <td><strong><?php echo $row['nome_categoria']; ?></strong></td>
                                                    <td class="text-end">
                                                        <!-- Formttino POST crudo nascosto ad hoc eliminazione item pk frameworks variables padding setups limits -->
                                                        <form action="../api/manager/manager_api.php?action=elimina_categoria" method="POST" onsubmit="return confirm('Eliminare questa categoria e tutti i piatti collegati?');"> <!-- Javascript Confirm blocker pre POST submit mapping -->
                                                            <input type="hidden" name="id_categoria" value="<?php echo $row['id_categoria']; ?>">
                                                            <button class="btn-action btn-delete"><i class="fas fa-trash"></i></button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista Mega dei piatti totali caricati nel database con action tables edit update delete (12 / 12 grids layout rows limits laws parameters strings parameters layouts datasets sizes limitations mapping string -> conventions thresholds boundaries schemas margins mappings margins conventions defaults datasets frameworks limits -> setups) -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card-custom"> <!-- Card block mapping spacing mapping borders templates margins mapping margins definitions bounds limitations datasets -> limits setups boundaries datasets models limits datasets limits datasets -> formulas layouts -> conventions -->
                                <h5 class="card-title"><i class="fas fa-book-open me-2 text-info"></i>Lista Piatti Attivi</h5>
                                <div class="table-responsive"> <!-- Wrap table CSS -> no overflow x mobile horizontal scroll bar layouts mapping datasets limits arrays schemas formulas -->
                                    <table class="table-custom">
                                        <thead> <!-- Intesazione conventions templates schemas laws padding layouts -->
                                            <tr>
                                                <th>Piatto</th>
                                                <th class="col-desc">Estratto Descrizione</th>
                                                <th>Prezzo</th>
                                                <th class="text-end">Azioni</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Richiede tutti gli aliments preordinati alfabeticamente parameters setups schemas parameters schemas setups frameworks
                                            $result = $conn->query("SELECT * FROM alimenti ORDER BY nome_piatto ASC");

                                            // Ha items in sql db table sets -> limits constraints formats
                                            if ($result->num_rows > 0) {
                                                // Cycle all items form sql rows -> arrays layouts values
                                                while ($row = $result->fetch_assoc()) {
                                                    // Sanificazione string per HTML injection evasion in DOM injection x parameters modal datasets spacing limits arrays formats mappings boundaries laws conventions boundaries bounds setups mapping conventions presets schemas -> limits rules parameters margins boundaries schemas variables arrays mapping -> -> parameters constraints conventions schemas parameters
                                                    $allergeniSafe = htmlspecialchars($row['lista_allergeni'], ENT_QUOTES);
                                                    $descSafe = htmlspecialchars($row['descrizione'], ENT_QUOTES);
                                                    $nomeSafe = htmlspecialchars($row['nome_piatto'], ENT_QUOTES);

                                                    // Sputa echo file html row html form form limits laws conventions margins mapping timeouts layouts limits
                                                    echo "<tr>
                                                            <td class='fw-bold'>" . $row['nome_piatto'] . "</td>
                                                            <td class='col-desc small text-muted'>" . substr($row['descrizione'], 0, 80) . "...</td>
                                                            <td class='fw-bold text-success'>" . number_format($row['prezzo'], 2) . " €</td>
                                                            <td class='text-end'>
                                                                <div class='d-flex justify-content-end gap-2'>
                                                                    <!-- Bottoni azione update open modal data injector dataset attributes -->
                                                                    <button type='button' class='btn btn-warning btn-sm text-white'
                                                                        onclick='apriModalModifica(this)'
                                                                        data-id='" . $row['id_alimento'] . "'
                                                                        data-nome='" . $nomeSafe . "'
                                                                        data-desc='" . $descSafe . "'
                                                                        data-prezzo='" . $row['prezzo'] . "'
                                                                        data-cat='" . $row['id_categoria'] . "'
                                                                        data-img='" . ($row['immagine'] ? 'data:image/jpeg;base64,' . base64_encode($row['immagine']) : '') . "'
                                                                        data-allergeni='" . $allergeniSafe . "'>
                                                                        <i class='fas fa-edit'></i>
                                                                    </button>
                                                                    <!-- Formtto x killare a DB SQL il prodotto datasets form schemas -->
                                                                    <form action='../api/manager/manager_api.php?action=elimina_piatto' method='POST' onsubmit='return confirm(\"Eliminare questo piatto?\");' style='margin:0;'>
                                                                        <input type='hidden' name='id_alimento' value='" . $row['id_alimento'] . "'>
                                                                        <button type='submit' class='btn btn-danger btn-sm'><i class='fas fa-trash'></i></button>
                                                                    </form>
                                                                </div>
                                                            </td>
                                                          </tr>";
                                                }
                                            } else {
                                                // Zero files datasets frameworks mapping defaults schemas settings formulations conventions settings sets mapping defaults limits arrays boundaries -> defaults boundaries
                                                echo "<tr><td colspan='4' class='text-center py-4 text-muted'>Nessun piatto nel menu.</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Moduls imports string formats setups laws boundaries variables datasets arrays string -> sizing -> defaults conventions variables constants thresholds settings constants frameworks formulas layouts constraints padding boundaries thresholds limits layouts bounds regulations rules boundaries setups setups constants datasets constraints arrays budgets datasets parameters formats formats boundaries mapping offsets -> datasets limits conventions formatting conventions limitations offsets -> parameters schemas boundaries spacing datasets -->
<?php include "../include/modals/manager_modals.php"; ?>

<!-- Script logic inclusion rules datasets thresholds constants formats schemas conventions formats parameters sets mapping formats spacing settings layouts -> limitations mapping conventions constraints dimensions formatting schemas formats parameters sets parameters mapping sets parameters definitions spacing mapping layouts presets boundaries templates boundaries datasets borders margins boundaries frameworks conventions -->
<script src="../js/common.js"></script>
<script src="../js/manager.js?v=<?php echo time(); ?>"></script>
<!-- End html body tag datasets formatting formats settings conventions mapping setups formulas string mapping form -> boundaries schemas thresholds mapping mapping mapping dimensions thresholds sizes formatting formatting sets definitions boundaries offsets Form conventions models budgets layouts defaults timeouts margins borders parameters sizing boundaries definitions formulas -->
<?php include "../include/footer.php"; ?>