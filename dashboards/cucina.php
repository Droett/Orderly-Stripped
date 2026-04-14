<?php
// ============================================================
// cucina.php — Kitchen Dashboard
// ============================================================
// This is the screen displayed in the kitchen.
// It shows incoming orders in a Kanban-style board with two columns:
//   1. "IN ARRIVO"       — new orders waiting to be prepared
//   2. "IN PREPARAZIONE" — orders currently being cooked
//
// This PHP file just sets up the HTML structure.
// All the live order data (fetching + updating) is handled by
// cucina.js, which polls the API every few seconds automatically.
// ============================================================

// Start the PHP session so we can check who is logged in
session_start();

// Load the database connection — gives us the $conn variable
include '../include/conn.php';

// Load the permission checker
require_once '../include/auth/check_permesso.php';

// --- SECURITY CHECK ---
// If the user is not logged in as kitchen staff, redirect to the login page
if (!verificaPermesso($conn, 'dashboard/cucina')) {
    header("Location: ../index.php");
    exit;
}

// Load the shared HTML <head> and opening <body> tag
include '../include/header.php';
?>

<!-- Google Fonts: loads the "Poppins" font -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

<!-- Font Awesome: icon library (bell icon, fire icon, etc.) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Kitchen-specific styles -->
<link rel="stylesheet" href="../css/cucina.css">
<link rel="stylesheet" href="../css/common.css">


<!-- ============================================================
     TOP HEADER BAR
     Shows the app logo/name and theme toggle + logout buttons.
     ============================================================ -->
<div class="sticky-header">

    <!-- Left side: logo and title -->
    <div class="d-flex align-items-center gap-3">
        <img src="../imgs/ordnobg.png" width="50">
        <div>
            <div class="brand-title">Cucina</div>
            <div class="brand-subtitle">Monitor degli ordini in tempo reale</div>
        </div>
    </div>

    <!-- Right side: theme toggle + logout -->
    <div class="d-flex align-items-center gap-3">
        <!-- Toggles between dark and light mode (defined in common.js) -->
        <div class="theme-toggle" onclick="toggleTheme()" title="Cambia Tema">
            <i class="fas fa-moon" id="theme-icon"></i>
        </div>
        <a href="../logout.php" class="theme-toggle-sidebar text-danger" title="Esci">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</div>


<!-- ============================================================
     KANBAN BOARD
     Two side-by-side columns for the two active order states.
     Order cards are injected into each column by cucina.js.
     ============================================================ -->
<div class="kanban-board">

    <!-- COLUMN 1: New orders waiting to start ("in_attesa") -->
    <div class="k-column">
        <div class="k-header" style="color: var(--new-order-text);">
            <span><i class="fas fa-bell me-2"></i> IN ARRIVO</span>
            <!-- Order count badge — updated by JavaScript -->
            <span class="badge-count" id="count-new">0</span>
        </div>
        <!-- Order cards are inserted here by cucina.js -->
        <div class="k-body" id="col-new"></div>
    </div>

    <!-- COLUMN 2: Orders currently being prepared ("in_preparazione") -->
    <div class="k-column">
        <div class="k-header" style="color: var(--prep-order-text);">
            <span><i class="fas fa-fire me-2"></i> IN PREPARAZIONE</span>
            <!-- Order count badge — updated by JavaScript -->
            <span class="badge-count" id="count-prep">0</span>
        </div>
        <!-- Order cards are inserted here by cucina.js -->
        <div class="k-body" id="col-prep"></div>
    </div>
</div>


<!-- Shared JS utilities (theme toggle, etc.) -->
<script src="../js/common.js"></script>

<!-- Main kitchen page logic: fetches orders from the API and renders the cards -->
<script src="../js/cucina.js?v=<?php echo time(); ?>"></script>

<!-- Shared HTML footer (closing </body> and </html> tags) -->
<?php include '../include/footer.php'; ?>
