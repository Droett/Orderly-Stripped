<?php
session_start();
include "../include/conn.php";
require_once "../include/auth/check_permesso.php";
require_once "../include/constants.php";

if (!verificaPermesso($conn, 'dashboard/manager')) {
    header("Location: ../index.php");
    exit;
}

// ============================================================
// Handle all POST actions
// ============================================================
$action = $_GET['action'] ?? '';

if ($action && $_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {

        case 'aggiungi_tavolo': {
            $nome     = trim($_POST['nome_tavolo'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $posti    = intval($_POST['posti'] ?? 4);
            if (empty($nome) || empty($password)) die("Nome e Password obbligatori.");
            $check = $conn->prepare("SELECT id_utente FROM utenti WHERE username = ? AND ruolo='tavolo'");
            $check->bind_param("s", $nome);
            $check->execute();
            if ($check->get_result()->num_rows > 0) die("Esiste già un tavolo con questo nome.");
            $stmt = $conn->prepare("INSERT INTO utenti (username, password, ruolo, stato, posti, id_menu) VALUES (?, ?, 'tavolo', 'libero', ?, 1)");
            $stmt->bind_param("ssi", $nome, $password, $posti);
            $stmt->execute();
            header("Location: manager.php");
            exit;
        }

        case 'modifica_tavolo': {
            $id       = intval($_POST['id_tavolo'] ?? 0);
            $nome     = trim($_POST['nome_tavolo'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $posti    = intval($_POST['posti'] ?? 4);
            $stato    = trim($_POST['stato'] ?? 'libero');
            if ($id <= 0 || empty($nome) || empty($password)) die("Dati incompleti.");
            $stmt = $conn->prepare("UPDATE utenti SET username=?, password=?, posti=?, stato=? WHERE id_utente=? AND ruolo='tavolo'");
            $stmt->bind_param("ssisi", $nome, $password, $posti, $stato, $id);
            $stmt->execute();
            header("Location: manager.php");
            exit;
        }

        case 'elimina_tavolo': {
            $id = intval($_POST['id_tavolo'] ?? 0);
            if ($id <= 0) die("ID tavolo non valido.");
            $conn->query("DELETE do FROM dettaglio_ordini do INNER JOIN ordini o ON do.id_ordine = o.id_ordine WHERE o.id_utente = $id");
            $conn->query("DELETE FROM ordini WHERE id_utente = $id");
            $stmt = $conn->prepare("DELETE FROM utenti WHERE id_utente = ? AND ruolo='tavolo'");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            header("Location: manager.php");
            exit;
        }

        case 'cambia_stato_tavolo': {
            $id    = intval($_POST['id_tavolo'] ?? 0);
            $stato = trim($_POST['stato'] ?? '');
            if ($id > 0 && in_array($stato, ['libero', 'occupato', 'riservato'])) {
                $stmt = $conn->prepare("UPDATE utenti SET stato = ? WHERE id_utente = ? AND ruolo='tavolo'");
                $stmt->bind_param("si", $stato, $id);
                $stmt->execute();
            }
            header("Location: manager.php");
            exit;
        }

        case 'termina_sessione': {
            $id = intval($_POST['id_tavolo'] ?? 0);
            if ($id > 0) {
                $stmt = $conn->prepare("UPDATE utenti SET sessione_inizio = NOW(), stato = 'libero', device_token = NULL WHERE id_utente = ? AND ruolo='tavolo'");
                $stmt->bind_param("i", $id);
                $stmt->execute();
            }
            header("Location: manager.php");
            exit;
        }

        case 'aggiungi_categoria': {
            $nome   = trim($_POST['nome_categoria'] ?? '');
            $idMenu = intval($_POST['id_menu'] ?? 1);
            if (empty($nome)) die("La categoria deve avere un nome!");
            $stmt = $conn->prepare("INSERT INTO categorie (nome_categoria, id_menu) VALUES (?, ?)");
            $stmt->bind_param("si", $nome, $idMenu);
            $stmt->execute();
            header("Location: manager.php?msg=success&section=menu");
            exit;
        }

        case 'aggiungi_piatto': {
            $nome      = $_POST['nome_piatto'] ?? '';
            $desc      = $_POST['descrizione'] ?? '';
            $prezzo    = floatval($_POST['prezzo'] ?? 0);
            $idCat     = intval($_POST['id_categoria'] ?? 0);
            $allergeni = empty($_POST['allergeni']) ? "" : implode(',', $_POST['allergeni']);
            $imgFilename = null;
            if (isset($_FILES["immagine"]) && $_FILES["immagine"]["error"] === 0) {
                $ext = strtolower(pathinfo($_FILES["immagine"]["name"], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                    $uploadDir = __DIR__ . '/../imgs/piatti/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $imgFilename = uniqid() . '.' . $ext;
                    move_uploaded_file($_FILES["immagine"]["tmp_name"], $uploadDir . $imgFilename);
                }
            }
            $stmt = $conn->prepare("INSERT INTO alimenti (nome_piatto, descrizione, prezzo, id_categoria, immagine, lista_allergeni) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdiss", $nome, $desc, $prezzo, $idCat, $imgFilename, $allergeni);
            $stmt->execute();
            header("Location: manager.php?msg=success&section=menu");
            exit;
        }

        case 'modifica_piatto': {
            $id        = intval($_POST['id_alimento']);
            $nome      = $_POST['nome_piatto'] ?? '';
            $prezzo    = floatval($_POST['prezzo'] ?? 0);
            $desc      = $_POST['descrizione'] ?? '';
            $idCat     = intval($_POST['id_categoria'] ?? 0);
            $allergeni = empty($_POST['allergeni']) ? "" : implode(', ', $_POST['allergeni']);
            if (isset($_FILES["immagine"]) && $_FILES["immagine"]["error"] === 0) {
                $ext = strtolower(pathinfo($_FILES["immagine"]["name"], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                    $uploadDir = __DIR__ . '/../imgs/piatti/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $imgFilename = uniqid() . '.' . $ext;
                    move_uploaded_file($_FILES["immagine"]["tmp_name"], $uploadDir . $imgFilename);
                    $stmt = $conn->prepare("UPDATE alimenti SET nome_piatto=?, prezzo=?, descrizione=?, id_categoria=?, lista_allergeni=?, immagine=? WHERE id_alimento=?");
                    $stmt->bind_param("sdsissi", $nome, $prezzo, $desc, $idCat, $allergeni, $imgFilename, $id);
                }
            }
            if (!isset($stmt)) {
                $stmt = $conn->prepare("UPDATE alimenti SET nome_piatto=?, prezzo=?, descrizione=?, id_categoria=?, lista_allergeni=? WHERE id_alimento=?");
                $stmt->bind_param("sdsisi", $nome, $prezzo, $desc, $idCat, $allergeni, $id);
            }
            $stmt->execute();
            header("Location: manager.php?msg=success&section=menu");
            exit;
        }

        case 'elimina_piatto': {
            if (empty($_POST['id_alimento'])) die("ID piatto mancante.");
            $id = intval($_POST['id_alimento']);
            $stmt = $conn->prepare("DELETE FROM alimenti WHERE id_alimento = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            header("Location: manager.php?msg=success&section=menu");
            exit;
        }

        case 'elimina_categoria': {
            if (empty($_POST['id_categoria'])) die("ID categoria mancante.");
            $id = intval($_POST['id_categoria']);
            $stmt = $conn->prepare("DELETE FROM categorie WHERE id_categoria = ?");
            $stmt->bind_param("i", $id);
            try {
                if (!$stmt->execute()) throw new Exception($stmt->error);
                header("Location: manager.php?msg=success&section=menu");
                exit;
            } catch (Exception $e) {
                echo "<div style='font-family:sans-serif;padding:40px;text-align:center;color:#721c24;background:#f8d7da;'>";
                echo "<h2>Impossibile eliminare!</h2>";
                echo "<p>Ci sono ancora piatti collegati a questa categoria.</p>";
                echo "<br><a href='manager.php?section=menu' style='padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;'>Indietro</a>";
                echo "</div>";
                exit;
            }
        }
    }
}

// ============================================================
// Page data
// ============================================================
$section = $_GET['section'] ?? 'tavoli';

$tavoli = $conn->query("SELECT * FROM utenti WHERE ruolo='tavolo' ORDER BY username ASC");

$categorie_result = $conn->query("SELECT * FROM categorie ORDER BY nome_categoria");
$categorie_array  = [];
while ($cat = $categorie_result->fetch_assoc()) {
    $categorie_array[] = $cat;
}

$editTavolo = null;
if (isset($_GET['edit_tavolo'])) {
    $etid   = intval($_GET['edit_tavolo']);
    $etStmt = $conn->prepare("SELECT * FROM utenti WHERE id_utente = ? AND ruolo='tavolo'");
    $etStmt->bind_param("i", $etid);
    $etStmt->execute();
    $editTavolo = $etStmt->get_result()->fetch_assoc();
}

$editPiatto = null;
if (isset($_GET['edit_piatto'])) {
    $epid   = intval($_GET['edit_piatto']);
    $epStmt = $conn->prepare("SELECT * FROM alimenti WHERE id_alimento = ?");
    $epStmt->bind_param("i", $epid);
    $epStmt->execute();
    $editPiatto = $epStmt->get_result()->fetch_assoc();
}

include "../include/header.php";
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../css/manager.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="../css/common.css?v=<?php echo time(); ?>">


<div class="container-fluid p-0">
    <div class="row g-0">

        <!-- LEFT SIDEBAR -->
        <div class="col-md-3 col-lg-2 d-none d-md-block">
            <div class="sidebar-custom d-flex flex-column">

                <div class="text-center mb-5 mt-3">
                    <img src="../imgs/ordlogo.png" width="100">
                </div>

                <div class="px-3 flex-grow-1">
                    <small class="text-uppercase fw-bold ps-3 mb-2 d-block text-muted" style="font-size: 11px;">Pannello Admin</small>

                    <a href="manager.php?section=tavoli" class="btn-sidebar <?= $section !== 'menu' ? 'active' : '' ?>" style="text-decoration:none">
                        <i class="fas fa-chair me-3"></i> Gestione Tavoli
                    </a>
                    <a href="manager.php?section=menu" class="btn-sidebar <?= $section === 'menu' ? 'active' : '' ?>" style="text-decoration:none">
                        <i class="fas fa-utensils me-3"></i> Gestione Menu
                    </a>
                </div>

                <div class="p-4 mt-auto">
                    <div class="d-flex justify-content-center gap-3">
                        <a href="../logout.php" class="theme-toggle-sidebar text-danger" title="Esci">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>


        <!-- RIGHT COLUMN -->
        <div class="col-md-9 col-lg-10">

            <!-- Mobile navigation bar -->
            <div class="mobile-nav-bar d-md-none">
                <a href="manager.php?section=tavoli" class="mobile-nav-btn <?= $section !== 'menu' ? 'active' : '' ?>" style="text-decoration:none">
                    <i class="fas fa-chair"></i> Tavoli
                </a>
                <a href="manager.php?section=menu" class="mobile-nav-btn <?= $section === 'menu' ? 'active' : '' ?>" style="text-decoration:none">
                    <i class="fas fa-utensils"></i> Menu
                </a>
                <div class="ms-auto d-flex gap-2 align-items-center">
                    <a href="../logout.php" class="theme-toggle-sidebar text-danger" style="width:32px;height:32px;">
                        <i class="fas fa-sign-out-alt" style="font-size:0.8rem;"></i>
                    </a>
                </div>
            </div>


            <!-- ================================================
                 SECTION 1: TABLES
                 ================================================ -->
            <div id="page-tavoli" class="page-section" style="display: <?= $section !== 'menu' ? 'block' : 'none' ?>;">

                <div class="page-header">
                    <div>
                        <h2 class="fw-bold m-0">Gestione Tavoli</h2>
                        <p class="text-muted m-0 small">Controlla lo stato delle prenotazioni in tempo reale</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <button class="btn btn-dark rounded-pill px-4 py-2 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAggiungiTavolo">
                            <i class="fas fa-plus me-2"></i>Nuovo Tavolo
                        </button>
                    </div>
                </div>

                <?php if ($section !== 'menu' && !isset($_GET['edit_tavolo'])): ?>
                <meta http-equiv="refresh" content="30">
                <?php endif; ?>

                <?php if ($editTavolo): ?>
                <div class="card-custom mx-4 mb-4">
                    <h5 class="card-title"><i class="fas fa-pen me-2 text-warning"></i>Modifica Tavolo: <?= htmlspecialchars($editTavolo['username']) ?></h5>
                    <form method="POST" action="manager.php?action=modifica_tavolo">
                        <input type="hidden" name="id_tavolo" value="<?= $editTavolo['id_utente'] ?>">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="small text-muted fw-bold mb-1">Nome</label>
                                <input type="text" name="nome_tavolo" class="form-control" value="<?= htmlspecialchars($editTavolo['username']) ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="small text-muted fw-bold mb-1">Password</label>
                                <input type="text" name="password" class="form-control" value="<?= htmlspecialchars($editTavolo['password']) ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="small text-muted fw-bold mb-1">Posti</label>
                                <input type="number" name="posti" class="form-control" value="<?= $editTavolo['posti'] ?>" min="1" max="20">
                            </div>
                            <div class="col-12">
                                <label class="small text-muted fw-bold mb-1">Stato</label>
                                <select name="stato" class="form-select">
                                    <option value="libero" <?= $editTavolo['stato']==='libero'?'selected':'' ?>>Libero</option>
                                    <option value="riservato" <?= $editTavolo['stato']==='riservato'?'selected':'' ?>>Riservato</option>
                                </select>
                            </div>
                            <div class="col-12 d-flex gap-2">
                                <button type="submit" class="btn btn-dark rounded-pill px-4 fw-bold">Salva Modifiche</button>
                                <a href="manager.php?section=tavoli" class="btn btn-light rounded-pill px-4 fw-bold">Annulla</a>
                            </div>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <div class="tavoli-grid" id="tavoli-grid">
                    <?php
                    $tavoli_arr = [];
                    while ($t = $tavoli->fetch_assoc()) $tavoli_arr[] = $t;
                    $stati_next = ['libero' => 'occupato', 'occupato' => 'riservato', 'riservato' => 'libero'];
                    ?>
                    <?php if (empty($tavoli_arr)): ?>
                        <div class="tavoli-empty">
                            <i class="fas fa-chair"></i>
                            <h4>Nessun tavolo trovato</h4>
                            <p class="small">Aggiungi un tavolo per iniziare</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tavoli_arr as $t):
                            $stato      = $t['stato'] ?? 'libero';
                            $icon       = $stato === 'libero' ? 'fa-check-circle' : ($stato === 'occupato' ? 'fa-users' : 'fa-clock');
                            $label      = ucfirst($stato);
                            $nuovoStato = $stati_next[$stato] ?? 'libero';
                        ?>
                            <div class="tavolo-card" data-stato="<?= $stato ?>">
                                <div class="tavolo-card-header">
                                    <div class="tavolo-icon <?= $stato ?>"><i class="fas <?= $icon ?>"></i></div>
                                    <div class="tavolo-name"><?= htmlspecialchars($t['username']) ?></div>
                                    <div class="tavolo-seats"><i class="fas fa-chair"></i> <?= $t['posti'] ?> posti</div>
                                </div>
                                <div class="tavolo-card-footer">

                                    <form method="POST" action="manager.php?action=cambia_stato_tavolo" style="display:inline">
                                        <input type="hidden" name="id_tavolo" value="<?= $t['id_utente'] ?>">
                                        <input type="hidden" name="stato" value="<?= $nuovoStato ?>">
                                        <button type="submit" class="tavolo-status-badge badge-<?= $stato ?>">
                                            <span class="status-dot dot-<?= $stato ?>"></span> <?= $label ?>
                                        </button>
                                    </form>

                                    <div class="tavolo-actions">
                                        <?php if ($stato === 'occupato'): ?>
                                            <form method="POST" action="manager.php?action=termina_sessione" style="display:inline"
                                                  onsubmit="return confirm('Terminare la sessione di questo tavolo?')">
                                                <input type="hidden" name="id_tavolo" value="<?= $t['id_utente'] ?>">
                                                <button type="submit" class="btn-act" title="Resetta"><i class="fas fa-redo-alt"></i></button>
                                            </form>
                                        <?php endif; ?>

                                        <a href="manager.php?section=tavoli&edit_tavolo=<?= $t['id_utente'] ?>" class="btn-act" title="Modifica">
                                            <i class="fas fa-pen"></i>
                                        </a>

                                        <form method="POST" action="manager.php?action=elimina_tavolo" style="display:inline"
                                              onsubmit="return confirm('Eliminare il tavolo &quot;<?= htmlspecialchars($t['username'], ENT_QUOTES) ?>&quot;?')">
                                            <input type="hidden" name="id_tavolo" value="<?= $t['id_utente'] ?>">
                                            <button type="submit" class="btn-act btn-delete-t" title="Elimina"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>


            <!-- ================================================
                 SECTION 2: MENU
                 ================================================ -->
            <div id="page-menu" class="page-section" style="display: <?= $section === 'menu' ? 'block' : 'none' ?>;">
                <div class="container py-4">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="fw-bold m-0">Gestione Menu</h2>
                            <p class="text-muted m-0 small">Aggiungi, modifica o elimina piatti dal menu</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-dark rounded-pill px-4 py-2 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalImportCSV">
                                <i class="fas fa-file-upload me-2"></i>Importa CSV
                            </button>
                            <a href="export_menu.php" class="btn btn-dark rounded-pill px-4 py-2 fw-bold shadow-sm">
                                <i class="fas fa-file-csv me-2"></i>Esporta CSV
                            </a>
                        </div>
                    </div>

                    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
                        <div id="success-alert" class="alert alert-success border-0 shadow-sm rounded-3 mb-4 text-center fw-bold text-success">
                            Menu aggiornato correttamente!
                        </div>
                    <?php endif; ?>

                    <div class="row g-4">

                        <!-- Add new dish -->
                        <div class="col-lg-8">
                            <div class="card-custom">
                                <h5 class="card-title"><i class="fas fa-utensils me-2 text-warning"></i>Nuovo Piatto</h5>

                                <form action="manager.php?action=aggiungi_piatto" method="POST" enctype="multipart/form-data">
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <input type="text" name="nome_piatto" class="form-control" required placeholder="Nome del piatto">
                                        </div>
                                        <div class="col-md-4">
                                            <input type="number" step="0.01" name="prezzo" class="form-control" required placeholder="Prezzo (€)">
                                        </div>
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
                                        <div class="col-12">
                                            <textarea name="descrizione" class="form-control" rows="2" placeholder="Descrizione ingredienti..."></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label class="small text-muted fw-bold mb-2">ALLERGENI PRESENTI</label>
                                            <div class="d-flex flex-wrap gap-2 p-3 rounded allergeni-box">
                                                <?php foreach ($ALLERGENI as $a): ?>
                                                    <div class="form-check form-check-inline m-0 me-3">
                                                        <input class="form-check-input" type="checkbox" name="allergeni[]" value="<?php echo $a; ?>" id="al_<?php echo $a; ?>">
                                                        <label class="form-check-label small" for="al_<?php echo $a; ?>"><?php echo $a; ?></label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="small text-muted fw-bold">FOTO DEL PIATTO</label>
                                            <input type="file" name="immagine" class="form-control" accept="image/*" required>
                                        </div>
                                        <div class="col-12 mt-3">
                                            <button type="submit" class="btn-main">Aggiungi Piatto</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Categories -->
                        <div class="col-lg-4">
                            <div class="card-custom mb-4">
                                <h5 class="card-title"><i class="fas fa-tags me-2 text-primary"></i>Nuova Categoria</h5>
                                <form action="manager.php?action=aggiungi_categoria" method="POST" class="d-flex gap-2">
                                    <input type="text" name="nome_categoria" class="form-control" placeholder="Es: Burger" required>
                                    <input type="hidden" name="id_menu" value="1">
                                    <button type="submit" class="btn btn-dark rounded-3"><i class="fas fa-plus"></i></button>
                                </form>
                            </div>

                            <div class="card-custom">
                                <h5 class="card-title">Gestione Categorie</h5>
                                <div style="max-height: 300px; overflow-y: auto;">
                                    <table class="table-custom">
                                        <tbody>
                                            <?php foreach ($categorie_array as $row): ?>
                                                <tr>
                                                    <td><strong><?php echo $row['nome_categoria']; ?></strong></td>
                                                    <td class="text-end">
                                                        <form action="manager.php?action=elimina_categoria" method="POST"
                                                              onsubmit="return confirm('Eliminare questa categoria e tutti i piatti collegati?');">
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


                    <?php if ($editPiatto):
                        $epAllergeni = array_map('trim', explode(',', strtolower($editPiatto['lista_allergeni'] ?? '')));
                    ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card-custom">
                                <h5 class="card-title"><i class="fas fa-edit me-2 text-warning"></i>Modifica Piatto: <?= htmlspecialchars($editPiatto['nome_piatto']) ?></h5>
                                <form action="manager.php?action=modifica_piatto" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="id_alimento" value="<?= $editPiatto['id_alimento'] ?>">
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <input type="text" name="nome_piatto" class="form-control" value="<?= htmlspecialchars($editPiatto['nome_piatto']) ?>" required placeholder="Nome del piatto">
                                        </div>
                                        <div class="col-md-4">
                                            <input type="number" step="0.01" name="prezzo" class="form-control" value="<?= $editPiatto['prezzo'] ?>" required placeholder="Prezzo (€)">
                                        </div>
                                        <div class="col-12">
                                            <select name="id_categoria" class="form-select" required>
                                                <?php foreach ($categorie_array as $cat): ?>
                                                    <option value="<?= $cat['id_categoria'] ?>" <?= $cat['id_categoria'] == $editPiatto['id_categoria'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($cat['nome_categoria']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <textarea name="descrizione" class="form-control" rows="2" placeholder="Descrizione ingredienti..."><?= htmlspecialchars($editPiatto['descrizione']) ?></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label class="small text-muted fw-bold mb-2">ALLERGENI PRESENTI</label>
                                            <div class="d-flex flex-wrap gap-2 p-3 rounded allergeni-box">
                                                <?php foreach ($ALLERGENI as $a): ?>
                                                    <div class="form-check form-check-inline m-0 me-3">
                                                        <input class="form-check-input" type="checkbox" name="allergeni[]" value="<?= $a ?>"
                                                               id="ep_al_<?= $a ?>" <?= in_array(strtolower($a), $epAllergeni) ? 'checked' : '' ?>>
                                                        <label class="form-check-label small" for="ep_al_<?= $a ?>"><?= $a ?></label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="small text-muted fw-bold">FOTO DEL PIATTO</label>
                                            <?php if ($editPiatto['immagine']): ?>
                                                <div class="mb-2">
                                                    <img src="../imgs/piatti/<?= htmlspecialchars($editPiatto['immagine']) ?>" style="width:80px;height:80px;object-fit:cover;border-radius:12px;border:1px solid #ddd;">
                                                </div>
                                            <?php endif; ?>
                                            <input type="file" name="immagine" class="form-control" accept="image/*">
                                            <small class="text-muted">Lascia vuoto per mantenere l'immagine attuale</small>
                                        </div>
                                        <div class="col-12 d-flex gap-2 mt-2">
                                            <button type="submit" class="btn-main">Salva Modifiche</button>
                                            <a href="manager.php?section=menu" class="btn btn-light rounded-pill px-4 fw-bold">Annulla</a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Dish list -->
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
                                            $result = $conn->query("SELECT * FROM alimenti ORDER BY nome_piatto ASC");
                                            if ($result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    $allergeniSafe = htmlspecialchars($row['lista_allergeni'], ENT_QUOTES);
                                                    $descSafe      = htmlspecialchars($row['descrizione'], ENT_QUOTES);
                                                    $nomeSafe      = htmlspecialchars($row['nome_piatto'], ENT_QUOTES);
                                                    echo "<tr>
                                                            <td class='fw-bold'>" . $row['nome_piatto'] . "</td>
                                                            <td class='col-desc small text-muted'>" . substr($row['descrizione'], 0, 80) . "...</td>
                                                            <td class='fw-bold text-success'>" . number_format($row['prezzo'], 2) . " €</td>
                                                            <td class='text-end'>
                                                                <div class='d-flex justify-content-end gap-2'>
                                                                    <a href='manager.php?section=menu&edit_piatto=" . $row['id_alimento'] . "'
                                                                       class='btn btn-warning btn-sm text-white'>
                                                                        <i class='fas fa-edit'></i>
                                                                    </a>
                                                                    <form action='manager.php?action=elimina_piatto' method='POST'
                                                                          onsubmit='return confirm(\"Eliminare questo piatto?\");' style='margin:0;'>
                                                                        <input type='hidden' name='id_alimento' value='" . $row['id_alimento'] . "'>
                                                                        <button type='submit' class='btn btn-danger btn-sm'><i class='fas fa-trash'></i></button>
                                                                    </form>
                                                                </div>
                                                            </td>
                                                          </tr>";
                                                }
                                            } else {
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


<?php include "../include/modals/manager_modals.php"; ?>

<?php include "../include/footer.php"; ?>
