<?php
session_start();
include '../include/conn.php';
require_once '../include/auth/check_permesso.php';

if (!verificaPermesso($conn, 'dashboard/cucina')) {
    header("Location: ../index.php");
    exit;
}

// Handle state change submitted via form button
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id    = intval($_POST['id_ordine'] ?? 0);
    $stato = $_POST['nuovo_stato'] ?? '';
    if ($id && in_array($stato, ['in_attesa', 'in_preparazione', 'pronto'])) {
        $stmt = $conn->prepare("UPDATE ordini SET stato = ? WHERE id_ordine = ?");
        $stmt->bind_param("si", $stato, $id);
        $stmt->execute();
    }
    header("Location: cucina.php");
    exit;
}

// Fetch active orders
$sql = "SELECT o.id_ordine, o.id_utente, t.username, o.stato, o.data_ora,
               d.quantita, a.nome_piatto, d.note
        FROM ordini o
        LEFT JOIN utenti t ON o.id_utente = t.id_utente
        JOIN dettaglio_ordini d ON o.id_ordine = d.id_ordine
        JOIN alimenti a ON d.id_alimento = a.id_alimento
        WHERE o.stato IN ('in_attesa', 'in_preparazione')
        ORDER BY o.data_ora ASC";
$res = $conn->query($sql);

$ordini = [];
while ($row = $res->fetch_assoc()) {
    $id = $row['id_ordine'];
    if (!isset($ordini[$id])) {
        $ordini[$id] = [
            'id_ordine' => $id,
            'tavolo'    => !empty($row['username']) ? $row['username'] : "Tavolo " . $row['id_utente'],
            'stato'     => $row['stato'],
            'ora'       => date('H:i', strtotime($row['data_ora'])),
            'piatti'    => []
        ];
    }
    $ordini[$id]['piatti'][] = [
        'nome' => $row['nome_piatto'],
        'qta'  => $row['quantita'],
        'note' => $row['note'] ?? ''
    ];
}

$inAttesa = array_filter($ordini, fn($o) => $o['stato'] === 'in_attesa');
$inPrep   = array_filter($ordini, fn($o) => $o['stato'] === 'in_preparazione');

include '../include/header.php';
?>

<meta http-equiv="refresh" content="5">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../css/cucina.css">
<link rel="stylesheet" href="../css/common.css">


<div class="sticky-header">
    <div class="d-flex align-items-center gap-3">
        <img src="../imgs/ordnobg.png" width="50">
        <div>
            <div class="brand-title">Cucina</div>
            <div class="brand-subtitle">Monitor degli ordini in tempo reale</div>
        </div>
    </div>
    <div class="d-flex align-items-center gap-3">
        <a href="../logout.php" class="theme-toggle-sidebar text-danger" title="Esci">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</div>


<div class="kanban-board">

    <!-- COLUMN 1: New orders ("in_attesa") -->
    <div class="k-column">
        <div class="k-header" style="color: var(--new-order-text);">
            <span><i class="fas fa-bell me-2"></i> IN ARRIVO</span>
            <span class="badge-count" id="count-new"><?= count($inAttesa) ?></span>
        </div>
        <div class="k-body" id="col-new">
            <?php foreach ($inAttesa as $o): ?>
                <div class="order-card">
                    <div class="card-top">
                        <div class="table-badge"><?= htmlspecialchars($o['tavolo']) ?></div>
                        <div class="time-badge"><i class="fas fa-clock"></i> <?= $o['ora'] ?></div>
                    </div>
                    <?php foreach ($o['piatti'] as $p): ?>
                        <div class="dish-row">
                            <div class="qty-capsule"><?= $p['qta'] ?>x</div>
                            <div>
                                <strong><?= htmlspecialchars($p['nome']) ?></strong>
                                <?php if ($p['note']): ?>
                                    <br><small class="text-muted"><i class="fas fa-sticky-note me-1"></i><?= htmlspecialchars($p['note']) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <form method="POST" action="cucina.php">
                        <input type="hidden" name="id_ordine" value="<?= $o['id_ordine'] ?>">
                        <input type="hidden" name="nuovo_stato" value="in_preparazione">
                        <button type="submit" class="btn-action btn-start">
                            <i class="fas fa-fire"></i> Inizia Preparazione
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- COLUMN 2: Orders being prepared ("in_preparazione") -->
    <div class="k-column">
        <div class="k-header" style="color: var(--prep-order-text);">
            <span><i class="fas fa-fire me-2"></i> IN PREPARAZIONE</span>
            <span class="badge-count" id="count-prep"><?= count($inPrep) ?></span>
        </div>
        <div class="k-body" id="col-prep">
            <?php foreach ($inPrep as $o): ?>
                <div class="order-card">
                    <div class="card-top">
                        <div class="table-badge"><?= htmlspecialchars($o['tavolo']) ?></div>
                        <div class="time-badge"><i class="fas fa-clock"></i> <?= $o['ora'] ?></div>
                    </div>
                    <?php foreach ($o['piatti'] as $p): ?>
                        <div class="dish-row">
                            <div class="qty-capsule"><?= $p['qta'] ?>x</div>
                            <div>
                                <strong><?= htmlspecialchars($p['nome']) ?></strong>
                                <?php if ($p['note']): ?>
                                    <br><small class="text-muted"><i class="fas fa-sticky-note me-1"></i><?= htmlspecialchars($p['note']) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <form method="POST" action="cucina.php">
                        <input type="hidden" name="id_ordine" value="<?= $o['id_ordine'] ?>">
                        <input type="hidden" name="nuovo_stato" value="pronto">
                        <button type="submit" class="btn-action btn-green-custom">
                            <i class="fas fa-check"></i> Segna come Pronto ✓
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>


<?php include '../include/footer.php'; ?>
