<?php
session_start();
include '../include/conn.php';
require_once '../include/auth/check_permesso.php';

if (!verificaPermesso($conn, 'dashboard/cucina')) {
    header("Location: ../index.php");
    exit;
}
include '../include/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../css/cucina.css">
<link rel="stylesheet" href="../css/common.css">

<!-- Header Cucina -->
<div class="sticky-header">
    <div class="d-flex align-items-center gap-3">
        <img src="../imgs/ordnobg.png" width="50">
        <div>
            <div class="brand-title">Cucina</div>
            <div class="brand-subtitle">Monitor degli ordini in tempo reale</div>
        </div>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="theme-toggle" onclick="toggleTheme()" title="Cambia Tema">
            <i class="fas fa-moon" id="theme-icon"></i>
        </div>
        <a href="../logout.php" class="theme-toggle-sidebar text-danger" title="Esci">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</div>

<!-- Kanban Board -->
<div class="kanban-board">
    <div class="k-column">
        <div class="k-header" style="color: var(--new-order-text);">
            <span><i class="fas fa-bell me-2"></i> IN ARRIVO</span>
            <span class="badge-count" id="count-new">0</span>
        </div>
        <div class="k-body" id="col-new"></div>
    </div>

    <div class="k-column">
        <div class="k-header" style="color: var(--prep-order-text);">
            <span><i class="fas fa-fire me-2"></i> IN PREPARAZIONE</span>
            <span class="badge-count" id="count-prep">0</span>
        </div>
        <div class="k-body" id="col-prep"></div>
    </div>
</div>

<script src="../js/common.js"></script>
<script src="../js/cucina.js?v=<?php echo time(); ?>"></script>
<?php include '../include/footer.php'; ?>