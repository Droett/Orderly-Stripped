<?php
// Avvio sessione per tracciare il tavolo loggato
session_start();
// Inclusione script di connessione al database
include "../include/conn.php";
// Inclusione dello script per il controllo centralizzato dei permessi
require_once "../include/auth/check_permesso.php";

// Verifica se la sessione attuale ha i permessi per accedere a questa dashboard
if (!verificaPermesso($conn, 'dashboard/tavolo')) {
    // Se non ha i permessi, redirige alla pagina di login
    header("Location: ../index.php");
    // Interrompe l'esecuzione dello script
    exit;
}
// Inclusione dell'header HTML comune (librerie CSS e JS base)
include "../include/header.php";

// Recupera tutte le categorie dal database per creare i filtri menu
$categorie = $conn->query("SELECT * FROM categorie");
// Recupera tutti gli alimenti disponibili dal database
$prodotti = $conn->query("SELECT * FROM alimenti");
?>

<!-- Importazione Google Fonts per tipografia moderna -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<!-- Importazione FontAwesome per le icone (es. carrello, ricerca, filtri) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- Fogli di stile personalizzati con refresh cache forzato (time()) -->
<link rel="stylesheet" href="../css/tavolo.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="../css/common.css?v=<?php echo time(); ?>">

<!-- Contenitore principale Bootstrap fluido senza padding -->
<div class="container-fluid p-0">
    <!-- Riga principale senza gutter (spazi) tra le colonne -->
    <div class="row g-0">
        <!-- Sidebar Categorie (Visibile solo su schermi medi e grandi - Desktop) -->
        <div class="col-md-3 col-lg-2 d-none d-md-block">
            <!-- Contenitore flexbox a colonna per la barra laterale -->
            <div class="sidebar-custom d-flex flex-column">
                <!-- Logo del ristorante in cima alla barra laterale -->
                <div class="text-center mb-5 mt-3"><img src="../imgs/ordlogo.png" width="100"></div>

                <!-- Sezione scrollabile contenente i pulsanti delle categorie -->
                <div class="px-3 flex-grow-1 overflow-auto">
                    <!-- Titolo della sezione categorie -->
                    <small class="text-uppercase fw-bold ps-3 mb-2 d-block text-muted" style="font-size: 11px;">Esplora il Menu</small>
                    <!-- Pulsante "Tutto" per mostrare tutti i piatti (Attivo di default) -->
                    <div class="btn-categoria active" onclick="filtraCategoria('all', this)">
                        <i class="fas fa-utensils me-3"></i> Tutto
                    </div>
                    <!-- Ciclo PHP per generare dinamicamente i pulsanti categoria -->
                    <?php while ($cat = $categorie->fetch_assoc()): ?>
                        <!-- Pulsante per filtrare una singola categoria tramite ID -->
                        <div class="btn-categoria" onclick="filtraCategoria(<?php echo $cat['id_categoria']; ?>, this)">
                            <!-- Nome della categoria e icona segnalibro -->
                            <i class="fas fa-bookmark me-3"></i> <?php echo $cat['nome_categoria']; ?>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Sezione fissa in basso nella barra laterale -->
                <div class="p-4 mt-auto">
                    <div class="d-flex justify-content-center gap-3">
                        <!-- Pulsante per cambiare tema (Dark/Light Mode) -->
                        <div class="theme-toggle-sidebar" onclick="toggleTheme()" title="Cambia Tema">
                            <i class="fas fa-moon" id="theme-icon"></i>
                        </div>
                        <!-- Pulsante di logout -->
                        <a href="../logout.php" class="theme-toggle-sidebar text-danger" title="Chiudi Sessione">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonna principale per il contenuto del menu (Prodotti e Ricerca) -->
        <div class="col-md-9 col-lg-10">
            <!-- Intestazione fissa (Sticky Header) con barra di ricerca e tasti rapidi -->
            <div class="sticky-header d-flex justify-content-between align-items-center">
                <!-- Barra di ricerca prodotti testuale -->
                <div class="search-wrapper">
                    <!-- Icona lente di ingrandimento interna all'input -->
                    <i class="fas fa-search search-icon"></i>
                    <!-- Input testuale che filtra i prodotti in tempo reale tramite JS ('renderProdotti()') -->
                    <input type="text" id="search-bar" class="search-input" placeholder="Cerca un piatto..." oninput="renderProdotti()">
                </div>

                <!-- Gruppo di pulsanti sulla destra dell'intestazione fissa -->
                <div class="d-flex align-items-center justify-content-end gap-2">
                    <!-- Contatore in tempo reale del totale carrello stimato (Nascosto su mobile piccolo) -->
                    <div class="d-none d-sm-flex align-items-center me-2 bg-surface rounded-pill px-3 py-2 border shadow-sm">
                        <small class="text-uppercase fw-bold text-muted me-2" style="font-size: 10px;">Conto Stimato</small>
                        <!-- Valore numerico aggiornato dinamicamente in base ai click sui prodotti -->
                        <div class="fw-bold fs-5 text-price price-stable"><span id="soldi-header">0.00</span>€</div>
                    </div>

                    <!-- Pulsante apertura modale con lo storico ordini gia' confermati dalla cucina -->
                    <button class="btn btn-dark rounded-pill px-3 py-2 px-md-4 py-md-3 shadow-sm d-flex align-items-center" onclick="apriStorico()">
                        <i class="fas fa-receipt"></i>
                        <span class="d-none d-lg-inline fw-bold ms-2">Storico Ordini</span>
                    </button>
                    <!-- Pulsante apertura modale per selezione allergeni da filtrare (escludere dalla griglia) -->
                    <button class="btn btn-dark rounded-pill px-3 py-2 px-md-4 py-md-3 shadow-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#modalFiltri">
                        <i class="fas fa-filter"></i>
                        <span class="d-none d-lg-inline fw-bold ms-2">Filtra</span>
                    </button>
                    <!-- Pulsante apertura carrello ordini pendenti da inviare -->
                    <button class="btn btn-dark rounded-pill px-3 py-2 px-md-4 py-md-3 shadow-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#modalCarrello" onclick="aggiornaModale()">
                        <i class="fas fa-shopping-bag fa-lg"></i>
                        <span class="d-none d-lg-inline fw-bold ms-2">Carrello</span>
                        <!-- Badge quantita totale di elementi caricati in carrello pendente -->
                        <span id="pezzi-header" class="ms-1">0</span>
                    </button>

                    <!-- Pulsanti icona compatte per tema e logout, visibili solo su mobile al posto della sidebar -->
                    <div class="d-md-none" onclick="toggleTheme()" style="width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;background:var(--input-bg);border:1px solid var(--border-color);color:var(--text-muted);">
                        <i class="fas fa-moon" style="font-size:0.85rem;"></i>
                    </div>
                    <a href="../logout.php" class="d-md-none" style="width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:rgba(255,71,87,0.1);border:1px solid var(--border-color);color:#e74c3c;text-decoration:none;">
                        <i class="fas fa-sign-out-alt" style="font-size:0.85rem;"></i>
                    </a>
                </div>
            </div>

            <!-- Corpo principale: scrollabile contenente le griglie -->
            <div class="p-4 pb-5">
                <!-- Barra Categorie a scorrimento laterale per mobile (nascosta su schermi larghi) -->
                <div class="mobile-cat-bar d-md-none mb-3">
                    <div class="mobile-cat-btn active" onclick="filtraCategoria('all', this)">Tutto</div>
                    <!-- Generazione pulsanti macrocategorie orizzontali -->
                    <?php
                    $catMobile = $conn->query("SELECT * FROM categorie");
                    while ($cm = $catMobile->fetch_assoc()): ?>
                        <div class="mobile-cat-btn" onclick="filtraCategoria(<?php echo $cm['id_categoria']; ?>, this)">
                            <?php echo $cm['nome_categoria']; ?>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Griglia di schede (Card) di tutti i piatti -->
                <div class="row g-4">
                    <!-- Fetch di tutti i record menu estratti precedentemente -->
                    <?php while ($p = $prodotti->fetch_assoc()): ?>
                        <!-- Colonna singola della griglia bootstrap, si auto adatta responsive -->
                        <div class="col-sm-6 col-md-6 col-lg-4 col-xl-3 item-prodotto" data-cat="<?php echo $p['id_categoria']; ?>">
                            
                            <!-- Wrapper strutturale di una card alimento (click trigger zoom details) -->
                            <div class="card-prodotto" onclick="apriZoom(event, this)" 
                                data-id="<?php echo $p['id_alimento']; ?>" 
                                data-nome="<?php echo htmlspecialchars($p['nome_piatto']); ?>" 
                                data-desc="<?php echo htmlspecialchars($p['descrizione']); ?>" 
                                data-prezzo="<?php echo $p['prezzo']; ?>" 
                                data-img="<?php echo $p['immagine'] ? 'data:image/jpeg;base64,' . base64_encode($p['immagine']) : ''; ?>" 
                                data-allergeni="<?php echo htmlspecialchars($p['lista_allergeni']); ?>">

                                <!-- Crop header immagine -->
                                <div class="img-wrapper">
                                    <!-- Sorgente immagine o foto caricata su blob base64 -->
                                    <img src="<?php echo $p['immagine'] ? 'data:image/jpeg;base64,' . base64_encode($p['immagine']) : ''; ?>" class="img-prodotto" loading="lazy">
                                    <!-- Badge del prezzo overlay sopra la foto in alto a dx -->
                                    <div class="price-tag"><?php echo $p['prezzo']; ?>€</div>
                                </div>

                                <!-- Box inferio contenuto informativo card -->
                                <div class="card-body">
                                    <h5 class="piatto-title"><?php echo $p['nome_piatto']; ?></h5>
                                    <!-- Didascalia (Limitata tramite css line-clamp) -->
                                    <p class="piatto-desc"><?php echo $p['descrizione']; ?></p>

                                    <!-- Pillole Allergeni informativi del piatto -->
                                    <div class="mb-4" style="min-height: 25px;">
                                        <?php
                                        // Estrapola gli array di allergeni divisi colonna "," in DB
                                        $allergeni = explode(',', $p['lista_allergeni']);
                                        foreach ($allergeni as $a) {
                                            // Stampa singola pillola design pattern se valida
                                            if (trim($a) != "")
                                                echo "<span class='badge-alg'>" . trim($a) . "</span>";
                                        }
                                        ?>
                                    </div>

                                    <!-- Padiglione bottoni + e - e display current cart qty per quel piatto -->
                                    <div class=" mt-auto d-flex justify-content-center align-items-center pt-3" style="border-top: 1px solid var(--border-color);">
                                        <div class="qty-capsule-card d-flex align-items-center justify-content-between" style="background: var(--capsule-bg); border-radius: 15px; padding: 6px; width: 100%;">
                                            <!-- Bottone diminuisce quantità (intercetta evento click per non propagarsi e fare trigger allo zoom modale - pointer stop propagation JS) -->
                                            <button class="btn-card-qty" onclick="btnCardQty(event, <?php echo $p['id_alimento']; ?>, -1, <?php echo $p['prezzo']; ?>, '<?php echo addslashes($p['nome_piatto']); ?>')">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <!-- Display qty span agganciato per ID js dom -->
                                            <span id="q-<?php echo $p['id_alimento']; ?>" class="fw-bold fs-5" style="min-width: 30px; text-align: center;">0</span>
                                            <!-- Increment button intercettato -->
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
            </div>
        </div>
    </div>
</div>

<!-- Inclusione script HTML Popups specifici tavolo (Gestione checkout, Zoom view, Storico) -->
<?php include "../include/modals/tavolo_modals.php"; ?>

<!-- Inclusione utilities JS javascript motor -->
<script src="../js/common.js"></script>
<!-- Inclusione script pesanti della UI customer menu (refresh cache var) -->
<script src="../js/tavolo.js?v=<?php echo time(); ?>"></script>
<!-- Footer tag chiusure -->
<?php include "../include/footer.php"; ?>