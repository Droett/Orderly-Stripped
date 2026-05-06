<?php
session_start();
include "../include/conn.php";
require_once "../include/auth/check_permesso.php";

if (!verificaPermesso($conn, 'dashboard/tavolo')) {
    header("Location: ../index.php");
    exit;
}

$idTavolo = intval($_SESSION['id_tavolo']);

// Redirect to logout if manager has terminated this table's session
$sessCheck = $conn->prepare("SELECT stato, sessione_inizio FROM utenti WHERE id_utente = ?");
$sessCheck->bind_param("i", $idTavolo);
$sessCheck->execute();
$sessRow = $sessCheck->get_result()->fetch_assoc();
if (!$sessRow || $sessRow['stato'] !== 'occupato') {
    session_destroy();
    header("Location: ../logout.php");
    exit;
}
$orarioLogin = $sessRow['sessione_inizio'] ?? '1970-01-01 00:00:00';

// Handle cart actions
$action = $_POST['action'] ?? '';

if ($action === 'add_item') {
    $id   = intval($_POST['id_alimento']);
    $stmt = $conn->prepare("SELECT nome_piatto, prezzo FROM alimenti WHERE id_alimento = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        if (!isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id] = ['nome' => $row['nome_piatto'], 'qta' => 0, 'prezzo' => $row['prezzo']];
        }
        $_SESSION['cart'][$id]['qta']++;
    }
    header("Location: tavolo.php");
    exit;
}

if ($action === 'remove_item') {
    $id = intval($_POST['id_alimento']);
    if (isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]['qta']--;
        if ($_SESSION['cart'][$id]['qta'] <= 0) unset($_SESSION['cart'][$id]);
    }
    header("Location: tavolo.php");
    exit;
}

if ($action === 'invia_ordine') {
    $cart = $_SESSION['cart'] ?? [];
    if (!empty($cart)) {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO ordini (id_utente, stato, data_ora) VALUES (?, 'in_attesa', NOW())");
            $stmt->bind_param("i", $idTavolo);
            $stmt->execute();
            $idOrdine = $conn->insert_id;
            $det = $conn->prepare("INSERT INTO dettaglio_ordini (id_ordine, id_alimento, quantita, note) VALUES (?, ?, ?, NULL)");
            foreach ($cart as $idAlimento => $item) {
                if ($item['qta'] > 0) {
                    $det->bind_param("iii", $idOrdine, $idAlimento, $item['qta']);
                    $det->execute();
                }
            }
            $conn->commit();
            $_SESSION['cart'] = [];
        } catch (Exception $e) {
            $conn->rollback();
        }
    }
    header("Location: tavolo.php?msg=ordered");
    exit;
}

// Cart totals for top bar
$cart      = $_SESSION['cart'] ?? [];
$cartTotal = 0;
$cartCount = 0;
foreach ($cart as $item) {
    $cartTotal += $item['qta'] * $item['prezzo'];
    $cartCount += $item['qta'];
}

// Pre-fetch order history for the storico modal
$stmtOrd = $conn->prepare("SELECT o.id_ordine, o.stato, o.data_ora, d.quantita, a.nome_piatto, a.prezzo
    FROM ordini o
    JOIN dettaglio_ordini d ON o.id_ordine = d.id_ordine
    JOIN alimenti a ON d.id_alimento = a.id_alimento
    WHERE o.id_utente = ? AND o.data_ora >= ?
    ORDER BY o.data_ora DESC");
$stmtOrd->bind_param("is", $idTavolo, $orarioLogin);
$stmtOrd->execute();
$resultOrd = $stmtOrd->get_result();

$ordiniStorico = [];
while ($row = $resultOrd->fetch_assoc()) {
    $oid = $row['id_ordine'];
    if (!isset($ordiniStorico[$oid])) {
        $ordiniStorico[$oid] = [
            'stato'  => $row['stato'],
            'ora'    => date('H:i', strtotime($row['data_ora'])),
            'data'   => date('d/m/Y', strtotime($row['data_ora'])),
            'piatti' => [],
            'totale' => 0
        ];
    }
    $ordiniStorico[$oid]['piatti'][] = [
        'nome'   => $row['nome_piatto'],
        'qta'    => $row['quantita'],
        'prezzo' => number_format($row['prezzo'], 2)
    ];
    $ordiniStorico[$oid]['totale'] += $row['quantita'] * $row['prezzo'];
}

$storicoHtml   = '';
$totaleStorico = 0;
if (empty($ordiniStorico)) {
    $storicoHtml = '<div class="text-center text-muted py-5">
        <i class="fas fa-receipt fa-3x mb-3 opacity-25"></i>
        <h5>Nessun ordine</h5>
        <p class="small">Non hai ancora inviato ordini.</p>
    </div>';
} else {
    $labels = ['in_attesa' => 'In attesa', 'in_preparazione' => 'In preparazione', 'pronto' => 'Pronto'];
    foreach ($ordiniStorico as $o) {
        $totaleStorico += $o['totale'];
        $badgeClass = $o['stato'] === 'in_attesa'        ? 'bg-warning text-dark'
                    : ($o['stato'] === 'in_preparazione'  ? 'bg-info text-white' : 'bg-success');
        $label = $labels[$o['stato']] ?? $o['stato'];
        $storicoHtml .= '<div class="border rounded-4 p-3 mb-3">';
        $storicoHtml .= '<div class="d-flex justify-content-between align-items-center mb-2">';
        $storicoHtml .= '<span class="badge ' . $badgeClass . ' rounded-pill px-3 py-2">' . $label . '</span>';
        $storicoHtml .= '<small class="text-muted"><i class="fas fa-clock me-1"></i>' . $o['ora'] . ' • ' . $o['data'] . '</small>';
        $storicoHtml .= '</div>';
        foreach ($o['piatti'] as $p) {
            $storicoHtml .= '<div class="d-flex justify-content-between small py-1 border-bottom">';
            $storicoHtml .= '<span>' . $p['qta'] . 'x <strong>' . htmlspecialchars($p['nome']) . '</strong></span>';
            $storicoHtml .= '<span class="text-muted">' . $p['prezzo'] . '€</span>';
            $storicoHtml .= '</div>';
        }
        $storicoHtml .= '<div class="text-end mt-2 fw-bold text-price">' . number_format($o['totale'], 2) . '€</div>';
        $storicoHtml .= '</div>';
    }
}
$totaleStorico = number_format($totaleStorico, 2);

// Page data
$categorie = $conn->query("SELECT * FROM categorie");
$prodotti  = $conn->query("SELECT * FROM alimenti");

include "../include/header.php";
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../css/tavolo.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="../css/common.css?v=<?php echo time(); ?>">


<div class="container-fluid p-0">
    <div class="row g-0">

        <!-- LEFT SIDEBAR — category filters -->
        <div class="col-md-3 col-lg-2 d-none d-md-block">
            <div class="sidebar-custom d-flex flex-column">

                <div class="text-center mb-5 mt-3">
                    <img src="../imgs/ordlogo.png" width="100">
                </div>

                <div class="px-3 flex-grow-1 overflow-auto">
                    <small class="text-uppercase fw-bold ps-3 mb-2 d-block text-muted" style="font-size: 11px;">Esplora il Menu</small>

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


        <!-- RIGHT COLUMN -->
        <div class="col-md-9 col-lg-10">

            <!-- TOP BAR -->
            <div class="sticky-header d-flex justify-content-between align-items-center">

                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="search-bar" class="search-input" placeholder="Cerca un piatto..." oninput="renderProdotti()">
                </div>

                <div class="d-flex align-items-center justify-content-end gap-2">
                    <div class="d-none d-sm-flex align-items-center me-2 bg-surface rounded-pill px-3 py-2 border shadow-sm">
                        <small class="text-uppercase fw-bold text-muted me-2" style="font-size: 10px;">Conto Stimato</small>
                        <div class="fw-bold fs-5 text-price price-stable">
                            <?php echo number_format($cartTotal, 2); ?>€
                        </div>
                    </div>

                    <button class="btn btn-dark rounded-pill px-3 py-2 px-md-4 py-md-3 shadow-sm d-flex align-items-center"
                            data-bs-toggle="modal" data-bs-target="#modalOrdini">
                        <i class="fas fa-receipt"></i>
                        <span class="d-none d-lg-inline fw-bold ms-2">Storico Ordini</span>
                    </button>

                    <button class="btn btn-dark rounded-pill px-3 py-2 px-md-4 py-md-3 shadow-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#modalFiltri">
                        <i class="fas fa-filter"></i>
                        <span class="d-none d-lg-inline fw-bold ms-2">Filtra</span>
                    </button>

                    <button class="btn btn-dark rounded-pill px-3 py-2 px-md-4 py-md-3 shadow-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#modalCarrello">
                        <i class="fas fa-shopping-bag fa-lg"></i>
                        <span class="d-none d-lg-inline fw-bold ms-2">Carrello</span>
                        <span class="ms-1"><?php echo $cartCount; ?></span>
                    </button>

                    <div class="d-md-none" onclick="toggleTheme()" style="width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;background:var(--input-bg);border:1px solid var(--border-color);color:var(--text-muted);">
                        <i class="fas fa-moon" style="font-size:0.85rem;"></i>
                    </div>

                    <a href="../logout.php" class="d-md-none" style="width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:rgba(255,71,87,0.1);border:1px solid var(--border-color);color:#e74c3c;text-decoration:none;">
                        <i class="fas fa-sign-out-alt" style="font-size:0.85rem;"></i>
                    </a>
                </div>
            </div>


            <!-- MAIN CONTENT -->
            <div class="p-4 pb-5">

                <!-- Mobile category bar -->
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

                <!-- Dish cards grid -->
                <div class="row g-4">
                    <?php while ($p = $prodotti->fetch_assoc()): ?>
                        <div class="col-sm-6 col-md-6 col-lg-4 col-xl-3 item-prodotto" data-cat="<?php echo $p['id_categoria']; ?>">
                            <div class="card-prodotto"
                                data-nome="<?php echo htmlspecialchars($p['nome_piatto']); ?>"
                                data-desc="<?php echo htmlspecialchars($p['descrizione']); ?>"
                                data-allergeni="<?php echo htmlspecialchars($p['lista_allergeni']); ?>">

                                <div class="img-wrapper">
                                    <img src="<?php echo $p['immagine'] ? '../imgs/piatti/' . htmlspecialchars($p['immagine']) : ''; ?>" class="img-prodotto" loading="lazy">
                                    <div class="price-tag"><?php echo $p['prezzo']; ?>€</div>
                                </div>

                                <div class="card-body">
                                    <h5 class="piatto-title"><?php echo $p['nome_piatto']; ?></h5>
                                    <p class="piatto-desc"><?php echo $p['descrizione']; ?></p>

                                    <div class="mb-4" style="min-height: 25px;">
                                        <?php foreach (explode(',', $p['lista_allergeni']) as $a): ?>
                                            <?php if (trim($a) != ""): ?>
                                                <span class="badge-alg"><?php echo trim($a); ?></span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="mt-auto d-flex justify-content-center align-items-center pt-3" style="border-top: 1px solid var(--border-color);">
                                        <div class="qty-capsule-card d-flex align-items-center justify-content-between" style="background: var(--capsule-bg); border-radius: 15px; padding: 6px; width: 100%;">
                                            <form method="POST" action="tavolo.php" style="display:contents">
                                                <input type="hidden" name="action" value="remove_item">
                                                <input type="hidden" name="id_alimento" value="<?php echo $p['id_alimento']; ?>">
                                                <button type="submit" class="btn-card-qty"><i class="fas fa-minus"></i></button>
                                            </form>
                                            <span class="fw-bold fs-5" style="min-width: 30px; text-align: center;">
                                                <?php echo $_SESSION['cart'][$p['id_alimento']]['qta'] ?? 0; ?>
                                            </span>
                                            <form method="POST" action="tavolo.php" style="display:contents">
                                                <input type="hidden" name="action" value="add_item">
                                                <input type="hidden" name="id_alimento" value="<?php echo $p['id_alimento']; ?>">
                                                <button type="submit" class="btn-card-qty"><i class="fas fa-plus"></i></button>
                                            </form>
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


<?php if (isset($_GET['msg']) && $_GET['msg'] === 'ordered'): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    new bootstrap.Modal(document.getElementById('modalSuccesso')).show();
});
</script>
<?php endif; ?>

<?php include "../include/modals/tavolo_modals.php"; ?>

<script src="../js/common.js"></script>
<script src="../js/tavolo.js?v=<?php echo time(); ?>"></script>
<?php include "../include/footer.php"; ?>
