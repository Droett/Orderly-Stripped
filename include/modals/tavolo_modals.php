<?php require_once __DIR__ . '/../constants.php'; ?> 

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
            <div class="modal-body p-4 pt-2" id="corpo-ordini" style="min-height: 300px; max-height: 60vh; overflow-y: auto;"></div> 
            <div class="modal-footer border-0 p-4 bg-light-custom d-flex justify-content-between align-items-center"> 
                <div> 
                    <small class="text-uppercase fw-bold text-muted">Totale già ordinato</small> 
                    <h2 class="m-0 fw-bold text-price price-stable"><span id="totale-storico">0.00</span>€</h2> 
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
            <div class="modal-body p-0" id="corpo-carrello" style="min-height: 300px;"></div> 
            <div class="modal-footer border-0 p-4 d-flex justify-content-between align-items-center bg-light-custom"> 
                <div> 
                    <small class="text-uppercase fw-bold text-muted">Costo attuale</small> 
                    <h2 class="m-0 fw-bold text-price price-stable"><span id="totale-modale">0.00</span>€</h2> 
                </div> 
                <button id="btn-invia-ordine" class="btn btn-dark rounded-pill px-5 py-3 fs-5 fw-bold shadow" disabled> 
                    Invia ordine <i class="fas fa-paper-plane ms-2"></i> 
                </button> 
            </div> 
        </div> 
    </div> 
</div> 

<div class="modal fade" id="modalZoom" tabindex="-1"> 
    <div class="modal-dialog modal-dialog-centered modal-xl"> 
        <div class="modal-content modal-content-custom shadow-lg overflow-hidden"> 
            <div class="modal-body p-0"> 
                <div class="row g-0"> 
                    <div class="col-lg-6 position-relative bg-light-custom" style="min-height: 350px; overflow:hidden;"> 
                        <img id="zoom-img" src="" class="w-100 h-100" style="object-fit: cover; position: absolute; top:0; left:0;"> 
                    </div> 
                    <div class="col-lg-6 p-4 p-md-5 d-flex flex-column"> 
                        <div class="d-flex justify-content-between align-items-start mb-3"> 
                            <span class="badge bg-warning text-dark fs-6 rounded-pill px-3">APPROFONDIMENTO</span> 
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button> 
                        </div> 
                        <h1 class="fw-bold mb-2" id="zoom-nome">Nome Piatto</h1> 
                        <h4 class="fw-bold mb-4 text-muted"><span id="zoom-prezzo-unitario">0.00</span>€</h4> 
                        <p class="lead mb-4 text-muted flex-grow-1" id="zoom-desc">Descrizione...</p> 

                        <div class="mb-4"> 
                            <h6 class="text-uppercase small fw-bold mb-2 text-muted">Allergeni</h6> 
                            <div id="zoom-allergeni"></div> 
                        </div> 

                        <div class="mb-4 flex-grow-1"> 
                            <h6 class="text-uppercase small fw-bold mb-2 text-muted">Note per lo Chef 
                                <small>(opz.)</small> 
                            </h6> 
                            <textarea class="form-control rounded-3" id="zoom-note" rows="2" placeholder="Es. Ben cotto, senza salse..." style="resize: none; background: #f8f9fa; border: 1px solid #ddd;"></textarea> 
                        </div> 

                        <div class="mt-auto pt-3 border-top"> 
                            <div class="d-flex justify-content-between align-items-center mb-3"> 
                                <span class="fw-bold fs-5">Seleziona quantità</span> 
                                <div class="qty-capsule" style="width: 140px;"> 
                                    <button class="btn-circle btn-minus" onclick="updateZoomQty(-1)"><i class="fas fa-minus"></i></button> 
                                    <span class="qty-input" id="zoom-qty-display">1</span> 
                                    <button class="btn-circle btn-plus" onclick="updateZoomQty(1)"><i class="fas fa-plus"></i></button> 
                                </div> 
                            </div> 
                            <button class="btn btn-green-custom w-100 rounded-pill py-3 fw-bold fs-5 shadow-sm d-flex justify-content-between px-4" id="btn-zoom-add" onclick="confermaZoom()"> 
                                <span>Aggiungi</span> 
                                <span id="zoom-btn-totale">0.00€</span> 
                            </button> 
                        </div> 
                    </div> 
                </div> 
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
                <button type="button" class="btn btn-dark rounded-pill px-4 py-2 fw-bold shadow-sm" id="confirm-send-btn">SÌ, ORDINA!</button> 
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