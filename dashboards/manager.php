<?php
// ============================================================
// manager.php — Manager Dashboard
// ============================================================
// This is the page that the restaurant manager sees.
// It has two main sections (switched via the sidebar):
//   1. "Gestione Tavoli" — view and manage tables
//   2. "Gestione Menu"   — add, edit, or delete dishes and categories
//
// Most interactive features (adding/removing tables, editing dishes)
// are handled by manager.js via API calls to manager_api.php.
// ============================================================

// Start the PHP session so we can check who is logged in
session_start();

// Load the database connection — gives us the $conn variable
include "../include/conn.php";

// Load the permission checker and constants (e.g. list of allergens)
require_once "../include/auth/check_permesso.php";
require_once "../include/constants.php";

// --- SECURITY CHECK ---
// If the user is not logged in as a manager, redirect to the login page
if (!verificaPermesso($conn, 'dashboard/manager')) {
    header("Location: ../index.php");
    exit;
}

// Load the shared HTML <head> and opening <body> tag
include "../include/header.php";

// Fetch all tables (users with role "tavolo") sorted alphabetically by name
$tavoli = $conn->query("SELECT * FROM utenti WHERE ruolo='tavolo' ORDER BY username ASC");

// Fetch all menu categories, sorted alphabetically
$categorie_result = $conn->query("SELECT * FROM categorie ORDER BY nome_categoria");

// Convert the category result into a regular PHP array so we can loop through it
// multiple times in the HTML below (a MySQLi result can only be looped once)
$categorie_array = [];
while ($cat = $categorie_result->fetch_assoc()) {
    $categorie_array[] = $cat;
}
?>

<!-- Google Fonts: loads the "Poppins" font -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

<!-- Font Awesome: icon library (used for table icons, trash icons, etc.) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Page-specific styles for the manager view -->
<link rel="stylesheet" href="../css/manager.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="../css/common.css?v=<?php echo time(); ?>">


<!-- ============================================================
     MAIN LAYOUT: Two-column grid
     Left column  = sidebar with navigation buttons (desktop only)
     Right column = main content area (tables or menu management)
     ============================================================ -->
<div class="container-fluid p-0">
    <div class="row g-0">

        <!-- ====================================================
             LEFT SIDEBAR — navigation between sections
             Hidden on mobile screens (d-none d-md-block)
             ==================================================== -->
        <div class="col-md-3 col-lg-2 d-none d-md-block">
            <div class="sidebar-custom d-flex flex-column">

                <!-- Logo -->
                <div class="text-center mb-5 mt-3">
                    <img src="../imgs/ordlogo.png" width="100">
                </div>

                <!-- Navigation buttons — clicking calls switchPage() in manager.js -->
                <div class="px-3 flex-grow-1">
                    <small class="text-uppercase fw-bold ps-3 mb-2 d-block text-muted" style="font-size: 11px;">Pannello Admin</small>

                    <!-- "Tables" section button (active by default) -->
                    <div class="btn-sidebar active" onclick="switchPage('tavoli', this)">
                        <i class="fas fa-chair me-3"></i> Gestione Tavoli
                    </div>

                    <!-- "Menu" section button -->
                    <div class="btn-sidebar" onclick="switchPage('menu', this)">
                        <i class="fas fa-utensils me-3"></i> Gestione Menu
                    </div>
                </div>

                <!-- Bottom of sidebar: theme toggle + logout -->
                <div class="p-4 mt-auto">
                    <div class="d-flex justify-content-center gap-3">
                        <div class="theme-toggle-sidebar" onclick="toggleTheme()" title="Cambia Tema">
                            <i class="fas fa-moon" id="theme-icon"></i>
                        </div>
                        <a href="../logout.php" class="theme-toggle-sidebar text-danger" title="Esci">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>


        <!-- ====================================================
             RIGHT COLUMN — page content
             ==================================================== -->
        <div class="col-md-9 col-lg-10">

            <!-- Mobile navigation bar (replaces the sidebar on small screens) -->
            <div class="mobile-nav-bar d-md-none">
                <div class="mobile-nav-btn active" onclick="switchPage('tavoli', this)">
                    <i class="fas fa-chair"></i> Tavoli
                </div>
                <div class="mobile-nav-btn" onclick="switchPage('menu', this)">
                    <i class="fas fa-utensils"></i> Menu
                </div>
                <div class="ms-auto d-flex gap-2 align-items-center">
                    <div class="theme-toggle-sidebar" onclick="toggleTheme()" style="width:32px;height:32px;">
                        <i class="fas fa-moon" style="font-size:0.8rem;"></i>
                    </div>
                    <a href="../logout.php" class="theme-toggle-sidebar text-danger" style="width:32px;height:32px;">
                        <i class="fas fa-sign-out-alt" style="font-size:0.8rem;"></i>
                    </a>
                </div>
            </div>


            <!-- ================================================
                 SECTION 1: TABLES MANAGEMENT
                 Shown by default when the manager loads the page.
                 The table grid is populated by manager.js via API.
                 ================================================ -->
            <div id="page-tavoli" class="page-section active">

                <!-- Section header + "Add Table" button -->
                <div class="page-header">
                    <div>
                        <h2 class="fw-bold m-0">Gestione Tavoli</h2>
                        <p class="text-muted m-0 small">Controlla lo stato delle prenotazioni in tempo reale</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <!-- Opens a modal to create a new table -->
                        <button class="btn btn-dark rounded-pill px-4 py-2 fw-bold shadow-sm" onclick="apriModalAggiungi()">
                            <i class="fas fa-plus me-2"></i>Nuovo Tavolo
                        </button>
                    </div>
                </div>



                <!-- The table cards are injected here by manager.js -->
                <div class="tavoli-grid" id="tavoli-grid"></div>
            </div>


            <!-- ================================================
                 SECTION 2: MENU MANAGEMENT
                 Hidden by default; shown when "Gestione Menu" is clicked.
                 ================================================ -->
            <div id="page-menu" class="page-section" style="display: none;">
                <div class="container py-4">

                    <!-- Section header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="fw-bold m-0">Gestione Menu</h2>
                            <p class="text-muted m-0 small">Aggiungi, modifica o elimina piatti dal menu</p>
                        </div>
                    </div>

                    <!-- Success message (shown after a dish is added/edited successfully) -->
                    <!-- This is set by manager_api.php via a redirect with ?msg=success -->
                    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
                        <div id="success-alert" class="alert alert-success border-0 shadow-sm rounded-3 mb-4 text-center fw-bold text-success">
                            Menu aggiornato correttamente!
                        </div>
                    <?php endif; ?>


                    <div class="row g-4">

                        <!-- LEFT COLUMN: Add new dish form -->
                        <div class="col-lg-8">
                            <div class="card-custom">
                                <h5 class="card-title"><i class="fas fa-utensils me-2 text-warning"></i>Nuovo Piatto</h5>

                                <!-- This form submits to manager_api.php?action=aggiungi_piatto -->
                                <!-- enctype="multipart/form-data" is required when uploading a file -->
                                <form action="../api/manager/manager_api.php?action=aggiungi_piatto" method="POST" enctype="multipart/form-data">
                                    <div class="row g-3">

                                        <!-- Dish name input -->
                                        <div class="col-md-8">
                                            <input type="text" name="nome_piatto" class="form-control" required placeholder="Nome del piatto">
                                        </div>

                                        <!-- Price input (allows decimals like 9.50) -->
                                        <div class="col-md-4">
                                            <input type="number" step="0.01" name="prezzo" class="form-control" required placeholder="Prezzo (€)">
                                        </div>

                                        <!-- Category dropdown — populated from the database -->
                                        <div class="col-md-12">
                                            <select name="id_categoria" class="form-select" required>
                                                <option value="" selected disabled>Seleziona Categoria</option>
                                                <?php foreach ($categorie_array as $cat): ?>
                                                    <option value="<?php echo $cat['id_categoria']; ?>">
                                                        <?php echo $cat['nome_categoria']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Description / ingredients textarea -->
                                        <div class="col-12">
                                            <textarea name="descrizione" class="form-control" rows="2" placeholder="Descrizione ingredienti..."></textarea>
                                        </div>

                                        <!-- Allergen checkboxes — $ALLERGENI is defined in constants.php -->
                                        <div class="col-12">
                                            <label class="small text-muted fw-bold mb-2">ALLERGENI PRESENTI</label>
                                            <div class="d-flex flex-wrap gap-2 p-3 rounded allergeni-box">
                                                <?php foreach ($ALLERGENI as $a): ?>
                                                    <div class="form-check form-check-inline m-0 me-3">
                                                        <!-- name="allergeni[]" allows multiple checkboxes with the same name -->
                                                        <input class="form-check-input" type="checkbox" name="allergeni[]" value="<?php echo $a; ?>" id="al_<?php echo $a; ?>">
                                                        <label class="form-check-label small" for="al_<?php echo $a; ?>"><?php echo $a; ?></label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <!-- Photo upload — accepts any image file -->
                                        <div class="col-12">
                                            <label class="small text-muted fw-bold">FOTO DEL PIATTO</label>
                                            <input type="file" name="immagine" class="form-control" accept="image/*" required>
                                        </div>

                                        <!-- Submit button -->
                                        <div class="col-12 mt-3">
                                            <button type="submit" class="btn-main">Aggiungi Piatto</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>


                        <!-- RIGHT COLUMN: Add category + manage existing categories -->
                        <div class="col-lg-4">

                            <!-- Add new category form -->
                            <div class="card-custom mb-4">
                                <h5 class="card-title"><i class="fas fa-tags me-2 text-primary"></i>Nuova Categoria</h5>

                                <form action="../api/manager/manager_api.php?action=aggiungi_categoria" method="POST" class="d-flex gap-2">
                                    <input type="text" name="nome_categoria" class="form-control" placeholder="Es: Burger" required>
                                    <!-- Hidden field: always assigns the category to menu ID 1 -->
                                    <input type="hidden" name="id_menu" value="1">
                                    <button type="submit" class="btn btn-dark rounded-3"><i class="fas fa-plus"></i></button>
                                </form>
                            </div>

                            <!-- List of existing categories with delete buttons -->
                            <div class="card-custom">
                                <h5 class="card-title">Gestione Categorie</h5>

                                <div style="max-height: 300px; overflow-y: auto;">
                                    <table class="table-custom">
                                        <tbody>
                                            <?php foreach ($categorie_array as $row): ?>
                                                <tr>
                                                    <td><strong><?php echo $row['nome_categoria']; ?></strong></td>
                                                    <td class="text-end">
                                                        <!-- Delete form: submits to manager_api.php?action=elimina_categoria -->
                                                        <!-- onsubmit="return confirm(...)" shows a browser confirmation dialog before deleting -->
                                                        <form action="../api/manager/manager_api.php?action=elimina_categoria" method="POST" onsubmit="return confirm('Eliminare questa categoria e tutti i piatti collegati?');">
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


                    <!-- Full dish list table (all dishes currently in the menu) -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card-custom">
                                <h5 class="card-title"><i class="fas fa-book-open me-2 text-info"></i>Lista Piatti Attivi</h5>

                                <div class="table-responsive">
                                    <table class="table-custom">
                                        <thead>
                                            <tr>
                                                <th>Piatto</th>
                                                <th class="col-desc">Estratto Descrizione</th>
                                                <th>Prezzo</th>
                                                <th class="text-end">Azioni</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Load all dishes sorted alphabetically
                                            $result = $conn->query("SELECT * FROM alimenti ORDER BY nome_piatto ASC");

                                            if ($result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {

                                                    // htmlspecialchars() prevents XSS attacks by escaping HTML special characters
                                                    // ENT_QUOTES also escapes single quotes (needed for inline HTML attributes)
                                                    $allergeniSafe = htmlspecialchars($row['lista_allergeni'], ENT_QUOTES);
                                                    $descSafe      = htmlspecialchars($row['descrizione'], ENT_QUOTES);
                                                    $nomeSafe      = htmlspecialchars($row['nome_piatto'], ENT_QUOTES);

                                                    // Build each table row with edit + delete buttons
                                                    echo "<tr>
                                                            <td class='fw-bold'>" . $row['nome_piatto'] . "</td>
                                                            <td class='col-desc small text-muted'>" . substr($row['descrizione'], 0, 80) . "...</td>
                                                            <td class='fw-bold text-success'>" . number_format($row['prezzo'], 2) . " €</td>
                                                            <td class='text-end'>
                                                                <div class='d-flex justify-content-end gap-2'>

                                                                    <!-- Edit button: opens the edit modal in manager.js -->
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

                                                                    <!-- Delete form: asks for confirmation before deleting -->
                                                                    <form action='../api/manager/manager_api.php?action=elimina_piatto' method='POST' onsubmit='return confirm(\"Eliminare questo piatto?\");' style='margin:0;'>
                                                                        <input type='hidden' name='id_alimento' value='" . $row['id_alimento'] . "'>
                                                                        <button type='submit' class='btn btn-danger btn-sm'><i class='fas fa-trash'></i></button>
                                                                    </form>

                                                                </div>
                                                            </td>
                                                          </tr>";
                                                }
                                            } else {
                                                // No dishes in the menu yet
                                                echo "<tr><td colspan='4' class='text-center py-4 text-muted'>Nessun piatto nel menu.</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- END: dish list table -->

                </div>
            </div>
            <!-- END: menu section -->

        </div>
        <!-- END: right column -->

    </div>
</div>
<!-- END: main layout -->


<!-- Load manager modals (add table modal, edit dish modal, etc.) -->
<?php include "../include/modals/manager_modals.php"; ?>

<!-- Shared JS utilities (theme toggle, etc.) -->
<script src="../js/common.js"></script>

<!-- Main manager page logic: table grid, modals, API calls -->
<script src="../js/manager.js?v=<?php echo time(); ?>"></script>

<!-- Shared HTML footer (closing </body> and </html> tags) -->
<?php include "../include/footer.php"; ?>
