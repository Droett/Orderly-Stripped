<?php
require_once __DIR__ . '/../constants.php';
$cart      = $_SESSION['cart'] ?? [];
$cartEmpty = empty($cart);
$modalTotal = 0;
foreach ($cart as $item) {
    $modalTotal += $item['qta'] * $item['prezzo'];
}
?>

<div class="modal fade" id="modalOrdini" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content modal-content-custom shadow-lg">
            <div class="modal-header border-0 p-4 pb-2">
                <div>
                    <h3 class="modal-title fw-bold">I tuoi ordini</h3>
                    <p class="m-0 text-muted">Controlla lo stato delle tue ordinazioni</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-2" style="min-height: 300px; max-height: 60vh; overflow-y: auto;">
                <?php echo $storicoHtml; ?>
            </div>
            <div class="modal-footer border-0 p-4 bg-light-custom d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-uppercase fw-bold text-muted">Totale già ordinato</small>
                    <h2 class="m-0 fw-bold text-price price-stable"><?php echo $totaleStorico; ?>€</h2>
                </div>
                <button type="button" class="btn btn-dark rounded-pill px-5 py-3 fw-bold" data-bs-dismiss="modal">CHIUDI</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCarrello" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modal-content-custom shadow-lg">
            <div class="modal-header border-0 p-4 pb-2">
                <div>
                    <h3 class="modal-title fw-bold">Riepilogo ordine</h3>
                    <p class="m-0 text-muted">Controlla il tuo ordine prima di inviarlo</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" style="min-height: 300px;">
                <?php if ($cartEmpty): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-shopping-bag fa-3x mb-3" style="opacity:.3"></i>
                        <h5>Il carrello è vuoto</h5>
                        <p class="small">Aggiungi piatti per iniziare</p>
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush px-3">
                        <?php foreach ($cart as $id => $item): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-2 py-3">
                                <div>
                                    <strong><?php echo htmlspecialchars($item['nome']); ?></strong><br>
                                    <small class="text-muted"><?php echo number_format($item['prezzo'], 2); ?>€ cad.</small>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="qty-capsule" style="width:120px;">
                                        <form method="POST" action="tavolo.php" style="display:contents">
                                            <input type="hidden" name="action" value="remove_item">
                                            <input type="hidden" name="id_alimento" value="<?php echo $id; ?>">
                                            <button type="submit" class="btn-circle btn-minus"><i class="fas fa-minus"></i></button>
                                        </form>
                                        <span class="qty-input"><?php echo $item['qta']; ?></span>
                                        <form method="POST" action="tavolo.php" style="display:contents">
                                            <input type="hidden" name="action" value="add_item">
                                            <input type="hidden" name="id_alimento" value="<?php echo $id; ?>">
                                            <button type="submit" class="btn-circle btn-plus"><i class="fas fa-plus"></i></button>
                                        </form>
                                    </div>
                                    <strong class="text-price"><?php echo number_format($item['qta'] * $item['prezzo'], 2); ?>€</strong>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <div class="modal-footer border-0 p-4 d-flex justify-content-between align-items-center bg-light-custom">
                <div>
                    <small class="text-uppercase fw-bold text-muted">Costo attuale</small>
                    <h2 class="m-0 fw-bold text-price price-stable"><?php echo number_format($modalTotal, 2); ?>€</h2>
                </div>
                <button id="btn-invia-ordine" class="btn btn-dark rounded-pill px-5 py-3 fs-5 fw-bold shadow"
                        <?php echo $cartEmpty ? 'disabled' : 'data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalConfermaOrdine"'; ?>>
                    Invia ordine <i class="fas fa-paper-plane ms-2"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalFiltri" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom shadow-lg">
            <div class="modal-header border-0 p-4 pb-2">
                <div>
                    <h3 class="modal-title fw-bold">Gestione Intolleranze</h3>
                    <p class="m-0 text-muted">Seleziona cosa NON puoi mangiare</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4 pt-2">
                <div class="row g-2" id="lista-allergeni-filtro">
                    <?php foreach ($ALLERGENI as $a):
                        $safeId = str_replace(' ', '_', $a); ?>
                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2 p-2 border rounded" style="cursor:pointer">
                                <input class="form-check-input m-0 flex-shrink-0" type="checkbox" value="<?php echo $a; ?>" id="f_<?php echo $safeId; ?>">
                                <label class="form-check-label fw-bold w-100 m-0" for="f_<?php echo $safeId; ?>" style="cursor:pointer"><?php echo $a; ?></label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="modal-footer border-0 p-4 bg-light-custom justify-content-between">
                <button type="button" class="btn btn-link text-muted" onclick="resettaFiltriAllergeni()">Resetta Filtri</button>
                <button type="button" class="btn btn-dark rounded-pill px-5 fw-bold" onclick="applicaFiltriAllergeni()" data-bs-dismiss="modal">SALVA</button>
            </div>
        </div>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 start-50 translate-middle-x p-3">
    <div id="liveToast" class="toast align-items-center text-white bg-success border-0 shadow-lg" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-bold">
                <i class="fas fa-check-circle me-2"></i> <span id="toast-msg">Operazione riuscita!</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<div class="modal fade" id="modalConfermaOrdine" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom shadow-lg text-center p-4">
            <div class="mb-3"><i class="fas fa-question-circle fa-4x text-warning"></i></div>
            <h3 class="fw-bold mb-2">Confermi l'ordine?</h3>
            <p class="text-muted mb-4">L'ordine verrà inviato in cucina e non potrà essere annullato.</p>
            <div class="d-flex justify-content-center gap-3">
                <button type="button" class="btn btn-light rounded-pill px-4 py-2 fw-bold shadow-sm" data-bs-dismiss="modal">Annulla</button>
                <form method="POST" action="tavolo.php" style="display:contents">
                    <input type="hidden" name="action" value="invia_ordine">
                    <button type="submit" class="btn btn-dark rounded-pill px-4 py-2 fw-bold shadow-sm">SÌ, ORDINA!</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSuccesso" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom border-0 shadow-lg text-center p-5">
            <div class="mb-4"><i class="fas fa-check-circle fa-5x text-success"></i></div>
            <h2 class="fw-bold mb-2">Comanda Inviata!</h2>
            <p class="text-muted">Il tuo ordine è stato ricevuto. Buon appetito!</p>
            <button class="btn btn-dark rounded-pill px-5 py-2 mt-3" data-bs-dismiss="modal">Perfetto!</button>
        </div>
    </div>
</div>
