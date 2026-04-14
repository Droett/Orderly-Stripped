<?php
// ============================================================
// tavolo.php — Table Dashboard (Customer-facing menu page)
// ============================================================
// This is the page that customers at a table see.
// It shows the full menu, a search bar, a cart, and order history.
//
// All the interactive behaviour (add to cart, filter dishes, etc.)
// is handled by tavolo.js — this file just sets up the HTML structure
// and loads the data from the database.
// ============================================================

// Start the PHP session so we can read who is logged in
session_start();

// Load the database connection — gives us the $conn variable
include "../include/conn.php";

// Load the permission checker function
require_once "../include/auth/check_permesso.php";

// --- SECURITY CHECK ---
// If the user is not logged in as a table, redirect them to the login page
if (!verificaPermesso($conn, 'dashboard/tavolo')) {
    header("Location: ../index.php");
    exit;
}

// Load the shared HTML <head> and opening <body> tag
include "../include/header.php";

// Fetch all menu categories from the database (e.g. Starters, Pasta, Desserts)
$categorie = $conn->query("SELECT * FROM categorie");

// Fetch all dishes (products) from the database
$prodotti = $conn->query("SELECT * FROM alimenti");
?>

<!-- Google Fonts: loads the "Poppins" font used throughout the UI -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

<!-- Font Awesome: icon library (used for cart icon, search icon, etc.) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Page-specific styles for the table view -->
<!-- "?v=..." adds a timestamp to force the browser to reload the CSS on every page load -->
<link rel="stylesheet" href="../css/tavolo.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="../css/common.css?v=<?php echo time(); ?>">


<!-- ============================================================
     MAIN LAYOUT: Two-column grid
     Left column  = sidebar with category filters (desktop only)
     Right column = menu items + top search bar
     ============================================================ -->
<div class="container-fluid p-0">
    <div class="row g-0">

        <!-- ====================================================
             LEFT SIDEBAR — category filter buttons
             Hidden on mobile (d-none d-md-block = show on medium+ screens)
             ==================================================== -->
        <div class="col-md-3 col-lg-2 d-none d-md-block">
            <div class="sidebar-custom d-flex flex-column">

                <!-- Logo -->
                <div class="text-center mb-5 mt-3">
                    <img src="../imgs/ordlogo.png" width="100">
                </div>

                <!-- Category list — clicking a button filters the menu items -->
                <div class="px-3 flex-grow-1 overflow-auto">
                    <small class="text-uppercase fw-bold ps-3 mb-2 d-block text-muted" style="font-size: 11px;">Esplora il Menu</small>

                    <!-- "All" button — shows every dish regardless of category -->
                    <div class="btn-categoria active" onclick="filtraCategoria('all', this)">
                        <i class="fas fa-utensils me-3"></i> Tutto
                    </div>

                    <!-- Loop through each category from the database and create a filter button -->
                    <?php while ($cat = $categorie->fetch_assoc()): ?>
                        <div class="btn-categoria" onclick="filtraCategoria(<?php echo $cat['id_categoria']; ?>, this)">
                            <i class="fas fa-bookmark me-3"></i> <?php echo $cat['nome_categoria']; ?>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Bottom of sidebar: theme toggle + logout -->
                <div class="p-4 mt-auto">
                    <div class="d-flex justify-content-center gap-3">

                        <!-- Dark/light mode toggle button -->
                        <div class="theme-toggle-sidebar" onclick="toggleTheme()" title="Cambia Tema">
                            <i class="fas fa-moon" id="theme-icon"></i>
                        </div>

                        <!-- Logout link — sends the user back to the login page -->
                        <a href="../logout.php" class="theme-toggle-sidebar text-danger" title="Chiudi Sessione">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>


        <!-- ====================================================
             RIGHT COLUMN — search bar + menu grid
             ==================================================== -->
        <div class="col-md-9 col-lg-10">

            <!-- TOP BAR: search input, estimated bill, and action buttons -->
            <div class="sticky-header d-flex justify-content-between align-items-center">

                <!-- Search box — typing here calls renderProdotti() in tavolo.js to filter dishes -->
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="search-bar" class="search-input" placeholder="Cerca un piatto..." oninput="renderProdotti()">
                </div>

                <!-- Right side of the top bar: bill estimate, buttons -->
                <div class="d-flex align-items-center justify-content-end gap-2">

                    <!-- Estimated bill display (updated by JavaScript as items are added/removed) -->
                    <div class="d-none d-sm-flex align-items-center me-2 bg-surface rounded-pill px-3 py-2 border shadow-sm">
                        <small class="text-uppercase fw-bold text-muted me-2" style="font-size: 10px;">Conto Stimato</small>
                        <div class="fw-bold fs-5 text-price price-stable">
                            <span id="soldi-header">0.00</span>€
                        </div>
                    </div>

                    <!-- Button: open the order history panel -->
                    <button class="btn btn-dark rounded-pill px-3 py-2 px-md-4 py-md-3 shadow-sm d-flex align-items-center" onclick="apriStorico()">
                        <i class="fas fa-receipt"></i>
                        <span class="d-none d-lg-inline fw-bold ms-2">Storico Ordini</span>
                    </button>

                    <!-- Button: open the allergen/filter modal -->
                    <button class="btn btn-dark rounded-pill px-3 py-2 px-md-4 py-md-3 shadow-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#modalFiltri">
                        <i class="fas fa-filter"></i>
                        <span class="d-none d-lg-inline fw-bold ms-2">Filtra</span>
                    </button>

                    <!-- Button: open the cart modal -->
                    <button class="btn btn-dark rounded-pill px-3 py-2 px-md-4 py-md-3 shadow-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#modalCarrello" onclick="aggiornaModale()">
                        <i class="fas fa-shopping-bag fa-lg"></i>
                        <span class="d-none d-lg-inline fw-bold ms-2">Carrello</span>
                        <!-- Cart item count badge (updated by JavaScript) -->
                        <span id="pezzi-header" class="ms-1">0</span>
                    </button>

                    <!-- Mobile-only: theme toggle icon (visible only on small screens) -->
                    <div class="d-md-none" onclick="toggleTheme()" style="width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;background:var(--input-bg);border:1px solid var(--border-color);color:var(--text-muted);">
                        <i class="fas fa-moon" style="font-size:0.85rem;"></i>
                    </div>

                    <!-- Mobile-only: logout icon -->
                    <a href="../logout.php" class="d-md-none" style="width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:rgba(255,71,87,0.1);border:1px solid var(--border-color);color:#e74c3c;text-decoration:none;">
                        <i class="fas fa-sign-out-alt" style="font-size:0.85rem;"></i>
                    </a>
                </div>
            </div>


            <!-- MAIN CONTENT AREA -->
            <div class="p-4 pb-5">

                <!-- Mobile category bar (shown only on small screens, replaces the sidebar) -->
                <div class="mobile-cat-bar d-md-none mb-3">
                    <div class="mobile-cat-btn active" onclick="filtraCategoria('all', this)">Tutto</div>

                    <?php
                    // Re-query categories for the mobile bar (same data, separate result set)
                    $catMobile = $conn->query("SELECT * FROM categorie");
                    while ($cm = $catMobile->fetch_assoc()): ?>
                        <div class="mobile-cat-btn" onclick="filtraCategoria(<?php echo $cm['id_categoria']; ?>, this)">
                            <?php echo $cm['nome_categoria']; ?>
                        </div>
                    <?php endwhile; ?>
                </div>


                <!-- ============================================
                     DISH CARDS GRID
                     Each dish from the database becomes one card.
                     data-* attributes store dish info so JavaScript
                     can read them without making extra API calls.
                     ============================================ -->
                <div class="row g-4">
                    <?php while ($p = $prodotti->fetch_assoc()): ?>

                        <!-- Each dish gets its own Bootstrap column (responsive: 4 per row on large screens) -->
                        <!-- data-cat is used by filtraCategoria() in JavaScript to show/hide cards -->
                        <div class="col-sm-6 col-md-6 col-lg-4 col-xl-3 item-prodotto" data-cat="<?php echo $p['id_categoria']; ?>">

                            <!-- Clicking the card opens a zoomed detail view -->
                            <div class="card-prodotto" onclick="apriZoom(event, this)"
                                data-id="<?php echo $p['id_alimento']; ?>"
                                data-nome="<?php echo htmlspecialchars($p['nome_piatto']); ?>"
                                data-desc="<?php echo htmlspecialchars($p['descrizione']); ?>"
                                data-prezzo="<?php echo $p['prezzo']; ?>"
                                data-img="<?php echo $p['immagine'] ? 'data:image/jpeg;base64,' . base64_encode($p['immagine']) : ''; ?>"
                                data-allergeni="<?php echo htmlspecialchars($p['lista_allergeni']); ?>">

                                <!-- Dish photo -->
                                <div class="img-wrapper">
                                    <!-- Images are stored as binary data in the DB and converted to base64 for display -->
                                    <img src="<?php echo $p['immagine'] ? 'data:image/jpeg;base64,' . base64_encode($p['immagine']) : ''; ?>" class="img-prodotto" loading="lazy">
                                    <!-- Price tag shown on top of the image -->
                                    <div class="price-tag"><?php echo $p['prezzo']; ?>€</div>
                                </div>

                                <!-- Dish info below the image -->
                                <div class="card-body">
                                    <h5 class="piatto-title"><?php echo $p['nome_piatto']; ?></h5>
                                    <p class="piatto-desc"><?php echo $p['descrizione']; ?></p>

                                    <!-- Allergen badges (e.g. "Gluten", "Milk") -->
                                    <div class="mb-4" style="min-height: 25px;">
                                        <?php
                                        // Split the comma-separated allergen string into individual badges
                                        $allergeni = explode(',', $p['lista_allergeni']);
                                        foreach ($allergeni as $a) {
                                            if (trim($a) != "")
                                                echo "<span class='badge-alg'>" . trim($a) . "</span>";
                                        }
                                        ?>
                                    </div>

                                    <!-- Quantity selector: − [number] + buttons -->
                                    <div class="mt-auto d-flex justify-content-center align-items-center pt-3" style="border-top: 1px solid var(--border-color);">
                                        <div class="qty-capsule-card d-flex align-items-center justify-content-between" style="background: var(--capsule-bg); border-radius: 15px; padding: 6px; width: 100%;">

                                            <!-- Decrease quantity button — calls btnCardQty() in tavolo.js with -1 -->
                                            <button class="btn-card-qty" onclick="btnCardQty(event, <?php echo $p['id_alimento']; ?>, -1, <?php echo $p['prezzo']; ?>, '<?php echo addslashes($p['nome_piatto']); ?>')">
                                                <i class="fas fa-minus"></i>
                                            </button>

                                            <!-- Quantity display — updated by JavaScript. ID format: "q-{dish_id}" -->
                                            <span id="q-<?php echo $p['id_alimento']; ?>" class="fw-bold fs-5" style="min-width: 30px; text-align: center;">0</span>

                                            <!-- Increase quantity button — calls btnCardQty() in tavolo.js with +1 -->
                                            <button class="btn-card-qty" onclick="btnCardQty(event, <?php echo $p['id_alimento']; ?>, 1, <?php echo $p['prezzo']; ?>, '<?php echo addslashes($p['nome_piatto']); ?>')">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <!-- END: dish cards grid -->

            </div>
        </div>
        <!-- END: right column -->

    </div>
</div>
<!-- END: main layout -->


<!-- Load popup modals (cart modal, filters modal, order history modal, zoom modal) -->
<?php include "../include/modals/tavolo_modals.php"; ?>

<!-- Shared JS utilities (theme toggle, etc.) -->
<script src="../js/common.js"></script>

<!-- Main table page logic: cart, filtering, ordering, session polling -->
<script src="../js/tavolo.js?v=<?php echo time(); ?>"></script>

<!-- Shared HTML footer (closing </body> and </html> tags) -->
<?php include "../include/footer.php"; ?>
