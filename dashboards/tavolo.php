<?php
session_start();
include "../include/conn.php";
require_once "../include/auth/check_permesso.php";

if (!verificaPermesso($conn, 'dashboard/tavolo')) {
    header("Location: ../index.php");
    exit;
}
include "../include/header.php";

$categorie = $conn->query("SELECT * FROM categorie");
$prodotti = $conn->query("SELECT * FROM alimenti");
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../css/tavolo.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="../css/common.css?v=<?php echo time(); ?>">

<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Sidebar Categorie (Desktop) -->
        <div class="col-md-3 col-lg-2 d-none d-md-block">
            <div class="sidebar-custom d-flex flex-column">
                <div class="text-center mb-5 mt-3"><img src="../imgs/ordlogo.png" width="100"></div>

                <div class="px-3 flex-grow-1 overflow-auto">
                    <small class="text-uppercase fw-bold ps-3 mb-2 d-block text-muted" style="font-size: 11px;">Esplora
                        il Menu</small>
                    <div class="btn-categoria active" onclick="filtraCategoria('all', this)">
                        <i class="fas fa-utensils me-3"></i> Tutto
                    </div>
                    <?php while ($cat = $categorie->fetch_assoc()): ?>
                        <div class="btn-categoria" onclick="filtraCategoria(<?php echo $cat['id_categoria']; ?>, this)">
                            <i class="fas fa-bookmark me-3"></i> <?php echo $cat['nome_categoria']; ?>
                        </div>
                    <?php endwhile; ?>
                </div>

                <div class="p-4 mt-auto">
                    <div class="d-flex justify-content-center gap-3">
                        <div class="theme-toggle-sidebar" onclick="toggleTheme()" title="Cambia Tema">
                            <i class="fas fa-moon" id="theme-icon"></i>
                        </div>
                        <a href="../logout.php" class="theme-toggle-sidebar text-danger" title="Chiudi Sessione">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenuto Principale -->
        <div class="col-md-9 col-lg-10">
            <!-- Sticky Header -->
            <div class="sticky-header d-flex justify-content-between align-items-center">
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="search-bar" class="search-input" placeholder="Cerca un piatto..."
                        oninput="renderProdotti()">
                </div>

                <div class="d-flex align-items-center justify-content-end gap-2">
                    <div
                        class="d-none d-sm-flex align-items-center me-2 bg-surface rounded-pill px-3 py-2 border shadow-sm">
                        <small class="text-uppercase fw-bold text-muted me-2" style="font-size: 10px;">Conto
                            Stimato</small>
                        <div class="fw-bold fs-5 text-price price-stable"><span id="soldi-header">0.00</span>€</div>
                    </div>

                    <button
                        class="btn btn-dark rounded-pill px-3 py-2 px-md-4 py-md-3 shadow-sm d-flex align-items-center"
                        onclick="apriStorico()">
                        <i class="fas fa-receipt"></i>
                        <span class="d-none d-lg-inline fw-bold ms-2">Storico Ordini</span>
                    </button>
                    <button
                        class="btn btn-dark rounded-pill px-3 py-2 px-md-4 py-md-3 shadow-sm d-flex align-items-center"
                        data-bs-toggle="modal" data-bs-target="#modalFiltri">
                        <i class="fas fa-filter"></i>
                        <span class="d-none d-lg-inline fw-bold ms-2">Filtra</span>
                    </button>
                    <button
                        class="btn btn-dark rounded-pill px-3 py-2 px-md-4 py-md-3 shadow-sm d-flex align-items-center"
                        data-bs-toggle="modal" data-bs-target="#modalCarrello" onclick="aggiornaModale()">
                        <i class="fas fa-shopping-bag fa-lg"></i>
                        <span class="d-none d-lg-inline fw-bold ms-2">Carrello</span>
                        <span id="pezzi-header" class="ms-1">0</span>
                    </button>

                    <!-- Tema/Logout Mobile -->
                    <div class="d-md-none" onclick="toggleTheme()"
                        style="width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;background:var(--input-bg);border:1px solid var(--border-color);color:var(--text-muted);">
                        <i class="fas fa-moon" style="font-size:0.85rem;"></i>
                    </div>
                    <a href="../logout.php" class="d-md-none"
                        style="width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:rgba(255,71,87,0.1);border:1px solid var(--border-color);color:#e74c3c;text-decoration:none;">
                        <i class="fas fa-sign-out-alt" style="font-size:0.85rem;"></i>
                    </a>
                </div>
            </div>

            <!-- Griglia Prodotti -->
            <div class="p-4 pb-5">
                <!-- Categorie Mobile -->
                <div class="mobile-cat-bar d-md-none mb-3">
                    <div class="mobile-cat-btn active" onclick="filtraCategoria('all', this)">Tutto</div>
                    <?php
                    $catMobile = $conn->query("SELECT * FROM categorie");
                    while ($cm = $catMobile->fetch_assoc()): ?>
                        <div class="mobile-cat-btn" onclick="filtraCategoria(<?php echo $cm['id_categoria']; ?>, this)">
                            <?php echo $cm['nome_categoria']; ?>
                        </div>
                    <?php endwhile; ?>
                </div>

                <div class="row g-4">
                    <?php while ($p = $prodotti->fetch_assoc()): ?>
                        <div class="col-sm-6 col-md-6 col-lg-4 col-xl-3 item-prodotto"
                            data-cat="<?php echo $p['id_categoria']; ?>">

                            <div class="card-prodotto" onclick="apriZoom(event, this)"
                                data-id="<?php echo $p['id_alimento']; ?>"
                                data-nome="<?php echo htmlspecialchars($p['nome_piatto']); ?>"
                                data-desc="<?php echo htmlspecialchars($p['descrizione']); ?>"
                                data-prezzo="<?php echo $p['prezzo']; ?>"
                                data-img="<?php echo $p['immagine'] ? 'data:image/jpeg;base64,' . base64_encode($p['immagine']) : ''; ?>"
                                data-allergeni="<?php echo htmlspecialchars($p['lista_allergeni']); ?>">

                                <div class="img-wrapper">
                                    <img src="<?php echo $p['immagine'] ? 'data:image/jpeg;base64,' . base64_encode($p['immagine']) : ''; ?>"
                                        class="img-prodotto" loading="lazy">
                                    <div class="price-tag"><?php echo $p['prezzo']; ?>€</div>
                                </div>

                                <div class="card-body">
                                    <h5 class="piatto-title"><?php echo $p['nome_piatto']; ?></h5>
                                    <p class="piatto-desc"><?php echo $p['descrizione']; ?></p>

                                    <div class="mb-4" style="min-height: 25px;">
                                        <?php
                                        $allergeni = explode(',', $p['lista_allergeni']);
                                        foreach ($allergeni as $a) {
                                            if (trim($a) != "")
                                                echo "<span class='badge-alg'>" . trim($a) . "</span>";
                                        }
                                        ?>
                                    </div>

                                    <div class="mt-auto d-flex justify-content-center align-items-center pt-3"
                                        style="border-top: 1px solid var(--border-color);">
                                        <div class="qty-capsule-card d-flex align-items-center justify-content-between"
                                            style="background: var(--capsule-bg); border-radius: 15px; padding: 6px; width: 100%;">
                                            <button class="btn-card-qty"
                                                onclick="btnCardQty(event, <?php echo $p['id_alimento']; ?>, -1, <?php echo $p['prezzo']; ?>, '<?php echo addslashes($p['nome_piatto']); ?>')">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <span id="q-<?php echo $p['id_alimento']; ?>" class="fw-bold fs-5"
                                                style="min-width: 30px; text-align: center;">0</span>
                                            <button class="btn-card-qty"
                                                onclick="btnCardQty(event, <?php echo $p['id_alimento']; ?>, 1, <?php echo $p['prezzo']; ?>, '<?php echo addslashes($p['nome_piatto']); ?>')">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../include/modals/tavolo_modals.php"; ?>

<script src="../js/common.js"></script>
<script src="../js/tavolo.js?v=<?php echo time(); ?>"></script>
<?php include "../include/footer.php"; ?>